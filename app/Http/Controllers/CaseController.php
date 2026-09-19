<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ArchiveCaseRequest;
use App\Http\Requests\ResolveCaseRequest;
use App\Http\Requests\StoreCaseRequest;
use App\Http\Requests\UpdateCaseFollowUpRequest;
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
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaseController extends Controller
{
    private const ETATIB_RECORD_LIMIT = 200;

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $isWakaOnly = $user->hasRole('waka_kesiswaan')
            && ! $user->hasAnyRole(['guru_bk', 'koordinator_bk']);
        $activeTab = $request->string('tab', 'kasus')->toString();
        if (! in_array($activeTab, ['kasus', 'konsultasi'], true)) {
            $activeTab = 'kasus';
        }

        if ($activeTab === 'konsultasi') {
            abort_unless($user->can('viewAny', Consultation::class), 403);

            return $this->consultationIndex($request, $user, $isWakaOnly);
        }

        abort_unless($user->can('viewAny', BkCase::class), 403);

        $query = BkCase::query()->accessibleTo($user);
        $query->when($isWakaOnly, fn ($cases) => $cases
            ->select([
                'cases.id', 'cases.student_id', 'cases.temporary_student_id',
                'cases.service_date', 'cases.status_id', 'cases.service_field_id',
                'cases.follow_up_type_id',
            ])
            ->with([
                'student:id,name',
                'student.classMemberships' => fn ($memberships) => $memberships
                    ->select(['id', 'student_id', 'classroom_id', 'academic_year_id', 'effective_from', 'effective_until'])
                    ->with('classroom:id,name'),
                'temporaryStudent:id,input_name',
                'serviceField:id,label',
                'status:id,label,code',
                'followUpType:id,label',
            ]), fn ($cases) => $cases->with([
                'student.classMemberships.classroom',
                'temporaryStudent',
                'source',
                'serviceField',
                'status',
                'followUpType',
            ]));

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

        $sort = $request->string('sort', 'tanggal')->toString();
        $direction = $request->string('direction', 'desc')->toString();
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }
        $sortExpression = match ($sort) {
            'nama' => DB::raw('LOWER(COALESCE((SELECT name FROM students WHERE students.id = cases.student_id), (SELECT input_name FROM temporary_students WHERE temporary_students.id = cases.temporary_student_id)))'),
            'kelas' => Classroom::query()
                ->selectRaw('LOWER(classrooms.name)')
                ->join('student_class_memberships', 'student_class_memberships.classroom_id', '=', 'classrooms.id')
                ->whereColumn('student_class_memberships.student_id', 'cases.student_id')
                ->whereColumn('student_class_memberships.effective_from', '<=', 'cases.service_date')
                ->where(function ($memberships): void {
                    $memberships->whereNull('student_class_memberships.effective_until')
                        ->orWhereColumn('student_class_memberships.effective_until', '>=', 'cases.service_date');
                })
                ->orderByDesc('student_class_memberships.effective_from')
                ->orderByDesc('student_class_memberships.id')
                ->limit(1),
            'sumber' => ReferenceValue::query()->selectRaw('LOWER(label)')->whereColumn('references.id', 'cases.case_source_id'),
            'bidang' => ReferenceValue::query()->selectRaw('LOWER(label)')->whereColumn('references.id', 'cases.service_field_id'),
            'status' => ReferenceValue::query()->selectRaw('LOWER(label)')->whereColumn('references.id', 'cases.status_id'),
            default => 'cases.service_date',
        };
        $query->orderBy($sortExpression, $direction)->orderBy('cases.id', $direction);

        return view('pages.cases.index', [
            'cases' => $query->paginate(20)->withQueryString(),
            'consultations' => null,
            'activeTab' => $activeTab,
            'classrooms' => Classroom::query()->active()->orderBy('name')->get(),
            'caseSources' => ReferenceValue::query()->active()->forCategory('case_source')->orderBy('sort_order')->get(),
            'caseStatuses' => ReferenceValue::query()->active()->forCategory('case_status')->orderBy('sort_order')->get(),
            'followUpTypes' => ReferenceValue::query()->active()->forCategory('follow_up_type')->orderBy('sort_order')->get(),
            'canCreateCase' => $user->can('create', BkCase::class),
            'canCreateConsultation' => false,
            'consultationStatuses' => collect(),
            'serviceFields' => collect(),
            'isWakaOnly' => $isWakaOnly,
        ]);
    }

    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('create', BkCase::class), 403);

        $accessibleStudents = Student::query()
            ->availableForService()
            ->forActiveTeacherAssignment($user, now());
        $preselectedStudentId = $request->integer('student_id') ?: null;
        abort_if(
            $preselectedStudentId !== null
                && ! (clone $accessibleStudents)->whereKey($preselectedStudentId)->exists(),
            403,
        );
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
            'preselectedStudentId' => $preselectedStudentId,
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
                summary: 'Detail kasus dilihat oleh Waka Kesiswaan.',
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
            'etatibRecords',
        ]);

        if ($request->boolean('modal')) {
            return view('pages.cases._detail-modal', ['case' => $case]);
        }

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

        if ($request->boolean('modal')) {
            return view('pages.cases._edit-modal', ['case' => $case]);
        }

        return view('pages.cases.edit', [
            'case' => $case,
        ]);
    }

    public function update(UpdateCaseRequest $request, BkCase $case, CaseService $caseService): RedirectResponse|JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $case = $caseService->update($case, $request->validated(), $actor);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Kasus berhasil diperbarui.',
                'redirect' => route('cases.show', $case),
            ]);
        }

        return redirect()->route('cases.show', $case)->with('success', 'Kasus berhasil diperbarui.');
    }

    public function updateFollowUp(
        UpdateCaseFollowUpRequest $request,
        BkCase $case,
        CaseService $caseService,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();
        $case = $caseService->updateFollowUp(
            $case,
            isset($validated['follow_up_type_id']) ? (int) $validated['follow_up_type_id'] : null,
            $validated['expected_updated_at'],
            $actor,
        );

        return response()->json([
            'message' => 'Tindak lanjut berhasil diperbarui.',
            'data' => [
                'status_code' => $case->status?->code,
                'status_label' => $case->status?->label,
                'follow_up_type_id' => $case->follow_up_type_id,
                'follow_up_type_label' => $case->followUpType?->label,
                'updated_at' => $case->updated_at?->toJSON(),
            ],
        ]);
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

    private function consultationIndex(Request $request, User $user, bool $isWakaOnly): View
    {
        $query = Consultation::query()->accessibleTo($user);
        $query->when($isWakaOnly, fn ($consultations) => $consultations
            ->select([
                'consultations.id', 'consultations.student_id', 'consultations.temporary_student_id',
                'consultations.service_field_id', 'consultations.session_date', 'consultations.counselor_id',
            ])
            ->with([
                'student:id,name',
                'student.classMemberships' => fn ($memberships) => $memberships
                    ->select(['id', 'student_id', 'classroom_id', 'academic_year_id', 'effective_from', 'effective_until'])
                    ->with('classroom:id,name'),
                'temporaryStudent:id,input_name,reconciled_student_id',
                'temporaryStudent.reconciledStudent:id,name',
                'temporaryStudent.reconciledStudent.classMemberships' => fn ($memberships) => $memberships
                    ->select(['id', 'student_id', 'classroom_id', 'academic_year_id', 'effective_from', 'effective_until'])
                    ->with('classroom:id,name'),
                'serviceField:id,label',
            ]), fn ($consultations) => $consultations->with([
                'student.classMemberships.classroom',
                'temporaryStudent.reconciledStudent.classMemberships.classroom',
                'serviceField',
            ]));
        $search = $request->string('search')->trim()->toString();
        $query->when($search, fn ($consultations) => $consultations->where(function ($filter) use ($search): void {
            $filter->whereHas('student', fn ($students) => $students->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('temporaryStudent', fn ($students) => $students
                    ->where('input_name', 'like', '%'.$search.'%')
                    ->orWhereHas('reconciledStudent', fn ($reconciled) => $reconciled->where('name', 'like', '%'.$search.'%')));
        }));
        $query->when($request->integer('service_field_id'), fn ($consultations, int $id) => $consultations->where('service_field_id', $id));
        $sort = $request->string('sort', 'tanggal')->toString();
        $direction = $request->string('direction', 'desc')->toString();
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }
        $sortExpression = match ($sort) {
            'nama' => DB::raw('LOWER(COALESCE((SELECT name FROM students WHERE students.id = consultations.student_id), (SELECT COALESCE((SELECT name FROM students WHERE students.id = temporary_students.reconciled_student_id), input_name) FROM temporary_students WHERE temporary_students.id = consultations.temporary_student_id)))'),
            'kelas' => Classroom::query()
                ->selectRaw('LOWER(classrooms.name)')
                ->join('student_class_memberships', 'student_class_memberships.classroom_id', '=', 'classrooms.id')
                ->whereRaw('student_class_memberships.student_id = COALESCE(consultations.student_id, (SELECT reconciled_student_id FROM temporary_students WHERE temporary_students.id = consultations.temporary_student_id))')
                ->whereColumn('student_class_memberships.effective_from', '<=', 'consultations.session_date')
                ->where(function ($memberships): void {
                    $memberships->whereNull('student_class_memberships.effective_until')
                        ->orWhereColumn('student_class_memberships.effective_until', '>=', 'consultations.session_date');
                })
                ->orderByDesc('student_class_memberships.effective_from')
                ->orderByDesc('student_class_memberships.id')
                ->limit(1),
            'jenis_layanan' => ReferenceValue::query()->selectRaw('LOWER(label)')->whereColumn('references.id', 'consultations.service_field_id'),
            default => 'consultations.session_date',
        };
        $query->orderBy($sortExpression, $direction)->orderBy('consultations.id', $direction);

        return view('pages.cases.index', [
            'cases' => null,
            'consultations' => $query->paginate(20)->withQueryString(),
            'activeTab' => 'konsultasi',
            'classrooms' => collect(),
            'caseSources' => collect(),
            'caseStatuses' => collect(),
            'followUpTypes' => collect(),
            'canCreateCase' => false,
            'canCreateConsultation' => $user->can('create', Consultation::class),
            'consultationStatuses' => collect(),
            'serviceFields' => ReferenceValue::query()->active()->forCategory('service_field')->orderBy('sort_order')->get(),
            'isWakaOnly' => $isWakaOnly,
        ]);
    }
}
