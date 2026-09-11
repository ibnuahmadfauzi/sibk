<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Dapodik\DapodikSnapshot;
use App\Integrations\IntegrationConfigurationProvider;
use App\Integrations\IntegrationDriverRegistry;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\DapodikSyncPreviewItem;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class DapodikReconciliationService
{
    public const int PREVIEW_TTL_HOURS = 24;

    public function __construct(
        private readonly AuditService $auditService,
        private readonly IntegrationOperationLock $operationLock,
        private readonly IntegrationConfigurationProvider $configurationProvider,
        private readonly IntegrationDriverRegistry $drivers,
        private readonly StudentIdentityService $studentIdentityService,
        private readonly EtatibSyncService $etatibSyncService,
    ) {}

    public function createPreview(
        DapodikSnapshot $snapshot,
        ExternalSyncRun $run,
        IntegrationSetting $setting,
        IntegrationOperationContext $context,
        ?User $actor = null,
    ): ExternalSyncRun {
        $this->operationLock->assertCurrent($context, $setting);

        $generation = ((int) ExternalSyncRun::query()
            ->where('source', IntegrationSetting::PROVIDER_DAPODIK)
            ->whereKeyNot($run->getKey())
            ->max('preview_generation')) + 1;

        $superseded = ExternalSyncRun::query()
            ->where('source', IntegrationSetting::PROVIDER_DAPODIK)
            ->where('status', ExternalSyncRun::STATUS_PREVIEW_READY)
            ->lockForUpdate()
            ->get();
        foreach ($superseded as $previousRun) {
            $previousRun->update([
                'status' => ExternalSyncRun::STATUS_SUPERSEDED,
                'summary' => 'Pratinjau digantikan oleh snapshot Dapodik yang lebih baru.',
                'superseded_at' => now(),
            ]);
            $this->auditService->record(
                action: 'dapodik.preview_superseded',
                auditable: $previousRun,
                summary: 'Keputusan pratinjau lama dibatalkan karena snapshot baru tersedia.',
                actor: $actor,
                after: ['preview_generation' => $previousRun->preview_generation],
            );
        }

        /** @var array<string, array{candidate_id: ?int, match_status: string}> $yearMatches */
        $yearMatches = [];
        /** @var array<string, array{candidate_id: ?int, match_status: string}> $classroomMatches */
        $classroomMatches = [];
        /** @var array<string, array{candidate_id: ?int, match_status: string}> $studentMatches */
        $studentMatches = [];
        /** @var array<string, array<string, mixed>> $yearFields */
        $yearFields = [];
        $items = [];

        foreach ($snapshot->academicYears as $fields) {
            $safeFields = $this->normalizeAcademicYear($fields);
            $match = $this->classifyAcademicYear($safeFields);
            $yearMatches[$safeFields['source_id']] = $match;
            $yearFields[$safeFields['source_id']] = $safeFields;
            $items[] = $this->previewItem(
                DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR,
                $safeFields,
                $match,
                $generation,
            );
        }

        foreach ($snapshot->classrooms as $fields) {
            $safeFields = $this->normalizeClassroom($fields);
            $match = $this->classifyClassroom(
                $safeFields,
                $yearMatches[$safeFields['academic_year_source_id']] ?? null,
            );
            $classroomMatches[$safeFields['source_id']] = $match;
            $items[] = $this->previewItem(
                DapodikSyncPreviewItem::ENTITY_CLASSROOM,
                $safeFields,
                $match,
                $generation,
            );
        }

        foreach ($snapshot->students as $fields) {
            $safeFields = $this->normalizeStudent($fields);
            $match = $this->classifyStudent($safeFields);
            $studentMatches[$safeFields['source_id']] = $match;
            $items[] = $this->previewItem(
                DapodikSyncPreviewItem::ENTITY_STUDENT,
                $safeFields,
                $match,
                $generation,
            );
        }

        foreach ($snapshot->memberships as $fields) {
            $safeFields = $this->normalizeMembership($fields);
            $match = $this->classifyMembership(
                $safeFields,
                $studentMatches[$safeFields['student_source_id']] ?? null,
                $classroomMatches[$safeFields['classroom_source_id']] ?? null,
                $yearMatches[$safeFields['academic_year_source_id']] ?? null,
                $yearFields[$safeFields['academic_year_source_id']] ?? null,
            );
            $items[] = $this->previewItem(
                DapodikSyncPreviewItem::ENTITY_MEMBERSHIP,
                $safeFields,
                $match,
                $generation,
            );
        }

        $snapshotEvidence = $this->snapshotEvidence($snapshot);
        $deactivationPlan = $snapshot->isFullSnapshot
            ? $this->buildDeactivationPlan([
                DapodikSyncPreviewItem::ENTITY_CLASSROOM => array_column($snapshot->classrooms, 'source_id'),
                DapodikSyncPreviewItem::ENTITY_STUDENT => array_column($snapshot->students, 'source_id'),
                DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => array_column($snapshot->memberships, 'source_id'),
            ])
            : [];
        $fingerprint = $this->previewFingerprint(
            $snapshot->isFullSnapshot,
            $snapshotEvidence,
            array_column($items, 'item_hash'),
            $deactivationPlan,
        );
        $conflictCount = count(array_filter(
            $items,
            static fn (array $item): bool => $item['match_status'] === DapodikSyncPreviewItem::MATCH_CONFLICT,
        ));

        $run->update([
            'status' => ExternalSyncRun::STATUS_PREVIEW_READY,
            'is_full_snapshot' => $snapshot->isFullSnapshot,
            'received_count' => $snapshot->recordCount(),
            'processed_count' => 0,
            'conflict_count' => $conflictCount,
            'summary' => $conflictCount > 0
                ? sprintf('Pratinjau Dapodik siap dengan %d konflik yang harus diperiksa.', $conflictCount)
                : 'Pratinjau Dapodik siap diperiksa sebelum diterapkan.',
            'snapshot_fingerprint' => $fingerprint,
            'snapshot_evidence' => $snapshotEvidence,
            'deactivation_plan' => $deactivationPlan,
            'preview_generation' => $generation,
            'decision_revision' => 0,
            'configuration_version' => $setting->configuration_version,
            'preview_fencing_token' => $context->fencingToken,
            'driver_id' => $setting->verified_driver_id,
            'adapter_version' => $setting->verified_adapter_version,
            'contract_version' => $setting->verified_contract_version,
            'endpoint_policy_digest' => $setting->verified_endpoint_policy_digest,
            'preview_expires_at' => now()->addHours(self::PREVIEW_TTL_HOURS),
            'finished_at' => now(),
        ]);

        foreach ($items as $item) {
            $run->previewItems()->create($item);
        }

        $this->auditService->record(
            action: 'dapodik.preview_created',
            auditable: $run,
            summary: 'Snapshot Dapodik tervalidasi dan disimpan sebagai pratinjau tanpa mengubah data operasional.',
            actor: $actor,
            after: [
                'preview_generation' => $generation,
                'received_count' => $snapshot->recordCount(),
                'conflict_count' => $conflictCount,
                'is_full_snapshot' => $snapshot->isFullSnapshot,
                'snapshot_fingerprint' => $fingerprint,
            ],
        );
        $this->operationLock->assertCurrent($context, $setting);

        return $run->refresh();
    }

    /** @param array{decision: string, candidate_id?: int|null, decision_revision: int} $data */
    public function decide(
        ExternalSyncRun $run,
        DapodikSyncPreviewItem $item,
        array $data,
        User $actor,
    ): DapodikSyncPreviewItem {
        Gate::forUser($actor)->authorize('manageDataMaster');

        return $this->operationLock->run(
            IntegrationSetting::PROVIDER_DAPODIK,
            function (IntegrationOperationContext $context) use ($run, $item, $data, $actor): DapodikSyncPreviewItem {
                return DB::transaction(function () use ($run, $item, $data, $actor, $context): DapodikSyncPreviewItem {
                    $setting = IntegrationSetting::query()
                        ->where('provider', IntegrationSetting::PROVIDER_DAPODIK)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->operationLock->assertCurrent($context, $setting);
                    $currentRun = ExternalSyncRun::query()->lockForUpdate()->findOrFail($run->getKey());
                    $currentItem = DapodikSyncPreviewItem::query()->lockForUpdate()->findOrFail($item->getKey());
                    $this->assertCurrentPreview($currentRun);
                    if ($currentItem->external_sync_run_id !== $currentRun->getKey()
                        || $currentItem->preview_generation !== $currentRun->preview_generation
                    ) {
                        $this->validationError('item', 'Item tidak berasal dari pratinjau Dapodik yang dipilih.');
                    }
                    if ($currentItem->match_status !== DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
                        || ! in_array($currentItem->entity_type, [
                            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR,
                            DapodikSyncPreviewItem::ENTITY_CLASSROOM,
                        ], true)
                    ) {
                        $this->validationError('item', 'Item ini tidak dapat dipetakan secara manual.');
                    }
                    if ($currentItem->decision_revision !== (int) $data['decision_revision']) {
                        $this->validationError('decision_revision', 'Keputusan telah berubah. Muat ulang pratinjau.');
                    }

                    $decision = $data['decision'];
                    $candidateId = null;
                    $candidateFingerprint = null;
                    if ($decision === DapodikSyncPreviewItem::DECISION_MAP_EXISTING) {
                        $candidateId = (int) ($data['candidate_id'] ?? 0);
                        $candidate = $this->allowedManualCandidate($currentRun, $currentItem, $candidateId);
                        $candidateFingerprint = $this->targetFingerprint($currentItem->entity_type, $candidate);
                    } elseif ($decision !== DapodikSyncPreviewItem::DECISION_CREATE_NEW) {
                        $this->validationError('decision', 'Keputusan pemetaan tidak diizinkan.');
                    }

                    $currentItem->update([
                        'decision' => $decision,
                        'decision_candidate_id' => $candidateId,
                        'decision_target_fingerprint' => $candidateFingerprint,
                        'decision_revision' => $currentItem->decision_revision + 1,
                        'decided_by' => $actor->getKey(),
                        'decided_at' => now(),
                    ]);
                    $currentRun->increment('decision_revision');
                    $this->auditService->record(
                        action: 'dapodik.preview_decided',
                        auditable: $currentRun,
                        summary: 'Admin IT menetapkan keputusan pencocokan pratinjau Dapodik.',
                        actor: $actor,
                        after: [
                            'item_id' => $currentItem->getKey(),
                            'entity_type' => $currentItem->entity_type,
                            'decision' => $decision,
                            'decision_revision' => $currentItem->decision_revision,
                        ],
                    );
                    $this->operationLock->assertCurrent($context, $setting);

                    return $currentItem->refresh();
                });
            },
        );
    }

    public function apply(ExternalSyncRun $run, User $actor, int $expectedDecisionRevision): ExternalSyncRun
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        return $this->operationLock->run(
            IntegrationSetting::PROVIDER_DAPODIK,
            function (IntegrationOperationContext $context) use ($run, $actor, $expectedDecisionRevision): ExternalSyncRun {
                return DB::transaction(function () use ($run, $actor, $expectedDecisionRevision, $context): ExternalSyncRun {
                    $setting = IntegrationSetting::query()
                        ->where('provider', IntegrationSetting::PROVIDER_DAPODIK)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->operationLock->assertCurrent($context, $setting);
                    $currentRun = ExternalSyncRun::query()->lockForUpdate()->findOrFail($run->getKey());
                    $this->assertCurrentPreview($currentRun);
                    if ($currentRun->decision_revision !== $expectedDecisionRevision) {
                        $this->validationError('decision_revision', 'Keputusan pratinjau telah berubah. Muat ulang halaman.');
                    }

                    $driver = $this->drivers->dapodik();
                    if ($currentRun->configuration_version === null
                        || ! is_string($currentRun->driver_id)
                        || ! is_string($currentRun->adapter_version)
                        || ! is_string($currentRun->contract_version)
                        || ! is_string($currentRun->endpoint_policy_digest)
                        || $currentRun->driver_id !== $driver->id()
                        || $currentRun->adapter_version !== $driver->adapterVersion()
                        || $currentRun->contract_version !== $driver->contractVersion()
                        || $currentRun->endpoint_policy_digest !== $setting->verified_endpoint_policy_digest
                    ) {
                        $this->validationError('preview', 'Konfigurasi atau policy berubah setelah pratinjau dibuat.');
                    }
                    $this->configurationProvider->assertCurrent(
                        IntegrationSetting::PROVIDER_DAPODIK,
                        $currentRun->configuration_version,
                        $currentRun->driver_id,
                        $currentRun->adapter_version,
                        $currentRun->contract_version,
                        $context,
                    );

                    $items = DapodikSyncPreviewItem::query()
                        ->where('external_sync_run_id', $currentRun->getKey())
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();
                    $this->validateItemsForApply($currentRun, $items);
                    $this->validateResolvedGraph($items);
                    $this->validateTargetOwnership($items);
                    $this->validateDeactivationPlan($currentRun, $items);

                    $syncedAt = now();
                    $appliedIds = $this->applyItems($currentRun, $items, $actor, $syncedAt);
                    if ($currentRun->is_full_snapshot) {
                        $this->applyDeactivationPlan($currentRun, $actor);
                        $this->recordUnmatchedLocalIssues($currentRun, $appliedIds);
                    }
                    $this->studentIdentityService->reconcilePending($actor);
                    $this->etatibSyncService->reconcileStudentLinks($actor);

                    $issueCount = $currentRun->issues()->whereNull('resolved_at')->count();
                    $currentRun->update([
                        'status' => $issueCount > 0
                            ? ExternalSyncRun::STATUS_WARNING
                            : ExternalSyncRun::STATUS_SUCCEEDED,
                        'processed_count' => $items->count(),
                        'conflict_count' => $issueCount,
                        'summary' => $issueCount > 0
                            ? sprintf('Hasil Dapodik diterapkan; %d data lokal perlu diperiksa.', $issueCount)
                            : 'Hasil pencocokan Dapodik berhasil diterapkan.',
                        'applied_at' => $syncedAt,
                        'finished_at' => $syncedAt,
                    ]);
                    $this->auditService->record(
                        action: 'dapodik.preview_applied',
                        auditable: $currentRun,
                        summary: 'Pratinjau Dapodik diterapkan secara atomik pada ID internal yang sama.',
                        actor: $actor,
                        after: [
                            'preview_generation' => $currentRun->preview_generation,
                            'processed_count' => $items->count(),
                            'issue_count' => $issueCount,
                            'snapshot_fingerprint' => $currentRun->snapshot_fingerprint,
                        ],
                    );
                    $setting->refresh();
                    $this->operationLock->assertCurrent($context, $setting);

                    return $currentRun->refresh();
                });
            },
        );
    }

    /**
     * @param  Collection<int, DapodikSyncPreviewItem>  $items
     */
    private function validateItemsForApply(ExternalSyncRun $run, Collection $items): void
    {
        if ($items->count() !== $run->received_count || $items->isEmpty()) {
            $this->validationError('preview', 'Pratinjau Dapodik tidak lengkap.');
        }
        $hashes = [];
        foreach ($items as $item) {
            if ($item->preview_generation !== $run->preview_generation
                || $item->external_sync_run_id !== $run->getKey()
            ) {
                $this->validationError('preview', 'Generasi item pratinjau tidak konsisten.');
            }
            $expectedHash = hash('sha256', $this->canonicalJson([
                'entity_type' => $item->entity_type,
                'safe_fields' => $item->safe_fields,
            ]));
            if (! hash_equals($expectedHash, $item->item_hash)) {
                $this->validationError('preview', 'Hash item pratinjau telah berubah.');
            }
            $hashes[] = $item->item_hash;

            if ($item->match_status === DapodikSyncPreviewItem::MATCH_CONFLICT) {
                $this->validationError('preview', 'Konflik pratinjau harus diselesaikan melalui snapshot yang sah.');
            }
            if ($item->match_status === DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
                && ! in_array($item->decision, [
                    DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
                    DapodikSyncPreviewItem::DECISION_CREATE_NEW,
                ], true)
            ) {
                $this->validationError('preview', 'Seluruh item yang meragukan harus diberi keputusan.');
            }

            $selectedCandidateId = $this->selectedCandidateId($item);
            $selectedFingerprint = $this->selectedTargetFingerprint($item);
            if ($selectedCandidateId !== null) {
                $target = $this->findTarget($item->entity_type, $selectedCandidateId, true);
                if (! is_string($selectedFingerprint)
                    || ! hash_equals($selectedFingerprint, $this->targetFingerprint($item->entity_type, $target))
                ) {
                    $this->validationError('preview', 'Data target berubah setelah pratinjau dibuat.');
                }
            } elseif ($item->match_status !== DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
                || $item->decision === DapodikSyncPreviewItem::DECISION_CREATE_NEW
            ) {
                $this->assertNoSourceTargetAppeared($item);
            }
        }
        if (! is_array($run->snapshot_evidence) || ! is_array($run->deactivation_plan)) {
            $this->validationError('preview', 'Metadata pratinjau Dapodik tidak lengkap.');
        }
        $fingerprint = $this->previewFingerprint(
            $run->is_full_snapshot,
            $run->snapshot_evidence,
            $hashes,
            $run->deactivation_plan,
        );
        if (! is_string($run->snapshot_fingerprint)
            || ! hash_equals($run->snapshot_fingerprint, $fingerprint)
        ) {
            $this->validationError('preview', 'Fingerprint snapshot Dapodik tidak konsisten.');
        }
    }

    /** @param Collection<int, DapodikSyncPreviewItem> $items @return array<string, list<int>> */
    private function applyItems(
        ExternalSyncRun $run,
        Collection $items,
        User $actor,
        mixed $syncedAt,
    ): array {
        $applied = [
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR => [],
            DapodikSyncPreviewItem::ENTITY_CLASSROOM => [],
            DapodikSyncPreviewItem::ENTITY_STUDENT => [],
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => [],
        ];
        $yearIds = [];
        $classroomIds = [];
        $studentIds = [];

        foreach ($items->where('entity_type', DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR) as $item) {
            $fields = $item->safe_fields;
            /** @var AcademicYear $target */
            $target = $this->selectedTarget($item) ?? new AcademicYear;
            $before = $target->exists ? $this->auditTarget($target) : null;
            $isActive = $target->exists ? $target->is_active : false;
            $target->fill([
                'dapodik_id' => $fields['source_id'],
                'name' => $fields['name'],
                'starts_on' => $fields['starts_on'],
                'ends_on' => $fields['ends_on'],
                'is_active' => $isActive,
                'synced_at' => $syncedAt,
                'master_source' => AcademicYear::MASTER_SOURCE_DAPODIK,
                'source_confirmed_at' => $syncedAt,
            ])->save();
            $yearIds[$fields['source_id']] = $target->getKey();
            $applied[DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR][] = $target->getKey();
            $this->auditAppliedTarget($run, $item, $target, $actor, $before);
        }

        foreach ($items->where('entity_type', DapodikSyncPreviewItem::ENTITY_CLASSROOM) as $item) {
            $fields = $item->safe_fields;
            $yearId = $yearIds[$fields['academic_year_source_id']] ?? null;
            if ($yearId === null) {
                $this->validationError('preview', 'Tahun ajaran untuk rombel tidak tersedia.');
            }
            /** @var Classroom $target */
            $target = $this->selectedTarget($item) ?? new Classroom;
            $before = $target->exists ? $this->auditTarget($target) : null;
            $target->fill([
                'dapodik_id' => $fields['source_id'],
                'academic_year_id' => $yearId,
                'name' => $fields['name'],
                'grade_level' => $fields['grade_level'],
                'major' => $fields['major'],
                'is_active' => $fields['is_active'],
                'synced_at' => $syncedAt,
                'master_source' => Classroom::MASTER_SOURCE_DAPODIK,
                'source_confirmed_at' => $syncedAt,
            ])->save();
            $classroomIds[$fields['source_id']] = $target->getKey();
            $applied[DapodikSyncPreviewItem::ENTITY_CLASSROOM][] = $target->getKey();
            $this->auditAppliedTarget($run, $item, $target, $actor, $before);
        }

        foreach ($items->where('entity_type', DapodikSyncPreviewItem::ENTITY_STUDENT) as $item) {
            $fields = $item->safe_fields;
            /** @var Student $target */
            $target = $this->selectedTarget($item) ?? new Student;
            $before = $target->exists ? $this->auditTarget($target) : null;
            $target->fill([
                'dapodik_id' => $fields['source_id'],
                'nisn' => $fields['nisn'],
                'name' => $fields['name'],
                'is_active' => $fields['is_active'],
                'synced_at' => $syncedAt,
                'master_source' => Student::MASTER_SOURCE_DAPODIK,
                'source_confirmed_at' => $syncedAt,
            ])->save();
            $studentIds[$fields['source_id']] = $target->getKey();
            $applied[DapodikSyncPreviewItem::ENTITY_STUDENT][] = $target->getKey();
            $this->auditAppliedTarget($run, $item, $target, $actor, $before);
        }

        foreach ($items->where('entity_type', DapodikSyncPreviewItem::ENTITY_MEMBERSHIP) as $item) {
            $fields = $item->safe_fields;
            $studentId = $studentIds[$fields['student_source_id']] ?? null;
            $classroomId = $classroomIds[$fields['classroom_source_id']] ?? null;
            $yearId = $yearIds[$fields['academic_year_source_id']] ?? null;
            if ($studentId === null || $classroomId === null || $yearId === null) {
                $this->validationError('preview', 'Relasi keanggotaan Dapodik tidak lengkap.');
            }
            /** @var StudentClassMembership $target */
            $target = $this->selectedTarget($item) ?? new StudentClassMembership;
            $before = $target->exists ? $this->auditTarget($target) : null;
            $target->fill([
                'dapodik_id' => $fields['source_id'],
                'student_id' => $studentId,
                'classroom_id' => $classroomId,
                'academic_year_id' => $yearId,
                'effective_from' => $fields['effective_from'],
                'effective_until' => $fields['effective_until'],
                'is_active' => $fields['is_active'],
                'synced_at' => $syncedAt,
                'master_source' => StudentClassMembership::MASTER_SOURCE_DAPODIK,
                'source_confirmed_at' => $syncedAt,
            ])->save();
            $applied[DapodikSyncPreviewItem::ENTITY_MEMBERSHIP][] = $target->getKey();
            $this->auditAppliedTarget($run, $item, $target, $actor, $before);
        }

        return $applied;
    }

    private function selectedTarget(DapodikSyncPreviewItem $item): ?Model
    {
        $candidateId = $this->selectedCandidateId($item);

        return $candidateId === null ? null : $this->findTarget($item->entity_type, $candidateId, true);
    }

    private function selectedCandidateId(DapodikSyncPreviewItem $item): ?int
    {
        if ($item->match_status === DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING) {
            return $item->decision === DapodikSyncPreviewItem::DECISION_MAP_EXISTING
                ? $item->decision_candidate_id
                : null;
        }

        return $item->candidate_id;
    }

    private function selectedTargetFingerprint(DapodikSyncPreviewItem $item): ?string
    {
        return $item->match_status === DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
            ? $item->decision_target_fingerprint
            : $item->target_fingerprint;
    }

    private function assertNoSourceTargetAppeared(DapodikSyncPreviewItem $item): void
    {
        $exists = (match ($item->entity_type) {
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR => AcademicYear::query(),
            DapodikSyncPreviewItem::ENTITY_CLASSROOM => Classroom::query(),
            DapodikSyncPreviewItem::ENTITY_STUDENT => Student::query(),
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => StudentClassMembership::query(),
            default => throw new \LogicException('Jenis item pratinjau tidak dikenal.'),
        })->where('dapodik_id', $item->source_identifier)->lockForUpdate()->exists();

        if ($exists) {
            $this->validationError('preview', 'Target baru muncul setelah pratinjau dibuat.');
        }
        if ($item->entity_type === DapodikSyncPreviewItem::ENTITY_STUDENT
            && Student::query()->where('nisn', $item->safe_fields['nisn'])->lockForUpdate()->exists()
        ) {
            $this->validationError('preview', 'NISN target berubah setelah pratinjau dibuat.');
        }
        if ($item->entity_type === DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR
            && AcademicYear::query()->where('name', $item->safe_fields['name'])->lockForUpdate()->exists()
        ) {
            $this->validationError('preview', 'Tahun ajaran target berubah setelah pratinjau dibuat.');
        }
    }

    /** @param Collection<int, DapodikSyncPreviewItem> $items */
    private function validateResolvedGraph(Collection $items): void
    {
        $byEntityAndSource = $items->keyBy(
            fn (DapodikSyncPreviewItem $item): string => $item->entity_type.':'.$item->source_identifier,
        );

        foreach ($items->where('entity_type', DapodikSyncPreviewItem::ENTITY_CLASSROOM) as $item) {
            $targetId = $this->selectedCandidateId($item);
            if ($targetId === null) {
                continue;
            }
            $yearItem = $byEntityAndSource->get(
                DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR.':'.$item->safe_fields['academic_year_source_id'],
            );
            $yearId = $yearItem instanceof DapodikSyncPreviewItem
                ? $this->selectedCandidateId($yearItem)
                : null;
            /** @var Classroom $target */
            $target = $this->findTarget(DapodikSyncPreviewItem::ENTITY_CLASSROOM, $targetId, true);
            if ($yearId === null || $target->academic_year_id !== $yearId) {
                $this->validationError('preview', 'Keputusan rombel tidak lagi sesuai dengan keputusan tahun ajaran.');
            }
        }

        foreach ($items->where('entity_type', DapodikSyncPreviewItem::ENTITY_MEMBERSHIP) as $item) {
            $targetId = $this->selectedCandidateId($item);
            if ($targetId === null) {
                continue;
            }
            $studentItem = $byEntityAndSource->get(
                DapodikSyncPreviewItem::ENTITY_STUDENT.':'.$item->safe_fields['student_source_id'],
            );
            $classroomItem = $byEntityAndSource->get(
                DapodikSyncPreviewItem::ENTITY_CLASSROOM.':'.$item->safe_fields['classroom_source_id'],
            );
            $yearItem = $byEntityAndSource->get(
                DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR.':'.$item->safe_fields['academic_year_source_id'],
            );
            $studentId = $studentItem instanceof DapodikSyncPreviewItem
                ? $this->selectedCandidateId($studentItem)
                : null;
            $classroomId = $classroomItem instanceof DapodikSyncPreviewItem
                ? $this->selectedCandidateId($classroomItem)
                : null;
            $yearId = $yearItem instanceof DapodikSyncPreviewItem
                ? $this->selectedCandidateId($yearItem)
                : null;
            /** @var StudentClassMembership $target */
            $target = $this->findTarget(DapodikSyncPreviewItem::ENTITY_MEMBERSHIP, $targetId, true);
            if ($studentId === null
                || $classroomId === null
                || $yearId === null
                || $target->student_id !== $studentId
                || $target->classroom_id !== $classroomId
                || $target->academic_year_id !== $yearId
            ) {
                $this->validationError('preview', 'Keanggotaan tidak lagi sesuai dengan keputusan murid, rombel, dan tahun ajaran.');
            }
        }
    }

    /** @param Collection<int, DapodikSyncPreviewItem> $items */
    private function validateTargetOwnership(Collection $items): void
    {
        $byEntityAndSource = $items->keyBy(
            fn (DapodikSyncPreviewItem $item): string => $item->entity_type.':'.$item->source_identifier,
        );

        foreach ($items as $item) {
            $candidateId = $this->selectedCandidateId($item);
            $sourceOwner = $this->targetQuery($item->entity_type)
                ->where('dapodik_id', $item->source_identifier);
            if ($candidateId !== null) {
                $sourceOwner->whereKeyNot($candidateId);
            }
            if ($sourceOwner->lockForUpdate()->exists()) {
                $this->validationError('preview', 'Identitas sumber telah dimiliki data lain setelah pratinjau dibuat.');
            }

            $naturalOwner = match ($item->entity_type) {
                DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR => AcademicYear::query()
                    ->where('name', $item->safe_fields['name']),
                DapodikSyncPreviewItem::ENTITY_CLASSROOM => $this->classroomNaturalOwnerQuery($item, $byEntityAndSource),
                DapodikSyncPreviewItem::ENTITY_STUDENT => Student::query()
                    ->where('nisn', $item->safe_fields['nisn']),
                DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => $this->membershipNaturalOwnerQuery($item, $byEntityAndSource),
                default => throw new \LogicException('Jenis item pratinjau tidak dikenal.'),
            };
            if ($naturalOwner === null) {
                continue;
            }
            if ($candidateId !== null) {
                $naturalOwner->whereKeyNot($candidateId);
            }
            if ($naturalOwner->lockForUpdate()->exists()) {
                $this->validationError('preview', 'Natural key telah dimiliki data lain setelah pratinjau dibuat.');
            }
        }
    }

    /** @param Collection<string, DapodikSyncPreviewItem> $byEntityAndSource */
    private function classroomNaturalOwnerQuery(
        DapodikSyncPreviewItem $item,
        Collection $byEntityAndSource,
    ): mixed {
        $yearItem = $byEntityAndSource->get(
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR.':'.$item->safe_fields['academic_year_source_id'],
        );
        $yearId = $yearItem instanceof DapodikSyncPreviewItem
            ? $this->selectedCandidateId($yearItem)
            : null;

        return $yearId === null
            ? null
            : Classroom::query()
                ->where('academic_year_id', $yearId)
                ->where('name', $item->safe_fields['name']);
    }

    /** @param Collection<string, DapodikSyncPreviewItem> $byEntityAndSource */
    private function membershipNaturalOwnerQuery(
        DapodikSyncPreviewItem $item,
        Collection $byEntityAndSource,
    ): mixed {
        $studentItem = $byEntityAndSource->get(
            DapodikSyncPreviewItem::ENTITY_STUDENT.':'.$item->safe_fields['student_source_id'],
        );
        $classroomItem = $byEntityAndSource->get(
            DapodikSyncPreviewItem::ENTITY_CLASSROOM.':'.$item->safe_fields['classroom_source_id'],
        );
        $studentId = $studentItem instanceof DapodikSyncPreviewItem
            ? $this->selectedCandidateId($studentItem)
            : null;
        $classroomId = $classroomItem instanceof DapodikSyncPreviewItem
            ? $this->selectedCandidateId($classroomItem)
            : null;
        if ($studentId === null || $classroomId === null) {
            return null;
        }

        return StudentClassMembership::query()
            ->where('student_id', $studentId)
            ->where('classroom_id', $classroomId)
            ->whereDate('effective_from', $item->safe_fields['effective_from']);
    }

    /** @param Collection<int, DapodikSyncPreviewItem> $items */
    private function validateDeactivationPlan(ExternalSyncRun $run, Collection $items): void
    {
        if (! $run->is_full_snapshot) {
            if ($run->deactivation_plan !== []) {
                $this->validationError('preview', 'Snapshot parsial tidak boleh memiliki rencana penonaktifan.');
            }

            return;
        }

        $currentPlan = $this->buildDeactivationPlan([
            DapodikSyncPreviewItem::ENTITY_CLASSROOM => $items
                ->where('entity_type', DapodikSyncPreviewItem::ENTITY_CLASSROOM)
                ->pluck('source_identifier')->values()->all(),
            DapodikSyncPreviewItem::ENTITY_STUDENT => $items
                ->where('entity_type', DapodikSyncPreviewItem::ENTITY_STUDENT)
                ->pluck('source_identifier')->values()->all(),
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => $items
                ->where('entity_type', DapodikSyncPreviewItem::ENTITY_MEMBERSHIP)
                ->pluck('source_identifier')->values()->all(),
        ]);
        if ($this->canonicalJson($currentPlan) !== $this->canonicalJson($run->deactivation_plan)) {
            $this->validationError('preview', 'Rencana penonaktifan berubah setelah pratinjau dibuat.');
        }
    }

    private function applyDeactivationPlan(ExternalSyncRun $run, User $actor): void
    {
        foreach ($run->deactivation_plan as $planned) {
            $target = $this->findTarget($planned['entity_type'], (int) $planned['target_id'], true);
            $before = $this->auditTarget($target);
            $target->update(['is_active' => false]);
            $this->auditService->record(
                action: "dapodik.{$planned['entity_type']}_deactivated",
                auditable: $target,
                summary: 'Data terverifikasi Dapodik dinonaktifkan sesuai rencana snapshot penuh.',
                actor: $actor,
                before: $before,
                after: $this->auditTarget($target),
            );
        }
    }

    /** @param array<string, list<int>> $appliedIds */
    private function recordUnmatchedLocalIssues(ExternalSyncRun $run, array $appliedIds): void
    {
        foreach ([
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR => AcademicYear::query(),
            DapodikSyncPreviewItem::ENTITY_CLASSROOM => Classroom::query(),
            DapodikSyncPreviewItem::ENTITY_STUDENT => Student::query(),
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => StudentClassMembership::query(),
        ] as $entityType => $query) {
            $query->whereIn('master_source', [
                AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                AcademicYear::MASTER_SOURCE_LEGACY_UNCLASSIFIED,
            ])->where('is_active', true);
            if ($appliedIds[$entityType] !== []) {
                $query->whereNotIn('id', $appliedIds[$entityType]);
            }
            foreach ($query->lockForUpdate()->get(['id']) as $target) {
                $run->issues()->create([
                    'entity_type' => $entityType,
                    'source_identifier' => 'local:'.$target->getKey(),
                    'issue_code' => 'unmatched_local_record',
                    'summary' => 'Data lokal belum cocok dengan snapshot Dapodik dan tetap dipertahankan.',
                ]);
            }
        }
    }

    /** @return array<string, mixed> */
    private function auditTarget(Model $target): array
    {
        return array_intersect_key($target->getAttributes(), array_flip([
            'id',
            'dapodik_id',
            'name',
            'nisn',
            'academic_year_id',
            'student_id',
            'classroom_id',
            'grade_level',
            'major',
            'effective_from',
            'effective_until',
            'is_active',
            'master_source',
            'source_confirmed_at',
        ]));
    }

    /** @param array<string, mixed>|null $before */
    private function auditAppliedTarget(
        ExternalSyncRun $run,
        DapodikSyncPreviewItem $item,
        Model $target,
        User $actor,
        ?array $before,
    ): void {
        $verb = $before === null ? 'created' : 'reconciled';
        $this->auditService->record(
            action: "dapodik.{$item->entity_type}_{$verb}",
            auditable: $target,
            summary: $before === null
                ? 'Data resmi Dapodik dibuat dari pratinjau terkonfirmasi.'
                : 'Data lokal dicocokkan dengan Dapodik pada ID internal yang sama.',
            actor: $actor,
            before: $before,
            after: $this->auditTarget($target),
        );
    }

    private function assertCurrentPreview(ExternalSyncRun $run): void
    {
        if ($run->source !== IntegrationSetting::PROVIDER_DAPODIK
            || $run->status !== ExternalSyncRun::STATUS_PREVIEW_READY
            || $run->applied_at !== null
            || $run->superseded_at !== null
        ) {
            $this->validationError('preview', 'Pratinjau Dapodik ini tidak lagi dapat digunakan.');
        }
        if ($run->preview_expires_at === null || $run->preview_expires_at->isPast()) {
            $this->validationError('preview', 'Pratinjau Dapodik telah kedaluwarsa. Tarik snapshot baru.');
        }
        $latestGeneration = (int) ExternalSyncRun::query()
            ->where('source', IntegrationSetting::PROVIDER_DAPODIK)
            ->max('preview_generation');
        if ($run->preview_generation !== $latestGeneration) {
            $this->validationError('preview', 'Pratinjau Dapodik bukan generasi terbaru.');
        }
    }

    private function allowedManualCandidate(
        ExternalSyncRun $run,
        DapodikSyncPreviewItem $item,
        int $candidateId,
    ): Model {
        if ($item->entity_type === DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR) {
            $candidate = AcademicYear::query()->lockForUpdate()->find($candidateId);
            if ($candidate === null
                || $candidate->master_source !== AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL
                || $candidate->source_confirmed_at !== null
                || $candidate->dapodik_id !== null
                || $this->dateValue($candidate->starts_on) !== ($item->safe_fields['starts_on'] ?? null)
                || $this->dateValue($candidate->ends_on) !== ($item->safe_fields['ends_on'] ?? null)
            ) {
                $this->validationError('candidate_id', 'Tahun ajaran sementara tidak dapat dipilih.');
            }

            return $candidate;
        }

        $candidate = Classroom::query()->lockForUpdate()->find($candidateId);
        $yearSourceId = $item->safe_fields['academic_year_source_id'] ?? null;
        $yearItem = $run->previewItems()
            ->where('entity_type', DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR)
            ->where('source_identifier', $yearSourceId)
            ->lockForUpdate()
            ->first();
        $yearCandidateId = $yearItem?->decision === DapodikSyncPreviewItem::DECISION_MAP_EXISTING
            ? $yearItem->decision_candidate_id
            : $yearItem?->candidate_id;
        if ($candidate === null
            || $candidate->master_source !== Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL
            || $candidate->source_confirmed_at !== null
            || $candidate->dapodik_id !== null
            || $yearCandidateId === null
            || $candidate->academic_year_id !== $yearCandidateId
        ) {
            $this->validationError('candidate_id', 'Rombel sementara tidak berada pada konteks tahun ajaran yang benar.');
        }

        return $candidate;
    }

    private function validationError(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    /** @param array<string, mixed> $fields @return array<string, mixed> */
    private function normalizeAcademicYear(array $fields): array
    {
        return [
            'source_id' => $fields['source_id'],
            'name' => $fields['name'],
            'starts_on' => $fields['starts_on'] ?? null,
            'ends_on' => $fields['ends_on'] ?? null,
            'is_active' => $fields['is_active'] ?? false,
        ];
    }

    /** @param array<string, mixed> $fields @return array<string, mixed> */
    private function normalizeClassroom(array $fields): array
    {
        return [
            'source_id' => $fields['source_id'],
            'academic_year_source_id' => $fields['academic_year_source_id'],
            'name' => $fields['name'],
            'grade_level' => $fields['grade_level'] ?? null,
            'major' => $fields['major'] ?? null,
            'is_active' => $fields['is_active'] ?? true,
        ];
    }

    /** @param array<string, mixed> $fields @return array<string, mixed> */
    private function normalizeStudent(array $fields): array
    {
        return [
            'source_id' => $fields['source_id'],
            'nisn' => $fields['nisn'],
            'name' => $fields['name'],
            'is_active' => $fields['is_active'] ?? true,
        ];
    }

    /** @param array<string, mixed> $fields @return array<string, mixed> */
    private function normalizeMembership(array $fields): array
    {
        return [
            'source_id' => $fields['source_id'],
            'student_source_id' => $fields['student_source_id'],
            'classroom_source_id' => $fields['classroom_source_id'],
            'academic_year_source_id' => $fields['academic_year_source_id'],
            'effective_from' => $fields['effective_from'],
            'effective_until' => $fields['effective_until'] ?? null,
            'is_active' => $fields['is_active'] ?? true,
        ];
    }

    /**
     * @param  array<string, mixed>  $safeFields
     * @param  array{candidate_id: ?int, match_status: string}  $match
     * @return array<string, mixed>
     */
    private function previewItem(string $entityType, array $safeFields, array $match, int $generation): array
    {
        $candidate = $match['candidate_id'] === null
            ? null
            : $this->findTarget($entityType, $match['candidate_id']);

        return [
            'entity_type' => $entityType,
            'source_identifier' => $safeFields['source_id'],
            'candidate_id' => $match['candidate_id'],
            'match_status' => $match['match_status'],
            'safe_fields' => $safeFields,
            'item_hash' => hash('sha256', $this->canonicalJson([
                'entity_type' => $entityType,
                'safe_fields' => $safeFields,
            ])),
            'target_fingerprint' => $candidate === null ? null : $this->targetFingerprint($entityType, $candidate),
            'preview_generation' => $generation,
            'decision_revision' => 0,
        ];
    }

    /** @param array<string, mixed> $fields @return array{candidate_id: ?int, match_status: string} */
    private function classifyAcademicYear(array $fields): array
    {
        $bySource = AcademicYear::query()->where('dapodik_id', $fields['source_id'])->get();
        $byIdentity = AcademicYear::query()->where('name', $fields['name'])->get()
            ->filter(fn (AcademicYear $year): bool => $this->dateValue($year->starts_on) === $fields['starts_on']
                && $this->dateValue($year->ends_on) === $fields['ends_on'])
            ->values();

        return $this->classifyIdentityCandidate(
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR,
            $fields,
            $bySource,
            $byIdentity,
            AcademicYear::query()
                ->where('master_source', AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL)
                ->whereNull('source_confirmed_at')
                ->whereNull('dapodik_id')
                ->exists(),
        );
    }

    /** @param array<string, mixed> $fields @param array{candidate_id: ?int, match_status: string}|null $yearMatch @return array{candidate_id: ?int, match_status: string} */
    private function classifyClassroom(array $fields, ?array $yearMatch): array
    {
        $bySource = Classroom::query()->where('dapodik_id', $fields['source_id'])->get();
        $yearId = $yearMatch['candidate_id'] ?? null;
        $byIdentity = $yearId === null
            ? new Collection
            : Classroom::query()
                ->where('academic_year_id', $yearId)
                ->where('name', $fields['name'])
                ->get();
        $hasManualCandidate = $yearId !== null && Classroom::query()
            ->where('academic_year_id', $yearId)
            ->where('master_source', Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL)
            ->whereNull('source_confirmed_at')
            ->whereNull('dapodik_id')
            ->exists();
        if ($yearMatch !== null && $yearMatch['match_status'] === DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING) {
            $hasManualCandidate = true;
        }

        return $this->classifyIdentityCandidate(
            DapodikSyncPreviewItem::ENTITY_CLASSROOM,
            $fields,
            $bySource,
            $byIdentity,
            $hasManualCandidate,
        );
    }

    /** @param array<string, mixed> $fields @return array{candidate_id: ?int, match_status: string} */
    private function classifyStudent(array $fields): array
    {
        $bySource = Student::query()->where('dapodik_id', $fields['source_id'])->get();
        $byNisn = Student::query()->where('nisn', $fields['nisn'])->get();
        if ($bySource->count() > 1 || $byNisn->count() > 1) {
            return $this->match(null, DapodikSyncPreviewItem::MATCH_CONFLICT);
        }
        $sourceCandidate = $bySource->first();
        $nisnCandidate = $byNisn->first();
        if ($sourceCandidate !== null && $sourceCandidate->nisn !== $fields['nisn']) {
            return $this->match(null, DapodikSyncPreviewItem::MATCH_CONFLICT);
        }
        if ($sourceCandidate !== null && $nisnCandidate !== null && ! $sourceCandidate->is($nisnCandidate)) {
            return $this->match(null, DapodikSyncPreviewItem::MATCH_CONFLICT);
        }
        if ($nisnCandidate !== null
            && $nisnCandidate->dapodik_id !== null
            && $nisnCandidate->dapodik_id !== $fields['source_id']
        ) {
            return $this->match(null, DapodikSyncPreviewItem::MATCH_CONFLICT);
        }

        /** @var Student|null $candidate */
        $candidate = $sourceCandidate ?? $nisnCandidate;
        if ($candidate === null) {
            return $this->match(null, DapodikSyncPreviewItem::MATCH_NEW);
        }

        return $this->match(
            $candidate->getKey(),
            $this->targetEqualsSnapshot(DapodikSyncPreviewItem::ENTITY_STUDENT, $candidate, $fields)
                ? DapodikSyncPreviewItem::MATCH_EXACT
                : DapodikSyncPreviewItem::MATCH_CHANGED,
        );
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array{candidate_id: ?int, match_status: string}|null  $studentMatch
     * @param  array{candidate_id: ?int, match_status: string}|null  $classroomMatch
     * @param  array{candidate_id: ?int, match_status: string}|null  $yearMatch
     * @param  array<string, mixed>|null  $yearFields
     * @return array{candidate_id: ?int, match_status: string}
     */
    private function classifyMembership(
        array $fields,
        ?array $studentMatch,
        ?array $classroomMatch,
        ?array $yearMatch,
        ?array $yearFields,
    ): array {
        $bySource = StudentClassMembership::query()->where('dapodik_id', $fields['source_id'])->get();
        if ($bySource->count() > 1) {
            return $this->match(null, DapodikSyncPreviewItem::MATCH_CONFLICT);
        }

        $studentId = $studentMatch['candidate_id'] ?? null;
        $classroomId = $classroomMatch['candidate_id'] ?? null;
        $yearId = $yearMatch['candidate_id'] ?? null;
        $byIdentity = ($studentId === null || $classroomId === null || $yearId === null)
            ? new Collection
            : StudentClassMembership::query()
                ->where('student_id', $studentId)
                ->where('classroom_id', $classroomId)
                ->where('academic_year_id', $yearId)
                ->whereDate('effective_from', $fields['effective_from'])
                ->get();
        if ($byIdentity->isEmpty()
            && $studentId !== null
            && (($classroomMatch['match_status'] ?? null) === DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
                || ($yearMatch['match_status'] ?? null) === DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING)
        ) {
            $byIdentity = $this->membershipCandidatesForParentContext(
                $fields,
                $studentId,
                $classroomMatch,
                $yearMatch,
                $yearFields,
            );
        }

        return $this->classifyIdentityCandidate(
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP,
            $fields,
            $bySource,
            $byIdentity,
            false,
        );
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array{candidate_id: ?int, match_status: string}|null  $classroomMatch
     * @param  array{candidate_id: ?int, match_status: string}|null  $yearMatch
     * @param  array<string, mixed>|null  $yearFields
     * @return Collection<int, StudentClassMembership>
     */
    private function membershipCandidatesForParentContext(
        array $fields,
        int $studentId,
        ?array $classroomMatch,
        ?array $yearMatch,
        ?array $yearFields,
    ): Collection {
        $yearIds = $this->candidateYearIds($yearMatch, $yearFields);
        $classroomYears = $this->candidateClassroomYears($classroomMatch, $yearIds);
        if ($yearIds === [] || $classroomYears === []) {
            return new Collection;
        }

        return StudentClassMembership::query()
            ->where('student_id', $studentId)
            ->whereDate('effective_from', $fields['effective_from'])
            ->whereIn('academic_year_id', $yearIds)
            ->whereIn('classroom_id', array_keys($classroomYears))
            ->where('master_source', StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL)
            ->whereNull('source_confirmed_at')
            ->whereNull('dapodik_id')
            ->get()
            ->filter(
                fn (StudentClassMembership $membership): bool => ($classroomYears[$membership->classroom_id] ?? null)
                    === $membership->academic_year_id,
            )
            ->values();
    }

    /**
     * @param  array{candidate_id: ?int, match_status: string}|null  $yearMatch
     * @param  array<string, mixed>|null  $yearFields
     * @return list<int>
     */
    private function candidateYearIds(?array $yearMatch, ?array $yearFields): array
    {
        $candidateId = $yearMatch['candidate_id'] ?? null;
        if ($candidateId !== null) {
            return [$candidateId];
        }
        if (($yearMatch['match_status'] ?? null) !== DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
            || $yearFields === null
        ) {
            return [];
        }

        return AcademicYear::query()
            ->whereDate('starts_on', $yearFields['starts_on'])
            ->whereDate('ends_on', $yearFields['ends_on'])
            ->where('master_source', AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL)
            ->whereNull('source_confirmed_at')
            ->whereNull('dapodik_id')
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /**
     * @param  array{candidate_id: ?int, match_status: string}|null  $classroomMatch
     * @param  list<int>  $yearIds
     * @return array<int, int>
     */
    private function candidateClassroomYears(
        ?array $classroomMatch,
        array $yearIds,
    ): array {
        $candidateId = $classroomMatch['candidate_id'] ?? null;
        if ($candidateId !== null) {
            return Classroom::query()
                ->whereKey($candidateId)
                ->whereIn('academic_year_id', $yearIds)
                ->pluck('academic_year_id', 'id')
                ->map(fn (mixed $yearId): int => (int) $yearId)
                ->all();
        }
        if (($classroomMatch['match_status'] ?? null) !== DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
            || $yearIds === []) {
            return [];
        }

        return Classroom::query()
            ->whereIn('academic_year_id', $yearIds)
            ->where('master_source', Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL)
            ->whereNull('source_confirmed_at')
            ->whereNull('dapodik_id')
            ->orderBy('id')
            ->pluck('academic_year_id', 'id')
            ->map(fn (mixed $yearId): int => (int) $yearId)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  Collection<int, Model>  $bySource
     * @param  Collection<int, Model>  $byIdentity
     * @return array{candidate_id: ?int, match_status: string}
     */
    private function classifyIdentityCandidate(
        string $entityType,
        array $fields,
        Collection $bySource,
        Collection $byIdentity,
        bool $hasManualCandidate,
    ): array {
        if ($bySource->count() > 1 || $byIdentity->count() > 1) {
            return $this->match(null, DapodikSyncPreviewItem::MATCH_CONFLICT);
        }
        $sourceCandidate = $bySource->first();
        $identityCandidate = $byIdentity->first();
        if ($sourceCandidate !== null && $identityCandidate !== null && ! $sourceCandidate->is($identityCandidate)) {
            return $this->match(null, DapodikSyncPreviewItem::MATCH_CONFLICT);
        }

        /** @var Model|null $candidate */
        $candidate = $sourceCandidate ?? $identityCandidate;
        if ($candidate === null) {
            return $this->match(
                null,
                $hasManualCandidate
                    ? DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
                    : DapodikSyncPreviewItem::MATCH_NEW,
            );
        }
        if ($sourceCandidate === null
            && (($candidate->getAttribute('master_source') !== AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL)
                || $candidate->getAttribute('source_confirmed_at') !== null
                || $candidate->getAttribute('dapodik_id') !== null)
        ) {
            return $this->match(null, DapodikSyncPreviewItem::MATCH_CONFLICT);
        }

        return $this->match(
            $candidate->getKey(),
            $this->targetEqualsSnapshot($entityType, $candidate, $fields)
                ? DapodikSyncPreviewItem::MATCH_EXACT
                : DapodikSyncPreviewItem::MATCH_CHANGED,
        );
    }

    /** @return array{candidate_id: ?int, match_status: string} */
    private function match(?int $candidateId, string $status): array
    {
        return ['candidate_id' => $candidateId, 'match_status' => $status];
    }

    /** @param array<string, mixed> $fields */
    private function targetEqualsSnapshot(string $entityType, Model $candidate, array $fields): bool
    {
        if ($candidate->getAttribute('dapodik_id') !== $fields['source_id']
            || $candidate->getAttribute('master_source') !== AcademicYear::MASTER_SOURCE_DAPODIK
            || $candidate->getAttribute('source_confirmed_at') === null
        ) {
            return false;
        }

        return match ($entityType) {
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR => $candidate->getAttribute('name') === $fields['name']
                && $this->dateValue($candidate->getAttribute('starts_on')) === $fields['starts_on']
                && $this->dateValue($candidate->getAttribute('ends_on')) === $fields['ends_on'],
            DapodikSyncPreviewItem::ENTITY_CLASSROOM => $candidate->getAttribute('name') === $fields['name']
                && $candidate->getAttribute('grade_level') === $fields['grade_level']
                && $candidate->getAttribute('major') === $fields['major']
                && (bool) $candidate->getAttribute('is_active') === $fields['is_active'],
            DapodikSyncPreviewItem::ENTITY_STUDENT => $candidate->getAttribute('nisn') === $fields['nisn']
                && $candidate->getAttribute('name') === $fields['name']
                && (bool) $candidate->getAttribute('is_active') === $fields['is_active'],
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => $this->dateValue($candidate->getAttribute('effective_from')) === $fields['effective_from']
                && $this->dateValue($candidate->getAttribute('effective_until')) === $fields['effective_until']
                && (bool) $candidate->getAttribute('is_active') === $fields['is_active'],
            default => false,
        };
    }

    private function findTarget(string $entityType, int $id, bool $forUpdate = false): Model
    {
        $query = $this->targetQuery($entityType);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    private function targetQuery(string $entityType): mixed
    {
        return match ($entityType) {
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR => AcademicYear::query(),
            DapodikSyncPreviewItem::ENTITY_CLASSROOM => Classroom::query(),
            DapodikSyncPreviewItem::ENTITY_STUDENT => Student::query(),
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => StudentClassMembership::query(),
            default => throw new \LogicException('Jenis item pratinjau tidak dikenal.'),
        };
    }

    private function targetFingerprint(string $entityType, Model $target): string
    {
        $fields = match ($entityType) {
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR => ['id', 'dapodik_id', 'name', 'starts_on', 'ends_on', 'is_active', 'master_source', 'source_confirmed_at'],
            DapodikSyncPreviewItem::ENTITY_CLASSROOM => ['id', 'dapodik_id', 'academic_year_id', 'name', 'grade_level', 'major', 'is_active', 'master_source', 'source_confirmed_at'],
            DapodikSyncPreviewItem::ENTITY_STUDENT => ['id', 'dapodik_id', 'nisn', 'name', 'is_active', 'master_source', 'source_confirmed_at'],
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => ['id', 'dapodik_id', 'student_id', 'classroom_id', 'academic_year_id', 'effective_from', 'effective_until', 'is_active', 'master_source', 'source_confirmed_at'],
            default => throw new \LogicException('Jenis item pratinjau tidak dikenal.'),
        };

        return hash('sha256', $this->canonicalJson([
            'entity_type' => $entityType,
            'target' => array_intersect_key($target->getAttributes(), array_flip($fields)),
        ]));
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : mb_substr((string) $value, 0, 10);
    }

    /** @return array<string, int|string> */
    private function snapshotEvidence(DapodikSnapshot $snapshot): array
    {
        $evidence = $snapshot->evidence;
        if ($evidence === null) {
            throw new \LogicException('Snapshot tervalidasi wajib memiliki evidence.');
        }

        return [
            'reported_source_identifier' => $evidence->reportedSourceIdentifier,
            'contract_marker' => $evidence->contractMarker,
            'completeness_marker' => $evidence->completenessMarker,
            'page_count' => $evidence->pageCount,
            'record_count' => $evidence->recordCount,
            'processed_bytes' => $evidence->processedBytes,
        ];
    }

    /**
     * @param  array<string, list<string>>  $incomingSourceIds
     * @return list<array{entity_type: string, target_id: int, target_fingerprint: string}>
     */
    private function buildDeactivationPlan(array $incomingSourceIds): array
    {
        $plan = [];
        foreach ([
            DapodikSyncPreviewItem::ENTITY_CLASSROOM => Classroom::query(),
            DapodikSyncPreviewItem::ENTITY_STUDENT => Student::query(),
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => StudentClassMembership::query(),
        ] as $entityType => $query) {
            $query->where('master_source', AcademicYear::MASTER_SOURCE_DAPODIK)
                ->where('is_active', true)
                ->whereNotNull('dapodik_id');
            $sourceIds = $incomingSourceIds[$entityType] ?? [];
            if ($sourceIds !== []) {
                $query->whereNotIn('dapodik_id', $sourceIds);
            }
            foreach ($query->orderBy('id')->lockForUpdate()->get() as $target) {
                $plan[] = [
                    'entity_type' => $entityType,
                    'target_id' => $target->getKey(),
                    'target_fingerprint' => $this->targetFingerprint($entityType, $target),
                ];
            }
        }

        return $plan;
    }

    /** @param array<string, mixed> $value */
    private function canonicalJson(array $value): string
    {
        return json_encode(
            $this->canonicalize($value),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value, SORT_STRING);

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }

    /**
     * @param  array<string, int|string>  $snapshotEvidence
     * @param  list<string>  $itemHashes
     * @param  list<array{entity_type: string, target_id: int, target_fingerprint: string}>  $deactivationPlan
     */
    private function previewFingerprint(
        bool $isFullSnapshot,
        array $snapshotEvidence,
        array $itemHashes,
        array $deactivationPlan,
    ): string {
        return hash('sha256', $this->canonicalJson([
            'is_full_snapshot' => $isFullSnapshot,
            'snapshot_evidence' => $snapshotEvidence,
            'item_hashes' => $itemHashes,
            'deactivation_plan' => $deactivationPlan,
        ]));
    }
}
