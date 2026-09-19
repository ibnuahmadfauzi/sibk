<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ArchiveConsultationRequest;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ConsultationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('cases.index', ['tab' => 'konsultasi']);
    }

    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('create', Consultation::class), 403);

        return $this->formData($user, null, $request);
    }

    public function store(StoreConsultationRequest $request, ConsultationService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $consultation = $service->create($request->validated(), $actor);

        return redirect()->route('consultations.show', $consultation)->with('success', 'Konsultasi berhasil dicatat.');
    }

    public function show(Request $request, Consultation $consultation, AuditService $auditService): View
    {
        /** @var User $user */
        $user = $request->user();
        $isWakaOnly = $user->hasRole('waka_kesiswaan')
            && ! $user->hasAnyRole(['guru_bk', 'koordinator_bk']);

        if ($isWakaOnly) {
            $consultation = Consultation::query()
                ->accessibleTo($user)
                ->whereKey($consultation->getKey())
                ->select(['id', 'student_id', 'temporary_student_id', 'service_field_id', 'session_date', 'problem', 'handling', 'result', 'counselor_id'])
                ->with([
                    'student:id,name',
                    'student.classMemberships' => fn ($memberships) => $memberships
                        ->select(['id', 'student_id', 'classroom_id', 'effective_from', 'effective_until'])
                        ->with('classroom:id,name')
                        ->orderByDesc('effective_from')
                        ->orderByDesc('id'),
                    'temporaryStudent:id,input_name,reconciled_student_id',
                    'temporaryStudent.reconciledStudent:id,name',
                    'temporaryStudent.reconciledStudent.classMemberships' => fn ($memberships) => $memberships
                        ->select(['id', 'student_id', 'classroom_id', 'effective_from', 'effective_until'])
                        ->with('classroom:id,name')
                        ->orderByDesc('effective_from')
                        ->orderByDesc('id'),
                    'serviceField:id,label',
                    'counselor:id,name',
                ])
                ->firstOrFail();
            abort_unless($user->can('view', $consultation), 403);
            $auditService->record('consultation.viewed_by_waka', $consultation, 'Detail konsultasi dilihat oleh Waka Kesiswaan.', $user);
        } else {
            abort_unless($user->can('view', $consultation), 403);
            $consultation->load([
                'student.classMemberships.classroom.academicYear',
                'temporaryStudent.reconciledStudent.classMemberships.classroom.academicYear',
                'serviceField',
                'counselor',
            ]);
        }
        $data = [
            'consultation' => $consultation,
            'modal' => $request->boolean('modal'),
            'canUpdateConsultation' => $user->can('update', $consultation),
            'canArchiveConsultation' => $user->can('archive', $consultation),
        ];

        return view(
            $request->boolean('modal') ? 'pages.consultations._detail-modal' : 'pages.consultations.show',
            $data,
        );
    }

    public function edit(Request $request, Consultation $consultation): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('update', $consultation), 403);
        $consultation->load([
            'student.classMemberships' => fn ($memberships) => $memberships
                ->active()
                ->effectiveOn(now()->toDateString())
                ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                ->with('classroom'),
            'temporaryStudent',
        ]);

        $data = $this->formValues($user, $consultation, $request);

        return $request->boolean('modal')
            ? view('pages.consultations._edit-modal', [...$data, 'modal' => true])
            : view('pages.consultations.create', $data);
    }

    public function update(
        UpdateConsultationRequest $request,
        Consultation $consultation,
        ConsultationService $service,
    ): RedirectResponse|JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $consultation = $service->update($consultation, $request->validated(), $actor);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Konsultasi berhasil diperbarui.',
                'redirect' => route('cases.index', ['tab' => 'konsultasi']),
                'data' => [
                    'service_field_id' => $consultation->service_field_id,
                    'session_date' => $consultation->session_date?->toDateString(),
                    'problem' => $consultation->problem,
                    'handling' => $consultation->handling,
                    'result' => $consultation->result,
                    'updated_at' => $consultation->updated_at?->toJSON(),
                ],
            ]);
        }

        return redirect()->route('consultations.show', $consultation)->with('success', 'Konsultasi berhasil diperbarui.');
    }

    public function destroy(
        ArchiveConsultationRequest $request,
        Consultation $consultation,
        ConsultationService $service,
    ): RedirectResponse|JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $service->archive($consultation, $actor);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Konsultasi berhasil diarsipkan.',
                'redirect' => route('cases.index', ['tab' => 'konsultasi']),
            ]);
        }

        return redirect()->route('cases.index', ['tab' => 'konsultasi'])
            ->with('success', 'Konsultasi berhasil diarsipkan.');
    }

    private function formData(User $user, ?Consultation $consultation, Request $request): View
    {
        return view('pages.consultations.create', $this->formValues($user, $consultation, $request));
    }

    /** @return array<string, mixed> */
    private function formValues(User $user, ?Consultation $consultation, Request $request): array
    {
        $students = Student::query()
            ->availableForService()
            ->professionallyAccessibleTo($user)
            ->with(['classMemberships' => fn ($memberships) => $memberships
                ->active()
                ->effectiveOn(now()->toDateString())
                ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                ->with('classroom')])
            ->orderBy('name')
            ->get();

        return [
            'consultation' => $consultation,
            'isEdit' => $consultation !== null,
            'modal' => false,
            'students' => $students,
            'serviceFields' => ReferenceValue::query()->active()->forCategory('service_field')->orderBy('sort_order')->get(),
            'preselectedStudentId' => $request->integer('student_id') ?: null,
        ];
    }
}
