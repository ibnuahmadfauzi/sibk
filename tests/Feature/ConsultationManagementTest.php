<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\CaseService;
use App\Services\ConsultationService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConsultationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_teacher_creates_consultation_with_physically_separated_private_note_and_safe_audit(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();

        $response = $this->actingAs($teacher)->post(route('consultations.store'), [
            ...$this->payload(),
            'student_id' => $student->id,
            'sensitive_content' => 'ISI-SANGAT-RAHASIA',
            'internal_note' => 'CATATAN-INTERNAL',
            'conclusion' => 'Kesimpulan profesional.',
        ]);

        $consultation = Consultation::query()->firstOrFail();
        $response->assertRedirect(route('consultations.show', $consultation));
        $this->assertMatchesRegularExpression('/^KNS-2026-\d{4}$/', $consultation->registration_number);
        $this->assertDatabaseHas('consultations', ['student_id' => $student->id, 'general_summary' => 'Ringkasan umum yang diizinkan.']);
        $this->assertDatabaseHas('consultation_private_notes', ['consultation_id' => $consultation->id, 'sensitive_content' => 'ISI-SANGAT-RAHASIA']);

        $audit = AuditLog::query()
            ->where('auditable_type', $consultation->getMorphClass())
            ->where('auditable_id', $consultation->getKey())
            ->where('action', 'consultation.created')
            ->firstOrFail();
        $serializedAudit = json_encode([$audit->before_values, $audit->after_values, $audit->summary]);
        $this->assertStringNotContainsString('ISI-SANGAT-RAHASIA', $serializedAudit);
        $this->assertStringNotContainsString('CATATAN-INTERNAL', $serializedAudit);
        $this->assertContains('sensitive_content', $audit->after_values['private_fields_present']);
    }

    public function test_temporary_identity_and_case_identity_invariants_are_enforced(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $otherStudent = Student::query()->create(['nisn' => '0099999999', 'name' => 'Murid Lain', 'is_active' => true]);
        $case = $this->createCase($teacher, $student);

        $this->actingAs($teacher)->from(route('consultations.create'))->post(route('consultations.store'), [
            ...$this->payload(),
            'temporary_nisn' => $student->nisn,
            'temporary_name' => 'Nama Duplikat',
        ])->assertSessionHasErrors('temporary_nisn');

        $this->actingAs($teacher)->from(route('consultations.create'))->post(route('consultations.store'), [
            ...$this->payload(),
            'student_id' => $otherStudent->id,
        ])->assertSessionHasErrors('student_id');

        $this->actingAs($teacher)->from(route('consultations.create'))->post(route('consultations.store'), [
            ...$this->payload(),
            'temporary_nisn' => '0088888888',
            'temporary_name' => 'Identitas Sementara',
            'case_id' => $case->id,
        ])->assertSessionHasErrors('case_id');

        $this->actingAs($teacher)->post(route('consultations.store'), [
            ...$this->payload('dijadwalkan'),
            'temporary_nisn' => '0088888888',
            'temporary_name' => 'Identitas Sementara',
            'general_summary' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('temporary_students', ['nisn' => '0088888888', 'input_name' => 'Identitas Sementara']);
        $this->assertDatabaseCount('consultations', 1);
    }

    public function test_schedule_and_completed_status_validation_use_dynamic_references(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $service = app(ConsultationService::class);

        foreach ([
            [...$this->payload(), 'student_id' => $student->id, 'starts_at' => '10:00', 'ends_at' => '09:00'],
            [...$this->payload(), 'student_id' => $student->id, 'session_date' => today()->addDay()->toDateString()],
            [...$this->payload(), 'student_id' => $student->id, 'general_summary' => null],
            [...$this->payload(), 'student_id' => $student->id, 'follow_up_date' => '2026-08-19'],
        ] as $invalid) {
            try {
                $service->create($invalid, $teacher);
                $this->fail('Payload konsultasi tidak valid seharusnya ditolak.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('consultations', 0);
            }
        }
    }

    public function test_successor_reads_old_private_history_but_cannot_edit_and_roles_are_redacted(): void
    {
        [$firstTeacher, $student, $year, $classroom, $assignment] = $this->teacherAndScopedStudent(true);
        $consultation = app(ConsultationService::class)->create([
            ...$this->payload(),
            'student_id' => $student->id,
            'sensitive_content' => 'HISTORI-PRIVAT-LAMA',
        ], $firstTeacher);
        $successor = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $admin = $this->userWithRole('admin_it');

        $assignment->update(['effective_until' => '2026-08-19']);
        TeacherAssignment::query()->create([
            'user_id' => $successor->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-08-20',
            'decision_number' => 'SK-PENERUS',
            'assigned_by' => $coordinator->id,
        ]);

        $this->actingAs($successor)->get(route('consultations.show', $consultation))->assertOk()->assertSee('HISTORI-PRIVAT-LAMA');
        $this->actingAs($successor)->get(route('consultations.edit', $consultation))->assertForbidden();
        $this->actingAs($firstTeacher)->get(route('consultations.show', $consultation))->assertForbidden();
        $this->actingAs($coordinator)->get(route('consultations.show', $consultation))->assertOk()->assertSee('Ringkasan umum yang diizinkan.')->assertDontSee('HISTORI-PRIVAT-LAMA')->assertDontSee('Edit Data Sesi');
        $this->actingAs($waka)->get(route('consultations.show', $consultation))->assertForbidden();
        $this->actingAs($admin)->get(route('consultations.show', $consultation))->assertForbidden();
    }

    public function test_only_original_author_with_current_authority_can_update_private_fields(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $consultation = app(ConsultationService::class)->create([
            ...$this->payload(), 'student_id' => $student->id, 'sensitive_content' => 'Versi awal',
        ], $teacher);

        $this->actingAs($teacher)->patch(route('consultations.update', $consultation), [
            ...$this->payload(),
            'sensitive_content' => 'Versi diperbarui',
            'internal_note' => 'Rahasia baru',
        ])->assertRedirect(route('consultations.show', $consultation));

        $this->assertDatabaseHas('consultation_private_notes', ['consultation_id' => $consultation->id, 'sensitive_content' => 'Versi diperbarui']);
        $audit = AuditLog::query()->where('action', 'consultation.updated')->firstOrFail();
        $this->assertStringNotContainsString('Versi diperbarui', json_encode($audit->after_values));
    }

    public function test_temporary_identity_history_follows_active_special_case_assignment(): void
    {
        $firstTeacher = $this->userWithRole('guru_bk');
        $successor = $this->userWithRole('guru_bk');
        $case = app(CaseService::class)->createCase([
            'temporary_nisn' => '0077777777',
            'temporary_name' => 'Murid Sementara Kasus',
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2026-08-01',
            'initial_info' => 'Informasi awal.',
            'initial_action' => 'Asesmen awal.',
        ], $firstTeacher);
        $consultation = app(ConsultationService::class)->create([
            ...$this->payload(),
            'temporary_nisn' => '0077777777',
            'temporary_name' => 'Murid Sementara Kasus',
            'case_id' => $case->id,
            'sensitive_content' => 'HISTORI-TEMPORER-PRIVAT',
        ], $firstTeacher);
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $successor->id,
            'assignment_type' => CaseAssignment::TYPE_ADDITIONAL,
            'effective_from' => '2026-08-20',
            'reason' => 'Kewenangan khusus pengujian.',
            'assigned_by' => $firstTeacher->id,
        ]);

        $this->actingAs($successor)->get(route('consultations.show', $consultation))
            ->assertOk()
            ->assertSee('HISTORI-TEMPORER-PRIVAT');
        $this->actingAs($successor)->get(route('consultations.edit', $consultation))->assertForbidden();
    }

    /** @return array{User, Student}|array{User, Student, AcademicYear, Classroom, TeacherAssignment} */
    private function teacherAndScopedStudent(bool $full = false): array
    {
        $teacher = $this->userWithRole('guru_bk');
        $year = AcademicYear::query()->create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $classroom = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 1', 'is_active' => true]);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Scope', 'is_active' => true]);
        StudentClassMembership::query()->create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'effective_from' => '2026-07-15', 'is_active' => true]);
        $assignment = TeacherAssignment::query()->create(['user_id' => $teacher->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'effective_from' => '2026-07-15', 'decision_number' => 'SK-SCOPE', 'assigned_by' => $teacher->id]);

        return $full ? [$teacher, $student, $year, $classroom, $assignment] : [$teacher, $student];
    }

    private function createCase(User $teacher, Student $student): BkCase
    {
        return app(CaseService::class)->createCase([
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2026-08-01',
            'initial_info' => 'Informasi awal.',
            'initial_action' => 'Asesmen awal.',
        ], $teacher);
    }

    /** @return array<string, mixed> */
    private function payload(string $statusCode = 'terlaksana'): array
    {
        return [
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('consultation_status', $statusCode)->id,
            'topic' => 'Penyesuaian diri',
            'referral_source' => 'Inisiatif murid',
            'session_date' => '2026-08-20',
            'starts_at' => '09:00',
            'ends_at' => '10:00',
            'follow_up_date' => '2026-08-22',
            'general_summary' => 'Ringkasan umum yang diizinkan.',
        ];
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
