<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentIdentityService;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemporaryIdentityConflictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    #[Test]
    public function different_names_for_the_same_unverified_nisn_are_held_as_conflicts(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $service = app(StudentIdentityService::class);

        $first = $service->createTemporary('0012345678', 'Nama Pertama', $teacher);
        $second = $service->createTemporary('0012345678', 'Nama Berbeda', $teacher);

        $conflictStatusId = ReferenceValue::query()
            ->where('category', 'reconciliation_status')
            ->where('code', 'ditahan_konflik')
            ->firstOrFail()
            ->id;

        $this->assertSame($conflictStatusId, $first->refresh()->reconciliation_status_id);
        $this->assertSame($conflictStatusId, $second->refresh()->reconciliation_status_id);
        $this->assertNull($first->reconciled_student_id);
        $this->assertNull($second->reconciled_student_id);
    }

    #[Test]
    public function unresolved_source_identity_mismatch_blocks_exact_nisn_reconciliation(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $service = app(StudentIdentityService::class);
        $temporary = $service->createTemporary('0012345678', 'Nama Masukan', $teacher);
        $student = Student::query()->create([
            'dapodik_id' => 'student-official',
            'nisn' => '0012345678',
            'name' => 'Nama Resmi',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now(),
        ]);
        $run = ExternalSyncRun::query()->create([
            'source' => 'dapodik',
            'status' => ExternalSyncRun::STATUS_WARNING,
            'is_full_snapshot' => false,
            'received_count' => 1,
            'processed_count' => 0,
            'conflict_count' => 1,
            'started_at' => now(),
        ]);
        $issue = ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->id,
            'entity_type' => 'student',
            'source_identifier' => 'student-conflicting-source',
            'nisn' => '0012345678',
            'input_name' => 'Nama Konflik',
            'issue_code' => 'source_identity_mismatch',
            'summary' => 'Source ID bertentangan dengan NISN lokal.',
        ]);

        $reconciliation = $service->reconcile($temporary, $teacher);

        $conflictStatusId = ReferenceValue::query()
            ->where('category', 'reconciliation_status')
            ->where('code', 'ditahan_konflik')
            ->firstOrFail()
            ->id;
        $this->assertSame($conflictStatusId, $temporary->refresh()->reconciliation_status_id);
        $this->assertNull($temporary->reconciled_student_id);
        $this->assertNull($issue->refresh()->resolved_at);
        $this->assertNull($issue->resolved_student_id);
        $this->assertNull($reconciliation->student_id);
        $this->assertContains('source_identity_mismatch', $reconciliation->conflict_details);
        $this->assertSame('student-official', $student->refresh()->dapodik_id);
    }

    #[Test]
    public function etatib_source_identity_issue_does_not_block_dapodik_identity_reconciliation(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $service = app(StudentIdentityService::class);
        $temporary = $service->createTemporary('0012345678', 'Nama Masukan', $teacher);
        $student = Student::query()->create([
            'dapodik_id' => 'student-official',
            'nisn' => '0012345678',
            'name' => 'Nama Resmi',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now(),
        ]);
        $run = ExternalSyncRun::query()->create([
            'source' => 'etatib',
            'status' => ExternalSyncRun::STATUS_WARNING,
            'is_full_snapshot' => false,
            'received_count' => 1,
            'processed_count' => 0,
            'conflict_count' => 1,
            'started_at' => now(),
        ]);
        $issue = ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->id,
            'entity_type' => 'etatib_record',
            'source_identifier' => 'tatib-conflicting-source',
            'nisn' => '0012345678',
            'input_name' => 'Nama Konflik',
            'issue_code' => 'source_identity_mismatch',
            'summary' => 'Identitas record e-Tatib bertentangan dengan NISN lokal.',
        ]);

        $reconciliation = $service->reconcile($temporary, $teacher);

        $this->assertSame($student->id, $temporary->refresh()->reconciled_student_id);
        $this->assertSame($student->id, $reconciliation->student_id);
        $this->assertNull($issue->refresh()->resolved_at);
        $this->assertNull($issue->resolved_student_id);
    }

    #[Test]
    public function unresolved_dapodik_duplicate_nisn_issue_blocks_reconciliation(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $service = app(StudentIdentityService::class);
        $temporary = $service->createTemporary('0012345678', 'Nama Masukan', $teacher);
        $student = Student::query()->create([
            'dapodik_id' => 'student-official',
            'nisn' => '0012345678',
            'name' => 'Nama Resmi',
            'is_active' => true,
            'master_source' => Student::MASTER_SOURCE_DAPODIK,
            'source_confirmed_at' => now(),
        ]);
        $run = ExternalSyncRun::query()->create([
            'source' => 'dapodik',
            'status' => ExternalSyncRun::STATUS_WARNING,
            'is_full_snapshot' => false,
            'received_count' => 2,
            'processed_count' => 0,
            'conflict_count' => 1,
            'started_at' => now(),
        ]);
        $issue = ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->id,
            'entity_type' => 'student',
            'source_identifier' => 'student-official',
            'nisn' => '0012345678',
            'input_name' => 'Nama Duplikat',
            'issue_code' => 'duplicate_nisn',
            'summary' => 'NISN muncul lebih dari sekali pada sumber Dapodik.',
        ]);

        $reconciliation = $service->reconcile($temporary, $teacher);

        $this->assertNull($temporary->refresh()->reconciled_student_id);
        $this->assertNull($reconciliation->student_id);
        $this->assertContains('duplicate_nisn', $reconciliation->conflict_details);
        $this->assertNull($issue->refresh()->resolved_at);
        $this->assertSame('student-official', $student->refresh()->dapodik_id);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
