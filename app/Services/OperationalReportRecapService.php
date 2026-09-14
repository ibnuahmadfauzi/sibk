<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\OperationalReportRecap;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ExternalTatibRecord;
use App\Models\Student;
use App\Models\StudentClassMembership;
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

        return match ($filters['tab']) {
            self::TAB_VIOLATIONS => $this->buildViolations($actor, $filters),
            self::TAB_SERVICES => $this->buildServices($actor, $filters),
            self::TAB_ACHIEVEMENTS => $this->buildAchievements($actor, $filters),
        };
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
            ->flatMap(fn (LazyCollection $chunk): Collection => $this->violationRows($chunk->collect(), $year));

        return [
            'id' => self::TAB_VIOLATIONS,
            'columns' => ['Murid', 'NISN Tersamarkan', 'Kelas', 'Jumlah pelanggaran', 'Total poin', 'Pelanggaran terakhir'],
            'rows' => $rows,
        ];
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
                'latest_date' => $latest?->occurred_at?->locale('id')->translatedFormat('d M Y') ?? $latestAt->locale('id')->translatedFormat('d M Y'),
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
