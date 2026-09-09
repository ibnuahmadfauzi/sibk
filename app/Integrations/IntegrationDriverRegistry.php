<?php

declare(strict_types=1);

namespace App\Integrations;

use App\Integrations\Dapodik\DapodikDriver;
use App\Integrations\Dapodik\UnavailableDapodikDriver;
use App\Integrations\Etatib\EtatibDriver;
use App\Integrations\Etatib\UnavailableEtatibDriver;
use InvalidArgumentException;

final class IntegrationDriverRegistry
{
    /**
     * @param  array<string, array<string, mixed>>|null  $configuration
     */
    public function __construct(
        private readonly ?array $configuration = null,
        private readonly ?DapodikDriver $dapodikDriver = null,
        private readonly ?EtatibDriver $etatibDriver = null,
    ) {}

    public function dapodik(): DapodikDriver
    {
        if ($this->dapodikDriver !== null) {
            return $this->dapodikDriver;
        }

        return match ($this->driverName('dapodik')) {
            'unavailable' => new UnavailableDapodikDriver,
        };
    }

    public function etatib(): EtatibDriver
    {
        if ($this->etatibDriver !== null) {
            return $this->etatibDriver;
        }

        return match ($this->driverName('etatib')) {
            'unavailable' => new UnavailableEtatibDriver,
        };
    }

    private function driverName(string $provider): string
    {
        $configuration = $this->configuration ?? config('sibk.integrations', []);
        $driver = $configuration[$provider]['driver'] ?? null;

        if (! is_string($driver) || $driver !== 'unavailable') {
            throw new InvalidArgumentException("Integration driver for {$provider} is not allowed.");
        }

        return $driver;
    }
}
