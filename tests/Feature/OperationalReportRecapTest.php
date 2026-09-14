<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalReportRecapTest extends TestCase
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

    public function test_operational_report_tabs_follow_role_authorization(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Laporan');
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Laporan');
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Laporan');
        $admin = $this->userWithRole('admin_it', 'Admin Laporan');

        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->actingAs($teacher)->get(route('reports.index'))->assertOk();
        $this->actingAs($coordinator)->get(route('reports.index', ['tab' => 'prestasi']))->assertOk();
        $this->actingAs($waka)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.index'))->assertForbidden();
    }

    public function test_request_rejects_invalid_dates_and_class_outside_year_or_scope(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Filter');
        [, $allowedClass] = $this->scopedStudent($teacher, 'Murid Filter', '0012345678', 'X RPL 1');
        $otherYear = AcademicYear::query()->create([
            'name' => '2025/2026',
            'starts_on' => '2025-07-01',
            'ends_on' => '2026-06-30',
            'is_active' => false,
        ]);
        $crossYearClass = Classroom::query()->create([
            'academic_year_id' => $otherYear->id,
            'name' => 'X Lama',
            'is_active' => true,
        ]);

        $this->actingAs($teacher)->get(route('reports.index', [
            'tab' => 'pelanggaran',
            'academic_year_id' => $this->year->id,
            'date_start' => '2026-08-20',
            'date_end' => '2026-08-01',
            'classroom_id' => $crossYearClass->id,
        ]))->assertSessionHasErrors(['date_end', 'classroom_id']);

        $this->actingAs($teacher)->get(route('reports.index', [
            'tab' => 'pelanggaran',
            'academic_year_id' => $this->year->id,
            'classroom_id' => $allowedClass->id,
        ]))->assertOk();
    }

    public function test_counselor_filter_is_validated_safely(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Biasa');
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator');
        $activeCounselor = $this->userWithRole('guru_bk', 'Guru Aktif');
        $inactiveCounselor = $this->userWithRole('guru_bk', 'Guru Nonaktif');
        $inactiveCounselor->update(['is_active' => false]);

        $this->actingAs($teacher)->get(route('reports.index', [
            'tab' => 'layanan',
            'counselor_id' => $activeCounselor->id,
        ]))->assertSessionHasErrors('counselor_id');
        $this->actingAs($coordinator)->get(route('reports.index', [
            'tab' => 'prestasi',
            'counselor_id' => $activeCounselor->id,
        ]))->assertSessionHasErrors('counselor_id');
        $this->actingAs($coordinator)->get(route('reports.index', [
            'tab' => 'layanan',
            'counselor_id' => $inactiveCounselor->id,
        ]))->assertSessionHasErrors('counselor_id');
        $this->actingAs($coordinator)->get(route('reports.index', [
            'tab' => 'layanan',
            'counselor_id' => $activeCounselor->id,
        ]))->assertOk();
    }

    public function test_request_rejects_invalid_and_mixed_report_modes(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Mode');

        $this->actingAs($teacher)->get(route('reports.index', [
            'tab' => 'tidak-tersedia',
        ]))->assertSessionHasErrors('tab');
        $this->actingAs($teacher)->get(route('reports.index', [
            'tab' => 'layanan',
            'type' => 'rekap-layanan-bk',
        ]))->assertSessionHasErrors('type');
        $this->actingAs($teacher)->get(route('reports.preview'))
            ->assertSessionHasErrors('type');
        $this->actingAs($teacher)->get(route('reports.preview', [
            'type' => 'rekap-layanan-bk',
            'tab' => 'layanan',
        ]))->assertSessionHasErrors('tab');
        $this->actingAs($teacher)->get(route('reports.export', [
            'tab' => 'layanan',
            'type' => 'rekap-layanan-bk',
            'format' => 'csv',
        ]))->assertSessionHasErrors(['tab', 'type']);
    }

    private function userWithRole(string $slug, string $name): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }

    private function reference(string $category, string $code): ReferenceValue
    {
        return ReferenceValue::query()->where('category', $category)->where('code', $code)->firstOrFail();
    }

    /** @return array{Student, Classroom} */
    private function scopedStudent(User $teacher, string $name, string $nisn, string $className): array
    {
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => $className,
            'is_active' => true,
        ]);
        $student = Student::query()->create(['nisn' => $nisn, 'name' => $name, 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-01',
            'decision_number' => 'SK-'.$classroom->id,
            'assigned_by' => $teacher->id,
        ]);

        return [$student, $classroom];
    }
}
