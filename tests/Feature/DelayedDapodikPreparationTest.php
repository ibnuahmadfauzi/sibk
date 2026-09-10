<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AcademicYearPreparationService;
use App\Services\AchievementService;
use App\Services\CaseService;
use App\Services\ConsultationService;
use App\Services\ProvisionalRosterCsvParser;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'preparation_reference' => 'Kalender Pendidikan 2027/2028',
        ], $admin);

        $this->assertFalse($year->is_active);
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
    public function preparation_requires_an_active_admin_unique_name_unique_period_and_official_reference(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $inactiveAdmin = $this->userWithRole('admin_it', false);
        $coordinator = $this->userWithRole('koordinator_bk');
        $payload = [
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
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
            fn () => $service->prepareAcademicYear([...$payload, 'starts_on' => '2028-07-01', 'ends_on' => '2029-06-30'], $admin),
            'name',
        );
        $this->assertValidationError(
            fn () => $service->prepareAcademicYear([...$payload, 'name' => 'Tahun Duplikat'], $admin),
            'period',
        );
        $this->assertValidationError(
            fn () => $service->prepareAcademicYear([...$payload, 'name' => '2028/2029', 'starts_on' => '2028-07-01', 'ends_on' => '2029-06-30', 'preparation_reference' => ''], $admin),
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
    public function strict_csv_parser_accepts_utf8_bom_and_exactly_five_thousand_rows(): void
    {
        $parser = app(ProvisionalRosterCsvParser::class);
        $this->assertTrue(method_exists($parser, 'parse'));
        $rows = [];
        for ($number = 1; $number <= 5000; $number++) {
            $rows[] = sprintf('%010d,Nama Murid %d,X RPL 1', $number, $number);
        }

        $parsed = $parser->parse($this->csv("\xEF\xBB\xBFnisn,nama,rombel\n".implode("\n", $rows)));

        $this->assertCount(5000, $parsed);
        $this->assertSame([
            'nisn' => '0000000001',
            'name' => 'Nama Murid 1',
            'classroom' => 'X RPL 1',
        ], $parsed[0]);
    }
    #[Test]
public function strict_csv_parser_ignores_blank_rows_between_and_after_records(): void
{
    $parsed = app(ProvisionalRosterCsvParser::class)->parse(
        $this->csv(
            "nisn,nama,rombel\n"
            ."0012345678,Nama Murid A,X RPL 1\n"
            ."\n"
            ."0098765432,Nama Murid B,X RPL 2\n"
            ."\n"
        ),
    );

    $this->assertSame([
        [
            'nisn' => '0012345678',
            'name' => 'Nama Murid A',
            'classroom' => 'X RPL 1',
        ],
        [
            'nisn' => '0098765432',
            'name' => 'Nama Murid B',
            'classroom' => 'X RPL 2',
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
            'content' => "nisn,nama,rombel\n0012345678,Nama,X RPL 1",
        ]];
        yield 'header tidak exact' => [[
            'content' => "nama,nisn,rombel\nNama,0012345678,X RPL 1",
        ]];
        yield 'tanpa baris data' => [[
            'content' => 'nisn,nama,rombel',
        ]];
        yield 'nisn bukan sepuluh digit' => [[
            'content' => "nisn,nama,rombel\n1234,Nama,X RPL 1",
        ]];
        yield 'nisn ganda' => [[
            'content' => "nisn,nama,rombel\n0012345678,Nama A,X RPL 1\n0012345678,Nama B,X RPL 2",
        ]];
        yield 'kolom kurang' => [[
            'content' => "nisn,nama,rombel\n0012345678,Nama",
        ]];
        yield 'kolom ekstra' => [[
            'content' => "nisn,nama,rombel\n0012345678,Nama,X RPL 1,Ekstra",
        ]];
        yield 'quote tidak sah di field tanpa enclosure' => [[
            'content' => "nisn,nama,rombel\n0012345678,Nama \"tidak sah\",X RPL 1",
        ]];
        yield 'formula pada nama' => [[
            'content' => "nisn,nama,rombel\n0012345678,=HYPERLINK(\"https://invalid.test\"),X RPL 1",
        ]];
        yield 'formula pada rombel' => [[
            'content' => "nisn,nama,rombel\n0012345678,Nama,+SUM(1)",
        ]];
        yield 'control character' => [[
            'content' => "nisn,nama,rombel\n0012345678,Nama\tMurid,X RPL 1",
        ]];
        yield 'control character di tepi field' => [[
            'content' => "nisn,nama,rombel\n0012345678,\tNama Murid,X RPL 1",
        ]];
        yield 'utf8 tidak valid' => [[
            'content' => "nisn,nama,rombel\n0012345678,Nama\xFF,X RPL 1",
        ]];
        yield 'bom utf16' => [[
            'content' => "\xFF\xFEn\x00i\x00s\x00n\x00",
        ]];
        yield 'lebih dari lima ribu baris' => [[
            'content' => "nisn,nama,rombel\n".implode("\n", array_map(
                static fn (int $number): string => sprintf('%010d,Nama %d,X RPL 1', $number, $number),
                range(1, 5001),
            )),
        ]];
        yield 'lebih dari dua mebibyte' => [[
            'content' => "nisn,nama,rombel\n0012345678,".str_repeat('A', (2 * 1024 * 1024) + 1).',X RPL 1',
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
            'effective_from' => $year->starts_on,
            'master_source' => StudentClassMembership::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => $confirmedAt,
        ]);
        $case = $this->caseFor($dapodikStudent, $admin);
        $csv = $this->csv(implode("\n", [
            'nisn,nama,rombel',
            '0012345678,Nama CSV Tidak Boleh Menimpa,X RPL 1',
            '0098765432,Nama CSV Lama Tidak Menimpa,X RPL 1',
            '0000000003,Murid Baru,X RPL 2',
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
            'effective_from' => $year->starts_on,
        ]);
        $conflictingFile = $this->csv(implode("\n", [
            'nisn,nama,rombel',
            '0000000002,Murid Baru,X RPL 2',
            '0012345678,Murid Existing,X RPL 3',
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
    public function data_master_only_offers_roster_import_for_provisional_years(): void
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
            ->assertSee(route('data-master.academic-years.roster-imports.store', $provisional), false)
            ->assertDontSee(route('data-master.academic-years.roster-imports.store', $dapodik), false)
            ->assertDontSee(route('data-master.academic-years.roster-imports.store', $legacy), false);
    }

    #[Test]
    public function malformed_csv_is_rejected_without_data_changes_and_leaves_only_a_sanitized_failure_audit(): void
    {
        $service = app(AcademicYearPreparationService::class);
        $admin = $this->userWithRole('admin_it');
        $year = $this->prepareYear($service, $admin);
        $unsafeCsv = $this->csv(implode("\n", [
            'nisn,nama,rombel',
            '0012345678,=NAMA_RAHASIA,X RPL RAHASIA',
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
            'effective_from' => $targetYear->starts_on,
        ]);
        $auditCountBefore = AuditLog::query()->count();

        $this->assertValidationError(
            fn () => $service->importRoster($targetYear, $this->csv(implode("\n", [
                'nisn,nama,rombel',
                '0000000002,Murid Baru Tidak Boleh Tersimpan,X RPL 2',
                '0012345678,Murid Pasangan Silang,X RPL 1',
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
    public function coordinator_activation_requires_complete_single_teacher_assignments_and_controls_teacher_scope(): void
    {
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
            'nisn,nama,rombel',
            '0012345678,Murid Satu,X RPL 1',
            '0098765432,Murid Dua,X RPL 2',
        ])), $admin);
        $classes = Classroom::query()->where('academic_year_id', $year->id)->orderBy('name')->get();
        $student = Student::query()->where('nisn', '0012345678')->firstOrFail();

        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classes[0]->id,
            'academic_year_id' => $year->id,
            'effective_from' => $year->starts_on,
            'decision_number' => 'SK-GURU-1',
            'assigned_by' => $coordinator->id,
        ]);

        $this->assertFalse(Student::query()->forActiveTeacherAssignment($teacher, '2027-07-01')->whereKey($student)->exists());
        $this->assertValidationError(fn () => $service->activate($year, $coordinator), 'assignments');
        $this->assertTrue($oldYear->refresh()->is_active);
        $this->assertFalse($year->refresh()->is_active);

        foreach ([$teacher, $otherTeacher] as $assignedTeacher) {
            TeacherAssignment::query()->create([
                'user_id' => $assignedTeacher->id,
                'classroom_id' => $classes[1]->id,
                'academic_year_id' => $year->id,
                'effective_from' => $year->starts_on,
                'decision_number' => 'SK-GANDA-'.$assignedTeacher->id,
                'assigned_by' => $coordinator->id,
            ]);
        }
        $this->assertValidationError(fn () => $service->activate($year, $coordinator), 'assignments');
        TeacherAssignment::query()->where('classroom_id', $classes[1]->id)->where('user_id', $otherTeacher->id)->delete();

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
        $this->assertTrue(Student::query()->forActiveTeacherAssignment($teacher, '2027-07-01')->whereKey($student)->exists());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic_year.activated',
            'auditable_id' => $year->id,
            'actor_id' => $coordinator->id,
        ]);
    }

    #[Test]
    public function preparation_endpoints_enforce_roles_input_and_year_classroom_pairs(): void
    {
        $admin = $this->userWithRole('admin_it');
        $inactiveAdmin = $this->userWithRole('admin_it', false);
        $coordinator = $this->userWithRole('koordinator_bk');
        $inactiveCoordinator = $this->userWithRole('koordinator_bk', false);
        $teacher = $this->userWithRole('guru_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $payload = [
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
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
                'starts_on' => 'tidak-valid',
                'preparation_reference' => '',
            ])
            ->assertSessionHasErrors(['name', 'starts_on', 'preparation_reference']);

        $this->actingAs($admin)
            ->post(route('data-master.academic-years.store'), $payload)
            ->assertRedirect(route('data-master.index'));
        $year = AcademicYear::query()->where('name', '2027/2028')->firstOrFail();

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
            ->get(route('assignments.classes.manage', ['academic_year_id' => $year->id]))
            ->assertOk()
            ->assertSee('Kesiapan Aktivasi')
            ->assertDontSee('Aktifkan Tahun Ajaran');
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
                'decision_number' => 'SK-SILANG',
                'effective_date' => '2028-07-01',
            ])
            ->assertSessionHasErrors('classroom_id');

        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => $year->starts_on,
            'decision_number' => 'SK-AKTIVASI',
            'assigned_by' => $coordinator->id,
        ]);
        $this->actingAs($coordinator)
            ->get(route('assignments.classes.manage', ['academic_year_id' => $year->id]))
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
            'nisn,nama,rombel',
            '0012345678,Nama CSV Tidak Menimpa,X RPL 1',
            '0098765432,Murid Kelas Lain,X RPL 2',
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
            'effective_from' => $year->starts_on,
            'decision_number' => 'SK-GURU-UTAMA',
            'assigned_by' => $coordinator->id,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $otherTeacher->id,
            'classroom_id' => $classes[1]->id,
            'academic_year_id' => $year->id,
            'effective_from' => $year->starts_on,
            'decision_number' => 'SK-GURU-LAIN',
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
            ->assertSee('— Sementara');
        $this->actingAs($assignedTeacher)->get(route('consultations.create'))
            ->assertOk()
            ->assertSee('Murid Terverifikasi Utama')
            ->assertSee('— Sementara');
        $this->actingAs($assignedTeacher)->get(route('achievements.create'))
            ->assertOk()
            ->assertSee('Murid Terverifikasi Utama')
            ->assertSee('— Sementara');

        $case = app(CaseService::class)->createCase($casePayload, $assignedTeacher);
        $this->actingAs($assignedTeacher)->get(route('cases.show', $case))->assertOk();
        $this->actingAs($otherTeacher)->get(route('cases.show', $case))->assertForbidden();
        $caseResolution = [
            'closed_at' => '2027-07-10',
            'final_result' => 'Selesai',
            'resolution_summary' => 'Pendampingan sudah selesai.',
        ];
        $this->assertValidationError(
            fn () => app(CaseService::class)->resolve($case, $caseResolution, $otherTeacher),
            'case',
        );
        app(CaseService::class)->resolve($case, $caseResolution, $assignedTeacher);
        $this->assertValidationError(fn () => app(CaseService::class)->createCase($casePayload, $otherTeacher), 'student_id');

        $consultation = app(ConsultationService::class)->create($consultationPayload, $assignedTeacher);
        $this->assertInstanceOf(Consultation::class, $consultation);
        $this->actingAs($assignedTeacher)->get(route('consultations.show', $consultation))->assertOk();
        $this->actingAs($assignedTeacher)->get(route('consultations.edit', $consultation))
            ->assertOk()
            ->assertSee('— Sementara');
        $this->actingAs($otherTeacher)->get(route('consultations.show', $consultation))->assertForbidden();
        app(ConsultationService::class)->update($consultation, [
            ...$consultationPayload,
            'topic' => 'Topik diperbarui',
        ], $assignedTeacher);
        $this->assertSame('Topik diperbarui', $consultation->refresh()->topic);
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
            ->assertSee('— Sementara');
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
            'case_id' => null,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('consultation_status', 'terlaksana')->id,
            'topic' => 'Penyesuaian diri',
            'referral_source' => 'Inisiatif murid',
            'session_date' => '2027-07-10',
            'starts_at' => '08:00',
            'ends_at' => '08:30',
            'follow_up_date' => null,
            'general_summary' => 'Ringkasan umum layanan.',
            'internal_note' => null,
            'sensitive_content' => null,
            'conclusion' => null,
            'follow_up_plan' => null,
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
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
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
        return $this->csv("nisn,nama,rombel\n0012345678,Nama Murid,X RPL 1");
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
