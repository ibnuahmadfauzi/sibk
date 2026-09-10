<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Integrations\Etatib\ConfiguredEtatibConnector;
use App\Integrations\Etatib\EtatibConnector;
use App\Integrations\Etatib\EtatibDriver;
use App\Integrations\Etatib\EtatibSnapshot;
use App\Integrations\Etatib\EtatibSnapshotValidator;
use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationDriverRegistry;
use App\Integrations\IntegrationEndpointPolicy;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Integrations\IntegrationProbeResult;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Integrations\IntegrationSnapshotEvidence;
use App\Models\AuditLog;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\IntegrationSetting;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EtatibSyncService;
use App\Services\IntegrationSettingService;
use Carbon\CarbonImmutable;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Exceptions;
use RuntimeException;
use Tests\TestCase;

class EtatibSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_reconciliation_links_only_a_unique_verified_dapodik_nisn_without_fetching_etatib(): void
    {
        $connector = new class implements EtatibConnector
        {
            public int $fetchCalls = 0;

            public function fetchSnapshot(IntegrationOperationContext $context): EtatibSnapshot
            {
                $this->fetchCalls++;

                throw new RuntimeException('Konektor tidak boleh dipanggil saat relink lokal.');
            }
        };
        $this->app->instance(EtatibConnector::class, $connector);

        $verified = Student::query()->create([
            'dapodik_id' => 'dapodik-student-verified',
            'nisn' => '0012345678',
            'name' => 'Murid Terverifikasi',
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now(),
        ]);
        $provisional = Student::query()->create([
            'nisn' => '0098765432',
            'name' => 'Murid Sementara',
            'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
        ]);
        $verifiedRecord = ExternalTatibRecord::query()->create([
            'source_identifier' => 'tatib-verified',
            'nisn' => $verified->nisn,
            'occurred_at' => now(),
            'violation_type' => 'Terlambat',
            'category' => 'Disiplin',
            'points' => 5,
        ]);
        $provisionalRecord = ExternalTatibRecord::query()->create([
            'source_identifier' => 'tatib-provisional',
            'nisn' => $provisional->nisn,
            'occurred_at' => now(),
            'violation_type' => 'Terlambat',
            'category' => 'Disiplin',
            'points' => 5,
        ]);

        $linked = app(EtatibSyncService::class)->reconcileStudentLinks();

        $this->assertSame(1, $linked);
        $this->assertSame($verified->id, $verifiedRecord->refresh()->student_id);
        $this->assertNull($provisionalRecord->refresh()->student_id);
        $this->assertSame(0, $connector->fetchCalls);
    }

    public function test_full_and_partial_sync_are_idempotent_and_only_full_deactivates_missing_records(): void
    {
        $admin = $this->userWithRole('admin_it');
        Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Resmi', 'is_active' => true]);
        $old = ExternalTatibRecord::query()->create([
            'source_identifier' => 'old', 'nisn' => '0000000001', 'occurred_at' => now(),
            'violation_type' => 'Data lama', 'category' => 'Lama', 'points' => 1, 'is_active' => true, 'synced_at' => now(),
        ]);

        $this->fakeConnector($this->snapshot(false));
        app(EtatibSyncService::class)->synchronize($admin);
        $this->assertTrue($old->refresh()->is_active);

        $this->fakeConnector($this->snapshot(true));
        $first = app(EtatibSyncService::class)->synchronize($admin);
        $second = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $first->status);
        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $second->status);
        $this->assertFalse($old->refresh()->is_active);
        $this->assertDatabaseCount('external_tatib_records', 2);
        $this->assertDatabaseHas('external_tatib_records', ['source_identifier' => 'tatib-1', 'student_id' => Student::query()->firstOrFail()->id, 'is_active' => true]);
    }

    public function test_duplicate_source_and_identity_mismatch_do_not_overwrite_valid_record(): void
    {
        $admin = $this->userWithRole('admin_it');
        $existing = ExternalTatibRecord::query()->create([
            'source_identifier' => 'tatib-1', 'nisn' => '0012345678', 'occurred_at' => '2026-08-01 08:00:00',
            'violation_type' => 'Data sah', 'category' => 'Disiplin', 'points' => 5, 'is_active' => true, 'synced_at' => now(),
        ]);
        $this->fakeConnector(new EtatibSnapshot(false, [[
            'source_id' => 'tatib-1', 'nisn' => '0099999999', 'occurred_at' => '2026-08-19 08:00:00',
            'violation_type' => 'Data konflik', 'category' => 'Konflik', 'points' => 99,
        ]]));

        $run = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_WARNING, $run->status);
        $this->assertSame('Data sah', $existing->refresh()->violation_type);
        $this->assertSame(5, $existing->points);
        $this->assertDatabaseHas('external_sync_issues', ['issue_code' => 'source_identity_mismatch', 'source_identifier' => 'tatib-1']);
    }

    public function test_unconfigured_connector_fails_safely_and_endpoint_is_admin_only(): void
    {
        $admin = $this->userWithRole('admin_it');
        $teacher = $this->userWithRole('guru_bk');

        $this->actingAs($teacher)->post(route('data-master.etatib.sync'))->assertForbidden();
        $this->actingAs($admin)->post(route('data-master.etatib.sync'))->assertSessionHasErrors('etatib_sync');
        $this->assertDatabaseHas('external_sync_runs', ['source' => 'etatib', 'status' => ExternalSyncRun::STATUS_FAILED]);
    }

    public function test_unavailable_driver_fails_safely_without_outbound_or_data_change(): void
    {
        $admin = $this->userWithRole('admin_it');
        $old = ExternalTatibRecord::query()->create([
            'source_identifier' => 'old', 'nisn' => '0000000001', 'occurred_at' => now(),
            'violation_type' => 'Data lama', 'category' => 'Lama', 'points' => 1,
            'is_active' => true, 'synced_at' => now(),
        ]);
        IntegrationSetting::query()->create([
            'provider' => 'etatib',
            'is_enabled' => true,
        ]);

        $run = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_FAILED, $run->status);
        $this->assertSame('Adapter integrasi belum tersedia.', $run->summary);
        $this->assertTrue($old->refresh()->is_active);
    }

    public function test_validator_requires_admitted_contract_limits_and_revision_rules(): void
    {
        $snapshot = $this->evidencedSnapshot();

        $this->expectException(IntegrationConfigurationException::class);
        (new EtatibSnapshotValidator)->validate($snapshot, $this->runtimeConfiguration());
    }

    public function test_configured_sync_validates_then_imports_under_current_fence(): void
    {
        $admin = $this->userWithRole('admin_it');
        Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Resmi', 'is_active' => true]);
        $driver = new Task10EtatibDriver($this->evidencedSnapshot());
        $this->bindConfiguredConnector($driver);
        $this->seedActiveSetting($driver);

        $run = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $run->status, (string) $run->summary);
        $this->assertSame(1, $driver->fetchCalls);
        $this->assertDatabaseHas('external_tatib_records', ['source_identifier' => 'tatib-1', 'points' => 5]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'etatib.sync_completed']);
    }

    public function test_configured_sync_rejects_immutable_etatib_field_drift(): void
    {
        $admin = $this->userWithRole('admin_it');
        $existing = $this->existingRecord();
        $snapshot = $this->evidencedSnapshotWith([
            'violation_type' => 'Pelanggaran yang diubah',
            'source_synced_at' => '2026-08-21 08:00:00',
        ]);
        $driver = new Task10EtatibDriver($snapshot);
        $this->bindConfiguredConnector($driver);
        $this->seedActiveSetting($driver);

        $run = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_FAILED, $run->status);
        $this->assertSame('Terlambat', $existing->refresh()->violation_type);
        $this->assertSame('2026-08-20 08:00:00', $existing->source_synced_at?->format('Y-m-d H:i:s'));
    }

    public function test_configured_sync_rejects_mutable_update_without_revision(): void
    {
        $admin = $this->userWithRole('admin_it');
        $existing = $this->existingRecord();
        $snapshot = $this->evidencedSnapshotWith(['source_status' => 'Diperbarui']);
        $records = $snapshot->records;
        unset($records[0]['source_synced_at']);
        $driver = new Task10EtatibDriver(new EtatibSnapshot(true, $records, $snapshot->evidence));
        $this->bindConfiguredConnector($driver);
        $this->seedActiveSetting($driver);

        $run = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_FAILED, $run->status);
        $this->assertSame('Terverifikasi', $existing->refresh()->source_status);
    }

    public function test_configured_sync_rejects_mutable_update_with_stale_revision(): void
    {
        $admin = $this->userWithRole('admin_it');
        $existing = $this->existingRecord();
        $driver = new Task10EtatibDriver($this->evidencedSnapshotWith([
            'source_status' => 'Diperbarui',
            'source_synced_at' => '2026-08-19 08:00:00',
        ]));
        $this->bindConfiguredConnector($driver);
        $this->seedActiveSetting($driver);

        $run = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_FAILED, $run->status);
        $this->assertSame('Terverifikasi', $existing->refresh()->source_status);
        $this->assertSame('2026-08-20 08:00:00', $existing->source_synced_at?->format('Y-m-d H:i:s'));
    }

    public function test_configured_sync_accepts_mutable_update_with_newer_revision_and_persists_provenance(): void
    {
        $admin = $this->userWithRole('admin_it');
        $existing = $this->existingRecord();
        $snapshot = $this->evidencedSnapshotWith([
            'source_status' => 'Diperbarui',
            'source_synced_at' => '2026-08-21 08:00:00',
        ]);
        $driver = new Task10EtatibDriver($snapshot);
        $this->bindConfiguredConnector($driver);
        $this->seedActiveSetting($driver);

        $run = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $run->status, (string) $run->summary);
        $this->assertSame('Diperbarui', $existing->refresh()->source_status);
        $this->assertSame('2026-08-21 08:00:00', $existing->source_synced_at?->format('Y-m-d H:i:s'));
    }

    public function test_configuration_change_during_fetch_rejects_snapshot_and_keeps_old_data(): void
    {
        Exceptions::fake();
        $admin = $this->userWithRole('admin_it');
        $old = ExternalTatibRecord::query()->create([
            'source_identifier' => 'old', 'nisn' => '0000000001', 'occurred_at' => now(),
            'violation_type' => 'Data lama', 'category' => 'Lama', 'points' => 1,
            'is_active' => true, 'synced_at' => now(),
        ]);
        $driver = new Task10EtatibDriver($this->evidencedSnapshot());
        $driver->onFetch = static fn () => IntegrationSetting::query()
            ->where('provider', 'etatib')
            ->increment('configuration_version');
        $this->bindConfiguredConnector($driver);
        $this->seedActiveSetting($driver);

        $run = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_FAILED, $run->status);
        $this->assertTrue($old->refresh()->is_active);
        $this->assertDatabaseMissing('external_tatib_records', ['source_identifier' => 'tatib-1']);
        $this->assertStringNotContainsString('secret', (string) $run->summary);
        Exceptions::assertNothingReported();
    }

    public function test_invalid_oversized_or_colliding_snapshot_is_rejected_before_import(): void
    {
        ExternalTatibRecord::query()->create([
            'source_identifier' => 'tatib-1', 'nisn' => '0099999999', 'occurred_at' => now(),
            'violation_type' => 'Data lama', 'category' => 'Lama', 'points' => 1,
            'is_active' => true, 'synced_at' => now(),
        ]);
        $snapshot = $this->evidencedSnapshot(bytes: 2049);
        $validator = $this->admittedValidator();

        try {
            $validator->validate($snapshot, $this->runtimeConfiguration());
            $this->fail('Snapshot oversized harus ditolak.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('response_too_large', $exception->resultCode());
        }

        $valid = $this->evidencedSnapshot();
        $records = $valid->records;
        $records[0]['points'] = '5';
        $invalid = new EtatibSnapshot(true, $records, $valid->evidence);
        try {
            $validator->validate($invalid, $this->runtimeConfiguration());
            $this->fail('Tipe data yang salah harus ditolak.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('contract_invalid', $exception->resultCode());
        }

        $this->expectException(IntegrationConfigurationException::class);
        $validator->validate($this->evidencedSnapshot(), $this->runtimeConfiguration());
    }

    public function test_etatib_validator_rejects_scalar_and_null_records_as_contract_invalid(): void
    {
        $snapshot = $this->evidencedSnapshot();

        foreach (['invalid', null] as $invalidItem) {
            $invalid = new EtatibSnapshot(true, [$invalidItem], $snapshot->evidence);

            try {
                $this->admittedValidator()->validate($invalid, $this->runtimeConfiguration());
                $this->fail(sprintf('Record %s harus ditolak.', get_debug_type($invalidItem)));
            } catch (IntegrationConfigurationException $exception) {
                $this->assertSame('contract_invalid', $exception->resultCode());
            }
        }
    }

    public function test_success_audit_failure_rolls_back_etatib_import_before_recording_failure(): void
    {
        Exceptions::fake();
        $admin = $this->userWithRole('admin_it');
        Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Resmi', 'is_active' => true]);
        $audit = new class extends AuditService
        {
            private int $calls = 0;

            public function record(
                string $action,
                Model $auditable,
                string $summary,
                ?User $actor = null,
                ?array $before = null,
                ?array $after = null,
                ?Request $request = null,
            ): AuditLog {
                $this->calls++;
                if ($this->calls === 1) {
                    throw new RuntimeException('Synthetic audit failure.');
                }

                return parent::record($action, $auditable, $summary, $actor, $before, $after, $request);
            }
        };
        $this->app->instance(AuditService::class, $audit);
        $driver = new Task10EtatibDriver($this->evidencedSnapshot());
        $this->bindConfiguredConnector($driver);
        $this->seedActiveSetting($driver);

        $run = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_FAILED, $run->status);
        $this->assertDatabaseMissing('external_tatib_records', ['source_identifier' => 'tatib-1']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'etatib.sync_completed']);
    }

    public function test_failure_audit_failure_keeps_import_rolled_back_and_run_terminal(): void
    {
        Exceptions::fake();
        $admin = $this->userWithRole('admin_it');
        Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Resmi', 'is_active' => true]);
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
        $driver = new Task10EtatibDriver($this->evidencedSnapshot());
        $this->bindConfiguredConnector($driver);
        $this->seedActiveSetting($driver);

        $run = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_FAILED, $run->status);
        $this->assertNotNull($run->finished_at);
        $this->assertDatabaseMissing('external_tatib_records', ['source_identifier' => 'tatib-1']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'etatib.sync_completed']);
    }

    public function test_deadline_change_before_success_completion_does_not_settle_from_stale_context(): void
    {
        $now = CarbonImmutable::parse('2026-09-10 08:00:00');
        CarbonImmutable::setTestNow($now);
        $admin = $this->userWithRole('admin_it');
        Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Resmi', 'is_active' => true]);
        $audit = new class extends AuditService
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
                CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(2));

                return parent::record($action, $auditable, $summary, $actor, $before, $after, $request);
            }
        };
        $this->app->instance(AuditService::class, $audit);
        $driver = new Task10EtatibDriver($this->evidencedSnapshot());
        $this->bindConfiguredConnector($driver);
        $this->seedActiveSetting($driver);

        try {
            app(EtatibSyncService::class)->synchronize($admin);
            $this->fail('Konteks kedaluwarsa tidak boleh menyelesaikan run.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('timeout', $exception->resultCode());
        } finally {
            CarbonImmutable::setTestNow();
        }

        $run = ExternalSyncRun::query()->sole();
        $this->assertSame(ExternalSyncRun::STATUS_RUNNING, $run->status);
        $this->assertNull($run->finished_at);
        $this->assertDatabaseMissing('external_tatib_records', ['source_identifier' => 'tatib-1']);
        $this->assertDatabaseMissing('audit_logs', ['auditable_id' => $run->getKey()]);
    }

    public function test_fence_change_before_success_completion_does_not_settle_from_stale_context(): void
    {
        $admin = $this->userWithRole('admin_it');
        Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Resmi', 'is_active' => true]);
        $driver = new Task10EtatibDriver($this->evidencedSnapshot());
        $driver->onFetch = static fn () => IntegrationSetting::query()
            ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
            ->increment('operation_fence_version');
        $this->bindConfiguredConnector($driver);
        $this->seedActiveSetting($driver);

        try {
            app(EtatibSyncService::class)->synchronize($admin);
            $this->fail('Konteks dengan fencing lama tidak boleh menyelesaikan run.');
        } catch (IntegrationConfigurationException $exception) {
            $this->assertSame('configuration_changed', $exception->resultCode());
        }

        $run = ExternalSyncRun::query()->sole();
        $this->assertSame(ExternalSyncRun::STATUS_RUNNING, $run->status);
        $this->assertNull($run->finished_at);
        $this->assertDatabaseMissing('external_tatib_records', ['source_identifier' => 'tatib-1']);
        $this->assertDatabaseMissing('audit_logs', ['auditable_id' => $run->getKey()]);
    }

    public function test_failure_settlement_does_not_overwrite_a_terminal_run(): void
    {
        $admin = $this->userWithRole('admin_it');
        $driver = new Task10EtatibDriver($this->evidencedSnapshot());
        $driver->onFetch = static function (): never {
            ExternalSyncRun::query()->latest('id')->firstOrFail()->update([
                'status' => ExternalSyncRun::STATUS_SUCCEEDED,
                'summary' => 'Sudah diselesaikan proses lain.',
                'finished_at' => now(),
            ]);

            throw new IntegrationConfigurationException('configuration_changed');
        };
        $this->bindConfiguredConnector($driver);
        $this->seedActiveSetting($driver);

        $run = app(EtatibSyncService::class)->synchronize($admin);

        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $run->status);
        $this->assertSame('Sudah diselesaikan proses lain.', $run->summary);
        $this->assertDatabaseMissing('audit_logs', ['auditable_id' => $run->getKey()]);
    }

    public function test_busy_sync_fails_safely_without_writing_outside_the_operation_lock(): void
    {
        $admin = $this->userWithRole('admin_it');
        $lock = cache()->lock('sibk:integration:etatib:operation', 60);
        $this->assertTrue($lock->get());

        try {
            $this->actingAs($admin)->post(route('data-master.etatib.sync'))
                ->assertRedirect()
                ->assertSessionHasErrors('etatib_sync');
            $this->assertDatabaseCount('external_sync_runs', 0);
            $this->assertDatabaseCount('audit_logs', 0);
        } finally {
            $lock->release();
        }
    }

    private function fakeConnector(EtatibSnapshot $snapshot): void
    {
        $this->app->instance(EtatibConnector::class, new class($snapshot) implements EtatibConnector
        {
            public function __construct(private readonly EtatibSnapshot $snapshot) {}

            public function fetchSnapshot(IntegrationOperationContext $context): EtatibSnapshot
            {
                return $this->snapshot;
            }
        });
    }

    private function snapshot(bool $isFull): EtatibSnapshot
    {
        return new EtatibSnapshot($isFull, [[
            'source_id' => 'tatib-1',
            'nisn' => '0012345678',
            'occurred_at' => '2026-08-18 09:00:00',
            'violation_type' => 'Terlambat',
            'category' => 'Kedisiplinan',
            'points' => 5,
            'source_status' => 'Terverifikasi',
            'source_synced_at' => '2026-08-20 08:00:00',
        ]]);
    }

    private function evidencedSnapshot(int $bytes = 512): EtatibSnapshot
    {
        $snapshot = $this->snapshot(true);

        return new EtatibSnapshot(
            true,
            $snapshot->records,
            new IntegrationSnapshotEvidence('school-01', 'contract-v1', 'full', 1, 1, $bytes),
        );
    }

    /** @param array<string, mixed> $overrides */
    private function evidencedSnapshotWith(array $overrides): EtatibSnapshot
    {
        $snapshot = $this->evidencedSnapshot();
        $records = $snapshot->records;
        $records[0] = array_replace($records[0], $overrides);

        return new EtatibSnapshot(true, $records, $snapshot->evidence);
    }

    private function existingRecord(): ExternalTatibRecord
    {
        Student::query()->firstOrCreate(
            ['nisn' => '0012345678'],
            ['name' => 'Murid Resmi', 'is_active' => true],
        );

        return ExternalTatibRecord::query()->create([
            'source_identifier' => 'tatib-1',
            'nisn' => '0012345678',
            'occurred_at' => '2026-08-18 09:00:00',
            'violation_type' => 'Terlambat',
            'category' => 'Kedisiplinan',
            'points' => 5,
            'source_status' => 'Terverifikasi',
            'is_active' => true,
            'source_synced_at' => '2026-08-20 08:00:00',
            'synced_at' => now(),
        ]);
    }

    private function admittedValidator(): EtatibSnapshotValidator
    {
        return new EtatibSnapshotValidator(
            'contract-v1',
            ['full' => true, 'partial' => false],
            2,
            10,
            2048,
            ['source_id', 'nisn', 'occurred_at', 'violation_type', 'category', 'points'],
            ['source_status', 'source_synced_at'],
            'source_synced_at_timestamp',
        );
    }

    private function bindConfiguredConnector(Task10EtatibDriver $driver): void
    {
        $registry = new IntegrationDriverRegistry(etatibDriver: $driver);
        $service = new IntegrationSettingService($registry, app(IntegrationOperationLock::class), app(AuditService::class));
        $this->app->instance(IntegrationDriverRegistry::class, $registry);
        $this->app->instance(EtatibConnector::class, new ConfiguredEtatibConnector($registry, $service, $this->admittedValidator()));
    }

    private function seedActiveSetting(Task10EtatibDriver $driver): void
    {
        config()->set('sibk.integrations.etatib', [
            'driver' => 'unavailable',
            'allowed_origins' => ['https://etatib.example.test'],
            'allow_private_networks' => false,
        ]);
        IntegrationSetting::query()->updateOrCreate(['provider' => 'etatib'], [
            'base_url' => 'https://etatib.example.test',
            'expected_source_identifier' => 'school-01',
            'credentials' => ['type' => 'api_token', 'token' => 'secret'],
            'configuration_version' => 1,
            'verified_configuration_version' => 1,
            'verified_driver_id' => $driver->id(),
            'verified_adapter_version' => $driver->adapterVersion(),
            'verified_contract_version' => $driver->contractVersion(),
            'verified_endpoint_policy_digest' => (new IntegrationEndpointPolicy(['https://etatib.example.test'], false))->digest(),
            'last_test_status' => 'success',
            'last_test_code' => 'success',
            'is_enabled' => true,
        ]);
    }

    private function runtimeConfiguration(): IntegrationRuntimeConfiguration
    {
        return new IntegrationRuntimeConfiguration(
            provider: 'etatib',
            baseUrl: 'https://etatib.example.test',
            expectedSourceIdentifier: 'school-01',
            credentials: ['type' => 'api_token', 'token' => 'secret'],
            timeoutSeconds: 30,
            configurationVersion: 1,
            operationFenceVersion: 1,
            endpointPolicyDigest: str_repeat('a', 64),
        );
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}

final class Task10EtatibDriver implements EtatibDriver
{
    public int $fetchCalls = 0;

    public ?\Closure $onFetch = null;

    public function __construct(private readonly EtatibSnapshot $snapshot) {}

    public function id(): string
    {
        return 'synthetic-etatib';
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

    public function fetchSnapshot(IntegrationRuntimeConfiguration $configuration): EtatibSnapshot
    {
        $this->fetchCalls++;
        ($this->onFetch ?? static fn (): null => null)();

        return $this->snapshot;
    }
}
