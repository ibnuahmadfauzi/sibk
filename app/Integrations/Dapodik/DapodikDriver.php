<?php

declare(strict_types=1);

namespace App\Integrations\Dapodik;

use App\Integrations\IntegrationDriver;
use App\Integrations\IntegrationRuntimeConfiguration;

interface DapodikDriver extends IntegrationDriver
{
    public function fetchSnapshot(IntegrationRuntimeConfiguration $configuration): DapodikSnapshot;
}
