<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $this->authorizeRequest($request);

        $users = User::query()
            ->with('roles:id,slug,name')
            ->orderBy('name')
            ->paginate(20);

        if ($request->expectsJson()) {
            return response()->json($users);
        }

        return view('pages.admin.users.index', [
            'users' => $users,
            'roles' => Role::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request, AccountService $accountService): JsonResponse|RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $user = $accountService->create($request->validated(), $actor);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akun berhasil dibuat.',
                'data' => $user,
            ], 201);
        }

        return redirect()->route('admin.users.index')->with('success', 'Akun berhasil dibuat.');
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
}
