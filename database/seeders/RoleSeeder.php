<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'guru_bk' => 'Guru BK',
            'koordinator_bk' => 'Koordinator BK',
            'waka_kesiswaan' => 'Waka Kesiswaan',
            'admin_it' => 'Admin IT',
        ];

        foreach ($roles as $slug => $name) {
            Role::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true],
            );
        }
    }
}
