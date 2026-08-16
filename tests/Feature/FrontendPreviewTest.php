<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FrontendPreviewTest extends TestCase
{
    public function test_root_redirects_to_pg_001_login(): void
    {
        $this->get('/')->assertRedirect(route('login.preview'));
    }

    #[DataProvider('authStateProvider')]
    public function test_pg_001_exposes_each_static_auth_state(string $state): void
    {
        $this->get('/login?auth_state='.$state)
            ->assertOk()
            ->assertSee('data-page-id="PG-001"', false)
            ->assertSee('data-preview-result="'.$state.'"', false)
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocomplete="current-password"', false);
    }

    public function test_pg_001_rejects_unknown_preview_state(): void
    {
        $this->get('/login?auth_state=unknown')
            ->assertOk()
            ->assertSee('data-preview-result="error"', false);
    }

    #[DataProvider('dashboardRoleProvider')]
    public function test_pg_002_renders_each_authorized_role_fixture(string $role, string $label): void
    {
        $this->get('/dashboard?role='.$role)
            ->assertOk()
            ->assertSee('data-page-id="PG-002"', false)
            ->assertSee($label)
            ->assertSee('Tahun ajaran aktif');
    }

    #[DataProvider('dashboardStateProvider')]
    public function test_pg_002_renders_each_static_state(string $state, string $expected): void
    {
        $this->get('/dashboard?role=guru&state='.$state)
            ->assertOk()
            ->assertSee('data-preview-state="'.$state.'"', false)
            ->assertSee($expected);
    }

    public function test_pg_002_waka_fixture_is_explicitly_read_only(): void
    {
        $this->get('/dashboard?role=waka')
            ->assertOk()
            ->assertSee('Tampilan koordinasi hanya-baca')
            ->assertSee('Kasus terkoordinasi')
            ->assertDontSee('Cari profil murid');
    }

    public function test_pg_002_falls_back_from_unknown_role_and_year(): void
    {
        $this->get('/dashboard?role=admin&year=invalid')
            ->assertOk()
            ->assertSee('Guru BK')
            ->assertSee('2026/2027');
    }

    public static function authStateProvider(): array
    {
        return [
            'generic credential failure' => ['error'],
            'system failure' => ['system-error'],
            'static success' => ['success'],
        ];
    }

    public static function dashboardRoleProvider(): array
    {
        return [
            'guru' => ['guru', 'Guru BK'],
            'coordinator' => ['koordinator', 'Koordinator BK'],
            'waka' => ['waka', 'Waka Kesiswaan'],
        ];
    }

    public static function dashboardStateProvider(): array
    {
        return [
            'default' => ['default', 'Ringkasan operasional'],
            'loading' => ['loading', 'Memuat ringkasan dashboard'],
            'empty' => ['empty', 'Tidak ada kasus prioritas'],
            'failure' => ['error', 'Ringkasan belum dapat dimuat'],
        ];
    }
}
