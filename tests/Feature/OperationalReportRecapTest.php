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

    public function test_coordinator_reads_out_of_scope_records_only_through_reports_and_withdrawals(): void
    {
        [$year, $classroom, $student, $owner, $case] = $this->caseFixture();
        $consultation = Consultation::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'service_field_id' => $case->service_field_id,
            'session_date' => '2026-09-02',
            'problem' => 'Latar konsultasi di luar penugasan.',
            'handling' => 'Penanganan konsultasi.',
            'result' => 'Hasil konsultasi luar penugasan.',
            'counselor_id' => $owner->id,
        ]);
        WithdrawalProgress::query()->create([
            'student_id' => $student->id, 'teacher_id' => $owner->id,
            'classroom_id' => $classroom->id, 'recorded_on' => '2026-09-03',
            'progress' => WithdrawalProgress::PROGRESS_AT_BK,
            'reason' => 'Pindah sekolah.', 'note' => 'Catatan pengunduran lintas kelas.',
        ]);
        $coordinator = $this->userWithRole('koordinator_bk');
        $coordinator->roles()->attach(Role::query()->where('slug', 'guru_bk')->firstOrFail());
        $this->actingAs($coordinator);

        foreach (['kasus', 'konsultasi'] as $tab) {
            $this->get(route('cases.index', ['tab' => $tab]))->assertOk()->assertDontSee($student->name);
        }
        $this->get(route('cases.index', ['tab' => 'pengunduran-diri']))->assertOk()->assertSee($student->name);
        $this->get(route('cases.show', $case))->assertForbidden();
        $this->get(route('consultations.show', [$consultation, 'inline' => 1]))->assertForbidden();
        $this->get(route('students.show', ['student' => $student, 'tab' => 'konsultasi']))
            ->assertOk()->assertDontSee($consultation->problem)->assertDontSee($consultation->result);
        $this->get(route('students.show', ['student' => $student, 'tab' => 'kasus']))
            ->assertOk()->assertDontSee($case->initial_info);

        foreach (['case' => $case, 'consultation' => $consultation] as $type => $record) {
            $filters = ['academic_year_id' => $year->id, 'service_type' => $type];
            $this->get(route('reports.index', $filters))->assertOk()->assertSee($student->name);
            $this->get(route('reports.preview', $filters))->assertOk()->assertSee($student->name);
            $this->get(route('reports.records.preview', ['type' => $type, 'id' => $record->id]))
                ->assertOk()->assertSee($student->name);
        }
        $coordinator->roles()->detach(Role::query()->where('slug', 'guru_bk')->firstOrFail());
        $coordinator->unsetRelation('roles');
        $this->get(route('reports.index', ['academic_year_id' => $year->id]))
            ->assertOk()->assertSee($student->name);
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

    public function test_recap_modal_preserves_filters_and_embedded_document_access(): void
    {
        [$year, $classroom, , $owner] = $this->caseFixture();
        $filters = [
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'service_type' => 'case',
        ];

        $index = $this->actingAs($owner)->get(route('reports.index', $filters));
        $index->assertOk()
            ->assertSee('data-bs-target="#report-preview-modal"', false)
            ->assertSee('data-preview-url="'.e(route('reports.preview', [...$filters, 'embedded' => 1])).'"', false)
            ->assertSee('href="'.e(route('reports.export', [...$filters, 'format' => 'xlsx'])).'"', false);

        $this->actingAs($owner)->get(route('reports.preview', [...$filters, 'embedded' => 1]))
            ->assertOk()
            ->assertSee('sibk-report-embedded')
            ->assertSee('sibk-document-sheet')
            ->assertDontSee('sibk-sidebar')
            ->assertDontSee('data-print-report');

        $this->actingAs($owner)->get(route('reports.preview', $filters))
            ->assertOk()->assertSee('Kembali ke Laporan');

        $this->actingAs($this->userWithRole('waka_kesiswaan'))
            ->get(route('reports.preview', [...$filters, 'embedded' => 1]))->assertForbidden();
        $this->actingAs($this->userWithRole('admin_it'))
            ->get(route('reports.preview', [...$filters, 'embedded' => 1]))->assertForbidden();
    }

    public function test_bk_report_detail_spans_all_columns_without_archive_action(): void
    {
        [$year, , , $owner, $case] = $this->caseFixture();

        $response = $this->actingAs($owner)->get(route('reports.index', [
            'academic_year_id' => $year->id,
            'service_type' => 'case',
        ]));

        $response->assertOk()
            ->assertSee('data-report-detail-toggle', false)
            ->assertSee('colspan="6"', false)
            ->assertDontSee('sibk-report-detail-spacer', false)
            ->assertDontSee('action="'.route('cases.destroy', $case).'"', false)
            ->assertDontSee('Arsipkan '.$case->student->name);

        $this->assertMatchesRegularExpression(
            '/id="report-detail-case-'.$case->id.'"\s*>\s*<td colspan="6">/',
            $response->getContent(),
        );
    }

    public function test_waka_withdrawal_report_shows_latest_progress_and_bottom_pagination(): void
    {
        [$year, $classroom, $student, $owner] = $this->caseFixture();
        $waka = $this->userWithRole('waka_kesiswaan');
        for ($i = 0; $i < 11; $i++) {
            $student = Student::query()->create([
                'nisn' => sprintf('009999%04d', $i),
                'name' => 'Murid Pengunduran '.$i,
                'is_active' => true,
            ]);
            $withdrawal = WithdrawalProgress::query()->create([
                'student_id' => $student->id,
                'teacher_id' => $owner->id,
                'classroom_id' => $classroom->id,
                'recorded_on' => '2026-09-03',
                'progress' => WithdrawalProgress::PROGRESS_IN_PROGRESS,
                'reason' => 'Alasan rahasia.',
                'note' => 'Catatan rahasia.',
            ]);
            foreach (['2026-09-05' => WithdrawalProgress::PROGRESS_AT_TU, '2026-09-04' => WithdrawalProgress::PROGRESS_AT_BK] as $date => $progress) {
                $withdrawal->followUps()->create([
                    'progress' => $progress,
                    'follow_up_date' => $date,
                    'notes' => 'Tindak lanjut rahasia.',
                    'created_by' => $owner->id,
                ]);
            }
        }

        $filters = ['academic_year_id' => $year->id, 'service_type' => 'withdrawal'];
        $response = $this->actingAs($waka)->get(route('reports.index', $filters));
        $response->assertOk()
            ->assertViewHas('report', fn (array $report): bool => $report['columns'] === ['No', 'Hari / Tanggal', 'Nama / Kelas', 'Guru', 'Keterangan']
                && $report['rows']->total() === 11 && $report['rows']->count() === 10)
            ->assertSee('Berkas sudah masuk TU')
            ->assertDontSee('Berkas masih di BK')
            ->assertDontSee('Catatan Layanan')
            ->assertDontSee('data-report-page-size', false)
            ->assertDontSee('Alasan rahasia.')
            ->assertDontSee('Catatan rahasia.')
            ->assertDontSee('Tindak lanjut rahasia.')
            ->assertSee('page=2', false);
        $this->actingAs($waka)->get(route('reports.index', $filters + ['page' => 2]))
            ->assertOk()->assertViewHas('report', fn (array $report): bool => $report['rows']->first()['number'] === 11);
        $this->actingAs($waka)->get(route('reports.records.preview', ['type' => 'withdrawal', 'id' => $withdrawal->id]))
            ->assertForbidden();
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
        $this->assertSame('Berkas masih diproses', $row['detail_note']);

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

    public function test_bk_withdrawal_report_shows_teacher_latest_progress_and_note_detail(): void
    {
        [$year, $classroom, $student, $owner] = $this->caseFixture();
        $withdrawal = WithdrawalProgress::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $owner->id,
            'classroom_id' => $classroom->id,
            'recorded_on' => '2026-09-03',
            'progress' => WithdrawalProgress::PROGRESS_IN_PROGRESS,
            'reason' => 'Pindah sekolah.',
            'note' => 'Catatan awal pengunduran.',
        ]);
        $withdrawal->followUps()->create([
            'progress' => WithdrawalProgress::PROGRESS_AT_BK,
            'follow_up_date' => '2026-09-05',
            'notes' => 'Catatan progres pertama.',
            'created_by' => $owner->id,
        ]);
        $withdrawal->followUps()->create([
            'progress' => WithdrawalProgress::PROGRESS_AT_TU,
            'follow_up_date' => '2026-09-05',
            'notes' => 'Catatan progres terbaru.',
            'created_by' => $owner->id,
        ]);
        $withdrawal->followUps()->create([
            'progress' => WithdrawalProgress::PROGRESS_IN_PROGRESS,
            'follow_up_date' => '2026-09-04',
            'notes' => 'Catatan lama yang dicatat belakangan.',
            'created_by' => $owner->id,
        ]);

        foreach ([$owner, $this->userWithRole('koordinator_bk')] as $actor) {
            $response = $this->actingAs($actor)->get(route('reports.index', [
                'academic_year_id' => $year->id,
                'service_type' => 'withdrawal',
            ]));

            $response->assertOk()
                ->assertViewHas('report', fn (array $report): bool => $report['columns'] === [
                    'No', 'Hari / Tanggal', 'Nama / Kelas', 'Guru', 'Keterangan', 'Aksi',
                ] && $report['rows']->first()['follow_up_label'] === 'Berkas sudah masuk TU')
                ->assertSee($owner->name)
                ->assertSee('Catatan awal pengunduran.')
                ->assertSee('colspan="6"', false)
                ->assertDontSee('Latar Belakang Masalah')
                ->assertDontSee('Penanganan</strong>', false)
                ->assertDontSee('Catatan progres terbaru.');
            $this->assertMatchesRegularExpression(
                '/id="report-detail-withdrawal-'.$withdrawal->id.'"\s*>\s*<td colspan="6">/',
                $response->getContent(),
            );
        }
    }

    public function test_withdrawal_report_uses_record_progress_when_there_is_no_follow_up(): void
    {
        [$year, $classroom, $student, $owner] = $this->caseFixture();
        WithdrawalProgress::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $owner->id,
            'classroom_id' => $classroom->id,
            'recorded_on' => '2026-09-03',
            'progress' => WithdrawalProgress::PROGRESS_AT_BK,
            'reason' => 'Pindah sekolah.',
            'note' => '',
        ]);

        $this->actingAs($owner)->get(route('reports.index', [
            'academic_year_id' => $year->id,
            'service_type' => 'withdrawal',
        ]))->assertOk()
            ->assertSee('Berkas masih di BK')
            ->assertSee('Belum ada catatan.');
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
