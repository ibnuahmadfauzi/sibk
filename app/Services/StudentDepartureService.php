<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;
use App\Models\StudentDeparture;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StudentDepartureService
{
    public function __construct(private readonly AuditService $auditService) {}

    /** @param array<string, mixed> $data */
    public function record(Student $student, array $data, User $actor): StudentDeparture
    {
        return DB::transaction(function () use ($student, $data, $actor): StudentDeparture {
            $student = Student::query()->lockForUpdate()->findOrFail($student->getKey());
            $actor = User::query()->with('roles')->findOrFail($actor->getKey());
            abort_unless($actor->can('create', [StudentDeparture::class, $student]), 403);
            $departure = StudentDeparture::query()->firstOrCreate(
                ['student_id' => $student->getKey()],
                [
                    'departure_type' => $data['departure_type'],
                    'status' => StudentDeparture::STATUS_IN_PROGRESS,
                    'reported_at' => $data['reported_at'],
                    'recommendation_summary' => $data['recommendation_summary'] ?? null,
                    'recorded_by' => $actor->getKey(),
                ],
            );

            if (! $departure->wasRecentlyCreated) {
                $departure = StudentDeparture::query()->lockForUpdate()->findOrFail($departure->getKey());
                if ($departure->status !== StudentDeparture::STATUS_CANCELLED) {
                    throw ValidationException::withMessages(['departure' => 'Proses keluar murid sudah tercatat.']);
                }

                $before = $this->snapshot($departure);
                $departure->update([
                    'departure_type' => $data['departure_type'],
                    'status' => StudentDeparture::STATUS_IN_PROGRESS,
                    'recommendation_summary' => $data['recommendation_summary'] ?? null,
                    'effective_date' => null,
                    'finalized_by' => null,
                    'finalized_at' => null,
                    'decision_note' => null,
                ]);
            }

            $this->auditService->record(
                action: 'student_departure.recorded',
                auditable: $departure,
                summary: 'Proses keluar murid dicatat.',
                actor: $actor,
                before: $before ?? null,
                after: $this->snapshot($departure->refresh()),
            );

            return $departure;
        });
    }

    /** @param array<string, mixed> $data */
    public function updateDraft(StudentDeparture $departure, array $data, User $actor): StudentDeparture
    {
        return DB::transaction(function () use ($departure, $data, $actor): StudentDeparture {
            Student::query()->lockForUpdate()->findOrFail($departure->student_id);
            $departure = StudentDeparture::query()->lockForUpdate()->findOrFail($departure->getKey());
            $actor = User::query()->with('roles')->findOrFail($actor->getKey());
            abort_unless($actor->can('update', $departure), 403);
            $this->ensureInProgress($departure);
            $before = $this->snapshot($departure);
            $departure->update([
                'departure_type' => $data['departure_type'],
                'recommendation_summary' => $data['recommendation_summary'] ?? null,
            ]);
            $this->auditService->record(
                action: 'student_departure.updated',
                auditable: $departure,
                summary: 'Proses keluar murid diperbarui.',
                actor: $actor,
                before: $before,
                after: $this->snapshot($departure->refresh()),
            );

            return $departure;
        });
    }

    /** @param array<string, mixed> $data */
    public function finalize(StudentDeparture $departure, array $data, User $actor): StudentDeparture
    {
        return DB::transaction(function () use ($departure, $data, $actor): StudentDeparture {
            Student::query()->lockForUpdate()->findOrFail($departure->student_id);
            $departure = StudentDeparture::query()->lockForUpdate()->findOrFail($departure->getKey());
            $actor = User::query()->with('roles')->findOrFail($actor->getKey());
            abort_unless($actor->can('finalize', $departure), 403);
            $this->ensureInProgress($departure);
            $decision = (string) $data['decision'];
            if (! in_array($decision, [StudentDeparture::STATUS_CANCELLED, StudentDeparture::STATUS_OFFICIAL], true)) {
                throw ValidationException::withMessages(['decision' => 'Keputusan proses keluar tidak valid.']);
            }
            if ($decision === StudentDeparture::STATUS_OFFICIAL && empty($data['effective_date'])) {
                throw ValidationException::withMessages(['effective_date' => 'Tanggal efektif wajib diisi.']);
            }

            $before = $this->snapshot($departure);
            $departure->update([
                'status' => $decision,
                'effective_date' => $decision === StudentDeparture::STATUS_OFFICIAL ? $data['effective_date'] : null,
                'finalized_by' => $actor->getKey(),
                'finalized_at' => now(),
                'decision_note' => $data['decision_note'] ?? null,
            ]);
            $this->auditService->record(
                action: $decision === StudentDeparture::STATUS_OFFICIAL
                    ? 'student_departure.officialized'
                    : 'student_departure.cancelled',
                auditable: $departure,
                summary: $decision === StudentDeparture::STATUS_OFFICIAL
                    ? 'Proses keluar murid ditetapkan resmi.'
                    : 'Proses keluar murid dibatalkan.',
                actor: $actor,
                before: $before,
                after: $this->snapshot($departure->refresh()),
            );

            return $departure;
        });
    }

    private function ensureInProgress(StudentDeparture $departure): void
    {
        if ($departure->status !== StudentDeparture::STATUS_IN_PROGRESS) {
            throw ValidationException::withMessages(['departure' => 'Hanya proses yang sedang berjalan dapat diubah.']);
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(StudentDeparture $departure): array
    {
        return $departure->only([
            'departure_type', 'status', 'reported_at', 'effective_date',
            'recommendation_summary', 'recorded_by', 'finalized_by',
            'finalized_at', 'decision_note',
        ]);
    }
}
