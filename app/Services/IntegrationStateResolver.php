<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationDriver;
use App\Integrations\IntegrationDriverRegistry;
use App\Integrations\IntegrationEndpointPolicy;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Integrations\IntegrationProbeResult;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Integrations\IntegrationSettingState;
use App\Models\IntegrationSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

final class IntegrationStateResolver
{
    public function __construct(
        private readonly IntegrationDriverRegistry $drivers,
        private readonly IntegrationOperationLock $operationLock,
    ) {}

    public function allStates(): array
    {
        Gate::authorize('manageDataMaster');

        return [
            IntegrationSetting::PROVIDER_DAPODIK => $this->forProvider(IntegrationSetting::PROVIDER_DAPODIK),
            IntegrationSetting::PROVIDER_ETATIB => $this->forProvider(IntegrationSetting::PROVIDER_ETATIB),
        ];
    }

    public function active(
        string $provider,
        string $driverId,
        string $adapterVersion,
        string $contractVersion,
        ?IntegrationOperationContext $context = null,
    ): IntegrationRuntimeConfiguration {
        $this->assertProvider($provider);
        $setting = IntegrationSetting::query()->where('provider', $provider)->first();
        if ($setting === null || ! $setting->is_enabled) {
            throw new IntegrationConfigurationException('not_active');
        }

        $driver = $this->driver($provider);
        if (! $driver->isAvailable()) {
            throw new IntegrationConfigurationException('adapter_unavailable');
        }
        if ($driver->id() !== $driverId
            || $driver->adapterVersion() !== $adapterVersion
            || $driver->contractVersion() !== $contractVersion
        ) {
            throw new IntegrationConfigurationException('configuration_changed');
        }
        if ($context !== null) {
            $this->operationLock->assertCurrent($context, $setting);
        }
        $this->assertActivationInvariant($setting);

        return $this->runtimeConfiguration(
            $setting,
            $context?->fencingToken ?? $setting->operation_fence_version,
        );
    }

    public function assertCurrent(
        string $provider,
        int $configurationVersion,
        string $driverId,
        string $adapterVersion,
        string $contractVersion,
        ?IntegrationOperationContext $context = null,
    ): void {
        $configuration = $this->active(
            $provider,
            $driverId,
            $adapterVersion,
            $contractVersion,
            $context,
        );
        if ($configuration->configurationVersion !== $configurationVersion) {
            throw new IntegrationConfigurationException('configuration_changed');
        }
    }

    public function forProvider(string $provider): IntegrationSettingState
    {
        $setting = IntegrationSetting::query()->where('provider', $provider)->first();
        if ($setting === null) {
            $setting = new IntegrationSetting([
                'provider' => $provider,
                'timeout_seconds' => 30,
                'configuration_version' => 0,
                'operation_fence_version' => 0,
                'last_test_status' => IntegrationSetting::TEST_STATUS_UNTESTED,
                'is_enabled' => false,
            ]);
        }

        return $this->forSetting($setting);
    }

