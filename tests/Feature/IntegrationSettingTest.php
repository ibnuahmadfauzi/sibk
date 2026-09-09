<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Integrations\IntegrationProbeResult;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Integrations\IntegrationSettingState;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonSerializable;
use LogicException;
use ReflectionClass;
use SensitiveParameterValue;
use Tests\TestCase;

class IntegrationSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_are_encrypted_and_hidden(): void
    {
        $token = Str::random(64);

        $setting = IntegrationSetting::query()->create([
            'provider' => IntegrationSetting::PROVIDER_DAPODIK,
            'credentials' => [
                'type' => 'api_token',
                'token' => $token,
            ],
        ]);

        $storedCredentials = DB::table('integration_settings')
            ->where('id', $setting->id)
            ->value('credentials');

        $this->assertIsString($storedCredentials);
        $this->assertStringNotContainsString($token, $storedCredentials);
        $this->assertSame($token, $setting->fresh()->credentials['token']);
        $this->assertArrayNotHasKey('credentials', $setting->toArray());
        $this->assertStringNotContainsString($token, $setting->toJson());
    }

    public function test_default_setting_is_unverified_and_disabled(): void
    {
        $setting = IntegrationSetting::query()->create([
            'provider' => IntegrationSetting::PROVIDER_ETATIB,
        ])->fresh();

        $this->assertSame(30, $setting->timeout_seconds);
        $this->assertSame(0, $setting->configuration_version);
        $this->assertNull($setting->verified_configuration_version);
        $this->assertNull($setting->verified_driver_id);
        $this->assertNull($setting->verified_adapter_version);
        $this->assertNull($setting->verified_contract_version);
        $this->assertNull($setting->verified_endpoint_policy_digest);
        $this->assertSame(IntegrationSetting::TEST_STATUS_UNTESTED, $setting->last_test_status);
        $this->assertNull($setting->last_test_code);
        $this->assertNull($setting->last_tested_at);
        $this->assertFalse($setting->is_enabled);
    }

    public function test_provider_is_unique(): void
    {
        IntegrationSetting::query()->create([
            'provider' => IntegrationSetting::PROVIDER_DAPODIK,
        ]);

        $this->expectException(QueryException::class);

        IntegrationSetting::query()->create([
            'provider' => IntegrationSetting::PROVIDER_DAPODIK,
        ]);
    }

    public function test_user_foreign_keys_become_null_when_user_is_deleted(): void
    {
        $actor = User::factory()->create();
        $setting = IntegrationSetting::query()->create([
            'provider' => IntegrationSetting::PROVIDER_DAPODIK,
            'last_tested_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $actor->delete();

        $setting->refresh();

        $this->assertNull($setting->last_tested_by);
        $this->assertNull($setting->updated_by);
    }

    public function test_corrupt_ciphertext_is_not_treated_as_configured(): void
    {
        $id = DB::table('integration_settings')->insertGetId([
            'provider' => IntegrationSetting::PROVIDER_DAPODIK,
            'credentials' => 'not-valid-ciphertext',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $setting = IntegrationSetting::query()->findOrFail($id);

        $this->expectException(DecryptException::class);

        $setting->credentials;
    }

    public function test_driver_adapter_and_contract_versions_are_stored_separately(): void
    {
        $setting = IntegrationSetting::query()->create([
            'provider' => IntegrationSetting::PROVIDER_ETATIB,
            'verified_driver_id' => 'driver-id',
            'verified_adapter_version' => 'adapter-version',
            'verified_contract_version' => 'contract-version',
        ])->fresh();

        $this->assertSame('driver-id', $setting->verified_driver_id);
        $this->assertSame('adapter-version', $setting->verified_adapter_version);
        $this->assertSame('contract-version', $setting->verified_contract_version);
    }

    public function test_verified_endpoint_policy_digest_is_persisted_separately(): void
    {
        $digest = hash('sha256', Str::random());
        $setting = IntegrationSetting::query()->create([
            'provider' => IntegrationSetting::PROVIDER_DAPODIK,
            'verified_contract_version' => 'contract-version',
            'verified_endpoint_policy_digest' => $digest,
        ])->fresh();

        $this->assertSame('contract-version', $setting->verified_contract_version);
        $this->assertSame($digest, $setting->verified_endpoint_policy_digest);
    }

    public function test_operation_fence_version_defaults_to_zero(): void
    {
        $setting = IntegrationSetting::query()->create([
            'provider' => IntegrationSetting::PROVIDER_DAPODIK,
        ])->fresh();

        $this->assertSame(0, $setting->operation_fence_version);
    }

    public function test_state_and_probe_dtos_contain_only_safe_metadata(): void
    {
        $state = new IntegrationSettingState(
            provider: IntegrationSetting::PROVIDER_DAPODIK,
            baseUrl: 'https://dapodik.example.test',
            expectedSourceIdentifier: 'school-id',
            timeoutSeconds: 30,
            hasCredentials: true,
            state: IntegrationSettingState::STATE_BLOCKED,
            configurationVersion: 1,
            operationFenceVersion: 0,
            verifiedConfigurationVersion: null,
            verifiedDriverId: null,
            verifiedAdapterVersion: null,
            verifiedContractVersion: null,
            verifiedEndpointPolicyDigest: null,
            lastTestStatus: IntegrationSetting::TEST_STATUS_UNTESTED,
            lastTestCode: null,
            lastTestedAt: null,
            isEnabled: false,
        );
        $probe = new IntegrationProbeResult(
            code: 'success',
            driverId: 'driver-id',
            adapterVersion: 'adapter-version',
            contractVersion: 'contract-version',
            reportedSourceIdentifier: 'school-id',
            schemaValid: true,
            completenessVerified: true,
        );

        $stateProperties = array_map(
            static fn (\ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass($state))->getProperties(),
        );
        $expectedStateProperties = [
            'baseUrl',
            'configurationVersion',
            'expectedSourceIdentifier',
            'hasCredentials',
            'isEnabled',
            'lastTestCode',
            'lastTestedAt',
            'lastTestStatus',
            'operationFenceVersion',
            'provider',
            'state',
            'timeoutSeconds',
            'verifiedAdapterVersion',
            'verifiedConfigurationVersion',
            'verifiedContractVersion',
            'verifiedDriverId',
            'verifiedEndpointPolicyDigest',
        ];
        sort($stateProperties);
        sort($expectedStateProperties);

        $probeProperties = array_map(
            static fn (\ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass($probe))->getProperties(),
        );
        $expectedProbeProperties = [
            'adapterVersion',
            'code',
            'completenessVerified',
            'contractVersion',
            'driverId',
            'reportedSourceIdentifier',
            'schemaValid',
        ];
        sort($probeProperties);
        sort($expectedProbeProperties);

        $this->assertSame($expectedStateProperties, $stateProperties);
        $this->assertSame($expectedProbeProperties, $probeProperties);
    }

    public function test_runtime_configuration_redacts_debug_and_export_representations(): void
    {
        $token = Str::random(64);
        $configuration = new IntegrationRuntimeConfiguration(
            provider: IntegrationSetting::PROVIDER_DAPODIK,
            baseUrl: 'https://dapodik.example.test',
            expectedSourceIdentifier: 'school-id',
            credentials: [
                'type' => 'api_token',
                'token' => $token,
            ],
            timeoutSeconds: 30,
            configurationVersion: 1,
            operationFenceVersion: 0,
            endpointPolicyDigest: hash('sha256', 'policy'),
        );

        ob_start();
        var_dump($configuration);
        $dump = ob_get_clean();

        $this->assertIsString($dump);

        $representations = [
            'print_r' => print_r($configuration, true),
            'var_dump' => $dump,
            'var_export' => var_export($configuration, true),
            'json' => json_encode($configuration, JSON_THROW_ON_ERROR),
            'log_context' => json_encode(['configuration' => $configuration], JSON_THROW_ON_ERROR),
        ];

        foreach ($representations as $surface => $representation) {
            $this->assertFalse(
                str_contains($representation, $token),
                "Credential leaked through {$surface} representation.",
            );
        }

        $credentialsProperty = (new ReflectionClass($configuration))->getProperty('credentials');

        $this->assertTrue($credentialsProperty->isPrivate());
        $this->assertTrue(
            $credentialsProperty->getValue($configuration) instanceof SensitiveParameterValue,
            'Credential must be wrapped in SensitiveParameterValue.',
        );
        $this->assertNotInstanceOf(JsonSerializable::class, $configuration);
        $this->assertFalse(method_exists($configuration, 'toArray'));
        $this->assertFalse(method_exists($configuration, 'jsonSerialize'));
        $this->assertFalse(method_exists($configuration, '__toString'));
        $this->assertSame('api_token', $configuration->credentials()['type']);
    }

    public function test_runtime_configuration_rejects_native_serialization(): void
    {
        $token = Str::random(64);
        $configuration = new IntegrationRuntimeConfiguration(
            provider: IntegrationSetting::PROVIDER_ETATIB,
            baseUrl: 'https://etatib.example.test',
            expectedSourceIdentifier: 'school-id',
            credentials: [
                'type' => 'api_token',
                'token' => $token,
            ],
            timeoutSeconds: 30,
            configurationVersion: 1,
            operationFenceVersion: 0,
            endpointPolicyDigest: hash('sha256', 'policy'),
        );

        try {
            serialize($configuration);
            $this->fail('Native serialization must be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Integration runtime configuration cannot be serialized.',
                $exception->getMessage(),
            );
            $this->assertFalse(str_contains($exception->getMessage(), $token));
        }
    }
}
