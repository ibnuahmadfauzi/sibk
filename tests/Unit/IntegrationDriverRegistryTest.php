<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Integrations\Dapodik\DapodikUnavailableException;
use App\Integrations\Dapodik\UnavailableDapodikDriver;
use App\Integrations\Etatib\EtatibUnavailableException;
use App\Integrations\Etatib\UnavailableEtatibDriver;
use App\Integrations\IntegrationDriver;
use App\Integrations\IntegrationDriverRegistry;
use App\Integrations\IntegrationRuntimeConfiguration;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

final class IntegrationDriverRegistryTest extends TestCase
{
    public function test_registry_resolves_only_explicitly_whitelisted_unavailable_drivers(): void
    {
        $registry = new IntegrationDriverRegistry([
            'dapodik' => ['driver' => 'unavailable'],
            'etatib' => ['driver' => 'unavailable'],
        ]);

        self::assertInstanceOf(UnavailableDapodikDriver::class, $registry->dapodik());
        self::assertInstanceOf(UnavailableEtatibDriver::class, $registry->etatib());
    }

    public function test_registry_rejects_a_driver_name_that_is_not_whitelisted(): void
    {
        $registry = new IntegrationDriverRegistry([
            'dapodik' => ['driver' => 'App\\Integrations\\Dapodik\\HttpDapodikDriver'],
            'etatib' => ['driver' => 'unavailable'],
        ]);

        $this->expectException(InvalidArgumentException::class);

        $registry->dapodik();
    }

    public function test_registry_rejects_missing_or_empty_driver_configuration(): void
    {
        $registry = new IntegrationDriverRegistry([
            'dapodik' => ['driver' => ''],
        ]);

        $this->expectException(InvalidArgumentException::class);

        $registry->dapodik();
    }

    public function test_unavailable_probe_returns_safe_code_and_separate_versions_without_outbound_http(): void
    {
        Http::preventStrayRequests();

        $driver = (new IntegrationDriverRegistry([
            'dapodik' => ['driver' => 'unavailable'],
            'etatib' => ['driver' => 'unavailable'],
        ]))->dapodik();

        $result = $driver->probe($this->configuration('dapodik'));

        self::assertSame('adapter_unavailable', $result->code);
        self::assertSame($driver->id(), $result->driverId);
        self::assertSame($driver->adapterVersion(), $result->adapterVersion);
        self::assertSame($driver->contractVersion(), $result->contractVersion);
        self::assertNotSame($result->driverId, $result->adapterVersion);
        self::assertNotSame($result->adapterVersion, $result->contractVersion);
        self::assertNull($result->reportedSourceIdentifier);
        self::assertFalse($result->schemaValid);
        self::assertFalse($result->completenessVerified);
    }

    public function test_unavailable_dapodik_driver_never_fetches_a_snapshot(): void
    {
        $driver = new UnavailableDapodikDriver;

        $this->expectException(DapodikUnavailableException::class);

        $driver->fetchSnapshot($this->configuration('dapodik'));
    }

    public function test_unavailable_etatib_driver_never_fetches_a_snapshot(): void
    {
        $driver = new UnavailableEtatibDriver;

        $this->expectException(EtatibUnavailableException::class);

        $driver->fetchSnapshot($this->configuration('etatib'));
    }

    public function test_driver_result_code_whitelist_contains_only_safe_codes(): void
    {
        self::assertSame([
            'success',
            'adapter_unavailable',
            'incomplete_configuration',
            'endpoint_not_allowed',
            'credential_unreadable',
            'busy',
            'timeout',
            'connection_failed',
            'authentication_rejected',
            'rate_limited',
            'remote_unavailable',
            'contract_invalid',
            'source_identity_mismatch',
            'response_too_large',
            'configuration_changed',
        ], IntegrationDriver::RESULT_CODES);
    }

    private function configuration(string $provider): IntegrationRuntimeConfiguration
    {
        return new IntegrationRuntimeConfiguration(
            provider: $provider,
            baseUrl: 'https://api.example.sch.id',
            expectedSourceIdentifier: 'school-001',
            credentials: ['type' => 'api_token', 'token' => 'never-used'],
            timeoutSeconds: 30,
            configurationVersion: 1,
            operationFenceVersion: 0,
            endpointPolicyDigest: str_repeat('a', 64),
        );
    }
}
