<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * @param  array{name: string, email: string, password: string, roles: list<string>, is_active?: bool}  $data
     */
    public function create(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => $data['is_active'] ?? true,
                'deactivated_at' => ($data['is_active'] ?? true) ? null : now(),
                'updated_by' => $actor->getKey(),
            ]);

            $roleIds = Role::query()->whereIn('slug', $data['roles'])->pluck('id');
            $user->roles()->sync($roleIds);
            $user->load('roles');

            $this->auditService->record(
                action: 'account.created',
                auditable: $user,
                summary: sprintf('Akun %s dibuat.', $user->email),
                actor: $actor,
                after: $this->snapshot($user),
            );

            return $user;
        });
    }

    /**
     * @param  array{name?: string, email?: string, password?: string|null, roles?: list<string>, is_active?: bool}  $data
     */
    public function update(User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor): User {
            $user->loadMissing('roles');
            $before = $this->snapshot($user);
            $attributes = Arr::only($data, ['name', 'email', 'password', 'is_active']);

            if (($attributes['password'] ?? null) === null) {
                unset($attributes['password']);
            }

            if (array_key_exists('is_active', $attributes)) {
                $attributes['deactivated_at'] = $attributes['is_active'] ? null : now();
            }

            $user->fill([...$attributes, 'updated_by' => $actor->getKey()]);
            $user->save();

            if (array_key_exists('roles', $data)) {
                $roleIds = Role::query()->whereIn('slug', $data['roles'])->pluck('id');
                $user->roles()->sync($roleIds);
            }

            $user->load('roles');
            $this->auditService->record(
                action: 'account.updated',
                auditable: $user,
                summary: sprintf('Akun %s diperbarui.', $user->email),
                actor: $actor,
                before: $before,
                after: $this->snapshot($user),
            );

            return $user;
        });
    }

    /** @return array<string, mixed> */
    private function snapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'roles' => $user->roles->pluck('slug')->sort()->values()->all(),
        ];
    }
}
