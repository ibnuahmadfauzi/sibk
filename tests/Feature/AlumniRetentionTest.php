<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\IntegrationSetting;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\StudentDeparture;
use App\Models\User;
use App\Services\AlumniRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

final class AlumniRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-22 12:00:00');
        config(['alumni_retention.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
    }

    public function test_only_official_graduates_reach_exact_one_year_anniversary(): void
    {
        $due = $this->student('0000000001', '2025-10-22');
        $recent = $this->student('0000000002', '2025-10-23');
        $transfer = $this->student('0000000003', '2024-06-30', StudentDeparture::TYPE_TRANSFER);
        $draft = $this->student('0000000004', '2024-06-30', StudentDeparture::TYPE_GRADUATED, StudentDeparture::STATUS_IN_PROGRESS);
        $cancelled = $this->student('0000000005', '2024-06-30', StudentDeparture::TYPE_GRADUATED, StudentDeparture::STATUS_CANCELLED);

        $this->artisan('sibk:hapus-alumni')->assertSuccessful();
        $this->assertDatabaseCount('students', 5);
        $this->assertSame(['eligible' => 1, 'deferred' => 0, 'deleted' => 0], app(AlumniRetentionService::class)->run());

        $this->artisan('sibk:hapus-alumni', ['--execute' => true])->assertSuccessful();
        $this->assertDatabaseMissing('students', ['id' => $due->id]);
        foreach ([$recent, $transfer, $draft, $cancelled] as $protected) {
            $this->assertDatabaseHas('students', ['id' => $protected->id]);
        }
        $this->assertSame(['eligible' => 0, 'deferred' => 0, 'deleted' => 0], app(AlumniRetentionService::class)->run(true));
    }

    public function test_leap_day_graduation_expires_on_28_february_next_year(): void
    {
        $this->travelTo('2025-02-27 12:00:00');
        $student = $this->student('0000000010', '2024-02-29');
        $this->assertSame(0, app(AlumniRetentionService::class)->run()['eligible']);

        $this->travelTo('2025-02-28 12:00:00');
        $this->assertSame(1, app(AlumniRetentionService::class)->run()['eligible']);
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_active_or_future_membership_protects_reenrolled_graduate(): void
    {
        $student = $this->student('0000000020', '2024-06-30');
        $year = AcademicYear::query()->create([
            'name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id, 'name' => 'X RPL 1', 'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id, 'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id, 'is_active' => true,
        ]);

        $this->assertSame(['eligible' => 0, 'deferred' => 1, 'deleted' => 0], app(AlumniRetentionService::class)->run(true));
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_deletes_exact_bk_records_and_preserves_other_student(): void
    {
        $graduate = $this->student('0000000030', '2024-06-30');
        $other = $this->student('0000000031', '2026-06-30');
        $graduateRecord = DB::table('external_tatib_records')->insertGetId([
            'source_identifier' => 'etatib-old', 'nisn' => $graduate->nisn,
            'student_id' => $graduate->id, 'violation_type' => 'Terlambat',
            'category' => 'Ringan', 'points' => 10,
        ]);
        DB::table('external_tatib_records')->insert([
            'source_identifier' => 'etatib-active', 'nisn' => $other->nisn,
            'student_id' => $other->id, 'violation_type' => 'Terlambat',
            'category' => 'Ringan', 'points' => 10,
        ]);
        DB::table('audit_logs')->insert([
            'action' => 'etatib.updated', 'auditable_type' => 'App\\Models\\ExternalTatibRecord',
            'auditable_id' => $graduateRecord, 'summary' => $graduate->name,
        ]);
        DB::table('audit_logs')->insert([
            'action' => 'student.updated', 'auditable_type' => Student::class,
            'auditable_id' => $other->id, 'summary' => $other->name,
        ]);

        $result = app(AlumniRetentionService::class)->run(true);
        $this->assertSame(['eligible' => 1, 'deferred' => 0, 'deleted' => 1], $result);
        $this->assertDatabaseMissing('students', ['id' => $graduate->id]);
        $this->assertDatabaseMissing('external_tatib_records', ['id' => $graduateRecord]);
        $this->assertDatabaseMissing('audit_logs', ['summary' => $graduate->name]);
        $this->assertDatabaseHas('students', ['id' => $other->id]);
        $this->assertDatabaseHas('external_tatib_records', ['source_identifier' => 'etatib-active']);
        $this->assertDatabaseHas('audit_logs', ['summary' => $other->name]);
        $this->assertDatabaseCount('alumni_retention_markers', 1);
    }

    public function test_legacy_probe_sample_defers_matching_graduate_without_changing_shared_data(): void
    {
        $graduate = $this->student('0012345678', '2024-06-30');
        $summary = ['record_count' => 2, 'samples' => [
            ['nisn' => '0012****78', 'violation' => 'Ringan'],
            ['nisn' => '0099****99', 'violation' => 'Lainnya'],
        ]];
        $setting = IntegrationSetting::query()->create([
            'provider' => IntegrationSetting::PROVIDER_ETATIB,
            'last_probe_summary' => $summary,
        ]);

        $this->assertSame(['eligible' => 0, 'deferred' => 1, 'deleted' => 0], app(AlumniRetentionService::class)->run(true));
        $this->assertDatabaseHas('students', ['id' => $graduate->id]);
        $this->assertSame($summary, $setting->refresh()->last_probe_summary);
    }

    public function test_deletes_services_temporary_identity_and_preview_side_records(): void
    {
        $graduate = $this->student('0000000050', '2024-06-30');
        $actor = User::factory()->create();
        $reference = DB::table('references')->insertGetId([
            'category' => 'retention_test', 'code' => 'one', 'label' => 'Test',
        ]);
        $temporary = DB::table('temporary_students')->insertGetId([
            'nisn' => $graduate->nisn, 'input_name' => $graduate->name,
            'created_by' => $actor->id, 'reconciliation_status_id' => $reference,
            'reconciled_student_id' => $graduate->id,
        ]);
        DB::table('identity_reconciliations')->insert([
            'temporary_student_id' => $temporary, 'source_nisn' => $graduate->nisn,
            'student_id' => $graduate->id, 'status_id' => $reference,
            'result' => 'Cocok', 'checked_at' => now(),
        ]);
        $case = DB::table('cases')->insertGetId([
            'temporary_student_id' => $temporary, 'case_source_id' => $reference,
            'service_field_id' => $reference, 'status_id' => $reference,
            'service_date' => '2024-05-01', 'initial_info' => 'Rahasia kasus',
            'initial_action' => 'Asesmen', 'created_by' => $actor->id,
        ]);
        DB::table('case_follow_ups')->insert([
            'case_id' => $case, 'follow_up_type_id' => $reference,
            'follow_up_date' => '2024-05-02', 'notes' => 'Rahasia tindak lanjut',
        ]);
        DB::table('case_assignments')->insert([
            'case_id' => $case, 'user_id' => $actor->id,
            'reason' => 'Penugasan', 'assigned_by' => $actor->id,
        ]);
        DB::table('consultations')->insert([
            'temporary_student_id' => $temporary, 'service_field_id' => $reference,
            'session_date' => '2024-05-02', 'problem' => 'Rahasia konseling',
            'counselor_id' => $actor->id,
        ]);
        DB::table('achievements')->insert([
            'student_id' => $graduate->id, 'type_id' => $reference, 'level_id' => $reference,
            'activity_name' => 'Prestasi lama', 'organizer' => 'Sekolah',
            'achievement_date' => '2024-05-01', 'result' => 'Juara',
            'evidence_reference' => 'Dokumen', 'verification_status_id' => $reference,
            'recorded_by' => $actor->id,
        ]);
        DB::table('external_sync_issues')->insert([
            'external_sync_run_id' => $this->syncRun('etatib', 'succeeded'),
            'entity_type' => 'student', 'nisn' => $graduate->nisn,
            'resolved_student_id' => $graduate->id,
            'issue_code' => 'student_not_found', 'summary' => 'Rahasia masalah sinkronisasi',
        ]);
        $previewRun = $this->syncRun('dapodik', 'superseded');
        $preview = DB::table('dapodik_sync_preview_items')->insertGetId([
            'external_sync_run_id' => $previewRun, 'entity_type' => 'student',
            'source_identifier' => 'dapodik-old', 'candidate_id' => $graduate->id,
            'match_status' => 'exact_match',
            'safe_fields' => json_encode(['source_id' => 'dapodik-old', 'nisn' => $graduate->nisn]),
            'item_hash' => str_repeat('a', 64), 'preview_generation' => 1,
        ]);
        $unrelatedPreview = DB::table('dapodik_sync_preview_items')->insertGetId([
            'external_sync_run_id' => $previewRun, 'entity_type' => 'membership',
            'source_identifier' => 'dapodik-other-membership',
            'match_status' => 'new_record',
            'safe_fields' => json_encode(['source_id' => 'dapodik-other-membership', 'student_source_id' => 'another-student']),
            'item_hash' => str_repeat('b', 64), 'preview_generation' => 1,
        ]);
        DB::table('audit_logs')->insert([
            'action' => 'dapodik.preview_decided', 'auditable_type' => 'App\\Models\\ExternalSyncRun',
            'auditable_id' => $previewRun, 'summary' => 'Keputusan pratinjau',
            'after_values' => json_encode(['item_id' => $preview]),
        ]);

        $this->assertSame(1, app(AlumniRetentionService::class)->run(true)['deleted']);
        foreach (['students', 'temporary_students', 'identity_reconciliations', 'cases',
            'case_follow_ups', 'case_assignments', 'consultations', 'achievements',
            'external_sync_issues', 'audit_logs'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertDatabaseHas('dapodik_sync_preview_items', ['id' => $unrelatedPreview]);
        $this->assertDatabaseMissing('dapodik_sync_preview_items', ['id' => $preview]);
        $this->assertDatabaseCount('external_sync_runs', 2);
    }

    public function test_unexpired_dapodik_preview_defers_until_preview_finishes(): void
    {
        $graduate = $this->student('0000000060', '2024-06-30');
        $runId = $this->syncRun('dapodik', 'preview_ready', '2026-10-23 00:00:00');
        DB::table('dapodik_sync_preview_items')->insert([
            'external_sync_run_id' => $runId, 'entity_type' => 'student',
            'source_identifier' => 'dapodik-pending', 'candidate_id' => $graduate->id,
            'match_status' => 'exact_match',
            'safe_fields' => json_encode(['source_id' => 'dapodik-pending', 'nisn' => $graduate->nisn]),
            'item_hash' => str_repeat('a', 64), 'preview_generation' => 1,
        ]);

        $this->assertSame(1, app(AlumniRetentionService::class)->run(true)['deferred']);
        $this->assertDatabaseHas('students', ['id' => $graduate->id]);
        $this->assertDatabaseCount('dapodik_sync_preview_items', 1);
    }

    public function test_noncanonical_etatib_alias_defers_purge_without_touching_other_pupil(): void
    {
        $graduate = $this->student('0000000070', '2024-06-30');
        $other = $this->student('0000000071', '2026-06-30');
        DB::table('external_tatib_records')->insert([
            'source_identifier' => 'etatib-alias', 'nisn' => $other->nisn,
            'student_id' => $graduate->id, 'violation_type' => 'Ringan',
            'category' => 'Ringan', 'points' => 10,
        ]);

        $this->assertSame(1, app(AlumniRetentionService::class)->run(true)['deferred']);
        $this->assertDatabaseHas('students', ['id' => $graduate->id]);
        $this->assertDatabaseHas('students', ['id' => $other->id]);
        $this->assertDatabaseHas('external_tatib_records', ['source_identifier' => 'etatib-alias']);
        $this->assertDatabaseCount('alumni_retention_markers', 0);
    }

    public function test_owned_etatib_source_nisn_with_missing_leading_zeroes_is_deleted(): void
    {
        $graduate = $this->student('0095000088', '2024-06-30');
        DB::table('external_tatib_records')->insert([
            'source_identifier' => 'etatib-raw-nisn', 'nisn' => $graduate->nisn,
            'source_nisn' => '95000088', 'student_id' => $graduate->id,
            'violation_type' => 'Ringan', 'category' => 'Ringan', 'points' => 10,
        ]);

        $this->assertSame(1, app(AlumniRetentionService::class)->run(true)['deleted']);
        $this->assertDatabaseMissing('students', ['id' => $graduate->id]);
        $this->assertDatabaseMissing('external_tatib_records', ['source_identifier' => 'etatib-raw-nisn']);
        $this->assertDatabaseCount('alumni_retention_markers', 1);
    }

    public function test_raw_nisn_owned_by_another_student_defers_everything(): void
    {
        $graduate = $this->student('0095000088', '2024-06-30');
        $other = $this->student('95000088', '2026-06-30');
        DB::table('external_tatib_records')->insert([
            'source_identifier' => 'etatib-conflicting-raw', 'nisn' => $graduate->nisn,
            'source_nisn' => $other->nisn, 'student_id' => $graduate->id,
            'violation_type' => 'Ringan', 'category' => 'Ringan', 'points' => 10,
        ]);

        $this->assertSame(1, app(AlumniRetentionService::class)->run(true)['deferred']);
        $this->assertDatabaseHas('students', ['id' => $graduate->id]);
        $this->assertDatabaseHas('students', ['id' => $other->id]);
        $this->assertDatabaseHas('external_tatib_records', ['source_identifier' => 'etatib-conflicting-raw']);
        $this->assertDatabaseCount('alumni_retention_markers', 0);
    }

    public function test_missing_marker_key_fails_before_any_deletion(): void
    {
        $student = $this->student('0000000040', '2024-06-30');
        config(['alumni_retention.key' => null]);

        $this->expectException(RuntimeException::class);
        try {
            app(AlumniRetentionService::class)->run(true);
        } finally {
            $this->assertDatabaseHas('students', ['id' => $student->id]);
            $this->assertDatabaseCount('alumni_retention_markers', 0);
        }
    }

    public function test_local_dapodik_issues_and_their_audits_are_removed_only_for_the_graduate(): void
    {
        $graduate = $this->student('0000000050', '2024-06-30');
        $other = $this->student('0000000051', '2026-06-30');
        $year = AcademicYear::query()->create([
            'name' => '2023/2024', 'starts_on' => '2023-07-01', 'ends_on' => '2024-06-30', 'is_active' => false,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id, 'name' => 'XII RPL 1', 'is_active' => false,
        ]);
        $membership = StudentClassMembership::query()->create([
            'student_id' => $graduate->id, 'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id, 'is_active' => false,
        ]);
        $run = $this->syncRun('dapodik', 'succeeded');
        $issues = [];
        foreach ([['student', $graduate->id], ['membership', $membership->id], ['student', $other->id]] as [$type, $id]) {
            $issue = DB::table('external_sync_issues')->insertGetId([
                'external_sync_run_id' => $run, 'entity_type' => $type,
                'source_identifier' => 'local:'.$id, 'issue_code' => 'unmatched_local_record',
                'summary' => 'Catatan lokal yang tidak ditemukan pada sumber.',
            ]);
            $issues[] = $issue;
            DB::table('audit_logs')->insert([
                'action' => 'sync_issue.reviewed', 'auditable_type' => 'App\\Models\\ExternalSyncIssue',
                'auditable_id' => $issue, 'summary' => 'Catatan pemeriksaan masalah.',
            ]);
        }

        $this->assertSame(1, app(AlumniRetentionService::class)->run(true)['deleted']);
        foreach (array_slice($issues, 0, 2) as $issue) {
            $this->assertDatabaseMissing('external_sync_issues', ['id' => $issue]);
            $this->assertDatabaseMissing('audit_logs', ['auditable_type' => 'App\\Models\\ExternalSyncIssue', 'auditable_id' => $issue]);
        }
        $this->assertDatabaseHas('external_sync_issues', ['id' => $issues[2]]);
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => 'App\\Models\\ExternalSyncIssue', 'auditable_id' => $issues[2]]);
    }

    public function test_failed_student_delete_rolls_back_markers_and_child_deletions(): void
    {
        $student = $this->student('0000000060', '2024-06-30');
        DB::table('external_tatib_records')->insert([
            'source_identifier' => 'etatib-rollback', 'nisn' => $student->nisn,
            'student_id' => $student->id, 'violation_type' => 'Ringan', 'category' => 'Ringan', 'points' => 10,
        ]);
        DB::unprepared("CREATE TRIGGER retention_test_abort BEFORE DELETE ON students BEGIN SELECT RAISE(ABORT, 'uji rollback'); END");

        try {
            app(AlumniRetentionService::class)->run(true);
            $this->fail('Kegagalan penghapusan harus membatalkan transaksi.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertDatabaseHas('students', ['id' => $student->id]);
            $this->assertDatabaseHas('external_tatib_records', ['source_identifier' => 'etatib-rollback']);
            $this->assertDatabaseHas('student_departures', ['student_id' => $student->id]);
            $this->assertDatabaseCount('alumni_retention_markers', 0);
        } finally {
            DB::unprepared('DROP TRIGGER retention_test_abort');
        }
    }

    private function student(
        string $nisn,
        string $date,
        string $type = StudentDeparture::TYPE_GRADUATED,
        string $status = StudentDeparture::STATUS_OFFICIAL,
    ): Student {
        $student = Student::query()->create(['nisn' => $nisn, 'name' => 'Murid '.$nisn, 'is_active' => false]);
        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => $type,
            'status' => $status,
            'reported_at' => $date,
            'effective_date' => $status === StudentDeparture::STATUS_OFFICIAL ? $date : null,
            'recorded_by' => User::factory()->create()->id,
        ]);

        return $student;
    }

    private function syncRun(string $source, string $status, ?string $expires = null): int
    {
        return DB::table('external_sync_runs')->insertGetId([
            'source' => $source, 'status' => $status, 'started_at' => now(),
            'preview_expires_at' => $expires,
        ]);
    }
}
