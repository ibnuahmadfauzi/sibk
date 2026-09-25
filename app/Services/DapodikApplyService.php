<?php

declare(strict_types=1);

namespace App\Services;

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

final class DapodikApplyService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly IntegrationOperationLock $operationLock,
        private readonly IntegrationConfigurationProvider $configurationProvider,
        private readonly IntegrationDriverRegistry $drivers,
        private readonly StudentIdentityService $studentIdentityService,
        private readonly EtatibSyncService $etatibSyncService,
        private readonly DapodikMatchResolver $matches,
        private readonly DapodikApplyValidator $validator,
        private readonly DapodikDeactivationPlanner $deactivations,
    ) {}

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
                    $this->matches->assertCurrentPreview($currentRun);
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
                    $this->validator->validate($currentRun, $items);
                    $this->deactivations->validate($currentRun, $items);

                    $syncedAt = now();
                    $appliedIds = $this->applyItems($currentRun, $items, $actor, $syncedAt);
                    if ($currentRun->is_full_snapshot) {
                        $this->deactivations->apply($currentRun, $actor);
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
            $target = $this->matches->selectedTarget($item) ?? new AcademicYear;
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
            $target = $this->matches->selectedTarget($item) ?? new Classroom;
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
            $target = $this->matches->selectedTarget($item) ?? new Student;
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
            $target = $this->matches->selectedTarget($item) ?? new StudentClassMembership;
            $before = $target->exists ? $this->auditTarget($target) : null;
            $target->fill([
                'dapodik_id' => $fields['source_id'],
                'student_id' => $studentId,
                'classroom_id' => $classroomId,
                'academic_year_id' => $yearId,
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
                    'summary' => 'Data sekolah belum cocok dengan Dapodik dan tetap disimpan.',
                ]);
            }
        }
    }

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
            'is_active',
            'master_source',
            'source_confirmed_at',
        ]));
    }

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

    private function validationError(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
