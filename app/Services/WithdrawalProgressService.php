<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Models\WithdrawalProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WithdrawalProgressService
{
    public function __construct(private readonly AuditService $auditService) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): WithdrawalProgress
    {
        return DB::transaction(function () use ($data, $actor): WithdrawalProgress {
            abort_unless($actor->can('create', WithdrawalProgress::class), 403);

            $student = Student::query()->lockForUpdate()->findOrFail($data['student_id']);
            if (! Student::query()->availableForService($data['recorded_on'])
                ->forActiveTeacherAssignment($actor)->whereKey($student->getKey())->exists()) {
                throw ValidationException::withMessages(['student_id' => 'Murid tidak berada dalam kelas yang ditugaskan kepada Anda.']);
            }
            if (WithdrawalProgress::query()->where('student_id', $student->getKey())->exists()) {
                throw ValidationException::withMessages(['student_id' => 'Penanganan pengunduran diri murid ini sudah dicatat.']);
            }

            $membership = StudentClassMembership::query()->active()
                ->where('student_id', $student->getKey())
                ->whereHas('academicYear', fn ($years) => $years
                    ->whereDate('starts_on', '<=', $data['recorded_on'])
                    ->whereDate('ends_on', '>=', $data['recorded_on']))
                ->first();
            if ($membership === null) {
                throw ValidationException::withMessages(['recorded_on' => 'Kelas murid pada tanggal tersebut tidak tersedia.']);
            }

            $withdrawal = WithdrawalProgress::query()->create([
                'student_id' => $student->getKey(),
                'teacher_id' => $actor->getKey(),
                'classroom_id' => $membership->classroom_id,
                'recorded_on' => $data['recorded_on'],
                'progress' => $data['progress'],
                'note' => $data['note'] ?? null,
                'reason' => $data['reason'],
            ]);

            $this->auditService->record('withdrawal_progress.created', $withdrawal,
                'Penanganan pengunduran diri dicatat.', $actor, [], [
                    'student_id' => $withdrawal->student_id,
                    'classroom_id' => $withdrawal->classroom_id,
                    'recorded_on' => $withdrawal->recorded_on->toDateString(),
                    'progress' => $withdrawal->progress,
                ]);

            return $withdrawal;
        });
    }

    public function updateProgress(WithdrawalProgress $withdrawal, string $progress, User $actor): void
    {
        DB::transaction(function () use ($withdrawal, $progress, $actor): void {
            $withdrawal = WithdrawalProgress::query()->lockForUpdate()->findOrFail($withdrawal->getKey());
            abort_unless($actor->can('update', $withdrawal), 403);
            if (! in_array($progress, array_keys(WithdrawalProgress::labels()), true)) {
                throw ValidationException::withMessages(['progress' => 'Progres penanganan tidak valid.']);
            }

            $before = ['progress' => $withdrawal->progress];
            $withdrawal->update(['progress' => $progress]);
            $this->auditService->recordChanges('withdrawal_progress.updated', $withdrawal,
                'Progres pengunduran diri diperbarui.', $actor, $before, ['progress' => $progress]);
        });
    }
}
