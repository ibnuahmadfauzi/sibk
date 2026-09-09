<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AcademicYearPreparationService;
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

    private function prepareYear(AcademicYearPreparationService $service, User $admin): AcademicYear
    {
        return $service->prepareAcademicYear([
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'preparation_reference' => 'Kalender Pendidikan 2027/2028',
        ], $admin);
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
}
