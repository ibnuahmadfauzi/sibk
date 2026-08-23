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

        $this->actingAs($teacher)
            ->get(route('assignments.classes.index'))
            ->assertOk()
            ->assertSee('X RPL 1')
            ->assertDontSee('X RPL 2');
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
