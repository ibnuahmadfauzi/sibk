<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TemporaryStudent;
use App\Models\User;
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
            $this->reference('service_field', (int) $data['service_field_id']);

            $membership = $student === null ? null : StudentClassMembership::query()
                ->active()
                ->where('student_id', $student->getKey())
                ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                ->first();

            $consultation = new Consultation([
                'student_id' => $student?->getKey(),
                'temporary_student_id' => $temporaryStudent?->getKey(),
                'academic_year_id' => $membership?->academic_year_id,
                'classroom_id' => $membership?->classroom_id,
                'service_field_id' => $data['service_field_id'],
                'session_date' => $data['session_date'],
                'problem' => $data['problem'],
                'handling' => $data['handling'],
                'result' => $data['result'],
                'counselor_id' => $actor->getKey(),
            ]);

            $consultation->save();

            $this->auditService->recordChanges(
                action: 'consultation.created',
                auditable: $consultation,
                summary: sprintf('Konsultasi untuk %s dibuat.', $consultation->identityName()),
                actor: $actor,
                before: [],
                after: $this->snapshot($consultation),
            );

            return $consultation->load(['student', 'temporaryStudent.reconciledStudent', 'serviceField', 'counselor']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Consultation $consultation, array $data, User $actor): Consultation
    {
        return DB::transaction(function () use ($consultation, $data, $actor): Consultation {
            $consultation = Consultation::query()->lockForUpdate()->findOrFail($consultation->getKey());
            $this->assertOwnedAndAccessible($consultation, $actor, 'diubah');

            if (! $consultation->updated_at->equalTo(CarbonImmutable::parse((string) $data['expected_updated_at']))) {
                throw ValidationException::withMessages([
                    'expected_updated_at' => 'Data telah berubah. Muat ulang sebelum menyimpan.',
                ]);
            }

            $studentId = $consultation->student_id
                ?? $consultation->temporaryStudent()->value('reconciled_student_id');
            $this->assertStudentAvailable($studentId, (string) $data['session_date']);

            $this->reference('service_field', (int) $data['service_field_id']);
            $before = $this->snapshot($consultation);
            $consultation->update([
                'service_field_id' => $data['service_field_id'],
                'session_date' => $data['session_date'],
                'problem' => $data['problem'],
                'handling' => $data['handling'],
                'result' => $data['result'],
            ]);

            $this->auditService->recordChanges(
                action: 'consultation.updated',
                auditable: $consultation,
                summary: sprintf('Konsultasi untuk %s diperbarui.', $consultation->identityName()),
                actor: $actor,
                before: $before,
                after: $this->snapshot($consultation->refresh()),
            );

            return $consultation->load(['student', 'temporaryStudent.reconciledStudent', 'serviceField', 'counselor']);
        });
    }

    public function archive(Consultation $consultation, User $actor): void
    {
        DB::transaction(function () use ($consultation, $actor): void {
            $consultation = Consultation::query()->lockForUpdate()->findOrFail($consultation->getKey());
            $this->assertOwnedAndAccessible($consultation, $actor, 'diarsipkan');

            $this->auditService->record(
                action: 'consultation.archived',
                auditable: $consultation,
                summary: sprintf('Konsultasi untuk %s diarsipkan.', $consultation->identityName()),
                actor: $actor,
                before: $this->snapshot($consultation),
            );
            $consultation->delete();
        });
    }

    /** @param array<string, mixed> $data @return array{?Student, ?TemporaryStudent} */
    private function resolveIdentity(array $data, User $actor): array
    {
        if (($data['student_id'] ?? null) !== null) {
            $student = Student::query()->lockForUpdate()->findOrFail($data['student_id']);
            if (! Student::query()
                ->availableForService((string) $data['session_date'])
                ->professionallyAccessibleTo($actor)
                ->whereKey($student->getKey())
                ->exists()) {
                throw ValidationException::withMessages([
                    'student_id' => 'Murid tidak berada dalam kewenangan profesional Anda.',
                ]);
            }

            return [$student, null];
        }

        if (($data['temporary_student_id'] ?? null) !== null) {
            $temporary = TemporaryStudent::query()
                ->with('reconciledStudent')
                ->lockForUpdate()
                ->findOrFail($data['temporary_student_id']);

            if ($temporary->reconciled_student_id !== null) {
                $student = $temporary->reconciledStudent;
                if ($student === null || ! Student::query()
                    ->professionallyAccessibleTo($actor)
                    ->whereKey($student?->getKey())
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'temporary_student_id' => 'Identitas sementara tidak berada dalam kewenangan profesional Anda.',
                    ]);
                }
                $this->assertStudentAvailable($student->getKey(), (string) $data['session_date']);
            } elseif ($temporary->created_by !== $actor->getKey()) {
                throw ValidationException::withMessages([
                    'temporary_student_id' => 'Identitas sementara tidak berada dalam kewenangan profesional Anda.',
                ]);
            }

            return [null, $temporary];
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

    private function assertOwnedAndAccessible(Consultation $consultation, User $actor, string $action): void
    {
        if ($consultation->counselor_id !== $actor->getKey() || ! $consultation->isProfessionallyAccessibleTo($actor)) {
            throw ValidationException::withMessages([
                'consultation' => "Konsultasi hanya dapat {$action} oleh pencatat yang masih memiliki kewenangan.",
            ]);
        }
    }

    private function assertStudentAvailable(mixed $studentId, string $date): void
    {
        if ($studentId === null) {
            return;
        }

        Student::query()->lockForUpdate()->findOrFail($studentId);
        if (! Student::query()->availableForService($date)->whereKey($studentId)->exists()) {
            throw ValidationException::withMessages([
                'session_date' => 'Tanggal sesi harus sebelum tanggal keluar resmi murid.',
            ]);
        }
    }

    private function reference(string $category, int $id): ReferenceValue
    {
        return ReferenceValue::query()->active()->where('category', $category)->findOrFail($id);
    }

    /** @return array<string, mixed> */
    private function snapshot(Consultation $consultation): array
    {
        return [
            'student_id' => $consultation->student_id,
            'temporary_student_id' => $consultation->temporary_student_id,
            'service_field_id' => $consultation->service_field_id,
            'session_date' => $consultation->session_date?->toDateString(),
            'problem' => $consultation->problem,
            'handling' => $consultation->handling,
            'result' => $consultation->result,
            'counselor_id' => $consultation->counselor_id,
        ];
    }
}
