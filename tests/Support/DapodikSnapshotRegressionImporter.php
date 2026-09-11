<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Integrations\Dapodik\DapodikSnapshot;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Services\StudentIdentityService;

/**
 * Test-only regression helper for legacy Dapodik cache mapping.
 *
 * Production Dapodik changes require the Task 11 preview/apply workflow.
 */
final class DapodikSnapshotRegressionImporter
{
    public function __construct(
        private readonly StudentIdentityService $studentIdentityService,
    ) {}

    public function import(DapodikSnapshot $snapshot, ExternalSyncRun $run, ?User $actor = null): int
    {
        $syncedAt = now();
        $processed = 0;
        $yearIds = [];
        $classroomIds = [];
        $studentIds = [];
        $membershipIds = [];
        $protectedClassroomIds = [];
        $protectedStudentIds = [];
        $protectedMembershipIds = [];

        foreach ($snapshot->academicYears as $item) {
            $year = AcademicYear::query()->updateOrCreate(
                ['dapodik_id' => $item['source_id']],
                [
                    'name' => $item['name'],
                    'starts_on' => $item['starts_on'] ?? null,
                    'ends_on' => $item['ends_on'] ?? null,
                    'is_active' => $item['is_active'] ?? true,
                    'synced_at' => $syncedAt,
                    'master_source' => AcademicYear::MASTER_SOURCE_DAPODIK,
                    'source_confirmed_at' => $syncedAt,
                ],
            );
            $yearIds[$item['source_id']] = $year->getKey();
            $processed++;
        }

        foreach ($snapshot->classrooms as $item) {
            $yearId = $yearIds[$item['academic_year_source_id']] ?? null;
            if ($yearId === null) {
                $existing = Classroom::query()->where('dapodik_id', $item['source_id'])->first();
                if ($existing !== null) {
                    $protectedClassroomIds[] = $existing->getKey();
                }
                $this->issue($run, 'classroom', $item['source_id'], 'missing_academic_year', 'Tahun ajaran sumber untuk kelas tidak ditemukan.');

                continue;
            }

            $classroom = Classroom::query()->updateOrCreate(
                ['dapodik_id' => $item['source_id']],
                [
                    'academic_year_id' => $yearId,
                    'name' => $item['name'],
                    'grade_level' => $item['grade_level'] ?? null,
                    'major' => $item['major'] ?? null,
                    'is_active' => $item['is_active'] ?? true,
                    'synced_at' => $syncedAt,
                    'master_source' => Classroom::MASTER_SOURCE_DAPODIK,
                    'source_confirmed_at' => $syncedAt,
                ],
            );
            $classroomIds[$item['source_id']] = $classroom->getKey();
            $processed++;
        }

        $duplicateNisns = collect($snapshot->students)->groupBy('nisn')
            ->filter(fn ($items): bool => $items->count() > 1)->keys();
        foreach ($snapshot->students as $item) {
            if ($duplicateNisns->contains($item['nisn'])) {
                $existing = Student::query()->where('dapodik_id', $item['source_id'])
                    ->orWhere('nisn', $item['nisn'])->first();
                if ($existing !== null) {
                    $protectedStudentIds[] = $existing->getKey();
                }
                $this->issue($run, 'student', $item['source_id'], 'duplicate_nisn', 'NISN muncul lebih dari sekali pada payload Dapodik.', $item['nisn'], $item['name']);

                continue;
            }

            $bySource = Student::query()->where('dapodik_id', $item['source_id'])->first();
            $byNisn = Student::query()->where('nisn', $item['nisn'])->first();
            if ($bySource !== null && $bySource->nisn !== $item['nisn']) {
                $protectedStudentIds[] = $bySource->getKey();
                $this->issue($run, 'student', $item['source_id'], 'source_identity_mismatch', 'Identitas sumber menunjuk ke NISN yang berbeda.', $item['nisn'], $item['name']);

                continue;
            }
            if ($byNisn !== null && $byNisn->dapodik_id !== null && $byNisn->dapodik_id !== $item['source_id']) {
                $protectedStudentIds[] = $byNisn->getKey();
                $this->issue($run, 'student', $item['source_id'], 'nisn_source_conflict', 'NISN telah terhubung dengan identitas sumber lain.', $item['nisn'], $item['name']);

                continue;
            }

            $student = $bySource ?? $byNisn ?? new Student;
            $student->fill([
                'dapodik_id' => $item['source_id'],
                'nisn' => $item['nisn'],
                'name' => $item['name'],
                'is_active' => $item['is_active'] ?? true,
                'synced_at' => $syncedAt,
                'master_source' => Student::MASTER_SOURCE_DAPODIK,
                'source_confirmed_at' => $syncedAt,
            ])->save();
            $studentIds[$item['source_id']] = $student->getKey();
            $processed++;
        }

        foreach ($snapshot->memberships as $item) {
            $studentId = $studentIds[$item['student_source_id']] ?? null;
            $classroomId = $classroomIds[$item['classroom_source_id']] ?? null;
            $yearId = $yearIds[$item['academic_year_source_id']] ?? null;
            if ($studentId === null || $classroomId === null || $yearId === null) {
                $existing = StudentClassMembership::query()->where('dapodik_id', $item['source_id'])->first();
                if ($existing !== null) {
                    $protectedMembershipIds[] = $existing->getKey();
                }
                $this->issue($run, 'membership', $item['source_id'], 'missing_relation', 'Relasi murid, kelas, atau tahun ajaran tidak tersedia.');

                continue;
            }

            $membership = StudentClassMembership::query()->updateOrCreate(
                ['dapodik_id' => $item['source_id']],
                [
                    'student_id' => $studentId,
                    'classroom_id' => $classroomId,
                    'academic_year_id' => $yearId,
                    'effective_from' => $item['effective_from'],
                    'effective_until' => $item['effective_until'] ?? null,
                    'is_active' => $item['is_active'] ?? true,
                    'synced_at' => $syncedAt,
                    'master_source' => StudentClassMembership::MASTER_SOURCE_DAPODIK,
                    'source_confirmed_at' => $syncedAt,
                ],
            );
            $membershipIds[] = $membership->getKey();
            $processed++;
        }

        if ($snapshot->isFullSnapshot) {
            $this->deactivateMissing(AcademicYear::query(), array_values($yearIds));
            $this->deactivateMissing(Classroom::query(), [...array_values($classroomIds), ...$protectedClassroomIds]);
            $this->deactivateMissing(Student::query(), [...array_values($studentIds), ...$protectedStudentIds]);
            $this->deactivateMissing(StudentClassMembership::query(), [...$membershipIds, ...$protectedMembershipIds]);
        }
        $this->studentIdentityService->reconcilePending($actor);

        return $processed;
    }

    /** @param \Illuminate\Database\Eloquent\Builder<*> $query @param list<int> $ids */
    private function deactivateMissing($query, array $ids): void
    {
        $query->whereNotNull('dapodik_id');
        if ($ids !== []) {
            $query->whereNotIn('id', $ids);
        }
        $query->update(['is_active' => false]);
    }

    private function issue(ExternalSyncRun $run, string $entityType, ?string $sourceIdentifier, string $code, string $summary, ?string $nisn = null, ?string $inputName = null): ExternalSyncIssue
    {
        return ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->getKey(),
            'entity_type' => $entityType,
            'source_identifier' => $sourceIdentifier,
            'nisn' => $nisn,
            'input_name' => $inputName,
            'issue_code' => $code,
            'summary' => $summary,
        ]);
    }
}
