<?php

declare(strict_types=1);

namespace App\Integrations\Dapodik;

use App\Integrations\IntegrationOperationContext;

interface DapodikConnector
{
    public function fetchSnapshot(IntegrationOperationContext $context): DapodikSnapshot;
}
