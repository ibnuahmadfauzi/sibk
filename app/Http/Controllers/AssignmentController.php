<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassAssignmentRequest;
use App\Http\Requests\StoreClassAssignmentsRequest;
use App\Http\Requests\UnassignClassRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AcademicYearPreparationService;
use App\Services\AssignmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request, AcademicYearPreparationService $preparationService): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('viewAny', TeacherAssignment::class), 403);

        $academicYears = AcademicYear::query()->orderByDesc('id')->get();
        $activeYear = $academicYears->firstWhere('is_active', true);
        $preparationYears = $academicYears->filter(
            fn (AcademicYear $year) => ! $year->is_active && $year->activated_at === null
        );
        $canChooseYear = $user->can('create', TeacherAssignment::class);
        $requestedYear = $academicYears->firstWhere('id', $request->integer('academic_year_id'));
        $selectedYear = $activeYear;
        if ($canChooseYear && $requestedYear !== null
            && ($requestedYear->is_active || $requestedYear->activated_at === null)) {
            $selectedYear = $requestedYear;
        }
        $canManage = $canChooseYear && $selectedYear !== null;
        $classes = Classroom::query()
            ->active()
            ->with('teacherAssignments')
            ->withCount(['studentClassMemberships as student_count' => fn ($memberships) => $memberships
                ->active()
                ->where('academic_year_id', $selectedYear?->getKey())
                ->whereHas('student', fn ($students) => $students->active())])
            ->when($selectedYear !== null,
                fn ($query) => $query->where('academic_year_id', $selectedYear->getKey()),
                fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('name')
            ->get();

        $counselors = User::query()->active()
            ->whereHas('roles', fn ($roles) => $roles->where('slug', 'guru_bk')->where('is_active', true))
            ->when(! $user->hasAnyRole(['koordinator_bk', 'waka_kesiswaan', 'admin_it']),
                fn ($query) => $query->whereKey($user->getKey()))
            ->orderBy('name')->get();
        $classesByTeacher = $classes->filter(fn (Classroom $classroom) => $classroom->teacherAssignments->isNotEmpty())
            ->groupBy(fn (Classroom $classroom) => $classroom->teacherAssignments->first()->user_id);
        $classSuggestions = $counselors->flatMap(
            fn (User $counselor) => $classesByTeacher->get($counselor->getKey(), collect())->pluck('name')
        )->unique()->sort(SORT_NATURAL)->values();
        $unassignedClasses = $classes->filter(fn (Classroom $classroom) => $classroom->teacherAssignments->isEmpty());
        $status = $request->string('status')->toString();
        $search = $request->string('search_kelas')->toString();
        $rows = $counselors->map(function (User $counselor) use ($classesByTeacher): array {
            $assignedClasses = $classesByTeacher->get($counselor->getKey(), collect());

            return [
                'teacher' => $counselor,
                'classes' => $assignedClasses,
                'student_count' => $assignedClasses->sum('student_count'),
            ];
        })->filter(fn (array $row) => match ($status) {
            'assigned' => $row['classes']->isNotEmpty(),
            'unassigned' => $row['classes']->isEmpty(),
            default => true,
        })->filter(fn (array $row) => $search === '' || $row['classes']->contains(
            fn (Classroom $classroom) => str_contains(mb_strtolower($classroom->name), mb_strtolower($search))
        ));

        return view('pages.assignments.classes.index', [
            'rows' => $rows,
            'classSuggestions' => $classSuggestions,
            'unassignedClasses' => $unassignedClasses,
            'activeYear' => $activeYear,
            'preparationYears' => $preparationYears,
            'canChooseYear' => $canChooseYear,
            'selectedYear' => $selectedYear,
            'canManage' => $canManage,
            'activationReadiness' => $canManage ? $preparationService->activationReadiness($selectedYear) : null,
            'previousYear' => $canManage && $selectedYear->is_active
                ? $preparationService->previousYearCandidate($selectedYear)
                : null,
        ]);
    }

    public function manage(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('create', TeacherAssignment::class), 403);

        return redirect()->route('assignments.classes.index', $request->only('academic_year_id', 'classroom_id'));
    }

    public function storeClassAssignment(
        StoreClassAssignmentRequest $request,
        AssignmentService $assignmentService,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $assignment = $assignmentService->assignClass($request->validated(), $actor);

        return redirect()
            ->route('assignments.classes.index', ['academic_year_id' => $assignment->academic_year_id])
            ->with('success_title', 'Kelas ditugaskan')
            ->with('success', sprintf(
                '%s kini diampu %s.',
                $assignment->classroom->name,
                $assignment->teacher->name,
            ));
    }

    public function storeClassAssignments(
        StoreClassAssignmentsRequest $request,
        AssignmentService $assignmentService,
    ): RedirectResponse {
        $assignments = $assignmentService->assignClasses($request->validated(), $request->user());
        $first = $assignments->first();

        return redirect()
            ->route('assignments.classes.index', ['academic_year_id' => $first->academic_year_id])
            ->with('success_title', 'Kelas ditugaskan')
            ->with('success', sprintf(
                '%d kelas ditambahkan untuk %s.',
                $assignments->count(),
                $first->teacher->name,
            ));
    }

    public function destroyClassAssignment(
        UnassignClassRequest $request,
        Classroom $classroom,
        AssignmentService $assignmentService,
    ): RedirectResponse {
        $assignmentService->unassignClass($classroom, $request->integer('user_id'), $request->user());

        return redirect()
            ->route('assignments.classes.index', ['academic_year_id' => $classroom->academic_year_id])
            ->with('success_title', 'Penugasan dibatalkan')
            ->with('success', sprintf('%s kembali tersedia untuk ditugaskan.', $classroom->name));
    }
}
