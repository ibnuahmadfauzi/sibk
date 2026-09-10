<?php

declare(strict_types=1);

namespace App\Integrations\Etatib;

use App\Integrations\IntegrationOperationContext;

interface EtatibConnector
{
    public function fetchSnapshot(IntegrationOperationContext $context): EtatibSnapshot;
}
