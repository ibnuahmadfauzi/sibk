<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\IntegrationSetting;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\EtatibSyncService;
use App\Services\SimpleEtatibApiService;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class EtatibAutomaticSyncTest extends TestCase
{
    use RefreshDatabase;

    private const string URL = 'https://etatib.example.test/pelanggaran?school=smkn1';

    private const string REPLACEMENT_URL = 'https://etatib-baru.example.test/pelanggaran';

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

    public function test_admin_can_activate_encrypted_automatic_sync_after_successful_sync(): void
    {
        $admin = $this->admin();
        Student::query()->create([
            'nisn' => '0093200788',
            'name' => 'FERRYSCHA PUTRI',
        ]);
        Http::fake([self::URL => Http::sequence()
            ->push($this->payload())
            ->push($this->payload())]);

        $this->actingAs($admin)
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertOk();
        $response = $this->actingAs($admin)->post(route('data-master.etatib.automatic.store'), [
            'api_url' => self::URL,
            'current_password' => 'password',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success');

        $setting = IntegrationSetting::query()
            ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
            ->sole();
        $this->assertTrue($setting->automatic_sync_enabled);
        $this->assertSame(self::URL, $setting->automatic_sync_url);
        $this->assertSame($admin->id, $setting->automatic_sync_updated_by);
        $this->assertNotNull($setting->automatic_sync_enabled_at);
        $this->assertNotSame(self::URL, $setting->getRawOriginal('automatic_sync_url'));
        $this->assertStringNotContainsString(
            self::URL,
            (string) $setting->getRawOriginal('automatic_sync_url'),
        );
        $this->assertDatabaseHas('external_sync_runs', [
            'source' => IntegrationSetting::PROVIDER_ETATIB,
            'status' => ExternalSyncRun::STATUS_SUCCEEDED,
            'triggered_by' => $admin->id,
        ]);
        $this->assertStringNotContainsString(
            self::URL,
            AuditLog::query()->get()->toJson(),
        );

        $this->actingAs($admin)
            ->get(route('data-master.index', ['tab' => 'etatib']))
            ->assertOk()
            ->assertSee('Aktif: Senin-Jumat, 15.00 WIB')
            ->assertSee('Tinjau Data')
            ->assertDontSee(self::URL);
    }

    public function test_invalid_password_or_private_url_cannot_activate_automatic_sync(): void
    {
        $admin = $this->admin();
        Http::preventStrayRequests();

        $this->actingAs($admin)
            ->from(route('data-master.index'))
            ->post(route('data-master.etatib.automatic.store'), [
                'api_url' => self::URL,
                'current_password' => 'salah',
            ])
            ->assertRedirect(route('data-master.index'))
            ->assertSessionHasErrors('current_password', null, 'etatib_automatic');

        $this->actingAs($admin)
            ->from(route('data-master.index'))
            ->post(route('data-master.etatib.automatic.store'), [
                'api_url' => 'http://127.0.0.1/etatib',
                'current_password' => 'password',
            ])
            ->assertRedirect(route('data-master.index'))
            ->assertSessionHasErrors('etatib_automatic', null, 'etatib_automatic');

        $setting = IntegrationSetting::query()
            ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
            ->sole();
        $this->assertFalse($setting->automatic_sync_enabled);
        $this->assertNull($setting->getRawOriginal('automatic_sync_url'));
        $this->assertDatabaseHas('external_sync_runs', [
            'source' => IntegrationSetting::PROVIDER_ETATIB,
            'status' => ExternalSyncRun::STATUS_FAILED,
            'triggered_by' => $admin->id,
        ]);
    }

    public function test_scheduled_command_uses_stored_url_and_marks_run_as_automatic(): void
    {
        Student::query()->create([
            'nisn' => '0093200788',
            'name' => 'FERRYSCHA PUTRI',
        ]);
        $this->automaticSetting();
        Http::fake([self::URL => Http::response($this->payload())]);

        $this->artisan('sibk:sync-etatib')->assertSuccessful();

        $run = ExternalSyncRun::query()->latest('id')->sole();
        $this->assertSame(ExternalSyncRun::STATUS_SUCCEEDED, $run->status);
        $this->assertNull($run->triggered_by);
        $this->assertDatabaseHas('external_tatib_records', [
            'nisn' => '0093200788',
            'student_id' => Student::query()->sole()->id,
        ]);
        Http::assertSentCount(1);
    }

    public function test_command_skips_without_http_when_automatic_sync_is_inactive(): void
    {
        Http::preventStrayRequests();

        $this->artisan('sibk:sync-etatib')
            ->expectsOutputToContain('koneksi e-Tatib tidak aktif')
            ->assertSuccessful();

        $this->assertDatabaseCount('external_sync_runs', 0);
        Http::assertNothingSent();
    }

    public function test_scheduled_sync_keeps_active_records_when_api_omits_one(): void
    {
        $this->automaticSetting();
        ExternalTatibRecord::query()->create([
            'source_identifier' => 'existing-record',
            'nisn' => '0093200788',
            'source_nisn' => '93200788',
            'source_student_name' => 'FERRYSCHA PUTRI',
            'source_classroom_name' => '12 PH 2',
            'occurred_at' => now()->subDay(),
            'violation_type' => 'Data lama',
            'category' => 'ringan',
            'points' => 5,
            'source_status' => 'active',
            'is_active' => true,
            'synced_at' => now()->subDay(),
        ]);
        Http::fake([self::URL => Http::response($this->payload())]);

        $this->artisan('sibk:sync-etatib')->assertFailed();

        $this->assertDatabaseHas('external_tatib_records', [
            'source_identifier' => 'existing-record',
            'is_active' => true,
        ]);
        $this->assertDatabaseCount('external_tatib_records', 1);
    }

    public function test_admin_can_sync_now_and_replace_the_stored_url_after_revalidation(): void
    {
        $admin = $this->admin();
        Student::query()->create([
            'nisn' => '0093200788',
            'name' => 'FERRYSCHA PUTRI',
        ]);
        $this->automaticSetting($admin);
        Http::fake([
            self::URL => Http::response($this->payload()),
            self::REPLACEMENT_URL => Http::sequence()
                ->push($this->payload())
                ->push($this->payload()),
        ]);

        $this->actingAs($admin)
            ->post(route('data-master.etatib.automatic.sync'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('external_sync_runs', [
            'source' => IntegrationSetting::PROVIDER_ETATIB,
            'status' => ExternalSyncRun::STATUS_SUCCEEDED,
            'triggered_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::REPLACEMENT_URL])
            ->assertOk();
        $this
            ->post(route('data-master.etatib.automatic.store'), [
                'api_url' => self::REPLACEMENT_URL,
                'current_password' => 'password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $setting = IntegrationSetting::query()->sole();
        $this->assertSame(self::REPLACEMENT_URL, $setting->automatic_sync_url);
        $this->assertStringNotContainsString(
            self::REPLACEMENT_URL,
            AuditLog::query()->get()->toJson(),
        );
        Http::assertSentCount(3);
    }

    public function test_automatic_failure_keeps_old_data_and_is_visible_on_admin_dashboard(): void
    {
        $admin = $this->admin();
        $this->automaticSetting();
        ExternalTatibRecord::query()->create([
            'source_identifier' => 'existing-record',
            'nisn' => '0093200788',
            'source_nisn' => '93200788',
            'source_student_name' => 'FERRYSCHA PUTRI',
            'source_classroom_name' => '12 PH 2',
            'occurred_at' => now()->subDay(),
            'violation_type' => 'Data lama',
            'category' => 'ringan',
            'points' => 5,
            'source_status' => 'active',
            'is_active' => true,
            'synced_at' => now()->subDay(),
        ]);
        Http::fake([self::URL => Http::response('bukan json', 200)]);

        $this->artisan('sibk:sync-etatib')->assertFailed();

        $this->assertDatabaseHas('external_tatib_records', [
            'source_identifier' => 'existing-record',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('external_sync_runs', [
            'source' => IntegrationSetting::PROVIDER_ETATIB,
            'status' => ExternalSyncRun::STATUS_FAILED,
            'triggered_by' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.preview'))
            ->assertOk()
            ->assertSee('Pembaruan otomatis e-Tatib gagal')
            ->assertDontSee(self::URL);
    }

    public function test_admin_can_disable_and_delete_the_stored_url_with_password(): void
    {
        $admin = $this->admin();
        $this->automaticSetting($admin);

        $this->actingAs($admin)
            ->from(route('data-master.index'))
            ->delete(route('data-master.etatib.automatic.destroy'), [
                'current_password' => 'salah',
            ])
            ->assertSessionHasErrors('current_password', null, 'etatib_automatic');

        $this->assertTrue(IntegrationSetting::query()->sole()->automatic_sync_enabled);

        $this->actingAs($admin)
            ->delete(route('data-master.etatib.automatic.destroy'), [
                'current_password' => 'password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $setting = IntegrationSetting::query()->sole();
        $this->assertFalse($setting->automatic_sync_enabled);
        $this->assertNull($setting->getRawOriginal('automatic_sync_url'));
        $this->assertStringNotContainsString(
            self::URL,
            AuditLog::query()->get()->toJson(),
        );
    }

    public function test_non_admin_cannot_manage_automatic_sync(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('slug', 'guru_bk')->firstOrFail());

        $this->actingAs($teacher)
            ->post(route('data-master.etatib.automatic.store'), [
                'api_url' => self::URL,
                'current_password' => 'password',
            ])
            ->assertForbidden();
        $this->actingAs($teacher)
            ->post(route('data-master.etatib.automatic.sync'))
            ->assertForbidden();
        $this->actingAs($teacher)
            ->delete(route('data-master.etatib.automatic.destroy'), [
                'current_password' => 'password',
            ])
            ->assertForbidden();
    }

    public function test_scheduler_runs_at_three_pm_jakarta_on_weekdays_with_overlap_guards(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains($event->command ?? '', 'sibk:sync-etatib'));

        $this->assertNotNull($event);
        $this->assertSame('0 15 * * 1-5', $event->expression);
        $this->assertSame('Asia/Jakarta', $event->timezone);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertTrue($event->onOneServer);
    }

    private function automaticSetting(?User $actor = null): IntegrationSetting
    {
        return IntegrationSetting::query()->create([
            'provider' => IntegrationSetting::PROVIDER_ETATIB,
            'automatic_sync_url' => self::URL,
            'automatic_sync_enabled' => true,
            'automatic_sync_enabled_at' => now(),
            'automatic_sync_updated_by' => $actor?->id,
        ]);
    }

    /** @return list<array<string, int|string>> */
    private function payload(): array
    {
        return [[
            'siswa_nisn' => '93200788',
            'siswa_nama' => 'FERRYSCHA PUTRI',
            'siswa_kelas' => '12 PH 2',
            'pelanggaran' => 'Datang Terlambat',
            'poin_pelanggaran' => 10,
            'pencatat' => 'ANIS',
            'kategori' => 'ringan',
            'tanggal_pelanggaran' => '20 Jul 2026 10:17:00',
            'total_poin' => '20',
        ]];
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin_it')->firstOrFail());

        return $admin;
    }
}
