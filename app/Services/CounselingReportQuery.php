<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Consultation;
use App\Models\FollowUp;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class CounselingReportQuery extends LegacyReportQuery
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
            ReportService::TYPE_CONSULTATIONS => $paginate
                ? $this->paginatedConsultations($user, $filters, $year, $start, $end)
                : $this->consultations($user, $filters, $year, $start, $end),
            ReportService::TYPE_FOLLOW_UPS => $paginate
                ? $this->paginatedFollowUps($user, $filters, $year, $start, $end)
                : $this->followUps($user, $filters, $year, $start, $end),
            ReportService::TYPE_SERVICE_RECAP => $paginate
                ? $this->paginatedServiceRecap($user, $filters, $year, $start, $end)
                : $this->serviceRecap($user, $filters, $year, $start, $end),
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
        return match ($type) {
            ReportService::TYPE_CONSULTATIONS => [
                'id' => $type,
                'columns' => ['Nomor Sesi', 'Inisial Murid', 'Kelas', 'Bidang Layanan', 'Tanggal', 'Guru BK', 'Status'],
                'rows' => $this->consultationReportQuery($user, $filters, $year, $start, $end)
                    ->latest('session_date')->latest('id')->lazy(500)
                    ->map(fn (Consultation $consultation): array => $this->row([
                        $consultation->registration_number,
                        $this->initials($consultation->identityName()),
                        $this->historicClass($this->consultationStudent($consultation), $consultation->session_date, $year),
                        $consultation->serviceField->label,
                        $consultation->session_date->locale('id')->translatedFormat('d M Y'),
                        $consultation->counselor->name,
                        $consultation->status->label,
                    ], 6, $this->statusTone($consultation->status->code))),
            ],
            ReportService::TYPE_FOLLOW_UPS => [
                'id' => $type,
                'columns' => ['Nomor Kasus', 'Inisial Murid', 'Kelas', 'Bentuk Tindak Lanjut', 'Tanggal Rencana', 'Tanggal Pelaksanaan', 'Status'],
                'rows' => $this->followUpReportQuery($user, $filters, $year, $start, $end)
                    ->latest('planned_date')->latest('id')->lazy(500)
                    ->map(fn (FollowUp $followUp): array => $this->row([
                        $followUp->case->registration_number,
                        $this->initials($followUp->case->identityName()),
                        $this->historicClass($this->caseStudent($followUp->case), $followUp->planned_date, $year),
                        $followUp->type->label,
                        $followUp->planned_date->locale('id')->translatedFormat('d M Y'),
                        $followUp->execution_date?->locale('id')->translatedFormat('d M Y') ?? '—',
                        $followUp->status->label,
                    ], 6, $this->statusTone($followUp->status->code))),
            ],
            ReportService::TYPE_SERVICE_RECAP => $this->serviceRecapExport($user, $filters, $year, $start, $end),
            default => abort(404),
        };
    }

    /** @return array{columns: list<string>, rows: Collection<int, array<string, mixed>>, stats: array<string, array<string, string>>} */
    protected function consultations(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $query = Consultation::query()->accessibleTo($user)
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->with(['student.classMemberships.classroom', 'temporaryStudent.reconciledStudent.classMemberships.classroom', 'serviceField', 'status', 'counselor']);
        $this->applyCommonServiceFilters($query, $filters);
        $this->applyHistoricClassFilter($query, $filters, $year, 'consultations.session_date', [
            'student.classMemberships',
            'temporaryStudent.reconciledStudent.classMemberships',
        ]);
        $items = $query->get()->filter(fn (Consultation $consultation): bool => $this->matchesClass(
            $this->consultationStudent($consultation),
            $consultation->session_date,
            $year,
            $filters,
        ));
        $rows = $items->sortByDesc('session_date')->values()->map(fn (Consultation $consultation): array => $this->row([
            $consultation->registration_number,
            $this->initials($consultation->identityName()),
            $this->historicClass($this->consultationStudent($consultation), $consultation->session_date, $year),
            $consultation->serviceField->label,
            $consultation->session_date->locale('id')->translatedFormat('d M Y'),
            $consultation->counselor->name,
            $consultation->status->label,
        ], 6, $this->statusTone($consultation->status->code)));

        return [
            'columns' => ['Nomor Sesi', 'Inisial Murid', 'Kelas', 'Bidang Layanan', 'Tanggal', 'Guru BK', 'Status'],
            'rows' => $rows,
            'stats' => $this->stats(
                ['Total Konsultasi', $items->count(), 'Tanpa isi sensitif'],
                ['Terlaksana', $items->where('status.code', 'terlaksana')->count(), 'Sesi selesai'],
                ['Terjadwal', $items->whereIn('status.code', ['dijadwalkan', 'menunggu_konfirmasi'])->count(), 'Menunggu pelaksanaan'],
            ),
        ];
    }

    /** @return array{columns: list<string>, rows: LengthAwarePaginator, stats: array<string, array<string, string>>} */
    protected function paginatedConsultations(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $query = $this->consultationReportQuery($user, $filters, $year, $start, $end);
        $total = (clone $query)->count();
        $completed = (clone $query)->whereHas('status', fn (Builder $statuses): Builder => $statuses->where('code', 'terlaksana'))->count();
        $scheduled = (clone $query)->whereHas('status', fn (Builder $statuses): Builder => $statuses->whereIn('code', ['dijadwalkan', 'menunggu_konfirmasi']))->count();
        $page = $query->latest('session_date')->latest('id')->paginate(20)->withQueryString();
        $page->setCollection($page->getCollection()->map(fn (Consultation $consultation): array => $this->row([
            $consultation->registration_number,
            $this->initials($consultation->identityName()),
            $this->historicClass($this->consultationStudent($consultation), $consultation->session_date, $year),
            $consultation->serviceField->label,
            $consultation->session_date->locale('id')->translatedFormat('d M Y'),
            $consultation->counselor->name,
            $consultation->status->label,
        ], 6, $this->statusTone($consultation->status->code))));

        return [
            'columns' => ['Nomor Sesi', 'Inisial Murid', 'Kelas', 'Bidang Layanan', 'Tanggal', 'Guru BK', 'Status'],
            'rows' => $page,
            'stats' => $this->stats(
                ['Total Konsultasi', $total, 'Tanpa isi sensitif'],
                ['Terlaksana', $completed, 'Sesi selesai'],
                ['Terjadwal', $scheduled, 'Menunggu pelaksanaan'],
            ),
        ];
    }

    /** @return Builder<Consultation> */
    protected function consultationReportQuery(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        $query = Consultation::query()->accessibleTo($user)
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->with(['student.classMemberships.classroom', 'temporaryStudent.reconciledStudent.classMemberships.classroom', 'serviceField', 'status', 'counselor']);
        $this->applyCommonServiceFilters($query, $filters);
        $this->applyHistoricClassFilter($query, $filters, $year, 'consultations.session_date', [
            'student.classMemberships',
            'temporaryStudent.reconciledStudent.classMemberships',
        ]);

        return $query;
    }

    /** @return array{columns: list<string>, rows: Collection<int, array<string, mixed>>, stats: array<string, array<string, string>>} */
    protected function followUps(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $query = FollowUp::query()->whereBetween('planned_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('case', fn (Builder $cases): Builder => $cases->accessibleTo($user))
            ->with(['case.student.classMemberships.classroom', 'case.temporaryStudent.reconciledStudent.classMemberships.classroom', 'case.temporaryStudent', 'type', 'status']);
        $query->when($filters['status_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('status_id', $id));
        $query->when($filters['student_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->whereHas('case', fn (Builder $cases): Builder => $cases->where('student_id', $id)));
        $query->when($filters['service_field_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->whereHas('case', fn (Builder $cases): Builder => $cases->where('service_field_id', $id)));
        $this->applyHistoricClassFilter($query, $filters, $year, 'follow_ups.planned_date', [
            'case.student.classMemberships',
            'case.temporaryStudent.reconciledStudent.classMemberships',
        ]);
        $items = $query->get()->filter(fn (FollowUp $followUp): bool => $this->matchesClass(
            $this->caseStudent($followUp->case),
            $followUp->planned_date,
            $year,
            $filters,
        ));
        $rows = $items->sortByDesc('planned_date')->values()->map(fn (FollowUp $followUp): array => $this->row([
            $followUp->case->registration_number,
            $this->initials($followUp->case->identityName()),
            $this->historicClass($this->caseStudent($followUp->case), $followUp->planned_date, $year),
            $followUp->type->label,
            $followUp->planned_date->locale('id')->translatedFormat('d M Y'),
            $followUp->execution_date?->locale('id')->translatedFormat('d M Y') ?? '—',
            $followUp->status->label,
        ], 6, $this->statusTone($followUp->status->code)));

        return [
            'columns' => ['Nomor Kasus', 'Inisial Murid', 'Kelas', 'Bentuk Tindak Lanjut', 'Tanggal Rencana', 'Tanggal Pelaksanaan', 'Status'],
            'rows' => $rows,
            'stats' => $this->stats(
                ['Total Tindak Lanjut', $items->count(), 'Periode terpilih'],
                ['Perlu Pelaksanaan', $items->whereNotIn('status.code', ['terlaksana', 'dibatalkan'])->count(), 'Belum terlaksana'],
                ['Terlaksana', $items->where('status.code', 'terlaksana')->count(), 'Selesai dilaksanakan'],
            ),
        ];
    }

    /** @return array{columns: list<string>, rows: LengthAwarePaginator, stats: array<string, array<string, string>>} */
    protected function paginatedFollowUps(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $query = $this->followUpReportQuery($user, $filters, $year, $start, $end);
        $total = (clone $query)->count();
        $completed = (clone $query)->whereHas('status', fn (Builder $statuses): Builder => $statuses->where('code', 'terlaksana'))->count();
        $pending = (clone $query)->whereHas('status', fn (Builder $statuses): Builder => $statuses->whereNotIn('code', ['terlaksana', 'dibatalkan']))->count();
        $page = $query->latest('planned_date')->latest('id')->paginate(20)->withQueryString();
        $page->setCollection($page->getCollection()->map(fn (FollowUp $followUp): array => $this->row([
            $followUp->case->registration_number,
            $this->initials($followUp->case->identityName()),
            $this->historicClass($this->caseStudent($followUp->case), $followUp->planned_date, $year),
            $followUp->type->label,
            $followUp->planned_date->locale('id')->translatedFormat('d M Y'),
            $followUp->execution_date?->locale('id')->translatedFormat('d M Y') ?? 'â€”',
            $followUp->status->label,
        ], 6, $this->statusTone($followUp->status->code))));

        return [
            'columns' => ['Nomor Kasus', 'Inisial Murid', 'Kelas', 'Bentuk Tindak Lanjut', 'Tanggal Rencana', 'Tanggal Pelaksanaan', 'Status'],
            'rows' => $page,
            'stats' => $this->stats(
                ['Total Tindak Lanjut', $total, 'Periode terpilih'],
                ['Perlu Pelaksanaan', $pending, 'Belum terlaksana'],
                ['Terlaksana', $completed, 'Selesai dilaksanakan'],
            ),
        ];
    }

    /** @return Builder<FollowUp> */
    protected function followUpReportQuery(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        $query = FollowUp::query()->whereBetween('planned_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('case', fn (Builder $cases): Builder => $cases->accessibleTo($user))
            ->with(['case.student.classMemberships.classroom', 'case.temporaryStudent.reconciledStudent.classMemberships.classroom', 'case.temporaryStudent', 'type', 'status']);
        $query->when($filters['status_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('status_id', $id));
        $query->when($filters['student_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->whereHas('case', fn (Builder $cases): Builder => $cases->where('student_id', $id)));
        $query->when($filters['service_field_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->whereHas('case', fn (Builder $cases): Builder => $cases->where('service_field_id', $id)));
        $this->applyHistoricClassFilter($query, $filters, $year, 'follow_ups.planned_date', [
            'case.student.classMemberships',
            'case.temporaryStudent.reconciledStudent.classMemberships',
        ]);

        return $query;
    }

    /** @return array{columns: list<string>, rows: Collection<int, array<string, mixed>>, stats: array<string, array<string, string>>} */
    protected function serviceRecap(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $caseQuery = BkCase::query()->accessibleTo($user)
            ->whereBetween('service_date', [$start->toDateString(), $end->toDateString()])
            ->with(['student.classMemberships.classroom', 'temporaryStudent.reconciledStudent.classMemberships.classroom', 'temporaryStudent', 'serviceField', 'status', 'creator']);
        $caseQuery->when($filters['student_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('student_id', $id));
        $caseQuery->when($filters['service_field_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('service_field_id', $id));
        $caseQuery->when($filters['status_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('status_id', $id));
        $caseQuery->when($filters['counselor_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('created_by', $id));
        $this->applyHistoricClassFilter($caseQuery, $filters, $year, 'cases.service_date', [
            'student.classMemberships',
            'temporaryStudent.reconciledStudent.classMemberships',
        ]);
        $cases = $caseQuery->get()->filter(fn (BkCase $case): bool => $this->matchesClass($this->caseStudent($case), $case->service_date, $year, $filters));

        $consultations = collect();
        if ($this->policy->viewType($user, ReportService::TYPE_CONSULTATIONS)) {
            $consultationQuery = Consultation::query()->accessibleTo($user)
                ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
                ->with(['student.classMemberships.classroom', 'temporaryStudent.reconciledStudent.classMemberships.classroom', 'serviceField', 'status', 'counselor']);
            $this->applyCommonServiceFilters($consultationQuery, $filters);
            $this->applyHistoricClassFilter($consultationQuery, $filters, $year, 'consultations.session_date', [
                'student.classMemberships',
                'temporaryStudent.reconciledStudent.classMemberships',
            ]);
            $consultations = $consultationQuery->get()->filter(fn (Consultation $consultation): bool => $this->matchesClass(
                $this->consultationStudent($consultation),
                $consultation->session_date,
                $year,
                $filters,
            ));
        }
        $caseRows = $cases->map(fn (BkCase $case): array => $this->datedRow($case->service_date, [
            $case->registration_number,
            'Kasus BK',
            $this->initials($case->identityName()),
            $this->historicClass($this->caseStudent($case), $case->service_date, $year),
            $case->serviceField->label,
            $case->status->label,
            $case->service_date->locale('id')->translatedFormat('d M Y'),
            $case->creator->name,
        ], 5, $this->statusTone($case->status->code)));
        $consultationRows = $consultations->map(fn (Consultation $consultation): array => $this->datedRow($consultation->session_date, [
            $consultation->registration_number,
            'Konsultasi',
            $this->initials($consultation->identityName()),
            $this->historicClass($this->consultationStudent($consultation), $consultation->session_date, $year),
            $consultation->serviceField->label,
            $consultation->status->label,
            $consultation->session_date->locale('id')->translatedFormat('d M Y'),
            $consultation->counselor->name,
        ], 5, $this->statusTone($consultation->status->code)));
        $rows = $caseRows->concat($consultationRows)->sortByDesc('sort_date')->values();

        return [
            'columns' => ['Nomor', 'Jenis Layanan', 'Inisial Murid', 'Kelas', 'Bidang Layanan', 'Status', 'Tanggal', 'Guru BK'],
            'rows' => $rows,
            'stats' => $this->stats(
                ['Jumlah Layanan', $rows->count(), 'Kasus dan konsultasi umum'],
                ['Kasus BK', $cases->count(), 'Sesuai kewenangan'],
                ['Konsultasi', $consultations->count(), 'Tanpa isi sensitif'],
            ),
        ];
    }

    /** @return array{columns: list<string>, rows: LengthAwarePaginator, stats: array<string, array<string, string>>} */
    protected function paginatedServiceRecap(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $caseQuery = $this->caseRecapQuery($user, $filters, $year, $start, $end);
        $consultationQuery = $this->policy->viewType($user, ReportService::TYPE_CONSULTATIONS)
            ? $this->consultationReportQuery($user, $filters, $year, $start, $end)
            : null;
        $caseCount = (clone $caseQuery)->count();
        $consultationCount = $consultationQuery === null ? 0 : (clone $consultationQuery)->count();
        $pageNumber = max(1, (int) ($filters['page'] ?? request()->integer('page', 1)));
        $window = $pageNumber * 20;
        $caseRows = $caseQuery->latest('service_date')->latest('id')->limit($window)->get()->map(
            fn (BkCase $case): array => $this->datedRow($case->service_date, [
                $case->registration_number,
                'Kasus BK',
                $this->initials($case->identityName()),
                $this->historicClass($this->caseStudent($case), $case->service_date, $year),
                $case->serviceField->label,
                $case->status->label,
                $case->service_date->locale('id')->translatedFormat('d M Y'),
                $case->creator->name,
            ], 5, $this->statusTone($case->status->code)),
        );
        $consultationRows = $consultationQuery?->latest('session_date')->latest('id')->limit($window)->get()->map(
            fn (Consultation $consultation): array => $this->datedRow($consultation->session_date, [
                $consultation->registration_number,
                'Konsultasi',
                $this->initials($consultation->identityName()),
                $this->historicClass($this->consultationStudent($consultation), $consultation->session_date, $year),
                $consultation->serviceField->label,
                $consultation->status->label,
                $consultation->session_date->locale('id')->translatedFormat('d M Y'),
                $consultation->counselor->name,
            ], 5, $this->statusTone($consultation->status->code)),
        ) ?? collect();
        $rows = $caseRows->concat($consultationRows)->sortByDesc('sort_date')->values();
        $paginator = new LengthAwarePaginator(
            $rows->forPage($pageNumber, 20)->values(),
            $caseCount + $consultationCount,
            20,
            $pageNumber,
            ['path' => request()->url(), 'query' => request()->query()],
        );

        return [
            'columns' => ['Nomor', 'Jenis Layanan', 'Inisial Murid', 'Kelas', 'Bidang Layanan', 'Status', 'Tanggal', 'Guru BK'],
            'rows' => $paginator,
            'stats' => $this->stats(
                ['Jumlah Layanan', $caseCount + $consultationCount, 'Kasus dan konsultasi umum'],
                ['Kasus BK', $caseCount, 'Sesuai kewenangan'],
                ['Konsultasi', $consultationCount, 'Tanpa isi sensitif'],
            ),
        ];
    }

    /** @return Builder<BkCase> */
    protected function caseRecapQuery(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        $query = BkCase::query()->accessibleTo($user)
            ->whereBetween('service_date', [$start->toDateString(), $end->toDateString()])
            ->with(['student.classMemberships.classroom', 'temporaryStudent.reconciledStudent.classMemberships.classroom', 'temporaryStudent', 'serviceField', 'status', 'creator']);
        $query->when($filters['student_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('student_id', $id));
        $query->when($filters['service_field_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('service_field_id', $id));
        $query->when($filters['status_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('status_id', $id));
        $query->when($filters['counselor_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('created_by', $id));
        $this->applyHistoricClassFilter($query, $filters, $year, 'cases.service_date', [
            'student.classMemberships',
            'temporaryStudent.reconciledStudent.classMemberships',
        ]);

        return $query;
    }

    /** @return array{id: string, columns: list<string>, rows: iterable<int, array<string, mixed>>} */
    protected function serviceRecapExport(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $caseRows = $this->caseRecapQuery($user, $filters, $year, $start, $end)->latest('service_date')->latest('id')->lazy(500)->map(
            fn (BkCase $case): array => $this->datedRow($case->service_date, [
                $case->registration_number, 'Kasus BK', $this->initials($case->identityName()),
                $this->historicClass($this->caseStudent($case), $case->service_date, $year),
                $case->serviceField->label, $case->status->label,
                $case->service_date->locale('id')->translatedFormat('d M Y'), $case->creator->name,
            ], 5, $this->statusTone($case->status->code)),
        );
        $consultationRows = collect();
        if ($this->policy->viewType($user, ReportService::TYPE_CONSULTATIONS)) {
            $consultationRows = $this->consultationReportQuery($user, $filters, $year, $start, $end)->latest('session_date')->latest('id')->lazy(500)->map(
                fn (Consultation $consultation): array => $this->datedRow($consultation->session_date, [
                    $consultation->registration_number, 'Konsultasi', $this->initials($consultation->identityName()),
                    $this->historicClass($this->consultationStudent($consultation), $consultation->session_date, $year),
                    $consultation->serviceField->label, $consultation->status->label,
                    $consultation->session_date->locale('id')->translatedFormat('d M Y'), $consultation->counselor->name,
                ], 5, $this->statusTone($consultation->status->code)),
            );
        }

        return [
            'id' => ReportService::TYPE_SERVICE_RECAP,
            'columns' => ['Nomor', 'Jenis Layanan', 'Inisial Murid', 'Kelas', 'Bidang Layanan', 'Status', 'Tanggal', 'Guru BK'],
            'rows' => $caseRows->concat($consultationRows),
        ];
    }

    /** @param Builder<Consultation> $query */
    protected function applyCommonServiceFilters(Builder $query, array $filters): void
    {
        $query->when($filters['student_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('student_id', $id));
        $query->when($filters['service_field_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('service_field_id', $id));
        $query->when($filters['status_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('status_id', $id));
        $query->when($filters['counselor_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('counselor_id', $id));
    }

    protected function caseStudent(BkCase $case): ?Student
    {
        return $case->student ?? $case->temporaryStudent?->reconciledStudent;
    }

    protected function consultationStudent(Consultation $consultation): ?Student
    {
        return $consultation->student ?? $consultation->temporaryStudent?->reconciledStudent;
    }
}
