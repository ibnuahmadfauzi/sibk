<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Integrations\Etatib\EtatibSnapshot;
use App\Models\BkCase;
use App\Models\EtatibIdentityMapping;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\IntegrationSetting;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\EtatibSyncService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EtatibIdentityMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
        IntegrationSetting::query()->create(['provider' => IntegrationSetting::PROVIDER_ETATIB]);
    }

    public function test_admin_can_map_conflict_to_different_nisn_and_mapping_survives_next_sync(): void
    {
        $admin = $this->userWithRole('admin_it');
        $target = Student::query()->create([
            'nisn' => '0011111111',
            'name' => 'Murid Master Tujuan',
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        [$issue, $record] = $this->conflict('0093200788', 'Nama Dari e-Tatib', 'source-1');
        $this->conflict('0093200788', '  nama   dari E-tatib ', 'source-2');

        $this->actingAs($admin)->post(
            route('data-master.etatib.mappings.store', $issue),
            ['student_id' => $target->getKey(), 'confirmed' => '1'],
        )->assertRedirect(route('data-master.etatib.conflicts.index'));

        $mapping = EtatibIdentityMapping::query()->sole();
        $this->assertTrue($mapping->is_active);
        $this->assertSame($target->getKey(), $mapping->student_id);
        $this->assertSame($target->getKey(), $record->refresh()->student_id);
        $this->assertSame(
            2,
            ExternalTatibRecord::query()->where('student_id', $target->getKey())->count(),
        );
        $this->assertSame(0, ExternalSyncIssue::query()->whereNull('resolved_at')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'etatib.identity_mapped']);

        app(EtatibSyncService::class)->synchronizeSnapshot(new EtatibSnapshot(
            isFullSnapshot: true,
            records: [$this->snapshotItem('source-3', '0093200788', 'NAMA DARI E-TATIB')],
        ), $admin);

        $this->assertDatabaseHas('external_tatib_records', [
            'source_identifier' => 'source-3',
            'student_id' => $target->getKey(),
        ]);
    }

    public function test_mapping_can_be_changed_and_revoked_back_to_strict_matching(): void
    {
        $admin = $this->userWithRole('admin_it');
        $first = Student::query()->create(['nisn' => '0011111111', 'name' => 'Tujuan Pertama']);
        $second = Student::query()->create(['nisn' => '0022222222', 'name' => 'Tujuan Kedua']);
        [$issue, $record] = $this->conflict('0093200788', 'Nama Tidak Sama', 'source-change');

        $this->actingAs($admin)->post(route('data-master.etatib.mappings.store', $issue), [
            'student_id' => $first->getKey(),
            'confirmed' => '1',
        ]);
        $mapping = EtatibIdentityMapping::query()->sole();

        $this->actingAs($admin)->patch(route('data-master.etatib.mappings.update', $mapping), [
            'student_id' => $second->getKey(),
            'confirmed' => '1',
        ])->assertRedirect(route('data-master.etatib.conflicts.index', ['tab' => 'mappings']));
        $this->assertSame($second->getKey(), $record->refresh()->student_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'etatib.identity_mapping_changed']);

        $this->actingAs($admin)->delete(route('data-master.etatib.mappings.destroy', $mapping), [
            'confirmed' => '1',
        ])->assertRedirect(route('data-master.etatib.conflicts.index', ['tab' => 'mappings']));

        $this->assertNull($record->refresh()->student_id);
        $this->assertFalse($mapping->refresh()->is_active);
        $this->assertDatabaseHas('external_sync_issues', [
            'source_identifier' => 'source-change',
            'resolved_at' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'etatib.identity_mapping_revoked']);
    }

    public function test_mapping_change_and_revoke_are_blocked_after_record_is_linked_to_case(): void
    {
        $admin = $this->userWithRole('admin_it');
        $target = Student::query()->create(['nisn' => '0011111111', 'name' => 'Tujuan Pertama']);
        $other = Student::query()->create(['nisn' => '0022222222', 'name' => 'Tujuan Kedua']);
        [$issue, $record] = $this->conflict('0093200788', 'Nama Tidak Sama', 'source-linked');
        $this->actingAs($admin)->post(route('data-master.etatib.mappings.store', $issue), [
            'student_id' => $target->getKey(),
            'confirmed' => '1',
        ]);
        $mapping = EtatibIdentityMapping::query()->sole();
        $case = $this->caseFor($target, $admin);
        $case->etatibRecords()->attach($record->getKey(), ['linked_by' => $admin->getKey()]);

        $this->actingAs($admin)->patch(route('data-master.etatib.mappings.update', $mapping), [
            'student_id' => $other->getKey(),
            'confirmed' => '1',
        ])->assertSessionHasErrors('student_id');
        $this->assertSame($target->getKey(), $record->refresh()->student_id);

        $this->actingAs($admin)->delete(route('data-master.etatib.mappings.destroy', $mapping), [
            'confirmed' => '1',
        ])->assertSessionHasErrors('student_id');
        $this->assertTrue($mapping->refresh()->is_active);
    }

    public function test_conflict_page_candidates_and_mutations_are_admin_only(): void
    {
        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');
        $inactiveAdmin = $this->userWithRole('admin_it', false);
        Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Kandidat Historis',
            'is_active' => false,
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        [$issue] = $this->conflict('0093200788', 'Nama Konflik', 'source-auth');

        $this->actingAs($admin)->get(route('data-master.etatib.conflicts.index'))
            ->assertOk()
            ->assertSee('Nama Konflik')
            ->assertSee('NISN 0093200788');
        $this->actingAs($admin)->getJson(route('data-master.etatib.candidates', ['search' => 'Historis']))
            ->assertOk()
            ->assertJsonPath('data.0.nisn', '0012345678')
            ->assertJsonPath('data.0.status', 'Historis')
            ->assertJsonPath('data.0.source', 'API Siswa');

        $this->actingAs($teacher)
            ->get(route('data-master.etatib.conflicts.index'))
            ->assertForbidden();
        $this->actingAs($teacher)
            ->getJson(route('data-master.etatib.candidates', ['search' => 'Historis']))
            ->assertForbidden();
        $this->actingAs($teacher)->post(route('data-master.etatib.mappings.store', $issue), [
            'student_id' => 1,
            'confirmed' => '1',
        ])->assertForbidden();
        $this->actingAs($inactiveAdmin)
            ->get(route('data-master.etatib.conflicts.index'))
            ->assertRedirect(route('login'));
    }

    /** @return array{ExternalSyncIssue, ExternalTatibRecord} */
    private function conflict(string $nisn, string $name, string $sourceIdentifier): array
    {
        $run = ExternalSyncRun::query()->create([
            'source' => 'etatib',
            'status' => ExternalSyncRun::STATUS_WARNING,
            'started_at' => now(),
            'finished_at' => now(),
        ]);
        $record = ExternalTatibRecord::query()->create([
            'source_identifier' => $sourceIdentifier,
            'nisn' => $nisn,
            'source_nisn' => ltrim($nisn, '0'),
            'source_student_name' => $name,
            'source_classroom_name' => '12 PH 2',
            'occurred_at' => '2026-07-20 10:17:00',
            'violation_type' => 'Datang Terlambat',
            'category' => 'ringan',
            'points' => 10,
            'is_active' => true,
            'synced_at' => now(),
        ]);
        $issue = ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->getKey(),
            'entity_type' => 'etatib_record',
            'source_identifier' => $sourceIdentifier,
            'nisn' => $nisn,
            'input_name' => $name,
            'issue_code' => 'student_not_found',
            'summary' => 'NISN e-Tatib belum ditemukan pada master murid.',
        ]);

        return [$issue, $record];
    }

    /** @return array<string, int|string|null> */
    private function snapshotItem(string $sourceIdentifier, string $nisn, string $name): array
    {
        return [
            'source_id' => $sourceIdentifier,
            'nisn' => $nisn,
            'source_nisn' => ltrim($nisn, '0'),
            'source_student_name' => $name,
            'source_classroom_name' => '12 PH 2',
            'occurred_at' => '2026-07-21T10:17:00+07:00',
            'violation_type' => 'Datang Terlambat',
            'category' => 'ringan',
            'recorded_by_name' => 'ANIS',
            'points' => 10,
            'source_total_points' => 20,
            'source_status' => null,
            'source_synced_at' => null,
            'source_deleted_at' => null,
        ];
    }

    private function caseFor(Student $student, User $actor): BkCase
    {
        return BkCase::query()->create([
            'student_id' => $student->getKey(),
            'case_source_id' => ReferenceValue::query()->where('category', 'case_source')->firstOrFail()->getKey(),
            'service_field_id' => ReferenceValue::query()->where('category', 'service_field')->firstOrFail()->getKey(),
            'status_id' => ReferenceValue::query()->where('category', 'case_status')->firstOrFail()->getKey(),
            'service_date' => '2026-09-25',
            'initial_info' => 'Informasi awal.',
            'initial_action' => 'Tindakan awal.',
            'created_by' => $actor->getKey(),
        ]);
    }

    private function userWithRole(string $role, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }
}
