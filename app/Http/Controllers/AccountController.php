<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('roles');
        $academicYear = AcademicYear::query()->active()->orderByDesc('starts_on')->first();

        return view('pages.account.index', [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->where('is_active', true)->pluck('name')->values()->all(),
                'status' => $user->is_active ? 'Aktif' : 'Nonaktif',
                'last_login_at' => $user->last_login_at,
                'academic_year' => $academicYear?->name ?? 'Belum tersedia',
            ],
        ]);
    }
}
