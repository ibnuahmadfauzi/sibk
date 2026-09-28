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

    public function test_teacher_records_one_note_and_changes_only_progress(): void
    {
        [$teacher, $student, $classroom] = $this->assignedStudent();
        $this->actingAs($teacher)->post(route('withdrawals.store'), $this->payload($student))
            ->assertRedirect(route('cases.index', ['tab' => 'pengunduran-diri']));

        $withdrawal = WithdrawalProgress::query()->firstOrFail();
        $this->assertSame($teacher->id, $withdrawal->teacher_id);
        $this->assertSame($classroom->id, $withdrawal->classroom_id);
        $this->assertSame('Permintaan dari keluarga.', $withdrawal->reason);
        $this->assertSame(WithdrawalProgress::PROGRESS_IN_PROGRESS, $withdrawal->progress);

        $this->patch(route('withdrawals.progress.update', $withdrawal), [
            'progress' => WithdrawalProgress::PROGRESS_AT_BK,
            'reason' => 'Tidak boleh diubah melalui dropdown',
        ])->assertRedirect();
        $this->assertSame(WithdrawalProgress::PROGRESS_AT_BK, $withdrawal->refresh()->progress);
        $this->assertSame('Permintaan dari keluarga.', $withdrawal->reason);
        $this->assertSame(2, AuditLog::query()->where('auditable_type', $withdrawal->getMorphClass())->count());
        $this->assertDatabaseCount('student_departures', 0);

        $this->post(route('withdrawals.store'), $this->payload($student))
            ->assertSessionHasErrors('student_id');
        $this->assertDatabaseCount('withdrawal_progresses', 1);
    }

    public function test_unassigned_teacher_and_other_roles_cannot_mutate(): void
    {
        [$teacher, $student] = $this->assignedStudent();
        $otherTeacher = $this->userWithRole('guru_bk');
        $coordinator = $this->userWithRole('koordinator_bk');
        $waka = $this->userWithRole('waka_kesiswaan');
        $admin = $this->userWithRole('admin_it');

        $this->actingAs($otherTeacher)->post(route('withdrawals.store'), $this->payload($student))
            ->assertSessionHasErrors('student_id');
        $this->actingAs($teacher)->post(route('withdrawals.store'), $this->payload($student))
            ->assertRedirect();
        $withdrawal = WithdrawalProgress::query()->firstOrFail();
        $otherTeacher->roles()->attach(Role::query()->where('slug', 'koordinator_bk')->firstOrFail());

        foreach ([$otherTeacher, $coordinator, $waka, $admin] as $user) {
            $this->actingAs($user)->patch(route('withdrawals.progress.update', $withdrawal), [
                'progress' => WithdrawalProgress::PROGRESS_AT_TU,
            ])->assertForbidden();
        }
        foreach ([$coordinator, $waka, $admin] as $user) {
            $this->actingAs($user)->post(route('withdrawals.store'), $this->payload($student))
                ->assertForbidden();
        }
        $this->assertSame(WithdrawalProgress::PROGRESS_IN_PROGRESS, $withdrawal->refresh()->progress);
        $this->assertTrue($coordinator->can('view', $withdrawal));
        $this->assertFalse($waka->can('view', $withdrawal));
        $this->assertFalse($admin->can('view', $withdrawal));
    }

    public function test_invalid_progress_and_future_date_are_rejected(): void
    {
        [$teacher, $student] = $this->assignedStudent();
        $this->actingAs($teacher)->post(route('withdrawals.store'), [
            ...$this->payload($student), 'recorded_on' => '2026-09-28', 'progress' => 'resmi_keluar',
        ])->assertSessionHasErrors(['recorded_on', 'progress']);
        $this->assertDatabaseCount('withdrawal_progresses', 0);
    }

    public function test_tab_filters_records_and_keeps_reason_inside_detail_for_bk_only(): void
    {
        [$teacher, $student] = $this->assignedStudent();
        $url = route('cases.index', ['tab' => 'pengunduran-diri']);
        $this->actingAs($teacher)->get($url)->assertOk()
            ->assertSee('id="withdrawal-student-lookup"', false)
            ->assertSee('list="withdrawal-student-options"', false)
            ->assertSee('name="student_id"', false)
            ->assertSee('Simpan')
            ->assertDontSee('id="modal-tambah-tindak-lanjut"', false);
        $this->actingAs($teacher)->post(route('withdrawals.store'), $this->payload($student))->assertRedirect();

        $this->get($url)->assertOk()
            ->assertSee('Catat Pengunduran Diri')
            ->assertSee('Nama Guru')->assertSee('Nama Siswa')->assertSee('Progres Penanganan')
            ->assertSee('data-withdrawal-progress', false)->assertSee('data-report-detail-toggle', false)
            ->assertSee($student->name)
            ->assertSee('Alasan pengunduran diri')
            ->assertSee('Permintaan dari keluarga.');
        $this->get($url.'&search=Tidak%20Ada')->assertOk()->assertDontSee('Permintaan dari keluarga.');
        $this->get($url.'&progress='.WithdrawalProgress::PROGRESS_AT_TU)->assertOk()
            ->assertDontSee('Permintaan dari keluarga.');

        $coordinator = $this->userWithRole('koordinator_bk');
        $this->actingAs($coordinator)->get($url)->assertOk()->assertSee($student->name)
            ->assertDontSee('name="student_id"', false);
        foreach (['waka_kesiswaan', 'admin_it'] as $role) {
            $this->actingAs($this->userWithRole($role))->get($url)->assertForbidden();
        }
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
            'progress' => WithdrawalProgress::PROGRESS_IN_PROGRESS,
            'note' => 'Pertemuan dengan keluarga.',
            'reason' => 'Permintaan dari keluarga.',
        ];
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
