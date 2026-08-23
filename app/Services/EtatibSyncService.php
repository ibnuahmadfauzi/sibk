<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Etatib\EtatibConnector;
use App\Integrations\Etatib\EtatibUnavailableException;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class EtatibSyncService
{
    public function __construct(
        private readonly EtatibConnector $connector,
        private readonly AuditService $auditService,
    ) {}

    public function synchronize(?User $actor = null): ExternalSyncRun
    {
        $run = ExternalSyncRun::query()->create([
            'source' => 'etatib',
            'status' => ExternalSyncRun::STATUS_RUNNING,
            'triggered_by' => $actor?->getKey(),
            'started_at' => now(),
        ]);

        try {
            $snapshot = $this->connector->fetchSnapshot();
            $run->update([
                'is_full_snapshot' => $snapshot->isFullSnapshot,
                'received_count' => count($snapshot->records),
            ]);

            $processed = DB::transaction(function () use ($snapshot, $run): int {
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
            $run->update([
                'status' => $conflicts > 0 ? ExternalSyncRun::STATUS_WARNING : ExternalSyncRun::STATUS_SUCCEEDED,
                'processed_count' => $processed,
                'conflict_count' => $conflicts,
                'summary' => $conflicts > 0
                    ? sprintf('Sinkronisasi e-Tatib selesai dengan %d data yang perlu diperiksa.', $conflicts)
                    : 'Sinkronisasi e-Tatib berhasil.',
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            if (! $exception instanceof EtatibUnavailableException) {
                report($exception);
            }

            $run->update([
                'status' => ExternalSyncRun::STATUS_FAILED,
                'summary' => $exception instanceof EtatibUnavailableException
                    ? $exception->getMessage()
                    : 'Sinkronisasi e-Tatib gagal. Data lama tetap dipertahankan.',
                'finished_at' => now(),
            ]);
        }

        $run->refresh();
        $this->auditService->record(
            action: 'etatib.sync_completed',
            auditable: $run,
            summary: $run->summary ?? 'Sinkronisasi e-Tatib selesai.',
            actor: $actor,
            after: [
                'status' => $run->status,
                'received_count' => $run->received_count,
                'processed_count' => $run->processed_count,
                'conflict_count' => $run->conflict_count,
            ],
        );

        return $run;
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
