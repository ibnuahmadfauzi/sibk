<?php

declare(strict_types=1);

namespace App\Integrations;

interface IntegrationConfigurationProvider
{
    public function active(
        string $provider,
        string $driverId,
        string $adapterVersion,
        string $contractVersion,
        ?IntegrationOperationContext $context = null,
    ): IntegrationRuntimeConfiguration;

    public function assertCurrent(
        string $provider,
        int $configurationVersion,
        string $driverId,
        string $adapterVersion,
        string $contractVersion,
        ?IntegrationOperationContext $context = null,
    ): void;
}
