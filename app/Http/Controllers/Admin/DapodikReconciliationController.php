<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MapDapodikPreviewItemRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\DapodikSyncPreviewItem;
use App\Models\ExternalSyncRun;
use App\Models\User;
use App\Services\DapodikReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DapodikReconciliationController extends Controller
{
    public function show(Request $request, ExternalSyncRun $syncRun): Response
    {
        Gate::forUser($request->user())->authorize('manageDataMaster');
        abort_unless($syncRun->source === 'dapodik', 404);

        return response()->view('pages.data-master.dapodik-preview', [
            'syncRun' => $syncRun->load(['previewItems' => fn ($query) => $query
                ->orderByRaw("CASE entity_type WHEN 'academic_year' THEN 1 WHEN 'classroom' THEN 2 WHEN 'student' THEN 3 ELSE 4 END")
                ->orderBy('id')]),
            'academicYearCandidates' => AcademicYear::query()
                ->where('master_source', AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL)
                ->whereNull('source_confirmed_at')
                ->whereNull('dapodik_id')
                ->orderBy('name')
                ->get(),
            'classroomCandidates' => Classroom::query()
                ->with('academicYear:id,name')
                ->where('master_source', Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL)
                ->whereNull('source_confirmed_at')
                ->whereNull('dapodik_id')
                ->orderBy('name')
                ->get(),
        ])->header('Cache-Control', 'no-store');
    }

    public function update(
        MapDapodikPreviewItemRequest $request,
        ExternalSyncRun $syncRun,
        DapodikSyncPreviewItem $item,
        DapodikReconciliationService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $service->decide($syncRun, $item, $request->validated(), $actor);

        return redirect()
            ->route('data-master.dapodik.previews.show', $syncRun)
            ->with('success', 'Keputusan pencocokan berhasil disimpan.');
    }

    public function apply(
        Request $request,
        ExternalSyncRun $syncRun,
        DapodikReconciliationService $service,
    ): RedirectResponse {
        Gate::forUser($request->user())->authorize('manageDataMaster');
        $validated = $request->validate([
            'decision_revision' => ['required', 'integer', 'min:0'],
        ]);
        /** @var User $actor */
        $actor = $request->user();
        $service->apply($syncRun, $actor, (int) $validated['decision_revision']);

        return redirect()
            ->route('data-master.index')
            ->with('success', 'Pratinjau Dapodik berhasil diterapkan tanpa mengganti ID internal.');
    }
}
