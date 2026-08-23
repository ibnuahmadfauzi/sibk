<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SprintNineHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_legacy_preview_routes_are_get_only_redirects_without_fixture_payload(): void
    {
        $user = $this->userWithRole('guru_bk');

        $this->actingAs($user)->get('/_preview/cases?tab=konsultasi')
            ->assertRedirect(route('cases.index', ['tab' => 'konsultasi']));
        $this->actingAs($user)->post('/_preview/cases')->assertMethodNotAllowed();
        $this->assertStringNotContainsString('Murid A', file_get_contents(base_path('routes/web.php')) ?: '');
    }

    public function test_routes_are_cacheable_controller_actions_and_private_disk_is_not_served(): void
    {
        $domainRoutes = collect(Route::getRoutes()->getRoutes())
            ->reject(fn (IlluminateRoute $route): bool => str_starts_with($route->uri(), '_ignition') || $route->uri() === 'up');

        $this->assertFalse($domainRoutes->contains(
            fn (IlluminateRoute $route): bool => $route->getAction('uses') instanceof \Closure,
        ));
        $this->assertFalse((bool) config('filesystems.disks.local.serve'));
        $this->assertFalse($domainRoutes->contains(fn (IlluminateRoute $route): bool => $route->uri() === 'storage/{path}'));
    }

    public function test_account_page_uses_authenticated_database_values_and_capability_navigation(): void
    {
        AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $admin = $this->userWithRole('admin_it', 'Admin Nyata');
        $admin->update(['last_login_at' => '2026-08-20 08:30:00']);

        $this->actingAs($admin)->get(route('account.index'))
            ->assertOk()
            ->assertSee('Admin Nyata')
            ->assertSee($admin->email)
            ->assertSee('Admin IT')
            ->assertSee('2026/2027')
            ->assertDontSee('Ubah Kata Sandi')
            ->assertDontSee('Layanan BK')
            ->assertDontSee('Data Murid')
            ->assertSee('Data Master');
    }

    public function test_production_database_seeder_never_creates_demo_accounts(): void
    {
        $originalEnvironment = app()->environment();
        app()->detectEnvironment(static fn (): string => 'production');
        try {
            (new DatabaseSeeder)->setContainer(app())->run();
        } finally {
            app()->detectEnvironment(static fn (): string => $originalEnvironment);
        }

        $this->assertDatabaseMissing('users', ['email' => 'admin.it@ruangbk.test']);
        $this->assertDatabaseMissing('users', ['email' => 'rbac.admin@ruangbk.test']);
        $this->assertDatabaseHas('roles', ['slug' => 'admin_it']);
        $this->assertDatabaseHas('references', ['category' => 'case_status']);
    }

    public function test_scoped_bindings_reject_follow_up_from_another_case(): void
    {
        $coordinator = $this->userWithRole('koordinator_bk');
        $creator = $this->userWithRole('guru_bk');
        $caseA = $this->case($creator, 'K-2026-9001');
        $caseB = $this->case($creator, 'K-2026-9002');
        $followUp = FollowUp::query()->create([
            'case_id' => $caseB->id,
            'follow_up_type_id' => $this->reference('follow_up_type', 'konsultasi_individual')->id,
            'status_id' => $this->reference('follow_up_status', 'terjadwal')->id,
            'planned_date' => now()->toDateString(),
            'recorded_by' => $creator->id,
        ]);

        $this->actingAs($coordinator)->get(route('cases.follow-ups.edit', [$caseA, $followUp]))->assertNotFound();
    }

    public function test_every_mutation_route_uses_web_csrf_middleware(): void
    {
        $mutationRoutes = collect(Route::getRoutes()->getRoutes())->filter(function (IlluminateRoute $route): bool {
            return collect($route->methods())->intersect(['POST', 'PUT', 'PATCH', 'DELETE'])->isNotEmpty();
        });

        $this->assertNotEmpty($mutationRoutes);
        foreach ($mutationRoutes as $route) {
            $this->assertContains('web', $route->gatherMiddleware(), $route->uri().' tidak memakai grup middleware web.');
        }
    }

    public function test_audit_and_soft_deleted_operational_history_survive_beyond_three_years(): void
    {
        $creator = $this->userWithRole('guru_bk');
        $case = $this->case($creator, 'K-2022-9001');
        $case->forceFill(['created_at' => now()->subYears(4), 'updated_at' => now()->subYears(4)])->saveQuietly();
        $case->delete();
        $audit = AuditLog::query()->create([
            'actor_id' => $creator->id,
            'action' => 'case.retention_test',
            'auditable_type' => BkCase::class,
            'auditable_id' => $case->id,
            'summary' => 'Histori retensi.',
        ]);
        $audit->forceFill(['created_at' => now()->subYears(4)])->saveQuietly();

        $this->assertNotNull(BkCase::withTrashed()->find($case->id));
        $this->assertNotNull(AuditLog::query()->find($audit->id));
        $this->assertFileDoesNotExist(base_path('app/Console/Commands/PruneBkData.php'));
    }

    private function case(User $creator, string $number): BkCase
    {
        return BkCase::query()->create([
            'registration_number' => $number,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('case_status', 'baru')->id,
            'service_date' => now()->toDateString(),
            'initial_info' => 'Data pengujian.',
            'initial_action' => 'Asesmen awal.',
            'created_by' => $creator->id,
        ]);
    }

    private function reference(string $category, string $code): ReferenceValue
    {
        return ReferenceValue::query()->where('category', $category)->where('code', $code)->firstOrFail();
    }

    private function userWithRole(string $slug, string $name = 'Pengguna Hardening'): User
    {
        $user = User::factory()->create(['name' => $name, 'is_active' => true]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
