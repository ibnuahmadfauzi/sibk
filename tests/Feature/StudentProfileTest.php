<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
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
            ->assertSee('Konsultasi');

        $this->actingAs($otherTeacher)->get(route('students.index'))
            ->assertOk()
            ->assertDontSee($student->name);
        $this->actingAs($otherTeacher)->get(route('students.show', $student))->assertForbidden();
    }

    public function test_waka_uses_safe_portal_instead_of_generic_student_profile(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $waka = $this->userWithRole('waka_kesiswaan');
        $case = $this->createCase($teacher, $student);
        $this->createConsultation($teacher, $student);
        $linkedRecord = $this->etatibRecord($student, 'ET-TERKAIT', 'Pelanggaran terkait');
        $unlinkedRecord = $this->etatibRecord($student, 'ET-LAIN', 'Pelanggaran lain');
        $case->etatibRecords()->attach($linkedRecord->id, ['linked_by' => $teacher->id]);

        $this->actingAs($waka)->get(route('waka.monitoring.students'))
            ->assertOk()
            ->assertSee($student->name)
            ->assertDontSee($student->nisn)
            ->assertDontSee('Pelanggaran terkait')
            ->assertDontSee('Pelanggaran lain')
            ->assertDontSee('Konsultasi')
            ->assertDontSee('Ringkasan konsultasi aman');
        $this->actingAs($waka)->get(route('students.index'))->assertForbidden();
        $this->actingAs($waka)->get(route('students.show', ['student' => $student, 'tab' => 'etatib']))->assertForbidden();

        $this->assertNotSame($linkedRecord->id, $unlinkedRecord->id);
    }

    public function test_coordinator_sees_consultation_fields_without_legacy_case_or_status_columns_while_admin_is_denied_profile(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $coordinator = $this->userWithRole('koordinator_bk');
        $admin = $this->userWithRole('admin_it');
        $this->createConsultation($teacher, $student);

        $this->actingAs($coordinator)->get(route('students.show', ['student' => $student, 'tab' => 'konsultasi']))
            ->assertOk()
            ->assertSee('Permasalahan')
            ->assertSee('Penanganan')
            ->assertSee('Hasil')
            ->assertDontSee('<th>Kasus</th>', false)
            ->assertDontSee('<th>Status</th>', false);
        $this->actingAs($admin)->get(route('students.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('students.show', $student))->assertForbidden();
    }

    public function test_profile_uses_neutral_service_labels_without_internal_numbers(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $case = $this->createCase($teacher, $student);
        $this->createConsultation($teacher, $student);
        $case->update(['registration_number' => 'K-INTERNAL-PROFILE']);

        $this->actingAs($teacher)->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Kasus dicatat')
            ->assertSee('Konsultasi dicatat')
            ->assertDontSee('K-INTERNAL-PROFILE')
            ->assertDontSee('<th>No.</th>', false);
        $this->get(route('students.show', ['student' => $student, 'tab' => 'kasus']))
            ->assertOk()
            ->assertDontSee('<th>No.</th>', false)
            ->assertDontSee('K-INTERNAL-PROFILE');
    }

    public function test_legacy_nisn_url_redirects_to_database_profile(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();

        $this->actingAs($teacher)->get(route('students.legacy', ['nisn' => $student->nisn, 'tab' => 'kasus']))
            ->assertRedirect(route('students.show', ['student' => $student, 'tab' => 'kasus']));
    }

    public function test_profile_keeps_class_history_after_year_is_deactivated(): void
    {
        $coordinator = $this->userWithRole('koordinator_bk');
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'X AKL Histori',
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'nisn' => '0066666666',
            'name' => 'Murid Masa Transisi',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);

        $this->travelTo('2027-07-15 08:00:00');
        $year->update(['is_active' => false]);

        $this->actingAs($coordinator)->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Tanpa kelas aktif')
            ->assertSee('X AKL Histori')
            ->assertSee('2026/2027')
            ->assertDontSee('s.d. sekarang');
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
            'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
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

    private function createConsultation(User $teacher, Student $student): Consultation
    {
        return app(ConsultationService::class)->create([
            'student_id' => $student->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'session_date' => '2026-08-20',
            'problem' => 'Permasalahan profil murid',
            'handling' => 'Penanganan profil murid',
            'result' => 'Hasil profil murid',
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
