<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassAssignmentRequest;
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
        $selectedYear = $academicYears->firstWhere('id', $request->integer('academic_year_id'))
            ?? $academicYears->firstWhere('is_active', true)
            ?? $academicYears->first();
        $canManage = $user->can('create', TeacherAssignment::class)
            && $selectedYear !== null
            && ($selectedYear->is_active || $selectedYear->activated_at === null);
        $isSummaryRole = $user->hasAnyRole(['koordinator_bk', 'waka_kesiswaan', 'admin_it']);

        $classes = Classroom::query()
            ->active()
            ->with(['academicYear', 'teacherAssignments.teacher'])
            ->withCount(['studentClassMemberships as student_count' => fn ($memberships) => $memberships
                ->active()->whereHas('student', fn ($students) => $students->active())])
            ->when($selectedYear !== null, fn ($query) => $query->where('academic_year_id', $selectedYear->getKey()))
            ->when($request->string('search_kelas')->toString(), fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($isSummaryRole === false,
                fn ($query) => $query->whereHas('teacherAssignments', fn ($assignments) => $assignments->where('user_id', $user->getKey())))
            ->orderBy('name')
            ->get();

        $status = $request->string('status')->toString();
        if ($status === 'assigned') {
            $classes = $classes->filter(fn (Classroom $classroom) => $classroom->teacherAssignments->isNotEmpty());
        } elseif ($status === 'unassigned') {
            $classes = $classes->filter(fn (Classroom $classroom) => $classroom->teacherAssignments->isEmpty());
        }

        return view('pages.assignments.classes.index', [
            'classes' => $classes,
            'academicYears' => $academicYears,
            'selectedYear' => $selectedYear,
            'canManage' => $canManage,
            'activationReadiness' => $canManage ? $preparationService->activationReadiness($selectedYear) : null,
            'counselors' => $canManage ? User::query()->active()
                ->whereHas('roles', fn ($roles) => $roles->where('slug', 'guru_bk')->where('is_active', true))
                ->orderBy('name')->get() : collect(),
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
            ->with('success', 'Penugasan kelas berhasil disimpan.');
    }
}
