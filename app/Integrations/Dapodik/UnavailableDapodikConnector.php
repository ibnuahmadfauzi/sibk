<?php

declare(strict_types=1);

namespace App\Integrations\Dapodik;

use App\Integrations\IntegrationOperationContext;

class UnavailableDapodikConnector implements DapodikConnector
{
    public function fetchSnapshot(IntegrationOperationContext $context): DapodikSnapshot
    {
        throw new DapodikUnavailableException(
            'Koneksi Dapodik belum dikonfigurasi. Hubungi pengelola integrasi sekolah.',
        );
    }
}
