<?php

declare(strict_types=1);

namespace App\Integrations;

use Carbon\CarbonImmutable;

final readonly class IntegrationOperationContext
{
    public function __construct(
        public string $provider,
        public int $fencingToken,
        public CarbonImmutable $startedAt,
        public CarbonImmutable $deadline,
    ) {}

    public function assertWithinDeadline(): void
    {
        if (CarbonImmutable::now()->greaterThanOrEqualTo($this->deadline)) {
            throw new IntegrationConfigurationException('timeout');
        }
    }
}
