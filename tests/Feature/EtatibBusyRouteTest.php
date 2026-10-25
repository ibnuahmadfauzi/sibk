<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class EtatibBusyRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_busy_mapping_returns_to_conflicts_without_changing_record(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin_it')->firstOrFail());
        $student = Student::query()->create(['nisn' => '0011111111', 'name' => 'Murid Master']);
        $run = ExternalSyncRun::query()->create(['source' => 'etatib', 'status' => ExternalSyncRun::STATUS_WARNING, 'started_at' => now()]);
        $record = ExternalTatibRecord::query()->create([
            'source_identifier' => 'etatib-busy-1', 'nisn' => '0093200788',
            'source_student_name' => 'Murid API', 'source_status' => 'active',
            'violation_type' => 'Terlambat', 'category' => 'ringan', 'points' => 5,
            'is_active' => true, 'synced_at' => now(),
        ]);
        $issue = ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->getKey(), 'entity_type' => 'etatib_record',
            'source_identifier' => $record->source_identifier, 'nisn' => $record->nisn,
            'input_name' => 'Murid API', 'issue_code' => 'student_not_found', 'summary' => 'Perlu ditinjau.',
        ]);

        $lock = Cache::lock('sibk:integration:etatib:operation', 60);
        $this->assertTrue($lock->get());
        try {
            $this->actingAs($admin)->post(route('data-master.etatib.mappings.store', $issue), [
                'student_id' => $student->getKey(), 'confirmed' => '1',
            ])->assertRedirect(route('data-master.etatib.conflicts.index'))
                ->assertSessionHasErrors('etatib_mapping');
            $this->postJson(route('data-master.etatib.mappings.store', $issue), [
                'student_id' => $student->getKey(), 'confirmed' => '1',
            ])->assertStatus(409)->assertJsonPath('message', 'Sinkronisasi e-Tatib sedang berjalan. Coba lagi setelah selesai.')
                ->assertHeader('Cache-Control', 'no-store, private');
        } finally {
            $lock->release();
        }

        $this->assertNull($record->refresh()->student_id);
        $this->assertDatabaseCount('etatib_identity_mappings', 0);
    }
}
