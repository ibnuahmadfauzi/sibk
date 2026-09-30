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
use App\Models\StudentDeparture;
use App\Models\TeacherAssignment;
use App\Models\TemporaryStudent;
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
            ->assertSee('Catat Konsultasi')
            ->assertSeeInOrder(['Murid dan Layanan', 'Catatan Layanan'], false)
            ->assertDontSee('<h2 class="h5 fw-bold text-dark mb-0">Informasi Layanan</h2>', false)
            ->assertSee('step-badge rounded-circle bg-primary', false)
            ->assertSee('id="student_lookup_results"', false)
            ->assertSee('id="student_nisn"', false)
            ->assertSee('id="student_name"', false)
            ->assertSee('id="manual_classroom_select"', false)
            ->assertSee('id="student_lookup_hint"', false)
            ->assertSee('name="student_id"', false)
            ->assertSee('name="temporary_nisn"', false)
            ->assertSee('name="temporary_name"', false)
            ->assertSee('name="temporary_classroom_id"', false)
            ->assertSee('type="date"', false)
            ->assertSee('value="2026-09-18"', false)
            ->assertSee('data-autosave-form="consultation"', false)
            ->assertSee('data-clear-draft', false)
            ->assertSee('data-draft-status', false)
            ->assertSee('id="btn-save-consultation"', false)
            ->assertSee('Simpan');

        $response = $this->post(route('consultations.store'), [
            ...$this->payload(),
            'student_id' => $student->id,
            'session_date' => '2026-09-17',
        ]);
        $response->assertSessionHasNoErrors();

        $consultation = Consultation::query()->firstOrFail();
        $response->assertRedirect(route('cases.index', ['tab' => 'konsultasi']));
        $response->assertSessionHas('success', 'Konsultasi berhasil dicatat.');
        $this->assertSame($student->id, $consultation->student_id);
        $this->assertNull($consultation->temporary_student_id);
        $this->assertSame('2026-09-17', $consultation->session_date->toDateString());
        $this->assertSame('Kesulitan beradaptasi di kelas.', $consultation->problem);
        $this->assertSame('Asesmen dan konseling individual.', $consultation->handling);
        $this->assertSame('Murid menyepakati langkah perbaikan.', $consultation->result);
    }

    public function test_consultation_lookup_selection_accepts_autofilled_identity_fields(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $classroomId = $student->classMemberships()->firstOrFail()->classroom_id;

        $response = $this->actingAs($teacher)->post(route('consultations.store'), [
            ...$this->payload(),
            'student_id' => $student->id,
            'temporary_nisn' => $student->nisn,
            'temporary_name' => $student->name,
            'temporary_classroom_id' => $classroomId,
            'session_date' => '2026-09-17',
        ]);
        $response->assertSessionHasNoErrors();

        $consultation = Consultation::query()->firstOrFail();
        $this->assertSame($student->id, $consultation->student_id);
        $this->assertNull($consultation->temporary_student_id);
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

    public function test_create_form_restores_temporary_student_input_after_validation_error(): void
    {
        [$teacher] = $this->teacherAndScopedStudent();
        $classroomId = TeacherAssignment::query()->where('user_id', $teacher->id)->value('classroom_id');

        $this->actingAs($teacher)
            ->from(route('consultations.create'))
            ->post(route('consultations.store'), [
                ...$this->payload(),
                'temporary_nisn' => '0098765432',
                'temporary_name' => 'Murid Manual',
                'temporary_classroom_id' => $classroomId,
                'service_field_id' => '',
            ])
            ->assertSessionHasErrors('service_field_id');

        $this->get(route('consultations.create'))
            ->assertOk()
            ->assertSee('value="0098765432"', false)
            ->assertSee('value="Murid Manual"', false)
            ->assertSee('value="'.$classroomId.'" selected', false);
    }

    public function test_unknown_nisn_creates_temporary_identity_and_service(): void
    {
        [$teacher] = $this->teacherAndScopedStudent();

        $this->actingAs($teacher)->post(route('consultations.store'), [
            ...$this->payload(),
            'temporary_nisn' => '0098765432',
            'temporary_name' => 'Murid Belum Sinkron',
            'temporary_classroom_id' => TeacherAssignment::query()->where('user_id', $teacher->id)->value('classroom_id'),
        ])->assertRedirect();

        $this->assertDatabaseHas('temporary_students', [
            'nisn' => '0098765432',
            'input_name' => 'Murid Belum Sinkron',
        ]);
        $this->assertDatabaseHas('consultations', [
            'student_id' => null,
            'problem' => 'Kesulitan beradaptasi di kelas.',
        ]);
        $this->assertNotNull(Consultation::query()->firstOrFail()->classroom_id);
        $this->assertNotNull(Consultation::query()->firstOrFail()->academic_year_id);
    }

    public function test_temporary_consultation_cannot_use_another_teachers_classroom(): void
    {
        [$teacher] = $this->teacherAndScopedStudent();
        $otherTeacher = $this->userWithRole('guru_bk');
        $year = AcademicYear::query()->firstOrFail();
        $otherClassroom = Classroom::query()->create([
            'academic_year_id' => $year->id, 'name' => 'X RPL 2', 'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $otherTeacher->id, 'classroom_id' => $otherClassroom->id,
            'academic_year_id' => $year->id, 'assigned_by' => $otherTeacher->id,
        ]);

        $this->actingAs($teacher)->post(route('consultations.store'), [
            ...$this->payload(),
            'temporary_nisn' => '0099999999',
            'temporary_name' => 'Murid Baru',
            'temporary_classroom_id' => $otherClassroom->id,
        ])->assertSessionHasErrors('temporary_classroom_id');

        $this->assertDatabaseCount('consultations', 0);
        $this->assertDatabaseCount('temporary_students', 0);
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
            ->assertSeeInOrder(['Informasi Umum', 'Nama Murid', 'Tanggal Layanan', 'NISN', 'Jenis Layanan', 'Rombel/Kelas', 'Catatan Permasalahan', 'Latar Belakang', 'Penanganan', 'Ringkasan'], false)
            ->assertSee($consultation->identityName())
            ->assertSee($consultation->identityNisn())
            ->assertSee('18 September 2026')
            ->assertSee('Jumat')
            ->assertSee($consultation->serviceField->label)
            ->assertSee('X RPL 1')
            ->assertSee($consultation->problem)
            ->assertSee($consultation->handling)
            ->assertSee($consultation->result)
            ->assertSee('Kembali ke daftar')
            ->assertSee('Edit')
            ->assertSee('data-confirm-title="Hapus Konsultasi?"', false)
            ->assertSee('data-app-confirm-submit', false);
        $this->get(route('consultations.show', [$consultation, 'modal' => 1]))
            ->assertOk()
            ->assertSee('data-consultation-detail-modal', false)
            ->assertSeeInOrder(['Detail Konsultasi', 'Informasi Umum', 'Catatan Permasalahan', 'Tutup'], false)
            ->assertSee('btn-close', false)
            ->assertSee('data-bs-dismiss="modal"', false)
            ->assertDontSee('<html', false)
            ->assertDontSee('data-modal-url', false)
            ->assertDontSee('Arsipkan', false)
            ->assertDontSee('<div data-consultation-detail-modal', false);
        $this->get(route('consultations.edit', $consultation))
            ->assertOk()
            ->assertViewIs('pages.consultations.create')
            ->assertSeeInOrder(['Edit Konsultasi', 'Informasi Umum', 'Nama Murid', 'Tanggal Layanan', 'NISN', 'Jenis Layanan', 'Rombel/Kelas', 'Catatan Permasalahan', 'Latar Belakang', 'Penanganan', 'Ringkasan', 'Batal', 'Simpan Perubahan'], false)
            ->assertSee('data-autosave-form="consultation"', false)
            ->assertSee('name="session_date"', false)
            ->assertSee('name="service_field_id"', false)
            ->assertSee('name="problem"', false)
            ->assertSee('name="handling"', false)
            ->assertSee('name="result"', false)
            ->assertDontSee('name="student_id"', false)
            ->assertDontSee('name="temporary_student_id"', false)
            ->assertDontSee('name="temporary_nisn"', false)
            ->assertDontSee('name="temporary_name"', false);
        $this->get(route('consultations.edit', [$consultation, 'modal' => 1]))
            ->assertOk()
            ->assertViewIs('pages.consultations._edit-content')
            ->assertSee('data-consultation-edit-modal', false)
            ->assertSee('class="modal-header border-0 pb-2"', false)
            ->assertSee('class="modal-body pt-2 sibk-case-detail sibk-case-edit"', false)
            ->assertSee('data-bs-dismiss="modal"', false)
            ->assertSee('name="expected_updated_at"', false)
            ->assertSee('Simpan Perubahan')
            ->assertDontSee('name="temporary_nisn"', false)
            ->assertDontSee('<html', false)
            ->assertDontSee('<div data-consultation-edit-modal', false);
    }

    public function test_reconciled_temporary_identity_is_excluded_after_official_departure(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $temporary = $this->reconciledTemporary($owner, $student);
        $this->officialDeparture($owner, $student, '2026-09-17');
        $consultation = $this->consultationForTemporary($owner, $temporary, '2026-09-18');

        $this->assertFalse(
            Consultation::query()->accessibleTo($owner)->whereKey($consultation->getKey())->exists(),
        );
    }

    public function test_reconciled_temporary_identity_cannot_create_or_move_service_on_or_after_departure(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $temporary = $this->reconciledTemporary($owner, $student);
        $this->officialDeparture($owner, $student, '2026-09-17');

        $this->actingAs($owner)->post(route('consultations.store'), [
            ...$this->payload(),
            'temporary_student_id' => $temporary->id,
            'session_date' => '2026-09-17',
        ])->assertSessionHasErrors('session_date');

        $consultation = $this->consultationForTemporary($owner, $temporary, '2026-09-16');
        $this->actingAs($owner)->patch(route('consultations.update', $consultation), [
            ...$this->payload(),
            'session_date' => '2026-09-17',
            'expected_updated_at' => $consultation->updated_at->toJSON(),
        ])->assertSessionHasErrors('session_date');

        $this->assertSame('2026-09-16', $consultation->refresh()->session_date->toDateString());
    }

    public function test_direct_routes_reject_out_of_scope_roles_and_owner_who_lost_authority(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $consultation = $this->createConsultation($owner, $student);
        $payload = [
            ...$this->payload(),
            'expected_updated_at' => $consultation->updated_at->toJSON(),
        ];

        foreach (['guru_bk', 'admin_it'] as $role) {
            $actor = $this->userWithRole($role);
            $this->actingAs($actor)->get(route('consultations.show', $consultation))->assertForbidden();
        }

        foreach (['koordinator_bk', 'waka_kesiswaan'] as $role) {
            $actor = $this->userWithRole($role);
            $this->actingAs($actor)->get(route('consultations.show', $consultation))->assertOk();
        }

        foreach (['guru_bk', 'koordinator_bk', 'waka_kesiswaan', 'admin_it'] as $role) {
            $actor = $this->userWithRole($role);
            $this->get(route('consultations.edit', $consultation))->assertForbidden();
            $this->patch(route('consultations.update', $consultation), $payload)->assertForbidden();
            $this->delete(route('consultations.destroy', $consultation))->assertForbidden();
        }

        $replacement = $this->userWithRole('guru_bk');
        TeacherAssignment::query()->where('user_id', $owner->id)->update(['user_id' => $replacement->id]);
        $this->actingAs($owner)->get(route('consultations.show', $consultation))->assertForbidden();
        $this->get(route('consultations.edit', $consultation))->assertForbidden();
        $this->patch(route('consultations.update', $consultation), $payload)->assertForbidden();
        $this->delete(route('consultations.destroy', $consultation))->assertForbidden();
        $this->assertNotSoftDeleted('consultations', ['id' => $consultation->id]);
    }

    public function test_modal_mutations_return_list_redirect_as_json(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $consultation = $this->createConsultation($owner, $student);
        $listUrl = route('cases.index', ['tab' => 'konsultasi']);

        $this->actingAs($owner)->patchJson(route('consultations.update', $consultation), [
            ...$this->payload(),
            'problem' => 'Permasalahan modal diperbarui.',
            'expected_updated_at' => $consultation->updated_at->toJSON(),
        ])->assertOk()
            ->assertJsonPath('redirect', $listUrl)
            ->assertSessionHas('success', 'Konsultasi berhasil diperbarui.');

        $this->deleteJson(route('consultations.destroy', $consultation))
            ->assertOk()
            ->assertJsonPath('redirect', $listUrl);
        $this->assertSoftDeleted('consultations', ['id' => $consultation->id]);
    }

    public function test_shared_list_searches_only_name_filters_service_field_and_uses_allowed_stable_sorts(): void
    {
        [$teacher, $zeta] = $this->teacherAndScopedStudent();
        $coordinator = $this->userWithRole('koordinator_bk');
        $zeta->update(['name' => 'Murid Zeta']);
        $year = AcademicYear::query()->firstOrFail();
        $alphaClass = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'X AP 1',
            'is_active' => true,
        ]);
        $alpha = Student::query()->create(['nisn' => '0012345679', 'name' => 'Murid Alpha', 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $alpha->id,
            'classroom_id' => $alphaClass->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $beta = Student::query()->create(['nisn' => '0012345680', 'name' => 'Murid Beta', 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $beta->id,
            'classroom_id' => $alphaClass->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $pribadi = ReferenceValue::query()->where('category', 'service_field')->where('code', 'pribadi')->firstOrFail();
        $sosial = ReferenceValue::query()->where('category', 'service_field')->where('code', 'sosial')->firstOrFail();
        $listedZeta = $this->listedConsultation($teacher, $zeta, $sosial, '2026-09-18');
        $this->listedConsultation($teacher, $alpha, $pribadi, '2026-09-17');
        $this->listedConsultation($teacher, $beta, $pribadi, '2026-09-17');

        $this->actingAs($coordinator)->get(route('cases.index', ['tab' => 'konsultasi', 'search' => $zeta->nisn]))
            ->assertOk()
            ->assertDontSee('Murid Zeta');
        $this->get(route('cases.index', ['tab' => 'konsultasi', 'service_field_id' => $pribadi->id]))
            ->assertOk()
            ->assertSee('Murid Alpha')
            ->assertDontSee('Murid Zeta');

        foreach ([
            ['tanggal', 'asc', ['Murid Alpha', 'Murid Beta', 'Murid Zeta']],
            ['nama', 'desc', ['Murid Zeta', 'Murid Beta', 'Murid Alpha']],
            ['kelas', 'asc', ['Murid Alpha', 'Murid Beta', 'Murid Zeta']],
            ['jenis_layanan', 'asc', ['Murid Alpha', 'Murid Beta', 'Murid Zeta']],
        ] as [$sort, $direction, $names]) {
            $this->get(route('cases.index', compact('sort', 'direction') + ['tab' => 'konsultasi']))
                ->assertOk()
                ->assertSeeInOrder($names);
        }

        $this->get(route('cases.index', ['tab' => 'konsultasi']))
            ->assertSeeInOrder(['Hari/Tanggal', 'Nama & Kelas', 'Jenis Layanan', 'Aksi'], false)
            ->assertSee('Jumat')
            ->assertSee('18 Sep 2026')
            ->assertDontSee('Hasil Murid Zeta')
            ->assertSee('data-modal-url="'.route('consultations.show', [$listedZeta, 'modal' => 1]).'"', false)
            ->assertSee('aria-label="Lihat detail konsultasi '.$listedZeta->identityName().'"', false)
            ->assertDontSee('<th scope="col">No</th>', false)
            ->assertDontSee('<th scope="col">Hasil</th>', false)
            ->assertDontSee('data-report-detail-toggle', false);
    }

    public function test_shared_list_keeps_class_snapshot_after_membership_change(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $coordinator = $this->userWithRole('koordinator_bk');
        $year = AcademicYear::query()->firstOrFail();
        $consultation = $this->createConsultation($teacher, $student);
        $historicalClass = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'X RPL Historis',
            'is_active' => true,
        ]);
        $student->classMemberships()->firstOrFail()->update(['classroom_id' => $historicalClass->id]);

        $this->actingAs($coordinator)->get(route('cases.index', ['tab' => 'konsultasi']))
            ->assertOk()
            ->assertSee('X RPL 1')
            ->assertDontSee('X RPL Historis');
        $this->assertSame($consultation->classroom_id, $consultation->fresh()->classroom_id);
    }

    public function test_shared_list_reuses_case_table_and_actions(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $consultation = $this->createConsultation($teacher, $student);

        $this->actingAs($teacher)->get(route('cases.index', ['tab' => 'konsultasi']))
            ->assertOk()
            ->assertSeeInOrder(['Hari/Tanggal', 'Nama & Kelas', 'Jenis Layanan', 'Aksi'], false)
            ->assertSee('class="table-responsive"', false)
            ->assertSee('class="btn btn-icon-action btn-icon-action--info"', false)
            ->assertSee('class="btn btn-icon-action btn-icon-action--primary"', false)
            ->assertSee('class="btn btn-icon-action btn-icon-action--danger"', false)
            ->assertSee('data-modal-url="'.route('consultations.show', [$consultation, 'modal' => 1]).'"', false)
            ->assertSee('data-modal-url="'.route('consultations.edit', [$consultation, 'modal' => 1]).'"', false)
            ->assertSee('aria-label="Lihat detail konsultasi '.$consultation->identityName().'"', false)
            ->assertSee('aria-label="Edit konsultasi '.$consultation->identityName().'"', false)
            ->assertSee('aria-label="Arsipkan konsultasi '.$consultation->identityName().'"', false)
            ->assertSee('data-app-confirm-submit', false)
            ->assertDontSee('data-confirm-submit', false)
            ->assertDontSee('class="sibk-panel sibk-operational-report"', false)
            ->assertDontSee('class="sibk-report-summary"', false)
            ->assertDontSee('class="sibk-operational-report-cards p-3"', false)
            ->assertDontSee('data-report-detail-toggle', false)
            ->assertDontSee('class="sibk-report-detail-row d-none"', false);

        $coordinator = $this->userWithRole('koordinator_bk');
        $this->actingAs($coordinator)->get(route('cases.index', ['tab' => 'konsultasi']))
            ->assertOk()
            ->assertSee('aria-label="Lihat detail konsultasi '.$consultation->identityName().'"', false)
            ->assertDontSee('aria-label="Edit konsultasi '.$consultation->identityName().'"', false)
            ->assertDontSee('aria-label="Arsipkan konsultasi '.$consultation->identityName().'"', false);
    }

    public function test_waka_list_keeps_four_columns_and_detail_only_without_exposing_result(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $consultation = $this->createConsultation($teacher, $student);
        $waka = $this->userWithRole('waka_kesiswaan');

        $this->actingAs($waka)->get(route('cases.index', ['tab' => 'konsultasi']))
            ->assertOk()
            ->assertSeeInOrder(['Hari/Tanggal', 'Nama & Kelas', 'Jenis Layanan', 'Aksi'], false)
            ->assertSee('data-modal-url="'.route('consultations.show', [$consultation, 'modal' => 1]).'"', false)
            ->assertSee('aria-label="Lihat detail konsultasi '.$consultation->identityName().'"', false)
            ->assertDontSee('aria-label="Edit konsultasi '.$consultation->identityName().'"', false)
            ->assertDontSee('aria-label="Arsipkan konsultasi '.$consultation->identityName().'"', false)
            ->assertDontSee($consultation->result);

        $this->get(route('consultations.show', [$consultation, 'modal' => 1]))
            ->assertOk()
            ->assertSee('data-consultation-detail-modal', false);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'consultation.viewed_by_waka',
            'auditable_type' => Consultation::class,
            'auditable_id' => $consultation->id,
            'actor_id' => $waka->id,
        ]);
    }

    public function test_previous_year_consultation_is_read_only(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $consultation = $this->createConsultation($teacher, $student);
        AcademicYear::query()->whereKey($consultation->academic_year_id)->update(['is_active' => false]);
        $nextYear = AcademicYear::query()->create(['name' => '2027/2028', 'is_active' => true]);
        $nextClassroom = Classroom::query()->create([
            'academic_year_id' => $nextYear->id, 'name' => 'XI RPL 1', 'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id, 'classroom_id' => $nextClassroom->id,
            'academic_year_id' => $nextYear->id, 'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id, 'classroom_id' => $nextClassroom->id,
            'academic_year_id' => $nextYear->id, 'assigned_by' => $teacher->id,
        ]);

        $this->actingAs($teacher)->get(route('consultations.show', $consultation))->assertOk();
        $this->assertFalse($teacher->can('update', $consultation->fresh()));
        $this->actingAs($teacher)->patch(route('consultations.update', $consultation), [
            ...$this->payload(),
            'student_id' => $student->id,
            'expected_updated_at' => $consultation->updated_at->toJSON(),
        ])->assertForbidden();
        $this->actingAs($teacher)->delete(route('consultations.destroy', $consultation))->assertForbidden();
        $this->actingAs($teacher)->get(route('cases.index', ['tab' => 'konsultasi']))
            ->assertOk()
            ->assertSee('aria-label="Lihat detail konsultasi '.$consultation->identityName().'"', false)
            ->assertDontSee('aria-label="Edit konsultasi '.$consultation->identityName().'"', false)
            ->assertDontSee('aria-label="Arsipkan konsultasi '.$consultation->identityName().'"', false);
        $this->assertDatabaseHas('consultations', ['id' => $consultation->id, 'deleted_at' => null]);
    }

    /** @return array{User, Student} */
    private function teacherAndScopedStudent(): array
    {
        $teacher = $this->userWithRole('guru_bk');
        $year = AcademicYear::query()->create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $classroom = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 1', 'is_active' => true]);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Scope', 'is_active' => true]);
        StudentClassMembership::query()->create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        TeacherAssignment::query()->create(['user_id' => $teacher->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'assigned_by' => $teacher->id]);

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

    private function listedConsultation(User $teacher, Student $student, ReferenceValue $serviceField, string $date): Consultation
    {
        $consultation = new Consultation([
            'student_id' => $student->id,
            'academic_year_id' => $student->classMemberships()->firstOrFail()->academic_year_id,
            'classroom_id' => $student->classMemberships()->firstOrFail()->classroom_id,
            'service_field_id' => $serviceField->id,
            'session_date' => $date,
            'problem' => 'Permasalahan '.$student->name,
            'handling' => 'Penanganan '.$student->name,
            'result' => 'Hasil '.$student->name,
            'counselor_id' => $teacher->id,
        ]);
        $consultation->save();

        return $consultation;
    }

    private function reconciledTemporary(User $creator, Student $student): TemporaryStudent
    {
        return TemporaryStudent::query()->create([
            'nisn' => $student->nisn,
            'input_name' => 'Nama Sebelum Rekonsiliasi',
            'created_by' => $creator->id,
            'reconciliation_status_id' => ReferenceValue::query()
                ->where('category', 'reconciliation_status')
                ->where('code', 'terekonsiliasi')
                ->firstOrFail()->id,
            'reconciled_student_id' => $student->id,
            'reconciled_by' => $creator->id,
            'reconciled_at' => now(),
        ]);
    }

    private function officialDeparture(User $teacher, Student $student, string $date): void
    {
        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'status' => StudentDeparture::STATUS_OFFICIAL,
            'reported_at' => '2026-09-16',
            'effective_date' => $date,
            'recorded_by' => $teacher->id,
            'finalized_by' => $this->userWithRole('koordinator_bk')->id,
            'finalized_at' => now(),
        ]);
    }

    private function consultationForTemporary(User $teacher, TemporaryStudent $temporary, string $date): Consultation
    {
        $consultation = new Consultation([
            ...$this->payload(),
            'academic_year_id' => AcademicYear::query()->active()->value('id'),
            'temporary_student_id' => $temporary->id,
            'session_date' => $date,
            'counselor_id' => $teacher->id,
        ]);
        $consultation->save();

        return $consultation;
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
