<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AlumniRetentionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

final class PurgeExpiredAlumni extends Command
{
    protected $signature = 'sibk:hapus-alumni {--execute : Jalankan penghapusan permanen}';

    protected $description = 'Hapus data BK alumni resmi satu tahun setelah kelulusan';

    public function handle(AlumniRetentionService $service): int
    {
        try {
            $result = $service->run((bool) $this->option('execute'));
            if ($this->option('execute')) {
                Log::notice('Retensi alumni selesai.', $result);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Penghapusan alumni gagal. Data transaksi terakhir dibatalkan; periksa log aplikasi.');

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            '%s: %d memenuhi syarat, %d ditunda, %d dihapus.',
            $this->option('execute') ? 'Penghapusan' : 'Pratinjau',
            $result['eligible'], $result['deferred'], $result['deleted'],
        ));

        return self::SUCCESS;
    }
}
