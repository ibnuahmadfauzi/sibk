<?php

declare(strict_types=1);

namespace App\Integrations;

final readonly class IntegrationSnapshotEvidence
{
    public function __construct(
        public string $reportedSourceIdentifier,
        public string $contractMarker,
        public string $completenessMarker,
        public int $pageCount,
        public int $recordCount,
        public int $processedBytes,
    ) {}
}
