<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Etatib\EtatibUnavailableException;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Models\EtatibDuplicateDecision;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class EtatibAutomaticSyncService
{
    private const array DEFAULT_DAYS = [0, 1, 2, 3, 4, 5, 6];

    private const array DEFAULT_TIMES = ['10:00', '13:00', '14:00'];

    public function __construct(
        private readonly SimpleEtatibApiService $apiService,
        private readonly IntegrationOperationLock $operationLock,
        private readonly AuditService $auditService,
    ) {}

    /** @param array<string, mixed>|null $preview */
    public function activate(string $url, User $actor, ?array $preview = null, array $decisions = [], array $duplicateDecisions = [], bool $useSavedApi = false): ExternalSyncRun
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        return $this->apiService->synchronize(
            $url, $actor, $preview, $decisions, $duplicateDecisions,
            fn (IntegrationOperationContext $context): IntegrationSetting => $this->storeUrl($context, $url, $actor, $useSavedApi),
        );
    }

    public function synchronizeNow(User $actor): ExternalSyncRun
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        return $this->apiService->synchronizeStored($actor);
    }

    public function synchronizeScheduled(): ExternalSyncRun
    {
        return $this->apiService->synchronizeStored();
    }

    /** @return array{days: list<int>, times: list<string>} */
    public function schedule(): array
    {
        $setting = IntegrationSetting::query()->where('provider', IntegrationSetting::PROVIDER_ETATIB)->first();

        return [
            'days' => $setting?->automatic_sync_schedule_days ?? self::DEFAULT_DAYS,
            'times' => $setting?->automatic_sync_schedule_times ?? self::DEFAULT_TIMES,
        ];
    }

    public function isDueAt(DateTimeInterface $time): bool
    {
        $setting = IntegrationSetting::query()->where('provider', IntegrationSetting::PROVIDER_ETATIB)->first();
        if (! $setting?->automatic_sync_enabled) {
            return false;
        }

        $local = CarbonImmutable::instance($time)->setTimezone('Asia/Jakarta');
        $days = $setting->automatic_sync_schedule_days ?? self::DEFAULT_DAYS;
        $times = $setting->automatic_sync_schedule_times ?? self::DEFAULT_TIMES;

        return in_array($local->dayOfWeek, $days, true) && in_array($local->format('H:i'), $times, true);
    }

    /** @param list<int> $days @param list<string> $times */
    public function updateSchedule(array $days, array $times, User $actor): void
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        sort($days);
        sort($times);

        $this->operationLock->run(IntegrationSetting::PROVIDER_ETATIB, function (IntegrationOperationContext $context) use ($days, $times, $actor): void {
            DB::transaction(function () use ($context, $days, $times, $actor): void {
                $setting = IntegrationSetting::query()->where('provider', IntegrationSetting::PROVIDER_ETATIB)->lockForUpdate()->firstOrFail();
                $this->operationLock->assertCurrent($context, $setting);
                $before = [
                    'days' => $setting->automatic_sync_schedule_days ?? self::DEFAULT_DAYS,
                    'times' => $setting->automatic_sync_schedule_times ?? self::DEFAULT_TIMES,
                ];
                if ($before === ['days' => $days, 'times' => $times]) {
                    return;
                }
                $setting->automatic_sync_schedule_days = $days;
                $setting->automatic_sync_schedule_times = $times;
                $setting->save();
                $this->auditService->record('etatib.automatic_sync_schedule_changed', $setting,
                    'Jadwal pembaruan otomatis e-Tatib diperbarui.', $actor,
                    before: $before, after: ['days' => $days, 'times' => $times]);
                $setting->refresh();
                $this->operationLock->assertCurrent($context, $setting);
            });
        });
    }

    public function deactivate(User $actor): void
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        $this->operationLock->run(
            IntegrationSetting::PROVIDER_ETATIB,
            function (IntegrationOperationContext $context) use ($actor): void {
                DB::transaction(function () use ($context, $actor): void {
                    $setting = IntegrationSetting::query()
                        ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->operationLock->assertCurrent($context, $setting);

                    $wasEnabled = $setting->automatic_sync_enabled;
                    $hadUrl = $setting->getRawOriginal('automatic_sync_url') !== null;
                    $setting->automatic_sync_enabled = false;
                    $setting->automatic_sync_enabled_at = null;
                    $setting->automatic_sync_updated_by = $actor->getKey();
                    $setting->save();

                    $this->auditService->record(
                        action: 'etatib.automatic_sync_disabled',
                        auditable: $setting,
                        summary: 'Pembaruan otomatis e-Tatib dinonaktifkan.',
                        actor: $actor,
                        before: [
                            'automatic_sync_enabled' => $wasEnabled,
                            'has_automatic_sync_url' => $hadUrl,
                        ],
                        after: [
                            'automatic_sync_enabled' => false,
                            'has_automatic_sync_url' => $hadUrl,
                        ],
                    );

                    $setting->refresh();
                    $this->operationLock->assertCurrent($context, $setting);
                });
            },
        );
    }

    public function revokeDuplicateDecision(EtatibDuplicateDecision $decision, User $actor): void
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        $this->operationLock->run(IntegrationSetting::PROVIDER_ETATIB, function (IntegrationOperationContext $context) use ($decision, $actor): void {
            DB::transaction(function () use ($decision, $actor, $context): void {
                $setting = IntegrationSetting::query()->where('provider', IntegrationSetting::PROVIDER_ETATIB)
                    ->lockForUpdate()->firstOrFail();
                $this->operationLock->assertCurrent($context, $setting);
                $decision = EtatibDuplicateDecision::query()->lockForUpdate()->findOrFail($decision->getKey());
                if (! $decision->is_active) {
                    return;
                }
                $decision->update(['is_active' => false, 'revoked_by' => $actor->getKey(), 'revoked_at' => now()]);
                $this->auditService->record('etatib.duplicate_revoked', $decision,
                    'Keputusan penggabungan baris e-Tatib dicabut.', $actor,
                    before: ['is_active' => true], after: ['is_active' => false, 'group_key' => $decision->group_key]);
                $setting->refresh();
                $this->operationLock->assertCurrent($context, $setting);
            });
        });
    }

    private function storeUrl(
        IntegrationOperationContext $context,
        string $url,
        User $actor,
        bool $useSavedApi,
    ): IntegrationSetting {
        $setting = IntegrationSetting::query()
            ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
            ->lockForUpdate()
            ->firstOrFail();
        $this->operationLock->assertCurrent($context, $setting);

        $previousUrl = null;
        if ($setting->getRawOriginal('automatic_sync_url') !== null) {
            try {
                $previousUrl = $setting->automatic_sync_url;
            } catch (DecryptException) {
                $previousUrl = null;
            }
        }
        if ($useSavedApi && $previousUrl !== $url) {
            throw new EtatibUnavailableException('Link API tersimpan berubah. Tinjau data kembali sebelum mengaktifkan pembaruan otomatis.');
        }
        $wasEnabled = $setting->automatic_sync_enabled;
        $setting->automatic_sync_url = $url;
        $setting->automatic_sync_enabled = true;
        $setting->automatic_sync_enabled_at = now();
        $setting->automatic_sync_updated_by = $actor->getKey();
        $setting->save();

        $this->auditService->record(
            action: $wasEnabled
                ? 'etatib.automatic_sync_url_changed'
                : 'etatib.automatic_sync_enabled',
            auditable: $setting,
            summary: $wasEnabled
                ? 'Link pembaruan otomatis e-Tatib diperbarui.'
                : 'Pembaruan otomatis e-Tatib diaktifkan.',
            actor: $actor,
            before: [
                'automatic_sync_enabled' => $wasEnabled,
                'has_automatic_sync_url' => $previousUrl !== null,
            ],
            after: [
                'automatic_sync_enabled' => true,
                'has_automatic_sync_url' => true,
                'automatic_sync_url_changed' => $previousUrl !== $url,
            ],
        );

        $setting->refresh();
        $this->operationLock->assertCurrent($context, $setting);

        return $setting;
    }
}
