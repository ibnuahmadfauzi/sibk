<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Integrations\Etatib\EtatibSnapshot;
use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\EtatibIdentityMapping;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\IntegrationSetting;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Services\EtatibIdentityNormalizer;
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

    public function test_mapping_candidate_shows_class_from_academic_year_state(): void
    {
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->getKey(),
            'name' => 'X RPL 1',
            'is_active' => true,
        ]);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Kandidat Rombel']);
        StudentClassMembership::query()->create([
            'student_id' => $student->getKey(),
            'classroom_id' => $classroom->getKey(),
            'academic_year_id' => $year->getKey(),
            'is_active' => true,
        ]);
        $olderYear = AcademicYear::query()->create([
            'name' => '2025/2026',
            'starts_on' => '2025-07-01',
            'ends_on' => '2026-06-30',
            'is_active' => false,
        ]);
        $olderClassroom = Classroom::query()->create([
            'academic_year_id' => $olderYear->getKey(),
            'name' => 'IX RPL 1',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->getKey(),
            'classroom_id' => $olderClassroom->getKey(),
            'academic_year_id' => $olderYear->getKey(),
            'is_active' => true,
        ]);

        $this->actingAs($this->userWithRole('admin_it'))
            ->getJson(route('data-master.etatib.candidates', ['search' => 'Kandidat Rombel']))
            ->assertOk()
            ->assertJsonPath('data.0.classroom', 'X RPL 1');
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

    public function test_manual_mapping_cannot_override_existing_canonical_nisn(): void
    {
        $admin = $this->userWithRole('admin_it');
        Student::query()->create(['nisn' => '0093200788', 'name' => 'Nama Dapodik', 'master_source' => Student::MASTER_SOURCE_DAPODIK]);
        $other = Student::query()->create(['nisn' => '0011111111', 'name' => 'Murid Lain']);
        [$issue, $record] = $this->conflict('0093200788', 'Nama Berbeda', 'source-canonical');

        $this->actingAs($admin)->post(route('data-master.etatib.mappings.store', $issue), [
            'student_id' => $other->getKey(), 'confirmed' => '1',
        ])->assertSessionHasErrors('student_id');

        $this->assertNull($record->refresh()->student_id);
        $this->assertDatabaseCount('etatib_identity_mappings', 0);
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

    public function test_sync_preserves_case_link_when_canonical_nisn_points_to_another_student(): void
    {
        $admin = $this->userWithRole('admin_it');
        $canonical = Student::query()->create(['nisn' => '0093200788', 'name' => 'Murid Dapodik', 'master_source' => Student::MASTER_SOURCE_DAPODIK]);
        $previous = Student::query()->create(['nisn' => '0011111111', 'name' => 'Murid Kasus']);
        [, $record] = $this->conflict('0093200788', 'Nama e-Tatib', 'source-linked-drift');
        $record->update(['student_id' => $previous->getKey()]);
        $case = $this->caseFor($previous, $admin);
        $case->etatibRecords()->attach($record->getKey(), ['linked_by' => $admin->getKey()]);

        $run = app(EtatibSyncService::class)->synchronizeSnapshot(new EtatibSnapshot(true, [
            $this->snapshotItem('source-linked-drift', '0093200788', 'Nama e-Tatib'),
        ]), $admin);

        $this->assertSame(ExternalSyncRun::STATUS_FAILED, $run->status);
        $this->assertSame($previous->getKey(), $record->refresh()->student_id);
        $this->assertSame($previous->getKey(), $case->refresh()->student_id);
        $this->assertDatabaseHas('case_etatib_links', ['case_id' => $case->getKey(), 'external_tatib_record_id' => $record->getKey(), 'deleted_at' => null]);
        $this->assertSame(0, EtatibIdentityMapping::query()->count());
        $this->assertNotSame($canonical->getKey(), $record->student_id);
    }

    public function test_new_manual_mapping_cannot_move_record_already_linked_to_a_case(): void
    {
        $admin = $this->userWithRole('admin_it');
        $previous = Student::query()->create(['nisn' => '0011111111', 'name' => 'Murid Kasus']);
        $other = Student::query()->create(['nisn' => '0022222222', 'name' => 'Murid Tujuan']);
        [$issue, $record] = $this->conflict('0093200788', 'Nama e-Tatib', 'source-manual-linked');
        $record->update(['student_id' => $previous->getKey()]);
        $case = $this->caseFor($previous, $admin);
        $case->etatibRecords()->attach($record->getKey(), ['linked_by' => $admin->getKey()]);

        $this->actingAs($admin)->post(route('data-master.etatib.mappings.store', $issue), [
            'student_id' => $other->getKey(), 'confirmed' => '1',
        ])->assertSessionHasErrors('student_id');

        $this->assertSame($previous->getKey(), $record->refresh()->student_id);
        $this->assertDatabaseCount('etatib_identity_mappings', 0);
        $this->assertDatabaseHas('case_etatib_links', ['case_id' => $case->getKey(), 'external_tatib_record_id' => $record->getKey(), 'deleted_at' => null]);
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

    public function test_conflict_search_filters_name_and_nisn_before_ten_row_pagination(): void
    {
        $admin = $this->userWithRole('admin_it');
        for ($number = 1; $number <= 11; $number++) {
            $this->conflict(sprintf('%010d', $number), 'Sumber Pencarian '.$number, 'source-search-'.$number);
        }
        $this->conflict('0099999999', 'Nama Lain', 'source-search-other');

        $firstPage = $this->actingAs($admin)->get(route('data-master.etatib.conflicts.index', [
            'search' => 'Sumber Pencarian',
        ]))->assertOk()
            ->assertSee('Sumber Pencarian 1')
            ->assertDontSee('Nama Lain')
            ->assertSee('search=Sumber%20Pencarian&amp;page=2', false);
        $this->assertSame(10, substr_count($firstPage->getContent(), 'data-etatib-map-open'));

        $this->actingAs($admin)->get(route('data-master.etatib.conflicts.index', [
            'search' => '0099999999',
        ]))->assertOk()
            ->assertSee('Nama Lain')
            ->assertDontSee('Sumber Pencarian 1');
    }

    public function test_manual_mapping_search_includes_source_and_master_identity(): void
    {
        $admin = $this->userWithRole('admin_it');
        $student = Student::query()->create(['nisn' => '0011111111', 'name' => 'Tujuan Khusus']);
        [$issue] = $this->conflict('0093200788', 'Sumber Khas', 'source-mapping-search');
        $this->actingAs($admin)->post(route('data-master.etatib.mappings.store', $issue), [
            'student_id' => $student->getKey(),
            'confirmed' => '1',
        ]);

        foreach (['Sumber Khas', '0093200788', 'Tujuan Khusus', '0011111111'] as $search) {
            $this->actingAs($admin)->get(route('data-master.etatib.conflicts.index', [
                'tab' => 'mappings',
                'search' => $search,
            ]))->assertOk()->assertSee('Sumber Khas')->assertSee('Tujuan Khusus');
        }
        $this->actingAs($admin)->get(route('data-master.etatib.conflicts.index', [
            'tab' => 'mappings',
            'search' => 'Tidak Ada',
        ]))->assertOk()->assertDontSee('Sumber Khas');
    }

    public function test_admin_can_preview_and_apply_unique_nisn_matches_without_changing_master_identity(): void
    {
        $admin = $this->userWithRole('admin_it');
        $student = Student::query()->create(['nisn' => '0011111111', 'name' => 'Nama Resmi Sekolah']);
        [$issue, $record] = $this->conflict('0011111111', 'Nama e-Tatib Berbeda', 'source-auto-nisn');
        $this->conflict('0022222222', 'Belum Ada Murid', 'source-auto-skip');

        $preview = $this->actingAs($admin)->getJson(route('data-master.etatib.conflicts.automatic.preview', ['mode' => 'nisn']))
            ->assertOk()
            ->assertJsonCount(1, 'proposals')
            ->assertJsonCount(1, 'skipped')
            ->assertJsonPath('proposals.0.student_id', $student->getKey());
        $this->assertNull($record->refresh()->student_id);

        $this->actingAs($admin)->post(route('data-master.etatib.conflicts.automatic.apply'), [
            'mode' => 'nisn',
            'fingerprint' => $preview->json('fingerprint'),
            'confirmed' => '1',
        ])->assertRedirect(route('data-master.etatib.conflicts.index'));

        $this->assertSame($student->getKey(), $record->refresh()->student_id);
        $this->assertSame('Nama Resmi Sekolah', $student->refresh()->name);
        $this->assertNotNull($issue->refresh()->resolved_at);
        $this->assertSame(1, EtatibIdentityMapping::query()->active()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'etatib.identity_auto_mapped']);
        $this->assertDatabaseHas('external_sync_issues', ['source_identifier' => 'source-auto-skip', 'resolved_at' => null]);
    }

    public function test_name_mode_cannot_automatically_link_same_name_with_different_nisn(): void
    {
        $admin = $this->userWithRole('admin_it');
        $student = Student::query()->create(['nisn' => '0011111111', 'name' => 'Murid Nama Sama']);
        Student::query()->create(['nisn' => '0022222222', 'name' => 'Nama Duplikat']);
        Student::query()->create(['nisn' => '0033333333', 'name' => 'NAMA DUPLIKAT']);
        [$issue, $record] = $this->conflict('0099999991', '  murid   nama SAMA ', 'source-auto-name');
        $this->conflict('0099999992', 'Nama Duplikat', 'source-auto-ambiguous');

        $this->actingAs($admin)->getJson(route('data-master.etatib.conflicts.automatic.preview', ['mode' => 'name']))
            ->assertUnprocessable()->assertJsonValidationErrors('mode');
        $this->actingAs($admin)->post(route('data-master.etatib.conflicts.automatic.apply'), [
            'mode' => 'name', 'fingerprint' => str_repeat('0', 64), 'confirmed' => '1',
        ])->assertSessionHasErrors('mode');
        $this->assertNull($record->refresh()->student_id);
        $this->assertNull($issue->refresh()->resolved_at);
        $this->assertSame(0, EtatibIdentityMapping::query()->count());
    }

    public function test_manual_matching_can_map_all_records_of_one_source_identity_when_nisn_absent(): void
    {
        $admin = $this->userWithRole('admin_it');
        $student = Student::query()->create(['nisn' => '0011111111', 'name' => 'Murid Nama Sama']);
        [$firstIssue, $firstRecord] = $this->conflict('0099999991', 'MURID  NAMA SAMA', 'source-name-one');
        [$secondIssue, $secondRecord] = $this->conflict('0099999991', 'murid nama sama', 'source-name-two');

        $this->actingAs($admin)->post(route('data-master.etatib.mappings.store', $firstIssue), [
            'student_id' => $student->getKey(), 'confirmed' => '1',
        ])->assertRedirect(route('data-master.etatib.conflicts.index'));

        $this->assertSame($student->getKey(), $firstRecord->refresh()->student_id);
        $this->assertSame($student->getKey(), $secondRecord->refresh()->student_id);
        $this->assertNotNull($firstIssue->refresh()->resolved_at);
        $this->assertNotNull($secondIssue->refresh()->resolved_at);
        $this->assertSame(1, EtatibIdentityMapping::query()->active()->count());
    }

    public function test_nisn_preview_does_not_propose_name_only_matches(): void
    {
        $admin = $this->userWithRole('admin_it');
        Student::query()->create(['nisn' => '0011111111', 'name' => 'Nama Sama']);
        $this->conflict('0099999991', 'Nama Sama', 'source-same-one');
        $this->conflict('0099999992', 'NAMA SAMA', 'source-same-two');

        $this->actingAs($admin)->getJson(route('data-master.etatib.conflicts.automatic.preview', ['mode' => 'nisn']))
            ->assertOk()
            ->assertJsonCount(0, 'proposals')
            ->assertJsonCount(2, 'skipped');
    }

    public function test_nisn_matching_links_two_source_names_to_one_master_student(): void
    {
        $admin = $this->userWithRole('admin_it');
        $student = Student::query()->create(['nisn' => '0011111111', 'name' => 'Nama Sekolah']);
        [, $first] = $this->conflict('0011111111', 'Nama e-Tatib Satu', 'source-nisn-one');
        [, $second] = $this->conflict('0011111111', 'Nama e-Tatib Dua', 'source-nisn-two');

        $preview = $this->actingAs($admin)->getJson(route('data-master.etatib.conflicts.automatic.preview', ['mode' => 'nisn']))
            ->assertOk()->assertJsonCount(2, 'proposals')->assertJsonCount(0, 'skipped');
        $this->actingAs($admin)->post(route('data-master.etatib.conflicts.automatic.apply'), [
            'mode' => 'nisn', 'fingerprint' => $preview->json('fingerprint'), 'confirmed' => '1',
        ])->assertRedirect(route('data-master.etatib.conflicts.index'));
        $this->assertSame($student->getKey(), $first->refresh()->student_id);
        $this->assertSame($student->getKey(), $second->refresh()->student_id);
        $this->assertSame('Nama Sekolah', $student->refresh()->name);
    }

    public function test_automatic_preview_preserves_existing_active_mapping(): void
    {
        $admin = $this->userWithRole('admin_it');
        $student = Student::query()->create(['nisn' => '0011111111', 'name' => 'Nama Sekolah']);
        [$issue, $record] = $this->conflict('0011111111', 'Nama e-Tatib', 'source-existing-map');
        EtatibIdentityMapping::query()->create([
            'source_nisn' => '0011111111',
            'source_name' => 'Nama e-Tatib',
            'source_name_hash' => app(EtatibIdentityNormalizer::class)->nameHash('Nama e-Tatib'),
            'student_id' => $student->getKey(),
            'is_active' => true,
            'mapped_by' => $admin->getKey(),
            'mapped_at' => now(),
        ]);

        $this->actingAs($admin)->getJson(route('data-master.etatib.conflicts.automatic.preview', ['mode' => 'nisn']))
            ->assertOk()
            ->assertJsonCount(0, 'proposals')
            ->assertJsonCount(1, 'skipped');
        $this->assertNull($record->refresh()->student_id);
        $this->assertNull($issue->refresh()->resolved_at);
    }

    public function test_automatic_matching_requires_admin_and_confirmation(): void
    {
        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');
        Student::query()->create(['nisn' => '0011111111', 'name' => 'Master']);
        $this->conflict('0011111111', 'Sumber', 'source-auto-auth');

        $this->actingAs($teacher)->getJson(route('data-master.etatib.conflicts.automatic.preview', ['mode' => 'nisn']))
            ->assertForbidden();
        $this->actingAs($teacher)->post(route('data-master.etatib.conflicts.automatic.apply'), [
            'mode' => 'nisn', 'fingerprint' => str_repeat('0', 64), 'confirmed' => '1',
        ])->assertForbidden();
        $preview = $this->actingAs($admin)->getJson(route('data-master.etatib.conflicts.automatic.preview', ['mode' => 'nisn']))->assertOk();
        $this->actingAs($admin)->post(route('data-master.etatib.conflicts.automatic.apply'), [
            'mode' => 'nisn', 'fingerprint' => $preview->json('fingerprint'),
        ])->assertSessionHasErrors('confirmed');
        $this->assertSame(0, EtatibIdentityMapping::query()->count());
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
