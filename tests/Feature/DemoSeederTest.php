<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Consultation;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

final class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-21 10:00:00');
        config()->set('sibk.demo.enabled', true);
        config()->set('sibk.demo.account_password', 'SecretDemo2026!');
    }

    public function test_creates_synthetic_demo_and_does_not_reset_existing_password_on_repeat(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertSame(4, User::query()->count());
        $this->assertSame(3, Student::query()->count());
        $this->assertSame(2, BkCase::query()->count());
        $this->assertSame(1, Consultation::query()->count());
        $this->assertSame('2026/2027', AcademicYear::query()->sole()->name);
        $this->assertSame(1, Student::query()->forActiveTeacherAssignment(
            User::query()->where('email', 'guru@demo.ruangbk.test')->firstOrFail(),
        )->where('name', 'Murid Demo Alya')->count());
        foreach (['admin' => 'admin_it', 'guru' => 'guru_bk', 'koordinator' => 'koordinator_bk', 'waka' => 'waka_kesiswaan'] as $account => $role) {
            $user = User::query()->where('email', $account.'@demo.ruangbk.test')->firstOrFail();
            $this->assertTrue($user->hasRole($role));
            $this->actingAs($user)->get(route('dashboard.preview'))->assertOk();
        }

        $admin = User::query()->where('email', 'admin@demo.ruangbk.test')->firstOrFail();
        $this->assertTrue(Hash::check('SecretDemo2026!', $admin->password));
        $admin->update(['password' => 'ChangedDemo2026!']);
        $this->seed(DemoSeeder::class);

        $this->assertSame(4, User::query()->count());
        $this->assertSame(3, Student::query()->count());
        $this->assertSame(2, BkCase::query()->count());
        $this->assertSame(1, Consultation::query()->count());
        $this->assertTrue(Hash::check('ChangedDemo2026!', $admin->fresh()->password));
    }

    public function test_refuses_to_run_without_flag_or_with_weak_password(): void
    {
        config()->set('sibk.demo.enabled', false);
        try {
            $this->seed(DemoSeeder::class);
            $this->fail('Seeder seharusnya menolak fitur demo yang tidak diaktifkan.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('users', 0);
        }

        config()->set('sibk.demo.enabled', true);
        config()->set('sibk.demo.account_password', 'short');
        $this->expectException(InvalidArgumentException::class);
        $this->seed(DemoSeeder::class);
    }

    public function test_repeat_seed_keeps_new_demo_entries_added_through_the_application(): void
    {
        $this->seed(DemoSeeder::class);
        $newUser = User::factory()->create(['email' => 'pengunjung@demo.ruangbk.test']);
        $newStudent = Student::query()->create(['nisn' => '1234567890', 'name' => 'Murid Uji Tambahan']);

        $this->seed(DemoSeeder::class);

        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('students', 4);
        $this->assertSame($newUser->id, User::query()->where('email', $newUser->email)->firstOrFail()->id);
        $this->assertSame($newStudent->id, Student::query()->where('nisn', $newStudent->nisn)->firstOrFail()->id);
    }

    public function test_refuses_existing_school_data_without_touching_it(): void
    {
        $user = User::factory()->create(['email' => 'guru@sekolah.test']);

        try {
            $this->seed(DemoSeeder::class);
            $this->fail('Seeder seharusnya menolak database sekolah.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('users', 1);
            $this->assertSame($user->id, User::query()->sole()->id);
            $this->assertDatabaseCount('roles', 0);
        }
    }
}
