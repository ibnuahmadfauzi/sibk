<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Integrations\IntegrationSettingState;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use SensitiveParameterValue;

final class IntegrationSettingUpdater
{
    public function __construct(
        private readonly IntegrationOperationLock $operationLock,
        private readonly AuditService $auditService,
        private readonly IntegrationStateResolver $states,
    ) {}

    public function save(
        string $provider,
        #[\SensitiveParameter] array $data,
        User $actor,
    ): IntegrationSettingState {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $this->states->assertProvider($provider);
        $normalized = $this->normalizeSaveData($provider, $data);

        return $this->operationLock->run(
            $provider,
            function (IntegrationOperationContext $context) use ($provider, $normalized, $actor): IntegrationSettingState {
                return DB::transaction(function () use ($provider, $normalized, $actor, $context): IntegrationSettingState {
                    $setting = IntegrationSetting::query()
                        ->where('provider', $provider)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->operationLock->assertCurrent($context, $setting);

                    $before = $this->states->auditSnapshot($setting, false);
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
                            'token' => $normalized['api_key']->getValue(),
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
                        return $this->states->forSetting($setting);
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
                        after: $this->states->auditSnapshot($setting, $credentialsChanged),
                    );
                    $context->assertWithinDeadline();

                    return $this->states->forSetting($setting->refresh());
                });
            },
        );
    }

    private function normalizeSaveData(string $provider, #[\SensitiveParameter] array $data): array
    {
        $baseUrl = $this->nullableTrimmedString($data['base_url'] ?? null, 500);
        $expectedSourceIdentifier = $this->nullableTrimmedString(
            $data['expected_source_identifier'] ?? null,
            100,
        );
        $apiKey = $data['api_key'] ?? '';
        if (! is_string($apiKey) || mb_strlen($apiKey) > 1000) {
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
        if (! is_int($timeout) || $timeout < 5 || $timeout > 120) {
            throw new IntegrationConfigurationException('invalid_configuration');
        }
        if ($baseUrl !== null) {
            try {
                $baseUrl = $this->states->policy($provider)->assertAllowedEndpoint($baseUrl);
            } catch (InvalidArgumentException $exception) {
                throw new IntegrationConfigurationException('endpoint_not_allowed', $exception);
            }
        }

        return [
            'base_url' => $baseUrl,
            'expected_source_identifier' => $expectedSourceIdentifier,
            'api_key' => new SensitiveParameterValue($apiKey),
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

    private function clearVerification(IntegrationSetting $setting): void
    {
        $setting->verified_configuration_version = null;
        $setting->verified_driver_id = null;
        $setting->verified_adapter_version = null;
        $setting->verified_contract_version = null;
        $setting->verified_endpoint_policy_digest = null;
    }
}
