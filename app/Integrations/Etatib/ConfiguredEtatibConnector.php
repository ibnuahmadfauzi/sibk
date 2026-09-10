<?php

declare(strict_types=1);

namespace App\Integrations\Etatib;

use App\Integrations\IntegrationConfigurationProvider;
use App\Integrations\IntegrationDriverRegistry;
use App\Integrations\IntegrationOperationContext;
use App\Models\IntegrationSetting;

final class ConfiguredEtatibConnector implements EtatibConnector
{
    public function __construct(
        private readonly IntegrationDriverRegistry $drivers,
        private readonly IntegrationConfigurationProvider $settings,
        private readonly EtatibSnapshotValidator $validator,
    ) {}

    public function fetchSnapshot(IntegrationOperationContext $context): EtatibSnapshot
    {
        $driver = $this->drivers->etatib();
        $configuration = $this->settings->active(
            IntegrationSetting::PROVIDER_ETATIB,
            $driver->id(),
            $driver->adapterVersion(),
            $driver->contractVersion(),
            $context,
        );
        $snapshot = $driver->fetchSnapshot($configuration);
        $context->assertWithinDeadline();
        $this->validator->validate($snapshot, $configuration);
        $this->settings->assertCurrent(
            IntegrationSetting::PROVIDER_ETATIB,
            $configuration->configurationVersion,
            $driver->id(),
            $driver->adapterVersion(),
            $driver->contractVersion(),
            $context,
        );

        return $snapshot;
    }
}
