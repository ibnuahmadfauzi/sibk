<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ApiTrialSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new InvalidArgumentException('Seeder uji API hanya boleh dijalankan pada environment local/testing.');
        }

        $password = (string) config('sibk.seed_accounts.password');
        if (mb_strlen($password) < 8) {
            throw new InvalidArgumentException('SIBK_SEED_ACCOUNT_PASSWORD minimal terdiri dari 8 karakter.');
        }

        $this->call([RoleSeeder::class, ReferenceSeeder::class]);

        $accounts = [
            ['Nadia Prameswari', 'nadia.bk@ruangbk.test', 'guru_bk'],
            ['Raka Wicaksana', 'raka.bk@ruangbk.test', 'guru_bk'],
            ['Sinta Maharani', 'sinta.bk@ruangbk.test', 'guru_bk'],
            ['Dimas Aditya', 'dimas.bk@ruangbk.test', 'guru_bk'],
            ['Larissa Putri', 'larissa.bk@ruangbk.test', 'guru_bk'],
            ['Fajar Nugraha', 'fajar.bk@ruangbk.test', 'guru_bk'],
            ['Ratih Kurniasih', 'ratih.koordinator@ruangbk.test', 'koordinator_bk'],
            ['Admin Uji API', 'admin.api@ruangbk.test', 'admin_it'],
        ];

        DB::transaction(function () use ($accounts, $password): void {
            foreach ($accounts as [$name, $email, $roleSlug]) {
                $user = User::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => $password,
                        'email_verified_at' => now(),
                        'is_active' => true,
                        'deactivated_at' => null,
                    ],
                );

                $role = Role::query()->where('slug', $roleSlug)->where('is_active', true)->firstOrFail();
                $user->roles()->sync([$role->getKey()]);
            }
        });
    }
}
