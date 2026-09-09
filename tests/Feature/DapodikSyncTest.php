<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Integrations\Dapodik\DapodikConnector;
use App\Integrations\Dapodik\DapodikSnapshot;
use App\Models\ExternalSyncRun;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\DapodikSyncService;
use App\Services\StudentIdentityService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DapodikSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_full_snapshot_imports_master_data_and_is_idempotent(): void
    {
        $admin = $this->userWithRole('admin_it');
        $this->fakeConnector($this->snapshot());

        $first = app(DapodikSyncService::class)->synchronize($admin);
        $second = app(DapodikSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $first->status);
        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $second->status);
        $this->assertDatabaseCount('academic_years', 1);
        $this->assertDatabaseCount('classrooms', 1);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('student_class_memberships', 1);
        $this->assertDatabaseHas('students', ['nisn' => '0012345678', 'name' => 'Nama Resmi', 'is_active' => true]);
    }

    public function test_partial_snapshot_does_not_deactivate_missing_data_but_full_snapshot_does(): void
    {
        $admin = $this->userWithRole('admin_it');
        $existing = Student::query()->create([
            'dapodik_id' => 'student-old',
            'nisn' => '0000000001',
            'name' => 'Murid Lama',
            'is_active' => true,
        ]);

        $partial = $this->snapshot(isFull: false);
        $this->fakeConnector($partial);
        app(DapodikSyncService::class)->synchronize($admin);
        $this->assertTrue($existing->refresh()->is_active);

        $this->fakeConnector($this->snapshot(isFull: true));
        app(DapodikSyncService::class)->synchronize($admin);
        $this->assertFalse($existing->refresh()->is_active);
    }

    public function test_duplicate_nisn_is_held_without_overwriting_valid_student(): void
    {
        $admin = $this->userWithRole('admin_it');
        $existing = Student::query()->create([
            'dapodik_id' => 'student-1',
            'nisn' => '0012345678',
            'name' => 'Nama Sah',
            'is_active' => true,
        ]);
        $snapshot = new DapodikSnapshot(
            isFullSnapshot: false,
            academicYears: [],
            classrooms: [],
            students: [
                ['source_id' => 'student-1', 'nisn' => '0012345678', 'name' => 'Nama Berubah'],
                ['source_id' => 'student-2', 'nisn' => '0012345678', 'name' => 'Nama Duplikat'],
            ],
            memberships: [],
        );
        $this->fakeConnector($snapshot);

        $run = app(DapodikSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_WARNING, $run->status);
        $this->assertSame('Nama Sah', $existing->refresh()->name);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('external_sync_issues', 2);
    }

    public function test_temporary_identity_reconciles_by_nisn_and_keeps_input_name_in_history(): void
    {
        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');
        $temporary = app(StudentIdentityService::class)
            ->createTemporary('0012345678', 'Nama Masukan Awal', $teacher);
        $this->fakeConnector($this->snapshot());

        app(DapodikSyncService::class)->synchronize($admin);

        $temporary->refresh();
        $this->assertNotNull($temporary->reconciled_student_id);
        $this->assertSame('Nama Masukan Awal', $temporary->input_name);
        $this->assertSame(
            'terekonsiliasi',
            ReferenceValue::query()->findOrFail($temporary->reconciliation_status_id)->code,
        );
        $this->assertDatabaseHas('identity_reconciliations', [
            'temporary_student_id' => $temporary->id,
            'official_name' => 'Nama Resmi',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student_identity.reconciled']);
    }

    public function test_unavailable_connector_is_reported_safely_and_data_master_is_admin_only(): void
    {
        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');

        $this->actingAs($teacher)
            ->get(route('data-master.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('data-master.index'))
            ->assertOk()
            ->assertSee('Belum ada data');

        $this->actingAs($admin)
            ->post(route('data-master.dapodik.sync'))
            ->assertSessionHasErrors('sync');

        $this->assertDatabaseHas('external_sync_runs', [
            'source' => 'dapodik',
            'status' => ExternalSyncRun::STATUS_FAILED,
        ]);
    }

    private function fakeConnector(DapodikSnapshot $snapshot): void
    {
        $this->app->instance(DapodikConnector::class, new class($snapshot) implements DapodikConnector
        {
            public function __construct(private readonly DapodikSnapshot $snapshot) {}

            public function fetchSnapshot(): DapodikSnapshot
            {
                return $this->snapshot;
            }
        });
    }

    private function snapshot(bool $isFull = true): DapodikSnapshot
    {
        return new DapodikSnapshot(
            isFullSnapshot: $isFull,
            academicYears: [[
                'source_id' => 'year-2026',
                'name' => '2026/2027',
                'starts_on' => '2026-07-01',
                'ends_on' => '2027-06-30',
                'is_active' => true,
            ]],
            classrooms: [[
                'source_id' => 'class-1',
                'academic_year_source_id' => 'year-2026',
                'name' => 'X RPL 1',
                'grade_level' => 10,
                'major' => 'RPL',
                'is_active' => true,
            ]],
            students: [[
                'source_id' => 'student-1',
                'nisn' => '0012345678',
                'name' => 'Nama Resmi',
                'is_active' => true,
            ]],
            memberships: [[
                'source_id' => 'membership-1',
                'student_source_id' => 'student-1',
                'classroom_source_id' => 'class-1',
                'academic_year_source_id' => 'year-2026',
                'effective_from' => '2026-07-15',
                'effective_until' => null,
                'is_active' => true,
            ]],
        );
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
