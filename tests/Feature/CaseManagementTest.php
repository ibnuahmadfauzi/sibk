<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\StoreCaseRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ExternalTatibRecord;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\StudentDeparture;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\StudentIdentityService;
use App\Services\WakaStudentCaseService;
use App\Support\ServiceRecordStatus;
use Carbon\Carbon;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_etatib_case_requires_local_record_and_starts_in_progress(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $student = $this->scopedStudent($teacher);

        $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload('e_tatib'),
            'student_id' => $student->id,
        ])->assertSessionHasErrors('etatib_record_ids');

        $record = $this->etatibRecord($student);
        $response = $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload('e_tatib'),
            'student_id' => $student->id,
            'etatib_record_ids' => [$record->id],
        ]);

        $case = BkCase::query()->firstOrFail();
        $response->assertRedirect(route('cases.index', ['tab' => 'kasus']));
        $this->assertSame(ServiceRecordStatus::IN_PROGRESS, $case->status->code);
        $this->assertDatabaseHas('case_etatib_links', [
            'case_id' => $case->id,
            'external_tatib_record_id' => $record->id,
        ]);
    }

    public function test_manual_exact_nisn_uses_official_student_without_overwriting_name(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $student = $this->scopedStudent($teacher, 'Nama Resmi', '0012345678');

        $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload(),
            'temporary_nisn' => $student->nisn,
            'temporary_name' => 'Nama Ketikan Berbeda',
        ])->assertRedirect();

        $case = BkCase::query()->firstOrFail();
        $this->assertSame($student->id, $case->student_id);
        $this->assertNull($case->temporary_student_id);
        $this->assertSame('Nama Resmi', $student->refresh()->name);
    }

    public function test_create_form_exposes_lookup_for_students_in_teacher_scope_only(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $otherTeacher = $this->userWithRole('guru_bk');
        $student = $this->scopedStudent($teacher, 'Murid Dalam Scope', '0011111111');
        $this->scopedStudent($otherTeacher, 'Murid Luar Scope', '0022222222', 'XI RPL 2');

        $this->actingAs($teacher)->get(route('cases.create', ['modal' => 1]))
            ->assertOk()
            ->assertViewIs('pages.cases._create-modal')
            ->assertDontSee('Kembali ke daftar kasus')
            ->assertSee('aria-controls="student_lookup_results"', false)
            ->assertSee('id="student_lookup_results"', false)
            ->assertSee('Murid Dalam Scope')
            ->assertSee($student->nisn)
            ->assertSee('X RPL 1')
            ->assertDontSee('Murid Luar Scope')
            ->assertDontSee('0022222222');
    }

    public function test_legacy_create_route_redirects_to_modal_context(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $student = $this->scopedStudent($teacher);
        $this->actingAs($teacher)->get(route('cases.create', ['student_id' => $student->id]))
            ->assertRedirect(route('cases.index', ['student_id' => $student->id, 'tab' => 'kasus', 'create' => 1]));
        $this->get(route('cases.index', ['create' => 1]))
            ->assertSee('data-modal-auto-open', false)
            ->assertSee('data-modal-url="'.route('cases.create', ['modal' => 1]).'"', false)
            ->assertDontSee('href="'.route('cases.create').'"', false);
        $this->get(route('dashboard.preview'))
            ->assertSee('data-modal-url="'.route('cases.create', ['modal' => 1]).'"', false)
            ->assertDontSee('href="'.route('cases.create').'"', false);
    }

    public function test_etatib_modal_search_filters_before_pagination_and_enforces_scope(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $student = $this->scopedStudent($teacher, 'Murid Pencarian', '0011111111');
        $first = $this->etatibRecord($student);
        foreach (range(1, 11) as $number) {
            $record = $first->replicate();
            $record->source_identifier = 'ET-PAGE-'.$number;
            $record->occurred_at = $first->occurred_at->addDays($number);
            $record->save();
        }
        $otherTeacher = $this->userWithRole('guru_bk');
        $outside = $this->etatibRecord($this->scopedStudent($otherTeacher, 'Murid Terlarang', '0022222222', 'XI RPL 2'));
        $params = ['modal' => 1, 'etatib_search' => 1];
        $this->actingAs($teacher)->getJson(route('cases.create', $params))
            ->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('total', 12)->assertJsonPath('last_page', 2);
        $this->getJson(route('cases.create', [...$params, 'q' => '0011111111', 'page' => 2]))
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('total', 12);
        $this->getJson(route('cases.create', [...$params, 'q' => 'Murid Pencarian']))
            ->assertOk()->assertJsonPath('total', 12);
        $this->getJson(route('cases.create', [...$params, 'record_id' => $outside->id]))
            ->assertOk()->assertJsonCount(0, 'data');
        $this->getJson(route('cases.create', [...$params, 'record_id' => $first->id, 'q' => 'Pencarian sebelumnya']))
            ->assertOk()->assertJsonPath('data.0.id', $first->id);
    }

    public function test_modal_create_returns_json_redirect_after_saving(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $student = $this->scopedStudent($teacher);
        $this->actingAs($teacher)->postJson(route('cases.store'), [
            ...$this->casePayload(), 'student_id' => $student->id,
        ])->assertOk()->assertJsonPath('redirect', route('cases.index', ['tab' => 'kasus']));
        $this->assertDatabaseCount('cases', 1);
    }

    public function test_unmapped_etatib_modal_keeps_explicit_nisn_context_and_requires_assigned_classroom(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $student = $this->scopedStudent($teacher);
        $classroomId = $student->classMemberships()->firstOrFail()->classroom_id;
        $record = $this->etatibRecord($student);
        $record->update(['student_id' => null, 'nisn' => '0099999999', 'source_student_name' => 'Murid Belum Terpetakan']);
        $this->actingAs($teacher)->getJson(route('cases.create', [
            'modal' => 1, 'etatib_search' => 1, 'temporary_nisn' => $record->nisn, 'record_id' => $record->id,
        ]))->assertOk()->assertJsonPath('data.0.id', $record->id)->assertJsonPath('data.0.classroom_id', null);

        $payload = [...$this->casePayload('e_tatib'), 'etatib_record_id' => $record->id];
        $this->postJson(route('cases.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('temporary_classroom_id');
        $this->postJson(route('cases.store'), [...$payload, 'temporary_classroom_id' => $classroomId])->assertOk();
        $this->assertDatabaseHas('cases', ['classroom_id' => $classroomId, 'student_id' => null]);
    }

    public function test_non_etatib_lookup_selection_accepts_autofilled_identity_fields(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $student = $this->scopedStudent($teacher, 'Murid Terpilih', '0033333333');
        $classroomId = $student->classMemberships()->firstOrFail()->classroom_id;

        $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload(),
            'student_id' => $student->id,
            'temporary_nisn' => $student->nisn,
            'temporary_name' => $student->name,
            'temporary_classroom_id' => $classroomId,
        ])->assertRedirect(route('cases.index', ['tab' => 'kasus']));

        $case = BkCase::query()->firstOrFail();
        $this->assertSame($student->id, $case->student_id);
        $this->assertNull($case->temporary_student_id);
    }

    public function test_non_etatib_lookup_selection_keeps_selected_student_when_nisn_changes_before_submit(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $student = $this->scopedStudent($teacher, 'Murid Terpilih', '0044444444');
        $staleNisn = $student->nisn;
        $student->update(['nisn' => '0055555555']);

        $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload(),
            'student_id' => $student->id,
            'temporary_nisn' => $staleNisn,
            'temporary_name' => $student->name,
            'temporary_classroom_id' => $student->classMemberships()->firstOrFail()->classroom_id,
        ])->assertRedirect(route('cases.index', ['tab' => 'kasus']));

        $case = BkCase::query()->firstOrFail();
        $this->assertSame($student->id, $case->student_id);
        $this->assertNull($case->temporary_student_id);
    }

    public function test_manual_unknown_nisn_creates_temporary_identity(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $this->scopedStudent($teacher);

        $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload(),
            'temporary_nisn' => '0099999999',
            'temporary_name' => 'Murid Belum Sinkron',
            'temporary_classroom_id' => TeacherAssignment::query()->where('user_id', $teacher->id)->value('classroom_id'),
        ])->assertRedirect();

        $case = BkCase::query()->firstOrFail();
        $this->assertNull($case->student_id);
        $this->assertSame('Murid Belum Sinkron', $case->temporaryStudent?->input_name);
        $this->assertNotNull($case->classroom_id);
        $this->assertNotNull($case->academic_year_id);
    }

    public function test_reconciled_case_follows_master_name_teacher_scope_and_departure(): void
    {
        $owner = $this->userWithRole('guru_bk');
        $newTeacher = $this->userWithRole('guru_bk');
        $outsider = $this->userWithRole('guru_bk');
        $existing = $this->scopedStudent($owner);
        $temporary = app(StudentIdentityService::class)->createTemporary('0099999999', 'Nama Saat Layanan', $owner);
        $case = $this->createCase($owner, $existing, [
            'student_id' => null,
            'temporary_student_id' => $temporary->id,
        ]);
        $membership = $existing->classMemberships()->firstOrFail();
        $student = Student::query()->create([
            'nisn' => $temporary->nisn,
            'name' => 'Nama dari Sekolah',
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $membership->classroom_id,
            'academic_year_id' => $membership->academic_year_id,
            'is_active' => true,
        ]);
        app(StudentIdentityService::class)->reconcile($temporary, $owner);
        TeacherAssignment::query()->where('user_id', $owner->id)->update(['user_id' => $newTeacher->id]);
        Consultation::query()->create([
            'temporary_student_id' => $temporary->id,
            'academic_year_id' => $membership->academic_year_id,
            'classroom_id' => $membership->classroom_id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'session_date' => '2026-08-01',
            'problem' => 'Permasalahan sementara.',
            'handling' => 'Ditangani.',
            'result' => 'Dipantau.',
            'counselor_id' => $owner->id,
        ]);

        $this->assertNull($case->refresh()->student_id);
        $this->assertSame($student->id, $temporary->refresh()->reconciled_student_id);
        $this->assertSame('Nama Dari Sekolah', $case->identityName());
        $this->assertTrue(BkCase::query()->accessibleTo($newTeacher)->whereKey($case->id)->exists());
        $this->actingAs($newTeacher)->get(route('cases.index', ['search' => 'Nama dari Sekolah']))
            ->assertOk()->assertSee('Nama Dari Sekolah');
        $this->get(route('cases.show', $case))->assertOk();
        $this->get(route('students.show', ['student' => $student, 'tab' => 'kasus']))
            ->assertOk()->assertSee('Informasi awal layanan.');
        $studentRows = $this->get(route('students.index', ['search' => 'Nama dari Sekolah', 'sort' => 'permasalahan', 'direction' => 'desc']))
            ->assertOk()->viewData('students');
        $this->assertSame(1, (int) $studentRows->items()[0]->active_cases_count);
        $this->assertSame(1, (int) $studentRows->items()[0]->consultations_count);
        $this->actingAs($owner)->get(route('students.show', $student))->assertOk();
        $this->actingAs($outsider)->get(route('cases.show', $case))->assertForbidden();
        $this->get(route('students.show', $student))->assertForbidden();

        $this->createCase($owner, $student);
        $wakaRows = app(WakaStudentCaseService::class)->paginateSafe($this->userWithRole('waka_kesiswaan'), []);
        $this->assertSame(1, $wakaRows->total());
        $this->assertSame(2, $wakaRows->items()[0]['jumlah_kasus']);

        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'status' => StudentDeparture::STATUS_OFFICIAL,
            'reported_at' => '2026-07-31',
            'effective_date' => '2026-08-01',
            'recorded_by' => $owner->id,
            'finalized_by' => $this->userWithRole('koordinator_bk')->id,
            'finalized_at' => now(),
        ]);
        $this->assertFalse(BkCase::query()->accessibleTo($newTeacher)->whereKey($case->id)->exists());
        $this->actingAs($newTeacher)->get(route('cases.show', $case))->assertForbidden();
    }

    public function test_temporary_case_cannot_use_another_teachers_classroom(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $otherTeacher = $this->userWithRole('guru_bk');
        $this->scopedStudent($otherTeacher);
        $classroomId = TeacherAssignment::query()->where('user_id', $otherTeacher->id)->value('classroom_id');

        $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload(),
            'temporary_nisn' => '0099999999',
            'temporary_name' => 'Murid Baru',
            'temporary_classroom_id' => $classroomId,
        ])->assertSessionHasErrors('temporary_classroom_id');

        $this->assertSame(0, BkCase::query()->count());
        $this->assertDatabaseCount('temporary_students', 0);
    }

    public function test_complete_requires_summary_and_uses_server_date(): void
    {
        Carbon::setTestNow('2026-09-17 08:30:00');
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));

        $this->actingAs($teacher)->patch(route('cases.complete', $case), [
            'expected_updated_at' => $case->updated_at->toJSON(),
            'resolution_summary' => 'Ringkasan yang belum disimpan tidak boleh dipakai.',
        ])->assertSessionHasErrors('resolution_summary');

        $case->update(['resolution_summary' => 'Murid menyepakati langkah penyelesaian.']);
        $this->actingAs($teacher)->patch(route('cases.complete', $case), [
            'expected_updated_at' => $case->updated_at->toJSON(),
            'initial_info' => 'Narasi dari payload yang harus diabaikan.',
            'closed_at' => '1999-01-01',
        ])->assertRedirect(route('cases.index'));

        $case->refresh();
        $this->assertSame(ServiceRecordStatus::COMPLETED, $case->status->code);
        $this->assertSame('2026-09-17', $case->closed_at?->toDateString());
        $this->assertSame('Murid menyepakati langkah penyelesaian.', $case->resolution_summary);
        $this->assertSame('Informasi awal layanan.', $case->initial_info);
        $this->assertDatabaseHas('audit_logs', ['action' => 'case.completed', 'auditable_id' => $case->id]);
    }

    public function test_completion_rejects_stale_and_completed_cases_and_non_owners(): void
    {
        Carbon::setTestNow('2026-09-17 08:30:00');
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher), ['resolution_summary' => 'Kesepakatan selesai.']);
        $staleTimestamp = $case->updated_at->toJSON();
        Carbon::setTestNow('2026-09-17 08:31:00');
        $case->update(['initial_info' => 'Perubahan dari tab lain.']);

        $this->actingAs($teacher)->patchJson(route('cases.complete', $case), [
            'expected_updated_at' => $staleTimestamp,
        ])->assertUnprocessable()->assertJsonValidationErrors('expected_updated_at');
        $this->assertNull($case->refresh()->closed_at);

        foreach ([$this->userWithRole('guru_bk'), $this->userWithRole('waka_kesiswaan')] as $other) {
            $this->actingAs($other)->patchJson(route('cases.complete', $case), [
                'expected_updated_at' => $case->updated_at->toJSON(),
            ])->assertForbidden();
        }

        $this->actingAs($teacher)->patch(route('cases.update', $case), [
            ...$this->updatePayload($case),
            'action' => 'complete',
        ])->assertSessionHasErrors('action');

        $this->actingAs($teacher)->patchJson(route('cases.complete', $case), [
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertOk();
        $this->actingAs($teacher)->patchJson(route('cases.complete', $case->refresh()), [
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertUnprocessable()->assertJsonValidationErrors('case');
    }

    public function test_completed_case_can_save_narratives_and_audit_only_changed_fields(): void
    {
        Carbon::setTestNow('2026-09-17 08:30:00');
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $this->completeCase($teacher, $case);
        $closedAt = $case->refresh()->closed_at?->toDateString();
        $expectedUpdatedAt = $case->updated_at->toJSON();
        Carbon::setTestNow('2026-09-17 08:31:00');

        $this->actingAs($teacher)->patch(route('cases.update', $case), [
            'initial_info' => $case->initial_info,
            'initial_action' => 'Penanganan diperjelas setelah kasus selesai.',
            'resolution_summary' => $case->resolution_summary,
            'action' => 'save',
            'expected_updated_at' => $expectedUpdatedAt,
        ])->assertRedirect(route('cases.show', $case));

        $case->refresh();
        $this->assertSame(ServiceRecordStatus::COMPLETED, $case->status->code);
        $this->assertSame($closedAt, $case->closed_at?->toDateString());
        $audit = AuditLog::query()->where('action', 'case.updated')->latest('id')->firstOrFail();
        $this->assertSame(['initial_action' => 'Asesmen awal.'], $audit->before_values);
        $this->assertSame(['initial_action' => 'Penanganan diperjelas setelah kasus selesai.'], $audit->after_values);
    }

    public function test_owner_can_update_case_metadata_but_not_source_or_identity(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $serviceField = $this->reference('service_field', 'sosial');

        $this->actingAs($teacher)->patch(route('cases.update', $case), [
            ...$this->updatePayload($case),
            'service_field_id' => $serviceField->id,
            'service_date' => '2026-08-02',
        ])->assertRedirect(route('cases.show', $case));

        $case->refresh();
        $this->assertSame($serviceField->id, $case->service_field_id);
        $this->assertSame('2026-08-02', $case->service_date?->toDateString());

        $serviceField->update(['is_active' => false]);
        $this->actingAs($teacher)->get(route('cases.edit', [$case, 'modal' => 1]))
            ->assertOk()
            ->assertSee('<option value="'.$serviceField->id.'" selected>'.$serviceField->label.'</option>', false);
        $this->actingAs($teacher)->patch(route('cases.update', $case), [
            ...$this->updatePayload($case),
            'service_field_id' => $serviceField->id,
            'service_date' => $case->service_date?->toDateString(),
        ])->assertRedirect(route('cases.show', $case));

        $case->refresh();

        $this->actingAs($teacher)->patch(route('cases.update', $case), [
            ...$this->updatePayload($case),
            'case_source_id' => $this->reference('case_source', 'rujukan')->id,
            'temporary_name' => 'Identitas yang diubah',
        ])->assertSessionHasErrors(['case_source_id', 'temporary_name']);
    }

    public function test_modal_case_update_returns_to_case_list_with_success_notice(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));

        $this->actingAs($teacher)->patchJson(route('cases.update', $case), $this->updatePayload($case))
            ->assertOk()
            ->assertJsonPath('redirect', route('cases.index', ['tab' => 'kasus']))
            ->assertSessionHas('success', 'Kasus berhasil diperbarui.');
    }

    public function test_stale_case_update_is_rejected(): void
    {
        Carbon::setTestNow('2026-09-17 08:30:00');
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $staleTimestamp = $case->updated_at->toJSON();
        Carbon::setTestNow('2026-09-17 08:31:00');
        $case->update(['initial_info' => 'Perubahan dari tab lain.']);

        $this->actingAs($teacher)->patchJson(route('cases.update', $case), [
            ...$this->updatePayload($case),
            'initial_info' => 'Perubahan yang menimpa.',
            'expected_updated_at' => $staleTimestamp,
        ])->assertUnprocessable()->assertJsonValidationErrors('expected_updated_at');

        $this->assertSame('Perubahan dari tab lain.', $case->refresh()->initial_info);
    }

    public function test_follow_up_is_unique_per_case_with_manual_date_and_server_record_time(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $homeVisit = $this->reference('follow_up_type', 'home_visit');
        $statement = $this->reference('follow_up_type', 'surat_pernyataan');
        Carbon::setTestNow('2026-10-09 10:30:00');

        $response = $this->actingAs($teacher)->postJson(route('cases.follow-ups.store', $case), [
            'follow_up_type_id' => $homeVisit->id,
            'follow_up_date' => '2026-10-07',
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertOk()->assertJsonPath('data.status_code', ServiceRecordStatus::NEEDS_FOLLOW_UP)
            ->assertJsonPath('data.follow_up_type_label', 'Home Visit')
            ->assertJsonPath('data.follow_up_date', '2026-10-07');
        $this->assertDatabaseHas('case_follow_ups', [
            'case_id' => $case->id,
            'follow_up_type_id' => $homeVisit->id,
            'follow_up_date' => '2026-10-07 00:00:00',
            'performed_at' => '2026-10-09 10:30:00',
        ]);

        $this->actingAs($teacher)->postJson(route('cases.follow-ups.store', $case), [
            'follow_up_type_id' => $homeVisit->id,
            'follow_up_date' => '2026-10-08',
            'expected_updated_at' => $response->json('data.updated_at'),
        ])->assertUnprocessable()->assertJsonValidationErrors('follow_up_type_id');

        Carbon::setTestNow('2026-10-09 10:31:00');
        $this->actingAs($teacher)->postJson(route('cases.follow-ups.store', $case), [
            'follow_up_type_id' => $statement->id,
            'follow_up_date' => '2026-10-08',
            'expected_updated_at' => $response->json('data.updated_at'),
        ])->assertOk()->assertJsonPath('data.follow_up_type_id', $statement->id);

        $case->refresh();
        $this->assertSame($statement->id, $case->follow_up_type_id);
        $this->assertSame(2, $case->followUps()->count());
    }

    public function test_follow_up_rejects_completed_case_forbidden_role_and_inactive_reference(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $homeVisit = $this->reference('follow_up_type', 'home_visit');
        $inactive = $this->reference('follow_up_type', 'surat_pernyataan');
        $inactive->update(['is_active' => false]);

        $this->actingAs($teacher)->postJson(route('cases.follow-ups.store', $case), [
            'follow_up_type_id' => $inactive->id,
            'follow_up_date' => '2026-10-09',
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertUnprocessable()->assertJsonValidationErrors('follow_up_type_id');

        $otherTeacher = $this->userWithRole('guru_bk');
        $this->actingAs($otherTeacher)->postJson(route('cases.follow-ups.store', $case), [
            'follow_up_type_id' => $homeVisit->id,
            'follow_up_date' => '2026-10-09',
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertForbidden();

        foreach (['koordinator_bk', 'waka_kesiswaan', 'admin_it'] as $role) {
            $this->actingAs($this->userWithRole($role))->postJson(route('cases.follow-ups.store', $case), [
                'follow_up_type_id' => $homeVisit->id,
                'follow_up_date' => '2026-10-09',
                'expected_updated_at' => $case->updated_at->toJSON(),
            ])->assertForbidden();
        }

        $this->completeCase($teacher, $case->refresh());
        $this->actingAs($teacher)->postJson(route('cases.follow-ups.store', $case), [
            'follow_up_type_id' => $homeVisit->id,
            'follow_up_date' => '2026-10-09',
            'expected_updated_at' => $case->refresh()->updated_at->toJSON(),
        ])->assertForbidden();
    }

    public function test_follow_up_rejects_stale_case_without_changing_history(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $staleTimestamp = $case->updated_at->toJSON();
        $case->forceFill(['updated_at' => $case->updated_at->addMinute()])->save();

        $this->actingAs($teacher)->postJson(route('cases.follow-ups.store', $case), [
            'follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id,
            'follow_up_date' => '2026-10-09',
            'expected_updated_at' => $staleTimestamp,
        ])->assertUnprocessable()->assertJsonValidationErrors('expected_updated_at');

        $this->assertDatabaseCount('case_follow_ups', 0);
    }

    public function test_latest_follow_up_uses_manual_date_even_when_entry_is_newer(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $legacyType = $this->reference('follow_up_type', 'home_visit');
        $newType = $this->reference('follow_up_type', 'surat_pernyataan');
        $case->followUps()->create([
            'follow_up_type_id' => $legacyType->id,
            'follow_up_date' => '2027-01-01',
            'created_by' => $teacher->id,
        ]);
        $case->update(['follow_up_type_id' => $legacyType->id]);
        Carbon::setTestNow('2026-10-09 10:30:00');

        $this->actingAs($teacher)->postJson(route('cases.follow-ups.store', $case), [
            'follow_up_type_id' => $newType->id,
            'follow_up_date' => '2026-10-09',
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertOk()->assertJsonPath('data.latest_follow_up_label', $legacyType->label);

        $this->assertSame($legacyType->id, $case->refresh()->follow_up_type_id);
        $this->assertSame($legacyType->id, $case->followUps()->firstOrFail()->follow_up_type_id);
    }

    public function test_manual_follow_up_date_is_independent_of_server_record_time(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        Carbon::setTestNow('2026-10-08 17:30:00 UTC');

        $this->actingAs($teacher)->postJson(route('cases.follow-ups.store', $case), [
            'follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id,
            'follow_up_date' => '2026-10-07',
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertOk()->assertJsonPath('data.follow_up_date', '2026-10-07');

        $this->assertDatabaseHas('case_follow_ups', [
            'case_id' => $case->id,
            'performed_at' => '2026-10-08 17:30:00',
            'follow_up_date' => '2026-10-07 00:00:00',
        ]);
    }

    public function test_follow_up_requires_a_manual_date(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));

        $this->actingAs($teacher)->postJson(route('cases.follow-ups.store', $case), [
            'follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id,
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertUnprocessable()->assertJsonValidationErrors('follow_up_date');

        $this->assertDatabaseCount('case_follow_ups', 0);
    }

    public function test_migration_rejects_legacy_case_with_cleared_follow_up_summary(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $case->followUps()->create([
            'follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id,
            'follow_up_date' => '2026-10-09',
            'created_by' => $teacher->id,
        ]);

        $migration = require database_path('migrations/2026_10_09_000100_enforce_case_follow_up_type_unique.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Ringkasan tindak lanjut kasus tidak sesuai dengan riwayat');
        $migration->up();
    }

    public function test_case_list_has_final_columns_and_progressive_modal_contract(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));

        $response = $this->actingAs($teacher)->get(route('cases.index'));

        $response->assertOk()
            ->assertSeeInOrder(['Hari/Tanggal', 'Nama & Kelas', 'Jenis Masalah', 'Tindak Lanjut', 'Hasil', 'Aksi'], false)
            ->assertDontSee('<th>Status</th>', false)
            ->assertDontSee('name="status_id"', false)
            ->assertSee('name="follow_up_type_id"', false)
            ->assertDontSee('<tr data-modal-url=', false)
            ->assertSee('href="'.route('cases.show', $case).'"', false)
            ->assertSee('title="Lihat selengkapnya" aria-label="Lihat selengkapnya"', false)
            ->assertSee('data-modal-url="'.route('cases.show', [$case, 'modal' => 1]).'"', false)
            ->assertSee('data-store-url="'.route('cases.follow-ups.store', $case).'"', false)
            ->assertSee('name="follow_up_date"', false)
            ->assertSee('name="expected_updated_at"', false)
            ->assertSee('action="'.route('cases.complete', $case).'"', false)
            ->assertSee('id="case-complete-updated-at-'.$case->id.'"', false)
            ->assertSee('data-confirm-title="Selesaikan Kasus BK?"', false)
            ->assertSee('data-confirm-title="Hapus Kasus BK?"', false)
            ->assertSee('data-confirm-subject="'.$case->identityName().' ('.$case->classroom->name.')"', false)
            ->assertSee('data-app-confirmation-modal', false)
            ->assertDontSee('intent=complete', false)
            ->assertSee('aria-labelledby="case-modal-title"', false)
            ->assertSee('modal-dialog-scrollable', false)
            ->assertSee('aria-label="Tutup"', false)
            ->assertDontSee("/cases/{$case->id}/follow-ups/create", false)
            ->assertDontSee("/cases/{$case->id}/resolve", false);
        $this->assertSame(1, substr_count($response->getContent(), 'id="case-modal"'));
    }

    public function test_case_list_shows_result_and_expandable_notes_only_to_professional_users(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher), [
            'initial_info' => 'Latar khusus murid.',
            'initial_action' => 'Penanganan khusus murid.',
            'resolution_summary' => 'Hasil khusus murid.',
        ]);

        $this->actingAs($teacher)->get(route('cases.index'))
            ->assertOk()
            ->assertSee('Hasil khusus murid.')
            ->assertSee('data-report-detail-toggle', false)
            ->assertSee('aria-controls="case-notes-'.$case->id.'"', false)
            ->assertSee('id="case-notes-'.$case->id.'"', false)
            ->assertSee('Latar khusus murid.')
            ->assertSee('Penanganan khusus murid.')
            ->assertSee('title="Lihat selengkapnya" aria-label="Lihat selengkapnya"', false);

        $waka = $this->userWithRole('waka_kesiswaan');
        $this->actingAs($waka)->get(route('cases.index'))
            ->assertOk()
            ->assertSee($case->identityName())
            ->assertDontSee('Hasil khusus murid.')
            ->assertDontSee('Latar khusus murid.')
            ->assertDontSee('Penanganan khusus murid.')
            ->assertDontSee('data-report-detail-toggle', false);
    }

    public function test_case_search_matches_name_only_and_follow_up_filter_is_applied(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $alpha = $this->createCase($teacher, $this->scopedStudent($teacher, 'Alpha Murid', '0011111111'));
        $beta = $this->createCase($teacher, $this->scopedStudent($teacher, 'Beta Murid', '0022222222'));
        $homeVisit = $this->reference('follow_up_type', 'home_visit');
        $beta->update([
            'status_id' => $this->reference('case_status', ServiceRecordStatus::NEEDS_FOLLOW_UP)->id,
            'follow_up_type_id' => $homeVisit->id,
        ]);

        $this->actingAs($teacher)->get(route('cases.index', ['search' => 'Alpha']))
            ->assertSee('Alpha Murid')->assertDontSee('Beta Murid');
        $this->actingAs($teacher)->get(route('cases.index', ['search' => '0011111111']))
            ->assertDontSee('Alpha Murid');
        $this->actingAs($teacher)->get(route('cases.index', ['follow_up_type_id' => $homeVisit->id]))
            ->assertSee('Beta Murid')->assertDontSee('Alpha Murid');
        $this->actingAs($teacher)->get(route('cases.index', ['status_id' => $beta->status_id]))
            ->assertSee('Beta Murid')->assertDontSee('Alpha Murid');
        $this->assertNotSame($alpha->id, $beta->id);
    }

    public function test_case_month_filter_keeps_year_boundary_and_rejects_invalid_month(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $this->createCase($teacher, $this->scopedStudent($teacher, 'November Murid', '0011111111'), ['service_date' => '2026-11-30']);
        $this->createCase($teacher, $this->scopedStudent($teacher, 'Desember Murid', '0022222222'), ['service_date' => '2026-12-31']);
        $this->createCase($teacher, $this->scopedStudent($teacher, 'Januari Murid', '0033333333'), ['service_date' => '2027-01-01']);

        $this->actingAs($teacher)->get(route('cases.index', ['month' => '2026-12']))
            ->assertOk()->assertSee('Desember Murid')->assertDontSee('November Murid')->assertDontSee('Januari Murid');
        $this->actingAs($teacher)->get(route('cases.index', ['month' => '2027-01']))
            ->assertOk()->assertSee('Januari Murid')->assertDontSee('Desember Murid');
        $this->actingAs($teacher)->get(route('cases.index', ['month' => '2026-13']))
            ->assertOk()->assertDontSee('November Murid')->assertDontSee('Desember Murid')->assertDontSee('Januari Murid');
    }

    public function test_case_sorting_uses_allowlist_and_invalid_sort_falls_back_to_date(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $alpha = $this->createCase($teacher, $this->scopedStudent($teacher, 'Alpha', '0011111111', 'Z Kelas'), ['service_date' => '2026-08-02']);
        $beta = $this->createCase($teacher, $this->scopedStudent($teacher, 'Beta', '0022222222', 'X Kelas'), ['service_date' => '2026-08-01']);
        $charlie = $this->createCase($teacher, $this->scopedStudent($teacher, 'Charlie', '0033333333', 'Y Kelas'), ['service_date' => '2026-08-03']);

        $alpha->update([
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::COMPLETED)->id,
        ]);
        $beta->update([
            'case_source_id' => $this->reference('case_source', 'e_tatib')->id,
            'service_field_id' => $this->reference('service_field', 'sosial')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::IN_PROGRESS)->id,
        ]);
        $charlie->update([
            'case_source_id' => $this->reference('case_source', 'rujukan')->id,
            'service_field_id' => $this->reference('service_field', 'belajar')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::NEEDS_FOLLOW_UP)->id,
        ]);

        foreach ([
            'nama' => ['Alpha', 'Beta', 'Charlie'],
            'kelas' => ['Beta', 'Charlie', 'Alpha'],
            'tanggal' => ['Beta', 'Alpha', 'Charlie'],
            'sumber' => ['Beta', 'Charlie', 'Alpha'],
            'bidang' => ['Charlie', 'Alpha', 'Beta'],
            'status' => ['Beta', 'Alpha', 'Charlie'],
        ] as $sort => $expected) {
            $this->actingAs($teacher)->get(route('cases.index', ['sort' => $sort, 'direction' => 'asc']))
                ->assertOk()->assertSeeInOrder($expected);
        }

        $this->actingAs($teacher)->get(route('cases.index', ['sort' => 'status;drop table cases', 'direction' => 'sideways']))
            ->assertOk()->assertSeeInOrder(['Charlie', 'Alpha', 'Beta']);
    }

    public function test_case_sorting_uses_id_as_tie_breaker_in_same_direction(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $first = $this->createCase($teacher, $this->scopedStudent($teacher, 'Pertama', '0011111111'), ['service_date' => '2026-08-01']);
        $second = $this->createCase($teacher, $this->scopedStudent($teacher, 'Kedua', '0022222222'), ['service_date' => '2026-08-01']);

        $this->actingAs($teacher)->get(route('cases.index', ['sort' => 'tanggal', 'direction' => 'asc']))
            ->assertSeeInOrder([$first->identityName(), $second->identityName()]);
        $this->actingAs($teacher)->get(route('cases.index', ['sort' => 'tanggal', 'direction' => 'desc']))
            ->assertSeeInOrder([$second->identityName(), $first->identityName()]);
    }

    public function test_detail_and_edit_support_modal_and_full_page_fallbacks(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));

        $this->actingAs($teacher)->get(route('cases.show', $case))
            ->assertOk()->assertViewIs('pages.cases.show')->assertSee('Detail Permasalahan');
        $this->actingAs($teacher)->get(route('cases.show', [$case, 'modal' => 1]))
            ->assertOk()->assertViewIs('pages.cases._detail-modal')
            ->assertSeeInOrder(['Detail Kasus BK', 'Data Murid', 'NISN', 'Nama', 'Rombel', 'Informasi Layanan', 'Sumber', 'Tanggal Layanan', 'Jenis Masalah', 'Status', 'Catatan Permasalahan', 'Latar Belakang', 'Penanganan', 'Ringkasan', 'Riwayat Tindak Lanjut'])
            ->assertSee($case->student->nisn)
            ->assertSee($case->source->label)
            ->assertSee('Belum ada riwayat tindak lanjut.');
        $this->actingAs($teacher)->get(route('cases.edit', $case))
            ->assertOk()->assertViewIs('pages.cases.edit')->assertSee('Ubah Permasalahan');
        $this->actingAs($teacher)->get(route('cases.edit', [$case, 'modal' => 1]))
            ->assertOk()->assertViewIs('pages.cases._edit-modal')
            ->assertSeeInOrder(['Edit Permasalahan', 'Data Murid', 'Nama Murid', 'NISN', 'Rombel', 'Sumber', 'Jenis Masalah', 'Tanggal Layanan', 'Catatan Permasalahan', 'Latar Belakang', 'Penanganan', 'Ringkasan', 'Batal', 'Simpan'])
            ->assertSee('name="expected_updated_at"', false)
            ->assertSee('name="action" value="save"', false)
            ->assertSee('name="service_field_id"', false)
            ->assertSee('name="service_date"', false)
            ->assertDontSee('name="action" value="complete"', false)
            ->assertDontSee('name="case_source_id"', false)
            ->assertDontSee('name="change_reason"', false)
            ->assertDontSee('name="waka_summary"', false)
            ->assertDontSee('data-autosave-form', false)
            ->assertDontSee('data-draft-status', false)
            ->assertDontSee('data-clear-draft', false);
    }

    public function test_etatib_edit_modal_shows_read_only_student_identity(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $student = $this->scopedStudent($teacher);
        $case = $this->createCase($teacher, $student, ['case_source_id' => $this->reference('case_source', 'e_tatib')->id]);

        $this->actingAs($teacher)->get(route('cases.edit', [$case, 'modal' => 1]))
            ->assertOk()
            ->assertSee($student->nisn)
            ->assertSee($student->name)
            ->assertSee($case->classroom->name)
            ->assertDontSee(' disabled', false)
            ->assertDontSee('sibk-panel', false)
            ->assertDontSee('name="student_id"', false)
            ->assertDontSee('name="case_source_id"', false);
    }

    public function test_completed_case_edit_trigger_requires_confirmation_and_waka_cannot_mutate(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $this->completeCase($teacher, $case);
        $indexResponse = $this->actingAs($teacher)->get(route('cases.index'));
        $indexResponse->assertSee('data-app-confirm', false);
        $indexResponse->assertSee('data-confirm-title="Edit Permasalahan Selesai?"', false);
        $indexResponse->assertSee('data-confirm-message="Permasalahan milik"', false);
        $indexResponse->assertSee('data-confirm-suffix=" telah selesai. Apakah Anda ingin melanjutkan pengeditan?"', false);
        $indexResponse->assertSee('btn-icon-action--disabled', false);
        $indexResponse->assertDontSee('window.confirm');

        $showResponse = $this->actingAs($teacher)->get(route('cases.show', $case));
        $showResponse->assertSee('data-app-confirm', false);
        $showResponse->assertSee('data-confirm-title="Edit Permasalahan Selesai?"', false);
        $showResponse->assertDontSee('window.confirm');

        $activeCase = $this->createCase($teacher, $this->scopedStudent($teacher, 'Murid Aktif', '0088888888'));
        $activeResponse = $this->actingAs($teacher)->get(route('cases.show', $activeCase));
        $activeResponse->assertSee('<a href="'.route('cases.edit', $activeCase).'" class="btn btn-primary">Ubah</a>', false);

        $activeListResponse = $this->actingAs($teacher)->get(route('cases.index', ['search' => 'Murid Aktif']));
        $activeListResponse->assertSee('btn-icon-action--primary', false);
        $activeListResponse->assertSee('data-app-confirm-submit', false);
        $activeListResponse->assertDontSee('data-confirm-title="Edit Permasalahan Selesai?"', false);

        $nonOwner = $this->userWithRole('guru_bk');
        $this->actingAs($nonOwner)->delete(route('cases.destroy', $case))->assertForbidden();
        $waka = $this->userWithRole('waka_kesiswaan');
        $this->actingAs($waka)->patchJson(route('cases.update', $case), $this->updatePayload($case))
            ->assertForbidden();
        $this->actingAs($waka)->postJson(route('cases.follow-ups.store', $case), [
            'follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id,
            'follow_up_date' => '2026-10-09',
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertForbidden();
        $this->actingAs($waka)->delete(route('cases.destroy', $case))->assertForbidden();
    }

    public function test_owner_can_archive_case_and_remove_it_from_operational_views(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher, 'Murid Diarsipkan'));

        $this->actingAs($teacher)->delete(route('cases.destroy', $case))
            ->assertRedirect(route('cases.index'));

        $this->assertSoftDeleted('cases', ['id' => $case->id]);
        $this->actingAs($teacher)->get(route('cases.index'))->assertDontSee('Murid Diarsipkan');
        $this->actingAs($teacher)->get(route('cases.show', $case))->assertNotFound();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'case.archived',
            'auditable_type' => BkCase::class,
            'auditable_id' => $case->id,
        ]);
    }

    public function test_previous_year_case_is_read_only(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        AcademicYear::query()->whereKey($case->academic_year_id)->update(['is_active' => false]);

        $this->assertFalse($teacher->can('update', $case->fresh()));
        $this->actingAs($teacher)->patchJson(route('cases.complete', $case), [
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertForbidden();
        $this->actingAs($teacher)->delete(route('cases.destroy', $case))->assertForbidden();
        $this->assertDatabaseHas('cases', ['id' => $case->id, 'deleted_at' => null]);
    }

    public function test_coordinator_can_read_all_cases_without_gaining_ownership(): void
    {
        $coordinator = $this->userWithRole('koordinator_bk');
        $coordinator->roles()->attach(Role::query()->where('slug', 'guru_bk')->firstOrFail());
        $assignedStudent = $this->scopedStudent($coordinator, 'Murid Kelas Ampuan', '0011111111');
        $otherTeacher = $this->userWithRole('guru_bk');
        $otherStudent = $this->scopedStudent($otherTeacher, 'Murid Kelas Lain', '0022222222', 'XI RPL 2');
        $ownCase = $this->createCase($coordinator, $otherStudent);
        $classCase = $this->createCase($otherTeacher, $assignedStudent);
        $hiddenCase = $this->createCase($otherTeacher, $otherStudent);

        $this->actingAs($coordinator)->get(route('cases.index', ['tab' => 'kasus']))
            ->assertOk()
            ->assertSee('Murid Kelas Ampuan')
            ->assertSee('Murid Kelas Lain')
            ->assertSee('href="'.route('cases.show', $hiddenCase).'"', false);
        $this->get(route('cases.show', $ownCase))->assertOk();
        $this->get(route('cases.show', $classCase))->assertOk();
        $this->get(route('cases.show', $hiddenCase))->assertOk();
        $this->get(route('cases.show', [$hiddenCase, 'modal' => 1]))->assertOk();
        $this->assertFalse($coordinator->can('update', $hiddenCase));
        $this->assertFalse($coordinator->can('viewInternal', $hiddenCase));

        $coordinatorOnly = $this->userWithRole('koordinator_bk');
        $this->actingAs($coordinatorOnly)->get(route('cases.index', ['tab' => 'kasus']))
            ->assertOk()
            ->assertSee('href="'.route('cases.show', $ownCase).'"', false)
            ->assertSee('href="'.route('cases.show', $classCase).'"', false);
        $this->get(route('cases.show', $ownCase))->assertOk();
        $this->assertFalse($coordinatorOnly->can('create', BkCase::class));
    }

    private function completeCase(User $teacher, BkCase $case): void
    {
        $case->update([
            'status_id' => $this->reference('case_status', ServiceRecordStatus::COMPLETED)->id,
            'resolution_summary' => 'Kasus selesai setelah penanganan.',
            'closed_at' => today()->toDateString(),
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function createCase(User $teacher, Student $student, array $overrides = []): BkCase
    {
        $case = BkCase::query()->create([
            'registration_number' => 'K-2026-'.str_pad((string) (BkCase::query()->count() + 1), 4, '0', STR_PAD_LEFT),
            'student_id' => $student->id,
            'academic_year_id' => $student->classMemberships()->firstOrFail()->academic_year_id,
            'classroom_id' => $student->classMemberships()->firstOrFail()->classroom_id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::IN_PROGRESS)->id,
            'service_date' => '2026-08-01',
            'initial_info' => 'Informasi awal layanan.',
            'initial_action' => 'Asesmen awal.',
            'created_by' => $teacher->id,
            ...$overrides,
        ]);
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $teacher->id,
            'reason' => 'Fixture pemilik kasus.',
            'assigned_by' => $teacher->id,
        ]);

        return $case;
    }

    private function scopedStudent(
        User $teacher,
        string $name = 'Murid Scope',
        string $nisn = '0012345678',
        string $className = 'X RPL 1',
    ): Student {
        $year = AcademicYear::query()->firstOrCreate(
            ['name' => '2026/2027'],
            ['starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true],
        );
        $classroom = Classroom::query()->firstOrCreate(
            ['academic_year_id' => $year->id, 'name' => $className],
            ['is_active' => true],
        );
        $student = Student::query()->create(['nisn' => $nisn, 'name' => $name, 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        TeacherAssignment::query()->firstOrCreate([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
        ], [
            'assigned_by' => $teacher->id,
        ]);

        return $student;
    }

    public function test_etatib_request_keeps_raw_source_name_when_display_name_is_submitted(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $record = $this->etatibRecord($this->scopedStudent($teacher));
        $record->update(['student_id' => null, 'source_student_name' => 'NADIA PUTRI']);
        $request = new class extends StoreCaseRequest
        {
            public function prepare(): void
            {
                $this->prepareForValidation();
            }
        };
        $request->merge([
            'case_source_id' => $this->reference('case_source', 'e_tatib')->id,
            'etatib_record_ids' => [$record->id],
            'temporary_nisn' => $record->nisn,
            'temporary_name' => 'Nadia Putri',
        ]);
        $request->prepare();
        $this->assertSame('NADIA PUTRI', $request->input('temporary_name'));
    }

    private function etatibRecord(Student $student): ExternalTatibRecord
    {
        return ExternalTatibRecord::query()->create([
            'source_identifier' => 'ET-'.$student->id,
            'nisn' => $student->nisn,
            'student_id' => $student->id,
            'occurred_at' => '2026-08-18 09:00:00',
            'violation_type' => 'Terlambat',
            'category' => 'Kedisiplinan',
            'points' => 5,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function casePayload(string $sourceCode = 'temuan_guru_bk'): array
    {
        return [
            'case_source_id' => $this->reference('case_source', $sourceCode)->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2026-08-01',
            'initial_info' => 'Informasi awal layanan.',
            'initial_action' => 'Asesmen awal.',
        ];
    }

    /** @return array<string, mixed> */
    private function updatePayload(BkCase $case): array
    {
        return [
            'initial_info' => $case->initial_info,
            'initial_action' => $case->initial_action,
            'resolution_summary' => $case->resolution_summary,
            'action' => 'save',
            'expected_updated_at' => $case->updated_at->toJSON(),
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
