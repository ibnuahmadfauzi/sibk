<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\EtatibDuplicateDecision;
use App\Models\EtatibIdentityMapping;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\IntegrationSetting;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\EtatibSyncService;
use App\Services\SimpleEtatibApiService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SimpleEtatibApiTest extends TestCase
{
    use RefreshDatabase;

    private const string URL = 'https://etatib.example.test/pelanggaran';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->app->instance(
            SimpleEtatibApiService::class,
            new SimpleEtatibApiService(
                app(EtatibSyncService::class),
                static fn (string $host): array => ['8.8.8.8'],
            ),
        );
    }

    public function test_preview_shows_counts_and_only_conflicting_identities_without_mutation(): void
    {
        Student::query()->create([
            'nisn' => '0093200788',
            'name' => 'FERRYSCHA PUTRI',
        ]);
        Http::fake([self::URL => Http::response($this->payload())]);

        $response = $this->actingAs($this->admin())->postJson(
            route('data-master.etatib.preview'),
            ['api_url' => self::URL],
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.rows', 2)
            ->assertJsonPath('data.students', 2)
            ->assertJsonPath('data.matched', 1)
            ->assertJsonPath('data.conflicts', 1)
            ->assertJsonPath('data.missing_students', 1)
            ->assertJsonPath('data.name_mismatches', 0)
            ->assertJsonPath('data.missing', 0)
            ->assertJsonPath('data.identity_conflicts.0.nisn', '0081784737')
            ->assertJsonPath('data.identity_conflicts.0.classroom', '11 TKJ 1')
            ->assertJsonMissingPath('data.fingerprint')
            ->assertJsonMissingPath('data.sample');
        $this->assertStringNotContainsString('0093200788', $response->getContent());
        $this->assertStringNotContainsString('FERRYSCHA PUTRI', $response->getContent());
        $this->assertDatabaseCount('external_tatib_records', 0);
        $this->assertDatabaseCount('external_sync_runs', 0);
    }

    public function test_sync_normalizes_short_nisn_and_persists_structured_fields(): void
    {
        $student = Student::query()->create([
            'nisn' => '0093200788',
            'name' => 'FERRYSCHA PUTRI',
        ]);
        Http::fake([self::URL => Http::sequence()
            ->push([$this->payload()[0]])
            ->push([$this->payload()[0]])]);

        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk();
        $this
            ->post(route('data-master.etatib.sync'), ['api_url' => self::URL])
            ->assertRedirect()
            ->assertSessionHas('success', 'Sinkronisasi e-Tatib berhasil.');

        $record = ExternalTatibRecord::query()->sole();
        $this->assertStringStartsWith('simple-', $record->source_identifier);
        $this->assertSame('0093200788', $record->nisn);
        $this->assertSame('93200788', $record->source_nisn);
        $this->assertSame($student->id, $record->student_id);
        $this->assertSame('12 PH 2', $record->source_classroom_name);
        $this->assertSame('ANIS', $record->recorded_by_name);
        $this->assertSame(10, $record->points);
        $this->assertSame(20, $record->source_total_points);
        $this->assertDatabaseHas('external_sync_runs', [
            'source' => 'etatib',
            'status' => ExternalSyncRun::STATUS_SUCCEEDED,
            'received_count' => 1,
            'processed_count' => 1,
        ]);
    }

    public function test_preview_remains_valid_after_ten_minutes_while_session_is_active(): void
    {
        Http::fake([self::URL => Http::sequence()
            ->push([$this->payload()[0]])
            ->push([$this->payload()[0]])]);

        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk();
        $this->travel(11)->minutes();

        $this->post(route('data-master.etatib.sync'), ['api_url' => self::URL])
            ->assertSessionHas('warning', 'Sinkronisasi e-Tatib selesai dengan 1 data yang perlu diperiksa.');
    }

    public function test_sync_without_preview_shows_error_toast_on_data_master(): void
    {
        Http::fake([self::URL => Http::response([$this->payload()[0]])]);

        $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('data-master.index', ['tab' => 'etatib']))
            ->post(route('data-master.etatib.sync'), ['api_url' => self::URL])
            ->assertOk()
            ->assertSee('sibk-notification-toast--error', false)
            ->assertSee('Sesi tinjauan e-Tatib berakhir. Tinjau data kembali sebelum sinkronisasi.');
    }

    public function test_preview_flags_same_nisn_with_different_name_and_shows_source_class(): void
    {
        Student::query()->create([
            'nisn' => '0093200788',
            'name' => 'Nama di Master',
        ]);
        Http::fake([self::URL => Http::response([$this->payload()[0]])]);

        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk()
            ->assertJsonPath('data.conflicts', 1)
            ->assertJsonPath('data.missing_students', 0)
            ->assertJsonPath('data.name_mismatches', 1)
            ->assertJsonPath('data.identity_conflicts.0.classroom', '12 PH 2')
            ->assertJsonPath('data.identity_conflicts.0.reason', 'Nama pada master: Nama di Master · Kelas master: -');
    }

    public function test_admin_can_choose_master_student_in_preview_and_sync_without_another_mapping_step(): void
    {
        $sameNisn = Student::query()->create(['nisn' => '0093200788', 'name' => 'NAMA LAIN']);
        $suggestion = Student::query()->create(['nisn' => '0012345678', 'name' => 'FERRYSCHA PUTRI']);
        Http::fake([self::URL => Http::sequence()
            ->push([$this->payload()[0]])
            ->push([$this->payload()[0]])]);

        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk()
            ->assertJsonPath('data.identity_conflicts.0.master.id', $sameNisn->id)
            ->assertJsonPath('data.identity_conflicts.0.master.nisn', '0093200788')
            ->assertJsonPath('data.identity_conflicts.0.suggestions.0.id', $suggestion->id)
            ->assertJsonPath('data.identity_conflicts.0.suggestions.0.nisn', '0012345678');
        $this->assertDatabaseCount('etatib_identity_mappings', 0);

        $this->post(route('data-master.etatib.sync'), [
            'api_url' => self::URL,
            'identity_decisions' => [[
                'nisn' => '0093200788',
                'name' => 'FERRYSCHA PUTRI',
                'student_id' => $suggestion->id,
            ]],
        ])->assertSessionHas('success');

        $this->assertSame($suggestion->id, ExternalTatibRecord::query()->sole()->student_id);
        $this->assertSame($suggestion->id, EtatibIdentityMapping::query()->sole()->student_id);
    }

    public function test_forged_preview_identity_choice_rejects_sync_without_mapping(): void
    {
        $student = Student::query()->create(['nisn' => '0093200788', 'name' => 'NAMA LAIN']);
        Http::fake([self::URL => Http::sequence()
            ->push([$this->payload()[0]])
            ->push([$this->payload()[0]])]);

        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk();
        $this->post(route('data-master.etatib.sync'), [
            'api_url' => self::URL,
            'identity_decisions' => [[
                'nisn' => '0093200788',
                'name' => 'NAMA YANG TIDAK ADA',
                'student_id' => $student->id,
            ]],
        ])->assertSessionHasErrors('etatib_sync');

        $this->assertDatabaseCount('etatib_identity_mappings', 0);
        $this->assertDatabaseCount('external_tatib_records', 0);
    }

    public function test_preview_shows_active_year_without_calendar_gate(): void
    {
        AcademicYear::query()->create([
            'name' => '2024/2025',
            'is_active' => true,
        ]);
        Http::fake([self::URL => Http::response($this->payload())]);

        $response = $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk();

        $response->assertJsonPath('data.active_year', '2024/2025')
            ->assertJsonMissingPath('data.roster_warning');
    }

    public function test_sync_rejects_changed_api_response_after_preview(): void
    {
        $changed = $this->payload();
        $changed[0]['poin_pelanggaran'] = 15;
        Http::fake([
            self::URL => Http::sequence()
                ->push($this->payload())
                ->push($changed),
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk();
        $this->post(route('data-master.etatib.sync'), ['api_url' => self::URL])
            ->assertRedirect()
            ->assertSessionHasErrors('etatib_sync');

        $this->assertDatabaseCount('external_tatib_records', 0);
        $this->assertDatabaseHas('external_sync_runs', ['status' => ExternalSyncRun::STATUS_FAILED]);
    }

    public function test_changing_current_timestamp_is_saved_without_date_or_duplicates_on_repeat_sync(): void
    {
        Student::query()->create(['nisn' => '0093200788', 'name' => 'FERRYSCHA PUTRI']);
        $sequence = Http::sequence();
        foreach (range(0, 7) as $seconds) {
            $payload = $this->payload();
            $payload[1]['tanggal_pelanggaran'] = now('Asia/Jakarta')->addSeconds($seconds)->format('d M Y H:i:s');
            $sequence->push($payload);
        }
        Http::fake([self::URL => $sequence]);

        $admin = $this->admin();
        foreach (range(1, 2) as $attempt) {
            $this->actingAs($admin)
                ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
                ->assertOk()
                ->assertJsonPath('data.rows', 2)
                ->assertJsonPath('data.undated', 1)
                ->assertJsonPath('data.missing', 0);
            $this->post(route('data-master.etatib.sync'), ['api_url' => self::URL])
                ->assertSessionHas('warning');

            $this->assertDatabaseCount('external_tatib_records', 2);
            $this->assertSame(1, ExternalTatibRecord::query()->whereNull('occurred_at')->count());
            $this->assertStringStartsWith('simple-undated-', ExternalTatibRecord::query()->whereNull('occurred_at')->sole()->source_identifier);
            $this->assertDatabaseHas('external_sync_runs', [
                'status' => ExternalSyncRun::STATUS_WARNING,
                'received_count' => 2,
                'processed_count' => 2,
                'is_full_snapshot' => true,
            ]);
        }
    }

    public function test_changing_current_timestamp_can_activate_automatic_sync_with_warning(): void
    {
        $sequence = Http::sequence();
        foreach (range(0, 3) as $seconds) {
            $payload = $this->payload();
            $payload[1]['tanggal_pelanggaran'] = now('Asia/Jakarta')->addSeconds($seconds)->format('d M Y H:i:s');
            $sequence->push($payload);
        }
        Http::fake([self::URL => $sequence]);

        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk()
            ->assertJsonPath('data.undated', 1);
        $this->post(route('data-master.etatib.automatic.store'), [
            'api_url' => self::URL,
            'current_password' => 'password',
        ])->assertSessionHas('warning');

        $this->assertDatabaseCount('external_tatib_records', 2);
        $this->assertTrue((bool) IntegrationSetting::query()
            ->where('provider', 'etatib')->value('automatic_sync_enabled'));
    }

    public function test_later_valid_date_updates_the_same_undated_record(): void
    {
        Student::query()->create(['nisn' => '0093200788', 'name' => 'FERRYSCHA PUTRI']);
        Student::query()->create(['nisn' => '0081784737', 'name' => 'Ridho Parulian Siagian']);
        $sequence = Http::sequence();
        foreach (range(0, 3) as $seconds) {
            $payload = $this->payload();
            $payload[1]['tanggal_pelanggaran'] = now('Asia/Jakarta')->addSeconds($seconds)->format('d M Y H:i:s');
            $sequence->push($payload);
        }
        $sequence->push($this->payload())->push($this->payload());
        Http::fake([self::URL => $sequence]);

        $admin = $this->admin();
        $this->actingAs($admin)->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])->assertOk();
        $this->post(route('data-master.etatib.sync'), ['api_url' => self::URL])->assertSessionHas('warning');
        $undatedId = ExternalTatibRecord::query()->whereNull('occurred_at')->sole()->id;

        $this->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk()
            ->assertJsonPath('data.undated', 0);
        $this->post(route('data-master.etatib.sync'), ['api_url' => self::URL])->assertSessionHas('success');

        $this->assertDatabaseCount('external_tatib_records', 2);
        $this->assertNotNull(ExternalTatibRecord::query()->findOrFail($undatedId)->occurred_at);
    }

    public function test_ambiguous_undated_violations_are_rejected(): void
    {
        $first = $this->payload();
        $first[1]['tanggal_pelanggaran'] = now('Asia/Jakarta')->format('d M Y H:i:s');
        $duplicate = $first[1];
        $duplicate['tanggal_pelanggaran'] = now('Asia/Jakarta')->subDay()->format('d M Y H:i:s');
        $first[] = $duplicate;
        $second = $first;
        $second[1]['tanggal_pelanggaran'] = now('Asia/Jakarta')->addSecond()->format('d M Y H:i:s');
        Http::fake([self::URL => Http::sequence()->push($first)->push($second)]);

        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('api_url');
        $this->assertDatabaseCount('external_tatib_records', 0);
    }

    public function test_other_api_changes_are_not_hidden_by_a_changing_timestamp(): void
    {
        $first = $this->payload();
        $first[1]['tanggal_pelanggaran'] = now('Asia/Jakarta')->format('d M Y H:i:s');
        $second = $first;
        $second[1]['tanggal_pelanggaran'] = now('Asia/Jakarta')->addSecond()->format('d M Y H:i:s');
        $second[0]['poin_pelanggaran'] = 15;
        Http::fake([self::URL => Http::sequence()->push($first)->push($second)]);

        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('api_url');

        $this->assertDatabaseCount('external_tatib_records', 0);
    }

    public function test_missing_previous_record_blocks_sync_and_preserves_local_data(): void
    {
        Http::fake([self::URL => Http::sequence()
            ->push($this->payload())
            ->push($this->payload())
            ->push([$this->payload()[0]])
            ->push([$this->payload()[0]])]);
        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk();
        $this->post(route('data-master.etatib.sync'), ['api_url' => self::URL])
            ->assertSessionHas('warning');

        $this->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk()
            ->assertJsonPath('data.missing', 1);
        $this->post(route('data-master.etatib.sync'), ['api_url' => self::URL])
            ->assertSessionHasErrors('etatib_sync');

        $this->assertSame(2, ExternalTatibRecord::query()->active()->count());
    }

    public function test_masked_nisn_is_rejected(): void
    {
        $masked = $this->payload()[0];
        $masked['siswa_nisn'] = '95****88';
        Http::fake([self::URL => Http::response([$masked])]);

        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('api_url');
    }

    public function test_identical_rows_require_approval(): void
    {
        Http::fake([self::URL => fn () => Http::response([$this->payload()[0], $this->payload()[0]])]);
        $preview = $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk()->assertJsonPath('data.received', 2)->assertJsonPath('data.rows', 1)
            ->assertJsonPath('data.duplicate_groups.0.rows', [1, 2])
            ->assertJsonPath('data.duplicate_groups.0.approved', false);

        $key = $preview->json('data.duplicate_groups.0.key');
        $this->post(route('data-master.etatib.sync'), ['api_url' => self::URL])
            ->assertSessionHasErrors('etatib_sync');
        $this->assertDatabaseCount('etatib_duplicate_decisions', 0);
        $this->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])->assertOk();
        $this->post(route('data-master.etatib.sync'), [
            'api_url' => self::URL, 'duplicate_decisions' => [$key],
        ])->assertSessionHas('warning');

        $this->assertDatabaseCount('external_tatib_records', 1);
        $this->assertDatabaseHas('etatib_duplicate_decisions', ['group_key' => $key, 'copy_count' => 2, 'is_active' => true]);
    }

    public function test_changed_duplicate_count_rejects_old_choice_and_existing_approval(): void
    {
        $row = $this->payload()[0];
        Http::fake([self::URL => Http::sequence()->push([$row, $row])->push([$row, $row, $row])]);
        $preview = $this->actingAs($this->admin())->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])->assertOk();
        $this->post(route('data-master.etatib.sync'), [
            'api_url' => self::URL, 'duplicate_decisions' => [$preview->json('data.duplicate_groups.0.key')],
        ])->assertSessionHasErrors('etatib_sync');
        $this->assertDatabaseCount('etatib_duplicate_decisions', 0);
        $this->assertDatabaseCount('external_tatib_records', 0);
    }

    public function test_forged_duplicate_key_and_later_sync_failure_leave_no_approval(): void
    {
        $row = $this->payload()[0];
        Http::fake([self::URL => fn () => Http::response([$row, $row])]);
        $admin = $this->admin();
        $student = Student::query()->create(['nisn' => '0093200788', 'name' => 'NAMA LAIN']);
        $preview = $this->actingAs($admin)->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])->assertOk();
        $key = $preview->json('data.duplicate_groups.0.key');

        $this->post(route('data-master.etatib.sync'), [
            'api_url' => self::URL, 'duplicate_decisions' => [str_repeat('a', 64)],
        ])->assertSessionHasErrors('etatib_sync');
        $this->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])->assertOk();
        $this->post(route('data-master.etatib.sync'), [
            'api_url' => self::URL, 'duplicate_decisions' => [$key],
            'identity_decisions' => [['nisn' => '0093200788', 'name' => 'TIDAK ADA', 'student_id' => $student->id]],
        ])->assertSessionHasErrors('etatib_sync');

        $this->assertDatabaseCount('etatib_duplicate_decisions', 0);
        $this->assertDatabaseCount('external_tatib_records', 0);
    }

    public function test_automatic_sync_reuses_only_unchanged_approved_group(): void
    {
        $row = $this->payload()[0];
        $payload = [$row, $row];
        Http::fake([self::URL => function () use (&$payload) {
            return Http::response($payload);
        }]);
        $admin = $this->admin();
        $preview = $this->actingAs($admin)->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])->assertOk();
        $this->post(route('data-master.etatib.automatic.store'), [
            'api_url' => self::URL, 'current_password' => 'password',
            'duplicate_decisions' => [$preview->json('data.duplicate_groups.0.key')],
        ])->assertSessionHas('warning');

        $this->post(route('data-master.etatib.automatic.sync'))->assertSessionHas('warning');
        $this->assertDatabaseCount('external_tatib_records', 1);
        $payload[] = $row;
        $this->post(route('data-master.etatib.automatic.sync'))->assertSessionHasErrors('etatib_automatic', null, 'etatib_automatic');
        $this->assertDatabaseCount('external_tatib_records', 1);
    }

    public function test_fingerprint_collision_with_different_source_fields_still_blocks_preview(): void
    {
        $row = $this->payload()[0];
        $different = $row;
        $different['kategori'] = 'berat';
        Http::fake([self::URL => Http::response([$row, $different])]);

        $this->actingAs($this->admin())->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertUnprocessable()->assertJsonValidationErrors('api_url');
        $this->assertDatabaseCount('etatib_duplicate_decisions', 0);
    }

    public function test_duplicate_row_numbers_stay_at_source_positions_when_another_date_changes(): void
    {
        $recent = $this->payload()[1];
        $recent['tanggal_pelanggaran'] = now('Asia/Jakarta')->format('d M Y H:i:s');
        $duplicate = $this->payload()[0];
        $first = [$recent, $duplicate, $duplicate];
        $second = $first;
        $second[0]['tanggal_pelanggaran'] = now('Asia/Jakarta')->addSecond()->format('d M Y H:i:s');
        Http::fake([self::URL => Http::sequence()->push($first)->push($second)]);

        $this->actingAs($this->admin())->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk()->assertJsonPath('data.duplicate_groups.0.rows', [2, 3]);
    }

    public function test_approved_duplicate_is_reused_until_admin_revokes_it(): void
    {
        $row = $this->payload()[0];
        Http::fake([self::URL => fn () => Http::response([$row, $row])]);
        $admin = $this->admin();
        $preview = $this->actingAs($admin)->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])->assertOk();
        $this->post(route('data-master.etatib.sync'), [
            'api_url' => self::URL, 'duplicate_decisions' => [$preview->json('data.duplicate_groups.0.key')],
        ])->assertSessionHas('warning');
        $decision = EtatibDuplicateDecision::query()->sole();

        $this->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk()->assertJsonPath('data.duplicate_groups.0.approved', true)
            ->assertJsonPath('data.duplicate_groups.0.decision_id', $decision->id);
        $this->post(route('data-master.etatib.sync'), ['api_url' => self::URL])->assertSessionHas('warning');
        $this->assertDatabaseCount('external_tatib_records', 1);

        $this->delete(route('data-master.etatib.duplicates.destroy', $decision))->assertSessionHas('success');
        $this->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk()->assertJsonPath('data.duplicate_groups.0.approved', false);
        $this->post(route('data-master.etatib.sync'), ['api_url' => self::URL])->assertSessionHasErrors('etatib_sync');
        $this->assertDatabaseCount('external_tatib_records', 1);
    }

    public function test_etatib_api_routes_are_limited_to_admin_it(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('slug', 'guru_bk')->firstOrFail());

        $this->post(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertRedirect(route('login'));
        $this->actingAs($teacher)
            ->post(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertForbidden();
        $this->actingAs($teacher)
            ->post(route('data-master.etatib.sync'), ['api_url' => self::URL])
            ->assertForbidden();
        $decision = EtatibDuplicateDecision::query()->create([
            'url_hash' => hash('sha256', self::URL), 'group_key' => str_repeat('a', 64),
            'source_nisn' => '0093200788', 'source_name' => 'FERRYSCHA PUTRI',
            'copy_count' => 2, 'is_active' => true, 'approved_at' => now(),
        ]);
        $this->delete(route('data-master.etatib.duplicates.destroy', $decision))->assertForbidden();
        $this->assertTrue($decision->fresh()->is_active);
    }

    /** @return list<array<string, int|string>> */
    private function payload(): array
    {
        return [
            [
                'siswa_nisn' => '93200788',
                'siswa_nama' => 'FERRYSCHA PUTRI',
                'siswa_kelas' => '12 PH 2',
                'pelanggaran' => 'Datang Terlambat',
                'poin_pelanggaran' => 10,
                'pencatat' => 'ANIS',
                'kategori' => 'ringan',
                'tanggal_pelanggaran' => '20 Jul 2026 10:17:00',
                'total_poin' => '20',
            ],
            [
                'siswa_nisn' => '0081784737',
                'siswa_nama' => 'Ridho Parulian Siagian',
                'siswa_kelas' => '11 TKJ 1',
                'pelanggaran' => 'Atribut seragam tidak lengkap',
                'poin_pelanggaran' => 10,
                'pencatat' => 'Aminatus Syaadah',
                'kategori' => 'ringan',
                'tanggal_pelanggaran' => '29 Jul 2026 10:17:00',
                'total_poin' => '50',
            ],
        ];
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin_it')->firstOrFail());

        return $admin;
    }
}
