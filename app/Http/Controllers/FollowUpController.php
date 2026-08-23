<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SaveFollowUpRequest;
use App\Models\BkCase;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\User;
use App\Services\FollowUpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function create(Request $request, BkCase $case): View
    {
        abort_unless($request->user()?->can('update', $case), 403);

        return $this->form($case, null);
    }

    public function edit(Request $request, BkCase $case, FollowUp $followUp): View
    {
        abort_unless($request->user()?->can('update', $case), 403);
        abort_unless($followUp->case_id === $case->getKey(), 404);

        return $this->form($case, $followUp);
    }

    public function store(
        SaveFollowUpRequest $request,
        BkCase $case,
        FollowUpService $followUpService,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $followUpService->record($case, $request->validated(), $actor);

        return redirect()->route('cases.show', $case)->with('success', 'Tindak lanjut berhasil dicatat.');
    }

    public function update(
        SaveFollowUpRequest $request,
        BkCase $case,
        FollowUp $followUp,
        FollowUpService $followUpService,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $followUpService->update($case, $followUp, $request->validated(), $actor);

        return redirect()->route('cases.show', $case)->with('success', 'Tindak lanjut berhasil diperbarui.');
    }

    private function form(BkCase $case, ?FollowUp $followUp): View
    {
        $case->load(['student', 'temporaryStudent']);

        return view('pages.cases.follow-up', [
            'case' => $case,
            'followUp' => $followUp,
            'isEdit' => $followUp !== null,
            'followUpTypes' => ReferenceValue::query()->active()->forCategory('follow_up_type')->orderBy('sort_order')->get(),
            'followUpStatuses' => ReferenceValue::query()->active()->forCategory('follow_up_status')->orderBy('sort_order')->get(),
        ]);
    }
}
