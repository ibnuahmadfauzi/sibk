<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class EtatibAutomaticSyncService
{
    public function __construct(
        private readonly SimpleEtatibApiService $apiService,
        private readonly IntegrationOperationLock $operationLock,
        private readonly AuditService $auditService,
    ) {}

    /** @param array<string, mixed>|null $preview */
    public function activate(string $url, User $actor, ?array $preview = null): ExternalSyncRun
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $run = $this->apiService->synchronize($url, $actor, $preview);

        if (! in_array($run->status, [ExternalSyncRun::STATUS_SUCCEEDED, ExternalSyncRun::STATUS_WARNING], true)) {
            return $run;
        }

        $this->operationLock->run(
            IntegrationSetting::PROVIDER_ETATIB,
            fn (IntegrationOperationContext $context): IntegrationSetting => $this->storeUrl(
                $context,
                $url,
                $actor,
            ),
        );

        return $run;
    }

    public function synchronizeNow(User $actor): ExternalSyncRun
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        return $this->apiService->synchronizeStored($actor);
    }

    public function synchronizeScheduled(): ExternalSyncRun
    {
        return $this->apiService->synchronizeStored();
    }

    public function deactivate(User $actor): void
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        $this->operationLock->run(
            IntegrationSetting::PROVIDER_ETATIB,
            function (IntegrationOperationContext $context) use ($actor): void {
                DB::transaction(function () use ($context, $actor): void {
                    $setting = IntegrationSetting::query()
                        ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->operationLock->assertCurrent($context, $setting);

                    $wasEnabled = $setting->automatic_sync_enabled;
                    $hadUrl = $setting->getRawOriginal('automatic_sync_url') !== null;
                    $setting->automatic_sync_url = null;
                    $setting->automatic_sync_enabled = false;
                    $setting->automatic_sync_enabled_at = null;
                    $setting->automatic_sync_updated_by = $actor->getKey();
                    $setting->save();

                    $this->auditService->record(
                        action: 'etatib.automatic_sync_disabled',
                        auditable: $setting,
                        summary: 'Pembaruan otomatis e-Tatib dinonaktifkan.',
                        actor: $actor,
                        before: [
                            'automatic_sync_enabled' => $wasEnabled,
                            'has_automatic_sync_url' => $hadUrl,
                        ],
                        after: [
                            'automatic_sync_enabled' => false,
                            'has_automatic_sync_url' => false,
                        ],
                    );

                    $setting->refresh();
                    $this->operationLock->assertCurrent($context, $setting);
                });
            },
        );
    }

    private function storeUrl(
        IntegrationOperationContext $context,
        string $url,
        User $actor,
    ): IntegrationSetting {
        return DB::transaction(function () use ($context, $url, $actor): IntegrationSetting {
            $setting = IntegrationSetting::query()
                ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
                ->lockForUpdate()
                ->firstOrFail();
            $this->operationLock->assertCurrent($context, $setting);

            $previousUrl = null;
            if ($setting->getRawOriginal('automatic_sync_url') !== null) {
                try {
                    $previousUrl = $setting->automatic_sync_url;
                } catch (DecryptException) {
                    $previousUrl = null;
                }
            }
            $wasEnabled = $setting->automatic_sync_enabled;
            $setting->automatic_sync_url = $url;
            $setting->automatic_sync_enabled = true;
            $setting->automatic_sync_enabled_at = now();
            $setting->automatic_sync_updated_by = $actor->getKey();
            $setting->save();

            $this->auditService->record(
                action: $wasEnabled
                    ? 'etatib.automatic_sync_url_changed'
                    : 'etatib.automatic_sync_enabled',
                auditable: $setting,
                summary: $wasEnabled
                    ? 'Link pembaruan otomatis e-Tatib diperbarui.'
                    : 'Pembaruan otomatis e-Tatib diaktifkan.',
                actor: $actor,
                before: [
                    'automatic_sync_enabled' => $wasEnabled,
                    'has_automatic_sync_url' => $previousUrl !== null,
                ],
                after: [
                    'automatic_sync_enabled' => true,
                    'has_automatic_sync_url' => true,
                    'automatic_sync_url_changed' => $previousUrl !== $url,
                ],
            );

            $setting->refresh();
            $this->operationLock->assertCurrent($context, $setting);

            return $setting;
        });
    }
}
