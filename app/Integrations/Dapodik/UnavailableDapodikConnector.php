<?php

declare(strict_types=1);

namespace App\Integrations\Dapodik;

class UnavailableDapodikConnector implements DapodikConnector
{
    public function fetchSnapshot(): DapodikSnapshot
    {
        throw new DapodikUnavailableException(
            'Koneksi Dapodik belum dikonfigurasi. Hubungi pengelola integrasi sekolah.',
        );
    }
}
