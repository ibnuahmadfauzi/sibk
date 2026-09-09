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
            'guru' => ['guru_bk', 'Murid dalam cakupan'],
            'coordinator' => ['koordinator_bk', 'Rekap tata kelola'],
            'waka' => ['waka_kesiswaan', 'Tampilan koordinasi hanya-baca'],
        ];
    }
}
