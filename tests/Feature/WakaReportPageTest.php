<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\ExternalTatibRecord;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Services\WakaPeriodReportService;
use Carbon\CarbonImmutable;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WakaReportPageTest extends TestCase
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

    public function test_period_recap_contains_aggregates_without_identity_or_narrative(): void
    {
        [$waka, $case] = $this->periodFixture();
        $recap = app(WakaPeriodReportService::class)->build(
            $this->year,
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-12-31'),
        );

        $this->assertSame([
            'served_students' => 1,
            'cases_recorded' => 2,
            'needs_follow_up' => 1,
            'completed' => 1,
        ], $recap['metrics']);
        $this->assertSame([
            'violations' => 2,
            'linked_students' => 1,
            'verified_achievements' => 1,
        ], $recap['student_affairs']);

        $this->actingAs($waka)->get(route('waka.reports', [
            'tab' => 'rekap',
            'academic_year_id' => $this->year->id,
            'date_start' => '2026-07-01',
            'date_end' => '2026-12-31',
        ]))->assertOk()
            ->assertSee('Murid ditangani')
            ->assertSee('Kasus tercatat')
            ->assertSee('Konteks Kesiswaan')
            ->assertSee('XI RPL Rekap')
            ->assertDontSee($case->identityName())
            ->assertDontSee($case->registration_number)
            ->assertDontSee('SENTINEL-NARASI');
    }

    public function test_final_report_is_only_a_development_placeholder_for_waka_and_coordinator(): void
    {
        foreach (['waka_kesiswaan', 'koordinator_bk'] as $role) {
            $user = $this->userWithRole($role, 'Pengguna Laporan Akhir '.$role);

            $this->actingAs($user)
                ->get(route('waka.reports', ['tab' => 'laporan-akhir']))
                ->assertOk()
                ->assertSee('Dalam pengembangan')
                ->assertDontSee('Simpan Draf')
                ->assertDontSee('Terbitkan')
                ->assertDontSee('Unduh PDF');
        }
    }

    public function test_period_recap_filter_lists_available_academic_years(): void
    {
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Pilih Tahun');
        AcademicYear::query()->create([
            'name' => '2025/2026',
            'starts_on' => '2025-07-01',
            'ends_on' => '2026-06-30',
            'is_active' => false,
        ]);

        $this->actingAs($waka)->get(route('waka.reports', [
            'tab' => 'rekap',
            'academic_year_id' => $this->year->id,
            'date_start' => '2026-07-01',
            'date_end' => '2026-12-31',
        ]))->assertOk()
            ->assertSee('2026/2027')
            ->assertSee('2025/2026');
    }

    /** @return array{User, BkCase} */
    private function periodFixture(): array
    {
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Rekap');
        $owner = $this->userWithRole('guru_bk', 'Guru BK Rekap');
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'XI RPL Rekap',
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'nisn' => '8111222233',
            'name' => 'SENTINEL-NAMA-MURID',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->year->id,
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);

        $case = $this->createCase($student, $owner, 'K-2026-REKAP-01', 'membutuhkan_tindak_lanjut', '2026-08-10');
        $completed = $this->createCase($student, $owner, 'K-2026-REKAP-02', 'selesai', '2026-09-01');
        $completed->update(['closed_at' => '2026-09-02']);

        ExternalTatibRecord::query()->create([
            'source_identifier' => 'ET-REKAP-01',
            'nisn' => $student->nisn,
            'student_id' => $student->id,
            'occurred_at' => '2026-08-05 07:00:00',
            'violation_type' => 'SENTINEL-NARASI-PELANGGARAN',
            'category' => 'Kedisiplinan',
            'points' => 5,
            'is_active' => true,
            'synced_at' => now(),
        ]);
        ExternalTatibRecord::query()->create([
            'source_identifier' => 'ET-REKAP-02',
            'nisn' => '8000000000',
            'student_id' => null,
            'occurred_at' => '2026-08-06 07:00:00',
            'violation_type' => 'SENTINEL-NARASI-BELUM-TERKAIT',
            'category' => 'Kerapian',
            'points' => 3,
            'is_active' => true,
            'synced_at' => now(),
        ]);
        Achievement::query()->create([
            'student_id' => $student->id,
            'type_id' => $this->reference('achievement_type', 'akademik')->id,
            'level_id' => $this->reference('achievement_level', 'sekolah')->id,
            'activity_name' => 'SENTINEL-NARASI-PRESTASI',
            'organizer' => 'Sekolah',
            'achievement_date' => '2026-08-15',
            'result' => 'Juara 1',
            'evidence_reference' => 'Bukti rahasia',
            'verification_status_id' => $this->reference('achievement_verification_status', 'terverifikasi')->id,
            'recorded_by' => $owner->id,
            'reviewer_id' => $owner->id,
            'reviewed_at' => now(),
        ]);

        return [$waka, $case];
    }

    private function createCase(Student $student, User $owner, string $number, string $status, string $date): BkCase
    {
        return BkCase::query()->create([
            'registration_number' => $number,
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('case_status', $status)->id,
            'service_date' => $date,
            'initial_info' => 'SENTINEL-NARASI',
            'initial_action' => 'Asesmen awal.',
            'waka_summary' => 'SENTINEL-NARASI-RINGKASAN',
            'internal_note' => 'SENTINEL-NARASI-INTERNAL',
            'created_by' => $owner->id,
        ]);
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
