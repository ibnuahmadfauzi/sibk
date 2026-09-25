<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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
            '/assignments/classes' => 403, '/assignments/cases' => 403,
            '/achievements' => 200, '/data-master' => 403, '/admin/users' => 403,
            '/data-master/students' => 403,
            '/waka/students-with-cases' => 403, '/waka/reports?tab=laporan-akhir' => 403,
            '/consultations' => 302, '/consultations/create' => 200,
            '/assignments/classes/manage' => 403, '/waka/reports?tab=penanganan' => 403,
        ]];
        yield 'Koordinator BK' => ['koordinator_bk', [
            '/dashboard' => 200, '/cases' => 200, '/students' => 200, '/reports' => 200,
            '/assignments/classes' => 200, '/assignments/cases' => 200,
            '/achievements' => 200, '/data-master' => 403, '/admin/users' => 403,
            '/data-master/students' => 403,
            '/waka/students-with-cases' => 403, '/waka/reports?tab=laporan-akhir' => 403,
            '/consultations' => 302, '/consultations/create' => 403,
            '/assignments/classes/manage' => 200, '/waka/reports?tab=penanganan' => 403,
        ]];
        yield 'Waka Kesiswaan' => ['waka_kesiswaan', [
            '/dashboard' => 200, '/cases' => 200, '/students' => 403, '/reports' => 200,
            '/assignments/classes' => 403, '/assignments/cases' => 403,
            '/achievements' => 403, '/consultations/create' => 403,
            '/data-master' => 403, '/admin/users' => 403,
            '/data-master/students' => 403,
            '/waka/students-with-cases' => 200, '/waka/reports?tab=laporan-akhir' => 302,
            '/consultations' => 302, '/assignments/classes/manage' => 403,
            '/waka/reports?tab=penanganan' => 302,
        ]];
        yield 'Admin IT' => ['admin_it', [
            '/dashboard' => 200, '/cases' => 403, '/students' => 403, '/reports' => 403,
            '/assignments/classes' => 403, '/assignments/cases' => 403,
            '/achievements' => 403, '/data-master' => 200, '/admin/users' => 200,
            '/data-master/students' => 200,
            '/waka/students-with-cases' => 403, '/waka/reports?tab=laporan-akhir' => 403,
            '/consultations' => 302, '/consultations/create' => 403,
            '/assignments/classes/manage' => 403, '/waka/reports?tab=penanganan' => 403,
        ]];
    }

    #[DataProvider('roleMatrix')]
    public function test_auth_01_through_auth_07_route_family_matrix(string $role, array $matrix): void
    {
        $user = $this->userWithRole($role);

        foreach ($matrix as $uri => $status) {
            $response = $this->actingAs($user)->get($uri);
            $this->assertSame(
                $status,
                $response->getStatusCode(),
                sprintf('Role %s menerima status yang salah untuk %s.', $role, $uri),
            );
            if ($uri === '/consultations') {
                $response->assertRedirect(route('cases.index', ['tab' => 'konsultasi']));
                $this->get(route('cases.index', ['tab' => 'konsultasi']))->assertStatus($matrix['/cases']);
            }
        }
    }

    public function test_guest_and_inactive_sessions_cannot_access_any_operational_family(): void
    {
        $uris = array_unique(array_merge(...array_map(static fn (array $row): array => array_keys($row[1]), iterator_to_array(self::roleMatrix(), false))));
        foreach ($uris as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }

        $inactive = $this->userWithRole('koordinator_bk');
        $inactive->update(['is_active' => false, 'deactivated_at' => now()]);
        foreach ($uris as $uri) {
            $this->actingAs($inactive)->get($uri)->assertRedirect(route('login'));
        }
    }

    public function test_retired_feature_routes_are_not_registered(): void
    {
        $user = $this->userWithRole('admin_it');

        foreach ([
            '/corrections', '/notifications', '/history',
            '/_preview/notifications', '/_preview/corrections',
            '/_preview/corrections/create', '/_preview/corrections/show',
            '/_preview/history',
        ] as $uri) {
            $this->actingAs($user)->get($uri)->assertNotFound();
        }

        $routes = collect(Route::getRoutes())->map->getName()->filter();
        $this->assertFalse($routes->contains(fn (string $name): bool => str_starts_with($name, 'corrections.')
            || str_starts_with($name, 'notifications.')
            || str_starts_with($name, 'history.')
            || str_starts_with($name, 'fixtures.notifications')
            || str_starts_with($name, 'fixtures.corrections')
            || str_starts_with($name, 'fixtures.history')
        ));
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

    public function test_admin_can_review_student_master_without_opening_service_profiles(): void
    {
        $admin = $this->userWithRole('admin_it');
        $year = AcademicYear::query()->create([
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'is_active' => false,
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'XI RPL 1',
            'is_active' => true,
            'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Persiapan',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2027-07-01',
            'is_active' => true,
            'master_source' => StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);

        $this->actingAs($admin)
            ->get(route('data-master.students.index', ['academic_year_id' => $year->id]))
            ->assertOk()
            ->assertSee('Murid Persiapan')
            ->assertSee('0012345678')
            ->assertSee('XI RPL 1')
            ->assertSee('2027/2028')
            ->assertSee('Belum Aktif')
            ->assertSee('Sementara')
            ->assertDontSee(route('students.show', $student), false);

        $this->actingAs($admin)
            ->get(route('students.show', $student))
            ->assertForbidden();
    }

    public function test_student_master_is_restricted_to_admin_it(): void
    {
        $this->get(route('data-master.students.index'))
            ->assertRedirect(route('login'));

        foreach (['guru_bk', 'koordinator_bk', 'waka_kesiswaan'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('data-master.students.index'))
                ->assertForbidden();
        }
    }

    public function test_waka_multi_role_keeps_authority_from_the_non_waka_role(): void
    {
        $user = $this->userWithRole('guru_bk');
        $user->roles()->attach(Role::query()->where('slug', 'waka_kesiswaan')->firstOrFail());

        foreach (['/cases', '/students', '/reports', '/achievements'] as $uri) {
            $this->actingAs($user)->get($uri)->assertOk();
        }
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
