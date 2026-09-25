<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncEtatibApiRequest;
use App\Integrations\IntegrationConfigurationException;
use App\Models\AcademicYear;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicYearRolloverQuery;
use App\Services\DapodikSyncService;
use App\Services\SimpleEtatibApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DataMasterController extends Controller
{
    public function index(
        Request $request,
        AcademicYearRolloverQuery $rolloverQuery,
    ): Response {
        $this->authorizeAdmin($request);

        $rolloverTargetYear = AcademicYear::query()
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->first();

        return response()->view('pages.data-master.index', [
            'activeTab' => match ($request->query('tab')) {
                'etatib' => 'etatib',
                default => 'dapodik',
            },
            'etatibAutomaticSetting' => $this->etatibAutomaticSetting(),
            'latestDapodikPreview' => ExternalSyncRun::query()
                ->where('source', 'dapodik')
                ->where('status', ExternalSyncRun::STATUS_PREVIEW_READY)
                ->latest('preview_generation')
                ->first(),
            'unresolvedIssueCount' => ExternalSyncIssue::query()
                ->where('entity_type', 'etatib_record')
                ->whereIn('issue_code', ['student_not_found', 'student_name_mismatch'])
                ->whereNull('resolved_at')
                ->count(),
            'studentCount' => Student::query()->active()->count(),
            'rolloverSummary' => $rolloverTargetYear === null
                ? null
                : $rolloverQuery->summarize($rolloverTargetYear),
            'preparationYears' => AcademicYear::query()
                ->where('master_source', AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL)
                ->withCount([
                    'classrooms as active_classroom_count' => fn ($query) => $query->where('is_active', true),
                    'studentClassMemberships as active_student_count' => fn ($query) => $query
                        ->where('is_active', true)
                        ->whereHas('student', fn ($students) => $students->where('is_active', true)),
                ])
                ->orderByDesc('starts_on')
                ->orderByDesc('name')
                ->get(),
        ])->header('Cache-Control', 'no-store');
    }

    public function synchronize(Request $request, DapodikSyncService $syncService): RedirectResponse
    {
        $this->authorizeAdmin($request);
        /** @var User $actor */
        $actor = $request->user();
        try {
            $run = $syncService->synchronize($actor);
        } catch (IntegrationConfigurationException $exception) {
            return back()->withErrors(['sync' => $exception->getMessage()]);
        }

        if ($run->status === ExternalSyncRun::STATUS_FAILED) {
            return back()->withErrors(['sync' => $run->summary ?? 'Sinkronisasi Dapodik gagal.']);
        }

        if ($run->status === ExternalSyncRun::STATUS_PREVIEW_READY) {
            return redirect()
                ->route('data-master.dapodik.previews.show', $run)
                ->with('success', $run->summary);
        }

        return back()->with(
            $run->status === ExternalSyncRun::STATUS_WARNING ? 'warning' : 'success',
            $run->summary,
        );
    }

    public function students(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $academicYearId = $request->integer('academic_year_id') ?: null;
        $search = $request->string('search')->trim()->toString();

        $students = Student::query()
            ->when($search !== '', fn ($query) => $query->where(function ($filter) use ($search): void {
                $filter->where('name', 'like', '%'.$search.'%')
                    ->orWhere('nisn', 'like', '%'.$search.'%');
            }))
            ->when($academicYearId !== null, fn ($query) => $query->whereHas(
                'classMemberships',
                fn ($memberships) => $memberships
                    ->active()
                    ->where('academic_year_id', $academicYearId),
            ))
            ->with(['classMemberships' => fn ($memberships) => $memberships
                ->active()
                ->when($academicYearId !== null, fn ($query) => $query
                    ->where('academic_year_id', $academicYearId))
                ->with(['classroom', 'academicYear'])
                ->latestYearFirst()])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return response()->view('pages.data-master.students', [
            'students' => $students,
            'academicYears' => AcademicYear::query()
                ->orderByDesc('starts_on')
                ->orderByDesc('id')
                ->get(),
            'selectedAcademicYearId' => $academicYearId,
        ])->header('Cache-Control', 'no-store');
    }

    public function previewEtatib(
        SyncEtatibApiRequest $request,
        SimpleEtatibApiService $service,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()
            ->json([
                'success' => true,
                'data' => $service->preview($request->apiUrl(), $actor),
            ])
            ->header('Cache-Control', 'no-store, private');
    }

    public function synchronizeEtatib(
        SyncEtatibApiRequest $request,
        SimpleEtatibApiService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $run = $service->synchronize($request->apiUrl(), $actor);

        if ($run->status === ExternalSyncRun::STATUS_FAILED) {
            return back()->withErrors(['etatib_sync' => $run->summary ?? 'Sinkronisasi e-Tatib gagal.']);
        }

        return back()->with(
            $run->status === ExternalSyncRun::STATUS_WARNING ? 'warning' : 'success',
            $run->summary,
        );
    }

    private function authorizeAdmin(Request $request): void
    {
        Gate::forUser($request->user())->authorize('manageDataMaster');
    }

    /** @return array{enabled: bool, configured: bool, enabled_at: mixed, last_run: ExternalSyncRun|null} */
    private function etatibAutomaticSetting(): array
    {
        $setting = IntegrationSetting::query()
            ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
            ->first();

        return [
            'enabled' => (bool) $setting?->automatic_sync_enabled,
            'configured' => $setting?->getRawOriginal('automatic_sync_url') !== null,
            'enabled_at' => $setting?->automatic_sync_enabled_at,
            'last_run' => ExternalSyncRun::query()
                ->where('source', 'etatib')
                ->whereNull('triggered_by')
                ->latest('started_at')
                ->first(),
        ];
    }
}
