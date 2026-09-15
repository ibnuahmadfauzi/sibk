<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ArchiveCaseRequest;
use App\Http\Requests\ResolveCaseRequest;
use App\Http\Requests\StoreCaseRequest;
use App\Http\Requests\UpdateCaseRequest;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ExternalTatibRecord;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CaseService;
use App\Services\WakaMonitoringService;
use App\Support\ServiceRecordStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    private const ETATIB_RECORD_LIMIT = 200;

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $activeTab = $request->string('tab', 'kasus')->toString();
        if (! in_array($activeTab, ['kasus', 'konsultasi'], true)) {
            $activeTab = 'kasus';
        }

        if ($activeTab === 'konsultasi') {
            abort_unless($user->can('viewAny', Consultation::class), 403);

            return $this->consultationIndex($request, $user);
        }

        abort_unless($user->can('viewAny', BkCase::class), 403);

        $query = BkCase::query()
            ->accessibleTo($user)
            ->with([
                'student.classMemberships.classroom',
                'temporaryStudent',
                'source',
                'serviceField',
                'status',
                'followUps' => fn ($followUps) => $followUps->with('status')->orderByDesc('planned_date'),
            ]);

        $search = $request->string('search')->trim()->toString();
        $query->when($search, fn ($cases) => $cases->where(function ($filter) use ($search): void {
            $filter->whereHas('student', fn ($students) => $students->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('temporaryStudent', fn ($students) => $students->where('input_name', 'like', '%'.$search.'%'));
        }));
        $query->when($request->integer('classroom_id'), fn ($cases, int $classroomId) => $cases->whereHas(
            'student.classMemberships',
            fn ($memberships) => $memberships->where('classroom_id', $classroomId),
        ));
        $query->when($request->integer('case_source_id'), fn ($cases, int $id) => $cases->where('case_source_id', $id));
        $query->when($request->integer('status_id'), fn ($cases, int $id) => $cases->where('status_id', $id));
        $query->when($request->string('month')->toString(), function ($cases, string $month): void {
            if (preg_match('/^(\d{4})-(\d{2})$/', $month, $matches) === 1) {
                $cases->whereYear('service_date', (int) $matches[1])->whereMonth('service_date', (int) $matches[2]);
            }
        });

        return view('pages.cases.index', [
            'cases' => $query->latest('service_date')->paginate(20)->withQueryString(),
            'consultations' => null,
            'activeTab' => $activeTab,
            'classrooms' => Classroom::query()->active()->orderBy('name')->get(),
            'caseSources' => ReferenceValue::query()->active()->forCategory('case_source')->orderBy('sort_order')->get(),
            'caseStatuses' => ReferenceValue::query()->active()->forCategory('case_status')->orderBy('sort_order')->get(),
            'canCreateCase' => $user->can('create', BkCase::class),
            'canCreateConsultation' => false,
            'consultationStatuses' => collect(),
            'serviceFields' => collect(),
        ]);
    }

    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('create', BkCase::class), 403);

        $accessibleStudents = Student::query()
            ->active()
            ->forActiveTeacherAssignment($user, now());
        $temporaryNisnCandidate = $request->string('temporary_nisn')->trim()->toString();
        $temporaryNisnFilter = preg_match('/^[0-9]{1,20}$/D', $temporaryNisnCandidate) === 1
            ? $temporaryNisnCandidate
            : null;
        $students = (clone $accessibleStudents)
            ->with(['classMemberships' => fn ($memberships) => $memberships
                ->active()
                ->effectiveOn(now()->toDateString())
                ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                ->with('classroom')])
            ->orderBy('name')
            ->get();
        $etatibRecords = ExternalTatibRecord::query()
            ->active()
            ->where(function ($records) use ($accessibleStudents, $temporaryNisnFilter): void {
                $records->whereIn('student_id', (clone $accessibleStudents)->select('students.id'));

                if ($temporaryNisnFilter !== null) {
                    $records->orWhere(fn ($unmapped) => $unmapped
                        ->whereNull('student_id')
                        ->where('nisn', $temporaryNisnFilter));
                }
            })
            ->when($temporaryNisnFilter !== null, fn ($records) => $records->where('nisn', $temporaryNisnFilter))
            ->latest('occurred_at')
            ->latest('id')
            ->limit(self::ETATIB_RECORD_LIMIT + 1)
            ->get();
        $etatibRecordsCapped = $etatibRecords->count() > self::ETATIB_RECORD_LIMIT;
        $etatibRecords = $etatibRecords->take(self::ETATIB_RECORD_LIMIT);

        return view('pages.cases.create', [
            'students' => $students,
            'caseSources' => ReferenceValue::query()->active()->forCategory('case_source')->orderBy('sort_order')->get(),
            'serviceFields' => ReferenceValue::query()->active()->forCategory('service_field')->orderBy('sort_order')->get(),
            'etatibRecords' => $etatibRecords,
            'etatibRecordsCapped' => $etatibRecordsCapped,
            'temporaryNisnFilter' => $temporaryNisnFilter,
            'preselectedStudentId' => $request->integer('student_id') ?: null,
        ]);
    }

    public function store(StoreCaseRequest $request, CaseService $caseService): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $case = $caseService->createCase($request->validated(), $actor);

        return redirect()->route('cases.show', $case)->with('success', 'Kasus berhasil dibuat.');
    }

    public function show(
        Request $request,
        BkCase $case,
        AuditService $auditService,
        WakaMonitoringService $wakaMonitoring,
    ): View {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('view', $case), 403);
        $isWakaOnly = $user->hasRole('waka_kesiswaan')
            && ! $user->hasAnyRole(['guru_bk', 'koordinator_bk']);

        if ($isWakaOnly) {
            $detail = $wakaMonitoring->detailSafe($user, (int) $case->getKey());
            $auditService->record(
                action: 'case.viewed_by_waka',
                auditable: $case,
                summary: 'Detail kasus terkoordinasi dilihat oleh Waka Kesiswaan.',
                actor: $user,
            );

            return view('pages.waka.case-detail', ['detail' => $detail]);
        }

        $case->load([
            'student.classMemberships.classroom',
            'temporaryStudent',
            'source',
            'serviceField',
            'status',
            'assignments.teacher',
            'coordinations.waka',
            'coordinations.status',
            'followUps.type',
            'followUps.status',
            'followUps.recorder',
            'etatibRecords',
        ]);

        return view('pages.cases.show', [
            'case' => $case,
            'canViewInternal' => $user->can('viewInternal', $case),
            'canUpdateCase' => $user->can('update', $case),
            'canArchiveCase' => $user->can('archive', $case),
            'canManageCase' => $user->can('resolve', $case),
            'canAssignCase' => $user->can('assign', $case),
            'canCoordinateCase' => $user->can('coordinate', $case),
            'wakaUsers' => User::query()->active()->whereHas(
                'roles',
                fn ($roles) => $roles->where('slug', 'waka_kesiswaan')->where('is_active', true),
            )->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, BkCase $case): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('update', $case), 403);
        $case->load(['student', 'temporaryStudent', 'source', 'serviceField', 'status']);

        return view('pages.cases.edit', [
            'case' => $case,
            'isCompleted' => $case->status?->code === ServiceRecordStatus::COMPLETED,
            'terminalConfirmed' => $request->boolean('confirm_terminal'),
            'caseSources' => ReferenceValue::query()->active()->forCategory('case_source')->orderBy('sort_order')->get(),
            'serviceFields' => ReferenceValue::query()->active()->forCategory('service_field')->orderBy('sort_order')->get(),
            'caseStatuses' => ReferenceValue::query()
                ->active()
                ->forCategory('case_status')
                ->where('code', '!=', ServiceRecordStatus::COMPLETED)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function update(UpdateCaseRequest $request, BkCase $case, CaseService $caseService): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $caseService->update($case, $request->validated(), $actor);

        return redirect()->route('cases.show', $case)->with('success', 'Kasus berhasil diperbarui.');
    }

    public function resolveForm(Request $request, BkCase $case): View
    {
        abort_unless($request->user()?->can('resolve', $case), 403);
        $case->load(['student', 'temporaryStudent', 'status', 'followUps']);

        return view('pages.cases.resolve', compact('case'));
    }

    public function resolve(
        ResolveCaseRequest $request,
        BkCase $case,
        CaseService $caseService,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $caseService->resolve($case, $request->validated(), $actor);

        return redirect()->route('cases.show', $case)->with('success', 'Kasus berhasil diselesaikan.');
    }

    public function destroy(ArchiveCaseRequest $request, BkCase $case, CaseService $caseService): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $caseService->archive($case, $actor);

        return redirect()->route('cases.index')->with('success', 'Kasus berhasil diarsipkan.');
    }

    private function consultationIndex(Request $request, User $user): View
    {
        $query = Consultation::query()
            ->accessibleTo($user)
            ->with([
                'student.classMemberships.classroom',
                'temporaryStudent.reconciledStudent',
                'case',
                'serviceField',
                'status',
                'counselor',
            ]);
        $search = $request->string('search')->trim()->toString();
        $query->when($search, fn ($consultations) => $consultations->where(function ($filter) use ($search): void {
            $filter->where('registration_number', 'like', '%'.$search.'%')
                ->orWhere('topic', 'like', '%'.$search.'%')
                ->orWhereHas('student', fn ($students) => $students
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('nisn', 'like', '%'.$search.'%'))
                ->orWhereHas('temporaryStudent', fn ($students) => $students
                    ->where('input_name', 'like', '%'.$search.'%')
                    ->orWhere('nisn', 'like', '%'.$search.'%'));
        }));
        $query->when($request->integer('classroom_id'), fn ($consultations, int $classroomId) => $consultations
            ->whereHas('student.classMemberships', fn ($memberships) => $memberships->where('classroom_id', $classroomId)));
        $query->when($request->integer('service_field_id'), fn ($consultations, int $id) => $consultations->where('service_field_id', $id));
        $query->when($request->integer('consultation_status_id'), fn ($consultations, int $id) => $consultations->where('status_id', $id));
        $query->when($request->string('month')->toString(), function ($consultations, string $month): void {
            if (preg_match('/^(\d{4})-(\d{2})$/', $month, $matches) === 1) {
                $consultations->whereYear('session_date', (int) $matches[1])->whereMonth('session_date', (int) $matches[2]);
            }
        });

        return view('pages.cases.index', [
            'cases' => null,
            'consultations' => $query->latest('session_date')->paginate(20)->withQueryString(),
            'activeTab' => 'konsultasi',
            'classrooms' => Classroom::query()->active()->orderBy('name')->get(),
            'caseSources' => collect(),
            'caseStatuses' => collect(),
            'canCreateCase' => false,
            'canCreateConsultation' => $user->can('create', Consultation::class),
            'consultationStatuses' => ReferenceValue::query()->active()->forCategory('consultation_status')->orderBy('sort_order')->get(),
            'serviceFields' => ReferenceValue::query()->active()->forCategory('service_field')->orderBy('sort_order')->get(),
        ]);
    }
}
