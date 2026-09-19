<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ExternalTatibRecord;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ViolationReportQuery extends LegacyReportQuery
{
    public function build(
        string $type,
        User $user,
        array $filters,
        ?AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
        bool $paginate,
    ): array {
        $data = match ($type) {
            ReportService::TYPE_STUDENT_VIOLATIONS => $paginate
                ? $this->paginatedStudentViolations($user, $filters, $year, $start, $end)
                : $this->studentViolations($user, $filters, $year, $start, $end),
            ReportService::TYPE_CLASS_VIOLATIONS => $this->classViolations($user, $filters, $year, $start, $end),
            ReportService::TYPE_VIOLATION_POINTS => $this->violationPoints($user, $filters, $year, $start, $end),
            default => abort(404),
        };
        $rows = $data['rows'];
        $data['rows'] = $rows instanceof LengthAwarePaginator
            ? $rows
            : ($paginate ? $this->paginate($rows, $filters) : $rows);

        return $data;
    }

    public function export(
        string $type,
        User $user,
        array $filters,
        ?AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        if ($type !== ReportService::TYPE_STUDENT_VIOLATIONS) {
            $report = $this->build($type, $user, $filters, $year, $start, $end, false);

            return ['id' => $type, 'columns' => $report['columns'], 'rows' => $report['rows']];
        }

        return [
            'id' => $type,
            'columns' => ['NISN Tersamarkan', 'Inisial Murid', 'Kelas', 'Tanggal', 'Jenis Pelanggaran', 'Kategori', 'Poin'],
            'rows' => $this->etatibReportQuery($user, $filters, $year, $start, $end)
                ->latest('occurred_at')->latest('id')->lazy(500)
                ->map(fn (ExternalTatibRecord $record): array => $this->row([
                    $this->maskNisn($record->nisn),
                    $this->initials($record->student?->name),
                    $this->historicClass($record->student, $record->occurred_at, $year),
                    $record->occurred_at->locale('id')->translatedFormat('d M Y'),
                    $record->violation_type,
                    $record->category,
                    $record->points.' poin',
                ], 6, $this->pointsTone($record->points))),
        ];
    }

    /** @return Collection<int, string> */
    public function categories(User $user): Collection
    {
        return $this->etatibQuery($user)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    /** @return array{columns: list<string>, rows: LengthAwarePaginator, stats: array<string, array<string, string>>} */
    protected function paginatedStudentViolations(
        User $user,
        array $filters,
        ?AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $query = $this->etatibReportQuery($user, $filters, $year, $start, $end);
        $total = (clone $query)->count();
        $studentCount = (clone $query)->whereNotNull('student_id')->distinct()->count('student_id');
        $totalPoints = (int) (clone $query)->sum('points');
        $page = $query->latest('occurred_at')->paginate(20)->withQueryString();
        $page->setCollection($page->getCollection()->map(function (ExternalTatibRecord $record) use ($year): array {
            return $this->row([
                $this->maskNisn($record->nisn),
                $this->initials($record->student?->name),
                $this->historicClass($record->student, $record->occurred_at, $year),
                $record->occurred_at->locale('id')->translatedFormat('d M Y'),
                $record->violation_type,
                $record->category,
                $record->points.' poin',
            ], 6, $this->pointsTone($record->points));
        }));

        return [
            'columns' => ['NISN Tersamarkan', 'Inisial Murid', 'Kelas', 'Tanggal', 'Jenis Pelanggaran', 'Kategori', 'Poin'],
            'rows' => $page,
            'stats' => $this->stats(
                ['Total Kejadian', $total, 'Periode terpilih'],
                ['Murid Terkait', $studentCount, 'Identitas tersamarkan'],
                ['Total Poin', $totalPoints, 'Mirror e-Tatib'],
            ),
        ];
    }

    /** @return array{columns: list<string>, rows: Collection<int, array<string, mixed>>, stats: array<string, array<string, string>>} */
    protected function studentViolations(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $records = $this->filteredEtatib($user, $filters, $year, $start, $end);
        $rows = $records->sortByDesc('occurred_at')->values()->map(function (ExternalTatibRecord $record) use ($year): array {
            return $this->row([
                $this->maskNisn($record->nisn),
                $this->initials($record->student?->name),
                $this->historicClass($record->student, $record->occurred_at, $year),
                $record->occurred_at->locale('id')->translatedFormat('d M Y'),
                $record->violation_type,
                $record->category,
                $record->points.' poin',
            ], 6, $this->pointsTone($record->points));
        });

        return [
            'columns' => ['NISN Tersamarkan', 'Inisial Murid', 'Kelas', 'Tanggal', 'Jenis Pelanggaran', 'Kategori', 'Poin'],
            'rows' => $rows,
            'stats' => $this->stats(
                ['Total Kejadian', $records->count(), 'Periode terpilih'],
                ['Murid Terkait', $records->pluck('student_id')->filter()->unique()->count(), 'Identitas tersamarkan'],
                ['Total Poin', $records->sum('points'), 'Mirror e-Tatib'],
            ),
        ];
    }

    /** @return array{columns: list<string>, rows: Collection<int, array<string, mixed>>, stats: array<string, array<string, string>>} */
    protected function classViolations(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $groups = [];
        $total = 0;
        $totalPoints = 0;
        foreach ($this->etatibReportQuery($user, $filters, $year, $start, $end)->lazy(500) as $record) {
            $class = $this->historicClass($record->student, $record->occurred_at, $year);
            $groups[$class] ??= ['count' => 0, 'points' => 0, 'categories' => [], 'latest' => null];
            $groups[$class]['count']++;
            $groups[$class]['points'] += $record->points;
            $groups[$class]['categories'][$record->category] = ($groups[$class]['categories'][$record->category] ?? 0) + 1;
            if ($groups[$class]['latest'] === null || $record->occurred_at->gt($groups[$class]['latest'])) {
                $groups[$class]['latest'] = $record->occurred_at;
            }
            $total++;
            $totalPoints += $record->points;
        }
        $rows = collect($groups)->map(function (array $group, string $class): array {
            arsort($group['categories']);

            return $this->row([
                $class,
                $group['count'].' kejadian',
                $group['points'].' poin',
                array_key_first($group['categories']) ?? '—',
                $group['latest']?->locale('id')->translatedFormat('d M Y') ?? '—',
            ]);
        })->values();

        return [
            'columns' => ['Kelas', 'Jumlah Kejadian', 'Total Poin', 'Kategori Dominan', 'Kejadian Terakhir'],
            'rows' => $rows,
            'stats' => $this->stats(
                ['Total Pelanggaran', $total, 'Periode terpilih'],
                ['Kelas Terkait', count($groups), 'Berdasarkan histori kelas'],
                ['Total Poin', $totalPoints, 'Mirror e-Tatib'],
            ),
        ];
    }

    /** @return array{columns: list<string>, rows: Collection<int, array<string, mixed>>, stats: array<string, array<string, string>>} */
    protected function violationPoints(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $groups = [];
        $total = 0;
        $totalPoints = 0;
        foreach ($this->etatibReportQuery($user, $filters, $year, $start, $end)->lazy(500) as $record) {
            $key = $record->student_id !== null ? 'student-'.$record->student_id : 'nisn-'.$record->nisn;
            $groups[$key] ??= ['points' => 0, 'categories' => [], 'latest' => $record, 'synced_at' => $record->synced_at];
            $groups[$key]['points'] += $record->points;
            $groups[$key]['categories'][$record->category] = true;
            if ($record->occurred_at->gt($groups[$key]['latest']->occurred_at)) {
                $groups[$key]['latest'] = $record;
            }
            if ($record->synced_at !== null && ($groups[$key]['synced_at'] === null || $record->synced_at->gt($groups[$key]['synced_at']))) {
                $groups[$key]['synced_at'] = $record->synced_at;
            }
            $total++;
            $totalPoints += $record->points;
        }
        $minimum = (int) ($filters['minimum_points'] ?? 0);
        $groups = array_filter($groups, fn (array $group): bool => $group['points'] >= $minimum);
        $rows = collect($groups)->map(function (array $group) use ($year): array {
            /** @var ExternalTatibRecord $latest */
            $latest = $group['latest'];

            return $this->row([
                $this->maskNisn($latest->nisn),
                $this->initials($latest->student?->name),
                $this->historicClass($latest->student, $latest->occurred_at, $year),
                $group['points'].' poin',
                implode(', ', array_keys($group['categories'])) ?: '—',
                $group['synced_at']?->locale('id')->translatedFormat('d M Y H.i') ?? '—',
            ], 3, $this->pointsTone($group['points']));
        })->sortByDesc(fn (array $row): int => (int) $row['sort_value'])->values();

        return [
            'columns' => ['NISN Tersamarkan', 'Inisial Murid', 'Kelas', 'Total Poin', 'Kategori', 'Sinkron Terakhir'],
            'rows' => $rows,
            'stats' => $this->stats(
                ['Total Akumulasi Poin', $totalPoints, 'Periode terpilih'],
                ['Murid Tercatat', count($groups), 'Sesuai ambang filter'],
                ['Total Kejadian', $total, 'Mirror e-Tatib'],
            ),
        ];
    }

    /** @return Collection<int, ExternalTatibRecord> */
    protected function filteredEtatib(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $query = $this->etatibReportQuery($user, $filters, $year, $start, $end);

        return $query->get()->filter(fn (ExternalTatibRecord $record): bool => $this->matchesClass(
            $record->student,
            $record->occurred_at,
            $year,
            $filters,
        ))->values();
    }

    /** @return Builder<ExternalTatibRecord> */
    protected function etatibReportQuery(
        User $user,
        array $filters,
        ?AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): Builder {
        $query = ExternalTatibRecord::query()->active()
            ->whereBetween('occurred_at', [$start, $end])
            ->with('student.classMemberships.classroom');
        $this->scopeEtatib($query, $user);
        $query->when($filters['student_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('student_id', $id));
        $query->when($filters['category'] ?? null, fn (Builder $builder, string $category): Builder => $builder->where('category', $category));
        $this->applyHistoricClassFilter($query, $filters, $year, 'external_tatib_records.occurred_at', ['student.classMemberships']);

        return $query;
    }

    /** @param Builder<ExternalTatibRecord> $query */
    protected function scopeEtatib(Builder $query, User $user): void
    {
        if ($user->hasRole('koordinator_bk')) {
            return;
        }

        $query->where(function (Builder $access) use ($user): void {
            $hasCondition = false;
            if ($user->hasRole('guru_bk')) {
                $access->whereIn('student_id', Student::query()->professionallyAccessibleTo($user)->select('students.id'));
                $hasCondition = true;
            }
            if ($user->hasRole('waka_kesiswaan')) {
                $method = $hasCondition ? 'orWhereHas' : 'whereHas';
                $access->{$method}('cases', fn (Builder $cases): Builder => $cases->accessibleTo($user));
            }
        });
    }

    protected function pointsTone(int $points): string
    {
        return $points >= 25 ? 'warning' : 'primary';
    }

    /** @return Builder<ExternalTatibRecord> */
    protected function etatibQuery(User $user): Builder
    {
        $query = ExternalTatibRecord::query()->active();
        $this->scopeEtatib($query, $user);

        return $query;
    }
}
