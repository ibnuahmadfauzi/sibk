<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AssignCaseRequest;
use App\Http\Requests\StoreClassAssignmentRequest;
use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AcademicYearPreparationService;
use App\Services\AssignmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('viewAny', TeacherAssignment::class), 403);

        $query = TeacherAssignment::query()->with(['teacher', 'classroom', 'academicYear']);

        if (! $user->hasAnyRole(['koordinator_bk', 'waka_kesiswaan', 'admin_it'])) {
            $query->where('user_id', $user->getKey());
        }

        $query
            ->when($request->integer('academic_year_id'), fn ($builder, int $yearId) => $builder->where('academic_year_id', $yearId))
            ->when($request->string('search_kelas')->toString(), fn ($builder, string $search) => $builder->whereHas(
                'classroom',
                fn ($classrooms) => $classrooms->where('name', 'like', '%'.$search.'%'),
            ));

        if ($request->string('status')->toString() === 'aktif') {
            $query->activeOn(now());
        } elseif ($request->string('status')->toString() === 'terjadwal') {
            $query->scheduledOn(now());
        } elseif (in_array($request->string('status')->toString(), ['berakhir', 'nonaktif'], true)) {
            $query->endedOn(now());
        }

        return view('pages.assignments.classes.index', [
            'assignments' => $query->orderByDesc('effective_from')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('starts_on')->orderByDesc('name')->get(),
            'canManage' => $user->can('create', TeacherAssignment::class),
        ]);
    }

    public function manage(
        Request $request,
        AcademicYearPreparationService $preparationService,
    ): View {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('create', TeacherAssignment::class), 403);

        $academicYears = AcademicYear::query()->orderByDesc('starts_on')->orderByDesc('name')->get();
        $selectedYear = AcademicYear::query()->find($request->integer('academic_year_id'))
            ?? AcademicYear::query()->active()->orderByDesc('starts_on')->first()
            ?? $academicYears->first();
        $classes = Classroom::query()
            ->with('academicYear')
            ->active()
            ->when($selectedYear !== null, fn ($query) => $query->where('academic_year_id', $selectedYear->getKey()))
            ->orderBy('name')
            ->get();
        $selectedClass = $classes->firstWhere('id', $request->integer('classroom_id'))
            ?? $classes->firstWhere('academic_year_id', $selectedYear?->getKey())
            ?? $classes->first();
        $counselors = User::query()
            ->active()
            ->whereHas('roles', fn ($roles) => $roles->where('slug', 'guru_bk')->where('is_active', true))
            ->orderBy('name')
            ->get();
        $currentAssignment = null;
        if ($selectedClass !== null) {
            $assignmentQuery = TeacherAssignment::query()
                ->with(['teacher', 'academicYear', 'classroom'])
                ->where('classroom_id', $selectedClass->getKey());
            $currentAssignment = (clone $assignmentQuery)->activeOn(now())->latest('effective_from')->first()
                ?? (clone $assignmentQuery)->scheduledOn(now())->oldest('effective_from')->first();
        }
        $activationReadiness = $selectedYear !== null
            ? $preparationService->activationReadiness($selectedYear)
            : ['ready' => false, 'issues' => ['Tahun ajaran belum tersedia.'], 'classrooms' => collect()];

        return view('pages.assignments.classes.manage', compact(
            'academicYears',
            'selectedYear',
            'classes',
            'selectedClass',
            'counselors',
            'currentAssignment',
            'activationReadiness',
        ));
    }

    public function storeClassAssignment(
        StoreClassAssignmentRequest $request,
        AssignmentService $assignmentService,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $assignmentService->assignClass($request->validated(), $actor);

        return redirect()
            ->route('assignments.classes.index', ['academic_year_id' => $request->integer('academic_year_id')])
            ->with('success', 'Penugasan kelas berhasil disimpan.');
    }

    public function caseIndex(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        Gate::forUser($user)->authorize('manageCaseAssignments');

        $cases = BkCase::query()
            ->whereNull('closed_at')
            ->with(['student.classMemberships.classroom', 'temporaryStudent', 'status', 'assignments.teacher'])
            ->latest('service_date')
            ->get();
        $selectedCase = $cases->firstWhere('id', $request->integer('case_id'))
            ?? $cases->firstWhere('registration_number', $request->string('case_no')->toString())
            ?? $cases->first();
        $counselors = User::query()
            ->active()
            ->whereHas('roles', fn ($roles) => $roles->where('slug', 'guru_bk')->where('is_active', true))
            ->orderBy('name')
            ->get();

        return view('pages.assignments.cases.index', compact('cases', 'selectedCase', 'counselors'));
    }

    public function assignCase(
        AssignCaseRequest $request,
        BkCase $case,
        AssignmentService $assignmentService,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $assignmentService->assignCase($case, $request->validated(), $actor);

        return redirect()->route('cases.show', $case)->with('success', 'Penugasan kasus berhasil diperbarui.');
    }
}