    public function forSetting(IntegrationSetting $setting): IntegrationSettingState
    {
        try {
            $driver = $this->driver($setting->provider);
        } catch (InvalidArgumentException) {
            $driver = null;
        }
        $credentials = null;
        $credentialUnreadable = false;
        try {
            $credentials = $setting->credentials;
        } catch (DecryptException) {
            $credentialUnreadable = true;
        }
        $hasCredentials = is_array($credentials)
            && ($credentials['type'] ?? null) === 'api_token'
            && is_string($credentials['token'] ?? null)
            && $credentials['token'] !== '';
        $complete = is_string($setting->base_url) && $setting->base_url !== ''
            && is_string($setting->expected_source_identifier) && $setting->expected_source_identifier !== ''
            && $hasCredentials;
        $endpointAllowed = false;
        $policyDigest = null;
        if (is_string($setting->base_url) && $setting->base_url !== '') {
            try {
                $policy = $this->policy($setting->provider);
                $policy->assertAllowedEndpoint($setting->base_url);
                $policyDigest = $policy->digest();
                $endpointAllowed = true;
            } catch (InvalidArgumentException) {
                // Safe state is blocked below; no endpoint details leave the service.
            }
        }
        $verifiedCurrent = $complete
            && $endpointAllowed
            && $driver !== null
            && $setting->last_test_status === IntegrationSetting::TEST_STATUS_SUCCESS
            && $setting->verified_configuration_version === $setting->configuration_version
            && $setting->verified_driver_id === $driver->id()
            && $setting->verified_adapter_version === $driver->adapterVersion()
            && $setting->verified_contract_version === $driver->contractVersion()
            && $setting->verified_endpoint_policy_digest === $policyDigest;

        $state = match (true) {
            $credentialUnreadable => IntegrationSettingState::STATE_BLOCKED,
            ! $complete => IntegrationSettingState::STATE_UNCONFIGURED,
            $driver === null || ! $driver->isAvailable() || ! $endpointAllowed => IntegrationSettingState::STATE_BLOCKED,
            $setting->is_enabled && $verifiedCurrent => IntegrationSettingState::STATE_ACTIVE,
            $setting->is_enabled => IntegrationSettingState::STATE_BLOCKED,
            $verifiedCurrent => IntegrationSettingState::STATE_READY,
            $setting->last_test_status === IntegrationSetting::TEST_STATUS_FAILED => IntegrationSettingState::STATE_TEST_FAILED,
            $setting->last_test_status === IntegrationSetting::TEST_STATUS_SUCCESS => IntegrationSettingState::STATE_BLOCKED,
            default => IntegrationSettingState::STATE_DRAFT,
        };

        return new IntegrationSettingState(
            provider: $setting->provider,
            label: $setting->provider === IntegrationSetting::PROVIDER_DAPODIK ? 'Dapodik' : 'e-Tatib',
            baseUrl: $setting->base_url,
            expectedSourceIdentifier: $setting->expected_source_identifier,
            timeoutSeconds: $setting->timeout_seconds ?? 30,
            hasCredentials: $hasCredentials,
            state: $state,
            configurationVersion: $setting->configuration_version ?? 0,
            operationFenceVersion: $setting->operation_fence_version ?? 0,
            verifiedConfigurationVersion: $setting->verified_configuration_version,
            verifiedDriverId: $setting->verified_driver_id,
            verifiedAdapterVersion: $setting->verified_adapter_version,
            verifiedContractVersion: $setting->verified_contract_version,
            verifiedEndpointPolicyDigest: $setting->verified_endpoint_policy_digest,
            lastTestStatus: $setting->last_test_status ?? IntegrationSetting::TEST_STATUS_UNTESTED,
            lastTestCode: $setting->last_test_code,
            lastTestedAt: $setting->last_tested_at?->toImmutable(),
            isEnabled: (bool) $setting->is_enabled,
            adapterAvailable: $driver?->isAvailable() ?? false,
            canTest: $complete && $endpointAllowed && ($driver?->isAvailable() ?? false),
            canActivate: $state === IntegrationSettingState::STATE_READY,
            canDeactivate: (bool) $setting->is_enabled,
        );
    }

    public function runtimeConfiguration(
        IntegrationSetting $setting,
        int $fencingToken,
    ): IntegrationRuntimeConfiguration {
        if (! is_string($setting->base_url) || $setting->base_url === ''
            || ! is_string($setting->expected_source_identifier) || $setting->expected_source_identifier === ''
        ) {
            throw new IntegrationConfigurationException('incomplete_configuration');
        }
        try {
            $credentials = $setting->credentials;
        } catch (DecryptException) {
            throw new IntegrationConfigurationException('credential_unreadable');
        }
        if (! is_array($credentials)
            || ($credentials['type'] ?? null) !== 'api_token'
            || ! is_string($credentials['token'] ?? null)
            || $credentials['token'] === ''
        ) {
            throw new IntegrationConfigurationException('incomplete_configuration');
        }
        try {
            $policy = $this->policy($setting->provider);
            $baseUrl = $policy->assertAllowedEndpoint($setting->base_url);
        } catch (InvalidArgumentException $exception) {
            throw new IntegrationConfigurationException('endpoint_not_allowed', $exception);
        }

        return new IntegrationRuntimeConfiguration(
            provider: $setting->provider,
            baseUrl: $baseUrl,
            expectedSourceIdentifier: $setting->expected_source_identifier,
            credentials: $credentials,
            timeoutSeconds: $setting->timeout_seconds,
            configurationVersion: $setting->configuration_version,
            operationFenceVersion: $fencingToken,
            endpointPolicyDigest: $policy->digest(),
        );
    }

