<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AccountPasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user()?->must_change_password) {
            return redirect()->route('account.index', ['password' => 1]);
        }

        return view('pages.account.change-password', [
            'required' => (bool) $request->user()?->must_change_password,
        ]);
    }

    public function update(ChangePasswordRequest $request, AccountService $service): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $required = (bool) $user->must_change_password;
        $service->changePassword($user, $request->validated(), $request->session()->getId());
        $request->session()->regenerate();

        return redirect()->route($required ? 'dashboard.preview' : 'account.index')->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
