<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_it_can_create_a_multi_role_account(): void
    {
        $admin = $this->userWithRoles(['admin_it']);

        $response = $this->actingAs($admin)->postJson('/admin/users', [
            'name' => 'Guru BK Rangkap',
            'email' => 'guru.rangkap@example.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'roles' => ['guru_bk', 'koordinator_bk'],
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'guru.rangkap@example.test')
            ->assertJsonMissingPath('data.password');

        $created = User::query()->where('email', 'guru.rangkap@example.test')->firstOrFail();
        $this->assertTrue($created->hasRole('guru_bk'));
        $this->assertTrue($created->hasRole('koordinator_bk'));
        $this->assertFalse($created->hasRole('admin_it'));
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'account.created',
            'auditable_id' => $created->id,
        ]);
    }

    public function test_admin_it_receives_web_interface_and_browser_forms_redirect_back(): void
    {
        $admin = $this->userWithRoles(['admin_it']);
        $target = $this->userWithRoles(['guru_bk']);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertViewIs('pages.admin.users.index')
            ->assertSee('Kelola Akun')
            ->assertSee($target->email)
            ->assertSee('Buat Akun')
            ->assertHeader('content-type', 'text/html; charset=UTF-8');

        $this->actingAs($admin)->patch(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'roles' => ['guru_bk'],
            'is_active' => false,
        ])->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'Akun berhasil diperbarui.');

        $this->assertFalse($target->fresh()?->is_active);
    }

    public function test_admin_it_can_deactivate_and_reactivate_an_account(): void
    {
        $admin = $this->userWithRoles(['admin_it']);
        $target = $this->userWithRoles(['guru_bk']);

        $this->actingAs($admin)->patchJson('/admin/users/'.$target->id, [
            'is_active' => false,
        ])->assertOk()->assertJsonPath('data.is_active', false);

        $this->assertFalse($target->fresh()?->is_active);
        $this->assertNotNull($target->fresh()?->deactivated_at);

        $this->actingAs($admin)->patchJson('/admin/users/'.$target->id, [
            'is_active' => true,
        ])->assertOk()->assertJsonPath('data.is_active', true);

        $this->assertTrue($target->fresh()?->is_active);
        $this->assertNull($target->fresh()?->deactivated_at);
    }

    public function test_coordinator_and_multi_role_counselor_cannot_manage_accounts(): void
    {
        $coordinator = $this->userWithRoles(['koordinator_bk']);
        $multiRole = $this->userWithRoles(['guru_bk', 'koordinator_bk']);
        $payload = [
            'name' => 'Pengguna Baru',
            'email' => 'baru@example.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'roles' => ['guru_bk'],
        ];

        $this->actingAs($coordinator)->postJson('/admin/users', $payload)->assertForbidden();
        $this->actingAs($multiRole)->getJson('/admin/users')->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'baru@example.test']);
    }

    public function test_admin_payload_is_validated_in_indonesian(): void
    {
        $admin = $this->userWithRoles(['admin_it']);

        $response = $this->actingAs($admin)->postJson('/admin/users', [
            'name' => '',
            'email' => 'bukan-email',
            'password' => 'pendek',
            'roles' => ['peran_tidak_ada'],
        ]);

        $response->assertUnprocessable();
        $errors = $response->json('errors');
        $this->assertIsArray($errors);
        $this->assertSame('Nama pengguna wajib diisi.', $errors['name'][0] ?? null);
    }

    public function test_inactive_role_does_not_grant_capability(): void
    {
        $admin = $this->userWithRoles(['admin_it']);
        Role::query()->where('slug', 'admin_it')->update(['is_active' => false]);
        $admin->load('roles');

        $this->actingAs($admin)->getJson('/admin/users')->assertForbidden();
        $this->assertFalse($admin->hasRole('admin_it'));
    }

    /** @param list<string> $roleSlugs */
    private function userWithRoles(array $roleSlugs): User
    {
        $user = User::factory()->create();
        $roleIds = Role::query()->whereIn('slug', $roleSlugs)->pluck('id');
        $user->roles()->sync($roleIds);

        return $user;
    }
}
