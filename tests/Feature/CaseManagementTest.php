<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\ExternalTatibRecord;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Support\ServiceRecordStatus;
use Carbon\Carbon;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_etatib_case_requires_local_record_and_starts_in_progress(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $student = $this->scopedStudent($teacher);

        $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload('e_tatib'),
            'student_id' => $student->id,
        ])->assertSessionHasErrors('etatib_record_ids');

        $record = $this->etatibRecord($student);
        $response = $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload('e_tatib'),
            'student_id' => $student->id,
            'etatib_record_ids' => [$record->id],
        ]);

        $case = BkCase::query()->firstOrFail();
        $response->assertRedirect(route('cases.show', $case));
        $this->assertSame(ServiceRecordStatus::IN_PROGRESS, $case->status->code);
        $this->assertDatabaseHas('case_etatib_links', [
            'case_id' => $case->id,
            'external_tatib_record_id' => $record->id,
        ]);
    }

    public function test_manual_exact_nisn_uses_official_student_without_overwriting_name(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $student = $this->scopedStudent($teacher, 'Nama Resmi', '0012345678');

        $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload(),
            'temporary_nisn' => $student->nisn,
            'temporary_name' => 'Nama Ketikan Berbeda',
        ])->assertRedirect();

        $case = BkCase::query()->firstOrFail();
        $this->assertSame($student->id, $case->student_id);
        $this->assertNull($case->temporary_student_id);
        $this->assertSame('Nama Resmi', $student->refresh()->name);
    }

    public function test_manual_unknown_nisn_creates_temporary_identity(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $this->scopedStudent($teacher);

        $this->actingAs($teacher)->post(route('cases.store'), [
            ...$this->casePayload(),
            'temporary_nisn' => '0099999999',
            'temporary_name' => 'Murid Belum Sinkron',
        ])->assertRedirect();

        $case = BkCase::query()->firstOrFail();
        $this->assertNull($case->student_id);
        $this->assertSame('Murid Belum Sinkron', $case->temporaryStudent?->input_name);
    }

    public function test_complete_requires_summary_and_uses_server_date(): void
    {
        Carbon::setTestNow('2026-09-17 08:30:00');
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));

        $this->actingAs($teacher)->patch(route('cases.update', $case), [
            ...$this->updatePayload($case),
            'action' => 'complete',
            'resolution_summary' => '',
        ])->assertSessionHasErrors('resolution_summary');

        $this->actingAs($teacher)->patch(route('cases.update', $case), [
            ...$this->updatePayload($case),
            'action' => 'complete',
            'resolution_summary' => 'Murid menyepakati langkah penyelesaian.',
            'closed_at' => '1999-01-01',
        ])->assertRedirect(route('cases.show', $case));

        $case->refresh();
        $this->assertSame(ServiceRecordStatus::COMPLETED, $case->status->code);
        $this->assertSame('2026-09-17', $case->closed_at?->toDateString());
    }

    public function test_completed_case_can_save_narratives_and_audit_only_changed_fields(): void
    {
        Carbon::setTestNow('2026-09-17 08:30:00');
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $this->completeCase($teacher, $case);
        $closedAt = $case->refresh()->closed_at?->toDateString();
        $expectedUpdatedAt = $case->updated_at->toJSON();
        Carbon::setTestNow('2026-09-17 08:31:00');

        $this->actingAs($teacher)->patch(route('cases.update', $case), [
            'initial_info' => $case->initial_info,
            'initial_action' => 'Penanganan diperjelas setelah kasus selesai.',
            'resolution_summary' => $case->resolution_summary,
            'action' => 'save',
            'expected_updated_at' => $expectedUpdatedAt,
        ])->assertRedirect(route('cases.show', $case));

        $case->refresh();
        $this->assertSame(ServiceRecordStatus::COMPLETED, $case->status->code);
        $this->assertSame($closedAt, $case->closed_at?->toDateString());
        $audit = AuditLog::query()->where('action', 'case.updated')->latest('id')->firstOrFail();
        $this->assertSame(['initial_action' => 'Asesmen awal.'], $audit->before_values);
        $this->assertSame(['initial_action' => 'Penanganan diperjelas setelah kasus selesai.'], $audit->after_values);
    }

    public function test_stale_case_update_is_rejected(): void
    {
        Carbon::setTestNow('2026-09-17 08:30:00');
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $staleTimestamp = $case->updated_at->toJSON();
        Carbon::setTestNow('2026-09-17 08:31:00');
        $case->update(['initial_info' => 'Perubahan dari tab lain.']);

        $this->actingAs($teacher)->patchJson(route('cases.update', $case), [
            ...$this->updatePayload($case),
            'initial_info' => 'Perubahan yang menimpa.',
            'expected_updated_at' => $staleTimestamp,
        ])->assertUnprocessable()->assertJsonValidationErrors('expected_updated_at');

        $this->assertSame('Perubahan dari tab lain.', $case->refresh()->initial_info);
    }

    public function test_follow_up_can_be_set_replaced_and_cleared(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $homeVisit = $this->reference('follow_up_type', 'home_visit');
        $statement = $this->reference('follow_up_type', 'surat_pernyataan');

        $response = $this->actingAs($teacher)->patchJson($this->followUpUrl($case), [
            'follow_up_type_id' => $homeVisit->id,
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertOk()->assertJsonPath('data.status_code', ServiceRecordStatus::NEEDS_FOLLOW_UP)
            ->assertJsonPath('data.follow_up_type_label', 'Home Visit');

        $response = $this->actingAs($teacher)->patchJson($this->followUpUrl($case), [
            'follow_up_type_id' => $statement->id,
            'expected_updated_at' => $response->json('data.updated_at'),
        ])->assertOk()->assertJsonPath('data.follow_up_type_id', $statement->id);

        $this->actingAs($teacher)->patchJson($this->followUpUrl($case), [
            'follow_up_type_id' => null,
            'expected_updated_at' => $response->json('data.updated_at'),
        ])->assertOk()->assertJsonPath('data.status_code', ServiceRecordStatus::IN_PROGRESS)
            ->assertJsonPath('data.follow_up_type_id', null);

        $case->refresh();
        $this->assertNull($case->follow_up_type_id);
        $this->assertSame(ServiceRecordStatus::IN_PROGRESS, $case->status->code);
    }

    public function test_follow_up_rejects_completed_case_forbidden_role_and_inactive_reference(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $homeVisit = $this->reference('follow_up_type', 'home_visit');
        $inactive = $this->reference('follow_up_type', 'surat_pernyataan');
        $inactive->update(['is_active' => false]);

        $this->actingAs($teacher)->patchJson($this->followUpUrl($case), [
            'follow_up_type_id' => $inactive->id,
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertUnprocessable()->assertJsonValidationErrors('follow_up_type_id');

        foreach (['koordinator_bk', 'waka_kesiswaan', 'admin_it'] as $role) {
            $this->actingAs($this->userWithRole($role))->patchJson($this->followUpUrl($case), [
                'follow_up_type_id' => $homeVisit->id,
                'expected_updated_at' => $case->updated_at->toJSON(),
            ])->assertForbidden();
        }

        $this->completeCase($teacher, $case->refresh());
        $this->actingAs($teacher)->patchJson($this->followUpUrl($case), [
            'follow_up_type_id' => $homeVisit->id,
            'expected_updated_at' => $case->refresh()->updated_at->toJSON(),
        ])->assertUnprocessable()->assertJsonValidationErrors('case');
    }

    public function test_case_list_has_final_columns_and_progressive_modal_contract(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));

        $response = $this->actingAs($teacher)->get(route('cases.index'));

        $response->assertOk()
            ->assertSeeInOrder(['Murid', 'Kelas', 'Tanggal', 'Sumber', 'Bidang', 'Status', 'Tindak Lanjut', 'Aksi'])
            ->assertSee('<tr data-modal-url="'.route('cases.show', [$case, 'modal' => 1]).'">', false)
            ->assertSee('data-modal-url="'.route('cases.show', [$case, 'modal' => 1]).'"', false)
            ->assertSee('data-follow-up-url="'.$this->followUpUrl($case).'"', false)
            ->assertSee('data-save-status', false)
            ->assertSee('aria-labelledby="case-modal-title"', false)
            ->assertSee('modal-dialog-scrollable', false)
            ->assertSee('aria-label="Tutup"', false)
            ->assertDontSee("/cases/{$case->id}/follow-ups/create", false)
            ->assertDontSee("/cases/{$case->id}/resolve", false);
        $this->assertSame(1, substr_count($response->getContent(), 'id="case-modal"'));
    }

    public function test_case_search_matches_name_only_and_status_filter_is_applied(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $alpha = $this->createCase($teacher, $this->scopedStudent($teacher, 'Alpha Murid', '0011111111'));
        $beta = $this->createCase($teacher, $this->scopedStudent($teacher, 'Beta Murid', '0022222222'));
        $beta->update(['status_id' => $this->reference('case_status', ServiceRecordStatus::NEEDS_FOLLOW_UP)->id]);

        $this->actingAs($teacher)->get(route('cases.index', ['search' => 'Alpha']))
            ->assertSee('Alpha Murid')->assertDontSee('Beta Murid');
        $this->actingAs($teacher)->get(route('cases.index', ['search' => '0011111111']))
            ->assertDontSee('Alpha Murid');
        $this->actingAs($teacher)->get(route('cases.index', ['status_id' => $beta->status_id]))
            ->assertSee('Beta Murid')->assertDontSee('Alpha Murid');
        $this->assertNotSame($alpha->id, $beta->id);
    }

    public function test_case_sorting_uses_allowlist_and_invalid_sort_falls_back_to_date(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $alpha = $this->createCase($teacher, $this->scopedStudent($teacher, 'Alpha', '0011111111', 'Z Kelas'), ['service_date' => '2026-08-02']);
        $beta = $this->createCase($teacher, $this->scopedStudent($teacher, 'Beta', '0022222222', 'X Kelas'), ['service_date' => '2026-08-01']);
        $charlie = $this->createCase($teacher, $this->scopedStudent($teacher, 'Charlie', '0033333333', 'Y Kelas'), ['service_date' => '2026-08-03']);

        $alpha->update([
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::COMPLETED)->id,
        ]);
        $beta->update([
            'case_source_id' => $this->reference('case_source', 'e_tatib')->id,
            'service_field_id' => $this->reference('service_field', 'sosial')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::IN_PROGRESS)->id,
        ]);
        $charlie->update([
            'case_source_id' => $this->reference('case_source', 'rujukan')->id,
            'service_field_id' => $this->reference('service_field', 'belajar')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::NEEDS_FOLLOW_UP)->id,
        ]);

        foreach ([
            'nama' => ['Alpha', 'Beta', 'Charlie'],
            'kelas' => ['Beta', 'Charlie', 'Alpha'],
            'tanggal' => ['Beta', 'Alpha', 'Charlie'],
            'sumber' => ['Beta', 'Charlie', 'Alpha'],
            'bidang' => ['Charlie', 'Alpha', 'Beta'],
            'status' => ['Beta', 'Alpha', 'Charlie'],
        ] as $sort => $expected) {
            $this->actingAs($teacher)->get(route('cases.index', ['sort' => $sort, 'direction' => 'asc']))
                ->assertOk()->assertSeeInOrder($expected);
        }

        $this->actingAs($teacher)->get(route('cases.index', ['sort' => 'status;drop table cases', 'direction' => 'sideways']))
            ->assertOk()->assertSeeInOrder(['Charlie', 'Alpha', 'Beta']);
    }

    public function test_case_sorting_uses_id_as_tie_breaker_in_same_direction(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $first = $this->createCase($teacher, $this->scopedStudent($teacher, 'Pertama', '0011111111'), ['service_date' => '2026-08-01']);
        $second = $this->createCase($teacher, $this->scopedStudent($teacher, 'Kedua', '0022222222'), ['service_date' => '2026-08-01']);

        $this->actingAs($teacher)->get(route('cases.index', ['sort' => 'tanggal', 'direction' => 'asc']))
            ->assertSeeInOrder([$first->identityName(), $second->identityName()]);
        $this->actingAs($teacher)->get(route('cases.index', ['sort' => 'tanggal', 'direction' => 'desc']))
            ->assertSeeInOrder([$second->identityName(), $first->identityName()]);
    }

    public function test_detail_and_edit_support_modal_and_full_page_fallbacks(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));

        $this->actingAs($teacher)->get(route('cases.show', $case))
            ->assertOk()->assertViewIs('pages.cases.show')->assertSee('Detail Kasus');
        $this->actingAs($teacher)->get(route('cases.show', [$case, 'modal' => 1]))
            ->assertOk()->assertViewIs('pages.cases._detail-modal')
            ->assertSeeInOrder(['Nama', 'Tanggal', 'Kelas', 'Status', 'Guru BK', 'Latar Belakang', 'Penanganan', 'Catatan Penyelesaian']);
        $this->actingAs($teacher)->get(route('cases.edit', $case))
            ->assertOk()->assertViewIs('pages.cases.edit')->assertSee('Ubah Kasus');
        $this->actingAs($teacher)->get(route('cases.edit', [$case, 'modal' => 1]))
            ->assertOk()->assertViewIs('pages.cases._edit-modal')
            ->assertSee('name="expected_updated_at"', false)
            ->assertSee('name="action" value="save"', false)
            ->assertSee('name="action" value="complete"', false)
            ->assertDontSee('name="change_reason"', false)
            ->assertDontSee('name="waka_summary"', false);
    }

    public function test_completed_case_edit_trigger_requires_confirmation_and_waka_cannot_mutate(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher));
        $this->completeCase($teacher, $case);
        $confirmation = "if (! window.confirm('Kasus ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?')) { event.stopImmediatePropagation(); return false; }";

        $this->actingAs($teacher)->get(route('cases.index'))
            ->assertSee('data-confirm-message="Kasus ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?"', false)
            ->assertSee('onclick="'.$confirmation.'"', false);
        $this->actingAs($teacher)->get(route('cases.show', $case))
            ->assertSee('onclick="'.$confirmation.'"', false);

        $nonOwner = $this->userWithRole('guru_bk');
        $this->actingAs($nonOwner)->delete(route('cases.destroy', $case))->assertForbidden();
        $waka = $this->userWithRole('waka_kesiswaan');
        $this->actingAs($waka)->patchJson(route('cases.update', $case), $this->updatePayload($case))
            ->assertForbidden();
        $this->actingAs($waka)->patchJson($this->followUpUrl($case), [
            'follow_up_type_id' => null,
            'expected_updated_at' => $case->updated_at->toJSON(),
        ])->assertForbidden();
        $this->actingAs($waka)->delete(route('cases.destroy', $case))->assertForbidden();
    }

    public function test_owner_can_archive_case_and_remove_it_from_operational_views(): void
    {
        $teacher = $this->userWithRole('guru_bk');
        $case = $this->createCase($teacher, $this->scopedStudent($teacher, 'Murid Diarsipkan'));

        $this->actingAs($teacher)->delete(route('cases.destroy', $case))
            ->assertRedirect(route('cases.index'));

        $this->assertSoftDeleted('cases', ['id' => $case->id]);
        $this->actingAs($teacher)->get(route('cases.index'))->assertDontSee('Murid Diarsipkan');
        $this->actingAs($teacher)->get(route('cases.show', $case))->assertNotFound();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'case.archived',
            'auditable_type' => BkCase::class,
            'auditable_id' => $case->id,
        ]);
    }

    private function completeCase(User $teacher, BkCase $case): void
    {
        $case->update([
            'status_id' => $this->reference('case_status', ServiceRecordStatus::COMPLETED)->id,
            'resolution_summary' => 'Kasus selesai setelah penanganan.',
            'closed_at' => today()->toDateString(),
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function createCase(User $teacher, Student $student, array $overrides = []): BkCase
    {
        $case = BkCase::query()->create([
            'registration_number' => 'K-2026-'.str_pad((string) (BkCase::query()->count() + 1), 4, '0', STR_PAD_LEFT),
            'student_id' => $student->id,
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'status_id' => $this->reference('case_status', ServiceRecordStatus::IN_PROGRESS)->id,
            'service_date' => '2026-08-01',
            'initial_info' => 'Informasi awal layanan.',
            'initial_action' => 'Asesmen awal.',
            'created_by' => $teacher->id,
            ...$overrides,
        ]);
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $teacher->id,
            'assignment_type' => CaseAssignment::TYPE_OWNER,
            'effective_from' => $case->service_date->toDateString(),
            'reason' => 'Fixture pemilik kasus.',
            'assigned_by' => $teacher->id,
        ]);

        return $case;
    }

    private function followUpUrl(BkCase $case): string
    {
        return url('/cases/'.$case->id.'/follow-up');
    }

    private function scopedStudent(
        User $teacher,
        string $name = 'Murid Scope',
        string $nisn = '0012345678',
        string $className = 'X RPL 1',
    ): Student {
        $year = AcademicYear::query()->firstOrCreate(
            ['name' => '2026/2027'],
            ['starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true],
        );
        $classroom = Classroom::query()->firstOrCreate(
            ['academic_year_id' => $year->id, 'name' => $className],
            ['is_active' => true],
        );
        $student = Student::query()->create(['nisn' => $nisn, 'name' => $name, 'is_active' => true]);
        StudentClassMembership::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-15',
            'is_active' => true,
        ]);
        TeacherAssignment::query()->firstOrCreate([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => '2026-07-15',
        ], [
            'decision_number' => 'SK-'.$classroom->id,
            'assigned_by' => $teacher->id,
        ]);

        return $student;
    }

    private function etatibRecord(Student $student): ExternalTatibRecord
    {
        return ExternalTatibRecord::query()->create([
            'source_identifier' => 'ET-'.$student->id,
            'nisn' => $student->nisn,
            'student_id' => $student->id,
            'occurred_at' => '2026-08-18 09:00:00',
            'violation_type' => 'Terlambat',
            'category' => 'Kedisiplinan',
            'points' => 5,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function casePayload(string $sourceCode = 'temuan_guru_bk'): array
    {
        return [
            'case_source_id' => $this->reference('case_source', $sourceCode)->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => '2026-08-01',
            'initial_info' => 'Informasi awal layanan.',
            'initial_action' => 'Asesmen awal.',
        ];
    }

    /** @return array<string, mixed> */
    private function updatePayload(BkCase $case): array
    {
        return [
            'initial_info' => $case->initial_info,
            'initial_action' => $case->initial_action,
            'resolution_summary' => $case->resolution_summary,
            'action' => 'save',
            'expected_updated_at' => $case->updated_at->toJSON(),
        ];
    }

    private function reference(string $category, string $code): ReferenceValue
    {
        return ReferenceValue::query()->where('category', $category)->where('code', $code)->firstOrFail();
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
