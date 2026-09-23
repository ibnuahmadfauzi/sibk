<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\TeacherAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * @param  array{user_id: int, classroom_id: int, academic_year_id: int, decision_number: string, effective_date: string, effective_until?: string|null, notes?: string|null}  $data
     */
    public function assignClass(array $data, User $actor): TeacherAssignment
    {
        return DB::transaction(function () use ($data, $actor): TeacherAssignment {
            $teacher = User::query()->with('roles')->lockForUpdate()->findOrFail($data['user_id']);
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($data['classroom_id']);
            $academicYear = AcademicYear::query()->lockForUpdate()->findOrFail($data['academic_year_id']);
            $start = CarbonImmutable::parse($data['effective_date'])->startOfDay();
            $end = isset($data['effective_until']) && $data['effective_until'] !== null
                ? CarbonImmutable::parse($data['effective_until'])->startOfDay()
                : null;

            $this->validateAssignment($teacher, $classroom, $academicYear, $start, $end);

            $assignments = TeacherAssignment::query()
                ->where('classroom_id', $classroom->getKey())
                ->where('academic_year_id', $academicYear->getKey())
                ->orderBy('effective_from')
                ->lockForUpdate()
                ->get();
            $current = $assignments->first(fn (TeacherAssignment $assignment): bool => $assignment->effective_from->lte($start)
                && ($assignment->effective_until === null || $assignment->effective_until->gte($start))
            );

            if ($current !== null) {
                if ($current->effective_from->equalTo($start)) {
                    throw ValidationException::withMessages([
                        'effective_date' => 'Sudah ada penugasan yang dimulai pada tanggal tersebut.',
                    ]);
                }

                $before = $this->snapshot($current);
                $current->update(['effective_until' => $start->subDay()->toDateString()]);
                $this->auditService->record(
                    action: 'class_assignment.closed',
                    auditable: $current,
                    summary: sprintf('Periode penugasan kelas %s ditutup.', $classroom->name),
                    actor: $actor,
                    before: $before,
                    after: $this->snapshot($current->refresh()),
                );
            }

            $overlap = TeacherAssignment::query()
                ->where('classroom_id', $classroom->getKey())
                ->where('academic_year_id', $academicYear->getKey())
                ->whereDate('effective_from', '<=', $end?->toDateString() ?? '9999-12-31')
                ->where(function ($period) use ($start): void {
                    $period->whereNull('effective_until')
                        ->orWhereDate('effective_until', '>=', $start->toDateString());
                })
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'effective_date' => 'Periode penugasan bertumpang tindih dengan penugasan lain.',
                ]);
            }

            $assignment = TeacherAssignment::query()->create([
                'user_id' => $teacher->getKey(),
                'classroom_id' => $classroom->getKey(),
                'academic_year_id' => $academicYear->getKey(),
                'effective_from' => $start->toDateString(),
                'effective_until' => $end?->toDateString(),
                'decision_number' => $data['decision_number'],
                'notes' => $data['notes'] ?? null,
                'assigned_by' => $actor->getKey(),
            ]);

            $this->auditService->record(
                action: 'class_assignment.created',
                auditable: $assignment,
                summary: sprintf('Guru BK ditugaskan untuk kelas %s.', $classroom->name),
                actor: $actor,
                after: $this->snapshot($assignment),
            );

            return $assignment->load(['teacher.roles', 'classroom', 'academicYear']);
        });
    }

    private function validateAssignment(
        User $teacher,
        Classroom $classroom,
        AcademicYear $academicYear,
        CarbonImmutable $start,
        ?CarbonImmutable $end,
    ): void {
        if (! $teacher->is_active || ! $teacher->hasRole('guru_bk')) {
            throw ValidationException::withMessages([
                'user_id' => 'Penanggung jawab harus merupakan Guru BK aktif.',
            ]);
        }

        if ($classroom->academic_year_id !== $academicYear->getKey()) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Kelas tidak termasuk dalam tahun ajaran yang dipilih.',
            ]);
        }

        if ($end !== null && $end->lt($start)) {
            throw ValidationException::withMessages([
                'effective_until' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
            ]);
        }

        if ($academicYear->starts_on !== null && $start->lt($academicYear->starts_on)) {
            throw ValidationException::withMessages([
                'effective_date' => 'Tanggal mulai berada di luar tahun ajaran.',
            ]);
        }

        if ($academicYear->ends_on !== null && ($start->gt($academicYear->ends_on) || $end?->gt($academicYear->ends_on))) {
            throw ValidationException::withMessages([
                'effective_until' => 'Periode penugasan berada di luar tahun ajaran.',
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(TeacherAssignment $assignment): array
    {
        return [
            'user_id' => $assignment->user_id,
            'classroom_id' => $assignment->classroom_id,
            'academic_year_id' => $assignment->academic_year_id,
            'effective_from' => $assignment->effective_from?->toDateString(),
            'effective_until' => $assignment->effective_until?->toDateString(),
            'decision_number' => $assignment->decision_number,
            'notes' => $assignment->notes,
        ];
    }

}
