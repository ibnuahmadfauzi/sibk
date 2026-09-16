<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Dapodik\DapodikSnapshot;
use App\Integrations\IntegrationOperationContext;
use App\Models\DapodikSyncPreviewItem;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\User;

final class DapodikReconciliationService
{
    public const int PREVIEW_TTL_HOURS = 24;

    public function __construct(
        private readonly DapodikPreviewBuilder $previewBuilder,
        private readonly DapodikMatchResolver $matchResolver,
        private readonly DapodikApplyService $applyService,
    ) {}

    public function createPreview(
        DapodikSnapshot $snapshot,
        ExternalSyncRun $run,
        IntegrationSetting $setting,
        IntegrationOperationContext $context,
        ?User $actor = null,
    ): ExternalSyncRun {
        return $this->previewBuilder->create($snapshot, $run, $setting, $context, $actor);
    }

    /** @param array{decision: string, candidate_id?: int|null, decision_revision: int} $data */
    public function decide(
        ExternalSyncRun $run,
        DapodikSyncPreviewItem $item,
        array $data,
        User $actor,
    ): DapodikSyncPreviewItem {
        return $this->matchResolver->decide($run, $item, $data, $actor);
    }

    public function apply(ExternalSyncRun $run, User $actor, int $expectedDecisionRevision): ExternalSyncRun
    {
        return $this->applyService->apply($run, $actor, $expectedDecisionRevision);
    }
}
