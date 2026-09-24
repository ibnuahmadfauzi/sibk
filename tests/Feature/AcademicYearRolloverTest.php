<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
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
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
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
    public function activation_is_blocked_when_an_active_classroom_has_no_active_students(): void
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

        $this->assertSame('not_ready', $readiness['state']);
        $this->assertFalse($readiness['ready']);
        $this->assertContains(
            'Setiap rombel aktif harus memiliki minimal satu murid aktif.',
            $readiness['issues'],
        );
        $this->assertNotContains(
            'Setiap rombel harus memiliki tepat satu Guru BK aktif sejak awal tahun ajaran.',
            $readiness['issues'],
        );
        $emptyClassroomReadiness = $readiness['classrooms']->first(
            fn (array $row): bool => $row['classroom']->is($emptyClassroom),
        );
        $this->assertNotNull($emptyClassroomReadiness);
        $this->assertFalse($emptyClassroomReadiness['ready']);

        try {
            $service->activate($targetYear, $coordinator);
            $this->fail('Tahun ajaran dengan rombel kosong dapat diaktifkan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('students', $exception->errors());
        }

        $this->assertTrue($sourceYear->refresh()->is_active);
        $this->assertFalse($targetYear->refresh()->is_active);
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
            ->assertSee('Perlu Konfirmasi')
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
