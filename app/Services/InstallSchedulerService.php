<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class InstallSchedulerService
{
    /** @return array{string, string, string} */
    protected function platform(): array
    {
        return [PHP_OS_FAMILY, PHP_BINARY, base_path()];
    }

    /** @return array{status: string, entry: string} */
    public function run(bool $dryRun): array
    {
        [$os, $php, $app] = $this->platform();
        if ($os !== 'Linux') {
            throw new RuntimeException('Pemasangan scheduler hanya tersedia di Linux.');
        }
        foreach ([$php, $app] as $path) {
            if (! str_starts_with($path, '/') || preg_match('/[%\r\n\x00]/', $path)) {
                throw new RuntimeException('Path PHP atau aplikasi tidak aman untuk cron.');
            }
        }

        $logDirectory = rtrim($app, '/').'/storage/logs';
        $logPath = $logDirectory.'/scheduler.log';
        if (! $dryRun) {
            $uid = Process::run(['id', '-u']);
            if (! $uid->successful() || ! preg_match('/^\d+$/', trim($uid->output()))) {
                throw new RuntimeException('Gagal memastikan akun Linux saat ini.');
            }
            if (trim($uid->output()) === '0') {
                throw new RuntimeException('Jangan memasang scheduler sebagai root. Gunakan akun aplikasi.');
            }
            if (! File::isDirectory($logDirectory) || ! File::isWritable($logDirectory)
                || (File::exists($logPath) && (! File::isFile($logPath) || ! File::isWritable($logPath)))) {
                throw new RuntimeException('Direktori atau file log scheduler tidak dapat ditulis akun ini.');
            }
        }

        $marker = '# SIBK scheduler '.hash('sha256', $app);
        $entry = '* * * * * '.$this->quote($php).' '.$this->quote(rtrim($app, '/').'/artisan').' schedule:run >> '.$this->quote($logPath).' 2>&1';
        $current = $this->readCron();
        $lines = preg_split('/\r\n|\n|\r/', $current);
        $markers = array_values(array_filter($lines, fn (string $line): bool => str_contains($line, '# SIBK scheduler')));
        $schedules = array_values(array_filter($lines, fn (string $line): bool => ! str_starts_with(ltrim($line), '#') && str_contains($line, 'schedule:run')));

        if ($markers === [$marker] && $schedules === [$entry]) {
            return ['status' => 'already-installed', 'entry' => $entry];
        }
        if ($markers !== [] || $schedules !== []) {
            throw new RuntimeException('Entri scheduler sudah ada atau berbeda. Periksa crontab secara manual.');
        }
        if ($dryRun) {
            return ['status' => 'preview', 'entry' => $entry];
        }

        if ($this->readCron() !== $current) {
            throw new RuntimeException('Crontab berubah saat pemasangan. Ulangi setelah memeriksa perubahan.');
        }

        $next = $current.(($current !== '' && ! str_ends_with($current, "\n")) ? "\n" : '').$marker."\n".$entry."\n";
        $result = Process::env(['LC_ALL' => 'C', 'NO_COLOR' => '1'])->input($next)->run(['crontab', '-']);
        if (! $result->successful()) {
            throw new RuntimeException('Gagal memasang crontab. Entri scheduler belum dikonfirmasi.');
        }

        return ['status' => 'installed', 'entry' => $entry];
    }

    private function readCron(): string
    {
        $result = Process::env(['LC_ALL' => 'C', 'NO_COLOR' => '1'])->run(['crontab', '-l']);
        if ($result->successful()) {
            return $result->output();
        }
        if ($result->exitCode() === 1 && $result->output() === ''
            && preg_match('/^no crontab for [^\r\n]+$/', trim($result->errorOutput()))) {
            return '';
        }
        throw new RuntimeException('Gagal membaca crontab akun saat ini.');
    }

    private function quote(string $path): string
    {
        return "'".str_replace("'", "'\\''", $path)."'";
    }
}
