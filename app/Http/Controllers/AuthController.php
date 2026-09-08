<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(Request $request): View
    {
        $allowedStates = ['error', 'system-error', 'success'];
        $authPreviewState = $request->query('auth_state', 'error');

        if (! in_array($authPreviewState, $allowedStates, true)) {
            $authPreviewState = 'error';
        }

        return view('pages.login.index', compact('authPreviewState'));
    }

    /** @throws ValidationException */
    public function store(LoginRequest $request, AuditService $auditService): RedirectResponse
    {
        $credentials = [
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'credentials' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        $auditService->record(
            action: 'auth.login',
            auditable: $user,
            summary: 'Pengguna berhasil masuk.',
            actor: $user,
            request: $request,
        );

        return redirect()->intended(route('dashboard.preview'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
