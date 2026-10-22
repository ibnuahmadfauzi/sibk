<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Integrations\Dapodik\DapodikSnapshot;
use App\Integrations\Etatib\EtatibSnapshot;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Integrations\IntegrationSnapshotEvidence;
use App\Models\AcademicYear;
use App\Models\DapodikSyncPreviewItem;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicYearPreparationService;
use App\Services\AlumniRetentionRegistry;
use App\Services\ApiSiswaRosterImportService;
use App\Services\DapodikApplyValidator;
use App\Services\DapodikPreviewBuilder;
use App\Services\EtatibSyncService;
use App\Services\SimpleEtatibApiService;
use App\Services\StudentIdentityService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AlumniRetentionImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
        config(['alumni_retention.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    }

    public function test_etatib_sync_skips_erased_alumni_but_keeps_new_unmatched_students(): void
    {
        $this->markErased('0012345678');
        IntegrationSetting::query()->create(['provider' => 'etatib']);

        $run = app(EtatibSyncService::class)->synchronizeSnapshot(new EtatibSnapshot(false, [
            ['source_id' => 'tatib-alumni', 'nisn' => '0012345678', 'occurred_at' => '2024-05-01 09:00:00', 'violation_type' => 'Terlambat', 'category' => 'ringan', 'points' => 10],
            ['source_id' => 'tatib-baru', 'nisn' => '0098765432', 'occurred_at' => '2026-10-01 09:00:00', 'violation_type' => 'Terlambat', 'category' => 'ringan', 'points' => 10],
        ]));

        $this->assertSame(2, $run->received_count);
        $this->assertSame(1, $run->processed_count);
        $this->assertDatabaseMissing('external_tatib_records', ['source_identifier' => 'tatib-alumni']);
        $this->assertDatabaseMissing('external_sync_issues', ['source_identifier' => 'tatib-alumni']);
        $this->assertDatabaseHas('external_tatib_records', ['source_identifier' => 'tatib-baru', 'nisn' => '0098765432']);
    }

    public function test_dapodik_preview_excludes_erased_student_and_its_membership(): void
    {
        $this->markErased('0012345678', 'alumni-source');
        IntegrationSetting::query()->create(['provider' => 'dapodik']);
        $snapshot = new DapodikSnapshot(
            isFullSnapshot: false,
            academicYears: [['source_id' => 'year-2026', 'name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30']],
            classrooms: [['source_id' => 'class-1', 'academic_year_source_id' => 'year-2026', 'name' => 'X RPL 1']],
            students: [
                ['source_id' => 'alumni-source', 'nisn' => '0012345678', 'name' => 'Alumni'],
                ['source_id' => 'new-source', 'nisn' => '0098765432', 'name' => 'Murid Baru'],
            ],
            memberships: [
                ['source_id' => 'alumni-membership', 'student_source_id' => 'alumni-source', 'classroom_source_id' => 'class-1', 'academic_year_source_id' => 'year-2026'],
                ['source_id' => 'new-membership', 'student_source_id' => 'new-source', 'classroom_source_id' => 'class-1', 'academic_year_source_id' => 'year-2026'],
            ],
            evidence: new IntegrationSnapshotEvidence('school-01', 'contract-v1', 'partial', 1, 6, 512),
        );
        $run = ExternalSyncRun::query()->create(['source' => 'dapodik', 'status' => ExternalSyncRun::STATUS_RUNNING, 'started_at' => now()]);

        app(IntegrationOperationLock::class)->run('dapodik', function (IntegrationOperationContext $context) use ($snapshot, $run): void {
            DB::transaction(function () use ($snapshot, $run, $context): void {
                $setting = IntegrationSetting::query()->where('provider', 'dapodik')->lockForUpdate()->firstOrFail();
                app(DapodikPreviewBuilder::class)->create($snapshot, $run, $setting, $context);
            });
        });

        $this->assertSame(6, $run->refresh()->received_count);
        $this->assertSame(2, $run->snapshot_evidence['retention_excluded_count']);
        $this->assertSame(1, $run->previewItems()->where('entity_type', DapodikSyncPreviewItem::ENTITY_STUDENT)->count());
        $this->assertSame(1, $run->previewItems()->where('entity_type', DapodikSyncPreviewItem::ENTITY_MEMBERSHIP)->count());
        $this->assertDatabaseMissing('dapodik_sync_preview_items', ['source_identifier' => 'alumni-source']);
        $this->assertDatabaseMissing('dapodik_sync_preview_items', ['source_identifier' => 'alumni-membership']);
        $this->assertDatabaseHas('dapodik_sync_preview_items', ['source_identifier' => 'new-source']);
        app(DapodikApplyValidator::class)->validate($run->refresh(), $run->previewItems()->orderBy('id')->get());
    }

    public function test_roster_and_temporary_identity_reject_erased_alumni_atomically(): void
    {
        $this->markErased('0012345678');
        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');
        AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);

        try {
            app(AcademicYearPreparationService::class)->importRosterPayload([
                'success' => true,
                'data' => [
                    ['nisn' => '0098765432', 'nama' => 'Murid Baru', 'rombel' => 'X RPL 1', 'tahun_pelajaran' => '2026/2027'],
                    ['nisn' => '0012345678', 'nama' => 'Alumni', 'rombel' => 'X RPL 1', 'tahun_pelajaran' => '2026/2027'],
                ],
            ], $admin);
            $this->fail('NISN alumni harus ditolak.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('students', 0);
        }

        $this->expectException(ValidationException::class);
        app(StudentIdentityService::class)->createTemporary('0012345678', 'Alumni', $teacher);
    }

    public function test_api_roster_preview_and_import_hide_erased_alumni_but_accept_new_student(): void
    {
        $this->markErased('0012345678');
        $admin = $this->userWithRole('admin_it');
        AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $rows = [
            ['nisn' => '0012345678', 'name' => 'Alumni', 'classroom' => 'X RPL 1', 'academic_year_name' => '2026/2027'],
            ['nisn' => '0098765432', 'name' => 'Murid Baru', 'classroom' => 'X RPL 1', 'academic_year_name' => '2026/2027'],
        ];
        $service = app(ApiSiswaRosterImportService::class);
        $preview = $service->previewRows($rows, $admin);
        $this->assertSame(1, $preview['rows']);
        $this->assertSame(1, $preview['students']);
        $this->assertTrue($preview['can_import']);
        $this->assertSame(hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR)), $preview['preview_hash']);

        Http::fake(['https://8.8.8.8/roster' => Http::response([
            'success' => true,
            'data' => [
                ['nisn' => '0012345678', 'nama' => 'Alumni', 'rombel' => 'X RPL 1', 'tahun_pelajaran' => '2026/2027'],
                ['nisn' => '0098765432', 'nama' => 'Murid Baru', 'rombel' => 'X RPL 1', 'tahun_pelajaran' => '2026/2027'],
            ],
        ])]);
        $result = $service->import('https://8.8.8.8/roster', $admin, [], $preview['preview_hash']);

        $this->assertSame(1, $result->rows);
        $this->assertDatabaseMissing('students', ['nisn' => '0012345678']);
        $this->assertDatabaseHas('students', ['nisn' => '0098765432']);
    }

    public function test_api_roster_keeps_original_choice_indices_when_alumni_rows_are_hidden(): void
    {
        $this->markErased('0012345678');
        $admin = $this->userWithRole('admin_it');
        AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $rows = [
            ['nisn' => '0012345678', 'name' => 'Alumni', 'classroom' => 'X RPL 1', 'academic_year_name' => '2026/2027'],
            ['nisn' => '0098765432', 'name' => 'Nama Pilihan Pertama', 'classroom' => 'X RPL 1', 'academic_year_name' => '2026/2027'],
            ['nisn' => '0098765432', 'name' => 'Nama Pilihan Kedua', 'classroom' => 'X RPL 1', 'academic_year_name' => '2026/2027'],
        ];
        $service = app(ApiSiswaRosterImportService::class);
        $preview = $service->previewRows($rows, $admin);
        $this->assertSame([1, 2], array_column($preview['entries'], 'row_index'));

        Http::fake(['https://8.8.8.8/roster' => Http::response([
            'success' => true,
            'data' => array_map(fn (array $row): array => [
                'nisn' => $row['nisn'],
                'nama' => $row['name'],
                'rombel' => $row['classroom'],
                'tahun_pelajaran' => $row['academic_year_name'],
            ], $rows),
        ])]);
        $service->import('https://8.8.8.8/roster', $admin, [2], $preview['preview_hash']);

        $this->assertDatabaseHas('students', ['nisn' => '0098765432', 'name' => 'Nama Pilihan Kedua']);
        $this->assertDatabaseMissing('students', ['nisn' => '0012345678']);
    }

    public function test_simple_etatib_preview_and_sync_exclude_erased_alumni_duplicate_groups(): void
    {
        $this->markErased('0012345678');
        Student::query()->create(['nisn' => '0098765432', 'name' => 'Murid Baru']);
        $this->app->instance(SimpleEtatibApiService::class, new SimpleEtatibApiService(
            app(EtatibSyncService::class),
            app(AlumniRetentionRegistry::class),
            static fn (string $host): array => ['8.8.8.8'],
        ));
        $alumni = [
            'siswa_nisn' => '0012345678', 'siswa_nama' => 'Alumni', 'siswa_kelas' => 'X RPL 1',
            'pelanggaran' => 'Terlambat', 'poin_pelanggaran' => 10, 'pencatat' => 'Guru',
            'kategori' => 'ringan', 'tanggal_pelanggaran' => '20 Jul 2024 10:17:00', 'total_poin' => '10',
        ];
        $new = array_replace($alumni, [
            'siswa_nisn' => '0098765432', 'siswa_nama' => 'Murid Baru',
            'tanggal_pelanggaran' => '20 Jul 2026 10:17:00',
        ]);
        $url = 'https://etatib.example.test/pelanggaran';
        Http::fake([$url => fn () => Http::response([$alumni, $alumni, $new])]);

        $preview = $this->actingAs($this->userWithRole('admin_it'))
            ->postJson(route('data-master.etatib.preview'), ['api_url' => $url])
            ->assertOk()
            ->assertJsonPath('data.received', 3)
            ->assertJsonPath('data.rows', 1)
            ->assertJsonPath('data.duplicate_groups', []);
        $this->assertStringNotContainsString('0012345678', $preview->getContent());
        $this->post(route('data-master.etatib.sync'), ['api_url' => $url])->assertRedirect();

        $run = ExternalSyncRun::query()->where('source', 'etatib')->latest('id')->firstOrFail();
        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $run->status, $run->summary ?? '');
        $this->assertDatabaseCount('etatib_duplicate_decisions', 0);
        $this->assertDatabaseMissing('external_tatib_records', ['nisn' => '0012345678']);
        $this->assertDatabaseHas('external_tatib_records', ['nisn' => '0098765432']);
    }

    public function test_simple_etatib_rechecks_marker_when_it_appears_after_preview(): void
    {
        Student::query()->create(['nisn' => '0098765432', 'name' => 'Murid Baru']);
        $this->app->instance(SimpleEtatibApiService::class, new SimpleEtatibApiService(
            app(EtatibSyncService::class),
            app(AlumniRetentionRegistry::class),
            static fn (string $host): array => ['8.8.8.8'],
        ));
        $alumni = [
            'siswa_nisn' => '0012345678', 'siswa_nama' => 'Alumni', 'siswa_kelas' => 'X RPL 1',
            'pelanggaran' => 'Terlambat', 'poin_pelanggaran' => 10, 'pencatat' => 'Guru',
            'kategori' => 'ringan', 'tanggal_pelanggaran' => '20 Jul 2024 10:17:00', 'total_poin' => '10',
        ];
        $new = array_replace($alumni, [
            'siswa_nisn' => '0098765432', 'siswa_nama' => 'Murid Baru',
            'tanggal_pelanggaran' => '20 Jul 2026 10:17:00',
        ]);
        $url = 'https://etatib.example.test/pelanggaran';
        Http::fake([$url => fn () => Http::response([$alumni, $new])]);

        $this->actingAs($this->userWithRole('admin_it'))
            ->postJson(route('data-master.etatib.preview'), ['api_url' => $url])
            ->assertOk()
            ->assertJsonPath('data.rows', 2);
        $this->markErased('0012345678');
        $this->post(route('data-master.etatib.sync'), ['api_url' => $url])->assertRedirect();

        $this->assertDatabaseHas('external_tatib_records', ['nisn' => '0098765432']);
        $this->assertDatabaseMissing('external_tatib_records', ['nisn' => '0012345678']);
    }

    private function markErased(string $nisn, ?string $dapodikId = null): void
    {
        $student = Student::query()->create(['nisn' => $nisn, 'name' => 'Alumni', 'dapodik_id' => $dapodikId]);
        DB::transaction(fn () => app(AlumniRetentionRegistry::class)->record($student));
        $student->delete();
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
