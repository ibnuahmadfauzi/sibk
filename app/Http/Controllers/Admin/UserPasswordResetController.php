<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Models\User;
use App\Services\TemporaryPasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class UserPasswordResetController extends Controller
{
    public function __invoke(ResetUserPasswordRequest $request, User $user, TemporaryPasswordService $service): JsonResponse|Response
    {
        /** @var User $actor */
        $actor = $request->user();
        $result = $service->issue($user, $actor);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Kata sandi sementara berhasil diterbitkan.',
                'data' => $result->user,
                'temporary_password' => $result->plainTextPassword,
                'expires_at' => $result->expiresAt->toISOString(),
            ])->header('Cache-Control', 'no-store, private');
        }

        return response()
            ->view('pages.admin.users.temporary-password', ['result' => $result])
            ->header('Cache-Control', 'no-store, private');
    }
}
