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
use App\Models\TemporaryStudent;
use App\Models\User;
use App\Models\WithdrawalProgress;
use App\Services\OperationalReportRecapService;
use Carbon\CarbonImmutable;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OperationalReportRecapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_classroom_text_filter_applies_to_report_and_document(): void
    {
        [$year, $classroom, $student, $owner] = $this->caseFixture();
        $this->actingAs($owner);
        foreach (['reports.index', 'reports.preview'] as $route) {
            $this->get(route($route, ['classroom_search' => $classroom->name, 'semester' => '1']))
                ->assertOk()->assertSee($student->name);
            $this->get(route($route, ['classroom_search' => 'Kelas Tidak Ada', 'semester' => '1']))
                ->assertOk()->assertDontSee($student->name);
        }
    }

    public function test_coordinator_reads_out_of_scope_records_across_reports_and_student_services(): void
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
            $this->get(route('cases.index', ['tab' => $tab]))->assertOk()->assertSee($student->name);
        }
        $this->get(route('cases.index', ['tab' => 'pengunduran-diri']))->assertOk()->assertSee($student->name);
        $this->get(route('cases.show', $case))->assertOk();
        $this->get(route('consultations.show', [$consultation, 'inline' => 1]))->assertOk();
        $this->get(route('students.show', ['student' => $student, 'tab' => 'konsultasi']))
            ->assertOk()->assertSee($consultation->problem)->assertSee($consultation->result);
        $this->get(route('students.show', ['student' => $student, 'tab' => 'kasus']))
            ->assertOk()->assertSee($case->initial_info);

        foreach (['case' => $case, 'consultation' => $consultation] as $type => $record) {
            $filters = ['academic_year_id' => $year->id, 'service_type' => $type, 'semester' => '1'];
            $this->get(route('reports.index', $filters))->assertOk()->assertSee($student->name);
            $this->get(route('reports.preview', $filters))->assertOk()->assertSee($student->name);
            $this->get(route('reports.records.preview', ['type' => $type, 'id' => $record->id]))
                ->assertOk()->assertSee($student->name);
        }
        $coordinator->roles()->detach(Role::query()->where('slug', 'guru_bk')->firstOrFail());
        $coordinator->unsetRelation('roles');
        $this->get(route('reports.index', ['academic_year_id' => $year->id]))
            ->assertOk()->assertSee($student->name);
        $this->get(route('students.show', ['student' => $student, 'tab' => 'kasus']))
            ->assertOk()->assertSee($case->initial_info);
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

    public function test_document_loads_student_names_in_one_query_per_service(): void
    {
        [$year, $classroom, $student, $owner, $case] = $this->caseFixture();
        $otherStudent = Student::query()->create([
            'nisn' => '0012345679',
            'name' => 'Murid Laporan Kedua',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $otherStudent->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $otherCase = $case->replicate();
        $otherCase->registration_number = 'K-2026-0002';
        $otherCase->student_id = $otherStudent->id;
        $otherCase->save();
        CaseAssignment::query()->create([
            'case_id' => $otherCase->id,
            'user_id' => $owner->id,
            'reason' => 'Pemilik saat pencatatan.',
            'assigned_by' => $owner->id,
        ]);

        foreach ([$student, $otherStudent] as $currentStudent) {
            Consultation::query()->create([
                'student_id' => $currentStudent->id,
                'academic_year_id' => $year->id,
                'classroom_id' => $classroom->id,
                'service_field_id' => $case->service_field_id,
                'session_date' => '2026-09-02',
                'problem' => 'Masalah konsultasi.',
                'handling' => 'Penanganan konsultasi.',
                'result' => 'Hasil konsultasi.',
                'counselor_id' => $owner->id,
            ]);
        }

        $selectCounts = ['students' => 0, 'academic_years' => 0, 'temporary_students' => 0];
        DB::listen(static function ($query) use (&$selectCounts): void {
            foreach (array_keys($selectCounts) as $table) {
                if (preg_match('/^\s*select\b(?:(?!\bfrom\b).)*\bfrom\s+[`"]?'.$table.'[`"]?(?:\s|$)/is', $query->sql)) {
                    $selectCounts[$table]++;
                }
            }
        });

        foreach (['case', 'consultation'] as $type) {
            $selectCounts = array_fill_keys(array_keys($selectCounts), 0);
            $report = app(OperationalReportRecapService::class)->allForDocument($owner, [
                'academic_year_id' => $year->id,
                'service_type' => $type,
                'semester' => '1',
            ]);

            $this->assertCount(2, $report['rows']);
            $this->assertEqualsCanonicalizing(
                [$student->name, $otherStudent->name],
                $report['rows']->pluck('name')->all(),
            );
            $this->assertSame(1, $selectCounts['students'], $type.' harus memuat semua nama murid dalam satu query.');
            $this->assertSame(2, $selectCounts['academic_years'], $type.' tidak boleh memeriksa tahun ajaran per catatan.');
        }

        foreach ([1, 2] as $index) {
            $temporary = TemporaryStudent::query()->create([
                'nisn' => sprintf('009876543%d', $index),
                'input_name' => 'Murid Sementara '.$index,
                'created_by' => $owner->id,
                'reconciliation_status_id' => ReferenceValue::query()
                    ->where('category', 'reconciliation_status')
                    ->where('code', 'menunggu_rekonsiliasi')
                    ->firstOrFail()->id,
            ]);
            Consultation::query()->create([
                'temporary_student_id' => $temporary->id,
                'academic_year_id' => $year->id,
                'classroom_id' => $classroom->id,
                'service_field_id' => $case->service_field_id,
                'session_date' => '2026-09-03',
                'problem' => 'Masalah identitas sementara.',
                'handling' => 'Penanganan konsultasi.',
                'result' => 'Hasil konsultasi.',
                'counselor_id' => $owner->id,
            ]);
        }

        $selectCounts = array_fill_keys(array_keys($selectCounts), 0);
        $report = app(OperationalReportRecapService::class)->allForDocument($owner, [
            'academic_year_id' => $year->id,
            'service_type' => 'consultation',
            'semester' => '1',
        ]);
        $this->assertCount(4, $report['rows']);
        $this->assertEqualsCanonicalizing(
            [$student->name, $otherStudent->name, 'Murid Sementara 1', 'Murid Sementara 2'],
            $report['rows']->pluck('name')->all(),
        );
        $this->assertSame(1, $selectCounts['temporary_students']);
        $this->assertSame(1, $selectCounts['students']);
        $this->assertSame(2, $selectCounts['academic_years']);

        $this->actingAs($this->userWithRole('waka_kesiswaan'))
            ->get(route('reports.preview', ['academic_year_id' => $year->id]))
            ->assertForbidden();
    }

    public function test_document_limit_checks_filtered_count_before_loading_records(): void
    {
        config()->set('sibk.report_max_document_rows', 2);
        [$year, $classroom, , $owner, $case] = $this->caseFixture();
        $otherClassroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'X RPL 2',
        ]);
        $nextYear = AcademicYear::query()->create([
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'is_active' => false,
        ]);
        $nextClassroom = Classroom::query()->create([
            'academic_year_id' => $nextYear->id,
            'name' => 'XI RPL 1',
        ]);

        $firstClassIds = [$case->id];
        foreach ([
            ['K-2026-0002', $year, $classroom],
            ['K-2026-0003', $year, $otherClassroom],
            ['K-2027-0001', $nextYear, $nextClassroom],
        ] as [$number, $recordYear, $recordClassroom]) {
            $record = $case->replicate();
            $record->registration_number = $number;
            $record->academic_year_id = $recordYear->id;
            $record->classroom_id = $recordClassroom->id;
            $record->service_date = $recordYear->id === $nextYear->id ? '2027-09-01' : '2026-09-01';
            $record->save();
            if ($recordClassroom->id === $classroom->id) {
                $firstClassIds[] = $record->id;
            }
            CaseAssignment::query()->create([
                'case_id' => $record->id,
                'user_id' => $owner->id,
                'reason' => 'Pemilik saat pencatatan.',
                'assigned_by' => $owner->id,
            ]);
        }

        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        try {
            app(OperationalReportRecapService::class)->allForDocument($owner, [
                'academic_year_id' => $year->id,
                'service_type' => 'case',
                'semester' => '1',
            ]);
            $this->fail('Dokumen di atas batas harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('classroom_search', $exception->errors());
        }
        $this->assertCount(1, array_filter($queries, static fn (string $sql): bool => str_contains($sql, 'total_count')));
        $this->assertCount(1, array_filter($queries, static fn (string $sql): bool => str_contains($sql, 'report_records')));
        $this->assertCount(0, array_filter($queries, static fn (string $sql): bool => (bool) preg_match(
            '/^\s*select\b(?:(?!\bfrom\b).)*\bfrom\s+[`"]?(?:cases|consultations|students)[`"]?(?:\s|$)/is',
            $sql,
        )));

        $filters = ['academic_year_id' => $year->id, 'service_type' => 'case', 'semester' => '1', 'classroom_id' => $classroom->id];
        $report = app(OperationalReportRecapService::class)->allForDocument($owner, $filters);
        $this->assertSame(2, $report['rows']->count());
        $this->assertEqualsCanonicalizing($firstClassIds, $report['rows']->pluck('record_id')->all());

        $report = app(OperationalReportRecapService::class)->allForDocument($owner, [
            ...$filters,
            'classroom_id' => $otherClassroom->id,
        ]);
        $this->assertSame(1, $report['rows']->count());
        $this->assertSame($otherClassroom->name, $report['rows']->first()['classroom']);

        $report = app(OperationalReportRecapService::class)->allForDocument($owner, [
            'academic_year_id' => $nextYear->id,
            'service_type' => 'case',
            'semester' => '1',
        ]);
        $this->assertSame(1, $report['rows']->count());
        $this->assertSame($nextClassroom->name, $report['rows']->first()['classroom']);

        $page = app(OperationalReportRecapService::class)->paginateForUi($owner, [
            'academic_year_id' => $year->id,
            'service_type' => 'case',
        ]);
        $this->assertSame(3, $page['rows']->total());
        $this->assertSame(3, $page['rows']->count());
        $this->actingAs($this->userWithRole('waka_kesiswaan'))
            ->get(route('reports.preview', ['academic_year_id' => $year->id]))
            ->assertForbidden();
    }

    public function test_document_without_any_academic_year_is_rejected(): void
    {
        config()->set('sibk.report_max_document_rows', 2);
        $owner = $this->userWithRole('guru_bk');

        try {
            app(OperationalReportRecapService::class)->allForDocument($owner, ['service_type' => 'case', 'semester' => '1']);
            $this->fail('Dokumen tanpa tahun ajaran harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('academic_year_id', $exception->errors());
        }
    }

    public function test_document_rejects_records_added_after_its_count_before_hydration(): void
    {
        config()->set('sibk.report_max_document_rows', 2);
        [$year, , , $owner, $case] = $this->caseFixture();
        $second = $case->replicate();
        $second->registration_number = 'K-2026-0002';
        $second->save();
        CaseAssignment::query()->create([
            'case_id' => $second->id,
            'user_id' => $owner->id,
            'reason' => 'Pemilik saat pencatatan.',
            'assigned_by' => $owner->id,
        ]);

        $lateInsertDone = false;
        $queries = [];
        DB::listen(static function ($query) use (&$lateInsertDone, &$queries, $case, $owner): void {
            $queries[] = $query->sql;
            if (! $lateInsertDone && str_contains($query->sql, 'total_count')) {
                $lateInsertDone = true;
                $third = $case->replicate();
                $third->registration_number = 'K-2026-0003';
                $third->save();
                CaseAssignment::query()->create([
                    'case_id' => $third->id,
                    'user_id' => $owner->id,
                    'reason' => 'Pemilik saat pencatatan.',
                    'assigned_by' => $owner->id,
                ]);
            }
        });

        try {
            app(OperationalReportRecapService::class)->allForDocument($owner, [
                'academic_year_id' => $year->id,
                'service_type' => 'case',
                'semester' => '1',
            ]);
            $this->fail('Record baru setelah hitungan harus menolak dokumen.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('classroom_search', $exception->errors());
        }

        $this->assertTrue($lateInsertDone);
        $this->assertCount(0, array_filter($queries, static fn (string $sql): bool => (bool) preg_match(
            '/^\s*select\b(?:(?!\bfrom\b).)*\bfrom\s+[`"]?(?:cases|consultations|students)[`"]?(?:\s|$)/is',
            $sql,
        )));
    }

    public function test_semester_boundaries_and_custom_dates_filter_all_report_services(): void
    {
        [$year, $classroom, $student, $owner, $case] = $this->caseFixture();
        $caseIds = ['2026-09-01' => $case->id];
        foreach (['2026-07-01', '2026-12-31', '2027-01-01', '2027-06-30', '2027-07-01'] as $index => $date) {
            $record = $case->replicate();
            $record->registration_number = sprintf('K-2026-00%02d', $index + 2);
            $record->service_date = $date;
            $record->save();
            CaseAssignment::query()->create([
                'case_id' => $record->id,
                'user_id' => $owner->id,
                'reason' => 'Pemilik saat pencatatan.',
                'assigned_by' => $owner->id,
            ]);
            $caseIds[$date] = $record->id;
        }
        foreach (['2026-12-31', '2027-01-01'] as $index => $date) {
            Consultation::query()->create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'classroom_id' => $classroom->id,
                'service_field_id' => $case->service_field_id,
                'session_date' => $date,
                'problem' => 'Konsultasi batas semester.',
                'handling' => 'Penanganan.',
                'result' => 'Selesai.',
                'counselor_id' => $owner->id,
            ]);
            $withdrawalStudent = Student::query()->create([
                'nisn' => sprintf('008765432%d', $index),
                'name' => 'Murid Pengunduran '.$index,
                'is_active' => true,
            ]);
            StudentClassMembership::query()->create([
                'student_id' => $withdrawalStudent->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => $year->id,
                'is_active' => true,
            ]);
            WithdrawalProgress::query()->create([
                'student_id' => $withdrawalStudent->id,
                'teacher_id' => $owner->id,
                'classroom_id' => $classroom->id,
                'recorded_on' => $date,
                'progress' => WithdrawalProgress::PROGRESS_IN_PROGRESS,
                'reason' => 'Pindah sekolah.',
                'note' => 'Catatan batas semester.',
            ]);
        }

        $service = app(OperationalReportRecapService::class);
        $page = $service->paginateForUi($owner, ['academic_year_id' => $year->id, 'service_type' => 'case']);
        $this->assertSame(6, $page['rows']->total());
        $this->assertNull($page['period']);
        $semesterPage = $service->paginateForUi($owner, [
            'academic_year_id' => $year->id, 'service_type' => 'case', 'semester' => '1',
        ]);
        $this->assertSame(3, $semesterPage['rows']->total());
        $this->assertSame('2026-07-01', $semesterPage['period']['semester_start']);

        foreach (['1' => 3, '2' => 2] as $semester => $caseCount) {
            $report = $service->allForDocument($owner, [
                'academic_year_id' => $year->id, 'service_type' => 'case', 'semester' => (string) $semester,
            ]);
            $this->assertSame($caseCount, $report['rows']->count());
        }
        $this->assertEqualsCanonicalizing(
            [$caseIds['2026-07-01'], $caseIds['2026-09-01'], $caseIds['2026-12-31']],
            $service->allForDocument($owner, [
                'academic_year_id' => $year->id, 'service_type' => 'case', 'semester' => '1',
            ])['rows']->pluck('record_id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$caseIds['2027-01-01'], $caseIds['2027-06-30']],
            $service->allForDocument($owner, [
                'academic_year_id' => $year->id, 'service_type' => 'case', 'semester' => '2',
            ])['rows']->pluck('record_id')->all(),
        );

        foreach (['consultation', 'withdrawal'] as $type) {
            foreach (['1', '2'] as $semester) {
                $report = $service->allForDocument($owner, [
                    'academic_year_id' => $year->id, 'service_type' => $type, 'semester' => $semester,
                ]);
                $this->assertSame(1, $report['rows']->count());
                $this->assertSame($semester === '1' ? '2026-12-31' : '2027-01-01', $report['rows']->first()['date']->toDateString());
            }
        }

        $custom = $service->allForDocument($owner, [
            'academic_year_id' => $year->id, 'service_type' => 'case', 'semester' => '1',
            'date_from' => '2026-09-01', 'date_to' => '2026-12-31',
        ]);
        $this->assertEqualsCanonicalizing(
            [$caseIds['2026-09-01'], $caseIds['2026-12-31']],
            $custom['rows']->pluck('record_id')->all(),
        );
        $this->assertSame([
            'semester' => 1,
            'start' => '2026-09-01',
            'end' => '2026-12-31',
            'semester_start' => '2026-07-01',
            'semester_end' => '2026-12-31',
            'label' => 'Semester 1 · 1 September 2026–31 Desember 2026',
        ], $custom['period']);
        $this->assertSame(2, $service->allForDocument($owner, [
            'academic_year_id' => $year->id, 'service_type' => 'case', 'semester' => '1',
            'date_to' => '2026-09-01',
        ])['rows']->count());
        $this->assertSame(1, $service->allForDocument($owner, [
            'academic_year_id' => $year->id, 'service_type' => 'case', 'semester' => '1',
            'date_from' => '2026-12-31',
        ])['rows']->count());
    }

    public function test_report_period_rejects_missing_or_invalid_semester_dates_and_metadata(): void
    {
        [$year, , , $owner] = $this->caseFixture();
        $service = app(OperationalReportRecapService::class);
        $base = ['academic_year_id' => $year->id, 'service_type' => 'case'];

        foreach ([
            [$base, 'semester'],
            [$base + ['date_from' => '2026-09-01'], 'semester'],
            [$base + ['semester' => '3'], 'semester'],
            [$base + ['semester' => '1', 'date_from' => '2026-02-30'], 'date_from'],
            [$base + ['semester' => '1', 'date_from' => '2026-12-31', 'date_to' => '2026-09-01'], 'date_to'],
            [$base + ['semester' => '1', 'date_to' => '2027-01-01'], 'date_to'],
            [$base + ['semester' => '2', 'date_from' => '2026-12-31'], 'date_from'],
            [$base + ['semester' => '2', 'date_to' => '2028-01-01'], 'date_to'],
        ] as [$filters, $key]) {
            try {
                $service->allForDocument($owner, $filters);
                $this->fail('Periode dokumen tidak valid harus ditolak.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($key, $exception->errors());
            }
        }

        $this->actingAs($owner)->get(route('reports.index', $base + ['semester' => '3']))
            ->assertSessionHasErrors('semester');
        $this->actingAs($owner)->get(route('reports.index', $base + ['date_from' => '2026/09/01']))
            ->assertSessionHasErrors('date_from');
        $invalidRange = $this->actingAs($owner)->get(route('reports.index', $base + [
            'semester' => '1', 'date_from' => '2026-12-31', 'date_to' => '2026-09-01',
        ]));
        $invalidRange->assertSessionHasErrors('date_to');
        $this->get($invalidRange->headers->get('Location'))->assertOk();

        $year->update(['starts_on' => null, 'ends_on' => null]);
        $report = $service->allForDocument($owner, $base + ['semester' => '1']);
        $this->assertSame(1, $report['rows']->count());
        $this->assertSame('2026-07-01', $report['period']['semester_start']);

        $year->update(['starts_on' => '2026-09-01', 'ends_on' => '2027-06-30']);
        try {
            $service->allForDocument($owner, $base + ['semester' => '1']);
            $this->fail('Semester di luar tanggal tahun ajaran harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('academic_year_id', $exception->errors());
        }
        $report = $service->allForDocument($owner, $base + [
            'semester' => '1', 'date_from' => '2026-09-01',
        ]);
        $this->assertSame(1, $report['rows']->count());

        $year->update(['name' => 'Tahun Tidak Pasti', 'starts_on' => null, 'ends_on' => null]);
        try {
            $service->allForDocument($owner, $base + ['semester' => '1']);
            $this->fail('Tahun tanpa tanggal dan nama pasangan yang valid harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('academic_year_id', $exception->errors());
        }

        $year->update(['name' => '2026/2027', 'starts_on' => '2025-07-01']);
        try {
            $service->allForDocument($owner, $base + ['semester' => '1']);
            $this->fail('Tanggal tahun yang bertentangan dengan nama harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('academic_year_id', $exception->errors());
        }
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

    public function test_recap_defaults_to_last_thirty_days_within_selected_semester(): void
    {
        [$year, , , $owner] = $this->caseFixture();
        $service = app(OperationalReportRecapService::class);
        foreach ([
            ['2026-07-01', 1, '2026-07-01', '2026-07-01'],
            ['2026-10-21', 1, '2026-09-22', '2026-10-21'],
            ['2027-01-01', 2, '2027-01-01', '2027-01-01'],
            ['2027-01-30', 2, '2027-01-01', '2027-01-30'],
        ] as [$today, $semester, $start, $end]) {
            $this->travelTo(CarbonImmutable::parse($today, 'Asia/Jakarta')->startOfDay());
            $period = $service->defaultPeriodFor($year);
            $this->assertSame([$semester, $start, $end], [
                $period['semester'], $period['start'], $period['end'],
            ]);
            $this->assertLessThanOrEqual(30, CarbonImmutable::parse($start)->diffInDays($end) + 1);
            $this->actingAs($owner)->get(route('reports.index', ['academic_year_id' => $year->id]))
                ->assertOk()
                ->assertViewHas('recapPeriod', fn (array $selected): bool => $selected['start'] === $start
                    && $selected['end'] === $end && $selected['semester'] === $semester);
        }
        $this->travelBack();
    }

    public function test_recap_default_clamps_to_selected_year_and_recorded_dates(): void
    {
        [$year] = $this->caseFixture();
        $service = app(OperationalReportRecapService::class);
        $this->travelTo(CarbonImmutable::parse('2026-10-21 12:00:00', 'Asia/Jakarta'));

        $year->update(['name' => '2025/2026', 'starts_on' => '2025-07-01', 'ends_on' => '2026-06-30']);
        $past = $service->defaultPeriodFor($year);
        $this->assertSame(['2026-06-01', '2026-06-30', 2], [$past['start'], $past['end'], $past['semester']]);

        $year->update(['name' => '2027/2028', 'starts_on' => '2027-07-15', 'ends_on' => '2028-02-15']);
        $future = $service->defaultPeriodFor($year);
        $this->assertSame(['2027-07-15', '2027-07-15', 1], [$future['start'], $future['end'], $future['semester']]);

        $year->update(['name' => '2026/2027', 'starts_on' => '2026-10-01', 'ends_on' => '2027-01-15']);
        $partial = $service->defaultPeriodFor($year);
        $this->assertSame(['2026-10-01', '2026-10-21', 1], [$partial['start'], $partial['end'], $partial['semester']]);
        $this->travelBack();
    }

    public function test_recap_default_errors_do_not_block_report_list(): void
    {
        [$year, , , $owner] = $this->caseFixture();
        $year->update(['name' => 'Tahun Tidak Pasti', 'starts_on' => null, 'ends_on' => null]);
        $this->actingAs($owner)->get(route('reports.index', ['academic_year_id' => $year->id]))
            ->assertOk()
            ->assertViewHas('recapPeriod', null)
            ->assertViewHas('recapPeriodError', 'Periode tahun ajaran tidak dapat dipastikan.');
    }

    public function test_report_without_academic_year_still_opens_with_recap_error(): void
    {
        $owner = $this->userWithRole('guru_bk');
        $this->actingAs($owner)->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('recapPeriod', null)
            ->assertViewHas('recapPeriodError', 'Tahun ajaran belum tersedia untuk membuat dokumen laporan.');
    }

    public function test_recap_modal_preserves_filters_and_embedded_document_access(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-21 12:00:00', 'Asia/Jakarta'));
        [$year, $classroom, , $owner] = $this->caseFixture();
        $filters = [
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'service_type' => 'case',
            'semester' => '1',
        ];

        $index = $this->actingAs($owner)->get(route('reports.index', $filters));
        $documentFilters = $filters + ['date_from' => '2026-09-22', 'date_to' => '2026-10-21'];
        $index->assertOk()
            ->assertSee('data-bs-target="#report-preview-modal"', false)
            ->assertSee('data-preview-url="'.e(route('reports.preview', [...$documentFilters, 'embedded' => 1])).'"', false)
            ->assertSee('data-export-url="'.e(route('reports.export', [...$documentFilters, 'format' => 'xlsx'])).'"', false)
            ->assertSee('Buat rekap')->assertSee('data-report-period-form', false);

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
        $this->travelBack();
    }

    public function test_recap_semester_controls_respect_partial_academic_year(): void
    {
        [$year, , , $owner] = $this->caseFixture();
        $this->travelTo(CarbonImmutable::parse('2026-10-21 12:00:00', 'Asia/Jakarta'));
        $year->update(['starts_on' => '2026-10-01', 'ends_on' => '2027-01-15']);
        $this->actingAs($owner)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('data-semester-one-start="2026-10-01" data-semester-one-end="2026-12-31"', false)
            ->assertSee('data-semester-two-start="2027-01-01" data-semester-two-end="2027-01-15"', false)
            ->assertSee('min="2026-10-01" max="2026-12-31" required', false);
        $year->update(['ends_on' => '2026-12-15']);
        $response = $this->get(route('reports.index'))->assertOk();
        $this->assertMatchesRegularExpression('/<option value="2"[^>]*disabled/', $response->getContent());
        $this->travelBack();
    }

    public function test_old_period_parameters_do_not_silently_filter_report_list(): void
    {
        [$year, , , $owner, $case] = $this->caseFixture();
        $this->actingAs($owner)->get(route('reports.index', [
            'academic_year_id' => $year->id,
            'semester' => '2',
            'date_from' => '2027-01-01',
            'date_to' => '2027-01-31',
        ]))->assertOk()->assertViewHas('report', function (array $report) use ($case): bool {
            $this->assertSame([$case->id], $report['rows']->pluck('record_id')->all());
            $this->assertNull($report['period']);
            foreach (['semester', 'date_from', 'date_to'] as $field) {
                $this->assertStringNotContainsString($field.'=', $report['rows']->url(2));
            }
            return true;
        });
    }

    public function test_recap_document_heading_follows_the_selected_service(): void
    {
        [$year, , , $owner] = $this->caseFixture();

        foreach ([
            'case' => 'Rekap Catatan Permasalahan',
            'consultation' => 'Rekap Catatan Bimbingan & Konseling',
            'withdrawal' => 'Rekap Catatan Pengunduran Diri',
        ] as $type => $heading) {
            $this->actingAs($owner)->get(route('reports.preview', [
                'academic_year_id' => $year->id,
                'service_type' => $type,
                'semester' => '1',
                'embedded' => 1,
            ]))->assertOk()
                ->assertSee($heading)
                ->assertDontSee('Ringkasan laporan:');
        }
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
