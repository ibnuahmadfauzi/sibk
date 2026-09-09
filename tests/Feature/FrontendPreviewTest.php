<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
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
            ->assertSee('id="identifierError"', false)
            ->assertSee('id="passwordError"', false)
            ->assertSee('action="'.route('login.store').'"', false);
    }

    #[DataProvider('dashboardRoleProvider')]
    public function test_pg_002_renders_database_dashboard_for_authenticated_role(string $role, string $expected): void
    {
        $this->authenticateAs($role);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('data-page-id="PG-002"', false)
            ->assertSee($expected);
    }

    public function test_pg_002_waka_dashboard_is_explicitly_read_only(): void
    {
        $this->authenticateAs('waka_kesiswaan');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Tampilan koordinasi hanya-baca')
            ->assertSee('Kasus terkoordinasi')
            ->assertDontSee('Cari profil murid');
    }

    public function test_academic_year_preparation_pages_follow_the_existing_panel_hierarchy(): void
    {
        $year = AcademicYear::query()->create([
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'is_active' => false,
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $admin = $this->authenticateAs('admin_it');
        $this->get(route('data-master.index'))
            ->assertOk()
            ->assertSee('Persiapan Tahun Ajaran')
            ->assertSee('Buat Tahun Ajaran Sementara')
            ->assertSee('name="name"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('nisn,nama,rombel');

        $admin->roles()->detach();
        $admin->roles()->attach(Role::query()->where('slug', 'koordinator_bk')->firstOrFail());

        $this->get(route('assignments.classes.manage', ['academic_year_id' => $year->id]))
            ->assertOk()
            ->assertSee('Kesiapan Aktivasi')
            ->assertSee('Sementara')
            ->assertDontSee('Aktifkan Tahun Ajaran');
    }

    public function test_pg_501_integration_settings_follow_existing_panels_and_plain_language_workflow(): void
    {
        $this->authenticateAs('admin_it');

        $this->get(route('data-master.index'))
            ->assertOk()
            ->assertSee('Pengaturan Koneksi Sumber Data')
            ->assertSee('class="col-12 col-xl-6"', false)
            ->assertSee('data-integration-panel="dapodik"', false)
            ->assertSee('data-integration-panel="etatib"', false)
            ->assertSeeInOrder([
                'Ringkasan koneksi',
                'Konfigurasi',
                'Simpan Pengaturan',
                'Uji Koneksi',
                'Aktifkan',
                'Keadaan data terakhir',
            ])
            ->assertSee('Adapter belum tersedia')
            ->assertSee('Sinkronisasi baru dapat digunakan setelah adapter resmi tersedia dan koneksi berhasil diaktifkan.')
            ->assertDontSee('modal')
            ->assertDontSee('data-bs-toggle="collapse"', false);
    }

    public function test_small_danger_badge_uses_a_contrast_safe_token(): void
    {
        $tokenSource = file_get_contents(resource_path('scss/_token-values.scss'));
        $dashboardSource = file_get_contents(resource_path('scss/app-dashboard.scss'));

        $this->assertIsString($tokenSource);
        $this->assertIsString($dashboardSource);
        $this->assertMatchesRegularExpression('/\$sibk-danger-strong:\s*(#[0-9A-Fa-f]{6});/', $tokenSource);
        $this->assertMatchesRegularExpression('/\$sibk-danger-soft:\s*(#[0-9A-Fa-f]{6});/', $tokenSource);
        preg_match('/\$sibk-danger-strong:\s*(#[0-9A-Fa-f]{6});/', $tokenSource, $foreground);
        preg_match('/\$sibk-danger-soft:\s*(#[0-9A-Fa-f]{6});/', $tokenSource, $background);

        $this->assertGreaterThanOrEqual(4.5, $this->contrastRatio($foreground[1], $background[1]));
        $this->assertStringContainsString('color: var(--sibk-color-danger-strong);', $dashboardSource);
    }

    private function authenticateAs(string $roleSlug): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user->roles()->attach($role);
        $this->actingAs($user);

        return $user;
    }

    private function contrastRatio(string $foreground, string $background): float
    {
        $foregroundLuminance = $this->relativeLuminance($foreground);
        $backgroundLuminance = $this->relativeLuminance($background);

        return (max($foregroundLuminance, $backgroundLuminance) + 0.05)
            / (min($foregroundLuminance, $backgroundLuminance) + 0.05);
    }

    private function relativeLuminance(string $hex): float
    {
        $channels = array_map(
            static function (string $channel): float {
                $value = hexdec($channel) / 255;

                return $value <= 0.04045
                    ? $value / 12.92
                    : (($value + 0.055) / 1.055) ** 2.4;
            },
            str_split(substr($hex, 1), 2),
        );

        return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    }

    /** @return array<string, array{string, string}> */
    public static function dashboardRoleProvider(): array
    {
        return [
            'guru' => ['guru_bk', 'Murid dalam cakupan'],
            'coordinator' => ['koordinator_bk', 'Rekap tata kelola'],
            'waka' => ['waka_kesiswaan', 'Tampilan koordinasi hanya-baca'],
        ];
    }
}
