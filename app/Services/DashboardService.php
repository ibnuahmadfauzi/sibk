<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\IntegrationSetting;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Support\ServiceRecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(private readonly WakaDashboardService $wakaDashboard) {}

    /** @return array<string, mixed> */
    public function forUser(User $user, ?AcademicYear $academicYear): array
    {
        if ($user->hasRole('koordinator_bk')) {
            return $this->operational($user, $academicYear, 'coordinator');
        }
        if ($user->hasRole('guru_bk')) {
            return $this->operational($user, $academicYear, 'teacher');
        }
        if ($user->hasRole('waka_kesiswaan')) {
            return $this->wakaDashboard->build($user, $academicYear);
        }

        return $this->technical($user, $academicYear);
    }

    /** @return array<string, mixed> */
    private function operational(User $user, ?AcademicYear $year, string $mode): array
    {
        [$start, $end] = $this->period($year);
        $cases = BkCase::query()->withinStudentServicePeriod()
            ->with(['student.classMemberships.classroom', 'temporaryStudent', 'status']);
        if ($mode === 'coordinator') {
            $students = Student::query()->availableForService($end)
                ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                    ->whereHas('classMemberships', fn (Builder $memberships): Builder => $memberships
                        ->where('academic_year_id', $selected->getKey())));
        } elseif ($mode === 'teacher') {
            $cases->accessibleTo($user);
            $students = Student::query()->availableForService($end)->professionallyAccessibleTo($user)
                ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                    ->where(function (Builder $scope) use ($user, $selected, $start, $end): void {
                        $scope->whereHas('classMemberships', fn (Builder $memberships): Builder => $memberships
                            ->where('academic_year_id', $selected->getKey()))
                            ->orWhereHas('cases.assignments', fn (Builder $assignments): Builder => $assignments
                                ->where('user_id', $user->getKey())->effectiveOn(now())
                                ->whereHas('case', fn (Builder $case): Builder => $case
                                    ->whereBetween('service_date', [$start, $end])));
                    }));
        } else {
            // Waka melihat SELURUH kasus aktif sekolah — bukan hanya yang terkoordinasi
            $cases->whereBetween('service_date', [$start, $end]);
            $students = Student::query()->availableForService($end)->whereIn('id', (clone $cases)
                ->whereNotNull('student_id')->select('student_id'));
        }
        if ($mode !== 'waka') {
            $cases->whereBetween('service_date', [$start, $end]);
        }
        $caseIds = (clone $cases)->select('cases.id');

        $followUpCases = (clone $cases)
            ->whereHas('status', fn (Builder $status): Builder => $status
                ->where('code', ServiceRecordStatus::NEEDS_FOLLOW_UP))
            ->with(['student.classMemberships.classroom', 'temporaryStudent', 'followUpType', 'status'])
            ->latest('service_date');
        $followUpCount = (clone $followUpCases)->count();
        $etatib = ExternalTatibRecord::query()->active()->whereBetween('occurred_at', [$start, $end]);
        if ($mode === 'teacher') {
            $etatib->where(function (Builder $scope) use ($students, $caseIds): void {
                $scope->whereIn('student_id', (clone $students)->select('students.id'))
                    ->orWhereHas('cases', fn (Builder $cases): Builder => $cases->whereIn('cases.id', clone $caseIds));
            });
        } elseif ($mode === 'waka') {
            $etatib->whereHas('cases', fn (Builder $linkedCases): Builder => $linkedCases->whereIn('cases.id', clone $caseIds));
        }

        $scopeText = match ($mode) {
            'coordinator' => sprintf('Rekap tata kelola %d Guru BK aktif', User::query()->active()->whereHas('roles', fn ($roles) => $roles->where('slug', 'guru_bk')->where('is_active', true))->count()),
            'teacher' => $this->teacherScope($user, $year),
            default => 'Tampilan koordinasi hanya-baca dari seluruh kasus aktif sekolah',
        };
        $activeCases = (clone $cases)->whereNull('closed_at')->count();
        $stats = [
            ['label' => $mode === 'waka' ? 'Murid dalam pemantauan' : 'Murid dalam cakupan', 'value' => (string) $students->distinct()->count('students.id'), 'meta' => $mode === 'waka' ? 'Seluruh murid dengan kasus aktif' : 'Sesuai tahun ajaran dan kewenangan', 'tone' => 'primary', 'kind' => 'students'],
            ['label' => $mode === 'waka' ? 'Seluruh kasus aktif' : 'Kasus aktif', 'value' => (string) $activeCases, 'meta' => $mode === 'waka' ? 'Hanya-baca, ringkasan aman' : 'Belum diselesaikan', 'tone' => 'warning', 'kind' => 'cases'],
            ['label' => 'Kasus Tindak Lanjut', 'value' => (string) $followUpCount, 'meta' => 'Perlu ditindaklanjuti', 'tone' => 'success', 'kind' => 'schedule'],
            ['label' => 'Data e-Tatib terkait', 'value' => (string) $etatib->count(), 'meta' => 'Mirror read-only dalam kewenangan', 'tone' => 'info', 'kind' => 'etatib'],
        ];

        return [
            'role_key' => $mode,
            'label' => match ($mode) {
                'coordinator' => 'Koordinator BK', 'teacher' => 'Guru BK', default => 'Waka Kesiswaan'
            },
            'user_name' => $user->name,
            'scope' => $scopeText,
            'read_only' => $mode === 'waka',
            'description' => $mode === 'waka' ? 'Ringkasan seluruh kasus aktif sekolah — tampilan hanya-baca tanpa catatan internal atau konsultasi sensitif.' : 'Ringkasan operasional dari data layanan sesuai kewenangan Anda.',
            'stats' => $stats,
            'schedule_title' => $mode === 'waka' ? 'Kasus aktif sekolah' : 'Kasus Tindak Lanjut',
            'schedule_url' => $mode === 'waka' ? route('waka.monitoring.handling') : route('cases.index'),
            'tindak_lanjut' => $mode === 'waka'
                ? $this->caseItems((clone $cases)->latest('updated_at')->limit(6)->get())
                : $this->followUpItems($followUpCases->limit(6)->get()),
            'context_panel' => [
                'title' => match ($mode) {
                    'teacher' => 'Cakupan layanan Anda',
                    'coordinator' => 'Kesiapan penugasan BK',
                },
                'items' => match ($mode) {
                    'teacher' => $this->teacherCoverageItems($user, $year),
                    'coordinator' => $this->coordinatorCoverageItems($year),
                },
            ],
            'quick_actions' => $this->quickActions($mode),
        ];
    }

    /** @return array<string, mixed> */
    private function technical(User $user, ?AcademicYear $year): array
    {
        $syncRuns = ExternalSyncRun::query()->latest('started_at')->limit(6)->get();

        return [
            'role_key' => 'admin',
            'label' => 'Admin IT',
            'user_name' => $user->name,
            'scope' => 'Akun, sinkronisasi, konflik sumber, dan kesiapan integrasi',
            'read_only' => false,
            'description' => 'Ringkasan teknis tanpa membuka isi layanan BK.',
            'stats' => [
                ['label' => 'Akun aktif', 'value' => (string) User::query()->active()->count(), 'meta' => 'Seluruh peran aktif', 'tone' => 'primary', 'kind' => 'students'],
                ['label' => 'Akun nonaktif', 'value' => (string) User::query()->where('is_active', false)->count(), 'meta' => 'Tidak dapat masuk', 'tone' => 'warning', 'kind' => 'cases'],
                ['label' => 'Konflik belum selesai', 'value' => (string) ExternalSyncIssue::query()->whereNull('resolved_at')->count(), 'meta' => 'Dapodik dan e-Tatib', 'tone' => 'success', 'kind' => 'schedule'],
                ['label' => 'Tahun ajaran aktif', 'value' => (string) AcademicYear::query()->where('is_active', true)->count(), 'meta' => 'Baseline operasional', 'tone' => 'info', 'kind' => 'etatib'],
            ],
            'schedule_title' => 'Status sinkronisasi terbaru',
            'schedule_url' => route('data-master.index'),
            'tindak_lanjut' => $syncRuns->map(fn (ExternalSyncRun $run): array => [
                'date' => $run->started_at->format('d'),
                'month' => $run->started_at->locale('id')->translatedFormat('M'),
                'year' => $run->started_at->format('Y'),
                'code' => strtoupper($run->source).' #'.$run->id,
                'title' => $run->summary ?: 'Sinkronisasi sumber eksternal',
                'context_label' => sprintf('%d diproses, %d konflik', $run->processed_count, $run->conflict_count),
                'status' => $run->status,
                'status_tone' => $run->status === ExternalSyncRun::STATUS_FAILED ? 'danger' : 'info',
                'url' => route('data-master.index'),
            ])->all(),
            'context_panel' => [
                'title' => 'Kesiapan data dan integrasi',
                'items' => $this->technicalReadinessItems($year),
            ],
            'quick_actions' => [
                ['label' => 'Kelola akun', 'url' => route('admin.users.index'), 'primary' => true, 'icon' => 'account', 'tone' => 'primary'],
                ['label' => 'Buka data master', 'url' => route('data-master.index'), 'primary' => false, 'icon' => 'data', 'tone' => 'info'],
            ],
        ];
    }

    /** @return list<array{label: string, value: string, meta: string}> */
    private function teacherCoverageItems(User $user, ?AcademicYear $year): array
    {
        $assignments = TeacherAssignment::query()
            ->where('user_id', $user->getKey())
            ->effectiveOn(now())
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('academic_year_id', $selected->getKey()));
        $cases = BkCase::query()
            ->withinStudentServicePeriod()
            ->whereNull('closed_at')
            ->whereHas('assignments', fn (Builder $query): Builder => $query
                ->where('user_id', $user->getKey())
                ->where('assignment_type', 'owner')
                ->effectiveOn(now()))
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->whereBetween('service_date', [$selected->starts_on, $selected->ends_on]));
        $followUpCases = (clone $cases)->whereHas('status', fn (Builder $status): Builder => $status
            ->where('code', ServiceRecordStatus::NEEDS_FOLLOW_UP));

        return [
            ['label' => 'Kelas ampuan', 'value' => (string) $assignments->distinct()->count('classroom_id'), 'meta' => 'Penugasan efektif saat ini'],
            ['label' => 'Kasus khusus aktif', 'value' => (string) $cases->count(), 'meta' => 'Sebagai penanggung jawab'],
            ['label' => 'Kasus Tindak Lanjut', 'value' => (string) $followUpCases->count(), 'meta' => 'Perlu ditindaklanjuti'],
        ];
    }

    /** @return list<array{label: string, value: string, meta: string}> */
    private function coordinatorCoverageItems(?AcademicYear $year): array
    {
        $unassignedClasses = Classroom::query()->active()
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('academic_year_id', $selected->getKey()))
            ->whereDoesntHave('teacherAssignments', fn (Builder $query): Builder => $query->effectiveOn(now()));
        $openFollowUps = BkCase::query()->whereHas('status', fn (Builder $status): Builder => $status
            ->where('code', ServiceRecordStatus::NEEDS_FOLLOW_UP))
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->whereBetween('service_date', [$selected->starts_on, $selected->ends_on]));

        return [
            ['label' => 'Guru BK aktif', 'value' => (string) User::query()->active()->whereHas('roles', fn (Builder $roles): Builder => $roles->where('slug', 'guru_bk')->where('is_active', true))->count(), 'meta' => 'Siap menerima penugasan'],
            ['label' => 'Kelas tanpa penugasan', 'value' => (string) $unassignedClasses->count(), 'meta' => 'Belum memiliki Guru BK efektif'],
            ['label' => 'Kasus Tindak Lanjut', 'value' => (string) $openFollowUps->count(), 'meta' => 'Perlu ditindaklanjuti'],
        ];
    }

    /** @return list<array{label: string, value: string, meta: string}> */
    private function technicalReadinessItems(?AcademicYear $year): array
    {
        $configuredProviders = IntegrationSetting::query()
            ->whereIn('provider', IntegrationSetting::PROVIDERS)
            ->whereNotNull('credentials')
            ->distinct()
            ->count('provider');

        return [
            ['label' => 'Akun aktif', 'value' => (string) User::query()->active()->count(), 'meta' => 'Seluruh peran operasional'],
            ['label' => 'Konflik sinkronisasi', 'value' => (string) ExternalSyncIssue::query()->whereNull('resolved_at')->count(), 'meta' => 'Belum diselesaikan'],
            ['label' => 'Tahun ajaran aktif', 'value' => $year?->name ?? 'Belum ada', 'meta' => 'Periode dashboard'],
            ['label' => 'Provider tanpa credential', 'value' => (string) (count(IntegrationSetting::PROVIDERS) - $configuredProviders), 'meta' => 'Dapodik dan e-Tatib'],
        ];
    }

    /** @return array{string, string} */
    private function period(?AcademicYear $year): array
    {
        return [
            $year?->starts_on?->toDateString() ?? now()->startOfYear()->toDateString(),
            $year?->ends_on?->toDateString() ?? now()->endOfYear()->toDateString(),
        ];
    }

    private function teacherScope(User $user, ?AcademicYear $year): string
    {
        $classes = $user->teacherAssignments()->effectiveOn(now())
            ->when($year, fn (Builder $assignments, AcademicYear $selected): Builder => $assignments->where('academic_year_id', $selected->getKey()))
            ->with('classroom')->get()->pluck('classroom.name')->filter()->join(', ');

        return $classes === '' ? 'Penugasan kasus khusus aktif' : 'Kelas '.$classes.' dan penugasan kasus khusus';
    }

    /** @param Collection<int, BkCase> $cases @return list<array<string, mixed>> */
    private function followUpItems(Collection $cases): array
    {
        return $cases->map(function (BkCase $case): array {
            $membership = $case->student?->classMemberships->sortByDesc('effective_from')->first();

            return [
                'date' => $case->service_date->format('d'),
                'month' => $case->service_date->locale('id')->translatedFormat('M'),
                'year' => $case->service_date->format('Y'),
                'code' => 'Layanan kasus',
                'title' => $case->followUpType?->label ?? 'Tindak Lanjut',
                'context_label' => sprintf('%s (%s)', $case->identityName(), $membership?->classroom?->name ?? 'tanpa kelas aktif'),
                'status' => $case->status->label,
                'status_tone' => 'warning',
                'url' => route('cases.show', $case),
            ];
        })->all();
    }

    /** @param Collection<int, BkCase> $cases @return list<array<string, mixed>> */
    private function caseItems(Collection $cases): array
    {
        return $cases->map(function (BkCase $case): array {
            return [
                'date' => $case->service_date->format('d'),
                'month' => $case->service_date->locale('id')->translatedFormat('M'),
                'year' => $case->service_date->format('Y'),
                'code' => 'Layanan kasus',
                'title' => 'Kasus layanan BK',
                'context_label' => $case->identityName(),
                'status' => $case->status->label,
                'status_tone' => $case->closed_at === null ? 'warning' : 'success',
                'url' => route('cases.show', $case),
            ];
        })->all();
    }

    /** @return list<array{label: string, url: string, primary: bool, icon: string, tone: string}> */
    private function quickActions(string $mode): array
    {
        if ($mode === 'waka') {
            return [
                ['label' => 'Lihat kasus koordinasi', 'url' => route('cases.index'), 'primary' => false, 'icon' => 'case', 'tone' => 'primary'],
                ['label' => 'Buka laporan', 'url' => route('reports.index'), 'primary' => false, 'icon' => 'report', 'tone' => 'info'],
            ];
        }

        return [
            ['label' => 'Buat Kasus', 'url' => route('cases.create'), 'primary' => true, 'icon' => 'case', 'tone' => 'primary'],
            ['label' => 'Catat Konsultasi', 'url' => route('consultations.create'), 'primary' => false, 'icon' => 'consultation', 'tone' => 'success'],
            ['label' => 'Laporan', 'url' => route('reports.index'), 'primary' => false, 'icon' => 'report', 'tone' => 'info'],
        ];
    }
}
