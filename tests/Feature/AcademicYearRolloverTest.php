<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\StudentDeparture;
use App\Models\TeacherAssignment;
use App\Models\TemporaryStudent;
use App\Models\User;
use App\Services\AcademicYearPreparationService;
use App\Services\AcademicYearRolloverQuery;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AcademicYearRolloverTest extends TestCase
{
    #[Test]
    public function data_master_only_shows_active_and_upcoming_academic_years(): void
    {
        $this->academicYear('2024/2025', '2024-07-01', '2025-06-30');
        $this->academicYear('2025/2026', '2025-07-01', '2026-06-30');
        $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $this->academicYear('2027/2028', '2027-07-01', '2028-06-30');

        $this->actingAs($this->userWithRole('admin_it'))
            ->get(route('data-master.index'))
            ->assertOk()
            ->assertSee('2026/2027')
            ->assertSee('2027/2028')
            ->assertSee('Aktif')
            ->assertSee('Belum Aktif')
            ->assertDontSee('2024/2025')
            ->assertDontSee('2025/2026')
            ->assertDontSee('Dibuat Admin IT');
    }

    #[Test]
    public function database_rejects_a_second_active_academic_year(): void
    {
        $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);

        $this->expectException(QueryException::class);
        $this->academicYear('2027/2028', '2027-07-01', '2028-06-30', true);
    }

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    #[Test]
    public function admin_can_delete_an_empty_preparation_year_but_not_one_with_classes(): void
    {
        $admin = $this->userWithRole('admin_it');
        $empty = $this->academicYear(
            '2027/2028', '2027-07-01', '2028-06-30',
            masterSource: AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        );
        $filled = $this->academicYear(
            '2028/2029', '2028-07-01', '2029-06-30',
            masterSource: AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        );
        $this->classroom($filled, 'X RPL 1');

        $this->actingAs($this->userWithRole('koordinator_bk'))
            ->delete(route('data-master.academic-years.destroy', $empty))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('data-master.academic-years.destroy', $empty))
            ->assertRedirect(route('data-master.index'));
        $this->assertDatabaseMissing('academic_years', ['id' => $empty->id]);
        $deletionAudit = AuditLog::query()
            ->where('action', 'academic_year.preparation_deleted')
            ->sole();
        $this->assertSame($empty->id, $deletionAudit->auditable_id);
        $this->assertSame($empty->name, $deletionAudit->before_values['name']);

        $this->actingAs($admin)
            ->delete(route('data-master.academic-years.destroy', $filled))
            ->assertSessionHasErrors('academic_year');
        $this->assertDatabaseHas('academic_years', ['id' => $filled->id]);

        $active = $this->academicYear(
            '2029/2030', '2029-07-01', '2030-06-30', true,
            AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        );
        $this->actingAs($admin)
            ->delete(route('data-master.academic-years.destroy', $active))
            ->assertSessionHasErrors('academic_year');
    }

    #[Test]
    public function admin_can_cancel_an_unactivated_year_and_its_provisional_roster_without_deleting_shared_students(): void
    {
        $admin = $this->userWithRole('admin_it');
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $previous = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $previousClassroom = $this->classroom($previous, 'X RPL 1');
        $shared = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Lama',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
        ]);
        $previousMembership = $this->membership($shared, $previousClassroom, $previous);
        $target = app(AcademicYearPreparationService::class)->prepareAcademicYear([
            'name' => '2027/2028',
            'preparation_reference' => 'Kalender sekolah',
        ], $admin);
        app(AcademicYearPreparationService::class)->importRosterPayload([
            'success' => true,
            'message' => 'OK',
            'data' => [
                ['nisn' => '0012345678', 'nama' => 'Murid Lama', 'rombel' => 'XI RPL 1', 'tahun_pelajaran' => '2027/2028'],
                ['nisn' => '0098765432', 'nama' => 'Murid Baru', 'rombel' => 'XI RPL 1', 'tahun_pelajaran' => '2027/2028'],
            ],
        ], $admin);
        $targetClassroom = Classroom::query()->where('academic_year_id', $target->id)->sole();
        $assignment = $this->assignTeacher($teacher, $coordinator, $targetClassroom, $target);
        $newStudent = Student::query()->where('nisn', '0098765432')->sole();

        $this->actingAs($admin)->get(route('data-master.index'))
            ->assertOk()
            ->assertSee('Batalkan persiapan');
        $this->actingAs($admin)
            ->delete(route('data-master.academic-years.destroy', $target))
            ->assertRedirect(route('data-master.index'));

        $this->assertDatabaseMissing('academic_years', ['id' => $target->id]);
        $this->assertDatabaseMissing('classrooms', ['id' => $targetClassroom->id]);
        $this->assertDatabaseMissing('teacher_assignments', ['id' => $assignment->id]);
        $this->assertDatabaseMissing('students', ['id' => $newStudent->id]);
        $this->assertDatabaseHas('students', ['id' => $shared->id]);
        $this->assertDatabaseHas('student_class_memberships', ['id' => $previousMembership->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic_year.preparation_deleted',
            'auditable_id' => $target->id,
        ]);
    }

    #[Test]
    public function cancellation_rejects_a_preparation_year_with_archived_service_history(): void
    {
        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');
        $year = $this->academicYear(
            '2027/2028', '2027-07-01', '2028-06-30',
            masterSource: AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        );
        $classroom = $this->classroom($year, 'X RPL 1');
        $classroom->update(['master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL]);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid', 'is_active' => true]);
        $membership = $this->membership($student, $classroom, $year);
        $membership->update(['master_source' => StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL]);
        $referenceId = ReferenceValue::query()->firstOrFail()->id;
        BkCase::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'case_source_id' => $referenceId,
            'service_field_id' => $referenceId,
            'status_id' => $referenceId,
            'service_date' => today(),
            'initial_info' => 'Catatan awal',
            'initial_action' => 'Pendampingan',
            'created_by' => $teacher->id,
        ])->delete();

        $this->actingAs($admin)
            ->delete(route('data-master.academic-years.destroy', $year))
            ->assertSessionHasErrors('academic_year');
        $this->assertDatabaseHas('academic_years', ['id' => $year->id]);
        $this->assertDatabaseHas('student_class_memberships', ['id' => $membership->id]);
    }

    #[Test]
    public function coordinator_can_restore_the_previous_year_before_services_are_recorded(): void
    {
        $this->travelTo('2027-07-01 09:00:00');
        $previous = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $previous->update(['activated_at' => now()->subYear()]);
        $current = $this->academicYear('2027/2028', '2027-07-01', '2028-06-30');
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid', 'is_active' => true]);
        foreach ([$previous, $current] as $year) {
            $classroom = $this->classroom($year, 'X RPL 1');
            $this->membership($student, $classroom, $year);
            $this->assignTeacher($teacher, $coordinator, $classroom, $year);
        }
        app(AcademicYearPreparationService::class)->activate($current, $coordinator);

        $this->actingAs($this->userWithRole('admin_it'))
            ->post(route('assignments.academic-years.restore-previous', $current))
            ->assertForbidden();

        $this->actingAs($coordinator)
            ->post(route('assignments.academic-years.restore-previous', $current))
            ->assertRedirect(route('assignments.classes.index', ['academic_year_id' => $previous->id]));

        $this->assertTrue($previous->refresh()->is_active);
        $this->assertFalse($current->refresh()->is_active);
        $this->assertNotNull($current->activated_at);
        $this->assertSame(1, AcademicYear::query()->active()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'academic_year.activation_reverted']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'academic_year.reactivated']);
    }

    #[Test]
    public function operational_activity_after_activation_blocks_restoring_the_previous_year(): void
    {
        $this->travelTo('2027-07-01 09:00:00');
        $previous = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $previous->update(['activated_at' => now()->subYear()]);
        $current = $this->academicYear('2027/2028', '2027-07-01', '2028-06-30');
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid', 'is_active' => true]);
        foreach ([$previous, $current] as $year) {
            $classroom = $this->classroom($year, 'X RPL 1');
            $this->membership($student, $classroom, $year);
            $this->assignTeacher($teacher, $coordinator, $classroom, $year);
        }
        app(AcademicYearPreparationService::class)->activate($current, $coordinator);
        $this->travel(1)->minutes();
        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'status' => StudentDeparture::STATUS_IN_PROGRESS,
            'reported_at' => today(),
        ]);

        $this->actingAs($coordinator)
            ->post(route('assignments.academic-years.restore-previous', $current))
            ->assertSessionHasErrors('academic_year');
        $this->assertTrue($current->refresh()->is_active);
        $this->assertFalse($previous->refresh()->is_active);
    }

    #[Test]
    public function soft_deleted_case_still_blocks_restoring_the_previous_year(): void
    {
        $this->travelTo('2027-07-01 09:00:00');
        $previous = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $previous->update(['activated_at' => now()->subYear()]);
        $current = $this->academicYear('2027/2028', '2027-07-01', '2028-06-30');
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid', 'is_active' => true]);
        foreach ([$previous, $current] as $year) {
            $classroom = $this->classroom($year, 'X RPL 1');
            $this->membership($student, $classroom, $year);
            $this->assignTeacher($teacher, $coordinator, $classroom, $year);
        }
        app(AcademicYearPreparationService::class)->activate($current, $coordinator);
        $referenceId = ReferenceValue::query()->firstOrFail()->id;
        BkCase::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $current->id,
            'classroom_id' => $current->classrooms()->firstOrFail()->id,
            'case_source_id' => $referenceId,
            'service_field_id' => $referenceId,
            'status_id' => $referenceId,
            'service_date' => today(),
            'referrer' => 'Guru',
            'initial_info' => 'Catatan awal',
            'initial_action' => 'Pendampingan',
            'created_by' => $teacher->id,
        ])->delete();

        $this->actingAs($coordinator)
            ->post(route('assignments.academic-years.restore-previous', $current))
            ->assertSessionHasErrors('academic_year');
        $this->assertTrue($current->refresh()->is_active);
    }

    #[Test]
    public function ambiguous_previous_year_blocks_rollback(): void
    {
        $first = $this->academicYear('2025/2026', '2025-07-01', '2026-06-30');
        $second = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30');
        $current = $this->academicYear('2027/2028', '2027-07-01', '2028-06-30', true);
        $first->update(['activated_at' => now()->subYear()]);
        $second->update(['activated_at' => now()->subYear()]);
        $current->update(['activated_at' => now()]);

        $this->actingAs($this->userWithRole('koordinator_bk'))
            ->post(route('assignments.academic-years.restore-previous', $current))
            ->assertSessionHasErrors('academic_year');
        $this->assertTrue($current->refresh()->is_active);
    }

    #[Test]
    public function rollover_summary_reports_active_students_without_target_membership(): void
    {
        $sourceYear = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $targetYear = $this->academicYear('2027/2028', '2027-07-01', '2028-06-30');
        $sourceClassroom = $this->classroom($sourceYear, 'XII RPL 1');
        $targetClassroom = $this->classroom($targetYear, 'X RPL 1');

        $needsConfirmation = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Perlu Konfirmasi',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
        ]);
        $alreadyPlaced = Student::query()->create([
            'nisn' => '0098765432',
            'name' => 'Murid Sudah Ditempatkan',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
        ]);
        $inactiveStudent = Student::query()->create([
            'nisn' => '0000000003',
            'name' => 'Murid Nonaktif',
            'is_active' => false,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
        ]);

        foreach ([$needsConfirmation, $alreadyPlaced, $inactiveStudent] as $student) {
            $this->membership($student, $sourceClassroom, $sourceYear);
        }
        $this->membership($alreadyPlaced, $targetClassroom, $targetYear);

        $beforeCounts = [
            'students' => Student::query()->count(),
            'memberships' => StudentClassMembership::query()->count(),
            'audits' => AuditLog::query()->count(),
        ];

        $summary = app(AcademicYearRolloverQuery::class)->summarize($targetYear);

        $this->assertSame($targetYear->id, $summary->targetYearId);
        $this->assertSame($sourceYear->id, $summary->sourceYearId);
        $this->assertSame('2026/2027', $summary->sourceYearName);
        $this->assertSame(1, $summary->needsConfirmationCount());
        $this->assertSame([
            [
                'student_id' => $needsConfirmation->id,
                'nisn' => '0012345678',
                'student_name' => 'Murid Perlu Konfirmasi',
                'source_classroom' => 'XII RPL 1',
            ],
        ], $summary->needsConfirmation);
        $this->assertSame($beforeCounts, [
            'students' => Student::query()->count(),
            'memberships' => StudentClassMembership::query()->count(),
            'audits' => AuditLog::query()->count(),
        ]);

        $this->membership($needsConfirmation, $targetClassroom, $targetYear);
        $updatedSummary = app(AcademicYearRolloverQuery::class)->summarize($targetYear);

        $this->assertSame(0, $updatedSummary->needsConfirmationCount());
        $this->assertSame([], $updatedSummary->needsConfirmation);
    }

    #[Test]
    public function complete_preparation_year_can_be_activated_without_calendar_gate(): void
    {
        $this->travelTo('2027-06-30 09:00:00');
        $sourceYear = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $targetYear = $this->academicYear('2027/2028', '2027-07-01', '2028-06-30');
        $sourceClassroom = $this->classroom($sourceYear, 'XI RPL 1');
        $targetClassroom = $this->classroom($targetYear, 'XII RPL 1');
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Terdaftar',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
        ]);
        $this->membership($student, $sourceClassroom, $sourceYear);
        $this->membership($student, $targetClassroom, $targetYear);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $targetClassroom->id,
            'academic_year_id' => $targetYear->id,
            'assigned_by' => $coordinator->id,
        ]);

        $service = app(AcademicYearPreparationService::class);
        $readiness = $service->activationReadiness($targetYear);

        $this->assertSame('ready', $readiness['state']);
        $this->assertTrue($readiness['ready']);
        $this->assertSame([], $readiness['issues']);
        $service->activate($targetYear, $coordinator);
        $this->assertFalse($sourceYear->refresh()->is_active);
        $this->assertTrue($targetYear->refresh()->is_active);
    }

    #[Test]
    public function activation_allows_an_empty_classroom_with_an_assigned_teacher(): void
    {
        $this->travelTo('2027-07-01 09:00:00');
        $sourceYear = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $targetYear = $this->academicYear('2027/2028', '2027-07-01', '2028-06-30');
        $filledClassroom = $this->classroom($targetYear, 'X RPL 1');
        $emptyClassroom = $this->classroom($targetYear, 'X RPL 2');
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Terdaftar',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
        ]);
        $this->membership($student, $filledClassroom, $targetYear);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $this->assignTeacher($teacher, $coordinator, $filledClassroom, $targetYear);
        $this->assignTeacher($teacher, $coordinator, $emptyClassroom, $targetYear);

        $service = app(AcademicYearPreparationService::class);
        $readiness = $service->activationReadiness($targetYear);

        $this->assertSame('ready', $readiness['state']);
        $this->assertTrue($readiness['ready']);
        $this->assertSame([], $readiness['issues']);
        $emptyClassroomReadiness = $readiness['classrooms']->first(
            fn (array $row): bool => $row['classroom']->is($emptyClassroom),
        );
        $this->assertNotNull($emptyClassroomReadiness);
        $this->assertSame(0, $emptyClassroomReadiness['student_count']);
        $this->assertTrue($emptyClassroomReadiness['ready']);

        $service->activate($targetYear, $coordinator);
        $this->assertFalse($sourceYear->refresh()->is_active);
        $this->assertTrue($targetYear->refresh()->is_active);
    }

    #[Test]
    public function database_rejects_multiple_memberships_for_one_student_and_year(): void
    {
        $this->travelTo('2027-07-01 09:00:00');
        $sourceYear = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $targetYear = $this->academicYear('2027/2028', '2027-07-01', '2028-06-30');
        $firstClassroom = $this->classroom($targetYear, 'X RPL 1');
        $secondClassroom = $this->classroom($targetYear, 'X RPL 2');
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Penempatan Ganda',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
        ]);
        $this->membership($student, $firstClassroom, $targetYear);
        $this->expectException(QueryException::class);
        $this->membership($student, $secondClassroom, $targetYear);
    }

    #[Test]
    public function rollover_and_provisional_identity_warnings_do_not_block_activation(): void
    {
        $this->travelTo('2027-07-01 09:00:00');
        $sourceYear = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $targetYear = $this->academicYear(
            '2027/2028',
            '2027-07-01',
            '2028-06-30',
            masterSource: AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        );
        $sourceClassroom = $this->classroom($sourceYear, 'XII RPL 1');
        $targetClassroom = $this->classroom($targetYear, 'X RPL 1');
        $missingStudent = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Perlu Konfirmasi',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
        ]);
        $placedStudent = Student::query()->create([
            'nisn' => '0098765432',
            'name' => 'Murid Tahun Target',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $this->membership($missingStudent, $sourceClassroom, $sourceYear);
        $this->membership($placedStudent, $targetClassroom, $targetYear);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $this->assignTeacher($teacher, $coordinator, $targetClassroom, $targetYear);
        TemporaryStudent::query()->create([
            'nisn' => '0000000003',
            'input_name' => 'Identitas Sementara',
            'created_by' => $teacher->id,
            'reconciliation_status_id' => ReferenceValue::query()
                ->where('category', 'reconciliation_status')
                ->where('code', 'menunggu_rekonsiliasi')
                ->firstOrFail()
                ->id,
        ]);

        $readiness = app(AcademicYearPreparationService::class)->activationReadiness($targetYear);

        $this->assertSame('ready', $readiness['state']);
        $this->assertTrue($readiness['ready']);
        $this->assertSame([], $readiness['issues']);
        $this->assertSame([
            '1 murid dari tahun ajaran sebelumnya perlu dikonfirmasi.',
            'Data tahun ajaran masih menggunakan sumber persiapan sementara.',
            'Identitas sementara masih menunggu rekonsiliasi.',
        ], $readiness['warnings']);
        $this->assertSame(1, $readiness['rollover']->needsConfirmationCount());
    }

    #[Test]
    public function verified_year_warns_when_target_roster_still_contains_provisional_data(): void
    {
        $this->travelTo('2027-07-01 09:00:00');
        $targetYear = $this->academicYear('2027/2028', '2027-07-01', '2028-06-30');
        $targetClassroom = $this->classroom($targetYear, 'X RPL 1');
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Persiapan',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $membership = $this->membership($student, $targetClassroom, $targetYear);
        $membership->update([
            'master_source' => StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $this->assignTeacher($teacher, $coordinator, $targetClassroom, $targetYear);

        $readiness = app(AcademicYearPreparationService::class)->activationReadiness($targetYear);

        $this->assertTrue($readiness['ready']);
        $this->assertContains(
            'Data tahun ajaran masih menggunakan sumber persiapan sementara.',
            $readiness['warnings'],
        );
    }

    #[Test]
    public function coordinator_sees_activation_button_for_ready_preparation_year(): void
    {
        $this->travelTo('2027-06-30 09:00:00');
        $sourceYear = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $targetYear = $this->academicYear('2027/2028', '2027-07-01', '2028-06-30');
        $targetClassroom = $this->classroom($targetYear, 'X RPL 1');
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Terdaftar',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
        ]);
        $this->membership($student, $targetClassroom, $targetYear);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $this->assignTeacher($teacher, $coordinator, $targetClassroom, $targetYear);

        $this->actingAs($coordinator)
            ->get(route('assignments.classes.index', ['academic_year_id' => $targetYear->id]))
            ->assertOk()
            ->assertSee('X RPL 1')
            ->assertSee('Aktifkan Tahun Ajaran');

        $this->actingAs($coordinator)
            ->from(route('assignments.classes.manage', ['academic_year_id' => $targetYear->id]))
            ->post(route('assignments.academic-years.activate', $targetYear))
            ->assertRedirect();
        $this->assertFalse($sourceYear->refresh()->is_active);
        $this->assertTrue($targetYear->refresh()->is_active);
    }

    #[Test]
    public function calendar_end_does_not_block_preparation_year_activation(): void
    {
        $this->travelTo('2028-07-01 09:00:00');
        $sourceYear = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $targetYear = $this->academicYear('2027/2028', '2027-07-01', '2028-06-30');
        $targetClassroom = $this->classroom($targetYear, 'X RPL 1');
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Terdaftar',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
        ]);
        $this->membership($student, $targetClassroom, $targetYear);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $this->assignTeacher($teacher, $coordinator, $targetClassroom, $targetYear);

        $this->actingAs($coordinator)
            ->get(route('assignments.classes.index', ['academic_year_id' => $targetYear->id]))
            ->assertOk()
            ->assertSee('X RPL 1')
            ->assertSee('Aktifkan Tahun Ajaran');
        $this->actingAs($coordinator)
            ->from(route('assignments.classes.manage', ['academic_year_id' => $targetYear->id]))
            ->post(route('assignments.academic-years.activate', $targetYear))
            ->assertRedirect();

        $this->assertFalse($sourceYear->refresh()->is_active);
        $this->assertTrue($targetYear->refresh()->is_active);
    }

    #[Test]
    public function admin_sees_students_needing_confirmation_on_data_master(): void
    {
        $sourceYear = $this->academicYear('2026/2027', '2026-07-01', '2027-06-30', true);
        $targetYear = $this->academicYear(
            '2027/2028',
            '2027-07-01',
            '2028-06-30',
            masterSource: AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        );
        $sourceClassroom = $this->classroom($sourceYear, 'XII RPL 1');
        $targetClassroom = $this->classroom($targetYear, 'X RPL 1');
        $needsConfirmation = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Perlu Konfirmasi',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
        ]);
        $alreadyPlaced = Student::query()->create([
            'nisn' => '0098765432',
            'name' => 'Murid Sudah Ditempatkan',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
        ]);
        $this->membership($needsConfirmation, $sourceClassroom, $sourceYear);
        $this->membership($alreadyPlaced, $sourceClassroom, $sourceYear);
        $this->membership($alreadyPlaced, $targetClassroom, $targetYear);

        $this->actingAs($this->userWithRole('admin_it'))
            ->get(route('data-master.index'))
            ->assertOk()
            ->assertSee('Murid Tahun Sebelumnya Belum Tercantum')
            ->assertSee('Periksa API Siswa')
            ->assertSee('Lihat daftar 1 murid')
            ->assertSee('0012345678')
            ->assertSee('Murid Perlu Konfirmasi')
            ->assertSee('XII RPL 1')
            ->assertDontSee('Murid Sudah Ditempatkan');
    }

    private function academicYear(
        string $name,
        string $startsOn,
        string $endsOn,
        bool $active = false,
        string $masterSource = AcademicYear::MASTER_SOURCE_DAPODIK,
    ): AcademicYear {
        return AcademicYear::query()->create([
            'name' => $name,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'is_active' => $active,
            'master_source' => $masterSource,
        ]);
    }

    private function classroom(AcademicYear $year, string $name): Classroom
    {
        return Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => $name,
            'is_active' => true,
            'master_source' => Classroom::MASTER_SOURCE_DAPODIK,
        ]);
    }

    private function membership(
        Student $student,
        Classroom $classroom,
        AcademicYear $year,
    ): StudentClassMembership {
        return StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
            'master_source' => StudentClassMembership::MASTER_SOURCE_DAPODIK,
        ]);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }

    private function assignTeacher(
        User $teacher,
        User $coordinator,
        Classroom $classroom,
        AcademicYear $year,
    ): TeacherAssignment {
        return TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'assigned_by' => $coordinator->id,
        ]);
    }
}
