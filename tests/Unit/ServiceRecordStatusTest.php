<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ServiceRecordStatus;
use PHPUnit\Framework\TestCase;

final class ServiceRecordStatusTest extends TestCase
{
    public function test_kontrak_status_pelayanan_dibekukan_untuk_semua_jalur(): void
    {
        $this->assertSame([
            'baru',
            'sedang_diproses',
            'membutuhkan_tindak_lanjut',
            'selesai',
        ], ServiceRecordStatus::codes());

        $this->assertSame('baru', ServiceRecordStatus::initialCode());
        $this->assertSame(['selesai'], ServiceRecordStatus::terminalCodes());
        $this->assertTrue(ServiceRecordStatus::isTerminal('selesai'));
        $this->assertFalse(ServiceRecordStatus::isTerminal('dibatalkan'));
        $this->assertFalse(ServiceRecordStatus::isTerminal('sedang_diproses'));
        $this->assertFalse(ServiceRecordStatus::isTerminal(null));
    }

    public function test_label_pengguna_memakai_bahasa_yang_disepakati(): void
    {
        $this->assertSame([
            'baru' => 'Baru dicatat',
            'sedang_diproses' => 'Sedang diproses',
            'membutuhkan_tindak_lanjut' => 'Membutuhkan tindak lanjut',
            'selesai' => 'Selesai',
        ], ServiceRecordStatus::labels());

        $this->assertNull(ServiceRecordStatus::label('tidak_dikenal'));
    }
}
