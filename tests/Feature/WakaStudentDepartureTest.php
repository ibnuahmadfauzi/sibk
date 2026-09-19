<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\StudentDeparture;
use App\Models\User;
use App\Support\ServiceRecordStatus;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WakaStudentDepartureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
        $this->travelTo('2026-09-14 08:00:00');
    }

    public function test_waka_reads_operational_departure_details_without_mutation_or_private_case_narrative(): void
    {
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Proses Keluar');
        $teacher = $this->userWithRole('guru_bk', 'Guru Pencatat');
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Pemutus');
        [$student, $classroom] = $this->studentFixture();
        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'status' => StudentDeparture::STATUS_OFFICIAL,
            'reported_at' => '2026-09-10',
            'recommendation_summary' => 'Rekomendasi operasional proses pindah.',
            'effective_date' => '2026-09-20',
            'recorded_by' => $teacher->id,
            'finalized_by' => $coordinator->id,
            'finalized_at' => '2026-09-14 07:00:00',
            'decision_note' => 'Surat keputusan sekolah telah diterima.',
        ]);
        BkCase::query()->create([
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::IN_PROGRESS)->id,
            'registration_number' => 'K-PRIVATE-001',
            'service_date' => '2026-09-01',
            'initial_info' => 'NARASI-PRIVAT-TIDAK-BOLEH-TAMPIL',
            'initial_action' => 'Tindakan privat.',
            'created_by' => $teacher->id,
        ]);

        $this->actingAs($waka)->get(route('waka.student-departures.index'))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee($classroom->name)
            ->assertSee('Pindah')
            ->assertSee('Resmi keluar')
            ->assertSee('10 Sep 2026')
            ->assertSee('20 Sep 2026')
            ->assertSee('Rekomendasi operasional proses pindah.')
            ->assertSee('Surat keputusan sekolah telah diterima.')
            ->assertSee($teacher->name)
            ->assertSee($coordinator->name)
            ->assertDontSee('NARASI-PRIVAT-TIDAK-BOLEH-TAMPIL')
            ->assertDontSee('Tetapkan Batal')
            ->assertDontSee('Tetapkan Resmi Keluar');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'waka.student_departures.viewed',
            'actor_id' => $waka->id,
        ]);
    }

    public function test_departure_page_is_waka_only(): void
    {
        foreach (['guru_bk', 'koordinator_bk', 'admin_it'] as $role) {
            $this->actingAs($this->userWithRole($role, 'Pengguna '.$role))
                ->get(route('waka.student-departures.index'))
                ->assertForbidden();
        }
    }

    public function test_waka_departure_page_keeps_all_process_statuses(): void
    {
        $waka = $this->userWithRole('waka_kesiswaan', 'Waka Semua Status');
        $teacher = $this->userWithRole('guru_bk', 'Guru Semua Status');
        $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Semua Status');

        foreach ([
            ['name' => 'Murid Proses', 'nisn' => '1000000001', 'status' => StudentDeparture::STATUS_IN_PROGRESS],
            ['name' => 'Murid Batal', 'nisn' => '1000000002', 'status' => StudentDeparture::STATUS_CANCELLED],
            ['name' => 'Murid Resmi', 'nisn' => '1000000003', 'status' => StudentDeparture::STATUS_OFFICIAL],
        ] as $item) {
            $student = Student::query()->create([
                'nisn' => $item['nisn'],
                'name' => $item['name'],
                'is_active' => true,
            ]);
            StudentDeparture::query()->create([
                'student_id' => $student->id,
                'departure_type' => StudentDeparture::TYPE_TRANSFER,
                'status' => $item['status'],
                'reported_at' => '2026-09-10',
                'effective_date' => $item['status'] === StudentDeparture::STATUS_OFFICIAL ? '2026-09-14' : null,
                'recorded_by' => $teacher->id,
                'finalized_by' => $item['status'] === StudentDeparture::STATUS_IN_PROGRESS ? null : $coordinator->id,
                'finalized_at' => $item['status'] === StudentDeparture::STATUS_IN_PROGRESS ? null : now(),
            ]);
        }

        $this->actingAs($waka)->get(route('waka.student-departures.index'))
            ->assertOk()
            ->assertSeeInOrder(['Murid Proses', 'Dalam proses'])
            ->assertSeeInOrder(['Murid Batal', 'Batal'])
            ->assertSeeInOrder(['Murid Resmi', 'Resmi keluar']);
    }

    /** @return array{Student, Classroom} */
    private function studentFixture(): array
    {
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'XI RPL Waka',
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'nisn' => '0098765432',
            'name' => 'Murid Waka Keluar',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);

        return [$student, $classroom];
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
