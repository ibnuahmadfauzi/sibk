<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Etatib\EtatibConnector;
use App\Integrations\Etatib\EtatibSnapshot;
use App\Integrations\Etatib\EtatibUnavailableException;
use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Models\EtatibIdentityMapping;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\IntegrationSetting;
use App\Models\Student;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class EtatibSyncService
{
    public function __construct(
        private readonly EtatibConnector $connector,
        private readonly AuditService $auditService,
        private readonly IntegrationOperationLock $operationLock,
        private readonly EtatibIdentityNormalizer $identityNormalizer,
    ) {}

    public function synchronize(?User $actor = null): ExternalSyncRun
    {
        return $this->operationLock->run(
            IntegrationSetting::PROVIDER_ETATIB,
            fn (IntegrationOperationContext $context): ExternalSyncRun => $this->synchronizeLocked($context, $actor),
        );
    }

    public function synchronizeSnapshot(EtatibSnapshot $snapshot, ?User $actor = null): ExternalSyncRun
    {
        return $this->synchronizeUsing(
            static fn (IntegrationOperationContext $context): EtatibSnapshot => $snapshot,
            $actor,
        );
    }

    /** @param Closure(IntegrationOperationContext): EtatibSnapshot $snapshotLoader */
    public function synchronizeUsing(Closure $snapshotLoader, ?User $actor = null): ExternalSyncRun
    {
        $connector = new class($snapshotLoader) implements EtatibConnector
        {
            /** @param Closure(IntegrationOperationContext): EtatibSnapshot $snapshotLoader */
            public function __construct(private readonly Closure $snapshotLoader) {}

            public function fetchSnapshot(IntegrationOperationContext $context): EtatibSnapshot
            {
                $context->assertWithinDeadline();

                return ($this->snapshotLoader)($context);
            }
        };

        return (new self(
            $connector,
            $this->auditService,
            $this->operationLock,
            $this->identityNormalizer,
        ))
            ->synchronize($actor);
    }

    public function reconcileStudentLinks(?User $actor = null): int
    {
        return $this->operationLock->run(
            IntegrationSetting::PROVIDER_ETATIB,
            fn (IntegrationOperationContext $context): int => $this->reconcileStudentLinksLocked($context, $actor),
        );
    }

    private function reconcileStudentLinksLocked(IntegrationOperationContext $context, ?User $actor): int
    {
        return DB::transaction(function () use ($context, $actor): int {
            $setting = IntegrationSetting::query()
                ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
                ->lockForUpdate()
                ->firstOrFail();
            $this->operationLock->assertCurrent($context, $setting);

            $linked = $this->reconcileUnlinkedRecords($actor);

            $setting->refresh();
            $this->operationLock->assertCurrent($context, $setting);

            return $linked;
        });
    }

    private function synchronizeLocked(IntegrationOperationContext $context, ?User $actor): ExternalSyncRun
    {
        $run = $this->mutate($context, fn (): ExternalSyncRun => ExternalSyncRun::query()->create([
            'source' => 'etatib',
            'status' => ExternalSyncRun::STATUS_RUNNING,
            'triggered_by' => $actor?->getKey(),
            'started_at' => now(),
        ]));

        try {
            $snapshot = $this->connector->fetchSnapshot($context);
            $this->mutate($context, fn () => $run->update([
                'is_full_snapshot' => $snapshot->isFullSnapshot,
                'received_count' => count($snapshot->records),
                'snapshot_evidence' => $snapshot->evidence === null ? null : [
                    'source_id' => $snapshot->evidence->reportedSourceIdentifier,
                    'contract_version' => $snapshot->evidence->contractMarker,
                    'mode' => $snapshot->evidence->completenessMarker,
                    'page_count' => $snapshot->evidence->pageCount,
                    'record_count' => $snapshot->evidence->recordCount,
                    'processed_bytes' => $snapshot->evidence->processedBytes,
                    'snapshot_id' => $snapshot->snapshotId,
                ],
            ]));

            $this->mutate($context, function () use ($snapshot, $run, $actor): void {
                $syncedAt = now();
                $processed = 0;
                $keptIds = [];
                $duplicateSourceIds = collect($snapshot->records)
                    ->groupBy('source_id')
                    ->filter(fn ($items): bool => $items->count() > 1)
                    ->keys();
                $manualMappings = EtatibIdentityMapping::query()
                    ->active()
                    ->with('student')
                    ->get()
                    ->keyBy(fn (EtatibIdentityMapping $mapping): string => $mapping->source_nisn.'|'.$mapping->source_name_hash);

                foreach ($snapshot->records as $item) {
                    $existing = ExternalTatibRecord::query()
                        ->where('source_identifier', $item['source_id'])
                        ->first();

                    if ($duplicateSourceIds->contains($item['source_id'])) {
                        if ($existing !== null) {
                            $keptIds[] = $existing->getKey();
                        }
                        $this->issue($run, $item, 'duplicate_source_identifier', 'Identitas record e-Tatib muncul lebih dari sekali pada payload.');

                        continue;
                    }

                    if ($existing !== null && $existing->nisn !== $item['nisn']) {
                        $keptIds[] = $existing->getKey();
                        $this->issue($run, $item, 'source_identity_mismatch', 'Identitas record e-Tatib menunjuk ke NISN yang berbeda.');

                        continue;
                    }

                    $sourceName = $item['source_student_name'] ?? null;
                    $manualMapping = is_string($sourceName)
                        ? $manualMappings->get($this->identityNormalizer->key($item['nisn'], $sourceName))
                        : null;
                    $student = $manualMapping?->student;

                    if ($student === null && $manualMapping === null) {
                        $student = Student::query()->where('nisn', $item['nisn'])->first();
                        if ($student === null) {
                            $this->issue($run, $item, 'student_not_found', 'NISN e-Tatib belum ditemukan pada master murid.');
                        } elseif (is_string($sourceName)
                            && ! $this->namesMatch($sourceName, $student->name)
                        ) {
                            $this->issue(
                                $run,
                                $item,
                                'student_name_mismatch',
                                'Nama e-Tatib berbeda dengan nama pada master murid. Record ditahan tanpa penautan.',
                            );
                            $student = null;
                        }
                    }

                    if ($student !== null) {
                        ExternalSyncIssue::query()
                            ->where('entity_type', 'etatib_record')
                            ->where('source_identifier', $item['source_id'])
                            ->whereIn('issue_code', ['student_not_found', 'student_name_mismatch'])
                            ->whereNull('resolved_at')
                            ->update([
                                'resolved_student_id' => $student->getKey(),
                                'resolved_by' => $run->triggered_by,
                                'resolved_at' => now(),
                            ]);
                    }

                    $record = ExternalTatibRecord::query()->updateOrCreate(
                        ['source_identifier' => $item['source_id']],
                        [
                            'nisn' => $item['nisn'],
                            'source_nisn' => $item['source_nisn'] ?? $item['nisn'],
                            'student_id' => $student?->getKey(),
                            'source_student_name' => $item['source_student_name'] ?? null,
                            'source_classroom_name' => $item['source_classroom_name'] ?? null,
                            'occurred_at' => $item['occurred_at'],
                            'violation_type' => $item['violation_type'],
                            'category' => $item['category'],
                            'recorded_by_name' => $item['recorded_by_name'] ?? null,
                            'points' => $item['points'],
                            'source_total_points' => $item['source_total_points'] ?? null,
                            'source_status' => $item['source_status'] ?? null,
                            'is_active' => ($item['source_deleted_at'] ?? null) === null,
                            'source_synced_at' => $item['source_synced_at'] ?? null,
                            'source_deleted_at' => $item['source_deleted_at'] ?? null,
                            'synced_at' => $syncedAt,
                        ],
                    );
                    $keptIds[] = $record->getKey();
                    $processed++;

                    if ($student !== null) {
                        $this->recordClassroomWarning($run, $item, $student);
                    }
                }

                if ($snapshot->isFullSnapshot) {
                    $missing = ExternalTatibRecord::query();
                    if ($keptIds !== []) {
                        $missing->whereNotIn('id', array_unique($keptIds));
                    }
                    $missing->update(['is_active' => false]);
                }

                $this->reconcileUnlinkedRecords($actor);

                $conflicts = $run->issues()->whereNull('resolved_at')->count();
                $this->finalizeRun(
                    $run,
                    $conflicts > 0 ? ExternalSyncRun::STATUS_WARNING : ExternalSyncRun::STATUS_SUCCEEDED,
                    $conflicts > 0
                        ? sprintf('Sinkronisasi e-Tatib selesai dengan %d data yang perlu diperiksa.', $conflicts)
                        : 'Sinkronisasi e-Tatib berhasil.',
                    $actor,
                    $processed,
                    $conflicts,
                );

                if ($snapshot->watermark !== null) {
                    $checkpoint = [
                        'sync_watermark' => $snapshot->watermark,
                        'last_successful_sync_at' => $syncedAt,
                    ];
                    if ($snapshot->isFullSnapshot) {
                        $checkpoint['last_full_synced_at'] = $syncedAt;
                    }

                    IntegrationSetting::query()
                        ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
                        ->update($checkpoint);
                }
            });
        } catch (Throwable $exception) {
            if (! $exception instanceof EtatibUnavailableException
                && ! $exception instanceof IntegrationConfigurationException
            ) {
                report(new RuntimeException('Unexpected e-Tatib integration failure.'));
            }

            $summary = $exception instanceof IntegrationConfigurationException
                ? $exception->getMessage()
                : ($exception instanceof EtatibUnavailableException
                    ? $exception->getMessage()
                    : 'Sinkronisasi e-Tatib gagal. Data lama tetap dipertahankan.');
            $this->settleFailure($context, $run, $summary, $actor);
        }

        return $run->refresh();
    }

    private function settleFailure(
        IntegrationOperationContext $context,
        ExternalSyncRun $run,
        string $summary,
        ?User $actor,
    ): void {
        try {
            $this->mutate($context, function () use ($run, $summary, $actor): void {
                $current = ExternalSyncRun::query()->lockForUpdate()->findOrFail($run->getKey());
                if ($current->source !== IntegrationSetting::PROVIDER_ETATIB
                    || $current->status !== ExternalSyncRun::STATUS_RUNNING
                ) {
                    return;
                }

                $this->finalizeRun($current, ExternalSyncRun::STATUS_FAILED, $summary, $actor);
            });
        } catch (Throwable) {
            $this->mutate($context, function () use ($run, $summary): void {
                $current = ExternalSyncRun::query()->lockForUpdate()->findOrFail($run->getKey());
                if ($current->source !== IntegrationSetting::PROVIDER_ETATIB
                    || $current->status !== ExternalSyncRun::STATUS_RUNNING
                ) {
                    return;
                }

                $current->update([
                    'status' => ExternalSyncRun::STATUS_FAILED,
                    'summary' => $summary,
                    'finished_at' => now(),
                ]);
            });
        }
    }

    private function finalizeRun(
        ExternalSyncRun $run,
        string $status,
        string $summary,
        ?User $actor,
        ?int $processed = null,
        ?int $conflicts = null,
    ): void {
        $run->update(array_filter([
            'status' => $status,
            'processed_count' => $processed,
            'conflict_count' => $conflicts,
            'summary' => $summary,
            'finished_at' => now(),
        ], static fn (mixed $value): bool => $value !== null));
        $run->refresh();
        $this->auditService->record(
            action: 'etatib.sync_completed',
            auditable: $run,
            summary: $summary,
            actor: $actor,
            after: [
                'status' => $run->status,
                'received_count' => $run->received_count,
                'processed_count' => $run->processed_count,
                'conflict_count' => $run->conflict_count,
            ],
        );
    }

    /** @template TResult @param \Closure(): TResult $mutation @return TResult */
    private function mutate(IntegrationOperationContext $context, Closure $mutation): mixed
    {
        return DB::transaction(function () use ($context, $mutation): mixed {
            $setting = IntegrationSetting::query()
                ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
                ->lockForUpdate()
                ->firstOrFail();
            $this->operationLock->assertCurrent($context, $setting);

            $result = $mutation();
            $setting->refresh();
            $this->operationLock->assertCurrent($context, $setting);

            return $result;
        });
    }

    /** @param array<string, mixed> $item */
    private function issue(ExternalSyncRun $run, array $item, string $code, string $summary): ExternalSyncIssue
    {
        return ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->getKey(),
            'entity_type' => 'etatib_record',
            'source_identifier' => $item['source_id'],
            'nisn' => $item['nisn'],
            'issue_code' => $code,
            'summary' => $summary,
            'input_name' => $item['source_student_name'] ?? null,
        ]);
    }

    private function namesMatch(string $sourceName, string $masterName): bool
    {
        return $this->identityNormalizer->name($sourceName) === $this->identityNormalizer->name($masterName);
    }

    private function reconcileUnlinkedRecords(?User $actor): int
    {
        $linked = 0;
        ExternalTatibRecord::query()
            ->whereNull('student_id')
            ->eachById(function (ExternalTatibRecord $record) use ($actor, &$linked): void {
                $mapping = $record->source_student_name === null
                    ? null
                    : EtatibIdentityMapping::query()
                        ->active()
                        ->where('source_nisn', $record->nisn)
                        ->where('source_name_hash', $this->identityNormalizer->nameHash($record->source_student_name))
                        ->with('student')
                        ->first();
                $student = $mapping?->student;

                if ($student === null) {
                    $students = Student::query()->where('nisn', $record->nisn)->get();
                    if ($students->count() !== 1) {
                        return;
                    }

                    $student = $students->firstOrFail();
                    if ($record->source_student_name !== null
                        && ! $this->namesMatch($record->source_student_name, $student->name)
                    ) {
                        return;
                    }
                }

                $record->update(['student_id' => $student->getKey()]);
                ExternalSyncIssue::query()
                    ->where('entity_type', 'etatib_record')
                    ->where('source_identifier', $record->source_identifier)
                    ->whereIn('issue_code', ['student_not_found', 'student_name_mismatch'])
                    ->whereNull('resolved_at')
                    ->update([
                        'resolved_student_id' => $student->getKey(),
                        'resolved_by' => $actor?->getKey(),
                        'resolved_at' => now(),
                    ]);
                $this->auditService->record(
                    action: 'etatib.student_relinked',
                    auditable: $record,
                    summary: 'Record e-Tatib ditautkan kembali melalui NISN exact tanpa write-back.',
                    actor: $actor,
                    after: ['student_id' => $student->getKey()],
                );
                $linked++;
            }, 1000);

        return $linked;
    }

    private function normalizeText(string $value): string
    {
        return $this->identityNormalizer->name($value);
    }

    /** @param array<string, mixed> $item */
    private function recordClassroomWarning(ExternalSyncRun $run, array $item, Student $student): void
    {
        $sourceClassroom = $item['source_classroom_name'] ?? null;
        if (! is_string($sourceClassroom) || $sourceClassroom === '') {
            return;
        }

        $occurredAt = substr((string) $item['occurred_at'], 0, 10);
        $classroomNames = $student->classMemberships()
            ->activeOn($occurredAt)
            ->with('classroom:id,name')
            ->get()
            ->pluck('classroom.name')
            ->filter()
            ->map(fn (string $name): string => $this->normalizeText($name));

        $unresolvedWarning = ExternalSyncIssue::query()
            ->where('entity_type', 'etatib_record')
            ->where('source_identifier', $item['source_id'])
            ->where('issue_code', 'student_classroom_mismatch')
            ->whereNull('resolved_at');

        if ($classroomNames->isNotEmpty()
            && ! $classroomNames->contains($this->normalizeText($sourceClassroom))
        ) {
            if (! $unresolvedWarning->exists()) {
                $this->issue(
                    $run,
                    $item,
                    'student_classroom_mismatch',
                    'Kelas pada e-Tatib berbeda dengan histori kelas murid; penautan NISN tetap dipertahankan.',
                );
            }

            return;
        }

        $unresolvedWarning->update([
            'resolved_student_id' => $student->getKey(),
            'resolved_by' => $run->triggered_by,
            'resolved_at' => now(),
        ]);
    }
}
