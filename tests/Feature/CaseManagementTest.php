<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\ExternalTatibRecord;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\CaseService;
use App\Services\FollowUpService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CaseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_teacher_creates_scoped_case_with_etatib_link_and_audit(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $record = ExternalTatibRecord::query()->create([
            'source_identifier' => 'tatib-1',
            'nisn' => $student->nisn,
            'student_id' => $student->id,
            'occurred_at' => '2026-08-18 09:00:00',
            'violation_type' => 'Terlambat',
            'category' => 'Kedisiplinan',
            'points' => 5,
            'is_active' => true,
            'synced_at' => now(),
        ]);

        $response = $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload('e_tatib'),
            'student_id' => $student->id,
            'etatib_record_ids' => [$record->id],
        ]);

        $case = BkCase::query()->firstOrFail();
        $response->assertRedirect(route('cases.show', $case));
        $this->assertMatchesRegularExpression('/^K-2026-\d{4}$/', $case->registration_number);
        $this->assertSame('baru', $case->status->code);
        $this->assertDatabaseHas('case_assignments', [
            'case_id' => $case->id,
            'user_id' => $teacher->id,
            'assignment_type' => CaseAssignment::TYPE_OWNER,
        ]);
        $this->assertDatabaseHas('case_etatib_links', ['case_id' => $case->id, 'external_tatib_record_id' => $record->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'case.created', 'auditable_id' => $case->id]);
    }

    public function test_dual_role_teacher_only_sees_and_links_etatib_for_students_in_teacher_scope(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $teacher->roles()->attach(Role::query()->where('slug', 'koordinator_bk')->firstOrFail());
        $outsideStudent = Student::query()->create([
            'nisn' => '0099999999',
            'name' => 'Murid Di Luar Scope',
            'is_active' => true,
        ]);
        $scopedRecord = $this->etatibRecord($student, 'ET-SCOPE', 'Pelanggaran Dalam Scope');
        $outsideRecord = ExternalTatibRecord::query()->create([
            'source_identifier' => 'ET-FORGED',
            'nisn' => $student->nisn,
            'student_id' => $outsideStudent->id,
            'occurred_at' => '2026-08-18 10:00:00',
            'violation_type' => 'Pelanggaran Di Luar Scope',
            'category' => 'Kedisiplinan',
            'points' => 10,
            'is_active' => true,
            'synced_at' => now(),
        ]);

        $this->actingAs($teacher)->get(route('cases.create'))
            ->assertOk()
            ->assertSee($scopedRecord->violation_type)
            ->assertDontSee($outsideRecord->violation_type);

        $this->actingAs($teacher)
            ->from(route('cases.create'))
            ->post(route('cases.store'), [
                ...$this->casePayload('e_tatib'),
                'student_id' => $student->id,
                'etatib_record_ids' => [$outsideRecord->id],
            ])
            ->assertSessionHasErrors('etatib_record_ids.0');

        try {
            app(CaseService::class)->createCase([
                ...$this->casePayload('e_tatib'),
                'student_id' => $student->id,
                'etatib_record_ids' => [$outsideRecord->id],
            ], $teacher);
            $this->fail('Service menerima ID e-Tatib hasil forge dari murid di luar scope.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('etatib_record_ids', $exception->errors());
        }

        $this->assertDatabaseCount('cases', 0);
    }

    public function test_temporary_identity_only_links_active_unmapped_etatib_with_exact_nisn(): void
    {
        [$teacher] = $this->teacherAndScopedStudent();
        $mappedStudent = Student::query()->create([
            'nisn' => '0077777777',
            'name' => 'Murid Master Lain',
            'is_active' => true,
        ]);
        $mappedRecord = ExternalTatibRecord::query()->create([
            'source_identifier' => 'ET-MAPPED',
            'nisn' => '0088888888',
            'student_id' => $mappedStudent->id,
            'occurred_at' => '2026-08-18 10:00:00',
            'violation_type' => 'Record Sudah Dipetakan',
            'category' => 'Kedisiplinan',
            'points' => 10,
            'is_active' => true,
            'synced_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->from(route('cases.create'))
            ->post(route('cases.store'), [
                ...$this->casePayload('e_tatib'),
                'temporary_nisn' => '0088888888',
                'temporary_name' => 'Identitas Sementara',
                'etatib_record_ids' => [$mappedRecord->id],
            ])
            ->assertSessionHasErrors('etatib_record_ids.0');

        try {
            app(CaseService::class)->createCase([
                ...$this->casePayload('e_tatib'),
                'temporary_nisn' => '0088888888',
                'temporary_name' => 'Identitas Sementara',
                'etatib_record_ids' => [$mappedRecord->id],
            ], $teacher);
            $this->fail('Service menerima record e-Tatib yang sudah dipetakan melalui identitas sementara.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('etatib_record_ids', $exception->errors());
        }

        $unmappedRecord = ExternalTatibRecord::query()->create([
            'source_identifier' => 'ET-UNMAPPED',
            'nisn' => '0088888888',
            'student_id' => null,
            'occurred_at' => '2026-08-18 11:00:00',
            'violation_type' => 'Record Belum Dipetakan',
            'category' => 'Kedisiplinan',
            'points' => 5,
            'is_active' => true,
            'synced_at' => now(),
        ]);

        $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload('e_tatib'),
            'temporary_nisn' => '0088888888',
            'temporary_name' => 'Identitas Sementara',
            'etatib_record_ids' => [$unmappedRecord->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('case_etatib_links', [
            'external_tatib_record_id' => $unmappedRecord->id,
        ]);
        $this->assertDatabaseCount('cases', 1);
    }

    public function test_case_identity_and_scope_invariants_are_enforced(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $outside = Student::query()->create(['nisn' => '0099999999', 'name' => 'Murid Luar', 'is_active' => true]);

        $this->actingAs($teacher)
            ->from(route('cases.create'))
            ->post(route('cases.store'), [...$this->casePayload(), 'student_id' => $outside->id])
            ->assertSessionHasErrors('student_id');

        $this->actingAs($teacher)
            ->from(route('cases.create'))
            ->post(route('cases.store'), [
                ...$this->casePayload(),
                'temporary_nisn' => $student->nisn,
                'temporary_name' => 'Nama Duplikat',
            ])
            ->assertSessionHasErrors('temporary_nisn');

        $this->actingAs($teacher)
            ->post(route('cases.store'), [
                ...$this->casePayload(),
                'temporary_nisn' => '0088888888',
                'temporary_name' => 'Identitas Sementara',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('temporary_students', ['nisn' => '0088888888', 'input_name' => 'Identitas Sementara']);
        $this->assertDatabaseCount('cases', 1);
    }

    public function test_object_policy_redacts_internal_notes_and_waka_access_is_audited(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $coordinator = $this->userWithRole('koordinator_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $admin = $this->userWithRole('admin_it');
        $case = $this->createCase($teacher, $student, 'CATATAN-RAHASIA');

        $coordination = app(CaseService::class)->coordinate($case, [
            'waka_user_id' => $waka->id,
            'coordination_need' => 'Perlu koordinasi tata kelola.',
        ], $teacher);

        $this->actingAs($teacher)->get(route('cases.show', $case))->assertOk()->assertSee('CATATAN-RAHASIA');
        $this->actingAs($coordinator)->get(route('cases.show', $case))->assertOk()->assertDontSee('CATATAN-RAHASIA');
        $this->actingAs($waka)->get(route('cases.show', $case))->assertOk()->assertDontSee('CATATAN-RAHASIA')->assertDontSee('Tambah Tindak Lanjut');
        $this->actingAs($admin)->get(route('cases.show', $case))->assertForbidden();
        $this->actingAs($admin)->get(route('cases.index'))->assertForbidden();
        $this->actingAs($waka)->patch(route('cases.coordinations.update', [$case, $coordination]), [
            'status_id' => $this->reference('coordination_status', 'selesai')->id,
            'result' => 'Waka tidak boleh menulis hasil.',
        ])->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'case.viewed_by_waka',
            'actor_id' => $waka->id,
            'auditable_id' => $case->id,
        ]);
    }

    public function test_first_follow_up_starts_handling_and_successor_cannot_edit_old_note(): void
    {
        [$firstTeacher, $student] = $this->teacherAndScopedStudent();
        $secondTeacher = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($firstTeacher, $student);
        $type = $this->reference('follow_up_type', 'konsultasi_individual');
        $scheduled = $this->reference('follow_up_status', 'terjadwal');
        $executed = $this->reference('follow_up_status', 'terlaksana');

        try {
            app(FollowUpService::class)->record($case, [
                'follow_up_type_id' => $type->id,
                'status_id' => $executed->id,
                'planned_date' => '2026-08-19',
            ], $firstTeacher);
            $this->fail('Validasi kondisional tindak lanjut tidak dijalankan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('execution_date', $exception->errors());
            $this->assertArrayHasKey('result', $exception->errors());
        }

        $followUp = app(FollowUpService::class)->record($case, [
            'follow_up_type_id' => $type->id,
            'status_id' => $scheduled->id,
            'planned_date' => '2026-08-19',
            'next_plan' => 'Pertemuan berikutnya.',
        ], $firstTeacher);

        $this->assertSame('dalam_penanganan', $case->refresh()->status->code);
        $this->assertDatabaseHas('audit_logs', ['action' => 'case.handling_started', 'auditable_id' => $case->id]);

        app(AssignmentService::class)->assignCase($case, [
            'assignment_type' => 'transfer',
            'to_user_id' => $secondTeacher->id,
            'reason' => 'Penyesuaian beban layanan.',
            'effective_date' => '2026-08-20',
        ], $coordinator);

        $this->actingAs($secondTeacher)
            ->from(route('cases.show', $case))
            ->patch(route('cases.follow-ups.update', [$case, $followUp]), [
                'follow_up_type_id' => $type->id,
                'status_id' => $scheduled->id,
                'planned_date' => '2026-08-20',
            ])
            ->assertSessionHasErrors('follow_up');
    }

    public function test_transfer_closes_owner_history_while_additional_assignment_keeps_owner(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $successor = $this->userWithRole('guru_bk');
        $additional = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($owner, $student);
        $service = app(AssignmentService::class);

        $extra = $service->assignCase($case, [
            'assignment_type' => 'additional',
            'to_user_id' => $additional->id,
            'reason' => 'Pendampingan khusus.',
            'effective_date' => '2026-08-19',
        ], $coordinator);
        $transfer = $service->assignCase($case, [
            'assignment_type' => 'transfer',
            'to_user_id' => $successor->id,
            'reason' => 'Pengalihan eksplisit.',
            'effective_date' => '2026-08-20',
        ], $coordinator);

        $oldOwner = CaseAssignment::query()->where('case_id', $case->id)->where('user_id', $owner->id)->firstOrFail();
        $this->assertSame('2026-08-19', $oldOwner->effective_until?->toDateString());
        $this->assertSame(CaseAssignment::TYPE_ADDITIONAL, $extra->assignment_type);
        $this->assertNull($extra->effective_until);
        $this->assertSame(CaseAssignment::TYPE_OWNER, $transfer->assignment_type);
        $this->assertDatabaseHas('audit_logs', ['action' => 'case.transferred', 'auditable_id' => $case->id]);
        $this->assertDatabaseCount('case_assignments', 3);
    }

    public function test_academic_year_rollover_does_not_transfer_active_case_ownership_automatically(): void
    {
        $this->travelTo('2026-08-20 08:00:00');
        [$oldTeacher, $student] = $this->teacherAndScopedStudent();
        $newTeacher = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($oldTeacher, $student, 'Catatan tetap milik penanggung jawab kasus.');
        $newYear = AcademicYear::query()->create([
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'is_active' => true,
        ]);
        $newClass = Classroom::query()->create([
            'academic_year_id' => $newYear->id,
            'name' => 'XI RPL 1',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $newClass->id,
            'academic_year_id' => $newYear->id,
            'effective_from' => '2027-07-01',
            'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $newTeacher->id,
            'classroom_id' => $newClass->id,
            'academic_year_id' => $newYear->id,
            'effective_from' => '2027-07-01',
            'decision_number' => 'SK-GURU-BK-2027',
            'assigned_by' => $coordinator->id,
        ]);

        $this->travelTo('2027-07-01 08:00:00');

        $this->assertTrue($oldTeacher->can('update', $case));
        $this->assertTrue($newTeacher->can('view', $case));
        $this->assertFalse($newTeacher->can('update', $case));
        $this->assertDatabaseCount('case_assignments', 1);

        app(AssignmentService::class)->assignCase($case, [
            'assignment_type' => 'transfer',
            'to_user_id' => $newTeacher->id,
            'reason' => 'Alih tanggung jawab setelah pergantian tahun ajaran.',
            'effective_date' => '2027-07-01',
        ], $coordinator);

        $this->assertFalse($oldTeacher->can('update', $case));
        $this->assertTrue($newTeacher->can('update', $case));
        $this->assertDatabaseHas('case_assignments', [
            'case_id' => $case->id,
            'user_id' => $oldTeacher->id,
            'effective_until' => '2027-06-30 00:00:00',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'case.transferred',
            'auditable_id' => $case->id,
        ]);
        $this->assertDatabaseCount('case_assignments', 2);
    }

    public function test_resolution_keeps_history_and_blocks_further_mutation_or_transfer(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $coordinator = $this->userWithRole('koordinator_bk');
        $otherTeacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $student);

        $this->actingAs($teacher)->post(route('cases.resolve', $case), [
            'closed_at' => '2026-08-20',
            'final_result' => 'Tujuan layanan tercapai.',
            'resolution_summary' => 'Kasus ditutup setelah asesmen dan tindak lanjut.',
            'continued_plan' => 'Pemantauan berkala.',
        ])->assertRedirect(route('cases.show', $case));

        $case->refresh();
        $this->assertSame('selesai', $case->status->code);
        $this->assertNotNull($case->closed_at);
        $this->actingAs($teacher)->get(route('cases.show', $case))->assertOk()->assertSee('Tujuan layanan tercapai.');
        $this->actingAs($teacher)->get(route('cases.follow-ups.create', $case))->assertForbidden();
        $this->actingAs($coordinator)->post(route('cases.assign', $case), [
            'assignment_type' => 'transfer',
            'to_user_id' => $otherTeacher->id,
            'reason' => 'Tidak boleh terjadi.',
            'effective_date' => '2026-08-20',
        ])->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'case.resolved', 'auditable_id' => $case->id]);
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

    private function createCase(User $teacher, Student $student, ?string $internalNote = null): BkCase
    {
        return app(CaseService::class)->createCase([
            ...$this->casePayload(),
            'student_id' => $student->id,
            'internal_note' => $internalNote,
        ], $teacher);
    }

    private function etatibRecord(Student $student, string $identifier, string $violation): ExternalTatibRecord
    {
        return ExternalTatibRecord::query()->create([
            'source_identifier' => $identifier,
            'nisn' => $student->nisn,
            'student_id' => $student->id,
            'occurred_at' => '2026-08-18 09:00:00',
            'violation_type' => $violation,
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
