<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ImportAchievementRequest;
use App\Models\User;
use App\Services\AchievementImportService;
use Illuminate\Http\RedirectResponse;

class AchievementImportController extends Controller
{
    public function store(ImportAchievementRequest $request, AchievementImportService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $count = $service->import($request->file('file'), $actor);

        return redirect()->route('achievements.index')->with('success', $count.' prestasi berhasil diimpor.');
    }
}
