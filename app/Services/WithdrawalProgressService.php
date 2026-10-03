<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Models\WithdrawalProgress;
use App\Models\WithdrawalProgressFollowUp;
use Carbon\CarbonImmutable;
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

            $note = $data['note'];

            $withdrawal = WithdrawalProgress::query()->create([
                'student_id' => $student->getKey(),
                'teacher_id' => $actor->getKey(),
                'classroom_id' => $membership->classroom_id,
                'recorded_on' => $data['recorded_on'],
                'progress' => WithdrawalProgress::PROGRESS_IN_PROGRESS,
                'note' => $note,
                'reason' => $note,
            ]);

            $withdrawal->followUps()->create([
                'progress' => WithdrawalProgress::PROGRESS_IN_PROGRESS,
                'follow_up_date' => $withdrawal->recorded_on->toDateString(),
                'notes' => null,
                'created_by' => $actor->getKey(),
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

    /** @param array<string, mixed> $data */
    public function addFollowUp(WithdrawalProgress $withdrawal, array $data, User $actor): WithdrawalProgressFollowUp
    {
        return DB::transaction(function () use ($withdrawal, $data, $actor): WithdrawalProgressFollowUp {
            $withdrawal = WithdrawalProgress::query()->lockForUpdate()->findOrFail($withdrawal->getKey());
            abort_unless($actor->can('update', $withdrawal), 403);
            if (! in_array($data['progress'], array_keys(WithdrawalProgress::labels()), true)) {
                throw ValidationException::withMessages(['progress' => 'Progres penanganan tidak valid.']);
            }

            $followUp = $withdrawal->followUps()->create([
                'progress' => $data['progress'],
                'follow_up_date' => $data['follow_up_date'],
                'notes' => null,
                'created_by' => $actor->getKey(),
            ]);
            $latest = $withdrawal->followUps()->firstOrFail();
            $withdrawal->update(['progress' => $latest->progress]);

            $this->auditService->record(
                'withdrawal_progress.follow_up_added',
                $withdrawal,
                'Progres penanganan pengunduran diri ditambahkan.',
                $actor,
                [],
                [
                    'follow_up_id' => $followUp->getKey(),
                    'progress' => $followUp->progress,
                    'follow_up_date' => $followUp->follow_up_date->toDateString(),
                ],
            );

            return $followUp->load('creator');
        });
    }

    /** @param array<string, mixed> $data */
    public function update(WithdrawalProgress $withdrawal, array $data, User $actor): WithdrawalProgress
    {
        return DB::transaction(function () use ($withdrawal, $data, $actor): WithdrawalProgress {
            $withdrawal = WithdrawalProgress::query()->lockForUpdate()->findOrFail($withdrawal->getKey());
            abort_unless($actor->can('update', $withdrawal), 403);

            $recordedOn = CarbonImmutable::parse($data['recorded_on'])->toDateString();

            if ($recordedOn !== $withdrawal->recorded_on->toDateString()) {
                if (! Student::query()->availableForService($recordedOn)
                    ->forActiveTeacherAssignment($actor)
                    ->whereKey($withdrawal->student_id)->exists()) {
                    throw ValidationException::withMessages(['recorded_on' => 'Murid tidak berada dalam kelas yang ditugaskan kepada Anda.']);
                }

                $membership = StudentClassMembership::query()->active()
                    ->where('student_id', $withdrawal->student_id)
                    ->whereHas('academicYear', fn ($years) => $years
                        ->whereDate('starts_on', '<=', $recordedOn)
                        ->whereDate('ends_on', '>=', $recordedOn))
                    ->first();
                if ($membership === null) {
                    throw ValidationException::withMessages(['recorded_on' => 'Kelas murid pada tanggal tersebut tidak tersedia.']);
                }
                $withdrawal->classroom_id = $membership->classroom_id;

                $initialFollowUp = WithdrawalProgressFollowUp::query()
                    ->where('withdrawal_progress_id', $withdrawal->getKey())
                    ->whereDate('follow_up_date', $withdrawal->recorded_on->toDateString())
                    ->whereNull('notes')
                    ->orderBy('id')
                    ->first();
                $initialFollowUp?->update(['follow_up_date' => $recordedOn]);
            }

            $note = $data['note'];

            $withdrawal->update([
                'recorded_on' => $recordedOn,
                'note' => $note,
                'reason' => $note,
            ]);

            $this->auditService->record('withdrawal_progress.updated', $withdrawal,
                'Penanganan pengunduran diri diperbarui.', $actor, [], [
                    'student_id' => $withdrawal->student_id,
                    'classroom_id' => $withdrawal->classroom_id,
                    'recorded_on' => $withdrawal->recorded_on->toDateString(),
                    'progress' => $withdrawal->progress,
                ]);

            return $withdrawal->fresh();
        });
    }

    public function destroy(WithdrawalProgress $withdrawal, User $actor): void
    {
        DB::transaction(function () use ($withdrawal, $actor): void {
            $withdrawal = WithdrawalProgress::query()->lockForUpdate()->findOrFail($withdrawal->getKey());
            abort_unless($actor->can('delete', $withdrawal), 403);

            $this->auditService->record('withdrawal_progress.deleted', $withdrawal,
                'Penanganan pengunduran diri dihapus.', $actor, [], [
                    'student_id' => $withdrawal->student_id,
                    'classroom_id' => $withdrawal->classroom_id,
                    'recorded_on' => $withdrawal->recorded_on->toDateString(),
                    'progress' => $withdrawal->progress,
                ]);

            $withdrawal->delete();
        });
    }
}
