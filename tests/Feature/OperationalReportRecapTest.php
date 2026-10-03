<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\WithdrawalProgress;
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

    public function test_jenis_layanan_dropdown_options_match_specification(): void
    {
        [, , , $owner] = $this->caseFixture();

        $response = $this->actingAs($owner)->get(route('reports.index'));

        $response->assertOk();
        $response->assertDontSee('Semua layanan');
        $response->assertDontSee('value="all"', false);
        $response->assertSeeInOrder([
            'Catatan Permasalahan',
            'Catatan Konsultasi',
            'Catatan Pengunduran Diri',
        ]);
        $response->assertSee('value="case"', false);
        $response->assertSee('value="consultation"', false);
        $response->assertSee('value="withdrawal"', false);
    }

    public function test_filtering_by_service_type_correctly_filters_each_service_type(): void
    {
        [$year, $classroom, $student, $owner, $case] = $this->caseFixture();

        $consultation = Consultation::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'service_field_id' => ReferenceValue::query()->forCategory('service_field')->firstOrFail()->id,
            'counselor_id' => $owner->id,
            'session_date' => '2026-09-02',
            'problem' => 'Masalah konsultasi.',
            'handling' => 'Penanganan konsultasi.',
            'result' => 'Hasil konsultasi.',
        ]);

        $withdrawal = WithdrawalProgress::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $owner->id,
            'classroom_id' => $classroom->id,
            'recorded_on' => '2026-09-03',
            'progress' => WithdrawalProgress::PROGRESS_IN_PROGRESS,
            'reason' => 'Pindah domisili keluarga.',
            'note' => 'Orang tua sudah konfirmasi.',
        ]);
        $withdrawal->followUps()->create([
            'progress' => WithdrawalProgress::PROGRESS_IN_PROGRESS,
            'follow_up_date' => '2026-09-03',
            'notes' => 'Tindak lanjut awal.',
            'created_by' => $owner->id,
        ]);

        // Filter withdrawal
        $withdrawalReport = app(OperationalReportRecapService::class)->paginateForUi($owner, [
            'academic_year_id' => $year->id,
            'service_type' => 'withdrawal',
        ]);
        $this->assertSame(1, $withdrawalReport['rows']->total());
        $row = $withdrawalReport['rows']->first();
        $this->assertSame('withdrawal', $row['type']);
        $this->assertSame('Pengunduran Diri', $row['service']);
        $this->assertSame('Orang tua sudah konfirmasi.', $row['problem']);
        $this->assertSame('—', $row['handling']);
        $this->assertSame('Berkas pengunduran masih progres', $row['detail_note']);

        // Web request for withdrawal
        $response = $this->actingAs($owner)->get(route('reports.index', [
            'academic_year_id' => $year->id,
            'service_type' => 'withdrawal',
        ]));
        $response->assertOk();
        $response->assertSee('Orang tua sudah konfirmasi.');
        $response->assertDontSee('Informasi awal.');
        $response->assertDontSee('Masalah konsultasi.');

        // Filter case
        $caseReport = app(OperationalReportRecapService::class)->paginateForUi($owner, [
            'academic_year_id' => $year->id,
            'service_type' => 'case',
        ]);
        $this->assertSame(1, $caseReport['rows']->total());
        $this->assertSame('case', $caseReport['rows']->first()['type']);

        $caseResponse = $this->actingAs($owner)->get(route('reports.index', [
            'academic_year_id' => $year->id,
            'service_type' => 'case',
        ]));
        $caseResponse->assertOk();
        $caseResponse->assertSee('Informasi awal.');
        $caseResponse->assertDontSee('Pindah domisili keluarga.');
        $caseResponse->assertDontSee('Masalah konsultasi.');

        // Filter consultation
        $consultationReport = app(OperationalReportRecapService::class)->paginateForUi($owner, [
            'academic_year_id' => $year->id,
            'service_type' => 'consultation',
        ]);
        $this->assertSame(1, $consultationReport['rows']->total());
        $this->assertSame('consultation', $consultationReport['rows']->first()['type']);

        $consultationResponse = $this->actingAs($owner)->get(route('reports.index', [
            'academic_year_id' => $year->id,
            'service_type' => 'consultation',
        ]));
        $consultationResponse->assertOk();
        $consultationResponse->assertSee('Masalah konsultasi.');
        $consultationResponse->assertDontSee('Informasi awal.');
        $consultationResponse->assertDontSee('Pindah domisili keluarga.');
    }

    public function test_record_preview_supports_withdrawal_type(): void
    {
        [$year, $classroom, $student, $owner] = $this->caseFixture();

        $withdrawal = WithdrawalProgress::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $owner->id,
            'classroom_id' => $classroom->id,
            'recorded_on' => '2026-09-03',
            'progress' => WithdrawalProgress::PROGRESS_IN_PROGRESS,
            'reason' => 'Pindah luar kota.',
            'note' => 'Menunggu surat pindah.',
        ]);

        $this->actingAs($owner)
            ->get(route('reports.records.preview', ['type' => 'withdrawal', 'id' => $withdrawal->id]))
            ->assertOk()
            ->assertSee('Pengunduran Diri')
            ->assertSee('Menunggu surat pindah.')
            ->assertDontSee('Pindah luar kota.');
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
