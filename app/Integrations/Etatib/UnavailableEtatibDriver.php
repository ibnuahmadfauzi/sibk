<?php

declare(strict_types=1);

namespace App\Integrations\Etatib;

use App\Integrations\IntegrationProbeResult;
use App\Integrations\IntegrationRuntimeConfiguration;

final class UnavailableEtatibDriver implements EtatibDriver
{
    public function id(): string
    {
        return 'unavailable';
    }

    public function adapterVersion(): string
    {
        return 'unavailable-etatib-adapter-v1';
    }

    public function contractVersion(): string
    {
        return 'unadmitted-etatib-contract';
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

    public function fetchSnapshot(IntegrationRuntimeConfiguration $configuration): EtatibSnapshot
    {
        throw new EtatibUnavailableException('Adapter e-Tatib belum tersedia.');
    }
}
