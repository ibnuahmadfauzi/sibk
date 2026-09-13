<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function createUserWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create(['is_active' => true]);
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
}
