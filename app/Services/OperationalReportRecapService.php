<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\OperationalReportRecap;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Policies\ReportPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

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
                        ->whereColumn('effective_from', '<=', $dateColumn)
                        ->where(function (Builder $period) use ($dateColumn): void {
                            $period->whereNull('effective_until')
                                ->orWhereColumn('effective_until', '>=', $dateColumn);
                        });
                });
            }
        });
    }
}
