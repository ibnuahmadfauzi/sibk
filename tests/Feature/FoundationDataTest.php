<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StudentSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class FoundationDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_seeders_are_idempotent(): void
    {
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);

        $this->assertSame(4, Role::query()->count());
        $this->assertSame(53, ReferenceValue::query()->count());
        $this->assertSame(4, ReferenceValue::query()->forCategory('case_status')->count());
        $this->assertSame(6, ReferenceValue::query()->forCategory('achievement_type')->count());
        $this->assertSame(5, ReferenceValue::query()->forCategory('achievement_level')->count());
        $this->assertSame(3, ReferenceValue::query()->forCategory('achievement_verification_status')->count());
    }

    public function test_role_accounts_seeder_is_idempotent_and_assigns_one_expected_role(): void
    {
        $this->seed([RoleSeeder::class, AccountSeeder::class]);
        $this->seed([RoleSeeder::class, AccountSeeder::class]);

        $expectedAccounts = [
            'guru.bk@ruangbk.test' => 'guru_bk',
            'koordinator.bk@ruangbk.test' => 'koordinator_bk',
            'waka.kesiswaan@ruangbk.test' => 'waka_kesiswaan',
            'admin.it@ruangbk.test' => 'admin_it',
        ];

        $this->assertSame(4, User::query()->whereIn('email', array_keys($expectedAccounts))->count());
        foreach ($expectedAccounts as $email => $role) {
            $user = User::query()->where('email', $email)->with('roles')->firstOrFail();
            $this->assertTrue($user->is_active);
            $this->assertSame([$role], $user->roles->pluck('slug')->all());
            $this->assertTrue(password_verify('RuangBK123!', $user->password));
        }
    }

    public function test_master_cache_keeps_class_membership_history(): void
    {
        $year = AcademicYear::query()->create(['name' => '2026/2027', 'is_active' => true]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'X RPL 1',
            'grade_level' => 10,
        ]);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid A']);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-15',
            'effective_until' => '2026-12-31',
        ]);

        $this->assertSame(1, $student->classMemberships()->effectiveOn('2026-08-20')->count());
        $this->assertSame(0, $student->classMemberships()->effectiveOn('2027-01-01')->count());
    }

    public function test_demo_student_seeder_is_idempotent(): void
    {
        $this->seed(StudentSeeder::class);
        $firstStudentIds = Student::query()
            ->where('dapodik_id', 'like', 'SEED-STUDENT-%')
            ->orderBy('dapodik_id')
            ->pluck('id')
            ->all();
        $firstMembershipIds = StudentClassMembership::query()
            ->where('dapodik_id', 'like', 'SEED-MEMBERSHIP-%')
            ->orderBy('dapodik_id')
            ->pluck('id')
            ->all();

        $this->seed(StudentSeeder::class);

        $this->assertCount(35, $firstStudentIds);
        $this->assertCount(35, $firstMembershipIds);
        $this->assertSame(
            $firstStudentIds,
            Student::query()
                ->where('dapodik_id', 'like', 'SEED-STUDENT-%')
                ->orderBy('dapodik_id')
                ->pluck('id')
                ->all(),
        );
        $this->assertSame(
            $firstMembershipIds,
            StudentClassMembership::query()
                ->where('dapodik_id', 'like', 'SEED-MEMBERSHIP-%')
                ->orderBy('dapodik_id')
                ->pluck('id')
                ->all(),
        );
        $this->assertSame(6, Classroom::query()->where('dapodik_id', 'like', 'SEED-CLASS-%')->count());
        $this->assertSame(1, AcademicYear::query()->where('dapodik_id', 'SEED-ACADEMIC-YEAR')->count());
    }

    public function test_demo_year_does_not_replace_an_official_active_year(): void
    {
        $official = AcademicYear::query()->create([
            'dapodik_id' => 'OFFICIAL-2026',
            'name' => '2026/2027',
            'is_active' => true,
        ]);

        $this->seed(StudentSeeder::class);

        $this->assertTrue($official->fresh()?->is_active);
        $this->assertFalse(
            AcademicYear::query()->where('dapodik_id', 'SEED-ACADEMIC-YEAR')->firstOrFail()->is_active,
        );
    }

    public function test_historical_case_and_etatib_migration_preserves_the_original_timestamp_contract(): void
    {
        $connection = 'historical_case_etatib_probe';
        $originalConnection = DB::getDefaultConnection();
        $this->configureSqliteMigrationProbe($connection);

        try {
            DB::setDefaultConnection($connection);
            $this->createCaseAndEtatibMigrationParents();
            $historicalMigration = require database_path(
                'migrations/2026_08_20_000700_create_case_and_etatib_tables.php',
            );
            $historicalMigration->up();

            $tatibColumns = $this->migrationColumns('external_tatib_records');
            $coordinationColumns = $this->migrationColumns('case_coordinations');

            $this->assertTrue($tatibColumns['occurred_at']['nullable']);
            $this->assertTrue($this->usesCurrentTimestamp($tatibColumns['synced_at']['default']));
            $this->assertTrue($coordinationColumns['coordinated_at']['nullable']);
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge($connection);
        }
    }

    public function test_case_and_etatib_timestamp_forward_migration_converges_fresh_and_transient_sqlite_schemas(): void
    {
        $fresh = $this->timestampMigrationContract('case_etatib_fresh_probe', false);
        $upgraded = $this->timestampMigrationContract('case_etatib_upgrade_probe', true);

        $this->assertSame($fresh, $upgraded);
    }

    private function configureSqliteMigrationProbe(string $connection): void
    {
        config()->set('database.connections.'.$connection, [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge($connection);
    }

    private function createCaseAndEtatibMigrationParents(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('temporary_students', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('references', function (Blueprint $table): void {
            $table->id();
        });
    }

    private function createTransientCaseAndEtatibTimestampSchema(): void
    {
        Schema::create('external_tatib_records', function (Blueprint $table): void {
            $table->id();
            $table->string('source_identifier')->unique();
            $table->string('nisn', 20)->index();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->string('violation_type', 200);
            $table->string('category', 100);
            $table->integer('points')->default(0);
            $table->string('source_status', 100)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('source_synced_at')->nullable();
            $table->timestamp('synced_at');
            $table->timestamps();
        });
        Schema::create('cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_source_id')->constrained('references')->restrictOnDelete();
            $table->foreignId('service_field_id')->constrained('references')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('references')->restrictOnDelete();
            $table->date('service_date');
            $table->text('initial_info');
            $table->text('initial_action');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('case_coordinations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('waka_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('references')->restrictOnDelete();
            $table->text('coordination_need');
            $table->text('result')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('coordinated_at');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['case_id', 'waka_user_id', 'status_id'], 'case_coordination_access_index');
        });
    }

    /** @return array<string, array{name: string, nullable: bool, default: mixed}> */
    private function migrationColumns(string $table): array
    {
        $columns = [];
        foreach (Schema::getColumns($table) as $column) {
            $columns[$column['name']] = $column;
        }

        return $columns;
    }

    private function usesCurrentTimestamp(mixed $default): bool
    {
        return is_string($default)
            && preg_replace('/[()\\s]/', '', strtoupper($default)) === 'CURRENT_TIMESTAMP';
    }

    /** @return array<string, mixed> */
    private function timestampMigrationContract(string $connection, bool $transientTimestampSchema): array
    {
        $originalConnection = DB::getDefaultConnection();
        $this->configureSqliteMigrationProbe($connection);

        try {
            DB::setDefaultConnection($connection);
            $this->createCaseAndEtatibMigrationParents();
            if ($transientTimestampSchema) {
                $this->createTransientCaseAndEtatibTimestampSchema();
            } else {
                $historicalMigration = require database_path(
                    'migrations/2026_08_20_000700_create_case_and_etatib_tables.php',
                );
                $historicalMigration->up();
            }

            Schema::table('case_coordinations', function (Blueprint $table): void {
                $table->index('coordination_need', 'case_coordination_custom_probe_index');
            });
            $this->insertCaseAndEtatibMigrationParents();

            DB::table('external_tatib_records')->insert([
                'id' => 1,
                'source_identifier' => 'legacy-1',
                'nisn' => '0012345678',
                'occurred_at' => '2026-08-20 08:00:00',
                'violation_type' => 'Data lama',
                'category' => 'Disiplin',
                'synced_at' => '2026-08-20 09:00:00',
            ]);
            DB::table('case_coordinations')->insert([
                'id' => 1,
                'case_id' => 1,
                'waka_user_id' => 2,
                'status_id' => 3,
                'coordination_need' => 'Koordinasi lama.',
                'recorded_by' => 1,
                'coordinated_at' => '2026-08-20 10:00:00',
            ]);

            $migration = require database_path(
                'migrations/2026_09_10_000100_relax_case_and_etatib_timestamps.php',
            );
            $migration->up();

            DB::table('external_tatib_records')->insert([
                'id' => 2,
                'source_identifier' => 'new-2',
                'nisn' => '0098765432',
                'occurred_at' => null,
                'violation_type' => 'Data baru',
                'category' => 'Disiplin',
            ]);
            DB::table('case_coordinations')->insert([
                'id' => 2,
                'case_id' => 1,
                'waka_user_id' => 2,
                'status_id' => 3,
                'coordination_need' => 'Koordinasi baru.',
                'recorded_by' => 1,
                'coordinated_at' => null,
            ]);

            $contract = $this->currentTimestampMigrationContract();
            $this->assertTrue($contract['custom_index']);
            $this->assertSame(['cases', 'references', 'users', 'users'], $contract['foreign_tables']);

            $migration->down();
            $this->assertSame($contract, $this->currentTimestampMigrationContract());

            return $contract;
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge($connection);
        }
    }

    private function insertCaseAndEtatibMigrationParents(): void
    {
        DB::table('users')->insert([
            ['id' => 1],
            ['id' => 2],
        ]);
        DB::table('references')->insert([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);
        DB::table('cases')->insert([
            'id' => 1,
            'case_source_id' => 1,
            'service_field_id' => 2,
            'status_id' => 3,
            'service_date' => '2026-08-20',
            'initial_info' => 'Informasi lama.',
            'initial_action' => 'Tindakan lama.',
            'created_by' => 1,
        ]);
    }

    /** @return array{columns: array<string, bool>, new_values: array<string, bool>, legacy_values: array<string, string>, custom_index: bool, foreign_tables: list<string>} */
    private function currentTimestampMigrationContract(): array
    {
        $tatibColumns = $this->migrationColumns('external_tatib_records');
        $coordinationColumns = $this->migrationColumns('case_coordinations');
        $foreignTables = array_map(
            static fn (array $foreignKey): string => $foreignKey['foreign_table'],
            Schema::getForeignKeys('case_coordinations'),
        );
        sort($foreignTables);

        return [
            'columns' => [
                'occurred_at_nullable' => $tatibColumns['occurred_at']['nullable'],
                'synced_at_uses_current' => $this->usesCurrentTimestamp($tatibColumns['synced_at']['default']),
                'coordinated_at_nullable' => $coordinationColumns['coordinated_at']['nullable'],
            ],
            'new_values' => [
                'occurred_at_is_null' => DB::table('external_tatib_records')->where('id', 2)->value('occurred_at') === null,
                'synced_at_is_present' => DB::table('external_tatib_records')->where('id', 2)->value('synced_at') !== null,
                'coordinated_at_is_null' => DB::table('case_coordinations')->where('id', 2)->value('coordinated_at') === null,
            ],
            'legacy_values' => [
                'occurred_at' => (string) DB::table('external_tatib_records')->where('id', 1)->value('occurred_at'),
                'synced_at' => (string) DB::table('external_tatib_records')->where('id', 1)->value('synced_at'),
                'coordinated_at' => (string) DB::table('case_coordinations')->where('id', 1)->value('coordinated_at'),
            ],
            'custom_index' => $this->hasMigrationIndex('case_coordinations', 'case_coordination_custom_probe_index'),
            'foreign_tables' => $foreignTables,
        ];
    }

    private function hasMigrationIndex(string $table, string $name): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['name'] === $name) {
                return true;
            }
        }

        return false;
    }

    public function test_audit_log_rejects_updates_and_deletes(): void
    {
        $user = User::factory()->create();
        $audit = AuditLog::query()->create([
            'actor_id' => $user->id,
            'action' => 'test.created',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'summary' => 'Audit pengujian.',
        ]);

        try {
            $audit->update(['summary' => 'Diubah']);
            $this->fail('Audit log seharusnya menolak perubahan.');
        } catch (LogicException $exception) {
            $this->assertSame('Jejak audit tidak dapat diubah.', $exception->getMessage());
        }

        try {
            $audit->delete();
            $this->fail('Audit log seharusnya menolak penghapusan.');
        } catch (LogicException $exception) {
            $this->assertSame('Jejak audit tidak dapat dihapus.', $exception->getMessage());
        }

        $this->assertDatabaseHas('audit_logs', ['id' => $audit->id, 'summary' => 'Audit pengujian.']);
    }
}
