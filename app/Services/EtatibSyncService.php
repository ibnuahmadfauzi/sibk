<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Etatib\EtatibConnector;
use App\Integrations\Etatib\EtatibUnavailableException;
use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\IntegrationSetting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class EtatibSyncService
{
    public function __construct(
        private readonly EtatibConnector $connector,
        private readonly AuditService $auditService,
        private readonly IntegrationOperationLock $operationLock,
    ) {}

    public function synchronize(?User $actor = null): ExternalSyncRun
    {
        return $this->operationLock->run(
            IntegrationSetting::PROVIDER_ETATIB,
            fn (IntegrationOperationContext $context): ExternalSyncRun => $this->synchronizeLocked($context, $actor),
        );
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
            ]));

            $processed = $this->mutate($context, function () use ($snapshot, $run): int {
                $syncedAt = now();
                $processed = 0;
                $keptIds = [];
                $duplicateSourceIds = collect($snapshot->records)
                    ->groupBy('source_id')
                    ->filter(fn ($items): bool => $items->count() > 1)
                    ->keys();

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

                    $student = Student::query()->where('nisn', $item['nisn'])->first();
                    if ($student === null) {
                        $this->issue($run, $item, 'student_not_found', 'NISN e-Tatib belum ditemukan pada master Dapodik.');
                    } else {
                        ExternalSyncIssue::query()
                            ->where('entity_type', 'etatib_record')
                            ->where('source_identifier', $item['source_id'])
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
                            'student_id' => $student?->getKey(),
                            'occurred_at' => $item['occurred_at'],
                            'violation_type' => $item['violation_type'],
                            'category' => $item['category'],
                            'points' => $item['points'],
                            'source_status' => $item['source_status'] ?? null,
                            'is_active' => true,
                            'source_synced_at' => $item['source_synced_at'] ?? null,
                            'synced_at' => $syncedAt,
                        ],
                    );
                    $keptIds[] = $record->getKey();
                    $processed++;
                }

                if ($snapshot->isFullSnapshot) {
                    $missing = ExternalTatibRecord::query();
                    if ($keptIds !== []) {
                        $missing->whereNotIn('id', array_unique($keptIds));
                    }
                    $missing->update(['is_active' => false]);
                }

                return $processed;
            });

            $conflicts = $run->issues()->whereNull('resolved_at')->count();
            $this->complete(
                $context,
                $run,
                $conflicts > 0 ? ExternalSyncRun::STATUS_WARNING : ExternalSyncRun::STATUS_SUCCEEDED,
                $conflicts > 0
                    ? sprintf('Sinkronisasi e-Tatib selesai dengan %d data yang perlu diperiksa.', $conflicts)
                    : 'Sinkronisasi e-Tatib berhasil.',
                $actor,
                $processed,
                $conflicts,
            );
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
            $this->complete($context, $run, ExternalSyncRun::STATUS_FAILED, $summary, $actor);
        }

        return $run->refresh();
    }

    private function complete(
        IntegrationOperationContext $context,
        ExternalSyncRun $run,
        string $status,
        string $summary,
        ?User $actor,
        ?int $processed = null,
        ?int $conflicts = null,
    ): void {
        $this->mutate($context, function () use ($run, $status, $summary, $actor, $processed, $conflicts): void {
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
        });
    }

    /** @template TResult @param \Closure(): TResult $mutation @return TResult */
    private function mutate(IntegrationOperationContext $context, \Closure $mutation): mixed
    {
        return DB::transaction(function () use ($context, $mutation): mixed {
            $setting = IntegrationSetting::query()
                ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
                ->lockForUpdate()
                ->firstOrFail();
            $this->operationLock->assertCurrent($context, $setting);

            $result = $mutation();
            $this->operationLock->assertCurrent($context, $setting);

            return $result;
        });
    }

    /** @param array{source_id: string, nisn: string, occurred_at: string, violation_type: string, category: string, points: int, source_status?: string|null, source_synced_at?: string|null} $item */
    private function issue(ExternalSyncRun $run, array $item, string $code, string $summary): ExternalSyncIssue
    {
        return ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->getKey(),
            'entity_type' => 'etatib_record',
            'source_identifier' => $item['source_id'],
            'nisn' => $item['nisn'],
            'issue_code' => $code,
            'summary' => $summary,
        ]);
    }
}
