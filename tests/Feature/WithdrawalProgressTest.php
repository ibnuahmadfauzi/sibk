<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\WithdrawalProgress;
use App\Models\WithdrawalProgressFollowUp;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WithdrawalProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->travelTo('2026-09-27 09:00:00');
    }

    public function test_create_page_records_fixed_initial_progress_and_history(): void
    {
        [$teacher, $student, $classroom] = $this->assignedStudent();

        $this->actingAs($teacher)->get(route('withdrawals.create'))
            ->assertOk()
            ->assertSee('data-withdrawal-create-page', false)
            ->assertSee('id="withdrawal-student-lookup"', false)
            ->assertSee('role="listbox"', false)
            ->assertSee('href="'.route('cases.index', ['tab' => 'pengunduran-diri']).'"', false)
            ->assertSee('btn-icon btn-light', false)
            ->assertSee('aria-label="Kembali ke daftar pengunduran diri"', false)
            ->assertSee('polyline points="12 19 5 12 12 5"', false)
            ->assertSeeInOrder(['Murid dan Tanggal', 'Catatan Pengunduran Diri', 'Progres awal'])
            ->assertSee('name="student_id"', false)
            ->assertSee('name="recorded_on"', false)
            ->assertSee('name="note"', false)
            ->assertSee('Catatan <span class="text-danger">*</span>', false)
            ->assertDontSee('name="reason"', false)
            ->assertDontSee('name="progress"', false);

        $this->post(route('withdrawals.store'), [
            ...$this->payload($student),
            'progress' => WithdrawalProgress::PROGRESS_AT_TU,
        ])->assertRedirect(route('cases.index', ['tab' => 'pengunduran-diri']));

        $withdrawal = WithdrawalProgress::query()->firstOrFail();
        $this->assertSame($teacher->id, $withdrawal->teacher_id);
        $this->assertSame($classroom->id, $withdrawal->classroom_id);
        $this->assertSame(WithdrawalProgress::PROGRESS_IN_PROGRESS, $withdrawal->progress);
        $this->assertDatabaseHas('withdrawal_progress_follow_ups', [
            'withdrawal_progress_id' => $withdrawal->id,
            'progress' => WithdrawalProgress::PROGRESS_IN_PROGRESS,
            'created_by' => $teacher->id,
            'notes' => null,
        ]);
        $this->assertDatabaseCount('withdrawal_progress_follow_ups', 1);
        $this->assertSame('2026-09-26', WithdrawalProgressFollowUp::query()->firstOrFail()->follow_up_date->toDateString());

        $this->post(route('withdrawals.store'), $this->payload($student))
            ->assertSessionHasErrors('student_id');
        $this->assertDatabaseCount('withdrawal_progresses', 1);
    }

    public function test_follow_ups_are_append_only_and_latest_date_controls_current_progress(): void
    {
        [$teacher, $student] = $this->assignedStudent();
        $this->actingAs($teacher)->post(route('withdrawals.store'), $this->payload($student));
        $withdrawal = WithdrawalProgress::query()->firstOrFail();

        $this->postJson(route('withdrawals.follow-ups.store', $withdrawal), [
            'progress' => WithdrawalProgress::PROGRESS_AT_TU,
            'follow_up_date' => '2026-09-27',
        ])->assertOk()
            ->assertJsonPath('data.current_progress', WithdrawalProgress::PROGRESS_AT_TU)
            ->assertJsonCount(2, 'data.follow_ups');

        $this->postJson(route('withdrawals.follow-ups.store', $withdrawal), [
            'progress' => WithdrawalProgress::PROGRESS_AT_BK,
            'follow_up_date' => '2026-09-26',
        ])->assertOk()->assertJsonPath('data.current_progress', WithdrawalProgress::PROGRESS_AT_TU);

        $this->postJson(route('withdrawals.follow-ups.store', $withdrawal), [
            'progress' => WithdrawalProgress::PROGRESS_AT_BK,
            'follow_up_date' => '2026-09-27',
        ])->assertOk()->assertJsonPath('data.current_progress', WithdrawalProgress::PROGRESS_AT_BK);

        $this->assertSame(WithdrawalProgress::PROGRESS_AT_BK, $withdrawal->refresh()->progress);
        $this->assertDatabaseCount('withdrawal_progress_follow_ups', 4);
        $this->assertSame(3, AuditLog::query()->where('action', 'withdrawal_progress.follow_up_added')->count());
        $this->assertDatabaseCount('student_departures', 0);
    }

    public function test_follow_up_validates_progress_and_date_range(): void
    {
        [$teacher, $student] = $this->assignedStudent();
        $this->actingAs($teacher)->post(route('withdrawals.store'), $this->payload($student));
        $withdrawal = WithdrawalProgress::query()->firstOrFail();

        $this->postJson(route('withdrawals.follow-ups.store', $withdrawal), [
            'progress' => 'resmi_keluar',
            'follow_up_date' => '2026-09-25',
        ])->assertUnprocessable()->assertJsonValidationErrors(['progress', 'follow_up_date']);

        $this->postJson(route('withdrawals.follow-ups.store', $withdrawal), [
            'progress' => WithdrawalProgress::PROGRESS_AT_BK,
            'follow_up_date' => '2026-09-28',
        ])->assertUnprocessable()->assertJsonValidationErrors('follow_up_date');

        $this->assertDatabaseCount('withdrawal_progress_follow_ups', 1);
    }

    public function test_authorization_keeps_coordinator_read_only_and_blocks_other_roles(): void
    {
        [$teacher, $student] = $this->assignedStudent();
        $otherTeacher = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $admin = $this->userWithRole('admin_it');

        $this->actingAs($otherTeacher)->get(route('withdrawals.create'))->assertOk();
        $this->post(route('withdrawals.store'), $this->payload($student))->assertSessionHasErrors('student_id');
        $this->actingAs($teacher)->post(route('withdrawals.store'), $this->payload($student));
        $withdrawal = WithdrawalProgress::query()->firstOrFail();

        foreach ([$otherTeacher, $coordinator, $waka, $admin] as $user) {
            $this->actingAs($user)->post(route('withdrawals.follow-ups.store', $withdrawal), [
                'progress' => WithdrawalProgress::PROGRESS_AT_TU,
                'follow_up_date' => '2026-09-27',
            ])->assertForbidden();
        }
        foreach ([$coordinator, $waka, $admin] as $user) {
            $this->actingAs($user)->get(route('withdrawals.create'))->assertForbidden();
            $this->post(route('withdrawals.store'), $this->payload($student))->assertForbidden();
        }

        $this->assertTrue($coordinator->can('view', $withdrawal));
        $this->assertFalse($waka->can('view', $withdrawal));
        $this->assertFalse($admin->can('view', $withdrawal));
        $this->assertDatabaseCount('withdrawal_progress_follow_ups', 1);
    }

    public function test_index_uses_case_style_follow_up_and_responsive_local_detail(): void
    {
        [$teacher, $student] = $this->assignedStudent();
        $this->actingAs($teacher)->post(route('withdrawals.store'), $this->payload($student));
        $withdrawal = WithdrawalProgress::query()->firstOrFail();
        $url = route('cases.index', ['tab' => 'pengunduran-diri']);

        $this->get($url)->assertOk()
            ->assertSeeInOrder(['No', 'Nama Guru', 'Tanggal', 'Nama Siswa', 'Kelas', 'Progres Penanganan', 'Catatan'])
            ->assertSee('href="'.route('withdrawals.create').'"', false)
            ->assertSee('data-withdrawal-popover-trigger', false)
            ->assertSee('data-withdrawal-follow-up-open', false)
            ->assertSee('data-store-url="'.route('withdrawals.follow-ups.store', $withdrawal).'"', false)
            ->assertSee('data-withdrawal-detail', false)
            ->assertSee('modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable', false)
            ->assertSee('data-detail-history', false)
            ->assertSee('Riwayat Progres Penanganan')
            ->assertSee('Tambah Progres Penanganan')
            ->assertDontSee('Riwayat Tindak Lanjut')
            ->assertDontSee('name="notes"', false)
            ->assertSee('<span data-withdrawal-follow-up-label>'.WithdrawalProgress::labels()[WithdrawalProgress::PROGRESS_IN_PROGRESS].'</span>', false)
            ->assertSee('Pertemuan dengan keluarga.')
            ->assertSee('data-note="Pertemuan dengan keluarga."', false)
            ->assertSee($student->nisn)
            ->assertSee('href="'.route('withdrawals.edit', $withdrawal).'"', false)
            ->assertSee('data-confirm-title="Hapus penanganan pengunduran diri?"', false)
            ->assertDontSee('withdrawal-progress-badge', false)
            ->assertDontSee('data-withdrawal-progress', false)
            ->assertDontSee('id="withdrawal-create-modal"', false)
            ->assertDontSee('data-report-detail-toggle', false);

        $this->get($url.'&search=Tidak%20Ada')->assertOk()->assertDontSee('Pertemuan dengan keluarga.');
        $this->get($url.'&progress='.WithdrawalProgress::PROGRESS_AT_TU)->assertOk()->assertDontSee('Pertemuan dengan keluarga.');

        $coordinator = $this->userWithRole('koordinator_bk');
        $this->actingAs($coordinator)->get($url)->assertOk()
            ->assertSee($student->name)
            ->assertSee('data-withdrawal-popover-trigger', false)
            ->assertDontSee('data-withdrawal-follow-up-open', false)
            ->assertDontSee(route('withdrawals.create'))
            ->assertDontSee(route('withdrawals.edit', $withdrawal), false)
            ->assertDontSee('data-confirm-title="Hapus penanganan pengunduran diri?"', false);
        foreach (['waka_kesiswaan', 'admin_it'] as $role) {
            $this->actingAs($this->userWithRole($role))->get($url)->assertForbidden();
        }
    }

    public function test_edit_modal_is_available_for_owner_teacher_only(): void
    {
        [$teacher, $student] = $this->assignedStudent();
        $this->actingAs($teacher)->post(route('withdrawals.store'), $this->payload($student));
        $withdrawal = WithdrawalProgress::query()->firstOrFail();

        $this->actingAs($teacher)->get(route('withdrawals.edit', [$withdrawal, 'modal' => 1]))->assertOk()
            ->assertSee('Edit Pengunduran Diri')
            ->assertSee($student->name)
            ->assertSee($student->nisn)
            ->assertSee('action="'.route('withdrawals.update', $withdrawal).'"', false)
            ->assertSee('data-bs-dismiss="modal"', false)
            ->assertSee('name="recorded_on"', false)
            ->assertSee('name="note"', false)
            ->assertSee('Catatan <span class="text-danger">*</span>', false)
            ->assertDontSee('name="reason"', false)
            ->assertDontSee('name="student_id"', false)
            ->assertDontSee('<html', false);

        $indexResponse = $this->actingAs($teacher)->get(route('cases.index', ['tab' => 'pengunduran-diri']));
        $indexResponse->assertOk()
            ->assertSee('data-modal-url="'.route('withdrawals.edit', [$withdrawal, 'modal' => 1]).'"', false)
            ->assertSee('data-service-record-modal', false)
            ->assertSee('modal-lg modal-dialog-centered modal-dialog-scrollable', false);

        $this->actingAs($this->userWithRole('guru_bk'))->get(route('withdrawals.edit', [$withdrawal, 'modal' => 1]))->assertForbidden();
        $this->actingAs($this->userWithRole('koordinator_bk'))->get(route('withdrawals.edit', [$withdrawal, 'modal' => 1]))->assertForbidden();
    }

    public function test_update_changes_fields_and_recomputes_classroom_for_new_date(): void
    {
        [$teacher, $student, $classroom] = $this->assignedStudent();
        $this->actingAs($teacher)->post(route('withdrawals.store'), $this->payload($student));
        $withdrawal = WithdrawalProgress::query()->firstOrFail();

        $pastYear = AcademicYear::query()->create([
            'name' => '2025/2026', 'starts_on' => '2025-07-01', 'ends_on' => '2026-06-30', 'is_active' => false,
        ]);
        $pastClassroom = Classroom::query()->create([
            'academic_year_id' => $pastYear->id, 'name' => 'IX RPL 1', 'is_active' => false,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id, 'classroom_id' => $pastClassroom->id,
            'academic_year_id' => $pastYear->id, 'is_active' => true,
        ]);

        $this->actingAs($teacher)->patch(route('withdrawals.update', $withdrawal), [
            'recorded_on' => '2026-06-15',
            'note' => 'Catatan tambahan.',
        ])->assertRedirect(route('cases.index', ['tab' => 'pengunduran-diri']));

        $withdrawal->refresh();
        $this->assertSame('2026-06-15', $withdrawal->recorded_on->toDateString());
        $this->assertSame('Catatan tambahan.', $withdrawal->note);
        $this->assertSame($pastClassroom->id, $withdrawal->classroom_id);
        $this->assertNotSame($classroom->id, $withdrawal->classroom_id);
        $this->assertSame('2026-06-15', WithdrawalProgressFollowUp::query()
            ->where('withdrawal_progress_id', $withdrawal->id)->firstOrFail()->follow_up_date->toDateString());
        $this->assertSame(1, AuditLog::query()->where('action', 'withdrawal_progress.updated')->count());

        $this->actingAs($teacher)->patchJson(route('withdrawals.update', $withdrawal), [
            'recorded_on' => '2026-06-15',
            'note' => 'Catatan modal diperbarui.',
        ])->assertOk()
            ->assertJsonPath('message', 'Penanganan pengunduran diri berhasil diperbarui.')
            ->assertJsonPath('redirect', route('cases.index', ['tab' => 'pengunduran-diri']))
            ->assertJsonPath('data.note', 'Catatan modal diperbarui.');

        $this->actingAs($teacher)->patchJson(route('withdrawals.update', $withdrawal), [
            'recorded_on' => '2026-06-15',
            'note' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['note']);

        $this->actingAs($teacher)->patch(route('withdrawals.update', $withdrawal), [
            'recorded_on' => '2025-06-15',
            'note' => 'Catatan tambahan.',
        ])->assertSessionHasErrors('recorded_on');
        $this->assertSame('2026-06-15', $withdrawal->refresh()->recorded_on->toDateString());

        foreach (['guru_bk', 'koordinator_bk'] as $role) {
            $this->actingAs($this->userWithRole($role))->patch(route('withdrawals.update', $withdrawal), [
                'recorded_on' => '2026-09-20',
                'note' => 'Catatan baru.',
            ])->assertForbidden();
        }
    }

    public function test_destroy_removes_withdrawal_and_its_follow_ups(): void
    {
        [$teacher, $student] = $this->assignedStudent();
        $this->actingAs($teacher)->post(route('withdrawals.store'), $this->payload($student));
        $withdrawal = WithdrawalProgress::query()->firstOrFail();
        $this->actingAs($teacher)->postJson(route('withdrawals.follow-ups.store', $withdrawal), [
            'progress' => WithdrawalProgress::PROGRESS_AT_TU,
            'follow_up_date' => '2026-09-27',
        ])->assertOk();
        $this->assertDatabaseCount('withdrawal_progress_follow_ups', 2);

        foreach (['guru_bk', 'koordinator_bk'] as $role) {
            $this->actingAs($this->userWithRole($role))->delete(route('withdrawals.destroy', $withdrawal))->assertForbidden();
        }

        $this->actingAs($teacher)->delete(route('withdrawals.destroy', $withdrawal))
            ->assertRedirect(route('cases.index', ['tab' => 'pengunduran-diri']));

        $this->assertDatabaseMissing('withdrawal_progresses', ['id' => $withdrawal->id]);
        $this->assertDatabaseMissing('withdrawal_progress_follow_ups', ['withdrawal_progress_id' => $withdrawal->id]);
        $deletedAudit = AuditLog::query()->where('action', 'withdrawal_progress.deleted')->firstOrFail();
        $this->assertSame($teacher->id, $deletedAudit->actor_id);
        $this->assertSame($withdrawal->id, $deletedAudit->auditable_id);
    }

    public function test_create_page_shows_empty_state_when_every_candidate_has_a_note(): void
    {
        [$teacher, $student] = $this->assignedStudent();
        $this->actingAs($teacher)->post(route('withdrawals.store'), $this->payload($student));

        $this->get(route('withdrawals.create'))->assertOk()
            ->assertSee('Semua murid sudah memiliki catatan')
            ->assertDontSee('data-withdrawal-create-form', false);
    }

    public function test_backdated_note_uses_classroom_from_recorded_year(): void
    {
        [$teacher, $student, $currentClassroom] = $this->assignedStudent();
        $this->actingAs($teacher)->post(route('withdrawals.store'), [
            ...$this->payload($student), 'recorded_on' => '2026-06-15',
        ])->assertSessionHasErrors('recorded_on');

        $pastYear = AcademicYear::query()->create([
            'name' => '2025/2026', 'starts_on' => '2025-07-01', 'ends_on' => '2026-06-30', 'is_active' => false,
        ]);
        $pastClassroom = Classroom::query()->create([
            'academic_year_id' => $pastYear->id, 'name' => 'IX RPL 1', 'is_active' => false,
        ]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id, 'classroom_id' => $pastClassroom->id,
            'academic_year_id' => $pastYear->id, 'is_active' => true,
        ]);

        $this->post(route('withdrawals.store'), [
            ...$this->payload($student), 'recorded_on' => '2026-06-15',
        ])->assertRedirect();
        $withdrawal = WithdrawalProgress::query()->firstOrFail();
        $this->assertSame($pastClassroom->id, $withdrawal->classroom_id);
        $this->assertNotSame($currentClassroom->id, $withdrawal->classroom_id);
        $this->assertSame('2026-06-15', WithdrawalProgressFollowUp::query()->firstOrFail()->follow_up_date->toDateString());
    }

    public function test_note_is_mandatory_for_create_and_update(): void
    {
        [$teacher, $student] = $this->assignedStudent();

        $this->actingAs($teacher)->post(route('withdrawals.store'), [
            'student_id' => $student->id,
            'recorded_on' => '2026-09-26',
            'note' => '',
        ])->assertSessionHasErrors(['note']);

        $response = $this->post(route('withdrawals.store'), [
            'student_id' => $student->id,
            'recorded_on' => '2026-09-26',
        ]);
        $response->assertSessionHasErrors(['note']);
        $this->assertSame('Catatan wajib diisi.', session('errors')->first('note'));

        $this->post(route('withdrawals.store'), $this->payload($student))->assertRedirect();
        $withdrawal = WithdrawalProgress::query()->firstOrFail();

        $this->patch(route('withdrawals.update', $withdrawal), [
            'recorded_on' => '2026-09-26',
            'note' => '',
        ])->assertSessionHasErrors(['note']);
        $this->assertSame('Catatan wajib diisi.', session('errors')->first('note'));
    }

    /** @return array{User, Student, Classroom} */
    private function assignedStudent(): array
    {
        $teacher = $this->userWithRole('guru_bk');
        $year = AcademicYear::query()->create([
            'name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true,
        ]);
        $classroom = Classroom::query()->create(['academic_year_id' => $year->id, 'name' => 'X RPL 1', 'is_active' => true]);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Scope', 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id, 'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id, 'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id, 'assigned_by' => $teacher->id,
        ]);

        return [$teacher, $student, $classroom];
    }

    /** @return array<string, mixed> */
    private function payload(Student $student): array
    {
        return [
            'student_id' => $student->id,
            'recorded_on' => '2026-09-26',
            'note' => 'Pertemuan dengan keluarga.',
        ];
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());
        return $user;
    }
}
