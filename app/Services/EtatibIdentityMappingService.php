<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Models\EtatibIdentityMapping;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\IntegrationSetting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class EtatibIdentityMappingService
{
    private const array IDENTITY_ISSUES = [
        'student_not_found',
        'student_name_mismatch',
    ];

    public function __construct(
        private readonly EtatibIdentityNormalizer $normalizer,
        private readonly IntegrationOperationLock $operationLock,
        private readonly AuditService $auditService,
    ) {}

    public function mapFromIssue(ExternalSyncIssue $issue, Student $student, User $actor): EtatibIdentityMapping
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        return $this->operationLock->run(
            IntegrationSetting::PROVIDER_ETATIB,
            fn (IntegrationOperationContext $context): EtatibIdentityMapping => $this->transaction(
                $context,
                fn (): EtatibIdentityMapping => $this->mapLockedIssue($issue->getKey(), $student, $actor),
            ),
        );
    }

    /** @return array{mode: string, proposals: list<array<string, mixed>>, skipped: list<array<string, int|string>>, fingerprint: string} */
    public function previewAutomatic(string $mode, User $actor): array
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        if ($mode !== 'nisn') {
            throw ValidationException::withMessages(['mode' => 'Pencocokan otomatis hanya berdasarkan NISN.']);
        }

        $issues = ExternalSyncIssue::query()
            ->where('entity_type', 'etatib_record')
            ->whereIn('issue_code', self::IDENTITY_ISSUES)
            ->whereNull('resolved_at')
            ->orderBy('id')
            ->get();
        $records = ExternalTatibRecord::query()
            ->whereIn('source_identifier', $issues->pluck('source_identifier')->filter())
            ->get()
            ->keyBy('source_identifier');
        $identityRecords = ExternalTatibRecord::query()
            ->whereIn('nisn', $records->pluck('nisn')->unique())
            ->get()
            ->groupBy(fn (ExternalTatibRecord $record): string => $this->normalizer->key($record->nisn, (string) $record->source_student_name));
        $students = Student::query()->get(['id', 'nisn', 'name']);
        $byNisn = $students->groupBy('nisn');
        $mapped = EtatibIdentityMapping::query()->active()->get()
            ->keyBy(fn (EtatibIdentityMapping $mapping): string => $mapping->source_nisn.'|'.$mapping->source_name_hash);
        $proposals = [];
        $skipped = [];
        $seen = [];

        foreach ($issues as $issue) {
            $record = $records->get($issue->source_identifier);
            if (! $record instanceof ExternalTatibRecord || trim((string) $record->source_student_name) === '') {
                continue;
            }
            $key = $this->normalizer->key($record->nisn, $record->source_student_name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $source = ['issue_id' => $issue->getKey(), 'source_nisn' => $record->nisn, 'source_name' => $record->source_student_name];
            if ($mapped->has($key) || $identityRecords->get($key, collect())->contains(fn (ExternalTatibRecord $item): bool => $item->student_id !== null)) {
                $skipped[] = $source + ['reason' => 'Sudah memiliki pencocokan.'];

                continue;
            }
            $candidates = $byNisn->get($record->nisn, collect());
            if ($candidates->count() !== 1) {
                $skipped[] = $source + ['reason' => $candidates->isEmpty() ? 'Tidak ditemukan di master murid.' : 'Lebih dari satu murid cocok.'];

                continue;
            }
            /** @var Student $student */
            $student = $candidates->first();
            $proposals[] = $source + [
                'student_id' => $student->getKey(),
                'student_nisn' => $student->nisn,
                'student_name' => $student->name,
            ];
        }

        return [
            'mode' => $mode,
            'proposals' => $proposals,
            'skipped' => $skipped,
            'fingerprint' => hash('sha256', json_encode([$mode, $proposals, $skipped], JSON_THROW_ON_ERROR)),
        ];
    }

    public function applyAutomatic(string $mode, string $fingerprint, User $actor): int
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        return $this->operationLock->run(
            IntegrationSetting::PROVIDER_ETATIB,
            fn (IntegrationOperationContext $context): int => $this->transaction($context, function () use ($mode, $fingerprint, $actor): int {
                $preview = $this->previewAutomatic($mode, $actor);
                if (! hash_equals($preview['fingerprint'], $fingerprint)) {
                    throw ValidationException::withMessages(['mode' => 'Data konflik atau murid berubah. Tinjau ulang hasil pencocokan.']);
                }
                if ($preview['proposals'] === []) {
                    throw ValidationException::withMessages(['mode' => 'Tidak ada identitas yang cocok secara tunggal.']);
                }

                $studentIds = array_column($preview['proposals'], 'student_id');
                $students = Student::query()->whereIn('id', $studentIds)->lockForUpdate()->get()->keyBy('id');
                foreach ($preview['proposals'] as $proposal) {
                    $student = $students->get($proposal['student_id']);
                    if (! $student instanceof Student
                        || $student->nisn !== $proposal['student_nisn']
                        || $student->name !== $proposal['student_name']) {
                        throw ValidationException::withMessages(['mode' => 'Data murid berubah. Tinjau ulang hasil pencocokan.']);
                    }
                    $this->mapLockedIssue($proposal['issue_id'], $student, $actor, $mode);
                }

                return count($preview['proposals']);
            }),
        );
    }

    private function mapLockedIssue(int $issueId, Student $student, User $actor, string $mode = 'manual'): EtatibIdentityMapping
    {
        $lockedIssue = ExternalSyncIssue::query()->lockForUpdate()->findOrFail($issueId);
        $record = $this->recordForIssue($lockedIssue);
        $identity = $this->identityFor($record);
        $canonical = Student::query()->where('nisn', $identity['nisn'])->first();
        if ($canonical !== null && $canonical->getKey() !== $student->getKey()) {
            throw ValidationException::withMessages(['student_id' => 'NISN sudah ditemukan di master murid. Pencocokan otomatis berdasarkan NISN berlaku.']);
        }

        $mapping = EtatibIdentityMapping::query()
            ->where('source_nisn', $identity['nisn'])
            ->where('source_name_hash', $identity['name_hash'])
            ->lockForUpdate()
            ->first();

        if ($mapping?->is_active) {
            if ($mapping->student_id === $student->getKey()) {
                return $mapping;
            }

            throw ValidationException::withMessages([
                'student_id' => 'Identitas sumber ini sudah memiliki pencocokan aktif. Gunakan aksi Ubah.',
            ]);
        }

        $records = ExternalTatibRecord::query()->where('nisn', $identity['nisn'])->lockForUpdate()->get()
            ->filter(fn (ExternalTatibRecord $item): bool => $item->source_student_name !== null
                && $this->normalizer->nameHash($item->source_student_name) === $identity['name_hash']);
        $this->ensureRelinkDoesNotChangeCase($records, $student);

        if ($mapping === null) {
            $mapping = EtatibIdentityMapping::query()->create([
                'source_nisn' => $identity['nisn'],
                'source_name' => $identity['name'],
                'source_name_hash' => $identity['name_hash'],
                'student_id' => $student->getKey(),
                'is_active' => true,
                'mapped_by' => $actor->getKey(),
                'mapped_at' => now(),
            ]);
        } else {
            $mapping->update([
                'source_name' => $identity['name'],
                'student_id' => $student->getKey(),
                'is_active' => true,
                'mapped_by' => $actor->getKey(),
                'mapped_at' => now(),
                'revoked_by' => null,
                'revoked_at' => null,
            ]);
        }

        $affected = $this->applyMapping($mapping, $student, $actor);
        $this->auditService->record(
            action: $mode === 'manual' ? 'etatib.identity_mapped' : 'etatib.identity_auto_mapped',
            auditable: $mapping,
            summary: $mode === 'manual'
                ? 'Identitas e-Tatib dicocokkan manual ke master murid.'
                : 'Identitas e-Tatib dicocokkan melalui pratinjau '.$mode.'.',
            actor: $actor,
            after: [
                'student_id' => $student->getKey(),
                'affected_records' => $affected,
                'method' => $mode,
            ],
        );

        return $mapping->refresh();
    }

    public function change(EtatibIdentityMapping $mapping, Student $student, User $actor): EtatibIdentityMapping
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        return $this->operationLock->run(
            IntegrationSetting::PROVIDER_ETATIB,
            fn (IntegrationOperationContext $context): EtatibIdentityMapping => $this->transaction(
                $context,
                function () use ($mapping, $student, $actor): EtatibIdentityMapping {
                    $locked = EtatibIdentityMapping::query()->lockForUpdate()->findOrFail($mapping->getKey());
                    if (! $locked->is_active) {
                        throw ValidationException::withMessages([
                            'student_id' => 'Pencocokan ini sudah dibatalkan.',
                        ]);
                    }
                    if ($locked->student_id === $student->getKey()) {
                        return $locked;
                    }

                    if (Student::query()->where('nisn', $locked->source_nisn)->exists()) {
                        throw ValidationException::withMessages(['student_id' => 'NISN sudah ditemukan di master murid. Pencocokan otomatis berdasarkan NISN berlaku.']);
                    }

                    $records = $this->recordsFor($locked);
                    $this->ensureNotLinkedToCase($records);
                    $previousStudentId = $locked->student_id;
                    $locked->update([
                        'student_id' => $student->getKey(),
                        'mapped_by' => $actor->getKey(),
                        'mapped_at' => now(),
                    ]);
                    $affected = $this->applyMapping($locked, $student, $actor, $records);

                    $this->auditService->record(
                        action: 'etatib.identity_mapping_changed',
                        auditable: $locked,
                        summary: 'Tujuan pencocokan manual e-Tatib diubah.',
                        actor: $actor,
                        before: ['student_id' => $previousStudentId],
                        after: [
                            'student_id' => $student->getKey(),
                            'affected_records' => $affected,
                        ],
                    );

                    return $locked->refresh();
                },
            ),
        );
    }

    public function revoke(EtatibIdentityMapping $mapping, User $actor): EtatibIdentityMapping
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        return $this->operationLock->run(
            IntegrationSetting::PROVIDER_ETATIB,
            fn (IntegrationOperationContext $context): EtatibIdentityMapping => $this->transaction(
                $context,
                function () use ($mapping, $actor): EtatibIdentityMapping {
                    $locked = EtatibIdentityMapping::query()->lockForUpdate()->findOrFail($mapping->getKey());
                    if (! $locked->is_active) {
                        return $locked;
                    }

                    $records = $this->recordsFor($locked);
                    $this->ensureNotLinkedToCase($records);
                    $previousStudentId = $locked->student_id;
                    $locked->update([
                        'is_active' => false,
                        'revoked_by' => $actor->getKey(),
                        'revoked_at' => now(),
                    ]);

                    foreach ($records as $record) {
                        $this->restoreStrictMatch($record, $actor);
                    }

                    $this->auditService->record(
                        action: 'etatib.identity_mapping_revoked',
                        auditable: $locked,
                        summary: 'Pencocokan manual e-Tatib dibatalkan dan aturan otomatis diterapkan kembali.',
                        actor: $actor,
                        before: ['student_id' => $previousStudentId, 'is_active' => true],
                        after: ['is_active' => false, 'affected_records' => $records->count()],
                    );

                    return $locked->refresh();
                },
            ),
        );
    }

    /** @template TResult @param callable(): TResult $callback @return TResult */
    private function transaction(IntegrationOperationContext $context, callable $callback): mixed
    {
        return DB::transaction(function () use ($context, $callback): mixed {
            $setting = IntegrationSetting::query()
                ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
                ->lockForUpdate()
                ->firstOrFail();
            $this->operationLock->assertCurrent($context, $setting);

            $result = $callback();
            $setting->refresh();
            $this->operationLock->assertCurrent($context, $setting);

            return $result;
        });
    }

    /** @return array{nisn: string, name: string, name_hash: string} */
    private function identityFor(ExternalTatibRecord $record): array
    {
        $sourceName = trim((string) $record->source_student_name);
        if ($sourceName === '') {
            throw ValidationException::withMessages([
                'student_id' => 'Nama sumber pada record e-Tatib tidak tersedia.',
            ]);
        }

        return [
            'nisn' => $record->nisn,
            'name' => $sourceName,
            'name_hash' => $this->normalizer->nameHash($sourceName),
        ];
    }

    private function recordForIssue(ExternalSyncIssue $issue): ExternalTatibRecord
    {
        if ($issue->entity_type !== 'etatib_record'
            || ! in_array($issue->issue_code, self::IDENTITY_ISSUES, true)
            || $issue->resolved_at !== null
            || $issue->source_identifier === null
        ) {
            throw ValidationException::withMessages([
                'student_id' => 'Konflik e-Tatib ini tidak lagi dapat dicocokkan.',
            ]);
        }

        return ExternalTatibRecord::query()
            ->where('source_identifier', $issue->source_identifier)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /** @return Collection<int, ExternalTatibRecord> */
    private function recordsFor(EtatibIdentityMapping $mapping): Collection
    {
        return ExternalTatibRecord::query()
            ->where('nisn', $mapping->source_nisn)
            ->lockForUpdate()
            ->get()
            ->filter(fn (ExternalTatibRecord $record): bool => $record->source_student_name !== null
                && $this->normalizer->nameHash($record->source_student_name) === $mapping->source_name_hash)
            ->values();
    }

    /** @param Collection<int, ExternalTatibRecord>|null $records */
    private function applyMapping(
        EtatibIdentityMapping $mapping,
        Student $student,
        User $actor,
        ?Collection $records = null,
    ): int {
        $records ??= $this->recordsFor($mapping);
        $this->ensureRelinkDoesNotChangeCase($records, $student);
        $sourceIdentifiers = $records->pluck('source_identifier');

        foreach ($records as $record) {
            $record->update(['student_id' => $student->getKey()]);
        }

        if ($sourceIdentifiers->isNotEmpty()) {
            ExternalSyncIssue::query()
                ->where('entity_type', 'etatib_record')
                ->whereIn('source_identifier', $sourceIdentifiers)
                ->whereIn('issue_code', self::IDENTITY_ISSUES)
                ->whereNull('resolved_at')
                ->update([
                    'resolved_student_id' => $student->getKey(),
                    'resolved_by' => $actor->getKey(),
                    'resolved_at' => now(),
                ]);
        }

        return $records->count();
    }

    /** @param Collection<int, ExternalTatibRecord> $records */
    private function ensureNotLinkedToCase(Collection $records): void
    {
        $linked = DB::table('case_etatib_links')
            ->whereIn('external_tatib_record_id', $records->modelKeys())
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if ($linked !== null) {
            throw ValidationException::withMessages([
                'student_id' => 'Pencocokan tidak dapat diubah karena pelanggaran sudah ditautkan ke kasus BK.',
            ]);
        }
    }

    /** @param Collection<int, ExternalTatibRecord> $records */
    private function ensureRelinkDoesNotChangeCase(Collection $records, Student $student): void
    {
        $changing = $records->filter(fn (ExternalTatibRecord $record): bool => $record->student_id !== $student->getKey());
        if ($changing->isNotEmpty()) {
            $this->ensureNotLinkedToCase($changing);
        }
    }

    private function restoreStrictMatch(ExternalTatibRecord $record, User $actor): void
    {
        $student = Student::query()->where('nisn', $record->nisn)->first();
        $matches = $student !== null;

        $record->update(['student_id' => $matches ? $student->getKey() : null]);

        if ($matches) {
            ExternalSyncIssue::query()
                ->where('entity_type', 'etatib_record')
                ->where('source_identifier', $record->source_identifier)
                ->whereIn('issue_code', self::IDENTITY_ISSUES)
                ->whereNull('resolved_at')
                ->update([
                    'resolved_student_id' => $student->getKey(),
                    'resolved_by' => $actor->getKey(),
                    'resolved_at' => now(),
                ]);

            return;
        }

        $issueCode = 'student_not_found';
        $issue = ExternalSyncIssue::query()
            ->where('entity_type', 'etatib_record')
            ->where('source_identifier', $record->source_identifier)
            ->where('issue_code', $issueCode)
            ->latest('id')
            ->first();

        if ($issue !== null) {
            $issue->update([
                'resolved_student_id' => null,
                'resolved_by' => null,
                'resolved_at' => null,
            ]);

            return;
        }

        $run = ExternalSyncRun::query()->where('source', 'etatib')->latest('id')->firstOrFail();
        ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->getKey(),
            'entity_type' => 'etatib_record',
            'source_identifier' => $record->source_identifier,
            'nisn' => $record->nisn,
            'input_name' => $record->source_student_name,
            'issue_code' => $issueCode,
            'summary' => 'NISN e-Tatib belum ditemukan pada master murid.',
        ]);
    }
}
