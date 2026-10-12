<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TemporaryPasswordResult;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        $roleIds = $this->validatedRoleIds($data['roles']);

        return DB::transaction(function () use ($data, $actor, $roleIds): TemporaryPasswordResult {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Str::random(64),
                'is_active' => $data['is_active'] ?? true,
                'deactivated_at' => ($data['is_active'] ?? true) ? null : now(),
                'updated_by' => $actor->getKey(),
            ]);

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
        if (array_key_exists('roles', $data) && $user->is($actor)) {
            throw ValidationException::withMessages(['roles' => 'Peran akun sendiri tidak dapat diubah.']);
        }

        if (array_key_exists('is_active', $data)
            && filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) === false
            && $user->is($actor)
        ) {
            throw ValidationException::withMessages(['is_active' => 'Akun sendiri tidak dapat dinonaktifkan.']);
        }

        $roleIds = array_key_exists('roles', $data)
            ? $this->validatedRoleIds($data['roles'])
            : null;

        return DB::transaction(function () use ($user, $data, $actor, $roleIds): User {
            $user->loadMissing('roles');
            $before = $this->snapshot($user);
            $attributes = Arr::only($data, ['name', 'email', 'is_active']);

            if (array_key_exists('is_active', $attributes)) {
                $attributes['deactivated_at'] = $attributes['is_active'] ? null : now();
            }

            $user->fill([...$attributes, 'updated_by' => $actor->getKey()]);
            $user->save();

            if ($roleIds !== null) {
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

    /**
     * @param  list<string>  $slugs
     * @return list<int>
     */
    private function validatedRoleIds(array $slugs): array
    {
        $selected = array_values(array_unique($slugs));
        sort($selected);
        $allowed = [
            ['admin_it'],
            ['guru_bk'],
            ['koordinator_bk'],
            ['waka_kesiswaan'],
            ['guru_bk', 'koordinator_bk'],
        ];
        $roles = Role::query()->active()->whereIn('slug', $selected)->get(['id', 'slug']);

        if (count($selected) !== count($slugs)
            || ! in_array($selected, $allowed, true)
            || $roles->count() !== count($selected)
        ) {
            throw ValidationException::withMessages([
                'roles' => 'Pilih Admin IT atau Waka Kesiswaan saja, atau Guru BK dan Koordinator BK.',
            ]);
        }

        return $roles->pluck('id')->all();
    }

    /** @param array{password: string} $data */
    public function changePassword(User $user, array $data, string $currentSessionId): User
    {
        return DB::transaction(function () use ($user, $data, $currentSessionId): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
            if ($user->must_change_password
                && ($user->temporary_password_expires_at === null || $user->temporary_password_expires_at->isPast())) {
                throw ValidationException::withMessages([
                    'current_password' => 'Kata sandi sementara telah kedaluwarsa. Hubungi Admin IT untuk menerbitkan ulang.',
                ]);
            }
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
