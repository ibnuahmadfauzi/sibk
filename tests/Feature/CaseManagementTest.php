<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
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
use App\Models\UserNotification;
use App\Services\AssignmentService;
use App\Services\CaseService;
use App\Services\CorrectionService;
use App\Services\FollowUpService;
use App\Support\ServiceRecordStatus;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RuntimeException;
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
        $this->assertSame(ServiceRecordStatus::NEW, $case->status->code);
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

    public function test_unmapped_etatib_is_only_rendered_for_an_exact_valid_nisn_filter(): void
    {
        [$teacher] = $this->teacherAndScopedStudent();
        $matching = $this->unmappedEtatibRecord('ET-FILTER-MATCH', '0088888888', 'Filter Cocok');
        $other = $this->unmappedEtatibRecord('ET-FILTER-OTHER', '0077777777', 'Filter NISN Lain');
        $inactive = $this->unmappedEtatibRecord('ET-FILTER-INACTIVE', '0088888888', 'Filter Tidak Aktif', false);

        $this->actingAs($teacher)->get(route('cases.create'))
            ->assertOk()
            ->assertDontSee($matching->violation_type)
            ->assertDontSee($other->violation_type)
            ->assertDontSee($inactive->violation_type);

        $this->actingAs($teacher)->get(route('cases.create', ['temporary_nisn' => '0088888888']))
            ->assertOk()
            ->assertSee($matching->violation_type)
            ->assertDontSee($other->violation_type)
            ->assertDontSee($inactive->violation_type)
            ->assertSee('value="0088888888"', false);

        $this->actingAs($teacher)->get(route('cases.create', ['temporary_nisn' => '0088x']))
            ->assertOk()
            ->assertDontSee($matching->violation_type)
            ->assertDontSee('value="0088x"', false);
    }

    public function test_case_form_caps_rendered_etatib_candidates(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();

        for ($index = 0; $index <= 200; $index++) {
            $this->etatibRecord(
                $student,
                sprintf('ET-CAP-%03d', $index),
                sprintf('Pelanggaran Cap %03d', $index),
            );
        }

        $response = $this->actingAs($teacher)->get(route('cases.create'));

        $response->assertOk()
            ->assertSee('Pelanggaran Cap 200')
            ->assertDontSee('Pelanggaran Cap 000')
            ->assertSee('Daftar data e-Tatib dibatasi');
        $this->assertSame(200, substr_count($response->getContent(), 'name="etatib_record_ids[]"'));
    }

    public function test_inactive_master_student_is_rejected_by_http_and_case_service(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $student->update(['is_active' => false]);
        $payload = [...$this->casePayload(), 'student_id' => $student->id];

        $this->actingAs($teacher)
            ->from(route('cases.create'))
            ->post(route('cases.store'), $payload)
            ->assertSessionHasErrors('student_id');

        try {
            app(CaseService::class)->createCase($payload, $teacher);
            $this->fail('Service menerima murid master yang tidak aktif.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('student_id', $exception->errors());
        }

        $this->assertDatabaseCount('cases', 0);
    }

    public function test_temporary_identity_rejects_wrong_nisn_and_inactive_unmapped_rows_at_both_boundaries(): void
    {
        [$teacher] = $this->teacherAndScopedStudent();
        $wrongNisn = $this->unmappedEtatibRecord('ET-WRONG-NISN', '0077777777', 'NISN Tidak Cocok');
        $inactive = $this->unmappedEtatibRecord('ET-INACTIVE', '0088888888', 'Record Tidak Aktif', false);

        foreach ([$wrongNisn, $inactive] as $record) {
            $payload = [
                ...$this->casePayload('e_tatib'),
                'temporary_nisn' => '0088888888',
                'temporary_name' => 'Identitas Sementara',
                'etatib_record_ids' => [$record->id],
            ];

            $this->actingAs($teacher)
                ->from(route('cases.create'))
                ->post(route('cases.store'), $payload)
                ->assertSessionHasErrors('etatib_record_ids.0');

            try {
                app(CaseService::class)->createCase($payload, $teacher);
                $this->fail('Service menerima record e-Tatib sementara yang tidak aktif atau berbeda NISN.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('etatib_record_ids', $exception->errors());
            }
        }

        $this->assertDatabaseCount('cases', 0);
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
            'result' => 'Waka dan Guru BK menyepakati pemantauan kehadiran selama dua pekan.',
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

        $this->assertSame('selesai', $coordination->status->code);
        $this->assertSame('Waka dan Guru BK menyepakati pemantauan kehadiran selama dua pekan.', $coordination->result);
        $this->actingAs($teacher)->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee($coordination->result)
            ->assertDontSee('Pilih status akhir')
            ->assertDontSee('Perbarui');

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
        $case->update(['waka_summary' => 'Asesmen awal telah dilakukan dan tindak lanjut dijadwalkan.']);
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

        $this->assertSame(ServiceRecordStatus::IN_PROGRESS, $case->refresh()->status->code);
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

    public function test_transfer_keeps_legacy_additional_history_but_only_owner_can_mutate(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $successor = $this->userWithRole('guru_bk');
        $additional = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($owner, $student);
        $service = app(AssignmentService::class);

        try {
            $service->assignCase($case, [
                'assignment_type' => 'additional',
                'to_user_id' => $additional->id,
                'reason' => 'Pendampingan khusus.',
                'effective_date' => '2026-08-19',
            ], $coordinator);
            $this->fail('Assignment tambahan baru seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('assignment_type', $exception->errors());
        }

        $extra = CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $additional->id,
            'assignment_type' => CaseAssignment::TYPE_ADDITIONAL,
            'effective_from' => '2026-08-19',
            'reason' => 'Histori kewenangan tambahan lama.',
            'assigned_by' => $coordinator->id,
        ]);

        $this->assertTrue($case->hasActiveAssignmentFor($additional));
        $this->assertFalse($case->hasActiveOwnerFor($additional));
        $this->assertFalse($additional->can('update', $case));
        $this->assertSame($owner->id, $case->activeOwnerAssignment()?->user_id);

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
        $this->assertSame($successor->id, $case->activeOwnerAssignment()?->user_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'case.transferred', 'auditable_id' => $case->id]);
        $this->assertDatabaseCount('case_assignments', 3);
    }

    public function test_transfer_rejects_overlapping_active_owners(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $duplicateOwner = $this->userWithRole('guru_bk');
        $successor = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($owner, $student);
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $duplicateOwner->id,
            'assignment_type' => CaseAssignment::TYPE_OWNER,
            'effective_from' => '2026-08-10',
            'reason' => 'Data tumpang tindih untuk pengujian.',
            'assigned_by' => $coordinator->id,
        ]);

        try {
            app(AssignmentService::class)->assignCase($case, [
                'assignment_type' => 'transfer',
                'to_user_id' => $successor->id,
                'reason' => 'Pengalihan harus ditolak.',
                'effective_date' => '2026-08-20',
            ], $coordinator);
            $this->fail('Pengalihan dengan owner aktif tumpang tindih seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('effective_date', $exception->errors());
        }

        $this->assertDatabaseCount('case_assignments', 2);
        $this->assertDatabaseMissing('case_assignments', [
            'case_id' => $case->id,
            'user_id' => $successor->id,
        ]);
    }

    public function test_transfer_rolls_back_when_new_owner_creation_fails(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $successor = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($owner, $student);
        $ownerAssignment = $case->activeOwnerAssignment();
        $event = 'eloquent.creating: '.CaseAssignment::class;

        Event::listen($event, static function (CaseAssignment $assignment) use ($successor): void {
            if ($assignment->user_id === $successor->id) {
                throw new RuntimeException('Simulasi kegagalan pembuatan owner baru.');
            }
        });

        try {
            app(AssignmentService::class)->assignCase($case, [
                'assignment_type' => 'transfer',
                'to_user_id' => $successor->id,
                'reason' => 'Pengalihan dengan kegagalan parsial.',
                'effective_date' => '2026-08-20',
            ], $coordinator);
            $this->fail('Kegagalan pembuatan owner baru seharusnya diteruskan.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan pembuatan owner baru.', $exception->getMessage());
        } finally {
            Event::forget($event);
        }

        $this->assertNull($ownerAssignment?->refresh()->effective_until);
        $this->assertDatabaseCount('case_assignments', 1);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'case_assignment.closed']);
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
            'waka_summary' => 'Penanganan selesai dan dilanjutkan dengan pemantauan berkala.',
        ])->assertRedirect(route('cases.show', $case));

        $case->refresh();
        $this->assertSame(ServiceRecordStatus::COMPLETED, $case->status->code);
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

    public function test_case_cannot_leave_new_status_without_waka_summary(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $case = $this->createCase($teacher, $student);
        $type = $this->reference('follow_up_type', 'konsultasi_individual');
        $scheduled = $this->reference('follow_up_status', 'terjadwal');

        try {
            app(FollowUpService::class)->record($case, [
                'follow_up_type_id' => $type->id,
                'status_id' => $scheduled->id,
                'planned_date' => '2026-08-20',
            ], $teacher);
            $this->fail('Tindak lanjut pertama seharusnya ditolak tanpa ringkasan untuk Waka.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('waka_summary', $exception->errors());
        }

        try {
            app(CaseService::class)->deactivate($case, $teacher);
            $this->fail('Pembatalan seharusnya ditolak tanpa ringkasan untuk Waka.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('waka_summary', $exception->errors());
        }

        $this->actingAs($teacher)
            ->from(route('cases.resolve.form', $case))
            ->post(route('cases.resolve', $case), [
                'closed_at' => '2026-08-20',
                'final_result' => 'Kasus selesai.',
                'resolution_summary' => 'Ringkasan penyelesaian.',
                'waka_summary' => '',
            ])
            ->assertSessionHasErrors('waka_summary');

        $this->assertSame(ServiceRecordStatus::NEW, $case->refresh()->status->code);
        $this->assertDatabaseCount('follow_ups', 0);
    }

    public function test_resolution_persists_final_waka_summary(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $case = $this->createCase($teacher, $student);

        $this->actingAs($teacher)->post(route('cases.resolve', $case), [
            'closed_at' => '2026-08-20',
            'final_result' => 'Tujuan layanan tercapai.',
            'resolution_summary' => 'Kasus ditutup setelah asesmen.',
            'waka_summary' => 'Asesmen dan intervensi awal selesai; kondisi murid stabil.',
        ])->assertRedirect(route('cases.show', $case));

        $this->assertSame('Asesmen dan intervensi awal selesai; kondisi murid stabil.', $case->refresh()->waka_summary);
    }

    public function test_active_owner_can_edit_safe_case_fields_without_changing_identity(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $otherStudent = Student::query()->create([
            'nisn' => '0099999999',
            'name' => 'Murid Lain',
            'is_active' => true,
        ]);
        $case = $this->createCase($teacher, $student);
        $newSource = $this->reference('case_source', 'rujukan');
        $newField = $this->reference('service_field', 'sosial');
        $inProgress = $this->reference('case_status', ServiceRecordStatus::IN_PROGRESS);

        $this->actingAs($teacher)->get(route('cases.edit', $case))
            ->assertOk()
            ->assertSee('Ubah Kasus')
            ->assertSee($case->identityName())
            ->assertDontSee('name="student_id"', false)
            ->assertDontSee('name="temporary_student_id"', false)
            ->assertDontSee('name="registration_number"', false);

        $this->actingAs($teacher)->patch(route('cases.update', $case), [
            'case_source_id' => $newSource->id,
            'service_field_id' => $newField->id,
            'status_id' => $inProgress->id,
            'service_date' => '2026-08-02',
            'referrer' => 'Wali kelas',
            'initial_info' => 'Informasi awal diperbarui.',
            'initial_action' => 'Penanganan awal diperbarui.',
            'internal_note' => 'Catatan profesional diperbarui.',
            'waka_summary' => 'Guru BK melakukan asesmen awal dan menyusun tindak lanjut.',
            'student_id' => $otherStudent->id,
            'temporary_student_id' => 999,
            'registration_number' => 'K-FORGED',
            'created_by' => $otherStudent->id,
        ])->assertRedirect(route('cases.show', $case));

        $case->refresh();
        $this->assertSame($student->id, $case->student_id);
        $this->assertNull($case->temporary_student_id);
        $this->assertMatchesRegularExpression('/^K-2026-\d{4}$/', $case->registration_number);
        $this->assertSame($teacher->id, $case->created_by);
        $this->assertSame($newSource->id, $case->case_source_id);
        $this->assertSame($newField->id, $case->service_field_id);
        $this->assertSame($inProgress->id, $case->status_id);
        $this->assertSame('2026-08-02', $case->service_date->toDateString());
        $this->assertSame('Guru BK melakukan asesmen awal dan menyusun tindak lanjut.', $case->waka_summary);
        $this->assertDatabaseHas('audit_logs', ['action' => 'case.updated', 'auditable_id' => $case->id]);
    }

    public function test_new_case_allows_empty_waka_summary_but_later_status_requires_it(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $case = $this->createCase($teacher, $student);
        $new = $this->reference('case_status', ServiceRecordStatus::NEW);
        $inProgress = $this->reference('case_status', ServiceRecordStatus::IN_PROGRESS);

        $this->actingAs($teacher)->patch(route('cases.update', $case), [
            ...$this->caseUpdatePayload($case),
            'status_id' => $new->id,
            'waka_summary' => '',
        ])->assertRedirect(route('cases.show', $case));
        $this->assertNull($case->refresh()->waka_summary);

        $this->actingAs($teacher)
            ->from(route('cases.edit', $case))
            ->patch(route('cases.update', $case), [
                ...$this->caseUpdatePayload($case),
                'status_id' => $inProgress->id,
                'waka_summary' => '',
            ])
            ->assertSessionHasErrors('waka_summary');

        try {
            app(CaseService::class)->update($case, [
                ...$this->caseUpdatePayload($case),
                'status_id' => $inProgress->id,
                'waka_summary' => null,
            ], $teacher);
            $this->fail('Service menerima kasus berjalan tanpa ringkasan untuk Waka.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('waka_summary', $exception->errors());
        }

        $this->actingAs($teacher)
            ->from(route('cases.edit', $case))
            ->patch(route('cases.update', $case), [
                ...$this->caseUpdatePayload($case),
                'status_id' => $inProgress->id,
                'waka_summary' => str_repeat('A', 501),
            ])
            ->assertSessionHasErrors('waka_summary');
    }

    public function test_case_edit_preserves_etatib_source_link_invariant(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $case = $this->createCase($teacher, $student);
        $payload = [
            ...$this->caseUpdatePayload($case),
            'case_source_id' => $this->reference('case_source', 'e_tatib')->id,
        ];

        $this->actingAs($teacher)
            ->from(route('cases.edit', $case))
            ->patch(route('cases.update', $case), $payload)
            ->assertSessionHasErrors('case_source_id');

        try {
            app(CaseService::class)->update($case, $payload, $teacher);
            $this->fail('Service menerima sumber e-Tatib tanpa record resmi tertaut.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('case_source_id', $exception->errors());
        }

        $this->assertSame('temuan_guru_bk', $case->refresh()->source->code);
    }

    public function test_additional_assignment_cannot_open_or_submit_case_edit(): void
    {
        [$owner, $student] = $this->teacherAndScopedStudent();
        $additional = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($owner, $student);
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $additional->id,
            'assignment_type' => CaseAssignment::TYPE_ADDITIONAL,
            'effective_from' => '2026-08-01',
            'reason' => 'Histori kewenangan tambahan lama.',
            'assigned_by' => $coordinator->id,
        ]);

        $this->actingAs($additional)->get(route('cases.edit', $case))->assertForbidden();
        $this->actingAs($additional)->patch(route('cases.update', $case), $this->caseUpdatePayload($case))->assertForbidden();

        try {
            app(CaseService::class)->update($case, $this->caseUpdatePayload($case), $additional);
            $this->fail('Service menerima perubahan dari assignment tambahan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('case', $exception->errors());
        }

        try {
            app(CaseService::class)->coordinate($case, [
                'waka_user_id' => $this->userWithRole('waka_kesiswaan')->id,
                'result' => 'Hasil koordinasi tidak sah.',
            ], $additional);
            $this->fail('Service menerima koordinasi dari assignment tambahan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('case', $exception->errors());
        }
    }

    public function test_terminal_case_rejects_direct_edit_but_verified_correction_still_works(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($teacher, $student);
        $case->update(['status_id' => $this->reference('case_status', ServiceRecordStatus::COMPLETED)->id]);

        $this->actingAs($teacher)->get(route('cases.edit', $case))->assertForbidden();
        $this->actingAs($teacher)->patch(route('cases.update', $case), $this->caseUpdatePayload($case))->assertForbidden();

        try {
            app(CaseService::class)->update($case, $this->caseUpdatePayload($case), $teacher);
            $this->fail('Service menerima perubahan langsung pada kasus terminal.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('case', $exception->errors());
        }

        app(CaseService::class)->applyApprovedCorrection($case, 'initial_action', 'Koreksi terverifikasi.', $coordinator);
        app(CaseService::class)->applyApprovedCorrection($case, 'waka_summary', 'Ringkasan aman hasil koreksi.', $coordinator);

        $this->assertSame('Koreksi terverifikasi.', $case->refresh()->initial_action);
        $this->assertSame('Ringkasan aman hasil koreksi.', $case->waka_summary);
        $this->assertDatabaseHas('audit_logs', ['action' => 'case.corrected', 'auditable_id' => $case->id]);
    }

    public function test_case_edit_cannot_bypass_dedicated_terminal_actions(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $case = $this->createCase($teacher, $student);

        $this->actingAs($teacher)->get(route('cases.edit', $case))
            ->assertOk()
            ->assertDontSee($this->reference('case_status', ServiceRecordStatus::COMPLETED)->label)
            ->assertDontSee($this->reference('case_status', ServiceRecordStatus::CANCELLED)->label);
        $this->actingAs($teacher)->get(route('cases.index'))
            ->assertOk()
            ->assertSee($this->reference('case_status', ServiceRecordStatus::COMPLETED)->label)
            ->assertSee($this->reference('case_status', ServiceRecordStatus::CANCELLED)->label);

        foreach (ServiceRecordStatus::terminalCodes() as $terminalCode) {
            $payload = [
                ...$this->caseUpdatePayload($case),
                'status_id' => $this->reference('case_status', $terminalCode)->id,
                'waka_summary' => 'Ringkasan tersedia.',
            ];

            $this->actingAs($teacher)
                ->from(route('cases.edit', $case))
                ->patch(route('cases.update', $case), $payload)
                ->assertSessionHasErrors('status_id');

            try {
                app(CaseService::class)->update($case, $payload, $teacher);
                $this->fail('Edit biasa menerima status terminal.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('status_id', $exception->errors());
            }
        }

        $this->assertSame(ServiceRecordStatus::NEW, $case->refresh()->status->code);
    }

    public function test_internal_case_code_is_hidden_from_operational_outputs_and_search(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $coordinator = $this->userWithRole('koordinator_bk');
        $case = $this->createCase($teacher, $student);
        $caseCode = (string) $case->registration_number;

        $this->actingAs($teacher)->get(route('cases.index'))->assertOk()->assertDontSee($caseCode);
        $this->actingAs($teacher)->get(route('cases.show', $case))->assertOk()->assertDontSee($caseCode);
        $this->actingAs($teacher)->get(route('cases.follow-ups.create', $case))->assertOk()->assertDontSee($caseCode);
        $this->actingAs($teacher)->get(route('cases.resolve.form', $case))->assertOk()->assertDontSee($caseCode);
        $this->actingAs($teacher)->get(route('consultations.create'))->assertOk()->assertDontSee($caseCode);
        $this->actingAs($teacher)->get(route('corrections.create'))->assertOk()->assertDontSee($caseCode);
        $this->actingAs($coordinator)->get(route('assignments.cases.index', ['case_id' => $case->id]))->assertOk()->assertDontSee($caseCode);
        $this->actingAs($teacher)->get(route('cases.index', ['search' => $caseCode]))
            ->assertOk()
            ->assertDontSee($case->identityName());

        $case->update(['waka_summary' => 'Asesmen awal dan jadwal tindak lanjut telah disusun.']);
        app(FollowUpService::class)->record($case, [
            'follow_up_type_id' => $this->reference('follow_up_type', 'konsultasi_individual')->id,
            'status_id' => $this->reference('follow_up_status', 'terjadwal')->id,
            'planned_date' => '2026-08-20',
        ], $teacher);
        $correction = app(CorrectionService::class)->submit([
            'target_type' => 'case',
            'target_id' => $case->id,
            'field_name' => 'waka_summary',
            'proposed_value' => 'Ringkasan aman diperbarui.',
            'reason' => 'Perlu memperjelas perkembangan umum.',
        ], $teacher);

        $this->assertStringNotContainsString($caseCode, $correction->target_label);
        $this->assertFalse(AuditLog::query()->where('summary', 'like', '%'.$caseCode.'%')->exists());
        $this->assertFalse(UserNotification::query()->where('title', 'like', '%'.$caseCode.'%')->orWhere('message', 'like', '%'.$caseCode.'%')->exists());
        $this->assertDatabaseHas('cases', ['id' => $case->id, 'registration_number' => $caseCode]);
    }

    public function test_teacher_creates_case_with_string_request_payload(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();

        $response = $this->actingAs($teacher)->post(route('cases.store'), [
            'case_source_id' => (string) $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => (string) $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2026-08-01',
            'initial_info' => 'Informasi awal layanan string.',
            'initial_action' => 'Asesmen awal string.',
            'student_id' => (string) $student->id,
        ]);

        $case = BkCase::query()->where('initial_info', 'Informasi awal layanan string.')->firstOrFail();
        $response->assertRedirect(route('cases.show', $case));
        $this->assertSame($student->id, $case->student_id);
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

    private function unmappedEtatibRecord(
        string $identifier,
        string $nisn,
        string $violation,
        bool $active = true,
    ): ExternalTatibRecord {
        return ExternalTatibRecord::query()->create([
            'source_identifier' => $identifier,
            'nisn' => $nisn,
            'student_id' => null,
            'occurred_at' => '2026-08-18 11:00:00',
            'violation_type' => $violation,
            'category' => 'Kedisiplinan',
            'points' => 5,
            'is_active' => $active,
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
    private function caseUpdatePayload(BkCase $case): array
    {
        return [
            'case_source_id' => $case->case_source_id,
            'service_field_id' => $case->service_field_id,
            'status_id' => $case->status_id,
            'service_date' => $case->service_date->toDateString(),
            'referrer' => $case->referrer,
            'initial_info' => $case->initial_info,
            'initial_action' => $case->initial_action,
            'internal_note' => $case->internal_note,
            'waka_summary' => $case->waka_summary,
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
