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
        $this->assertSame(52, ReferenceValue::query()->count());
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

    public function test_case_and_etatib_timestamp_changes_use_a_forward_migration(): void
    {
        $migrationPath = database_path(
            'migrations/2026_09_10_000100_relax_case_and_etatib_timestamps.php'
        );

        $this->assertFileExists($migrationPath);

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

            /*
            * Parent tables yang sudah tersedia sebelum migration
            * 2026_08_20_000700 dijalankan.
            */
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

            /*
            * Jalankan migration historis asli.
            * Test harus membuktikan upgrade dari schema lama,
            * bukan dari schema tiruan yang berbeda.
            */
            $historicalMigration = require database_path(
                'migrations/2026_08_20_000700_create_case_and_etatib_tables.php'
            );

            $historicalMigration->up();

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

            /*
            * Jalankan forward migration baru.
            */
            $migration = require $migrationPath;
            $migration->up();

            /*
            * Setelah upgrade, occurred_at boleh NULL
            * dan synced_at memperoleh default waktu saat ini.
            */
            DB::table('external_tatib_records')->insert([
                'id' => 2,
                'source_identifier' => 'new-2',
                'nisn' => '0098765432',
                'occurred_at' => null,
                'violation_type' => 'Data baru',
                'category' => 'Disiplin',
            ]);

            /*
            * coordinated_at juga boleh NULL setelah upgrade.
            */
            DB::table('case_coordinations')->insert([
                'id' => 2,
                'case_id' => 1,
                'waka_user_id' => 2,
                'status_id' => 3,
                'coordination_need' => 'Koordinasi baru.',
                'recorded_by' => 1,
                'coordinated_at' => null,
            ]);

            $newTatib = DB::table('external_tatib_records')
                ->where('id', 2)
                ->first();

            $this->assertNull($newTatib->occurred_at);
            $this->assertNotNull($newTatib->synced_at);

            $this->assertNull(
                DB::table('case_coordinations')
                    ->where('id', 2)
                    ->value('coordinated_at'),
            );

            /*
            * Data yang sudah ada sebelum upgrade harus tetap utuh.
            */
            $this->assertSame(
                '2026-08-20 08:00:00',
                DB::table('external_tatib_records')
                    ->where('id', 1)
                    ->value('occurred_at'),
            );

            $this->assertSame(
                '2026-08-20 09:00:00',
                DB::table('external_tatib_records')
                    ->where('id', 1)
                    ->value('synced_at'),
            );

            $this->assertSame(
                '2026-08-20 10:00:00',
                DB::table('case_coordinations')
                    ->where('id', 1)
                    ->value('coordinated_at'),
            );
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('migration_probe');
        }
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
