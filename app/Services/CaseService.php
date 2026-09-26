<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\ExternalTatibRecord;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Support\ServiceRecordStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
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
            $temporaryClassroom = null;

            if (($data['student_id'] ?? null) !== null) {
                $student = Student::query()->lockForUpdate()->find((int) $data['student_id']);
                if ($student === null || ! Student::query()
                    ->availableForService((string) $data['service_date'])
                    ->forActiveTeacherAssignment($actor)
                    ->whereKey($student->getKey())
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'student_id' => 'Murid ini tidak dapat Anda tangani pada tanggal layanan.',
                    ]);
                }
            } else {
                $student = Student::query()
                    ->availableForService((string) $data['service_date'])
                    ->forActiveTeacherAssignment($actor)
                    ->where('nisn', trim((string) $data['temporary_nisn']))
                    ->lockForUpdate()
                    ->first();

                if ($student === null) {
                    $temporaryClassroom = $this->studentIdentityService->assignedClassroom(
                        (int) ($data['temporary_classroom_id'] ?? 0), $actor,
                    );
                    $temporaryStudent = $this->studentIdentityService->createTemporary(
                        (string) $data['temporary_nisn'],
                        (string) $data['temporary_name'],
                        $actor,
                    );
                }
            }

            $source = $this->reference('case_source', (int) $data['case_source_id']);
            $this->reference('service_field', (int) $data['service_field_id']);
            $status = $this->referenceByCode('case_status', ServiceRecordStatus::IN_PROGRESS);
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

            $membership = $student === null ? null : StudentClassMembership::query()
                ->active()
                ->where('student_id', $student->getKey())
                ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                ->first();

            $case = BkCase::query()->create([
                'student_id' => $student?->getKey(),
                'temporary_student_id' => $temporaryStudent?->getKey(),
                'academic_year_id' => $membership?->academic_year_id ?? $temporaryClassroom?->academic_year_id,
                'classroom_id' => $membership?->classroom_id ?? $temporaryClassroom?->id,
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

    /** @param array{initial_info: string, initial_action: string, resolution_summary?: string|null, action: string, expected_updated_at: string} $data */
    public function update(BkCase $case, array $data, User $actor): BkCase
    {
        return DB::transaction(function () use ($case, $data, $actor): BkCase {
            $case = BkCase::query()->lockForUpdate()->findOrFail($case->getKey());
            $this->assertActiveYear($case);
            if (! $case->isOwnedBy($actor)) {
                throw ValidationException::withMessages(['case' => 'Anda bukan penanggung jawab aktif kasus ini.']);
            }

            $this->assertFresh($case, $data['expected_updated_at']);
            $before = $this->editableSnapshot($case);
            $changes = [
                'initial_info' => $data['initial_info'],
                'initial_action' => $data['initial_action'],
                'resolution_summary' => $data['resolution_summary'] ?? null,
            ];
            if ($data['action'] === 'complete') {
                $changes['status_id'] = $this->referenceByCode('case_status', ServiceRecordStatus::COMPLETED)->getKey();
                $changes['closed_at'] = today()->toDateString();
            }

            $case->update($changes);
            $case->refresh();
            $this->auditService->recordChanges(
                action: 'case.updated',
                auditable: $case,
                summary: sprintf('Kasus untuk %s diperbarui.', $case->identityName()),
                actor: $actor,
                before: $before,
                after: $this->editableSnapshot($case),
            );

            return $case->load(['status', 'followUpType']);
        });
    }

    public function updateFollowUp(
        BkCase $case,
        ?int $typeId,
        string $expectedUpdatedAt,
        User $actor,
    ): BkCase {
        return DB::transaction(function () use ($case, $typeId, $expectedUpdatedAt, $actor): BkCase {
            $case = BkCase::query()->with('status')->lockForUpdate()->findOrFail($case->getKey());
            $this->assertActiveYear($case);
            if (! $case->isOwnedBy($actor)) {
                throw ValidationException::withMessages(['case' => 'Anda bukan penanggung jawab aktif kasus ini.']);
            }
            if (ServiceRecordStatus::isTerminal($case->status?->code)) {
                throw ValidationException::withMessages(['case' => 'Tindak lanjut kasus selesai tidak dapat diubah.']);
            }

            $this->assertFresh($case, $expectedUpdatedAt);
            $type = $typeId === null ? null : $this->reference('follow_up_type', $typeId);
            $status = $this->referenceByCode(
                'case_status',
                $type === null ? ServiceRecordStatus::IN_PROGRESS : ServiceRecordStatus::NEEDS_FOLLOW_UP,
            );
            $before = ['follow_up_type_id' => $case->follow_up_type_id, 'status_id' => $case->status_id];
            $case->update(['follow_up_type_id' => $type?->getKey(), 'status_id' => $status->getKey()]);
            $case->refresh();
            $this->auditService->recordChanges(
                action: 'case.follow_up_updated',
                auditable: $case,
                summary: sprintf('Tindak lanjut kasus untuk %s diperbarui.', $case->identityName()),
                actor: $actor,
                before: $before,
                after: ['follow_up_type_id' => $case->follow_up_type_id, 'status_id' => $case->status_id],
            );

            return $case->load(['status', 'followUpType']);
        });
    }

    public function archive(BkCase $case, User $actor): void
    {
        DB::transaction(function () use ($case, $actor): void {
            $case = BkCase::query()->lockForUpdate()->findOrFail($case->getKey());
            $this->assertActiveYear($case);

            if (! $case->isOwnedBy($actor)) {
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

    private function assertActiveYear(BkCase $case): void
    {
        if ($case->academicYear?->is_active !== true) {
            throw ValidationException::withMessages(['case' => 'Kasus tahun ajaran sebelumnya hanya dapat dilihat.']);
        }
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

    private function assertFresh(BkCase $case, string $expectedUpdatedAt): void
    {
        if (! $case->updated_at?->equalTo(CarbonImmutable::parse($expectedUpdatedAt))) {
            throw ValidationException::withMessages([
                'expected_updated_at' => 'Data kasus telah berubah. Muat ulang sebelum menyimpan.',
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function editableSnapshot(BkCase $case): array
    {
        return [
            'initial_info' => $case->initial_info,
            'initial_action' => $case->initial_action,
            'resolution_summary' => $case->resolution_summary,
            'status_id' => $case->status_id,
            'closed_at' => $case->closed_at?->toDateString(),
        ];
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
        ];
    }
}
