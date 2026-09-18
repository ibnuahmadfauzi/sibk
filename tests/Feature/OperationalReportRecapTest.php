<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ExternalTatibRecord;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\TemporaryStudent;
use App\Models\User;
use App\Services\OperationalReportRecapService;
use App\Services\ReportService;
use App\Support\ServiceRecordStatus;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalReportRecapTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-08-20 10:00:00');
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
        $this->year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
    }

    public function test_operational_report_tabs_follow_role_authorization(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Laporan');
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Laporan');
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Laporan');
        $admin = $this->userWithRole('admin_it', 'Admin Laporan');

        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->actingAs($teacher)->get(route('reports.index'))->assertOk();
        $this->actingAs($coordinator)->get(route('reports.index', ['tab' => 'prestasi']))->assertOk();
        $this->actingAs($waka)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.index'))->assertForbidden();
    }

    public function test_request_rejects_invalid_dates_and_class_outside_year_or_scope(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Filter');
        [, $allowedClass] = $this->scopedStudent($teacher, 'Murid Filter', '0012345678', 'X RPL 1');
        $otherYear = AcademicYear::query()->create([
            'name' => '2025/2026',
            'starts_on' => '2025-07-01',
            'ends_on' => '2026-06-30',
            'is_active' => false,
        ]);
        $crossYearClass = Classroom::query()->create([
            'academic_year_id' => $otherYear->id,
            'name' => 'X Lama',
            'is_active' => true,
        ]);

        $this->actingAs($teacher)->get(route('reports.index', [
            'tab' => 'pelanggaran',
            'academic_year_id' => $this->year->id,
            'date_start' => '2026-08-20',
            'date_end' => '2026-08-01',
            'classroom_id' => $crossYearClass->id,
        ]))->assertSessionHasErrors(['date_end', 'classroom_id']);

        $this->actingAs($teacher)->get(route('reports.index', [
            'tab' => 'pelanggaran',
            'academic_year_id' => $this->year->id,
            'classroom_id' => $allowedClass->id,
        ]))->assertOk();
    }

    public function test_counselor_filter_is_validated_safely(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Biasa');
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator');
        $activeCounselor = $this->userWithRole('guru_bk', 'Guru Aktif');
        $inactiveCounselor = $this->userWithRole('guru_bk', 'Guru Nonaktif');
        $inactiveCounselor->update(['is_active' => false]);

        $this->actingAs($teacher)->get(route('reports.index', [
            'tab' => 'layanan',
            'counselor_id' => $activeCounselor->id,
        ]))->assertSessionHasErrors('counselor_id');
        $this->actingAs($coordinator)->get(route('reports.index', [
            'tab' => 'prestasi',
            'counselor_id' => $activeCounselor->id,
        ]))->assertSessionHasErrors('counselor_id');
        $this->actingAs($coordinator)->get(route('reports.index', [
            'tab' => 'layanan',
            'counselor_id' => $inactiveCounselor->id,
        ]))->assertSessionHasErrors('counselor_id');
        $this->actingAs($coordinator)->get(route('reports.index', [
            'tab' => 'layanan',
            'counselor_id' => $activeCounselor->id,
        ]))->assertOk();
    }

    public function test_request_rejects_invalid_and_mixed_report_modes(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Mode');

        $this->actingAs($teacher)->get(route('reports.index', [
            'tab' => 'tidak-tersedia',
        ]))->assertSessionHasErrors('tab');
        $this->actingAs($teacher)->get(route('reports.index', [
            'tab' => 'layanan',
            'type' => 'rekap-layanan-bk',
        ]))->assertSessionHasErrors('type');
        $this->actingAs($teacher)->get(route('reports.preview'))
            ->assertSessionHasErrors('type');
        $this->actingAs($teacher)->get(route('reports.preview', [
            'type' => 'rekap-layanan-bk',
            'tab' => 'layanan',
        ]))->assertSessionHasErrors('tab');
        $this->actingAs($teacher)->get(route('reports.export', [
            'tab' => 'layanan',
            'type' => 'rekap-layanan-bk',
            'format' => 'csv',
        ]))->assertSessionHasErrors(['tab', 'type']);
    }

    public function test_violation_tab_groups_searches_filters_and_keeps_scope(): void
    {
        $teacherA = $this->userWithRole('guru_bk', 'Guru A');
        $teacherB = $this->userWithRole('guru_bk', 'Guru B');
        [$studentA] = $this->scopedStudent($teacherA, 'Murid Alpha Rahasia', '0012345678', 'X RPL 1');
        [$studentB] = $this->scopedStudent($teacherB, 'Murid Beta Rahasia', '0098765432', 'X RPL 2');
        $historicalClass = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'X RPL Historis',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->where('student_id', $studentA->id)->update(['effective_from' => '2026-08-03']);
        StudentClassMembership::query()->create([
            'student_id' => $studentA->id,
            'classroom_id' => $historicalClass->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-01',
            'effective_until' => '2026-08-02',
            'is_active' => true,
        ]);
        $this->etatib($studentA, 'ET-A-1', 'Terlambat', 5, '2026-08-01');
        $this->etatib($studentA, 'ET-A-2', 'Atribut', 7, '2026-08-02');
        $this->etatib($studentB, 'ET-B-1', 'Guru lain', 9, '2026-08-03');

        $report = app(OperationalReportRecapService::class)->build($teacherA, [
            'tab' => 'pelanggaran',
            'academic_year_id' => $this->year->id,
            'q' => 'Alpha',
            'classroom_id' => $historicalClass->id,
        ]);
        $rows = collect($report['rows']->items());

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows->first()['violation_count']);
        $this->assertSame(12, $rows->first()['total_points']);
        $this->assertSame('Atribut', $rows->first()['latest_violation']);
        $this->assertSame('X RPL Historis', $rows->first()['classroom']);
        $this->assertSame('M.A.R.', $rows->first()['initials']);
        $this->assertStringNotContainsString($studentA->name, json_encode($rows->all(), JSON_THROW_ON_ERROR));

        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator');
        $combined = app(OperationalReportRecapService::class)->build($coordinator, ['tab' => 'pelanggaran']);
        $this->assertSame(2, $combined['rows']->total());
    }

    public function test_coordinator_keeps_unlinked_etatib_identity_without_exposing_source_data(): void
    {
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator');
        $teacher = $this->userWithRole('guru_bk', 'Guru');
        foreach (['ET-UNLINKED-1', 'ET-UNLINKED-2'] as $index => $identifier) {
            ExternalTatibRecord::query()->create([
                'source_identifier' => $identifier,
                'nisn' => '0088888888',
                'occurred_at' => '2026-08-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).' 07:00:00',
                'violation_type' => '=SUM(1+1)',
                'category' => 'Kedisiplinan',
                'points' => 4,
                'is_active' => true,
                'synced_at' => now(),
            ]);
        }

        $row = collect(app(OperationalReportRecapService::class)
            ->build($coordinator, ['tab' => 'pelanggaran'])['rows']->items())->first();
        $encoded = json_encode($row, JSON_THROW_ON_ERROR);

        $this->assertSame('Belum tertaut', $row['initials']);
        $this->assertSame('00******88', $row['masked_nisn']);
        $this->assertSame(2, $row['violation_count']);
        $this->assertStringNotContainsString('ET-UNLINKED', $encoded);
        $this->assertSame(0, app(OperationalReportRecapService::class)
            ->build($teacher, ['tab' => 'pelanggaran'])['rows']->total());
    }

    public function test_violation_query_count_does_not_grow_with_page_rows(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Query');
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'X Query',
            'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-01',
            'decision_number' => 'SK-QUERY',
            'assigned_by' => $teacher->id,
        ]);
        $this->studentWithViolation($classroom, 1);

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(OperationalReportRecapService::class)->build($teacher, ['tab' => 'pelanggaran']);
        $singleCount = count(DB::getQueryLog());

        foreach (range(2, 25) as $index) {
            $this->studentWithViolation($classroom, $index);
        }
        DB::flushQueryLog();
        $report = app(OperationalReportRecapService::class)->build($teacher, ['tab' => 'pelanggaran']);
        $manyCount = count(DB::getQueryLog());

        $this->assertCount(20, $report['rows']->items());
        $this->assertSame(25, $report['rows']->total());
        $this->assertLessThanOrEqual($singleCount + 2, $manyCount);
    }

    public function test_service_tab_groups_official_reconciled_and_temporary_identities_safely(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Layanan');
        [$student, $classroom] = $this->scopedStudent($teacher, 'Murid Layanan Resmi', '0022222222', 'XI RPL 1');
        $reconciled = $this->temporaryStudent('0022222222', 'Nama Masukan Lama', $teacher, $student);
        $unreconciled = $this->temporaryStudent('0033333333', 'Murid Sementara', $teacher);
        $officialCase = $this->caseRecord($teacher, $student, null, '2026-08-10', 'RAHASIA-KASUS');
        $officialCase->update([
            'follow_up_type_id' => $this->reference('follow_up_type', 'surat_pernyataan')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::NEEDS_FOLLOW_UP)->id,
        ]);
        $temporaryCase = $this->caseRecord($teacher, null, $unreconciled, '2026-08-11', 'RAHASIA-SEMENTARA');
        $this->consultationRecord($teacher, null, $reconciled, '2026-08-18', 'RAHASIA-KONSULTASI');
        $this->followUpRecord($officialCase, $teacher, 'terlaksana', '2026-08-15', '2026-08-19');
        $this->followUpRecord($officialCase, $teacher, 'terjadwal', '2026-08-25');

        $report = app(OperationalReportRecapService::class)->build($teacher, ['tab' => 'layanan']);
        $rows = collect($report['rows']->items())->keyBy('identity_key');
        $official = $rows->get('student:'.$student->id);
        $temporary = $rows->get('temporary:'.$unreconciled->id);

        $this->assertCount(2, $rows);
        $this->assertSame(1, $official['case_count']);
        $this->assertSame(1, $official['consultation_count']);
        $this->assertSame(1, $official['follow_up_case_count']);
        $this->assertSame(3, $report['stats']['service_count']);
        $this->assertSame(1, $report['stats']['follow_up_case_count']);
        $this->assertSame('18 Agu 2026', $official['latest_service_date']);
        $this->assertSame($classroom->name, $official['classroom']);
        $this->assertSame('Belum tersedia', $temporary['classroom']);
        $this->assertTrue($temporary['is_temporary']);
        $this->assertStringNotContainsString('RAHASIA-', json_encode($rows->all(), JSON_THROW_ON_ERROR));

        $filtered = app(OperationalReportRecapService::class)->build($teacher, [
            'tab' => 'layanan',
            'q' => 'Resmi',
            'classroom_id' => $classroom->id,
        ]);
        $this->assertSame(1, $filtered['rows']->total());
        $this->assertSame('student:'.$student->id, $filtered['rows']->items()[0]['identity_key']);
        $this->assertNotNull($temporaryCase->id);
    }

    public function test_coordinator_counselor_filter_uses_effective_owner_not_recorder(): void
    {
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator');
        $ownerA = $this->userWithRole('guru_bk', 'Guru A');
        $ownerB = $this->userWithRole('guru_bk', 'Guru B');
        [$studentA] = $this->scopedStudent($ownerA, 'Murid Owner A', '0044444444', 'X AKL 1');
        [$studentB] = $this->scopedStudent($ownerB, 'Murid Owner B', '0055555555', 'X AKL 2');
        $caseA = $this->caseRecord($ownerA, $studentA, null, '2026-08-10', 'Kasus A');
        $caseB = $this->caseRecord($ownerB, $studentB, null, '2026-08-10', 'Kasus B');
        $caseA->update(['created_by' => $ownerB->id]);
        $this->consultationRecord($ownerA, $studentA, null, '2026-08-12', 'Konsultasi A');
        $this->consultationRecord($ownerB, $studentB, null, '2026-08-12', 'Konsultasi B');

        $rows = collect(app(OperationalReportRecapService::class)->build($coordinator, [
            'tab' => 'layanan',
            'counselor_id' => $ownerA->id,
        ])['rows']->items());

        $this->assertCount(1, $rows);
        $this->assertSame('student:'.$studentA->id, $rows->first()['identity_key']);
        $this->assertSame(1, $rows->first()['case_count']);
        $this->assertSame(1, $rows->first()['consultation_count']);
        $this->assertSame(0, $rows->first()['follow_up_case_count']);
    }

    public function test_service_identity_query_is_paginated_before_batch_hydration(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Query Layanan');
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'X Layanan',
            'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-01',
            'decision_number' => 'SK-LAYANAN',
            'assigned_by' => $teacher->id,
        ]);
        $this->studentWithCase($teacher, $classroom, 1);

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(OperationalReportRecapService::class)->build($teacher, ['tab' => 'layanan']);
        $singleCount = count(DB::getQueryLog());
        foreach (range(2, 25) as $index) {
            $this->studentWithCase($teacher, $classroom, $index);
        }
        DB::flushQueryLog();
        $report = app(OperationalReportRecapService::class)->build($teacher, ['tab' => 'layanan']);

        $this->assertSame(25, $report['rows']->total());
        $this->assertCount(20, $report['rows']->items());
        $this->assertLessThanOrEqual($singleCount + 3, count(DB::getQueryLog()));
    }

    public function test_achievement_tab_groups_uses_verified_sort_order_and_hides_private_fields(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Prestasi');
        [$student, $classroom] = $this->scopedStudent($teacher, 'Murid Prestasi Rahasia', '0066666666', 'XI DKV 1');
        $this->achievementRecord($student, $teacher, 'Lomba Nasional', 'nasional', 'terverifikasi', '2026-08-10');
        $this->achievementRecord($student, $teacher, '=SUM(1+1)', 'internasional', 'menunggu', '2026-08-12');

        $report = app(OperationalReportRecapService::class)->build($teacher, [
            'tab' => 'prestasi',
            'q' => 'Prestasi',
            'classroom_id' => $classroom->id,
        ]);
        $row = $report['rows']->items()[0];
        $encoded = json_encode($row, JSON_THROW_ON_ERROR);

        $this->assertSame(2, $row['achievement_count']);
        $this->assertSame(1, $row['verified_count']);
        $this->assertSame('Nasional', $row['highest_verified_level']);
        $this->assertSame('=SUM(1+1)', $row['latest_achievement']);
        $this->assertStringNotContainsString('BUKTI-RAHASIA', $encoded);
        $this->assertStringNotContainsString('CATATAN-RAHASIA', $encoded);
        $this->assertStringNotContainsString($student->name, $encoded);
    }

    public function test_achievement_tab_keeps_scope_historical_class_and_stable_pagination(): void
    {
        $teacherA = $this->userWithRole('guru_bk', 'Guru A');
        $teacherB = $this->userWithRole('guru_bk', 'Guru B');
        [$studentA] = $this->scopedStudent($teacherA, 'Prestasi A', '0077777777', 'XI AKL 1');
        [$studentB] = $this->scopedStudent($teacherB, 'Prestasi B', '0088888888', 'XI AKL 2');
        $historicalClass = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'X AKL Historis',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->where('student_id', $studentA->id)->update(['effective_from' => '2026-08-11']);
        StudentClassMembership::query()->create([
            'student_id' => $studentA->id,
            'classroom_id' => $historicalClass->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-01',
            'effective_until' => '2026-08-10',
            'is_active' => true,
        ]);
        $this->achievementRecord($studentA, $teacherA, 'Prestasi A', 'sekolah', 'terverifikasi', '2026-08-10');
        $this->achievementRecord($studentB, $teacherB, 'Prestasi B', 'sekolah', 'terverifikasi', '2026-08-10');

        $report = app(OperationalReportRecapService::class)->build($teacherA, [
            'tab' => 'prestasi',
            'classroom_id' => $historicalClass->id,
        ]);

        $this->assertSame(1, $report['rows']->total());
        $this->assertSame('student:'.$studentA->id, $report['rows']->items()[0]['identity_key']);
        $this->assertSame($historicalClass->name, $report['rows']->items()[0]['classroom']);
    }

    public function test_reports_index_uses_three_deep_links_without_legacy_cards(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru UI');

        $this->actingAs($teacher)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Layanan BK')
            ->assertSee('aria-current="page"', false)
            ->assertSee(route('reports.index', ['tab' => 'pelanggaran']), false)
            ->assertSee(route('reports.index', ['tab' => 'prestasi']), false)
            ->assertDontSee('Pelanggaran per Murid')
            ->assertDontSee('Pelanggaran per Kelas')
            ->assertDontSee('Poin Pelanggaran')
            ->assertDontSee('sibk-report-grid');

        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator UI');
        $this->actingAs($teacher)->get(route('reports.index', ['tab' => 'layanan']))
            ->assertDontSee('name="counselor_id"', false);
        $this->actingAs($coordinator)->get(route('reports.index', ['tab' => 'layanan']))
            ->assertSee('name="counselor_id"', false);
        $this->actingAs($coordinator)->get(route('reports.index', ['tab' => 'prestasi']))
            ->assertDontSee('name="counselor_id"', false);
    }

    public function test_each_tab_renders_one_table_mobile_cards_and_preserves_valid_filters(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Markup');
        [, $classroom] = $this->scopedStudent($teacher, 'Murid Markup', '0099999999', 'X Markup');

        foreach (['pelanggaran', 'layanan', 'prestasi'] as $tab) {
            $response = $this->actingAs($teacher)->get(route('reports.index', [
                'tab' => $tab,
                'q' => 'Markup',
                'academic_year_id' => $this->year->id,
                'classroom_id' => $classroom->id,
            ]));
            $response->assertOk()
                ->assertSee('name="tab" value="'.$tab.'"', false)
                ->assertSee('name="q"', false)
                ->assertSee('name="classroom_id"', false)
                ->assertSee('sibk-operational-report-table', false)
                ->assertSee('sibk-operational-report-cards', false)
                ->assertSee('data-print-report', false)
                ->assertSee('q=Markup', false)
                ->assertSee('classroom_id='.$classroom->id, false)
                ->assertDontSee('onclick=', false);
        }

        foreach (range(1, 21) as $index) {
            $this->studentWithViolation($classroom, 100 + $index);
        }
        $this->actingAs($teacher)->get(route('reports.index', [
            'tab' => 'pelanggaran',
            'academic_year_id' => $this->year->id,
            'classroom_id' => $classroom->id,
        ]))->assertOk()->assertSee('page=2', false)->assertSee('tab=pelanggaran', false);
    }

    public function test_operational_csv_and_legacy_contract_use_their_own_validated_modes(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru CSV Baru');
        [$student] = $this->scopedStudent($teacher, 'Nama CSV Baru', '0010101010', 'X CSV');
        $this->etatib($student, 'ET-CSV-BARU', '=SUM(1+1)', 9, '2026-08-10');

        $operational = $this->actingAs($teacher)->get(route('reports.export', [
            'tab' => 'pelanggaran',
            'format' => 'csv',
        ]));
        $operational->assertOk()->assertDownload()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $operational->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Jumlah pelanggaran', $csv);
        $this->assertStringContainsString('N.C.B.', $csv);
        $this->assertStringContainsString("'=SUM(1+1)", $csv);
        $this->assertStringNotContainsString($student->name, $csv);
        $this->assertStringNotContainsString($student->nisn, $csv);

        $this->actingAs($teacher)->get(route('reports.preview', [
            'type' => ReportService::TYPE_STUDENT_VIOLATIONS,
        ]))->assertOk();
        $this->actingAs($teacher)->get(route('reports.export', [
            'type' => ReportService::TYPE_STUDENT_VIOLATIONS,
            'format' => 'csv',
        ]))->assertOk()->assertDownload();
        $this->actingAs($teacher)->get(route('reports.export', [
            'tab' => 'layanan',
            'type' => ReportService::TYPE_SERVICE_RECAP,
            'format' => 'csv',
        ]))->assertSessionHasErrors(['tab', 'type']);
        $this->actingAs($teacher)->get(route('reports.export', ['format' => 'csv']))
            ->assertSessionHasErrors(['tab', 'type']);
    }

    public function test_invalid_and_empty_states_are_accessible_and_reset_active_tab(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru State');

        $invalid = $this->actingAs($teacher)->followingRedirects()->get(route('reports.index', [
            'tab' => 'prestasi',
            'date_start' => '2026-08-20',
            'date_end' => '2026-08-01',
        ]));
        $invalid->assertOk()
            ->assertSee('role="alert"', false)
            ->assertSee('tabindex="-1"', false)
            ->assertSee('Tanggal akhir tidak boleh sebelum tanggal awal.');

        $this->actingAs($teacher)->get(route('reports.index', ['tab' => 'prestasi', 'q' => 'Tidak Ada']))
            ->assertOk()
            ->assertSee('Tidak ada murid pada periode atau filter terpilih')
            ->assertSee(route('reports.index', ['tab' => 'prestasi']), false);
    }

    private function userWithRole(string $slug, string $name): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }

    private function reference(string $category, string $code): ReferenceValue
    {
        return ReferenceValue::query()->where('category', $category)->where('code', $code)->firstOrFail();
    }

    /** @return array{Student, Classroom} */
    private function scopedStudent(User $teacher, string $name, string $nisn, string $className): array
    {
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => $className,
            'is_active' => true,
        ]);
        $student = Student::query()->create(['nisn' => $nisn, 'name' => $name, 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-01',
            'decision_number' => 'SK-'.$classroom->id,
            'assigned_by' => $teacher->id,
        ]);

        return [$student, $classroom];
    }

    private function etatib(Student $student, string $identifier, string $violation, int $points, string $date): ExternalTatibRecord
    {
        return ExternalTatibRecord::query()->create([
            'source_identifier' => $identifier,
            'nisn' => $student->nisn,
            'student_id' => $student->id,
            'occurred_at' => $date.' 07:00:00',
            'violation_type' => $violation,
            'category' => 'Kedisiplinan',
            'points' => $points,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function studentWithViolation(Classroom $classroom, int $index): Student
    {
        $student = Student::query()->create([
            'nisn' => str_pad((string) $index, 10, '0', STR_PAD_LEFT),
            'name' => 'Murid Query '.$index,
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);
        $this->etatib($student, 'ET-QUERY-'.$index, 'Pelanggaran '.$index, 1, '2026-08-10');

        return $student;
    }

    private function temporaryStudent(string $nisn, string $name, User $creator, ?Student $reconciled = null): TemporaryStudent
    {
        return TemporaryStudent::query()->create([
            'nisn' => $nisn,
            'input_name' => $name,
            'created_by' => $creator->id,
            'reconciliation_status_id' => $this->reference(
                'reconciliation_status',
                $reconciled === null ? 'menunggu_rekonsiliasi' : 'terekonsiliasi',
            )->id,
            'reconciled_student_id' => $reconciled?->id,
            'reconciled_by' => $reconciled === null ? null : $creator->id,
            'reconciled_at' => $reconciled === null ? null : now(),
        ]);
    }

    private function caseRecord(User $owner, ?Student $student, ?TemporaryStudent $temporary, string $date, string $secret): BkCase
    {
        $case = BkCase::query()->create([
            'student_id' => $student?->id,
            'temporary_student_id' => $temporary?->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::IN_PROGRESS)->id,
            'service_date' => $date,
            'initial_info' => $secret,
            'initial_action' => 'Asesmen awal',
            'created_by' => $owner->id,
        ]);
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $owner->id,
            'assignment_type' => CaseAssignment::TYPE_OWNER,
            'effective_from' => $date,
            'reason' => 'Penanggung jawab test',
            'assigned_by' => $owner->id,
        ]);

        return $case;
    }

    private function consultationRecord(User $counselor, ?Student $student, ?TemporaryStudent $temporary, string $date, string $secret): Consultation
    {
        $consultation = new Consultation([
            'student_id' => $student?->id,
            'temporary_student_id' => $temporary?->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'session_date' => $date,
            'problem' => $secret,
            'handling' => 'Penanganan aman',
            'result' => 'Hasil aman',
            'counselor_id' => $counselor->id,
        ]);
        $consultation->forceFill([
            'status_id' => $this->reference('consultation_status', ServiceRecordStatus::COMPLETED)->id,
            'topic' => $secret,
        ])->save();

        return $consultation;
    }

    private function followUpRecord(BkCase $case, User $recorder, string $status, string $planned, ?string $executed = null): FollowUp
    {
        return FollowUp::query()->create([
            'case_id' => $case->id,
            'follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id,
            'status_id' => $this->reference('follow_up_status', $status)->id,
            'planned_date' => $planned,
            'execution_date' => $executed,
            'result' => 'RAHASIA-HASIL',
            'next_plan' => 'RAHASIA-RENCANA',
            'recorded_by' => $recorder->id,
        ]);
    }

    private function studentWithCase(User $teacher, Classroom $classroom, int $index): Student
    {
        $student = Student::query()->create([
            'nisn' => '1'.str_pad((string) $index, 9, '0', STR_PAD_LEFT),
            'name' => 'Murid Layanan '.$index,
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);
        $this->caseRecord($teacher, $student, null, '2026-08-10', 'Fixture');

        return $student;
    }

    private function achievementRecord(
        Student $student,
        User $recorder,
        string $activity,
        string $level,
        string $verification,
        string $date,
    ): Achievement {
        return Achievement::query()->create([
            'student_id' => $student->id,
            'type_id' => $this->reference('achievement_type', 'akademik')->id,
            'level_id' => $this->reference('achievement_level', $level)->id,
            'activity_name' => $activity,
            'organizer' => 'Sekolah',
            'achievement_date' => $date,
            'result' => 'Juara',
            'evidence_reference' => 'BUKTI-RAHASIA',
            'evidence_description' => 'DESKRIPSI-RAHASIA',
            'notes' => 'CATATAN-RAHASIA',
            'verification_status_id' => $this->reference('achievement_verification_status', $verification)->id,
            'recorded_by' => $recorder->id,
            'verification_notes' => 'VERIFIKASI-RAHASIA',
        ]);
    }
}
