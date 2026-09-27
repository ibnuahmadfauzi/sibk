<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FrontendPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_root_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_pg_001_renders_real_login_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('data-page-id="PG-001"', false)
            ->assertSee('name="email"', false)
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('id="identifierError"', false)
            ->assertSee('id="passwordError"', false)
            ->assertSee('action="'.route('login.store').'"', false);
    }

    #[DataProvider('dashboardRoleProvider')]
    public function test_pg_002_renders_database_dashboard_for_authenticated_role(string $role, string $expected): void
    {
        $this->authenticateAs($role);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('data-page-id="PG-002"', false)
            ->assertSee($expected)
            ->assertDontSee('Ajukan Koreksi')
            ->assertDontSee('Aktivitas terbaru');
    }

    public function test_pg_002_waka_dashboard_is_explicitly_read_only(): void
    {
        $this->authenticateAs('waka_kesiswaan');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Hanya untuk dilihat')
            ->assertSee('Penanganan Terbaru')
            ->assertDontSee('Cari profil murid');
    }

    public function test_waka_only_sidebar_contains_monitoring_navigation(): void
    {
        $this->authenticateAs('waka_kesiswaan');

        $this->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSeeInOrder([
                'Dashboard',
                'PEMANTAUAN WAKA',
                'Murid dengan Permasalahan',
                'Laporan',
                'UTILITAS',
            ])
            ->assertSee('href="'.route('waka.monitoring.students').'"', false)
            ->assertSee('href="'.route('reports.index').'"', false)
            ->assertDontSee('Layanan BK')
            ->assertDontSee('Data Murid')
            ->assertDontSee('Penugasan Kelas')
            ->assertDontSee('Pengalihan Kasus')
            ->assertDontSee('Laporan Penanganan');
    }

    public function test_waka_coordinator_sidebar_keeps_operational_and_monitoring_navigation(): void
    {
        $user = $this->authenticateAs('koordinator_bk');
        $user->roles()->attach(Role::query()->where('slug', 'waka_kesiswaan')->firstOrFail());

        $this->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSee('Layanan BK')
            ->assertSee('Penugasan Kelas')
            ->assertSee('PEMANTAUAN WAKA')
            ->assertSee('Murid dengan Permasalahan')
            ->assertSee('href="'.route('reports.index').'"', false);
    }

    public function test_waka_reports_use_the_shared_read_only_page(): void
    {
        $this->authenticateAs('waka_kesiswaan');

        $this->get(route('waka.reports', ['tab' => 'laporan-akhir']))
            ->assertRedirect(route('reports.index'));

        $this->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Catatan Layanan')
            ->assertSee('sibk-operational-report-table', false)
            ->assertDontSee('Cetak / Unduh Rekap')
            ->assertDontSee('onclick=', false);
    }

    public function test_operational_reports_use_accessible_responsive_markup(): void
    {
        $this->authenticateAs('guru_bk');

        $this->get(route('reports.index', ['tab' => 'layanan']))
            ->assertOk()
            ->assertSee('sibk-operational-report-table', false)
            ->assertSee('sibk-operational-report-cards', false)
            ->assertSee('Cetak / Unduh Rekap')
            ->assertDontSee('onclick=', false);
    }

    public function test_academic_year_preparation_pages_follow_the_existing_panel_hierarchy(): void
    {
        $year = AcademicYear::query()->create([
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'is_active' => false,
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $admin = $this->authenticateAs('admin_it');
        $this->get(route('data-master.index'))
            ->assertOk()
            ->assertSee('Persiapan Tahun Ajaran')
            ->assertSee('Buat tahun ajaran lain')
            ->assertSee('Koordinator BK mengaktifkannya')
            ->assertDontSee('Periksa hasil impor')
            ->assertSee('name="name"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSeeInOrder([
                'Persiapan Tahun Ajaran',
                'Buat tahun ajaran lain',
                'Impor Murid dari API Siswa',
            ])
            ->assertSee('Impor CSV jika API belum tersedia')
            ->assertDontSee('Langkah 1 dari 3');

        $this->get(route('data-master.index', ['tab' => 'dapodik']))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('nisn,nama,rombel,tahun_pelajaran');

        $admin->roles()->detach();
        $admin->roles()->attach(Role::query()->where('slug', 'koordinator_bk')->firstOrFail());

        $this->get(route('assignments.classes.manage', ['academic_year_id' => $year->id]))
            ->assertRedirect(route('assignments.classes.index', ['academic_year_id' => $year->id]));
        $this->get(route('assignments.classes.index', ['academic_year_id' => $year->id]))
            ->assertOk()
            ->assertSee('Kesiapan Aktivasi')
            ->assertSee('Persiapan')
            ->assertSee('disabled>Aktifkan Tahun Ajaran', false);
    }

    public function test_sync_tab_shows_every_open_dashboard_issue_and_routes_identity_conflicts(): void
    {
        $admin = $this->authenticateAs('admin_it');
        $run = ExternalSyncRun::query()->create([
            'source' => 'etatib', 'status' => ExternalSyncRun::STATUS_WARNING,
            'started_at' => now(), 'finished_at' => now(),
        ]);
        ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->id, 'entity_type' => 'etatib_record',
            'source_identifier' => 'etatib-1', 'nisn' => '0012345678',
            'input_name' => 'Murid A', 'issue_code' => 'student_not_found',
            'summary' => 'Identitas belum cocok.',
        ]);
        ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->id, 'entity_type' => 'etatib_record',
            'source_identifier' => 'etatib-2', 'nisn' => '0012345679',
            'issue_code' => 'student_classroom_mismatch',
            'summary' => 'Kelas sumber berbeda.',
        ]);
        $dapodikRun = ExternalSyncRun::query()->create([
            'source' => 'dapodik', 'status' => ExternalSyncRun::STATUS_WARNING,
            'started_at' => now(), 'finished_at' => now(),
        ]);
        $localStudent = Student::query()->create([
            'nisn' => '0098765432', 'name' => 'Murid Lokal', 'is_active' => true,
        ]);
        ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $dapodikRun->id, 'entity_type' => 'student',
            'source_identifier' => 'local:'.$localStudent->id, 'issue_code' => 'unmatched_local_record',
            'summary' => 'Data sekolah belum cocok dengan Dapodik.',
        ]);
        $localYear = AcademicYear::query()->create(['name' => '2025/2026', 'is_active' => false]);
        ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $dapodikRun->id, 'entity_type' => 'academic_year',
            'source_identifier' => 'local:'.$localYear->id, 'issue_code' => 'unmatched_local_record',
            'summary' => 'Tahun sekolah belum cocok dengan Dapodik.',
        ]);
        ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->id, 'entity_type' => 'etatib_record',
            'source_identifier' => 'etatib-3', 'issue_code' => 'student_not_found',
            'summary' => 'Sudah selesai.', 'resolved_at' => now(),
        ]);

        $url = route('data-master.index', ['tab' => 'sinkronisasi']);
        $this->get('/dashboard')->assertOk()->assertSee($url);
        $this->get($url)->assertOk()
            ->assertSee('4 masalah dari Dapodik dan e-Tatib')
            ->assertSee('Identitas belum cocok.')
            ->assertSee('Kelas sumber berbeda.')
            ->assertSee('Data sekolah belum cocok dengan Dapodik.')
            ->assertSee('Murid Lokal (NISN 0098765432)')
            ->assertSee('Tahun ajaran 2025/2026')
            ->assertSee('Riwayat Sinkronisasi')
            ->assertSee(route('data-master.etatib.conflicts.index'))
            ->assertDontSee('Sudah selesai.');

        $admin->roles()->detach();
        $admin->roles()->attach(Role::query()->where('slug', 'guru_bk')->firstOrFail());
        $this->get($url)->assertForbidden();
    }

    public function test_data_master_shows_preparation_and_import_in_one_dapodik_tab(): void
    {
        $this->authenticateAs('admin_it');

        $this->get(route('data-master.index'))
            ->assertOk()
            ->assertSee('aria-label="Bagian Data Master"', false)
            ->assertSee('href="'.route('data-master.index', ['tab' => 'dapodik']).'"', false)
            ->assertSee('href="'.route('data-master.index', ['tab' => 'etatib']).'"', false)
            ->assertDontSee('Tahun Ajaran &amp; Murid', false)
            ->assertSee('e-Tatib')
            ->assertSee('Persiapan Tahun Ajaran')
            ->assertSee('Buat Tahun Ajaran')
            ->assertDontSee('name="preparation_reference"', false)
            ->assertSee('name="api_url"', false)
            ->assertDontSee('Periksa hasil impor')
            ->assertDontSee('Kelola Konflik e-Tatib')
            ->assertDontSee('Yang Perlu Ditinjau')
            ->assertDontSee('Belum ada data yang perlu ditinjau.')
            ->assertDontSee('Status dan riwayat sinkronisasi')
            ->assertDontSee('Belum ada riwayat sinkronisasi')
            ->assertDontSee('data-etatib-api-form', false);

        $this->get(route('data-master.index', ['tab' => 'dapodik']))
            ->assertOk()
            ->assertSee('Persiapan Tahun Ajaran')
            ->assertSee('col-12 col-xl-5 sibk-data-master-year', false)
            ->assertSee('col-12 col-xl-7 sibk-data-master-api', false)
            ->assertSee('Impor Murid dari API Siswa')
            ->assertSee('name="api_url"', false)
            ->assertSee('Tinjau Data')
            ->assertSee('data-api-siswa-preview-modal', false)
            ->assertSee('Pratinjau API Siswa')
            ->assertSee('Impor CSV')
            ->assertSeeInOrder(['Persiapan Tahun Ajaran', 'Impor Murid dari API Siswa', 'Impor CSV jika API belum tersedia'])
            ->assertDontSee('data-integration-panel="dapodik"', false)
            ->assertDontSee('data-etatib-api-form', false);

        $this->get(route('data-master.index', ['tab' => 'etatib']))
            ->assertOk()
            ->assertSee('Sinkronkan Data e-Tatib')
            ->assertDontSee('Yang Perlu Ditinjau')
            ->assertDontSee('Status dan riwayat sinkronisasi')
            ->assertSee('data-etatib-api-form', false)
            ->assertSee('data-etatib-preview-modal', false)
            ->assertDontSee('data-integration-panel="dapodik"', false)
            ->assertSeeInOrder([
                'Tautan API e-Tatib',
                'Tinjau Data',
                'Pratinjau e-Tatib',
                'Sinkronkan Data',
            ], false)
            ->assertDontSee('Kode sumber')
            ->assertDontSee('SIBK_ETATIB')
            ->assertDontSee('data-bs-toggle="collapse"', false);

        $this->withSession(['success' => 'Tahun ajaran dibuat.', 'year_prepared' => true])
            ->get(route('data-master.index'))
            ->assertSee('href="#api-siswa-import-title"', false)
            ->assertSee('Lanjut impor murid');

    }

    public function test_small_danger_badge_uses_a_contrast_safe_token(): void
    {
        $tokenSource = file_get_contents(resource_path('scss/_token-values.scss'));
        $dashboardSource = file_get_contents(resource_path('scss/app-dashboard.scss'));

        $this->assertIsString($tokenSource);
        $this->assertIsString($dashboardSource);
        $this->assertMatchesRegularExpression('/\$sibk-danger-strong:\s*(#[0-9A-Fa-f]{6});/', $tokenSource);
        $this->assertMatchesRegularExpression('/\$sibk-danger-soft:\s*(#[0-9A-Fa-f]{6});/', $tokenSource);
        preg_match('/\$sibk-danger-strong:\s*(#[0-9A-Fa-f]{6});/', $tokenSource, $foreground);
        preg_match('/\$sibk-danger-soft:\s*(#[0-9A-Fa-f]{6});/', $tokenSource, $background);

        $this->assertGreaterThanOrEqual(4.5, $this->contrastRatio($foreground[1], $background[1]));
        $this->assertStringContainsString('color: var(--sibk-color-danger-strong);', $dashboardSource);
    }

    private function authenticateAs(string $roleSlug): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user->roles()->attach($role);
        $this->actingAs($user);

        return $user;
    }

    private function contrastRatio(string $foreground, string $background): float
    {
        $foregroundLuminance = $this->relativeLuminance($foreground);
        $backgroundLuminance = $this->relativeLuminance($background);

        return (max($foregroundLuminance, $backgroundLuminance) + 0.05)
            / (min($foregroundLuminance, $backgroundLuminance) + 0.05);
    }

    private function relativeLuminance(string $hex): float
    {
        $channels = array_map(
            static function (string $channel): float {
                $value = hexdec($channel) / 255;

                return $value <= 0.04045
                    ? $value / 12.92
                    : (($value + 0.055) / 1.055) ** 2.4;
            },
            str_split(substr($hex, 1), 2),
        );

        return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    }

    /** @return array<string, array{string, string}> */
    public static function dashboardRoleProvider(): array
    {
        return [
            'guru' => ['guru_bk', 'Murid dalam cakupan'],
            'coordinator' => ['koordinator_bk', 'Guru BK aktif'],
            'waka' => ['waka_kesiswaan', 'Dashboard Waka Kesiswaan'],
        ];
    }
}
