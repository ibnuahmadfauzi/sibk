<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AchievementManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-09-27 10:00:00');
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_waka_creates_and_updates_achievement_while_bk_only_reads_within_scope(): void
    {
        [$teacher, $student] = $this->assignedStudent();
        $waka = $this->userWithRole('waka_kesiswaan');
        $coordinator = $this->userWithRole('koordinator_bk');
        $admin = $this->userWithRole('admin_it');

        $this->actingAs($waka)->get(route('achievements.index'))->assertOk()->assertSee('Impor Prestasi');
        $this->get(route('achievements.create'))->assertOk()->assertSee($student->name);
        $this->post(route('achievements.store'), $this->payload($student))->assertRedirect();
        $achievement = Achievement::query()->firstOrFail();
        $this->assertSame($waka->id, $achievement->recorded_by);
        $this->assertSame('Juara II', $achievement->result);
        $rejected = $achievement->replicate();
        $rejected->activity_name = 'Prestasi Ditolak Lama';
        $rejected->verification_status_id = ReferenceValue::query()->forCategory('achievement_verification_status')->where('code', 'ditolak')->valueOrFail('id');
        $rejected->save();
        $this->get(route('achievements.index'))->assertDontSee('Prestasi Ditolak Lama');
        $this->get(route('achievements.show', $rejected))->assertForbidden();
        $this->get(route('achievements.edit', $rejected))->assertForbidden();
        $this->get(route('achievements.show', $achievement))->assertOk()->assertSee('Edit Prestasi')->assertDontSee('Verifikasi Prestasi');
        $originalVersion = $achievement->updated_at->toJSON();
        $this->travel(1)->seconds();
        $this->patch(route('achievements.update', $achievement), [
            ...$this->payload($student), 'result' => 'Juara I', 'expected_updated_at' => $originalVersion,
        ])->assertRedirect();
        $this->assertSame('Juara I', $achievement->refresh()->result);
        $this->patch(route('achievements.update', $achievement), [
            ...$this->payload($student), 'result' => 'Juara III', 'expected_updated_at' => $originalVersion,
        ])->assertSessionHasErrors('expected_updated_at');
        $this->assertSame('Juara I', $achievement->refresh()->result);

        $this->actingAs($teacher)->get(route('achievements.index'))->assertOk()->assertDontSee('Catat Prestasi');
        $this->get(route('achievements.index'))->assertDontSee('Prestasi Ditolak Lama');
        $this->get(route('achievements.show', $achievement))->assertOk()->assertDontSee('Edit Prestasi');
        $this->get(route('students.show', ['student' => $student, 'tab' => 'prestasi']))->assertOk()->assertSee('Juara I')->assertDontSee('Catat Prestasi');
        $this->get(route('achievements.create'))->assertForbidden();
        $this->post(route('achievements.store'), $this->payload($student))->assertForbidden();
        $this->get(route('achievements.edit', $achievement))->assertForbidden();
        $this->patch(route('achievements.update', $achievement), $this->payload($student))->assertForbidden();

        foreach ([$coordinator, $admin] as $user) {
            $this->actingAs($user)->get(route('achievements.index'))->assertForbidden();
            $this->post(route('achievements.store'), $this->payload($student))->assertForbidden();
        }
    }

    public function test_excel_import_is_atomic_and_bk_cannot_import(): void
    {
        [$teacher, $student] = $this->assignedStudent();
        $waka = $this->userWithRole('waka_kesiswaan');
        $rows = [
            ['nisn', 'jenis', 'tingkat', 'kegiatan', 'penyelenggara', 'tanggal', 'hasil'],
            [$student->nisn, 'akademik', 'nasional', 'Lomba Sains', 'Sekolah', '2026-08-10', 'Juara I'],
        ];

        $this->actingAs($teacher)->post(route('achievements.import'), ['file' => $this->excel($rows)])->assertForbidden();
        $this->actingAs($waka)->post(route('achievements.import'), ['file' => $this->excel([
            ...$rows, ['9999999999', 'akademik', 'nasional', 'Lomba Lain', 'Sekolah', '2026-08-10', 'Juara II'],
        ])])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('achievements', 0);

        $this->post(route('achievements.import'), ['file' => $this->excel([
            $rows[0], [$student->nisn, 'akademik', 'nasional', 'Lomba Sains', 'Sekolah', '08/10/2026', 'Juara I'],
        ])])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('achievements', 0);

        $this->post(route('achievements.import'), ['file' => $this->excel([
            $rows[0],
            [$student->nisn, 'akademik', 'nasional', '=1+1', 'Sekolah', '2026-08-10', 'Juara I'],
        ])])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('achievements', 0);

        $this->post(route('achievements.import'), ['file' => $this->excel($rows)])->assertRedirect(route('achievements.index'));
        $this->assertDatabaseCount('achievements', 1);
        $this->assertSame('Lomba Sains', Achievement::query()->firstOrFail()->activity_name);

        $this->post(route('achievements.import'), ['file' => $this->excel($rows, true)])->assertRedirect(route('achievements.index'));
        $this->assertDatabaseCount('achievements', 2);
    }

    public function test_waka_cannot_record_future_or_inactive_student_achievement(): void
    {
        [, $student] = $this->assignedStudent();
        $waka = $this->userWithRole('waka_kesiswaan');
        $this->actingAs($waka)->post(route('achievements.store'), [
            ...$this->payload($student), 'achievement_date' => '2026-09-28',
        ])->assertSessionHasErrors('achievement_date');
        $student->update(['is_active' => false]);
        $this->post(route('achievements.store'), $this->payload($student))->assertSessionHasErrors('student_id');
        $this->assertDatabaseCount('achievements', 0);
    }

    public function test_excel_import_requirements_are_revealed_by_an_accessible_info_button(): void
    {
        $waka = $this->userWithRole('waka_kesiswaan');

        $this->actingAs($waka)->get(route('achievements.index'))
            ->assertOk()
            ->assertSee('<details', false)
            ->assertSee('<summary', false)
            ->assertSee('title="Lihat syarat file Excel"', false)
            ->assertDontSee('data-bs-toggle="collapse"', false)
            ->assertSee('Maksimal 1.000 baris dan 2 MB');
    }

    public function test_achievement_list_uses_search_and_class_filters_with_modal_detail(): void
    {
        [, $student] = $this->assignedStudent();
        $waka = $this->userWithRole('waka_kesiswaan');
        $this->actingAs($waka)->post(route('achievements.store'), $this->payload($student))->assertRedirect();
        $achievement = Achievement::query()->firstOrFail();

        $this->get(route('achievements.index', ['type_id' => 'retired']))
            ->assertOk()
            ->assertSee('name="search"', false)
            ->assertSee('name="classroom_id"', false)
            ->assertSee('>Tampilkan<', false)
            ->assertDontSee('>Reset<', false)
            ->assertDontSee('name="student_id"', false)
            ->assertDontSee('name="type_id"', false)
            ->assertDontSee('name="level_id"', false)
            ->assertDontSee('name="date_start"', false)
            ->assertDontSee('name="date_end"', false)
            ->assertSee('data-modal-url="'.route('achievements.show', [$achievement, 'modal' => 1]).'"', false)
            ->assertSee('data-modal-url="'.route('achievements.edit', [$achievement, 'modal' => 1]).'"', false)
            ->assertSee('title="Lihat selengkapnya"', false)
            ->assertSee('title="Edit prestasi"', false)
            ->assertSee('class="btn btn-sm btn-link d-inline-flex p-2" title="Edit prestasi"', false)
            ->assertDontSee('>Detail<', false)
            ->assertDontSee('>Edit<', false)
            ->assertSee('data-service-record-modal', false);

        $this->get(route('achievements.index', ['search' => 'Murid Prestasi']))
            ->assertOk()
            ->assertSee('>Reset<', false)
            ->assertDontSee('>Tampilkan<', false);

        $this->get(route('achievements.show', [$achievement, 'modal' => 1]))
            ->assertOk()
            ->assertSee('Detail Prestasi')
            ->assertSee($achievement->activity_name)
            ->assertSee('data-modal-url="'.route('achievements.edit', [$achievement, 'modal' => 1]).'"', false)
            ->assertDontSee('<html', false);

        $this->get(route('achievements.edit', [$achievement, 'modal' => 1]))
            ->assertOk()
            ->assertSee('Edit Prestasi')
            ->assertSee('name="expected_updated_at"', false)
            ->assertSee('name="activity_name"', false)
            ->assertDontSee('data-autosave-form', false)
            ->assertDontSee('<html', false);

        $this->travel(1)->seconds();
        $this->patchJson(route('achievements.update', $achievement), [
            ...$this->payload($student),
            'result' => 'Juara I',
            'expected_updated_at' => $achievement->updated_at->toJSON(),
        ])->assertOk()->assertJsonPath('redirect', route('achievements.index'));
        $this->assertSame('Juara I', $achievement->refresh()->result);
    }

    /** @return array{User, Student} */
    private function assignedStudent(): array
    {
        $teacher = $this->userWithRole('guru_bk');
        $year = AcademicYear::query()->create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $classroom = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 1', 'is_active' => true]);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Prestasi', 'is_active' => true]);
        StudentClassMembership::query()->create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        TeacherAssignment::query()->create(['user_id' => $teacher->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'assigned_by' => $teacher->id]);

        return [$teacher, $student];
    }

    /** @return array<string, mixed> */
    private function payload(Student $student): array
    {
        return [
            'student_id' => $student->id,
            'type_id' => ReferenceValue::query()->forCategory('achievement_type')->where('code', 'akademik')->firstOrFail()->id,
            'level_id' => ReferenceValue::query()->forCategory('achievement_level')->where('code', 'nasional')->firstOrFail()->id,
            'activity_name' => 'Olimpiade Kompetensi Murid',
            'organizer' => 'Pusat Prestasi Nasional',
            'achievement_date' => '2026-08-10',
            'result' => 'Juara II',
        ];
    }

    /** @param list<list<string>> $rows */
    private function excel(array $rows, bool $excelDate = false): UploadedFile
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray($rows);
        if ($excelDate) {
            $book->getActiveSheet()->setCellValue('F2', Date::PHPToExcel(new \DateTimeImmutable('2026-08-10')));
            $book->getActiveSheet()->getStyle('F2')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
        }
        $path = tempnam(sys_get_temp_dir(), 'prestasi-').'.xlsx';
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return new UploadedFile($path, 'prestasi.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
