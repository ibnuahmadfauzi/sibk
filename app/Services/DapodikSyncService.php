<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Dapodik\ConfiguredDapodikConnector;
use App\Integrations\Dapodik\DapodikConnector;
use App\Integrations\Dapodik\DapodikSnapshot;
use App\Integrations\Dapodik\DapodikUnavailableException;
use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DapodikSyncService
{
    public function __construct(
        private readonly DapodikConnector $connector,
        private readonly StudentIdentityService $studentIdentityService,
        private readonly AuditService $auditService,
        private readonly IntegrationOperationLock $operationLock,
    ) {}

    public function synchronize(?User $actor = null): ExternalSyncRun
    {
        return $this->operationLock->run(
            IntegrationSetting::PROVIDER_DAPODIK,
            fn (IntegrationOperationContext $context): ExternalSyncRun => $this->synchronizeLocked($context, $actor),
        );
    }

    private function synchronizeLocked(IntegrationOperationContext $context, ?User $actor): ExternalSyncRun
    {
        $run = $this->mutate($context, fn (): ExternalSyncRun => ExternalSyncRun::query()->create([
            'source' => 'dapodik',
            'status' => ExternalSyncRun::STATUS_RUNNING,
            'triggered_by' => $actor?->getKey(),
            'started_at' => now(),
        ]));

        try {
            if ($this->connector instanceof ConfiguredDapodikConnector) {
                throw new IntegrationConfigurationException('preview_unavailable');
            }
            $snapshot = $this->connector->fetchSnapshot($context);
            $this->mutate($context, fn () => $run->update([
                'is_full_snapshot' => $snapshot->isFullSnapshot,
                'received_count' => $snapshot->recordCount(),
            ]));
            $processed = $this->mutate($context, function () use ($snapshot, $run, $actor): int {
                $processed = $this->importSnapshot($snapshot, $run);
                $this->studentIdentityService->reconcilePending($actor);

                return $processed;
            });
            $conflicts = $run->issues()->count();
            $this->complete(
                $context,
                $run,
                $conflicts > 0 ? ExternalSyncRun::STATUS_WARNING : ExternalSyncRun::STATUS_SUCCEEDED,
                $conflicts > 0
                    ? sprintf('Sinkronisasi selesai dengan %d data yang perlu diperiksa.', $conflicts)
                    : 'Sinkronisasi Dapodik berhasil.',
                $actor,
                $processed,
                $conflicts,
            );
        } catch (Throwable $exception) {
            if (! $exception instanceof DapodikUnavailableException
                && ! $exception instanceof IntegrationConfigurationException
            ) {
                report(new RuntimeException('Unexpected Dapodik integration failure.'));
            }
            $summary = $exception instanceof IntegrationConfigurationException
                ? $exception->getMessage()
                : ($exception instanceof DapodikUnavailableException
                    ? $exception->getMessage()
                    : 'Sinkronisasi Dapodik gagal. Data master lama tetap dipertahankan.');
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
                action: 'dapodik.sync_completed',
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
                ->where('provider', IntegrationSetting::PROVIDER_DAPODIK)
                ->lockForUpdate()
                ->firstOrFail();
            $this->operationLock->assertCurrent($context, $setting);

            $result = $mutation();
            $this->operationLock->assertCurrent($context, $setting);

            return $result;
        });
    }

    private function importSnapshot(DapodikSnapshot $snapshot, ExternalSyncRun $run): int
    {
        $syncedAt = now();
        $processed = 0;
        $yearIds = [];
        $classroomIds = [];
        $studentIds = [];
        $membershipIds = [];
        $protectedClassroomIds = [];
        $protectedStudentIds = [];
        $protectedMembershipIds = [];

        foreach ($snapshot->academicYears as $item) {
            $year = AcademicYear::query()->updateOrCreate(
                ['dapodik_id' => $item['source_id']],
                [
                    'name' => $item['name'],
                    'starts_on' => $item['starts_on'] ?? null,
                    'ends_on' => $item['ends_on'] ?? null,
                    'is_active' => $item['is_active'] ?? true,
                    'synced_at' => $syncedAt,
                ],
            );
            $yearIds[$item['source_id']] = $year->getKey();
            $processed++;
        }

        foreach ($snapshot->classrooms as $item) {
            $yearId = $yearIds[$item['academic_year_source_id']] ?? null;

            if ($yearId === null) {
                $existing = Classroom::query()->where('dapodik_id', $item['source_id'])->first();
                if ($existing !== null) {
                    $protectedClassroomIds[] = $existing->getKey();
                }
                $this->issue($run, 'classroom', $item['source_id'], 'missing_academic_year', 'Tahun ajaran sumber untuk kelas tidak ditemukan.');

                continue;
            }

            $classroom = Classroom::query()->updateOrCreate(
                ['dapodik_id' => $item['source_id']],
                [
                    'academic_year_id' => $yearId,
                    'name' => $item['name'],
                    'grade_level' => $item['grade_level'] ?? null,
                    'major' => $item['major'] ?? null,
                    'is_active' => $item['is_active'] ?? true,
                    'synced_at' => $syncedAt,
                ],
            );
            $classroomIds[$item['source_id']] = $classroom->getKey();
            $processed++;
        }

        $duplicateNisns = collect($snapshot->students)
            ->groupBy('nisn')
            ->filter(fn ($items): bool => $items->count() > 1)
            ->keys();

        foreach ($snapshot->students as $item) {
            if ($duplicateNisns->contains($item['nisn'])) {
                $existing = Student::query()
                    ->where('dapodik_id', $item['source_id'])
                    ->orWhere('nisn', $item['nisn'])
                    ->first();
                if ($existing !== null) {
                    $protectedStudentIds[] = $existing->getKey();
                }
                $this->issue(
                    $run,
                    'student',
                    $item['source_id'],
                    'duplicate_nisn',
                    'NISN muncul lebih dari sekali pada payload Dapodik.',
                    $item['nisn'],
                    $item['name'],
                );

                continue;
            }

            $bySource = Student::query()->where('dapodik_id', $item['source_id'])->first();
            $byNisn = Student::query()->where('nisn', $item['nisn'])->first();

            if ($bySource !== null && $bySource->nisn !== $item['nisn']) {
                $protectedStudentIds[] = $bySource->getKey();
                $this->issue($run, 'student', $item['source_id'], 'source_identity_mismatch', 'Identitas sumber menunjuk ke NISN yang berbeda.', $item['nisn'], $item['name']);

                continue;
            }

            if ($byNisn !== null && $byNisn->dapodik_id !== null && $byNisn->dapodik_id !== $item['source_id']) {
                $protectedStudentIds[] = $byNisn->getKey();
                $this->issue($run, 'student', $item['source_id'], 'nisn_source_conflict', 'NISN telah terhubung dengan identitas sumber lain.', $item['nisn'], $item['name']);

                continue;
            }

            $student = $bySource ?? $byNisn ?? new Student;
            $student->fill([
                'dapodik_id' => $item['source_id'],
                'nisn' => $item['nisn'],
                'name' => $item['name'],
                'is_active' => $item['is_active'] ?? true,
                'synced_at' => $syncedAt,
            ])->save();
            $studentIds[$item['source_id']] = $student->getKey();
            $processed++;
        }

        foreach ($snapshot->memberships as $item) {
            $studentId = $studentIds[$item['student_source_id']] ?? null;
            $classroomId = $classroomIds[$item['classroom_source_id']] ?? null;
            $yearId = $yearIds[$item['academic_year_source_id']] ?? null;

            if ($studentId === null || $classroomId === null || $yearId === null) {
                $existing = StudentClassMembership::query()->where('dapodik_id', $item['source_id'])->first();
                if ($existing !== null) {
                    $protectedMembershipIds[] = $existing->getKey();
                }
                $this->issue($run, 'membership', $item['source_id'], 'missing_relation', 'Relasi murid, kelas, atau tahun ajaran tidak tersedia.');

                continue;
            }

            $membership = StudentClassMembership::query()->updateOrCreate(
                ['dapodik_id' => $item['source_id']],
                [
                    'student_id' => $studentId,
                    'classroom_id' => $classroomId,
                    'academic_year_id' => $yearId,
                    'effective_from' => $item['effective_from'],
                    'effective_until' => $item['effective_until'] ?? null,
                    'is_active' => $item['is_active'] ?? true,
                    'synced_at' => $syncedAt,
                ],
            );
            $membershipIds[] = $membership->getKey();
            $processed++;
        }

        if ($snapshot->isFullSnapshot) {
            $this->deactivateMissing(AcademicYear::query(), array_values($yearIds));
            $this->deactivateMissing(Classroom::query(), [...array_values($classroomIds), ...$protectedClassroomIds]);
            $this->deactivateMissing(Student::query(), [...array_values($studentIds), ...$protectedStudentIds]);
            $this->deactivateMissing(StudentClassMembership::query(), [...$membershipIds, ...$protectedMembershipIds]);
        }

        return $processed;
    }

    /** @param \Illuminate\Database\Eloquent\Builder<*> $query @param list<int> $ids */
    private function deactivateMissing($query, array $ids): void
    {
        $query->whereNotNull('dapodik_id');

        if ($ids !== []) {
            $query->whereNotIn('id', $ids);
        }

        $query->update(['is_active' => false]);
    }

    private function issue(
        ExternalSyncRun $run,
        string $entityType,
        ?string $sourceIdentifier,
        string $code,
        string $summary,
        ?string $nisn = null,
        ?string $inputName = null,
    ): ExternalSyncIssue {
        return ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->getKey(),
            'entity_type' => $entityType,
            'source_identifier' => $sourceIdentifier,
            'nisn' => $nisn,
            'input_name' => $inputName,
            'issue_code' => $code,
            'summary' => $summary,
        ]);
    }
}
