<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Etatib\EtatibSnapshot;
use App\Integrations\Etatib\EtatibUnavailableException;
use App\Integrations\IntegrationOperationContext;
use App\Models\AcademicYear;
use App\Models\EtatibDuplicateDecision;
use App\Models\EtatibIdentityMapping;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\IntegrationSetting;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use Psr\Http\Message\StreamInterface;
use Throwable;

final class SimpleEtatibApiService
{
    private const int MAX_RESPONSE_BYTES = 2 * 1024 * 1024;

    private const int MAX_RECORDS = 10000;

    private const int TIMEOUT_SECONDS = 15;

    /** @param (Closure(string): list<string>)|null $resolver */
    public function __construct(
        private readonly EtatibSyncService $syncService,
        private readonly ?Closure $resolver = null,
    ) {}

    /** @return array<string, mixed> */
    public function preview(string $url, User $actor): array
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $read = $this->verifiedRecords($url);
        $records = $this->uniqueRecords($read['records']);
        $groups = $this->duplicateGroups($read['records']);
        $approved = EtatibDuplicateDecision::query()->where('url_hash', hash('sha256', $url))
            ->where('is_active', true)->whereIn('group_key', array_column($groups, 'key'))
            ->get()->keyBy('group_key');
        foreach ($groups as &$group) {
            $decision = $approved->get($group['key']);
            $group['approved'] = $decision !== null;
            $group['decision_id'] = $decision?->getKey();
        }
        unset($group);
        $students = Student::query()
            ->whereIn('nisn', collect($records)->pluck('nisn')->unique())
            ->with(['classMemberships' => fn ($memberships) => $memberships
                ->with('classroom', 'academicYear')
                ->latestYearFirst()])
            ->get()
            ->keyBy('nisn');
        $manualMappings = EtatibIdentityMapping::query()
            ->active()
            ->get()
            ->keyBy(fn (EtatibIdentityMapping $mapping): string => $mapping->source_nisn.'|'.$mapping->source_name_hash);
        $matched = collect($records)->filter(function (array $record) use ($students, $manualMappings): bool {
            $mappingKey = $record['nisn'].'|'.hash('sha256', $this->normalizeText($record['source_student_name']));
            if ($manualMappings->has($mappingKey)) {
                return true;
            }

            $student = $students->get($record['nisn']);

            return $student instanceof Student
                && $this->normalizeText($student->name) === $this->normalizeText($record['source_student_name']);
        })->count();
        $identityConflicts = collect($records)
            ->filter(function (array $record) use ($students, $manualMappings): bool {
                $mappingKey = $record['nisn'].'|'.hash('sha256', $this->normalizeText($record['source_student_name']));
                if ($manualMappings->has($mappingKey)) {
                    return false;
                }

                $student = $students->get($record['nisn']);

                return ! $student instanceof Student
                    || $this->normalizeText($student->name) !== $this->normalizeText($record['source_student_name']);
            })
            ->groupBy(fn (array $record): string => $record['nisn'].'|'.$this->normalizeText($record['source_student_name']))
            ->map(function ($group) use ($students): array {
                $record = $group->first();
                $student = $students->get($record['nisn']);

                return [
                    'nisn' => $record['nisn'],
                    'name' => $record['source_student_name'],
                    'classroom' => $group->pluck('source_classroom_name')->unique()->implode(', '),
                    'kind' => $student instanceof Student ? 'name_mismatch' : 'nisn_not_found',
                    'reason' => $student instanceof Student
                        ? 'Nama pada master: '.$student->name.' · Kelas master: '.($student->classMemberships->first()?->classroom?->name ?? '-')
                        : 'NISN ini belum ada pada master murid.',
                ];
            })
            ->values()
            ->all();
        $conflictNames = collect($identityConflicts)
            ->pluck('name')
            ->map(fn (string $name): string => $this->normalizeText($name))
            ->unique()
            ->values();
        $nameCandidates = collect();
        foreach ($conflictNames->chunk(400) as $names) {
            $nameCandidates = $nameCandidates->concat(Student::query()
                ->whereIn(DB::raw('UPPER(TRIM(name))'), $names->all())
                ->with(['classMemberships' => fn ($memberships) => $memberships
                    ->with('classroom', 'academicYear')
                    ->latestYearFirst()])
                ->get());
        }
        $nameCandidates = $nameCandidates->groupBy(fn (Student $student): string => $this->normalizeText($student->name));
        $identityConflicts = array_map(function (array $conflict) use ($students, $nameCandidates): array {
            $sameNisn = $students->get($conflict['nisn']);
            $conflict['master'] = $sameNisn instanceof Student ? $this->studentChoice($sameNisn) : null;
            $conflict['suggestions'] = $nameCandidates
                ->get($this->normalizeText($conflict['name']), collect())
                ->reject(fn (Student $student): bool => $student->nisn === $conflict['nisn'])
                ->take(3)
                ->map(fn (Student $student): array => $this->studentChoice($student))
                ->values()
                ->all();

            return $conflict;
        }, $identityConflicts);

