<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Data\TemporaryPasswordResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountService;
use App\Services\TemporaryPasswordService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse|Response
    {
        $this->authorizeRequest($request);

        if ($request->expectsJson()) {
            return response()->json($this->users());
        }

        $encrypted = $request->session()->pull('account_password_result');
        $result = null;
        if (is_string($encrypted)) {
            $password = Crypt::decrypt($encrypted);
            $user = User::query()->find($password['user_id']);
            if ($user !== null) {
                $result = new TemporaryPasswordResult(
                    $user,
                    $password['value'],
                    CarbonImmutable::parse($password['expires_at']),
                );
            }
        }

        $response = response()->view('pages.admin.users.index', $this->pageData($result));

        return $result === null
            ? $response
            : $response->header('Cache-Control', 'no-store, private');
    }

    public function store(StoreUserRequest $request, AccountService $accountService): JsonResponse|RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $result = $accountService->create($request->validated(), $actor);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akun berhasil dibuat.',
                'data' => $result->user,
                'temporary_password' => $result->plainTextPassword,
                'expires_at' => $result->expiresAt->toISOString(),
            ], 201)->header('Cache-Control', 'no-store, private');
        }

        $this->flashPassword($request, $result);

        return redirect()->route('admin.users.index');
    }

    public function resetPassword(
        ResetUserPasswordRequest $request,
        User $user,
        TemporaryPasswordService $service,
    ): JsonResponse|RedirectResponse {
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

        $this->flashPassword($request, $result);

        return redirect()->route('admin.users.index');
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        AccountService $accountService,
    ): JsonResponse|RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $updatedUser = $accountService->update($user, $request->validated(), $actor);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akun berhasil diperbarui.',
                'data' => $updatedUser,
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Akun berhasil diperbarui.');
    }

    private function authorizeRequest(Request $request): void
    {
        abort_unless($request->user()?->can('viewAny', User::class), 403);
    }

    /** @return array<string, mixed> */
    private function pageData(?TemporaryPasswordResult $result = null): array
    {
        return [
            'users' => $this->users($result?->user->getKey()),
            'roles' => Role::query()->active()->orderBy('name')->get(),
            'temporaryPasswordResult' => $result,
        ];
    }

    private function users(?int $featuredId = null): LengthAwarePaginator
    {
        $query = User::query()->with('roles:id,slug,name');
        if ($featuredId !== null) {
            $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$featuredId]);
        }

        return $query->orderBy('name')->paginate(20);
    }

    private function flashPassword(Request $request, TemporaryPasswordResult $result): void
    {
        $request->session()->flash('account_password_result', Crypt::encrypt([
            'user_id' => $result->user->getKey(),
            'value' => $result->plainTextPassword,
            'expires_at' => $result->expiresAt->toISOString(),
        ]));
    }
}
