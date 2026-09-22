<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\OperationalReportRecap;
use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Policies\ReportPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class OperationalReportRecapService implements OperationalReportRecap
{
    public function __construct(private readonly ReportPolicy $policy) {}

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function paginateForUi(User $actor, array $filters): array
    {
        abort_unless($this->policy->viewAny($actor), 403);

        [$year, $filters] = $this->normalizedFilters($filters);
        $events = $this->eventQuery($actor, $filters, $year)
            ->orderByDesc('service_date')
            ->orderBy('record_type')
            ->orderByDesc('record_id')
            ->paginate((int) $filters['per_page'])
            ->withQueryString();

        $events->setCollection($this->hydrateRows(
            $actor,
            $events->getCollection(),
            $year,
            $events->firstItem() ?? 1,
        ));

        return $this->reportData($actor, $filters, $year, $events);
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function allForDocument(User $actor, array $filters): array
    {
        abort_unless($this->policy->viewAny($actor), 403);

        [$year, $filters] = $this->normalizedFilters($filters);
        $events = $this->eventQuery($actor, $filters, $year)
            ->orderBy('service_date')
            ->orderBy('record_type')
            ->orderBy('record_id')
            ->get();
        $rows = $this->hydrateRows($actor, $events, $year, 1);

        return $this->reportData($actor, $filters, $year, $rows);
    }

    public function findRecord(User $actor, string $type, int $id): BkCase|Consultation
    {
        abort_unless($this->policy->viewAny($actor), 403);

        return match ($type) {
            'case' => $this->caseQuery($actor)->whereKey($id)->firstOrFail(),
            'consultation' => $this->consultationQuery($actor)->whereKey($id)->firstOrFail(),
            default => abort(404),
        };
    }

    /** @return array<string, mixed> */
    public function recordForDocument(BkCase|Consultation $record): array
    {
        return $this->recordRow($record, null, 1);
    }

    /** @param array<string, mixed> $filters @return array{AcademicYear|null, array<string, mixed>} */
    private function normalizedFilters(array $filters): array
    {
        $year = isset($filters['academic_year_id'])
            ? AcademicYear::query()->find((int) $filters['academic_year_id'])
            : null;
        $year ??= AcademicYear::query()->active()->orderByDesc('starts_on')->first();
        $year ??= AcademicYear::query()->orderByDesc('starts_on')->first();

        return [$year, [
            'academic_year_id' => $year?->getKey(),
            'classroom_id' => isset($filters['classroom_id'])
                ? (int) $filters['classroom_id']
                : null,
            'service_type' => $filters['service_type'] ?? 'all',
            'per_page' => (int) ($filters['per_page'] ?? 10),
        ]];
    }

    /** @param array<string, mixed> $filters */
    private function eventQuery(
        User $actor,
        array $filters,
        ?AcademicYear $year,
    ): QueryBuilder {
        $caseEvents = BkCase::query()
            ->accessibleTo($actor)
            ->when(
                $year?->starts_on,
                fn (Builder $query, $date): Builder => $query->whereDate(
                    'cases.service_date',
                    '>=',
                    $date,
                ),
            )
            ->when(
                $year?->ends_on,
                fn (Builder $query, $date): Builder => $query->whereDate(
                    'cases.service_date',
                    '<=',
                    $date,
                ),
            )
            ->when(
                $filters['classroom_id'],
                fn (Builder $query, int $classroomId): Builder => $this->whereHistoricClass(
                    $query,
                    'cases.service_date',
                    $classroomId,
                    $year,
                    [
                        'student.classMemberships',
                        'temporaryStudent.reconciledStudent.classMemberships',
                    ],
                ),
            )
            ->selectRaw("'case' AS record_type")
            ->selectRaw('cases.id AS record_id')
            ->selectRaw('cases.service_date AS service_date')
            ->toBase();

        $consultationEvents = Consultation::query()
            ->accessibleTo($actor)
            ->when(
                $year?->starts_on,
                fn (Builder $query, $date): Builder => $query->whereDate(
                    'consultations.session_date',
                    '>=',
                    $date,
                ),
            )
            ->when(
                $year?->ends_on,
                fn (Builder $query, $date): Builder => $query->whereDate(
                    'consultations.session_date',
                    '<=',
                    $date,
                ),
            )
            ->when(
                $filters['classroom_id'],
                fn (Builder $query, int $classroomId): Builder => $this->whereHistoricClass(
                    $query,
                    'consultations.session_date',
                    $classroomId,
                    $year,
                    [
                        'student.classMemberships',
                        'temporaryStudent.reconciledStudent.classMemberships',
                    ],
                ),
            )
            ->selectRaw("'consultation' AS record_type")
            ->selectRaw('consultations.id AS record_id')
            ->selectRaw('consultations.session_date AS service_date')
            ->toBase();

        $events = match ($filters['service_type']) {
            'case' => $caseEvents,
            'consultation' => $consultationEvents,
            default => $caseEvents->unionAll($consultationEvents),
        };

        return DB::query()->fromSub($events, 'report_records');
    }

    /**
     * @param Collection<int, object> $events
     * @return Collection<int, array<string, mixed>>
     */
    private function hydrateRows(
        User $actor,
        Collection $events,
        ?AcademicYear $year,
        int $firstNumber,
    ): Collection {
        $cases = $this->caseQuery($actor)
            ->whereKey($events->where('record_type', 'case')->pluck('record_id'))
            ->get()
            ->keyBy('id');
        $consultations = $this->consultationQuery($actor)
            ->whereKey($events->where('record_type', 'consultation')->pluck('record_id'))
            ->get()
            ->keyBy('id');

        return $events->values()->map(function (object $event, int $index) use (
            $actor,
            $cases,
            $consultations,
            $year,
            $firstNumber,
        ): array {
            $record = $event->record_type === 'case'
                ? $cases->get((int) $event->record_id)
                : $consultations->get((int) $event->record_id);

            abort_if($record === null, 404);

            return $this->recordRow(
                $record,
                $actor,
                $firstNumber + $index,
                $year,
            );
        });
    }

    /** @return Builder<BkCase> */
    private function caseQuery(User $actor): Builder
    {
        return BkCase::query()
            ->accessibleTo($actor)
            ->with([
                'student.classMemberships.classroom',
                'temporaryStudent.reconciledStudent.classMemberships.classroom',
                'serviceField',
                'status',
                'followUpType',
                'assignments.teacher',
            ]);
    }

    /** @return Builder<Consultation> */
    private function consultationQuery(User $actor): Builder
    {
        return Consultation::query()
            ->accessibleTo($actor)
            ->with([
                'student.classMemberships.classroom',
                'temporaryStudent.reconciledStudent.classMemberships.classroom',
                'serviceField',
                'counselor',
            ]);
    }

    /** @return array<string, mixed> */
    private function recordRow(
        BkCase|Consultation $record,
        ?User $actor,
        int $number,
        ?AcademicYear $year = null,
    ): array {
        $isCase = $record instanceof BkCase;
        $type = $isCase ? 'case' : 'consultation';
        $date = $isCase ? $record->service_date : $record->session_date;

        return [
            'number' => $number,
            'type' => $type,
            'record_id' => $record->getKey(),
            'date' => $date,
            'date_label' => $date->locale('id')->translatedFormat('d M Y'),
            'name' => $record->identityName(),
            'classroom' => $this->classroomName($record, $year),
            'service' => $isCase ? 'Catatan Kasus' : 'Catatan Konsultasi',
            'service_field' => $record->serviceField?->label ?? '—',
            'problem' => $isCase ? $record->initial_info : $record->problem,
            'handling' => $isCase ? $record->initial_action : $record->handling,
            'detail_label' => $isCase ? 'Catatan Penyelesaian' : 'Hasil',
            'detail_note' => $isCase
                ? ($record->resolution_summary ?: '—')
                : ($record->result ?: '—'),
            'follow_up_label' => $isCase
                ? ($record->followUpType?->label ?? $record->status?->label ?? '—')
                : 'Selesai',
            'counselor' => $isCase
                ? ($record->assignments
                    ->where('assignment_type', 'owner')
                    ->sortByDesc(fn ($assignment): string => sprintf(
                        '%s-%010d',
                        $assignment->effective_from->format('Y-m-d'),
                        $assignment->id,
                    ))
                    ->first()?->teacher?->name ?? '—')
                : ($record->counselor?->name ?? '—'),
            'modal_url' => route(
                $isCase ? 'cases.show' : 'consultations.show',
                [$record, 'modal' => 1],
            ),
            'preview_url' => route('reports.records.preview', [
                'type' => $type,
                'id' => $record->getKey(),
            ]),
            'archive_url' => route(
                $isCase ? 'cases.destroy' : 'consultations.destroy',
                $record,
            ),
            'can_archive' => $actor?->can('archive', $record) ?? false,
        ];
    }

    private function classroomName(
        BkCase|Consultation $record,
        ?AcademicYear $year,
    ): string {
        $student = $record->student ?? $record->temporaryStudent?->reconciledStudent;
        if ($student === null) {
            return 'Identitas sementara';
        }

        $date = $record instanceof BkCase ? $record->service_date : $record->session_date;
        $membership = $student->classMemberships
            ->filter(fn (StudentClassMembership $item): bool => (
                $year === null || $item->academic_year_id === $year->getKey()
            ) && $item->effective_from->lte($date)
                && ($item->effective_until === null || $item->effective_until->gte($date)))
            ->sortByDesc(fn (StudentClassMembership $item): string => sprintf(
                '%s-%010d',
                $item->effective_from->format('Y-m-d'),
                $item->id,
            ))
            ->first();

        return $membership?->classroom?->name ?? 'Belum tersedia';
    }

    /**
     * @param LengthAwarePaginator<int, array<string, mixed>>|Collection<int, array<string, mixed>> $rows
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function reportData(
        User $actor,
        array $filters,
        ?AcademicYear $year,
        LengthAwarePaginator|Collection $rows,
    ): array {
        return [
            'title' => 'Laporan Layanan BK',
            'columns' => [
                'No',
                'Nama & Kelas',
                'Layanan',
                'Permasalahan',
                'Penanganan',
                'Aksi',
            ],
            'rows' => $rows,
            'filters' => $filters,
            'filter_options' => [
                'academic_years' => AcademicYear::query()
                    ->orderByDesc('starts_on')
                    ->get(['id', 'name']),
                'classrooms' => $this->accessibleClassrooms($actor, $year)
                    ->active()
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ],
            'academic_year' => $year,
            'generated_by' => $actor->name,
            'generated_at' => now(),
        ];
    }

    /** @return Builder<Classroom> */
    private function accessibleClassrooms(User $actor, ?AcademicYear $year): Builder
    {
        return Classroom::query()
            ->when(
                $year,
                fn (Builder $query, AcademicYear $selected): Builder => $query->where(
                    'academic_year_id',
                    $selected->getKey(),
                ),
            )
            ->whereHas(
                'studentClassMemberships.student',
                fn (Builder $students): Builder => $students->accessibleTo($actor),
            );
    }

    /** @param list<string> $membershipPaths */
    private function whereHistoricClass(
        Builder $query,
        string $dateColumn,
        int $classroomId,
        ?AcademicYear $year,
        array $membershipPaths,
    ): Builder {
        return $query->where(function (Builder $identities) use (
            $dateColumn,
            $classroomId,
            $year,
            $membershipPaths,
        ): void {
            foreach ($membershipPaths as $index => $path) {
                $method = $index === 0 ? 'whereHas' : 'orWhereHas';
                $identities->{$method}(
                    $path,
                    function (Builder $memberships) use (
                        $dateColumn,
                        $classroomId,
                        $year,
                    ): void {
                        $memberships
                            ->where('classroom_id', $classroomId)
                            ->when(
                                $year,
                                fn (Builder $scope, AcademicYear $selected): Builder => $scope
                                    ->where('academic_year_id', $selected->getKey()),
                            )
                            ->whereRaw('DATE(effective_from) <= DATE('.$dateColumn.')')
                            ->where(function (Builder $period) use ($dateColumn): void {
                                $period->whereNull('effective_until')
                                    ->orWhereRaw(
                                        'DATE(effective_until) >= DATE('.$dateColumn.')',
                                    );
                            });
                    },
                );
            }
        });
    }
}
