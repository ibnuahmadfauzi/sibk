<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ExternalSyncRun;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\StudentDeparture;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AcademicYearPreparationService;
use App\Services\AchievementService;
use App\Services\ApiSiswaRosterImportService;
use App\Services\CaseService;
use App\Services\ConsultationService;
use App\Services\ProvisionalRosterCsvParser;
use App\Services\ProvisionalRosterPayloadParser;
use App\Services\StudentIdentityService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DelayedDapodikPreparationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    #[Test]
    public function admin_prepares_an_inactive_provisional_academic_year_from_an_official_reference(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $this->assertTrue(method_exists($service, 'prepareAcademicYear'));
        $admin = $this->userWithRole('admin_it');

        $year = $service->prepareAcademicYear([
            'name' => '2027/2028',
            'preparation_reference' => 'Kalender Pendidikan 2027/2028',
        ], $admin);

        $this->assertFalse($year->is_active);
        $this->assertSame('2027-07-01', $year->starts_on->toDateString());
        $this->assertSame('2028-06-30', $year->ends_on->toDateString());
        $this->assertSame(AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL, $year->master_source);
        $this->assertNull($year->source_confirmed_at);
        $this->assertSame($admin->id, $year->prepared_by);
        $this->assertSame('Kalender Pendidikan 2027/2028', $year->preparation_reference);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic_year.prepared',
            'auditable_id' => $year->id,
            'actor_id' => $admin->id,
        ]);
    }

    #[Test]
    public function preparation_requires_an_active_admin_unique_name_and_official_reference(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $inactiveAdmin = $this->userWithRole('admin_it', false);
        $coordinator = $this->userWithRole('koordinator_bk');
        $payload = [
            'name' => '2027/2028',
            'preparation_reference' => 'SK Kepala Sekolah 001/2027',
        ];

        foreach ([$inactiveAdmin, $coordinator] as $unauthorized) {
            try {
                $service->prepareAcademicYear($payload, $unauthorized);
                $this->fail('Aktor tanpa hak persiapan diterima.');
            } catch (AuthorizationException) {
                $this->assertDatabaseCount('academic_years', 0);
            }
        }

        $service->prepareAcademicYear($payload, $admin);

        $this->assertValidationError(
            fn () => $service->prepareAcademicYear($payload, $admin),
            'name',
        );
        $this->assertValidationError(
            fn () => $service->prepareAcademicYear([...$payload, 'name' => '2028/2030'], $admin),
            'name',
        );
        $this->assertValidationError(
            fn () => $service->prepareAcademicYear([...$payload, 'name' => '2028/2029', 'preparation_reference' => ''], $admin),
            'preparation_reference',
        );
        $this->assertDatabaseCount('academic_years', 1);
    }

    #[Test]
    public function migration_backfills_old_rows_without_mislabeling_unknown_data_as_school_provisional(): void
    {
        $originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.migration_probe', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('migration_probe');

        try {
            DB::setDefaultConnection('migration_probe');
            $schema = Schema::connection('migration_probe');
            $schema->create('users', function (Blueprint $table): void {
                $table->id();
            });
            $schema->create('academic_years', function (Blueprint $table): void {
                $table->id();
                $table->string('dapodik_id')->nullable();
                $table->string('name');
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->boolean('is_active')->default(false);
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();
            });
            $schema->create('classrooms', function (Blueprint $table): void {
                $table->id();
                $table->string('dapodik_id')->nullable();
                $table->unsignedBigInteger('academic_year_id');
                $table->string('name');
                $table->timestamps();
            });
            $schema->create('students', function (Blueprint $table): void {
                $table->id();
                $table->string('dapodik_id')->nullable();
                $table->string('nisn');
                $table->string('name');
                $table->timestamps();
            });
            $schema->create('student_class_memberships', function (Blueprint $table): void {
                $table->id();
                $table->string('dapodik_id')->nullable();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('classroom_id');
                $table->unsignedBigInteger('academic_year_id');
                $table->timestamps();
            });

            DB::table('academic_years')->insert([
                ['id' => 1, 'dapodik_id' => 'dap-year', 'name' => '2025/2026'],
                ['id' => 2, 'dapodik_id' => null, 'name' => '2026/2027'],
            ]);
            DB::table('classrooms')->insert([
                ['id' => 1, 'dapodik_id' => 'dap-class', 'academic_year_id' => 1, 'name' => 'X RPL 1'],
                ['id' => 2, 'dapodik_id' => null, 'academic_year_id' => 2, 'name' => 'XI RPL 1'],
            ]);
            DB::table('students')->insert([
                ['id' => 1, 'dapodik_id' => 'dap-student', 'nisn' => '0012345678', 'name' => 'Dapodik'],
                ['id' => 2, 'dapodik_id' => null, 'nisn' => '0098765432', 'name' => 'Belum Diketahui'],
            ]);
            DB::table('student_class_memberships')->insert([
                ['id' => 1, 'dapodik_id' => 'dap-member', 'student_id' => 1, 'classroom_id' => 1, 'academic_year_id' => 1],
                ['id' => 2, 'dapodik_id' => null, 'student_id' => 2, 'classroom_id' => 2, 'academic_year_id' => 2],
            ]);

            $migration = require database_path('migrations/2026_09_09_000100_add_master_source_to_dapodik_cache.php');
            $migration->up();

            foreach (['academic_years', 'classrooms', 'students', 'student_class_memberships'] as $table) {
                $this->assertSame('dapodik', DB::table($table)->where('id', 1)->value('master_source'));
                $this->assertSame('legacy_unclassified', DB::table($table)->where('id', 2)->value('master_source'));
            }
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('migration_probe');
        }
    }

    #[Test]
    public function dapodik_preview_evidence_forward_migration_upgrades_original_preview_schema(): void
    {
        $originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.migration_preview_upgrade_probe', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('migration_preview_upgrade_probe');

        try {
            DB::setDefaultConnection('migration_preview_upgrade_probe');
            $schema = Schema::connection('migration_preview_upgrade_probe');
            $schema->create('users', function (Blueprint $table): void {
                $table->id();
            });
            $schema->create('external_sync_runs', function (Blueprint $table): void {
                $table->id();
                $table->string('source');
                $table->string('status');
                $table->string('snapshot_fingerprint', 64)->nullable();
                $table->unsignedInteger('preview_generation')->nullable();
                $table->unsignedInteger('decision_revision')->default(0);
                $table->timestamps();
            });
            $schema->create('dapodik_sync_preview_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('external_sync_run_id')->constrained()->cascadeOnDelete();
                $table->string('entity_type', 30);
                $table->string('source_identifier', 100);
                $table->string('match_status', 30);
                $table->json('safe_fields');
                $table->string('item_hash', 64);
                $table->unsignedInteger('preview_generation');
                $table->unsignedInteger('decision_revision')->default(0);
                $table->timestamps();
            });
            $this->assertFalse($schema->hasColumn('external_sync_runs', 'snapshot_evidence'));
            $this->assertFalse($schema->hasColumn('external_sync_runs', 'deactivation_plan'));

            $migrationPath = database_path(
                'migrations/2026_09_11_000100_add_dapodik_preview_evidence_to_external_sync_runs.php',
            );
            $this->assertFileExists($migrationPath);
            $migration = require $migrationPath;
            $migration->up();

            $this->assertTrue($schema->hasColumns('external_sync_runs', [
                'snapshot_evidence',
                'deactivation_plan',
            ]));
            $runId = DB::table('external_sync_runs')->insertGetId([
                'source' => 'dapodik',
                'status' => 'preview_ready',
                'snapshot_fingerprint' => str_repeat('a', 64),
                'snapshot_evidence' => json_encode(['completeness_marker' => 'partial'], JSON_THROW_ON_ERROR),
                'deactivation_plan' => json_encode([], JSON_THROW_ON_ERROR),
                'preview_generation' => 1,
                'decision_revision' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('dapodik_sync_preview_items')->insert([
                'external_sync_run_id' => $runId,
                'entity_type' => 'student',
                'source_identifier' => 'student-1',
                'match_status' => 'new_record',
                'safe_fields' => json_encode(['nisn' => '0012345678'], JSON_THROW_ON_ERROR),
                'item_hash' => str_repeat('b', 64),
                'preview_generation' => 1,
                'decision_revision' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertSame('partial', json_decode(
                DB::table('external_sync_runs')->where('id', $runId)->value('snapshot_evidence'),
                true,
                flags: JSON_THROW_ON_ERROR,
            )['completeness_marker']);
            $this->assertSame(1, DB::table('dapodik_sync_preview_items')->count());

            $migration->down();
            $this->assertTrue($schema->hasColumn('external_sync_runs', 'snapshot_evidence'));
            $this->assertTrue($schema->hasColumn('external_sync_runs', 'deactivation_plan'));
            $this->assertSame('partial', json_decode(
                DB::table('external_sync_runs')->where('id', $runId)->value('snapshot_evidence'),
                true,
                flags: JSON_THROW_ON_ERROR,
            )['completeness_marker']);
            $this->assertSame(1, DB::table('dapodik_sync_preview_items')->count());
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('migration_preview_upgrade_probe');
        }
    }

    #[Test]
    public function dapodik_preview_evidence_forward_migration_is_safe_when_fix_base_already_has_both_columns(): void
    {
        $originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.migration_preview_fix_base_probe', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('migration_preview_fix_base_probe');

        try {
            DB::setDefaultConnection('migration_preview_fix_base_probe');
            $schema = Schema::connection('migration_preview_fix_base_probe');
            $schema->create('external_sync_runs', function (Blueprint $table): void {
                $table->id();
                $table->json('snapshot_evidence')->nullable();
                $table->json('deactivation_plan')->nullable();
            });
            $runId = DB::table('external_sync_runs')->insertGetId([
                'snapshot_evidence' => json_encode(['sentinel' => 'fix-base'], JSON_THROW_ON_ERROR),
                'deactivation_plan' => json_encode([['entity_type' => 'student']], JSON_THROW_ON_ERROR),
            ]);
            $migration = require database_path(
                'migrations/2026_09_11_000100_add_dapodik_preview_evidence_to_external_sync_runs.php',
            );

            try {
                $migration->up();
            } catch (\Throwable $exception) {
                $this->fail('Forward migration harus no-op pada schema fix-base: '.$exception->getMessage());
            }

            $this->assertSame(
                ['sentinel' => 'fix-base'],
                json_decode(
                    DB::table('external_sync_runs')->where('id', $runId)->value('snapshot_evidence'),
                    true,
                    flags: JSON_THROW_ON_ERROR,
                ),
            );
            $migration->down();
            $this->assertTrue($schema->hasColumn('external_sync_runs', 'snapshot_evidence'));
            $this->assertTrue($schema->hasColumn('external_sync_runs', 'deactivation_plan'));
            $this->assertSame(
                [['entity_type' => 'student']],
                json_decode(
                    DB::table('external_sync_runs')->where('id', $runId)->value('deactivation_plan'),
                    true,
                    flags: JSON_THROW_ON_ERROR,
                ),
            );
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('migration_preview_fix_base_probe');
        }
    }

    #[Test]
    public function dapodik_preview_evidence_forward_migration_adds_only_the_missing_column(): void
    {
        $originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.migration_preview_partial_probe', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('migration_preview_partial_probe');

        try {
            DB::setDefaultConnection('migration_preview_partial_probe');
            $schema = Schema::connection('migration_preview_partial_probe');
            $schema->create('external_sync_runs', function (Blueprint $table): void {
                $table->id();
                $table->json('snapshot_evidence')->nullable();
            });
            $runId = DB::table('external_sync_runs')->insertGetId([
                'snapshot_evidence' => json_encode(['sentinel' => 'partial'], JSON_THROW_ON_ERROR),
            ]);
            $migration = require database_path(
                'migrations/2026_09_11_000100_add_dapodik_preview_evidence_to_external_sync_runs.php',
            );

            try {
                $migration->up();
            } catch (\Throwable $exception) {
                $this->fail('Forward migration harus menambah hanya kolom yang hilang: '.$exception->getMessage());
            }

            $this->assertTrue($schema->hasColumns('external_sync_runs', [
                'snapshot_evidence',
                'deactivation_plan',
            ]));
            $this->assertSame(
                ['sentinel' => 'partial'],
                json_decode(
                    DB::table('external_sync_runs')->where('id', $runId)->value('snapshot_evidence'),
                    true,
                    flags: JSON_THROW_ON_ERROR,
                ),
            );
            $migration->down();
            $this->assertTrue($schema->hasColumn('external_sync_runs', 'snapshot_evidence'));
            $this->assertTrue($schema->hasColumn('external_sync_runs', 'deactivation_plan'));
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('migration_preview_partial_probe');
        }
    }

    #[Test]
    public function dapodik_preview_migration_applies_after_provenance_and_before_timestamp_relaxation(): void
    {
        $originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.migration_preview_probe', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('migration_preview_probe');

        try {
            DB::setDefaultConnection('migration_preview_probe');
            $schema = Schema::connection('migration_preview_probe');
            $schema->create('users', function (Blueprint $table): void {
                $table->id();
            });
            $schema->create('external_sync_runs', function (Blueprint $table): void {
                $table->id();
                $table->string('source');
                $table->string('status');
                $table->timestamps();
            });

            $migration = require database_path('migrations/2026_09_09_000200_create_dapodik_sync_preview_items.php');
            $migration->up();
            $this->assertFalse($schema->hasColumn('external_sync_runs', 'snapshot_evidence'));
            $this->assertFalse($schema->hasColumn('external_sync_runs', 'deactivation_plan'));

            $evidenceMigration = require database_path(
                'migrations/2026_09_11_000100_add_dapodik_preview_evidence_to_external_sync_runs.php',
            );
            $evidenceMigration->up();

            $this->assertTrue($schema->hasColumns('external_sync_runs', [
                'snapshot_fingerprint',
                'snapshot_evidence',
                'deactivation_plan',
                'preview_generation',
                'decision_revision',
                'configuration_version',
                'preview_fencing_token',
                'endpoint_policy_digest',
                'preview_expires_at',
                'applied_at',
                'superseded_at',
            ]));
            $this->assertTrue($schema->hasTable('dapodik_sync_preview_items'));

            $runId = DB::table('external_sync_runs')->insertGetId([
                'source' => 'dapodik',
                'status' => 'preview_ready',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('dapodik_sync_preview_items')->insert([
                'external_sync_run_id' => $runId,
                'entity_type' => 'student',
                'source_identifier' => 'student-1',
                'match_status' => 'new_record',
                'safe_fields' => json_encode(['nisn' => '0012345678'], JSON_THROW_ON_ERROR),
                'item_hash' => str_repeat('a', 64),
                'preview_generation' => 1,
                'decision_revision' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertSame(1, DB::table('dapodik_sync_preview_items')->count());
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('migration_preview_probe');
        }
    }

    #[Test]
    public function strict_csv_parser_accepts_utf8_bom_and_exactly_five_thousand_rows(): void
    {
        $parser = app(ProvisionalRosterCsvParser::class);
        $this->assertTrue(method_exists($parser, 'parse'));
        $rows = [];
        for ($number = 1; $number <= 5000; $number++) {
            $rows[] = sprintf('%010d,Nama Murid %d,X RPL 1,2027/2028', $number, $number);
        }

        $parsed = $parser->parse($this->csv("\xEF\xBB\xBFnisn,nama,rombel,tahun_pelajaran\n".implode("\n", $rows)));

        $this->assertCount(5000, $parsed);
        $this->assertSame([
            'nisn' => '0000000001',
            'name' => 'Nama Murid 1',
            'classroom' => 'X RPL 1',
            'academic_year_name' => '2027/2028',
        ], $parsed[0]);
    }

    #[Test]
    public function strict_csv_parser_ignores_blank_rows_between_and_after_records(): void
    {
        $parsed = app(ProvisionalRosterCsvParser::class)->parse(
            $this->csv(
                "nisn,nama,rombel,tahun_pelajaran\n"
                ."0012345678,Nama Murid A,X RPL 1,2027/2028\n"
                ."\n"
                ."0098765432,Nama Murid B,X RPL 2,2027/2028\n"
                ."\n"
            ),
        );

        $this->assertSame([
            [
                'nisn' => '0012345678',
                'name' => 'Nama Murid A',
                'classroom' => 'X RPL 1',
                'academic_year_name' => '2027/2028',
            ],
            [
                'nisn' => '0098765432',
                'name' => 'Nama Murid B',
                'classroom' => 'X RPL 2',
                'academic_year_name' => '2027/2028',
            ],
        ], $parsed);
    }

    #[Test]
    public function roster_api_payload_parser_accepts_provider_response_shape(): void
    {
        $parsed = app(ProvisionalRosterPayloadParser::class)->parse([
            'success' => true,
            'message' => 'Data siswa ditemukan',
            'data' => [
                [
                    'nama' => 'ARDIANSYAH DWI PRASETYO',
                    'nisn' => '0045123492',
                    'rombel' => '12 PH 1',
                    'tahun_pelajaran' => '2026/2027',
                ],
            ],
        ]);

        $this->assertSame([
            [
                'nisn' => '0045123492',
                'name' => 'ARDIANSYAH DWI PRASETYO',
                'classroom' => '12 PH 1',
                'academic_year_name' => '2026/2027',
            ],
        ], $parsed);
    }

    /** @param array{name?: string, content: string} $fixture */
    #[Test]
    #[DataProvider('invalidCsvProvider')]
    public function strict_csv_parser_rejects_unsafe_or_malformed_files(array $fixture): void
    {
        $this->expectException(ValidationException::class);

        app(ProvisionalRosterCsvParser::class)->parse(
            $this->csv($fixture['content'], $fixture['name'] ?? 'roster.csv'),
        );
    }

    /** @return iterable<string, array{array{name?: string, content: string}}> */
    public static function invalidCsvProvider(): iterable
    {
        yield 'extension selain csv' => [[
            'name' => 'roster.txt',
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,Nama,X RPL 1,2027/2028",
        ]];
        yield 'header tidak exact' => [[
            'content' => "nama,nisn,rombel\nNama,0012345678,X RPL 1",
        ]];
        yield 'tanpa baris data' => [[
            'content' => 'nisn,nama,rombel,tahun_pelajaran',
        ]];
        yield 'nisn bukan sepuluh digit' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n1234,Nama,X RPL 1,2027/2028",
        ]];
        yield 'nisn ganda' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,Nama A,X RPL 1,2027/2028\n0012345678,Nama B,X RPL 2,2027/2028",
        ]];
        yield 'kolom kurang' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,Nama",
        ]];
        yield 'kolom ekstra' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,Nama,X RPL 1,2027/2028,Ekstra",
        ]];
        yield 'quote tidak sah di field tanpa enclosure' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,Nama \"tidak sah\",X RPL 1,2027/2028",
        ]];
        yield 'formula pada nama' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,=HYPERLINK(\"https://invalid.test\"),X RPL 1,2027/2028",
        ]];
        yield 'formula pada rombel' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,Nama,+SUM(1),2027/2028",
        ]];
        yield 'tahun pelajaran kosong' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,Nama,X RPL 1,",
        ]];
        yield 'formula pada tahun pelajaran' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,Nama,X RPL 1,=2027/2028",
        ]];
        yield 'control character' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,Nama\tMurid,X RPL 1,2027/2028",
        ]];
        yield 'control character di tepi field' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,\tNama Murid,X RPL 1,2027/2028",
        ]];
        yield 'utf8 tidak valid' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,Nama\xFF,X RPL 1,2027/2028",
        ]];
        yield 'bom utf16' => [[
            'content' => "\xFF\xFEn\x00i\x00s\x00n\x00",
        ]];
        yield 'lebih dari lima ribu baris' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n".implode("\n", array_map(
                static fn (int $number): string => sprintf('%010d,Nama %d,X RPL 1,2027/2028', $number, $number),
                range(1, 5001),
            )),
        ]];
        yield 'lebih dari dua mebibyte' => [[
            'content' => "nisn,nama,rombel,tahun_pelajaran\n0012345678,".str_repeat('A', (2 * 1024 * 1024) + 1).',X RPL 1,2027/2028',
        ]];
    }

    #[Test]
    public function roster_import_is_idempotent_preserves_existing_identity_provenance_and_bk_history(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $year = $this->prepareYear($service, $admin);
        $confirmedAt = now()->subMonth()->startOfSecond();
        $dapodikStudent = Student::query()->create([
            'dapodik_id' => 'dapodik-student-1',
            'nisn' => '0012345678',
            'name' => 'Nama Resmi Dapodik',
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => $confirmedAt,
        ]);
        $legacyStudent = Student::query()->create([
            'nisn' => '0098765432',
            'name' => 'Nama Data Lama',
            'master_source' => Student::MASTER_SOURCE_LEGACY_UNCLASSIFIED,
        ]);
        $confirmedClassroom = Classroom::query()->create([
            'dapodik_id' => 'dapodik-classroom-1',
            'academic_year_id' => $year->id,
            'name' => 'X RPL 1',
            'master_source' => Classroom::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => $confirmedAt,
        ]);
        $confirmedMembership = StudentClassMembership::query()->create([
            'dapodik_id' => 'dapodik-membership-1',
            'student_id' => $dapodikStudent->id,
            'classroom_id' => $confirmedClassroom->id,
            'academic_year_id' => $year->id,
            'master_source' => StudentClassMembership::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => $confirmedAt,
        ]);
        $case = $this->caseFor($dapodikStudent, $admin);
        $csv = $this->csv(implode("\n", [
            'nisn,nama,rombel,tahun_pelajaran',
            '0012345678,Nama CSV Tidak Boleh Menimpa,X RPL 1,2027/2028',
            '0098765432,Nama CSV Lama Tidak Menimpa,X RPL 1,2027/2028',
            '0000000003,Murid Baru,X RPL 2,2027/2028',
        ]));

        $first = $service->importRoster($year, $csv, $admin);
        $second = $service->importRoster($year, $this->csv($csv->getContent()), $admin);

        $this->assertSame(3, $first->rows);
        $this->assertSame(1, $first->studentsCreated);
        $this->assertSame(2, $first->studentsMatched);
        $this->assertSame(1, $first->classroomsCreated);
        $this->assertSame(2, $first->membershipsCreated);
        $this->assertSame(1, $first->membershipsUnchanged);
        $this->assertSame(0, $second->studentsCreated);
        $this->assertSame(3, $second->studentsMatched);
        $this->assertSame(0, $second->classroomsCreated);
        $this->assertSame(0, $second->membershipsCreated);
        $this->assertSame(3, $second->membershipsUnchanged);

        $dapodikStudent->refresh();
        $legacyStudent->refresh();
        $this->assertSame('Nama Resmi Dapodik', $dapodikStudent->name);
        $this->assertSame('dapodik-student-1', $dapodikStudent->dapodik_id);
        $this->assertSame(Student::MASTER_SOURCE_DAPODIK, $dapodikStudent->master_source);
        $this->assertTrue($confirmedAt->equalTo($dapodikStudent->source_confirmed_at));
        $this->assertSame('Nama Data Lama', $legacyStudent->name);
        $this->assertSame(Student::MASTER_SOURCE_LEGACY_UNCLASSIFIED, $legacyStudent->master_source);
        $this->assertSame($dapodikStudent->id, $case->refresh()->student_id);
        $this->assertSame(Classroom::MASTER_SOURCE_DAPODIK, $confirmedClassroom->refresh()->master_source);
        $this->assertSame('dapodik-classroom-1', $confirmedClassroom->dapodik_id);
        $this->assertTrue($confirmedAt->equalTo($confirmedClassroom->source_confirmed_at));
        $this->assertSame(StudentClassMembership::MASTER_SOURCE_DAPODIK, $confirmedMembership->refresh()->master_source);
        $this->assertSame('dapodik-membership-1', $confirmedMembership->dapodik_id);
        $this->assertTrue($confirmedAt->equalTo($confirmedMembership->source_confirmed_at));

        $newStudent = Student::query()->where('nisn', '0000000003')->firstOrFail();
        $this->assertSame(Student::MASTER_SOURCE_SCHOOL_PROVISIONAL, $newStudent->master_source);
        $this->assertNull($newStudent->dapodik_id);
        $this->assertFalse($year->refresh()->is_active);
        $this->assertSame(AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL, $year->master_source);
        $this->assertSame(1, Classroom::query()->where('master_source', Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL)->count());
        $this->assertSame(2, StudentClassMembership::query()->where('master_source', StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL)->count());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'provisional_student.created',
            'auditable_id' => $newStudent->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'provisional_classroom.created',
        ]);
        $this->assertSame(2, AuditLog::query()->where('action', 'provisional_membership.created')->count());

        $audit = AuditLog::query()->where('action', 'academic_year.roster_imported')->latest('id')->firstOrFail();
        $serializedAudit = json_encode([$audit->summary, $audit->before_values, $audit->after_values], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('0012345678', $serializedAudit);
        $this->assertStringNotContainsString('Nama CSV', $serializedAudit);
        $this->assertStringNotContainsString('nisn,nama,rombel', $serializedAudit);
    }

    #[Test]
    public function global_roster_import_moves_the_same_student_across_multiple_existing_years(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $firstYear = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => false,
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
            'prepared_by' => $admin->id,
            'preparation_reference' => 'Kalender Pendidikan 2026/2027',
        ]);
        $secondYear = $this->prepareYear($service, $admin);
        $file = $this->csv(implode("\n", [
            'nisn,nama,rombel,tahun_pelajaran',
            '0012345678,Nama Murid,XI PH 1,2026/2027',
            '0012345678,Nama Murid,XII PH 1,2027/2028',
        ]));

        $result = $service->importRosters($file, $admin);

        $student = Student::query()->where('nisn', '0012345678')->sole();
        $memberships = StudentClassMembership::query()
            ->with('classroom')
            ->where('student_id', $student->id)
            ->orderBy('academic_year_id')
            ->get();
        $this->assertSame(2, $result->rows);
        $this->assertSame(2, $result->academicYears);
        $this->assertSame(1, $result->studentsCreated);
        $this->assertCount(2, $memberships);
        $this->assertSame([$firstYear->id, $secondYear->id], $memberships->pluck('academic_year_id')->all());
        $this->assertSame(['XI PH 1', 'XII PH 1'], $memberships->pluck('classroom.name')->all());
    }

    #[Test]
    public function api_roster_payload_imports_students_with_the_existing_preparation_rules(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $firstYear = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => false,
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
            'prepared_by' => $admin->id,
            'preparation_reference' => 'Kalender Pendidikan 2026/2027',
        ]);
        $secondYear = $this->prepareYear($service, $admin);

        $result = $service->importRosterPayload([
            'success' => true,
            'message' => 'Data siswa ditemukan',
            'data' => [
                [
                    'nama' => 'ARDIANSYAH DWI PRASETYO',
                    'nisn' => '0045123492',
                    'rombel' => '12 PH 1',
                    'tahun_pelajaran' => '2026/2027',
                ],
                [
                    'nama' => 'ARDIANSYAH DWI PRASETYO',
                    'nisn' => '0045123492',
                    'rombel' => '13 PH 1',
                    'tahun_pelajaran' => '2027/2028',
                ],
            ],
        ], $admin);

        $student = Student::query()->where('nisn', '0045123492')->sole();
        $memberships = StudentClassMembership::query()
            ->with('classroom')
            ->where('student_id', $student->id)
            ->orderBy('academic_year_id')
            ->get();
        $this->assertSame(2, $result->rows);
        $this->assertSame(2, $result->academicYears);
        $this->assertSame(1, $result->studentsCreated);
        $this->assertSame([$firstYear->id, $secondYear->id], $memberships->pluck('academic_year_id')->all());
        $this->assertSame(['12 PH 1', '13 PH 1'], $memberships->pluck('classroom.name')->all());
    }

    #[Test]
    public function global_roster_route_accepts_api_payload_and_returns_json_summary(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $this->prepareYear($service, $admin);

        $this->actingAs($admin)
            ->postJson(route('data-master.roster-imports.store'), [
                'success' => true,
                'message' => 'Data siswa ditemukan',
                'data' => [
                    [
                        'nama' => 'NAMA MURID',
                        'nisn' => '0012345678',
                        'rombel' => 'X RPL 1',
                        'tahun_pelajaran' => '2027/2028',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rows', 1)
            ->assertJsonPath('data.academic_years', 1)
            ->assertJsonPath('data.students_created', 1);

        $this->assertDatabaseHas('students', ['nisn' => '0012345678']);
        $this->assertDatabaseHas('classrooms', ['name' => 'X RPL 1']);
        $this->assertDatabaseCount('student_class_memberships', 1);
    }

    #[Test]
    public function admin_imports_roster_from_a_one_time_api_siswa_url_with_a_safe_run_summary(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $this->prepareYear($service, $admin);
        $existingStudent = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Nama Lokal Dipertahankan',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $url = 'https://8.8.8.8/api/siswa?token=RAHASIA-URL';

        Http::fake([
            $url => Http::response([
                'success' => true,
                'message' => 'Data siswa ditemukan',
                'data' => [
                    [
                        'nama' => 'Nama API Tidak Menimpa',
                        'nisn' => '0012345678',
                        'rombel' => 'X RPL 1',
                        'tahun_pelajaran' => '2027/2028',
                    ],
                    [
                        'nama' => 'Murid Baru',
                        'nisn' => '0098765432',
                        'rombel' => 'X RPL 2',
                        'tahun_pelajaran' => '2027/2028',
                    ],
                ],
            ]),
        ]);

        $this->actingAs($admin)
            ->from(route('data-master.index'))
            ->post(route('data-master.roster-imports.store'), ['api_url' => $url])
            ->assertRedirect(route('data-master.index'))
            ->assertSessionHas('success');

        Http::assertSent(fn ($request): bool => $request->method() === 'GET'
            && $request->url() === $url);
        $this->assertSame('Nama Lokal Dipertahankan', $existingStudent->refresh()->name);
        $this->assertDatabaseHas('students', [
            'nisn' => '0098765432',
            'name' => 'Murid Baru',
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $this->assertDatabaseHas('classrooms', ['name' => 'X RPL 1']);
        $this->assertDatabaseHas('classrooms', ['name' => 'X RPL 2']);
        $this->assertDatabaseCount('student_class_memberships', 2);
        $this->assertDatabaseHas('audit_logs', ['action' => 'academic_year.roster_imported']);

        $run = ExternalSyncRun::query()->where('source', 'api_siswa')->sole();
        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $run->status);
        $this->assertSame(2, $run->received_count);
        $this->assertSame(2, $run->processed_count);
        $serializedRun = serialize($run->getAttributes());
        $this->assertStringNotContainsString($url, $serializedRun);
        $this->assertStringNotContainsString('RAHASIA-URL', $serializedRun);
        $this->assertStringNotContainsString('0012345678', $serializedRun);
        $this->assertStringNotContainsString('Nama API', $serializedRun);
    }

    #[Test]
    public function admin_previews_api_siswa_without_mutating_or_storing_the_payload(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $this->prepareYear($service, $admin);
        $url = 'https://8.8.8.8/api/siswa?token=PREVIEW-SECRET';

        Http::fake([
            $url => Http::response([
                'success' => true,
                'message' => 'Data siswa ditemukan',
                'data' => [
                    [
                        'nama' => 'Murid Pratinjau Satu',
                        'nisn' => '0012345678',
                        'rombel' => 'X RPL 1',
                        'tahun_pelajaran' => '2027/2028',
                    ],
                    [
                        'nama' => 'Murid Pratinjau Dua',
                        'nisn' => '0098765432',
                        'rombel' => 'X RPL 2',
                        'tahun_pelajaran' => '2027/2028',
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($admin)->postJson(
            route('data-master.roster-imports.preview'),
            ['api_url' => $url],
        );

        $response
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rows', 2)
            ->assertJsonPath('data.students', 2)
            ->assertJsonPath('data.can_import', true)
            ->assertJsonPath('data.academic_years.0.name', '2027/2028')
            ->assertJsonPath('data.academic_years.0.classrooms', 2)
            ->assertJsonPath('data.academic_years.0.ready', true)
            ->assertJsonPath('data.sample.0.nisn', '0012****78')
            ->assertJsonPath('data.sample.0.name', 'Murid Pratinjau Satu');
        $this->assertStringNotContainsString('0012345678', $response->getContent());
        $this->assertStringNotContainsString('PREVIEW-SECRET', $response->getContent());
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
        $this->assertDatabaseCount('external_sync_runs', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'academic_year.roster_imported']);
    }

    #[Test]
    public function api_siswa_preview_marks_unknown_year_as_not_ready_without_rejecting_the_preview(): void
    {
        $admin = $this->userWithRole('admin_it');
        Http::fake([
            'https://8.8.8.8/unknown-preview-year' => Http::response([
                'success' => true,
                'data' => [[
                    'nama' => 'Murid Tahun Belum Ada',
                    'nisn' => '0012345678',
                    'rombel' => 'X RPL 1',
                    'tahun_pelajaran' => '2030/2031',
                ]],
            ]),
        ]);

        $this->actingAs($admin)
            ->postJson(route('data-master.roster-imports.preview'), [
                'api_url' => 'https://8.8.8.8/unknown-preview-year',
            ])
            ->assertOk()
            ->assertJsonPath('data.can_import', false)
            ->assertJsonPath('data.academic_years.0.ready', false)
            ->assertJsonPath(
                'data.academic_years.0.status',
                'Tahun pelajaran belum dibuat di Data Master.',
            );

        $this->assertDatabaseCount('external_sync_runs', 0);
    }

    #[Test]
    public function api_siswa_preview_reports_a_safe_dns_diagnostic(): void
    {
        $admin = $this->userWithRole('admin_it');
        $this->resetHttpFactory();
        Http::fake(Http::failedConnection('cURL error 6: Could not resolve host'));

        $this->actingAs($admin)
            ->postJson(route('data-master.roster-imports.preview'), [
                'api_url' => 'https://8.8.8.8/dns',
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.api_url.0',
                'Nama host API Siswa tidak dapat ditemukan oleh DNS server SIBK.',
            );

        $this->assertDatabaseCount('external_sync_runs', 0);
    }

    #[Test]
    public function api_siswa_connection_diagnostic_classifies_ssl_failures_safely(): void
    {
        $method = new \ReflectionMethod(
            app(ApiSiswaRosterImportService::class),
            'connectionFailureMessage',
        );

        $this->assertSame(
            'Koneksi HTTPS API Siswa gagal diverifikasi. Periksa sertifikat SSL server API.',
            $method->invoke(
                app(ApiSiswaRosterImportService::class),
                new ConnectionException('cURL error 60: SSL certificate problem'),
            ),
        );
    }

    #[Test]
    public function api_siswa_preview_rejects_redirects_with_a_safe_diagnostic(): void
    {
        $admin = $this->userWithRole('admin_it');
        $this->resetHttpFactory();
        Http::fake(Http::response('', 302, [
            'Location' => 'https://8.8.4.4/final',
        ]));

        $this->actingAs($admin)
            ->postJson(route('data-master.roster-imports.preview'), [
                'api_url' => 'https://8.8.8.8/redirect',
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.api_url.0',
                'API Siswa mengalihkan permintaan (HTTP 302). Gunakan link tujuan akhir secara langsung.',
            );

        $this->assertDatabaseCount('external_sync_runs', 0);
    }

    #[Test]
    public function api_siswa_import_rejects_local_and_private_urls_without_sending_a_request(): void
    {
        $admin = $this->userWithRole('admin_it');
        Http::fake();

        foreach (['http://localhost/siswa', 'http://127.0.0.1/siswa', 'http://192.168.1.10/siswa'] as $url) {
            $this->actingAs($admin)
                ->from(route('data-master.index'))
                ->post(route('data-master.roster-imports.store'), ['api_url' => $url])
                ->assertRedirect(route('data-master.index'))
                ->assertSessionHasErrors('api_url');
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
        $this->assertSame(3, ExternalSyncRun::query()
            ->where('source', 'api_siswa')
            ->where('status', ExternalSyncRun::STATUS_FAILED)
            ->count());
    }

    #[Test]
    public function api_siswa_import_requires_a_valid_http_or_https_url(): void
    {
        $admin = $this->userWithRole('admin_it');

        foreach (['', 'bukan-url', 'ftp://8.8.8.8/siswa'] as $url) {
            $this->actingAs($admin)
                ->from(route('data-master.index'))
                ->post(route('data-master.roster-imports.store'), ['api_url' => $url])
                ->assertRedirect(route('data-master.index'))
                ->assertSessionHasErrors('api_url');
        }

        $this->assertDatabaseCount('external_sync_runs', 0);
    }

    #[Test]
    public function api_siswa_import_rejects_fetch_and_payload_failures_without_roster_mutation(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $this->prepareYear($service, $admin);

        Http::fake([
            'https://8.8.8.8/status' => Http::response('gagal', 503),
            'https://8.8.8.8/not-json' => Http::response('<html>bukan json</html>'),
            'https://8.8.8.8/too-large' => Http::response(str_repeat('A', (2 * 1024 * 1024) + 1)),
            'https://8.8.8.8/failed' => Http::response(['success' => false, 'data' => []]),
            'https://8.8.8.8/empty' => Http::response(['success' => true, 'data' => []]),
            'https://8.8.8.8/masked' => Http::response([
                'success' => true,
                'data' => [[
                    'nama' => 'Murid Masking',
                    'nisn' => '0045xxxx92',
                    'rombel' => 'X RPL 1',
                    'tahun_pelajaran' => '2027/2028',
                ]],
            ]),
            'https://8.8.8.8/unknown-year' => Http::response([
                'success' => true,
                'data' => [[
                    'nama' => 'Murid Tahun Belum Ada',
                    'nisn' => '0012345678',
                    'rombel' => 'X RPL 1',
                    'tahun_pelajaran' => '2030/2031',
                ]],
            ]),
            'https://8.8.8.8/timeout' => Http::failedConnection('timeout'),
        ]);

        foreach ([
            ['status', 'api_url'],
            ['not-json', 'api_url'],
            ['too-large', 'api_url'],
            ['failed', 'data'],
            ['empty', 'data'],
            ['masked', 'data'],
            ['unknown-year', 'academic_year'],
            ['timeout', 'api_url'],
        ] as [$path, $errorKey]) {
            $this->actingAs($admin)
                ->from(route('data-master.index'))
                ->post(route('data-master.roster-imports.store'), [
                    'api_url' => 'https://8.8.8.8/'.$path,
                ])
                ->assertRedirect(route('data-master.index'))
                ->assertSessionHasErrors($errorKey);
        }

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
        $this->assertSame(8, ExternalSyncRun::query()
            ->where('source', 'api_siswa')
            ->where('status', ExternalSyncRun::STATUS_FAILED)
            ->count());
        $this->assertDatabaseMissing('audit_logs', ['action' => 'academic_year.roster_imported']);
    }

    #[Test]
    public function api_siswa_url_is_not_flushed_back_to_the_session_after_validation_failure(): void
    {
        $admin = $this->userWithRole('admin_it');
        $secretUrl = 'https://8.8.8.8/api/siswa?token=TOKEN-TIDAK-BOLEH-DISIMPAN';

        $this->actingAs($admin)
            ->from(route('data-master.index'))
            ->post(route('data-master.roster-imports.store'), [
                'api_url' => $secretUrl,
                'file' => $this->validCsv(),
            ])
            ->assertSessionHasErrors(['api_url', 'file']);

        $this->assertNull(session()->getOldInput('api_url'));
        $this->assertStringNotContainsString('TOKEN-TIDAK-BOLEH-DISIMPAN', serialize(session()->all()));
        $this->assertDatabaseCount('external_sync_runs', 0);
    }

    #[Test]
    public function global_roster_import_rejects_an_unknown_year_atomically(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $this->prepareYear($service, $admin);
        $file = $this->csv(implode("\n", [
            'nisn,nama,rombel,tahun_pelajaran',
            '0012345678,Murid Valid,X RPL 1,2027/2028',
            '0098765432,Murid Tahun Tidak Ada,X RPL 2,2030/2031',
        ]));

        $this->assertValidationError(
            fn () => $service->importRosters($file, $admin),
            'academic_year',
        );

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'academic_year.roster_imported']);
    }

    #[Test]
    public function api_roster_payload_rejects_an_unknown_year_atomically(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $this->prepareYear($service, $admin);

        $this->assertValidationError(
            fn () => $service->importRosterPayload([
                'success' => true,
                'message' => 'Data siswa ditemukan',
                'data' => [
                    [
                        'nama' => 'Murid Valid',
                        'nisn' => '0012345678',
                        'rombel' => 'X RPL 1',
                        'tahun_pelajaran' => '2027/2028',
                    ],
                    [
                        'nama' => 'Murid Tahun Tidak Ada',
                        'nisn' => '0098765432',
                        'rombel' => 'X RPL 2',
                        'tahun_pelajaran' => '2030/2031',
                    ],
                ],
            ], $admin),
            'academic_year',
        );

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'academic_year.roster_imported']);
    }

    #[Test]
    public function legacy_roster_route_rejects_a_different_year_in_the_csv(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $year = $this->prepareYear($service, $admin);

        $this->actingAs($admin)
            ->from(route('data-master.index'))
            ->post(route('data-master.academic-years.roster-imports.store', $year), [
                'file' => $this->csv(
                    "nisn,nama,rombel,tahun_pelajaran\n"
                    .'0012345678,Nama Murid,X RPL 1,2028/2029',
                ),
            ])
            ->assertSessionHasErrors('academic_year');

        $this->assertDatabaseCount('students', 0);
    }

    #[Test]
    public function roster_import_is_atomic_rejects_class_conflicts_and_requires_an_inactive_year_and_active_admin(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $inactiveAdmin = $this->userWithRole('admin_it', false);
        $year = $this->prepareYear($service, $admin);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Existing']);
        $existingClass = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'X RPL 1',
            'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $existingClass->id,
            'academic_year_id' => $year->id,
        ]);
        $conflictingFile = $this->csv(implode("\n", [
            'nisn,nama,rombel,tahun_pelajaran',
            '0000000002,Murid Baru,X RPL 2,2027/2028',
            '0012345678,Murid Existing,X RPL 3,2027/2028',
        ]));

        $this->assertValidationError(
            fn () => $service->importRoster($year, $conflictingFile, $admin),
            'rombel',
        );
        $this->assertDatabaseMissing('students', ['nisn' => '0000000002']);
        $this->assertDatabaseMissing('classrooms', ['name' => 'X RPL 2']);
        $this->assertDatabaseMissing('classrooms', ['name' => 'X RPL 3']);
        $failedAudit = AuditLog::query()->where('action', 'academic_year.roster_import_failed')->latest('id')->firstOrFail();
        $this->assertSame('classroom_membership_conflict', $failedAudit->after_values['failure_code']);
        $this->assertSafeFailedImportAudit($failedAudit);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'academic_year.roster_imported']);

        try {
            $service->importRoster($year, $this->validCsv(), $inactiveAdmin);
            $this->fail('Admin nonaktif dapat mengimpor roster.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('student_class_memberships', 1);
        }

        $year->update(['is_active' => true]);
        $this->assertValidationError(
            fn () => $service->importRoster($year->refresh(), $this->validCsv(), $admin),
            'academic_year',
        );
    }

    #[Test]
    public function provisional_roster_import_does_not_change_student_departure_process(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');
        $year = $this->prepareYear($service, $admin);
        $csv = $this->validCsv();
        $service->importRoster($year, $csv, $admin);
        $student = Student::query()->where('nisn', '0012345678')->firstOrFail();
        $departure = StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'status' => StudentDeparture::STATUS_IN_PROGRESS,
            'reported_at' => '2026-09-14',
            'recorded_by' => $teacher->id,
        ]);
        $before = $departure->only(['status', 'effective_date', 'finalized_by', 'finalized_at']);

        $service->importRoster($year, $this->validCsv(), $admin);

        $this->assertSame($before, $departure->refresh()->only(array_keys($before)));
    }

    #[Test]
    public function roster_import_rejects_dapodik_and_legacy_years_without_mutation_or_success_audit(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');

        foreach ([
            AcademicYear::MASTER_SOURCE_DAPODIK => '2028/2029',
            AcademicYear::MASTER_SOURCE_LEGACY_UNCLASSIFIED => '2029/2030',
        ] as $source => $name) {
            $year = AcademicYear::query()->create([
                'name' => $name,
                'starts_on' => mb_substr($name, 0, 4).'-07-01',
                'ends_on' => mb_substr($name, 5, 4).'-06-30',
                'is_active' => false,
                'master_source' => $source,
            ]);

            $this->assertValidationError(
                fn () => $service->importRoster($year, $this->validCsv(), $admin),
                'academic_year',
            );
            $this->actingAs($admin)
                ->from(route('data-master.index'))
                ->post(route('data-master.academic-years.roster-imports.store', $year), [
                    'file' => $this->validCsv(),
                ])
                ->assertSessionHasErrors('academic_year');
        }

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'academic_year.roster_imported']);
    }

    #[Test]
    public function data_master_offers_one_global_roster_import_and_lists_preparation_years(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $provisional = $this->prepareYear($service, $admin);
        $dapodik = AcademicYear::query()->create([
            'name' => '2028/2029',
            'starts_on' => '2028-07-01',
            'ends_on' => '2029-06-30',
            'is_active' => false,
            'master_source' => AcademicYear::MASTER_SOURCE_DAPODIK,
        ]);
        $legacy = AcademicYear::query()->create([
            'name' => '2029/2030',
            'starts_on' => '2029-07-01',
            'ends_on' => '2030-06-30',
            'is_active' => false,
            'master_source' => AcademicYear::MASTER_SOURCE_LEGACY_UNCLASSIFIED,
        ]);

        $this->actingAs($admin)->get(route('data-master.index'))
            ->assertOk()
            ->assertSee(route('data-master.roster-imports.store'), false)
            ->assertSee('API Siswa')
            ->assertSee('name="api_url"', false)
            ->assertSee('data-api-siswa-preview-modal', false)
            ->assertSee('Cek &amp; Pratinjau', false)
            ->assertSee(route('data-master.roster-imports.preview'), false)
            ->assertSee('name="file"', false)
            ->assertDontSee('data-etatib-api-form', false)
            ->assertDontSee('data-integration-panel="dapodik"', false)
            ->assertSee($provisional->name)
            ->assertDontSee(route('data-master.academic-years.roster-imports.store', $dapodik), false)
            ->assertDontSee(route('data-master.academic-years.roster-imports.store', $legacy), false);

        $this->actingAs($admin)->get(route('data-master.index', ['tab' => 'etatib']))
            ->assertOk()
            ->assertSee('data-etatib-api-form', false)
            ->assertSee(route('data-master.etatib.preview'), false)
            ->assertSee('Pratinjau API e-Tatib');
    }

    #[Test]
    public function malformed_csv_is_rejected_without_data_changes_and_leaves_only_a_sanitized_failure_audit(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $year = $this->prepareYear($service, $admin);
        $unsafeCsv = $this->csv(implode("\n", [
            'nisn,nama,rombel,tahun_pelajaran',
            '0012345678,=NAMA_RAHASIA,X RPL RAHASIA,2027/2028',
        ]));

        $this->assertValidationError(
            fn () => $service->importRoster($year, $unsafeCsv, $admin),
            'file',
        );

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'academic_year.roster_imported']);
        $failedAudit = AuditLog::query()->where('action', 'academic_year.roster_import_failed')->sole();
        $this->assertSame($year->id, $failedAudit->auditable_id);
        $this->assertSame($admin->id, $failedAudit->actor_id);
        $this->assertSame('invalid_csv', $failedAudit->after_values['failure_code']);
        $this->assertSafeFailedImportAudit($failedAudit);
    }

    #[Test]
    public function cross_year_classroom_membership_is_rejected_before_mutation_and_audited_safely(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $otherYear = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => false,
        ]);
        $targetYear = $this->prepareYear($service, $admin);
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Pasangan Silang',
        ]);
        $otherYearClassroom = Classroom::query()->create([
            'academic_year_id' => $otherYear->id,
            'name' => 'X RPL 1',
        ]);
        $crossYearMembership = StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $otherYearClassroom->id,
            'academic_year_id' => $targetYear->id,
        ]);
        $auditCountBefore = AuditLog::query()->count();

        $this->assertValidationError(
            fn () => $service->importRoster($targetYear, $this->csv(implode("\n", [
                'nisn,nama,rombel,tahun_pelajaran',
                '0000000002,Murid Baru Tidak Boleh Tersimpan,X RPL 2,2027/2028',
                '0012345678,Murid Pasangan Silang,X RPL 1,2027/2028',
            ])), $admin),
            'rombel',
        );

        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('classrooms', 1);
        $this->assertDatabaseCount('student_class_memberships', 1);
        $this->assertDatabaseHas('student_class_memberships', [
            'id' => $crossYearMembership->id,
            'classroom_id' => $otherYearClassroom->id,
            'academic_year_id' => $targetYear->id,
        ]);
        $this->assertDatabaseMissing('students', ['nisn' => '0000000002']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'provisional_student.created']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'provisional_classroom.created']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'provisional_membership.created']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'academic_year.roster_imported']);
        $this->assertSame($auditCountBefore + 1, AuditLog::query()->count());
        $failedAudit = AuditLog::query()->where('action', 'academic_year.roster_import_failed')->sole();
        $this->assertSame('classroom_membership_conflict', $failedAudit->after_values['failure_code']);
        $this->assertSafeFailedImportAudit($failedAudit);
    }

    #[Test]
    public function duplicate_local_nisn_fails_closed_before_any_import_change(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique('students_nisn_unique');
        });

        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $year = $this->prepareYear($service, $admin);
        Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid A']);
        Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid B']);

        $this->assertValidationError(
            fn () => $service->importRoster($year, $this->validCsv(), $admin),
            'nisn',
        );
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
    }

    #[Test]
    public function non_exact_local_nisn_is_held_as_a_conflict_instead_of_being_matched_or_duplicated(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $year = $this->prepareYear($service, $admin);
        $legacyStudent = Student::query()->create([
            'nisn' => '0012345678 ',
            'name' => 'Data Lama Dengan Spasi',
        ]);

        $this->assertValidationError(
            fn () => $service->importRoster($year, $this->validCsv(), $admin),
            'nisn',
        );
        $this->assertSame('0012345678 ', $legacyStudent->refresh()->nisn);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('student_class_memberships', 0);
    }

    #[Test]
    public function temporary_identity_distinguishes_unverified_provisional_data_from_a_missing_nisn(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $service = app(StudentIdentityService::class);
        $temporary = $service->createTemporary('0012345678', 'Nama Masukan', $teacher);

        $missing = $service->reconcile($temporary, $teacher);
        $this->assertStringContainsString('NISN tidak ditemukan', $missing->result);
        $this->assertNull($temporary->refresh()->reconciled_student_id);

        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Nama Persiapan',
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $unverified = $service->reconcile($temporary, $teacher);
        $this->assertStringContainsString('belum terverifikasi Dapodik', $unverified->result);
        $this->assertNull($temporary->refresh()->reconciled_student_id);

        $student->update([
            'dapodik_id' => 'student-official',
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now(),
        ]);
        $verified = $service->reconcile($temporary, $teacher);
        $this->assertStringContainsString('berhasil ditautkan', $verified->result);
        $this->assertSame($student->id, $temporary->refresh()->reconciled_student_id);
    }

    #[Test]
    public function coordinator_activation_requires_complete_single_teacher_assignments_and_controls_teacher_scope(): void
    {
        $this->travelTo('2027-07-01 09:00:00');
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $coordinator = $this->userWithRole('koordinator_bk');
        $teacher = $this->userWithRole('guru_bk');
        $otherTeacher = $this->userWithRole('guru_bk');
        $oldYear = AcademicYear::query()->create([
            'dapodik_id' => 'old-year',
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
            'master_source' => AcademicYear::MASTER_SOURCE_DAPODIK,
        ]);
        $year = $this->prepareYear($service, $admin);
        $service->importRoster($year, $this->csv(implode("\n", [
            'nisn,nama,rombel,tahun_pelajaran',
            '0012345678,Murid Satu,X RPL 1,2027/2028',
            '0098765432,Murid Dua,X RPL 2,2027/2028',
        ])), $admin);
        $classes = Classroom::query()->where('academic_year_id', $year->id)->orderBy('name')->get();
        $student = Student::query()->where('nisn', '0012345678')->firstOrFail();

        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classes[0]->id,
            'academic_year_id' => $year->id,
            'assigned_by' => $coordinator->id,
        ]);

        $this->assertFalse(Student::query()->forActiveTeacherAssignment($teacher)->whereKey($student)->exists());
        $this->assertValidationError(fn () => $service->activate($year, $coordinator), 'assignments');
        $this->assertTrue($oldYear->refresh()->is_active);
        $this->assertFalse($year->refresh()->is_active);

        TeacherAssignment::query()->create([
            'user_id' => $otherTeacher->id,
            'classroom_id' => $classes[1]->id,
            'academic_year_id' => $year->id,
            'assigned_by' => $coordinator->id,
        ]);

        try {
            $service->activate($year, $admin);
            $this->fail('Admin IT dapat mengaktifkan tahun ajaran.');
        } catch (AuthorizationException) {
            $this->assertFalse($year->refresh()->is_active);
        }

        $activated = $service->activate($year, $coordinator);

        $this->assertTrue($activated->is_active);
        $this->assertSame($coordinator->id, $activated->activated_by);
        $this->assertNotNull($activated->activated_at);
        $this->assertSame(AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL, $activated->master_source);
        $this->assertFalse($oldYear->refresh()->is_active);
        $this->assertSame(AcademicYear::MASTER_SOURCE_DAPODIK, $oldYear->master_source);
        $this->assertTrue(Student::query()->forActiveTeacherAssignment($teacher)->whereKey($student)->exists());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic_year.activated',
            'auditable_id' => $year->id,
            'actor_id' => $coordinator->id,
        ]);
    }

    #[Test]
    public function preparation_endpoints_enforce_roles_input_and_year_classroom_pairs(): void
    {
        $this->travelTo('2027-07-01 09:00:00');
        $admin = $this->userWithRole('admin_it');
        $inactiveAdmin = $this->userWithRole('admin_it', false);
        $coordinator = $this->userWithRole('koordinator_bk');
        $inactiveCoordinator = $this->userWithRole('koordinator_bk', false);
        $teacher = $this->userWithRole('guru_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $payload = [
            'name' => '2027/2028',
            'preparation_reference' => 'SK Kepala Sekolah 001/2027',
        ];

        $this->post(route('data-master.academic-years.store'), $payload)->assertRedirect(route('login'));
        foreach ([$teacher, $coordinator, $waka] as $unauthorized) {
            $this->actingAs($unauthorized)
                ->post(route('data-master.academic-years.store'), $payload)
                ->assertForbidden();
        }
        $this->actingAs($inactiveAdmin)
            ->post(route('data-master.academic-years.store'), $payload)
            ->assertRedirect(route('login'));

        $this->actingAs($admin)
            ->from(route('data-master.index'))
            ->post(route('data-master.academic-years.store'), [
                ...$payload,
                'name' => 'tahun baru',
                'preparation_reference' => '',
            ])
            ->assertSessionHasErrors(['name', 'preparation_reference']);

        $this->actingAs($admin)
            ->post(route('data-master.academic-years.store'), $payload)
            ->assertRedirect(route('data-master.index'));
        $year = AcademicYear::query()->where('name', '2027/2028')->firstOrFail();

        $this->app['auth']->guard()->logout();
        $this->post(route('data-master.roster-imports.store'), [
            'file' => $this->validCsv(),
        ])->assertRedirect(route('login'));
        foreach ([$teacher, $coordinator, $waka] as $unauthorized) {
            $this->actingAs($unauthorized)
                ->post(route('data-master.roster-imports.store'), ['file' => $this->validCsv()])
                ->assertForbidden();
        }
        $this->actingAs($inactiveAdmin)
            ->post(route('data-master.roster-imports.store'), ['file' => $this->validCsv()])
            ->assertRedirect(route('login'));
        $this->actingAs($admin)
            ->from(route('data-master.index'))
            ->post(route('data-master.roster-imports.store'), [])
            ->assertSessionHasErrors('file');
        $this->actingAs($admin)
            ->post(route('data-master.roster-imports.store'), ['file' => $this->validCsv()])
            ->assertRedirect(route('data-master.index'));

        $this->app['auth']->guard()->logout();
        $this->post(route('data-master.roster-imports.preview'), [
            'api_url' => 'https://8.8.8.8/siswa',
        ])->assertRedirect(route('login'));
        foreach ([$teacher, $coordinator, $waka] as $unauthorized) {
            $this->actingAs($unauthorized)
                ->postJson(route('data-master.roster-imports.preview'), [
                    'api_url' => 'https://8.8.8.8/siswa',
                ])
                ->assertForbidden();
        }
        $this->actingAs($inactiveAdmin)
            ->post(route('data-master.roster-imports.preview'), [
                'api_url' => 'https://8.8.8.8/siswa',
            ])
            ->assertRedirect(route('login'));
        $this->actingAs($admin)
            ->postJson(route('data-master.roster-imports.preview'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('api_url');

        $this->app['auth']->guard()->logout();
        $this->post(route('data-master.academic-years.roster-imports.store', $year), [
            'file' => $this->validCsv(),
        ])->assertRedirect(route('login'));
        foreach ([$teacher, $coordinator, $waka] as $unauthorized) {
            $this->actingAs($unauthorized)
                ->post(route('data-master.academic-years.roster-imports.store', $year), ['file' => $this->validCsv()])
                ->assertForbidden();
        }
        $this->actingAs($inactiveAdmin)
            ->post(route('data-master.academic-years.roster-imports.store', $year), ['file' => $this->validCsv()])
            ->assertRedirect(route('login'));
        $this->actingAs($admin)
            ->post(route('data-master.academic-years.roster-imports.store', 999999), ['file' => $this->validCsv()])
            ->assertNotFound();
        $this->actingAs($admin)
            ->from(route('data-master.index'))
            ->post(route('data-master.academic-years.roster-imports.store', $year), [
                'file' => UploadedFile::fake()->create('terlalu-besar.csv', 2049, 'text/csv'),
            ])
            ->assertSessionHasErrors('file');
        $this->actingAs($admin)
            ->post(route('data-master.academic-years.roster-imports.store', $year), ['file' => $this->validCsv()])
            ->assertRedirect(route('data-master.index'));

        $classroom = Classroom::query()->where('academic_year_id', $year->id)->firstOrFail();
        $this->actingAs($coordinator)
            ->get(route('assignments.classes.index', ['academic_year_id' => $year->id]))
            ->assertOk()
            ->assertSee('Kesiapan Aktivasi')
            ->assertSee('Aktifkan Tahun Ajaran')
            ->assertSee('type="button" disabled', false);
        $otherYear = AcademicYear::query()->create([
            'name' => '2028/2029',
            'starts_on' => '2028-07-01',
            'ends_on' => '2029-06-30',
            'is_active' => false,
        ]);
        $this->actingAs($coordinator)
            ->from(route('assignments.classes.manage', ['academic_year_id' => $otherYear->id]))
            ->post(route('assignments.classes.store'), [
                'user_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => $otherYear->id,
                'effective_date' => '2028-07-01',
            ])
            ->assertSessionHasErrors('academic_year_id');

        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'assigned_by' => $coordinator->id,
        ]);
        $this->actingAs($coordinator)
            ->get(route('assignments.classes.index', ['academic_year_id' => $year->id]))
            ->assertOk()
            ->assertSee('Aktifkan Tahun Ajaran');
        $this->app['auth']->guard()->logout();
        $this->post(route('assignments.academic-years.activate', $year))->assertRedirect(route('login'));
        foreach ([$teacher, $waka, $admin] as $unauthorized) {
            $this->actingAs($unauthorized)
                ->post(route('assignments.academic-years.activate', $year))
                ->assertForbidden();
        }
        $this->actingAs($inactiveCoordinator)
            ->post(route('assignments.academic-years.activate', $year))
            ->assertRedirect(route('login'));
        $this->actingAs($coordinator)
            ->post(route('assignments.academic-years.activate', $year))
            ->assertRedirect(route('assignments.classes.manage', ['academic_year_id' => $year->id]));
        $this->assertTrue($year->refresh()->is_active);

        $this->actingAs($admin)
            ->from(route('data-master.index'))
            ->post(route('data-master.academic-years.roster-imports.store', $year), ['file' => $this->validCsv()])
            ->assertSessionHasErrors('academic_year');
    }

    #[Test]
    public function provisional_students_become_usable_for_real_bk_actions_only_after_activation_and_only_in_teacher_scope(): void
    {
        $this->travelTo('2027-07-10 09:00:00');
        $preparation = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $coordinator = $this->userWithRole('koordinator_bk');
        $assignedTeacher = $this->userWithRole('guru_bk');
        $otherTeacher = $this->userWithRole('guru_bk');
        $year = $this->prepareYear($preparation, $admin);
        $confirmedStudent = Student::query()->create([
            'dapodik_id' => 'dapodik-student-existing',
            'nisn' => '0012345678',
            'name' => 'Murid Terverifikasi Utama',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now()->subYear(),
        ]);
        $preparation->importRoster($year, $this->csv(implode("\n", [
            'nisn,nama,rombel,tahun_pelajaran',
            '0012345678,Nama CSV Tidak Menimpa,X RPL 1,2027/2028',
            '0098765432,Murid Kelas Lain,X RPL 2,2027/2028',
        ])), $admin);
        $classes = Classroom::query()->where('academic_year_id', $year->id)->orderBy('name')->get();
        $student = Student::query()->where('nisn', '0012345678')->firstOrFail();
        $this->assertSame($confirmedStudent->id, $student->id);
        $this->assertSame(Student::MASTER_SOURCE_DAPODIK, $student->master_source);
        $this->assertSame(
            StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL,
            StudentClassMembership::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $year->id)
                ->sole()
                ->master_source,
        );
        TeacherAssignment::query()->create([
            'user_id' => $assignedTeacher->id,
            'classroom_id' => $classes[0]->id,
            'academic_year_id' => $year->id,
            'assigned_by' => $coordinator->id,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $otherTeacher->id,
            'classroom_id' => $classes[1]->id,
            'academic_year_id' => $year->id,
            'assigned_by' => $coordinator->id,
        ]);

        $casePayload = $this->bkCasePayload($student);
        $consultationPayload = $this->consultationPayload($student);
        $achievementPayload = $this->achievementPayload($student);
        $this->assertValidationError(fn () => app(CaseService::class)->createCase($casePayload, $assignedTeacher), 'student_id');
        $this->assertValidationError(fn () => app(ConsultationService::class)->create($consultationPayload, $assignedTeacher), 'student_id');
        $this->assertValidationError(fn () => app(AchievementService::class)->create($achievementPayload, $assignedTeacher), 'student_id');
        $this->actingAs($assignedTeacher)->get(route('students.show', $student))->assertForbidden();

        $preparation->activate($year, $coordinator);

        $this->actingAs($assignedTeacher)->get(route('students.index', ['search' => 'Murid Terverifikasi Utama']))
            ->assertOk()
            ->assertSee('Murid Terverifikasi Utama')
            ->assertSee('>Sementara</span>', false);
        $this->actingAs($assignedTeacher)->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('>Sementara</span>', false);
        $this->actingAs($assignedTeacher)->get(route('cases.create'))
            ->assertOk()
            ->assertSee('Murid Terverifikasi Utama')
            ->assertSee('Catat Permasalahan');
        $this->actingAs($assignedTeacher)->get(route('consultations.create'))
            ->assertOk()
            ->assertSee('Murid Terverifikasi Utama')
            ->assertSee('Permasalahan');
        $this->actingAs($assignedTeacher)->get(route('achievements.create'))
            ->assertOk()
            ->assertSee('Murid Terverifikasi Utama')
            ->assertSee('Catat Prestasi');

        $case = app(CaseService::class)->createCase($casePayload, $assignedTeacher);
        $this->actingAs($assignedTeacher)->get(route('cases.show', $case))->assertOk();
        $this->actingAs($otherTeacher)->get(route('cases.show', $case))->assertForbidden();
        $caseUpdate = [
            'initial_info' => $case->initial_info,
            'initial_action' => $case->initial_action,
            'resolution_summary' => 'Pendampingan sudah selesai.',
            'action' => 'complete',
            'expected_updated_at' => $case->updated_at->toISOString(),
        ];
        $this->assertValidationError(
            fn () => app(CaseService::class)->update($case, $caseUpdate, $otherTeacher),
            'case',
        );
        app(CaseService::class)->update($case, $caseUpdate, $assignedTeacher);
        $this->assertValidationError(fn () => app(CaseService::class)->createCase($casePayload, $otherTeacher), 'student_id');

        $consultation = app(ConsultationService::class)->create($consultationPayload, $assignedTeacher);
        $this->assertInstanceOf(Consultation::class, $consultation);
        $this->actingAs($assignedTeacher)->get(route('consultations.show', $consultation))->assertOk();
        $this->actingAs($assignedTeacher)->get(route('consultations.edit', $consultation))
            ->assertOk()
            ->assertSee('Permasalahan');
        $this->actingAs($otherTeacher)->get(route('consultations.show', $consultation))->assertForbidden();
        app(ConsultationService::class)->update($consultation, [
            ...$consultationPayload,
            'problem' => 'Permasalahan diperbarui',
            'expected_updated_at' => $consultation->updated_at->toISOString(),
        ], $assignedTeacher);
        $this->assertSame('Permasalahan diperbarui', $consultation->refresh()->problem);
        $this->assertValidationError(
            fn () => app(ConsultationService::class)->create($consultationPayload, $otherTeacher),
            'student_id',
        );
        $this->assertValidationError(
            fn () => app(ConsultationService::class)->update($consultation, $consultationPayload, $otherTeacher),
            'consultation',
        );

        $achievement = app(AchievementService::class)->create($achievementPayload, $assignedTeacher);
        $this->assertInstanceOf(Achievement::class, $achievement);
        $this->actingAs($assignedTeacher)->get(route('achievements.edit', $achievement))
            ->assertOk()
            ->assertSee('Edit Prestasi');
        $this->assertValidationError(
            fn () => app(AchievementService::class)->create($achievementPayload, $otherTeacher),
            'student_id',
        );
    }

    /** @return array<string, mixed> */
    private function bkCasePayload(Student $student): array
    {
        return [
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2027-07-10',
            'initial_info' => 'Informasi awal layanan.',
            'initial_action' => 'Asesmen awal.',
        ];
    }

    /** @return array<string, mixed> */
    private function consultationPayload(Student $student): array
    {
        return [
            'student_id' => $student->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'session_date' => '2027-07-10',
            'problem' => 'Penyesuaian diri',
            'handling' => 'Konseling individual',
            'result' => 'Murid menyepakati langkah perbaikan.',
        ];
    }

    /** @return array<string, mixed> */
    private function achievementPayload(Student $student): array
    {
        return [
            'student_id' => $student->id,
            'type_id' => $this->reference('achievement_type', 'akademik')->id,
            'level_id' => $this->reference('achievement_level', 'sekolah')->id,
            'activity_name' => 'Lomba Keterampilan',
            'organizer' => 'Sekolah',
            'achievement_date' => '2027-07-09',
            'result' => 'Juara I',
            'evidence_reference' => 'Arsip sekolah',
            'evidence_description' => null,
            'notes' => null,
        ];
    }

    private function prepareYear(AcademicYearPreparationService $service, User $admin): AcademicYear
    {
        return $service->prepareAcademicYear([
            'name' => '2027/2028',
            'preparation_reference' => 'Kalender Pendidikan 2027/2028',
        ], $admin);
    }

    private function reference(string $category, string $code): ReferenceValue
    {
        return ReferenceValue::query()
            ->where('category', $category)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function validCsv(): UploadedFile
    {
        return $this->csv("nisn,nama,rombel,tahun_pelajaran\n0012345678,Nama Murid,X RPL 1,2027/2028");
    }

    private function resetHttpFactory(): void
    {
        $factory = new HttpFactory($this->app['events']);
        $this->app->instance(HttpFactory::class, $factory);
        Http::clearResolvedInstance(HttpFactory::class);
    }

    private function csv(string $content, string $name = 'roster.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function userWithRole(string $slug, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }

    private function caseFor(Student $student, User $creator): BkCase
    {
        return BkCase::query()->create([
            'registration_number' => 'BK-PRESERVED-001',
            'student_id' => $student->id,
            'case_source_id' => ReferenceValue::query()->where('category', 'case_source')->firstOrFail()->id,
            'service_field_id' => ReferenceValue::query()->where('category', 'service_field')->firstOrFail()->id,
            'status_id' => ReferenceValue::query()->where('category', 'case_status')->firstOrFail()->id,
            'service_date' => '2027-07-01',
            'initial_info' => 'Riwayat BK yang harus tetap terhubung.',
            'initial_action' => 'Tindakan awal.',
            'created_by' => $creator->id,
        ]);
    }

    private function assertValidationError(callable $operation, string $field): void
    {
        try {
            $operation();
            $this->fail(sprintf('Validasi %s tidak dijalankan.', $field));
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }

    private function assertSafeFailedImportAudit(AuditLog $audit): void
    {
        $this->assertNull($audit->before_values);
        $this->assertSame(
            ['failure_code' => $audit->after_values['failure_code']],
            $audit->after_values,
        );
        $serializedAudit = json_encode(
            [$audit->summary, $audit->before_values, $audit->after_values],
            JSON_THROW_ON_ERROR,
        );

        foreach (['0012345678', '0000000002', 'NAMA_RAHASIA', 'RPL RAHASIA', 'Murid Pasangan Silang', 'Murid Baru Tidak Boleh Tersimpan', 'Murid Existing', 'X RPL 1', 'X RPL 2', 'X RPL 3', 'nisn,nama,rombel'] as $sensitiveValue) {
            $this->assertStringNotContainsString($sensitiveValue, $serializedAudit);
        }
    }
}
