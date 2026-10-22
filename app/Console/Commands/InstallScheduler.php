<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\InstallSchedulerService;
use Illuminate\Console\Command;
use RuntimeException;

final class InstallScheduler extends Command
{
    protected $signature = 'sibk:pasang-scheduler {--dry-run : Tampilkan rencana tanpa mengubah crontab}';

    protected $description = 'Pasang Laravel scheduler pada crontab akun Linux saat ini';

    public function handle(InstallSchedulerService $service): int
    {
        try {
            $result = $service->run((bool) $this->option('dry-run'));
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(match ($result['status']) {
            'preview' => 'Pratinjau entri cron:',
            'already-installed' => 'Scheduler sudah terpasang:',
            default => 'Scheduler berhasil dipasang:',
        });
        $this->line($result['entry']);

        return self::SUCCESS;
    }
}
