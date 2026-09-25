<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\OperationalReportRecapService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalReportRecapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_report_uses_service_class_snapshot_after_membership_moves(): void
    {
        [$year, $classroom, $student, $owner, $case] = $this->caseFixture();
        $nextClassroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'XI RPL 2',
        ]);
        $student->classMemberships()->firstOrFail()->update(['classroom_id' => $nextClassroom->id]);

        $report = app(OperationalReportRecapService::class)->paginateForUi($owner, [
            'academic_year_id' => $year->id,
            'service_type' => 'case',
        ]);

        $this->assertSame(1, $report['rows']->total());
        $this->assertSame($classroom->name, $report['rows']->first()['classroom']);
        $this->assertSame($classroom->id, $case->fresh()->classroom_id);
        $this->assertSame($owner->id, $case->ownerAssignment()?->user_id);
    }

    public function test_report_access_matches_current_role_contract(): void
    {
        [, , , $owner] = $this->caseFixture();
        $coordinator = $this->userWithRole('koordinator_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $admin = $this->userWithRole('admin_it');

        $this->actingAs($owner)->get(route('reports.index'))->assertOk();
        $this->actingAs($coordinator)->get(route('reports.index'))->assertOk();
        $this->actingAs($waka)->get(route('reports.index'))->assertOk();
        $this->actingAs($waka)->get(route('reports.preview'))->assertForbidden();
        $this->actingAs($waka)->get(route('reports.export', ['format' => 'xlsx']))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.index'))->assertForbidden();
    }

    /** @return array{AcademicYear, Classroom, Student, User, BkCase} */
    private function caseFixture(): array
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
        ]);
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Laporan',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $owner = $this->userWithRole('guru_bk');
        TeacherAssignment::query()->create([
            'user_id' => $owner->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'assigned_by' => $owner->id,
        ]);
        $case = BkCase::query()->create([
            'registration_number' => 'K-2026-0001',
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'case_source_id' => ReferenceValue::query()->forCategory('case_source')->firstOrFail()->id,
            'service_field_id' => ReferenceValue::query()->forCategory('service_field')->firstOrFail()->id,
            'status_id' => ReferenceValue::query()->forCategory('case_status')->firstOrFail()->id,
            'service_date' => '2026-09-01',
            'initial_info' => 'Informasi awal.',
            'initial_action' => 'Asesmen awal.',
            'created_by' => $owner->id,
        ]);
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $owner->id,
            'reason' => 'Pemilik saat pencatatan.',
            'assigned_by' => $owner->id,
        ]);

        return [$year, $classroom, $student, $owner, $case];
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
