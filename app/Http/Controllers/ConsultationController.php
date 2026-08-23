<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Models\BkCase;
use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use App\Services\ConsultationService;
use Illuminate\Contracts\View\View;
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

    public function show(Request $request, Consultation $consultation): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('view', $consultation), 403);
        $consultation->load([
            'student.classMemberships.classroom.academicYear',
            'temporaryStudent.reconciledStudent',
            'case',
            'serviceField',
            'status',
            'counselor',
        ]);
        $canViewSensitive = $user->can('viewSensitive', $consultation);
        if ($canViewSensitive) {
            $consultation->load('privateNote.updater');
        }

        return view('pages.consultations.show', [
            'consultation' => $consultation,
            'canViewSensitive' => $canViewSensitive,
            'canUpdateConsultation' => $user->can('update', $consultation),
        ]);
    }

    public function edit(Request $request, Consultation $consultation): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('update', $consultation), 403);
        $consultation->load(['student', 'temporaryStudent', 'privateNote']);

        return $this->formData($user, $consultation, $request);
    }

    public function update(
        UpdateConsultationRequest $request,
        Consultation $consultation,
        ConsultationService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $service->update($consultation, $request->validated(), $actor);

        return redirect()->route('consultations.show', $consultation)->with('success', 'Konsultasi berhasil diperbarui.');
    }

    private function formData(User $user, ?Consultation $consultation, Request $request): View
    {
        $students = Student::query()
            ->active()
            ->professionallyAccessibleTo($user)
            ->with(['classMemberships' => fn ($memberships) => $memberships
                ->active()
                ->effectiveOn(now()->toDateString())
                ->with('classroom')])
            ->orderBy('name')
            ->get();
        $cases = BkCase::query()
            ->whereHas('assignments', fn ($assignments) => $assignments
                ->where('user_id', $user->getKey())
                ->effectiveOn(now()))
            ->with(['student', 'temporaryStudent.reconciledStudent', 'serviceField', 'status'])
            ->latest('service_date')
            ->get();

        return view('pages.consultations.create', [
            'consultation' => $consultation,
            'isEdit' => $consultation !== null,
            'students' => $students,
            'cases' => $cases,
            'serviceFields' => ReferenceValue::query()->active()->forCategory('service_field')->orderBy('sort_order')->get(),
            'consultationStatuses' => ReferenceValue::query()->active()->forCategory('consultation_status')->orderBy('sort_order')->get(),
            'preselectedStudentId' => $request->integer('student_id') ?: null,
        ]);
    }
}
