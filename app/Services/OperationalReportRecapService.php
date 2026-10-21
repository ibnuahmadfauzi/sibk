<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\OperationalReportRecap;
use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\User;
use App\Models\WithdrawalProgress;
use App\Policies\ReportPolicy;
use App\Support\StudentName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OperationalReportRecapService implements OperationalReportRecap
{
    public function __construct(private readonly ReportPolicy $policy) {}

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function paginateForUi(User $actor, array $filters): array
    {
        abort_unless($this->policy->viewAny($actor), 403);

        [$year, $filters, $period] = $this->normalizedFilters($filters);
        $eventQuery = $this->eventQuery($actor, $filters, $year, $period);
        $summary = $this->summary($eventQuery, $filters['service_type']);
        $events = $eventQuery
            ->orderBy('service_date', ($filters['sort'] ?? null) === 'tanggal' && ($filters['direction'] ?? null) === 'asc' ? 'asc' : 'desc')
            ->orderBy('record_type')
            ->orderByDesc('record_id')
            ->paginate((int) $filters['per_page'])
            ->appends(Arr::except(request()->query(), ['semester', 'date_from', 'date_to']));

        $rows = $this->hydrateRows(
            $actor,
            $events->getCollection(),
            $year,
            $events->firstItem() ?? 1,
        );

        if ($this->policy->viewDocument($actor) === false) {
            $rows = $rows->map(static fn (array $row): array => Arr::only($row, [
                'number',
                'day_label',
                'date_label',
                'name',
                'classroom',
                'service',
                'service_field',
                'detail_note',
                'counselor',
                'follow_up_label',
            ]));
        }

        $events->setCollection($rows);

        return $this->reportData($actor, $filters, $year, $period, $events, $summary);
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function allForDocument(User $actor, array $filters): array
    {
        abort_unless($this->policy->viewDocument($actor), 403);

        [$year, $filters, $period] = $this->normalizedFilters($filters);
        if ($year === null) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'Tahun ajaran belum tersedia untuk membuat dokumen laporan.',
            ])->redirectTo(route('reports.index', $filters));
        }
        if ($period === null) {
            throw ValidationException::withMessages([
                'semester' => 'Pilih semester sebelum membuat dokumen laporan.',
            ])->redirectTo(route('reports.index', $filters));
        }

        $eventQuery = $this->eventQuery($actor, $filters, $year, $period);
        $summary = $this->summary($eventQuery, $filters['service_type']);
        $limit = max(1, (int) config('sibk.report_max_document_rows', 2500));
        if ($summary[0]['value'] > $limit) {
            $this->rejectOversizedDocument($filters, $limit);
        }

        $events = $eventQuery
            ->orderBy('service_date')
            ->orderBy('record_type')
            ->orderBy('record_id')
            ->limit($limit + 1)
            ->get();
        if ($events->count() > $limit) {
            $this->rejectOversizedDocument($filters, $limit);
        }

        $rows = $this->hydrateRows($actor, $events, $year, 1);

        return $this->reportData($actor, $filters, $year, $period, $rows, $summary);
    }

    /** @param array<string, mixed> $filters */
    private function rejectOversizedDocument(array $filters, int $limit): never
    {
        throw ValidationException::withMessages([
            'classroom_search' => sprintf(
                'Dokumen laporan dibatasi hingga %s catatan. Persempit periode atau filter kelas lalu coba lagi.',
                number_format($limit, 0, ',', '.'),
            ),
        ])->redirectTo(route('reports.index', $filters));
    }

    public function findRecord(User $actor, string $type, int $id): BkCase|Consultation|WithdrawalProgress
    {
        abort_unless($this->policy->viewDocument($actor), 403);

        return match ($type) {
            'case' => $this->caseQuery($actor)->whereKey($id)->firstOrFail(),
            'consultation' => $this->consultationQuery($actor)->whereKey($id)->firstOrFail(),
            'withdrawal' => $this->withdrawalQuery($actor)->whereKey($id)->firstOrFail(),
            default => abort(404),
        };
    }

    /** @return array<string, mixed> */
    public function recordForDocument(BkCase|Consultation|WithdrawalProgress $record): array
    {
        return $this->recordRow($record, null, 1);
    }

    /** @return array<string, mixed> */
    public function defaultPeriodFor(?AcademicYear $year): array
    {
        if ($year === null) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'Tahun ajaran belum tersedia untuk membuat dokumen laporan.',
            ]);
        }

        [$academicStart, $academicEnd, $recordedStart, $recordedEnd] = $this->academicBounds(
            $year,
            route('reports.index', ['academic_year_id' => $year->getKey()]),
        );
        $first = max($academicStart, $recordedStart ?? $academicStart);
        $last = min($academicEnd, $recordedEnd ?? $academicEnd);
        $today = CarbonImmutable::today('Asia/Jakarta')->toDateString();
        $end = max($first, min($today, $last));
        $semester = substr($end, 0, 4) === substr($academicStart, 0, 4) ? '1' : '2';
        $semesterStart = $semester === '1' ? $academicStart : sprintf('%04d-01-01', (int) substr($academicStart, 0, 4) + 1);
        $start = max($first, $semesterStart, CarbonImmutable::parse($end)->subDays(29)->toDateString());

        return $this->periodFor($year, [
            'academic_year_id' => $year->getKey(),
            'semester' => $semester,
            'date_from' => $start,
            'date_to' => $end,
        ]);
    }

    /** @param array<string, mixed> $filters @return array{AcademicYear|null, array<string, mixed>, array<string, mixed>|null} */
    private function normalizedFilters(array $filters): array
    {
        $year = isset($filters['academic_year_id'])
            ? AcademicYear::query()->find((int) $filters['academic_year_id'])
            : AcademicYear::query()->active()->orderByDesc('starts_on')->first()
                ?? AcademicYear::query()->orderByDesc('starts_on')->first();

        $normalized = [
            'academic_year_id' => $year?->getKey(),
            'semester' => $filters['semester'] ?? null,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'classroom_id' => isset($filters['classroom_id'])
                ? (int) $filters['classroom_id']
                : null,
            'classroom_search' => trim((string) ($filters['classroom_search'] ?? '')),
            'service_type' => ! empty($filters['service_type']) ? $filters['service_type'] : 'case',
            'per_page' => (int) ($filters['per_page'] ?? 10),
            'sort' => $filters['sort'] ?? null,
            'direction' => $filters['direction'] ?? null,
        ];

        if (isset($filters['academic_year_id']) && $year === null) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'Tahun ajaran tidak tersedia.',
            ])->redirectTo(route('reports.index', $normalized));
        }

        return [$year, $normalized, $this->periodFor($year, $normalized)];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed>|null */
    private function periodFor(?AcademicYear $year, array $filters): ?array
    {
        $semester = $filters['semester'];
        $dateFrom = $filters['date_from'];
        $dateTo = $filters['date_to'];
        $redirect = route('reports.index', Arr::only($filters, [
            'academic_year_id', 'classroom_id', 'classroom_search', 'service_type', 'per_page',
        ]));

        if ($semester === null) {
            if ($dateFrom !== null || $dateTo !== null) {
                throw ValidationException::withMessages([
                    'semester' => 'Pilih semester sebelum mengatur periode tanggal.',
                ])->redirectTo($redirect);
            }

            return null;
        }
        if (! in_array($semester, ['1', '2'], true)) {
            throw ValidationException::withMessages(['semester' => 'Semester harus 1 atau 2.'])
                ->redirectTo($redirect);
        }
        foreach (['date_from' => $dateFrom, 'date_to' => $dateTo] as $field => $date) {
            if ($date !== null && (! is_string($date)
                || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
                || ! checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)))) {
                throw ValidationException::withMessages([$field => 'Tanggal harus valid dan berformat YYYY-MM-DD.'])
                    ->redirectTo($redirect);
            }
        }
        if ($year === null) {
            return null;
        }

        [$academicStart, $academicEnd, $recordedStart, $recordedEnd] = $this->academicBounds($year, $redirect);

        $semesterStart = $semester === '1' ? $academicStart : sprintf('%04d-01-01', (int) substr($academicStart, 0, 4) + 1);
        $semesterEnd = $semester === '1' ? sprintf('%04d-12-31', (int) substr($academicStart, 0, 4)) : $academicEnd;
        $start = $dateFrom ?? $semesterStart;
        $end = $dateTo ?? $semesterEnd;
        if ($start < $semesterStart || $start > $semesterEnd) {
            throw ValidationException::withMessages([
                'date_from' => 'Tanggal awal harus berada dalam semester terpilih.',
            ])->redirectTo($redirect);
        }
        if ($end < $semesterStart || $end > $semesterEnd) {
            throw ValidationException::withMessages([
                'date_to' => 'Tanggal akhir harus berada dalam semester terpilih.',
            ])->redirectTo($redirect);
        }
        if ($start > $end) {
            throw ValidationException::withMessages([
                'date_to' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            ])->redirectTo($redirect);
        }
        if (($recordedStart !== null && $start < $recordedStart)
            || ($recordedEnd !== null && $end > $recordedEnd)) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'Periode pilihan berada di luar tanggal tahun ajaran.',
            ])->redirectTo($redirect);
        }

        return [
            'semester' => (int) $semester,
            'start' => $start,
            'end' => $end,
            'semester_start' => $semesterStart,
            'semester_end' => $semesterEnd,
            'label' => sprintf(
                'Semester %s · %s–%s',
                $semester,
                CarbonImmutable::parse($start)->locale('id')->translatedFormat('j F Y'),
                CarbonImmutable::parse($end)->locale('id')->translatedFormat('j F Y'),
            ),
        ];
    }

    /** @return array{string, string, string|null, string|null} */
    private function academicBounds(AcademicYear $year, string $redirect): array
    {
        $nameYear = null;
        if (preg_match('/^(\d{4})\/(\d{4})$/D', $year->name, $parts)
            && (int) $parts[2] === (int) $parts[1] + 1) {
            $nameYear = (int) $parts[1];
        }
        $yearStart = $year->starts_on === null
            ? $nameYear
            : (int) $year->starts_on->year - ($year->starts_on->month < 7 ? 1 : 0);
        if ($yearStart === null || ($nameYear !== null && $nameYear !== $yearStart)) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'Periode tahun ajaran tidak dapat dipastikan.',
            ])->redirectTo($redirect);
        }

        $academicStart = sprintf('%04d-07-01', $yearStart);
        $academicEnd = sprintf('%04d-06-30', $yearStart + 1);
        $recordedStart = $year->starts_on?->toDateString();
        $recordedEnd = $year->ends_on?->toDateString();
        if (($recordedStart !== null && ($recordedStart < $academicStart || $recordedStart > $academicEnd))
            || ($recordedEnd !== null && ($recordedEnd < $academicStart || $recordedEnd > $academicEnd))
            || ($recordedStart !== null && $recordedEnd !== null && $recordedStart > $recordedEnd)) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'Tanggal tahun ajaran tidak sesuai dengan periode Juli–Juni.',
            ])->redirectTo($redirect);
        }

        return [$academicStart, $academicEnd, $recordedStart, $recordedEnd];
    }

    /** @param array<string, mixed> $filters */
    private function eventQuery(
        User $actor,
        array $filters,
        ?AcademicYear $year,
        ?array $period,
    ): QueryBuilder {
        $caseEvents = $this->reportServiceQuery(BkCase::query(), $actor)
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('cases.academic_year_id', $selected->getKey()))
            ->when(
                $filters['classroom_id'],
                fn (Builder $query, int $classroomId): Builder => $query->where('cases.classroom_id', $classroomId),
            )
            ->when($filters['classroom_search'] !== '', fn (Builder $query): Builder => $query
                ->whereHas('classroom', fn (Builder $classrooms): Builder => $classrooms
                    ->where('name', 'like', '%'.$filters['classroom_search'].'%')))
            ->selectRaw("'case' AS record_type")
            ->selectRaw('cases.id AS record_id')
            ->selectRaw('cases.service_date AS service_date')
            ->toBase();

        $consultationEvents = $this->reportServiceQuery(Consultation::query(), $actor)
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('consultations.academic_year_id', $selected->getKey()))
            ->when(
                $filters['classroom_id'],
                fn (Builder $query, int $classroomId): Builder => $query->where('consultations.classroom_id', $classroomId),
            )
            ->when($filters['classroom_search'] !== '', fn (Builder $query): Builder => $query
                ->whereHas('classroom', fn (Builder $classrooms): Builder => $classrooms
                    ->where('name', 'like', '%'.$filters['classroom_search'].'%')))
            ->selectRaw("'consultation' AS record_type")
            ->selectRaw('consultations.id AS record_id')
            ->selectRaw('consultations.session_date AS service_date')
            ->toBase();

        $withdrawalEvents = WithdrawalProgress::query()
            ->when(! $actor->hasRole('waka_kesiswaan'), fn (Builder $query): Builder => $query->accessibleTo($actor))
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->whereHas('classroom', fn (Builder $classrooms): Builder => $classrooms
                    ->where('academic_year_id', $selected->getKey())))
            ->when(
                $filters['classroom_id'],
                fn (Builder $query, int $classroomId): Builder => $query->where('withdrawal_progresses.classroom_id', $classroomId),
            )
            ->when($filters['classroom_search'] !== '', fn (Builder $query): Builder => $query
                ->whereHas('classroom', fn (Builder $classrooms): Builder => $classrooms
                    ->where('name', 'like', '%'.$filters['classroom_search'].'%')))
            ->selectRaw("'withdrawal' AS record_type")
            ->selectRaw('withdrawal_progresses.id AS record_id')
            ->selectRaw('withdrawal_progresses.recorded_on AS service_date')
            ->toBase();

        $events = match ($filters['service_type']) {
            'case' => $caseEvents,
            'consultation' => $consultationEvents,
            'withdrawal' => $withdrawalEvents,
            default => $caseEvents,
        };

        return DB::query()->fromSub($events, 'report_records')
            ->when($period, fn (QueryBuilder $query, array $selected): QueryBuilder => $query
                ->where('service_date', '>=', $selected['start'])
                ->where('service_date', '<', CarbonImmutable::parse($selected['end'])->addDay()->toDateString()));
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function summary(QueryBuilder $events, string $serviceType): array
    {
        $counts = DB::query()
            ->fromSub(clone $events, 'filtered_report_records')
            ->selectRaw('COUNT(*) AS total_count')
            ->selectRaw("SUM(CASE WHEN record_type = 'case' THEN 1 ELSE 0 END) AS case_count")
            ->selectRaw("SUM(CASE WHEN record_type = 'consultation' THEN 1 ELSE 0 END) AS consultation_count")
            ->selectRaw("SUM(CASE WHEN record_type = 'withdrawal' THEN 1 ELSE 0 END) AS withdrawal_count")
            ->first();

        $items = [[
            'label' => 'Total Catatan',
            'value' => (int) ($counts?->total_count ?? 0),
        ]];

        if ($serviceType === 'case') {
            $items[] = [
                'label' => 'Permasalahan',
                'value' => (int) ($counts?->case_count ?? 0),
            ];
        } elseif ($serviceType === 'consultation') {
            $items[] = [
                'label' => 'Konsultasi',
                'value' => (int) ($counts?->consultation_count ?? 0),
            ];
        } elseif ($serviceType === 'withdrawal') {
            $items[] = [
                'label' => 'Pengunduran Diri',
                'value' => (int) ($counts?->withdrawal_count ?? 0),
            ];
        }

        return $items;
    }

    /** @param list<array{label: string, value: int}> $summary */
    private function summarySentence(array $summary): string
    {
        $counts = array_column($summary, 'value', 'label');

        if (array_key_exists('Permasalahan', $counts)) {
            return sprintf(
                'Pada laporan ini terdapat %d catatan permasalahan.',
                $counts['Permasalahan'],
            );
        }

        if (array_key_exists('Konsultasi', $counts)) {
            return sprintf(
                'Pada laporan ini terdapat %d catatan konsultasi.',
                $counts['Konsultasi'],
            );
        }

        if (array_key_exists('Pengunduran Diri', $counts)) {
            return sprintf(
                'Pada laporan ini terdapat %d catatan pengunduran diri.',
                $counts['Pengunduran Diri'],
            );
        }

        return sprintf(
            'Pada laporan ini terdapat %d catatan layanan BK.',
            $counts['Total Catatan'] ?? 0,
        );
    }

    /**
     * @param  Collection<int, object>  $events
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
        $withdrawals = $this->withdrawalQuery($actor)
            ->whereKey($events->where('record_type', 'withdrawal')->pluck('record_id'))
            ->get()
            ->keyBy('id');

        return $events->values()->map(function (object $event, int $index) use (
            $actor,
            $cases,
            $consultations,
            $withdrawals,
            $year,
            $firstNumber,
        ): array {
            $record = match ($event->record_type) {
                'case' => $cases->get((int) $event->record_id),
                'consultation' => $consultations->get((int) $event->record_id),
                'withdrawal' => $withdrawals->get((int) $event->record_id),
                default => null,
            };

            abort_if($record === null, 404);

            return $this->recordRow(
                $record,
                $actor,
                $firstNumber + $index,
                $year,
            );
        });
    }

    private function reportServiceQuery(Builder $query, User $actor): Builder
    {
        return $actor->hasRole('koordinator_bk')
            ? $query->withinStudentServicePeriod()
            : $query->accessibleTo($actor);
    }

    /** @return Builder<BkCase> */
    private function caseQuery(User $actor): Builder
    {
        return $this->reportServiceQuery(BkCase::query(), $actor)
            ->with([
                'classroom',
                'student',
                'source',
                'serviceField',
                'status',
                'followUpType',
                'temporaryStudent.reconciledStudent',
                'assignments.teacher',
            ]);
    }

    /** @return Builder<Consultation> */
    private function consultationQuery(User $actor): Builder
    {
        return $this->reportServiceQuery(Consultation::query(), $actor)
            ->with([
                'classroom',
                'student',
                'serviceField',
                'counselor',
                'temporaryStudent.reconciledStudent',
            ]);
    }

    /** @return Builder<WithdrawalProgress> */
    private function withdrawalQuery(User $actor): Builder
    {
        return WithdrawalProgress::query()
            ->when(! $actor->hasRole('waka_kesiswaan'), fn (Builder $query): Builder => $query->accessibleTo($actor))
            ->with([
                'student',
                'classroom',
                'teacher',
                'followUps.creator',
            ]);
    }

    /** @return array<string, mixed> */
    private function recordRow(
        BkCase|Consultation|WithdrawalProgress $record,
        ?User $actor,
        int $number,
        ?AcademicYear $year = null,
    ): array {
        $isCase = $record instanceof BkCase;
        $isConsultation = $record instanceof Consultation;
        $isWithdrawal = $record instanceof WithdrawalProgress;

        $type = match (true) {
            $isCase => 'case',
            $isConsultation => 'consultation',
            $isWithdrawal => 'withdrawal',
        };
        $date = match (true) {
            $isCase => $record->service_date,
            $isConsultation => $record->session_date,
            $isWithdrawal => $record->recorded_on,
        };

        $withdrawalProgressLabel = $isWithdrawal
            ? ($record->followUps->first()?->progressLabel() ?? $record->progressLabel())
            : null;

        return [
            'number' => $number,
            'type' => $type,
            'record_id' => $record->getKey(),
            'date' => $date,
            'day_label' => $date->locale('id')->translatedFormat('l'),
            'date_label' => $date->locale('id')->translatedFormat('d M Y'),
            'name' => StudentName::display($record->identityName()),
            'classroom' => $this->classroomName($record, $year),
            'service' => match (true) {
                $isCase => 'Permasalahan',
                $isConsultation => 'Konsultasi',
                $isWithdrawal => 'Pengunduran Diri',
            },
            'service_field' => $isWithdrawal
                ? 'Pengunduran Diri'
                : ($record->serviceField?->label ?? '—'),
            'problem' => match (true) {
                $isCase => $record->initial_info,
                $isConsultation => $record->problem,
                $isWithdrawal => $record->note,
            },
            'handling' => match (true) {
                $isCase => $record->initial_action,
                $isConsultation => $record->handling,
                $isWithdrawal => '—',
            },
            'detail_label' => match (true) {
                $isCase => 'Catatan Penyelesaian',
                $isConsultation => 'Hasil',
                $isWithdrawal => 'Progres Penanganan',
            },
            'detail_note' => match (true) {
                $isCase => ($record->resolution_summary ?: '—'),
                $isConsultation => ($record->result ?: '—'),
                $isWithdrawal => ($withdrawalProgressLabel ?? '—'),
            },
            'follow_up_label' => match (true) {
                $isCase => ($record->followUpType?->label ?? $record->status?->label ?? '—'),
                $isConsultation => 'Selesai',
                $isWithdrawal => ($withdrawalProgressLabel ?? '—'),
            },
            'document_note' => match (true) {
                $isCase => sprintf(
                    "Sumber: %s\nTindak Lanjut: %s",
                    $record->source?->label ?? '—',
                    $record->followUpType?->label ?? '—',
                ),
                $isConsultation => 'Selesai',
                $isWithdrawal => $record->note
                    ? sprintf("Progres: %s\nCatatan: %s", $withdrawalProgressLabel ?? '—', $record->note)
                    : sprintf('Progres: %s', $withdrawalProgressLabel ?? '—'),
            },
            'counselor' => match (true) {
                $isCase => ($record->assignments->first()?->teacher?->name ?? '—'),
                $isConsultation => ($record->counselor?->name ?? '—'),
                $isWithdrawal => ($record->teacher?->name ?? '—'),
            },
            'archive_url' => match (true) {
                $isCase => route('cases.destroy', $record),
                $isConsultation => route('consultations.destroy', $record),
                $isWithdrawal => route('withdrawals.destroy', $record),
            },
        ];
    }

    private function classroomName(
        BkCase|Consultation|WithdrawalProgress $record,
        ?AcademicYear $year,
    ): string {
        if ($record instanceof WithdrawalProgress) {
            return $record->classroom?->name ?? 'Belum tersedia';
        }

        return $record->classroom?->name ?? ($record->temporary_student_id ? 'Identitas sementara' : 'Belum tersedia');
    }

    /**
     * @param  LengthAwarePaginator<int, array<string, mixed>>|Collection<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $filters
     * @param  list<array{label: string, value: int}>  $summary
     * @return array<string, mixed>
     */
    private function reportData(
        User $actor,
        array $filters,
        ?AcademicYear $year,
        ?array $period,
        LengthAwarePaginator|Collection $rows,
        array $summary,
    ): array {
        $canViewDocument = $this->policy->viewDocument($actor);

        return [
            'title' => 'Laporan Layanan BK',
            'columns' => match (true) {
                $filters['service_type'] === 'withdrawal' => [
                    'No',
                    'Hari / Tanggal',
                    'Nama / Kelas',
                    'Guru',
                    'Keterangan',
                    ...($canViewDocument ? ['Aksi'] : []),
                ],
                $canViewDocument => [
                    'No',
                    'Hari/Tanggal',
                    'Nama & Kelas',
                    'Layanan/Jenis Masalah',
                    'Hasil',
                    'Aksi',
                ],
                default => [
                    'No',
                    'Hari / Tanggal',
                    'Nama / Kelas',
                    'Jenis Masalah',
                    'Ringkasan',
                    'Guru BK',
                    'Keterangan',
                ],
            },
            'rows' => $rows,
            'summary' => $summary,
            'summary_sentence' => $this->summarySentence($summary),
            'document_section_title' => match ($filters['service_type']) {
                'consultation' => 'Rekap Catatan Bimbingan & Konseling',
                'withdrawal' => 'Rekap Catatan Pengunduran Diri',
                default => 'Rekap Catatan Permasalahan',
            },
            'filters' => $filters,
            'period' => $period,
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
            'can_view_document' => $canViewDocument,
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
            ->when(
                $actor->hasRole('waka_kesiswaan') === false,
                fn (Builder $query): Builder => $query->whereHas(
                    'studentClassMemberships.student',
                    fn (Builder $students): Builder => $students->accessibleTo($actor),
                ),
            );
    }
}
