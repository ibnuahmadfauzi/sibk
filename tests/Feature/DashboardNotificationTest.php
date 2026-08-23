<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseCoordination;
use App\Models\Classroom;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\AssignmentService;
use App\Services\CaseService;
use App\Services\DashboardService;
use App\Services\FollowUpService;
use App\Services\NotificationService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardNotificationTest extends TestCase
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
            'follow_up_type_id' => $this->reference('follow_up_type', 'konsultasi_individual')->id,
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
        $this->assertStringContainsString($studentA->name, $teacherDashboard['tindak_lanjut'][0]['context_label']);
        $this->assertStringNotContainsString($studentB->name, json_encode($teacherDashboard, JSON_THROW_ON_ERROR));

        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator');
        $coordinatorDashboard = $service->forUser($coordinator, $this->year);
        $this->assertSame('2', $this->stat($coordinatorDashboard, 'Murid dalam cakupan'));
        $this->assertSame('2', $this->stat($coordinatorDashboard, 'Kasus aktif'));

        $wakaDashboard = $service->forUser($waka, $this->year);
        $this->assertTrue($wakaDashboard['read_only']);
        $this->assertSame('1', $this->stat($wakaDashboard, 'Kasus terkoordinasi'));
        $this->assertStringContainsString($studentA->name, json_encode($wakaDashboard, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString($studentB->name, json_encode($wakaDashboard, JSON_THROW_ON_ERROR));

        $admin = $this->userWithRole('admin_it', 'Admin IT');
        $adminDashboard = $service->forUser($admin, $this->year);
        $this->assertSame('admin', $adminDashboard['role_key']);
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
            'follow_up_type_id' => $this->reference('follow_up_type', 'konsultasi_individual')->id,
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

    public function test_notifications_are_owner_scoped_and_read_actions_are_persisted(): void
    {
        $owner = $this->userWithRole('guru_bk', 'Penerima');
        $other = $this->userWithRole('guru_bk', 'Akun Lain');
        $inactive = $this->userWithRole('guru_bk', 'Akun Nonaktif');
        $inactive->update(['is_active' => false, 'deactivated_at' => now()]);
        app(NotificationService::class)->send(
            recipients: collect([$owner, $inactive]),
            category: UserNotification::CATEGORY_SCHEDULE,
            title: 'Jadwal tindak lanjut diperbarui.',
            message: 'Jadwal layanan tersedia untuk ditinjau.',
            target: null,
            actionRoute: 'notifications.preview',
            actionParameters: [],
            deduplicationKey: 'test-schedule-1',
        );
        app(NotificationService::class)->send(
            recipients: collect([$owner]),
            category: UserNotification::CATEGORY_SCHEDULE,
            title: 'Jadwal tindak lanjut diperbarui.',
            message: 'Jadwal layanan tersedia untuk ditinjau.',
            target: null,
            actionRoute: 'notifications.preview',
            actionParameters: [],
            deduplicationKey: 'test-schedule-1',
        );
        $notification = $owner->notifications()->firstOrFail();

        $this->assertDatabaseMissing('user_notifications', ['user_id' => $inactive->id]);
        $this->assertSame(1, $owner->notifications()->count());
        $this->actingAs($owner)->get(route('notifications.preview', ['filter' => 'unread']))
            ->assertOk()
            ->assertSee('data-page-id="PG-003"', false)
            ->assertSee('Jadwal tindak lanjut diperbarui.');
        $this->actingAs($other)->get(route('notifications.preview'))
            ->assertOk()
            ->assertDontSee('Jadwal tindak lanjut diperbarui.');
        $this->actingAs($other)->get(route('notifications.open', $notification))->assertForbidden();

        $this->actingAs($owner)->get(route('notifications.open', $notification))
            ->assertRedirect(route('notifications.preview'));
        $this->assertNotNull($notification->refresh()->read_at);

        $owner->notifications()->create([
            'category' => UserNotification::CATEGORY_CHANGE,
            'title' => 'Perubahan penting.',
            'message' => 'Perubahan kewenangan telah dicatat.',
            'deduplication_key' => 'test-change-1',
        ]);
        $this->actingAs($owner)->post(route('notifications.read-all'))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(0, $owner->notifications()->unread()->count());
    }

    public function test_assignment_coordination_and_schedule_events_notify_only_their_recipients(): void
    {
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Notifikasi');
        $teacher = $this->userWithRole('guru_bk', 'Guru Penerima');
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Penerima');
        $admin = $this->userWithRole('admin_it', 'Admin Tanpa Layanan');
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'X TKJ 1',
            'is_active' => true,
        ]);
        app(AssignmentService::class)->assignClass([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'decision_number' => 'SK-NOTIFIKASI',
            'effective_date' => '2026-07-15',
        ], $coordinator);
        $student = Student::query()->create(['nisn' => '0055555555', 'name' => 'Murid Notifikasi', 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-15',
            'is_active' => true,
        ]);
        $case = app(CaseService::class)->createCase([
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2026-08-20',
            'initial_info' => 'Informasi awal.',
            'initial_action' => 'Asesmen.',
        ], $teacher);
        app(CaseService::class)->coordinate($case, [
            'waka_user_id' => $waka->id,
            'coordination_need' => 'Dukungan kesiswaan.',
        ], $teacher);
        app(FollowUpService::class)->record($case, [
            'follow_up_type_id' => $this->reference('follow_up_type', 'konsultasi_individual')->id,
            'status_id' => $this->reference('follow_up_status', 'terjadwal')->id,
            'planned_date' => '2026-08-22',
        ], $teacher);

        $this->assertTrue($teacher->notifications()->where('category', UserNotification::CATEGORY_ASSIGNMENT)->exists());
        $this->assertTrue($teacher->notifications()->where('category', UserNotification::CATEGORY_SCHEDULE)->exists());
        $this->assertTrue($waka->notifications()->where('category', UserNotification::CATEGORY_COORDINATION)->exists());
        $this->assertSame(0, $admin->notifications()->count());
        $this->assertSame(0, $coordinator->notifications()->count());
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
        $case = app(CaseService::class)->createCase([
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2026-08-20',
            'initial_info' => 'Informasi awal yang aman.',
            'initial_action' => 'Asesmen awal.',
            'internal_note' => $internalNote,
        ], $teacher);

        return [$student, $case];
    }

    /** @param array<string, mixed> $dashboard */
    private function stat(array $dashboard, string $label): string
    {
        return collect($dashboard['stats'])->firstWhere('label', $label)['value'];
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
