<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Dapodik\ConfiguredDapodikConnector;
use App\Integrations\Dapodik\DapodikConnector;
use App\Integrations\Dapodik\DapodikUnavailableException;
use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Throwable;

class DapodikSyncService
{
    public function __construct(
        private readonly DapodikConnector $connector,
        private readonly DapodikReconciliationService $reconciliationService,
        private readonly AuditService $auditService,
        private readonly IntegrationOperationLock $operationLock,
    ) {}

    public function synchronize(?User $actor = null): ExternalSyncRun
    {
        if ($actor !== null) {
            Gate::forUser($actor)->authorize('manageDataMaster');
        }

        return $this->operationLock->run(
            IntegrationSetting::PROVIDER_DAPODIK,
            fn (IntegrationOperationContext $context): ExternalSyncRun => $this->synchronizeLocked($context, $actor),
        );
    }

    private function synchronizeLocked(IntegrationOperationContext $context, ?User $actor): ExternalSyncRun
    {
        $run = $this->mutate($context, fn (): ExternalSyncRun => ExternalSyncRun::query()->create([
            'source' => IntegrationSetting::PROVIDER_DAPODIK,
            'status' => ExternalSyncRun::STATUS_RUNNING,
            'triggered_by' => $actor?->getKey(),
            'started_at' => now(),
        ]));

        try {
            if (! $this->connector instanceof ConfiguredDapodikConnector) {
                throw new IntegrationConfigurationException('preview_unavailable');
            }
            $snapshot = $this->connector->fetchSnapshot($context);

            return $this->mutate($context, function () use ($snapshot, $run, $actor, $context): ExternalSyncRun {
                $currentRun = ExternalSyncRun::query()->lockForUpdate()->findOrFail($run->getKey());
                $setting = IntegrationSetting::query()
                    ->where('provider', IntegrationSetting::PROVIDER_DAPODIK)
                    ->lockForUpdate()
                    ->firstOrFail();

                return $this->reconciliationService->createPreview(
                    $snapshot,
                    $currentRun,
                    $setting,
                    $context,
                    $actor,
                );
            });
        } catch (Throwable $exception) {
            if (! $exception instanceof DapodikUnavailableException
                && ! $exception instanceof IntegrationConfigurationException
            ) {
                report(new RuntimeException('Unexpected Dapodik integration failure.'));
            }

            $summary = $exception instanceof IntegrationConfigurationException
                ? $exception->getMessage()
                : ($exception instanceof DapodikUnavailableException
                    ? $exception->getMessage()
                    : 'Sinkronisasi Dapodik gagal. Data lama tetap dipertahankan.');

            return $this->settleFailure($context, $run, $summary, $actor);
        }
    }

    private function settleFailure(
        IntegrationOperationContext $context,
        ExternalSyncRun $run,
        string $summary,
        ?User $actor,
    ): ExternalSyncRun {
        try {
            return $this->mutate($context, function () use ($run, $summary, $actor): ExternalSyncRun {
                $current = ExternalSyncRun::query()->lockForUpdate()->findOrFail($run->getKey());
                if ($current->status !== ExternalSyncRun::STATUS_RUNNING) {
                    return $current;
                }
                $this->finalizeFailure($current, $summary, $actor);

                return $current->refresh();
            });
        } catch (Throwable) {
            return $this->mutate($context, function () use ($run, $summary): ExternalSyncRun {
                $current = ExternalSyncRun::query()->lockForUpdate()->findOrFail($run->getKey());
                if ($current->status === ExternalSyncRun::STATUS_RUNNING) {
                    $current->update([
                        'status' => ExternalSyncRun::STATUS_FAILED,
                        'summary' => $summary,
                        'finished_at' => now(),
                    ]);
                }

                return $current->refresh();
            });
        }
    }

    private function finalizeFailure(ExternalSyncRun $run, string $summary, ?User $actor): void
    {
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
