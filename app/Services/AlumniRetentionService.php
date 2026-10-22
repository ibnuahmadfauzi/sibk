<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\CaseFollowUp;
use App\Models\Consultation;
use App\Models\DapodikSyncPreviewItem;
use App\Models\EtatibDuplicateDecision;
use App\Models\EtatibIdentityMapping;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalTatibRecord;
use App\Models\IdentityReconciliation;
use App\Models\IntegrationSetting;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\StudentDeparture;
use App\Models\TemporaryStudent;
use App\Models\WithdrawalProgress;
use App\Models\WithdrawalProgressFollowUp;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class AlumniRetentionService
{
    public function __construct(private readonly AlumniRetentionRegistry $registry) {}

    /** @return array{eligible: int, deferred: int, deleted: int} */
    public function run(bool $execute = false): array
    {
        if ($execute) {
            $this->registry->assertReady();
        }

        $result = ['eligible' => 0, 'deferred' => 0, 'deleted' => 0];
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        // The extra day admits 29 February; the exact anniversary is checked below.
        $cutoff = $today->subYearNoOverflow()->addDay()->toDateString();

        StudentDeparture::query()
            ->where('departure_type', StudentDeparture::TYPE_GRADUATED)
            ->where('status', StudentDeparture::STATUS_OFFICIAL)
            ->whereNotNull('effective_date')
            ->whereDate('effective_date', '<=', $cutoff)
            ->lazyById(100)
            ->each(function (StudentDeparture $departure) use ($execute, $today, &$result): void {
                if (! $this->anniversaryDue($departure, $today)) {
                    return;
                }
                if ($execute) {
                    $state = DB::transaction(fn (): string => $this->purgeOne($departure->student_id, $today, true), 5);
                } else {
                    $state = $this->purgeOne($departure->student_id, $today, false);
                }

                $result[$state]++;
                if ($state === 'deleted') {
                    $result['eligible']++;
                }
            });

        return $result;
    }

    private function purgeOne(int $studentId, CarbonImmutable $today, bool $execute): string
    {
        if ($execute) {
            $this->registry->lockImports();
        }
        $students = Student::query();
        $student = ($execute ? $students->lockForUpdate() : $students)->find($studentId);
        if ($student === null) {
            return 'deferred';
        }

        $departures = StudentDeparture::query()->where('student_id', $studentId);
        $departure = ($execute ? $departures->lockForUpdate() : $departures)->first();
        if ($departure === null) {
            return 'deferred';
        }

        $memberships = DB::table('student_class_memberships')->where('student_id', $studentId);
        $membershipIds = ($execute ? $memberships->lockForUpdate() : $memberships)->pluck('id')->all();
        $yearIds = DB::table('student_class_memberships')->whereIn('id', $membershipIds)
            ->distinct()->pluck('academic_year_id')->all();
        $years = DB::table('academic_years')->whereIn('id', $yearIds)->orderBy('id');
        $currentYears = ($execute ? $years->lockForUpdate() : $years)
            ->get(['id', 'starts_on', 'is_active'])->all();
        if (count($currentYears) !== count($yearIds) || ! $this->eligible($departure, $today, $currentYears)) {
            return 'deferred';
        }
        $aliases = $this->validatedAliases($student);
        if ($aliases === null || array_filter($aliases, static function (string $alias) use ($student): bool {
            $canonical = ctype_digit($alias) && strlen($alias) <= 10
                ? str_pad($alias, 10, '0', STR_PAD_LEFT)
                : $alias;

            return $canonical !== trim($student->nisn);
        }) !== []) {
            return 'deferred';
        }
        $preview = $this->previewItems($student, $membershipIds);

        if ((clone $preview)->join('external_sync_runs as runs', 'runs.id', '=', 'dapodik_sync_preview_items.external_sync_run_id')
            ->where('runs.source', 'dapodik')
            ->where('runs.status', 'preview_ready')
            ->where('runs.preview_expires_at', '>', now())
            ->exists()) {
            return 'deferred';
        }

        $previewItems = (clone $preview)->get([
            'dapodik_sync_preview_items.id', 'entity_type', 'source_identifier', 'candidate_id', 'safe_fields',
        ]);
        foreach ($previewItems as $item) {
            $fields = json_decode((string) $item->safe_fields, true);
            if (! is_array($fields)) {
                return 'deferred';
            }
            if ($item->entity_type === 'student') {
                if ((isset($fields['nisn']) && $fields['nisn'] !== $student->nisn)
                    || ($item->candidate_id !== null && (int) $item->candidate_id !== $studentId)) {
                    return 'deferred';
                }
            } elseif ((isset($fields['student_source_id']) && $fields['student_source_id'] !== $student->dapodik_id)
                || ($item->candidate_id !== null && ! in_array((int) $item->candidate_id, $membershipIds, true))) {
                return 'deferred';
            }
        }

        $nisn = trim($student->nisn);
        $probe = IntegrationSetting::query()->where('provider', IntegrationSetting::PROVIDER_ETATIB)
            ->first()?->last_probe_summary;
        if ($probe !== null) {
            if (! is_array($probe)) {
                return 'deferred';
            }
            if (isset($probe['samples'])) {
                if (! is_array($probe['samples'])) {
                    return 'deferred';
                }
                $masked = substr($nisn, 0, 4).'****'.substr($nisn, -2);
                foreach ($probe['samples'] as $sample) {
                    if (! is_array($sample) || ! isset($sample['nisn']) || ! is_string($sample['nisn'])
                        || $sample['nisn'] === $masked) {
                        return 'deferred';
                    }
                }
            }
        }

        $temporaryByNisn = DB::table('temporary_students')->where('nisn', $nisn);
        if ((clone $temporaryByNisn)->where(function (Builder $query) use ($studentId): void {
            $query->whereNull('reconciled_student_id')->orWhere('reconciled_student_id', '!=', $studentId);
        })->exists()) {
            return 'deferred';
        }

        if (DB::table('external_sync_issues')->where('nisn', $nisn)
            ->whereNotNull('resolved_student_id')->where('resolved_student_id', '!=', $studentId)->exists()) {
            return 'deferred';
        }

        if (DB::table('etatib_identity_mappings')->where('source_nisn', $nisn)
            ->where('student_id', '!=', $studentId)->exists()
            || DB::table('identity_reconciliations')->where('source_nisn', $nisn)
                ->whereNotNull('student_id')->where('student_id', '!=', $studentId)->exists()
            || DB::table('external_tatib_records')->where(function (Builder $query) use ($nisn): void {
                $query->where('nisn', $nisn)->orWhere('source_nisn', $nisn);
            })->whereNotNull('student_id')->where('student_id', '!=', $studentId)->exists()) {
            return 'deferred';
        }

        $temporaryIds = DB::table('temporary_students')->where('reconciled_student_id', $studentId)->pluck('id')->all();
        $caseIds = DB::table('cases')->where('student_id', $studentId)
            ->when($temporaryIds !== [], fn (Builder $query): Builder => $query->orWhereIn('temporary_student_id', $temporaryIds))
            ->pluck('id')->all();
        $consultationIds = DB::table('consultations')->where('student_id', $studentId)
            ->when($temporaryIds !== [], fn (Builder $query): Builder => $query->orWhereIn('temporary_student_id', $temporaryIds))
            ->pluck('id')->all();
        $etatibIds = DB::table('external_tatib_records')->where('student_id', $studentId)
            ->orWhere(function (Builder $query) use ($nisn): void {
                $query->whereNull('student_id')->where(function (Builder $match) use ($nisn): void {
                    $match->where('nisn', $nisn)->orWhere('source_nisn', $nisn);
                });
            })->pluck('id')->all();

        if ($etatibIds !== [] && DB::table('case_etatib_links')->whereIn('external_tatib_record_id', $etatibIds)
            ->when($caseIds !== [], fn (Builder $query): Builder => $query->whereNotIn('case_id', $caseIds))
            ->exists()) {
            return 'deferred';
        }

        if (! $execute) {
            return 'eligible';
        }

        $issueIds = DB::table('external_sync_issues')->where('resolved_student_id', $studentId)
            ->orWhere('nisn', $nisn)->pluck('id')->all();
        $localIssueIds = DB::table('external_sync_issues as issues')
            ->join('external_sync_runs as runs', 'runs.id', '=', 'issues.external_sync_run_id')
            ->where('runs.source', 'dapodik')
            ->where('issues.issue_code', 'unmatched_local_record')
            ->where(function (Builder $query) use ($studentId, $membershipIds): void {
                $query->where(function (Builder $student) use ($studentId): void {
                    $student->where('issues.entity_type', 'student')
                        ->where('issues.source_identifier', 'local:'.$studentId);
                });
                if ($membershipIds !== []) {
                    $query->orWhere(function (Builder $memberships) use ($membershipIds): void {
                        $memberships->where('issues.entity_type', 'membership')
                            ->whereIn('issues.source_identifier', array_map(
                                static fn (int $id): string => 'local:'.$id,
                                $membershipIds,
                            ));
                    });
                }
            })->pluck('issues.id')->all();
        $issueIds = array_values(array_unique([...$issueIds, ...$localIssueIds]));
        $previewIds = $previewItems->pluck('id')->all();
        $mappingIds = DB::table('etatib_identity_mappings')->where('student_id', $studentId)
            ->orWhere('source_nisn', $nisn)->pluck('id')->all();
        $reconciliationIds = DB::table('identity_reconciliations')->where('student_id', $studentId)
            ->when($temporaryIds !== [], fn (Builder $query): Builder => $query->orWhereIn('temporary_student_id', $temporaryIds))
            ->orWhere('source_nisn', $nisn)->pluck('id')->all();
        $duplicateIds = DB::table('etatib_duplicate_decisions')->where('source_nisn', $nisn)->pluck('id')->all();
        $withdrawalIds = DB::table('withdrawal_progresses')->where('student_id', $studentId)->pluck('id')->all();
        $owned = [
            Student::class => [$studentId],
            StudentDeparture::class => [$departure->id],
            StudentClassMembership::class => $membershipIds,
            TemporaryStudent::class => $temporaryIds,
            IdentityReconciliation::class => $reconciliationIds,
            BkCase::class => $caseIds,
            CaseAssignment::class => $this->ids('case_assignments', 'case_id', $caseIds),
            CaseFollowUp::class => $this->ids('case_follow_ups', 'case_id', $caseIds),
            Consultation::class => $consultationIds,
            Achievement::class => DB::table('achievements')->where('student_id', $studentId)->pluck('id')->all(),
            WithdrawalProgress::class => $withdrawalIds,
            WithdrawalProgressFollowUp::class => $this->ids('withdrawal_progress_follow_ups', 'withdrawal_progress_id', $withdrawalIds),
            ExternalTatibRecord::class => $etatibIds,
            EtatibIdentityMapping::class => $mappingIds,
            ExternalSyncIssue::class => $issueIds,
            EtatibDuplicateDecision::class => $duplicateIds,
            DapodikSyncPreviewItem::class => $previewIds,
        ];

        $this->registry->record($student);

        foreach ($owned as $type => $ids) {
            foreach (array_chunk($ids, 500) as $batch) {
                DB::table('audit_logs')->where('auditable_type', (new $type)->getMorphClass())
                    ->whereIn('auditable_id', $batch)->delete();
            }
        }
        foreach ($previewIds as $previewId) {
            DB::table('audit_logs')->where('action', 'dapodik.preview_decided')
                ->where('after_values->item_id', $previewId)->delete();
        }

        $this->deleteIds('dapodik_sync_preview_items', $previewIds);
        $this->deleteIds('external_sync_issues', $issueIds);
        $this->deleteIds('identity_reconciliations', $reconciliationIds);
        $this->deleteIds('consultations', $consultationIds);
        DB::table('case_etatib_links')->whereIn('case_id', $caseIds)->delete();
        $this->deleteIds('case_follow_ups', $owned[CaseFollowUp::class]);
        DB::table('case_assignments')->whereIn('case_id', $caseIds)->delete();
        $this->deleteIds('cases', $caseIds);
        $this->deleteIds('temporary_students', $temporaryIds);
        DB::table('withdrawal_progress_follow_ups')->whereIn('withdrawal_progress_id', $withdrawalIds)->delete();
        DB::table('withdrawal_progresses')->where('student_id', $studentId)->delete();
        DB::table('achievements')->where('student_id', $studentId)->delete();
        $this->deleteIds('etatib_duplicate_decisions', $duplicateIds);
        $this->deleteIds('etatib_identity_mappings', $mappingIds);
        $this->deleteIds('external_tatib_records', $etatibIds);
        DB::table('student_class_memberships')->where('student_id', $studentId)->delete();
        DB::table('students')->where('id', $studentId)->delete();

        return 'deleted';
    }

    private function eligible(StudentDeparture $departure, CarbonImmutable $today, array $years): bool
    {
        if ($departure->status !== StudentDeparture::STATUS_OFFICIAL
            || $departure->departure_type !== StudentDeparture::TYPE_GRADUATED
            || ! $this->anniversaryDue($departure, $today)) {
            return false;
        }

        foreach ($years as $year) {
            if ($year->starts_on === null || $year->is_active
                || $year->starts_on >= $departure->effective_date->toDateString()) {
                return false;
            }
        }

        return true;
    }

    private function anniversaryDue(StudentDeparture $departure, CarbonImmutable $today): bool
    {
        return $departure->effective_date !== null
            && CarbonImmutable::parse($departure->effective_date->toDateString(), 'Asia/Jakarta')
                ->addYearNoOverflow()->startOfDay()->lessThanOrEqualTo($today);
    }

    /** @return list<string>|null */
    private function validatedAliases(Student $student): ?array
    {
        $studentId = (int) $student->id;
        $temporary = DB::table('temporary_students')->where('reconciled_student_id', $studentId)
            ->get(['id', 'nisn']);
        $temporaryIds = $temporary->pluck('id')->all();
        $records = DB::table('external_tatib_records')->where('student_id', $studentId)
            ->get(['nisn', 'source_nisn']);
        $mappings = DB::table('etatib_identity_mappings')->where('student_id', $studentId)
            ->get(['source_nisn', 'source_name']);
        $reconciliations = DB::table('identity_reconciliations')->where('student_id', $studentId)
            ->get(['source_nisn', 'temporary_student_id']);

        foreach ($reconciliations as $reconciliation) {
            if (! in_array((int) $reconciliation->temporary_student_id, $temporaryIds, true)) {
                return null;
            }
        }

        $aliases = [];
        foreach ([
            $student->nisn,
            ...$temporary->pluck('nisn')->all(),
            ...$records->pluck('nisn')->all(),
            ...$records->pluck('source_nisn')->all(),
            ...$mappings->pluck('source_nisn')->all(),
            ...$reconciliations->pluck('source_nisn')->all(),
        ] as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            $aliases[$value] = true;
            if (ctype_digit($value) && strlen($value) <= 10) {
                $aliases[str_pad($value, 10, '0', STR_PAD_LEFT)] = true;
            }
        }

        $aliases = array_map('strval', array_keys($aliases));
        $allowedNames = array_map(static fn (string $name): string => mb_strtolower(trim($name)), [
            $student->name, ...$mappings->pluck('source_name')->all(),
        ]);
        foreach (array_chunk($aliases, 200) as $batch) {
            if (DB::table('students')->whereIn('nisn', $batch)->where('id', '!=', $studentId)->exists()
                || DB::table('temporary_students')->whereIn('nisn', $batch)
                    ->where(function (Builder $query) use ($studentId): void {
                        $query->whereNull('reconciled_student_id')->orWhere('reconciled_student_id', '!=', $studentId);
                    })->exists()
                || DB::table('etatib_identity_mappings')->whereIn('source_nisn', $batch)
                    ->where('student_id', '!=', $studentId)->exists()
                || DB::table('external_tatib_records')->where(function (Builder $query) use ($batch): void {
                    $query->whereIn('nisn', $batch)->orWhereIn('source_nisn', $batch);
                })->where(function (Builder $query) use ($studentId): void {
                    $query->whereNull('student_id')->orWhere('student_id', '!=', $studentId);
                })->exists()
                || DB::table('external_sync_issues')->whereIn('nisn', $batch)
                    ->where(function (Builder $query) use ($studentId): void {
                        $query->whereNull('resolved_student_id')->orWhere('resolved_student_id', '!=', $studentId);
                    })->exists()
                || DB::table('identity_reconciliations')->whereIn('source_nisn', $batch)
                    ->where(function (Builder $query) use ($studentId, $temporaryIds): void {
                        $query->where('student_id', '!=', $studentId)
                            ->orWhere(function (Builder $unresolved) use ($temporaryIds): void {
                                $unresolved->whereNull('student_id');
                                if ($temporaryIds !== []) {
                                    $unresolved->whereNotIn('temporary_student_id', $temporaryIds);
                                }
                            });
                    })->exists()) {
                return null;
            }

            foreach (DB::table('etatib_duplicate_decisions')->whereIn('source_nisn', $batch)
                ->pluck('source_name') as $name) {
                if (! in_array(mb_strtolower(trim($name)), $allowedNames, true)) {
                    return null;
                }
            }
        }

        return $aliases;
    }

    /** @param list<int> $membershipIds */
    private function previewItems(Student $student, array $membershipIds): Builder
    {
        return DB::table('dapodik_sync_preview_items')->where(function (Builder $query) use ($student, $membershipIds): void {
            $query->where(function (Builder $students) use ($student): void {
                $students->where('entity_type', 'student')->where(function (Builder $match) use ($student): void {
                    $match->where('candidate_id', $student->id)->orWhere('safe_fields->nisn', $student->nisn);
                    if ($student->dapodik_id !== null) {
                        $match->orWhere('source_identifier', $student->dapodik_id);
                    }
                });
            });
            if ($membershipIds !== [] || $student->dapodik_id !== null) {
                $query->orWhere(function (Builder $memberships) use ($student, $membershipIds): void {
                    $memberships->where('entity_type', 'membership')->where(function (Builder $match) use ($student, $membershipIds): void {
                        if ($membershipIds !== []) {
                            $match->whereIn('candidate_id', $membershipIds);
                        }
                        if ($student->dapodik_id !== null) {
                            $match->orWhere('safe_fields->student_source_id', $student->dapodik_id);
                        }
                    });
                });
            }
        });
    }

    /** @param list<int> $ids */
    private function deleteIds(string $table, array $ids): void
    {
        foreach (array_chunk($ids, 500) as $batch) {
            DB::table($table)->whereIn('id', $batch)->delete();
        }
    }

    /** @param list<int> $parentIds
     *  @return list<int>
     */
    private function ids(string $table, string $parentColumn, array $parentIds): array
    {
        return $parentIds === [] ? [] : DB::table($table)->whereIn($parentColumn, $parentIds)->pluck('id')->all();
    }
}
