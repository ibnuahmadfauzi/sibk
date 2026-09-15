<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TemporaryPasswordResult;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly TemporaryPasswordService $temporaryPasswords,
    ) {}

    /**
     * @param  array{name: string, email: string, roles: list<string>, is_active?: bool}  $data
     */
    public function create(array $data, User $actor): TemporaryPasswordResult
    {
        return DB::transaction(function () use ($data, $actor): TemporaryPasswordResult {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Str::random(64),
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

            return $this->temporaryPasswords->issue($user, $actor);
        });
    }

    /**
     * @param  array{name?: string, email?: string, roles?: list<string>, is_active?: bool}  $data
     */
    public function update(User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor): User {
            $user->loadMissing('roles');
            $before = $this->snapshot($user);
            $attributes = Arr::only($data, ['name', 'email', 'is_active']);

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

    /** @param array{password: string} $data */
    public function changePassword(User $user, array $data, string $currentSessionId): User
    {
        return DB::transaction(function () use ($user, $data, $currentSessionId): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $user->forceFill([
                'password' => $data['password'],
                'must_change_password' => false,
                'temporary_password_expires_at' => null,
                'password_changed_at' => now(),
            ])->save();
            DB::table('sessions')
                ->where('user_id', $user->getKey())
                ->where('id', '!=', $currentSessionId)
                ->delete();
            $this->auditService->record(
                action: 'account.password_changed',
                auditable: $user,
                summary: 'Kata sandi akun diperbarui oleh pemilik akun.',
                actor: $user,
            );

            return $user->refresh();
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
