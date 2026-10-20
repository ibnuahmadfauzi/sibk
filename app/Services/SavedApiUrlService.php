<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SavedApiUrlService
{
    public function __construct(
        private readonly ApiSiswaRosterImportService $roster,
        private readonly SimpleEtatibApiService $etatib,
        private readonly IntegrationOperationLock $operationLock,
        private readonly AuditService $audit,
    ) {}

    public function configured(string $provider): bool
    {
        $column = $this->column($provider);

        return IntegrationSetting::query()->where('provider', $provider)->whereNotNull($column)->exists();
    }

    public function get(string $provider, User $actor): string
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $column = $this->column($provider);
        $setting = IntegrationSetting::query()->where('provider', $provider)->first();

        try {
            $url = $setting?->$column;
        } catch (DecryptException) {
            $url = null;
        }

        if (! is_string($url) || $url === '') {
            throw ValidationException::withMessages(['api_url' => 'Link API tersimpan belum tersedia atau tidak dapat dibaca. Simpan ulang link.']);
        }

        return $url;
    }

    public function save(string $provider, #[\SensitiveParameter] string $url, User $actor): void
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $column = $this->column($provider);

        if ($provider === IntegrationSetting::PROVIDER_DAPODIK) {
            $this->roster->preview($url, $actor);
        } else {
            $this->etatib->preview($url, $actor);
        }

        $this->operationLock->run($provider, function (IntegrationOperationContext $context) use ($provider, $column, $url, $actor): void {
            DB::transaction(function () use ($context, $provider, $column, $url, $actor): void {
                $setting = IntegrationSetting::query()->where('provider', $provider)->lockForUpdate()->firstOrFail();
                $this->operationLock->assertCurrent($context, $setting);
                $previous = null;
                try {
                    $previous = $setting->$column;
                } catch (DecryptException) {
                    // Replacing an unreadable secret is allowed.
                }
                if ($previous === $url) {
                    return;
                }

                $wasEnabled = (bool) $setting->automatic_sync_enabled;
                $setting->$column = $url;
                if ($provider === IntegrationSetting::PROVIDER_ETATIB && $wasEnabled) {
                    $setting->automatic_sync_enabled = false;
                    $setting->automatic_sync_enabled_at = null;
                    $setting->automatic_sync_updated_by = $actor->getKey();
                }
                $setting->save();
                $this->audit->record(
                    action: $provider.'.api_url_saved',
                    auditable: $setting,
                    summary: 'Link API '.$provider.' disimpan.',
                    actor: $actor,
                    before: ['has_api_url' => $previous !== null, 'automatic_sync_enabled' => $wasEnabled],
                    after: ['has_api_url' => true, 'automatic_sync_enabled' => (bool) $setting->automatic_sync_enabled],
                );
                $setting->refresh();
                $this->operationLock->assertCurrent($context, $setting);
            });
        });
    }

    private function column(string $provider): string
    {
        return match ($provider) {
            IntegrationSetting::PROVIDER_DAPODIK => 'roster_api_url',
            IntegrationSetting::PROVIDER_ETATIB => 'automatic_sync_url',
            default => throw ValidationException::withMessages(['api_url' => 'Sumber API tidak dikenal.']),
        };
    }
}
