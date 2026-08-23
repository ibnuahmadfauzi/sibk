<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\Correction;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\FollowUp;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardService
{
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
            return $this->operational($user, $academicYear, 'waka');
        }

        return $this->technical($user);
    }

    /** @return array<string, mixed> */
    private function operational(User $user, ?AcademicYear $year, string $mode): array
    {
        [$start, $end] = $this->period($year);
        $cases = BkCase::query()->with(['student.classMemberships.classroom', 'temporaryStudent', 'status']);
        if ($mode === 'coordinator') {
            $students = Student::query()->active()
                ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                    ->whereHas('classMemberships', fn (Builder $memberships): Builder => $memberships
                        ->where('academic_year_id', $selected->getKey())));
        } elseif ($mode === 'teacher') {
            $cases->accessibleTo($user);
            $students = Student::query()->active()->professionallyAccessibleTo($user)
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
            $cases->whereHas('coordinations', fn (Builder $coordinations): Builder => $coordinations
                ->where('waka_user_id', $user->getKey()));
            $cases->whereBetween('service_date', [$start, $end]);
            $students = Student::query()->active()->whereIn('id', (clone $cases)
                ->whereNotNull('student_id')->select('student_id'));
        }
        if ($mode !== 'waka') {
            $cases->whereBetween('service_date', [$start, $end]);
        }
        $caseIds = (clone $cases)->select('cases.id');

        $upcoming = FollowUp::query()->whereIn('case_id', clone $caseIds)
            ->whereBetween('planned_date', [today(), today()->addDays(14)])
            ->whereHas('status', fn (Builder $status): Builder => $status->where('code', '!=', 'dibatalkan'))
            ->with(['case.student.classMemberships.classroom', 'case.temporaryStudent', 'type', 'status'])
            ->orderBy('planned_date');
        $upcomingCount = (clone $upcoming)->whereDate('planned_date', '<=', today()->addDays(7))->count();
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
            default => 'Kasus yang secara eksplisit dikoordinasikan kepada Anda',
        };
        $activeCases = (clone $cases)->whereNull('closed_at')->count();
        $stats = [
            ['label' => $mode === 'waka' ? 'Murid terkoordinasi' : 'Murid dalam cakupan', 'value' => (string) $students->distinct()->count('students.id'), 'meta' => 'Sesuai tahun ajaran dan kewenangan', 'tone' => 'primary', 'kind' => 'students'],
            ['label' => $mode === 'waka' ? 'Kasus terkoordinasi' : 'Kasus aktif', 'value' => (string) $activeCases, 'meta' => $mode === 'waka' ? 'Seluruhnya hanya-baca' : 'Belum diselesaikan', 'tone' => 'warning', 'kind' => 'cases'],
            ['label' => 'Tindak lanjut terdekat', 'value' => (string) $upcomingCount, 'meta' => 'Dalam tujuh hari ke depan', 'tone' => 'success', 'kind' => 'schedule'],
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
            'description' => $mode === 'waka' ? 'Ringkasan kasus yang dikoordinasikan tanpa catatan internal atau konsultasi sensitif.' : 'Ringkasan operasional dari data layanan sesuai kewenangan Anda.',
            'stats' => $stats,
            'schedule_title' => $mode === 'waka' ? 'Kasus terkoordinasi' : 'Tindak lanjut terdekat',
            'schedule_url' => route('cases.index'),
            'tindak_lanjut' => $mode === 'waka'
                ? $this->coordinatedCaseItems((clone $cases)->latest('updated_at')->limit(6)->get(), $user)
                : $this->followUpItems($upcoming->limit(6)->get()),
            'activities' => $this->activityItems($this->scopedAuditQuery($user)->latest()->limit(8)->get()),
            'quick_actions' => $this->quickActions($mode),
        ];
    }

    /** @return array<string, mixed> */
    private function technical(User $user): array
    {
        $syncRuns = ExternalSyncRun::query()->latest('started_at')->limit(6)->get();
        $pendingMaster = Correction::query()->where('correction_type', Correction::TYPE_MASTER)
            ->whereHas('status', fn (Builder $status): Builder => $status->whereIn('code', ['menunggu', 'diproses']))->count();

        return [
            'role_key' => 'admin',
            'label' => 'Admin IT',
            'user_name' => $user->name,
            'scope' => 'Akun, sinkronisasi, konflik sumber, dan koreksi master',
            'read_only' => false,
            'description' => 'Ringkasan teknis tanpa membuka isi layanan BK.',
            'stats' => [
                ['label' => 'Akun aktif', 'value' => (string) User::query()->active()->count(), 'meta' => 'Seluruh peran aktif', 'tone' => 'primary', 'kind' => 'students'],
                ['label' => 'Akun nonaktif', 'value' => (string) User::query()->where('is_active', false)->count(), 'meta' => 'Tidak dapat masuk', 'tone' => 'warning', 'kind' => 'cases'],
                ['label' => 'Konflik belum selesai', 'value' => (string) ExternalSyncIssue::query()->whereNull('resolved_at')->count(), 'meta' => 'Dapodik dan e-Tatib', 'tone' => 'success', 'kind' => 'schedule'],
                ['label' => 'Koreksi master aktif', 'value' => (string) $pendingMaster, 'meta' => 'Menunggu sumber resmi', 'tone' => 'info', 'kind' => 'etatib'],
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
            'activities' => $this->activityItems($this->scopedAuditQuery($user)->latest()->limit(8)->get()),
            'quick_actions' => [
                ['label' => 'Kelola akun', 'url' => route('admin.users.index'), 'primary' => true],
                ['label' => 'Buka data master', 'url' => route('data-master.index'), 'primary' => false],
                ['label' => 'Koreksi master', 'url' => route('corrections.index', ['correction_type' => 'master']), 'primary' => false],
            ],
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

    /** @param Collection<int, FollowUp> $followUps @return list<array<string, mixed>> */
    private function followUpItems(Collection $followUps): array
    {
        return $followUps->map(function (FollowUp $followUp): array {
            $membership = $followUp->case?->student?->classMemberships->sortByDesc('effective_from')->first();

            return [
                'date' => $followUp->planned_date->format('d'),
                'month' => $followUp->planned_date->locale('id')->translatedFormat('M'),
                'year' => $followUp->planned_date->format('Y'),
                'code' => $followUp->case?->registration_number ?? 'Kasus',
                'title' => $followUp->type->label,
                'context_label' => sprintf('%s (%s)', $followUp->case?->identityName(), $membership?->classroom?->name ?? 'tanpa kelas aktif'),
                'status' => $followUp->status->label,
                'status_tone' => $followUp->status->code === 'terlaksana' ? 'success' : 'warning',
                'url' => route('cases.show', $followUp->case_id),
            ];
        })->all();
    }

    /** @param Collection<int, BkCase> $cases @return list<array<string, mixed>> */
    private function coordinatedCaseItems(Collection $cases, User $user): array
    {
        return $cases->map(function (BkCase $case) use ($user): array {
            $coordination = $case->coordinations()->where('waka_user_id', $user->getKey())->latest('coordinated_at')->first();

            return [
                'date' => $coordination?->coordinated_at?->format('d') ?? $case->service_date->format('d'),
                'month' => $coordination?->coordinated_at?->locale('id')->translatedFormat('M') ?? $case->service_date->locale('id')->translatedFormat('M'),
                'year' => $coordination?->coordinated_at?->format('Y') ?? $case->service_date->format('Y'),
                'code' => $case->registration_number,
                'title' => 'Koordinasi kesiswaan',
                'context_label' => $case->identityName(),
                'status' => $case->status->label,
                'status_tone' => $case->closed_at === null ? 'warning' : 'success',
                'url' => route('cases.show', $case),
            ];
        })->all();
    }

    /** @param Collection<int, AuditLog> $logs @return list<array<string, mixed>> */
    private function activityItems(Collection $logs): array
    {
        return $logs->map(fn (AuditLog $log): array => [
            'icon' => str_contains($log->action, 'case') ? 'case-new' : (str_contains($log->action, 'sync') ? 'etatib' : 'followup'),
            'title' => $log->summary,
            'context' => $log->action,
            'time' => $log->created_at->locale('id')->diffForHumans(),
            'tone' => str_contains($log->action, 'failed') ? 'warning' : 'primary',
        ])->all();
    }

    /** @return Builder<AuditLog> */
    private function scopedAuditQuery(User $user): Builder
    {
        $query = AuditLog::query();
        if ($user->hasRole('koordinator_bk')) {
            return $query->where(function (Builder $governance): void {
                foreach (['case.', 'follow_up.', 'consultation.', 'class_assignment.', 'case_assignment.'] as $prefix) {
                    $governance->orWhere('action', 'like', $prefix.'%');
                }
            });
        }
        if ($user->hasAnyRole(['guru_bk', 'waka_kesiswaan'])) {
            return $query->where('actor_id', $user->getKey());
        }

        return $query->where(function (Builder $technical): void {
            foreach (['auth.', 'account.', 'dapodik.', 'etatib.', 'identity.', 'correction.master_'] as $prefix) {
                $technical->orWhere('action', 'like', $prefix.'%');
            }
        });
    }

    /** @return list<array{label: string, url: string, primary: bool}> */
    private function quickActions(string $mode): array
    {
        if ($mode === 'waka') {
            return [
                ['label' => 'Lihat kasus koordinasi', 'url' => route('cases.index'), 'primary' => false],
                ['label' => 'Buka laporan', 'url' => route('reports.index'), 'primary' => false],
            ];
        }

        return [
            ['label' => 'Lihat daftar kasus', 'url' => route('cases.index'), 'primary' => false],
            ['label' => 'Cari profil murid', 'url' => route('students.index'), 'primary' => true],
            ['label' => 'Buka laporan', 'url' => route('reports.index'), 'primary' => false],
        ];
    }
}
