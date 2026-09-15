<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BkCase;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\User;
use App\Support\ServiceRecordStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FollowUpService
{
    public function __construct(private readonly AuditService $auditService) {}

    /** @param array{follow_up_type_id: int, status_id: int, planned_date: string, execution_date?: string|null, result?: string|null, next_plan?: string|null} $data */
    public function record(BkCase $case, array $data, User $actor): FollowUp
    {
        return DB::transaction(function () use ($case, $data, $actor): FollowUp {
            $case = BkCase::query()->with('status')->lockForUpdate()->findOrFail($case->getKey());
            $this->validateOpenCase($case, $actor);
            if ($case->status->code === ServiceRecordStatus::NEW && blank($case->waka_summary)) {
                throw ValidationException::withMessages([
                    'waka_summary' => 'Ringkasan Penanganan untuk Waka wajib diisi sebelum tindak lanjut pertama dicatat.',
                ]);
            }
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

            if ($case->status->code === ServiceRecordStatus::NEW) {
                $handling = ReferenceValue::query()
                    ->where('category', 'case_status')
                    ->where('code', ServiceRecordStatus::IN_PROGRESS)
                    ->firstOrFail();
                $case->update(['status_id' => $handling->getKey()]);
                $this->auditService->record(
                    action: 'case.handling_started',
                    auditable: $case,
                    summary: sprintf('Penanganan kasus untuk %s dimulai.', $case->identityName()),
                    actor: $actor,
                    before: ['status' => ServiceRecordStatus::NEW],
                    after: ['status' => ServiceRecordStatus::IN_PROGRESS],
                );
            }

            $this->auditService->record(
                action: 'follow_up.created',
                auditable: $followUp,
                summary: sprintf('Tindak lanjut kasus untuk %s dicatat.', $case->identityName()),
                actor: $actor,
                after: $this->snapshot($followUp),
            );

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
                summary: sprintf('Tindak lanjut kasus untuk %s diperbarui.', $case->identityName()),
                actor: $actor,
                before: $before,
                after: $this->snapshot($followUp->refresh()),
            );

            return $followUp->load(['type', 'status', 'recorder']);
        });
    }

    private function validateOpenCase(BkCase $case, User $actor): void
    {
        $case->loadMissing('status');
        if (ServiceRecordStatus::isTerminal($case->status?->code)) {
            throw ValidationException::withMessages(['case' => 'Kasus terminal tidak dapat diubah.']);
        }

        if (! $case->hasActiveOwnerFor($actor)) {
            throw ValidationException::withMessages(['case' => 'Anda bukan penanggung jawab aktif kasus ini.']);
        }
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
