<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\CaseCoordination;
use App\Models\Classroom;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\StudentDeparture;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\DashboardService;
use App\Support\ServiceRecordStatus;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_dashboard_is_scoped_for_teacher_coordinator_waka_and_admin(): void
    {
        $teacherA = $this->userWithRole('guru_bk', 'Guru A');
        $teacherB = $this->userWithRole('guru_bk', 'Guru B');
        [$studentA, $caseA] = $this->createScopedCase($teacherA, 'X RPL 1', '0011111111', 'Murid Cakupan A');
        [$studentB, $caseB] = $this->createScopedCase($teacherB, 'X RPL 2', '0022222222', 'Murid Cakupan B');
        FollowUp::query()->create([
            'case_id' => $caseA->id,
            'follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id,
            'status_id' => $this->reference('follow_up_status', 'terjadwal')->id,
            'planned_date' => '2026-08-22',
            'recorded_by' => $teacherA->id,
        ]);
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Terkoordinasi');
        CaseCoordination::query()->create([
            'case_id' => $caseA->id,
            'waka_user_id' => $waka->id,
            'status_id' => $this->reference('coordination_status', 'menunggu')->id,
            'coordination_need' => 'Dukungan kebijakan sekolah.',
            'recorded_by' => $teacherA->id,
            'coordinated_at' => now(),
        ]);

        $service = app(DashboardService::class);
        $teacherDashboard = $service->forUser($teacherA, $this->year);
        $this->assertSame('1', $this->stat($teacherDashboard, 'Murid dalam cakupan'));
        $this->assertSame('1', $this->stat($teacherDashboard, 'Kasus aktif'));
        $this->assertSame('1', $this->contextValue($teacherDashboard, 'Kelas ampuan'));
        $this->assertSame('1', $this->contextValue($teacherDashboard, 'Kasus khusus aktif'));
        $this->assertSame('1', $this->contextValue($teacherDashboard, 'Tindak lanjut terdekat'));
        $this->assertStringContainsString($studentA->name, $teacherDashboard['tindak_lanjut'][0]['context_label']);
        $this->assertStringNotContainsString($studentB->name, json_encode($teacherDashboard, JSON_THROW_ON_ERROR));

        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator');
        $coordinatorDashboard = $service->forUser($coordinator, $this->year);
        $this->assertSame('2', $this->stat($coordinatorDashboard, 'Murid dalam cakupan'));
        $this->assertSame('2', $this->stat($coordinatorDashboard, 'Kasus aktif'));
        $this->assertSame('2', $this->contextValue($coordinatorDashboard, 'Guru BK aktif'));
        $this->assertSame('0', $this->contextValue($coordinatorDashboard, 'Kelas tanpa penugasan'));
        $this->assertSame('1', $this->contextValue($coordinatorDashboard, 'Tindak lanjut terbuka'));

        $wakaDashboard = $service->forUser($waka, $this->year);
        $this->assertTrue($wakaDashboard['read_only']);
        $this->assertSame('2', $this->stat($wakaDashboard, 'Kasus berjalan'));
        $this->assertStringContainsString($studentA->name, json_encode($wakaDashboard['latest'], JSON_THROW_ON_ERROR));
        $this->assertStringContainsString($studentB->name, json_encode($wakaDashboard['latest'], JSON_THROW_ON_ERROR));

        $admin = $this->userWithRole('admin_it', 'Admin IT');
        $adminDashboard = $service->forUser($admin, $this->year);
        $this->assertSame('admin', $adminDashboard['role_key']);
        $this->assertSame('2', $this->contextValue($adminDashboard, 'Provider tanpa credential'));
        $this->assertStringNotContainsString($studentA->name, json_encode($adminDashboard, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString($caseB->registration_number, json_encode($adminDashboard, JSON_THROW_ON_ERROR));
    }

    public function test_dashboard_route_does_not_leak_another_teachers_student_or_private_case_text(): void
    {
        $teacherA = $this->userWithRole('guru_bk', 'Guru Dashboard');
        $teacherB = $this->userWithRole('guru_bk', 'Guru Lain');
        [$studentA, $caseA] = $this->createScopedCase($teacherA, 'XI DKV 1', '0033333333', 'Murid Aman');
        [$studentB] = $this->createScopedCase($teacherB, 'XI DKV 2', '0044444444', 'Murid Rahasia', 'CATATAN-PRIVAT-TIDAK-BOLEH-BOCOR');
        FollowUp::query()->create([
            'case_id' => $caseA->id,
            'follow_up_type_id' => $this->reference('follow_up_type', 'home_visit')->id,
            'status_id' => $this->reference('follow_up_status', 'terjadwal')->id,
            'planned_date' => '2026-08-22',
            'recorded_by' => $teacherA->id,
        ]);

        $this->actingAs($teacherA)->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSee('data-page-id="PG-002"', false)
            ->assertSee($studentA->name)
            ->assertDontSee($studentB->name)
            ->assertDontSee('CATATAN-PRIVAT-TIDAK-BOLEH-BOCOR');
    }

    public function test_dashboard_uses_role_context_instead_of_audit_activity_feed(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Konteks');
        AuditLog::query()->create([
            'actor_id' => $teacher->id,
            'action' => 'secret.event',
            'auditable_type' => User::class,
            'auditable_id' => $teacher->id,
            'summary' => 'NARASI-AUDIT-RAHASIA',
        ]);

        $this->actingAs($teacher)->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSee('Cakupan layanan Anda')
            ->assertDontSee('Aktivitas terbaru')
            ->assertDontSee('NARASI-AUDIT-RAHASIA');
    }

    public function test_dashboard_excludes_official_departure_from_active_student_count(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Cakupan Keluar');
        [$student] = $this->createScopedCase($teacher, 'XII RPL 1', '0055555555', 'Murid Resmi Keluar');
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Keluar');
        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'status' => StudentDeparture::STATUS_OFFICIAL,
            'reported_at' => '2026-08-19',
            'effective_date' => '2026-08-21',
            'recorded_by' => $teacher->id,
            'finalized_by' => $coordinator->id,
            'finalized_at' => now(),
        ]);

        $dashboard = app(DashboardService::class)->forUser($teacher, $this->year);

        $this->assertSame('0', $this->stat($dashboard, 'Murid dalam cakupan'));
        $this->assertSame('1', $this->stat($dashboard, 'Kasus aktif'));
    }

    public function test_teacher_quick_actions_provide_an_allowed_icon_and_tone(): void
    {
        $teacher = $this->userWithRole('guru_bk', 'Guru Ikon');
        $actions = app(DashboardService::class)->forUser($teacher, $this->year)['quick_actions'];

        $this->assertSame(['case', 'consultation', 'report'], array_column($actions, 'icon'));
        $this->assertSame(['primary', 'success', 'info'], array_column($actions, 'tone'));
    }

    /** @return array{Student, BkCase} */
    private function createScopedCase(User $teacher, string $className, string $nisn, string $studentName, ?string $internalNote = null): array
    {
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => $className,
            'is_active' => true,
        ]);
        $student = Student::query()->create(['nisn' => $nisn, 'name' => $studentName, 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-15',
            'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-15',
            'decision_number' => 'SK-'.$className,
            'assigned_by' => $teacher->id,
        ]);
        $case = BkCase::query()->create([
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::IN_PROGRESS)->id,
            'service_date' => '2026-08-20',
            'initial_info' => 'Informasi awal yang aman.',
            'initial_action' => 'Asesmen awal.',
            'internal_note' => $internalNote,
            'created_by' => $teacher->id,
        ]);
        $case->update(['registration_number' => sprintf('K-2026-%04d', $case->id)]);
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $teacher->id,
            'assignment_type' => CaseAssignment::TYPE_OWNER,
            'effective_from' => '2026-08-20',
            'reason' => 'Fixture cakupan dashboard.',
            'assigned_by' => $teacher->id,
        ]);

        return [$student, $case];
    }

    /** @param array<string, mixed> $dashboard */
    private function stat(array $dashboard, string $label): string
    {
        return collect($dashboard['stats'] ?? $dashboard['metrics'])->firstWhere('label', $label)['value'];
    }

    /** @param array<string, mixed> $dashboard */
    private function contextValue(array $dashboard, string $label): string
    {
        return collect($dashboard['context_panel']['items'])->firstWhere('label', $label)['value'];
    }

    private function reference(string $category, string $code): ReferenceValue
    {
        return ReferenceValue::query()->where('category', $category)->where('code', $code)->firstOrFail();
    }

    private function userWithRole(string $slug, string $name): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
