<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BkCase;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FollowUpService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
    ) {}

    /** @param array{follow_up_type_id: int, status_id: int, planned_date: string, execution_date?: string|null, result?: string|null, next_plan?: string|null} $data */
    public function record(BkCase $case, array $data, User $actor): FollowUp
    {
        return DB::transaction(function () use ($case, $data, $actor): FollowUp {
            $case = BkCase::query()->with('status')->lockForUpdate()->findOrFail($case->getKey());
            $this->validateOpenCase($case, $actor);
            $status = $this->validateReferencesAndResult($data);

            $followUp = FollowUp::query()->create([
                'case_id' => $case->getKey(),
                'follow_up_type_id' => $data['follow_up_type_id'],
                'status_id' => $status->getKey(),
                'planned_date' => $data['planned_date'],
                'execution_date' => $data['execution_date'] ?? null,
                'result' => $data['result'] ?? null,
                'next_plan' => $data['next_plan'] ?? null,
                'recorded_by' => $actor->getKey(),
            ]);

            if ($case->status->code === 'baru') {
                $handling = ReferenceValue::query()
                    ->where('category', 'case_status')
                    ->where('code', 'dalam_penanganan')
                    ->firstOrFail();
                $case->update(['status_id' => $handling->getKey()]);
                $this->auditService->record(
                    action: 'case.handling_started',
                    auditable: $case,
                    summary: sprintf('Penanganan kasus %s dimulai.', $case->registration_number),
                    actor: $actor,
                    before: ['status' => 'baru'],
                    after: ['status' => 'dalam_penanganan'],
                );
            }

            $this->auditService->record(
                action: 'follow_up.created',
                auditable: $followUp,
                summary: sprintf('Tindak lanjut kasus %s dicatat.', $case->registration_number),
                actor: $actor,
                after: $this->snapshot($followUp),
            );
            $this->notifySchedule($case, $followUp, 'created');

            return $followUp->load(['type', 'status', 'recorder']);
        });
    }

    /** @param array{follow_up_type_id: int, status_id: int, planned_date: string, execution_date?: string|null, result?: string|null, next_plan?: string|null} $data */
    public function update(BkCase $case, FollowUp $followUp, array $data, User $actor): FollowUp
    {
        return DB::transaction(function () use ($case, $followUp, $data, $actor): FollowUp {
            if ($followUp->case_id !== $case->getKey()) {
                abort(404);
            }

            $this->validateOpenCase($case, $actor);
            if ($followUp->recorded_by !== $actor->getKey()) {
                throw ValidationException::withMessages([
                    'follow_up' => 'Tindak lanjut lama hanya dapat diubah oleh pencatat aslinya.',
                ]);
            }

            $status = $this->validateReferencesAndResult($data);
            $before = $this->snapshot($followUp);
            $followUp->update([
                'follow_up_type_id' => $data['follow_up_type_id'],
                'status_id' => $status->getKey(),
                'planned_date' => $data['planned_date'],
                'execution_date' => $data['execution_date'] ?? null,
                'result' => $data['result'] ?? null,
                'next_plan' => $data['next_plan'] ?? null,
            ]);

            $this->auditService->record(
                action: 'follow_up.updated',
                auditable: $followUp,
                summary: sprintf('Tindak lanjut kasus %s diperbarui.', $case->registration_number),
                actor: $actor,
                before: $before,
                after: $this->snapshot($followUp->refresh()),
            );
            $this->notifySchedule($case, $followUp, 'updated');

            return $followUp->load(['type', 'status', 'recorder']);
        });
    }

    public function applyApprovedCorrection(FollowUp $followUp, string $field, ?string $value, User $coordinator): FollowUp
    {
        return DB::transaction(function () use ($followUp, $field, $value, $coordinator): FollowUp {
            if (! $coordinator->hasRole('koordinator_bk')) {
                abort(403);
            }

            $followUp = FollowUp::query()->with('case')->lockForUpdate()->findOrFail($followUp->getKey());
            $allowed = ['follow_up_type_id', 'status_id', 'planned_date', 'execution_date', 'result', 'next_plan'];
            if (! in_array($field, $allowed, true)) {
                throw ValidationException::withMessages(['field_name' => 'Atribut tindak lanjut tidak dapat dikoreksi melalui alur ini.']);
            }

            $data = [
                'follow_up_type_id' => $followUp->follow_up_type_id,
                'status_id' => $followUp->status_id,
                'planned_date' => $followUp->planned_date->toDateString(),
                'execution_date' => $followUp->execution_date?->toDateString(),
                'result' => $followUp->result,
                'next_plan' => $followUp->next_plan,
            ];
            $data[$field] = in_array($field, ['follow_up_type_id', 'status_id'], true) ? (int) $value : $value;
            $this->validateReferencesAndResult($data);
            $before = $this->snapshot($followUp);
            $followUp->update($data);
            $this->auditService->record(
                action: 'follow_up.corrected',
                auditable: $followUp,
                summary: sprintf('Tindak lanjut kasus %s dikoreksi melalui pengajuan terverifikasi.', $followUp->case->registration_number),
                actor: $coordinator,
                before: $before,
                after: $this->snapshot($followUp->refresh()),
            );

            return $followUp;
        });
    }

    private function validateOpenCase(BkCase $case, User $actor): void
    {
        if ($case->closed_at !== null) {
            throw ValidationException::withMessages(['case' => 'Kasus yang sudah selesai tidak dapat diubah.']);
        }

        if (! $case->hasActiveAssignmentFor($actor)) {
            throw ValidationException::withMessages(['case' => 'Anda tidak memiliki penugasan aktif pada kasus ini.']);
        }
    }

    private function notifySchedule(BkCase $case, FollowUp $followUp, string $event): void
    {
        $this->notificationService->send(
            recipients: $this->notificationService->activeCaseTeachers($case),
            category: UserNotification::CATEGORY_SCHEDULE,
            title: sprintf('Jadwal tindak lanjut kasus %s.', $case->registration_number),
            message: sprintf('Tindak lanjut dijadwalkan pada %s.', $followUp->planned_date->format('d-m-Y')),
            target: $followUp,
            actionRoute: 'cases.show',
            actionParameters: ['case' => $case->getKey()],
            deduplicationKey: sprintf('follow-up-%s:%d:%s', $event, $followUp->getKey(), md5($followUp->updated_at?->toJSON() ?? $event)),
        );
    }

    /** @param array{follow_up_type_id: int, status_id: int, planned_date: string, execution_date?: string|null, result?: string|null, next_plan?: string|null} $data */
    private function validateReferencesAndResult(array $data): ReferenceValue
    {
        ReferenceValue::query()
            ->active()
            ->where('category', 'follow_up_type')
            ->findOrFail($data['follow_up_type_id']);
        $status = ReferenceValue::query()
            ->active()
            ->where('category', 'follow_up_status')
            ->findOrFail($data['status_id']);

        if ($status->code === 'terlaksana'
            && (($data['execution_date'] ?? null) === null || blank($data['result'] ?? null))) {
            throw ValidationException::withMessages([
                'execution_date' => 'Tanggal pelaksanaan wajib diisi untuk tindak lanjut yang terlaksana.',
                'result' => 'Hasil wajib diisi untuk tindak lanjut yang terlaksana.',
            ]);
        }

        return $status;
    }

    /** @return array<string, mixed> */
    private function snapshot(FollowUp $followUp): array
    {
        return [
            'case_id' => $followUp->case_id,
            'follow_up_type_id' => $followUp->follow_up_type_id,
            'status_id' => $followUp->status_id,
            'planned_date' => $followUp->planned_date?->toDateString(),
            'execution_date' => $followUp->execution_date?->toDateString(),
            'result' => $followUp->result,
            'next_plan' => $followUp->next_plan,
            'recorded_by' => $followUp->recorded_by,
        ];
    }
}
