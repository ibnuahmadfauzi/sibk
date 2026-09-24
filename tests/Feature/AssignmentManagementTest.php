<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\CaseService;
use App\Services\OperationalReportRecapService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_assignment_updates_one_state_and_same_teacher_is_no_op(): void
    {
        [$year, $classroom] = $this->masterContext();
        $coordinator = $this->userWithRole('koordinator_bk');
        $firstTeacher = $this->userWithRole('guru_bk');
        $secondTeacher = $this->userWithRole('guru_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $payload = ['classroom_id' => $classroom->id, 'user_id' => $firstTeacher->id];

        $this->actingAs($waka)->post(route('assignments.classes.store'), $payload)->assertForbidden();
        $this->actingAs($coordinator)->post(route('assignments.classes.store'), $payload)
            ->assertRedirect(route('assignments.classes.index', ['academic_year_id' => $year->id]));
        $this->assertDatabaseCount('audit_logs', 1);

        $this->actingAs($coordinator)->post(route('assignments.classes.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('audit_logs', 1);

        app(AssignmentService::class)->assignClass([
            'classroom_id' => $classroom->id,
            'user_id' => $secondTeacher->id,
        ], $coordinator);

        $this->assertDatabaseCount('teacher_assignments', 1);
        $this->assertDatabaseHas('teacher_assignments', [
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'user_id' => $secondTeacher->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'class_assignment.updated']);
    }

    public function test_request_rejects_forged_year_and_list_shows_unassigned_classes(): void
    {
        [$year, $classroom] = $this->masterContext();
        Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 2']);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');

        $this->actingAs($coordinator)->post(route('assignments.classes.store'), [
            'classroom_id' => $classroom->id,
            'user_id' => $teacher->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-01',
        ])->assertSessionHasErrors('academic_year_id');
        $this->actingAs($coordinator)->post(route('assignments.classes.store'), [
            'classroom_id' => $classroom->id,
            'user_id' => $teacher->id,
            'effective_from' => '2026-07-01',
        ])->assertSessionHasErrors('effective_from');
        $this->assertDatabaseCount('teacher_assignments', 0);

        $this->actingAs($coordinator)->get(route('assignments.classes.index'))
            ->assertOk()
            ->assertSee('X RPL 1')
            ->assertSee('X RPL 2')
            ->assertSee('aria-label="Atur Guru BK untuk X RPL 1"', false)
            ->assertSee('data-bs-target="#classAssignmentModal"', false)
            ->assertSee('Tahun ajaran ini sudah aktif.');
        $this->actingAs($coordinator)->get(route('assignments.classes.index', ['status' => 'unassigned']))
            ->assertOk()->assertSee('X RPL 2');
    }

    public function test_preparation_assignment_does_not_grant_student_scope(): void
    {
        [$year, $classroom] = $this->masterContext(false);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Persiapan']);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
        ]);
        app(AssignmentService::class)->assignClass([
            'classroom_id' => $classroom->id,
            'user_id' => $teacher->id,
        ], $coordinator);

        $this->assertFalse(Student::query()->forActiveTeacherAssignment($teacher)->whereKey($student)->exists());
        $this->actingAs($coordinator)->get(route('assignments.classes.index', ['academic_year_id' => $year->id]))
            ->assertSee('Aktifkan Tahun Ajaran')
            ->assertSee('action="'.route('assignments.academic-years.activate', $year).'"', false);
        $year->update(['is_active' => true]);
        $this->assertTrue(Student::query()->forActiveTeacherAssignment($teacher)->whereKey($student)->exists());
    }

    public function test_preparation_year_without_dates_can_be_activated_when_classes_are_ready(): void
    {
        $year = AcademicYear::query()->create(['name' => '2027/2028', 'is_active' => false]);
        $classroom = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 1']);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Baru', 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        app(AssignmentService::class)->assignClass([
            'classroom_id' => $classroom->id,
            'user_id' => $teacher->id,
        ], $coordinator);

        $this->actingAs($coordinator)->get(route('assignments.classes.index', ['academic_year_id' => $year->id]))
            ->assertOk()
            ->assertSee('Aktifkan Tahun Ajaran');
        $this->actingAs($coordinator)->post(route('assignments.academic-years.activate', $year))
            ->assertRedirect();
        $this->assertTrue($year->refresh()->is_active);
    }

    public function test_changing_class_teacher_keeps_case_owner_and_service_snapshot(): void
    {
        [$year, $classroom] = $this->masterContext();
        $coordinator = $this->userWithRole('koordinator_bk');
        $owner = $this->userWithRole('guru_bk');
        $nextTeacher = $this->userWithRole('guru_bk');
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Kasus']);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
        ]);
        app(AssignmentService::class)->assignClass(['classroom_id' => $classroom->id, 'user_id' => $owner->id], $coordinator);

        $case = app(CaseService::class)->createCase([
            'student_id' => $student->id,
            'case_source_id' => ReferenceValue::query()->forCategory('case_source')->where('code', 'temuan_guru_bk')->firstOrFail()->id,
            'service_field_id' => ReferenceValue::query()->forCategory('service_field')->where('code', 'pribadi')->firstOrFail()->id,
            'service_date' => today()->toDateString(),
            'initial_info' => 'Informasi awal.',
            'initial_action' => 'Asesmen awal.',
        ], $owner);

        app(AssignmentService::class)->assignClass(['classroom_id' => $classroom->id, 'user_id' => $nextTeacher->id], $coordinator);

        $this->assertSame($owner->id, $case->ownerAssignment()?->user_id);
        $this->assertSame($classroom->id, $case->fresh()->classroom_id);
        $this->assertSame($year->id, $case->fresh()->academic_year_id);
        $this->assertTrue(BkCase::query()->accessibleTo($owner)->whereKey($case)->exists());
        $this->assertTrue(Student::query()->forActiveTeacherAssignment($nextTeacher)->whereKey($student)->exists());
        $report = app(OperationalReportRecapService::class)->paginateForUi($owner, [
            'academic_year_id' => $year->id,
        ]);
        $this->assertSame($classroom->name, $report['rows']->first()['classroom']);
    }

    /** @return array{AcademicYear, Classroom} */
    private function masterContext(bool $active = true): array
    {
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => $active,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'X RPL 1',
        ]);

        return [$year, $classroom];
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
