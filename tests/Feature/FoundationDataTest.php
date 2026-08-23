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
use Illuminate\Foundation\Testing\RefreshDatabase;
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
