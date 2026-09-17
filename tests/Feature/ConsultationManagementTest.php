<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
        $this->travelTo('2026-09-18 09:00:00');
    }

    public function test_teacher_creates_completed_service_for_exact_local_student_on_editable_date(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();

        $this->actingAs($teacher)->get(route('consultations.create'))
            ->assertOk()
            ->assertSee('type="date"', false)
            ->assertSee('value="2026-09-18"', false);

        $response = $this->post(route('consultations.store'), [
            ...$this->payload(),
            'student_id' => $student->id,
            'session_date' => '2026-09-17',
        ]);
        $response->assertSessionHasNoErrors();

        $consultation = Consultation::query()->firstOrFail();
        $response->assertRedirect(route('consultations.show', $consultation));
        $this->assertSame($student->id, $consultation->student_id);
        $this->assertNull($consultation->temporary_student_id);
        $this->assertSame('2026-09-17', $consultation->session_date->toDateString());
        $this->assertSame('Kesulitan beradaptasi di kelas.', $consultation->problem);
        $this->assertSame('Asesmen dan konseling individual.', $consultation->handling);
        $this->assertSame('Murid menyepakati langkah perbaikan.', $consultation->result);
        $this->assertDatabaseCount('consultation_private_notes', 0);
    }

    public function test_exact_master_nisn_is_not_duplicated_as_temporary_identity(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();

        $this->actingAs($teacher)->from(route('consultations.create'))->post(route('consultations.store'), [
            ...$this->payload(),
            'temporary_nisn' => $student->nisn,
            'temporary_name' => 'Nama tidak boleh menimpa master',
        ])->assertSessionHasErrors('temporary_nisn');

        $this->assertDatabaseCount('consultations', 0);
        $this->assertDatabaseCount('temporary_students', 0);
        $this->assertSame('Murid Scope', $student->refresh()->name);
    }

    public function test_unknown_nisn_creates_temporary_identity_and_service(): void
    {
        [$teacher] = $this->teacherAndScopedStudent();

        $this->actingAs($teacher)->post(route('consultations.store'), [
            ...$this->payload(),
            'temporary_nisn' => '0098765432',
            'temporary_name' => 'Murid Belum Sinkron',
        ])->assertRedirect();

        $this->assertDatabaseHas('temporary_students', [
            'nisn' => '0098765432',
            'input_name' => 'Murid Belum Sinkron',
        ]);
        $this->assertDatabaseHas('consultations', [
            'student_id' => null,
            'problem' => 'Kesulitan beradaptasi di kelas.',
        ]);
    }

    public function test_all_three_narratives_are_required_and_limited_to_ten_thousand_characters(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();

        foreach (['problem', 'handling', 'result'] as $field) {
            $this->actingAs($teacher)->from(route('consultations.create'))->post(route('consultations.store'), [
                ...$this->payload(),
                'student_id' => $student->id,
                $field => '',
            ])->assertSessionHasErrors($field);

            $this->actingAs($teacher)->from(route('consultations.create'))->post(route('consultations.store'), [
                ...$this->payload(),
                'student_id' => $student->id,
                $field => str_repeat('a', 10001),
            ])->assertSessionHasErrors($field);
        }

        $this->assertDatabaseCount('consultations', 0);
    }

    public function test_owner_updates_five_service_fields_but_cannot_change_identity(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $otherStudent = Student::query()->create(['nisn' => '0099999999', 'name' => 'Murid Lain', 'is_active' => true]);
        $consultation = $this->createConsultation($owner, $student);

        $this->actingAs($owner)->patch(route('consultations.update', $consultation), [
            ...$this->payload(),
            'student_id' => $otherStudent->id,
            'expected_updated_at' => $consultation->updated_at->toJSON(),
        ])->assertSessionHasErrors('student_id');

        $response = $this->actingAs($owner)->patchJson(route('consultations.update', $consultation), [
            ...$this->payload(),
            'session_date' => '2026-09-16',
            'problem' => 'Permasalahan diperbarui.',
            'handling' => 'Penanganan diperbarui.',
            'result' => 'Hasil diperbarui.',
            'expected_updated_at' => $consultation->updated_at->toJSON(),
        ]);

        $response->assertOk()->assertJsonPath('message', 'Konsultasi berhasil diperbarui.');
        $consultation->refresh();
        $this->assertSame($student->id, $consultation->student_id);
        $this->assertSame('2026-09-16', $consultation->session_date->toDateString());
        $this->assertSame('Permasalahan diperbarui.', $consultation->problem);
    }

    public function test_stale_update_is_rejected_and_successful_update_audits_only_changed_fields(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $consultation = $this->createConsultation($owner, $student);
        $staleTimestamp = $consultation->updated_at->toJSON();
        $consultation->forceFill(['updated_at' => $consultation->updated_at->addSecond()])->saveQuietly();

        $this->actingAs($owner)->patchJson(route('consultations.update', $consultation), [
            ...$this->payload(),
            'problem' => 'Perubahan stale.',
            'expected_updated_at' => $staleTimestamp,
        ])->assertUnprocessable()->assertJsonValidationErrors('expected_updated_at');

        $this->actingAs($owner)->patchJson(route('consultations.update', $consultation), [
            ...$this->payload(),
            'problem' => 'Permasalahan terbaru.',
            'expected_updated_at' => $consultation->refresh()->updated_at->toJSON(),
        ])->assertOk();

        $audit = AuditLog::query()->where('action', 'consultation.updated')->latest('id')->firstOrFail();
        $this->assertSame(['problem' => 'Kesulitan beradaptasi di kelas.'], $audit->before_values);
        $this->assertSame(['problem' => 'Permasalahan terbaru.'], $audit->after_values);
    }

    public function test_only_authorized_owner_can_archive_with_soft_delete(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $otherTeacher = $this->userWithRole('guru_bk');
        $consultation = $this->createConsultation($owner, $student);

        $this->actingAs($otherTeacher)->delete(route('consultations.destroy', $consultation))->assertForbidden();
        $this->actingAs($owner)->delete(route('consultations.destroy', $consultation))
            ->assertRedirect(route('cases.index', ['tab' => 'konsultasi']));

        $this->assertSoftDeleted('consultations', ['id' => $consultation->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'consultation.archived',
            'auditable_id' => $consultation->id,
        ]);
    }

    public function test_detail_and_edit_support_modal_partials_and_full_page_fallbacks(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $consultation = $this->createConsultation($owner, $student);

        $this->actingAs($owner)->get(route('consultations.show', $consultation))
            ->assertOk()
            ->assertSee('Detail Konsultasi')
            ->assertSee('Permasalahan')
            ->assertSee('Penanganan')
            ->assertSee('Hasil')
            ->assertSee('Arsipkan konsultasi ini?');
        $this->get(route('consultations.show', [$consultation, 'modal' => 1]))
            ->assertOk()
            ->assertSee('data-consultation-detail-modal', false)
            ->assertDontSee('<html', false);
        $this->get(route('consultations.edit', $consultation))
            ->assertOk()
            ->assertSee('Layanan ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?')
            ->assertDontSee('name="student_id"', false);
        $this->get(route('consultations.edit', [$consultation, 'modal' => 1]))
            ->assertOk()
            ->assertSee('data-consultation-edit-modal', false)
            ->assertSee('name="expected_updated_at"', false)
            ->assertDontSee('name="temporary_nisn"', false);
    }

    /** @return array{User, Student} */
    private function teacherAndScopedStudent(): array
    {
        $teacher = $this->userWithRole('guru_bk');
        $year = AcademicYear::query()->create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $classroom = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 1', 'is_active' => true]);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Scope', 'is_active' => true]);
        StudentClassMembership::query()->create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'effective_from' => '2026-07-15', 'is_active' => true]);
        TeacherAssignment::query()->create(['user_id' => $teacher->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'effective_from' => '2026-07-15', 'decision_number' => 'SK-SCOPE', 'assigned_by' => $teacher->id]);

        return [$teacher, $student];
    }

    private function createConsultation(User $teacher, Student $student): Consultation
    {
        $this->actingAs($teacher)->post(route('consultations.store'), [
            ...$this->payload(),
            'student_id' => $student->id,
        ])->assertRedirect();

        return Consultation::query()->latest('id')->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'service_field_id' => ReferenceValue::query()->where('category', 'service_field')->where('code', 'pribadi')->firstOrFail()->id,
            'session_date' => '2026-09-18',
            'problem' => 'Kesulitan beradaptasi di kelas.',
            'handling' => 'Asesmen dan konseling individual.',
            'result' => 'Murid menyepakati langkah perbaikan.',
        ];
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
