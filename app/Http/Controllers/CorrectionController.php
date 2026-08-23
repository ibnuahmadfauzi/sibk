<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProcessMasterCorrectionRequest;
use App\Http\Requests\StoreCorrectionRequest;
use App\Http\Requests\VerifyCorrectionRequest;
use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\Consultation;
use App\Models\Correction;
use App\Models\ExternalSyncRun;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use App\Services\CorrectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CorrectionController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('viewAny', Correction::class), 403);
        $query = Correction::query()->accessibleTo($user)->with(['status', 'requester']);
        $search = $request->string('search')->trim()->toString();
        $query->when($search, fn ($corrections) => $corrections->where(function ($filter) use ($search): void {
            $filter->where('registration_number', 'like', '%'.$search.'%')
                ->orWhere('target_label', 'like', '%'.$search.'%')
                ->orWhere('field_label', 'like', '%'.$search.'%')
                ->orWhereHas('requester', fn ($users) => $users->where('name', 'like', '%'.$search.'%'));
        }));
        $query->when($request->string('correction_type')->toString(), fn ($corrections, string $type) => $corrections->where('correction_type', $type));
        $query->when($request->integer('status_id'), fn ($corrections, int $statusId) => $corrections->where('status_id', $statusId));

        return view('pages.corrections.index', [
            'corrections' => $query->latest()->paginate(20)->withQueryString(),
            'statuses' => ReferenceValue::query()->active()->forCategory('correction_status')->orderBy('sort_order')->get(),
            'canCreateCorrection' => $user->can('create', Correction::class),
        ]);
    }

    public function create(Request $request, CorrectionService $service): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('create', Correction::class), 403);

        $cases = collect();
        $followUps = collect();
        $consultations = collect();
        $achievements = collect();
        if ($user->hasRole('guru_bk')) {
            $cases = BkCase::query()->accessibleTo($user)->with(['student', 'temporaryStudent'])->latest('service_date')->get();
            $followUps = FollowUp::query()->whereIn('case_id', $cases->pluck('id'))->with('case')->latest('planned_date')->get();
            $consultations = Consultation::query()->accessibleTo($user)->latest('session_date')->get();
            $achievements = Achievement::query()->accessibleTo($user)
                ->whereHas('verificationStatus', fn ($statuses) => $statuses->where('code', 'terverifikasi'))
                ->with('student')->latest('achievement_date')->get();
        }
        $students = Student::query()->active()->accessibleTo($user)->orderBy('name')->get();
        [$preselectedType, $preselectedId] = $this->preselection($request, $cases, $students);

        return view('pages.corrections.create', [
            'cases' => $cases,
            'followUps' => $followUps,
            'consultations' => $consultations,
            'achievements' => $achievements,
            'students' => $students,
            'fieldDefinitions' => $service->fieldDefinitions(),
            'preselectedType' => $preselectedType,
            'preselectedId' => $preselectedId,
        ]);
    }

    public function store(StoreCorrectionRequest $request, CorrectionService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $correction = $service->submit($request->validated(), $actor);

        return redirect()->route('corrections.show', $correction)->with('success', 'Pengajuan koreksi berhasil dikirim.');
    }

    public function show(Request $request, Correction $correction): View
    {
        /** @var User $user */
        $user = $request->user();
        $correction->load(['status', 'requester', 'reviewer', 'externalSyncRun']);
        abort_unless($user->can('view', $correction), 403);

        return view('pages.corrections.show', [
            'correction' => $correction,
            'canVerify' => $user->can('verify', $correction),
            'canProcessMaster' => $user->can('processMaster', $correction),
            'syncRuns' => $user->can('processMaster', $correction)
                ? ExternalSyncRun::query()->where('source', 'dapodik')
                    ->whereIn('status', [ExternalSyncRun::STATUS_SUCCEEDED, ExternalSyncRun::STATUS_WARNING])
                    ->whereNotNull('finished_at')->where('finished_at', '>=', $correction->created_at)
                    ->latest('finished_at')->limit(50)->get()
                : collect(),
        ]);
    }

    public function verify(VerifyCorrectionRequest $request, Correction $correction, CorrectionService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $service->verifyOperational($correction, $request->validated(), $actor);

        return redirect()->route('corrections.show', $correction)->with('success', 'Keputusan koreksi operasional berhasil disimpan.');
    }

    public function processMaster(ProcessMasterCorrectionRequest $request, Correction $correction, CorrectionService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $service->processMaster($correction, $request->validated(), $actor);

        return redirect()->route('corrections.show', $correction)->with('success', 'Status koreksi master berhasil diperbarui.');
    }

    /** @return array{?string, ?int} */
    private function preselection(Request $request, $cases, $students): array
    {
        $type = $request->string('target_type')->toString();
        $id = $request->integer('target_id') ?: null;
        if ($type !== '' && $id !== null) {
            return [$type, $id];
        }

        if (mb_strtolower($request->string('object_type')->toString()) === 'murid') {
            $student = $students->firstWhere('nisn', $request->string('object_id')->toString());

            return $student === null ? [null, null] : ['student', (int) $student->id];
        }
        if (mb_strtolower($request->string('object_type')->toString()) === 'kasus') {
            $case = $cases->firstWhere('registration_number', $request->string('object_id')->toString());

            return $case === null ? [null, null] : ['case', (int) $case->id];
        }

        return [null, null];
    }
}
