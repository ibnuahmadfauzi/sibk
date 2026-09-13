<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\WakaMonitoringRequest;
use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Services\WakaMonitoringService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class WakaMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);

        $this->academicYear = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
    }

    private function createUserWithRole(string $roleSlug, ?string $name = null): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create(array_filter([
            'name' => $name,
            'is_active' => true,
        ], static fn (mixed $value): bool => $value !== null));
        $user->roles()->attach($role->id);

        return $user;
    }

    private function ref(string $category, string $code): ReferenceValue
    {
        return ReferenceValue::query()->where('category', $category)->where('code', $code)->firstOrFail();
    }

    public function test_waka_kesiswaan_can_access_monitoring_pages(): void
    {
        $waka = $this->createUserWithRole('waka_kesiswaan');

        $this->actingAs($waka)
            ->get(route('waka.monitoring.students'))
            ->assertStatus(200)
            ->assertSee('Pemantauan Kasus');

        $this->actingAs($waka)
            ->get(route('waka.monitoring.handling'))
            ->assertStatus(200)
            ->assertSee('Pemantauan Kasus');
    }

    public function test_other_roles_cannot_access_waka_monitoring(): void
    {
        $guruBk = $this->createUserWithRole('guru_bk');
        $koordinator = $this->createUserWithRole('koordinator_bk');
        $admin = $this->createUserWithRole('admin_it');

        foreach ([$guruBk, $koordinator, $admin] as $user) {
            $this->actingAs($user)
                ->get(route('waka.monitoring.students'))
                ->assertStatus(403);

            $this->actingAs($user)
                ->get(route('waka.monitoring.handling'))
                ->assertStatus(403);

            $this->actingAs($user)
                ->get(route('waka.monitoring.export'))
                ->assertStatus(403);
        }
    }

    public function test_monitoring_students_view_excludes_sensitive_fields(): void
    {
        $waka = $this->createUserWithRole('waka_kesiswaan');

        $student = Student::query()->create([
            'nisn' => '9988776655',
            'name' => 'Budi Santoso Confidential',
            'gender' => 'L',
            'is_active' => true,
        ]);

        BkCase::query()->create([
            'registration_number' => 'K-2026-0001',
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'case_source_id' => $this->ref('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->ref('service_field', 'pribadi')->id,
            'status_id' => $this->ref('case_status', 'baru')->id,
            'service_date' => '2026-08-01',
            'initial_info' => 'Informasi rahasia murid budi',
            'initial_action' => 'Aksi awal',
            'internal_note' => 'Catatan internal BK rahasia',
            'created_by' => $waka->id,
        ]);

        $response = $this->actingAs($waka)->get(route('waka.monitoring.students'));

        $response->assertStatus(200)
            ->assertSee('Budi Santoso Confidential')
            ->assertDontSee('9988776655')
            ->assertDontSee('Informasi rahasia murid budi')
            ->assertDontSee('Catatan internal BK rahasia');
    }

    public function test_waka_monitoring_creates_audit_log_events(): void
    {
        $waka = $this->createUserWithRole('waka_kesiswaan');

        $this->actingAs($waka)->get(route('waka.monitoring.students'));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $waka->id,
            'action' => 'waka.monitoring.viewed',
        ]);

        $this->actingAs($waka)->get(route('waka.monitoring.export', ['period' => '2026-08']));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $waka->id,
            'action' => 'waka.monitoring.exported',
        ]);
    }

    public function test_waka_monitoring_export_csv(): void
    {
        $waka = $this->createUserWithRole('waka_kesiswaan');

        $student = Student::query()->create([
            'nisn' => '1122334455',
            'name' => 'Siti Aminah',
            'gender' => 'P',
            'is_active' => true,
        ]);

        BkCase::query()->create([
            'registration_number' => 'K-2026-0002',
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'case_source_id' => $this->ref('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->ref('service_field', 'pribadi')->id,
            'status_id' => $this->ref('case_status', 'baru')->id,
            'service_date' => '2026-08-01',
            'initial_info' => 'Info awal',
            'initial_action' => 'Aksi awal',
            'created_by' => $waka->id,
        ]);

        $response = $this->actingAs($waka)->get(route('waka.monitoring.export', ['format' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Murid', $content);
        $this->assertStringContainsString('Siti Aminah', $content);
        $this->assertStringNotContainsString('1122334455', $content);
    }

    public function test_handling_projection_with_real_owner_is_safe(): void
    {
        [$waka, $case, $owner] = $this->wakaCaseFixture();
        $this->assignOwner($case, $owner);

        $paginator = app(WakaMonitoringService::class)->paginateSafe($waka, [
            'period' => '2026-09',
            'sort' => 'tanggal',
            'direction' => 'desc',
            'page' => '1',
        ]);
        $row = $paginator->items()[0];

        $this->assertSame($owner->name, $row['guru_bk']);
        $this->assertArrayNotHasKey('registration_number', $row);
        $this->assertArrayNotHasKey('initial_info', $row);
        $this->assertArrayNotHasKey('internal_note', $row);
        $this->assertStringNotContainsString('SENTINEL-INTERNAL', json_encode($row, JSON_THROW_ON_ERROR));
    }

    public function test_handling_projection_uses_class_effective_on_service_date(): void
    {
        [$waka, $case, $owner, $student] = $this->wakaCaseFixture(serviceDate: '2026-08-15');
        $this->assignOwner($case, $owner);
        $oldClass = Classroom::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'X RPL Historis',
            'is_active' => false,
        ]);
        $newClass = Classroom::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'XI RPL Sekarang',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $oldClass->id,
            'academic_year_id' => $this->academicYear->id,
            'effective_from' => '2026-07-01',
            'effective_until' => '2026-08-31',
            'is_active' => false,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $newClass->id,
            'academic_year_id' => $this->academicYear->id,
            'effective_from' => '2026-09-01',
            'is_active' => true,
        ]);

        $row = app(WakaMonitoringService::class)->paginateSafe($waka, [
            'period' => '2026-08',
            'sort' => 'kelas',
            'direction' => 'asc',
            'page' => '1',
        ])->items()[0];

        $this->assertSame('X RPL Historis', $row['kelas']);
    }

    public function test_handling_projection_executes_only_allowed_sort_keys(): void
    {
        [$waka, $case, $owner] = $this->wakaCaseFixture();
        $this->assignOwner($case, $owner);

        foreach (WakaMonitoringRequest::HANDLING_SORT_ALLOWLIST as $sort) {
            $rows = app(WakaMonitoringService::class)->paginateSafe($waka, [
                'sort' => $sort,
                'direction' => 'asc',
                'page' => '1',
            ]);

            $this->assertGreaterThanOrEqual(1, $rows->total(), "Sort {$sort} gagal dieksekusi.");
        }

        $this->expectException(InvalidArgumentException::class);
        app(WakaMonitoringService::class)->paginateSafe($waka, [
            'sort' => 'cases.registration_number',
        ]);
    }

    public function test_csv_formula_cells_are_escaped(): void
    {
        [$waka, $case, $owner, $student] = $this->wakaCaseFixture(
            studentName: '=Murid Berbahaya',
            wakaSummary: "\rRingkasan Berbahaya",
        );
        $owner->update(['name' => '-Guru Berbahaya']);
        $this->assignOwner($case, $owner);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '+Kelas Berbahaya',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->academicYear->id,
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);
        $case->serviceField()->update(['label' => '@Bidang Berbahaya']);
        $case->status()->update(['label' => "\tStatus Berbahaya"]);

        $row = app(WakaMonitoringService::class)->exportCsvRows($waka, [
            'period' => '2026-09',
            'sort' => 'tanggal',
            'direction' => 'desc',
        ])->firstOrFail();

        $this->assertSame("'=Murid Berbahaya", $row['Murid']);
        $this->assertSame("'+Kelas Berbahaya", $row['Kelas']);
        $this->assertSame("'@Bidang Berbahaya", $row['Bidang Layanan']);
        $this->assertSame("'\tStatus Berbahaya", $row['Status']);
        $this->assertSame("'-Guru Berbahaya", $row['Guru BK']);
        $this->assertSame("'\rRingkasan Berbahaya", $row['Ringkasan Waka']);
    }

    /** @return array{User, BkCase, User, Student} */
    private function wakaCaseFixture(
        string $studentName = 'Murid Aman',
        string $serviceDate = '2026-09-05',
        string $wakaSummary = 'Ringkasan aman untuk Waka.',
    ): array {
        $waka = $this->createUserWithRole('waka_kesiswaan', 'Waka Kesiswaan');
        $owner = $this->createUserWithRole('guru_bk', 'Guru BK Pemilik');
        $student = Student::query()->create([
            'nisn' => '9911223344',
            'name' => $studentName,
            'is_active' => true,
        ]);
        $case = BkCase::query()->create([
            'registration_number' => 'K-2026-WAKA-01',
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'case_source_id' => $this->ref('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->ref('service_field', 'pribadi')->id,
            'status_id' => $this->ref('case_status', 'sedang_diproses')->id,
            'service_date' => $serviceDate,
            'initial_info' => 'Informasi awal rahasia.',
            'initial_action' => 'Asesmen awal.',
            'waka_summary' => $wakaSummary,
            'internal_note' => 'SENTINEL-INTERNAL',
            'created_by' => $owner->id,
        ]);

        return [$waka, $case, $owner, $student];
    }

    private function assignOwner(BkCase $case, User $owner): void
    {
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $owner->id,
            'assignment_type' => CaseAssignment::TYPE_OWNER,
            'effective_from' => '2026-07-01',
            'reason' => 'Penanggung jawab awal.',
            'assigned_by' => $owner->id,
        ]);
    }
}
