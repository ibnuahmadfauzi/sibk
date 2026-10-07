<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ExternalTatibRecord;
use App\Models\Student;
use App\Models\StudentDeparture;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('viewAny', Student::class), 403);

        $query = Student::query()
            ->availableForService()
            ->accessibleTo($user)
            ->withCount(['achievements' => fn ($achievements) => $achievements->accessibleTo($user)])
            ->withCount([
                'cases as active_cases_count' => fn ($cases) => $cases->accessibleTo($user)->whereNull('closed_at'),
                'consultations as consultations_count' => fn ($consultations) => $consultations->accessibleTo($user),
            ])
            ->withSum(['etatibRecords as tatib_points_sum' => fn ($etatib) => $etatib->whereNull('source_deleted_at')], 'points')
            ->with([
                'classMemberships' => fn ($memberships) => $memberships
                    ->active()
                    ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                    ->with('classroom.academicYear'),
                'cases' => fn ($cases) => $cases
                    ->accessibleTo($user),
                'consultations' => fn ($consultations) => $consultations
                    ->accessibleTo($user),
                'etatibRecords' => fn ($etatib) => $etatib
                    ->whereNull('source_deleted_at')
                    ->latest('occurred_at'),
            ]);
        $search = $request->string('search')->trim()->toString();
        $query->when($search, fn ($students) => $students->where(function ($filter) use ($search): void {
            $filter->where('name', 'like', '%'.$search.'%')->orWhere('nisn', 'like', '%'.$search.'%');
        }));
        $query->when($request->integer('classroom_id'), fn ($students, int $classroomId) => $students
            ->whereHas('classMemberships', fn ($memberships) => $memberships
                ->where('classroom_id', $classroomId)
                ->active()
                ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))));

        if ($user->hasRole('guru_bk') && ! $user->hasRole('koordinator_bk')) {
            $assignedClassroomIds = TeacherAssignment::query()
                ->where('user_id', $user->getKey())
                ->inActiveYear()
                ->pluck('classroom_id');
            $classroomQuery = Classroom::query()
                ->active()
                ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                ->whereIn('id', $assignedClassroomIds)
                ->orderBy('name');
        } else {
            $classroomQuery = Classroom::query()
                ->active()
                ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                ->orderBy('name');
        }

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString();
        $columns = ['murid' => 'name', 'permasalahan' => 'active_cases_count', 'poin' => 'tatib_points_sum', 'konsultasi' => 'consultations_count', 'prestasi' => 'achievements_count'];
        if (isset($columns[$sort]) && in_array($direction, ['asc', 'desc'], true)) {
            $query->orderBy($columns[$sort], $direction)->orderBy('students.id');
        } else {
            $query->orderBy('name')->orderBy('students.id');
        }

        return view('pages.students.index', [
            'students' => $query->paginate(20)->withQueryString(),
            'classrooms' => $classroomQuery->get(),
        ]);
    }

    public function show(Request $request, Student $student): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('view', $student), 403);
        $student->load(['classMemberships.classroom.academicYear', 'departure.recorder', 'departure.finalizer']);
        $currentMembership = $student->classMemberships()
            ->inActiveYear()
            ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
            ->with(['classroom', 'academicYear'])
            ->first();

        $activeTab = $request->string('tab', 'kasus')->toString();
        $allowedTabs = ['ringkasan', 'kasus', 'etatib', 'konsultasi', 'prestasi'];
        if (in_array($activeTab, $allowedTabs, true) === false) {
            $activeTab = 'kasus';
        }
        if ($user->hasRole('waka_kesiswaan') && $user->hasAnyRole(['guru_bk', 'koordinator_bk']) === false && $activeTab === 'konsultasi') {
            $activeTab = 'kasus';
        }

        $cases = BkCase::query()
            ->accessibleTo($user)
            ->where('student_id', $student->getKey())
            ->with(['source', 'serviceField', 'status', 'classroom', 'assignments.teacher', 'followUps.followUpType', 'followUpType'])
            ->latest('service_date')
            ->get();
        $etatibQuery = ExternalTatibRecord::query()
            ->with(['latestClassroomIssue', 'student.classMemberships' => fn ($memberships) => $memberships
                ->active()->with(['academicYear:id,starts_on,ends_on', 'classroom:id,name'])])
            ->where('student_id', $student->getKey());
        if ($user->hasRole('waka_kesiswaan') && $user->hasAnyRole(['guru_bk', 'koordinator_bk']) === false) {
            $etatibQuery->whereHas('cases', fn ($cases) => $cases->accessibleTo($user));
        }
        $etatibRecords = $etatibQuery->latest('occurred_at')->get();
        $officialEtatibTotal = $etatibRecords
            ->whereNull('source_deleted_at')
            ->sortByDesc('id')->sortByDesc('synced_at')
            ->first(fn (ExternalTatibRecord $record): bool => $record->source_total_points !== null)
            ?->source_total_points;

        $consultations = collect();
        if ($user->can('viewAny', Consultation::class)) {
            $consultations = Consultation::query()
                ->accessibleTo($user)
                ->where(function ($identity) use ($student): void {
                    $identity->where('student_id', $student->getKey())
                        ->orWhereHas('temporaryStudent', fn ($temporary) => $temporary
                            ->where('reconciled_student_id', $student->getKey()));
                })
                ->with(['serviceField', 'counselor', 'classroom'])
                ->latest('session_date')
                ->get();
        }

        $achievements = Achievement::query()
            ->accessibleTo($user)
            ->where('student_id', $student->getKey())
            ->with(['type', 'level'])
            ->latest('achievement_date')
            ->get();

        $canUseProfessionalActions = $user->can('viewSensitive', $student)
            && Student::query()->availableForService()->whereKey($student->getKey())->exists();
        $departure = $student->departure;

        return view('pages.students.show', [
            'student' => $student,
            'activeTab' => $activeTab,
            'cases' => $cases,
            'etatibRecords' => $etatibRecords,
            'consultations' => $consultations,
            'memberships' => $student->classMemberships->sortByDesc(
                fn ($membership) => $membership->academicYear?->name ?? '',
            ),
            'currentMembership' => $currentMembership,
            'stats' => [
                'cases' => $cases->count(),
                'consultations' => $consultations->count(),
                'points' => $etatibRecords->whereNull('source_deleted_at')->sum('points'),
                'source_points' => $officialEtatibTotal,
                'last_synced_at' => $etatibRecords->max('synced_at'),
                'achievements' => $achievements->count(),
            ],
            'recentActivities' => $this->recentActivities($cases, $consultations, $etatibRecords, $achievements),
            'achievements' => $achievements,
            'canViewConsultations' => $user->can('viewAny', Consultation::class),
            'canCreateConsultation' => $canUseProfessionalActions && $user->can('create', Consultation::class),
            'canCreateCase' => $canUseProfessionalActions && $user->can('create', BkCase::class),
            'departure' => $departure,
            'canCreateDeparture' => ($departure === null || $departure->status === StudentDeparture::STATUS_CANCELLED)
                && $user->can('create', [StudentDeparture::class, $student]),
            'canUpdateDeparture' => $departure !== null && $user->can('update', $departure),
            'canFinalizeDeparture' => $departure !== null
                && $departure->status === StudentDeparture::STATUS_IN_PROGRESS
                && $user->can('finalize', $departure),
            'isWakaSummary' => $user->hasRole('waka_kesiswaan') && $user->hasAnyRole(['guru_bk', 'koordinator_bk']) === false,
        ]);
    }

    public function legacy(Request $request): RedirectResponse
    {
        $student = Student::query()->where('nisn', $request->string('nisn')->toString())->firstOrFail();
        abort_unless($request->user()?->can('view', $student), 403);

        return redirect()->route('students.show', ['student' => $student, 'tab' => $request->string('tab', 'ringkasan')->toString()]);
    }

    /** @param Collection<int, BkCase> $cases @param Collection<int, Consultation> $consultations @param Collection<int, ExternalTatibRecord> $etatibRecords @param Collection<int, Achievement> $achievements @return Collection<int, array{date: mixed, label: string}> */
    private function recentActivities(Collection $cases, Collection $consultations, Collection $etatibRecords, Collection $achievements): Collection
    {
        return $cases->map(fn (BkCase $case): array => [
            'date' => $case->created_at,
            'label' => 'Kasus dicatat',
        ])->concat($consultations->map(fn (Consultation $consultation): array => [
            'date' => $consultation->created_at,
            'label' => 'Konsultasi dicatat',
        ]))->concat($etatibRecords->map(fn (ExternalTatibRecord $record): array => [
            'date' => $record->occurred_at,
            'label' => 'Data e-Tatib diperbarui',
        ]))->concat($achievements->map(fn (Achievement $achievement): array => [
            'date' => $achievement->created_at,
            'label' => sprintf('Prestasi %s dicatat', $achievement->activity_name),
        ]))->sortByDesc('date')->take(8)->values();
    }
}
