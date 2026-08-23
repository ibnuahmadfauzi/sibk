<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseCoordination;
use App\Models\Classroom;
use App\Models\ExternalTatibRecord;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\CaseService;
use App\Services\ConsultationService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_teacher_list_and_profile_only_expose_professionally_accessible_students(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $otherTeacher = $this->userWithRole('guru_bk');
        $this->createConsultation($teacher, $student);

        $this->actingAs($teacher)->get(route('students.index'))
            ->assertOk()
            ->assertSee($student->name);
        $this->actingAs($teacher)->get(route('students.show', $student))
            ->assertOk()
            ->assertSee($student->nisn)
            ->assertSee('Konsultasi dan Tindak Lanjut');

        $this->actingAs($otherTeacher)->get(route('students.index'))
            ->assertOk()
            ->assertDontSee($student->name);
        $this->actingAs($otherTeacher)->get(route('students.show', $student))->assertForbidden();
    }

    public function test_waka_profile_is_limited_to_coordinated_case_and_linked_etatib_without_consultation(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $waka = $this->userWithRole('waka_kesiswaan');
        $case = $this->createCase($teacher, $student);
        $this->createConsultation($teacher, $student);
        CaseCoordination::query()->create([
            'case_id' => $case->id,
            'waka_user_id' => $waka->id,
            'status_id' => $this->reference('coordination_status', 'menunggu')->id,
            'coordination_need' => 'Koordinasi dukungan kedisiplinan.',
            'recorded_by' => $teacher->id,
            'coordinated_at' => now(),
        ]);
        $linkedRecord = $this->etatibRecord($student, 'ET-TERKAIT', 'Pelanggaran terkait');
        $unlinkedRecord = $this->etatibRecord($student, 'ET-LAIN', 'Pelanggaran lain');
        $case->etatibRecords()->attach($linkedRecord->id, ['linked_by' => $teacher->id]);

        $this->actingAs($waka)->get(route('students.index'))
            ->assertOk()
            ->assertSee($student->name);
        $this->actingAs($waka)->get(route('students.show', ['student' => $student, 'tab' => 'etatib']))
            ->assertOk()
            ->assertSee('Pelanggaran terkait')
            ->assertDontSee('Pelanggaran lain')
            ->assertDontSee('Konsultasi dan Tindak Lanjut')
            ->assertDontSee('Ringkasan konsultasi aman');

        $this->assertNotSame($linkedRecord->id, $unlinkedRecord->id);
    }

    public function test_coordinator_sees_general_consultation_summary_while_admin_is_denied_profile(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $coordinator = $this->userWithRole('koordinator_bk');
        $admin = $this->userWithRole('admin_it');
        $this->createConsultation($teacher, $student);

        $this->actingAs($coordinator)->get(route('students.show', ['student' => $student, 'tab' => 'konsultasi']))
            ->assertOk()
            ->assertSee('Ringkasan konsultasi aman')
            ->assertDontSee('Catatan konsultasi privat');
        $this->actingAs($admin)->get(route('students.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('students.show', $student))->assertForbidden();
    }

    public function test_legacy_nisn_url_redirects_to_database_profile(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();

        $this->actingAs($teacher)->get(route('students.legacy', ['nisn' => $student->nisn, 'tab' => 'kasus']))
            ->assertRedirect(route('students.show', ['student' => $student, 'tab' => 'kasus']));
    }

    /** @return array{User, Student} */
    private function teacherAndScopedStudent(): array
    {
        $teacher = $this->userWithRole('guru_bk');
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
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Profil Database',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-15',
            'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-15',
            'decision_number' => 'SK-PROFIL',
            'assigned_by' => $teacher->id,
        ]);

        return [$teacher, $student];
    }

    private function createCase(User $teacher, Student $student): BkCase
    {
        return app(CaseService::class)->createCase([
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2026-08-01',
            'initial_info' => 'Informasi awal profil.',
            'initial_action' => 'Asesmen awal profil.',
        ], $teacher);
    }

    private function createConsultation(User $teacher, Student $student): void
    {
        app(ConsultationService::class)->create([
            'student_id' => $student->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('consultation_status', 'terlaksana')->id,
            'topic' => 'Profil murid',
            'session_date' => '2026-08-20',
            'general_summary' => 'Ringkasan konsultasi aman',
            'sensitive_content' => 'Catatan konsultasi privat',
        ], $teacher);
    }

    private function etatibRecord(Student $student, string $identifier, string $violation): ExternalTatibRecord
    {
        return ExternalTatibRecord::query()->create([
            'source_identifier' => $identifier,
            'nisn' => $student->nisn,
            'student_id' => $student->id,
            'occurred_at' => '2026-08-10 08:00:00',
            'violation_type' => $violation,
            'category' => 'Kedisiplinan',
            'points' => 10,
            'source_status' => 'Aktif',
            'is_active' => true,
            'synced_at' => now(),
        ]);
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
