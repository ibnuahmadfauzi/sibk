<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\DapodikSyncPreviewItem;
use App\Models\ExternalSyncRun;
use App\Models\Student;
use App\Models\StudentClassMembership;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class DapodikApplyValidator
{
    public function __construct(private readonly DapodikMatchResolver $matches) {}

    /** @param Collection<int, DapodikSyncPreviewItem> $items */
    public function validate(ExternalSyncRun $run, Collection $items): void
    {
        $this->assertDerivedMembershipDecisionsCurrent($items);
        $this->validateItemsForApply($run, $items);
        $this->validateResolvedGraph($items);
        $this->validateTargetOwnership($items);
    }

    private function assertDerivedMembershipDecisionsCurrent(Collection $items): void
    {
        $byEntityAndSource = $items->keyBy(
            fn (DapodikSyncPreviewItem $item): string => $item->entity_type.':'.$item->source_identifier,
        );
        foreach ($items->where('entity_type', DapodikSyncPreviewItem::ENTITY_MEMBERSHIP) as $item) {
            if ($item->match_status !== DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING) {
                continue;
            }

            $resolution = $this->matches->derivedMembershipResolution($item, $byEntityAndSource);
            if ($item->decision !== $resolution['decision']
                || $item->decision_candidate_id !== $resolution['decision_candidate_id']
                || $item->decision_target_fingerprint !== $resolution['decision_target_fingerprint']
            ) {
                $this->validationError(
                    'preview',
                    'Resolusi keanggotaan berubah setelah keputusan parent dibuat. Muat ulang pratinjau.',
                );
            }
        }
    }

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
            $expectedHash = hash('sha256', $this->matches->canonicalJson([
                'entity_type' => $item->entity_type,
                'safe_fields' => $item->safe_fields,
            ]));
            if (! hash_equals($expectedHash, $item->item_hash)) {
                $this->validationError('preview', 'Hash item pratinjau telah berubah.');
            }
            $hashes[] = $item->item_hash;

            if ($item->match_status === DapodikSyncPreviewItem::MATCH_CONFLICT) {
                $this->validationError('preview', 'Konflik data masih ada. Periksa daftar dan buat pratinjau baru.');
            }
            if ($item->match_status === DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING
                && ! in_array($item->decision, [
                    DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
                    DapodikSyncPreviewItem::DECISION_CREATE_NEW,
                ], true)
            ) {
                $this->validationError('preview', 'Seluruh item yang meragukan harus diberi keputusan.');
            }

            $selectedCandidateId = $this->matches->selectedCandidateId($item);
            $selectedFingerprint = $this->matches->selectedTargetFingerprint($item);
            if ($selectedCandidateId !== null) {
                $target = $this->matches->findTarget($item->entity_type, $selectedCandidateId, true);
                if (! is_string($selectedFingerprint)
                    || ! hash_equals($selectedFingerprint, $this->matches->targetFingerprint($item->entity_type, $target))
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
        $fingerprint = $this->matches->previewFingerprint(
            $run->is_full_snapshot,
            $run->snapshot_evidence,
            $hashes,
            $run->deactivation_plan,
        );
        if (! is_string($run->snapshot_fingerprint)
            || ! hash_equals($run->snapshot_fingerprint, $fingerprint)
        ) {
            $this->validationError('preview', 'Pratinjau Dapodik sudah berubah. Buat pratinjau baru.');
        }
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

    private function validateResolvedGraph(Collection $items): void
    {
        $byEntityAndSource = $items->keyBy(
            fn (DapodikSyncPreviewItem $item): string => $item->entity_type.':'.$item->source_identifier,
        );

        foreach ($items->where('entity_type', DapodikSyncPreviewItem::ENTITY_CLASSROOM) as $item) {
            $targetId = $this->matches->selectedCandidateId($item);
            if ($targetId === null) {
                continue;
            }
            $yearItem = $byEntityAndSource->get(
                DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR.':'.$item->safe_fields['academic_year_source_id'],
            );
            $yearId = $yearItem instanceof DapodikSyncPreviewItem
                ? $this->matches->selectedCandidateId($yearItem)
                : null;
            /** @var Classroom $target */
            $target = $this->matches->findTarget(DapodikSyncPreviewItem::ENTITY_CLASSROOM, $targetId, true);
            if ($yearId === null || $target->academic_year_id !== $yearId) {
                $this->validationError('preview', 'Keputusan rombel tidak lagi sesuai dengan keputusan tahun ajaran.');
            }
        }

        foreach ($items->where('entity_type', DapodikSyncPreviewItem::ENTITY_MEMBERSHIP) as $item) {
            $targetId = $this->matches->selectedCandidateId($item);
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
                ? $this->matches->selectedCandidateId($studentItem)
                : null;
            $classroomId = $classroomItem instanceof DapodikSyncPreviewItem
                ? $this->matches->selectedCandidateId($classroomItem)
                : null;
            $yearId = $yearItem instanceof DapodikSyncPreviewItem
                ? $this->matches->selectedCandidateId($yearItem)
                : null;
            /** @var StudentClassMembership $target */
            $target = $this->matches->findTarget(DapodikSyncPreviewItem::ENTITY_MEMBERSHIP, $targetId, true);
            if ($studentId === null
                || $classroomId === null
                || $yearId === null
                || $target->student_id !== $studentId
                || $target->academic_year_id !== $yearId
            ) {
                $this->validationError('preview', 'Keanggotaan tidak lagi sesuai dengan keputusan murid, rombel, dan tahun ajaran.');
            }
        }
    }

    private function validateTargetOwnership(Collection $items): void
    {
        $byEntityAndSource = $items->keyBy(
            fn (DapodikSyncPreviewItem $item): string => $item->entity_type.':'.$item->source_identifier,
        );

        foreach ($items as $item) {
            $candidateId = $this->matches->selectedCandidateId($item);
            $sourceOwner = $this->matches->targetQuery($item->entity_type)
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

    private function classroomNaturalOwnerQuery(
        DapodikSyncPreviewItem $item,
        Collection $byEntityAndSource,
    ): mixed {
        $yearItem = $byEntityAndSource->get(
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR.':'.$item->safe_fields['academic_year_source_id'],
        );
        $yearId = $yearItem instanceof DapodikSyncPreviewItem
            ? $this->matches->selectedCandidateId($yearItem)
            : null;

        return $yearId === null
            ? null
            : Classroom::query()
                ->where('academic_year_id', $yearId)
                ->where('name', $item->safe_fields['name']);
    }

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
        $yearItem = $byEntityAndSource->get(
            DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR.':'.$item->safe_fields['academic_year_source_id'],
        );
        $studentId = $studentItem instanceof DapodikSyncPreviewItem
            ? $this->matches->selectedCandidateId($studentItem)
            : null;
        $classroomId = $classroomItem instanceof DapodikSyncPreviewItem
            ? $this->matches->selectedCandidateId($classroomItem)
            : null;
        $yearId = $yearItem instanceof DapodikSyncPreviewItem
            ? $this->matches->selectedCandidateId($yearItem)
            : null;
        if ($studentId === null || $classroomId === null || $yearId === null) {
            return null;
        }

        return StudentClassMembership::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $yearId);
    }

    private function validationError(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
