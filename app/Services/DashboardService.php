<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
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
                    ->where(function (Builder $scope) use ($user, $selected): void {
                        $scope->whereHas('classMemberships', fn (Builder $memberships): Builder => $memberships
                            ->where('academic_year_id', $selected->getKey()))
                            ->orWhereHas('cases.assignments', fn (Builder $assignments): Builder => $assignments
                                ->where('user_id', $user->getKey())
                                ->whereHas('case', fn (Builder $case): Builder => $case
                                    ->where('academic_year_id', $selected->getKey())));
                    }));
        } else {
            // Waka melihat SELURUH kasus aktif sekolah — bukan hanya yang terkoordinasi
            $cases->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('academic_year_id', $selected->getKey()));
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
            'coordinator' => sprintf('%d Guru BK aktif', User::query()->active()->whereHas('roles', fn ($roles) => $roles->where('slug', 'guru_bk')->where('is_active', true))->count()),
            'teacher' => $this->teacherScope($user, $year),
            default => 'Ringkasan permasalahan aktif sekolah',
        };
        $activeCases = (clone $cases)->whereNull('closed_at')->count();
        $stats = [
            ['label' => $mode === 'waka' ? 'Murid dalam pemantauan' : 'Murid dalam cakupan', 'value' => (string) $students->distinct()->count('students.id'), 'meta' => $mode === 'waka' ? 'Seluruh murid dengan permasalahan aktif' : 'Sesuai tahun ajaran dan kewenangan', 'tone' => 'primary', 'kind' => 'students'],
            ['label' => $mode === 'waka' ? 'Seluruh permasalahan aktif' : 'Permasalahan aktif', 'value' => (string) $activeCases, 'meta' => $mode === 'waka' ? 'Hanya-baca, ringkasan aman' : 'Belum diselesaikan', 'tone' => 'warning', 'kind' => 'cases'],
            ['label' => 'Permasalahan Tindak Lanjut', 'value' => (string) $followUpCount, 'meta' => 'Perlu ditindaklanjuti', 'tone' => 'success', 'kind' => 'schedule'],
            ['label' => 'Data e-Tatib terkait', 'value' => (string) $etatib->count(), 'meta' => 'Sesuai akses Anda', 'tone' => 'info', 'kind' => 'etatib'],
        ];

        return [
            'role_key' => $mode,
            'label' => match ($mode) {
                'coordinator' => 'Koordinator BK', 'teacher' => 'Guru BK', default => 'Waka Kesiswaan'
            },
            'user_name' => $user->name,
            'scope' => $scopeText,
            'read_only' => $mode === 'waka',
            'description' => $mode === 'waka' ? 'Ringkasan seluruh permasalahan aktif sekolah — tampilan hanya-baca tanpa catatan internal atau konsultasi sensitif.' : 'Ringkasan operasional dari data layanan sesuai kewenangan Anda.',
            'stats' => $stats,
            'schedule_title' => match ($mode) {
                'waka' => 'Permasalahan aktif sekolah',
                'teacher' => 'Aktivitas Terbaru',
                default => 'Permasalahan Tindak Lanjut',
            },
            'schedule_url' => $mode === 'waka' ? route('waka.monitoring.handling') : route('cases.index'),
            'schedule_empty_title' => match ($mode) {
                'teacher' => 'Belum ada aktivitas',
                default => 'Tidak ada tindak lanjut',
            },
            'schedule_empty_description' => match ($mode) {
                'teacher' => 'Belum ada aktivitas terbaru dari kelas yang Anda ampu.',
                default => 'Tidak ada permasalahan berstatus Tindak Lanjut.',
            },
            'tindak_lanjut' => match ($mode) {
                'waka' => $this->caseItems((clone $cases)->latest('updated_at')->limit(6)->get()),
                'teacher' => $this->teacherLatestActivities($user, $year),
                default => $this->followUpItems($followUpCases->limit(6)->get()),
            },
            'context_panel' => [
                'title' => match ($mode) {
                    'teacher' => 'Kelas Ampuan',
                    'coordinator' => 'Kesiapan penugasan BK',
                },
                'items' => match ($mode) {
                    'teacher' => $this->teacherAssignedClasses($user, $year),
                    'coordinator' => $this->coordinatorCoverageItems($year),
                },
                'empty_title' => match ($mode) {
                    'teacher' => 'Belum ada kelas ampuan',
                    default => 'Tidak ada data',
                },
                'empty_description' => match ($mode) {
                    'teacher' => 'Anda belum memiliki penugasan kelas pada tahun ajaran ini.',
                    default => 'Tidak ada informasi untuk ditampilkan.',
                },
            ],
            'quick_actions' => $this->quickActions($mode),
        ];
    }

    /** @return array<string, mixed> */
    private function technical(User $user, ?AcademicYear $year): array
    {
        $syncRuns = ExternalSyncRun::query()->latest('started_at')->limit(6)->get();
        $etatibSetting = IntegrationSetting::query()
            ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
            ->first();
        $lastAutomaticEtatibRun = ExternalSyncRun::query()
            ->where('source', 'etatib')
            ->whereNull('triggered_by')
            ->latest('started_at')
            ->first();
        $automaticSyncFailed = (bool) $etatibSetting?->automatic_sync_enabled
            && $lastAutomaticEtatibRun?->status === ExternalSyncRun::STATUS_FAILED;

        return [
            'role_key' => 'admin',
            'label' => 'Admin IT',
            'user_name' => $user->name,
            'scope' => 'Akun, sinkronisasi, konflik sumber, dan kesiapan integrasi',
            'read_only' => false,
            'description' => 'Ringkasan teknis tanpa membuka isi layanan BK.',
            'alerts' => $automaticSyncFailed ? [[
                'tone' => 'danger',
                'title' => 'Pembaruan otomatis e-Tatib gagal',
                'message' => $lastAutomaticEtatibRun?->summary ?? 'Periksa status sinkronisasi pada Data Master.',
                'url' => route('data-master.index'),
            ]] : [],
            'stats' => [
                ['label' => 'Akun aktif', 'value' => (string) User::query()->active()->count(), 'meta' => 'Seluruh peran aktif', 'tone' => 'primary', 'kind' => 'students'],
                ['label' => 'Akun nonaktif', 'value' => (string) User::query()->where('is_active', false)->count(), 'meta' => 'Tidak dapat masuk', 'tone' => 'warning', 'kind' => 'cases'],
                ['label' => 'Konflik belum selesai', 'value' => (string) ExternalSyncIssue::query()->whereNull('resolved_at')->count(), 'meta' => 'Dapodik dan e-Tatib', 'tone' => 'success', 'kind' => 'schedule', 'url' => route('data-master.index', ['tab' => 'sinkronisasi'])],
                ['label' => 'Tahun ajaran aktif', 'value' => (string) AcademicYear::query()->where('is_active', true)->count(), 'meta' => 'Baseline operasional', 'tone' => 'info', 'kind' => 'etatib'],
            ],
            'schedule_title' => 'Status sinkronisasi terbaru',
            'schedule_url' => route('data-master.index', ['tab' => 'sinkronisasi']),
            'tindak_lanjut' => $syncRuns->map(fn (ExternalSyncRun $run): array => [
                'date' => $run->started_at->format('d'),
                'month' => $run->started_at->locale('id')->translatedFormat('M'),
                'year' => $run->started_at->format('Y'),
                'code' => strtoupper($run->source).' #'.$run->id,
                'title' => $run->summary ?: 'Sinkronisasi sumber eksternal',
                'context_label' => sprintf('%d diproses, %d konflik', $run->processed_count, $run->conflict_count),
                'status' => $run->status,
                'status_tone' => $run->status === ExternalSyncRun::STATUS_FAILED ? 'danger' : 'info',
                'url' => route('data-master.index', ['tab' => 'sinkronisasi']),
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

    /** @return list<array{label: string, value: string, meta: string, url: string}> */
    private function teacherAssignedClasses(User $user, ?AcademicYear $year): array
    {
        $assignments = TeacherAssignment::query()
            ->where('user_id', $user->getKey())
            ->when($year,
                fn (Builder $query, AcademicYear $selected): Builder => $query->where('academic_year_id', $selected->getKey()),
                fn (Builder $query): Builder => $query->inActiveYear()
            )
            ->with(['classroom' => fn ($query) => $query
                ->withCount(['studentClassMemberships as student_count' => fn (Builder $memberships): Builder => $memberships
                    ->active()
                    ->when($year,
                        fn (Builder $m, AcademicYear $selected): Builder => $m->where('academic_year_id', $selected->getKey()),
                        fn (Builder $m): Builder => $m->inActiveYear()
                    )
                    ->whereHas('student', fn (Builder $students): Builder => $students->active())
                ])
            ])
            ->get();

        $classrooms = $assignments
            ->pluck('classroom')
            ->filter()
            ->unique('id')
            ->sortBy('name', SORT_NATURAL);

        return $classrooms->map(function (Classroom $classroom): array {
            $metaParts = [];
            if ($classroom->grade_level) {
                $metaParts[] = 'Tingkat '.$classroom->grade_level;
            }
            if ($classroom->major) {
                $metaParts[] = $classroom->major;
            }

            return [
                'label' => $classroom->name,
                'value' => sprintf('%d murid', $classroom->student_count ?? 0),
                'meta' => ! empty($metaParts) ? implode(' • ', $metaParts) : 'Kelas aktif',
                'url' => route('students.index', ['classroom_id' => $classroom->getKey()]),
            ];
        })->values()->all();
    }

    /** @return list<array<string, mixed>> */
    private function teacherLatestActivities(User $user, ?AcademicYear $year): array
    {
        $assignedClassroomIds = TeacherAssignment::query()
            ->where('user_id', $user->getKey())
            ->when($year,
                fn (Builder $query, AcademicYear $selected): Builder => $query->where('academic_year_id', $selected->getKey()),
                fn (Builder $query): Builder => $query->inActiveYear()
            )
            ->pluck('classroom_id');

        if ($assignedClassroomIds->isEmpty()) {
            return [];
        }

        $cases = BkCase::query()
            ->accessibleTo($user)
            ->whereIn('classroom_id', $assignedClassroomIds)
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('academic_year_id', $selected->getKey()))
            ->with(['student', 'temporaryStudent', 'classroom', 'status', 'serviceField', 'followUpType'])
            ->latest('created_at')
            ->latest('id')
            ->limit(6)
            ->get();

        $consultations = Consultation::query()
            ->accessibleTo($user)
            ->whereIn('classroom_id', $assignedClassroomIds)
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('academic_year_id', $selected->getKey()))
            ->with(['student', 'temporaryStudent', 'classroom', 'serviceField'])
            ->latest('created_at')
            ->latest('id')
            ->limit(6)
            ->get();

        $caseItems = $cases->map(function (BkCase $case): array {
            $date = $case->service_date ?? $case->created_at;
            $statusTone = match ($case->status?->code) {
                ServiceRecordStatus::COMPLETED => 'success',
                ServiceRecordStatus::NEEDS_FOLLOW_UP => 'warning',
                default => 'primary',
            };

            return [
                'timestamp' => $case->created_at?->timestamp ?? 0,
                'date' => $date->format('d'),
                'month' => $date->locale('id')->translatedFormat('M'),
                'year' => $date->format('Y'),
                'code' => 'Layanan permasalahan',
                'title' => $case->followUpType?->label
                    ?? ($case->serviceField?->label ? 'Layanan '.$case->serviceField->label : 'Permasalahan layanan BK'),
                'context_label' => sprintf('%s (%s)', $case->identityName(), $case->classroom?->name ?? 'tanpa kelas'),
                'status' => $case->status?->label ?? 'Aktif',
                'status_tone' => $statusTone,
                'url' => route('cases.show', $case),
            ];
        });

        $consultationItems = $consultations->map(function (Consultation $consultation): array {
            $date = $consultation->session_date ?? $consultation->created_at;

            return [
                'timestamp' => $consultation->created_at?->timestamp ?? 0,
                'date' => $date->format('d'),
                'month' => $date->locale('id')->translatedFormat('M'),
                'year' => $date->format('Y'),
                'code' => 'Layanan konsultasi',
                'title' => $consultation->serviceField?->label ? 'Konsultasi '.$consultation->serviceField->label : 'Sesi konsultasi',
                'context_label' => sprintf('%s (%s)', $consultation->identityName(), $consultation->classroom?->name ?? 'tanpa kelas'),
                'status' => 'Konsultasi',
                'status_tone' => 'info',
                'url' => route('consultations.show', $consultation),
            ];
        });

        return $caseItems->concat($consultationItems)
            ->sortByDesc('timestamp')
            ->take(6)
            ->values()
            ->all();
    }

    /** @return list<array{label: string, value: string, meta: string}> */
    private function coordinatorCoverageItems(?AcademicYear $year): array
    {
        $unassignedClasses = Classroom::query()->active()
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('academic_year_id', $selected->getKey()))
            ->whereDoesntHave('teacherAssignments');
        $openFollowUps = BkCase::query()->whereHas('status', fn (Builder $status): Builder => $status
            ->where('code', ServiceRecordStatus::NEEDS_FOLLOW_UP))
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('academic_year_id', $selected->getKey()));

        return [
            ['label' => 'Guru BK aktif', 'value' => (string) User::query()->active()->whereHas('roles', fn (Builder $roles): Builder => $roles->where('slug', 'guru_bk')->where('is_active', true))->count(), 'meta' => 'Siap menerima penugasan'],
            ['label' => 'Kelas tanpa penugasan', 'value' => (string) $unassignedClasses->count(), 'meta' => 'Belum memiliki Guru BK efektif'],
            ['label' => 'Permasalahan Tindak Lanjut', 'value' => (string) $openFollowUps->count(), 'meta' => 'Perlu ditindaklanjuti'],
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
        $classes = $user->teacherAssignments()->inActiveYear()
            ->when($year, fn (Builder $assignments, AcademicYear $selected): Builder => $assignments->where('academic_year_id', $selected->getKey()))
            ->with('classroom')->get()->pluck('classroom.name')->filter()->join(', ');

        return $classes === '' ? 'Permasalahan khusus yang ditugaskan' : 'Kelas '.$classes.' dan permasalahan khusus yang ditugaskan';
    }

    /** @param Collection<int, BkCase> $cases @return list<array<string, mixed>> */
    private function followUpItems(Collection $cases): array
    {
        return $cases->map(function (BkCase $case): array {
            return [
                'date' => $case->service_date->format('d'),
                'month' => $case->service_date->locale('id')->translatedFormat('M'),
                'year' => $case->service_date->format('Y'),
                'code' => 'Layanan permasalahan',
                'title' => $case->followUpType?->label ?? 'Tindak Lanjut',
                'context_label' => sprintf('%s (%s)', $case->identityName(), $case->classroom?->name ?? 'tanpa kelas'),
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
                'code' => 'Layanan permasalahan',
                'title' => 'Permasalahan layanan BK',
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
                ['label' => 'Lihat permasalahan koordinasi', 'url' => route('cases.index'), 'primary' => false, 'icon' => 'case', 'tone' => 'primary'],
                ['label' => 'Buka laporan', 'url' => route('reports.index'), 'primary' => false, 'icon' => 'report', 'tone' => 'info'],
            ];
        }

        return [
            ['label' => 'Catat Permasalahan', 'url' => route('cases.create'), 'primary' => true, 'icon' => 'case', 'tone' => 'primary'],
            ['label' => 'Catat Konsultasi', 'url' => route('consultations.create'), 'primary' => true, 'icon' => 'consultation', 'tone' => 'primary'],
            ['label' => 'Laporan', 'url' => route('reports.index'), 'primary' => true, 'icon' => 'report', 'tone' => 'primary'],
        ];
    }
}
