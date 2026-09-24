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
        });
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
        });
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
        });
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
