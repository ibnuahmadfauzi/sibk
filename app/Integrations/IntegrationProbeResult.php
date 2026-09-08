<?php

declare(strict_types=1);

namespace App\Integrations;

final readonly class IntegrationProbeResult
{
    public function __construct(
        public string $code,
        public string $driverId,
        public string $adapterVersion,
        public string $contractVersion,
        public ?string $reportedSourceIdentifier,
        public bool $schemaValid,
        public bool $completenessVerified,
    ) {}
}
