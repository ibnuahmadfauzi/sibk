<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCaseFollowUpRequest;
use App\Models\BkCase;
use App\Models\User;
use App\Services\CaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CaseFollowUpController extends Controller
{
    public function index(Request $request, BkCase $case): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('view', $case), 403);

        $followUps = $case->followUps()
            ->with('followUpType')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'follow_up_type_id' => $item->follow_up_type_id,
                'follow_up_type_label' => $item->followUpType?->label,
                'follow_up_date' => $item->follow_up_date->toDateString(),
                'follow_up_date_formatted' => $item->follow_up_date->locale('id')->translatedFormat('d M Y'),
                'notes' => $item->notes,
                'created_at' => $item->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => $followUps,
        ]);
    }

    public function store(
        StoreCaseFollowUpRequest $request,
        BkCase $case,
        CaseService $caseService,
    ): JsonResponse|RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $followUp = $caseService->addFollowUp($case, $request->validated(), $actor);
        $case->refresh();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Tindak lanjut berhasil ditambahkan.',
                'data' => [
                    'id' => $followUp->id,
                    'follow_up_type_id' => $followUp->follow_up_type_id,
                    'follow_up_type_label' => $followUp->followUpType?->label,
                    'follow_up_date' => $followUp->follow_up_date->toDateString(),
                    'follow_up_date_formatted' => $followUp->follow_up_date->locale('id')->translatedFormat('d M Y'),
                    'notes' => $followUp->notes,
                    'status_code' => $case->status?->code,
                    'status_label' => $case->status?->label,
                    'updated_at' => $case->updated_at?->toJSON(),
                ],
            ]);
        }

        return redirect()->route('cases.index')->with('success', 'Tindak lanjut berhasil ditambahkan.');
    }
}
