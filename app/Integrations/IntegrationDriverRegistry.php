<?php

declare(strict_types=1);

namespace App\Integrations;

use App\Integrations\Dapodik\DapodikDriver;
use App\Integrations\Dapodik\UnavailableDapodikDriver;
use App\Integrations\Etatib\EtatibDriver;
use App\Integrations\Etatib\SchoolHttpEtatibDriver;
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

        $driver = $this->driverName('etatib');
        if ($driver === 'school_http_v1'
            && (bool) (($this->configuration ?? config('sibk.integrations', []))['etatib']['admission_approved'] ?? false)
        ) {
            return new SchoolHttpEtatibDriver;
        }

        return match ($driver) {
            'unavailable' => new UnavailableEtatibDriver,
            'school_http_v1' => new UnavailableEtatibDriver,
        };
    }

    private function driverName(string $provider): string
    {
        $configuration = $this->configuration ?? config('sibk.integrations', []);
        $driver = $configuration[$provider]['driver'] ?? null;

        $allowed = $provider === 'etatib'
            ? ['unavailable', 'school_http_v1']
            : ['unavailable'];
        if (! is_string($driver) || ! in_array($driver, $allowed, true)) {
            throw new InvalidArgumentException("Integration driver for {$provider} is not allowed.");
        }

        return $driver;
    }
}
