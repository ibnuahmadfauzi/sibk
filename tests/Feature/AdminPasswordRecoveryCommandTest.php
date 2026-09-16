<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AdminPasswordRecoveryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_admin_can_be_recovered_through_hidden_interactive_command(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin_it')->firstOrFail());

        $this->artisan('sibk:reset-admin-password')
            ->expectsQuestion('Email Admin IT', $admin->email)
            ->expectsQuestion('Kata sandi sementara baru', 'PulihAman123')
            ->expectsQuestion('Konfirmasi kata sandi sementara', 'PulihAman123')
            ->assertSuccessful();

        $admin->refresh();
        $this->assertTrue(Hash::check('PulihAman123', $admin->password));
        $this->assertTrue($admin->must_change_password);
        $this->assertStringNotContainsString('PulihAman123', Artisan::output());

        $auditJson = AuditLog::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $admin->id)
            ->get()
            ->toJson();
        $this->assertStringNotContainsString('PulihAman123', $auditJson);
    }

    public function test_recovery_rejects_non_admin_inactive_admin_and_invalid_password(): void
    {
        $this->seed(RoleSeeder::class);
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('slug', 'guru_bk')->firstOrFail());

        $this->artisan('sibk:reset-admin-password')
            ->expectsQuestion('Email Admin IT', $teacher->email)
            ->expectsOutput('Admin IT aktif tidak ditemukan.')
            ->assertFailed();

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin_it')->firstOrFail());
        $this->artisan('sibk:reset-admin-password')
            ->expectsQuestion('Email Admin IT', $admin->email)
            ->expectsQuestion('Kata sandi sementara baru', 'tanpaangka')
            ->expectsQuestion('Konfirmasi kata sandi sementara', 'tanpaangka')
            ->expectsOutput('Kata sandi minimal delapan karakter serta memuat huruf dan angka.')
            ->assertFailed();
    }
}
