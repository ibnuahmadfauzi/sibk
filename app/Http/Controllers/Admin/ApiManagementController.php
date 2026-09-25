<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiManagementController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('manageDataMaster'), 403);

        return redirect()->route('data-master.index', ['tab' => 'dapodik']);
    }
}
