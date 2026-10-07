<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncEtatibApiRequest;
use App\Integrations\IntegrationConfigurationException;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\EtatibDuplicateDecision;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\Student;
use App\Models\StudentClassMembership;
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
        $activeAcademicYear = AcademicYear::query()->active()->first();
        $activeTab = match ($request->query('tab')) {
            'etatib' => 'etatib',
            'sinkronisasi' => 'sinkronisasi',
            default => 'dapodik',
        };
        $sortQuery = static function ($query, string $sortParam, string $directionParam, array $columns, string $defaultColumn, string $defaultDirection = 'desc') use ($request) {
            $sort = $request->query($sortParam);
            $direction = $request->query($directionParam);
            if (is_string($sort) && array_key_exists($sort, $columns) && in_array($direction, ['asc', 'desc'], true)) {
                return $query->orderBy($columns[$sort], $direction)->orderBy('id');
            }

            return $query->orderBy($defaultColumn, $defaultDirection)->orderBy('id', $defaultDirection);
        };
        $syncIssues = $activeTab === 'sinkronisasi' ? $sortQuery(ExternalSyncIssue::query()
            ->whereNull('resolved_at')
            ->with('syncRun:id,source,started_at'), 'issue_sort', 'issue_direction', ['data' => 'input_name'], 'id')
            ->paginate(20, ['*'], 'issue_page')
            ->withQueryString() : null;
        $localTargets = [];
        if ($syncIssues !== null) {
            $localIds = fn (string $type): array => $syncIssues->getCollection()
                ->filter(fn (ExternalSyncIssue $issue): bool => $issue->entity_type === $type
                    && $issue->issue_code === 'unmatched_local_record'
                    && str_starts_with((string) $issue->source_identifier, 'local:'))
                ->map(fn (ExternalSyncIssue $issue): int => (int) substr($issue->source_identifier, 6))
                ->all();
            foreach (AcademicYear::query()->whereKey($localIds('academic_year'))->get(['id', 'name']) as $year) {
                $localTargets['academic_year:'.$year->id] = 'Tahun ajaran '.$year->name;
            }
            foreach (Student::query()->whereKey($localIds('student'))->get(['id', 'name', 'nisn']) as $student) {
                $localTargets['student:'.$student->id] = \App\Support\StudentName::display($student->name).' (NISN '.$student->nisn.')';
            }
            foreach (Classroom::query()->whereKey($localIds('classroom'))->with('academicYear:id,name')->get(['id', 'name', 'academic_year_id']) as $classroom) {
                $localTargets['classroom:'.$classroom->id] = $classroom->name.' ('.$classroom->academicYear?->name.')';
            }
            foreach (StudentClassMembership::query()->whereKey($localIds('membership'))
                ->with(['student:id,name', 'classroom:id,name', 'academicYear:id,name'])
                ->get(['id', 'student_id', 'classroom_id', 'academic_year_id']) as $membership) {
                $localTargets['membership:'.$membership->id] = \App\Support\StudentName::display($membership->student?->name ?? 'Murid').' — '.($membership->classroom?->name ?? 'Kelas').' ('.$membership->academicYear?->name.')';
            }
        }

        return response()->view('pages.data-master.index', [
            'activeTab' => $activeTab,
            'syncIssues' => $syncIssues,
            'classroomDecisions' => $syncIssues === null ? null : $sortQuery(ExternalSyncIssue::query()
                ->where('issue_code', 'student_classroom_mismatch')
                ->whereNotNull('resolved_at')
                ->whereIn('details->review->action', ['use_school', 'use_etatib']), 'decision_sort', 'decision_direction', ['data' => 'input_name', 'waktu' => 'resolved_at'], 'resolved_at')
                ->paginate(10, ['*'], 'decision_page')->withQueryString(),
            'localTargets' => $localTargets,
            'syncRuns' => $syncIssues === null ? null : $sortQuery(ExternalSyncRun::query(), 'run_sort', 'run_direction', ['waktu' => 'started_at', 'sumber' => 'source', 'status' => 'status'], 'started_at')
                ->paginate(20, ['id', 'source', 'status', 'started_at', 'processed_count', 'conflict_count', 'summary'], 'run_page')
                ->withQueryString(),
            'etatibAutomaticSetting' => $this->etatibAutomaticSetting(),
            'etatibDuplicateDecisions' => $activeTab === 'etatib'
                ? $sortQuery(EtatibDuplicateDecision::query()->where('is_active', true), 'duplicate_sort', 'duplicate_direction', ['murid' => 'source_name', 'jumlah' => 'copy_count', 'waktu' => 'approved_at'], 'approved_at')
                    ->paginate(10, ['id', 'source_nisn', 'source_name', 'copy_count', 'approved_at'], 'duplicate_page')->withQueryString()
                : null,
            'latestDapodikPreview' => ExternalSyncRun::query()
                ->where('source', 'dapodik')
                ->where('status', ExternalSyncRun::STATUS_PREVIEW_READY)
                ->latest('preview_generation')
                ->first(),
            'unresolvedIssueCount' => ExternalSyncIssue::query()
                ->where('entity_type', 'etatib_record')
                ->whereIn('issue_code', ['student_not_found', 'student_name_mismatch'])
                ->whereNull('resolved_at')
                ->distinct()
                ->count('source_identifier'),
            'rolloverSummary' => $rolloverTargetYear === null
                ? null
                : $rolloverQuery->summarize($rolloverTargetYear),
            'rolloverTargetYear' => $rolloverTargetYear,
            'activeAcademicYear' => $activeAcademicYear,
            'importableYearExists' => AcademicYear::query()
                ->where('master_source', AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL)
                ->where(fn ($years) => $years->where('is_active', true)->orWhereNull('activated_at'))
                ->when($activeAcademicYear !== null, fn ($years) => $years->where(fn ($visible) => $visible
                    ->where('is_active', true)
                    ->orWhere('name', '>', $activeAcademicYear->name)))
                ->exists(),
            'academicYears' => AcademicYear::query()
                ->when($activeAcademicYear !== null, fn ($years) => $years->where(fn ($visible) => $visible
                    ->where('is_active', true)
                    ->orWhere(fn ($preparing) => $preparing
                        ->whereNull('activated_at')
                        ->where('name', '>', $activeAcademicYear->name))))
                ->when($activeAcademicYear === null, fn ($years) => $years->whereNull('activated_at'))
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

    public function previewEtatib(
        SyncEtatibApiRequest $request,
        SimpleEtatibApiService $service,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $request->session()->forget('etatib_api_preview');
        $preview = $service->preview($request->apiUrl(), $actor);
        $request->session()->put('etatib_api_preview', [
            'actor_id' => $actor->getKey(),
            'url_hash' => hash('sha256', $request->apiUrl()),
            'fingerprint' => $preview['fingerprint'],
            'at' => now()->getTimestamp(),
        ]);
        unset($preview['fingerprint']);

        return response()
            ->json([
                'success' => true,
                'data' => $preview,
            ])
            ->header('Cache-Control', 'no-store, private');
    }

    public function synchronizeEtatib(
        SyncEtatibApiRequest $request,
        SimpleEtatibApiService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $run = $service->synchronize($request->apiUrl(), $actor, $request->session()->pull('etatib_api_preview'), $request->identityDecisions(), $request->duplicateDecisions());

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
