<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Integrations\Etatib\EtatibConnector;
use App\Integrations\Etatib\EtatibSnapshot;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\EtatibSyncService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtatibSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_full_and_partial_sync_are_idempotent_and_only_full_deactivates_missing_records(): void
    {
        $admin = $this->userWithRole('admin_it');
        Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Resmi', 'is_active' => true]);
        $old = ExternalTatibRecord::query()->create([
            'source_identifier' => 'old', 'nisn' => '0000000001', 'occurred_at' => now(),
            'violation_type' => 'Data lama', 'category' => 'Lama', 'points' => 1, 'is_active' => true, 'synced_at' => now(),
        ]);

        $this->fakeConnector($this->snapshot(false));
        app(EtatibSyncService::class)->synchronize($admin);
        $this->assertTrue($old->refresh()->is_active);

        $this->fakeConnector($this->snapshot(true));
        $first = app(EtatibSyncService::class)->synchronize($admin);
        $second = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $first->status);
        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $second->status);
        $this->assertFalse($old->refresh()->is_active);
        $this->assertDatabaseCount('external_tatib_records', 2);
        $this->assertDatabaseHas('external_tatib_records', ['source_identifier' => 'tatib-1', 'student_id' => Student::query()->firstOrFail()->id, 'is_active' => true]);
    }

    public function test_duplicate_source_and_identity_mismatch_do_not_overwrite_valid_record(): void
    {
        $admin = $this->userWithRole('admin_it');
        $existing = ExternalTatibRecord::query()->create([
            'source_identifier' => 'tatib-1', 'nisn' => '0012345678', 'occurred_at' => '2026-08-01 08:00:00',
            'violation_type' => 'Data sah', 'category' => 'Disiplin', 'points' => 5, 'is_active' => true, 'synced_at' => now(),
        ]);
        $this->fakeConnector(new EtatibSnapshot(false, [[
            'source_id' => 'tatib-1', 'nisn' => '0099999999', 'occurred_at' => '2026-08-19 08:00:00',
            'violation_type' => 'Data konflik', 'category' => 'Konflik', 'points' => 99,
        ]]));

        $run = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_WARNING, $run->status);
        $this->assertSame('Data sah', $existing->refresh()->violation_type);
        $this->assertSame(5, $existing->points);
        $this->assertDatabaseHas('external_sync_issues', ['issue_code' => 'source_identity_mismatch', 'source_identifier' => 'tatib-1']);
    }

    public function test_unconfigured_connector_fails_safely_and_endpoint_is_admin_only(): void
    {
        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');

        $this->actingAs($teacher)->post(route('data-master.etatib.sync'))->assertForbidden();
        $this->actingAs($admin)->post(route('data-master.etatib.sync'))->assertSessionHasErrors('etatib_sync');
        $this->assertDatabaseHas('external_sync_runs', ['source' => 'etatib', 'status' => ExternalSyncRun::STATUS_FAILED]);
    }

    private function fakeConnector(EtatibSnapshot $snapshot): void
    {
        $this->app->instance(EtatibConnector::class, new class($snapshot) implements EtatibConnector
        {
            public function __construct(private readonly EtatibSnapshot $snapshot) {}

            public function fetchSnapshot(): EtatibSnapshot
            {
                return $this->snapshot;
            }
        });
    }

    private function snapshot(bool $isFull): EtatibSnapshot
    {
        return new EtatibSnapshot($isFull, [[
            'source_id' => 'tatib-1',
            'nisn' => '0012345678',
            'occurred_at' => '2026-08-18 09:00:00',
            'violation_type' => 'Terlambat',
            'category' => 'Kedisiplinan',
            'points' => 5,
            'source_status' => 'Terverifikasi',
            'source_synced_at' => '2026-08-20 08:00:00',
        ]]);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
