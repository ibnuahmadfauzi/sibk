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
use App\Models\TemporaryStudent;
use App\Models\User;
use App\Services\WakaDashboardService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WakaDashboardTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-09-13 09:00:00');
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
        $this->year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
    }

    public function test_waka_dashboard_shows_school_metrics_without_case_codes(): void
    {
        [$waka, $coordinated] = $this->dashboardFixture();

        $this->actingAs($waka)->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSeeInOrder([
                'Murid',
                'Sedang Ditangani',
                'Perlu Tindak Lanjut',
                'Baru Bulan Ini',
                'Grafik Catatan BK',
                'Murid per Tingkat',
                'Kelas 10',
                'Kelas 11',
                'Kelas 12',
                'Catatan Permasalahan Terbanyak',
                'id="waka-follow-up-title"',
            ], false)
            ->assertSee('Jumlah murid yang memiliki catatan BK setiap bulan.')
            ->assertSee('Setiap murid dihitung sekali per bulan.')
            ->assertSee('Grafik tren murid per bulan')
            ->assertSee('bulan berjalan')
            ->assertDontSee($coordinated->registration_number)
            ->assertDontSee('SENTINEL-INTERNAL');
    }

    public function test_dashboard_shows_latest_cases_without_action_buttons(): void
    {
        [$waka, $coordinated, $notCoordinated] = $this->dashboardFixture();

        $response = $this->actingAs($waka)->get(route('dashboard.preview'));

        $response->assertOk()
            ->assertDontSee('Membutuhkan Perhatian')
            ->assertDontSee('Lihat semua')
            ->assertDontSee('Lihat detail')
            ->assertDontSee('href="'.route('cases.show', $coordinated).'"', false)
            ->assertDontSee('href="'.route('cases.show', $notCoordinated).'"', false);
    }

    public function test_waka_dashboard_view_is_audited_without_sensitive_data(): void
    {
        [$waka] = $this->dashboardFixture();

        $this->actingAs($waka)->get(route('dashboard.preview'))->assertOk();

        $audit = $waka->auditLogs()->where('action', 'waka.monitoring.viewed')->latest('id')->firstOrFail();
        $this->assertStringContainsString('dashboard', $audit->summary);
        $this->assertStringContainsString('academic_year_id', $audit->summary);
        $this->assertStringContainsString((string) $this->year->id, $audit->summary);
        $this->assertStringNotContainsString('SENTINEL-INTERNAL', $audit->summary);
        $this->assertStringNotContainsString('K-2026-', $audit->summary);
    }

    public function test_follow_up_uses_current_status_and_existing_summary_only(): void
    {
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Perhatian');
        $owner = $this->userWithRole('guru_bk', 'Guru BK Perhatian');
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'XI RPL 3',
            'is_active' => true,
        ]);
        $first = $this->createCase($owner, $classroom, '9055555555', 'Current Pertama', 'K-CURRENT-1', 'sedang_diproses', '2026-09-11');
        $first->update(['follow_up_type_id' => $this->reference('follow_up_type', 'surat_pernyataan')->id]);
        $second = $this->createCase($owner, $classroom, '9066666666', 'Current Kedua', 'K-CURRENT-2', 'membutuhkan_tindak_lanjut', '2026-09-11');
        $second->update(['resolution_summary' => 'Ringkasan aman.', 'follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id]);

        $dashboard = app(WakaDashboardService::class)->build($waka, $this->year);

        $this->assertSame('1', $dashboard['metrics'][2]['value']);
        $this->assertSame(['Current Kedua'], array_column($dashboard['follow_up_students'], 'name'));
        $this->assertSame('Ringkasan aman.', $dashboard['follow_up_students'][0]['services'][0]['summary']);
        $this->assertSame('Home Visit', $dashboard['follow_up_students'][0]['services'][0]['follow_up']);
        $this->assertSame('Permasalahan', $dashboard['follow_up_students'][0]['services'][0]['service']);
        $this->assertStringNotContainsString('SENTINEL-INTERNAL', json_encode($dashboard, JSON_THROW_ON_ERROR));
    }

    public function test_counts_unique_students_across_cases_and_consultations_by_month_and_first_history(): void
    {
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Statistik');
        $owner = $this->userWithRole('guru_bk', 'Guru Statistik');
        $classroom = Classroom::query()->create(['academic_year_id' => $this->year->id, 'name' => '10 PSPT 1', 'is_active' => true]);
        $a = $this->createCase($owner, $classroom, '9111111111', 'Murid Lama', 'K-STAT-1', 'sedang_diproses', '2026-07-10');
        $this->createCase($owner, $classroom, '9222222222', 'Murid Baru', 'K-STAT-2', 'membutuhkan_tindak_lanjut', '2026-09-10');
        $this->createConsultation($owner, $classroom, $a->student_id, '2026-09-01');
        $this->createConsultation($owner, $classroom, $a->student_id, '2026-09-02');
        $this->createConsultation($owner, $classroom, $this->createCase($owner, $classroom, '9333333333', 'Murid Konsultasi', 'K-STAT-3', 'selesai', '2026-09-03')->student_id, '2026-09-04');

        $dashboard = app(WakaDashboardService::class)->build($waka, $this->year);

        $this->assertSame(['3', '2', '1', '2'], array_column($dashboard['metrics'], 'value'));
        $this->assertSame(12, count($dashboard['trend']));
        $this->assertSame(1, $dashboard['trend'][0]['count']);
        $this->assertSame(0, $dashboard['trend'][1]['count']);
        $this->assertSame(3, $dashboard['trend'][2]['count']);
        $this->assertSame(3, $dashboard['grades'][0]['count']);
        $this->assertSame(['X', 'XI', 'XII'], array_column($dashboard['grades'], 'label'));
        $this->assertSame(1, count($dashboard['follow_up_students']));

        $classroom->update(['name' => 'Kelas tanpa tingkat', 'grade_level' => null]);
        $withoutGrade = app(WakaDashboardService::class)->build($waka, $this->year);
        $this->assertSame([0, 0, 0], array_column($withoutGrade['grades'], 'count'));
        $this->assertSame($dashboard['metrics'], $withoutGrade['metrics']);
        $this->assertSame($dashboard['trend'], $withoutGrade['trend']);
    }

    public function test_new_this_month_uses_first_service_across_prior_years(): void
    {
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Histori');
        $owner = $this->userWithRole('guru_bk', 'Guru Histori');
        $oldYear = AcademicYear::query()->create(['name' => '2025/2026', 'starts_on' => '2025-07-01', 'ends_on' => '2026-06-30', 'is_active' => false]);
        $oldClass = Classroom::query()->create(['academic_year_id' => $oldYear->id, 'name' => 'X RPL 1', 'is_active' => false]);
        $student = Student::query()->create(['nisn' => '9444444444', 'name' => 'Murid Berulang', 'is_active' => true]);
        $classroom = Classroom::query()->create(['academic_year_id' => $this->year->id, 'name' => 'XI RPL 1', 'is_active' => true]);
        $this->createConsultation($owner, $oldClass, $student->id, '2026-06-01', $oldYear);
        $this->createConsultation($owner, $classroom, $student->id, '2026-09-05');

        $dashboard = app(WakaDashboardService::class)->build($waka, $this->year);

        $this->assertSame(['1', '0', '0', '0'], array_column($dashboard['metrics'], 'value'));
        $this->assertSame(1, $dashboard['trend'][2]['count']);
        $this->assertSame(1, $dashboard['grades'][1]['count']);
    }

    public function test_reconciled_temporary_identity_counts_once_and_shows_canonical_name(): void
    {
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Identitas');
        $owner = $this->userWithRole('guru_bk', 'Guru Identitas');
        $classroom = Classroom::query()->create(['academic_year_id' => $this->year->id, 'name' => 'X RPL 1', 'is_active' => true]);
        $case = $this->createCase($owner, $classroom, '9555555555', 'Nama Resmi', 'K-IDENT-1', 'membutuhkan_tindak_lanjut', '2026-09-10');
        $temporary = TemporaryStudent::query()->create(['nisn' => '9777777777', 'input_name' => 'Nama Lama', 'reconciled_student_id' => $case->student_id, 'reconciliation_status_id' => $this->reference('reconciliation_status', 'terekonsiliasi')->id, 'created_by' => $owner->id]);
        $other = $this->createCase($owner, $classroom, '9666666666', 'Identitas Lain', 'K-IDENT-2', 'selesai', '2026-09-11');
        $other->update(['student_id' => null, 'temporary_student_id' => $temporary->id, 'status_id' => $this->reference('case_status', 'membutuhkan_tindak_lanjut')->id]);

        $dashboard = app(WakaDashboardService::class)->build($waka, $this->year);

        $this->assertSame(['1', '1', '1', '1'], array_column($dashboard['metrics'], 'value'));
        $this->assertSame(1, count($dashboard['follow_up_students']));
        $this->assertSame('Nama Resmi', $dashboard['follow_up_students'][0]['name']);
        $this->assertSame(2, count($dashboard['follow_up_students'][0]['services']));
        $this->assertSame([['label' => 'X RPL 1', 'count' => 1]], $dashboard['top_case_classrooms']);
    }

    public function test_dashboard_service_does_not_expand_case_or_consultation_access_for_admin(): void
    {
        $admin = $this->userWithRole('admin_it', 'Admin Statistik');
        $owner = $this->userWithRole('guru_bk', 'Guru Statistik');
        $classroom = Classroom::query()->create(['academic_year_id' => $this->year->id, 'name' => 'X RPL 1', 'is_active' => true]);
        $case = $this->createCase($owner, $classroom, '9888888888', 'Murid Privat', 'K-PRIVATE', 'membutuhkan_tindak_lanjut', '2026-09-10');
        $this->createConsultation($owner, $classroom, $case->student_id, '2026-09-11');

        $dashboard = app(WakaDashboardService::class)->build($admin, $this->year);

        $this->assertSame(['0', '0', '0', '0'], array_column($dashboard['metrics'], 'value'));
        $this->assertSame([], $dashboard['follow_up_students']);
        $this->assertSame([], $dashboard['top_case_classrooms']);
        $this->assertStringNotContainsString('Murid Privat', json_encode($dashboard, JSON_THROW_ON_ERROR));
    }

    public function test_top_classrooms_count_unique_case_students_only_within_period(): void
    {
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Ranking');
        $owner = $this->userWithRole('guru_bk', 'Guru Ranking');
        foreach (['X A', 'X B', 'X C', 'X D'] as $index => $name) {
            $classroom = Classroom::query()->create(['academic_year_id' => $this->year->id, 'name' => $name, 'is_active' => true]);
            $case = $this->createCase($owner, $classroom, '900000000'.$index, 'Murid '.$index, 'K-RANK-'.$index, 'selesai', '2026-09-01');
            if ($index === 1) {
                $this->createCase($owner, $classroom, '9000000010', 'Murid Tambahan', 'K-RANK-EXTRA', 'selesai', '2026-09-02');
            }
            if ($index === 3) {
                foreach (range(1, 4) as $extra) {
                    $duplicate = $case->replicate();
                    $duplicate->registration_number = 'K-DUP-'.$extra;
                    $duplicate->save();
                    $student = Student::query()->create(['nisn' => '900000002'.$extra, 'name' => 'Konsultasi '.$extra, 'is_active' => true]);
                    $this->createConsultation($owner, $classroom, $student->id, '2026-09-03');
                    $this->createCase($owner, $classroom, '900000003'.$extra, 'Di luar periode '.$extra, 'K-OLD-'.$extra, 'selesai', '2026-06-01');
                }
            }
        }

        $dashboard = app(WakaDashboardService::class)->build($waka, $this->year);
        $this->assertSame([
            ['label' => 'X B', 'count' => 2],
            ['label' => 'X A', 'count' => 1],
            ['label' => 'X C', 'count' => 1],
        ], $dashboard['top_case_classrooms']);
    }

    private function createConsultation(User $owner, Classroom $classroom, int $studentId, string $date, ?AcademicYear $year = null): Consultation
    {
        return Consultation::query()->create([
            'student_id' => $studentId,
            'academic_year_id' => ($year ?? $this->year)->id,
            'classroom_id' => $classroom->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'session_date' => $date,
            'problem' => 'PRIVATE-CONSULTATION',
            'handling' => 'Penanganan.',
            'result' => 'Hasil.',
            'counselor_id' => $owner->id,
        ]);
    }

    /** @return array{User, BkCase, BkCase} */
    private function dashboardFixture(): array
    {
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Dashboard');
        $owner = $this->userWithRole('guru_bk', 'Guru BK Dashboard');
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'XI RPL 2',
            'is_active' => true,
        ]);

        $coordinated = $this->createCase(
            owner: $owner,
            classroom: $classroom,
            nisn: '9011111111',
            name: 'Murid Terkoordinasi',
            number: 'K-2026-DASH-01',
            status: 'sedang_diproses',
            date: '2026-09-10',
        );
        $notCoordinated = $this->createCase(
            owner: $owner,
            classroom: $classroom,
            nisn: '9022222222',
            name: 'Murid Belum Terkoordinasi',
            number: 'K-2026-DASH-02',
            status: 'membutuhkan_tindak_lanjut',
            date: '2026-09-09',
        );
        $completed = $this->createCase(
            owner: $owner,
            classroom: $classroom,
            nisn: '9033333333',
            name: 'Murid Selesai',
            number: 'K-2026-DASH-03',
            status: 'selesai',
            date: '2026-09-03',
        );
        $completed->update(['closed_at' => '2026-09-11']);

        return [$waka, $coordinated, $notCoordinated];
    }

    private function createCase(
        User $owner,
        Classroom $classroom,
        string $nisn,
        string $name,
        string $number,
        string $status,
        string $date,
    ): BkCase {
        $student = Student::query()->create(['nisn' => $nisn, 'name' => $name, 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'is_active' => true,
        ]);
        $case = BkCase::query()->create([
            'academic_year_id' => $this->year->id,
            'classroom_id' => $classroom->id,
            'registration_number' => $number,
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('case_status', $status)->id,
            'service_date' => $date,
            'initial_info' => 'Informasi awal rahasia.',
            'initial_action' => 'Asesmen awal.',
            'waka_summary' => 'Ringkasan aman untuk Waka.',
            'internal_note' => 'SENTINEL-INTERNAL',
            'created_by' => $owner->id,
        ]);
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $owner->id,
            'reason' => 'Penanggung jawab kasus.',
            'assigned_by' => $owner->id,
        ]);

        return $case;
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
