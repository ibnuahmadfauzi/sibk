<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Services\WakaCaseProjectionQuery;
use App\Services\WakaMonitoringService;
use App\Services\WakaStudentCaseService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WakaMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_waka_projection_uses_snapshot_and_permanent_owner_without_private_fields(): void
    {
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
        $waka = $this->userWithRole('waka_kesiswaan');
        $owner = $this->userWithRole('guru_bk');
        $year = AcademicYear::query()->create(['name' => '2026/2027', 'is_active' => true]);
        $originalClass = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 1']);
        $nextClass = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'XI RPL 2']);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Aman', 'is_active' => true]);
        $membership = StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'classroom_id' => $originalClass->id,
            'is_active' => true,
        ]);
        $case = BkCase::query()->create([
            'registration_number' => 'K-2026-0001',
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'classroom_id' => $originalClass->id,
            'case_source_id' => ReferenceValue::query()->forCategory('case_source')->firstOrFail()->id,
            'service_field_id' => ReferenceValue::query()->forCategory('service_field')->firstOrFail()->id,
            'status_id' => ReferenceValue::query()->forCategory('case_status')->firstOrFail()->id,
            'service_date' => '2026-09-01',
            'initial_info' => 'Narasi privat.',
            'initial_action' => 'Asesmen privat.',
            'internal_note' => 'Catatan internal.',
            'created_by' => $owner->id,
        ]);
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $owner->id,
            'reason' => 'Pemilik saat pencatatan.',
            'assigned_by' => $owner->id,
        ]);
        $membership->update(['classroom_id' => $nextClass->id]);

        $projected = app(WakaCaseProjectionQuery::class)->build($waka)->firstOrFail();
        $row = app(WakaMonitoringService::class)->toSafeRow($projected);

        $this->assertSame('X RPL 1', $row['kelas']);
        $this->assertSame($owner->name, $row['guru_bk']);
        $this->assertArrayNotHasKey('initial_info', $row);
        $this->assertArrayNotHasKey('internal_note', $row);
        $this->assertArrayNotHasKey('registration_number', $row);
        $this->actingAs($waka)->get(route('waka.monitoring.students'))->assertOk();
        $this->actingAs($owner)->get(route('waka.monitoring.students'))->assertForbidden();
    }

    public function test_waka_student_count_sort_is_numeric_before_pagination(): void
    {
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
        $waka = $this->userWithRole('waka_kesiswaan');
        $owner = $this->userWithRole('guru_bk');
        $year = AcademicYear::query()->create(['name' => '2026/2027', 'is_active' => true]);
        $classroom = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 1']);
        foreach ([2, 10, 3] as $index => $count) {
            $student = Student::query()->create(['nisn' => sprintf('%010d', $index + 1), 'name' => 'Murid '.$count, 'is_active' => true]);
            for ($number = 0; $number < $count; $number++) {
                BkCase::query()->create([
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'classroom_id' => $classroom->id,
                    'case_source_id' => ReferenceValue::query()->forCategory('case_source')->firstOrFail()->id,
                    'service_field_id' => ReferenceValue::query()->forCategory('service_field')->firstOrFail()->id,
                    'status_id' => ReferenceValue::query()->forCategory('case_status')->firstOrFail()->id,
                    'service_date' => '2026-09-01',
                    'initial_info' => 'Catatan uji sorting.',
                    'initial_action' => 'Penanganan uji sorting.',
                    'created_by' => $owner->id,
                ]);
            }
        }

        $service = app(WakaStudentCaseService::class);
        $first = $service->paginateSafe($waka, ['sort' => 'jumlah_kasus', 'direction' => 'asc', 'page' => '1'], 1);
        $second = $service->paginateSafe($waka, ['sort' => 'jumlah_kasus', 'direction' => 'asc', 'page' => '2'], 1);
        $last = $service->paginateSafe($waka, ['sort' => 'jumlah_kasus', 'direction' => 'asc', 'page' => '3'], 1);
        $this->assertSame([2, 3, 10], [$first->first()['jumlah_kasus'], $second->first()['jumlah_kasus'], $last->first()['jumlah_kasus']]);
        $this->actingAs($waka)->get(route('waka.monitoring.students', ['sort' => 'jumlah_aktif', 'direction' => 'asc']))
            ->assertOk()->assertSee('aria-sort="ascending"', false);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
