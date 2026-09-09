<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Integrations\Dapodik\DapodikDriver;
use App\Integrations\Dapodik\DapodikSnapshot;
use App\Integrations\IntegrationBusyException;
use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationDriverRegistry;
use App\Integrations\IntegrationOperationLock;
use App\Integrations\IntegrationProbeResult;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Integrations\IntegrationSettingState;
use App\Models\AuditLog;
use App\Models\IntegrationSetting;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\IntegrationSettingService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonSerializable;
use LogicException;
use ReflectionClass;
use RuntimeException;
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
            label: 'Dapodik',
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
            adapterAvailable: false,
            canTest: false,
            canActivate: false,
            canDeactivate: false,
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
            'adapterAvailable',
            'canActivate',
            'canDeactivate',
            'canTest',
            'configurationVersion',
            'expectedSourceIdentifier',
            'hasCredentials',
            'isEnabled',
            'label',
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

    public function test_save_handles_first_save_keep_replace_remove_material_change_and_no_op(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $driver = new ConfigurableDapodikDriver;
        $service = $this->service($driver);
        $firstToken = 'token-first-save';

        $first = $service->save(IntegrationSetting::PROVIDER_DAPODIK, [
            'base_url' => 'https://dapodik.example.test/api',
            'expected_source_identifier' => 'school-01',
            'api_key' => $firstToken,
            'remove_api_key' => false,
            'timeout_seconds' => 30,
        ], $admin);

        $this->assertSame(1, $first->configurationVersion);
        $this->assertSame(IntegrationSettingState::STATE_DRAFT, $first->state);
        $this->assertTrue($first->hasCredentials);

        $noop = $service->save(IntegrationSetting::PROVIDER_DAPODIK, [
            'base_url' => 'https://dapodik.example.test/api',
            'expected_source_identifier' => 'school-01',
            'api_key' => '',
            'remove_api_key' => false,
            'timeout_seconds' => 30,
        ], $admin);

        $this->assertSame(1, $noop->configurationVersion);
        $this->assertSame($firstToken, IntegrationSetting::query()->sole()->credentials['token']);
        $this->assertSame(1, AuditLog::query()->where('action', 'dapodik.connection_settings_updated')->count());

        $replacement = 'token-replacement';
        $replaced = $service->save(IntegrationSetting::PROVIDER_DAPODIK, [
            'base_url' => 'https://dapodik.example.test/api/v2',
            'expected_source_identifier' => 'school-01',
            'api_key' => $replacement,
            'remove_api_key' => false,
            'timeout_seconds' => 25,
        ], $admin);

        $this->assertSame(2, $replaced->configurationVersion);
        $this->assertSame($replacement, IntegrationSetting::query()->sole()->credentials['token']);

        $removed = $service->save(IntegrationSetting::PROVIDER_DAPODIK, [
            'base_url' => 'https://dapodik.example.test/api/v2',
            'expected_source_identifier' => 'school-01',
            'api_key' => '',
            'remove_api_key' => true,
            'timeout_seconds' => 25,
        ], $admin);

        $this->assertSame(3, $removed->configurationVersion);
        $this->assertSame(IntegrationSettingState::STATE_UNCONFIGURED, $removed->state);
        $this->assertNull(IntegrationSetting::query()->sole()->credentials);

        $serializedAudit = AuditLog::query()->get()->map(fn (AuditLog $audit): string => json_encode([
            $audit->summary,
            $audit->before_values,
            $audit->after_values,
        ], JSON_THROW_ON_ERROR))->implode('\n');
        $this->assertStringNotContainsString($firstToken, $serializedAudit);
        $this->assertStringNotContainsString($replacement, $serializedAudit);
        $this->assertSame(
            ['configuration_version', 'credential_changed', 'endpoint_origin', 'has_credentials', 'is_enabled', 'last_test_code', 'last_test_status', 'operation_fence_version', 'provider', 'timeout_seconds', 'verified_adapter_version', 'verified_configuration_version', 'verified_contract_version', 'verified_driver_id'],
            tap(array_keys(AuditLog::query()->where('action', 'dapodik.connection_settings_updated')->latest('id')->firstOrFail()->after_values), static fn (array &$keys) => sort($keys)),
        );
    }

    public function test_save_rejects_replace_and_remove_together_without_exposing_or_changing_secret(): void
    {
        $admin = $this->admin();
        $service = $this->service(new ConfigurableDapodikDriver);
        $service->save('dapodik', $this->completeData('existing-secret'), $admin);

        try {
            $service->save('dapodik', [
                ...$this->completeData('new-secret'),
                'remove_api_key' => true,
            ], $admin);
            $this->fail('Replace and remove must be rejected.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('invalid_configuration', $exception->resultCode());
            $this->assertStringNotContainsString('new-secret', $exception->getMessage());
        }

        $this->assertSame('existing-secret', IntegrationSetting::query()->sole()->credentials['token']);
        $this->assertSame(1, IntegrationSetting::query()->sole()->configuration_version);
    }

    public function test_unreadable_credential_blocks_state_but_can_be_safely_replaced(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $driver = new ConfigurableDapodikDriver;
        $service = $this->service($driver);
        DB::table('integration_settings')->insert([
            'provider' => 'dapodik',
            'base_url' => 'https://dapodik.example.test/api',
            'expected_source_identifier' => 'school-01',
            'credentials' => 'corrupt-ciphertext',
            'timeout_seconds' => 30,
            'configuration_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(IntegrationSettingState::STATE_BLOCKED, $service->allStates()['dapodik']->state);
        $recovered = $service->save('dapodik', $this->completeData('replacement-secret'), $admin);

        $this->assertSame(IntegrationSettingState::STATE_DRAFT, $recovered->state);
        $this->assertSame('replacement-secret', IntegrationSetting::query()->sole()->credentials['token']);
    }

    public function test_incomplete_source_identity_stays_unconfigured_and_rejects_test_and_activation(): void
    {
        $admin = $this->admin();
        $service = $this->service(new ConfigurableDapodikDriver);
        $state = $service->save('dapodik', [
            ...$this->completeData(),
            'expected_source_identifier' => '   ',
        ], $admin);

        $this->assertSame(IntegrationSettingState::STATE_UNCONFIGURED, $state->state);

        foreach (['testConnection', 'activate'] as $method) {
            try {
                $service->{$method}('dapodik', $admin);
                $this->fail("{$method} must reject an incomplete configuration.");
            } catch (IntegrationConfigurationException $exception) {
                $this->assertSame('incomplete_configuration', $exception->resultCode());
            }
        }
    }

    public function test_successful_test_activation_deactivation_and_failed_test_follow_state_machine_without_sync_side_effects(): void
    {
        $admin = $this->admin();
        $driver = new ConfigurableDapodikDriver;
        $service = $this->service($driver);
        $service->save('dapodik', $this->completeData(), $admin);

        $runCount = DB::table('external_sync_runs')->count();
        $studentCount = DB::table('students')->count();
        $ready = $service->testConnection('dapodik', $admin);

        $this->assertSame(IntegrationSettingState::STATE_READY, $ready->state);
        $this->assertSame($runCount, DB::table('external_sync_runs')->count());
        $this->assertSame($studentCount, DB::table('students')->count());
        $this->assertSame(IntegrationSettingState::STATE_ACTIVE, $service->activate('dapodik', $admin)->state);
        $this->assertSame(IntegrationSettingState::STATE_READY, $service->deactivate('dapodik', $admin)->state);

        $driver->probeResult = $driver->result(code: 'authentication_rejected');
        $failed = $service->testConnection('dapodik', $admin);
        $this->assertSame(IntegrationSettingState::STATE_TEST_FAILED, $failed->state);
        $this->assertSame('authentication_rejected', $failed->lastTestCode);
        $this->assertFalse($failed->isEnabled);
        $this->assertNull($failed->verifiedConfigurationVersion);

        $actions = AuditLog::query()->pluck('action')->all();
        $this->assertContains('dapodik.connection_tested', $actions);
        $this->assertContains('dapodik.connection_enabled', $actions);
        $this->assertContains('dapodik.connection_disabled', $actions);
    }

    public function test_source_identity_and_minimum_schema_are_required_for_probe_success(): void
    {
        $admin = $this->admin();
        $driver = new ConfigurableDapodikDriver;
        $service = $this->service($driver);
        $service->save('dapodik', $this->completeData(), $admin);

        $driver->probeResult = $driver->result(reportedSourceIdentifier: 'other-school');
        $identityMismatch = $service->testConnection('dapodik', $admin);
        $this->assertSame(IntegrationSettingState::STATE_TEST_FAILED, $identityMismatch->state);
        $this->assertSame('source_identity_mismatch', $identityMismatch->lastTestCode);

        $driver->probeResult = $driver->result(schemaValid: false);
        $schemaFailure = $service->testConnection('dapodik', $admin);
        $this->assertSame('contract_invalid', $schemaFailure->lastTestCode);

        $driver->probeResult = $driver->result(completenessVerified: false);
        $minimumSchemaSuccess = $service->testConnection('dapodik', $admin);
        $this->assertSame(IntegrationSettingState::STATE_READY, $minimumSchemaSuccess->state);
    }

    public function test_stale_probe_result_is_not_written_after_configuration_changes(): void
    {
        $admin = $this->admin();
        $driver = new ConfigurableDapodikDriver;
        $service = $this->service($driver);
        $service->save('dapodik', $this->completeData(), $admin);
        $driver->onProbe = static function (): void {
            IntegrationSetting::query()->where('provider', 'dapodik')->increment('configuration_version');
        };

        $this->expectException(IntegrationConfigurationException::class);

        try {
            $service->testConnection('dapodik', $admin);
        } finally {
            $setting = IntegrationSetting::query()->sole();
            $this->assertSame(IntegrationSetting::TEST_STATUS_UNTESTED, $setting->last_test_status);
            $this->assertNull($setting->verified_configuration_version);
        }
    }

    public function test_driver_adapter_contract_and_allowlist_drift_block_effective_state_and_runtime_configuration(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $driver = new ConfigurableDapodikDriver;
        $service = $this->service($driver);
        $service->save('dapodik', $this->completeData(), $admin);
        $service->testConnection('dapodik', $admin);
        $service->activate('dapodik', $admin);

        foreach (['driverId', 'adapter', 'contract'] as $property) {
            $original = $driver->{$property};
            $driver->{$property} = $original.'-changed';
            $this->assertSame(IntegrationSettingState::STATE_BLOCKED, $service->allStates()['dapodik']->state);
            $driver->{$property} = $original;
        }

        config()->set('sibk.integrations.dapodik.allowed_origins', [
            'https://dapodik.example.test',
            'https://another.example.test',
        ]);
        $this->assertSame(IntegrationSettingState::STATE_BLOCKED, $service->allStates()['dapodik']->state);

        try {
            $service->active('dapodik', $driver->id(), $driver->adapterVersion(), $driver->contractVersion());
            $this->fail('Policy drift must block runtime configuration.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('configuration_changed', $exception->resultCode());
        }
    }

    public function test_stale_fencing_token_after_lease_ownership_moves_cannot_write_probe_result(): void
    {
        $admin = $this->admin();
        $driver = new ConfigurableDapodikDriver;
        $lock = new IntegrationOperationLock(app(CacheFactory::class));
        $service = $this->service($driver, operationLock: $lock);
        $service->save('dapodik', $this->completeData(), $admin);
        $driver->onProbe = function () use ($lock): void {
            app(CacheFactory::class)->store()
                ->lock('sibk:integration:dapodik:operation')
                ->forceRelease();
            $lock->run('dapodik', static fn (): null => null);
        };

        try {
            $service->testConnection('dapodik', $admin);
            $this->fail('Stale fencing token must be rejected.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('configuration_changed', $exception->resultCode());
        }

        $setting = IntegrationSetting::query()->sole();
        $this->assertSame(3, $setting->operation_fence_version);
        $this->assertSame(IntegrationSetting::TEST_STATUS_UNTESTED, $setting->last_test_status);
    }

    public function test_operation_lock_is_non_blocking_and_enforces_deadline_invariant_and_hard_deadline(): void
    {
        $cache = app(CacheFactory::class);
        $lock = new IntegrationOperationLock($cache);
        $busyWasRaised = false;

        $lock->run('dapodik', function () use ($lock, &$busyWasRaised): void {
            try {
                $lock->run('dapodik', static fn (): null => null);
            } catch (IntegrationBusyException) {
                $busyWasRaised = true;
            }
        });
        $this->assertTrue($busyWasRaised);

        $this->expectException(\InvalidArgumentException::class);
        new IntegrationOperationLock($cache, leaseTtlSeconds: 10, hardDeadlineSeconds: 9, safetyMarginSeconds: 2);
    }

    public function test_probe_result_after_hard_deadline_is_not_written(): void
    {
        $admin = $this->admin();
        $driver = new ConfigurableDapodikDriver;
        $service = $this->service($driver);
        $startedAt = CarbonImmutable::now();
        $service->save('dapodik', $this->completeData(), $admin);
        $driver->onProbe = static function () use ($startedAt): void {
            CarbonImmutable::setTestNow($startedAt->addSeconds(IntegrationOperationLock::HARD_DEADLINE_SECONDS + 1));
        };

        try {
            $service->testConnection('dapodik', $admin);
            $this->fail('Expired operation must be rejected.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('timeout', $exception->resultCode());
        } finally {
            CarbonImmutable::setTestNow();
        }

        $this->assertSame(IntegrationSetting::TEST_STATUS_UNTESTED, IntegrationSetting::query()->sole()->last_test_status);
    }

    public function test_configuration_change_rolls_back_when_audit_write_fails(): void
    {
        $admin = $this->admin();
        $audit = \Mockery::mock(AuditService::class);
        $audit->shouldReceive('record')->once()->andThrow(new RuntimeException('audit unavailable'));
        $service = $this->service(new ConfigurableDapodikDriver, auditService: $audit);

        try {
            $service->save('dapodik', $this->completeData('never-persisted-secret'), $admin);
            $this->fail('Audit failure must roll back configuration change.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
            $this->assertStringNotContainsString('never-persisted-secret', $exception->getMessage());
        }

        $setting = IntegrationSetting::query()->sole();
        $this->assertNull($setting->base_url);
        $this->assertNull($setting->credentials);
        $this->assertSame(0, $setting->configuration_version);
        $this->assertSame(0, AuditLog::query()->count());
    }

    public function test_probe_state_rolls_back_when_audit_write_fails(): void
    {
        $admin = $this->admin();
        $driver = new ConfigurableDapodikDriver;
        $normalService = $this->service($driver);
        $normalService->save('dapodik', $this->completeData(), $admin);
        $audit = \Mockery::mock(AuditService::class);
        $audit->shouldReceive('record')->once()->andThrow(new RuntimeException('audit unavailable'));
        $service = $this->service($driver, auditService: $audit);

        try {
            $service->testConnection('dapodik', $admin);
            $this->fail('Audit failure must roll back probe result.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }

        $setting = IntegrationSetting::query()->sole();
        $this->assertSame(IntegrationSetting::TEST_STATUS_UNTESTED, $setting->last_test_status);
        $this->assertNull($setting->verified_configuration_version);
        $this->assertFalse($setting->is_enabled);
    }

    public function test_production_driver_remains_unavailable_and_blocks_test_activation(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        config()->set('sibk.integrations.dapodik', [
            'driver' => 'unavailable',
            'allowed_origins' => ['https://dapodik.example.test'],
            'allow_private_networks' => false,
        ]);
        $service = app(IntegrationSettingService::class);
        $saved = $service->save('dapodik', $this->completeData(), $admin);

        $this->assertSame(IntegrationSettingState::STATE_BLOCKED, $saved->state);
        $tested = $service->testConnection('dapodik', $admin);
        $this->assertSame(IntegrationSettingState::STATE_BLOCKED, $tested->state);
        $this->assertSame('adapter_unavailable', $tested->lastTestCode);

        try {
            $service->activate('dapodik', $admin);
            $this->fail('Unavailable production adapter must not activate.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('adapter_unavailable', $exception->resultCode());
        }
    }

    public function test_service_authorizes_configuration_actions_and_all_states_are_safe_and_capability_aware(): void
    {
        $user = User::factory()->create();
        $service = $this->service(new ConfigurableDapodikDriver);

        foreach (['save', 'testConnection', 'activate', 'deactivate'] as $method) {
            try {
                $method === 'save'
                    ? $service->save('dapodik', $this->completeData(), $user)
                    : $service->{$method}('dapodik', $user);
                $this->fail("{$method} must authorize in the service.");
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }

        $admin = $this->admin();
        $this->actingAs($admin);
        $states = $service->allStates();
        $this->assertSame(['dapodik', 'etatib'], array_keys($states));
        $this->assertSame('Dapodik', $states['dapodik']->label);
        $this->assertTrue($states['dapodik']->adapterAvailable);
        $this->assertFalse($states['dapodik']->canTest);
        $this->assertFalse($states['dapodik']->canActivate);
        $this->assertFalse($states['dapodik']->canDeactivate);
        $this->assertStringNotContainsString('token', json_encode($states, JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    private function completeData(string $token = 'secret-token'): array
    {
        return [
            'base_url' => 'https://dapodik.example.test/api',
            'expected_source_identifier' => 'school-01',
            'api_key' => $token,
            'remove_api_key' => false,
            'timeout_seconds' => 30,
        ];
    }

    private function admin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => 'admin_it'],
            ['name' => 'Admin IT', 'is_active' => true],
        );
        $admin = User::factory()->create();
        $admin->roles()->attach($role);

        return $admin;
    }

    private function service(
        ConfigurableDapodikDriver $driver,
        ?IntegrationOperationLock $operationLock = null,
        ?AuditService $auditService = null,
    ): IntegrationSettingService {
        config()->set('sibk.integrations.dapodik', [
            'driver' => 'unavailable',
            'allowed_origins' => ['https://dapodik.example.test'],
            'allow_private_networks' => false,
        ]);
        config()->set('sibk.integrations.etatib', [
            'driver' => 'unavailable',
            'allowed_origins' => ['https://etatib.example.test'],
            'allow_private_networks' => false,
        ]);

        return new IntegrationSettingService(
            drivers: new IntegrationDriverRegistry(dapodikDriver: $driver),
            operationLock: $operationLock ?? app(IntegrationOperationLock::class),
            auditService: $auditService ?? app(AuditService::class),
        );
    }
}

final class ConfigurableDapodikDriver implements DapodikDriver
{
    public string $driverId = 'fake-dapodik';

    public string $adapter = 'fake-adapter-v1';

    public string $contract = 'fake-contract-v1';

    public bool $available = true;

    public ?IntegrationProbeResult $probeResult = null;

    public ?Closure $onProbe = null;

    public function id(): string
    {
        return $this->driverId;
    }

    public function adapterVersion(): string
    {
        return $this->adapter;
    }

    public function contractVersion(): string
    {
        return $this->contract;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function probe(IntegrationRuntimeConfiguration $configuration): IntegrationProbeResult
    {
        ($this->onProbe ?? static fn (): null => null)();

        return $this->probeResult ?? $this->result();
    }

    public function fetchSnapshot(IntegrationRuntimeConfiguration $configuration): DapodikSnapshot
    {
        throw new LogicException('Not used by configuration lifecycle tests.');
    }

    public function result(
        string $code = IntegrationProbeResult::CODE_SUCCESS,
        string $reportedSourceIdentifier = 'school-01',
        bool $schemaValid = true,
        bool $completenessVerified = true,
    ): IntegrationProbeResult {
        return new IntegrationProbeResult(
            code: $code,
            driverId: $this->driverId,
            adapterVersion: $this->adapter,
            contractVersion: $this->contract,
            reportedSourceIdentifier: $reportedSourceIdentifier,
            schemaValid: $schemaValid,
            completenessVerified: $completenessVerified,
        );
    }
}
