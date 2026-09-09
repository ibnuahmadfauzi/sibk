<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationConfigurationProvider;
use App\Integrations\IntegrationDriver;
use App\Integrations\IntegrationDriverRegistry;
use App\Integrations\IntegrationEndpointPolicy;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Integrations\IntegrationProbeResult;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Integrations\IntegrationSettingState;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Throwable;

final class IntegrationSettingService implements IntegrationConfigurationProvider
{
    public function __construct(
        private readonly IntegrationDriverRegistry $drivers,
        private readonly IntegrationOperationLock $operationLock,
        private readonly AuditService $auditService,
    ) {}

    /** @return array{dapodik: IntegrationSettingState, etatib: IntegrationSettingState} */
    public function allStates(): array
    {
        Gate::authorize('manageDataMaster');

        return [
            IntegrationSetting::PROVIDER_DAPODIK => $this->stateForProvider(IntegrationSetting::PROVIDER_DAPODIK),
            IntegrationSetting::PROVIDER_ETATIB => $this->stateForProvider(IntegrationSetting::PROVIDER_ETATIB),
        ];
    }

    /** @param array<string, mixed> $data */
    public function save(string $provider, array $data, User $actor): IntegrationSettingState
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $this->assertProvider($provider);

        return $this->operationLock->run(
            $provider,
            function (IntegrationOperationContext $context) use ($provider, $data, $actor): IntegrationSettingState {
                $normalized = $this->normalizeSaveData($provider, $data);

                return DB::transaction(function () use ($provider, $normalized, $actor, $context): IntegrationSettingState {
                    $setting = IntegrationSetting::query()
                        ->where('provider', $provider)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->operationLock->assertCurrent($context, $setting);

                    $before = $this->auditSnapshot($setting, false);
                    $credentialMutation = $normalized['replace_credentials'] || $normalized['remove_credentials'];
                    $credentialsChanged = false;
                    $existingCredentials = null;
                    $storedCredentialUnreadable = false;

                    $hasStoredCredentialPayload = $setting->getRawOriginal('credentials') !== null;
                    if ($credentialMutation || $hasStoredCredentialPayload) {
                        try {
                            $existingCredentials = $setting->credentials;
                        } catch (DecryptException $exception) {
                            if (! $credentialMutation) {
                                throw new IntegrationConfigurationException('credential_unreadable');
                            }
                            $storedCredentialUnreadable = true;
                        }
                    }

                    $nextCredentials = $existingCredentials;
                    if ($normalized['replace_credentials']) {
                        $nextCredentials = [
                            'type' => 'api_token',
                            'token' => $normalized['api_key'],
                        ];
                        $credentialsChanged = $existingCredentials !== $nextCredentials;
                    } elseif ($normalized['remove_credentials']) {
                        $nextCredentials = null;
                        $credentialsChanged = $hasStoredCredentialPayload;
                    }

                    $materialChanged = $setting->base_url !== $normalized['base_url']
                        || $setting->expected_source_identifier !== $normalized['expected_source_identifier']
                        || $setting->timeout_seconds !== $normalized['timeout_seconds']
                        || $credentialsChanged;

                    if (! $materialChanged) {
                        return $this->state($setting);
                    }

                    $setting->base_url = $normalized['base_url'];
                    $setting->expected_source_identifier = $normalized['expected_source_identifier'];
                    $setting->timeout_seconds = $normalized['timeout_seconds'];
                    if ($credentialMutation) {
                        if ($storedCredentialUnreadable) {
                            $credentialCarrier = new IntegrationSetting;
                            $credentialCarrier->credentials = $nextCredentials;
                            $rawCredentials = $credentialCarrier->getAttributes()['credentials'] ?? null;
                            DB::table('integration_settings')
                                ->where('id', $setting->getKey())
                                ->update(['credentials' => $rawCredentials]);
                            $attributes = $setting->getAttributes();
                            $attributes['credentials'] = $rawCredentials;
                            $setting->setRawAttributes($attributes);
                            $setting->syncOriginalAttribute('credentials');
                        } else {
                            $setting->credentials = $nextCredentials;
                        }
                    }
                    $setting->configuration_version++;
                    $this->clearVerification($setting);
                    $setting->last_test_status = IntegrationSetting::TEST_STATUS_UNTESTED;
                    $setting->last_test_code = null;
                    $setting->last_tested_at = null;
                    $setting->last_tested_by = null;
                    $setting->is_enabled = false;
                    $setting->updated_by = $actor->getKey();
                    $setting->save();

                    $this->auditService->record(
                        action: "{$provider}.connection_settings_updated",
                        auditable: $setting,
                        summary: 'Konfigurasi koneksi integrasi diperbarui.',
                        actor: $actor,
                        before: $before,
                        after: $this->auditSnapshot($setting, $credentialsChanged),
                    );
                    $context->assertWithinDeadline();

                    return $this->state($setting->refresh());
                });
            },
        );
    }

    public function testConnection(string $provider, User $actor): IntegrationSettingState
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $this->assertProvider($provider);

        return $this->operationLock->run(
            $provider,
            function (IntegrationOperationContext $context) use ($provider, $actor): IntegrationSettingState {
                $setting = IntegrationSetting::query()->where('provider', $provider)->firstOrFail();
                $driver = $this->driver($provider);
                $configuration = $this->runtimeConfiguration($setting, $context->fencingToken);
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
                ): IntegrationSettingState {
                    $current = IntegrationSetting::query()
                        ->where('provider', $provider)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->operationLock->assertCurrent($context, $current);
                    $this->assertCapturedConfigurationIsCurrent($current, $captured);

                    $before = $this->auditSnapshot($current, false);
                    $success = $safeCode === IntegrationProbeResult::CODE_SUCCESS;
                    if ($success) {
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
                    $current->save();

                    $this->auditService->record(
                        action: "{$provider}.connection_tested",
                        auditable: $current,
                        summary: 'Koneksi integrasi diuji tanpa mengimpor data.',
                        actor: $actor,
                        before: $before,
                        after: $this->auditSnapshot($current, false),
                    );
                    $context->assertWithinDeadline();

                    return $this->state($current->refresh());
                });
            },
        );
    }

    public function activate(string $provider, User $actor): IntegrationSettingState
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $this->assertProvider($provider);

        return $this->operationLock->run(
            $provider,
            function (IntegrationOperationContext $context) use ($provider, $actor): IntegrationSettingState {
                return DB::transaction(function () use ($provider, $actor, $context): IntegrationSettingState {
                    $setting = IntegrationSetting::query()
                        ->where('provider', $provider)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->operationLock->assertCurrent($context, $setting);
                    $this->assertActivationInvariant($setting);

                    if ($setting->is_enabled) {
                        return $this->state($setting);
                    }

                    $before = $this->auditSnapshot($setting, false);
                    $setting->is_enabled = true;
                    $setting->updated_by = $actor->getKey();
                    $setting->save();
                    $this->auditService->record(
                        action: "{$provider}.connection_enabled",
                        auditable: $setting,
                        summary: 'Koneksi integrasi diaktifkan.',
                        actor: $actor,
                        before: $before,
                        after: $this->auditSnapshot($setting, false),
                    );
                    $context->assertWithinDeadline();

                    return $this->state($setting->refresh());
                });
            },
        );
    }

    public function deactivate(string $provider, User $actor): IntegrationSettingState
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $this->assertProvider($provider);

        return $this->operationLock->run(
            $provider,
            function (IntegrationOperationContext $context) use ($provider, $actor): IntegrationSettingState {
                return DB::transaction(function () use ($provider, $actor, $context): IntegrationSettingState {
                    $setting = IntegrationSetting::query()
                        ->where('provider', $provider)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->operationLock->assertCurrent($context, $setting);
                    $before = $this->auditSnapshot($setting, false);
                    $setting->is_enabled = false;
                    $setting->updated_by = $actor->getKey();
                    $setting->save();
                    $this->auditService->record(
                        action: "{$provider}.connection_disabled",
                        auditable: $setting,
                        summary: 'Koneksi integrasi dinonaktifkan.',
                        actor: $actor,
                        before: $before,
                        after: $this->auditSnapshot($setting, false),
                    );
                    $context->assertWithinDeadline();

                    return $this->state($setting->refresh());
                });
            },
        );
    }

    public function active(
        string $provider,
        string $driverId,
        string $adapterVersion,
        string $contractVersion,
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
        $this->assertActivationInvariant($setting);

        return $this->runtimeConfiguration($setting, $setting->operation_fence_version);
    }

    public function assertCurrent(
        string $provider,
        int $configurationVersion,
        string $driverId,
        string $adapterVersion,
        string $contractVersion,
    ): void {
        $configuration = $this->active($provider, $driverId, $adapterVersion, $contractVersion);
        if ($configuration->configurationVersion !== $configurationVersion) {
            throw new IntegrationConfigurationException('configuration_changed');
        }
    }

    private function stateForProvider(string $provider): IntegrationSettingState
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

        return $this->state($setting);
    }

    private function state(IntegrationSetting $setting): IntegrationSettingState
    {
        $driver = $this->driver($setting->provider);
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
            && $setting->last_test_status === IntegrationSetting::TEST_STATUS_SUCCESS
            && $setting->verified_configuration_version === $setting->configuration_version
            && $setting->verified_driver_id === $driver->id()
            && $setting->verified_adapter_version === $driver->adapterVersion()
            && $setting->verified_contract_version === $driver->contractVersion()
            && $setting->verified_endpoint_policy_digest === $policyDigest;

        $state = match (true) {
            $credentialUnreadable => IntegrationSettingState::STATE_BLOCKED,
            ! $complete => IntegrationSettingState::STATE_UNCONFIGURED,
            ! $driver->isAvailable() || ! $endpointAllowed => IntegrationSettingState::STATE_BLOCKED,
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
            adapterAvailable: $driver->isAvailable(),
            canTest: $complete && $endpointAllowed && $driver->isAvailable(),
            canActivate: $state === IntegrationSettingState::STATE_READY,
            canDeactivate: (bool) $setting->is_enabled,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{base_url: ?string, expected_source_identifier: ?string, api_key: string, replace_credentials: bool, remove_credentials: bool, timeout_seconds: int}
     */
    private function normalizeSaveData(string $provider, array $data): array
    {
        $baseUrl = $this->nullableTrimmedString($data['base_url'] ?? null, 500);
        $expectedSourceIdentifier = $this->nullableTrimmedString(
            $data['expected_source_identifier'] ?? null,
            100,
        );
        $apiKey = $data['api_key'] ?? '';
        if (! is_string($apiKey)) {
            throw new IntegrationConfigurationException('invalid_configuration');
        }
        $replaceCredentials = $apiKey !== '';
        $removeCredentials = filter_var(
            $data['remove_api_key'] ?? false,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );
        if ($removeCredentials === null || ($replaceCredentials && $removeCredentials)) {
            throw new IntegrationConfigurationException('invalid_configuration');
        }
        $timeout = filter_var($data['timeout_seconds'] ?? 30, FILTER_VALIDATE_INT);
        if (! is_int($timeout) || $timeout < 1 || $timeout > IntegrationOperationLock::HARD_DEADLINE_SECONDS) {
            throw new IntegrationConfigurationException('invalid_configuration');
        }
        if ($baseUrl !== null) {
            try {
                $baseUrl = $this->policy($provider)->assertAllowedEndpoint($baseUrl);
            } catch (InvalidArgumentException $exception) {
                throw new IntegrationConfigurationException('endpoint_not_allowed', $exception);
            }
        }

        return [
            'base_url' => $baseUrl,
            'expected_source_identifier' => $expectedSourceIdentifier,
            'api_key' => $apiKey,
            'replace_credentials' => $replaceCredentials,
            'remove_credentials' => $removeCredentials,
            'timeout_seconds' => $timeout,
        ];
    }

    private function nullableTrimmedString(mixed $value, int $maxLength): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_string($value)) {
            throw new IntegrationConfigurationException('invalid_configuration');
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > $maxLength) {
            throw new IntegrationConfigurationException('invalid_configuration');
        }

        return $value;
    }

    private function runtimeConfiguration(
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

    /** @param array{configuration_version: int, driver_id: string, adapter_version: string, contract_version: string, endpoint_policy_digest: string} $captured */
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

    /** @param array{configuration_version: int, driver_id: string, adapter_version: string, contract_version: string, endpoint_policy_digest: string} $captured */
    private function assertCapturedConfigurationIsCurrent(IntegrationSetting $setting, array $captured): void
    {
        $driver = $this->driver($setting->provider);
        try {
            $policyDigest = $this->policy($setting->provider)->digest();
            if (is_string($setting->base_url)) {
                $this->policy($setting->provider)->assertAllowedEndpoint($setting->base_url);
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

    private function assertActivationInvariant(IntegrationSetting $setting): void
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

    private function clearVerification(IntegrationSetting $setting): void
    {
        $setting->verified_configuration_version = null;
        $setting->verified_driver_id = null;
        $setting->verified_adapter_version = null;
        $setting->verified_contract_version = null;
        $setting->verified_endpoint_policy_digest = null;
    }

    private function driver(string $provider): IntegrationDriver
    {
        return $provider === IntegrationSetting::PROVIDER_DAPODIK
            ? $this->drivers->dapodik()
            : $this->drivers->etatib();
    }

    private function policy(string $provider): IntegrationEndpointPolicy
    {
        $configuration = config("sibk.integrations.{$provider}", []);
        $origins = $configuration['allowed_origins'] ?? [];
        $allowPrivateNetworks = $configuration['allow_private_networks'] ?? false;
        if (! is_array($origins) || ! is_bool($allowPrivateNetworks)) {
            throw new InvalidArgumentException('Integration endpoint policy configuration is invalid.');
        }

        return new IntegrationEndpointPolicy($origins, $allowPrivateNetworks);
    }

    /** @return array<string, bool|int|string|null> */
    private function auditSnapshot(IntegrationSetting $setting, bool $credentialChanged): array
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

    private function assertProvider(string $provider): void
    {
        if (! in_array($provider, IntegrationSetting::PROVIDERS, true)) {
            throw new IntegrationConfigurationException('invalid_configuration');
        }
    }
}
