<?php

declare(strict_types=1);

namespace App\Integrations\Etatib;

use App\Integrations\IntegrationOperationContext;

class UnavailableEtatibConnector implements EtatibConnector
{
    public function fetchSnapshot(IntegrationOperationContext $context): EtatibSnapshot
    {
        throw new EtatibUnavailableException(
            'Koneksi e-Tatib belum dikonfigurasi. Hubungi pengelola integrasi sekolah.',
        );
    }
}
