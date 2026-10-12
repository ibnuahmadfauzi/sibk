<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function __construct(private readonly AuditService $auditService) {}

    /** @return array{added: int, already_assigned: int, unavailable: int, previous_year: string} */
    public function copyPreviousYearClasses(AcademicYear $targetYear, User $teacher, User $actor): array
    {
        Gate::forUser($actor)->authorize('create', TeacherAssignment::class);

        return DB::transaction(function () use ($targetYear, $teacher, $actor): array {
            $targetYear = AcademicYear::query()->lockForUpdate()->findOrFail($targetYear->getKey());
            $teacher = User::query()->with('roles')->lockForUpdate()->findOrFail($teacher->getKey());
            if ((! $targetYear->is_active && $targetYear->activated_at !== null)
                || ! $teacher->is_active || ! $teacher->hasRole('guru_bk')) {
                throw ValidationException::withMessages([
                    'academic_year_id' => 'Tahun ajaran atau Guru BK tidak tersedia untuk pembagian kelas.',
                ]);
            }

            if (! preg_match('/^(\d{4})\/(\d{4})$/', $targetYear->name, $matches)
                || (int) $matches[2] !== (int) $matches[1] + 1) {
                throw ValidationException::withMessages([
                    'academic_year_id' => 'Tahun ajaran sebelumnya tidak dapat dipastikan.',
                ]);
            }
            $previousName = sprintf('%04d/%04d', (int) $matches[1] - 1, (int) $matches[1]);
            $previousYear = AcademicYear::query()->where('name', $previousName)->first();
            if ($previousYear === null) {
                throw ValidationException::withMessages([
                    'academic_year_id' => 'Tahun ajaran sebelumnya belum tersedia.',
                ]);
            }

            $previousNames = TeacherAssignment::query()
                ->where('academic_year_id', $previousYear->getKey())
                ->where('user_id', $teacher->getKey())
                ->with('classroom:id,name')
                ->get()
                ->pluck('classroom.name')
                ->filter()
                ->all();
            if ($previousNames === []) {
                throw ValidationException::withMessages([
                    'user_id' => sprintf('%s belum memiliki kelas binaan pada %s.', $teacher->name, $previousName),
                ]);
            }
            $targetClasses = Classroom::query()->active()
                ->where('academic_year_id', $targetYear->getKey())
                ->whereIn('name', $previousNames)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $assignedClassIds = TeacherAssignment::query()
                ->where('academic_year_id', $targetYear->getKey())
                ->whereIn('classroom_id', $targetClasses->modelKeys())
                ->lockForUpdate()
                ->pluck('classroom_id')
                ->all();

            $added = 0;
            foreach ($targetClasses as $classroom) {
                if (in_array($classroom->getKey(), $assignedClassIds, true)) {
                    continue;
                }
                $this->assignClass([
                    'classroom_id' => $classroom->getKey(),
                    'user_id' => $teacher->getKey(),
                    'only_if_unassigned' => true,
                ], $actor);
                $added++;
            }

            return [
                'added' => $added,
                'already_assigned' => count($assignedClassIds),
                'unavailable' => count($previousNames) - $targetClasses->count(),
                'previous_year' => $previousName,
            ];
        }, 3);
    }

    /**
     * @param  array{user_id: int, classroom_ids: list<int>}  $data
     * @return Collection<int, TeacherAssignment>
     */
    public function assignClasses(array $data, User $actor): Collection
    {
        Gate::forUser($actor)->authorize('create', TeacherAssignment::class);
        $classroomIds = $data['classroom_ids'];
        sort($classroomIds);

        return DB::transaction(function () use ($classroomIds, $data, $actor): Collection {
            $assignments = collect();
            $yearId = null;

            foreach ($classroomIds as $classroomId) {
                $assignment = $this->assignClass([
                    'classroom_id' => $classroomId,
                    'user_id' => $data['user_id'],
                    'only_if_unassigned' => true,
                ], $actor);
                if ($yearId !== null && $assignment->academic_year_id !== $yearId) {
                    throw ValidationException::withMessages([
                        'classroom_ids' => 'Semua kelas harus berasal dari tahun ajaran yang sama.',
                    ]);
                }
                $yearId = $assignment->academic_year_id;
                $assignments->push($assignment);
            }

            return $assignments;
        }, 3);
    }

    /**
     * @param  array{user_id: int, classroom_id: int, only_if_unassigned?: bool}  $data
     */
    public function assignClass(array $data, User $actor): TeacherAssignment
    {
        Gate::forUser($actor)->authorize('create', TeacherAssignment::class);

        return DB::transaction(function () use ($data, $actor): TeacherAssignment {
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($data['classroom_id']);
            $academicYear = AcademicYear::query()->lockForUpdate()->findOrFail($classroom->academic_year_id);
            $teacher = User::query()->with('roles')->lockForUpdate()->findOrFail($data['user_id']);

            $isArchived = $academicYear->is_active === false && $academicYear->activated_at !== null;
            if ($classroom->is_active === false || $isArchived) {
                throw ValidationException::withMessages(['classroom_id' => 'Kelas tidak tersedia untuk penugasan.']);
            }

            if ($teacher->is_active === false || $teacher->hasRole('guru_bk') === false) {
                throw ValidationException::withMessages(['user_id' => 'Penanggung jawab harus Guru BK aktif.']);
            }

            $assignment = TeacherAssignment::query()
                ->where('classroom_id', $classroom->getKey())
                ->where('academic_year_id', $classroom->academic_year_id)
                ->lockForUpdate()
                ->first();

            if (($data['only_if_unassigned'] ?? false) && $assignment !== null) {
                throw ValidationException::withMessages([
                    'classroom_id' => 'Kelas sudah ditugaskan. Muat ulang daftar sebelum memilih kelas lain.',
                ]);
            }

            if ($assignment?->user_id === $teacher->getKey()) {
                return $assignment->load(['teacher', 'classroom', 'academicYear']);
            }

            $before = $assignment === null ? [] : $this->snapshot($assignment);
            $assignment ??= new TeacherAssignment;
            $assignment->fill([
                'user_id' => $teacher->getKey(),
                'classroom_id' => $classroom->getKey(),
                'academic_year_id' => $classroom->academic_year_id,
                'assigned_by' => $actor->getKey(),
            ]);
            $assignment->save();

            $this->auditService->record(
                action: $before === [] ? 'class_assignment.created' : 'class_assignment.updated',
                auditable: $assignment,
                summary: sprintf('Guru BK kelas %s diperbarui.', $classroom->name),
                actor: $actor,
                before: $before,
                after: $this->snapshot($assignment),
            );

            return $assignment->load(['teacher.roles', 'classroom', 'academicYear']);
        }, 3);
    }

    public function unassignClass(Classroom $classroom, int $expectedUserId, User $actor): void
    {
        Gate::forUser($actor)->authorize('create', TeacherAssignment::class);

        DB::transaction(function () use ($classroom, $expectedUserId, $actor): void {
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($classroom->getKey());
            $year = AcademicYear::query()->lockForUpdate()->findOrFail($classroom->academic_year_id);
            if (! $classroom->is_active || (! $year->is_active && $year->activated_at !== null)) {
                throw ValidationException::withMessages(['classroom_id' => 'Kelas tidak tersedia untuk penugasan.']);
            }

            $assignment = TeacherAssignment::query()
                ->where('classroom_id', $classroom->getKey())
                ->where('academic_year_id', $year->getKey())
                ->lockForUpdate()
                ->first();
            if ($assignment === null || $assignment->user_id !== $expectedUserId) {
                throw ValidationException::withMessages([
                    'classroom_id' => 'Penugasan berubah. Muat ulang daftar sebelum membatalkan.',
                ]);
            }

            $before = $this->snapshot($assignment);
            $this->auditService->record(
                action: 'class_assignment.deleted',
                auditable: $assignment,
                summary: sprintf('Penugasan Guru BK kelas %s dibatalkan.', $classroom->name),
                actor: $actor,
                before: $before,
                after: [...$before, 'user_id' => null],
            );
            $assignment->delete();
        }, 3);
    }

    /** @return array<string, mixed> */
    private function snapshot(TeacherAssignment $assignment): array
    {
        return [
            'user_id' => $assignment->user_id,
            'classroom_id' => $assignment->classroom_id,
            'academic_year_id' => $assignment->academic_year_id,
        ];
    }
}
