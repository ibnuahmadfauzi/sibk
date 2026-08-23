<?php

declare(strict_types=1);

namespace App\Integrations\Etatib;

interface EtatibConnector
{
    public function fetchSnapshot(): EtatibSnapshot;
}