        return [
            'rows' => count($records),
            'received' => $read['received'],
            'undated' => $read['undated'],
            'students' => collect($records)->pluck('nisn')->unique()->count(),
            'matched' => $matched,
            'conflicts' => count($records) - $matched,
            'missing' => $this->missingActiveCount($records),
            'missing_students' => collect($identityConflicts)->where('kind', 'nisn_not_found')->count(),
            'name_mismatches' => collect($identityConflicts)->where('kind', 'name_mismatch')->count(),
            'active_year' => AcademicYear::query()->active()->value('name'),
            'identity_conflicts' => $identityConflicts,
            'duplicate_groups' => $groups,
            'fingerprint' => $this->fingerprint($read['records']),
        ];
    }

    /** @param array<string, mixed>|null $preview */
    public function synchronize(string $url, User $actor, ?array $preview = null, array $decisions = [], array $duplicateDecisions = []): ExternalSyncRun
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        IntegrationSetting::query()->firstOrCreate([
            'provider' => IntegrationSetting::PROVIDER_ETATIB,
        ]);

        $newApprovals = [];

        return $this->syncService->synchronizeUsing(
            function (IntegrationOperationContext $context) use ($url, $preview, $actor, $duplicateDecisions, &$newApprovals): EtatibSnapshot {
                return $this->snapshotForSync($url, $preview, $actor, $duplicateDecisions, $newApprovals);
            },
            $actor,
            $decisions,
            function () use ($url, $actor, &$newApprovals): void {
                foreach ($newApprovals as $group) {
                    $decision = EtatibDuplicateDecision::query()->firstOrCreate(
                        ['url_hash' => hash('sha256', $url), 'group_key' => $group['key']],
                        ['source_nisn' => $group['nisn'], 'source_name' => $group['name'], 'copy_count' => $group['count'],
                            'is_active' => true, 'approved_by' => $actor->getKey(), 'approved_at' => now()],
                    );
                    if (! $decision->wasRecentlyCreated) {
                        $decision->update(['is_active' => true, 'approved_by' => $actor->getKey(), 'approved_at' => now(),
                            'revoked_by' => null, 'revoked_at' => null]);
                    }
                    app(AuditService::class)->record('etatib.duplicate_approved', $decision,
                        'Baris e-Tatib identik disetujui sebagai satu kejadian.', $actor,
                        after: ['group_key' => $group['key'], 'copy_count' => $group['count']]);
                }
            },
        );
    }

    public function synchronizeStored(?User $actor = null): ExternalSyncRun
    {
        if ($actor !== null) {
            Gate::forUser($actor)->authorize('manageDataMaster');
        }

        IntegrationSetting::query()->firstOrCreate([
            'provider' => IntegrationSetting::PROVIDER_ETATIB,
        ]);

        return $this->syncService->synchronizeUsing(
            function (IntegrationOperationContext $context): EtatibSnapshot {
                $setting = IntegrationSetting::query()
                    ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
                    ->firstOrFail();

                if (! $setting->automatic_sync_enabled
                    || $setting->getRawOriginal('automatic_sync_url') === null
                ) {
                    throw new EtatibUnavailableException('Pembaruan otomatis e-Tatib belum aktif.');
                }

                try {
                    $url = $setting->automatic_sync_url;
                } catch (DecryptException) {
                    throw new EtatibUnavailableException(
                        'Link otomatis e-Tatib tidak dapat dibaca. Simpan ulang link melalui Data Master.',
                    );
                }

                if (! is_string($url) || $url === '') {
                    throw new EtatibUnavailableException('Link otomatis e-Tatib belum tersedia.');
                }

                $context->assertWithinDeadline();

                return $this->snapshotForSync($url);
            },
            $actor,
        );
    }

    /** @return array{id: int, nisn: string, name: string, classroom: string, academic_year: string|null} */
    private function studentChoice(Student $student): array
    {
        $membership = $student->classMemberships->first();

        return [
            'id' => (int) $student->getKey(),
            'nisn' => $student->nisn,
            'name' => $student->name,
            'classroom' => $membership?->classroom?->name ?? '-',
            'academic_year' => $membership?->academicYear?->name,
        ];
    }

    /** @param array<string, mixed>|null $preview */
    private function snapshotForSync(string $url, ?array $preview = null, ?User $actor = null, array $duplicateDecisions = [], array &$newApprovals = []): EtatibSnapshot
    {
        try {
            $read = $this->verifiedRecords($url);
            $records = $read['records'];
            if ($actor !== null && (
                ! is_array($preview)
                || ($preview['actor_id'] ?? null) !== $actor->getKey()
                || ($preview['url_hash'] ?? null) !== hash('sha256', $url)
                || ! is_int($preview['at'] ?? null)
                || $preview['at'] < now()->getTimestamp() - (int) config('session.lifetime') * 60
                || ! is_string($preview['fingerprint'] ?? null)
            )) {
                throw new EtatibUnavailableException('Sesi tinjauan e-Tatib berakhir. Tinjau data kembali sebelum sinkronisasi.');
            }
            if ($actor !== null && ! hash_equals($preview['fingerprint'], $this->fingerprint($records))) {
                throw new EtatibUnavailableException('Data API e-Tatib berubah setelah ditinjau. Tinjau data terbaru sebelum sinkronisasi.');
            }

            $groups = $this->duplicateGroups($records);
            $keys = array_column($groups, 'key');
            if (count($duplicateDecisions) !== count(array_unique($duplicateDecisions))
                || array_diff($duplicateDecisions, $keys) !== []) {
                throw new EtatibUnavailableException('Pilihan duplikasi tidak sesuai dengan pratinjau. Tinjau data kembali.');
            }
            $approved = EtatibDuplicateDecision::query()->where('url_hash', hash('sha256', $url))
                ->where('is_active', true)->whereIn('group_key', $keys)->pluck('group_key')->all();
            $unapproved = array_diff($keys, $approved, $duplicateDecisions);
            if ($unapproved !== []) {
                throw new EtatibUnavailableException('Ada baris e-Tatib identik yang belum diputuskan Admin IT. Tinjau data kembali.');
            }
            $newApprovals = array_values(array_filter($groups,
                static fn (array $group): bool => in_array($group['key'], $duplicateDecisions, true)
                    && ! in_array($group['key'], $approved, true)));
            $records = $this->uniqueRecords($records);
            foreach ($records as &$record) {
                unset($record['_source_hash'], $record['_row']);
            }
            unset($record);

            $missing = $this->missingActiveCount($records);
            if ($missing > 0) {
                throw new EtatibUnavailableException(sprintf(
                    '%d pelanggaran aktif sebelumnya tidak ada dalam respons API e-Tatib. Sinkronisasi ditahan; periksa kelengkapan data di sumber.',
                    $missing,
                ));
            }

            return new EtatibSnapshot(
                isFullSnapshot: true,
                records: $records,
                undatedCount: $read['undated'],
            );
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();

            throw new EtatibUnavailableException(
                is_string($message) ? $message : 'Sinkronisasi e-Tatib gagal. Data lama tetap dipertahankan.',
            );
        }
    }

    /** @param list<array<string, int|string|null>> $records */
    private function missingActiveCount(array $records): int
    {
        return ExternalTatibRecord::query()
            ->active()
            ->whereNotIn('source_identifier', array_column($records, 'source_id'))
            ->count();
    }

    /** @param list<array<string, int|string|null>> $records */
    private function fingerprint(array $records): string
    {
        $stable = array_map(static function (array $record): array {
            unset($record['source_synced_at'], $record['_row']);

            return $record;
        }, $records);
        usort($stable, static fn (array $left, array $right): int => strcmp($left['source_id'], $right['source_id']));

        return hash('sha256', json_encode($stable, JSON_THROW_ON_ERROR));
    }

    /** @param list<array<string, int|string|null>> $records @return list<array<string, int|string|null>> */
    private function uniqueRecords(array $records): array
    {
        return array_values(collect($records)->unique('source_id')->all());
    }

    /** @param list<array<string, int|string|null>> $records @return list<array<string, mixed>> */
    private function duplicateGroups(array $records): array
    {
        $groups = [];
        foreach ($records as $record) {
            $groups[$record['source_id']]['rows'][] = $record['_row'];
            $groups[$record['source_id']]['record'] = $record;
        }
        $result = [];
        foreach ($groups as $group) {
            $count = count($group['rows']);
            if ($count < 2) {
                continue;
            }
            $record = $group['record'];
            unset($record['source_id'], $record['source_synced_at']);
            $result[] = [
                'key' => hash('sha256', json_encode([$record['_source_hash'], $count], JSON_THROW_ON_ERROR)),
                'rows' => $group['rows'], 'count' => $count,
                'nisn' => $record['nisn'], 'name' => $record['source_student_name'],
                'classroom' => $record['source_classroom_name'],
            ];
        }

        return $result;
    }

    /** @return array{records: list<array<string, int|string|null>>, received: int, undated: int} */
    private function verifiedRecords(string $url): array
    {
        $first = $this->records($url);
        if (! collect($first)->contains(fn (array $record): bool => $this->hasRecentDate($record))) {
            return ['records' => $this->retainUndatedIds($first), 'received' => count($first), 'undated' => 0];
        }

        usleep(1_100_000);
        $second = $this->records($url);
        if ($this->fingerprint($first) === $this->fingerprint($second)) {
            return ['records' => $this->retainUndatedIds($second), 'received' => count($second), 'undated' => 0];
        }

        $firstById = array_column($first, null, 'source_id');
        $secondById = array_column($second, null, 'source_id');
        $removed = array_diff_key($firstById, $secondById);
        $added = array_diff_key($secondById, $firstById);
        if (count($first) !== count($second) || $removed === [] || count($removed) !== count($added)) {
            $this->fail('Data API e-Tatib berubah saat diperiksa. Tinjau data kembali.');
        }

        $firstCounts = array_count_values(array_map($this->withoutDateFingerprint(...), $first));
        $secondCounts = array_count_values(array_map($this->withoutDateFingerprint(...), $second));
        $removedBySignature = [];
        $addedBySignature = [];
        foreach ($removed as $record) {
            $signature = $this->withoutDateFingerprint($record);
            if (($firstCounts[$signature] ?? 0) !== 1 || ($secondCounts[$signature] ?? 0) !== 1 || ! $this->hasRecentDate($record)) {
                $this->fail('Tanggal pelanggaran yang berubah tidak dapat dibedakan dengan aman. Sinkronisasi ditahan.');
            }
            $removedBySignature[$signature] = $record;
        }
        foreach ($added as $record) {
            $signature = $this->withoutDateFingerprint($record);
            if (($firstCounts[$signature] ?? 0) !== 1 || ($secondCounts[$signature] ?? 0) !== 1 || ! $this->hasRecentDate($record)) {
                $this->fail('Tanggal pelanggaran yang berubah tidak dapat dibedakan dengan aman. Sinkronisasi ditahan.');
            }
            $addedBySignature[$signature] = $record;
        }
        ksort($removedBySignature);
        ksort($addedBySignature);
        if (array_keys($removedBySignature) !== array_keys($addedBySignature)) {
            $this->fail('Data API e-Tatib berubah selain tanggal pelanggaran. Tinjau data kembali.');
        }

        $stableFirst = array_values(array_filter($first, static fn (array $record): bool => isset($secondById[$record['source_id']])));
        $stableSecond = array_values(array_filter($second, static fn (array $record): bool => isset($firstById[$record['source_id']])));
        if ($this->fingerprint($stableFirst) !== $this->fingerprint($stableSecond)) {
            $this->fail('Data API e-Tatib berubah selain tanggal pelanggaran. Tinjau data kembali.');
        }

        $firstIdentityCounts = array_count_values(array_map($this->undatedSourceId(...), $first));
        $secondIdentityCounts = array_count_values(array_map($this->undatedSourceId(...), $second));
        foreach ($addedBySignature as $signature => $newRecord) {
            $oldRecord = $removedBySignature[$signature];
            $sourceId = $this->undatedSourceId($newRecord);
            if ($sourceId !== $this->undatedSourceId($oldRecord)
                || $firstIdentityCounts[$sourceId] !== 1
                || $secondIdentityCounts[$sourceId] !== 1
            ) {
                $this->fail('Beberapa pelanggaran tanpa tanggal tidak dapat dibedakan. Sinkronisasi ditahan.');
            }
            $oldRecord['source_id'] = $sourceId;
            $oldRecord['occurred_at'] = null;
            $oldRecord['_source_hash'] = $sourceId;
            $newRecord['source_id'] = $sourceId;
            $newRecord['occurred_at'] = null;
            $newRecord['_source_hash'] = $sourceId;
            $stableFirst[] = $oldRecord;
            $stableSecond[] = $newRecord;
        }
        if ($this->fingerprint($stableFirst) !== $this->fingerprint($stableSecond)) {
            $this->fail('Data API e-Tatib berubah saat diperiksa. Tinjau data kembali.');
        }

        return [
            'records' => $this->retainUndatedIds($stableSecond),
            'received' => count($second),
            'undated' => count($added),
        ];
    }

    /** @param array<string, int|string|null> $record */
    private function undatedSourceId(array $record): string
    {
        return 'simple-undated-'.hash('sha256', json_encode([
            $record['nisn'],
            $this->normalizeText($record['violation_type']),
            $record['points'],
            $this->normalizeText($record['recorded_by_name']),
        ], JSON_THROW_ON_ERROR));
    }

    /** @param list<array<string, int|string|null>> $records @return list<array<string, int|string|null>> */
    private function retainUndatedIds(array $records): array
    {
        $known = ExternalTatibRecord::query()
            ->whereNull('occurred_at')
            ->where('source_identifier', 'like', 'simple-undated-%')
            ->pluck('source_identifier')
            ->flip();
        if ($known->isEmpty()) {
            return $records;
        }

        $counts = array_count_values(array_map($this->undatedSourceId(...), $records));
        foreach ($records as &$record) {
            $sourceId = $this->undatedSourceId($record);
            if (! $known->has($sourceId)) {
                continue;
            }
            if ($counts[$sourceId] !== 1) {
                $this->fail('Beberapa pelanggaran tanpa tanggal tidak dapat dibedakan. Sinkronisasi ditahan.');
            }
            $record['source_id'] = $sourceId;
        }
        unset($record);

        return $records;
    }

    /** @param array<string, int|string|null> $record */
    private function hasRecentDate(array $record): bool
    {
        $timestamp = CarbonImmutable::parse($record['occurred_at'], 'Asia/Jakarta')->getTimestamp();

        return abs($timestamp - CarbonImmutable::now('Asia/Jakarta')->getTimestamp()) <= 300;
    }

    /** @param array<string, int|string|null> $record */
    private function withoutDateFingerprint(array $record): string
    {
        unset($record['source_id'], $record['occurred_at'], $record['source_synced_at'], $record['_source_hash'], $record['_row']);

        return hash('sha256', json_encode($record, JSON_THROW_ON_ERROR));
    }

    /** @return list<array<string, int|string|null>> */
    private function records(string $url): array
    {
        try {
            $this->assertPublicUrl($url);
            $payload = $this->fetchPayload($url);

            return $this->parse($payload);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (ConnectionException $exception) {
            throw ValidationException::withMessages([
                'api_url' => $this->connectionFailureMessage($exception),
            ]);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'api_url' => 'Respons API e-Tatib tidak dapat diproses oleh server SIBK.',
            ]);
        }
    }

    /** @return list<mixed> */
    private function fetchPayload(string $url): array
    {
        $response = Http::acceptJson()
            ->connectTimeout(5)
            ->timeout(self::TIMEOUT_SECONDS)
            ->withoutRedirecting()
            ->withOptions(['stream' => true])
            ->get($url);

        if (! $response->successful()) {
            $message = $response->redirect()
                ? 'API e-Tatib mengalihkan permintaan. Gunakan link tujuan akhir secara langsung.'
                : sprintf('API e-Tatib mengembalikan HTTP %d.', $response->status());
            $this->fail($message);
        }

        try {
            $payload = json_decode(
                $this->readLimitedBody($response->toPsrResponse()->getBody()),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            $this->fail('Respons API e-Tatib wajib berupa JSON yang valid.');
        }

        if (! is_array($payload) || ! array_is_list($payload)) {
            $this->fail('Respons API e-Tatib wajib berupa daftar JSON langsung.');
        }

        return $payload;
    }

    /** @param list<mixed> $payload @return list<array<string, int|string|null>> */
    private function parse(array $payload): array
    {
        if ($payload === []) {
            $this->fail('API e-Tatib tidak mengembalikan data pelanggaran.');
        }
        if (count($payload) > self::MAX_RECORDS) {
            $this->fail('Data API e-Tatib maksimal 10.000 baris.');
        }

        $records = [];
        $identifiers = [];
        foreach ($payload as $index => $item) {
            if (! is_array($item)) {
                $this->fail(sprintf('Data e-Tatib baris %d tidak valid.', $index + 1));
            }

            $required = [
                'siswa_nisn', 'siswa_nama', 'siswa_kelas', 'pelanggaran',
                'poin_pelanggaran', 'pencatat', 'kategori',
                'tanggal_pelanggaran', 'total_poin',
            ];
            if (array_diff($required, array_keys($item)) !== []) {
                $this->fail(sprintf('Data e-Tatib baris %d belum lengkap.', $index + 1));
            }

            $sourceNisn = trim((string) $item['siswa_nisn']);
            if (preg_match('/^\d{1,10}$/D', $sourceNisn) !== 1) {
                $this->fail(sprintf('NISN e-Tatib baris %d harus berupa angka maksimal 10 digit tanpa masking.', $index + 1));
            }
            $nisn = str_pad($sourceNisn, 10, '0', STR_PAD_LEFT);
            $occurredAt = CarbonImmutable::createFromFormat(
                '!d M Y H:i:s',
                trim((string) $item['tanggal_pelanggaran']),
                'Asia/Jakarta',
            );
            if ($occurredAt === false) {
                $this->fail(sprintf('Tanggal pelanggaran baris %d harus seperti 20 Jul 2026 10:17:00.', $index + 1));
            }

            $name = $this->requiredText($item['siswa_nama'], 'nama murid', $index);
            $classroom = $this->requiredText($item['siswa_kelas'], 'kelas', $index);
            $violation = $this->requiredText($item['pelanggaran'], 'pelanggaran', $index);
            $recorder = $this->requiredText($item['pencatat'], 'pencatat', $index);
            $category = $this->requiredText($item['kategori'], 'kategori', $index);
            $points = $this->nonNegativeInteger($item['poin_pelanggaran'], 'poin pelanggaran', $index);
            $totalPoints = $this->nonNegativeInteger($item['total_poin'], 'total poin', $index);
            $identifier = 'simple-'.hash('sha256', implode('|', [
                $nisn,
                $occurredAt->format('Y-m-d H:i:s'),
                $this->normalizeText($violation),
                (string) $points,
                $this->normalizeText($recorder),
            ]));
            $sourceFields = [];
            foreach ($required as $field) {
                $sourceFields[$field] = $item[$field];
            }
            $sourceHash = hash('sha256', json_encode($sourceFields, JSON_THROW_ON_ERROR));
            $record = [
                'source_id' => $identifier,
                'nisn' => $nisn,
                'source_nisn' => $sourceNisn,
                'source_student_name' => $name,
                'source_classroom_name' => $classroom,
                'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
                'violation_type' => $violation,
                'category' => $category,
                'recorded_by_name' => $recorder,
                'points' => $points,
                'source_total_points' => $totalPoints,
                'source_status' => 'active',
                'source_synced_at' => now()->format('Y-m-d H:i:s'),
                'source_deleted_at' => null,
                '_source_hash' => $sourceHash,
                '_row' => $index + 1,
            ];
            if (isset($identifiers[$identifier])) {
                $previous = $records[$identifiers[$identifier] - 1];
                if ($previous['_source_hash'] !== $sourceHash) {
                    $this->fail(sprintf('Data e-Tatib baris %d memakai penanda yang sama dengan baris %d, tetapi isinya berbeda. Sinkronisasi ditahan.', $index + 1, $identifiers[$identifier]));
                }
            } else {
                $identifiers[$identifier] = $index + 1;
            }
            $records[] = $record;
        }

        return $records;
    }

    private function requiredText(mixed $value, string $label, int $index): string
    {
        if (! is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > 200) {
            $this->fail(sprintf('%s e-Tatib baris %d tidak valid.', ucfirst($label), $index + 1));
        }

        return trim($value);
    }

    private function nonNegativeInteger(mixed $value, string $label, int $index): int
    {
        if ((! is_int($value) && (! is_string($value) || preg_match('/^\d+$/D', $value) !== 1))
            || (int) $value < 0
        ) {
            $this->fail(sprintf('%s e-Tatib baris %d tidak valid.', ucfirst($label), $index + 1));
        }

        return (int) $value;
    }

    private function readLimitedBody(StreamInterface $stream): string
    {
        $body = '';
        while (! $stream->eof()) {
            $body .= $stream->read(8192);
            if (strlen($body) > self::MAX_RESPONSE_BYTES) {
                $this->fail('Ukuran respons API e-Tatib maksimal 2 MiB.');
            }
        }

        return $body;
    }

    private function assertPublicUrl(string $url): void
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])
        ) {
            $this->fail('Link API e-Tatib harus berupa alamat HTTP atau HTTPS publik yang valid.');
        }

        $host = strtolower(trim($parts['host'], '[]'));
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            $this->fail('Link API e-Tatib tidak boleh mengarah ke localhost atau jaringan internal.');
        }
        $addresses = filter_var($host, FILTER_VALIDATE_IP) !== false
            ? [$host]
            : ($this->resolver !== null ? ($this->resolver)($host) : $this->resolveHost($host));
        if ($addresses === []) {
            $this->fail('Host API e-Tatib tidak dapat ditemukan.');
        }
        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $this->fail('Link API e-Tatib tidak boleh mengarah ke localhost atau jaringan internal.');
            }
        }
    }

    /** @return list<string> */
    private function resolveHost(string $host): array
    {
        if (preg_match('/^(?=.{1,253}$)(?![-.])[a-z0-9.-]+(?<![-.])$/D', $host) !== 1) {
            return [];
        }
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (! is_array($records)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (array $record): ?string => $record['ip'] ?? $record['ipv6'] ?? null,
            $records,
        ))));
    }

    private function connectionFailureMessage(ConnectionException $exception): string
    {
        $message = strtolower($exception->getMessage());

        return match (true) {
            preg_match('/curl error 6\b/', $message) === 1 => 'Nama host API e-Tatib tidak dapat ditemukan.',
            preg_match('/curl error 7\b/', $message) === 1 => 'Host API e-Tatib ditemukan, tetapi koneksinya ditolak.',
            preg_match('/curl error 28\b/', $message) === 1 => 'API e-Tatib tidak merespons dalam 15 detik.',
            default => 'Server SIBK tidak dapat membuka koneksi ke API e-Tatib.',
        };
    }

    private function normalizeText(string $value): string
    {
        return Str::upper((string) preg_replace('/\s+/u', ' ', trim($value)));
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['api_url' => $message]);
    }
}
