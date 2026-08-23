<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ExternalTatibRecord;
use App\Models\Student;
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
            ->active()
            ->accessibleTo($user)
            ->with([
                'classMemberships' => fn ($memberships) => $memberships
                    ->active()
                    ->effectiveOn(now()->toDateString())
                    ->with('classroom.academicYear'),
                'cases' => fn ($cases) => $cases
                    ->accessibleTo($user)
                    ->with(['followUps.status']),
            ]);
        $search = $request->string('search')->trim()->toString();
        $query->when($search, fn ($students) => $students->where(function ($filter) use ($search): void {
            $filter->where('name', 'like', '%'.$search.'%')->orWhere('nisn', 'like', '%'.$search.'%');
        }));
        $query->when($request->integer('classroom_id'), fn ($students, int $classroomId) => $students
            ->whereHas('classMemberships', fn ($memberships) => $memberships
                ->where('classroom_id', $classroomId)
                ->active()
                ->effectiveOn(now()->toDateString())));

        return view('pages.students.index', [
            'students' => $query->orderBy('name')->paginate(20)->withQueryString(),
            'classrooms' => Classroom::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Student $student): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('view', $student), 403);
        $student->load(['classMemberships.classroom.academicYear']);

        $activeTab = $request->string('tab', 'ringkasan')->toString();
        $allowedTabs = ['ringkasan', 'kasus', 'etatib', 'konsultasi', 'prestasi'];
        if (! in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'ringkasan';
        }
        if ($user->hasRole('waka_kesiswaan') && ! $user->hasAnyRole(['guru_bk', 'koordinator_bk']) && $activeTab === 'konsultasi') {
            $activeTab = 'ringkasan';
        }

        $cases = BkCase::query()
            ->accessibleTo($user)
            ->where('student_id', $student->getKey())
            ->with(['source', 'serviceField', 'status', 'assignments.teacher', 'followUps.status'])
            ->latest('service_date')
            ->get();
        $etatibQuery = ExternalTatibRecord::query()->active()->where('student_id', $student->getKey());
        if ($user->hasRole('waka_kesiswaan') && ! $user->hasAnyRole(['guru_bk', 'koordinator_bk'])) {
            $etatibQuery->whereHas('cases.coordinations', fn ($coordinations) => $coordinations
                ->where('waka_user_id', $user->getKey()));
        }
        $etatibRecords = $etatibQuery->latest('occurred_at')->get();

        $consultations = collect();
        if ($user->can('viewAny', Consultation::class)) {
            $consultations = Consultation::query()
                ->accessibleTo($user)
                ->where(function ($identity) use ($student): void {
                    $identity->where('student_id', $student->getKey())
                        ->orWhereHas('temporaryStudent', fn ($temporary) => $temporary
                            ->where('reconciled_student_id', $student->getKey()));
                })
                ->with(['case', 'serviceField', 'status', 'counselor'])
                ->latest('session_date')
                ->get();
        }

        $achievements = Achievement::query()
            ->accessibleTo($user)
            ->where('student_id', $student->getKey())
            ->with(['type', 'level', 'verificationStatus', 'recorder', 'reviewer'])
            ->latest('achievement_date')
            ->get();

        $canUseProfessionalActions = $user->can('viewSensitive', $student);

        return view('pages.students.show', [
            'student' => $student,
            'activeTab' => $activeTab,
            'cases' => $cases,
            'etatibRecords' => $etatibRecords,
            'consultations' => $consultations,
            'memberships' => $student->classMemberships->sortByDesc('effective_from'),
            'stats' => [
                'active_cases' => $cases->whereNull('closed_at')->count(),
                'points' => $etatibRecords->sum('points'),
                'follow_ups' => $cases->flatMap->followUps
                    ->filter(fn ($followUp): bool => $followUp->planned_date->gte(today()) && $followUp->status?->code !== 'dibatalkan')
                    ->count(),
                'achievements' => $achievements->count(),
            ],
            'recentActivities' => $this->recentActivities($cases, $consultations, $etatibRecords, $achievements),
            'achievements' => $achievements,
            'canViewConsultations' => $user->can('viewAny', Consultation::class),
            'canCreateConsultation' => $canUseProfessionalActions && $user->can('create', Consultation::class),
            'canCreateCase' => $canUseProfessionalActions && $user->can('create', BkCase::class),
            'canCreateAchievement' => $canUseProfessionalActions && $user->can('create', Achievement::class),
            'isWakaSummary' => $user->hasRole('waka_kesiswaan') && ! $user->hasAnyRole(['guru_bk', 'koordinator_bk']),
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
            'label' => sprintf('Kasus %s dicatat', $case->registration_number),
        ])->concat($consultations->map(fn (Consultation $consultation): array => [
            'date' => $consultation->created_at,
            'label' => sprintf('Konsultasi %s dicatat', $consultation->registration_number),
        ]))->concat($etatibRecords->map(fn (ExternalTatibRecord $record): array => [
            'date' => $record->occurred_at,
            'label' => 'Data e-Tatib diperbarui',
        ]))->concat($achievements->map(fn (Achievement $achievement): array => [
            'date' => $achievement->created_at,
            'label' => sprintf('Prestasi %s dicatat', $achievement->activity_name),
        ]))->sortByDesc('date')->take(8)->values();
    }
}
