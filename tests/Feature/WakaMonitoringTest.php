<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\WakaMonitoringRequest;
use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use App\Services\WakaMonitoringService;
use App\Support\ServiceRecordStatus;
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
            ->assertSee('Murid dengan Kasus');

        $this->actingAs($waka)
            ->get(route('waka.monitoring.handling'))
            ->assertRedirect(route('waka.reports', ['tab' => 'penanganan']));

        $this->actingAs($waka)
            ->get(route('waka.reports', ['tab' => 'penanganan']))
            ->assertStatus(200)
            ->assertSee('Monitoring Penanganan');
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
            'status_id' => $this->ref('case_status', 'sedang_diproses')->id,
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

    public function test_sort_links_expose_direction_without_absolute_hidden_text(): void
    {
        [$waka, $case, $owner] = $this->wakaCaseFixture();
        $this->assignOwner($case, $owner);

        $this->actingAs($waka)
            ->get(route('waka.monitoring.students', [
                'sort' => 'murid',
                'direction' => 'asc',
            ]))
            ->assertOk()
            ->assertSee('aria-sort="ascending"', false)
            ->assertSee('aria-label="Murid, diurutkan naik"', false)
            ->assertDontSee('<span class="visually-hidden">, diurutkan', false);

        $this->actingAs($waka)
            ->get(route('waka.reports', [
                'tab' => 'penanganan',
                'sort' => 'tanggal',
                'direction' => 'desc',
            ]))
            ->assertOk()
            ->assertSee('aria-sort="descending"', false)
            ->assertSee('aria-label="Tanggal, diurutkan turun"', false)
            ->assertDontSee('<span class="visually-hidden">, diurutkan', false);
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

    public function test_archived_case_is_absent_from_waka_dashboard_monitoring_and_report(): void
    {
        [$waka, $case, $owner, $student] = $this->wakaCaseFixture(studentName: 'Murid Kasus Arsip');
        $this->assignOwner($case, $owner);
        $case->delete();

        $this->actingAs($waka)->get(route('dashboard.preview'))
            ->assertOk()
            ->assertDontSee($student->name);
        $this->actingAs($waka)->get(route('waka.monitoring.students'))
            ->assertOk()
            ->assertDontSee($student->name);
        $this->actingAs($waka)->get(route('waka.reports', ['tab' => 'penanganan']))
            ->assertOk()
            ->assertDontSee($student->name);
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
            'status_id' => $this->ref('case_status', 'sedang_diproses')->id,
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

    public function test_active_waka_can_read_all_service_details_without_coordination_but_cannot_mutate_them(): void
    {
        [$waka, $case, $owner, $student] = $this->wakaCaseFixture();
        $this->assignOwner($case, $owner);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'XI WAKA 1',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->academicYear->id,
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);
        $secondCase = BkCase::query()->create([
            'registration_number' => 'K-2026-WAKA-02',
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'case_source_id' => $this->ref('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->ref('service_field', 'pribadi')->id,
            'status_id' => $this->ref('case_status', 'sedang_diproses')->id,
            'service_date' => '2026-09-06',
            'initial_info' => 'Informasi awal kedua.',
            'initial_action' => 'Asesmen kedua.',
            'created_by' => $owner->id,
        ]);
        $this->assignOwner($secondCase, $owner);
        $consultations = collect(['Pertama', 'Kedua'])->map(fn (string $label): Consultation => Consultation::query()->forceCreate([
            'student_id' => $student->id,
            'service_field_id' => $this->ref('service_field', 'pribadi')->id,
            'status_id' => $this->ref('consultation_status', 'sedang_diproses')->id,
            'topic' => "Konsultasi {$label}",
            'session_date' => '2026-09-05',
            'problem' => "Permasalahan {$label}.",
            'handling' => "Penanganan {$label}.",
            'result' => "Hasil {$label}.",
            'counselor_id' => $owner->id,
        ]));
        $consultation = $consultations->firstOrFail();
        $secondConsultation = $consultations->last();

        $this->actingAs($waka)->get(route('cases.index'))
            ->assertOk()
            ->assertViewHas('cases', fn ($cases): bool => collect($cases->items())->every(
                fn (BkCase $listedCase): bool => array_keys($listedCase->getAttributes()) === [
                    'id', 'student_id', 'temporary_student_id', 'service_date',
                    'status_id', 'service_field_id', 'follow_up_type_id',
                ]
                    && array_keys($listedCase->student->getAttributes()) === ['id', 'name']
                    && array_keys($listedCase->student->classMemberships->first()->getAttributes()) === [
                        'id', 'student_id', 'classroom_id', 'academic_year_id', 'effective_from', 'effective_until',
                    ],
            ));
        $this->actingAs($waka)->get(route('cases.index', ['tab' => 'konsultasi']))
            ->assertOk()
            ->assertDontSee('Permasalahan Pertama.')
            ->assertViewHas('consultations', fn ($listedConsultations): bool => collect($listedConsultations->items())->every(
                fn (Consultation $listedConsultation): bool => array_keys($listedConsultation->getAttributes()) === [
                    'id', 'student_id', 'temporary_student_id', 'service_field_id', 'session_date', 'counselor_id',
                ]
                    && array_keys($listedConsultation->student->getAttributes()) === ['id', 'name']
                    && array_keys($listedConsultation->student->classMemberships->first()->getAttributes()) === [
                        'id', 'student_id', 'classroom_id', 'academic_year_id', 'effective_from', 'effective_until',
                    ],
            ));
        $this->assertSame(2, BkCase::query()->accessibleTo($waka)->count());
        $this->assertSame(2, Consultation::query()->accessibleTo($waka)->count());

        $this->actingAs($waka)->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('Informasi awal rahasia.')
            ->assertSee('Asesmen awal.')
            ->assertDontSee('SENTINEL-INTERNAL');
        $this->actingAs($waka)->get(route('consultations.show', $consultation))
            ->assertOk()
            ->assertSee('XI WAKA 1')
            ->assertSee('Permasalahan Pertama.')
            ->assertSee('Penanganan Pertama.')
            ->assertSee('Hasil Pertama.');
        $this->actingAs($waka)->get(route('cases.show', $secondCase))->assertOk();
        $this->actingAs($waka)->get(route('consultations.show', $secondConsultation))->assertOk();
        $this->actingAs($waka)->get(route('cases.show', $case))->assertOk();

        $this->assertSame(2, $this->auditCount($waka, 'case.viewed_by_waka', $case->id));
        $this->assertSame(1, $this->auditCount($waka, 'case.viewed_by_waka', $secondCase->id));
        $this->assertSame(1, $this->auditCount($waka, 'consultation.viewed_by_waka', $consultation->id));
        $this->assertSame(1, $this->auditCount($waka, 'consultation.viewed_by_waka', $secondConsultation->id));

        $this->actingAs($waka)->get(route('cases.create'))->assertForbidden();
        $this->actingAs($waka)->get(route('cases.resolve.form', $case))->assertForbidden();
        $this->actingAs($waka)->post(route('cases.follow-ups.store', $case))->assertForbidden();
        $this->actingAs($waka)->delete(route('cases.destroy', $case))->assertForbidden();
        $this->actingAs($waka)->get(route('consultations.create'))->assertForbidden();
        $this->actingAs($waka)->patch(route('consultations.update', $consultation))->assertForbidden();
        $this->actingAs($waka)->delete(route('consultations.destroy', $consultation))->assertForbidden();

        $admin = $this->createUserWithRole('admin_it');
        $this->actingAs($admin)->get(route('cases.show', $case))->assertForbidden();
        $this->actingAs($admin)->get(route('consultations.show', $consultation))->assertForbidden();
    }

    public function test_waka_csv_never_exports_service_narratives(): void
    {
        [$waka, $case, $owner] = $this->wakaCaseFixture();
        $this->assignOwner($case, $owner);
        $case->update([
            'initial_info' => 'SENTINEL-INITIAL-INFO',
            'initial_action' => 'SENTINEL-INITIAL-ACTION',
            'resolution_summary' => 'SENTINEL-RESOLUTION',
        ]);

        $content = $this->actingAs($waka)
            ->get(route('waka.monitoring.export', ['format' => 'csv']))
            ->streamedContent();

        foreach (['SENTINEL-INITIAL-INFO', 'SENTINEL-INITIAL-ACTION', 'SENTINEL-RESOLUTION'] as $narrative) {
            $this->assertStringNotContainsString($narrative, $content);
        }
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
        [$waka, $case, $owner, $student] = $this->wakaCaseFixture(studentName: '=Murid Berbahaya');
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
        $this->assertArrayNotHasKey('Ringkasan Waka', $row);
    }

    public function test_students_page_groups_multiple_cases_into_one_student_row(): void
    {
        [$waka, $student] = $this->wakaStudentWithCases();

        $this->actingAs($waka)
            ->get('/waka/students-with-cases')
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('2 kasus')
            ->assertSee('1 masih aktif');
    }

    public function test_reports_page_uses_goal_based_tabs(): void
    {
        $waka = $this->createUserWithRole('waka_kesiswaan');

        $this->actingAs($waka)
            ->get('/waka/reports?tab=penanganan')
            ->assertOk()
            ->assertSeeInOrder(['Monitoring Penanganan', 'Rekap Periode', 'Laporan Akhir'])
            ->assertDontSee('Pelanggaran per Murid')
            ->assertDontSee('Poin Pelanggaran');
    }

    public function test_waka_portal_access_rules_are_enforced_per_page(): void
    {
        $this->get('/waka/students-with-cases')->assertRedirect(route('login'));
        $this->get('/waka/reports?tab=penanganan')->assertRedirect(route('login'));

        $inactiveWaka = $this->createUserWithRole('waka_kesiswaan');
        $inactiveWaka->update(['is_active' => false, 'deactivated_at' => now()]);
        $this->actingAs($inactiveWaka)
            ->get('/waka/reports?tab=penanganan')
            ->assertRedirect(route('login'));

        foreach (['guru_bk', 'admin_it'] as $role) {
            $user = $this->createUserWithRole($role);
            $this->actingAs($user)->get('/waka/students-with-cases')->assertForbidden();
            $this->actingAs($user)->get('/waka/reports?tab=penanganan')->assertForbidden();
            $this->actingAs($user)->get('/waka/handling-reports/export')->assertForbidden();
        }

        $coordinator = $this->createUserWithRole('koordinator_bk');
        $this->actingAs($coordinator)->get('/waka/students-with-cases')->assertForbidden();
        $this->actingAs($coordinator)->get('/waka/reports?tab=penanganan')->assertForbidden();
        $this->actingAs($coordinator)->get('/waka/reports?tab=rekap')->assertForbidden();
        $this->actingAs($coordinator)->get('/waka/handling-reports/export')->assertForbidden();
        $this->actingAs($coordinator)
            ->get('/waka/reports?tab=laporan-akhir')
            ->assertOk()
            ->assertSee('Dalam pengembangan');
    }

    public function test_legacy_bookmark_preserves_validated_monitoring_filters(): void
    {
        $waka = $this->createUserWithRole('waka_kesiswaan');
        $query = [
            'period' => '2026-09',
            'status' => ServiceRecordStatus::IN_PROGRESS,
            'sort' => 'murid',
            'direction' => 'asc',
            'page' => 2,
        ];

        $this->actingAs($waka)
            ->get('/waka/handling-reports?'.http_build_query($query))
            ->assertRedirect('/waka/reports?'.http_build_query(['tab' => 'penanganan', ...$query]));
    }

    public function test_forged_sort_identifiers_are_rejected_per_page(): void
    {
        $waka = $this->createUserWithRole('waka_kesiswaan');

        $this->actingAs($waka)
            ->from('/waka/reports?tab=penanganan')
            ->get('/waka/reports?tab=penanganan&sort=cases.registration_number')
            ->assertRedirect('/waka/reports?tab=penanganan')
            ->assertSessionHasErrors('sort');

        $this->actingAs($waka)
            ->from('/waka/students-with-cases')
            ->get('/waka/students-with-cases?sort=bidang')
            ->assertRedirect('/waka/students-with-cases')
            ->assertSessionHasErrors('sort');
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

    private function auditCount(User $actor, string $action, int $auditableId): int
    {
        return $actor->auditLogs()
            ->where('action', $action)
            ->where('auditable_id', $auditableId)
            ->count();
    }

    /** @return array{User, Student} */
    private function wakaStudentWithCases(): array
    {
        $waka = $this->createUserWithRole('waka_kesiswaan', 'Waka Daftar Murid');
        $owner = $this->createUserWithRole('guru_bk', 'Guru BK Daftar Murid');
        $student = Student::query()->create([
            'nisn' => '8877665544',
            'name' => 'Murid Dua Kasus',
            'is_active' => true,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'XI RPL 1',
            'is_active' => true,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $this->academicYear->id,
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);

        foreach ([
            ['number' => 'K-2026-GROUP-01', 'status' => 'sedang_diproses', 'date' => '2026-09-10', 'closed_at' => null],
            ['number' => 'K-2026-GROUP-02', 'status' => 'selesai', 'date' => '2026-09-05', 'closed_at' => '2026-09-06'],
        ] as $item) {
            $case = BkCase::query()->create([
                'registration_number' => $item['number'],
                'student_id' => $student->id,
                'case_source_id' => $this->ref('case_source', 'temuan_guru_bk')->id,
                'service_field_id' => $this->ref('service_field', 'pribadi')->id,
                'status_id' => $this->ref('case_status', $item['status'])->id,
                'service_date' => $item['date'],
                'initial_info' => 'Informasi rahasia.',
                'initial_action' => 'Asesmen awal.',
                'waka_summary' => 'Ringkasan aman.',
                'closed_at' => $item['closed_at'],
                'created_by' => $owner->id,
            ]);
            $this->assignOwner($case, $owner);
        }

        return [$waka, $student];
    }
}
