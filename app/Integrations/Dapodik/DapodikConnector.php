<?php

declare(strict_types=1);

namespace App\Integrations\Dapodik;

interface DapodikConnector
{
    public function fetchSnapshot(): DapodikSnapshot;
}
