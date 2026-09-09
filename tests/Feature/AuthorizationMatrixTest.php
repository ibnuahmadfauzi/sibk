<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    /** @return iterable<string, array{string, array<string, int>}> */
    public static function roleMatrix(): iterable
    {
        yield 'Guru BK' => ['guru_bk', [
            '/dashboard' => 200, '/cases' => 200, '/students' => 200, '/reports' => 200,
            '/assignments/classes' => 403, '/assignments/cases' => 403, '/corrections' => 200,
            '/history' => 200, '/achievements' => 200, '/data-master' => 403, '/admin/users' => 403,
        ]];
        yield 'Koordinator BK' => ['koordinator_bk', [
            '/dashboard' => 200, '/cases' => 200, '/students' => 200, '/reports' => 200,
            '/assignments/classes' => 200, '/assignments/cases' => 200, '/corrections' => 200,
            '/history' => 200, '/achievements' => 200, '/data-master' => 403, '/admin/users' => 403,
        ]];
        yield 'Waka Kesiswaan' => ['waka_kesiswaan', [
            '/dashboard' => 200, '/cases' => 200, '/students' => 200, '/reports' => 200,
            '/assignments/classes' => 403, '/assignments/cases' => 403, '/corrections' => 403,
            '/history' => 200, '/achievements' => 200, '/consultations/create' => 403,
            '/data-master' => 403, '/admin/users' => 403,
        ]];
        yield 'Admin IT' => ['admin_it', [
            '/dashboard' => 200, '/cases' => 403, '/students' => 403, '/reports' => 403,
            '/assignments/classes' => 403, '/assignments/cases' => 403, '/corrections' => 200,
            '/history' => 200, '/achievements' => 403, '/data-master' => 200, '/admin/users' => 200,
        ]];
    }

    #[DataProvider('roleMatrix')]
    public function test_auth_01_through_auth_07_route_family_matrix(string $role, array $matrix): void
    {
        $user = $this->userWithRole($role);

        foreach ($matrix as $uri => $status) {
            $this->actingAs($user)->get($uri)->assertStatus($status);
        }
    }

    public function test_guest_and_inactive_sessions_cannot_access_any_operational_family(): void
    {
        $uris = ['/dashboard', '/cases', '/students', '/reports', '/assignments/classes', '/corrections', '/history', '/achievements', '/data-master', '/admin/users'];
        foreach ($uris as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }

        $inactive = $this->userWithRole('koordinator_bk');
        $inactive->update(['is_active' => false, 'deactivated_at' => now()]);
        foreach ($uris as $uri) {
            $this->actingAs($inactive)->get($uri)->assertRedirect(route('login'));
        }
    }

    public function test_admin_sidebar_never_advertises_service_capabilities(): void
    {
        $admin = $this->userWithRole('admin_it');

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertDontSee('Layanan BK')
            ->assertDontSee('Data Murid')
            ->assertDontSee('Laporan')
            ->assertDontSee('Penugasan Kelas')
            ->assertDontSee('Pengalihan Kasus')
            ->assertSee('Data Master');
    }

    public function test_preparation_and_activation_controls_are_visible_only_to_the_responsible_roles(): void
    {
        $year = AcademicYear::query()->create([
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'is_active' => false,
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $admin = $this->userWithRole('admin_it');
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $waka = $this->userWithRole('waka_kesiswaan');

        $this->actingAs($admin)->get(route('data-master.index'))
            ->assertOk()
            ->assertSee('Persiapan Tahun Ajaran');
        $this->actingAs($coordinator)->get(route('assignments.classes.manage', ['academic_year_id' => $year->id]))
            ->assertOk()
            ->assertSee('Kesiapan Aktivasi');
        $this->actingAs($teacher)->get(route('assignments.classes.manage', ['academic_year_id' => $year->id]))
            ->assertForbidden();
        $this->actingAs($waka)->get(route('assignments.classes.manage', ['academic_year_id' => $year->id]))
            ->assertForbidden();
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
