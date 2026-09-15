<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\StudentDeparture;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\CaseService;
use App\Services\ConsultationService;
use App\Services\OperationalReportRecapService;
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
        $audit = AuditLog::query()->where('action', 'student_departure.officialized')->sole();
        $this->assertSame('Keputusan resmi sekolah telah diterima.', $audit->after_values['decision_note']);
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
        $audit = AuditLog::query()->where('action', 'student_departure.recorded')->latest('id')->firstOrFail();
        $this->assertSame('Menunggu keputusan resmi sekolah.', $audit->before_values['recommendation_summary']);
        $this->assertSame('Rencana keluar dibatalkan sekolah.', $audit->before_values['decision_note']);
        $this->assertSame('Rencana baru sedang menunggu keputusan.', $audit->after_values['recommendation_summary']);
        $this->assertNull($audit->after_values['decision_note']);
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
        $audit = AuditLog::query()->where('action', 'student_departure.updated')->sole();
        $this->assertSame('Menunggu keputusan resmi sekolah.', $audit->before_values['recommendation_summary']);
        $this->assertSame('Ringkasan rekomendasi diperbarui.', $audit->after_values['recommendation_summary']);
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

    public function test_archived_records_and_official_departure_do_not_reappear_cross_surface(): void
    {
        [$teacher, $student, $case, $consultation] = $this->operationalFixture();
        $coordinator = $this->userWithRole('koordinator_bk');
        $case->delete();
        $consultation->delete();
        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'status' => StudentDeparture::STATUS_OFFICIAL,
            'reported_at' => today()->subDay(),
            'effective_date' => today(),
            'recorded_by' => $teacher->id,
            'finalized_by' => $coordinator->id,
            'finalized_at' => now(),
        ]);

        $this->actingAs($teacher)->get(route('cases.index'))
            ->assertOk()
            ->assertDontSee($case->registration_number)
            ->assertDontSee($consultation->registration_number);
        $this->actingAs($teacher)->get(route('students.index'))
            ->assertOk()
            ->assertDontSee($student->nisn);
        $this->actingAs($teacher)->get(route('reports.index', ['tab' => 'layanan']))
            ->assertOk()
            ->assertDontSee($case->registration_number)
            ->assertDontSee($consultation->registration_number);
        $this->actingAs($teacher)->get(route('cases.create', ['student_id' => $student->id]))
            ->assertForbidden();

        $this->actingAs($coordinator)->get(route('students.show', $student))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('Resmi keluar');
        $this->actingAs($coordinator)->get(route('cases.create', ['student_id' => $student->id]))
            ->assertForbidden();
    }

    public function test_future_service_on_or_after_known_departure_date_is_rejected(): void
    {
        [$teacher, $student, $departure] = $this->departureFixture();
        app(StudentDepartureService::class)->finalize($departure, [
            'decision' => StudentDeparture::STATUS_OFFICIAL,
            'effective_date' => '2026-09-20',
        ], $this->userWithRole('koordinator_bk'));

        try {
            app(ConsultationService::class)->create([
                'student_id' => $student->id,
                'service_field_id' => $this->reference('service_field', 'pribadi')->id,
                'status_id' => $this->reference('consultation_status', 'baru')->id,
                'topic' => 'Layanan setelah keluar',
                'session_date' => '2026-09-20',
            ], $teacher);
            $this->fail('Layanan dapat dijadwalkan pada tanggal keluar resmi.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('student_id', $exception->errors());
        }

        $this->assertDatabaseCount('consultations', 0);
    }

    public function test_retroactive_departure_excludes_later_records_from_service_recap(): void
    {
        [$teacher, $student, $case, $consultation] = $this->operationalFixture();
        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'status' => StudentDeparture::STATUS_OFFICIAL,
            'reported_at' => '2026-09-14',
            'effective_date' => '2026-09-10',
            'recorded_by' => $teacher->id,
            'finalized_by' => $this->userWithRole('koordinator_bk')->id,
            'finalized_at' => now(),
        ]);

        $yearId = $student->classMemberships()->value('academic_year_id');
        $report = app(OperationalReportRecapService::class)->build($teacher, [
            'tab' => OperationalReportRecapService::TAB_SERVICES,
            'academic_year_id' => $yearId,
        ]);

        $this->assertSame(0, $report['rows']->total());
        $this->assertTrue($case->exists);
        $this->assertTrue($consultation->exists);
    }

    public function test_existing_service_dates_cannot_move_to_or_after_official_departure(): void
    {
        [$teacher, $student, $case, $consultation] = $this->operationalFixture();
        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'status' => StudentDeparture::STATUS_OFFICIAL,
            'reported_at' => '2026-09-14',
            'effective_date' => '2026-09-14',
            'recorded_by' => $teacher->id,
            'finalized_by' => $this->userWithRole('koordinator_bk')->id,
            'finalized_at' => now(),
        ]);

        try {
            app(CaseService::class)->update($case, [
                'case_source_id' => $case->case_source_id,
                'service_field_id' => $case->service_field_id,
                'status_id' => $case->status_id,
                'service_date' => '2026-09-14',
                'initial_info' => $case->initial_info,
                'initial_action' => $case->initial_action,
            ], $teacher);
            $this->fail('Tanggal kasus dapat dipindah ke tanggal keluar resmi.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('service_date', $exception->errors());
        }

        try {
            app(ConsultationService::class)->update($consultation, [
                'case_id' => null,
                'service_field_id' => $consultation->service_field_id,
                'status_id' => $consultation->status_id,
                'topic' => $consultation->topic,
                'session_date' => '2026-09-14',
                'general_summary' => $consultation->general_summary,
                'change_reason' => 'Menjaga pengujian batas tanggal keluar resmi.',
            ], $teacher);
            $this->fail('Tanggal konsultasi dapat dipindah ke tanggal keluar resmi.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('session_date', $exception->errors());
        }
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

    /** @return array{User, Student, BkCase, Consultation} */
    private function operationalFixture(): array
    {
        [$teacher, $student] = $this->scopedStudentFixture();
        $case = app(CaseService::class)->createCase([
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2026-09-10',
            'initial_info' => 'Informasi awal.',
            'initial_action' => 'Asesmen awal.',
        ], $teacher);
        $consultation = app(ConsultationService::class)->create([
            'student_id' => $student->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('consultation_status', 'selesai')->id,
            'topic' => 'Persiapan perpindahan',
            'session_date' => '2026-09-11',
            'general_summary' => 'Ringkasan layanan.',
        ], $teacher);

        return [$teacher, $student, $case, $consultation];
    }

    private function reference(string $category, string $code): ReferenceValue
    {
        return ReferenceValue::query()
            ->where('category', $category)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
