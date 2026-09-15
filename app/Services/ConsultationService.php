<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BkCase;
use App\Models\Consultation;
use App\Models\ConsultationPrivateNote;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\TemporaryStudent;
use App\Models\User;
use App\Support\ServiceRecordStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsultationService
{
    public function __construct(
        private readonly StudentIdentityService $studentIdentityService,
        private readonly AuditService $auditService,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Consultation
    {
        return DB::transaction(function () use ($data, $actor): Consultation {
            [$student, $temporaryStudent] = $this->resolveIdentity($data, $actor);
            $case = $this->resolveCase($data['case_id'] ?? null, $student, $temporaryStudent, $actor);
            $status = $this->reference('consultation_status', (int) $data['status_id']);
            $this->reference('service_field', (int) $data['service_field_id']);
            $this->validateSchedule($data, $status);

            $consultation = Consultation::query()->create([
                'student_id' => $student?->getKey(),
                'temporary_student_id' => $temporaryStudent?->getKey(),
                'case_id' => $case?->getKey(),
                'service_field_id' => $data['service_field_id'],
                'status_id' => $status->getKey(),
                'topic' => $data['topic'],
                'referral_source' => $data['referral_source'] ?? null,
                'session_date' => $data['session_date'],
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'follow_up_date' => $data['follow_up_date'] ?? null,
                'general_summary' => $data['general_summary'] ?? null,
                'counselor_id' => $actor->getKey(),
            ]);
            $consultation->update([
                'registration_number' => sprintf(
                    'KNS-%s-%04d',
                    $consultation->session_date->format('Y'),
                    $consultation->getKey(),
                ),
            ]);

            ConsultationPrivateNote::query()->create([
                'consultation_id' => $consultation->getKey(),
                'internal_note' => $data['internal_note'] ?? null,
                'sensitive_content' => $data['sensitive_content'] ?? null,
                'conclusion' => $data['conclusion'] ?? null,
                'follow_up_plan' => $data['follow_up_plan'] ?? null,
                'updated_by' => $actor->getKey(),
            ]);

            $this->auditService->record(
                action: 'consultation.created',
                auditable: $consultation,
                summary: sprintf('Konsultasi %s dibuat.', $consultation->registration_number),
                actor: $actor,
                after: $this->auditSnapshot($consultation, $data),
            );

            return $consultation->load(['student', 'temporaryStudent.reconciledStudent', 'case', 'serviceField', 'status', 'counselor']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Consultation $consultation, array $data, User $actor): Consultation
    {
        return DB::transaction(function () use ($consultation, $data, $actor): Consultation {
            $consultation = Consultation::query()->lockForUpdate()->findOrFail($consultation->getKey());
            $consultation->loadMissing('status');
            $isCompleted = $consultation->status?->code === ServiceRecordStatus::COMPLETED;
            if ($consultation->counselor_id !== $actor->getKey() || ! $consultation->isProfessionallyAccessibleTo($actor)) {
                throw ValidationException::withMessages([
                    'consultation' => 'Konsultasi hanya dapat diubah oleh pencatat yang masih memiliki kewenangan.',
                ]);
            }

            $consultation->loadMissing(['student', 'temporaryStudent']);
            $case = $this->resolveCase(
                $data['case_id'] ?? null,
                $consultation->student,
                $consultation->temporaryStudent,
                $actor,
            );
            $status = $this->reference('consultation_status', (int) $data['status_id']);
            $reasonLength = mb_strlen(trim((string) ($data['change_reason'] ?? '')));
            if ($isCompleted && ($reasonLength < 10 || $reasonLength > 500)) {
                throw ValidationException::withMessages([
                    'change_reason' => 'Alasan perubahan wajib diisi untuk data yang telah selesai.',
                ]);
            }
            if ($isCompleted && $status->getKey() !== $consultation->status_id) {
                throw ValidationException::withMessages([
                    'status_id' => 'Status data yang telah selesai tidak dapat diubah.',
                ]);
            }
            if (! $isCompleted && ServiceRecordStatus::isTerminal($status->code)) {
                throw ValidationException::withMessages([
                    'status_id' => 'Gunakan tindakan penyelesaian untuk status selesai.',
                ]);
            }
            $this->reference('service_field', (int) $data['service_field_id']);
            $this->validateSchedule($data, $status);

            $before = $this->auditSnapshot($consultation, []);
            $consultation->update([
                'case_id' => $case?->getKey(),
                'service_field_id' => $data['service_field_id'],
                'status_id' => $status->getKey(),
                'topic' => $data['topic'],
                'referral_source' => $data['referral_source'] ?? null,
                'session_date' => $data['session_date'],
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'follow_up_date' => $data['follow_up_date'] ?? null,
                'general_summary' => $data['general_summary'] ?? null,
            ]);

            $privateNote = $consultation->privateNote()->withTrashed()->first();
            $changedPrivateFields = $this->changedPrivateFields($privateNote, $data);
            if ($privateNote === null) {
                $privateNote = new ConsultationPrivateNote(['consultation_id' => $consultation->getKey()]);
            }
            if ($privateNote->trashed()) {
                $privateNote->restore();
            }
            $privateNote->fill([
                'internal_note' => $data['internal_note'] ?? null,
                'sensitive_content' => $data['sensitive_content'] ?? null,
                'conclusion' => $data['conclusion'] ?? null,
                'follow_up_plan' => $data['follow_up_plan'] ?? null,
                'updated_by' => $actor->getKey(),
            ])->save();

            $after = $this->auditSnapshot($consultation->refresh(), $data, $changedPrivateFields);
            if ($isCompleted) {
                $after['change_reason'] = trim((string) $data['change_reason']);
            }
            $this->auditService->record(
                action: $isCompleted ? 'consultation.completed_record_updated' : 'consultation.updated',
                auditable: $consultation,
                summary: sprintf('Konsultasi %s diperbarui.', $consultation->registration_number),
                actor: $actor,
                before: $before,
                after: $after,
            );

            return $consultation->load(['student', 'temporaryStudent.reconciledStudent', 'case', 'serviceField', 'status', 'counselor']);
        });
    }

    public function archive(Consultation $consultation, User $actor): void
    {
        DB::transaction(function () use ($consultation, $actor): void {
            $consultation = Consultation::query()->lockForUpdate()->findOrFail($consultation->getKey());
            if ($consultation->counselor_id !== $actor->getKey() || ! $consultation->isProfessionallyAccessibleTo($actor)) {
                throw ValidationException::withMessages([
                    'consultation' => 'Konsultasi hanya dapat diarsipkan oleh pencatat yang masih memiliki kewenangan.',
                ]);
            }

            $this->auditService->record(
                action: 'consultation.archived',
                auditable: $consultation,
                summary: sprintf('Konsultasi %s diarsipkan.', $consultation->registration_number),
                actor: $actor,
                before: $this->auditSnapshot($consultation, []),
            );
            $consultation->delete();
        });
    }

    /** @param array<string, mixed> $data @return array{?Student, ?TemporaryStudent} */
    private function resolveIdentity(array $data, User $actor): array
    {
        if (($data['student_id'] ?? null) !== null) {
            $student = Student::query()->findOrFail($data['student_id']);
            if (! Student::query()->availableForService()->professionallyAccessibleTo($actor)->whereKey($student->getKey())->exists()) {
                throw ValidationException::withMessages(['student_id' => 'Murid tidak berada dalam kewenangan profesional Anda.']);
            }

            return [$student, null];
        }

        if (($data['case_id'] ?? null) !== null) {
            $case = BkCase::query()->with('temporaryStudent')->findOrFail((int) $data['case_id']);
            if ($case->temporaryStudent !== null && $case->temporaryStudent->nisn === (string) $data['temporary_nisn']) {
                return [null, $case->temporaryStudent];
            }
        }

        return [
            null,
            $this->studentIdentityService->createTemporary(
                (string) $data['temporary_nisn'],
                (string) $data['temporary_name'],
                $actor,
            ),
        ];
    }

    private function resolveCase(mixed $caseId, ?Student $student, ?TemporaryStudent $temporary, User $actor): ?BkCase
    {
        if ($caseId === null || $caseId === '') {
            return null;
        }

        $case = BkCase::query()->lockForUpdate()->findOrFail((int) $caseId);
        if (! $case->hasActiveOwnerFor($actor)) {
            throw ValidationException::withMessages(['case_id' => 'Kasus terkait bukan tanggung jawab aktif Anda.']);
        }

        $sameIdentity = ($student !== null && $case->student_id === $student->getKey())
            || ($temporary !== null && $case->temporary_student_id === $temporary->getKey());
        if (! $sameIdentity) {
            throw ValidationException::withMessages(['case_id' => 'Kasus terkait harus memiliki identitas murid yang sama.']);
        }

        return $case;
    }

    /** @param array<string, mixed> $data */
    private function validateSchedule(array $data, ReferenceValue $status): void
    {
        $sessionDate = CarbonImmutable::parse((string) $data['session_date'])->startOfDay();
        if (($data['starts_at'] ?? null) !== null && ($data['ends_at'] ?? null) !== null) {
            $start = CarbonImmutable::parse($sessionDate->toDateString().' '.$data['starts_at']);
            $end = CarbonImmutable::parse($sessionDate->toDateString().' '.$data['ends_at']);
            if ($end->lte($start)) {
                throw ValidationException::withMessages(['ends_at' => 'Jam selesai harus setelah jam mulai.']);
            }
        }

        if ($status->code === ServiceRecordStatus::COMPLETED) {
            if ($sessionDate->isFuture()) {
                throw ValidationException::withMessages(['session_date' => 'Sesi terlaksana tidak boleh berada di masa depan.']);
            }
            if (blank($data['general_summary'] ?? null)) {
                throw ValidationException::withMessages(['general_summary' => 'Ringkasan umum wajib diisi untuk sesi yang terlaksana.']);
            }
        }

        if (($data['follow_up_date'] ?? null) !== null
            && CarbonImmutable::parse((string) $data['follow_up_date'])->startOfDay()->lt($sessionDate)) {
            throw ValidationException::withMessages(['follow_up_date' => 'Jadwal tindak lanjut tidak boleh sebelum tanggal sesi.']);
        }
    }

    private function reference(string $category, int $id): ReferenceValue
    {
        return ReferenceValue::query()->active()->where('category', $category)->findOrFail($id);
    }

    /** @param array<string, mixed> $data @param list<string> $changedPrivateFields @return array<string, mixed> */
    private function auditSnapshot(Consultation $consultation, array $data, array $changedPrivateFields = []): array
    {
        return [
            'registration_number' => $consultation->registration_number,
            'student_id' => $consultation->student_id,
            'temporary_student_id' => $consultation->temporary_student_id,
            'case_id' => $consultation->case_id,
            'service_field_id' => $consultation->service_field_id,
            'status_id' => $consultation->status_id,
            'session_date' => $consultation->session_date?->toDateString(),
            'starts_at' => $consultation->starts_at,
            'ends_at' => $consultation->ends_at,
            'follow_up_date' => $consultation->follow_up_date?->toDateString(),
            'counselor_id' => $consultation->counselor_id,
            'private_fields_present' => $changedPrivateFields !== []
                ? $changedPrivateFields
                : $this->presentPrivateFields($data),
        ];
    }

    /** @param array<string, mixed> $data @return list<string> */
    private function presentPrivateFields(array $data): array
    {
        return collect(['internal_note', 'sensitive_content', 'conclusion', 'follow_up_plan'])
            ->filter(fn (string $field): bool => filled($data[$field] ?? null))
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $data @return list<string> */
    private function changedPrivateFields(?ConsultationPrivateNote $note, array $data): array
    {
        return collect(['internal_note', 'sensitive_content', 'conclusion', 'follow_up_plan'])
            ->filter(fn (string $field): bool => (string) ($note?->{$field} ?? '') !== (string) ($data[$field] ?? ''))
            ->values()
            ->all();
    }
}
