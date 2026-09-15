<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentDeparture;
use App\Models\User;
use App\Services\WakaStudentDepartureService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class WakaStudentDepartureController extends Controller
{
    public function __invoke(Request $request, WakaStudentDepartureService $service): View
    {
        abort_unless($request->user()?->can('viewAny', StudentDeparture::class), 403);
        /** @var User $user */
        $user = $request->user();
        $departures = $service->paginate();
        $service->auditViewed($user, $departures->count(), $request);

        return view('pages.waka.student-departures.index', ['departures' => $departures]);
    }
}
