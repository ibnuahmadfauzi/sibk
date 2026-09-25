<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Integrations\Etatib\SchoolHttpEtatibDriver;
use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SchoolHttpEtatibDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_snapshot_normalizes_nisn_and_probe_only_exposes_masked_samples(): void
    {
        Student::query()->create([
            'nisn' => '0095000088',
            'name' => 'Ferryscha Putri',
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response(
            $this->payload('full', [$this->record('95000088')]),
            200,
        ));

        $driver = $this->driver();
        $snapshot = $driver->fetchSnapshot($this->configuration());
        $probe = $driver->probe($this->configuration());

        $this->assertTrue($snapshot->isFullSnapshot);
        $this->assertSame('0095000088', $snapshot->records[0]['nisn']);
        $this->assertSame('95000088', $snapshot->records[0]['source_nisn']);
        $this->assertSame(20, $snapshot->records[0]['source_total_points']);
        $this->assertSame(0, $probe->preview['conflict_count']);
        $this->assertSame('0095****88', $probe->preview['samples'][0]['nisn']);
        $this->assertStringNotContainsString('0095000088', json_encode($probe->preview, JSON_THROW_ON_ERROR));
    }

    public function test_delta_uses_checkpoint_and_reads_all_cursor_pages(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $this->assertSame('delta', $query['mode'] ?? null);
            $this->assertSame('2026-09-23T18:00:00+07:00', $query['updated_since'] ?? null);

            if (($query['cursor'] ?? null) === 'page-2') {
                return Http::response($this->payload('delta', [$this->record('95000089', 'evt-2')], false), 200);
            }

            return Http::response($this->payload('delta', [$this->record('95000088')], true), 200);
        });

        $snapshot = $this->driver()->fetchSnapshot($this->configuration(
            watermark: '2026-09-23T18:00:00+07:00',
            lastFull: now()->toAtomString(),
        ));

        $this->assertFalse($snapshot->isFullSnapshot);
        $this->assertCount(2, $snapshot->records);
        $this->assertSame(2, $snapshot->evidence?->pageCount);
    }

    public function test_full_reconciliation_is_forced_after_seven_days(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $this->assertSame('full', $query['mode'] ?? null);
            $this->assertArrayNotHasKey('updated_since', $query);

            return Http::response($this->payload('full', [$this->record('95000088')]), 200);
        });

        $snapshot = $this->driver()->fetchSnapshot($this->configuration(
            watermark: '2026-09-15T18:00:00+07:00',
            lastFull: now()->subDays(8)->toAtomString(),
        ));

        $this->assertTrue($snapshot->isFullSnapshot);
    }

    public function test_masked_nisn_and_non_wib_timestamp_are_rejected(): void
    {
        Http::preventStrayRequests();
        $record = $this->record('95****88');
        $record['updated_at'] = '2026-07-20T10:18:00Z';
        Http::fake(['*' => Http::response($this->payload('full', [$record]), 200)]);

        $this->expectException(IntegrationConfigurationException::class);
        $this->driver()->fetchSnapshot($this->configuration());
    }

    public function test_scheduled_command_skips_cleanly_when_connection_is_inactive(): void
    {
        $this->artisan('sibk:sync-etatib')
            ->expectsOutputToContain('koneksi e-Tatib tidak aktif')
            ->assertSuccessful();
    }

    public function test_private_dns_result_is_rejected_before_http_request(): void
    {
        Http::preventStrayRequests();
        $driver = new SchoolHttpEtatibDriver(static fn (string $host): array => ['127.0.0.1']);

        $this->expectException(IntegrationConfigurationException::class);
        $driver->fetchSnapshot($this->configuration());
    }

    public function test_connection_failure_returns_safe_code(): void
    {
        Http::preventStrayRequests();
        Http::fake(Http::failedConnection('timeout'));

        try {
            $this->driver()->fetchSnapshot($this->configuration());
            $this->fail('Connection failure should be rejected.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('connection_failed', $exception->resultCode());
        }
    }

    public function test_redirect_non_json_and_oversized_page_are_rejected(): void
    {
        Http::preventStrayRequests();

        foreach ([
            Http::response('', 302, ['Location' => 'https://other.example.test']),
            Http::response('<html>not json</html>', 200),
            Http::response(str_repeat('x', 2 * 1024 * 1024 + 1), 200),
        ] as $response) {
            Http::fake(['*' => $response]);

            try {
                $this->driver()->fetchSnapshot($this->configuration());
                $this->fail('Invalid HTTP response should be rejected.');
            } catch (IntegrationConfigurationException $exception) {
                $this->assertContains($exception->resultCode(), ['contract_invalid', 'response_too_large']);
            }
        }
    }

    private function configuration(?string $watermark = null, ?string $lastFull = null): IntegrationRuntimeConfiguration
    {
        return new IntegrationRuntimeConfiguration(
            provider: 'etatib',
            baseUrl: 'https://etatib.example.test/api/pelanggaran',
            expectedSourceIdentifier: 'smkn1-surabaya',
            credentials: [],
            timeoutSeconds: 10,
            configurationVersion: 1,
            operationFenceVersion: 1,
            endpointPolicyDigest: hash('sha256', 'test'),
            syncWatermark: $watermark,
            lastFullSyncedAt: $lastFull,
        );
    }

    private function driver(): SchoolHttpEtatibDriver
    {
        return new SchoolHttpEtatibDriver(static fn (string $host): array => ['8.8.8.8']);
    }

    /** @param list<array<string, mixed>> $records @return array<string, mixed> */
    private function payload(string $mode, array $records, bool $hasMore = false): array
    {
        return [
            'success' => true,
            'source_id' => 'smkn1-surabaya',
            'contract_version' => SchoolHttpEtatibDriver::CONTRACT_VERSION,
            'mode' => $mode,
            'snapshot_id' => 'snapshot-20260924',
            'generated_at' => '2026-09-24T18:00:00+07:00',
            'watermark' => '2026-09-24T18:00:00+07:00',
            'next_cursor' => $hasMore ? 'page-2' : null,
            'has_more' => $hasMore,
            'total' => ($hasMore || ($records[0]['id_pelanggaran'] ?? '') === 'evt-2')
                ? 2
                : count($records),
            'data' => $records,
        ];
    }

    /** @return array<string, mixed> */
    private function record(string $nisn, string $id = 'evt-1'): array
    {
        return [
            'id_pelanggaran' => $id,
            'siswa_nisn' => $nisn,
            'siswa_nama' => 'FERRYSCHA PUTRI',
            'siswa_kelas' => '12 PH 2',
            'pelanggaran' => 'Datang Terlambat',
            'poin_pelanggaran' => 10,
            'pencatat' => 'ANIS',
            'kategori' => 'ringan',
            'tanggal_pelanggaran' => '2026-07-20T10:17:00+07:00',
            'total_poin' => 20,
            'updated_at' => '2026-07-20T10:18:00+07:00',
            'deleted_at' => null,
        ];
    }
}
