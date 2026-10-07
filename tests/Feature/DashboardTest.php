<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\StudentDeparture;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\DashboardService;
use App\Support\ServiceRecordStatus;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_dashboard_is_scoped_for_teacher_coordinator_waka_and_admin(): void
    {
        $teacherA = $this->userWithRole('guru_bk', 'Guru A');
        $teacherB = $this->userWithRole('guru_bk', 'Guru B');
        [$studentA, $caseA] = $this->createScopedCase($teacherA, 'X RPL 1', '0011111111', 'Murid Cakupan A');
        [$studentB, $caseB] = $this->createScopedCase($teacherB, 'X RPL 2', '0022222222', 'Murid Cakupan B');
        $caseA->update([
            'status_id' => $this->reference('case_status', ServiceRecordStatus::NEEDS_FOLLOW_UP)->id,
            'follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id,
        ]);
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Terkoordinasi');

        $service = app(DashboardService::class);
        $teacherDashboard = $service->forUser($teacherA, $this->year);
        $this->assertSame('1', $this->stat($teacherDashboard, 'Murid Binaan'));
        $this->assertSame('1', $this->stat($teacherDashboard, 'Belum Selesai'));
        $this->assertSame('Kelas Binaan', $teacherDashboard['context_panel']['title']);
        $this->assertSame('1 murid', $this->contextValue($teacherDashboard, 'X RPL 1'));
        $this->assertSame('1', $this->stat($teacherDashboard, 'Perlu Tindak Lanjut'));
        $this->assertSame('0', $this->stat($teacherDashboard, 'Pelanggaran e-Tatib'));
        $this->assertSame('Permasalahan aktif', collect($teacherDashboard['stats'])->firstWhere('label', 'Belum Selesai')['meta']);
        $followUpStat = collect($teacherDashboard['stats'])->firstWhere('label', 'Perlu Tindak Lanjut');
        $this->assertSame('Menunggu tindak lanjut', $followUpStat['meta']);
        $this->assertSame('warning', $followUpStat['tone']);
        $this->assertSame('Permasalahan Pribadi', $teacherDashboard['tindak_lanjut'][0]['title']);
        $this->assertSame('Home Visit', $teacherDashboard['tindak_lanjut'][0]['follow_up']);
        $this->assertSame('Layanan permasalahan', $teacherDashboard['tindak_lanjut'][0]['code']);
        $this->assertSame('Tindak Lanjut', $teacherDashboard['tindak_lanjut'][0]['status']);
        $this->assertStringContainsString($studentA->name.' · X RPL 1', $teacherDashboard['tindak_lanjut'][0]['context_label']);
        $this->assertStringNotContainsString($studentB->name, json_encode($teacherDashboard, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString($caseA->registration_number, json_encode($teacherDashboard, JSON_THROW_ON_ERROR));

        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator');
        $coordinatorDashboard = $service->forUser($coordinator, $this->year);
        $this->assertSame('2', $this->stat($coordinatorDashboard, 'Murid dalam cakupan'));
        $this->assertSame('2', $this->stat($coordinatorDashboard, 'Permasalahan aktif'));
        $this->assertSame('2', $this->contextValue($coordinatorDashboard, 'Guru BK aktif'));
        $this->assertSame('0', $this->contextValue($coordinatorDashboard, 'Kelas tanpa penugasan'));
        $this->assertSame('1', $this->stat($coordinatorDashboard, 'Permasalahan Tindak Lanjut'));
        $this->assertSame('1', $this->contextValue($coordinatorDashboard, 'Permasalahan Tindak Lanjut'));

        $wakaDashboard = $service->forUser($waka, $this->year);
        $this->assertTrue($wakaDashboard['read_only']);
        $this->assertSame('2', $this->stat($wakaDashboard, 'Murid Tercatat'));
        $this->assertSame('2', $this->stat($wakaDashboard, 'Sedang Ditangani'));
        $this->assertStringContainsString($studentA->name, json_encode($wakaDashboard['follow_up_students'], JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString($studentB->name, json_encode($wakaDashboard['follow_up_students'], JSON_THROW_ON_ERROR));

        $admin = $this->userWithRole('admin_it', 'Admin IT');
        $adminDashboard = $service->forUser($admin, $this->year);
        $this->assertSame('admin', $adminDashboard['role_key']);
        $this->assertSame('1', $this->stat($adminDashboard, 'Integrasi Bermasalah'));
        $this->assertSame('Perlu konfigurasi', $this->contextValue($adminDashboard, 'Dapodik'));
        $this->assertSame('Belum ada data', $this->contextValue($adminDashboard, 'e-Tatib'));
        $this->assertStringNotContainsString($studentA->name, json_encode($adminDashboard, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString($caseB->registration_number, json_encode($adminDashboard, JSON_THROW_ON_ERROR));
    }

    public function test_admin_dashboard_uses_localized_sync_status_and_source_health(): void
    {
        $admin = $this->userWithRole('admin_it', 'Admin Dashboard');
        IntegrationSetting::query()->create(['provider' => IntegrationSetting::PROVIDER_ETATIB]);
        $etatibRun = ExternalSyncRun::query()->create([
            'source' => 'etatib',
            'status' => ExternalSyncRun::STATUS_WARNING,
            'started_at' => now(),
            'received_count' => 855,
            'processed_count' => 855,
            'conflict_count' => 1,
            'summary' => 'IDENTITAS-TEKNIS-RAHASIA',
        ]);
        ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $etatibRun->id,
            'entity_type' => 'student',
            'issue_code' => 'identity_conflict',
            'summary' => 'DETAIL-KONFLIK-RAHASIA',
        ]);
        ExternalSyncRun::query()->create([
            'source' => 'api_siswa',
            'status' => ExternalSyncRun::STATUS_SUCCEEDED,
            'started_at' => now()->subMinute(),
            'received_count' => 2529,
            'processed_count' => 2526,
            'conflict_count' => 0,
            'summary' => 'IDENTIFIER-IMPOR-RAHASIA',
        ]);

        $dashboard = app(DashboardService::class)->forUser($admin, $this->year);
        $this->assertSame(['Akun Aktif', 'Akun Nonaktif', 'Konflik Sinkronisasi', 'Integrasi Bermasalah'], array_column($dashboard['stats'], 'label'));
        $this->assertSame('1', $this->stat($dashboard, 'Konflik Sinkronisasi'));
        $this->assertSame('2', $this->stat($dashboard, 'Integrasi Bermasalah'));
        $this->assertSame('warning', collect($dashboard['stats'])->firstWhere('label', 'Konflik Sinkronisasi')['tone']);
        $this->assertSame(route('data-master.index', ['tab' => 'sinkronisasi']), collect($dashboard['stats'])->firstWhere('label', 'Konflik Sinkronisasi')['url']);
        $this->assertSame('Status Integrasi', $dashboard['context_panel']['title']);
        $this->assertSame(['Dapodik', 'e-Tatib', 'API Siswa'], array_column($dashboard['context_panel']['items'], 'label'));
        $this->assertSame('Perhatian', $this->contextValue($dashboard, 'e-Tatib'));
        $this->assertSame('Normal', $this->contextValue($dashboard, 'API Siswa'));
        $this->assertSame('1 konflik perlu ditinjau', collect($dashboard['context_panel']['items'])->firstWhere('label', 'e-Tatib')['meta']);
        $this->assertSame('e-Tatib', $dashboard['tindak_lanjut'][0]['code']);
        $this->assertSame('Peringatan', $dashboard['tindak_lanjut'][0]['status']);
        $this->assertSame('855 diproses, 1 konflik', $dashboard['tindak_lanjut'][0]['context_label']);
        $this->assertSame('Data Siswa', $dashboard['tindak_lanjut'][1]['code']);
        $this->assertSame('Berhasil', $dashboard['tindak_lanjut'][1]['status']);
        $this->assertSame(['Tambah Akun'], array_column($dashboard['quick_actions'], 'label'));
        $this->assertSame(route('admin.users.index', ['action' => 'create']), $dashboard['quick_actions'][0]['url']);
        $this->assertStringNotContainsString('RAHASIA', json_encode($dashboard, JSON_THROW_ON_ERROR));

        $this->actingAs($admin)->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSee('Status Integrasi')
            ->assertSee('Integrasi Bermasalah')
            ->assertSee('Tambah Akun')
            ->assertSee('Peringatan')
            ->assertSee('Berhasil')
            ->assertSee(route('admin.users.index', ['action' => 'create']))
            ->assertDontSee('IDENTITAS-TEKNIS-RAHASIA')
            ->assertDontSee('IDENTIFIER-IMPOR-RAHASIA');
    }

    public function test_admin_dashboard_recognizes_successful_manual_etatib_sync_without_stored_url(): void
    {
        $admin = $this->userWithRole('admin_it', 'Admin Sinkronisasi');
        IntegrationSetting::query()->create(['provider' => IntegrationSetting::PROVIDER_ETATIB]);
        ExternalSyncRun::query()->create([
            'source' => 'etatib',
            'status' => ExternalSyncRun::STATUS_SUCCEEDED,
            'started_at' => now(),
            'processed_count' => 25,
        ]);
        ExternalSyncRun::query()->create([
            'source' => 'dapodik',
            'status' => ExternalSyncRun::STATUS_SUCCEEDED,
            'started_at' => now()->subMinute(),
            'processed_count' => 25,
        ]);

        $dashboard = app(DashboardService::class)->forUser($admin, $this->year);
        $this->assertSame('Normal', $this->contextValue($dashboard, 'e-Tatib'));
        $this->assertSame('Sinkronisasi berhasil', collect($dashboard['context_panel']['items'])->firstWhere('label', 'e-Tatib')['meta']);
        $this->assertSame('Perlu konfigurasi', $this->contextValue($dashboard, 'Dapodik'));
        $this->assertSame('1', $this->stat($dashboard, 'Integrasi Bermasalah'));
    }

    public function test_admin_dashboard_keeps_automatic_etatib_failure_visible_after_manual_success(): void
    {
        $admin = $this->userWithRole('admin_it', 'Admin Otomatis');
        IntegrationSetting::query()->create([
            'provider' => IntegrationSetting::PROVIDER_ETATIB,
            'automatic_sync_enabled' => true,
        ]);
        ExternalSyncRun::query()->create([
            'source' => 'etatib',
            'status' => ExternalSyncRun::STATUS_FAILED,
            'started_at' => now()->subHour(),
        ]);
        ExternalSyncRun::query()->create([
            'source' => 'etatib',
            'status' => ExternalSyncRun::STATUS_SUCCEEDED,
            'triggered_by' => $admin->id,
            'started_at' => now(),
        ]);

        $dashboard = app(DashboardService::class)->forUser($admin, $this->year);
        $etatib = collect($dashboard['context_panel']['items'])->firstWhere('label', 'e-Tatib');
        $this->assertSame('Perhatian', $etatib['value']);
        $this->assertSame('Pembaruan otomatis gagal', $etatib['meta']);
        $this->assertSame('2', $this->stat($dashboard, 'Integrasi Bermasalah'));
        $this->assertSame('Pembaruan otomatis e-Tatib gagal', $dashboard['alerts'][0]['title']);
    }

    public function test_dashboard_route_does_not_leak_another_teachers_student_or_private_case_text(): void
    {
        $teacherA = $this->userWithRole('guru_bk', 'Guru Dashboard');
        $teacherB = $this->userWithRole('guru_bk', 'Guru Lain');
        [$studentA, $caseA] = $this->createScopedCase($teacherA, 'XI DKV 1', '0033333333', 'Murid Aman');
        [$studentB] = $this->createScopedCase($teacherB, 'XI DKV 2', '0044444444', 'Murid Rahasia', 'CATATAN-PRIVAT-TIDAK-BOLEH-BOCOR');
        $caseA->update([
            'status_id' => $this->reference('case_status', ServiceRecordStatus::NEEDS_FOLLOW_UP)->id,
            'follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id,
        ]);

        $this->actingAs($teacherA)->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSee('data-page-id="PG-002"', false)
            ->assertSee($studentA->name)
            ->assertDontSee($studentB->name)
            ->assertDontSee('CATATAN-PRIVAT-TIDAK-BOLEH-BOCOR');
    }

    public function test_dashboard_uses_role_context_instead_of_audit_activity_feed(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Konteks');
        AuditLog::query()->create([
            'actor_id' => $teacher->id,
            'action' => 'secret.event',
            'auditable_type' => User::class,
            'auditable_id' => $teacher->id,
            'summary' => 'NARASI-AUDIT-RAHASIA',
        ]);

        $this->actingAs($teacher)->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSee('Kelas Binaan')
            ->assertSee('Belum ada kelas binaan pada tingkat ini.')
            ->assertDontSee('Aktivitas terbaru')
            ->assertDontSee('NARASI-AUDIT-RAHASIA');
    }

    public function test_dashboard_empty_state_describes_cases_without_implying_a_schedule(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Tanpa Kasus');

        $this->actingAs($teacher)->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSee('Belum ada aktivitas terbaru dari kelas binaan Anda.')
            ->assertDontSee('Tidak ada jadwal tindak lanjut dalam waktu dekat.');
    }

    public function test_dashboard_excludes_official_departure_from_active_student_count(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Cakupan Keluar');
        [$student] = $this->createScopedCase($teacher, 'XII RPL 1', '0055555555', 'Murid Resmi Keluar');
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Keluar');
        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'status' => StudentDeparture::STATUS_OFFICIAL,
            'reported_at' => '2026-08-19',
            'effective_date' => '2026-08-21',
            'recorded_by' => $teacher->id,
            'finalized_by' => $coordinator->id,
            'finalized_at' => now(),
        ]);

        $dashboard = app(DashboardService::class)->forUser($teacher, $this->year);

        $this->assertSame('0', $this->stat($dashboard, 'Murid Binaan'));
        $this->assertSame('1', $this->stat($dashboard, 'Belum Selesai'));
    }

    public function test_teacher_quick_actions_provide_an_allowed_icon_and_tone(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Ikon');
        $actions = app(DashboardService::class)->forUser($teacher, $this->year)['quick_actions'];

        $this->assertSame(['case', 'consultation'], array_column($actions, 'icon'));
        $this->assertSame(['primary', 'primary'], array_column($actions, 'tone'));
    }

    public function test_coordinator_quick_actions_exclude_case_and_consultation_creation(): void
    {
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Quick Action');
        $actions = app(DashboardService::class)->forUser($coordinator, $this->year)['quick_actions'];
        $labels = array_column($actions, 'label');

        $this->assertSame(['Laporan'], $labels);
        $this->assertNotContains('Catat Permasalahan', $labels);
        $this->assertNotContains('Catat Konsultasi', $labels);
    }

    public function test_coordinator_dashboard_does_not_render_case_and_consultation_quick_menu(): void
    {
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Tampilan');

        $this->actingAs($coordinator)->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSee('Laporan')
            ->assertDontSee('Catat Permasalahan')
            ->assertDontSee('Catat Konsultasi');
    }

    public function test_teacher_dashboard_displays_only_assigned_classes_in_context_panel(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Ampuan');
        $otherTeacher = $this->userWithRole('guru_bk', 'Guru Lain');

        $classA = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'X RPL 1',
            'grade_level' => 10,
            'major' => 'Rekayasa Perangkat Lunak',
            'is_active' => true,
        ]);
        $classB = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'Kelas XI - RPL 2',
            'major' => 'Rekayasa Perangkat Lunak',
            'is_active' => true,
        ]);
        $classOther = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'XII TKJ 1',
            'grade_level' => 12,
            'major' => 'Teknik Komputer dan Jaringan',
            'is_active' => true,
        ]);

        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classA->id,
            'academic_year_id' => $this->year->id,
            'assigned_by' => $teacher->id,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classB->id,
            'academic_year_id' => $this->year->id,
            'assigned_by' => $teacher->id,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $otherTeacher->id,
            'classroom_id' => $classOther->id,
            'academic_year_id' => $this->year->id,
            'assigned_by' => $otherTeacher->id,
        ]);

        $student1 = Student::query()->create(['nisn' => '0011223344', 'name' => 'Murid Satu', 'is_active' => true]);
        $student2 = Student::query()->create(['nisn' => '0011223355', 'name' => 'Murid Dua', 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $student1->id,
            'classroom_id' => $classA->id,
            'academic_year_id' => $this->year->id,
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student2->id,
            'classroom_id' => $classA->id,
            'academic_year_id' => $this->year->id,
            'is_active' => true,
        ]);

        $this->actingAs($teacher)->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSee('Kelas Binaan')
            ->assertSee('X RPL 1')
            ->assertSee('2 murid')
            ->assertSee('Kelas XI - RPL 2')
            ->assertSee('0 murid')
            ->assertDontSee('XII TKJ 1')
            ->assertDontSee('Cakupan layanan Anda')
            ->assertDontSee('Permasalahan khusus aktif');

        $groups = app(DashboardService::class)->forUser($teacher, $this->year)['context_panel']['groups'];
        $this->assertSame([10, 11, 12], array_keys($groups));
        $this->assertSame(['class_count' => 1, 'student_count' => 2], array_intersect_key($groups[10], array_flip(['class_count', 'student_count'])));
        $this->assertSame('X RPL 1', $groups[10]['items'][0]['label']);
        $this->assertSame('Kelas XI - RPL 2', $groups[11]['items'][0]['label']);
        $this->assertSame([], $groups[12]['items']);
    }

    public function test_teacher_dashboard_activity_cards_are_display_only_and_capped_at_five(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Aktivitas');
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'X RPL 1',
            'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'assigned_by' => $teacher->id,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $student = Student::query()->create([
                'nisn' => sprintf('00112233%02d', $i),
                'name' => 'Murid Kasus '.$i,
                'is_active' => true,
            ]);
            StudentClassMembership::query()->create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => $this->year->id,
                'is_active' => true,
            ]);
            $case = BkCase::query()->create([
                'student_id' => $student->id,
                'academic_year_id' => $this->year->id,
                'classroom_id' => $classroom->id,
                'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
                'service_field_id' => $this->reference('service_field', 'pribadi')->id,
                'status_id' => $this->reference('case_status', ServiceRecordStatus::IN_PROGRESS)->id,
                'service_date' => '2026-08-20',
                'initial_info' => 'Info '.$i,
                'initial_action' => 'Aksi '.$i,
                'created_by' => $teacher->id,
                'created_at' => now()->addMinutes($i),
            ]);
            if ($i === 3) {
                $case->update(['follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id]);
            }
            CaseAssignment::query()->create([
                'case_id' => $case->id,
                'user_id' => $teacher->id,
                'reason' => 'Pemilik',
                'assigned_by' => $teacher->id,
            ]);
        }

        for ($j = 1; $j <= 3; $j++) {
            $student = Student::query()->create([
                'nisn' => sprintf('00112244%02d', $j),
                'name' => 'Murid Konsul '.$j,
                'is_active' => true,
            ]);
            StudentClassMembership::query()->create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => $this->year->id,
                'is_active' => true,
            ]);
            Consultation::query()->create([
                'student_id' => $student->id,
                'academic_year_id' => $this->year->id,
                'classroom_id' => $classroom->id,
                'service_field_id' => $this->reference('service_field', 'belajar')->id,
                'counselor_id' => $teacher->id,
                'session_date' => '2026-08-21',
                'problem' => 'Konsul problem '.$j,
                'handling' => 'Konsul action '.$j,
                'result' => 'Konsul result '.$j,
                'created_at' => now()->addMinutes(10 + $j),
            ]);
        }

        $dashboard = app(DashboardService::class)->forUser($teacher, $this->year);
        $this->assertCount(5, $dashboard['tindak_lanjut']);
        $this->assertNull($dashboard['schedule_url']);
        $caseActivity = collect($dashboard['tindak_lanjut'])->firstWhere('code', 'Layanan permasalahan');
        $consultationActivity = collect($dashboard['tindak_lanjut'])->firstWhere('code', 'Layanan konsultasi');
        $this->assertSame('Permasalahan Pribadi', $caseActivity['title']);
        $this->assertSame('Home Visit', $caseActivity['follow_up']);
        $this->assertSame('Murid Kasus 3 · X RPL 1', $caseActivity['context_label']);
        $this->assertSame('Konsultasi Belajar', $consultationActivity['title']);
        $this->assertNull($consultationActivity['follow_up']);
        foreach ($dashboard['tindak_lanjut'] as $activity) {
            $this->assertArrayNotHasKey('url', $activity);
            $this->assertNotEmpty($activity['date']);
            $this->assertNotEmpty($activity['month']);
            $this->assertNotEmpty($activity['year']);
            $this->assertNotEmpty($activity['code']);
            $this->assertNotEmpty($activity['title']);
            $this->assertNotEmpty($activity['context_label']);
            $this->assertNotEmpty($activity['status']);
            $this->assertArrayNotHasKey('url', $activity);
        }

        $response = $this->actingAs($teacher)->get(route('dashboard.preview'));
        $response->assertOk();
        $response->assertSee('Aktivitas Terbaru');
        $response->assertDontSee('Lihat semua');
        $response->assertSee('li class="sibk-teacher-activities__row"', false);
        $response->assertDontSee('sibk-list-item__chevron', false);
        $response->assertDontSee(route('cases.show', BkCase::firstOrFail()));
        $response->assertDontSee(route('consultations.show', Consultation::firstOrFail()));
    }

    /** @return array{Student, BkCase} */
    private function createScopedCase(User $teacher, string $className, string $nisn, string $studentName, ?string $internalNote = null): array
    {
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => $className,
            'is_active' => true,
        ]);
        $student = Student::query()->create(['nisn' => $nisn, 'name' => $studentName, 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'assigned_by' => $teacher->id,
        ]);
        $case = BkCase::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'classroom_id' => $classroom->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::IN_PROGRESS)->id,
            'service_date' => '2026-08-20',
            'initial_info' => 'Informasi awal yang aman.',
            'initial_action' => 'Asesmen awal.',
            'internal_note' => $internalNote,
            'created_by' => $teacher->id,
        ]);
        $case->update(['registration_number' => sprintf('K-2026-%04d', $case->id)]);
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $teacher->id,
            'reason' => 'Fixture cakupan dashboard.',
            'assigned_by' => $teacher->id,
        ]);

        return [$student, $case];
    }

    /** @param array<string, mixed> $dashboard */
    private function stat(array $dashboard, string $label): string
    {
        return collect($dashboard['stats'] ?? $dashboard['metrics'])->firstWhere('label', $label)['value'];
    }

    /** @param array<string, mixed> $dashboard */
    private function contextValue(array $dashboard, string $label): string
    {
        return collect($dashboard['context_panel']['items'])->firstWhere('label', $label)['value'];
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
