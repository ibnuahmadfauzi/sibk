<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\CaseCoordination;
use App\Models\Classroom;
use App\Models\Correction;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\CaseService;
use App\Services\CorrectionService;
use App\Services\ReportService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-08-20 10:00:00');
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_teacher_can_create_scoped_achievement_with_metadata_evidence_and_safe_audit(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent('Murid Prestasi Utama', '0012345678');
        $response = $this->actingAs($teacher)->post(route('achievements.store'), $this->payload($student));
        $achievement = Achievement::query()->firstOrFail();

        $response->assertRedirect(route('achievements.show', $achievement));
        $this->assertSame('menunggu', $achievement->verificationStatus->code);
        $this->assertSame($teacher->id, $achievement->recorded_by);
        $audit = AuditLog::query()->where('action', 'achievement.created')->firstOrFail();
        $this->assertStringNotContainsString('ARSIP/RAHASIA/001', json_encode($audit->after_values, JSON_THROW_ON_ERROR));
        $this->actingAs($teacher)->get(route('achievements.show', $achievement))
            ->assertOk()->assertSee('ARSIP/RAHASIA/001')->assertSee('Menunggu Verifikasi');
    }

    public function test_creation_rejects_out_of_scope_inactive_future_and_missing_evidence(): void
    {
        [$teacher] = $this->teacherAndScopedStudent('Murid Dalam Scope', '0011111111');
        [, $outside] = $this->teacherAndScopedStudent('Murid Luar Scope', '0022222222');

        $this->actingAs($teacher)->post(route('achievements.store'), $this->payload($outside))
            ->assertSessionHasErrors('student_id');
        $outside->update(['is_active' => false]);
        $this->actingAs($teacher)->post(route('achievements.store'), $this->payload($outside))
            ->assertSessionHasErrors('student_id');
        $payload = $this->payload(Student::query()->where('nisn', '0011111111')->firstOrFail());
        $payload['achievement_date'] = '2026-08-21';
        $payload['evidence_reference'] = '';
        $this->actingAs($teacher)->post(route('achievements.store'), $payload)
            ->assertSessionHasErrors(['achievement_date', 'evidence_reference']);
        $this->assertDatabaseCount('achievements', 0);
    }

    public function test_only_original_teacher_can_edit_pending_record_and_review_is_final(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent('Murid Edit Prestasi', '0033333333');
        $successor = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $achievement = app(AchievementService::class)->create($this->payload($student), $teacher);
        $update = $this->payload($student);
        unset($update['student_id']);
        $update['result'] = 'Juara I';

        $this->actingAs($successor)->patch(route('achievements.update', $achievement), $update)->assertForbidden();
        $this->actingAs($teacher)->patch(route('achievements.update', $achievement), $update)->assertRedirect();
        $this->assertSame('Juara I', $achievement->refresh()->result);
        $this->actingAs($coordinator)->post(route('achievements.verify', $achievement), ['decision' => 'ditolak'])
            ->assertSessionHasErrors('verification_notes');
        $this->actingAs($coordinator)->post(route('achievements.verify', $achievement), [
            'decision' => 'terverifikasi', 'verification_notes' => 'Bukti telah diperiksa.',
        ])->assertRedirect();
        $this->assertSame('terverifikasi', $achievement->refresh()->verificationStatus->code);
        $this->actingAs($teacher)->patch(route('achievements.update', $achievement), $update)->assertForbidden();
        $this->actingAs($coordinator)->post(route('achievements.verify', $achievement), ['decision' => 'ditolak', 'verification_notes' => 'Ulang'])
            ->assertForbidden();
    }

    public function test_role_matrix_profile_and_waka_only_expose_verified_coordinated_achievement(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent('Murid Koordinasi Prestasi', '0044444444');
        $coordinator = $this->userWithRole('koordinator_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $admin = $this->userWithRole('admin_it');
        $case = $this->caseFor($teacher, $student);
        CaseCoordination::query()->create([
            'case_id' => $case->id, 'waka_user_id' => $waka->id,
            'status_id' => $this->reference('coordination_status', 'menunggu')->id,
            'coordination_need' => 'Koordinasi prestasi murid.', 'recorded_by' => $teacher->id, 'coordinated_at' => now(),
        ]);
        $pending = app(AchievementService::class)->create($this->payload($student, 'Prestasi Menunggu'), $teacher);

        $this->actingAs($waka)->get(route('achievements.show', $pending))->assertForbidden();
        app(AchievementService::class)->verify($pending, ['decision' => 'terverifikasi'], $coordinator);
        $this->actingAs($waka)->get(route('students.show', ['student' => $student, 'tab' => 'prestasi']))
            ->assertOk()->assertSee('Prestasi Menunggu')->assertDontSee('Catat Prestasi');
        $this->actingAs($admin)->get(route('achievements.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('achievements.show', $pending))->assertForbidden();
        $this->actingAs($coordinator)->get(route('achievements.index'))->assertOk()->assertSee('Prestasi Menunggu');
        app(AchievementService::class)->create($this->payload($student, 'Prestasi Belum Disahkan'), $teacher);
        $wakaReport = app(ReportService::class)->build($waka, ['type' => ReportService::TYPE_ACHIEVEMENTS], false);
        $this->assertCount(1, $wakaReport['rows']);
        $this->assertStringNotContainsString('Prestasi Belum Disahkan', collect($wakaReport['rows']->first()['cells'])->pluck('value')->join('|'));
    }

    public function test_multi_role_uses_teacher_scope_for_creation_and_coordinator_function_for_review(): void
    {
        [$multiRole, $student] = $this->teacherAndScopedStudent('Murid Multi Role', '0066666666');
        $multiRole->roles()->attach(Role::query()->where('slug', 'koordinator_bk')->firstOrFail());

        $this->actingAs($multiRole)->post(route('achievements.store'), $this->payload($student, 'Prestasi Multi Role'))->assertRedirect();
        $achievement = Achievement::query()->where('activity_name', 'Prestasi Multi Role')->firstOrFail();
        $this->actingAs($multiRole)->post(route('achievements.verify', $achievement), [
            'decision' => 'terverifikasi',
        ])->assertRedirect();
        $this->assertSame('terverifikasi', $achievement->refresh()->verificationStatus->code);
        $this->assertSame($multiRole->id, $achievement->reviewer_id);
    }

    public function test_verified_achievement_correction_uses_service_and_report_is_redacted(): void
    {
        [$teacher, $student, $classroom] = $this->teacherAndScopedStudent('Nama Lengkap Prestasi Rahasia', '0055555555');
        $coordinator = $this->userWithRole('koordinator_bk');
        $achievement = app(AchievementService::class)->create($this->payload($student, '=Prestasi Formula'), $teacher);
        app(AchievementService::class)->verify($achievement, ['decision' => 'terverifikasi'], $coordinator);
        $correction = app(CorrectionService::class)->submit([
            'target_type' => 'achievement', 'target_id' => $achievement->id, 'field_name' => 'result',
            'proposed_value' => 'Juara Umum', 'reason' => 'Hasil resmi telah dikonfirmasi.',
        ], $teacher);
        app(CorrectionService::class)->verifyOperational($correction, ['decision' => 'approved'], $coordinator);
        $this->assertSame('Juara Umum', $achievement->refresh()->result);
        $this->assertDatabaseHas('audit_logs', ['action' => 'achievement.corrected', 'auditable_id' => $achievement->id]);

        $report = app(ReportService::class)->build($teacher, [
            'type' => ReportService::TYPE_ACHIEVEMENTS,
            'classroom_id' => $classroom->id,
            'achievement_type_id' => $achievement->type_id,
            'achievement_level_id' => $achievement->level_id,
            'status_id' => $achievement->verification_status_id,
        ], false);
        $this->assertCount(1, $report['rows']);
        $values = collect($report['rows']->first()['cells'])->pluck('value')->join('|');
        $this->assertStringContainsString('N.L.P.R.', $values);
        $this->assertStringNotContainsString($student->name, $values);
        $this->assertStringNotContainsString($student->nisn, $values);
        $this->assertStringNotContainsString('ARSIP/RAHASIA/001', $values);

        $csv = $this->actingAs($teacher)->get(route('reports.export', ['type' => ReportService::TYPE_ACHIEVEMENTS, 'format' => 'csv']))
            ->assertOk()->assertDownload()->streamedContent();
        $this->assertStringContainsString("'=Prestasi Formula", $csv);
        $this->assertStringNotContainsString($student->name, $csv);
        $this->assertStringNotContainsString('ARSIP/RAHASIA/001', $csv);
        $this->assertInstanceOf(Correction::class, $correction);
    }

    /** @return array{User, Student, Classroom} */
    private function teacherAndScopedStudent(string $name, string $nisn): array
    {
        $teacher = $this->userWithRole('guru_bk');
        $year = AcademicYear::query()->firstOrCreate(
            ['name' => '2026/2027'],
            ['starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true],
        );
        $classroom = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'Kelas Prestasi '.(Student::query()->count() + 1), 'is_active' => true]);
        $student = Student::query()->create(['nisn' => $nisn, 'name' => $name, 'is_active' => true]);
        StudentClassMembership::query()->create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'effective_from' => '2026-07-01', 'is_active' => true]);
        TeacherAssignment::query()->create(['user_id' => $teacher->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'effective_from' => '2026-07-01', 'decision_number' => 'SK-'.$nisn, 'assigned_by' => $teacher->id]);

        return [$teacher, $student, $classroom];
    }

    /** @return array<string, mixed> */
    private function payload(Student $student, string $activity = 'Olimpiade Kompetensi Murid'): array
    {
        return [
            'student_id' => $student->id,
            'type_id' => $this->reference('achievement_type', 'akademik')->id,
            'level_id' => $this->reference('achievement_level', 'nasional')->id,
            'activity_name' => $activity,
            'organizer' => 'Pusat Prestasi Nasional',
            'achievement_date' => '2026-08-10',
            'result' => 'Juara II',
            'evidence_reference' => 'ARSIP/RAHASIA/001',
            'evidence_description' => 'Referensi dokumen fisik.',
            'notes' => 'Catatan internal prestasi.',
        ];
    }

    private function caseFor(User $teacher, Student $student): BkCase
    {
        return app(CaseService::class)->createCase([
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2026-08-01', 'initial_info' => 'Informasi awal.', 'initial_action' => 'Asesmen awal.',
        ], $teacher);
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
