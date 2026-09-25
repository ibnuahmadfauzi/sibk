<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Dapodik\DapodikSnapshot;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Models\DapodikSyncPreviewItem;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\User;

final class DapodikPreviewBuilder
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly IntegrationOperationLock $operationLock,
        private readonly DapodikMatchResolver $matches,
        private readonly DapodikDeactivationPlanner $deactivations,
    ) {}

    public function create(
        DapodikSnapshot $snapshot,
        ExternalSyncRun $run,
        IntegrationSetting $setting,
        IntegrationOperationContext $context,
        ?User $actor = null,
    ): ExternalSyncRun {
        $this->operationLock->assertCurrent($context, $setting);

        $generation = ((int) ExternalSyncRun::query()
            ->where('source', IntegrationSetting::PROVIDER_DAPODIK)
            ->whereKeyNot($run->getKey())
            ->max('preview_generation')) + 1;

        $superseded = ExternalSyncRun::query()
            ->where('source', IntegrationSetting::PROVIDER_DAPODIK)
            ->where('status', ExternalSyncRun::STATUS_PREVIEW_READY)
            ->lockForUpdate()
            ->get();
        foreach ($superseded as $previousRun) {
            $previousRun->update([
                'status' => ExternalSyncRun::STATUS_SUPERSEDED,
                'summary' => 'Pratinjau lama diganti oleh data Dapodik terbaru.',
                'superseded_at' => now(),
            ]);
            $this->auditService->record(
                action: 'dapodik.preview_superseded',
                auditable: $previousRun,
                summary: 'Keputusan lama dibatalkan karena data Dapodik terbaru tersedia.',
                actor: $actor,
                after: ['preview_generation' => $previousRun->preview_generation],
            );
        }

        /** @var array<string, array{candidate_id: ?int, match_status: string}> $yearMatches */
        $yearMatches = [];
        /** @var array<string, array{candidate_id: ?int, match_status: string}> $classroomMatches */
        $classroomMatches = [];
        /** @var array<string, array{candidate_id: ?int, match_status: string}> $studentMatches */
        $studentMatches = [];
        $items = [];

        foreach ($snapshot->academicYears as $fields) {
            $safeFields = $this->normalizeAcademicYear($fields);
            $match = $this->matches->classifyAcademicYear($safeFields);
            $yearMatches[$safeFields['source_id']] = $match;
            $items[] = $this->previewItem(
                DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR,
                $safeFields,
                $match,
                $generation,
            );
        }

        foreach ($snapshot->classrooms as $fields) {
            $safeFields = $this->normalizeClassroom($fields);
            $match = $this->matches->classifyClassroom(
                $safeFields,
                $yearMatches[$safeFields['academic_year_source_id']] ?? null,
            );
            $classroomMatches[$safeFields['source_id']] = $match;
            $items[] = $this->previewItem(
                DapodikSyncPreviewItem::ENTITY_CLASSROOM,
                $safeFields,
                $match,
                $generation,
            );
        }

        foreach ($snapshot->students as $fields) {
            $safeFields = $this->normalizeStudent($fields);
            $match = $this->matches->classifyStudent($safeFields);
            $studentMatches[$safeFields['source_id']] = $match;
            $items[] = $this->previewItem(
                DapodikSyncPreviewItem::ENTITY_STUDENT,
                $safeFields,
                $match,
                $generation,
            );
        }

        foreach ($snapshot->memberships as $fields) {
            $safeFields = $this->normalizeMembership($fields);
            $match = $this->matches->classifyMembership(
                $safeFields,
                $studentMatches[$safeFields['student_source_id']] ?? null,
                $classroomMatches[$safeFields['classroom_source_id']] ?? null,
                $yearMatches[$safeFields['academic_year_source_id']] ?? null,
            );
            $items[] = $this->previewItem(
                DapodikSyncPreviewItem::ENTITY_MEMBERSHIP,
                $safeFields,
                $match,
                $generation,
            );
        }

        $snapshotEvidence = $this->snapshotEvidence($snapshot);
        $deactivationPlan = $snapshot->isFullSnapshot
            ? $this->deactivations->build([
                DapodikSyncPreviewItem::ENTITY_CLASSROOM => array_column($snapshot->classrooms, 'source_id'),
                DapodikSyncPreviewItem::ENTITY_STUDENT => array_column($snapshot->students, 'source_id'),
                DapodikSyncPreviewItem::ENTITY_MEMBERSHIP => array_column($snapshot->memberships, 'source_id'),
            ])
            : [];
        $fingerprint = $this->matches->previewFingerprint(
            $snapshot->isFullSnapshot,
            $snapshotEvidence,
            array_column($items, 'item_hash'),
            $deactivationPlan,
        );
        $conflictCount = count(array_filter(
            $items,
            static fn (array $item): bool => $item['match_status'] === DapodikSyncPreviewItem::MATCH_CONFLICT,
        ));

        $run->update([
            'status' => ExternalSyncRun::STATUS_PREVIEW_READY,
            'is_full_snapshot' => $snapshot->isFullSnapshot,
            'received_count' => $snapshot->recordCount(),
            'processed_count' => 0,
            'conflict_count' => $conflictCount,
            'summary' => $conflictCount > 0
                ? sprintf('Pratinjau Dapodik siap dengan %d konflik yang harus diperiksa.', $conflictCount)
                : 'Pratinjau Dapodik siap diperiksa sebelum diterapkan.',
            'snapshot_fingerprint' => $fingerprint,
            'snapshot_evidence' => $snapshotEvidence,
            'deactivation_plan' => $deactivationPlan,
            'preview_generation' => $generation,
            'decision_revision' => 0,
            'configuration_version' => $setting->configuration_version,
            'preview_fencing_token' => $context->fencingToken,
            'driver_id' => $setting->verified_driver_id,
            'adapter_version' => $setting->verified_adapter_version,
            'contract_version' => $setting->verified_contract_version,
            'endpoint_policy_digest' => $setting->verified_endpoint_policy_digest,
            'preview_expires_at' => now()->addHours(DapodikReconciliationService::PREVIEW_TTL_HOURS),
            'finished_at' => now(),
        ]);

        foreach ($items as $item) {
            $run->previewItems()->create($item);
        }

        $this->auditService->record(
            action: 'dapodik.preview_created',
            auditable: $run,
            summary: 'Snapshot Dapodik tervalidasi dan disimpan sebagai pratinjau tanpa mengubah data operasional.',
            actor: $actor,
            after: [
                'preview_generation' => $generation,
                'received_count' => $snapshot->recordCount(),
                'conflict_count' => $conflictCount,
                'is_full_snapshot' => $snapshot->isFullSnapshot,
                'snapshot_fingerprint' => $fingerprint,
            ],
        );
        $this->operationLock->assertCurrent($context, $setting);

        return $run->refresh();
    }

    private function normalizeAcademicYear(array $fields): array
    {
        return [
            'source_id' => $fields['source_id'],
            'name' => $fields['name'],
            'starts_on' => $fields['starts_on'] ?? null,
            'ends_on' => $fields['ends_on'] ?? null,
            'is_active' => $fields['is_active'] ?? false,
        ];
    }

    private function normalizeClassroom(array $fields): array
    {
        return [
            'source_id' => $fields['source_id'],
            'academic_year_source_id' => $fields['academic_year_source_id'],
            'name' => $fields['name'],
            'grade_level' => $fields['grade_level'] ?? null,
            'major' => $fields['major'] ?? null,
            'is_active' => $fields['is_active'] ?? true,
        ];
    }

    private function normalizeStudent(array $fields): array
    {
        return [
            'source_id' => $fields['source_id'],
            'nisn' => $fields['nisn'],
            'name' => $fields['name'],
            'is_active' => $fields['is_active'] ?? true,
        ];
    }

    private function normalizeMembership(array $fields): array
    {
        return [
            'source_id' => $fields['source_id'],
            'student_source_id' => $fields['student_source_id'],
            'classroom_source_id' => $fields['classroom_source_id'],
            'academic_year_source_id' => $fields['academic_year_source_id'],
            'is_active' => $fields['is_active'] ?? true,
        ];
    }

    private function previewItem(string $entityType, array $safeFields, array $match, int $generation): array
    {
        $candidate = $match['candidate_id'] === null
            ? null
            : $this->matches->findTarget($entityType, $match['candidate_id']);

        return [
            'entity_type' => $entityType,
            'source_identifier' => $safeFields['source_id'],
            'candidate_id' => $match['candidate_id'],
            'match_status' => $match['match_status'],
            'safe_fields' => $safeFields,
            'item_hash' => hash('sha256', $this->matches->canonicalJson([
                'entity_type' => $entityType,
                'safe_fields' => $safeFields,
            ])),
            'target_fingerprint' => $candidate === null ? null : $this->matches->targetFingerprint($entityType, $candidate),
            'preview_generation' => $generation,
            'decision_revision' => 0,
        ];
    }

    private function snapshotEvidence(DapodikSnapshot $snapshot): array
    {
        $evidence = $snapshot->evidence;
        if ($evidence === null) {
            throw new \LogicException('Snapshot tervalidasi wajib memiliki evidence.');
        }

        return [
            'reported_source_identifier' => $evidence->reportedSourceIdentifier,
            'contract_marker' => $evidence->contractMarker,
            'completeness_marker' => $evidence->completenessMarker,
            'page_count' => $evidence->pageCount,
            'record_count' => $evidence->recordCount,
            'processed_bytes' => $evidence->processedBytes,
        ];
    }
}
