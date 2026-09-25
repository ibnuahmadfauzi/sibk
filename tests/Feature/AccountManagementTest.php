<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\TemporaryPasswordResult;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            'roles' => ['guru_bk', 'koordinator_bk'],
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'guru.rangkap@example.test')
            ->assertJsonStructure(['temporary_password', 'expires_at'])
            ->assertJsonMissingPath('data.password');

        $created = User::query()->where('email', 'guru.rangkap@example.test')->firstOrFail();
        $this->assertTrue($created->hasRole('guru_bk'));
        $this->assertTrue($created->hasRole('koordinator_bk'));
        $this->assertFalse($created->hasRole('admin_it'));
        $this->assertTrue($created->must_change_password);
        $this->assertTrue(Hash::check((string) $response->json('temporary_password'), $created->password));
        $this->assertSame('no-store, private', $response->headers->get('cache-control'));
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'account.created',
            'auditable_id' => $created->id,
        ]);
    }

    public function test_admin_creates_account_with_one_time_temporary_password(): void
    {
        $admin = $this->userWithRoles(['admin_it']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Guru Baru',
            'email' => 'guru.baru@example.test',
            'roles' => ['guru_bk'],
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));
        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertOk()
            ->assertHeader('cache-control', 'no-store, private')
            ->assertSee('Sandi sementara');
        $user = User::query()->where('email', 'guru.baru@example.test')->firstOrFail();
        $response->assertViewIs('pages.admin.users.index');
        $result = $response->viewData('temporaryPasswordResult');
        $this->assertInstanceOf(TemporaryPasswordResult::class, $result);
        $this->assertTrue($user->must_change_password);
        $this->assertNotNull($user->temporary_password_expires_at);
        $this->assertArrayNotHasKey('password', $user->toArray());

        $auditJson = DB::table('audit_logs')->where('auditable_id', $user->id)->get()->toJson();
        $this->assertStringNotContainsString($user->password, $auditJson);
        $this->assertStringNotContainsString($result->plainTextPassword, $auditJson);
        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertDontSee($result->plainTextPassword);
    }

    public function test_admin_resets_another_account_password_and_revokes_its_sessions(): void
    {
        $admin = $this->userWithRoles(['admin_it']);
        $target = $this->userWithRoles(['guru_bk']);
        DB::table('sessions')->insert([
            ['id' => 'target-session', 'user_id' => $target->id, 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'admin-session', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.users.reset-password', $target));

        $response->assertOk()
            ->assertHeader('cache-control', 'no-store, private')
            ->assertJsonStructure(['temporary_password', 'expires_at'])
            ->assertJsonMissingPath('data.password');
        $this->assertTrue($target->refresh()->must_change_password);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'admin-session']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account.temporary_password_issued',
            'auditable_id' => $target->id,
            'actor_id' => $admin->id,
        ]);

        $this->actingAs($admin)->postJson(route('admin.users.reset-password', $admin))->assertForbidden();
    }

    public function test_reset_password_appears_once_in_the_target_account_row(): void
    {
        $admin = $this->userWithRoles(['admin_it']);
        $target = $this->userWithRoles(['guru_bk']);

        $this->actingAs($admin)
            ->post(route('admin.users.reset-password', $target))
            ->assertRedirect(route('admin.users.index'));
        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertOk()
            ->assertViewIs('pages.admin.users.index')
            ->assertHeader('cache-control', 'no-store, private');

        $result = $response->viewData('temporaryPasswordResult');
        $this->assertInstanceOf(TemporaryPasswordResult::class, $result);
        $response->assertSee($result->plainTextPassword);
        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertDontSee($result->plainTextPassword);
    }

    public function test_browser_reset_uses_an_encrypted_one_request_result(): void
    {
        $admin = $this->userWithRoles(['admin_it']);
        $target = $this->userWithRoles(['guru_bk']);

        $this->actingAs($admin)
            ->post(route('admin.users.reset-password', $target))
            ->assertRedirect(route('admin.users.index'));

        $encrypted = session('account_password_result');
        $this->assertIsString($encrypted);
        $payload = Crypt::decrypt($encrypted);
        $this->assertSame($target->id, $payload['user_id']);
        $this->assertTrue(Hash::check($payload['value'], $target->fresh()->password));
        $this->assertStringNotContainsString($payload['value'], $encrypted);
    }

    public function test_update_account_ignores_forged_password_fields(): void
    {
        $admin = $this->userWithRoles(['admin_it']);
        $target = $this->userWithRoles(['guru_bk']);
        $originalHash = $target->password;

        $this->actingAs($admin)->patchJson(route('admin.users.update', $target), [
            'name' => 'Nama Diperbarui',
            'password' => 'Forged123',
            'password_confirmation' => 'Forged123',
        ])->assertOk();

        $this->assertSame($originalHash, $target->refresh()->password);
    }

    public function test_quick_role_change_replaces_only_the_target_roles_and_rejects_empty_selection(): void
    {
        $admin = $this->userWithRoles(['admin_it']);
        $target = $this->userWithRoles(['guru_bk']);
        $other = $this->userWithRoles(['guru_bk']);

        $this->actingAs($admin)->patchJson(route('admin.users.update', $target), [
            'roles' => ['koordinator_bk', 'guru_bk'],
        ])->assertOk();
        $this->assertTrue($target->fresh()->hasRole('koordinator_bk'));
        $this->assertTrue($other->fresh()->hasRole('guru_bk'));

        $this->actingAs($admin)->patchJson(route('admin.users.update', $target), [
            'roles' => [],
        ])->assertUnprocessable();
        $this->assertTrue($target->fresh()->hasRole('koordinator_bk'));
    }

    public function test_exclusive_roles_cannot_be_combined_and_own_roles_cannot_be_changed(): void
    {
        $admin = $this->userWithRoles(['admin_it']);
        $target = $this->userWithRoles(['guru_bk']);

        foreach (['admin_it', 'waka_kesiswaan'] as $exclusive) {
            $this->actingAs($admin)->postJson(route('admin.users.store'), [
                'name' => 'Peran Ganda',
                'email' => $exclusive.'@example.test',
                'roles' => [$exclusive, 'guru_bk'],
            ])->assertUnprocessable()->assertJsonValidationErrors('roles');

            $this->actingAs($admin)->patchJson(route('admin.users.update', $target), [
                'roles' => [$exclusive, 'koordinator_bk'],
            ])->assertUnprocessable()->assertJsonValidationErrors('roles');
        }

        $this->actingAs($admin)->patchJson(route('admin.users.update', $admin), [
            'roles' => ['guru_bk'],
        ])->assertUnprocessable()->assertJsonValidationErrors('roles');
        $this->actingAs($admin)->patchJson(route('admin.users.update', $admin), [
            'is_active' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('is_active');
        $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'is_active' => '0',
        ])->assertSessionHasErrors('is_active');
        $this->assertTrue($admin->fresh()->hasRole('admin_it'));
        $this->assertTrue($target->fresh()->hasRole('guru_bk'));

        $this->actingAs($admin)->patchJson(route('admin.users.update', $admin), [
            'name' => 'Admin Baru',
        ])->assertOk();
        $this->assertSame('Admin Baru', $admin->fresh()->name);
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
            ->assertSee('Tambah akun')
            ->assertSee('Reset sandi')
            ->assertSee('accountRolesModal')
            ->assertSee('role="switch"', false)
            ->assertSee('Status akun '.$target->name)
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
