<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Policies\ReportPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class LegacyReportQuery
{
    public function __construct(protected readonly ReportPolicy $policy) {}

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    abstract public function build(
        string $type,
        User $user,
        array $filters,
        ?AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
        bool $paginate,
    ): array;

    /** @param array<string, mixed> $filters @return array{id: string, columns: list<string>, rows: iterable<int, array<string, mixed>>} */
    abstract public function export(
        string $type,
        User $user,
        array $filters,
        ?AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array;

    public function studentLabel(Student $student): string
    {
        return $this->initials($student->name).' · '.$this->maskNisn($student->nisn);
    }

    /** @param Builder<*> $query @param list<string> $membershipPaths */
    protected function applyHistoricClassFilter(
        Builder $query,
        array $filters,
        ?AcademicYear $year,
        string $dateColumn,
        array $membershipPaths,
    ): void {
        if (! isset($filters['classroom_id'])) {
            return;
        }

        $query->where(function (Builder $access) use ($membershipPaths, $filters, $year, $dateColumn): void {
            foreach ($membershipPaths as $index => $path) {
                $method = $index === 0 ? 'whereHas' : 'orWhereHas';
                $access->{$method}($path, function (Builder $memberships) use ($filters, $year, $dateColumn): void {
                    $memberships->where('classroom_id', (int) $filters['classroom_id'])
                        ->when($year, fn (Builder $builder, AcademicYear $selected): Builder => $builder->where('academic_year_id', $selected->getKey()))
                        ->whereColumn('effective_from', '<=', $dateColumn)
                        ->where(function (Builder $period) use ($dateColumn): void {
                            $period->whereNull('effective_until')->orWhereColumn('effective_until', '>=', $dateColumn);
                        });
                });
            }
        });
    }

    protected function matchesClass(?Student $student, mixed $date, ?AcademicYear $year, array $filters): bool
    {
        if (! isset($filters['classroom_id'])) {
            return true;
        }

        return $this->historicMembership($student, $date, $year)?->classroom_id === (int) $filters['classroom_id'];
    }

    protected function historicClass(?Student $student, mixed $date, ?AcademicYear $year): string
    {
        return $this->historicMembership($student, $date, $year)?->classroom?->name ?? 'Tanpa kelas';
    }

    protected function historicMembership(?Student $student, mixed $date, ?AcademicYear $year): ?StudentClassMembership
    {
        if ($student === null) {
            return null;
        }

        $target = CarbonImmutable::parse($date)->toDateString();

        return $student->classMemberships
            ->filter(fn (StudentClassMembership $membership): bool => ($year === null || $membership->academic_year_id === $year->getKey())
                && $membership->effective_from->toDateString() <= $target
                && ($membership->effective_until === null || $membership->effective_until->toDateString() >= $target))
            ->sortByDesc('effective_from')
            ->first();
    }

    /** @param list<mixed> $values @return array<string, mixed> */
    protected function row(array $values, ?int $badgeIndex = null, string $tone = 'primary'): array
    {
        return [
            'cells' => collect($values)->map(fn (mixed $value, int $index): array => [
                'value' => (string) $value,
                'badge_tone' => $badgeIndex === $index ? $tone : null,
            ])->all(),
            'sort_value' => $badgeIndex !== null ? (int) $values[$badgeIndex] : 0,
        ];
    }

    /** @param list<mixed> $values @return array<string, mixed> */
    protected function datedRow(mixed $date, array $values, ?int $badgeIndex = null, string $tone = 'primary'): array
    {
        return [...$this->row($values, $badgeIndex, $tone), 'sort_date' => CarbonImmutable::parse($date)->timestamp];
    }

    /** @param array{string, int|string, string} $total @param array{string, int|string, string} $active @param array{string, int|string, string} $completed @return array<string, array<string, string>> */
    protected function stats(array $total, array $active, array $completed): array
    {
        return [
            'total' => ['label' => $total[0], 'value' => (string) $total[1], 'sub' => $total[2]],
            'active' => ['label' => $active[0], 'value' => (string) $active[1], 'sub' => $active[2]],
            'completed' => ['label' => $completed[0], 'value' => (string) $completed[1], 'sub' => $completed[2]],
        ];
    }

    protected function initials(?string $name): string
    {
        if (blank($name)) {
            return '—';
        }

        return collect(preg_split('/\s+/u', trim($name)) ?: [])
            ->filter()->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)).'.')->join('');
    }

    protected function maskNisn(string $nisn): string
    {
        $length = mb_strlen($nisn);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return mb_substr($nisn, 0, 2).str_repeat('*', $length - 4).mb_substr($nisn, -2);
    }

    protected function statusTone(string $code): string
    {
        return in_array($code, ['selesai', 'terlaksana', 'terverifikasi'], true) ? 'success'
            : (in_array($code, ['baru', 'dijadwalkan', 'terjadwal'], true) ? 'primary' : 'warning');
    }

    /** @param Collection<int, array<string, mixed>> $rows @param array<string, mixed> $filters */
    protected function paginate(Collection $rows, array $filters): LengthAwarePaginator
    {
        $page = max(1, (int) ($filters['page'] ?? request()->integer('page', 1)));
        $perPage = 20;

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }
}
