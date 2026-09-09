<?php

declare(strict_types=1);

namespace App\Integrations\Etatib;

use App\Integrations\IntegrationSnapshotEvidence;

final readonly class EtatibSnapshot
{
    /**
     * @param  list<array{source_id: string, nisn: string, occurred_at: string, violation_type: string, category: string, points: int, source_status?: string|null, source_synced_at?: string|null}>  $records
     */
    public function __construct(
        public bool $isFullSnapshot,
        public array $records,
        public ?IntegrationSnapshotEvidence $evidence = null,
    ) {}
}
