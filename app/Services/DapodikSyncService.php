<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DapodikSyncService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly IntegrationOperationLock $operationLock,
    ) {}

    public function synchronize(?User $actor = null): ExternalSyncRun
    {
        return $this->operationLock->run(
            IntegrationSetting::PROVIDER_DAPODIK,
            function (IntegrationOperationContext $context) use ($actor): ExternalSyncRun {
                $run = $this->mutate($context, fn (): ExternalSyncRun => ExternalSyncRun::query()->create([
                    'source' => 'dapodik',
                    'status' => ExternalSyncRun::STATUS_RUNNING,
                    'triggered_by' => $actor?->getKey(),
                    'started_at' => now(),
                ]));
                $summary = (new IntegrationConfigurationException('preview_unavailable'))->getMessage();
                $this->mutate($context, function () use ($run, $summary, $actor): void {
                    $run->update([
                        'status' => ExternalSyncRun::STATUS_FAILED,
                        'summary' => $summary,
                        'finished_at' => now(),
                    ]);
                    $run->refresh();
                    $this->auditService->record(
                        action: 'dapodik.sync_completed',
                        auditable: $run,
                        summary: $summary,
                        actor: $actor,
                        after: [
                            'status' => $run->status,
                            'received_count' => $run->received_count,
                            'processed_count' => $run->processed_count,
                            'conflict_count' => $run->conflict_count,
                        ],
                    );
                });

                return $run->refresh();
            },
        );
    }

    /** @template TResult @param \Closure(): TResult $mutation @return TResult */
    private function mutate(IntegrationOperationContext $context, \Closure $mutation): mixed
    {
        return DB::transaction(function () use ($context, $mutation): mixed {
            $setting = IntegrationSetting::query()
                ->where('provider', IntegrationSetting::PROVIDER_DAPODIK)
                ->lockForUpdate()
                ->firstOrFail();
            $this->operationLock->assertCurrent($context, $setting);

            $result = $mutation();
            $this->operationLock->assertCurrent($context, $setting);

            return $result;
        });
    }
}
