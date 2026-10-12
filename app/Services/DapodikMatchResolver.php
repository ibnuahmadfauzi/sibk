<?php

declare(strict_types=1);

namespace App\Services;

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

final class DapodikMatchResolver
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly IntegrationOperationLock $operationLock,
    ) {}

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
                    $items = DapodikSyncPreviewItem::query()
                        ->where('external_sync_run_id', $currentRun->getKey())
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();
                    $this->refreshDerivedMembershipDecisions($currentRun, $items, $actor);
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

    private function refreshDerivedMembershipDecisions(
        ExternalSyncRun $run,
        Collection $items,
        User $actor,
    ): void {
        $byEntityAndSource = $items->keyBy(
            fn (DapodikSyncPreviewItem $item): string => $item->entity_type.':'.$item->source_identifier,
        );
        $derivedConflicts = 0;
        foreach ($items->where('entity_type', DapodikSyncPreviewItem::ENTITY_MEMBERSHIP) as $item) {
            if ($item->match_status !== DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING) {
                continue;
            }

            $resolution = $this->derivedMembershipResolution($item, $byEntityAndSource);
            if ($resolution['decision'] === DapodikSyncPreviewItem::DECISION_CONFLICT) {
                $derivedConflicts++;
            }
            if ($item->decision === $resolution['decision']
                && $item->decision_candidate_id === $resolution['decision_candidate_id']
                && $item->decision_target_fingerprint === $resolution['decision_target_fingerprint']
            ) {
                continue;
            }

            $item->update([
                ...$resolution,
                'decision_revision' => $item->decision_revision + 1,
                'decided_by' => $resolution['decision'] === null ? null : $actor->getKey(),
                'decided_at' => $resolution['decision'] === null ? null : now(),
            ]);
        }

        $conflictCount = $items
            ->where('match_status', DapodikSyncPreviewItem::MATCH_CONFLICT)
            ->count() + $derivedConflicts;
        if ($run->conflict_count !== $conflictCount) {
            $run->update(['conflict_count' => $conflictCount]);
        }
    }

    public function derivedMembershipResolution(
        DapodikSyncPreviewItem $item,
        Collection $byEntityAndSource,
    ): array {
        $fields = $item->safe_fields;
        $studentItem = $byEntityAndSource->get(
            DapodikSyncPreviewItem::ENTITY_STUDENT.':'.$fields['student_source_id'],
        );
        $classroomItem = $byEntityAndSource->get(
            DapodikSyncPreviewItem::ENTITY_CLASSROOM.':'.$fields['classroom_source_id'],
        );
        $yearItem = $byEntityAndSource->get(
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR.':'.$fields['academic_year_source_id'],
        );
        if (! $studentItem instanceof DapodikSyncPreviewItem
            || ! $classroomItem instanceof DapodikSyncPreviewItem
            || ! $yearItem instanceof DapodikSyncPreviewItem
        ) {
            return $this->derivedMembershipConflict();
        }
        foreach ([$studentItem, $classroomItem, $yearItem] as $parentItem) {
            if ($parentItem->match_status === DapodikSyncPreviewItem::MATCH_CONFLICT) {
                return $this->derivedMembershipConflict();
            }
            if ($parentItem->match_status === DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
                && ! in_array($parentItem->decision, [
                    DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
                    DapodikSyncPreviewItem::DECISION_CREATE_NEW,
                ], true)
            ) {
                return $this->derivedMembershipUnresolved();
            }
        }

        $studentId = $this->selectedCandidateId($studentItem);
        $classroomId = $this->selectedCandidateId($classroomItem);
        $yearId = $this->selectedCandidateId($yearItem);
        if ($studentId === null || $classroomId === null || $yearId === null) {
            return $this->derivedMembershipCreateNew();
        }

        $classroom = Classroom::query()->whereKey($classroomId)->lockForUpdate()->first();
        if (! $classroom instanceof Classroom || $classroom->academic_year_id !== $yearId) {
            return $this->derivedMembershipConflict();
        }

        $bySource = StudentClassMembership::query()
            ->where('dapodik_id', $item->source_identifier)
            ->lockForUpdate()
            ->get();
        $byIdentity = StudentClassMembership::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $yearId)
            ->lockForUpdate()
            ->get();
        if ($bySource->count() > 1 || $byIdentity->count() > 1) {
            return $this->derivedMembershipConflict();
        }

        $sourceCandidate = $bySource->first();
        $identityCandidate = $byIdentity->first();
        if ($sourceCandidate !== null
            && ($sourceCandidate->student_id !== $studentId
                || $sourceCandidate->academic_year_id !== $yearId)
        ) {
            return $this->derivedMembershipConflict();
        }
        if ($sourceCandidate !== null
            && $identityCandidate !== null
            && ! $sourceCandidate->is($identityCandidate)
        ) {
            return $this->derivedMembershipConflict();
        }

        /** @var StudentClassMembership|null $candidate */
        $candidate = $sourceCandidate ?? $identityCandidate;
        if ($candidate === null) {
            return $this->derivedMembershipCreateNew();
        }
        if ($sourceCandidate === null
            && ($candidate->master_source !== StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL
                || $candidate->source_confirmed_at !== null
                || $candidate->dapodik_id !== null)
        ) {
            return $this->derivedMembershipConflict();
        }

        return [
            'decision' => DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
            'decision_candidate_id' => $candidate->getKey(),
            'decision_target_fingerprint' => $this->targetFingerprint(
                DapodikSyncPreviewItem::ENTITY_MEMBERSHIP,
                $candidate,
            ),
        ];
    }

    private function derivedMembershipUnresolved(): array
    {
        return [
            'decision' => null,
            'decision_candidate_id' => null,
            'decision_target_fingerprint' => null,
        ];
    }

    private function derivedMembershipCreateNew(): array
    {
        return [
            'decision' => DapodikSyncPreviewItem::DECISION_CREATE_NEW,
            'decision_candidate_id' => null,
            'decision_target_fingerprint' => null,
        ];
    }

    private function derivedMembershipConflict(): array
    {
        return [
            'decision' => DapodikSyncPreviewItem::DECISION_CONFLICT,
            'decision_candidate_id' => null,
            'decision_target_fingerprint' => null,
        ];
    }

    public function selectedTarget(DapodikSyncPreviewItem $item): ?Model
    {
        $candidateId = $this->selectedCandidateId($item);

        return $candidateId === null ? null : $this->findTarget($item->entity_type, $candidateId, true);
    }

    public function selectedCandidateId(DapodikSyncPreviewItem $item): ?int
    {
        if ($item->match_status === DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING) {
            return $item->decision === DapodikSyncPreviewItem::DECISION_MAP_EXISTING
                ? $item->decision_candidate_id
                : null;
        }

        return $item->candidate_id;
    }

    public function selectedTargetFingerprint(DapodikSyncPreviewItem $item): ?string
    {
        return $item->match_status === DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
            ? $item->decision_target_fingerprint
            : $item->target_fingerprint;
    }

    public function assertCurrentPreview(ExternalSyncRun $run): void
    {
        if ($run->source !== IntegrationSetting::PROVIDER_DAPODIK
            || $run->status !== ExternalSyncRun::STATUS_PREVIEW_READY
            || $run->applied_at !== null
            || $run->superseded_at !== null
        ) {
            $this->validationError('preview', 'Pratinjau Dapodik ini tidak lagi dapat digunakan.');
        }
        if ($run->preview_expires_at === null || $run->preview_expires_at->isPast()) {
            $this->validationError('preview', 'Pratinjau Dapodik telah kedaluwarsa. Ambil data terbaru.');
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

    public function classifyAcademicYear(array $fields): array
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

    public function classifyClassroom(array $fields, ?array $yearMatch): array
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

    public function classifyStudent(array $fields): array
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

    public function classifyMembership(
        array $fields,
        ?array $studentMatch,
        ?array $classroomMatch,
        ?array $yearMatch,
    ): array {
        $bySource = StudentClassMembership::query()->where('dapodik_id', $fields['source_id'])->get();
        if ($bySource->count() > 1) {
            return $this->match(null, DapodikSyncPreviewItem::MATCH_CONFLICT);
        }

        if (($classroomMatch['match_status'] ?? null) === DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
            || ($yearMatch['match_status'] ?? null) === DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
        ) {
            return $this->match(null, DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING);
        }

        $studentId = $studentMatch['candidate_id'] ?? null;
        $classroomId = $classroomMatch['candidate_id'] ?? null;
        $yearId = $yearMatch['candidate_id'] ?? null;
        $byIdentity = ($studentId === null || $classroomId === null || $yearId === null)
            ? new Collection
            : StudentClassMembership::query()
                ->where('student_id', $studentId)
                ->where('academic_year_id', $yearId)
                ->get();

        return $this->classifyIdentityCandidate(
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP,
            $fields,
            $bySource,
            $byIdentity,
            false,
        );
    }

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

    private function match(?int $candidateId, string $status): array
    {
        return ['candidate_id' => $candidateId, 'match_status' => $status];
    }

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
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => $candidate->student?->dapodik_id === $fields['student_source_id']
                && $candidate->classroom?->dapodik_id === $fields['classroom_source_id']
                && $candidate->academicYear?->dapodik_id === $fields['academic_year_source_id']
                && (bool) $candidate->getAttribute('is_active') === $fields['is_active'],
            default => false,
        };
    }

    public function findTarget(string $entityType, int $id, bool $forUpdate = false): Model
    {
        $query = $this->targetQuery($entityType);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    public function targetQuery(string $entityType): mixed
    {
        return match ($entityType) {
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR => AcademicYear::query(),
            DapodikSyncPreviewItem::ENTITY_CLASSROOM => Classroom::query(),
            DapodikSyncPreviewItem::ENTITY_STUDENT => Student::query(),
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => StudentClassMembership::query(),
            default => throw new \LogicException('Jenis item pratinjau tidak dikenal.'),
        };
    }

    public function targetFingerprint(string $entityType, Model $target): string
    {
        $fields = match ($entityType) {
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR => ['id', 'dapodik_id', 'name', 'starts_on', 'ends_on', 'is_active', 'master_source', 'source_confirmed_at'],
            DapodikSyncPreviewItem::ENTITY_CLASSROOM => ['id', 'dapodik_id', 'academic_year_id', 'name', 'grade_level', 'major', 'is_active', 'master_source', 'source_confirmed_at'],
            DapodikSyncPreviewItem::ENTITY_STUDENT => ['id', 'dapodik_id', 'nisn', 'name', 'is_active', 'master_source', 'source_confirmed_at'],
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => ['id', 'dapodik_id', 'student_id', 'classroom_id', 'academic_year_id', 'is_active', 'master_source', 'source_confirmed_at'],
            default => throw new \LogicException('Jenis item pratinjau tidak dikenal.'),
        };

        return hash('sha256', $this->canonicalJson([
            'entity_type' => $entityType,
            'target' => array_intersect_key($target->getAttributes(), array_flip($fields)),
        ]));
    }

    public function dateValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : mb_substr((string) $value, 0, 10);
    }

    public function canonicalJson(array $value): string
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

    public function previewFingerprint(
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
