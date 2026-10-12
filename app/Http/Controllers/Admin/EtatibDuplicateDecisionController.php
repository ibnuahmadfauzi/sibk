<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EtatibDuplicateDecision;
use App\Services\EtatibAutomaticSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EtatibDuplicateDecisionController extends Controller
{
    public function destroy(Request $request, EtatibDuplicateDecision $decision, EtatibAutomaticSyncService $service): RedirectResponse
    {
        $service->revokeDuplicateDecision($decision, $request->user());

        return back()->with('success', 'Keputusan duplikasi dicabut. Sinkronisasi berikutnya memerlukan tinjauan baru.');
    }
}
