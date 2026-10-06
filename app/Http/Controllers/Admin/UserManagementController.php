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
        $filters = $this->filters($request);

        if ($request->expectsJson()) {
            return response()->json($this->users($filters));
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

        $response = response()->view('pages.admin.users.index', $this->pageData($filters, $result));

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
    private function pageData(array $filters, ?TemporaryPasswordResult $result = null): array
    {
        return [
            'users' => $this->users($filters, $result?->user->getKey()),
            'roles' => Role::query()->active()->orderBy('name')->get(),
            'filters' => $filters,
            'temporaryPasswordResult' => $result,
        ];
    }

    /** @return array{q: string, role: string} */
    private function filters(Request $request): array
    {
        $search = $request->query('q');
        $role = $request->query('role');
        $role = is_string($role) && Role::query()->active()->where('slug', $role)->exists()
            ? $role
            : '';

        return [
            'q' => is_string($search) ? trim($search) : '',
            'role' => $role,
        ];
    }

    /** @param array{q: string, role: string} $filters */
    private function users(array $filters, ?int $featuredId = null): LengthAwarePaginator
    {
        $query = User::query()->with('roles:id,slug,name');
        if ($filters['q'] !== '') {
            $query->where(function ($users) use ($filters): void {
                $users->where('name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('email', 'like', '%'.$filters['q'].'%');
            });
        }
        if ($filters['role'] !== '') {
            $query->whereHas('roles', fn ($roles) => $roles->where('slug', $filters['role']));
        }
        if ($featuredId !== null) {
            $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$featuredId]);
        }

        return $query->orderBy('name')->paginate(20)->appends(array_filter($filters));
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
