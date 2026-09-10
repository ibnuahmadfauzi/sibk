<?php

declare(strict_types=1);

namespace App\Integrations\Dapodik;

use App\Integrations\IntegrationProbeResult;
use App\Integrations\IntegrationRuntimeConfiguration;

final class UnavailableDapodikDriver implements DapodikDriver
{
    public function id(): string
    {
        return 'unavailable';
    }

    public function adapterVersion(): string
    {
        return 'unavailable-dapodik-adapter-v1';
    }

    public function contractVersion(): string
    {
        return 'unadmitted-dapodik-contract';
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function probe(IntegrationRuntimeConfiguration $configuration): IntegrationProbeResult
    {
        return new IntegrationProbeResult(
            code: IntegrationProbeResult::CODE_ADAPTER_UNAVAILABLE,
            driverId: $this->id(),
            adapterVersion: $this->adapterVersion(),
            contractVersion: $this->contractVersion(),
            reportedSourceIdentifier: null,
            schemaValid: false,
            completenessVerified: false,
        );
    }

    public function fetchSnapshot(IntegrationRuntimeConfiguration $configuration): DapodikSnapshot
    {
        throw new DapodikUnavailableException('Adapter Dapodik belum tersedia.');
    }
}
