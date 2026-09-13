<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\ExternalTatibRecord;
use App\Support\ServiceRecordStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class WakaPeriodReportService
{
    /** @return array<string, mixed> */
    public function build(AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $cases = BkCase::query()->whereBetween('service_date', [
            $start->toDateString(),
            $end->toDateString(),
        ]);

        return [
            'metrics' => [
                'served_students' => $this->distinctIdentityCount(clone $cases),
                'cases_recorded' => (clone $cases)->count(),
                'needs_follow_up' => $this->caseCountByStatus(clone $cases, ServiceRecordStatus::NEEDS_FOLLOW_UP),
                'completed' => $this->caseCountByStatus(clone $cases, ServiceRecordStatus::COMPLETED),
            ],
            'service_fields' => $this->referenceCounts(clone $cases, 'service_field_id'),
            'statuses' => $this->referenceCounts(clone $cases, 'status_id', true),
            'student_affairs' => $this->studentAffairsCounts($start, $end),
            'classes' => $this->classRows($year, $start, $end),
        ];
    }

    /** @param Builder<BkCase> $cases */
    private function distinctIdentityCount(Builder $cases): int
    {
        $official = (clone $cases)->whereNotNull('student_id')->distinct()->count('student_id');
        $temporary = (clone $cases)->whereNotNull('temporary_student_id')->distinct()->count('temporary_student_id');

        return $official + $temporary;
    }

    /** @param Builder<BkCase> $cases */
    private function caseCountByStatus(Builder $cases, string $status): int
    {
        return $cases->whereHas('status', static fn (Builder $reference): Builder => $reference
            ->where('code', $status))->count();
    }

    /**
     * @param  Builder<BkCase>  $cases
     * @return list<array{code: string, label: string, count: int}>
     */
    private function referenceCounts(Builder $cases, string $foreignKey, bool $includeZeroStatuses = false): array
    {
        $counts = $cases
            ->join('references as aggregate_reference', 'aggregate_reference.id', '=', "cases.{$foreignKey}")
            ->selectRaw('aggregate_reference.code, aggregate_reference.label, COUNT(*) as aggregate_count')
            ->groupBy('aggregate_reference.code', 'aggregate_reference.label', 'aggregate_reference.sort_order')
            ->orderBy('aggregate_reference.sort_order')
            ->get()
            ->map(static fn (BkCase $row): array => [
                'code' => (string) $row->getAttribute('code'),
                'label' => (string) $row->getAttribute('label'),
                'count' => (int) $row->getAttribute('aggregate_count'),
            ])
            ->keyBy('code');

        if (! $includeZeroStatuses) {
            return $counts->values()->all();
        }

        return collect(ServiceRecordStatus::labels())->map(
            static fn (string $label, string $code): array => $counts->get($code, [
                'code' => $code,
                'label' => $label,
                'count' => 0,
            ]),
        )->values()->all();
    }

    /** @return array{violations: int, linked_students: int, verified_achievements: int} */
    private function studentAffairsCounts(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $etatib = ExternalTatibRecord::query()->active()->whereBetween('occurred_at', [
            $start->startOfDay(),
            $end->endOfDay(),
        ]);
        $achievements = Achievement::query()
            ->whereBetween('achievement_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('verificationStatus', static fn (Builder $status): Builder => $status
                ->where('code', 'terverifikasi'));

        return [
            'violations' => (clone $etatib)->count(),
            'linked_students' => (clone $etatib)->whereNotNull('student_id')->distinct()->count('student_id'),
            'verified_achievements' => $achievements->count(),
        ];
    }

    /**
     * @return list<array{classroom: string, served_students: int, active_cases: int, completed: int, needs_follow_up: int}>
     */
    private function classRows(AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return BkCase::query()
            ->join('student_class_memberships as recap_memberships', function ($join): void {
                $join->on('recap_memberships.student_id', '=', 'cases.student_id')
                    ->whereColumn('recap_memberships.effective_from', '<=', 'cases.service_date')
                    ->where(function ($period): void {
                        $period->whereNull('recap_memberships.effective_until')
                            ->orWhereColumn('recap_memberships.effective_until', '>=', 'cases.service_date');
                    });
            })
            ->join('classrooms as recap_classrooms', 'recap_classrooms.id', '=', 'recap_memberships.classroom_id')
            ->join('references as recap_status', 'recap_status.id', '=', 'cases.status_id')
            ->where('recap_memberships.academic_year_id', $year->getKey())
            ->whereBetween('cases.service_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('recap_classrooms.name as classroom')
            ->selectRaw('COUNT(DISTINCT cases.student_id) as served_students')
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN recap_status.code NOT IN (?, ?) THEN cases.id END) as active_cases',
                ServiceRecordStatus::terminalCodes(),
            )
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN recap_status.code = ? THEN cases.id END) as completed',
                [ServiceRecordStatus::COMPLETED],
            )
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN recap_status.code = ? THEN cases.id END) as needs_follow_up',
                [ServiceRecordStatus::NEEDS_FOLLOW_UP],
            )
            ->groupBy('recap_classrooms.id', 'recap_classrooms.name')
            ->orderBy('recap_classrooms.name')
            ->get()
            ->map(static fn (BkCase $row): array => [
                'classroom' => (string) $row->getAttribute('classroom'),
                'served_students' => (int) $row->getAttribute('served_students'),
                'active_cases' => (int) $row->getAttribute('active_cases'),
                'completed' => (int) $row->getAttribute('completed'),
                'needs_follow_up' => (int) $row->getAttribute('needs_follow_up'),
            ])
            ->all();
    }
}
