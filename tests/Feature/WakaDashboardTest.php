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
use App\Services\WakaDashboardService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WakaDashboardTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-09-13 09:00:00');
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
        $this->year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
    }

    public function test_waka_dashboard_shows_school_metrics_without_case_codes(): void
    {
        [$waka, $coordinated] = $this->dashboardFixture();

        $this->actingAs($waka)->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSeeInOrder([
                'Permasalahan berjalan',
                'Sedang diproses',
                'Membutuhkan tindak lanjut',
                'Selesai bulan ini',
                'Membutuhkan Perhatian',
                'Komposisi Status',
                'Penanganan Terbaru',
            ])
            ->assertDontSee($coordinated->registration_number)
            ->assertDontSee('SENTINEL-INTERNAL');
    }

    public function test_every_dashboard_case_has_read_only_detail_link(): void
    {
        [$waka, $coordinated, $notCoordinated] = $this->dashboardFixture();

        $response = $this->actingAs($waka)->get(route('dashboard.preview'));

        $response->assertOk()
            ->assertSee('Lihat detail')
            ->assertSee('href="'.route('cases.show', $coordinated).'"', false)
            ->assertSee('href="'.route('cases.show', $notCoordinated).'"', false);
    }

    public function test_waka_dashboard_view_is_audited_without_sensitive_data(): void
    {
        [$waka] = $this->dashboardFixture();

        $this->actingAs($waka)->get(route('dashboard.preview'))->assertOk();

        $audit = $waka->auditLogs()->where('action', 'waka.monitoring.viewed')->latest('id')->firstOrFail();
        $this->assertStringContainsString('dashboard', $audit->summary);
        $this->assertStringContainsString('academic_year_id', $audit->summary);
        $this->assertStringContainsString((string) $this->year->id, $audit->summary);
        $this->assertStringNotContainsString('SENTINEL-INTERNAL', $audit->summary);
        $this->assertStringNotContainsString('K-2026-', $audit->summary);
    }

    public function test_attention_uses_current_case_fields_and_stable_date_id_order_only(): void
    {
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Perhatian');
        $owner = $this->userWithRole('guru_bk', 'Guru BK Perhatian');
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'XI RPL 3',
            'is_active' => true,
        ]);
        $first = $this->createCase($owner, $classroom, '9055555555', 'Current Pertama', 'K-CURRENT-1', 'sedang_diproses', '2026-09-11');
        $first->update(['follow_up_type_id' => $this->reference('follow_up_type', 'surat_pernyataan')->id]);
        $second = $this->createCase($owner, $classroom, '9066666666', 'Current Kedua', 'K-CURRENT-2', 'membutuhkan_tindak_lanjut', '2026-09-11');

        $attention = app(WakaDashboardService::class)->build($waka, $this->year)['attention'];

        $this->assertSame(['Current Kedua', 'Current Pertama'], array_column($attention, 'nama_murid'));
        $this->assertSame(['-', 'Surat Pernyataan'], array_column($attention, 'tindak_lanjut'));
    }

    /** @return array{User, BkCase, BkCase} */
    private function dashboardFixture(): array
    {
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Dashboard');
        $owner = $this->userWithRole('guru_bk', 'Guru BK Dashboard');
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'XI RPL 2',
            'is_active' => true,
        ]);

        $coordinated = $this->createCase(
            owner: $owner,
            classroom: $classroom,
            nisn: '9011111111',
            name: 'Murid Terkoordinasi',
            number: 'K-2026-DASH-01',
            status: 'sedang_diproses',
            date: '2026-09-10',
        );
        $notCoordinated = $this->createCase(
            owner: $owner,
            classroom: $classroom,
            nisn: '9022222222',
            name: 'Murid Belum Terkoordinasi',
            number: 'K-2026-DASH-02',
            status: 'membutuhkan_tindak_lanjut',
            date: '2026-09-09',
        );
        $completed = $this->createCase(
            owner: $owner,
            classroom: $classroom,
            nisn: '9033333333',
            name: 'Murid Selesai',
            number: 'K-2026-DASH-03',
            status: 'selesai',
            date: '2026-09-03',
        );
        $completed->update(['closed_at' => '2026-09-11']);

        return [$waka, $coordinated, $notCoordinated];
    }

    private function createCase(
        User $owner,
        Classroom $classroom,
        string $nisn,
        string $name,
        string $number,
        string $status,
        string $date,
    ): BkCase {
        $student = Student::query()->create(['nisn' => $nisn, 'name' => $name, 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'is_active' => true,
        ]);
        $case = BkCase::query()->create([
            'academic_year_id' => $this->year->id,
            'classroom_id' => $classroom->id,
            'registration_number' => $number,
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('case_status', $status)->id,
            'service_date' => $date,
            'initial_info' => 'Informasi awal rahasia.',
            'initial_action' => 'Asesmen awal.',
            'waka_summary' => 'Ringkasan aman untuk Waka.',
            'internal_note' => 'SENTINEL-INTERNAL',
            'created_by' => $owner->id,
        ]);
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $owner->id,
            'reason' => 'Penanggung jawab kasus.',
            'assigned_by' => $owner->id,
        ]);

        return $case;
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
