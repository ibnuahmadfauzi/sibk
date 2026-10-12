<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Integrations\IntegrationProbeResult;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Integrations\IntegrationSettingState;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Throwable;

final class IntegrationConnectionTester
{
    public function __construct(
        private readonly IntegrationOperationLock $operationLock,
        private readonly AuditService $auditService,
        private readonly IntegrationStateResolver $states,
    ) {}

    public function test(string $provider, User $actor): IntegrationSettingState
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $this->states->assertProvider($provider);

        return $this->operationLock->run(
            $provider,
            function (IntegrationOperationContext $context) use ($provider, $actor): IntegrationSettingState {
                $setting = IntegrationSetting::query()->where('provider', $provider)->firstOrFail();
                $driver = $this->states->driver($provider);
                $configuration = $this->states->runtimeConfiguration($setting, $context->fencingToken);
                $captured = [
                    'configuration_version' => $setting->configuration_version,
                    'driver_id' => $driver->id(),
                    'adapter_version' => $driver->adapterVersion(),
                    'contract_version' => $driver->contractVersion(),
                    'endpoint_policy_digest' => $configuration->endpointPolicyDigest,
                ];

                try {
                    $result = $driver->probe($configuration);
                } catch (Throwable) {
                    $result = new IntegrationProbeResult(
                        code: 'connection_failed',
                        driverId: $driver->id(),
                        adapterVersion: $driver->adapterVersion(),
                        contractVersion: $driver->contractVersion(),
                        reportedSourceIdentifier: null,
                        schemaValid: false,
                        completenessVerified: false,
                    );
                }

                $context->assertWithinDeadline();
                $safeCode = $this->validatedProbeCode($result, $configuration, $captured);

                return DB::transaction(function () use (
                    $provider,
                    $actor,
                    $context,
                    $captured,
                    $safeCode,
                    $result,
                ): IntegrationSettingState {
                    $current = IntegrationSetting::query()
                        ->where('provider', $provider)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->operationLock->assertCurrent($context, $current);
                    $this->assertCapturedConfigurationIsCurrent($current, $captured);

                    $before = $this->states->auditSnapshot($current, false);
                    $success = $safeCode === IntegrationProbeResult::CODE_SUCCESS;
                    if ($success) {
                        $verifiedContractChanged = $current->verified_driver_id !== $captured['driver_id']
                            || $current->verified_adapter_version !== $captured['adapter_version']
                            || $current->verified_contract_version !== $captured['contract_version']
                            || $current->verified_endpoint_policy_digest !== $captured['endpoint_policy_digest'];
                        if ($verifiedContractChanged) {
                            $current->sync_watermark = null;
                            $current->last_full_synced_at = null;
                            $current->last_successful_sync_at = null;
                        }
                        $current->verified_configuration_version = $captured['configuration_version'];
                        $current->verified_driver_id = $captured['driver_id'];
                        $current->verified_adapter_version = $captured['adapter_version'];
                        $current->verified_contract_version = $captured['contract_version'];
                        $current->verified_endpoint_policy_digest = $captured['endpoint_policy_digest'];
                    } else {
                        $this->clearVerification($current);
                        $current->is_enabled = false;
                    }
                    $current->last_test_status = $success
                        ? IntegrationSetting::TEST_STATUS_SUCCESS
                        : IntegrationSetting::TEST_STATUS_FAILED;
                    $current->last_test_code = $safeCode;
                    $current->last_tested_at = now();
                    $current->last_tested_by = $actor->getKey();
                    $current->last_probe_summary = $success ? $result->preview : null;
                    $current->save();

                    $this->auditService->record(
                        action: "{$provider}.connection_tested",
                        auditable: $current,
                        summary: 'Koneksi integrasi diuji tanpa mengimpor data.',
                        actor: $actor,
                        before: $before,
                        after: $this->states->auditSnapshot($current, false),
                    );
                    $context->assertWithinDeadline();

                    return $this->states->forSetting($current->refresh());
                });
            },
        );
    }

    private function validatedProbeCode(
        IntegrationProbeResult $result,
        IntegrationRuntimeConfiguration $configuration,
        array $captured,
    ): string {
        if ($result->driverId !== $captured['driver_id']
            || $result->adapterVersion !== $captured['adapter_version']
            || $result->contractVersion !== $captured['contract_version']
        ) {
            return 'contract_invalid';
        }
        if ($result->code !== IntegrationProbeResult::CODE_SUCCESS) {
            return $result->code;
        }
        if (! $result->schemaValid) {
            return 'contract_invalid';
        }
        if (! is_string($result->reportedSourceIdentifier)
            || ! hash_equals($configuration->expectedSourceIdentifier, $result->reportedSourceIdentifier)
        ) {
            return 'source_identity_mismatch';
        }

        return IntegrationProbeResult::CODE_SUCCESS;
    }

    private function assertCapturedConfigurationIsCurrent(IntegrationSetting $setting, array $captured): void
    {
        $driver = $this->states->driver($setting->provider);
        try {
            $policyDigest = $this->states->policy($setting->provider)->digest();
            if (is_string($setting->base_url)) {
                $this->states->policy($setting->provider)->assertAllowedEndpoint($setting->base_url);
            }
        } catch (InvalidArgumentException) {
            throw new IntegrationConfigurationException('configuration_changed');
        }

        if ($setting->configuration_version !== $captured['configuration_version']
            || $driver->id() !== $captured['driver_id']
            || $driver->adapterVersion() !== $captured['adapter_version']
            || $driver->contractVersion() !== $captured['contract_version']
            || $policyDigest !== $captured['endpoint_policy_digest']
        ) {
            throw new IntegrationConfigurationException('configuration_changed');
        }
    }

    private function clearVerification(IntegrationSetting $setting): void
    {
        $setting->verified_configuration_version = null;
        $setting->verified_driver_id = null;
        $setting->verified_adapter_version = null;
        $setting->verified_contract_version = null;
        $setting->verified_endpoint_policy_digest = null;
    }
}
