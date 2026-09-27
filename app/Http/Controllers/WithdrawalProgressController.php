<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWithdrawalProgressRequest;
use App\Http\Requests\UpdateWithdrawalProgressRequest;
use App\Models\User;
use App\Models\WithdrawalProgress;
use App\Services\WithdrawalProgressService;
use Illuminate\Http\RedirectResponse;

final class WithdrawalProgressController extends Controller
{
    public function store(StoreWithdrawalProgressRequest $request, WithdrawalProgressService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $service->create($request->validated(), $actor);

        return redirect()->route('cases.index', ['tab' => 'pengunduran-diri'])
            ->with('success', 'Penanganan pengunduran diri berhasil dicatat.');
    }

    public function updateProgress(UpdateWithdrawalProgressRequest $request, WithdrawalProgress $withdrawal, WithdrawalProgressService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $service->updateProgress($withdrawal, $request->validated('progress'), $actor);

        return redirect()->route('cases.index', ['tab' => 'pengunduran-diri'])
            ->with('success', 'Progres pengunduran diri berhasil diperbarui.');
    }
}
