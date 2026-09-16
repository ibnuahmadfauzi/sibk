<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use App\Policies\ReportPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class LegacyReportAdapter
{
    public function __construct(
        private readonly ReportPolicy $policy,
        private readonly ViolationReportQuery $violations,
        private readonly CounselingReportQuery $counseling,
        private readonly AchievementReportQuery $achievements,
    ) {}

    /** @return list<array<string, mixed>> */
    public function catalogFor(User $user): array
    {
        return collect($this->catalog())
            ->filter(fn (array $report): bool => $this->policy->viewType($user, $report['id']))
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function build(User $user, array $filters, bool $paginate = true): array
    {
        $type = (string) ($filters['type'] ?? ReportService::TYPE_SERVICE_RECAP);
        abort_unless($this->policy->viewType($user, $type), 403);
        $definition = collect($this->catalog())->firstWhere('id', $type);
        abort_if($definition === null, 404);
        [$year, $start, $end] = $this->period($filters);
        $filters['academic_year_id'] = $year?->getKey();
        $filters['date_start'] = $start->toDateString();
        $filters['date_end'] = $end->toDateString();
        $data = $this->queryFor($type)->build($type, $user, $filters, $year, $start, $end, $paginate);

        return [
            ...$definition,
            'columns' => $data['columns'],
            'stats' => $data['stats'],
            'rows' => $data['rows'],
            'filters' => $filters,
            'filter_options' => $this->filterOptions($user, $type, $year),
            'academic_year' => $year,
            'period_start' => $start,
            'period_end' => $end,
            'generated_by' => $user->name,
            'generated_at' => now(),
        ];
    }

    /** @param array<string, mixed> $filters @return array{id: string, columns: list<string>, rows: iterable<int, array<string, mixed>>} */
    public function exportRows(User $user, array $filters): array
    {
        $type = (string) ($filters['type'] ?? ReportService::TYPE_SERVICE_RECAP);
        abort_unless($this->policy->viewType($user, $type), 403);
        [$year, $start, $end] = $this->period($filters);
        $filters['academic_year_id'] = $year?->getKey();
        $filters['date_start'] = $start->toDateString();
        $filters['date_end'] = $end->toDateString();

        return $this->queryFor($type)->export($type, $user, $filters, $year, $start, $end);
    }

    private function queryFor(string $type): LegacyReportQuery
    {
        return match ($type) {
            ReportService::TYPE_STUDENT_VIOLATIONS,
            ReportService::TYPE_CLASS_VIOLATIONS,
            ReportService::TYPE_VIOLATION_POINTS => $this->violations,
            ReportService::TYPE_CONSULTATIONS,
            ReportService::TYPE_FOLLOW_UPS,
            ReportService::TYPE_SERVICE_RECAP => $this->counseling,
            ReportService::TYPE_ACHIEVEMENTS => $this->achievements,
            default => abort(404),
        };
    }

    /** @param array<string, mixed> $filters @return array{AcademicYear|null, CarbonImmutable, CarbonImmutable} */
    private function period(array $filters): array
    {
        $year = isset($filters['academic_year_id'])
            ? AcademicYear::query()->find($filters['academic_year_id'])
            : AcademicYear::query()->where('is_active', true)->orderByDesc('starts_on')->first();
        $year ??= AcademicYear::query()->orderByDesc('starts_on')->first();
        $start = CarbonImmutable::parse($filters['date_start'] ?? $year?->starts_on?->toDateString() ?? now()->startOfYear()->toDateString());
        $end = CarbonImmutable::parse($filters['date_end'] ?? $year?->ends_on?->toDateString() ?? now()->endOfYear()->toDateString());

        return [$year, $start->startOfDay(), $end->endOfDay()];
    }

    /** @return array<string, Collection<int, mixed>> */
    private function filterOptions(User $user, string $type, ?AcademicYear $year): array
    {
        $students = Student::query()->active()->accessibleTo($user)->orderBy('name')->get()
            ->map(fn (Student $student): array => ['id' => $student->id, 'label' => $this->violations->studentLabel($student)]);
        $classrooms = Classroom::query()->active()
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query->where('academic_year_id', $selected->getKey()))
            ->whereHas('studentClassMemberships.student', fn (Builder $students): Builder => $students->accessibleTo($user))
            ->orderBy('name')->get();
        $statusCategories = match ($type) {
            ReportService::TYPE_CONSULTATIONS => ['consultation_status'],
            ReportService::TYPE_FOLLOW_UPS => ['follow_up_status'],
            ReportService::TYPE_SERVICE_RECAP => ['case_status', 'consultation_status'],
            ReportService::TYPE_ACHIEVEMENTS => ['achievement_verification_status'],
            default => [],
        };

        return [
            'academic_years' => AcademicYear::query()->orderByDesc('starts_on')->get(),
            'classrooms' => $classrooms,
            'students' => $students,
            'categories' => $this->violations->categories($user),
            'service_fields' => ReferenceValue::query()->active()->forCategory('service_field')->orderBy('sort_order')->get(),
            'statuses' => ReferenceValue::query()->active()->whereIn('category', $statusCategories)->orderBy('sort_order')->get(),
            'achievement_types' => ReferenceValue::query()->active()->forCategory('achievement_type')->orderBy('sort_order')->get(),
            'achievement_levels' => ReferenceValue::query()->active()->forCategory('achievement_level')->orderBy('sort_order')->get(),
            'counselors' => $user->hasRole('koordinator_bk')
                ? User::query()->active()->whereHas('roles', fn (Builder $roles): Builder => $roles->where('slug', 'guru_bk')->where('is_active', true))->orderBy('name')->get()
                : collect(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function catalog(): array
    {
        return [
            ['id' => ReportService::TYPE_STUDENT_VIOLATIONS, 'title' => 'Pelanggaran per Murid', 'description' => 'Riwayat pelanggaran per murid', 'badge' => 'Murid', 'tone' => 'warning', 'icon' => 'student', 'filter_keys' => ['period', 'classroom', 'student', 'category']],
            ['id' => ReportService::TYPE_CLASS_VIOLATIONS, 'title' => 'Pelanggaran per Kelas', 'description' => 'Ringkasan pelanggaran per kelas', 'badge' => 'Kelas', 'tone' => 'info', 'icon' => 'classroom', 'filter_keys' => ['period', 'classroom', 'category']],
            ['id' => ReportService::TYPE_VIOLATION_POINTS, 'title' => 'Poin Pelanggaran', 'description' => 'Rekap poin dalam periode', 'badge' => 'Poin', 'tone' => 'primary', 'icon' => 'points', 'filter_keys' => ['period', 'classroom', 'student', 'minimum_points']],
            ['id' => ReportService::TYPE_CONSULTATIONS, 'title' => 'Konsultasi', 'description' => 'Rekap konsultasi tanpa isi sensitif', 'badge' => 'Layanan', 'tone' => 'success', 'icon' => 'consultation', 'filter_keys' => ['period', 'classroom', 'student', 'service_field', 'status', 'counselor']],
            ['id' => ReportService::TYPE_FOLLOW_UPS, 'title' => 'Status Tindak Lanjut', 'description' => 'Pemantauan status tindak lanjut', 'badge' => 'Tindak Lanjut', 'tone' => 'warning', 'icon' => 'follow-up', 'filter_keys' => ['period', 'classroom', 'student', 'service_field', 'status']],
            ['id' => ReportService::TYPE_SERVICE_RECAP, 'title' => 'Rekap Layanan BK', 'description' => 'Ringkasan layanan BK yang diizinkan', 'badge' => 'Rekap', 'tone' => 'primary', 'icon' => 'recap', 'filter_keys' => ['period', 'classroom', 'student', 'service_field', 'status', 'counselor']],
            ['id' => ReportService::TYPE_ACHIEVEMENTS, 'title' => 'Prestasi', 'description' => 'Rekap prestasi minimum sesuai kewenangan', 'badge' => 'Prestasi', 'tone' => 'info', 'icon' => 'achievement', 'filter_keys' => ['period', 'classroom', 'student', 'achievement_type', 'achievement_level', 'status']],
        ];
    }
}
