<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Integrations\Dapodik\ConfiguredDapodikConnector;
use App\Integrations\Dapodik\DapodikConnector;
use App\Integrations\Dapodik\DapodikDriver;
use App\Integrations\Dapodik\DapodikSnapshot;
use App\Integrations\Dapodik\DapodikSnapshotValidator;
use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationDriverRegistry;
use App\Integrations\IntegrationEndpointPolicy;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Integrations\IntegrationProbeResult;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Integrations\IntegrationSnapshotEvidence;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DapodikSnapshotImporter;
use App\Services\DapodikSyncService;
use App\Services\IntegrationSettingService;
use App\Services\StudentIdentityService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_public_dapodik_sync_service_rejects_configured_connector_before_fetch_or_import(): void
    {
        $admin = $this->userWithRole('admin_it');
        $driver = new Task10DapodikDriver($this->evidencedSnapshot());
        $this->app->instance(DapodikConnector::class, $this->configuredConnector($driver));
        $this->seedActiveSetting($driver);

        $run = app(DapodikSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_FAILED, $run->status);
        $this->assertSame(0, $driver->fetchCalls);
        $this->assertDatabaseCount('academic_years', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('student_class_memberships', 0);
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
    }

    private function importSnapshot(DapodikSnapshot $snapshot, User $actor): ExternalSyncRun
    {
        $run = ExternalSyncRun::query()->create([
            'source' => 'dapodik',
            'status' => ExternalSyncRun::STATUS_RUNNING,
            'triggered_by' => $actor->getKey(),
            'started_at' => now(),
        ]);
        app(DapodikSnapshotImporter::class)->import($snapshot, $run, $actor);

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

    private function evidencedSnapshot(): DapodikSnapshot
    {
        $snapshot = $this->snapshot();

        return new DapodikSnapshot(
            $snapshot->isFullSnapshot,
            $snapshot->academicYears,
            $snapshot->classrooms,
            $snapshot->students,
            $snapshot->memberships,
            new IntegrationSnapshotEvidence('school-01', 'contract-v1', 'full', 1, 4, 512),
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
