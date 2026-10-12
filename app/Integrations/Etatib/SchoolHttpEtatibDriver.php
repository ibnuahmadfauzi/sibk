<?php

declare(strict_types=1);

namespace App\Integrations\Etatib;

use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationProbeResult;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Integrations\IntegrationSnapshotEvidence;
use App\Models\Student;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;
use Psr\Http\Message\StreamInterface;
use Throwable;

final class SchoolHttpEtatibDriver implements EtatibDriver
{
    public const string CONTRACT_VERSION = 'etatib-school-v1';

    private const int PAGE_SIZE = 500;

    private const int MAXIMUM_PAGES = 20;

    private const int MAXIMUM_RECORDS = 10000;

    private const int MAXIMUM_PAGE_BYTES = 2097152;

    private const int MAXIMUM_OPERATION_BYTES = 10485760;

    /** @param (Closure(string): list<string>)|null $resolver */
    public function __construct(private readonly ?Closure $resolver = null) {}

    public function id(): string
    {
        return 'school_http_v1';
    }

    public function adapterVersion(): string
    {
        return 'school-http-etatib-adapter-v1';
    }

    public function contractVersion(): string
    {
        return self::CONTRACT_VERSION;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function probe(IntegrationRuntimeConfiguration $configuration): IntegrationProbeResult
    {
        try {
            $snapshot = $this->fetch($configuration, forceFull: true);
            $dates = collect($snapshot->records)->pluck('occurred_at')->filter()->sort()->values();
            $students = Student::query()
                ->whereIn('nisn', collect($snapshot->records)->pluck('nisn')->unique())
                ->get()
                ->keyBy('nisn');
            $conflicts = collect($snapshot->records)->filter(function (array $record) use ($students): bool {
                $student = $students->get($record['nisn']);

                return $student === null
                    || $this->normalizeName($record['source_student_name']) !== $this->normalizeName($student->name);
            })->count();

            return new IntegrationProbeResult(
                code: IntegrationProbeResult::CODE_SUCCESS,
                driverId: $this->id(),
                adapterVersion: $this->adapterVersion(),
                contractVersion: $this->contractVersion(),
                reportedSourceIdentifier: $snapshot->evidence?->reportedSourceIdentifier,
                schemaValid: true,
                completenessVerified: $snapshot->isFullSnapshot,
                preview: [
                    'mode' => 'full',
                    'record_count' => count($snapshot->records),
                    'conflict_count' => $conflicts,
                    'from' => $dates->first(),
                    'until' => $dates->last(),
                    'samples' => collect($snapshot->records)->take(5)->map(fn (array $record): array => [
                        'nisn' => $this->maskNisn($record['nisn']),
                        'violation' => $record['violation_type'],
                        'occurred_at' => $record['occurred_at'],
                        'points' => $record['points'],
                    ])->all(),
                ],
            );
        } catch (IntegrationConfigurationException $exception) {
            return new IntegrationProbeResult(
                code: in_array($exception->resultCode(), IntegrationProbeResult::RESULT_CODES, true)
                    ? $exception->resultCode()
                    : 'connection_failed',
                driverId: $this->id(),
                adapterVersion: $this->adapterVersion(),
                contractVersion: $this->contractVersion(),
                reportedSourceIdentifier: null,
                schemaValid: false,
                completenessVerified: false,
            );
        }
    }

    public function fetchSnapshot(IntegrationRuntimeConfiguration $configuration): EtatibSnapshot
    {
        return $this->fetch($configuration, forceFull: false);
    }

    private function fetch(IntegrationRuntimeConfiguration $configuration, bool $forceFull): EtatibSnapshot
    {
        if (! str_starts_with($configuration->baseUrl, 'https://')) {
            throw new IntegrationConfigurationException('endpoint_not_allowed');
        }

        $lastFull = $configuration->lastFullSyncedAt === null
            ? null
            : new DateTimeImmutable($configuration->lastFullSyncedAt);
        $fullDue = $lastFull === null || $lastFull <= new DateTimeImmutable('-7 days');
        $mode = $forceFull || $configuration->syncWatermark === null || $fullDue ? 'full' : 'delta';
        $cursor = null;
        $records = [];
        $processedBytes = 0;
        $pageCount = 0;
        $identity = null;
        $snapshotId = null;
        $watermark = null;
        $expectedTotal = null;
        $request = $this->request($configuration);

        do {
            $pageCount++;
            if ($pageCount > self::MAXIMUM_PAGES) {
                throw new IntegrationConfigurationException('response_too_large');
            }

            $query = ['mode' => $mode, 'limit' => self::PAGE_SIZE];
            if ($mode === 'delta') {
                $query['updated_since'] = (string) $configuration->syncWatermark;
            }
            if ($cursor !== null) {
                $query['cursor'] = $cursor;
            }

            try {
                $response = $request->get($configuration->baseUrl, $query);
            } catch (ConnectionException) {
                throw new IntegrationConfigurationException('connection_failed');
            } catch (Throwable) {
                throw new IntegrationConfigurationException('remote_unavailable');
            }

            if ($response->redirect()) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
            if ($response->status() === 429) {
                throw new IntegrationConfigurationException('rate_limited');
            }
            if ($response->serverError()) {
                throw new IntegrationConfigurationException('remote_unavailable');
            }
            if (! $response->successful()) {
                throw new IntegrationConfigurationException('connection_failed');
            }

            $body = $this->readLimitedBody($response->toPsrResponse()->getBody(), $processedBytes);
            $bytes = strlen($body);
            $processedBytes += $bytes;

            try {
                $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
            if (! is_array($payload)) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
            $this->assertEnvelope($payload, $mode);

            $pageIdentity = (string) $payload['source_id'];
            $pageSnapshotId = (string) $payload['snapshot_id'];
            $pageWatermark = (string) $payload['watermark'];
            $pageTotal = (int) $payload['total'];
            if (($identity !== null && $identity !== $pageIdentity)
                || ($snapshotId !== null && $snapshotId !== $pageSnapshotId)
                || ($watermark !== null && $watermark !== $pageWatermark)
                || ($expectedTotal !== null && $expectedTotal !== $pageTotal)
            ) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
            $identity = $pageIdentity;
            $snapshotId = $pageSnapshotId;
            $watermark = $pageWatermark;
            $expectedTotal = $pageTotal;

            foreach ($payload['data'] as $item) {
                if (! is_array($item)) {
                    throw new IntegrationConfigurationException('contract_invalid');
                }
                $records[] = $this->mapRecord($item);
                if (count($records) > self::MAXIMUM_RECORDS) {
                    throw new IntegrationConfigurationException('response_too_large');
                }
            }

            $hasMore = $payload['has_more'];
            $cursor = $payload['next_cursor'];
            if ($hasMore && (! is_string($cursor) || $cursor === '')) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
        } while ($hasMore);

        if ($expectedTotal !== count($records)) {
            throw new IntegrationConfigurationException('contract_invalid');
        }

        return new EtatibSnapshot(
            isFullSnapshot: $mode === 'full',
            records: $records,
            evidence: new IntegrationSnapshotEvidence(
                reportedSourceIdentifier: (string) $identity,
                contractMarker: self::CONTRACT_VERSION,
                completenessMarker: $mode,
                pageCount: $pageCount,
                recordCount: count($records),
                processedBytes: $processedBytes,
            ),
            watermark: $watermark,
            snapshotId: $snapshotId,
        );
    }

    private function request(IntegrationRuntimeConfiguration $configuration): PendingRequest
    {
        $parts = parse_url($configuration->baseUrl);
        if (! is_array($parts) || ! isset($parts['host'])) {
            throw new IntegrationConfigurationException('endpoint_not_allowed');
        }
        $host = trim((string) $parts['host'], '[]');
        $port = (int) ($parts['port'] ?? 443);
        $addresses = ($this->resolver ?? $this->resolveHost(...))($host);
        if ($addresses === []) {
            throw new IntegrationConfigurationException('connection_failed');
        }

        $resolveEntries = [];
        foreach (array_unique($addresses) as $address) {
            if (filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) === false) {
                throw new IntegrationConfigurationException('endpoint_not_allowed');
            }
            $curlAddress = str_contains($address, ':') ? '['.$address.']' : $address;
            $resolveEntries[] = sprintf('%s:%d:%s', $host, $port, $curlAddress);
        }

        return Http::acceptJson()
            ->connectTimeout(5)
            ->timeout(min(15, $configuration->timeoutSeconds))
            ->retry(2, 250, throw: false)
            ->withOptions([
                'allow_redirects' => false,
                'proxy' => '',
                'stream' => true,
                'curl' => [CURLOPT_RESOLVE => $resolveEntries],
            ]);
    }

    /** @param array<string, mixed> $payload */
    private function assertEnvelope(array $payload, string $mode): void
    {
        $required = [
            'success', 'source_id', 'contract_version', 'mode', 'snapshot_id',
            'generated_at', 'watermark', 'next_cursor', 'has_more', 'total', 'data',
        ];
        if (array_diff($required, array_keys($payload)) !== []
            || $payload['success'] !== true
            || ! is_string($payload['source_id']) || $payload['source_id'] === ''
            || $payload['contract_version'] !== self::CONTRACT_VERSION
            || $payload['mode'] !== $mode
            || ! is_string($payload['snapshot_id']) || $payload['snapshot_id'] === ''
            || $this->isoTimestamp($payload['generated_at'] ?? null) === null
            || $this->isoTimestamp($payload['watermark'] ?? null) === null
            || ! is_bool($payload['has_more'])
            || ! is_int($payload['total']) || $payload['total'] < 0 || $payload['total'] > self::MAXIMUM_RECORDS
            || ! is_array($payload['data']) || ! array_is_list($payload['data'])
            || count($payload['data']) > self::PAGE_SIZE
            || ($payload['next_cursor'] !== null && ! is_string($payload['next_cursor']))
        ) {
            throw new IntegrationConfigurationException('contract_invalid');
        }
    }

    /** @param array<string, mixed> $item @return array<string, int|string|null> */
    private function mapRecord(array $item): array
    {
        $required = [
            'id_pelanggaran', 'siswa_nisn', 'siswa_nama', 'siswa_kelas',
            'pelanggaran', 'poin_pelanggaran', 'pencatat', 'kategori',
            'tanggal_pelanggaran', 'total_poin', 'updated_at', 'deleted_at',
        ];
        if (array_diff($required, array_keys($item)) !== []) {
            throw new IntegrationConfigurationException('contract_invalid');
        }
        foreach (['id_pelanggaran', 'siswa_nisn', 'siswa_nama', 'siswa_kelas', 'pelanggaran', 'pencatat', 'kategori'] as $field) {
            if ((! is_string($item[$field]) && ! ($field === 'siswa_nisn' && is_int($item[$field])))
                || trim((string) $item[$field]) === ''
            ) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
        }
        if (! is_int($item['poin_pelanggaran']) || $item['poin_pelanggaran'] < 0
            || ! is_int($item['total_poin']) || $item['total_poin'] < 0
        ) {
            throw new IntegrationConfigurationException('contract_invalid');
        }
        $occurredAt = $this->isoTimestamp($item['tanggal_pelanggaran']);
        $updatedAt = $this->isoTimestamp($item['updated_at']);
        $deletedAt = $item['deleted_at'] === null ? null : $this->isoTimestamp($item['deleted_at']);
        if ($occurredAt === null || $updatedAt === null || ($item['deleted_at'] !== null && $deletedAt === null)) {
            throw new IntegrationConfigurationException('contract_invalid');
        }

        $sourceNisn = trim((string) $item['siswa_nisn']);
        if (preg_match('/^\d{1,10}$/D', $sourceNisn) !== 1) {
            throw new IntegrationConfigurationException('contract_invalid');
        }

        return [
            'source_id' => trim((string) $item['id_pelanggaran']),
            'nisn' => str_pad($sourceNisn, 10, '0', STR_PAD_LEFT),
            'source_nisn' => $sourceNisn,
            'source_student_name' => trim((string) $item['siswa_nama']),
            'source_classroom_name' => trim((string) $item['siswa_kelas']),
            'occurred_at' => $occurredAt,
            'violation_type' => trim((string) $item['pelanggaran']),
            'category' => trim((string) $item['kategori']),
            'recorded_by_name' => trim((string) $item['pencatat']),
            'points' => $item['poin_pelanggaran'],
            'source_total_points' => $item['total_poin'],
            'source_status' => $deletedAt === null ? 'active' : 'deleted',
            'source_synced_at' => $updatedAt,
            'source_deleted_at' => $deletedAt,
        ];
    }

    private function isoTimestamp(mixed $value): ?string
    {
        if (! is_string($value) || ! str_ends_with($value, '+07:00')) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $value);

        return $date === false ? null : $date->format('Y-m-d H:i:s');
    }

    private function maskNisn(string $nisn): string
    {
        return substr($nisn, 0, 4).'****'.substr($nisn, -2);
    }

    private function normalizeName(string $name): string
    {
        return Str::upper((string) preg_replace('/\s+/u', ' ', trim($name)));
    }

    private function readLimitedBody(StreamInterface $stream, int $processedBytes): string
    {
        $body = '';
        while (! $stream->eof()) {
            $body .= $stream->read(8192);
            if (strlen($body) > self::MAXIMUM_PAGE_BYTES
                || $processedBytes + strlen($body) > self::MAXIMUM_OPERATION_BYTES
            ) {
                throw new IntegrationConfigurationException('response_too_large');
            }
        }

        return $body;
    }

    /** @return list<string> */
    private function resolveHost(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (! is_array($records)) {
            return [];
        }

        return collect($records)
            ->map(static fn (array $record): mixed => $record['ip'] ?? $record['ipv6'] ?? null)
            ->filter(static fn (mixed $address): bool => is_string($address))
            ->unique()
            ->values()
            ->all();
    }
}
