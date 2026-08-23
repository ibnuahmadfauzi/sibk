<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Achievement;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AchievementService
{
    public function __construct(private readonly AuditService $auditService) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Achievement
    {
        return DB::transaction(function () use ($data, $actor): Achievement {
            $student = Student::query()->active()->lockForUpdate()->findOrFail((int) $data['student_id']);
            $this->ensureStudentScope($student, $actor);
            $this->validateReferences($data);
            $achievement = Achievement::query()->create([
                ...$this->metadata($data),
                'student_id' => $student->getKey(),
                'verification_status_id' => $this->status('menunggu')->getKey(),
                'recorded_by' => $actor->getKey(),
            ]);
            $this->auditService->record(
                action: 'achievement.created',
                auditable: $achievement,
                summary: sprintf('Prestasi %s untuk murid %s dicatat.', $achievement->activity_name, $student->name),
                actor: $actor,
                after: $this->auditSnapshot($achievement),
            );

            return $achievement->load(['student', 'type', 'level', 'verificationStatus', 'recorder']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Achievement $achievement, array $data, User $actor): Achievement
    {
        return DB::transaction(function () use ($achievement, $data, $actor): Achievement {
            $achievement = Achievement::query()->with('verificationStatus')->lockForUpdate()->findOrFail($achievement->getKey());
            abort_unless($actor->can('update', $achievement), 403);
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

            return $achievement->load(['student', 'type', 'level', 'verificationStatus', 'recorder']);
        });
    }

    /** @param array{decision: string, verification_notes?: string|null} $data */
    public function verify(Achievement $achievement, array $data, User $reviewer): Achievement
    {
        return DB::transaction(function () use ($achievement, $data, $reviewer): Achievement {
            $achievement = Achievement::query()->with('verificationStatus')->lockForUpdate()->findOrFail($achievement->getKey());
            abort_unless($reviewer->can('verify', $achievement), 403);
            $before = $this->auditSnapshot($achievement);
            $achievement->update([
                'verification_status_id' => $this->status($data['decision'])->getKey(),
                'reviewer_id' => $reviewer->getKey(),
                'reviewed_at' => now(),
                'verification_notes' => filled($data['verification_notes'] ?? null) ? trim((string) $data['verification_notes']) : null,
            ]);
            $this->auditService->record(
                action: 'achievement.reviewed',
                auditable: $achievement,
                summary: sprintf('Prestasi %s dinyatakan %s.', $achievement->activity_name, $data['decision']),
                actor: $reviewer,
                before: $before,
                after: $this->auditSnapshot($achievement->refresh()),
            );

            return $achievement->load(['student', 'type', 'level', 'verificationStatus', 'recorder', 'reviewer']);
        });
    }

    public function applyApprovedCorrection(Achievement $achievement, string $field, ?string $value, User $coordinator): void
    {
        $achievement = Achievement::query()->with('verificationStatus')->lockForUpdate()->findOrFail($achievement->getKey());
        if ($achievement->verificationStatus->code !== 'terverifikasi') {
            throw ValidationException::withMessages(['correction' => 'Hanya prestasi terverifikasi yang dapat dikoreksi melalui alur ini.']);
        }
        $allowed = ['type_id', 'level_id', 'activity_name', 'organizer', 'achievement_date', 'result', 'evidence_reference', 'evidence_description', 'notes'];
        if (! in_array($field, $allowed, true)) {
            throw ValidationException::withMessages(['field_name' => 'Atribut prestasi tidak dapat dikoreksi.']);
        }
        if ($field === 'achievement_date' && $value !== null && $value > today()->toDateString()) {
            throw ValidationException::withMessages(['proposed_value' => 'Tanggal prestasi tidak boleh berada di masa depan.']);
        }
        $before = $this->auditSnapshot($achievement);
        $achievement->update([
            $field => in_array($field, ['type_id', 'level_id'], true) ? (int) $value : $value,
            'reviewer_id' => $coordinator->getKey(),
            'reviewed_at' => now(),
        ]);
        $this->auditService->record(
            action: 'achievement.corrected',
            auditable: $achievement,
            summary: sprintf('Koreksi atribut %s pada prestasi telah diterapkan.', $field),
            actor: $coordinator,
            before: [...$before, 'changed_fields' => [$field]],
            after: [...$this->auditSnapshot($achievement->refresh()), 'changed_fields' => [$field]],
        );
    }

    private function ensureStudentScope(Student $student, User $actor): void
    {
        if (! Student::query()->active()->professionallyAccessibleTo($actor)->whereKey($student->getKey())->exists()) {
            throw ValidationException::withMessages(['student_id' => 'Murid tidak berada dalam kewenangan profesional Anda.']);
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
            'evidence_reference' => trim((string) $data['evidence_reference']),
            'evidence_description' => filled($data['evidence_description'] ?? null) ? trim((string) $data['evidence_description']) : null,
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
        ];
    }

    private function status(string $code): ReferenceValue
    {
        return ReferenceValue::query()->active()->forCategory('achievement_verification_status')->where('code', $code)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function auditSnapshot(Achievement $achievement): array
    {
        return [
            'student_id' => $achievement->student_id,
            'type_id' => $achievement->type_id,
            'level_id' => $achievement->level_id,
            'achievement_date' => $achievement->achievement_date?->toDateString(),
            'verification_status_id' => $achievement->verification_status_id,
            'recorded_by' => $achievement->recorded_by,
            'reviewer_id' => $achievement->reviewer_id,
            'reviewed_at' => $achievement->reviewed_at?->toISOString(),
        ];
    }
}
