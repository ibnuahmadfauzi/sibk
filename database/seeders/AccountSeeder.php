<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) config('sibk.seed_accounts.password');
        if (mb_strlen($password) < 8) {
            throw new InvalidArgumentException('SIBK_SEED_ACCOUNT_PASSWORD minimal terdiri dari 8 karakter.');
        }

        $accounts = [
            'guru_bk' => [
                'name' => 'Guru BK Demo',
                'email' => 'guru.bk@ruangbk.test',
            ],
            'koordinator_bk' => [
                'name' => 'Koordinator BK Demo',
                'email' => 'koordinator.bk@ruangbk.test',
            ],
            'waka_kesiswaan' => [
                'name' => 'Waka Kesiswaan Demo',
                'email' => 'waka.kesiswaan@ruangbk.test',
            ],
            'admin_it' => [
                'name' => 'Admin IT Demo',
                'email' => 'admin.it@ruangbk.test',
            ],
        ];

        DB::transaction(function () use ($accounts, $password): void {
            foreach ($accounts as $roleSlug => $account) {
                $role = Role::query()->where('slug', $roleSlug)->where('is_active', true)->firstOrFail();
                $user = User::query()->updateOrCreate(
                    ['email' => $account['email']],
                    [
                        'name' => $account['name'],
                        'password' => \Illuminate\Support\Facades\Hash::make($password),
                        'email_verified_at' => now(),
                        'is_active' => true,
                        'deactivated_at' => null,
                    ],
                );

                $user->roles()->sync([$role->getKey()]);
            }
        });
    }
}
