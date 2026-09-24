<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * @param  array{user_id: int, classroom_id: int}  $data
     */
    public function assignClass(array $data, User $actor): TeacherAssignment
    {
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
