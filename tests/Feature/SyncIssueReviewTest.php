<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SyncIssueReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function authenticateAs(string $roleSlug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $roleSlug)->firstOrFail());
        $this->actingAs($user);

        return $user;
    }

    private function issue(string $source = 'dapodik'): ExternalSyncIssue
    {
        $run = ExternalSyncRun::query()->create(['source' => $source, 'status' => ExternalSyncRun::STATUS_WARNING, 'started_at' => now()]);

        return ExternalSyncIssue::query()->create([
            'external_sync_run_id' => $run->id,
            'entity_type' => $source === 'etatib' ? 'etatib_record' : 'student',
            'source_identifier' => 'local:1',
            'issue_code' => 'unmatched_local_record',
            'summary' => 'Periksa sumber.',
            'details' => ['original' => 'tetap'],
        ]);
    }

    public function test_admin_review_records_note_without_resolving_or_replacing_details(): void
    {
        $admin = $this->authenticateAs('admin_it');
        $issue = $this->issue();

        $this->patch(route('data-master.sync-issues.update', $issue), [
            'action' => 'source_correction', 'note' => 'Perbaiki NISN pada sumber.',
        ])->assertRedirect(route('data-master.index', ['tab' => 'sinkronisasi']).'#sync-issues-title');

        $issue->refresh();
        $this->assertNull($issue->resolved_at);
        $this->assertNull($issue->resolved_student_id);
        $this->assertSame('tetap', $issue->details['original']);
        $this->assertSame('source_correction', $issue->details['review']['status']);
        $this->assertSame($admin->id, $issue->details['review']['reviewed_by']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'sync_issue.reviewed', 'auditable_id' => $issue->id]);
        $this->get(route('data-master.index', ['tab' => 'sinkronisasi']))
            ->assertOk()->assertSee('Perlu koreksi sumber')->assertSee('1 data memiliki masalah');
    }

    public function test_review_requires_admin_valid_status_and_note_and_rejects_stale_resolved_issue(): void
    {
        $admin = $this->authenticateAs('admin_it');
        $issue = $this->issue();
        $url = route('data-master.sync-issues.update', $issue);
        $this->patch($url, ['action' => 'resolved', 'note' => 'x'])->assertSessionHasErrors('action');
        $this->patch($url, ['action' => 'source_correction'])->assertSessionHasErrors('note');
        $this->assertSame(['original' => 'tetap'], $issue->fresh()->details);

        $admin->roles()->detach();
        $admin->roles()->attach(Role::query()->where('slug', 'guru_bk')->firstOrFail());
        $this->get(route('data-master.sync-issues.show', $issue))->assertForbidden();
        $this->patch($url, ['action' => 'source_correction', 'note' => 'Sudah dilihat.'])->assertForbidden();

        $admin->roles()->detach();
        $admin->roles()->attach(Role::query()->where('slug', 'admin_it')->firstOrFail());
        $issue->update(['resolved_at' => now()]);
        $this->patch($url, ['action' => 'source_correction', 'note' => 'Sudah dilihat.'])->assertSessionHasErrors('action');
        $this->assertSame(['original' => 'tetap'], $issue->fresh()->details);
    }

    public function test_etatib_detail_uses_class_from_occurrence_year_and_keeps_identity_action_visible(): void
    {
        $this->authenticateAs('admin_it');
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Master', 'is_active' => true]);
        $oldYear = AcademicYear::query()->create(['name' => '2025/2026', 'starts_on' => '2025-07-01', 'ends_on' => '2026-06-30', 'is_active' => false]);
        $newYear = AcademicYear::query()->create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $oldClass = Classroom::query()->create(['name' => '10 RPL 1', 'academic_year_id' => $oldYear->id, 'is_active' => false]);
        $newClass = Classroom::query()->create(['name' => '11 RPL 1', 'academic_year_id' => $newYear->id, 'is_active' => true]);
        StudentClassMembership::query()->create(['student_id' => $student->id, 'classroom_id' => $oldClass->id, 'academic_year_id' => $oldYear->id, 'is_active' => true]);
        StudentClassMembership::query()->create(['student_id' => $student->id, 'classroom_id' => $newClass->id, 'academic_year_id' => $newYear->id, 'is_active' => true]);
        $issue = $this->issue('etatib');
        $issue->update(['source_identifier' => 'etatib-1', 'issue_code' => 'student_name_mismatch', 'nisn' => '0012345678', 'input_name' => 'Murid Sumber']);
        ExternalTatibRecord::query()->create([
            'source_identifier' => 'etatib-1', 'nisn' => '0012345678', 'source_nisn' => '0012345678',
            'student_id' => null, 'source_student_name' => 'Murid Sumber',
            'source_classroom_name' => '10 RPL 2', 'occurred_at' => '2025-09-01',
            'violation_type' => 'Contoh', 'category' => 'ringan', 'points' => 10, 'is_active' => true,
        ]);

        $this->get(route('data-master.sync-issues.show', $issue))->assertOk()
            ->assertSee('Murid Sumber')->assertSee('Murid Master')
            ->assertSee('10 RPL 2')->assertSee('10 RPL 1')
            ->assertDontSee('11 RPL 1')->assertSee('Cocokkan identitas murid')
            ->assertSee('Calon murid dengan NISN sama; belum dicocokkan.');

        $issue->update(['issue_code' => 'source_identity_mismatch', 'nisn' => '0098765432', 'input_name' => 'Identitas Baru']);
        $this->get(route('data-master.sync-issues.show', $issue))->assertOk()
            ->assertSee('Identitas Baru')->assertSee('0098765432')
            ->assertSee('Data tersimpan sebelumnya: Murid Sumber, NISN 0012345678');
    }
}
