<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCaseCoordinationRequest;
use App\Http\Requests\UpdateCaseCoordinationRequest;
use App\Models\BkCase;
use App\Models\CaseCoordination;
use App\Models\User;
use App\Services\CaseService;
use Illuminate\Http\RedirectResponse;

class CaseCoordinationController extends Controller
{
    public function store(
        StoreCaseCoordinationRequest $request,
        BkCase $case,
        CaseService $caseService,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $caseService->coordinate($case, $request->validated(), $actor);

        return back()->with('success', 'Koordinasi Waka berhasil dicatat.');
    }

    public function update(
        UpdateCaseCoordinationRequest $request,
        BkCase $case,
        CaseCoordination $coordination,
        CaseService $caseService,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $caseService->updateCoordination($case, $coordination, $request->validated(), $actor);

        return back()->with('success', 'Status koordinasi berhasil diperbarui.');
    }
}
