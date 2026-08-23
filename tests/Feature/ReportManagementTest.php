<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\CaseCoordination;
use App\Models\Classroom;
use App\Models\ConsultationPrivateNote;
use App\Models\ExternalTatibRecord;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\CaseService;
use App\Services\ConsultationService;
use App\Services\ReportService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportManagementTest extends TestCase
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

    public function test_report_routes_follow_role_authorization_and_hide_waka_consultation(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Laporan');
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Laporan');
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Laporan');
        $admin = $this->userWithRole('admin_it', 'Admin Laporan');

        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->actingAs($teacher)->get(route('reports.index'))->assertOk()->assertSee('data-page-id="PG-301"', false);
        $this->actingAs($coordinator)->get(route('reports.index'))->assertOk()->assertSee('Konsultasi');
        $this->actingAs($waka)->get(route('reports.index'))->assertOk()->assertDontSee('Rekap konsultasi tanpa isi sensitif');
        $this->actingAs($waka)->get(route('reports.preview', ['type' => ReportService::TYPE_CONSULTATIONS]))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.preview', ['type' => ReportService::TYPE_SERVICE_RECAP]))->assertForbidden();
    }

    public function test_teacher_scope_and_historical_class_are_used_without_exposing_identity(): void
    {
        $teacherA = $this->userWithRole('guru_bk', 'Guru A');
        $teacherB = $this->userWithRole('guru_bk', 'Guru B');
        [$studentA, $currentClassA] = $this->scopedStudent($teacherA, 'Murid Rahasia Alpha', '0012345678', 'XI RPL 1');
        [$studentB] = $this->scopedStudent($teacherB, 'Murid Rahasia Beta', '0098765432', 'XI RPL 2');
        $oldClass = Classroom::query()->create(['academic_year_id' => $this->year->id, 'name' => 'X RPL Historis', 'is_active' => true]);
        StudentClassMembership::query()->where('student_id', $studentA->id)->update(['effective_from' => '2026-08-11']);
        StudentClassMembership::query()->create([
            'student_id' => $studentA->id,
            'classroom_id' => $oldClass->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-01',
            'effective_until' => '2026-08-10',
            'is_active' => true,
        ]);
        $this->etatib($studentA, 'ET-A', '=HYPERLINK("https://invalid.test")', 'Kedisiplinan', 10, '2026-08-05');
        $this->etatib($studentB, 'ET-B', 'Data Guru Lain', 'Kerapian', 15, '2026-08-06');

        $response = $this->actingAs($teacherA)->get(route('reports.preview', [
            'type' => ReportService::TYPE_STUDENT_VIOLATIONS,
            'academic_year_id' => $this->year->id,
        ]));
        $response->assertOk()
            ->assertSee('data-page-id="PG-302"', false)
            ->assertSee('X RPL Historis')
            ->assertSee('M.R.A.')
            ->assertSee('00******78')
            ->assertDontSee($studentA->name)
            ->assertDontSee($studentA->nisn)
            ->assertDontSee($studentB->name)
            ->assertDontSee('Data Guru Lain');
        $this->assertNotSame($currentClassA->id, $oldClass->id);
        $classReport = app(ReportService::class)->build($teacherA, ['type' => ReportService::TYPE_CLASS_VIOLATIONS], false);
        $pointsReport = app(ReportService::class)->build($teacherA, ['type' => ReportService::TYPE_VIOLATION_POINTS], false);
        $this->assertSame('X RPL Historis', $classReport['rows']->first()['cells'][0]['value']);
        $this->assertSame('10 poin', $pointsReport['rows']->first()['cells'][3]['value']);
    }

    public function test_coordinator_combines_scopes_while_waka_only_sees_linked_coordination_data(): void
    {
        $teacherA = $this->userWithRole('guru_bk', 'Guru A');
        $teacherB = $this->userWithRole('guru_bk', 'Guru B');
        [$studentA] = $this->scopedStudent($teacherA, 'Murid Koordinasi A', '0011111111', 'X AKL 1');
        [$studentB] = $this->scopedStudent($teacherB, 'Murid Koordinasi B', '0022222222', 'X AKL 2');
        $caseA = $this->caseFor($teacherA, $studentA);
        $caseB = $this->caseFor($teacherB, $studentB);
        $recordA = $this->etatib($studentA, 'ET-KOOR-A', 'Terlambat A', 'Kedisiplinan', 5, '2026-08-10');
        $this->etatib($studentB, 'ET-KOOR-B', 'Terlambat B', 'Kedisiplinan', 7, '2026-08-11');
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Terbatas');
        CaseCoordination::query()->create([
            'case_id' => $caseA->id,
            'waka_user_id' => $waka->id,
            'status_id' => $this->reference('coordination_status', 'menunggu')->id,
            'coordination_need' => 'Koordinasi kebijakan.',
            'recorded_by' => $teacherA->id,
            'coordinated_at' => now(),
        ]);
        $caseA->etatibRecords()->attach($recordA->id, ['linked_by' => $teacherA->id]);
        FollowUp::query()->create([
            'case_id' => $caseA->id,
            'follow_up_type_id' => $this->reference('follow_up_type', 'konsultasi_individual')->id,
            'status_id' => $this->reference('follow_up_status', 'terjadwal')->id,
            'planned_date' => '2026-08-22',
            'result' => 'HASIL-PRIVAT-WAKA',
            'next_plan' => 'RENCANA-PRIVAT-WAKA',
            'recorded_by' => $teacherA->id,
        ]);

        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Gabungan');
        $coordinatorReport = app(ReportService::class)->build($coordinator, ['type' => ReportService::TYPE_STUDENT_VIOLATIONS], false);
        $wakaReport = app(ReportService::class)->build($waka, ['type' => ReportService::TYPE_STUDENT_VIOLATIONS], false);
        $recap = app(ReportService::class)->build($coordinator, ['type' => ReportService::TYPE_SERVICE_RECAP], false);
        $this->assertCount(2, $coordinatorReport['rows']);
        $this->assertCount(1, $wakaReport['rows']);
        $this->assertCount(2, $recap['rows']);

        $this->actingAs($waka)->get(route('reports.preview', ['type' => ReportService::TYPE_FOLLOW_UPS]))
            ->assertOk()
            ->assertSee($caseA->registration_number)
            ->assertDontSee($caseB->registration_number)
            ->assertDontSee('HASIL-PRIVAT-WAKA')
            ->assertDontSee('RENCANA-PRIVAT-WAKA');
    }

    public function test_consultation_report_never_loads_private_or_general_narrative(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Konsultasi');
        [$student] = $this->scopedStudent($teacher, 'Nama Lengkap Konsultasi', '0033333333', 'X DKV 1');
        $consultation = app(ConsultationService::class)->create([
            'student_id' => $student->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('consultation_status', 'terlaksana')->id,
            'topic' => 'Topik yang tidak masuk laporan',
            'session_date' => '2026-08-19',
            'general_summary' => 'RINGKASAN-UMUM-TIDAK-DIEKSPOR',
            'internal_note' => 'CATATAN-INTERNAL-RAHASIA',
            'sensitive_content' => 'ISI-SENSITIF-RAHASIA',
        ], $teacher);
        $this->assertDatabaseHas('consultation_private_notes', ['consultation_id' => $consultation->id]);
        $this->assertInstanceOf(ConsultationPrivateNote::class, $consultation->privateNote()->first());

        $this->actingAs($teacher)->get(route('reports.preview', ['type' => ReportService::TYPE_CONSULTATIONS]))
            ->assertOk()
            ->assertSee($consultation->registration_number)
            ->assertSee('N.L.K.')
            ->assertDontSee($student->name)
            ->assertDontSee('RINGKASAN-UMUM-TIDAK-DIEKSPOR')
            ->assertDontSee('CATATAN-INTERNAL-RAHASIA')
            ->assertDontSee('ISI-SENSITIF-RAHASIA');
    }

    public function test_filters_pagination_and_achievement_empty_state_are_database_driven(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Filter');
        [$student] = $this->scopedStudent($teacher, 'Murid Filter', '0044444444', 'X MPLB 1');
        foreach (range(1, 21) as $index) {
            $this->etatib($student, 'ET-PAGE-'.$index, 'Pelanggaran '.$index, $index === 1 ? 'Kerapian' : 'Kedisiplinan', 1, '2026-08-10');
        }

        $allRows = app(ReportService::class)->build($teacher, ['type' => ReportService::TYPE_STUDENT_VIOLATIONS]);
        $this->assertSame(21, $allRows['rows']->total());
        $this->assertTrue($allRows['rows']->hasMorePages());
        $report = app(ReportService::class)->build($teacher, [
            'type' => ReportService::TYPE_STUDENT_VIOLATIONS,
            'category' => 'Kedisiplinan',
        ]);
        $this->assertSame(20, $report['rows']->total());
        $this->assertFalse($report['rows']->hasMorePages());

        $this->actingAs($teacher)->get(route('reports.preview', [
            'type' => ReportService::TYPE_ACHIEVEMENTS,
        ]))->assertOk()->assertSee('Tidak ada data untuk filter dan kewenangan yang dipilih')->assertDontSee('LKS Web Technologies');
        $this->actingAs($teacher)->get(route('reports.preview', [
            'type' => ReportService::TYPE_STUDENT_VIOLATIONS,
            'date_start' => '2026-08-20',
            'date_end' => '2026-08-01',
        ]))->assertSessionHasErrors('date_end');
        $this->actingAs($teacher)->get(route('reports.preview', ['type' => 'tidak-tersedia']))->assertSessionHasErrors('type');
    }

    public function test_csv_uses_same_redacted_rows_and_rejects_unavailable_formats(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru CSV');
        [$student] = $this->scopedStudent($teacher, 'Nama CSV Rahasia', '0055555555', 'XI BDP 1');
        $this->etatib($student, 'ET-CSV', '=SUM(1+1)', 'Kedisiplinan', 9, '2026-08-10');

        $response = $this->actingAs($teacher)->get(route('reports.export', [
            'type' => ReportService::TYPE_STUDENT_VIOLATIONS,
            'format' => 'csv',
        ]));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('N.C.R.', $csv);
        $this->assertStringContainsString("'=SUM(1+1)", $csv);
        $this->assertStringNotContainsString($student->name, $csv);
        $this->assertStringNotContainsString($student->nisn, $csv);
        $this->actingAs($teacher)->get(route('reports.preview', ['type' => ReportService::TYPE_STUDENT_VIOLATIONS]))
            ->assertOk()->assertSee('N.C.R.')->assertSee('00******55');

        $this->actingAs($teacher)->get(route('reports.export', [
            'type' => ReportService::TYPE_STUDENT_VIOLATIONS,
        ]))->assertSessionHasErrors('format');

        foreach (['xlsx', 'pdf'] as $format) {
            $this->actingAs($teacher)->get(route('reports.export', [
                'type' => ReportService::TYPE_STUDENT_VIOLATIONS,
                'format' => $format,
            ]))->assertSessionHasErrors('format');
        }
    }

    public function test_all_seven_report_definitions_can_be_built_without_fixture_rows(): void
    {
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Seluruh Laporan');

        foreach (ReportService::types() as $type) {
            $report = app(ReportService::class)->build($coordinator, ['type' => $type], false);
            $this->assertSame($type, $report['id']);
            $this->assertNotEmpty($report['columns']);
        }
    }

    public function test_special_case_assignment_and_multi_role_are_evaluated_separately(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Kasus Khusus');
        $owner = $this->userWithRole('guru_bk', 'Guru Pemilik');
        [$student] = $this->scopedStudent($owner, 'Murid Kasus Khusus', '0066666666', 'X PSPT 1');
        $case = $this->caseFor($owner, $student);
        $this->etatib($student, 'ET-KHUSUS', 'Pelanggaran kasus khusus', 'Kedisiplinan', 8, '2026-08-10');
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Penugasan');
        $assignment = CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $teacher->id,
            'assignment_type' => CaseAssignment::TYPE_ADDITIONAL,
            'effective_from' => '2026-08-01',
            'reason' => 'Pendampingan khusus.',
            'assigned_by' => $coordinator->id,
        ]);

        $scoped = app(ReportService::class)->build($teacher, ['type' => ReportService::TYPE_STUDENT_VIOLATIONS], false);
        $this->assertCount(1, $scoped['rows']);
        $assignment->update(['effective_until' => '2026-08-19']);
        $expired = app(ReportService::class)->build($teacher, ['type' => ReportService::TYPE_STUDENT_VIOLATIONS], false);
        $this->assertCount(0, $expired['rows']);

        $teacher->roles()->attach(Role::query()->where('slug', 'koordinator_bk')->firstOrFail());
        $combined = app(ReportService::class)->build($teacher->refresh(), ['type' => ReportService::TYPE_STUDENT_VIOLATIONS], false);
        $this->assertCount(1, $combined['rows']);
    }

    public function test_report_preview_query_count_does_not_grow_with_page_rows(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Regresi Query');
        [$student] = $this->scopedStudent($teacher, 'Murid Regresi Query', '0077777777', 'X Query 1');
        $this->etatib($student, 'ET-QUERY-1', 'Pelanggaran 1', 'Kedisiplinan', 1, '2026-08-10');
        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $beforeSingle = count($queries);
        app(ReportService::class)->build($teacher, ['type' => ReportService::TYPE_STUDENT_VIOLATIONS]);
        $singleCount = count($queries) - $beforeSingle;

        foreach (range(2, 25) as $index) {
            $this->etatib($student, 'ET-QUERY-'.$index, 'Pelanggaran '.$index, 'Kedisiplinan', 1, '2026-08-10');
        }
        $beforeMany = count($queries);
        $report = app(ReportService::class)->build($teacher, ['type' => ReportService::TYPE_STUDENT_VIOLATIONS]);
        $manyCount = count($queries) - $beforeMany;

        $this->assertCount(20, $report['rows']->items());
        $this->assertLessThanOrEqual($singleCount + 2, $manyCount);
    }

    /** @return array{Student, Classroom} */
    private function scopedStudent(User $teacher, string $name, string $nisn, string $className): array
    {
        $classroom = Classroom::query()->create(['academic_year_id' => $this->year->id, 'name' => $className, 'is_active' => true]);
        $student = Student::query()->create(['nisn' => $nisn, 'name' => $name, 'is_active' => true]);
        StudentClassMembership::query()->create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $this->year->id, 'effective_from' => '2026-07-01', 'is_active' => true]);
        TeacherAssignment::query()->create(['user_id' => $teacher->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $this->year->id, 'effective_from' => '2026-07-01', 'decision_number' => 'SK-'.$className, 'assigned_by' => $teacher->id]);

        return [$student, $classroom];
    }

    private function caseFor(User $teacher, Student $student): BkCase
    {
        return app(CaseService::class)->createCase([
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2026-08-01',
            'initial_info' => 'Informasi awal.',
            'initial_action' => 'Asesmen awal.',
        ], $teacher);
    }

    private function etatib(Student $student, string $identifier, string $violation, string $category, int $points, string $date): ExternalTatibRecord
    {
        return ExternalTatibRecord::query()->create([
            'source_identifier' => $identifier,
            'nisn' => $student->nisn,
            'student_id' => $student->id,
            'occurred_at' => $date.' 07:00:00',
            'violation_type' => $violation,
            'category' => $category,
            'points' => $points,
            'source_status' => 'Aktif',
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function reference(string $category, string $code): ReferenceValue
    {
        return ReferenceValue::query()->where('category', $category)->where('code', $code)->firstOrFail();
    }

    private function userWithRole(string $slug, string $name): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
