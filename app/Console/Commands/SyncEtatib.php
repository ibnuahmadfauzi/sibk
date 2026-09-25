<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Integrations\IntegrationConfigurationException;
use App\Models\ExternalSyncRun;
use App\Models\IntegrationSetting;
use App\Services\EtatibAutomaticSyncService;
use Illuminate\Console\Command;

final class SyncEtatib extends Command
{
    protected $signature = 'sibk:sync-etatib';

    protected $description = 'Sinkronkan data pelanggaran dari API e-Tatib yang aktif';

    public function handle(EtatibAutomaticSyncService $service): int
    {
        $active = IntegrationSetting::query()
            ->where('provider', IntegrationSetting::PROVIDER_ETATIB)
            ->where('automatic_sync_enabled', true)
            ->whereNotNull('automatic_sync_url')
            ->exists();

        if (! $active) {
            $this->components->info('Sinkronisasi dilewati karena koneksi e-Tatib tidak aktif.');

            return self::SUCCESS;
        }

        try {
            $run = $service->synchronizeScheduled();
        } catch (IntegrationConfigurationException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
        if ($run->status === ExternalSyncRun::STATUS_FAILED) {
            $this->components->error($run->summary ?? 'Sinkronisasi e-Tatib gagal.');

            return self::FAILURE;
        }

        $this->components->info($run->summary ?? 'Sinkronisasi e-Tatib selesai.');

        return self::SUCCESS;
    }
}
