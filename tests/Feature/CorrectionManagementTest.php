<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\CaseCoordination;
use App\Models\Classroom;
use App\Models\Correction;
use App\Models\ExternalSyncRun;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\CaseService;
use App\Services\ConsultationService;
use App\Services\CorrectionService;
use App\Services\FollowUpService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrectionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_operational_correction_derives_old_value_and_approval_uses_case_service(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($teacher, $student);

        $this->actingAs($teacher)->post(route('corrections.store'), [
            'target_type' => 'case',
            'target_id' => $case->id,
            'field_name' => 'service_date',
            'proposed_value' => '2026-08-02',
            'reason' => 'Tanggal pada dokumen layanan perlu disesuaikan.',
        ])->assertRedirect();

        $correction = Correction::query()->firstOrFail();
        $this->assertSame('2026-08-01', $correction->old_value);
        $this->assertMatchesRegularExpression('/^KR-2026-\d{4}$/', $correction->registration_number);
        $this->actingAs($coordinator)->post(route('corrections.verify', $correction), [
            'decision' => 'approved',
            'review_notes' => 'Dokumen pendukung telah diperiksa.',
        ])->assertRedirect(route('corrections.show', $correction));

        $this->assertSame('2026-08-02', $case->refresh()->service_date->toDateString());
        $this->assertSame('disetujui', $correction->refresh()->status->code);
        $this->assertDatabaseHas('audit_logs', ['action' => 'case.corrected', 'auditable_id' => $case->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'correction.reviewed', 'auditable_id' => $correction->id]);
    }

    public function test_stale_operational_correction_is_rejected_without_partial_mutation(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($teacher, $student);
        $correction = app(CorrectionService::class)->submit([
            'target_type' => 'case',
            'target_id' => $case->id,
            'field_name' => 'initial_action',
            'proposed_value' => 'Usulan tindakan terkoreksi.',
            'reason' => 'Perlu penyesuaian.',
        ], $teacher);
        $case->update(['initial_action' => 'Sudah berubah melalui proses lain.']);

        $this->actingAs($coordinator)->from(route('corrections.show', $correction))
            ->post(route('corrections.verify', $correction), ['decision' => 'approved'])
            ->assertSessionHasErrors('correction');

        $this->assertSame('Sudah berubah melalui proses lain.', $case->refresh()->initial_action);
        $this->assertSame('menunggu', $correction->refresh()->status->code);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'case.corrected', 'auditable_id' => $case->id]);
    }

    public function test_rejection_requires_notes_and_does_not_change_domain_object(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($teacher, $student);
        $correction = $this->submitCaseCorrection($teacher, $case);

        $this->actingAs($coordinator)->post(route('corrections.verify', $correction), ['decision' => 'rejected'])
            ->assertSessionHasErrors('review_notes');
        $this->actingAs($coordinator)->post(route('corrections.verify', $correction), [
            'decision' => 'rejected',
            'review_notes' => 'Dokumen tidak mendukung perubahan.',
        ])->assertRedirect();

        $this->assertSame('2026-08-01', $case->refresh()->service_date->toDateString());
        $this->assertSame('ditolak', $correction->refresh()->status->code);
    }

    public function test_master_correction_never_mutates_student_and_completion_requires_matching_newer_sync(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $admin = $this->userWithRole('admin_it');
        $correction = app(CorrectionService::class)->submit([
            'target_type' => 'student',
            'target_id' => $student->id,
            'field_name' => 'name',
            'proposed_value' => 'Nama Resmi Baru',
            'reason' => 'Nama pada sumber resmi perlu diperbaiki.',
        ], $teacher);

        $this->actingAs($admin)->post(route('corrections.process-master', $correction), [
            'action' => 'processing',
            'review_notes' => 'Diteruskan ke pengelola Dapodik.',
        ])->assertRedirect();
        $this->assertSame('Murid Scope Koreksi', $student->refresh()->name);
        $this->assertSame('diproses', $correction->refresh()->status->code);

        $unmatchedRun = $this->successfulDapodikRun($admin);
        $this->actingAs($admin)->post(route('corrections.process-master', $correction), [
            'action' => 'completed',
            'external_sync_run_id' => $unmatchedRun->id,
        ])->assertSessionHasErrors('external_sync_run_id');
        $this->assertSame('diproses', $correction->refresh()->status->code);

        $student->update(['name' => 'Nama Resmi Baru']);
        $matchingRun = $this->successfulDapodikRun($admin);
        $this->actingAs($admin)->post(route('corrections.process-master', $correction), [
            'action' => 'completed',
            'external_sync_run_id' => $matchingRun->id,
        ])->assertRedirect();

        $this->assertSame('selesai', $correction->refresh()->status->code);
        $this->assertSame($matchingRun->id, $correction->external_sync_run_id);
    }

    public function test_role_matrix_limits_lists_details_and_verification_actions(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $otherTeacher = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $otherWaka = $this->userWithRole('waka_kesiswaan');
        $admin = $this->userWithRole('admin_it');
        $case = $this->createCase($teacher, $student);
        CaseCoordination::query()->create([
            'case_id' => $case->id,
            'waka_user_id' => $waka->id,
            'status_id' => $this->reference('coordination_status', 'menunggu')->id,
            'coordination_need' => 'Koordinasi pengujian koreksi.',
            'recorded_by' => $teacher->id,
            'coordinated_at' => now(),
        ]);
        $operational = $this->submitCaseCorrection($teacher, $case);
        $master = app(CorrectionService::class)->submit([
            'target_type' => 'student', 'target_id' => $student->id, 'field_name' => 'nisn',
            'proposed_value' => '0099999999', 'reason' => 'Pelaporan sumber resmi.',
        ], $teacher);

        $this->actingAs($teacher)->get(route('corrections.index'))->assertOk()->assertSee($operational->registration_number)->assertSee($master->registration_number);
        $this->actingAs($otherTeacher)->get(route('corrections.show', $operational))->assertForbidden();
        $this->actingAs($coordinator)->get(route('corrections.index'))->assertOk()->assertSee($operational->registration_number)->assertSee($master->registration_number);
        $this->actingAs($waka)->get(route('corrections.show', $operational))->assertOk()->assertDontSee('Verifikasi Koordinator BK');
        $this->actingAs($otherWaka)->get(route('corrections.show', $operational))->assertForbidden();
        $this->actingAs($admin)->get(route('corrections.index'))->assertOk()->assertSee($master->registration_number)->assertDontSee($operational->registration_number);
        $this->actingAs($admin)->post(route('corrections.verify', $operational), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($coordinator)->post(route('corrections.process-master', $master), ['action' => 'processing'])->assertForbidden();

        $this->actingAs($otherTeacher)->post(route('corrections.store'), [
            'target_type' => 'case', 'target_id' => $case->id, 'field_name' => 'service_date',
            'proposed_value' => '2026-08-03', 'reason' => 'Di luar scope.',
        ])->assertSessionHasErrors('target_id');
    }

    public function test_history_only_exposes_role_appropriate_audit_summaries(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $otherTeacher = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $admin = $this->userWithRole('admin_it');
        AuditLog::query()->create(['actor_id' => $teacher->id, 'action' => 'case.corrected', 'auditable_type' => BkCase::class, 'auditable_id' => 10, 'summary' => 'RINGKASAN-GURU-A']);
        AuditLog::query()->create(['actor_id' => $otherTeacher->id, 'action' => 'case.corrected', 'auditable_type' => BkCase::class, 'auditable_id' => 11, 'summary' => 'RINGKASAN-GURU-B']);
        AuditLog::query()->create(['actor_id' => $admin->id, 'action' => 'dapodik.synchronized', 'auditable_type' => ExternalSyncRun::class, 'auditable_id' => 1, 'summary' => 'RINGKASAN-TEKNIS']);

        $this->actingAs($teacher)->get(route('history.index'))->assertOk()->assertSee('RINGKASAN-GURU-A')->assertDontSee('RINGKASAN-GURU-B')->assertDontSee('RINGKASAN-TEKNIS');
        $this->actingAs($admin)->get(route('history.index'))->assertOk()->assertSee('RINGKASAN-TEKNIS')->assertDontSee('RINGKASAN-GURU-A');
        $this->actingAs($coordinator)->get(route('history.index'))->assertOk()->assertSee('RINGKASAN-GURU-A')->assertSee('RINGKASAN-GURU-B')->assertSee('RINGKASAN-TEKNIS');
    }

    public function test_approved_follow_up_and_consultation_corrections_use_their_domain_services(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($teacher, $student);
        $followUp = app(FollowUpService::class)->record($case, [
            'follow_up_type_id' => $this->reference('follow_up_type', 'konsultasi_individual')->id,
            'status_id' => $this->reference('follow_up_status', 'terjadwal')->id,
            'planned_date' => '2026-08-22',
        ], $teacher);
        $consultation = app(ConsultationService::class)->create([
            'student_id' => $student->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('consultation_status', 'terlaksana')->id,
            'topic' => 'Koreksi layanan',
            'session_date' => '2026-08-20',
            'general_summary' => 'Ringkasan umum.',
        ], $teacher);

        $followUpCorrection = app(CorrectionService::class)->submit([
            'target_type' => 'follow_up', 'target_id' => $followUp->id, 'field_name' => 'follow_up_type_id',
            'proposed_value' => 'kunjungan_rumah', 'reason' => 'Jenis tindak lanjut perlu diperbaiki.',
        ], $teacher);
        $consultationCorrection = app(CorrectionService::class)->submit([
            'target_type' => 'consultation', 'target_id' => $consultation->id, 'field_name' => 'service_field_id',
            'proposed_value' => 'Belajar', 'reason' => 'Bidang layanan perlu diperbaiki.',
        ], $teacher);

        foreach ([$followUpCorrection, $consultationCorrection] as $correction) {
            $this->actingAs($coordinator)->post(route('corrections.verify', $correction), ['decision' => 'approved'])->assertRedirect();
        }

        $this->assertSame($this->reference('follow_up_type', 'kunjungan_rumah')->id, $followUp->refresh()->follow_up_type_id);
        $this->assertSame($this->reference('service_field', 'belajar')->id, $consultation->refresh()->service_field_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'follow_up.corrected', 'auditable_id' => $followUp->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'consultation.corrected', 'auditable_id' => $consultation->id]);
    }

    /** @return array{User, Student} */
    private function teacherAndScopedStudent(): array
    {
        $teacher = $this->userWithRole('guru_bk');
        $year = AcademicYear::query()->create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $classroom = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 1', 'is_active' => true]);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Scope Koreksi', 'is_active' => true]);
        StudentClassMembership::query()->create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'effective_from' => '2026-07-15', 'is_active' => true]);
        TeacherAssignment::query()->create(['user_id' => $teacher->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'effective_from' => '2026-07-15', 'decision_number' => 'SK-KOREKSI', 'assigned_by' => $teacher->id]);

        return [$teacher, $student];
    }

    private function createCase(User $teacher, Student $student): BkCase
    {
        return app(CaseService::class)->createCase([
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2026-08-01',
            'initial_info' => 'Informasi awal koreksi.',
            'initial_action' => 'Asesmen awal koreksi.',
        ], $teacher);
    }

    private function submitCaseCorrection(User $teacher, BkCase $case): Correction
    {
        return app(CorrectionService::class)->submit([
            'target_type' => 'case', 'target_id' => $case->id, 'field_name' => 'service_date',
            'proposed_value' => '2026-08-02', 'reason' => 'Penyesuaian tanggal layanan.',
        ], $teacher);
    }

    private function successfulDapodikRun(User $admin): ExternalSyncRun
    {
        return ExternalSyncRun::query()->create([
            'source' => 'dapodik', 'status' => ExternalSyncRun::STATUS_SUCCEEDED,
            'is_full_snapshot' => false, 'triggered_by' => $admin->id,
            'received_count' => 1, 'processed_count' => 1, 'conflict_count' => 0,
            'summary' => 'Sinkronisasi pengujian.', 'started_at' => now(), 'finished_at' => now()->addSecond(),
        ]);
    }

    private function reference(string $category, string $code): ReferenceValue
    {
        return ReferenceValue::query()->where('category', $category)->where('code', $code)->firstOrFail();
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