    public function assertActivationInvariant(IntegrationSetting $setting): void
    {
        $configuration = $this->runtimeConfiguration($setting, $setting->operation_fence_version);
        $driver = $this->driver($setting->provider);
        if (! $driver->isAvailable()) {
            throw new IntegrationConfigurationException('adapter_unavailable');
        }
        if ($setting->last_test_status !== IntegrationSetting::TEST_STATUS_SUCCESS
            || $setting->last_test_code !== IntegrationProbeResult::CODE_SUCCESS
            || $setting->verified_configuration_version !== $setting->configuration_version
            || $setting->verified_driver_id !== $driver->id()
            || $setting->verified_adapter_version !== $driver->adapterVersion()
            || $setting->verified_contract_version !== $driver->contractVersion()
            || $setting->verified_endpoint_policy_digest !== $configuration->endpointPolicyDigest
        ) {
            throw new IntegrationConfigurationException('configuration_changed');
        }
    }

    public function driver(string $provider): IntegrationDriver
    {
        return $provider === IntegrationSetting::PROVIDER_DAPODIK
            ? $this->drivers->dapodik()
            : $this->drivers->etatib();
    }

    public function policy(string $provider): IntegrationEndpointPolicy
    {
        $configuration = config("sibk.integrations.{$provider}", []);
        $origins = $configuration['allowed_origins'] ?? [];
        $allowPrivateNetworks = $configuration['allow_private_networks'] ?? false;
        if (! is_array($origins) || ! is_bool($allowPrivateNetworks)) {
            throw new InvalidArgumentException('Integration endpoint policy configuration is invalid.');
        }

        return new IntegrationEndpointPolicy($origins, $allowPrivateNetworks);
    }

    public function auditSnapshot(IntegrationSetting $setting, bool $credentialChanged): array
    {
        return [
            'provider' => $setting->provider,
            'endpoint_origin' => $this->endpointOrigin($setting->base_url),
            'timeout_seconds' => $setting->timeout_seconds,
            'configuration_version' => $setting->configuration_version,
            'operation_fence_version' => $setting->operation_fence_version,
            'verified_configuration_version' => $setting->verified_configuration_version,
            'verified_driver_id' => $setting->verified_driver_id,
            'verified_adapter_version' => $setting->verified_adapter_version,
            'verified_contract_version' => $setting->verified_contract_version,
            'last_test_status' => $setting->last_test_status,
            'last_test_code' => $setting->last_test_code,
            'is_enabled' => (bool) $setting->is_enabled,
            'has_credentials' => $this->hasReadableCredentials($setting),
            'credential_changed' => $credentialChanged,
        ];
    }

    private function hasReadableCredentials(IntegrationSetting $setting): bool
    {
        try {
            $credentials = $setting->credentials;
        } catch (DecryptException) {
            return false;
        }

        return is_array($credentials)
            && ($credentials['type'] ?? null) === 'api_token'
            && is_string($credentials['token'] ?? null)
            && $credentials['token'] !== '';
    }

    private function endpointOrigin(?string $endpoint): ?string
    {
        if ($endpoint === null) {
            return null;
        }
        $scheme = parse_url($endpoint, PHP_URL_SCHEME);
        $host = parse_url($endpoint, PHP_URL_HOST);
        $port = parse_url($endpoint, PHP_URL_PORT);
        if (! is_string($scheme) || ! is_string($host)) {
            return null;
        }
        $defaultPort = $scheme === 'https' ? 443 : 80;

        return $scheme.'://'.$host.(is_int($port) && $port !== $defaultPort ? ":{$port}" : '');
    }

    public function assertProvider(string $provider): void
    {
        if (! in_array($provider, IntegrationSetting::PROVIDERS, true)) {
            throw new IntegrationConfigurationException('invalid_configuration');
        }
    }
}
