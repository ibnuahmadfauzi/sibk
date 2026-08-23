<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $service): View
    {
        /** @var User $user */
        $user = $request->user();
        $years = AcademicYear::query()->orderByDesc('starts_on')->get();
        $activeYear = $request->integer('academic_year_id')
            ? $years->firstWhere('id', $request->integer('academic_year_id'))
            : ($years->firstWhere('is_active', true) ?? $years->first());
        $dashboard = $service->forUser($user, $activeYear);

        return view('pages.dashboard.index', [
            'dashboard' => $dashboard,
            'years' => $years,
            'activeYear' => $activeYear,
        ]);
    }
}
