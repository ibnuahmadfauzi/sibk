<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\IntegrationSetting;
use App\Models\Role;
use App\Models\User;
use App\Services\EtatibAutomaticSyncService;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EtatibAutomaticScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_default_three_times_daily_only_run_when_automatic_sync_is_active(): void
    {
        $service = app(EtatibAutomaticSyncService::class);
        $this->assertSame(['days' => [0, 1, 2, 3, 4, 5, 6], 'times' => ['10:00', '13:00', '15:00']], $service->schedule());
        IntegrationSetting::query()->create(['provider' => 'etatib', 'automatic_sync_enabled' => false]);
        $time = CarbonImmutable::parse('2026-10-25 10:00:00', 'Asia/Jakarta');
        $this->assertFalse($service->isDueAt($time));
        IntegrationSetting::query()->where('provider', 'etatib')->update(['automatic_sync_enabled' => true]);
        $this->assertTrue($service->isDueAt($time));
        $this->assertTrue($service->isDueAt($time->setTimezone('UTC')));
        $this->assertFalse($service->isDueAt($time->addMinute()));
        $this->assertTrue($service->isDueAt($time->setTime(13, 0)));
        $this->assertTrue($service->isDueAt($time->setTime(15, 0)));
        $this->assertFalse($service->isDueAt($time->setTime(14, 0)));
    }

    public function test_admin_can_change_days_and_times_without_enabling_sync(): void
    {
        $admin = $this->user('admin_it');
        IntegrationSetting::query()->create(['provider' => 'etatib', 'automatic_sync_enabled' => false]);

        $this->actingAs($admin)->patch(route('data-master.etatib.automatic.schedule'), [
            'schedule_days' => [1, 3, 5],
            'schedule_times' => ['14:30', '09:05'],
            'current_password' => 'password',
        ])->assertRedirect()->assertSessionHas('success');

        $service = app(EtatibAutomaticSyncService::class);
        $this->assertSame(['days' => [1, 3, 5], 'times' => ['09:05', '14:30']], $service->schedule());
        $this->assertFalse(IntegrationSetting::query()->where('provider', 'etatib')->sole()->automatic_sync_enabled);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertSame('etatib.automatic_sync_schedule_changed', AuditLog::query()->sole()->action);
        IntegrationSetting::query()->where('provider', 'etatib')->update(['automatic_sync_enabled' => true]);
        $this->assertTrue($service->isDueAt(CarbonImmutable::parse('2026-10-26 09:05:00', 'Asia/Jakarta')));
        $this->assertFalse($service->isDueAt(CarbonImmutable::parse('2026-10-27 09:05:00', 'Asia/Jakarta')));
        $this->assertFalse($service->isDueAt(CarbonImmutable::parse('2026-10-26 10:00:00', 'Asia/Jakarta')));
    }

    public function test_invalid_schedule_or_password_and_non_admin_cannot_change_schedule(): void
    {
        $admin = $this->user('admin_it');
        $teacher = $this->user('guru_bk');
        IntegrationSetting::query()->create(['provider' => 'etatib']);
        $payload = ['schedule_days' => [1, 1], 'schedule_times' => ['25:00'], 'current_password' => 'password'];

        $this->actingAs($admin)->patch(route('data-master.etatib.automatic.schedule'), $payload)
            ->assertSessionHasErrors(['schedule_days.1', 'schedule_times.0'], null, 'etatib_schedule');
        $this->actingAs($admin)->patch(route('data-master.etatib.automatic.schedule'), [
            'schedule_days' => [1], 'schedule_times' => ['10:00'], 'current_password' => 'salah',
        ])->assertSessionHasErrors('current_password', null, 'etatib_schedule');
        $this->actingAs($teacher)->patch(route('data-master.etatib.automatic.schedule'), [
            'schedule_days' => [1], 'schedule_times' => ['10:00'], 'current_password' => 'password',
        ])->assertForbidden();

        $this->assertSame(['days' => [0, 1, 2, 3, 4, 5, 6], 'times' => ['10:00', '13:00', '15:00']], app(EtatibAutomaticSyncService::class)->schedule());
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }
}
