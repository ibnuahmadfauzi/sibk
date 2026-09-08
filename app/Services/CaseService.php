<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\CaseCoordination;
use App\Models\ExternalTatibRecord;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CaseService
{
    public function __construct(
        private readonly StudentIdentityService $studentIdentityService,
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * @param  array{student_id?: int|null, temporary_nisn?: string|null, temporary_name?: string|null, case_source_id: int, service_field_id: int, service_date: string, referrer?: string|null, initial_info: string, initial_action: string, internal_note?: string|null, etatib_record_ids?: list<int>}  $data
     */
    public function createCase(array $data, User $actor): BkCase
    {
        return DB::transaction(function () use ($data, $actor): BkCase {
            $student = null;
            $temporaryStudent = null;

            if (($data['student_id'] ?? null) !== null) {
                $student = Student::query()->findOrFail($data['student_id']);
                $inScope = Student::query()
                    ->forActiveTeacherAssignment($actor, now())
                    ->whereKey($student->getKey())
                    ->exists();

                if (! $inScope) {
                    throw ValidationException::withMessages([
                        'student_id' => 'Murid tidak berada dalam scope aktif Anda.',
                    ]);
                }
            } else {
                $temporaryStudent = $this->studentIdentityService->createTemporary(
                    (string) $data['temporary_nisn'],
                    (string) $data['temporary_name'],
                    $actor,
                );
            }

            $source = $this->reference('case_source',(int) $data['case_source_id']);
            $this->reference('service_field',(int) $data['service_field_id']);
            $status = $this->referenceByCode('case_status', 'baru');
            $nisn = $student?->nisn ?? $temporaryStudent?->nisn ?? '';
            $etatibIds = $data['etatib_record_ids'] ?? [];
            $etatibRecords = ExternalTatibRecord::query()
                ->active()
                ->whereIn('id', $etatibIds)
                ->get();

            if ($etatibRecords->count() !== count(array_unique($etatibIds))
                || $etatibRecords->contains(fn (ExternalTatibRecord $record): bool => $record->nisn !== $nisn)) {
                throw ValidationException::withMessages([
                    'etatib_record_ids' => 'Data e-Tatib tidak tersedia atau tidak sesuai dengan NISN murid.',
                ]);
            }

            if ($source->code === 'e_tatib' && $etatibRecords->isEmpty()) {
                throw ValidationException::withMessages([
                    'etatib_record_ids' => 'Kasus yang bersumber dari e-Tatib wajib menautkan data pelanggaran resmi.',
                ]);
            }

            $case = BkCase::query()->create([
                'student_id' => $student?->getKey(),
                'temporary_student_id' => $temporaryStudent?->getKey(),
                'case_source_id' => $source->getKey(),
                'service_field_id' => $data['service_field_id'],
                'status_id' => $status->getKey(),
                'service_date' => $data['service_date'],
                'referrer' => $data['referrer'] ?? null,
                'initial_info' => $data['initial_info'],
                'initial_action' => $data['initial_action'],
                'internal_note' => $data['internal_note'] ?? null,
                'created_by' => $actor->getKey(),
            ]);
            $case->update([
                'registration_number' => sprintf(
                    'K-%s-%04d',
                    $case->service_date->format('Y'),
                    $case->getKey(),
                ),
            ]);

            CaseAssignment::query()->create([
                'case_id' => $case->getKey(),
                'user_id' => $actor->getKey(),
                'assignment_type' => CaseAssignment::TYPE_OWNER,
                'effective_from' => $case->service_date->toDateString(),
                'reason' => 'Penanggung jawab awal saat kasus dibuat.',
                'assigned_by' => $actor->getKey(),
            ]);

            foreach ($etatibRecords as $record) {
                $case->etatibRecords()->attach($record->getKey(), ['linked_by' => $actor->getKey()]);
            }

            $this->auditService->record(
                action: 'case.created',
                auditable: $case,
                summary: sprintf('Kasus %s dibuat.', $case->registration_number),
                actor: $actor,
                after: $this->snapshot($case),
            );

            return $case->load(['student', 'temporaryStudent', 'source', 'serviceField', 'status', 'assignments.teacher', 'etatibRecords']);
        });
    }

    /** @param array{closed_at: string, final_result: string, resolution_summary: string, continued_plan?: string|null} $data */
    public function resolve(BkCase $case, array $data, User $actor): BkCase
    {
        return DB::transaction(function () use ($case, $data, $actor): BkCase {
            $case = BkCase::query()->lockForUpdate()->findOrFail($case->getKey());

            if ($case->closed_at !== null) {
                throw ValidationException::withMessages(['case' => 'Kasus sudah selesai.']);
            }

            if (! $case->hasActiveAssignmentFor($actor)) {
                throw ValidationException::withMessages(['case' => 'Anda tidak memiliki penugasan aktif pada kasus ini.']);
            }

            if ($data['closed_at'] < $case->service_date->toDateString()) {
                throw ValidationException::withMessages([
                    'closed_at' => 'Tanggal selesai tidak boleh sebelum tanggal layanan.',
                ]);
            }

            $before = $this->snapshot($case);
            $status = $this->referenceByCode('case_status', 'selesai');
            $case->update([
                'status_id' => $status->getKey(),
                'closed_at' => $data['closed_at'],
                'final_result' => $data['final_result'],
                'resolution_summary' => $data['resolution_summary'],
                'continued_plan' => $data['continued_plan'] ?? null,
            ]);

            $this->auditService->record(
                action: 'case.resolved',
                auditable: $case,
                summary: sprintf('Kasus %s diselesaikan.', $case->registration_number),
                actor: $actor,
                before: $before,
                after: $this->snapshot($case->refresh()),
            );

            return $case->load('status');
        });
    }

    public function applyApprovedCorrection(BkCase $case, string $field, ?string $value, User $coordinator): BkCase
    {
        return DB::transaction(function () use ($case, $field, $value, $coordinator): BkCase {
            if (! $coordinator->hasRole('koordinator_bk')) {
                abort(403);
            }

            $case = BkCase::query()->lockForUpdate()->findOrFail($case->getKey());
            if (! in_array($field, ['service_date', 'service_field_id', 'initial_action'], true)) {
                throw ValidationException::withMessages(['field_name' => 'Atribut kasus tidak dapat dikoreksi melalui alur ini.']);
            }

            if ($field === 'service_field_id') {
                ReferenceValue::query()->active()->forCategory('service_field')->findOrFail((int) $value);
                $value = (string) ((int) $value);
            }
            if ($field === 'initial_action' && blank($value)) {
                throw ValidationException::withMessages(['proposed_value' => 'Penanganan awal tidak boleh kosong.']);
            }
            if ($field === 'service_date' && $case->closed_at !== null && $value > $case->closed_at->toDateString()) {
                throw ValidationException::withMessages(['proposed_value' => 'Tanggal layanan tidak boleh setelah tanggal penyelesaian kasus.']);
            }

            $before = [$field => $this->correctionValue($case, $field)];
            $case->update([$field => $value]);
            $this->auditService->record(
                action: 'case.corrected',
                auditable: $case,
                summary: sprintf('Atribut %s pada kasus %s dikoreksi melalui pengajuan terverifikasi.', $field, $case->registration_number),
                actor: $coordinator,
                before: $before,
                after: [$field => $this->correctionValue($case->refresh(), $field)],
            );

            return $case;
        });
    }

    private function correctionValue(BkCase $case, string $field): ?string
    {
        if ($field === 'service_date') {
            return $case->service_date?->toDateString();
        }

        return $case->{$field} === null ? null : (string) $case->{$field};
    }

    /** @param array{waka_user_id: int, coordination_need: string} $data */
    public function coordinate(BkCase $case, array $data, User $actor): CaseCoordination
    {
        return DB::transaction(function () use ($case, $data, $actor): CaseCoordination {
            if ($case->closed_at !== null) {
                throw ValidationException::withMessages(['case' => 'Kasus yang sudah selesai tidak dapat dikoordinasikan.']);
            }

            $waka = User::query()->with('roles')->findOrFail($data['waka_user_id']);
            if (! $waka->is_active || ! $waka->hasRole('waka_kesiswaan')) {
                throw ValidationException::withMessages([
                    'waka_user_id' => 'Tujuan koordinasi harus merupakan Waka Kesiswaan aktif.',
                ]);
            }

            $waiting = $this->referenceByCode('coordination_status', 'menunggu');
            if ($case->coordinations()
                ->where('waka_user_id', $waka->getKey())
                ->where('status_id', $waiting->getKey())
                ->exists()) {
                throw ValidationException::withMessages([
                    'waka_user_id' => 'Koordinasi aktif kepada Waka tersebut sudah tersedia.',
                ]);
            }

            $coordination = CaseCoordination::query()->create([
                'case_id' => $case->getKey(),
                'waka_user_id' => $waka->getKey(),
                'status_id' => $waiting->getKey(),
                'coordination_need' => $data['coordination_need'],
                'recorded_by' => $actor->getKey(),
                'coordinated_at' => now(),
            ]);

            $this->auditService->record(
                action: 'case.coordinated',
                auditable: $case,
                summary: sprintf('Kasus %s dikoordinasikan kepada Waka Kesiswaan.', $case->registration_number),
                actor: $actor,
                after: [
                    'coordination_id' => $coordination->getKey(),
                    'waka_user_id' => $waka->getKey(),
                    'status' => $waiting->code,
                ],
            );
            $this->notificationService->send(
                recipients: collect([$waka]),
                category: UserNotification::CATEGORY_COORDINATION,
                title: sprintf('Koordinasi kasus %s diterima.', $case->registration_number),
                message: 'Kasus dikoordinasikan kepada Anda dalam mode hanya-baca.',
                target: $case,
                actionRoute: 'cases.show',
                actionParameters: ['case' => $case->getKey()],
                deduplicationKey: 'case-coordination-created:'.$coordination->getKey(),
            );

            return $coordination->load(['waka', 'status']);
        });
    }

    /** @param array{status_id: int, result?: string|null} $data */
    public function updateCoordination(
        BkCase $case,
        CaseCoordination $coordination,
        array $data,
        User $actor,
    ): CaseCoordination {
        return DB::transaction(function () use ($case, $coordination, $data, $actor): CaseCoordination {
            if ($coordination->case_id !== $case->getKey()) {
                abort(404);
            }

            $coordination->loadMissing('status');
            if ($coordination->status->code !== 'menunggu') {
                throw ValidationException::withMessages(['status_id' => 'Koordinasi ini sudah ditutup.']);
            }

            $status = $this->reference('coordination_status', $data['status_id']);
            if (! in_array($status->code, ['selesai', 'dibatalkan'], true)) {
                throw ValidationException::withMessages([
                    'status_id' => 'Status koordinasi hanya dapat diselesaikan atau dibatalkan.',
                ]);
            }

            $before = [
                'status_id' => $coordination->status_id,
                'result' => $coordination->result,
            ];
            $coordination->update([
                'status_id' => $status->getKey(),
                'result' => $data['result'] ?? null,
            ]);

            $this->auditService->record(
                action: 'case.coordination_updated',
                auditable: $case,
                summary: sprintf('Status koordinasi kasus %s diperbarui.', $case->registration_number),
                actor: $actor,
                before: $before,
                after: ['status_id' => $status->getKey(), 'result' => $coordination->result],
            );
            $coordination->loadMissing('waka');
            $this->notificationService->send(
                recipients: collect([$coordination->waka]),
                category: UserNotification::CATEGORY_CHANGE,
                title: sprintf('Koordinasi kasus %s diperbarui.', $case->registration_number),
                message: sprintf('Status koordinasi berubah menjadi %s.', $status->label),
                target: $case,
                actionRoute: 'cases.show',
                actionParameters: ['case' => $case->getKey()],
                deduplicationKey: 'case-coordination-updated:'.$coordination->getKey().':'.$status->getKey(),
            );

            return $coordination->load('status');
        });
    }

    private function reference(string $category, int $id): ReferenceValue
    {
        return ReferenceValue::query()
            ->active()
            ->where('category', $category)
            ->findOrFail($id);
    }

    private function referenceByCode(string $category, string $code): ReferenceValue
    {
        return ReferenceValue::query()
            ->active()
            ->where('category', $category)
            ->where('code', $code)
            ->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function snapshot(BkCase $case): array
    {
        return [
            'registration_number' => $case->registration_number,
            'student_id' => $case->student_id,
            'temporary_student_id' => $case->temporary_student_id,
            'case_source_id' => $case->case_source_id,
            'service_field_id' => $case->service_field_id,
            'status_id' => $case->status_id,
            'service_date' => $case->service_date?->toDateString(),
            'closed_at' => $case->closed_at?->toDateString(),
            'final_result' => $case->final_result,
        ];
    }
}
