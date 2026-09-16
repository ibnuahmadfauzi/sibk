<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\OperationalReportRecap;
use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ExternalTatibRecord;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TemporaryStudent;
use App\Models\User;
use App\Policies\ReportPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

final class OperationalReportRecapService implements OperationalReportRecap
{
    public const string TAB_VIOLATIONS = 'pelanggaran';

    public const string TAB_SERVICES = 'layanan';

    public const string TAB_ACHIEVEMENTS = 'prestasi';

    public function __construct(private readonly ReportPolicy $policy) {}

    /** @return list<string> */
    public static function tabs(): array
    {
        return [self::TAB_VIOLATIONS, self::TAB_SERVICES, self::TAB_ACHIEVEMENTS];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function build(User $actor, array $filters): array
    {
        abort_unless($this->policy->viewTab($actor, (string) $filters['tab']), 403);

        $report = match ($filters['tab']) {
            self::TAB_VIOLATIONS => $this->buildViolations($actor, $filters),
            self::TAB_SERVICES => $this->buildServices($actor, $filters),
            self::TAB_ACHIEVEMENTS => $this->buildAchievements($actor, $filters),
        };

        return [
            ...$report,
            'tabs' => [
                ['id' => self::TAB_VIOLATIONS, 'label' => 'Pelanggaran & Poin'],
                ['id' => self::TAB_SERVICES, 'label' => 'Layanan BK'],
                ['id' => self::TAB_ACHIEVEMENTS, 'label' => 'Prestasi'],
            ],
            'filter_options' => $this->filterOptions($actor, $report),
            'academic_year' => $report['academic_year'] === null ? null : [
                'id' => $report['academic_year']->id,
                'name' => $report['academic_year']->name,
            ],
        ];
    }

    /** @param array<string, mixed> $filters @return array{id: string, columns: list<string>, rows: iterable<int, array<string, mixed>>} */
    public function exportRows(User $actor, array $filters): array
    {
        abort_unless($this->policy->exportTab($actor, (string) $filters['tab']), 403);

        return match ($filters['tab']) {
            self::TAB_VIOLATIONS => $this->exportViolations($actor, $filters),
            self::TAB_SERVICES => $this->exportServices($actor, $filters),
            self::TAB_ACHIEVEMENTS => $this->exportAchievements($actor, $filters),
        };
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function buildViolations(User $actor, array $filters): array
    {
        [$year, $start, $end] = $this->period($filters);
        $filters = [
            ...$filters,
            'academic_year_id' => $year?->getKey(),
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
        ];
        $aggregates = $this->violationAggregateQuery($actor, $filters, $year, $start, $end);
        $summary = DB::query()->fromSub(clone $aggregates, 'violation_summary')
            ->selectRaw('COUNT(*) AS identity_count, COALESCE(SUM(violation_count), 0) AS violation_count, COALESCE(SUM(total_points), 0) AS total_points')
            ->first();
        $rows = (clone $aggregates)
            ->orderByDesc('latest_at')
            ->orderBy('identity_type')
            ->orderBy('identity_value')
            ->paginate(20)
            ->withQueryString();
        $rows->setCollection($this->violationRows($rows->getCollection(), $year));

        return [
            'id' => self::TAB_VIOLATIONS,
            'tab' => self::TAB_VIOLATIONS,
            'title' => 'Pelanggaran & Poin',
            'columns' => ['Murid', 'NISN Tersamarkan', 'Kelas', 'Jumlah pelanggaran', 'Total poin', 'Pelanggaran terakhir'],
            'stats' => [
                'student_count' => (int) ($summary->identity_count ?? 0),
                'violation_count' => (int) ($summary->violation_count ?? 0),
                'total_points' => (int) ($summary->total_points ?? 0),
            ],
            'rows' => $rows,
            'filters' => $filters,
            'academic_year' => $year,
            'period_start' => $start,
            'period_end' => $end,
            'generated_by' => $actor->name,
            'generated_at' => now(),
        ];
    }

    /** @param array<string, mixed> $filters @return array{id: string, columns: list<string>, rows: iterable<int, array<string, mixed>>} */
    private function exportViolations(User $actor, array $filters): array
    {
        [$year, $start, $end] = $this->period($filters);
        $filters = [
            ...$filters,
            'academic_year_id' => $year?->getKey(),
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
        ];
        $rows = $this->violationAggregateQuery($actor, $filters, $year, $start, $end)
            ->orderByDesc('latest_at')
            ->orderBy('identity_type')
            ->orderBy('identity_value')
            ->lazy(500)
            ->chunk(500)
            ->flatMap(fn (LazyCollection $chunk): Collection => $this->violationRows($chunk->collect(), $year))
            ->map(fn (array $row): array => $this->csvRow([
                $row['initials'],
                $row['masked_nisn'],
                $row['classroom'],
                $row['violation_count'],
                $row['total_points'],
                $row['latest_violation'].' · '.$row['latest_date'],
            ]));

        return [
            'id' => self::TAB_VIOLATIONS,
            'columns' => ['Murid', 'NISN Tersamarkan', 'Kelas', 'Jumlah pelanggaran', 'Total poin', 'Pelanggaran terakhir'],
            'rows' => $rows,
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function buildServices(User $actor, array $filters): array
    {
        [$year, $start, $end] = $this->period($filters);
        $filters = [
            ...$filters,
            'academic_year_id' => $year?->getKey(),
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
        ];
        $identities = $this->serviceIdentityQuery($actor, $filters, $year, $start, $end);
        $summary = DB::query()->fromSub(clone $identities, 'service_summary')
            ->selectRaw('COUNT(*) AS identity_count, COALESCE(SUM(case_count + consultation_count + follow_up_count), 0) AS service_count, COALESCE(SUM(open_follow_up_count), 0) AS open_follow_up_count')
            ->first();
        $rows = (clone $identities)
            ->orderByDesc('latest_included_at')
            ->orderBy('identity_type')
            ->orderBy('identity_id')
            ->paginate(20)
            ->withQueryString();
        $rows->setCollection($this->serviceRows($rows->getCollection(), $year));

        return [
            'id' => self::TAB_SERVICES,
            'tab' => self::TAB_SERVICES,
            'title' => 'Layanan BK',
            'columns' => ['Murid', 'NISN Tersamarkan', 'Kelas', 'Kasus', 'Konsultasi', 'Tindak lanjut', 'Perlu tindak lanjut', 'Layanan terakhir'],
            'stats' => [
                'student_count' => (int) ($summary->identity_count ?? 0),
                'service_count' => (int) ($summary->service_count ?? 0),
                'open_follow_up_count' => (int) ($summary->open_follow_up_count ?? 0),
            ],
            'rows' => $rows,
            'filters' => $filters,
            'academic_year' => $year,
            'period_start' => $start,
            'period_end' => $end,
            'generated_by' => $actor->name,
            'generated_at' => now(),
        ];
    }

    /** @param array<string, mixed> $filters @return array{id: string, columns: list<string>, rows: iterable<int, array<string, mixed>>} */
    private function exportServices(User $actor, array $filters): array
    {
        [$year, $start, $end] = $this->period($filters);
        $filters = [
            ...$filters,
            'academic_year_id' => $year?->getKey(),
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
        ];
        $rows = $this->serviceIdentityQuery($actor, $filters, $year, $start, $end)
            ->orderByDesc('latest_included_at')
            ->orderBy('identity_type')
            ->orderBy('identity_id')
            ->lazy(500)
            ->chunk(500)
            ->flatMap(fn (LazyCollection $chunk): Collection => $this->serviceRows($chunk->collect(), $year))
            ->map(fn (array $row): array => $this->csvRow([
                $row['initials'],
                $row['masked_nisn'],
                $row['classroom'],
                $row['case_count'],
                $row['consultation_count'],
                $row['follow_up_count'],
                $row['open_follow_up_count'],
                $row['latest_service_date'],
            ]));

        return [
            'id' => self::TAB_SERVICES,
            'columns' => ['Murid', 'NISN Tersamarkan', 'Kelas', 'Kasus', 'Konsultasi', 'Tindak lanjut', 'Perlu tindak lanjut', 'Layanan terakhir'],
            'rows' => $rows,
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function buildAchievements(User $actor, array $filters): array
    {
        [$year, $start, $end] = $this->period($filters);
        $filters = [
            ...$filters,
            'academic_year_id' => $year?->getKey(),
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
        ];
        $aggregates = $this->achievementAggregateQuery($actor, $filters, $year, $start, $end);
        $summary = DB::query()->fromSub(clone $aggregates, 'achievement_summary')
            ->selectRaw('COUNT(*) AS student_count, COALESCE(SUM(achievement_count), 0) AS achievement_count, COALESCE(SUM(verified_count), 0) AS verified_count')
            ->first();
        $rows = (clone $aggregates)
            ->orderByDesc('latest_at')
            ->orderBy('student_id')
            ->paginate(20)
            ->withQueryString();
        $rows->setCollection($this->achievementRows($rows->getCollection(), $year));

        return [
            'id' => self::TAB_ACHIEVEMENTS,
            'tab' => self::TAB_ACHIEVEMENTS,
            'title' => 'Prestasi',
            'columns' => ['Murid', 'NISN Tersamarkan', 'Kelas', 'Jumlah prestasi', 'Terverifikasi', 'Tingkat tertinggi', 'Prestasi terbaru'],
            'stats' => [
                'student_count' => (int) ($summary->student_count ?? 0),
                'achievement_count' => (int) ($summary->achievement_count ?? 0),
                'verified_count' => (int) ($summary->verified_count ?? 0),
            ],
            'rows' => $rows,
            'filters' => $filters,
            'academic_year' => $year,
            'period_start' => $start,
            'period_end' => $end,
            'generated_by' => $actor->name,
            'generated_at' => now(),
        ];
    }

    /** @param array<string, mixed> $filters @return array{id: string, columns: list<string>, rows: iterable<int, array<string, mixed>>} */
    private function exportAchievements(User $actor, array $filters): array
    {
        [$year, $start, $end] = $this->period($filters);
        $filters = [
            ...$filters,
            'academic_year_id' => $year?->getKey(),
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
        ];
        $rows = $this->achievementAggregateQuery($actor, $filters, $year, $start, $end)
            ->orderByDesc('latest_at')
            ->orderBy('student_id')
            ->lazy(500)
            ->chunk(500)
            ->flatMap(fn (LazyCollection $chunk): Collection => $this->achievementRows($chunk->collect(), $year))
            ->map(fn (array $row): array => $this->csvRow([
                $row['initials'],
                $row['masked_nisn'],
                $row['classroom'],
                $row['achievement_count'],
                $row['verified_count'],
                $row['highest_verified_level'],
                $row['latest_achievement'].' · '.$row['latest_date'],
            ]));

        return [
            'id' => self::TAB_ACHIEVEMENTS,
            'columns' => ['Murid', 'NISN Tersamarkan', 'Kelas', 'Jumlah prestasi', 'Terverifikasi', 'Tingkat tertinggi', 'Prestasi terbaru'],
            'rows' => $rows,
        ];
    }

    /** @param array<string, mixed> $filters */
    private function achievementAggregateQuery(
        User $actor,
        array $filters,
        ?AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): Builder {
        $query = Achievement::query()
            ->accessibleTo($actor)
            ->join('references as level_reference', function ($join): void {
                $join->on('level_reference.id', '=', 'achievements.level_id')
                    ->where('level_reference.category', 'achievement_level');
            })
            ->join('references as verification_reference', function ($join): void {
                $join->on('verification_reference.id', '=', 'achievements.verification_status_id')
                    ->where('verification_reference.category', 'achievement_verification_status');
            })
            ->whereBetween('achievements.achievement_date', [$start->toDateString(), $end->toDateString()])
            ->when($filters['q'] ?? null, fn (Builder $items, string $q): Builder => $items
                ->whereHas('student', fn (Builder $students): Builder => $students
                    ->whereRaw($this->likeClause('students.name'), ['%'.$this->escapeLike($q).'%'])));

        if (isset($filters['classroom_id'])) {
            $this->whereHistoricClass($query, 'achievements.achievement_date', (int) $filters['classroom_id'], $year);
        }

        return $query
            ->select('achievements.student_id')
            ->selectRaw('COUNT(*) AS achievement_count')
            ->selectRaw("SUM(CASE WHEN verification_reference.code = 'terverifikasi' THEN 1 ELSE 0 END) AS verified_count")
            ->selectRaw('MAX(achievements.achievement_date) AS latest_at')
            ->selectRaw("MAX(CASE WHEN verification_reference.code = 'terverifikasi' THEN level_reference.sort_order ELSE NULL END) AS highest_verified_sort")
            ->groupBy('achievements.student_id');
    }

    /** @param Collection<int, object> $aggregates @return Collection<int, array<string, mixed>> */
    private function achievementRows(Collection $aggregates, ?AcademicYear $year): Collection
    {
        if ($aggregates->isEmpty()) {
            return collect();
        }

        $studentIds = $aggregates->pluck('student_id')->map(fn (mixed $id): int => (int) $id)->all();
        $students = Student::query()->whereKey($studentIds)->get()->keyBy('id');
        $latest = $this->latestAchievements($aggregates);
        $levels = ReferenceValue::query()
            ->forCategory('achievement_level')
            ->whereIn('sort_order', $aggregates->pluck('highest_verified_sort')->filter()->unique()->all())
            ->get()
            ->keyBy('sort_order');
        $memberships = StudentClassMembership::query()
            ->with('classroom')
            ->whereIn('student_id', $studentIds)
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('academic_year_id', $selected->id))
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('student_id');

        return $aggregates->map(function (object $aggregate) use ($students, $latest, $levels, $memberships): array {
            $student = $students->get((int) $aggregate->student_id);
            $latestAchievement = $latest->get((int) $aggregate->student_id);
            $latestAt = CarbonImmutable::parse($aggregate->latest_at);
            $membership = $memberships->get((int) $aggregate->student_id, collect())->first(
                fn (StudentClassMembership $item): bool => $item->effective_from->startOfDay()->lte($latestAt)
                    && ($item->effective_until === null || $item->effective_until->endOfDay()->gte($latestAt)),
            );

            return [
                'identity_key' => 'student:'.$student->id,
                'initials' => $this->initials($student->name),
                'masked_nisn' => $this->maskNisn($student->nisn),
                'classroom' => $membership?->classroom?->name ?? 'Tanpa kelas',
                'achievement_count' => (int) $aggregate->achievement_count,
                'verified_count' => (int) $aggregate->verified_count,
                'highest_verified_level' => $levels->get((int) $aggregate->highest_verified_sort)?->label ?? 'Belum ada',
                'latest_achievement' => $latestAchievement?->activity_name ?? '—',
                'latest_date' => $this->formatDate($latestAchievement?->achievement_date ?? $latestAt),
            ];
        });
    }

    /** @param Collection<int, object> $aggregates @return Collection<int, Achievement> */
    private function latestAchievements(Collection $aggregates): Collection
    {
        return Achievement::query()
            ->withinStudentServicePeriod()
            ->select(['id', 'student_id', 'activity_name', 'achievement_date'])
            ->where(function (Builder $items) use ($aggregates): void {
                foreach ($aggregates as $aggregate) {
                    $items->orWhere(fn (Builder $identity): Builder => $identity
                        ->where('student_id', (int) $aggregate->student_id)
                        ->where('achievement_date', $aggregate->latest_at));
                }
            })
            ->orderByDesc('achievement_date')
            ->orderByDesc('id')
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');
    }

    /** @param array<string, mixed> $filters */
    private function serviceIdentityQuery(
        User $actor,
        array $filters,
        ?AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): QueryBuilder {
        $caseEvents = BkCase::query()
            ->accessibleTo($actor)
            ->leftJoin('temporary_students', 'temporary_students.id', '=', 'cases.temporary_student_id')
            ->whereBetween('cases.service_date', [$start->toDateString(), $end->toDateString()]);
        $this->applyCaseIdentityFilters($caseEvents, $filters, $year, 'cases.service_date');
        $caseEvents
            ->selectRaw("CASE WHEN cases.student_id IS NOT NULL OR temporary_students.reconciled_student_id IS NOT NULL THEN 'student' ELSE 'temporary' END AS identity_type")
            ->selectRaw('COALESCE(cases.student_id, temporary_students.reconciled_student_id, cases.temporary_student_id) AS identity_id')
            ->selectRaw('cases.service_date AS included_at, cases.service_date AS actual_at')
            ->selectRaw('1 AS case_count, 0 AS consultation_count, 0 AS follow_up_count, 0 AS open_follow_up_count');

        $consultationEvents = Consultation::query()
            ->accessibleTo($actor)
            ->leftJoin('temporary_students', 'temporary_students.id', '=', 'consultations.temporary_student_id')
            ->whereBetween('consultations.session_date', [$start->toDateString(), $end->toDateString()]);
        $this->applyConsultationIdentityFilters($consultationEvents, $filters, $year);
        $consultationEvents
            ->selectRaw("CASE WHEN consultations.student_id IS NOT NULL OR temporary_students.reconciled_student_id IS NOT NULL THEN 'student' ELSE 'temporary' END AS identity_type")
            ->selectRaw('COALESCE(consultations.student_id, temporary_students.reconciled_student_id, consultations.temporary_student_id) AS identity_id')
            ->selectRaw('consultations.session_date AS included_at, consultations.session_date AS actual_at')
            ->selectRaw('0 AS case_count, 1 AS consultation_count, 0 AS follow_up_count, 0 AS open_follow_up_count');

        $followUpDate = 'COALESCE(follow_ups.execution_date, follow_ups.planned_date)';
        $followUpEvents = FollowUp::query()
            ->whereHas('case', fn (Builder $cases): Builder => $cases->accessibleTo($actor))
            ->join('cases', 'cases.id', '=', 'follow_ups.case_id')
            ->leftJoin('temporary_students', 'temporary_students.id', '=', 'cases.temporary_student_id')
            ->join('references as follow_up_status', 'follow_up_status.id', '=', 'follow_ups.status_id')
            ->whereBetween(DB::raw($followUpDate), [$start->toDateString(), $end->toDateString()]);
        $this->applyFollowUpIdentityFilters($followUpEvents, $filters, $year, $followUpDate);
        $followUpEvents
            ->selectRaw("CASE WHEN cases.student_id IS NOT NULL OR temporary_students.reconciled_student_id IS NOT NULL THEN 'student' ELSE 'temporary' END AS identity_type")
            ->selectRaw('COALESCE(cases.student_id, temporary_students.reconciled_student_id, cases.temporary_student_id) AS identity_id')
            ->selectRaw($followUpDate.' AS included_at, follow_ups.execution_date AS actual_at')
            ->selectRaw("0 AS case_count, 0 AS consultation_count, 1 AS follow_up_count, CASE WHEN follow_up_status.code IN ('terjadwal', 'ditunda') THEN 1 ELSE 0 END AS open_follow_up_count");

        return DB::query()
            ->fromSub($caseEvents->unionAll($consultationEvents)->unionAll($followUpEvents), 'service_events')
            ->select(['identity_type', 'identity_id'])
            ->selectRaw('SUM(case_count) AS case_count')
            ->selectRaw('SUM(consultation_count) AS consultation_count')
            ->selectRaw('SUM(follow_up_count) AS follow_up_count')
            ->selectRaw('SUM(open_follow_up_count) AS open_follow_up_count')
            ->selectRaw('MAX(included_at) AS latest_included_at')
            ->selectRaw('MAX(actual_at) AS latest_service_at')
            ->groupBy('identity_type', 'identity_id');
    }

    /** @param array<string, mixed> $filters */
    private function applyCaseIdentityFilters(Builder $query, array $filters, ?AcademicYear $year, string $dateColumn): void
    {
        $this->applyServiceNameFilter($query, $filters, 'student', 'temporaryStudent');
        if (isset($filters['classroom_id'])) {
            $this->whereHistoricClass($query, $dateColumn, (int) $filters['classroom_id'], $year, [
                'student.classMemberships',
                'temporaryStudent.reconciledStudent.classMemberships',
            ]);
        }
        if (isset($filters['counselor_id'])) {
            $query->whereHas('assignments', fn (Builder $assignments): Builder => $assignments
                ->where('assignment_type', CaseAssignment::TYPE_OWNER)
                ->where('user_id', (int) $filters['counselor_id'])
                ->whereRaw('DATE(case_assignments.effective_from) <= DATE('.$dateColumn.')')
                ->where(function (Builder $period) use ($dateColumn): void {
                    $period->whereNull('case_assignments.effective_until')
                        ->orWhereRaw('DATE(case_assignments.effective_until) >= DATE('.$dateColumn.')');
                }));
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyConsultationIdentityFilters(Builder $query, array $filters, ?AcademicYear $year): void
    {
        $this->applyServiceNameFilter($query, $filters, 'student', 'temporaryStudent');
        if (isset($filters['classroom_id'])) {
            $this->whereHistoricClass($query, 'consultations.session_date', (int) $filters['classroom_id'], $year, [
                'student.classMemberships',
                'temporaryStudent.reconciledStudent.classMemberships',
            ]);
        }
        if (isset($filters['counselor_id'])) {
            $query->where('consultations.counselor_id', (int) $filters['counselor_id']);
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyFollowUpIdentityFilters(Builder $query, array $filters, ?AcademicYear $year, string $dateColumn): void
    {
        $this->applyServiceNameFilter($query, $filters, 'case.student', 'case.temporaryStudent');
        if (isset($filters['classroom_id'])) {
            $this->whereHistoricClass($query, $dateColumn, (int) $filters['classroom_id'], $year, [
                'case.student.classMemberships',
                'case.temporaryStudent.reconciledStudent.classMemberships',
            ]);
        }
        if (isset($filters['counselor_id'])) {
            $query->whereHas('case.assignments', fn (Builder $assignments): Builder => $assignments
                ->where('assignment_type', CaseAssignment::TYPE_OWNER)
                ->where('user_id', (int) $filters['counselor_id'])
                ->whereRaw('DATE(case_assignments.effective_from) <= DATE('.$dateColumn.')')
                ->where(function (Builder $period) use ($dateColumn): void {
                    $period->whereNull('case_assignments.effective_until')
                        ->orWhereRaw('DATE(case_assignments.effective_until) >= DATE('.$dateColumn.')');
                }));
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyServiceNameFilter(Builder $query, array $filters, string $studentPath, string $temporaryPath): void
    {
        $q = $filters['q'] ?? null;
        if (blank($q)) {
            return;
        }

        $pattern = '%'.$this->escapeLike((string) $q).'%';
        $query->where(function (Builder $identities) use ($studentPath, $temporaryPath, $pattern): void {
            $identities->whereHas($studentPath, fn (Builder $students): Builder => $students
                ->whereRaw($this->likeClause('students.name'), [$pattern]))
                ->orWhereHas($temporaryPath.'.reconciledStudent', fn (Builder $students): Builder => $students
                    ->whereRaw($this->likeClause('students.name'), [$pattern]))
                ->orWhereHas($temporaryPath, fn (Builder $temporary): Builder => $temporary
                    ->whereNull('reconciled_student_id')
                    ->whereRaw($this->likeClause('temporary_students.input_name'), [$pattern]));
        });
    }

    /** @param Collection<int, object> $aggregates @return Collection<int, array<string, mixed>> */
    private function serviceRows(Collection $aggregates, ?AcademicYear $year): Collection
    {
        if ($aggregates->isEmpty()) {
            return collect();
        }

        $studentIds = $aggregates->where('identity_type', 'student')->pluck('identity_id')->map(fn (mixed $id): int => (int) $id)->all();
        $temporaryIds = $aggregates->where('identity_type', 'temporary')->pluck('identity_id')->map(fn (mixed $id): int => (int) $id)->all();
        $students = Student::query()->whereKey($studentIds)->get()->keyBy('id');
        $temporaryStudents = TemporaryStudent::query()->whereKey($temporaryIds)->get()->keyBy('id');
        $memberships = StudentClassMembership::query()
            ->with('classroom')
            ->whereIn('student_id', $studentIds)
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('academic_year_id', $selected->id))
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('student_id');

        return $aggregates->map(function (object $aggregate) use ($students, $temporaryStudents, $memberships): array {
            $isTemporary = $aggregate->identity_type === 'temporary';
            $identityId = (int) $aggregate->identity_id;
            $student = $isTemporary ? null : $students->get($identityId);
            $temporary = $isTemporary ? $temporaryStudents->get($identityId) : null;
            $latestServiceAt = $aggregate->latest_service_at === null ? null : CarbonImmutable::parse($aggregate->latest_service_at);
            $membership = $student === null || $latestServiceAt === null
                ? null
                : $memberships->get($student->id, collect())->first(
                    fn (StudentClassMembership $item): bool => $item->effective_from->startOfDay()->lte($latestServiceAt)
                        && ($item->effective_until === null || $item->effective_until->endOfDay()->gte($latestServiceAt)),
                );

            return [
                'identity_key' => $this->identityKey($student?->id, $temporary?->id, null),
                'initials' => $this->initials($student?->name ?? $temporary?->input_name),
                'masked_nisn' => $this->maskNisn($student?->nisn ?? $temporary?->nisn ?? ''),
                'classroom' => $membership?->classroom?->name ?? 'Belum tersedia',
                'is_temporary' => $isTemporary,
                'identity_badge' => $isTemporary ? 'Belum terverifikasi Dapodik' : null,
                'case_count' => (int) $aggregate->case_count,
                'consultation_count' => (int) $aggregate->consultation_count,
                'follow_up_count' => (int) $aggregate->follow_up_count,
                'open_follow_up_count' => (int) $aggregate->open_follow_up_count,
                'latest_service_date' => $latestServiceAt === null ? 'Belum terlaksana' : $this->formatDate($latestServiceAt),
            ];
        });
    }

    private function identityKey(?int $studentId, ?int $temporaryStudentId, ?int $reconciledStudentId): string
    {
        $officialStudentId = $studentId ?? $reconciledStudentId;

        return $officialStudentId !== null
            ? 'student:'.$officialStudentId
            : 'temporary:'.(int) $temporaryStudentId;
    }

    private function formatDate(mixed $date): string
    {
        return str_replace(' Agt ', ' Agu ', CarbonImmutable::parse($date)->locale('id')->translatedFormat('d M Y'));
    }

    /** @param array<string, mixed> $filters */
    private function violationAggregateQuery(
        User $actor,
        array $filters,
        ?AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): QueryBuilder {
        $linked = ExternalTatibRecord::query()
            ->active()
            ->whereNotNull('student_id')
            ->whereBetween('occurred_at', [$start, $end])
            ->whereIn('student_id', $this->accessibleStudents($actor)->select('students.id'))
            ->when($filters['q'] ?? null, fn (Builder $records, string $q): Builder => $records
                ->whereHas('student', fn (Builder $students): Builder => $students
                    ->whereRaw($this->likeClause('name'), ['%'.$this->escapeLike($q).'%'])))
            ->when($filters['classroom_id'] ?? null, fn (Builder $records, int $classroomId): Builder => $this->whereHistoricClass($records, 'external_tatib_records.occurred_at', $classroomId, $year))
            ->selectRaw("'student' AS identity_type, student_id AS identity_value")
            ->selectRaw('MIN(id) AS identity_anchor_id, COUNT(*) AS violation_count, COALESCE(SUM(points), 0) AS total_points, MAX(occurred_at) AS latest_at')
            ->groupBy('student_id');

        $includeUnlinked = $actor->hasRole('koordinator_bk')
            && blank($filters['q'] ?? null)
            && ! isset($filters['classroom_id']);
        $unlinked = ExternalTatibRecord::query()
            ->active()
            ->whereNull('student_id')
            ->whereBetween('occurred_at', [$start, $end])
            ->when(! $includeUnlinked, fn (Builder $records): Builder => $records->whereRaw('1 = 0'))
            ->selectRaw("'etatib' AS identity_type, nisn AS identity_value")
            ->selectRaw('MIN(id) AS identity_anchor_id, COUNT(*) AS violation_count, COALESCE(SUM(points), 0) AS total_points, MAX(occurred_at) AS latest_at')
            ->groupBy('nisn');

        return DB::query()->fromSub($linked->unionAll($unlinked), 'violation_identities');
    }

    /** @param Collection<int, object> $aggregates @return Collection<int, array<string, mixed>> */
    private function violationRows(Collection $aggregates, ?AcademicYear $year): Collection
    {
        if ($aggregates->isEmpty()) {
            return collect();
        }

        $studentIds = $aggregates->where('identity_type', 'student')->pluck('identity_value')->map(fn (mixed $id): int => (int) $id)->all();
        $students = Student::query()->whereKey($studentIds)->get()->keyBy('id');
        $latestRecords = $this->latestViolationRecords($aggregates);
        $memberships = StudentClassMembership::query()
            ->with('classroom')
            ->whereIn('student_id', $studentIds)
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('academic_year_id', $selected->id))
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('student_id');

        return $aggregates->map(function (object $aggregate) use ($students, $latestRecords, $memberships): array {
            $type = (string) $aggregate->identity_type;
            $value = (string) $aggregate->identity_value;
            $latest = $latestRecords->get($type.':'.$value);
            $student = $type === 'student' ? $students->get((int) $value) : null;
            $latestAt = CarbonImmutable::parse($aggregate->latest_at);
            $membership = $student === null ? null : $memberships->get($student->id, collect())->first(
                fn (StudentClassMembership $item): bool => $item->effective_from->startOfDay()->lte($latestAt)
                    && ($item->effective_until === null || $item->effective_until->endOfDay()->gte($latestAt)),
            );

            return [
                'identity_key' => $student === null ? 'etatib:'.$aggregate->identity_anchor_id : 'student:'.$student->id,
                'initials' => $student === null ? 'Belum tertaut' : $this->initials($student->name),
                'masked_nisn' => $this->maskNisn($student?->nisn ?? $value),
                'classroom' => $student === null ? 'Belum tersedia' : ($membership?->classroom?->name ?? 'Tanpa kelas'),
                'violation_count' => (int) $aggregate->violation_count,
                'total_points' => (int) $aggregate->total_points,
                'latest_violation' => $latest?->violation_type ?? '—',
                'latest_date' => $this->formatDate($latest?->occurred_at ?? $latestAt),
            ];
        });
    }

    /** @param Collection<int, object> $aggregates @return Collection<string, ExternalTatibRecord> */
    private function latestViolationRecords(Collection $aggregates): Collection
    {
        return ExternalTatibRecord::query()
            ->active()
            ->where(function (Builder $records) use ($aggregates): void {
                foreach ($aggregates as $aggregate) {
                    $records->orWhere(function (Builder $identity) use ($aggregate): void {
                        if ($aggregate->identity_type === 'student') {
                            $identity->where('student_id', (int) $aggregate->identity_value);
                        } else {
                            $identity->whereNull('student_id')->where('nisn', (string) $aggregate->identity_value);
                        }
                        $identity->where('occurred_at', $aggregate->latest_at);
                    });
                }
            })
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (ExternalTatibRecord $record): string => $record->student_id === null
                ? 'etatib:'.$record->nisn
                : 'student:'.$record->student_id)
            ->keyBy(fn (ExternalTatibRecord $record): string => $record->student_id === null
                ? 'etatib:'.$record->nisn
                : 'student:'.$record->student_id);
    }

    private function initials(?string $name): string
    {
        if (blank($name)) {
            return '—';
        }

        return collect(preg_split('/\s+/u', trim($name)) ?: [])
            ->filter()
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)).'.')
            ->join('');
    }

    private function maskNisn(string $nisn): string
    {
        $length = mb_strlen($nisn);

        return $length <= 4
            ? str_repeat('*', $length)
            : mb_substr($nisn, 0, 2).str_repeat('*', $length - 4).mb_substr($nisn, -2);
    }

    /** @param array<string, mixed> $filters @return array{AcademicYear|null, CarbonImmutable, CarbonImmutable} */
    private function period(array $filters): array
    {
        $year = isset($filters['academic_year_id'])
            ? AcademicYear::query()->find($filters['academic_year_id'])
            : AcademicYear::query()->active()->orderByDesc('starts_on')->first();
        $year ??= AcademicYear::query()->orderByDesc('starts_on')->first();
        $start = CarbonImmutable::parse($filters['date_start'] ?? $year?->starts_on?->toDateString() ?? now()->startOfYear()->toDateString());
        $end = CarbonImmutable::parse($filters['date_end'] ?? $year?->ends_on?->toDateString() ?? now()->endOfYear()->toDateString());

        return [$year, $start->startOfDay(), $end->endOfDay()];
    }

    /** @return Builder<Student> */
    private function accessibleStudents(User $actor): Builder
    {
        return Student::query()->accessibleTo($actor);
    }

    /** @return Builder<Classroom> */
    private function accessibleClassrooms(User $actor, ?AcademicYear $year): Builder
    {
        return Classroom::query()
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('academic_year_id', $selected->id))
            ->whereHas('studentClassMemberships.student', fn (Builder $students): Builder => $students
                ->accessibleTo($actor));
    }

    /** @return Builder<User> */
    private function activeCounselors(): Builder
    {
        return User::query()->active()->whereHas('roles', fn (Builder $roles): Builder => $roles
            ->where('slug', 'guru_bk')->where('is_active', true));
    }

    /** @param array<string, mixed> $report @return array<string, list<array{id: int, name: string}>> */
    private function filterOptions(User $actor, array $report): array
    {
        /** @var AcademicYear|null $year */
        $year = $report['academic_year'];

        return [
            'academic_years' => AcademicYear::query()->orderByDesc('starts_on')->get(['id', 'name'])
                ->map(fn (AcademicYear $item): array => ['id' => $item->id, 'name' => $item->name])->all(),
            'classrooms' => $this->accessibleClassrooms($actor, $year)->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Classroom $item): array => ['id' => $item->id, 'name' => $item->name])->all(),
            'counselors' => $actor->hasRole('koordinator_bk') && $report['tab'] === self::TAB_SERVICES
                ? $this->activeCounselors()->orderBy('name')->get(['users.id', 'users.name'])
                    ->map(fn (User $item): array => ['id' => $item->id, 'name' => $item->name])->all()
                : [],
        ];
    }

    /** @param list<mixed> $values @return array{cells: list<array{value: string}>} */
    private function csvRow(array $values): array
    {
        return ['cells' => array_map(fn (mixed $value): array => ['value' => (string) $value], $values)];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function likeClause(string $column): string
    {
        $escape = DB::connection()->getDriverName() === 'mysql' ? "'\\\\'" : "'\\'";

        return $column.' LIKE ? ESCAPE '.$escape;
    }

    /** @param list<string> $membershipPaths */
    private function whereHistoricClass(
        Builder $query,
        string $dateColumn,
        int $classroomId,
        ?AcademicYear $year,
        array $membershipPaths = ['student.classMemberships'],
    ): Builder {
        return $query->where(function (Builder $identities) use ($dateColumn, $classroomId, $year, $membershipPaths): void {
            foreach ($membershipPaths as $index => $path) {
                $method = $index === 0 ? 'whereHas' : 'orWhereHas';
                $identities->{$method}($path, function (Builder $memberships) use ($dateColumn, $classroomId, $year): void {
                    $memberships->where('classroom_id', $classroomId)
                        ->when($year, fn (Builder $scope, AcademicYear $selected): Builder => $scope
                            ->where('academic_year_id', $selected->id))
                        ->whereRaw('DATE(effective_from) <= DATE('.$dateColumn.')')
                        ->where(function (Builder $period) use ($dateColumn): void {
                            $period->whereNull('effective_until')
                                ->orWhereRaw('DATE(effective_until) >= DATE('.$dateColumn.')');
                        });
                });
            }
        });
    }
}
