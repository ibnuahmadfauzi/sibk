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
use App\Services\EtatibAutomaticSyncService;
use App\Services\EtatibSyncService;
use App\Services\AlumniRetentionRegistry;
use App\Services\SimpleEtatibApiService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SavedApiUrlTest extends TestCase
{
    use RefreshDatabase;

    private const string ROSTER_URL = 'https://8.8.8.8/siswa?token=rahasia-dapodik';

    private const string ETATIB_URL = 'https://etatib.example.test/pelanggaran?token=rahasia-etatib';

    private const string ETATIB_NEW_URL = 'https://etatib-baru.example.test/pelanggaran?token=baru';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->app->instance(SimpleEtatibApiService::class, new SimpleEtatibApiService(
            app(EtatibSyncService::class),
            app(AlumniRetentionRegistry::class),
            static fn (string $host): array => ['8.8.8.8'],
        ));
    }

    public function test_admin_saves_encrypted_roster_url_and_uses_it_without_exposing_it_in_page_or_audit(): void
    {
        $admin = $this->user('admin_it');
        Http::fake([self::ROSTER_URL => fn () => Http::response($this->rosterPayload())]);

        $this->actingAs($admin)->patch(route('data-master.api-connections.update', 'dapodik'), [
            'api_url' => self::ROSTER_URL,
            'current_password' => 'password',
        ])->assertRedirect()->assertSessionHas('success');

        $setting = IntegrationSetting::query()->where('provider', 'dapodik')->sole();
        $this->assertSame(self::ROSTER_URL, $setting->roster_api_url);
        $this->assertStringNotContainsString(self::ROSTER_URL, (string) $setting->getRawOriginal('roster_api_url'));
        $this->assertStringNotContainsString(self::ROSTER_URL, $setting->toJson());
        $this->assertStringNotContainsString(self::ROSTER_URL, AuditLog::query()->get()->toJson());
        $this->assertDatabaseCount('external_sync_runs', 0);
        $this->assertDatabaseCount('students', 0);

        $this->actingAs($admin)->get(route('data-master.index'))->assertOk()->assertDontSee(self::ROSTER_URL);
        $this->actingAs($admin)->postJson(route('data-master.roster-imports.preview'), [
            'use_saved_api' => true,
        ])->assertOk()->assertJsonPath('data.rows', 1)->assertDontSee(self::ROSTER_URL);

        $this->actingAs($admin)->postJson(route('data-master.api-connections.reveal', 'dapodik'), [
            'current_password' => 'password',
        ])->assertOk()->assertJsonPath('api_url', self::ROSTER_URL)
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_api_url_requires_admin_current_password_and_https_without_replacing_saved_value_on_failure(): void
    {
        $admin = $this->user('admin_it');
        $teacher = $this->user('guru_bk');
        IntegrationSetting::query()->create(['provider' => 'dapodik', 'roster_api_url' => self::ROSTER_URL]);
        Http::preventStrayRequests();

        $this->actingAs($teacher)->patch(route('data-master.api-connections.update', 'dapodik'), [
            'api_url' => 'https://8.8.8.8/lain', 'current_password' => 'password',
        ])->assertForbidden();
        $this->actingAs($teacher)->postJson(route('data-master.api-connections.reveal', 'dapodik'), [
            'current_password' => 'password',
        ])->assertForbidden();

        $this->actingAs($admin)->patch(route('data-master.api-connections.update', 'dapodik'), [
            'api_url' => 'https://8.8.8.8/lain', 'current_password' => 'salah',
        ])->assertRedirect()->assertSessionHasErrors('current_password', null, 'api_connections_dapodik')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSessionMissing('_old_input.api_url')->assertSessionMissing('_old_input.current_password');
        $this->actingAs($admin)->patch(route('data-master.api-connections.update', 'dapodik'), [
            'api_url' => 'http://8.8.8.8/lain', 'current_password' => 'password',
        ])->assertRedirect()->assertSessionHasErrors('api_url', null, 'api_connections_dapodik');
        $this->actingAs($admin)->postJson(route('data-master.api-connections.reveal', 'dapodik'), [
            'current_password' => 'salah',
        ])->assertUnprocessable()->assertDontSee(self::ROSTER_URL)
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertSame(self::ROSTER_URL, IntegrationSetting::query()->where('provider', 'dapodik')->sole()->roster_api_url);
        Http::assertNothingSent();
    }

    public function test_editing_etatib_url_pauses_active_schedule_until_saved_url_is_previewed_and_activated(): void
    {
        $admin = $this->user('admin_it');
        IntegrationSetting::query()->create([
            'provider' => 'etatib',
            'automatic_sync_url' => self::ETATIB_URL,
            'automatic_sync_enabled' => true,
        ]);
        Http::fake([self::ETATIB_NEW_URL => fn () => Http::response($this->etatibPayload())]);

        $this->actingAs($admin)->patch(route('data-master.api-connections.update', 'etatib'), [
            'api_url' => self::ETATIB_NEW_URL,
            'current_password' => 'password',
        ])->assertRedirect()->assertSessionHas('success');

        $setting = IntegrationSetting::query()->where('provider', 'etatib')->sole();
        $this->assertSame(self::ETATIB_NEW_URL, $setting->automatic_sync_url);
        $this->assertFalse($setting->automatic_sync_enabled);
        $this->assertDatabaseCount('external_sync_runs', 0);
        $this->assertDatabaseCount('external_tatib_records', 0);
        $this->assertStringNotContainsString(self::ETATIB_NEW_URL, AuditLog::query()->get()->toJson());

        $this->actingAs($admin)->postJson(route('data-master.etatib.preview'), [
            'use_saved_api' => true,
        ])->assertOk();
        $this->actingAs($admin)->post(route('data-master.etatib.automatic.store'), [
            'use_saved_api' => true,
            'current_password' => 'password',
        ])->assertRedirect()->assertSessionHas('warning');

        $this->assertTrue($setting->refresh()->automatic_sync_enabled);
        $this->assertSame(self::ETATIB_NEW_URL, $setting->automatic_sync_url);
        $this->assertDatabaseHas('external_sync_runs', ['source' => 'etatib', 'status' => ExternalSyncRun::STATUS_WARNING]);
    }

    public function test_activation_failure_rolls_back_records_and_does_not_enable_automatic_sync(): void
    {
        $admin = $this->user('admin_it');
        Student::query()->create(['nisn' => '0093200788', 'name' => 'FERRYSCHA PUTRI']);
        Http::fake([self::ETATIB_URL => fn () => Http::response($this->etatibPayload())]);
        $preview = $this->preview(self::ETATIB_URL, $admin);
        $appliedRecordCount = null;
        $run = app(SimpleEtatibApiService::class)->synchronize(
            self::ETATIB_URL, $admin, $preview, [], [],
            static function () use (&$appliedRecordCount): never {
                $appliedRecordCount = ExternalTatibRecord::query()->count();
                throw new \RuntimeException('gagal setelah pemrosesan');
            },
        );

        $this->assertSame(ExternalSyncRun::STATUS_FAILED, $run->status);
        $this->assertSame(1, $appliedRecordCount);
        $this->assertDatabaseCount('external_tatib_records', 0);
        $this->assertFalse((bool) IntegrationSetting::query()->where('provider', 'etatib')->sole()->automatic_sync_enabled);
    }

    public function test_stale_saved_etatib_url_cannot_overwrite_a_newer_url_during_activation(): void
    {
        $admin = $this->user('admin_it');
        IntegrationSetting::query()->create([
            'provider' => 'etatib',
            'automatic_sync_url' => self::ETATIB_NEW_URL,
            'automatic_sync_enabled' => false,
        ]);
        Http::fake([self::ETATIB_URL => fn () => Http::response($this->etatibPayload())]);
        $preview = $this->preview(self::ETATIB_URL, $admin);

        $run = app(EtatibAutomaticSyncService::class)->activate(
            self::ETATIB_URL, $admin, $preview, [], [], true,
        );

        $this->assertSame(ExternalSyncRun::STATUS_FAILED, $run->status);
        $this->assertStringContainsString('Link API tersimpan berubah', $run->summary);
        $setting = IntegrationSetting::query()->where('provider', 'etatib')->sole();
        $this->assertSame(self::ETATIB_NEW_URL, $setting->automatic_sync_url);
        $this->assertFalse($setting->automatic_sync_enabled);
        $this->assertDatabaseCount('external_tatib_records', 0);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }

    private function preview(string $url, User $actor): array
    {
        $preview = app(SimpleEtatibApiService::class)->preview($url, $actor);

        return [
            'actor_id' => $actor->getKey(),
            'url_hash' => hash('sha256', $url),
            'fingerprint' => $preview['fingerprint'],
            'at' => now()->getTimestamp(),
        ];
    }

    /** @return array<string, mixed> */
    private function rosterPayload(): array
    {
        return ['success' => true, 'data' => [[
            'nama' => 'Murid Baru', 'nisn' => '0098765432', 'rombel' => 'X RPL 1', 'tahun_pelajaran' => '2027/2028',
        ]]];
    }

    /** @return list<array<string, int|string>> */
    private function etatibPayload(): array
    {
        return [[
            'siswa_nisn' => '93200788', 'siswa_nama' => 'FERRYSCHA PUTRI', 'siswa_kelas' => '12 PH 2',
            'pelanggaran' => 'Datang Terlambat', 'poin_pelanggaran' => 10,
            'pencatat' => 'ANIS', 'kategori' => 'ringan', 'tanggal_pelanggaran' => '20 Jul 2026 10:17:00',
            'total_poin' => '20',
        ]];
    }
}
