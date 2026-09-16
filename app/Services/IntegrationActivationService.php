<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Integrations\IntegrationSettingState;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class IntegrationActivationService
{
    public function __construct(
        private readonly IntegrationOperationLock $operationLock,
        private readonly AuditService $auditService,
        private readonly IntegrationStateResolver $states,
    ) {}

    public function activate(string $provider, User $actor): IntegrationSettingState
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $this->states->assertProvider($provider);

        return $this->operationLock->run(
            $provider,
            function (IntegrationOperationContext $context) use ($provider, $actor): IntegrationSettingState {
                return DB::transaction(function () use ($provider, $actor, $context): IntegrationSettingState {
                    $setting = IntegrationSetting::query()
                        ->where('provider', $provider)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->operationLock->assertCurrent($context, $setting);
                    $this->states->assertActivationInvariant($setting);

                    if ($setting->is_enabled) {
                        return $this->states->forSetting($setting);
                    }

                    $before = $this->states->auditSnapshot($setting, false);
                    $setting->is_enabled = true;
                    $setting->updated_by = $actor->getKey();
                    $setting->save();
                    $this->auditService->record(
                        action: "{$provider}.connection_enabled",
                        auditable: $setting,
                        summary: 'Koneksi integrasi diaktifkan.',
                        actor: $actor,
                        before: $before,
                        after: $this->states->auditSnapshot($setting, false),
                    );
                    $context->assertWithinDeadline();

                    return $this->states->forSetting($setting->refresh());
                });
            },
        );
    }

    public function deactivate(string $provider, User $actor): IntegrationSettingState
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $this->states->assertProvider($provider);

        return $this->operationLock->run(
            $provider,
            function (IntegrationOperationContext $context) use ($provider, $actor): IntegrationSettingState {
                return DB::transaction(function () use ($provider, $actor, $context): IntegrationSettingState {
                    $setting = IntegrationSetting::query()
                        ->where('provider', $provider)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->operationLock->assertCurrent($context, $setting);
                    $before = $this->states->auditSnapshot($setting, false);
                    $setting->is_enabled = false;
                    $setting->updated_by = $actor->getKey();
                    $setting->save();
                    $this->auditService->record(
                        action: "{$provider}.connection_disabled",
                        auditable: $setting,
                        summary: 'Koneksi integrasi dinonaktifkan.',
                        actor: $actor,
                        before: $before,
                        after: $this->states->auditSnapshot($setting, false),
                    );
                    $context->assertWithinDeadline();

                    return $this->states->forSetting($setting->refresh());
                });
            },
        );
    }
}
