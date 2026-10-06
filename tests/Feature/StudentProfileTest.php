<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\CaseFollowUp;
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

    public function test_student_names_are_title_cased_in_list_profile_and_search_without_changing_source(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $student->update(['name' => 'NADIA PUTRI']);

        $this->actingAs($teacher)->get(route('students.index', ['search' => 'NADIA']))
            ->assertOk()->assertSee('Nadia Putri')->assertDontSee('NADIA PUTRI')
            ->assertSeeInOrder(['<th>No</th>', '<th>Murid</th>', 'Permasalahan', 'Poin Pelanggaran', 'Konsultasi', 'Prestasi'], false)
            ->assertSeeInOrder(['Nadia Putri', $student->nisn]);
        $this->actingAs($teacher)->get(route('students.show', $student))
            ->assertOk()->assertSee('Nadia Putri')->assertDontSee('NADIA PUTRI');

        $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'NADIA PUTRI']);
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

    public function test_profile_opens_history_beneath_summary_header(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $this->createCase($teacher, $student);
        $this->createConsultation($teacher, $student);
        $this->etatibRecord($student, 'ET-PROFIL', 'Pelanggaran profil');

        $this->actingAs($teacher)->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('sibk-student-hero', false)
            ->assertSee('Layanan BK')
            ->assertDontSee('Riwayat kelas dan aktivitas layanan')
            ->assertSee('Terakhir disinkronkan:')
            ->assertDontSee('Pelanggaran profil');

        $this->get(route('students.show', ['student' => $student, 'tab' => 'ringkasan']))
            ->assertOk()->assertSee('Histori kelas');
    }

    public function test_undated_etatib_record_is_visible_without_inventing_a_date(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $record = $this->etatibRecord($student, 'ET-TANPA-TANGGAL', 'Pelanggaran tanpa tanggal');
        $record->update(['occurred_at' => null]);

        $this->actingAs($teacher)
            ->get(route('students.show', ['student' => $student, 'tab' => 'etatib']))
            ->assertOk()
            ->assertSee('Tanggal belum tersedia');
        $this->get(route('cases.create'))
            ->assertOk()
            ->assertSee('Tanggal belum tersedia');
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
            ->assertSee('Riwayat Kelas')
            ->assertSee('Hasil/Ringkasan')
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
            ->assertDontSee('Kasus dicatat')
            ->assertDontSee('Konsultasi dicatat')
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

        $this->actingAs($coordinator)->get(route('students.show', ['student' => $student, 'tab' => 'ringkasan']))
            ->assertOk()
            ->assertSee('Tanpa kelas aktif')
            ->assertSee('X AKL Histori')
            ->assertSee('2026/2027')
            ->assertDontSee('s.d. sekarang');
    }

    public function test_profile_shows_historical_classroom_and_inline_details(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $case = $this->createCase($teacher, $student);
        $consultation = $this->createConsultation($teacher, $student);
        $case->update(['resolution_summary' => 'Hasil penanganan profil']);
        CaseFollowUp::query()->create([
            'case_id' => $case->id,
            'follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id,
            'follow_up_date' => '2026-08-21',
            'created_by' => $teacher->id,
        ]);
        $newClassroom = Classroom::query()->create([
            'academic_year_id' => $case->academic_year_id,
            'name' => 'XI RPL 2',
            'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $newClassroom->id,
            'academic_year_id' => $case->academic_year_id,
            'assigned_by' => $teacher->id,
        ]);
        StudentClassMembership::query()->where('student_id', $student->id)->update(['classroom_id' => $newClassroom->id]);
        $achievement = Achievement::query()->create([
            'student_id' => $student->id,
            'type_id' => $this->reference('achievement_type', 'akademik')->id,
            'level_id' => $this->reference('achievement_level', 'kota_kabupaten')->id,
            'activity_name' => 'Lomba Sains',
            'organizer' => 'Dinas Pendidikan',
            'achievement_date' => '2026-08-01',
            'result' => 'Juara I',
            'evidence_reference' => 'Sertifikat',
            'verification_status_id' => $this->reference('achievement_verification_status', 'terverifikasi')->id,
            'recorded_by' => $teacher->id,
        ]);

        $this->actingAs($teacher)->get(route('students.show', ['student' => $student, 'tab' => 'kasus']))
            ->assertOk()
            ->assertSeeInOrder(['Jenis Masalah', 'Riwayat Kelas', 'Guru BK', 'Hasil/Ringkasan', 'Aksi'])
            ->assertSeeInOrder(['XI RPL 2', 'X RPL 1', 'Hasil penanganan profil', 'Tindak lanjut terakhir:'])
            ->assertSee('Home Visit')
            ->assertSee('aria-controls="profile-case-notes-'.$case->id.'"', false)
            ->assertSee('Latar Belakang Masalah')
            ->assertSee('Informasi awal profil.')
            ->assertDontSee('data-modal-url="'.route('cases.show', [$case, 'modal' => 1]).'"', false);

        $this->actingAs($teacher)->get(route('students.show', ['student' => $student, 'tab' => 'konsultasi']))
            ->assertOk()
            ->assertSeeInOrder(['Jenis Masalah', 'Riwayat Kelas', 'Guru BK', 'Hasil/Ringkasan', 'Aksi'])
            ->assertSeeInOrder(['XI RPL 2', 'X RPL 1', 'Hasil profil murid'])
            ->assertSee('aria-controls="profile-consultation-notes-'.$consultation->id.'"', false)
            ->assertSee('Penanganan profil murid')
            ->assertDontSee('data-modal-url="'.route('consultations.show', [$consultation, 'modal' => 1]).'"', false);

        $this->actingAs($teacher)->get(route('students.show', ['student' => $student, 'tab' => 'prestasi']))
            ->assertOk()
            ->assertSee($achievement->activity_name)
            ->assertDontSee('<th scope="col">Pencatat</th>', false)
            ->assertDontSee('data-modal-url="'.route('achievements.show', [$achievement, 'modal' => 1]).'"', false);
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

    public function test_profile_keeps_archived_history_and_excludes_explicit_cancellations_from_points(): void
    {
        [$teacher, $student] = $this->teacherAndScopedStudent();
        $this->etatibRecord($student, 'OLD', 'Riwayat semester lalu')->update([
            'is_active' => false, 'occurred_at' => '2025-08-10', 'source_total_points' => 90,
            'synced_at' => now()->subYear(),
        ]);
        $this->etatibRecord($student, 'NEW', 'Riwayat semester baru')->update(['source_total_points' => 10]);
        $this->etatibRecord($student, 'CANCEL', 'Catatan dibatalkan')->update([
            'is_active' => false, 'source_deleted_at' => now(), 'points' => 50,
        ]);

        $this->actingAs($teacher)->get(route('students.show', [$student, 'tab' => 'etatib']))
            ->assertOk()
            ->assertSee('Riwayat semester lalu')
            ->assertSee('Riwayat semester baru')
            ->assertSee('Dibatalkan sumber (tidak dihitung)')
            ->assertViewHas('etatibRecords', fn ($records) => $records->count() === 3)
            ->assertViewHas('stats', fn ($stats) => $stats['points'] === 20 && $stats['source_points'] === 10);
        $this->assertSame(20, $student->tatibPoints());
        $this->assertSame(20, $student->load('etatibRecords')->tatibPoints());
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
