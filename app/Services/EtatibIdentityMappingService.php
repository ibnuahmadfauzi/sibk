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
                function () use ($issue, $student, $actor): EtatibIdentityMapping {
                    $lockedIssue = ExternalSyncIssue::query()->lockForUpdate()->findOrFail($issue->getKey());
                    $record = $this->recordForIssue($lockedIssue);
                    $identity = $this->identityFor($record);

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
                        action: 'etatib.identity_mapped',
                        auditable: $mapping,
                        summary: 'Identitas e-Tatib dicocokkan manual ke master murid.',
                        actor: $actor,
                        after: [
                            'student_id' => $student->getKey(),
                            'affected_records' => $affected,
                        ],
                    );

                    return $mapping->refresh();
                },
            ),
        );
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
            ->exists();

        if ($linked) {
            throw ValidationException::withMessages([
                'student_id' => 'Pencocokan tidak dapat diubah karena pelanggaran sudah ditautkan ke kasus BK.',
            ]);
        }
    }

    private function restoreStrictMatch(ExternalTatibRecord $record, User $actor): void
    {
        $student = Student::query()->where('nisn', $record->nisn)->first();
        $matches = $student !== null
            && $record->source_student_name !== null
            && $this->normalizer->name($record->source_student_name) === $this->normalizer->name($student->name);

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

        $issueCode = $student === null ? 'student_not_found' : 'student_name_mismatch';
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
            'summary' => $student === null
                ? 'NISN e-Tatib belum ditemukan pada master murid.'
                : 'Nama e-Tatib berbeda dengan nama pada master murid. Record ditahan tanpa penautan.',
        ]);
    }
}
