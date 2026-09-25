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
            ->assertRedirect(route('assignments.classes.index', ['academic_year_id' => $year->id]))
            ->assertSessionHas('success_title', 'Kelas ditugaskan')
            ->assertSessionHas('success', "{$classroom->name} kini diampu {$firstTeacher->name}.");
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

    public function test_request_rejects_forged_year_and_list_shows_unassigned_classes_in_add_menu(): void
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
            ->assertSee('aria-label="Tambah kelas untuk '.$teacher->name.'"', false)
            ->assertSee('id="classPickerSearch"', false)
            ->assertSee('action="'.route('assignments.classes.batch').'"', false)
            ->assertSee('data-class-picker-option="x rpl 2"', false)
            ->assertSee('Tahun ajaran ini sudah aktif.');
        $this->actingAs($coordinator)->get(route('assignments.classes.index', ['status' => 'unassigned']))
            ->assertOk()->assertSee($teacher->name);
    }

    public function test_preparation_year_requires_an_explicit_coordinator_choice(): void
    {
        [$activeYear] = $this->masterContext();
        $preparationYear = AcademicYear::query()->create([
            'name' => '2027/2028',
            'is_active' => false,
        ]);
        Classroom::query()->create([
            'academic_year_id' => $preparationYear->id,
            'name' => 'XI RPL 1',
        ]);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');

        $this->actingAs($coordinator)->get(route('assignments.classes.index'))
            ->assertOk()
            ->assertViewHas('selectedYear', fn ($year) => $year->is($activeYear))
            ->assertSee('Daftar Penugasan '.$activeYear->name)
            ->assertSee('Tahun penugasan')
            ->assertDontSee('id="academic_year_id"', false);

        $this->actingAs($coordinator)->get(route('assignments.classes.index', [
            'academic_year_id' => $preparationYear->id,
        ]))->assertOk()
            ->assertViewHas('selectedYear', fn ($year) => $year->is($preparationYear))
            ->assertSee('Daftar Penugasan '.$preparationYear->name)
            ->assertSee('type="hidden" name="academic_year_id" value="'.$preparationYear->id.'"', false)
            ->assertSee('XI RPL 1');

        $this->actingAs($teacher)->get(route('assignments.classes.index', [
            'academic_year_id' => $preparationYear->id,
        ]))->assertOk()
            ->assertViewHas('selectedYear', fn ($year) => $year->is($activeYear))
            ->assertDontSee('Tahun penugasan');
    }

    public function test_no_active_year_does_not_select_preparation_automatically(): void
    {
        [$preparationYear] = $this->masterContext(false);
        $coordinator = $this->userWithRole('koordinator_bk');

        $this->actingAs($coordinator)->get(route('assignments.classes.index'))
            ->assertOk()
            ->assertViewHas('selectedYear', null)
            ->assertSee('Belum ada tahun ajaran aktif untuk menampilkan penugasan.');

        $this->actingAs($coordinator)->get(route('assignments.classes.index', [
            'academic_year_id' => $preparationYear->id,
        ]))->assertOk()
            ->assertViewHas('selectedYear', fn ($year) => $year->is($preparationYear))
            ->assertSee('Daftar Penugasan '.$preparationYear->name);
    }

    public function test_batch_assignment_saves_selected_classes_together(): void
    {
        [$year, $firstClass] = $this->masterContext();
        $secondClass = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 2']);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $payload = [
            'user_id' => $teacher->id,
            'classroom_ids' => [$firstClass->id, $secondClass->id],
        ];

        $this->actingAs($waka)->post(route('assignments.classes.batch'), $payload)->assertForbidden();
        $this->actingAs($coordinator)->post(route('assignments.classes.batch'), $payload)
            ->assertRedirect(route('assignments.classes.index', ['academic_year_id' => $year->id]))
            ->assertSessionHas('success', "2 kelas ditambahkan untuk {$teacher->name}.");
        $this->assertDatabaseHas('teacher_assignments', ['classroom_id' => $firstClass->id, 'user_id' => $teacher->id]);
        $this->assertDatabaseHas('teacher_assignments', ['classroom_id' => $secondClass->id, 'user_id' => $teacher->id]);
        $this->assertDatabaseCount('audit_logs', 2);
    }

    public function test_batch_assignment_rolls_back_when_a_selected_class_is_taken(): void
    {
        [$year, $firstClass] = $this->masterContext();
        $secondClass = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 2']);
        $coordinator = $this->userWithRole('koordinator_bk');
        $firstTeacher = $this->userWithRole('guru_bk');
        $secondTeacher = $this->userWithRole('guru_bk');
        app(AssignmentService::class)->assignClass([
            'classroom_id' => $secondClass->id,
            'user_id' => $firstTeacher->id,
        ], $coordinator);

        $this->actingAs($coordinator)->post(route('assignments.classes.batch'), [
            'user_id' => $secondTeacher->id,
            'classroom_ids' => [$firstClass->id, $secondClass->id],
        ])->assertSessionHasErrors('classroom_id');
        $this->assertDatabaseMissing('teacher_assignments', ['classroom_id' => $firstClass->id]);
        $this->assertDatabaseHas('teacher_assignments', [
            'classroom_id' => $secondClass->id,
            'user_id' => $firstTeacher->id,
        ]);
        $this->assertDatabaseCount('audit_logs', 1);

        $otherYear = AcademicYear::query()->create(['name' => '2027/2028', 'is_active' => false]);
        $otherClass = Classroom::query()->create([
            'academic_year_id' => $otherYear->id,
            'name' => 'XI RPL 1',
        ]);
        $this->actingAs($coordinator)->post(route('assignments.classes.batch'), [
            'user_id' => $secondTeacher->id,
            'classroom_ids' => [$firstClass->id, $otherClass->id],
        ])->assertSessionHasErrors('classroom_ids');
        $this->assertDatabaseMissing('teacher_assignments', ['classroom_id' => $firstClass->id]);
        $this->assertDatabaseMissing('teacher_assignments', ['classroom_id' => $otherClass->id]);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_teacher_rows_count_active_students_and_filter_by_teacher_status(): void
    {
        [$year, $firstClass] = $this->masterContext();
        $secondClass = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 2']);
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $withoutClass = $this->userWithRole('guru_bk');
        foreach ([$firstClass, $secondClass] as $index => $classroom) {
            app(AssignmentService::class)->assignClass([
                'classroom_id' => $classroom->id,
                'user_id' => $teacher->id,
            ], $coordinator);
            for ($number = 0; $number <= $index; $number++) {
                $student = Student::query()->create([
                    'nisn' => sprintf('%010d', $index * 10 + $number + 1),
                    'name' => 'Murid '.$index.'-'.$number,
                ]);
                StudentClassMembership::query()->create([
                    'student_id' => $student->id,
                    'classroom_id' => $classroom->id,
                    'academic_year_id' => $year->id,
                ]);
            }
        }

        $this->actingAs($coordinator)->get(route('assignments.classes.index'))
            ->assertOk()->assertSee($teacher->name)->assertSee($withoutClass->name)
            ->assertSee('>3</td>', false);
        $this->actingAs($coordinator)->get(route('assignments.classes.index', ['status' => 'unassigned']))
            ->assertOk()->assertSee($withoutClass->name)->assertDontSee($teacher->name);
        $this->actingAs($teacher)->get(route('assignments.classes.index'))
            ->assertOk()->assertSee($teacher->name)->assertDontSee($withoutClass->name)
            ->assertDontSee('Tambah kelas untuk');
    }

    public function test_add_guard_and_cancel_reject_stale_requests_without_changing_owner(): void
    {
        [$year, $classroom] = $this->masterContext();
        $coordinator = $this->userWithRole('koordinator_bk');
        $firstTeacher = $this->userWithRole('guru_bk');
        $secondTeacher = $this->userWithRole('guru_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $payload = [
            'classroom_id' => $classroom->id,
            'user_id' => $firstTeacher->id,
            'only_if_unassigned' => 1,
        ];

        $this->actingAs($coordinator)->post(route('assignments.classes.store'), $payload)->assertRedirect();
        $this->actingAs($coordinator)->post(route('assignments.classes.store'), [
            ...$payload,
            'user_id' => $secondTeacher->id,
        ])->assertSessionHasErrors('classroom_id');
        $this->assertDatabaseHas('teacher_assignments', [
            'classroom_id' => $classroom->id,
            'user_id' => $firstTeacher->id,
        ]);

        $url = route('assignments.classes.destroy', $classroom);
        $this->actingAs($waka)->delete($url, ['user_id' => $firstTeacher->id])->assertForbidden();
        $this->actingAs($coordinator)->delete($url, ['user_id' => $secondTeacher->id])
            ->assertSessionHasErrors('classroom_id');
        $this->actingAs($coordinator)->delete($url, ['user_id' => $firstTeacher->id])
            ->assertRedirect(route('assignments.classes.index', ['academic_year_id' => $year->id]))
            ->assertSessionHas('success_title', 'Penugasan dibatalkan')
            ->assertSessionHas('success', "{$classroom->name} kembali tersedia untuk ditugaskan.");
        $this->assertDatabaseMissing('teacher_assignments', ['classroom_id' => $classroom->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'class_assignment.deleted']);
        $audit = AuditLog::query()->where('action', 'class_assignment.deleted')->firstOrFail();
        $this->assertSame($firstTeacher->id, $audit->before_values['user_id']);
        $this->assertNull($audit->after_values['user_id']);
        $this->actingAs($coordinator)->delete($url, ['user_id' => $firstTeacher->id])
            ->assertSessionHasErrors('classroom_id');

        app(AssignmentService::class)->assignClass([
            'classroom_id' => $classroom->id,
            'user_id' => $firstTeacher->id,
        ], $coordinator);
        $year->update(['is_active' => false, 'activated_at' => now()]);
        $this->actingAs($coordinator)->delete($url, ['user_id' => $firstTeacher->id])
            ->assertSessionHasErrors('classroom_id');
        $this->assertDatabaseHas('teacher_assignments', [
            'classroom_id' => $classroom->id,
            'user_id' => $firstTeacher->id,
        ]);
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
