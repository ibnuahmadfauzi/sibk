<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\StoreAchievementRequest;
use App\Http\Requests\StoreCaseRequest;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\StoreStudentDepartureRequest;
use App\Http\Requests\StoreWithdrawalProgressFollowUpRequest;
use App\Http\Requests\StoreWithdrawalProgressRequest;
use App\Http\Requests\UpdateCaseRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Http\Requests\UpdateWithdrawalProgressRequest;
use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ExternalTatibRecord;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\StudentDeparture;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\WithdrawalProgress;
use App\Support\ServiceRecordStatus;
use Carbon\CarbonImmutable;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SchoolTimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public static function calendarBoundaries(): iterable
    {
        yield 'sebelum tengah malam WIB' => ['2026-10-08 16:59:59', '2026-10-08'];
        yield 'tengah malam WIB' => ['2026-10-08 17:00:00', '2026-10-09'];
        yield 'pergantian tahun WIB' => ['2026-12-31 17:00:00', '2027-01-01'];
    }

    #[DataProvider('calendarBoundaries')]
    public function test_forms_use_wib_calendar_and_consultation_writes_keep_utc(string $instant, string $date): void
    {
        $this->travelTo(CarbonImmutable::parse($instant, 'UTC'));
        [$teacher, $student] = $this->fixture();
        $this->actingAs($teacher);

        foreach (['cases.create', 'consultations.create', 'withdrawals.create'] as $route) {
            $this->get(route($route, ['modal' => 1]))->assertOk()
                ->assertSee('value="'.$date.'"', false)
                ->assertSee('max="'.$date.'"', false);
        }

        $this->post(route('consultations.store'), [
            'student_id' => $student->id,
            'service_field_id' => $this->reference('service_field', 'pribadi'),
            'session_date' => $date,
            'problem' => 'Latar belakang uji WIB.',
            'handling' => 'Penanganan uji WIB.',
            'result' => 'Ringkasan uji WIB.',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $consultation = Consultation::query()->sole();
        $this->assertSame($date, $consultation->session_date->toDateString());
        $this->assertSame($instant, $consultation->getRawOriginal('created_at'));
        $this->assertSame($instant, $consultation->getRawOriginal('updated_at'));
        $this->assertSame('+00:00', $consultation->updated_at->format('P'));
    }

    public function test_all_calendar_validation_accepts_wib_today_and_rejects_tomorrow(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-08 17:30:00', 'UTC'));
        [$teacher] = $this->fixture();
        $route = new Route('POST', 'withdrawals/{withdrawal}', fn () => null);
        $route->bind(Request::create('/withdrawals/1', 'POST'));
        $route->setParameter('withdrawal', new WithdrawalProgress(['recorded_on' => '2026-10-08']));

        foreach ([
            StoreCaseRequest::class => 'service_date',
            UpdateCaseRequest::class => 'service_date',
            StoreConsultationRequest::class => 'session_date',
            UpdateConsultationRequest::class => 'session_date',
            StoreAchievementRequest::class => 'achievement_date',
            StoreStudentDepartureRequest::class => 'reported_at',
            StoreWithdrawalProgressRequest::class => 'recorded_on',
            UpdateWithdrawalProgressRequest::class => 'recorded_on',
            StoreWithdrawalProgressFollowUpRequest::class => 'follow_up_date',
        ] as $class => $field) {
            $request = new $class;
            $request->setUserResolver(fn () => $teacher);
            $request->setRouteResolver(fn () => $route);
            $rules = [$field => $request->rules()[$field]];
            $this->assertTrue(Validator::make([$field => '2026-10-09'], $rules)->passes(), $class);
            $this->assertTrue(Validator::make([$field => '2026-10-10'], $rules)->fails(), $class);
        }
    }

    public function test_default_student_availability_uses_effective_departure_date_in_wib(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-08 16:59:59', 'UTC'));
        [$teacher, $student] = $this->fixture();
        StudentDeparture::query()->create([
            'student_id' => $student->id,
            'departure_type' => StudentDeparture::TYPE_TRANSFER,
            'status' => StudentDeparture::STATUS_OFFICIAL,
            'reported_at' => '2026-10-08',
            'effective_date' => '2026-10-09',
            'recorded_by' => $teacher->id,
            'finalized_by' => $teacher->id,
        ]);
        $this->assertTrue(Student::query()->availableForService()->whereKey($student->id)->exists());
        $this->travelTo(CarbonImmutable::parse('2026-10-08 17:00:00', 'UTC'));
        $this->assertFalse(Student::query()->availableForService()->whereKey($student->id)->exists());
        $this->assertTrue(Student::query()->availableForService('2026-10-08')->whereKey($student->id)->exists());
    }

    public function test_case_completion_uses_wib_date_without_changing_timestamp_or_lock_token(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-08 17:30:00', 'UTC'));
        [$teacher, $student, $year, $classroom] = $this->fixture();
        $case = BkCase::query()->create([
            'registration_number' => 'WIB-2026-0001',
            'student_id' => $student->id, 'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id, 'created_by' => $teacher->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk'),
            'service_field_id' => $this->reference('service_field', 'pribadi'),
            'status_id' => $this->reference('case_status', ServiceRecordStatus::IN_PROGRESS),
            'service_date' => '2026-10-08', 'initial_info' => 'Latar uji.',
            'initial_action' => 'Penanganan uji.', 'resolution_summary' => 'Selesai.',
        ]);
        CaseAssignment::query()->create([
            'case_id' => $case->id, 'user_id' => $teacher->id,
            'assigned_by' => $teacher->id, 'reason' => 'Pengujian WIB.',
        ]);
        $this->actingAs($teacher)->patch(route('cases.complete', $case), [
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $case->refresh();
        $this->assertSame('2026-10-09', $case->closed_at->toDateString());
        $this->assertSame('2026-10-08 17:30:00', $case->getRawOriginal('updated_at'));
    }

    public function test_report_signature_converts_timestamp_to_wib_without_mutating_it(): void
    {
        $generatedAt = CarbonImmutable::parse('2026-12-31 17:30:00', 'UTC');
        $this->view('pages.reports.print._signature-block', [
            'generatedAt' => $generatedAt,
            'signatories' => [
                'left' => ['role' => 'Waka Kesiswaan', 'name' => 'Waka Uji'],
                'right' => ['role' => 'Koordinator BK', 'name' => 'Koordinator Uji'],
            ],
        ])->assertSee('Surabaya, 01 Januari 2027');
        $this->assertSame('2026-12-31 17:30:00', $generatedAt->format('Y-m-d H:i:s'));
    }

    public function test_profile_converts_internal_sync_time_but_keeps_etatib_wall_clock(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-08 17:30:00', 'UTC'));
        [$teacher, $student] = $this->fixture();
        ExternalTatibRecord::query()->create([
            'source_identifier' => 'WIB-ET-1', 'nisn' => $student->nisn,
            'student_id' => $student->id, 'occurred_at' => '2026-10-08 10:17:00',
            'violation_type' => 'Terlambat', 'category' => 'Kedisiplinan',
            'points' => 10, 'is_active' => true, 'synced_at' => now(),
        ]);

        $this->actingAs($teacher)->get(route('students.show', [$student, 'tab' => 'etatib']))
            ->assertOk()->assertSee('Terakhir disinkronkan: 09 Okt 2026')
            ->assertSee('10:17 (Kamis)')->assertDontSee('17:17 (Kamis)');
        $this->assertDatabaseHas('external_tatib_records', [
            'source_identifier' => 'WIB-ET-1', 'occurred_at' => '2026-10-08 10:17:00',
            'synced_at' => '2026-10-08 17:30:00',
        ]);
    }

    public function test_recent_activities_sort_internal_instants_and_etatib_local_time_consistently(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-08 17:30:00', 'UTC'));
        [$teacher, $student, $year, $classroom] = $this->fixture();
        Consultation::query()->create([
            'student_id' => $student->id, 'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'service_field_id' => $this->reference('service_field', 'pribadi'),
            'session_date' => '2026-10-09', 'problem' => 'Latar uji.',
            'handling' => 'Penanganan uji.', 'result' => 'Ringkasan uji.',
            'counselor_id' => $teacher->id,
        ]);
        ExternalTatibRecord::query()->create([
            'source_identifier' => 'WIB-ET-ORDER', 'nisn' => $student->nisn,
            'student_id' => $student->id, 'occurred_at' => '2026-10-09 00:10:00',
            'violation_type' => 'Terlambat', 'category' => 'Kedisiplinan',
            'points' => 10, 'is_active' => true, 'synced_at' => now(),
        ]);
        $this->actingAs($teacher)->get(route('students.show', $student))
            ->assertOk()->assertViewHas('recentActivities', fn ($activities): bool =>
                $activities->pluck('label')->all() === ['Konsultasi dicatat', 'Data e-Tatib diperbarui']
                && $activities[0]['date']->format('Y-m-d H:i') === '2026-10-09 00:30'
                && $activities[1]['date']->format('Y-m-d H:i') === '2026-10-09 00:10');
        $this->assertDatabaseHas('external_tatib_records', [
            'source_identifier' => 'WIB-ET-ORDER', 'occurred_at' => '2026-10-09 00:10:00',
        ]);
    }

    private function reference(string $category, string $code): int
    {
        return ReferenceValue::query()->where('category', $category)->where('code', $code)->sole()->id;
    }

    private function fixture(): array
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('slug', 'guru_bk')->sole());
        $year = AcademicYear::query()->create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $classroom = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 1', 'is_active' => true]);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Uji WIB', 'is_active' => true]);
        StudentClassMembership::query()->create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        TeacherAssignment::query()->create(['user_id' => $teacher->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'assigned_by' => $teacher->id]);

        return [$teacher, $student, $year, $classroom];
    }
}
