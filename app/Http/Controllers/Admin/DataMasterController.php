<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\Student;
use App\Models\User;
use App\Services\DapodikSyncService;
use App\Services\EtatibSyncService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DataMasterController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $activeYear = AcademicYear::query()->active()->orderByDesc('starts_on')->first();

        return view('pages.data-master.index', [
            'lastDapodikRun' => ExternalSyncRun::query()->where('source', 'dapodik')->latest('started_at')->first(),
            'lastSuccessfulDapodikRun' => ExternalSyncRun::query()
                ->where('source', 'dapodik')
                ->whereIn('status', [ExternalSyncRun::STATUS_SUCCEEDED, ExternalSyncRun::STATUS_WARNING])
                ->where('processed_count', '>', 0)
                ->latest('finished_at')
                ->first(),
            'lastEtatibRun' => ExternalSyncRun::query()->where('source', 'etatib')->latest('started_at')->first(),
            'lastSuccessfulEtatibRun' => ExternalSyncRun::query()
                ->where('source', 'etatib')
                ->whereIn('status', [ExternalSyncRun::STATUS_SUCCEEDED, ExternalSyncRun::STATUS_WARNING])
                ->where('processed_count', '>', 0)
                ->latest('finished_at')
                ->first(),
            'syncRuns' => ExternalSyncRun::query()->latest('started_at')->limit(10)->get(),
            'unresolvedIssueCount' => ExternalSyncIssue::query()->whereNull('resolved_at')->count(),
            'studentCount' => Student::query()->active()->count(),
            'classroomCount' => Classroom::query()
                ->active()
                ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->getKey()))
                ->count(),
            'activeYear' => $activeYear,
            'preparationYears' => AcademicYear::query()
                ->withCount([
                    'classrooms as active_classroom_count' => fn ($query) => $query->where('is_active', true),
                    'studentClassMemberships as active_student_count' => fn ($query) => $query
                        ->where('is_active', true)
                        ->whereHas('student', fn ($students) => $students->where('is_active', true)),
                ])
                ->orderByDesc('starts_on')
                ->orderByDesc('name')
                ->get(),
        ]);
    }

    public function synchronize(Request $request, DapodikSyncService $syncService): RedirectResponse
    {
        $this->authorizeAdmin($request);
        /** @var User $actor */
        $actor = $request->user();
        $run = $syncService->synchronize($actor);

        if ($run->status === ExternalSyncRun::STATUS_FAILED) {
            return back()->withErrors(['sync' => $run->summary ?? 'Sinkronisasi Dapodik gagal.']);
        }

        return back()->with(
            $run->status === ExternalSyncRun::STATUS_WARNING ? 'warning' : 'success',
            $run->summary,
        );
    }

    public function synchronizeEtatib(Request $request, EtatibSyncService $syncService): RedirectResponse
    {
        $this->authorizeAdmin($request);
        /** @var User $actor */
        $actor = $request->user();
        $run = $syncService->synchronize($actor);

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
}
