<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePasswordChanged
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ($request->user()?->must_change_password) {
            return redirect()->route('account.password.edit');
        }

        return $next($request);
    }
}
