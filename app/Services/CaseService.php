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
use App\Support\ServiceRecordStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CaseService
{
    public function __construct(
        private readonly StudentIdentityService $studentIdentityService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * @param  array{student_id?: int|string|null, temporary_nisn?: string|null, temporary_name?: string|null, case_source_id: int|string, service_field_id: int|string, service_date: string, referrer?: string|null, initial_info: string, initial_action: string, internal_note?: string|null, etatib_record_ids?: list<int|string>}  $data
     */
    public function createCase(array $data, User $actor): BkCase
    {
        return DB::transaction(function () use ($data, $actor): BkCase {
            $student = null;
            $temporaryStudent = null;

            if (($data['student_id'] ?? null) !== null) {
                $student = Student::query()->lockForUpdate()->find((int) $data['student_id']);
                if ($student === null || ! Student::query()
                    ->availableForService((string) $data['service_date'])
                    ->forActiveTeacherAssignment($actor, now())
                    ->whereKey($student->getKey())
                    ->exists()) {
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

            $source = $this->reference('case_source', (int) $data['case_source_id']);
            $this->reference('service_field', (int) $data['service_field_id']);
            $status = $this->referenceByCode('case_status', ServiceRecordStatus::NEW);
            $nisn = $student?->nisn ?? $temporaryStudent?->nisn ?? '';
            $etatibIds = $data['etatib_record_ids'] ?? [];
            $etatibRecords = ExternalTatibRecord::query()
                ->active()
                ->whereIn('id', $etatibIds)
                ->when(
                    $student !== null,
                    fn ($records) => $records->where('student_id', $student->getKey()),
                    fn ($records) => $records->whereNull('student_id')->where('nisn', $nisn),
                )
                ->get();

            if ($etatibRecords->count() !== count(array_unique($etatibIds))) {
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
                'service_field_id' => (int) $data['service_field_id'],
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
                summary: sprintf('Kasus untuk %s dibuat.', $case->identityName()),
                actor: $actor,
                after: $this->snapshot($case),
            );

            return $case->load(['student', 'temporaryStudent', 'source', 'serviceField', 'status', 'assignments.teacher', 'etatibRecords']);
        });
    }

    /** @param array{closed_at: string, final_result: string, resolution_summary: string, continued_plan?: string|null, waka_summary: string} $data */
    public function resolve(BkCase $case, array $data, User $actor): BkCase
    {
        return DB::transaction(function () use ($case, $data, $actor): BkCase {
            $case = BkCase::query()->lockForUpdate()->findOrFail($case->getKey());

            $case->loadMissing('status');
            if (ServiceRecordStatus::isTerminal($case->status?->code)) {
                throw ValidationException::withMessages(['case' => 'Kasus terminal tidak dapat diubah.']);
            }

            if (! $case->hasActiveOwnerFor($actor)) {
                throw ValidationException::withMessages(['case' => 'Anda bukan penanggung jawab aktif kasus ini.']);
            }

            $wakaSummary = trim((string) ($data['waka_summary'] ?? ''));
            if ($wakaSummary === '' || mb_strlen($wakaSummary) > 500) {
                throw ValidationException::withMessages([
                    'waka_summary' => 'Ringkasan Penanganan untuk Waka wajib diisi dan maksimal 500 karakter.',
                ]);
            }

            if ($data['closed_at'] < $case->service_date->toDateString()) {
                throw ValidationException::withMessages([
                    'closed_at' => 'Tanggal selesai tidak boleh sebelum tanggal layanan.',
                ]);
            }

            $before = $this->snapshot($case);
            $status = $this->referenceByCode('case_status', ServiceRecordStatus::COMPLETED);
            $case->update([
                'status_id' => $status->getKey(),
                'closed_at' => $data['closed_at'],
                'final_result' => $data['final_result'],
                'resolution_summary' => $data['resolution_summary'],
                'continued_plan' => $data['continued_plan'] ?? null,
                'waka_summary' => $wakaSummary,
            ]);

            $this->auditService->record(
                action: 'case.resolved',
                auditable: $case,
                summary: sprintf('Kasus untuk %s diselesaikan.', $case->identityName()),
                actor: $actor,
                before: $before,
                after: $this->snapshot($case->refresh()),
            );

            return $case->load('status');
        });
    }

    /**
     * @param  array{case_source_id: int|string, service_field_id: int|string, status_id: int|string, service_date: string, referrer?: string|null, initial_info: string, initial_action: string, internal_note?: string|null, waka_summary?: string|null, change_reason?: string|null}  $data
     */
    public function update(BkCase $case, array $data, User $actor): BkCase
    {
        return DB::transaction(function () use ($case, $data, $actor): BkCase {
            $case = BkCase::query()->lockForUpdate()->findOrFail($case->getKey());
            $case->loadMissing('status');
            $isCompleted = $case->status?->code === ServiceRecordStatus::COMPLETED;

            if (! $case->hasActiveOwnerFor($actor)) {
                throw ValidationException::withMessages(['case' => 'Anda bukan penanggung jawab aktif kasus ini.']);
            }

            $validated = Validator::make($data, [
                'case_source_id' => ['required', 'integer'],
                'service_field_id' => ['required', 'integer'],
                'status_id' => ['required', 'integer'],
                'service_date' => ['required', 'date', 'before_or_equal:today'],
                'referrer' => ['nullable', 'string', 'max:150'],
                'initial_info' => ['required', 'string', 'max:10000'],
                'initial_action' => ['required', 'string', 'max:10000'],
                'internal_note' => ['nullable', 'string', 'max:10000'],
                'waka_summary' => ['nullable', 'string', 'max:500'],
                'change_reason' => [$isCompleted ? 'required' : 'nullable', 'nullable', 'string', 'min:10', 'max:500'],
            ])->validate();

            if ($case->student_id !== null) {
                Student::query()->lockForUpdate()->findOrFail($case->student_id);
                if (! Student::query()->availableForService($validated['service_date'])->whereKey($case->student_id)->exists()) {
                    throw ValidationException::withMessages([
                        'service_date' => 'Tanggal layanan harus sebelum tanggal keluar resmi murid.',
                    ]);
                }
            }

            $source = $this->reference('case_source', (int) $validated['case_source_id']);
            $this->reference('service_field', (int) $validated['service_field_id']);
            $status = $this->reference('case_status', (int) $validated['status_id']);
            if ($source->code === 'e_tatib' && ! $case->etatibRecords()->exists()) {
                throw ValidationException::withMessages([
                    'case_source_id' => 'Sumber e-Tatib hanya dapat dipilih jika kasus memiliki record resmi tertaut.',
                ]);
            }
            if ($isCompleted && $status->getKey() !== $case->status_id) {
                throw ValidationException::withMessages([
                    'status_id' => 'Status data yang telah selesai tidak dapat diubah.',
                ]);
            }
            if (! $isCompleted && ServiceRecordStatus::isTerminal($status->code)) {
                throw ValidationException::withMessages([
                    'status_id' => 'Gunakan tindakan penyelesaian untuk status selesai.',
                ]);
            }
            if ($isCompleted && $case->closed_at !== null
                && $validated['service_date'] > $case->closed_at->toDateString()) {
                throw ValidationException::withMessages([
                    'service_date' => 'Tanggal layanan tidak boleh setelah tanggal penyelesaian kasus.',
                ]);
            }
            if ($status->code !== ServiceRecordStatus::NEW && blank($validated['waka_summary'] ?? null)) {
                throw ValidationException::withMessages([
                    'waka_summary' => 'Ringkasan Penanganan untuk Waka wajib diisi ketika kasus mulai diproses.',
                ]);
            }

            $before = $this->snapshot($case);
            $case->update([
                'case_source_id' => $validated['case_source_id'],
                'service_field_id' => $validated['service_field_id'],
                'status_id' => $status->getKey(),
                'service_date' => $validated['service_date'],
                'referrer' => $validated['referrer'] ?? null,
                'initial_info' => $validated['initial_info'],
                'initial_action' => $validated['initial_action'],
                'internal_note' => $validated['internal_note'] ?? null,
                'waka_summary' => filled($validated['waka_summary'] ?? null) ? trim((string) $validated['waka_summary']) : null,
            ]);

            $after = $this->snapshot($case->refresh());
            if ($isCompleted) {
                $after['change_reason'] = trim((string) $validated['change_reason']);
            }
            $this->auditService->record(
                action: $isCompleted ? 'case.completed_record_updated' : 'case.updated',
                auditable: $case,
                summary: sprintf('Kasus untuk %s diperbarui.', $case->identityName()),
                actor: $actor,
                before: $before,
                after: $after,
            );

            return $case->load(['source', 'serviceField', 'status']);
        });
    }

    public function archive(BkCase $case, User $actor): void
    {
        DB::transaction(function () use ($case, $actor): void {
            $case = BkCase::query()->lockForUpdate()->findOrFail($case->getKey());

            if (! $case->hasActiveOwnerFor($actor)) {
                throw ValidationException::withMessages(['case' => 'Anda bukan penanggung jawab aktif kasus ini.']);
            }

            $this->auditService->record(
                action: 'case.archived',
                auditable: $case,
                summary: sprintf('Kasus untuk %s diarsipkan.', $case->identityName()),
                actor: $actor,
                before: $this->snapshot($case),
            );
            $case->delete();
        });
    }

    /** @param array{waka_user_id: int, result?: string, coordination_need?: string} $data */
    public function coordinate(BkCase $case, array $data, User $actor): CaseCoordination
    {
        return DB::transaction(function () use ($case, $data, $actor): CaseCoordination {
            $case = BkCase::query()->with('status')->lockForUpdate()->findOrFail($case->getKey());
            $case->loadMissing('status');
            if (ServiceRecordStatus::isTerminal($case->status?->code)) {
                throw ValidationException::withMessages(['case' => 'Kasus terminal tidak dapat dikoordinasikan.']);
            }
            if (! $actor->hasRole('koordinator_bk') && ! $case->hasActiveOwnerFor($actor)) {
                throw ValidationException::withMessages(['case' => 'Koordinasi hanya dapat dicatat oleh penanggung jawab aktif atau Koordinator BK.']);
            }

            $result = trim((string) ($data['result'] ?? $data['coordination_need'] ?? ''));
            if ($result === '') {
                throw ValidationException::withMessages(['result' => 'Ringkasan hasil koordinasi wajib diisi.']);
            }

            $waka = User::query()->with('roles')->findOrFail($data['waka_user_id']);
            if (! $waka->is_active || ! $waka->hasRole('waka_kesiswaan')) {
                throw ValidationException::withMessages([
                    'waka_user_id' => 'Tujuan koordinasi harus merupakan Waka Kesiswaan aktif.',
                ]);
            }

            $completed = $this->referenceByCode('coordination_status', 'selesai');

            $coordination = CaseCoordination::query()->create([
                'case_id' => $case->getKey(),
                'waka_user_id' => $waka->getKey(),
                'status_id' => $completed->getKey(),
                'coordination_need' => 'Koordinasi dilakukan di luar aplikasi.',
                'result' => $result,
                'recorded_by' => $actor->getKey(),
                'coordinated_at' => now(),
            ]);

            $this->auditService->record(
                action: 'case.coordinated',
                auditable: $case,
                summary: sprintf('Hasil koordinasi kasus untuk %s dicatat.', $case->identityName()),
                actor: $actor,
                after: [
                    'coordination_id' => $coordination->getKey(),
                    'waka_user_id' => $waka->getKey(),
                    'status' => $completed->code,
                ],
            );

            return $coordination->load(['waka', 'status']);
        });
    }

    /** @param array{status_id: int|string, result?: string|null} $data */
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

            if (! $actor->hasRole('koordinator_bk') && ! $case->hasActiveOwnerFor($actor)) {
                throw ValidationException::withMessages(['case' => 'Koordinasi hanya dapat diperbarui oleh penanggung jawab aktif atau Koordinator BK.']);
            }

            $coordination->loadMissing('status');
            if ($coordination->status->code !== 'menunggu') {
                throw ValidationException::withMessages(['status_id' => 'Koordinasi ini sudah ditutup.']);
            }

            $status = $this->reference('coordination_status', (int) $data['status_id']);
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
                summary: sprintf('Status koordinasi kasus untuk %s diperbarui.', $case->identityName()),
                actor: $actor,
                before: $before,
                after: ['status_id' => $status->getKey(), 'result' => $coordination->result],
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
