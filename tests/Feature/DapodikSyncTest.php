<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Integrations\Dapodik\ConfiguredDapodikConnector;
use App\Integrations\Dapodik\DapodikConnector;
use App\Integrations\Dapodik\DapodikDriver;
use App\Integrations\Dapodik\DapodikSnapshot;
use App\Integrations\Dapodik\DapodikSnapshotValidator;
use App\Integrations\IntegrationBusyException;
use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationDriverRegistry;
use App\Integrations\IntegrationEndpointPolicy;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Integrations\IntegrationProbeResult;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Integrations\IntegrationSnapshotEvidence;
use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\DapodikSyncPreviewItem;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\FollowUp;
use App\Models\IntegrationSetting;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DapodikReconciliationService;
use App\Services\DapodikSyncService;
use App\Services\IntegrationSettingService;
use App\Services\StudentIdentityService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\Support\DapodikSnapshotRegressionImporter;
use Tests\TestCase;

class DapodikSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_full_snapshot_imports_master_data_and_is_idempotent(): void
    {
        $admin = $this->userWithRole('admin_it');
        $this->importSnapshot($this->snapshot(), $admin);
        $this->importSnapshot($this->snapshot(), $admin);

        $this->assertDatabaseCount('academic_years', 1);
        $this->assertDatabaseCount('classrooms', 1);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('student_class_memberships', 1);
        $this->assertDatabaseHas('students', ['nisn' => '0012345678', 'name' => 'Nama Resmi', 'is_active' => true]);
    }

    public function test_production_has_no_public_dapodik_snapshot_importer_bypass(): void
    {
        $this->assertFileDoesNotExist(app_path('Services/DapodikSnapshotImporter.php'));
    }

    public function test_dapodik_validator_rejects_scalar_and_null_items_as_contract_invalid(): void
    {
        $snapshot = $this->evidencedSnapshot();
        $collections = [
            'academicYears' => $snapshot->academicYears,
            'classrooms' => $snapshot->classrooms,
            'students' => $snapshot->students,
            'memberships' => $snapshot->memberships,
        ];

        foreach (['scalar', null] as $invalidItem) {
            foreach (array_keys($collections) as $collection) {
                $invalidCollections = $collections;
                $invalidCollections[$collection] = [$invalidItem];
                $invalid = new DapodikSnapshot(
                    $snapshot->isFullSnapshot,
                    $invalidCollections['academicYears'],
                    $invalidCollections['classrooms'],
                    $invalidCollections['students'],
                    $invalidCollections['memberships'],
                    $snapshot->evidence,
                );

                try {
                    $this->admittedValidator()->validate($invalid, $this->runtimeConfiguration());
                    $this->fail(sprintf('Item %s pada %s harus ditolak.', get_debug_type($invalidItem), $collection));
                } catch (IntegrationConfigurationException $exception) {
                    $this->assertSame('contract_invalid', $exception->resultCode());
                }
            }
        }
    }

    public function test_dapodik_audit_failure_leaves_no_running_sync_run(): void
    {
        $admin = $this->userWithRole('admin_it');
        $this->app->instance(AuditService::class, new class extends AuditService
        {
            public function record(
                string $action,
                Model $auditable,
                string $summary,
                ?User $actor = null,
                ?array $before = null,
                ?array $after = null,
                ?Request $request = null,
            ): AuditLog {
                throw new RuntimeException('Synthetic persistent audit failure.');
            }
        });

        $run = app(DapodikSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_FAILED, $run->status);
        $this->assertNotNull($run->finished_at);
        $this->assertDatabaseMissing('external_sync_runs', [
            'source' => 'dapodik',
            'status' => ExternalSyncRun::STATUS_RUNNING,
        ]);
    }

    public function test_snapshot_validator_fails_closed_without_contract_admission(): void
    {
        $snapshot = $this->snapshot();
        $evidence = new IntegrationSnapshotEvidence('school-01', 'contract-v1', 'full', 1, 4, 512);
        $this->assertSame(
            ['reportedSourceIdentifier', 'contractMarker', 'completenessMarker', 'pageCount', 'recordCount', 'processedBytes'],
            array_keys(get_object_vars($evidence)),
        );
        $snapshot = new DapodikSnapshot(
            $snapshot->isFullSnapshot,
            $snapshot->academicYears,
            $snapshot->classrooms,
            $snapshot->students,
            $snapshot->memberships,
            $evidence,
        );

        $this->expectException(IntegrationConfigurationException::class);
        (new DapodikSnapshotValidator)->validate($snapshot, $this->runtimeConfiguration());
    }

    public function test_snapshot_validator_rejects_oversized_data_and_classroom_source_moving_year(): void
    {
        $year = AcademicYear::query()->create([
            'dapodik_id' => 'year-old',
            'name' => '2025/2026',
            'is_active' => true,
        ]);
        Classroom::query()->create([
            'dapodik_id' => 'class-1',
            'academic_year_id' => $year->id,
            'name' => 'X RPL 1',
            'is_active' => true,
        ]);
        $snapshot = $this->snapshot();
        $snapshot = new DapodikSnapshot(
            $snapshot->isFullSnapshot,
            $snapshot->academicYears,
            $snapshot->classrooms,
            $snapshot->students,
            $snapshot->memberships,
            new IntegrationSnapshotEvidence('school-01', 'contract-v1', 'full', 1, 4, 2049),
        );
        $validator = new DapodikSnapshotValidator('contract-v1', ['full' => true, 'partial' => false], 2, 10, 2048);

        try {
            $validator->validate($snapshot, $this->runtimeConfiguration());
            $this->fail('Snapshot oversized harus ditolak.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('response_too_large', $exception->resultCode());
        }

        $snapshot = new DapodikSnapshot(
            $snapshot->isFullSnapshot,
            $snapshot->academicYears,
            $snapshot->classrooms,
            $snapshot->students,
            $snapshot->memberships,
            new IntegrationSnapshotEvidence('school-01', 'contract-v1', 'full', 1, 4, 512),
        );
        $this->expectException(IntegrationConfigurationException::class);
        $validator->validate($snapshot, $this->runtimeConfiguration());
    }

    public function test_configured_connector_requires_active_current_configuration_and_fence(): void
    {
        $driver = new Task10DapodikDriver($this->evidencedSnapshot());
        $connector = $this->configuredConnector($driver);

        try {
            app(IntegrationOperationLock::class)->run('dapodik', fn ($context) => $connector->fetchSnapshot($context));
            $this->fail('Konfigurasi tidak aktif harus ditolak.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('not_active', $exception->resultCode());
            $this->assertSame(0, $driver->fetchCalls);
        }

        $this->seedActiveSetting($driver);
        $driver->onFetch = static fn () => IntegrationSetting::query()
            ->where('provider', 'dapodik')
            ->increment('operation_fence_version');

        $this->expectException(IntegrationConfigurationException::class);
        app(IntegrationOperationLock::class)->run('dapodik', fn ($context) => $connector->fetchSnapshot($context));
    }

    public function test_direct_dapodik_post_is_blocked_for_a_nonconfigured_connector_before_fetch(): void
    {
        $admin = $this->userWithRole('admin_it');
        $old = Student::query()->create(['nisn' => '0090909001', 'name' => 'Data Lama', 'is_active' => true]);
        $connector = new class($this->snapshot()) implements DapodikConnector
        {
            public int $fetchCalls = 0;

            public function __construct(private readonly DapodikSnapshot $snapshot) {}

            public function fetchSnapshot(IntegrationOperationContext $context): DapodikSnapshot
            {
                $this->fetchCalls++;

                return $this->snapshot;
            }
        };
        $this->app->instance(DapodikConnector::class, $connector);

        $this->actingAs($admin)->post(route('data-master.dapodik.sync'))
            ->assertRedirect()
            ->assertSessionHasErrors('sync');

        $this->assertSame(0, $connector->fetchCalls);
        $this->assertTrue($old->refresh()->is_active);
        $this->assertDatabaseCount('academic_years', 0);
        $this->assertDatabaseHas('external_sync_runs', ['source' => 'dapodik', 'status' => 'failed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'dapodik.sync_completed']);
    }

    public function test_public_dapodik_sync_service_uses_configured_connector_to_create_preview_only(): void
    {
        $admin = $this->userWithRole('admin_it');
        $driver = new Task10DapodikDriver($this->evidencedSnapshot());
        $this->app->instance(DapodikConnector::class, $this->configuredConnector($driver));
        $this->seedActiveSetting($driver);

        $run = app(DapodikSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_PREVIEW_READY, $run->status);
        $this->assertSame(1, $driver->fetchCalls);
        $this->assertSame(4, $run->previewItems()->count());
        $this->assertDatabaseCount('academic_years', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
    }

    public function test_configured_sync_creates_an_immutable_preview_without_mutating_operational_data(): void
    {
        $admin = $this->userWithRole('admin_it');
        $existing = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Nama Persiapan',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $driver = $this->bindConfiguredPipeline($this->evidencedSnapshot());

        $run = app(DapodikSyncService::class)->synchronize($admin);

        $this->assertSame('preview_ready', $run->status);
        $this->assertSame(1, $driver->fetchCalls);
        $this->assertSame(1, $run->preview_generation);
        $this->assertSame(4, $run->previewItems()->count());
        $this->assertSame(64, strlen((string) $run->snapshot_fingerprint));
        $this->assertSame('synthetic-dapodik', $run->driver_id);
        $this->assertSame('synthetic-adapter-v1', $run->adapter_version);
        $this->assertSame('contract-v1', $run->contract_version);
        $this->assertNotNull($run->preview_expires_at);

        $this->assertSame('Nama Persiapan', $existing->refresh()->name);
        $this->assertNull($existing->dapodik_id);
        $this->assertSame(Student::MASTER_SOURCE_SCHOOL_PROVISIONAL, $existing->master_source);
        $this->assertDatabaseCount('academic_years', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
        $this->assertDatabaseHas('dapodik_sync_preview_items', [
            'external_sync_run_id' => $run->id,
            'entity_type' => 'student',
            'source_identifier' => 'student-1',
            'candidate_id' => $existing->id,
            'match_status' => 'changed',
            'preview_generation' => 1,
            'decision_revision' => 0,
        ]);
        $studentItem = $run->previewItems()->where('entity_type', 'student')->sole();
        $this->assertSame([
            'source_id' => 'student-1',
            'nisn' => '0012345678',
            'name' => 'Nama Resmi',
            'is_active' => true,
        ], $studentItem->safe_fields);
        $this->assertSame(64, strlen($studentItem->item_hash));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'dapodik.preview_created',
            'auditable_id' => $run->id,
            'actor_id' => $admin->id,
        ]);
    }

    public function test_admin_can_only_map_ambiguous_year_and_classroom_to_unconfirmed_provisional_candidates(): void
    {
        $this->assertTrue(Route::has('data-master.dapodik.previews.show'));
        $this->assertTrue(Route::has('data-master.dapodik.previews.items.update'));
        $this->assertTrue(Route::has('data-master.dapodik.previews.apply'));

        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');
        $year = AcademicYear::query()->create([
            'name' => '2027/2028 Persiapan',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $otherYear = AcademicYear::query()->create([
            'name' => '2028/2029 Persiapan',
            'starts_on' => '2028-07-01',
            'ends_on' => '2029-06-30',
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'X RPL Persiapan',
            'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $otherClassroom = Classroom::query()->create([
            'academic_year_id' => $otherYear->id,
            'name' => 'X RPL Lintas Tahun',
            'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $snapshot = new DapodikSnapshot(
            isFullSnapshot: false,
            academicYears: [[
                'source_id' => 'year-official',
                'name' => '2027/2028',
                'starts_on' => '2027-07-01',
                'ends_on' => '2028-06-30',
            ]],
            classrooms: [[
                'source_id' => 'class-official',
                'academic_year_source_id' => 'year-official',
                'name' => 'X RPL 1',
            ]],
            students: [],
            memberships: [],
            evidence: new IntegrationSnapshotEvidence('school-01', 'contract-v1', 'partial', 1, 2, 256),
        );
        $this->bindConfiguredPipeline($snapshot);
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $yearItem = $run->previewItems()->where('entity_type', DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR)->sole();
        $classroomItem = $run->previewItems()->where('entity_type', DapodikSyncPreviewItem::ENTITY_CLASSROOM)->sole();
        $this->assertSame(DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING, $yearItem->match_status);
        $this->assertSame(DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING, $classroomItem->match_status);

        $this->actingAs($teacher)->get(route('data-master.dapodik.previews.show', $run))->assertForbidden();
        $this->actingAs($teacher)->patch(
            route('data-master.dapodik.previews.items.update', [$run, $yearItem]),
            [
                'decision' => DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
                'candidate_id' => $year->id,
                'decision_revision' => 0,
            ],
        )->assertForbidden();
        $this->actingAs($teacher)->post(
            route('data-master.dapodik.previews.apply', $run),
            ['decision_revision' => 0],
        )->assertForbidden();
        $this->assertNull($yearItem->refresh()->decision);
        $this->actingAs($admin)->get(route('data-master.dapodik.previews.show', $run))
            ->assertOk()
            ->assertSee('Pratinjau Pencocokan Dapodik')
            ->assertSee('Belum terverifikasi Dapodik');
        $this->actingAs($admin)->get(route('data-master.index'))
            ->assertOk()
            ->assertSee('Pratinjau Dapodik menunggu penerapan')
            ->assertSee(route('data-master.dapodik.previews.show', $run), false);

        $this->actingAs($admin)->patch(
            route('data-master.dapodik.previews.items.update', [$run, $yearItem]),
            [
                'decision' => DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
                'candidate_id' => $year->id,
                'decision_revision' => 0,
            ],
        )->assertRedirect(route('data-master.dapodik.previews.show', $run));
        $this->assertDatabaseHas('dapodik_sync_preview_items', [
            'id' => $yearItem->id,
            'decision' => DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
            'decision_candidate_id' => $year->id,
            'decision_revision' => 1,
            'decided_by' => $admin->id,
        ]);

        $this->actingAs($admin)->patch(
            route('data-master.dapodik.previews.items.update', [$run, $classroomItem]),
            [
                'decision' => DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
                'candidate_id' => $otherClassroom->id,
                'decision_revision' => 0,
            ],
        )->assertSessionHasErrors('candidate_id');
        $this->actingAs($admin)->patch(
            route('data-master.dapodik.previews.items.update', [$run, $classroomItem]),
            [
                'decision' => DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
                'candidate_id' => $classroom->id,
                'decision_revision' => 0,
            ],
        )->assertRedirect(route('data-master.dapodik.previews.show', $run));

        $this->actingAs($admin)->patch(
            route('data-master.dapodik.previews.items.update', [$run, $yearItem]),
            [
                'decision' => DapodikSyncPreviewItem::DECISION_CREATE_NEW,
                'decision_revision' => 0,
            ],
        )->assertSessionHasErrors('decision_revision');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'dapodik.preview_decided',
            'auditable_id' => $run->id,
            'actor_id' => $admin->id,
        ]);
    }

    public function test_manual_parent_mapping_preserves_existing_provisional_membership_id(): void
    {
        $admin = $this->userWithRole('admin_it');
        $year = AcademicYear::query()->create([
            'name' => 'TA Persiapan',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'Rombel Persiapan',
            'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Nama Persiapan',
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $membership = StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-01',
            'master_source' => StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $this->bindConfiguredPipeline($this->evidencedSnapshot(false));
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $yearItem = $run->previewItems()->where('entity_type', DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR)->sole();
        $classroomItem = $run->previewItems()->where('entity_type', DapodikSyncPreviewItem::ENTITY_CLASSROOM)->sole();
        $membershipItem = $run->previewItems()->where('entity_type', DapodikSyncPreviewItem::ENTITY_MEMBERSHIP)->sole();

        $this->assertSame($membership->id, $membershipItem->candidate_id);
        $this->assertSame(DapodikSyncPreviewItem::MATCH_CHANGED, $membershipItem->match_status);
        app(DapodikReconciliationService::class)->decide($run, $yearItem, [
            'decision' => DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
            'candidate_id' => $year->id,
            'decision_revision' => 0,
        ], $admin);
        app(DapodikReconciliationService::class)->decide($run, $classroomItem, [
            'decision' => DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
            'candidate_id' => $classroom->id,
            'decision_revision' => 0,
        ], $admin);
        $run->refresh();

        app(DapodikReconciliationService::class)
            ->apply($run, $admin, $run->decision_revision);

        $this->assertDatabaseCount('student_class_memberships', 1);
        $this->assertSame('membership-1', $membership->refresh()->dapodik_id);
        $this->assertSame($student->id, $membership->student_id);
        $this->assertSame($classroom->id, $membership->classroom_id);
        $this->assertSame($year->id, $membership->academic_year_id);
    }

    public function test_manual_year_mapping_rejects_a_candidate_outside_snapshot_period(): void
    {
        $admin = $this->userWithRole('admin_it');
        $wrongPeriod = AcademicYear::query()->create([
            'name' => 'TA Periode Lain',
            'starts_on' => '2028-07-01',
            'ends_on' => '2029-06-30',
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $this->bindConfiguredPipeline($this->evidencedSnapshot(false));
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $yearItem = $run->previewItems()->where('entity_type', DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR)->sole();

        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)->decide($run, $yearItem, [
                'decision' => DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
                'candidate_id' => $wrongPeriod->id,
                'decision_revision' => 0,
            ], $admin),
            'candidate_id',
        );
        $this->assertNull($yearItem->refresh()->decision);
    }

    public function test_apply_rejects_child_mapping_after_parent_year_decision_changes(): void
    {
        $admin = $this->userWithRole('admin_it');
        $yearA = AcademicYear::query()->create([
            'name' => 'TA Persiapan A',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $yearB = AcademicYear::query()->create([
            'name' => 'TA Persiapan B',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $classroomA = Classroom::query()->create([
            'academic_year_id' => $yearA->id,
            'name' => 'Rombel Persiapan A',
            'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $snapshot = new DapodikSnapshot(
            isFullSnapshot: false,
            academicYears: [[
                'source_id' => 'year-official',
                'name' => '2026/2027',
                'starts_on' => '2026-07-01',
                'ends_on' => '2027-06-30',
            ]],
            classrooms: [[
                'source_id' => 'class-official',
                'academic_year_source_id' => 'year-official',
                'name' => 'X RPL 1',
            ]],
            students: [],
            memberships: [],
            evidence: new IntegrationSnapshotEvidence('school-01', 'contract-v1', 'partial', 1, 2, 256),
        );
        $this->bindConfiguredPipeline($snapshot);
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $yearItem = $run->previewItems()->where('entity_type', DapodikSyncPreviewItem::ENTITY_ACADEMIC_YEAR)->sole();
        $classroomItem = $run->previewItems()->where('entity_type', DapodikSyncPreviewItem::ENTITY_CLASSROOM)->sole();

        app(DapodikReconciliationService::class)->decide($run, $yearItem, [
            'decision' => DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
            'candidate_id' => $yearA->id,
            'decision_revision' => 0,
        ], $admin);
        app(DapodikReconciliationService::class)->decide($run, $classroomItem, [
            'decision' => DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
            'candidate_id' => $classroomA->id,
            'decision_revision' => 0,
        ], $admin);
        app(DapodikReconciliationService::class)->decide($run, $yearItem->refresh(), [
            'decision' => DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
            'candidate_id' => $yearB->id,
            'decision_revision' => 1,
        ], $admin);
        $run->refresh();

        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($run, $admin, $run->decision_revision),
            'preview',
        );
        $this->assertSame($yearA->id, $classroomA->refresh()->academic_year_id);
        $this->assertNull($classroomA->dapodik_id);
    }

    public function test_atomic_apply_preserves_internal_ids_and_bk_history_then_relinks_exact_nisn_identities(): void
    {
        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $temporary = app(StudentIdentityService::class)
            ->createTemporary('0012345678', 'Nama Masukan Sebelum Dapodik', $teacher);
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'X RPL 1',
            'is_active' => true,
            'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Nama Persiapan Berbeda',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $membership = StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-15',
            'is_active' => true,
            'master_source' => StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $assignment = TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-01',
            'decision_number' => 'SK-PENUGASAN-001',
            'assigned_by' => $coordinator->id,
        ]);
        $case = BkCase::query()->create([
            'registration_number' => 'BK-KEEP-001',
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source')->id,
            'service_field_id' => $this->reference('service_field')->id,
            'status_id' => $this->reference('case_status')->id,
            'service_date' => '2026-09-01',
            'initial_info' => 'Informasi awal.',
            'initial_action' => 'Tindakan awal.',
            'created_by' => $teacher->id,
        ]);
        $followUp = FollowUp::query()->create([
            'case_id' => $case->id,
            'follow_up_type_id' => $this->reference('follow_up_type')->id,
            'status_id' => $this->reference('follow_up_status')->id,
            'planned_date' => '2026-09-02',
            'recorded_by' => $teacher->id,
        ]);
        $consultation = Consultation::query()->create([
            'registration_number' => 'KS-KEEP-001',
            'student_id' => $student->id,
            'case_id' => $case->id,
            'service_field_id' => $this->reference('service_field')->id,
            'status_id' => $this->reference('consultation_status')->id,
            'topic' => 'Pendampingan',
            'session_date' => '2026-09-02',
            'counselor_id' => $teacher->id,
        ]);
        $achievement = Achievement::query()->create([
            'student_id' => $student->id,
            'type_id' => $this->reference('achievement_type')->id,
            'level_id' => $this->reference('achievement_level')->id,
            'activity_name' => 'Lomba',
            'organizer' => 'Sekolah',
            'achievement_date' => '2026-08-01',
            'result' => 'Juara I',
            'evidence_reference' => 'Arsip sekolah',
            'verification_status_id' => $this->reference('achievement_verification_status')->id,
            'recorded_by' => $teacher->id,
        ]);
        $etatib = ExternalTatibRecord::query()->create([
            'source_identifier' => 'etatib-unlinked-1',
            'nisn' => '0012345678',
            'student_id' => null,
            'occurred_at' => '2026-09-01 08:00:00',
            'violation_type' => 'Terlambat',
            'category' => 'Kedisiplinan',
            'points' => 5,
            'is_active' => true,
            'synced_at' => now()->subDay(),
        ]);
        $driver = $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $run = app(DapodikSyncService::class)->synchronize($admin);

        $this->actingAs($admin)->post(
            route('data-master.dapodik.previews.apply', $run),
            ['decision_revision' => $run->decision_revision],
        )->assertRedirect(route('data-master.index'));

        $this->assertSame(1, $driver->fetchCalls);
        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $run->refresh()->status);
        $this->assertNotNull($run->applied_at);
        $this->assertTrue($year->refresh()->is_active);
        $this->assertSame('year-2026', $year->dapodik_id);
        $this->assertSame(AcademicYear::MASTER_SOURCE_DAPODIK, $year->master_source);
        $this->assertSame('class-1', $classroom->refresh()->dapodik_id);
        $this->assertSame(Classroom::MASTER_SOURCE_DAPODIK, $classroom->master_source);
        $this->assertSame('student-1', $student->refresh()->dapodik_id);
        $this->assertSame('Nama Resmi', $student->name);
        $this->assertSame(Student::MASTER_SOURCE_DAPODIK, $student->master_source);
        $this->assertSame('membership-1', $membership->refresh()->dapodik_id);
        $this->assertSame(StudentClassMembership::MASTER_SOURCE_DAPODIK, $membership->master_source);

        $this->assertSame($student->id, $case->refresh()->student_id);
        $this->assertSame($student->id, $consultation->refresh()->student_id);
        $this->assertSame($case->id, $followUp->refresh()->case_id);
        $this->assertSame($student->id, $achievement->refresh()->student_id);
        $this->assertSame($classroom->id, $assignment->refresh()->classroom_id);
        $this->assertSame($year->id, $assignment->academic_year_id);
        $this->assertSame($student->id, $membership->student_id);
        $this->assertSame($classroom->id, $membership->classroom_id);
        $this->assertSame($year->id, $membership->academic_year_id);
        $this->assertSame($student->id, $temporary->refresh()->reconciled_student_id);
        $this->assertSame($student->id, $etatib->refresh()->student_id);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('classrooms', 1);
        $this->assertDatabaseCount('student_class_memberships', 1);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'dapodik.preview_applied',
            'auditable_id' => $run->id,
            'actor_id' => $admin->id,
        ]);
        $nameAudit = AuditLog::query()
            ->where('action', 'dapodik.student_reconciled')
            ->where('auditable_id', $student->id)
            ->sole();
        $this->assertSame('Nama Persiapan Berbeda', $nameAudit->before_values['name']);
        $this->assertSame('Nama Resmi', $nameAudit->after_values['name']);
    }

    public function test_apply_rejects_stale_configuration_policy_fingerprint_hash_revision_and_target_rows(): void
    {
        $admin = $this->userWithRole('admin_it');

        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $configurationRun = app(DapodikSyncService::class)->synchronize($admin);
        IntegrationSetting::query()->where('provider', 'dapodik')->increment('configuration_version');
        try {
            app(DapodikReconciliationService::class)
                ->apply($configurationRun, $admin, $configurationRun->decision_revision);
            $this->fail('Pratinjau dengan konfigurasi stale dapat diterapkan.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('configuration_changed', $exception->resultCode());
        }
        $this->assertDatabaseCount('academic_years', 0);

        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $policyRun = app(DapodikSyncService::class)->synchronize($admin);
        config()->set('sibk.integrations.dapodik.allowed_origins', ['https://other.example.test']);
        try {
            app(DapodikReconciliationService::class)
                ->apply($policyRun, $admin, $policyRun->decision_revision);
            $this->fail('Pratinjau dengan endpoint policy stale dapat diterapkan.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('endpoint_not_allowed', $exception->resultCode());
        }
        $this->assertDatabaseCount('academic_years', 0);

        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $fingerprintRun = app(DapodikSyncService::class)->synchronize($admin);
        DB::table('external_sync_runs')->where('id', $fingerprintRun->id)
            ->update(['snapshot_fingerprint' => str_repeat('f', 64)]);
        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($fingerprintRun, $admin, $fingerprintRun->decision_revision),
            'preview',
        );
        $this->assertDatabaseCount('academic_years', 0);

        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $hashRun = app(DapodikSyncService::class)->synchronize($admin);
        DB::table('dapodik_sync_preview_items')->where('external_sync_run_id', $hashRun->id)
            ->where('entity_type', 'student')
            ->update(['item_hash' => str_repeat('0', 64)]);
        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($hashRun, $admin, $hashRun->decision_revision),
            'preview',
        );
        $this->assertDatabaseCount('students', 0);

        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $revisionRun = app(DapodikSyncService::class)->synchronize($admin);
        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($revisionRun, $admin, $revisionRun->decision_revision + 1),
            'decision_revision',
        );
        $this->assertDatabaseCount('academic_years', 0);

        $student = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Nama Target Awal',
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $targetRun = app(DapodikSyncService::class)->synchronize($admin);
        $student->update(['name' => 'Nama Target Berubah Setelah Preview']);
        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($targetRun, $admin, $targetRun->decision_revision),
            'preview',
        );
        $this->assertNull($student->refresh()->dapodik_id);
    }

    public function test_apply_rejects_alternative_source_owner_created_after_candidate_preview(): void
    {
        $admin = $this->userWithRole('admin_it');
        $candidate = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Kandidat Persiapan',
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $this->bindConfiguredPipeline($this->evidencedSnapshot(false));
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $alternative = Student::query()->create([
            'dapodik_id' => 'student-1',
            'nisn' => '0000000013',
            'name' => 'Pemilik Source ID Baru',
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now(),
        ]);

        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($run, $admin, $run->decision_revision),
            'preview',
        );

        $this->assertNull($candidate->refresh()->dapodik_id);
        $this->assertSame('student-1', $alternative->refresh()->dapodik_id);
    }

    public function test_apply_rejects_academic_year_natural_key_created_after_new_item_preview(): void
    {
        $admin = $this->userWithRole('admin_it');
        $this->bindConfiguredPipeline($this->evidencedSnapshot(false));
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $late = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);

        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($run, $admin, $run->decision_revision),
            'preview',
        );
        $this->assertNull($late->refresh()->dapodik_id);
    }

    public function test_apply_rejects_student_natural_key_created_after_new_item_preview(): void
    {
        $admin = $this->userWithRole('admin_it');
        $this->bindConfiguredPipeline($this->evidencedSnapshot(false));
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $late = Student::query()->create([
            'nisn' => '0012345678',
            'name' => 'Murid Terlambat',
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);

        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($run, $admin, $run->decision_revision),
            'preview',
        );
        $this->assertNull($late->refresh()->dapodik_id);
    }

    public function test_apply_rejects_classroom_natural_key_created_after_new_item_preview(): void
    {
        $admin = $this->userWithRole('admin_it');
        $year = AcademicYear::query()->create([
            'dapodik_id' => 'year-2026',
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'master_source' => AcademicYear::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now(),
        ]);
        $this->bindConfiguredPipeline($this->evidencedSnapshot(false));
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $late = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'X RPL 1',
            'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);

        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($run, $admin, $run->decision_revision),
            'preview',
        );
        $this->assertNull($late->refresh()->dapodik_id);
    }

    public function test_apply_rejects_membership_natural_key_created_after_new_item_preview(): void
    {
        $admin = $this->userWithRole('admin_it');
        $confirmedAt = now();
        $year = AcademicYear::query()->create([
            'dapodik_id' => 'year-2026',
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'master_source' => AcademicYear::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => $confirmedAt,
        ]);
        $classroom = Classroom::query()->create([
            'dapodik_id' => 'class-1',
            'academic_year_id' => $year->id,
            'name' => 'X RPL 1',
            'grade_level' => 10,
            'major' => 'RPL',
            'is_active' => true,
            'master_source' => Classroom::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => $confirmedAt,
        ]);
        $student = Student::query()->create([
            'dapodik_id' => 'student-1',
            'nisn' => '0012345678',
            'name' => 'Nama Resmi',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => $confirmedAt,
        ]);
        $this->bindConfiguredPipeline($this->evidencedSnapshot(false));
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $late = StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-15',
            'master_source' => StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);

        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($run, $admin, $run->decision_revision),
            'preview',
        );
        $this->assertNull($late->refresh()->dapodik_id);
    }

    public function test_semantically_identical_json_key_reordering_does_not_invalidate_preview_hash(): void
    {
        $admin = $this->userWithRole('admin_it');
        $this->bindConfiguredPipeline($this->evidencedSnapshot(false));
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $item = $run->previewItems()
            ->where('entity_type', DapodikSyncPreviewItem::ENTITY_STUDENT)
            ->sole();
        DB::table('dapodik_sync_preview_items')->where('id', $item->id)->update([
            'safe_fields' => json_encode([
                'is_active' => true,
                'name' => 'Nama Resmi',
                'nisn' => '0012345678',
                'source_id' => 'student-1',
            ], JSON_THROW_ON_ERROR),
        ]);

        $applied = app(DapodikReconciliationService::class)
            ->apply($run, $admin, $run->decision_revision);

        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $applied->status);
        $this->assertDatabaseHas('students', [
            'dapodik_id' => 'student-1',
            'nisn' => '0012345678',
        ]);
    }

    public function test_stale_fencing_token_rolls_back_the_entire_apply(): void
    {
        $admin = $this->userWithRole('admin_it');
        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $this->app->instance(AuditService::class, new class extends AuditService
        {
            private bool $invalidated = false;

            public function record(
                string $action,
                Model $auditable,
                string $summary,
                ?User $actor = null,
                ?array $before = null,
                ?array $after = null,
                ?Request $request = null,
            ): AuditLog {
                $audit = parent::record($action, $auditable, $summary, $actor, $before, $after, $request);
                if (! $this->invalidated && str_starts_with($action, 'dapodik.academic_year_')) {
                    $this->invalidated = true;
                    IntegrationSetting::query()->where('provider', 'dapodik')->increment('operation_fence_version');
                }

                return $audit;
            }
        });

        try {
            app(DapodikReconciliationService::class)
                ->apply($run, $admin, $run->decision_revision);
            $this->fail('Proses dengan fencing token stale dapat melakukan commit.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('configuration_changed', $exception->resultCode());
        }

        $this->assertDatabaseCount('academic_years', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
        $this->assertSame(ExternalSyncRun::STATUS_PREVIEW_READY, $run->refresh()->status);
        $this->assertNull($run->applied_at);
    }

    public function test_full_apply_only_deactivates_missing_verified_rows_and_keeps_unmatched_local_data_for_review(): void
    {
        $admin = $this->userWithRole('admin_it');
        $oldYear = AcademicYear::query()->create([
            'dapodik_id' => 'old-year',
            'name' => '2025/2026',
            'is_active' => true,
            'master_source' => AcademicYear::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now()->subYear(),
        ]);
        $oldClassroom = Classroom::query()->create([
            'dapodik_id' => 'old-class',
            'academic_year_id' => $oldYear->id,
            'name' => 'X Lama',
            'is_active' => true,
            'master_source' => Classroom::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now()->subYear(),
        ]);
        $oldStudent = Student::query()->create([
            'dapodik_id' => 'old-student',
            'nisn' => '0000000001',
            'name' => 'Murid Lama Dapodik',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now()->subYear(),
        ]);
        $oldMembership = StudentClassMembership::query()->create([
            'dapodik_id' => 'old-membership',
            'student_id' => $oldStudent->id,
            'classroom_id' => $oldClassroom->id,
            'academic_year_id' => $oldYear->id,
            'effective_from' => '2025-07-01',
            'is_active' => true,
            'master_source' => StudentClassMembership::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now()->subYear(),
        ]);
        $provisionalYear = AcademicYear::query()->create([
            'name' => '2028/2029',
            'is_active' => true,
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $provisionalClassroom = Classroom::query()->create([
            'academic_year_id' => $provisionalYear->id,
            'name' => 'X Persiapan',
            'is_active' => true,
            'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $provisionalStudent = Student::query()->create([
            'nisn' => '0000000002',
            'name' => 'Murid Persiapan',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $legacyStudent = Student::query()->create([
            'nisn' => '0000000003',
            'name' => 'Murid Legacy',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_LEGACY_UNCLASSIFIED,
        ]);
        $provisionalMembership = StudentClassMembership::query()->create([
            'student_id' => $provisionalStudent->id,
            'classroom_id' => $provisionalClassroom->id,
            'academic_year_id' => $provisionalYear->id,
            'effective_from' => '2028-07-01',
            'is_active' => true,
            'master_source' => StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $run = app(DapodikSyncService::class)->synchronize($admin);

        foreach ($run->previewItems()->where('match_status', DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING)->get() as $item) {
            app(DapodikReconciliationService::class)->decide($run, $item, [
                'decision' => DapodikSyncPreviewItem::DECISION_CREATE_NEW,
                'decision_revision' => $item->decision_revision,
            ], $admin);
        }
        $run->refresh();

        app(DapodikReconciliationService::class)
            ->apply($run, $admin, $run->decision_revision);

        $this->assertTrue($oldYear->refresh()->is_active, 'Provider tidak boleh mengubah is_active tahun ajaran.');
        $this->assertFalse($oldClassroom->refresh()->is_active);
        $this->assertFalse($oldStudent->refresh()->is_active);
        $this->assertFalse($oldMembership->refresh()->is_active);
        $this->assertTrue($provisionalYear->refresh()->is_active);
        $this->assertTrue($provisionalClassroom->refresh()->is_active);
        $this->assertTrue($provisionalStudent->refresh()->is_active);
        $this->assertTrue($legacyStudent->refresh()->is_active);
        $this->assertTrue($provisionalMembership->refresh()->is_active);
        $this->assertSame(AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL, $provisionalYear->master_source);
        $this->assertSame(Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL, $provisionalClassroom->master_source);
        $this->assertSame(Student::MASTER_SOURCE_SCHOOL_PROVISIONAL, $provisionalStudent->master_source);
        $this->assertSame(Student::MASTER_SOURCE_LEGACY_UNCLASSIFIED, $legacyStudent->master_source);
        $this->assertSame(StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL, $provisionalMembership->master_source);
        $this->assertSame(ExternalSyncRun::STATUS_WARNING, $run->refresh()->status);
        $this->assertDatabaseHas('external_sync_issues', [
            'external_sync_run_id' => $run->id,
            'entity_type' => 'student',
            'source_identifier' => 'local:'.$provisionalStudent->id,
            'issue_code' => 'unmatched_local_record',
        ]);
        $this->assertDatabaseHas('external_sync_issues', [
            'external_sync_run_id' => $run->id,
            'entity_type' => 'student',
            'source_identifier' => 'local:'.$legacyStudent->id,
            'issue_code' => 'unmatched_local_record',
        ]);
    }

    public function test_partial_preview_metadata_tamper_cannot_deactivate_local_rows(): void
    {
        $admin = $this->userWithRole('admin_it');
        $existing = Student::query()->create([
            'dapodik_id' => 'student-not-in-partial',
            'nisn' => '0000000009',
            'name' => 'Murid Di Luar Snapshot Parsial',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now()->subDay(),
        ]);
        $this->bindConfiguredPipeline($this->evidencedSnapshot(false));
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $this->assertFalse($run->is_full_snapshot);

        DB::table('external_sync_runs')->where('id', $run->id)->update(['is_full_snapshot' => true]);

        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($run, $admin, $run->decision_revision),
            'preview',
        );
        $this->assertTrue($existing->refresh()->is_active);
    }

    public function test_full_preview_exposes_immutable_deactivation_plan_and_audits_each_target(): void
    {
        $admin = $this->userWithRole('admin_it');
        $missingStudent = Student::query()->create([
            'dapodik_id' => 'student-missing-from-full',
            'nisn' => '0000000010',
            'name' => 'Murid Tidak Lagi Di Sumber',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now()->subDay(),
        ]);
        $this->bindConfiguredPipeline($this->evidencedSnapshot());

        $run = app(DapodikSyncService::class)->synchronize($admin);

        $this->assertSame([
            'reported_source_identifier' => 'school-01',
            'contract_marker' => 'contract-v1',
            'completeness_marker' => 'full',
            'page_count' => 1,
            'record_count' => 4,
            'processed_bytes' => 512,
        ], $run->snapshot_evidence);
        $this->assertCount(1, $run->deactivation_plan);
        $planned = $run->deactivation_plan[0];
        $this->assertSame('student', $planned['entity_type']);
        $this->assertSame($missingStudent->id, $planned['target_id']);
        $this->assertSame(64, strlen($planned['target_fingerprint']));
        $this->actingAs($admin)
            ->get(route('data-master.dapodik.previews.show', $run))
            ->assertOk()
            ->assertSee('Snapshot penuh')
            ->assertSee('Murid internal #'.$missingStudent->id)
            ->assertSee(substr($planned['target_fingerprint'], 0, 12));

        try {
            $run->update(['deactivation_plan' => []]);
            $this->fail('Rencana deactivation dapat diubah setelah preview dibuat.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('tidak dapat diubah', $exception->getMessage());
        }

        app(DapodikReconciliationService::class)
            ->apply($run->refresh(), $admin, $run->decision_revision);

        $this->assertFalse($missingStudent->refresh()->is_active);
        $audit = AuditLog::query()
            ->where('action', 'dapodik.student_deactivated')
            ->where('auditable_id', $missingStudent->id)
            ->sole();
        $this->assertTrue((bool) $audit->before_values['is_active']);
        $this->assertFalse((bool) $audit->after_values['is_active']);
    }

    public function test_full_apply_rejects_new_deactivation_target_created_after_preview(): void
    {
        $admin = $this->userWithRole('admin_it');
        $planned = Student::query()->create([
            'dapodik_id' => 'student-planned-missing',
            'nisn' => '0000000011',
            'name' => 'Murid Direncanakan Nonaktif',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now()->subDay(),
        ]);
        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $late = Student::query()->create([
            'dapodik_id' => 'student-created-after-preview',
            'nisn' => '0000000012',
            'name' => 'Murid Terlambat Masuk Cache',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now(),
        ]);

        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($run, $admin, $run->decision_revision),
            'preview',
        );

        $this->assertTrue($planned->refresh()->is_active);
        $this->assertTrue($late->refresh()->is_active);
    }

    public function test_full_apply_rejects_changed_deactivation_target_after_preview(): void
    {
        $admin = $this->userWithRole('admin_it');
        $planned = Student::query()->create([
            'dapodik_id' => 'student-planned-changed',
            'nisn' => '0000000014',
            'name' => 'Nama Saat Preview',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now()->subDay(),
        ]);
        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $planned->update(['name' => 'Nama Berubah Setelah Preview']);

        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($run, $admin, $run->decision_revision),
            'preview',
        );
        $this->assertTrue($planned->refresh()->is_active);
    }

    public function test_new_preview_supersedes_old_decisions_and_duplicate_apply_is_rejected(): void
    {
        $admin = $this->userWithRole('admin_it');
        $snapshot = $this->evidencedSnapshot();
        $this->bindConfiguredPipeline($snapshot);
        $first = app(DapodikSyncService::class)->synchronize($admin);
        $this->bindConfiguredPipeline($snapshot);
        $second = app(DapodikSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_SUPERSEDED, $first->refresh()->status);
        $this->assertNotNull($first->superseded_at);
        $this->assertSame(2, $second->preview_generation);
        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($first, $admin, $first->decision_revision),
            'preview',
        );
        app(DapodikReconciliationService::class)
            ->apply($second, $admin, $second->decision_revision);
        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($second, $admin, $second->decision_revision),
            'preview',
        );
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'dapodik.preview_superseded',
            'auditable_id' => $first->id,
        ]);

        $this->bindConfiguredPipeline($snapshot);
        $exact = app(DapodikSyncService::class)->synchronize($admin);
        $this->assertSame(
            [DapodikSyncPreviewItem::MATCH_EXACT],
            $exact->previewItems()->pluck('match_status')->unique()->values()->all(),
        );
    }

    public function test_expired_incomplete_and_conflicting_previews_are_rejected_and_snapshot_fields_are_immutable(): void
    {
        $admin = $this->userWithRole('admin_it');
        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $expired = app(DapodikSyncService::class)->synchronize($admin);
        DB::table('external_sync_runs')->where('id', $expired->id)
            ->update(['preview_expires_at' => now()->subSecond()]);
        $expired->refresh();
        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($expired, $admin, $expired->decision_revision),
            'preview',
        );

        $provisionalYear = AcademicYear::query()->create([
            'name' => '2028/2029 Persiapan',
            'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $incomplete = app(DapodikSyncService::class)->synchronize($admin);
        $this->assertTrue($incomplete->previewItems()->where('match_status', DapodikSyncPreviewItem::MATCH_NEEDS_MAPPING)->exists());
        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($incomplete, $admin, $incomplete->decision_revision),
            'preview',
        );
        $this->assertNull($provisionalYear->refresh()->dapodik_id);

        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique('students_nisn_unique');
        });
        Student::query()->create(['nisn' => '0012345678', 'name' => 'Duplikat A']);
        Student::query()->create(['nisn' => '0012345678', 'name' => 'Duplikat B']);
        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $conflicting = app(DapodikSyncService::class)->synchronize($admin);
        $conflictItem = $conflicting->previewItems()
            ->where('entity_type', DapodikSyncPreviewItem::ENTITY_STUDENT)
            ->sole();
        $this->assertSame(DapodikSyncPreviewItem::MATCH_CONFLICT, $conflictItem->match_status);
        $this->actingAs($admin)->patch(
            route('data-master.dapodik.previews.items.update', [$conflicting, $conflictItem]),
            [
                'decision' => DapodikSyncPreviewItem::DECISION_CREATE_NEW,
                'decision_revision' => 0,
            ],
        )->assertSessionHasErrors('item');
        $this->assertValidationFailure(
            fn () => app(DapodikReconciliationService::class)
                ->apply($conflicting, $admin, $conflicting->decision_revision),
            'preview',
        );

        try {
            $conflictItem->update(['safe_fields' => ['source_id' => 'tampered']]);
            $this->fail('Snapshot item dapat dimutasi setelah dibuat.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('tidak dapat diubah', $exception->getMessage());
        }
    }

    public function test_busy_preview_fails_closed_without_run_or_preview_items(): void
    {
        $admin = $this->userWithRole('admin_it');
        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $lock = Cache::lock('sibk:integration:dapodik:operation', 60);
        $this->assertTrue($lock->get());

        try {
            app(DapodikSyncService::class)->synchronize($admin);
            $this->fail('Operasi preview kedua dapat melewati lock provider.');
        } catch (IntegrationBusyException $exception) {
            $this->assertSame('busy', $exception->resultCode());
        } finally {
            $lock->release();
        }

        $this->assertDatabaseCount('external_sync_runs', 0);
        $this->assertDatabaseCount('dapodik_sync_preview_items', 0);
    }

    public function test_apply_audit_failure_rolls_back_all_cache_and_run_mutations(): void
    {
        $admin = $this->userWithRole('admin_it');
        $this->bindConfiguredPipeline($this->evidencedSnapshot());
        $run = app(DapodikSyncService::class)->synchronize($admin);
        $this->app->instance(AuditService::class, new class extends AuditService
        {
            public function record(
                string $action,
                Model $auditable,
                string $summary,
                ?User $actor = null,
                ?array $before = null,
                ?array $after = null,
                ?Request $request = null,
            ): AuditLog {
                if ($action === 'dapodik.preview_applied') {
                    throw new RuntimeException('Synthetic apply audit failure.');
                }

                return parent::record($action, $auditable, $summary, $actor, $before, $after, $request);
            }
        });

        try {
            app(DapodikReconciliationService::class)
                ->apply($run, $admin, $run->decision_revision);
            $this->fail('Kegagalan audit tidak membatalkan apply.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic apply audit failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('academic_years', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
        $this->assertSame(ExternalSyncRun::STATUS_PREVIEW_READY, $run->refresh()->status);
        $this->assertNull($run->applied_at);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'dapodik.academic_year_created']);
    }

    public function test_configured_connector_rejects_nisn_bound_to_another_dapodik_source_id(): void
    {
        Student::query()->create([
            'dapodik_id' => 'student-old',
            'nisn' => '0012345678',
            'name' => 'Murid Lama',
            'is_active' => true,
        ]);
        $snapshot = new DapodikSnapshot(
            isFullSnapshot: false,
            academicYears: [],
            classrooms: [],
            students: [[
                'source_id' => 'student-new',
                'nisn' => '0012345678',
                'name' => 'Murid Baru',
                'is_active' => true,
            ]],
            memberships: [],
            evidence: new IntegrationSnapshotEvidence('school-01', 'contract-v1', 'partial', 1, 1, 128),
        );
        $driver = new Task10DapodikDriver($snapshot);
        $connector = $this->configuredConnector($driver);
        $this->seedActiveSetting($driver);

        try {
            app(IntegrationOperationLock::class)->run(
                'dapodik',
                fn (IntegrationOperationContext $context): DapodikSnapshot => $connector->fetchSnapshot($context),
            );
            $this->fail('NISN yang sudah terikat ke source ID lain harus ditolak.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('source_identity_mismatch', $exception->resultCode());
        }

        $this->assertSame(1, $driver->fetchCalls);
    }

    public function test_partial_snapshot_does_not_deactivate_missing_data_but_full_snapshot_does(): void
    {
        $admin = $this->userWithRole('admin_it');
        $existing = Student::query()->create([
            'dapodik_id' => 'student-old',
            'nisn' => '0000000001',
            'name' => 'Murid Lama',
            'is_active' => true,
        ]);

        $partial = $this->snapshot(isFull: false);
        $this->importSnapshot($partial, $admin);
        $this->assertTrue($existing->refresh()->is_active);

        $this->importSnapshot($this->snapshot(isFull: true), $admin);
        $this->assertFalse($existing->refresh()->is_active);
    }

    public function test_duplicate_nisn_is_held_without_overwriting_valid_student(): void
    {
        $admin = $this->userWithRole('admin_it');
        $existing = Student::query()->create([
            'dapodik_id' => 'student-1',
            'nisn' => '0012345678',
            'name' => 'Nama Sah',
            'is_active' => true,
        ]);
        $snapshot = new DapodikSnapshot(
            isFullSnapshot: false,
            academicYears: [],
            classrooms: [],
            students: [
                ['source_id' => 'student-1', 'nisn' => '0012345678', 'name' => 'Nama Berubah'],
                ['source_id' => 'student-2', 'nisn' => '0012345678', 'name' => 'Nama Duplikat'],
            ],
            memberships: [],
        );
        $run = $this->importSnapshot($snapshot, $admin);

        $this->assertSame('Nama Sah', $existing->refresh()->name);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('external_sync_issues', 2);
    }

    public function test_temporary_identity_reconciles_by_nisn_and_keeps_input_name_in_history(): void
    {
        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');
        $temporary = app(StudentIdentityService::class)
            ->createTemporary('0012345678', 'Nama Masukan Awal', $teacher);
        $this->importSnapshot($this->snapshot(), $admin);

        $temporary->refresh();
        $this->assertNotNull($temporary->reconciled_student_id);
        $this->assertSame('Nama Masukan Awal', $temporary->input_name);
        $this->assertSame(
            'terekonsiliasi',
            ReferenceValue::query()->findOrFail($temporary->reconciliation_status_id)->code,
        );
        $this->assertDatabaseHas('identity_reconciliations', [
            'temporary_student_id' => $temporary->id,
            'official_name' => 'Nama Resmi',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student_identity.reconciled']);
    }

    public function test_unavailable_connector_is_reported_safely_and_data_master_is_admin_only(): void
    {
        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');

        $this->actingAs($teacher)
            ->get(route('data-master.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('data-master.index'))
            ->assertOk()
            ->assertSee('Belum ada data');

        $this->actingAs($admin)
            ->post(route('data-master.dapodik.sync'))
            ->assertSessionHasErrors('sync');

        $this->assertDatabaseHas('external_sync_runs', [
            'source' => 'dapodik',
            'status' => ExternalSyncRun::STATUS_FAILED,
        ]);
        $this->assertDatabaseCount('dapodik_sync_preview_items', 0);
    }

    private function importSnapshot(DapodikSnapshot $snapshot, User $actor): ExternalSyncRun
    {
        $run = ExternalSyncRun::query()->create([
            'source' => 'dapodik',
            'status' => ExternalSyncRun::STATUS_RUNNING,
            'triggered_by' => $actor->getKey(),
            'started_at' => now(),
        ]);
        app(DapodikSnapshotRegressionImporter::class)->import($snapshot, $run, $actor);

        return $run;
    }

    private function snapshot(bool $isFull = true): DapodikSnapshot
    {
        return new DapodikSnapshot(
            isFullSnapshot: $isFull,
            academicYears: [[
                'source_id' => 'year-2026',
                'name' => '2026/2027',
                'starts_on' => '2026-07-01',
                'ends_on' => '2027-06-30',
                'is_active' => true,
            ]],
            classrooms: [[
                'source_id' => 'class-1',
                'academic_year_source_id' => 'year-2026',
                'name' => 'X RPL 1',
                'grade_level' => 10,
                'major' => 'RPL',
                'is_active' => true,
            ]],
            students: [[
                'source_id' => 'student-1',
                'nisn' => '0012345678',
                'name' => 'Nama Resmi',
                'is_active' => true,
            ]],
            memberships: [[
                'source_id' => 'membership-1',
                'student_source_id' => 'student-1',
                'classroom_source_id' => 'class-1',
                'academic_year_source_id' => 'year-2026',
                'effective_from' => '2026-07-15',
                'effective_until' => null,
                'is_active' => true,
            ]],
        );
    }

    private function evidencedSnapshot(bool $isFull = true): DapodikSnapshot
    {
        $snapshot = $this->snapshot($isFull);

        return new DapodikSnapshot(
            $snapshot->isFullSnapshot,
            $snapshot->academicYears,
            $snapshot->classrooms,
            $snapshot->students,
            $snapshot->memberships,
            new IntegrationSnapshotEvidence('school-01', 'contract-v1', $isFull ? 'full' : 'partial', 1, 4, 512),
        );
    }

    private function admittedValidator(): DapodikSnapshotValidator
    {
        return new DapodikSnapshotValidator('contract-v1', ['full' => true, 'partial' => false], 2, 10, 2048);
    }

    private function configuredConnector(Task10DapodikDriver $driver): ConfiguredDapodikConnector
    {
        $registry = new IntegrationDriverRegistry(dapodikDriver: $driver);

        return new ConfiguredDapodikConnector(
            $registry,
            new IntegrationSettingService($registry, app(IntegrationOperationLock::class), app(AuditService::class)),
            $this->admittedValidator(),
        );
    }

    private function bindConfiguredPipeline(DapodikSnapshot $snapshot): Task10DapodikDriver
    {
        $driver = new Task10DapodikDriver($snapshot);
        $registry = new IntegrationDriverRegistry(dapodikDriver: $driver);
        $this->app->instance(IntegrationDriverRegistry::class, $registry);
        $this->app->instance(DapodikConnector::class, new ConfiguredDapodikConnector(
            $registry,
            new IntegrationSettingService($registry, app(IntegrationOperationLock::class), app(AuditService::class)),
            $this->admittedValidator(),
        ));
        $this->seedActiveSetting($driver);

        return $driver;
    }

    private function seedActiveSetting(Task10DapodikDriver $driver): void
    {
        config()->set('sibk.integrations.dapodik', [
            'driver' => 'unavailable',
            'allowed_origins' => ['https://dapodik.example.test'],
            'allow_private_networks' => false,
        ]);
        IntegrationSetting::query()->updateOrCreate(['provider' => 'dapodik'], [
            'base_url' => 'https://dapodik.example.test',
            'expected_source_identifier' => 'school-01',
            'credentials' => ['type' => 'api_token', 'token' => 'secret'],
            'configuration_version' => 1,
            'verified_configuration_version' => 1,
            'verified_driver_id' => $driver->id(),
            'verified_adapter_version' => $driver->adapterVersion(),
            'verified_contract_version' => $driver->contractVersion(),
            'verified_endpoint_policy_digest' => (new IntegrationEndpointPolicy(['https://dapodik.example.test'], false))->digest(),
            'last_test_status' => 'success',
            'last_test_code' => 'success',
            'is_enabled' => true,
        ]);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }

    private function runtimeConfiguration(): IntegrationRuntimeConfiguration
    {
        return new IntegrationRuntimeConfiguration(
            provider: 'dapodik',
            baseUrl: 'https://dapodik.example.test',
            expectedSourceIdentifier: 'school-01',
            credentials: ['type' => 'api_token', 'token' => 'secret'],
            timeoutSeconds: 30,
            configurationVersion: 1,
            operationFenceVersion: 1,
            endpointPolicyDigest: str_repeat('a', 64),
        );
    }

    private function reference(string $category): ReferenceValue
    {
        return ReferenceValue::query()->where('category', $category)->firstOrFail();
    }

    private function assertValidationFailure(callable $operation, string $field): void
    {
        try {
            $operation();
            $this->fail(sprintf('Validasi %s tidak dijalankan.', $field));
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}

final class Task10DapodikDriver implements DapodikDriver
{
    public int $fetchCalls = 0;

    public ?\Closure $onFetch = null;

    public function __construct(private readonly DapodikSnapshot $snapshot) {}

    public function id(): string
    {
        return 'synthetic-dapodik';
    }

    public function adapterVersion(): string
    {
        return 'synthetic-adapter-v1';
    }

    public function contractVersion(): string
    {
        return 'contract-v1';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function probe(IntegrationRuntimeConfiguration $configuration): IntegrationProbeResult
    {
        throw new \LogicException('Not used.');
    }

    public function fetchSnapshot(IntegrationRuntimeConfiguration $configuration): DapodikSnapshot
    {
        $this->fetchCalls++;
        ($this->onFetch ?? static fn (): null => null)();

        return $this->snapshot;
    }
}
