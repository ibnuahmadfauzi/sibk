<?php

declare(strict_types=1);

namespace App\Integrations\Dapodik;

use App\Integrations\IntegrationConfigurationProvider;
use App\Integrations\IntegrationDriverRegistry;
use App\Integrations\IntegrationOperationContext;
use App\Models\IntegrationSetting;

final class ConfiguredDapodikConnector implements DapodikConnector
{
    public function __construct(
        private readonly IntegrationDriverRegistry $drivers,
        private readonly IntegrationConfigurationProvider $settings,
        private readonly DapodikSnapshotValidator $validator,
    ) {}

    public function fetchSnapshot(IntegrationOperationContext $context): DapodikSnapshot
    {
        $driver = $this->drivers->dapodik();
        $configuration = $this->settings->active(
            IntegrationSetting::PROVIDER_DAPODIK,
            $driver->id(),
            $driver->adapterVersion(),
            $driver->contractVersion(),
            $context,
        );
        $snapshot = $driver->fetchSnapshot($configuration);
        $context->assertWithinDeadline();
        $this->validator->validate($snapshot, $configuration);
        $this->settings->assertCurrent(
            IntegrationSetting::PROVIDER_DAPODIK,
            $configuration->configurationVersion,
            $driver->id(),
            $driver->adapterVersion(),
            $driver->contractVersion(),
            $context,
        );

        return $snapshot;
    }
}
