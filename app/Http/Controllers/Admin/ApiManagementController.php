<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExternalSyncRun;
use App\Services\IntegrationSettingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiManagementController extends Controller
{
    public function index(Request $request, IntegrationSettingService $service): Response
    {
        abort_unless($request->user()?->can('manageDataMaster'), 403);

        return response()->view('pages.admin.api.index', [
            'integrationStates' => $service->allStates(),
            'lastSuccessfulDapodikRun' => ExternalSyncRun::query()
                ->where('source', 'dapodik')
                ->whereIn('status', [ExternalSyncRun::STATUS_SUCCEEDED, ExternalSyncRun::STATUS_WARNING])
                ->where('processed_count', '>', 0)
                ->latest('finished_at')
                ->first(),
            'lastSuccessfulEtatibRun' => ExternalSyncRun::query()
                ->where('source', 'etatib')
                ->whereIn('status', [ExternalSyncRun::STATUS_SUCCEEDED, ExternalSyncRun::STATUS_WARNING])
                ->where('processed_count', '>', 0)
                ->latest('finished_at')
                ->first(),
        ])->header('Cache-Control', 'no-store');
    }
}
