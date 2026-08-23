<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
    ) {}

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
            $previousTeacher = null;

            $current = $assignments->first(fn (TeacherAssignment $assignment): bool => $assignment->effective_from->lte($start)
                && ($assignment->effective_until === null || $assignment->effective_until->gte($start))
            );

            if ($current !== null) {
                $previousTeacher = $current->teacher;
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
            $this->notificationService->send(
                recipients: collect([$teacher]),
                category: UserNotification::CATEGORY_ASSIGNMENT,
                title: sprintf('Penugasan kelas %s ditetapkan.', $classroom->name),
                message: sprintf('Periode berlaku mulai %s berdasarkan %s.', $start->format('d-m-Y'), $data['decision_number']),
                target: $assignment,
                actionRoute: 'assignments.classes.index',
                actionParameters: [],
                deduplicationKey: 'class-assignment-created:'.$assignment->getKey(),
            );
            if ($previousTeacher !== null && $previousTeacher->getKey() !== $teacher->getKey()) {
                $this->notificationService->send(
                    recipients: collect([$previousTeacher]),
                    category: UserNotification::CATEGORY_CHANGE,
                    title: sprintf('Penugasan kelas %s diperbarui.', $classroom->name),
                    message: sprintf('Periode penugasan Anda berakhir pada %s.', $start->subDay()->format('d-m-Y')),
                    target: $assignment,
                    actionRoute: 'assignments.classes.index',
                    actionParameters: [],
                    deduplicationKey: 'class-assignment-closed:'.$assignment->getKey(),
                );
            }

            return $assignment->load(['teacher.roles', 'classroom', 'academicYear']);
        });
    }

    /** @param array{assignment_type: string, to_user_id: int, reason: string, effective_date: string} $data */
    public function assignCase(BkCase $case, array $data, User $actor): CaseAssignment
    {
        return DB::transaction(function () use ($case, $data, $actor): CaseAssignment {
            $case = BkCase::query()->with('status')->lockForUpdate()->findOrFail($case->getKey());
            if ($case->closed_at !== null || $case->status->code === 'selesai') {
                throw ValidationException::withMessages(['case' => 'Kasus yang sudah selesai tidak dapat dialihkan.']);
            }

            $teacher = User::query()->with('roles')->lockForUpdate()->findOrFail($data['to_user_id']);
            if (! $teacher->is_active || ! $teacher->hasRole('guru_bk')) {
                throw ValidationException::withMessages([
                    'to_user_id' => 'Penerima harus merupakan Guru BK aktif.',
                ]);
            }

            $effectiveDate = CarbonImmutable::parse($data['effective_date'])->startOfDay();
            if ($effectiveDate->lt($case->service_date)) {
                throw ValidationException::withMessages([
                    'effective_date' => 'Tanggal berlaku tidak boleh sebelum tanggal layanan.',
                ]);
            }

            $type = $data['assignment_type'];
            if (! in_array($type, ['transfer', 'additional'], true)) {
                throw ValidationException::withMessages(['assignment_type' => 'Jenis penugasan kasus tidak valid.']);
            }

            $assignments = CaseAssignment::query()
                ->where('case_id', $case->getKey())
                ->orderBy('effective_from')
                ->lockForUpdate()
                ->get();
            $previousTeacher = null;

            if ($type === 'transfer') {
                $current = $assignments->first(fn (CaseAssignment $assignment): bool => $assignment->assignment_type === CaseAssignment::TYPE_OWNER
                    && $assignment->effective_from->lte($effectiveDate)
                    && ($assignment->effective_until === null || $assignment->effective_until->gte($effectiveDate))
                );

                if ($current === null) {
                    throw ValidationException::withMessages(['effective_date' => 'Pemilik kasus pada tanggal tersebut tidak ditemukan.']);
                }

                if ($current->user_id === $teacher->getKey()) {
                    throw ValidationException::withMessages(['to_user_id' => 'Penerima sudah menjadi pemilik kasus.']);
                }

                if ($current->effective_from->equalTo($effectiveDate)) {
                    throw ValidationException::withMessages(['effective_date' => 'Tanggal berlaku harus setelah awal penugasan pemilik saat ini.']);
                }

                $futureOwnerExists = $assignments->contains(fn (CaseAssignment $assignment): bool => $assignment->assignment_type === CaseAssignment::TYPE_OWNER
                    && $assignment->effective_from->gt($effectiveDate)
                );
                if ($futureOwnerExists) {
                    throw ValidationException::withMessages(['effective_date' => 'Sudah ada pengalihan pemilik yang dijadwalkan setelah tanggal tersebut.']);
                }

                $before = $this->caseAssignmentSnapshot($current);
                $previousTeacher = $current->teacher;
                $current->update(['effective_until' => $effectiveDate->subDay()->toDateString()]);
                $this->auditService->record(
                    action: 'case_assignment.closed',
                    auditable: $current,
                    summary: sprintf('Penugasan pemilik kasus %s ditutup.', $case->registration_number),
                    actor: $actor,
                    before: $before,
                    after: $this->caseAssignmentSnapshot($current->refresh()),
                );
                $assignmentType = CaseAssignment::TYPE_OWNER;
            } else {
                $duplicate = $assignments->contains(fn (CaseAssignment $assignment): bool => $assignment->assignment_type === CaseAssignment::TYPE_ADDITIONAL
                    && $assignment->user_id === $teacher->getKey()
                    && ($assignment->effective_until === null || $assignment->effective_until->gte($effectiveDate))
                );
                if ($duplicate) {
                    throw ValidationException::withMessages(['to_user_id' => 'Guru BK sudah memiliki kewenangan tambahan aktif.']);
                }
                $assignmentType = CaseAssignment::TYPE_ADDITIONAL;
            }

            $assignment = CaseAssignment::query()->create([
                'case_id' => $case->getKey(),
                'user_id' => $teacher->getKey(),
                'assignment_type' => $assignmentType,
                'effective_from' => $effectiveDate->toDateString(),
                'reason' => $data['reason'],
                'assigned_by' => $actor->getKey(),
            ]);

            $this->auditService->record(
                action: $type === 'transfer' ? 'case.transferred' : 'case.access_granted',
                auditable: $case,
                summary: $type === 'transfer'
                    ? sprintf('Kasus %s dialihkan kepada Guru BK lain.', $case->registration_number)
                    : sprintf('Kewenangan tambahan kasus %s diberikan.', $case->registration_number),
                actor: $actor,
                after: $this->caseAssignmentSnapshot($assignment),
            );
            $this->notificationService->send(
                recipients: collect([$teacher]),
                category: UserNotification::CATEGORY_ASSIGNMENT,
                title: sprintf('Penugasan kasus %s diperbarui.', $case->registration_number),
                message: $type === 'transfer' ? 'Anda ditetapkan sebagai pemilik kasus.' : 'Anda memperoleh kewenangan tambahan pada kasus.',
                target: $case,
                actionRoute: 'cases.show',
                actionParameters: ['case' => $case->getKey()],
                deduplicationKey: 'case-assignment-created:'.$assignment->getKey(),
            );
            if ($previousTeacher !== null && $previousTeacher->getKey() !== $teacher->getKey()) {
                $this->notificationService->send(
                    recipients: collect([$previousTeacher]),
                    category: UserNotification::CATEGORY_CHANGE,
                    title: sprintf('Kepemilikan kasus %s dialihkan.', $case->registration_number),
                    message: 'Penugasan pemilik Anda telah ditutup melalui pengalihan eksplisit.',
                    target: $case,
                    actionRoute: 'cases.show',
                    actionParameters: ['case' => $case->getKey()],
                    deduplicationKey: 'case-assignment-closed:'.$assignment->getKey(),
                );
            }

            return $assignment->load('teacher');
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

    /** @return array<string, mixed> */
    private function caseAssignmentSnapshot(CaseAssignment $assignment): array
    {
        return [
            'case_id' => $assignment->case_id,
            'user_id' => $assignment->user_id,
            'assignment_type' => $assignment->assignment_type,
            'effective_from' => $assignment->effective_from?->toDateString(),
            'effective_until' => $assignment->effective_until?->toDateString(),
            'reason' => $assignment->reason,
            'assigned_by' => $assignment->assigned_by,
        ];
    }
}
