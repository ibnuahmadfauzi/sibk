<?php

declare(strict_types=1);

namespace App\Integrations\Etatib;

use App\Integrations\IntegrationDriver;
use App\Integrations\IntegrationRuntimeConfiguration;

interface EtatibDriver extends IntegrationDriver
{
    public function fetchSnapshot(IntegrationRuntimeConfiguration $configuration): EtatibSnapshot;
}
