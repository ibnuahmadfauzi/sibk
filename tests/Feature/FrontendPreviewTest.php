<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FrontendPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_root_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_pg_001_renders_real_login_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('data-page-id="PG-001"', false)
            ->assertSee('name="email"', false)
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('action="'.route('login.store').'"', false);
    }

    #[DataProvider('dashboardRoleProvider')]
    public function test_pg_002_renders_fixture_for_authenticated_role(string $role, string $expected): void
    {
        $this->authenticateAs($role);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('data-page-id="PG-002"', false)
            ->assertSee($expected);
    }

    #[DataProvider('dashboardStateProvider')]
    public function test_pg_002_renders_each_static_state(string $state, string $expected): void
    {
        $this->authenticateAs('guru_bk');

        $this->get('/dashboard?state='.$state)
            ->assertOk()
            ->assertSee('data-preview-state="'.$state.'"', false)
            ->assertSee($expected);
    }

    public function test_pg_002_waka_fixture_is_explicitly_read_only(): void
    {
        $this->authenticateAs('waka_kesiswaan');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Tampilan koordinasi hanya-baca')
            ->assertSee('Kasus terkoordinasi')
            ->assertDontSee('Cari profil murid');
    }

    private function authenticateAs(string $roleSlug): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user->roles()->attach($role);
        $this->actingAs($user);

        return $user;
    }

    /** @return array<string, array{string, string}> */
    public static function dashboardRoleProvider(): array
    {
        return [
            'guru' => ['guru_bk', 'Konsultasi lanjutan'],
            'coordinator' => ['koordinator_bk', 'Review pembagian kasus'],
            'waka' => ['waka_kesiswaan', 'Tampilan koordinasi hanya-baca'],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function dashboardStateProvider(): array
    {
        return [
            'default' => ['default', 'Konsultasi lanjutan'],
            'loading' => ['loading', 'Memuat ringkasan dashboard'],
            'empty' => ['empty', 'Tidak ada tindak lanjut'],
            'failure' => ['error', 'Ringkasan belum dapat dimuat'],
        ];
    }
}
