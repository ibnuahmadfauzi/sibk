<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\DapodikSyncPreviewItem;
use App\Models\ExternalSyncRun;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class DapodikDeactivationPlanner
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly DapodikMatchResolver $matches,
    ) {}

    public function validate(ExternalSyncRun $run, Collection $items): void
    {
        if (! $run->is_full_snapshot) {
            if ($run->deactivation_plan !== []) {
                $this->validationError('preview', 'Snapshot parsial tidak boleh memiliki rencana penonaktifan.');
            }

            return;
        }

        $currentPlan = $this->build([
            DapodikSyncPreviewItem::ENTITY_CLASSROOM => $items
                ->where('entity_type', DapodikSyncPreviewItem::ENTITY_CLASSROOM)
                ->pluck('source_identifier')->values()->all(),
            DapodikSyncPreviewItem::ENTITY_STUDENT => $items
                ->where('entity_type', DapodikSyncPreviewItem::ENTITY_STUDENT)
                ->pluck('source_identifier')->values()->all(),
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => $items
                ->where('entity_type', DapodikSyncPreviewItem::ENTITY_MEMBERSHIP)
                ->pluck('source_identifier')->values()->all(),
        ]);
        if ($this->matches->canonicalJson($currentPlan) !== $this->matches->canonicalJson($run->deactivation_plan)) {
            $this->validationError('preview', 'Rencana penonaktifan berubah setelah pratinjau dibuat.');
        }
    }

    public function apply(ExternalSyncRun $run, User $actor): void
    {
        foreach ($run->deactivation_plan as $planned) {
            $target = $this->matches->findTarget($planned['entity_type'], (int) $planned['target_id'], true);
            $before = $this->auditTarget($target);
            $target->update(['is_active' => false]);
            $this->auditService->record(
                action: "dapodik.{$planned['entity_type']}_deactivated",
                auditable: $target,
                summary: 'Data terverifikasi Dapodik dinonaktifkan sesuai rencana snapshot penuh.',
                actor: $actor,
                before: $before,
                after: $this->auditTarget($target),
            );
        }
    }

    private function auditTarget(Model $target): array
    {
        return array_intersect_key($target->getAttributes(), array_flip([
            'id',
            'dapodik_id',
            'name',
            'nisn',
            'academic_year_id',
            'student_id',
            'classroom_id',
            'grade_level',
            'major',
            'effective_from',
            'effective_until',
            'is_active',
            'master_source',
            'source_confirmed_at',
        ]));
    }

    public function build(array $incomingSourceIds): array
    {
        $plan = [];
        foreach ([
            DapodikSyncPreviewItem::ENTITY_CLASSROOM => Classroom::query(),
            DapodikSyncPreviewItem::ENTITY_STUDENT => Student::query(),
            DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => StudentClassMembership::query(),
        ] as $entityType => $query) {
            $query->where('master_source', AcademicYear::MASTER_SOURCE_DAPODIK)
                ->where('is_active', true)
                ->whereNotNull('dapodik_id');
            $sourceIds = $incomingSourceIds[$entityType] ?? [];
            if ($sourceIds !== []) {
                $query->whereNotIn('dapodik_id', $sourceIds);
            }
            foreach ($query->orderBy('id')->lockForUpdate()->get() as $target) {
                $plan[] = [
                    'entity_type' => $entityType,
                    'target_id' => $target->getKey(),
                    'target_fingerprint' => $this->matches->targetFingerprint($entityType, $target),
                ];
            }
        }

        return $plan;
    }

    private function validationError(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
