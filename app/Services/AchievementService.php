<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Achievement;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AchievementService
{
    public function __construct(private readonly AuditService $auditService) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Achievement
    {
        abort_unless($actor->can('create', Achievement::class), 403);

        return DB::transaction(function () use ($data, $actor): Achievement {
            $student = Student::query()->lockForUpdate()->findOrFail((int) $data['student_id']);
            $this->ensureStudentScope($student, $actor, (string) $data['achievement_date']);
            $this->validateReferences($data);
            $achievement = Achievement::query()->create([
                ...$this->metadata($data),
                'student_id' => $student->getKey(),
                'evidence_reference' => '',
                'verification_status_id' => ReferenceValue::query()->forCategory('achievement_verification_status')->where('code', 'terverifikasi')->valueOrFail('id'),
                'recorded_by' => $actor->getKey(),
            ]);
            $this->auditService->record(
                action: 'achievement.created',
                auditable: $achievement,
                summary: sprintf('Prestasi %s untuk murid %s dicatat.', $achievement->activity_name, $student->name),
                actor: $actor,
                after: $this->auditSnapshot($achievement),
            );

            return $achievement->load(['student', 'type', 'level', 'recorder']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Achievement $achievement, array $data, User $actor): Achievement
    {
        return DB::transaction(function () use ($achievement, $data, $actor): Achievement {
            $achievement = Achievement::query()->lockForUpdate()->findOrFail($achievement->getKey());
            abort_unless($actor->can('update', $achievement), 403);
            if (! $achievement->updated_at->equalTo(CarbonImmutable::parse((string) $data['expected_updated_at']))) {
                throw ValidationException::withMessages(['expected_updated_at' => 'Data telah berubah. Muat ulang sebelum menyimpan.']);
            }
            $student = Student::query()->lockForUpdate()->findOrFail($achievement->student_id);
            $this->ensureStudentScope($student, $actor, (string) $data['achievement_date']);
            $this->validateReferences($data);
            $before = $this->auditSnapshot($achievement);
            $achievement->fill($this->metadata($data));
            $changedFields = array_keys($achievement->getDirty());
            $achievement->save();
            $this->auditService->record(
                action: 'achievement.updated',
                auditable: $achievement,
                summary: sprintf('Prestasi %s diperbarui.', $achievement->activity_name),
                actor: $actor,
                before: [...$before, 'changed_fields' => $changedFields],
                after: [...$this->auditSnapshot($achievement), 'changed_fields' => $changedFields],
            );

            return $achievement->load(['student', 'type', 'level', 'recorder']);
        });
    }

    private function ensureStudentScope(Student $student, User $actor, string $date): void
    {
        if (! $actor->can('create', Achievement::class)) {
            abort(403);
        }
        if (! Student::query()->availableForService($date)->whereKey($student->getKey())->exists()) {
            throw ValidationException::withMessages(['student_id' => 'Murid tidak tersedia pada tanggal prestasi.']);
        }
    }

    /** @param array<string, mixed> $data */
    private function validateReferences(array $data): void
    {
        foreach (['type_id' => 'achievement_type', 'level_id' => 'achievement_level'] as $field => $category) {
            if (! ReferenceValue::query()->active()->forCategory($category)->whereKey((int) $data[$field])->exists()) {
                throw ValidationException::withMessages([$field => 'Nilai referensi prestasi tidak tersedia.']);
            }
        }
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function metadata(array $data): array
    {
        return [
            'type_id' => (int) $data['type_id'],
            'level_id' => (int) $data['level_id'],
            'activity_name' => trim((string) $data['activity_name']),
            'organizer' => trim((string) $data['organizer']),
            'achievement_date' => $data['achievement_date'],
            'result' => trim((string) $data['result']),
        ];
    }

    /** @return array<string, mixed> */
    private function auditSnapshot(Achievement $achievement): array
    {
        return [
            'student_id' => $achievement->student_id,
            'type_id' => $achievement->type_id,
            'level_id' => $achievement->level_id,
            'achievement_date' => $achievement->achievement_date?->toDateString(),
            'recorded_by' => $achievement->recorded_by,
        ];
    }
}
