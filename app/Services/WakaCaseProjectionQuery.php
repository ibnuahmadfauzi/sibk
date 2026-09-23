<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\ReferenceValue;
use App\Models\StudentClassMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final class WakaCaseProjectionQuery
{
    /** @var list<string> */
    private const array SORT_ALLOWLIST = [
        'murid',
        'kelas',
        'bidang',
        'status',
        'guru_bk',
        'tanggal',
    ];

    /**
     * @param  array<string, string|null>  $filters
     * @return Builder<BkCase>
     */
    public function build(User $waka, array $filters = []): Builder
    {
        $query = BkCase::query()
            ->withinStudentServicePeriod()
            ->select([
                'cases.id',
                'cases.service_date',
                'cases.closed_at',
                'cases.student_id',
                'cases.temporary_student_id',
                'cases.service_field_id',
                'cases.status_id',
                'cases.follow_up_type_id',
            ])
            ->with([
                'student:id,name',
                'temporaryStudent:id,input_name',
                'student.classMemberships' => static fn ($memberships) => $memberships
                    ->select([
                        'id',
                        'student_id',
                        'classroom_id',
                        'academic_year_id',
                        'effective_from',
                        'effective_until',
                    ])
                    ->with(['classroom:id,name', 'academicYear:id,starts_on,ends_on'])
                    ->orderByDesc('effective_from')
                    ->orderByDesc('id'),
                'serviceField:id,label',
                'status:id,label,code',
                'followUpType:id,label',
                'assignments' => static fn ($assignments) => $assignments
                    ->select([
                        'id',
                        'case_id',
                        'user_id',
                        'assignment_type',
                        'effective_from',
                        'effective_until',
                    ])
                    ->where('assignment_type', CaseAssignment::TYPE_OWNER)
                    ->with('teacher:id,name')
                    ->latest('effective_from')
                    ->latest('id'),
            ]);

        $query = $this->applyDateFilters($query, $filters)
            ->when($filters['status'] ?? null, static fn (Builder $cases, string $status): Builder => $cases
                ->whereHas('status', static fn (Builder $reference): Builder => $reference->where('code', $status)));

        return $this->applyAllowedSort($query, $filters);
    }

    public function detail(User $waka, int $caseId): BkCase
    {
        return BkCase::query()
            ->accessibleTo($waka)
            ->whereKey($caseId)
            ->select([
                'cases.id', 'cases.student_id', 'cases.temporary_student_id',
                'cases.service_date', 'cases.status_id', 'cases.service_field_id',
                'cases.follow_up_type_id', 'cases.initial_info', 'cases.initial_action',
                'cases.resolution_summary', 'cases.closed_at',
            ])
            ->with([
                'student:id,name',
                'temporaryStudent:id,input_name',
                'student.classMemberships' => static fn ($memberships) => $memberships
                    ->select(['id', 'student_id', 'classroom_id', 'academic_year_id', 'effective_from', 'effective_until'])
                    ->with(['classroom:id,name', 'academicYear:id,starts_on,ends_on'])
                    ->orderByDesc('effective_from')->orderByDesc('id'),
                'serviceField:id,label', 'status:id,label,code', 'followUpType:id,label',
                'assignments' => static fn ($assignments) => $assignments
                    ->select(['id', 'case_id', 'user_id', 'assignment_type', 'effective_from', 'effective_until'])
                    ->where('assignment_type', CaseAssignment::TYPE_OWNER)
                    ->with('teacher:id,name')->latest('effective_from')->latest('id'),
            ])
            ->firstOrFail();
    }

    /**
     * @param  Builder<BkCase>  $query
     * @param  array<string, string|null>  $filters
     * @return Builder<BkCase>
     */
    private function applyDateFilters(Builder $query, array $filters): Builder
    {
        $period = $filters['period'] ?? null;
        if ($period !== null && $period !== '') {
            if (preg_match('/^\d{4}-\d{2}$/', $period) !== 1) {
                throw new InvalidArgumentException('Format periode Waka tidak valid.');
            }

            $start = CarbonImmutable::createFromFormat('!Y-m', $period);
            if ($start === false || $start->format('Y-m') !== $period) {
                throw new InvalidArgumentException('Format periode Waka tidak valid.');
            }

            return $query->whereBetween('cases.service_date', [
                $start->startOfMonth()->toDateString(),
                $start->endOfMonth()->toDateString(),
            ]);
        }

        return $query
            ->when($filters['date_start'] ?? null, static fn (Builder $cases, string $date): Builder => $cases
                ->whereDate('cases.service_date', '>=', $date))
            ->when($filters['date_end'] ?? null, static fn (Builder $cases, string $date): Builder => $cases
                ->whereDate('cases.service_date', '<=', $date));
    }

    /**
     * @param  Builder<BkCase>  $query
     * @param  array<string, string|null>  $filters
     * @return Builder<BkCase>
     */
    private function applyAllowedSort(Builder $query, array $filters): Builder
    {
        $sort = $filters['sort'] ?? 'tanggal';
        $direction = $filters['direction'] ?? 'desc';

        if (! in_array($sort, self::SORT_ALLOWLIST, true)) {
            throw new InvalidArgumentException('Pilihan urutan Waka tidak valid.');
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Arah urutan Waka tidak valid.');
        }

        match ($sort) {
            'murid' => $query->orderByRaw(
                "COALESCE((SELECT name FROM students WHERE students.id = cases.student_id), (SELECT input_name FROM temporary_students WHERE temporary_students.id = cases.temporary_student_id), '') {$direction}",
            ),
            'kelas' => $query->orderBy($this->historicalClassroomName(), $direction),
            'bidang' => $query->orderBy($this->referenceLabel('service_field_id'), $direction),
            'status' => $query->orderBy($this->referenceLabel('status_id'), $direction),
            'guru_bk' => $query->orderBy($this->ownerName(), $direction),
            'tanggal' => $query->orderBy('cases.service_date', $direction),
        };

        return $query->orderBy('cases.id', $direction);
    }

    private function historicalClassroomName(): Builder
    {
        return StudentClassMembership::query()
            ->select('classrooms.name')
            ->join('classrooms', 'classrooms.id', '=', 'student_class_memberships.classroom_id')
            ->join('academic_years', 'academic_years.id', '=', 'student_class_memberships.academic_year_id')
            ->whereColumn('student_class_memberships.student_id', 'cases.student_id')
            ->whereColumn('student_class_memberships.effective_from', '<=', 'cases.service_date')
            ->where(static fn (Builder $membership): Builder => $membership
                ->whereNull('student_class_memberships.effective_until')
                ->orWhereColumn('student_class_memberships.effective_until', '>=', 'cases.service_date'))
            ->where(static fn (Builder $year): Builder => $year
                ->whereNull('academic_years.starts_on')
                ->orWhereColumn('academic_years.starts_on', '<=', 'cases.service_date'))
            ->where(static fn (Builder $year): Builder => $year
                ->whereNull('academic_years.ends_on')
                ->orWhereColumn('academic_years.ends_on', '>=', 'cases.service_date'))
            ->orderByDesc('student_class_memberships.effective_from')
            ->orderByDesc('student_class_memberships.id')
            ->limit(1);
    }

    private function referenceLabel(string $foreignKey): Builder
    {
        return ReferenceValue::query()
            ->select('label')
            ->whereColumn('references.id', "cases.{$foreignKey}")
            ->limit(1);
    }

    private function ownerName(): Builder
    {
        $today = today()->toDateString();

        return CaseAssignment::query()
            ->select('users.name')
            ->join('users', 'users.id', '=', 'case_assignments.user_id')
            ->whereColumn('case_assignments.case_id', 'cases.id')
            ->where('case_assignments.assignment_type', CaseAssignment::TYPE_OWNER)
            ->orderByRaw(
                'CASE WHEN case_assignments.effective_from <= ? AND (case_assignments.effective_until IS NULL OR case_assignments.effective_until >= ?) THEN 0 ELSE 1 END',
                [$today, $today],
            )
            ->orderByDesc('case_assignments.effective_from')
            ->orderByDesc('case_assignments.id')
            ->limit(1);
    }
}
