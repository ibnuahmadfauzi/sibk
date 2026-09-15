<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\StudentDeparture;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\StudentDepartureService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class StudentDepartureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
        $this->travelTo('2026-09-14 08:00:00');
    }

    public function test_one_student_has_only_one_departure_process(): void
    {
        [$teacher, $student] = $this->scopedStudentFixture();

        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'status' => StudentDeparture::STATUS_IN_PROGRESS,
            'reported_at' => '2026-09-14',
            'recorded_by' => $teacher->id,
        ]);

        $this->expectException(QueryException::class);
        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_WITHDRAWAL,
            'status' => StudentDeparture::STATUS_IN_PROGRESS,
            'reported_at' => '2026-09-14',
            'recorded_by' => $teacher->id,
        ]);
    }

    public function test_teacher_records_process_without_deactivating_student(): void
    {
        [$teacher, $student] = $this->scopedStudentFixture();

        $this->actingAs($teacher)->post(route('students.departure.store', $student), [
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'reported_at' => '2026-09-14',
            'recommendation_summary' => 'Menunggu kelengkapan keputusan resmi sekolah.',
        ])->assertRedirect(route('students.show', $student));

        $this->assertDatabaseHas('student_departures', [
            'student_id' => $student->id,
            'status' => StudentDeparture::STATUS_IN_PROGRESS,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student_departure.recorded']);
        $this->assertTrue(Student::query()->availableForService()->whereKey($student->id)->exists());
        $this->assertTrue($student->refresh()->is_active);
    }

    public function test_only_coordinator_finalizes_and_official_exit_stops_new_service_scope(): void
    {
        [$teacher, $student, $departure] = $this->departureFixture();
        $coordinator = $this->userWithRole('koordinator_bk');

        $this->actingAs($teacher)->post(route('students.departure.finalize', $student), [
            'decision' => StudentDeparture::STATUS_OFFICIAL,
            'effective_date' => '2026-09-20',
        ])->assertForbidden();

        $this->actingAs($coordinator)->post(route('students.departure.finalize', $student), [
            'decision' => StudentDeparture::STATUS_OFFICIAL,
            'effective_date' => '2026-09-20',
            'decision_note' => 'Keputusan resmi sekolah telah diterima.',
        ])->assertRedirect(route('students.show', $student));

        $this->assertTrue(Student::query()->availableForService('2026-09-19')->whereKey($student->id)->exists());
        $this->assertFalse(Student::query()->availableForService('2026-09-20')->whereKey($student->id)->exists());
        $this->assertTrue(Student::query()->whereKey($student->id)->exists());
        $this->assertSame(StudentDeparture::STATUS_OFFICIAL, $departure->refresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student_departure.officialized']);
    }

    public function test_cancelled_process_reopens_the_same_row_and_keeps_first_dates(): void
    {
        [$teacher, $student, $departure] = $this->departureFixture();
        $coordinator = $this->userWithRole('koordinator_bk');
        $service = app(StudentDepartureService::class);
        $id = $departure->id;
        $createdAt = $departure->created_at?->toISOString();
        $reportedAt = $departure->reported_at?->toDateString();

        $service->finalize($departure, [
            'decision' => StudentDeparture::STATUS_CANCELLED,
            'effective_date' => null,
            'decision_note' => 'Rencana keluar dibatalkan sekolah.',
        ], $coordinator);
        $reopened = $service->record($student, [
            'departure_type' => StudentDeparture::TYPE_WITHDRAWAL,
            'reported_at' => '2026-09-20',
            'recommendation_summary' => 'Rencana baru sedang menunggu keputusan.',
        ], $teacher);

        $this->assertSame($id, $reopened->id);
        $this->assertSame($createdAt, $reopened->created_at?->toISOString());
        $this->assertSame($reportedAt, $reopened->reported_at?->toDateString());
        $this->assertSame(StudentDeparture::STATUS_IN_PROGRESS, $reopened->status);
        $this->assertNull($reopened->effective_date);
        $this->assertNull($reopened->finalized_by);
        $this->assertNull($reopened->finalized_at);
        $this->assertNull($reopened->decision_note);
        $this->assertDatabaseCount('student_departures', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student_departure.cancelled']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student_departure.recorded']);
    }

    public function test_scoped_teacher_updates_only_an_in_progress_process(): void
    {
        [$teacher, $student, $departure] = $this->departureFixture();

        $this->actingAs($teacher)->patch(route('students.departure.update', $student), [
            'departure_type' => StudentDeparture::TYPE_OTHER,
            'recommendation_summary' => 'Ringkasan rekomendasi diperbarui.',
            'reported_at' => '2026-09-01',
            'status' => StudentDeparture::STATUS_OFFICIAL,
        ])->assertRedirect(route('students.show', $student));

        $departure->refresh();
        $this->assertSame(StudentDeparture::TYPE_OTHER, $departure->departure_type);
        $this->assertSame('2026-09-14', $departure->reported_at?->toDateString());
        $this->assertSame(StudentDeparture::STATUS_IN_PROGRESS, $departure->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student_departure.updated']);
    }

    public function test_forged_scope_and_forbidden_roles_are_rejected(): void
    {
        [$teacher, $student, $departure] = $this->departureFixture();
        $outsideTeacher = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $admin = $this->userWithRole('admin_it');
        $payload = [
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'reported_at' => '2026-09-14',
        ];

        $this->actingAs($outsideTeacher)->post(route('students.departure.store', $student), $payload)->assertForbidden();
        $this->actingAs($coordinator)->post(route('students.departure.store', $student), $payload)->assertForbidden();
        $this->actingAs($waka)->post(route('students.departure.store', $student), $payload)->assertForbidden();
        $this->actingAs($admin)->post(route('students.departure.store', $student), $payload)->assertForbidden();

        foreach ([$teacher, $waka, $admin] as $forbidden) {
            $this->actingAs($forbidden)->post(route('students.departure.finalize', $student), [
                'decision' => StudentDeparture::STATUS_CANCELLED,
            ])->assertForbidden();
        }

        $this->assertSame(StudentDeparture::STATUS_IN_PROGRESS, $departure->refresh()->status);
        $this->assertDatabaseCount('student_departures', 1);
    }

    public function test_existing_in_progress_process_cannot_create_a_second_row(): void
    {
        [$teacher, $student] = $this->scopedStudentFixture();
        $service = app(StudentDepartureService::class);
        $service->record($student, [
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'reported_at' => '2026-09-14',
        ], $teacher);

        try {
            $service->record($student, [
                'departure_type' => StudentDeparture::TYPE_WITHDRAWAL,
                'reported_at' => '2026-09-14',
            ], $teacher);
            $this->fail('Proses aktif dapat direkam dua kali.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('departure', $exception->errors());
        }

        $this->assertDatabaseCount('student_departures', 1);
    }

    public function test_requests_reject_invalid_type_future_date_and_missing_official_date(): void
    {
        [$teacher, $student] = $this->scopedStudentFixture();

        $this->actingAs($teacher)->post(route('students.departure.store', $student), [
            'departure_type' => 'tidak_dikenal',
            'reported_at' => '2026-09-15',
            'recommendation_summary' => str_repeat('a', 501),
        ])->assertSessionHasErrors(['departure_type', 'reported_at', 'recommendation_summary']);

        $departure = app(StudentDepartureService::class)->record($student, [
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'reported_at' => '2026-09-14',
        ], $teacher);
        $coordinator = $this->userWithRole('koordinator_bk');

        $this->actingAs($coordinator)->post(route('students.departure.finalize', $student), [
            'decision' => StudentDeparture::STATUS_OFFICIAL,
        ])->assertSessionHasErrors('effective_date');

        $this->assertSame(StudentDeparture::STATUS_IN_PROGRESS, $departure->refresh()->status);
    }

    public function test_official_student_keeps_history_but_is_removed_from_every_new_service_surface(): void
    {
        [$teacher, $student, $departure] = $this->departureFixture();
        $coordinator = $this->userWithRole('koordinator_bk');
        app(StudentDepartureService::class)->finalize($departure, [
            'decision' => StudentDeparture::STATUS_OFFICIAL,
            'effective_date' => '2026-09-14',
        ], $coordinator);

        $this->actingAs($teacher)->get(route('students.index'))
            ->assertOk()
            ->assertDontSee($student->name);
        $this->actingAs($teacher)->get(route('students.show', $student))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('Resmi keluar')
            ->assertDontSee('Buat Kasus')
            ->assertDontSee('Catat Konsultasi')
            ->assertDontSee('Catat Prestasi');
        $this->actingAs($teacher)->get(route('cases.create'))
            ->assertOk()
            ->assertDontSee($student->name);
        $this->actingAs($teacher)->get(route('consultations.create'))
            ->assertOk()
            ->assertDontSee($student->name);
        $this->actingAs($teacher)->get(route('achievements.create'))
            ->assertOk()
            ->assertDontSee($student->name);
    }

    /** @return array{User, Student} */
    private function scopedStudentFixture(): array
    {
        $teacher = $this->userWithRole('guru_bk');
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'XI RPL 1',
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Proses Keluar',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-01',
            'decision_number' => 'SK-DEPARTURE',
            'assigned_by' => $teacher->id,
        ]);

        return [$teacher, $student];
    }

    /** @return array{User, Student, StudentDeparture} */
    private function departureFixture(): array
    {
        [$teacher, $student] = $this->scopedStudentFixture();
        $departure = app(StudentDepartureService::class)->record($student, [
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'reported_at' => '2026-09-14',
            'recommendation_summary' => 'Menunggu keputusan resmi sekolah.',
        ], $teacher);

        return [$teacher, $student, $departure];
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
