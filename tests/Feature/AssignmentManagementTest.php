<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AssignmentService;
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

    public function test_only_coordinator_can_open_manage_page_and_store_assignment(): void
    {
        [$year, $classroom] = $this->masterContext();
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $waka = $this->userWithRole('waka_kesiswaan');

        $this->actingAs($coordinator)
            ->get(route('assignments.classes.manage'))
            ->assertOk()
            ->assertSee('Atur Penugasan Kelas');

        $this->actingAs($waka)
            ->get(route('assignments.classes.manage'))
            ->assertForbidden();

        $payload = [
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'decision_number' => 'SK-001/2026',
            'effective_date' => '2026-07-15',
        ];

        $this->actingAs($waka)
            ->post(route('assignments.classes.store'), $payload)
            ->assertForbidden();

        $this->actingAs($coordinator)
            ->post(route('assignments.classes.store'), $payload)
            ->assertRedirect(route('assignments.classes.index', ['academic_year_id' => $year->id]));

        $this->assertDatabaseHas('teacher_assignments', [
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'decision_number' => 'SK-001/2026',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'class_assignment.created']);
    }

    public function test_midyear_assignment_closes_previous_period_without_overwriting_history(): void
    {
        [$year, $classroom] = $this->masterContext();
        $coordinator = $this->userWithRole('koordinator_bk');
        $firstTeacher = $this->userWithRole('guru_bk');
        $secondTeacher = $this->userWithRole('guru_bk');
        $service = app(AssignmentService::class);

        $first = $service->assignClass([
            'user_id' => $firstTeacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'decision_number' => 'SK-AWAL',
            'effective_date' => '2026-07-15',
        ], $coordinator);

        $second = $service->assignClass([
            'user_id' => $secondTeacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'decision_number' => 'SK-PERUBAHAN',
            'effective_date' => '2027-01-01',
        ], $coordinator);

        $this->assertSame('2026-12-31', $first->refresh()->effective_until?->toDateString());
        $this->assertSame('2027-01-01', $second->effective_from->toDateString());
        $this->assertSame(2, TeacherAssignment::query()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'class_assignment.closed']);
    }

    public function test_inactive_teacher_and_overlapping_period_are_rejected(): void
    {
        [$year, $classroom] = $this->masterContext();
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $inactiveTeacher = $this->userWithRole('guru_bk', false);

        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-15',
            'effective_until' => '2026-12-31',
            'decision_number' => 'SK-001',
            'assigned_by' => $coordinator->id,
        ]);

        $base = [
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'decision_number' => 'SK-002',
            'effective_date' => '2026-07-15',
        ];

        $this->actingAs($coordinator)
            ->from(route('assignments.classes.manage'))
            ->post(route('assignments.classes.store'), [...$base, 'user_id' => $teacher->id])
            ->assertSessionHasErrors('effective_date');

        $this->actingAs($coordinator)
            ->from(route('assignments.classes.manage'))
            ->post(route('assignments.classes.store'), [
                ...$base,
                'user_id' => $inactiveTeacher->id,
                'effective_date' => '2027-01-01',
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_teacher_list_and_student_scope_only_include_active_assignment(): void
    {
        [$year, $classroom] = $this->masterContext();
        $otherClass = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'X RPL 2',
        ]);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $otherTeacher = $this->userWithRole('guru_bk');
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Scope']);
        $outsideStudent = Student::query()->create(['nisn' => '0098765432', 'name' => 'Murid Luar']);

        foreach ([[$student, $classroom], [$outsideStudent, $otherClass]] as [$member, $memberClass]) {
            StudentClassMembership::query()->create([
                'student_id' => $member->id,
                'classroom_id' => $memberClass->id,
                'academic_year_id' => $year->id,
                'effective_from' => '2026-07-15',
            ]);
        }

        foreach ([[$teacher, $classroom], [$otherTeacher, $otherClass]] as [$assignedTeacher, $assignedClass]) {
            TeacherAssignment::query()->create([
                'user_id' => $assignedTeacher->id,
                'classroom_id' => $assignedClass->id,
                'academic_year_id' => $year->id,
                'effective_from' => '2026-07-15',
                'decision_number' => 'SK-SCOPE',
                'assigned_by' => $coordinator->id,
            ]);
        }

        $scopedIds = Student::query()
            ->forActiveTeacherAssignment($teacher, '2026-08-20')
            ->pluck('id');

        $this->assertTrue($scopedIds->contains($student->id));
        $this->assertFalse($scopedIds->contains($outsideStudent->id));

        $this->actingAs($coordinator)
            ->get(route('assignments.classes.index'))
            ->assertOk()
            ->assertSee('X RPL 1')
            ->assertSee('X RPL 2');

        $this->actingAs($teacher)
            ->get(route('assignments.classes.index'))
            ->assertForbidden();
    }

    public function test_academic_year_rollover_moves_class_scope_without_extending_open_ended_old_periods(): void
    {
        [$oldYear, $oldClass] = $this->masterContext();
        $oldYear->update(['is_active' => false]);
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
        $coordinator = $this->userWithRole('koordinator_bk');
        $oldTeacher = $this->userWithRole('guru_bk');
        $newTeacher = $this->userWithRole('guru_bk');
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Naik Kelas',
            'is_active' => true,
        ]);

        foreach ([
            [$oldYear, $oldClass, '2026-07-01'],
            [$newYear, $newClass, '2027-07-01'],
        ] as [$year, $classroom, $effectiveFrom]) {
            StudentClassMembership::query()->create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => $year->id,
                'effective_from' => $effectiveFrom,
                'effective_until' => null,
                'is_active' => true,
            ]);
        }

        foreach ([
            [$oldTeacher, $oldYear, $oldClass, '2026-07-01', 'SK-2026'],
            [$newTeacher, $newYear, $newClass, '2027-07-01', 'SK-2027'],
        ] as [$teacher, $year, $classroom, $effectiveFrom, $decisionNumber]) {
            TeacherAssignment::query()->create([
                'user_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => $year->id,
                'effective_from' => $effectiveFrom,
                'effective_until' => null,
                'decision_number' => $decisionNumber,
                'assigned_by' => $coordinator->id,
            ]);
        }

        $this->assertFalse(Student::query()->forActiveTeacherAssignment($oldTeacher, '2027-06-30')->whereKey($student)->exists());
        $this->assertFalse(Student::query()->forActiveTeacherAssignment($newTeacher, '2027-06-30')->whereKey($student)->exists());
        $this->assertFalse(Student::query()->forActiveTeacherAssignment($oldTeacher, '2027-07-01')->whereKey($student)->exists());
        $this->assertTrue(Student::query()->forActiveTeacherAssignment($newTeacher, '2027-07-01')->whereKey($student)->exists());

        $this->assertFalse($oldTeacher->teacherAssignments()->effectiveOn('2027-07-01')->exists());
        $this->assertFalse($student->classMemberships()->whereKey(
            $student->classMemberships()->oldest('effective_from')->value('id'),
        )->effectiveOn('2027-07-01')->exists());
        $this->assertSame(2, $student->classMemberships()->count());
        $this->assertSame(2, TeacherAssignment::query()->count());

        $this->travelTo('2027-07-01 08:00:00');
        $this->actingAs($oldTeacher)->get(route('students.show', $student))->assertForbidden();
        $this->actingAs($newTeacher)->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('XI RPL 1')
            ->assertSee('X RPL 1');
    }

    public function test_assignment_status_distinguishes_scheduled_active_and_ended_across_year_boundaries(): void
    {
        $this->travelTo('2027-07-15 08:00:00');
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $endedYear = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $activeYear = AcademicYear::query()->create([
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'is_active' => true,
        ]);
        $futureYear = AcademicYear::query()->create([
            'name' => '2028/2029',
            'starts_on' => '2028-07-01',
            'ends_on' => '2029-06-30',
            'is_active' => true,
        ]);

        foreach ([
            [$endedYear, 'X RPL Berakhir', '2026-07-01', 'SK-END'],
            [$activeYear, 'XI RPL Aktif', '2027-07-01', 'SK-ACTIVE'],
            [$futureYear, 'XII RPL Terjadwal', '2028-07-01', 'SK-SCHEDULED'],
        ] as [$year, $className, $effectiveFrom, $decisionNumber]) {
            $classroom = Classroom::query()->create([
                'academic_year_id' => $year->id,
                'name' => $className,
                'is_active' => true,
            ]);
            TeacherAssignment::query()->create([
                'user_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => $year->id,
                'effective_from' => $effectiveFrom,
                'effective_until' => null,
                'decision_number' => $decisionNumber,
                'assigned_by' => $coordinator->id,
            ]);
        }

        $this->actingAs($coordinator)->get(route('assignments.classes.index'))
            ->assertOk()
            ->assertSeeInOrder(['XII RPL Terjadwal', 'Terjadwal', 'XI RPL Aktif', 'Aktif', 'X RPL Berakhir', '30 Jun 2027', 'Berakhir']);

        $this->actingAs($coordinator)->get(route('assignments.classes.index', ['status' => 'terjadwal']))
            ->assertSee('XII RPL Terjadwal')
            ->assertDontSee('XI RPL Aktif')
            ->assertDontSee('X RPL Berakhir');

        $this->actingAs($coordinator)->get(route('assignments.classes.index', ['status' => 'berakhir']))
            ->assertSee('X RPL Berakhir')
            ->assertDontSee('XI RPL Aktif')
            ->assertDontSee('XII RPL Terjadwal');
    }

    public function test_manage_form_only_offers_classes_from_selected_year_and_rejects_forged_pair(): void
    {
        [$firstYear, $firstClass] = $this->masterContext();
        $secondYear = AcademicYear::query()->create([
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'is_active' => true,
        ]);
        $secondClass = Classroom::query()->create([
            'academic_year_id' => $secondYear->id,
            'name' => 'XI TKJ Pasangan Tahun Kedua',
            'is_active' => true,
        ]);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');

        $this->actingAs($coordinator)->get(route('assignments.classes.manage', [
            'academic_year_id' => $firstYear->id,
        ]))
            ->assertOk()
            ->assertSee($firstClass->name)
            ->assertDontSee($secondClass->name);

        $this->actingAs($coordinator)
            ->from(route('assignments.classes.manage', ['academic_year_id' => $firstYear->id]))
            ->post(route('assignments.classes.store'), [
                'user_id' => $teacher->id,
                'classroom_id' => $secondClass->id,
                'academic_year_id' => $firstYear->id,
                'decision_number' => 'SK-FORGED-PAIR',
                'effective_date' => '2026-08-01',
            ])
            ->assertSessionHasErrors('classroom_id');

        $this->assertDatabaseCount('teacher_assignments', 0);
    }

    /** @return array{AcademicYear, Classroom} */
    private function masterContext(): array
    {
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'X RPL 1',
            'is_active' => true,
        ]);

        return [$year, $classroom];
    }

    private function userWithRole(string $slug, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
