<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Integrations\Etatib\SchoolHttpEtatibDriver;
use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Models\Role;
use App\Models\User;
use App\Services\AlumniRetentionRegistry;
use App\Services\EtatibSyncService;
use App\Services\SimpleEtatibApiService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class EtatibSourceValidationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('invalidSimpleFields')]
    public function test_invalid_source_fields_cannot_enter_a_preview(string $field, mixed $value): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin_it')->sole());
        $this->app->instance(SimpleEtatibApiService::class, new SimpleEtatibApiService(
            app(EtatibSyncService::class), app(AlumniRetentionRegistry::class),
            static fn (string $host): array => ['8.8.8.8'],
        ));
        Http::preventStrayRequests();
        Http::fake(['https://etatib.example.test/pelanggaran' => Http::response([
            array_replace($this->record(), [$field => $value]),
        ])]);

        $this->actingAs($admin)->postJson(route('data-master.etatib.preview'), [
            'api_url' => 'https://etatib.example.test/pelanggaran',
        ])->assertUnprocessable()->assertJsonValidationErrors('api_url');
        $this->assertDatabaseCount('external_tatib_records', 0);
        $this->assertDatabaseCount('external_sync_runs', 0);
    }

    public static function invalidSimpleFields(): array
    {
        return [
            'invalid calendar date' => ['tanggal_pelanggaran', '31 Feb 2026 10:17:00'],
            'non leap year' => ['tanggal_pelanggaran', '29 Feb 2025 10:17:00'],
            'invalid hour' => ['tanggal_pelanggaran', '20 Jul 2026 25:17:00'],
            'points overflow' => ['poin_pelanggaran', '922337203685477580799'],
            'points exceed database range' => ['poin_pelanggaran', 2147483648],
            'total exceeds database range' => ['total_poin', '2147483648'],
        ];
    }

    #[DataProvider('invalidFormalFields')]
    public function test_formal_driver_rejects_invalid_source_fields(string $field, mixed $value): void
    {
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response($this->formalPayload(array_replace($this->record(), [
            'tanggal_pelanggaran' => '2026-07-20T10:17:00+07:00',
            $field => $value,
        ]))));

        $this->expectException(IntegrationConfigurationException::class);
        $this->driver()->fetchSnapshot($this->configuration());
    }

    public static function invalidFormalFields(): array
    {
        return [
            'invalid event date' => ['tanggal_pelanggaran', '2026-02-31T10:17:00+07:00'],
            'invalid updated date' => ['updated_at', '2025-02-29T10:18:00+07:00'],
            'invalid cancellation date' => ['deleted_at', '2026-02-31T10:19:00+07:00'],
            'points exceed database range' => ['poin_pelanggaran', 2147483648],
            'total exceeds database range' => ['total_poin', 2147483648],
        ];
    }

    public function test_formal_driver_accepts_leap_day_and_database_integer_boundaries(): void
    {
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response($this->formalPayload(array_replace($this->record(), [
            'tanggal_pelanggaran' => '2024-02-29T10:17:00+07:00',
            'poin_pelanggaran' => 0,
            'total_poin' => 2147483647,
        ]))));

        $snapshot = $this->driver()->fetchSnapshot($this->configuration());
        $this->assertSame('2024-02-29 10:17:00', $snapshot->records[0]['occurred_at']);
        $this->assertSame(0, $snapshot->records[0]['points']);
        $this->assertSame(2147483647, $snapshot->records[0]['source_total_points']);
    }

    private function record(): array
    {
        return [
            'siswa_nisn' => '0012345678', 'siswa_nama' => 'Murid Sumber', 'siswa_kelas' => 'X RPL 1',
            'pelanggaran' => 'Terlambat', 'poin_pelanggaran' => 5, 'pencatat' => 'Petugas',
            'kategori' => 'ringan', 'tanggal_pelanggaran' => '20 Jul 2026 10:17:00', 'total_poin' => 5,
        ];
    }

    private function formalPayload(array $record): array
    {
        return [
            'success' => true, 'source_id' => 'smkn1-surabaya',
            'contract_version' => SchoolHttpEtatibDriver::CONTRACT_VERSION, 'mode' => 'full',
            'snapshot_id' => 'snapshot-test', 'generated_at' => '2026-09-24T18:00:00+07:00',
            'watermark' => '2026-09-24T18:00:00+07:00', 'next_cursor' => null, 'has_more' => false,
            'total' => 1, 'data' => [array_replace([
                'id_pelanggaran' => 'evt-1', 'updated_at' => '2026-09-24T18:00:00+07:00',
                'deleted_at' => null,
            ], $record)],
        ];
    }

    private function driver(): SchoolHttpEtatibDriver
    {
        return new SchoolHttpEtatibDriver(static fn (string $host): array => ['8.8.8.8']);
    }

    private function configuration(): IntegrationRuntimeConfiguration
    {
        return new IntegrationRuntimeConfiguration(
            provider: 'etatib', baseUrl: 'https://etatib.example.test/pelanggaran',
            expectedSourceIdentifier: 'smkn1-surabaya', credentials: [], timeoutSeconds: 10,
            configurationVersion: 1, operationFenceVersion: 1, endpointPolicyDigest: hash('sha256', 'test'),
        );
    }
}
