<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login_and_logout(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard.preview'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()?->last_login_at);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'auth.login',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected_without_disclosing_account_state(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('credentials');

        $this->assertGuest();
        $this->assertSame(0, AuditLog::query()->count());
    }

    public function test_invalid_credentials_are_rendered_and_described_by_login_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->followingRedirects()
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ]);

        $response
            ->assertOk()
            ->assertSee('id="credentialsError"', false)
            ->assertSee(
                'aria-describedby="identifierError credentialsError"',
                false,
            )
            ->assertSee(
                'aria-describedby="passwordError credentialsError"',
                false,
            );
    }

    public function test_inactive_user_cannot_login_or_keep_an_existing_session(): void
    {
        $user = User::factory()->inactive()->create();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('credentials');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_operational_routes_require_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/cases')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
    }

    public function test_temporary_account_is_forced_to_change_password(): void
    {
        $user = User::factory()->create([
            'password' => 'Sementara123',
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addHour(),
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Sementara123',
        ])->assertRedirect(route('account.password.edit'));

        $this->get(route('dashboard.preview'))->assertRedirect(route('account.password.edit'));
        $this->get(route('account.password.edit'))->assertOk()->assertSee('Ganti Kata Sandi');
    }

    public function test_expired_temporary_password_is_rejected_after_authentication(): void
    {
        $user = User::factory()->create([
            'password' => 'Kedaluwarsa123',
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->subMinute(),
        ]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Kedaluwarsa123',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('credentials');

        $this->assertGuest();
        $this->assertSame(0, AuditLog::query()->where('action', 'auth.login')->count());
    }

    public function test_expired_temporary_password_cannot_be_changed_from_an_existing_session(): void
    {
        $user = User::factory()->create([
            'password' => 'Kedaluwarsa123',
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)->patch(route('account.password.update'), [
            'current_password' => 'Kedaluwarsa123',
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])->assertSessionHasErrors('current_password');

        $user->refresh();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('Kedaluwarsa123', $user->password));
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'account.password_changed',
            'auditable_id' => $user->id,
        ]);
    }

    public function test_user_changes_temporary_password_and_unlocks_operational_routes(): void
    {
        $user = User::factory()->create([
            'password' => 'Sementara123',
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addHour(),
        ]);

        $this->actingAs($user)->patch(route('account.password.update'), [
            'current_password' => 'Sementara123',
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])->assertRedirect(route('dashboard.preview'));

        $user->refresh();
        $this->assertTrue(Hash::check('PasswordBaru123', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertNull($user->temporary_password_expires_at);
        $this->assertNotNull($user->password_changed_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account.password_changed',
            'auditable_id' => $user->id,
        ]);
        $this->get(route('dashboard.preview'))->assertOk();

        $auditJson = AuditLog::query()->where('auditable_id', $user->id)->get()->toJson();
        $this->assertStringNotContainsString('Sementara123', $auditJson);
        $this->assertStringNotContainsString('PasswordBaru123', $auditJson);
    }

    public function test_new_password_requires_confirmation_letters_and_numbers(): void
    {
        $user = User::factory()->create([
            'password' => 'Sementara123',
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addHour(),
        ]);

        $this->actingAs($user)->patch(route('account.password.update'), [
            'current_password' => 'Sementara123',
            'password' => 'tanpaangka',
            'password_confirmation' => 'berbeda123',
        ])->assertSessionHasErrors('password');

        $this->assertTrue($user->refresh()->must_change_password);
    }

    public function test_new_password_must_differ_from_temporary_password(): void
    {
        $user = User::factory()->create([
            'password' => 'Sementara123',
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addHour(),
        ]);

        $this->actingAs($user)->patch(route('account.password.update'), [
            'current_password' => 'Sementara123',
            'password' => 'Sementara123',
            'password_confirmation' => 'Sementara123',
        ])->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('Sementara123', $user->password));
    }
}
