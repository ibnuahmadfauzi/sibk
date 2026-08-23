<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ExternalTatibRecord;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Policies\ReportPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportService
{
    public const TYPE_STUDENT_VIOLATIONS = 'pelanggaran-murid';

    public const TYPE_CLASS_VIOLATIONS = 'pelanggaran-kelas';

    public const TYPE_VIOLATION_POINTS = 'poin-pelanggaran';

    public const TYPE_CONSULTATIONS = 'konsultasi';

    public const TYPE_FOLLOW_UPS = 'status-tindak-lanjut';

    public const TYPE_SERVICE_RECAP = 'rekap-layanan-bk';

    public const TYPE_ACHIEVEMENTS = 'prestasi';

    public function __construct(private readonly ReportPolicy $policy) {}

    /** @return list<string> */
    public static function types(): array
    {
        return [
            self::TYPE_STUDENT_VIOLATIONS,
            self::TYPE_CLASS_VIOLATIONS,
            self::TYPE_VIOLATION_POINTS,
            self::TYPE_CONSULTATIONS,
            self::TYPE_FOLLOW_UPS,
            self::TYPE_SERVICE_RECAP,
            self::TYPE_ACHIEVEMENTS,
        ];
    }

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
        $type = (string) ($filters['type'] ?? self::TYPE_SERVICE_RECAP);
        abort_unless($this->policy->viewType($user, $type), 403);
        $definition = collect($this->catalog())->firstWhere('id', $type);
        abort_if($definition === null, 404);
        [$year, $start, $end] = $this->period($filters);
        $filters['academic_year_id'] = $year?->getKey();
        $filters['date_start'] = $start->toDateString();
        $filters['date_end'] = $end->toDateString();

        $data = match ($type) {
            self::TYPE_STUDENT_VIOLATIONS => $paginate
                ? $this->paginatedStudentViolations($user, $filters, $year, $start, $end)
                : $this->studentViolations($user, $filters, $year, $start, $end),
            self::TYPE_CLASS_VIOLATIONS => $this->classViolations($user, $filters, $year, $start, $end),
            self::TYPE_VIOLATION_POINTS => $this->violationPoints($user, $filters, $year, $start, $end),
            self::TYPE_CONSULTATIONS => $paginate
                ? $this->paginatedConsultations($user, $filters, $year, $start, $end)
                : $this->consultations($user, $filters, $year, $start, $end),
            self::TYPE_FOLLOW_UPS => $paginate
                ? $this->paginatedFollowUps($user, $filters, $year, $start, $end)
                : $this->followUps($user, $filters, $year, $start, $end),
            self::TYPE_SERVICE_RECAP => $paginate
                ? $this->paginatedServiceRecap($user, $filters, $year, $start, $end)
                : $this->serviceRecap($user, $filters, $year, $start, $end),
            self::TYPE_ACHIEVEMENTS => $paginate
                ? $this->paginatedAchievements($user, $filters, $year, $start, $end)
                : $this->achievements($user, $filters, $year, $start, $end),
        };
        $rows = $data['rows'];

        return [
            ...$definition,
            'columns' => $data['columns'],
            'stats' => $data['stats'],
            'rows' => $rows instanceof LengthAwarePaginator ? $rows : ($paginate ? $this->paginate($rows, $filters) : $rows),
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
        $type = (string) ($filters['type'] ?? self::TYPE_SERVICE_RECAP);
        abort_unless($this->policy->viewType($user, $type), 403);
        [$year, $start, $end] = $this->period($filters);
        $filters['academic_year_id'] = $year?->getKey();
        $filters['date_start'] = $start->toDateString();
        $filters['date_end'] = $end->toDateString();

        return match ($type) {
            self::TYPE_STUDENT_VIOLATIONS => [
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
            ],
            self::TYPE_CONSULTATIONS => [
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
            self::TYPE_FOLLOW_UPS => [
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
                        $followUp->execution_date?->locale('id')->translatedFormat('d M Y') ?? 'â€”',
                        $followUp->status->label,
                    ], 6, $this->statusTone($followUp->status->code))),
            ],
            self::TYPE_ACHIEVEMENTS => [
                'id' => $type,
                'columns' => ['Inisial Murid', 'NISN Tersamarkan', 'Kelas', 'Kegiatan', 'Jenis dan Tingkat', 'Hasil', 'Tanggal', 'Status Verifikasi'],
                'rows' => $this->achievementReportQuery($user, $filters, $year, $start, $end)
                    ->latest('achievement_date')->latest('id')->lazy(500)
                    ->map(fn (Achievement $achievement): array => $this->row([
                        $this->initials($achievement->student->name),
                        $this->maskNisn($achievement->student->nisn),
                        $this->historicClass($achievement->student, $achievement->achievement_date, $year),
                        $achievement->activity_name,
                        $achievement->type->label.' / '.$achievement->level->label,
                        $achievement->result,
                        $achievement->achievement_date->locale('id')->translatedFormat('d M Y'),
                        $achievement->verificationStatus->label,
                    ], 7, $this->statusTone($achievement->verificationStatus->code))),
            ],
            self::TYPE_SERVICE_RECAP => $this->serviceRecapExport($user, $filters, $year, $start, $end),
            default => $this->exportCollection($user, $filters),
        };
    }

    /** @param array<string, mixed> $filters @return array{id: string, columns: list<string>, rows: iterable<int, array<string, mixed>>} */
    private function exportCollection(User $user, array $filters): array
    {
        $report = $this->build($user, $filters, false);

        return ['id' => $report['id'], 'columns' => $report['columns'], 'rows' => $report['rows']];
    }

    /** @return array{columns: list<string>, rows: LengthAwarePaginator, stats: array<string, array<string, string>>} */
    private function paginatedStudentViolations(
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

    /** @return array{columns: list<string>, rows: Collection<int, array<string, mixed>>, stats: array<string, array<string, string>>} */
    private function studentViolations(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
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
    private function classViolations(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
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
    private function violationPoints(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
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

    /** @return array{columns: list<string>, rows: Collection<int, array<string, mixed>>, stats: array<string, array<string, string>>} */
    private function consultations(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
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
    private function paginatedConsultations(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
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
    private function consultationReportQuery(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): Builder
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
    private function followUps(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
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
    private function paginatedFollowUps(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
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
    private function followUpReportQuery(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): Builder
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
    private function serviceRecap(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
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
        if ($this->policy->viewType($user, self::TYPE_CONSULTATIONS)) {
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
    private function paginatedServiceRecap(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $caseQuery = $this->caseRecapQuery($user, $filters, $year, $start, $end);
        $consultationQuery = $this->policy->viewType($user, self::TYPE_CONSULTATIONS)
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
    private function caseRecapQuery(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): Builder
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
    private function serviceRecapExport(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
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
        if ($this->policy->viewType($user, self::TYPE_CONSULTATIONS)) {
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
            'id' => self::TYPE_SERVICE_RECAP,
            'columns' => ['Nomor', 'Jenis Layanan', 'Inisial Murid', 'Kelas', 'Bidang Layanan', 'Status', 'Tanggal', 'Guru BK'],
            'rows' => $caseRows->concat($consultationRows),
        ];
    }

    /** @return array{columns: list<string>, rows: Collection<int, array<string, mixed>>, stats: array<string, array<string, string>>} */
    private function achievements(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $query = Achievement::query()->accessibleTo($user)
            ->whereBetween('achievement_date', [$start->toDateString(), $end->toDateString()])
            ->with(['student.classMemberships.classroom', 'type', 'level', 'verificationStatus']);
        $query->when($filters['student_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('student_id', $id));
        $query->when($filters['achievement_type_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('type_id', $id));
        $query->when($filters['achievement_level_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('level_id', $id));
        $query->when($filters['status_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('verification_status_id', $id));
        $this->applyHistoricClassFilter($query, $filters, $year, 'achievements.achievement_date', ['student.classMemberships']);
        $items = $query->get()->filter(fn (Achievement $achievement): bool => $this->matchesClass(
            $achievement->student,
            $achievement->achievement_date,
            $year,
            $filters,
        ));
        $rows = $items->sortByDesc('achievement_date')->values()->map(fn (Achievement $achievement): array => $this->row([
            $this->initials($achievement->student->name),
            $this->maskNisn($achievement->student->nisn),
            $this->historicClass($achievement->student, $achievement->achievement_date, $year),
            $achievement->activity_name,
            $achievement->type->label.' / '.$achievement->level->label,
            $achievement->result,
            $achievement->achievement_date->locale('id')->translatedFormat('d M Y'),
            $achievement->verificationStatus->label,
        ], 7, $this->statusTone($achievement->verificationStatus->code)));

        return [
            'columns' => ['Inisial Murid', 'NISN Tersamarkan', 'Kelas', 'Kegiatan', 'Jenis dan Tingkat', 'Hasil', 'Tanggal', 'Status Verifikasi'],
            'rows' => $rows,
            'stats' => $this->stats(
                ['Total Prestasi', $items->count(), 'Periode terpilih'],
                ['Terverifikasi', $items->where('verificationStatus.code', 'terverifikasi')->count(), 'Sudah diperiksa'],
                ['Menunggu Verifikasi', $items->where('verificationStatus.code', 'menunggu')->count(), 'Perlu pemeriksaan'],
            ),
        ];
    }

    /** @return array{columns: list<string>, rows: LengthAwarePaginator, stats: array<string, array<string, string>>} */
    private function paginatedAchievements(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $query = $this->achievementReportQuery($user, $filters, $year, $start, $end);
        $total = (clone $query)->count();
        $verified = (clone $query)->whereHas('verificationStatus', fn (Builder $statuses): Builder => $statuses->where('code', 'terverifikasi'))->count();
        $pending = (clone $query)->whereHas('verificationStatus', fn (Builder $statuses): Builder => $statuses->where('code', 'menunggu'))->count();
        $page = $query->latest('achievement_date')->latest('id')->paginate(20)->withQueryString();
        $page->setCollection($page->getCollection()->map(fn (Achievement $achievement): array => $this->row([
            $this->initials($achievement->student->name),
            $this->maskNisn($achievement->student->nisn),
            $this->historicClass($achievement->student, $achievement->achievement_date, $year),
            $achievement->activity_name,
            $achievement->type->label.' / '.$achievement->level->label,
            $achievement->result,
            $achievement->achievement_date->locale('id')->translatedFormat('d M Y'),
            $achievement->verificationStatus->label,
        ], 7, $this->statusTone($achievement->verificationStatus->code))));

        return [
            'columns' => ['Inisial Murid', 'NISN Tersamarkan', 'Kelas', 'Kegiatan', 'Jenis dan Tingkat', 'Hasil', 'Tanggal', 'Status Verifikasi'],
            'rows' => $page,
            'stats' => $this->stats(
                ['Total Prestasi', $total, 'Periode terpilih'],
                ['Terverifikasi', $verified, 'Sudah diperiksa'],
                ['Menunggu Verifikasi', $pending, 'Perlu pemeriksaan'],
            ),
        ];
    }

    /** @return Builder<Achievement> */
    private function achievementReportQuery(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        $query = Achievement::query()->accessibleTo($user)
            ->whereBetween('achievement_date', [$start->toDateString(), $end->toDateString()])
            ->with(['student.classMemberships.classroom', 'type', 'level', 'verificationStatus']);
        $query->when($filters['student_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('student_id', $id));
        $query->when($filters['achievement_type_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('type_id', $id));
        $query->when($filters['achievement_level_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('level_id', $id));
        $query->when($filters['status_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('verification_status_id', $id));
        $this->applyHistoricClassFilter($query, $filters, $year, 'achievements.achievement_date', ['student.classMemberships']);

        return $query;
    }

    /** @return Collection<int, ExternalTatibRecord> */
    private function filteredEtatib(User $user, array $filters, ?AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): Collection
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
    private function etatibReportQuery(
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

    /** @param Builder<*> $query @param list<string> $membershipPaths */
    private function applyHistoricClassFilter(
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

    /** @param Builder<ExternalTatibRecord> $query */
    private function scopeEtatib(Builder $query, User $user): void
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
                $access->{$method}('cases.coordinations', fn (Builder $coordinations): Builder => $coordinations
                    ->where('waka_user_id', $user->getKey()));
            }
        });
    }

    /** @param Builder<Consultation> $query */
    private function applyCommonServiceFilters(Builder $query, array $filters): void
    {
        $query->when($filters['student_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('student_id', $id));
        $query->when($filters['service_field_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('service_field_id', $id));
        $query->when($filters['status_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('status_id', $id));
        $query->when($filters['counselor_id'] ?? null, fn (Builder $builder, int $id): Builder => $builder->where('counselor_id', $id));
    }

    private function matchesClass(?Student $student, mixed $date, ?AcademicYear $year, array $filters): bool
    {
        if (! isset($filters['classroom_id'])) {
            return true;
        }

        return $this->historicMembership($student, $date, $year)?->classroom_id === (int) $filters['classroom_id'];
    }

    private function historicClass(?Student $student, mixed $date, ?AcademicYear $year): string
    {
        return $this->historicMembership($student, $date, $year)?->classroom?->name ?? 'Tanpa kelas';
    }

    private function historicMembership(?Student $student, mixed $date, ?AcademicYear $year): ?StudentClassMembership
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

    private function caseStudent(BkCase $case): ?Student
    {
        return $case->student ?? $case->temporaryStudent?->reconciledStudent;
    }

    private function consultationStudent(Consultation $consultation): ?Student
    {
        return $consultation->student ?? $consultation->temporaryStudent?->reconciledStudent;
    }

    /** @param list<mixed> $values @return array<string, mixed> */
    private function row(array $values, ?int $badgeIndex = null, string $tone = 'primary'): array
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
    private function datedRow(mixed $date, array $values, ?int $badgeIndex = null, string $tone = 'primary'): array
    {
        return [...$this->row($values, $badgeIndex, $tone), 'sort_date' => CarbonImmutable::parse($date)->timestamp];
    }

    /** @param array{string, int|string, string} $total @param array{string, int|string, string} $active @param array{string, int|string, string} $completed @return array<string, array<string, string>> */
    private function stats(array $total, array $active, array $completed): array
    {
        return [
            'total' => ['label' => $total[0], 'value' => (string) $total[1], 'sub' => $total[2]],
            'active' => ['label' => $active[0], 'value' => (string) $active[1], 'sub' => $active[2]],
            'completed' => ['label' => $completed[0], 'value' => (string) $completed[1], 'sub' => $completed[2]],
        ];
    }

    private function initials(?string $name): string
    {
        if (blank($name)) {
            return '—';
        }

        return collect(preg_split('/\s+/u', trim($name)) ?: [])
            ->filter()->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)).'.')->join('');
    }

    private function maskNisn(string $nisn): string
    {
        $length = mb_strlen($nisn);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return mb_substr($nisn, 0, 2).str_repeat('*', $length - 4).mb_substr($nisn, -2);
    }

    private function pointsTone(int $points): string
    {
        return $points >= 25 ? 'warning' : 'primary';
    }

    private function statusTone(string $code): string
    {
        return in_array($code, ['selesai', 'terlaksana', 'terverifikasi'], true) ? 'success'
            : (in_array($code, ['baru', 'dijadwalkan', 'terjadwal'], true) ? 'primary' : 'warning');
    }

    /** @param Collection<int, array<string, mixed>> $rows @param array<string, mixed> $filters */
    private function paginate(Collection $rows, array $filters): LengthAwarePaginator
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

    /** @return array<string, Collection<int, mixed>> */
    private function filterOptions(User $user, string $type, ?AcademicYear $year): array
    {
        $students = Student::query()->active()->accessibleTo($user)->orderBy('name')->get()
            ->map(fn (Student $student): array => ['id' => $student->id, 'label' => $this->initials($student->name).' · '.$this->maskNisn($student->nisn)]);
        $classrooms = Classroom::query()->active()
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query->where('academic_year_id', $selected->getKey()))
            ->whereHas('studentClassMemberships.student', fn (Builder $students): Builder => $students->accessibleTo($user))
            ->orderBy('name')->get();
        $statusCategories = match ($type) {
            self::TYPE_CONSULTATIONS => ['consultation_status'],
            self::TYPE_FOLLOW_UPS => ['follow_up_status'],
            self::TYPE_SERVICE_RECAP => ['case_status', 'consultation_status'],
            self::TYPE_ACHIEVEMENTS => ['achievement_verification_status'],
            default => [],
        };

        return [
            'academic_years' => AcademicYear::query()->orderByDesc('starts_on')->get(),
            'classrooms' => $classrooms,
            'students' => $students,
            'categories' => $this->etatibQuery($user)->select('category')->distinct()->orderBy('category')->pluck('category'),
            'service_fields' => ReferenceValue::query()->active()->forCategory('service_field')->orderBy('sort_order')->get(),
            'statuses' => ReferenceValue::query()->active()->whereIn('category', $statusCategories)->orderBy('sort_order')->get(),
            'achievement_types' => ReferenceValue::query()->active()->forCategory('achievement_type')->orderBy('sort_order')->get(),
            'achievement_levels' => ReferenceValue::query()->active()->forCategory('achievement_level')->orderBy('sort_order')->get(),
            'counselors' => $user->hasRole('koordinator_bk')
                ? User::query()->active()->whereHas('roles', fn (Builder $roles): Builder => $roles->where('slug', 'guru_bk')->where('is_active', true))->orderBy('name')->get()
                : collect(),
        ];
    }

    /** @return Builder<ExternalTatibRecord> */
    private function etatibQuery(User $user): Builder
    {
        $query = ExternalTatibRecord::query()->active();
        $this->scopeEtatib($query, $user);

        return $query;
    }

    /** @return list<array<string, mixed>> */
    private function catalog(): array
    {
        return [
            ['id' => self::TYPE_STUDENT_VIOLATIONS, 'title' => 'Pelanggaran per Murid', 'description' => 'Riwayat pelanggaran per murid', 'badge' => 'Murid', 'tone' => 'warning', 'icon' => 'student', 'filter_keys' => ['period', 'classroom', 'student', 'category']],
            ['id' => self::TYPE_CLASS_VIOLATIONS, 'title' => 'Pelanggaran per Kelas', 'description' => 'Ringkasan pelanggaran per kelas', 'badge' => 'Kelas', 'tone' => 'info', 'icon' => 'classroom', 'filter_keys' => ['period', 'classroom', 'category']],
            ['id' => self::TYPE_VIOLATION_POINTS, 'title' => 'Poin Pelanggaran', 'description' => 'Rekap poin dalam periode', 'badge' => 'Poin', 'tone' => 'primary', 'icon' => 'points', 'filter_keys' => ['period', 'classroom', 'student', 'minimum_points']],
            ['id' => self::TYPE_CONSULTATIONS, 'title' => 'Konsultasi', 'description' => 'Rekap konsultasi tanpa isi sensitif', 'badge' => 'Layanan', 'tone' => 'success', 'icon' => 'consultation', 'filter_keys' => ['period', 'classroom', 'student', 'service_field', 'status', 'counselor']],
            ['id' => self::TYPE_FOLLOW_UPS, 'title' => 'Status Tindak Lanjut', 'description' => 'Pemantauan status tindak lanjut', 'badge' => 'Tindak Lanjut', 'tone' => 'warning', 'icon' => 'follow-up', 'filter_keys' => ['period', 'classroom', 'student', 'service_field', 'status']],
            ['id' => self::TYPE_SERVICE_RECAP, 'title' => 'Rekap Layanan BK', 'description' => 'Ringkasan layanan BK yang diizinkan', 'badge' => 'Rekap', 'tone' => 'primary', 'icon' => 'recap', 'filter_keys' => ['period', 'classroom', 'student', 'service_field', 'status', 'counselor']],
            ['id' => self::TYPE_ACHIEVEMENTS, 'title' => 'Prestasi', 'description' => 'Rekap prestasi minimum sesuai kewenangan', 'badge' => 'Prestasi', 'tone' => 'info', 'icon' => 'achievement', 'filter_keys' => ['period', 'classroom', 'student', 'achievement_type', 'achievement_level', 'status']],
        ];
    }
}
