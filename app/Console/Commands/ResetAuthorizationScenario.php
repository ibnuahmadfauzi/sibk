<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\AuthorizationScenarioCatalog;
use App\Support\AuthorizationScenarioVerifier;
use Database\Seeders\AuthorizationScenarioSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ResetAuthorizationScenario extends Command
{
    protected $signature = 'rbac:scenario-reset {--force : Lewati konfirmasi interaktif}';

    protected $description = 'Membuat ulang dataset sintetis dan lembar hasil penelitian RBAC.';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Perintah ini hanya tersedia pada environment local atau testing.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            'Data penelitian berpenanda RBAC-* akan dikembalikan ke kondisi awal. Lanjutkan?',
        )) {
            $this->components->info('Reset dataset RBAC dibatalkan.');

            return self::SUCCESS;
        }

        try {
            $seeder = app(AuthorizationScenarioSeeder::class);
            $seeder->setContainer(app())->setCommand($this)->run();
            $this->assertValidDataset();
            $path = $this->writeResultSheet();
            $this->assertValidDataset($path);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Dataset penelitian RBAC berhasil dikembalikan ke baseline.');
        $this->line('Aktor sintetis: '.count(AuthorizationScenarioCatalog::actors()));
        $this->line('Skenario: '.count(AuthorizationScenarioCatalog::scenarios()));
        $this->line('Versi dataset: '.AuthorizationScenarioCatalog::DATASET_VERSION);
        $this->line('Tanggal baseline: '.AuthorizationScenarioCatalog::baselineDate()?->toDateString());
        $this->line('Lembar hasil: '.$path);
        $this->newLine();
        $this->warn('Gunakan password dari SIBK_SEED_ACCOUNT_PASSWORD. Password tidak ditampilkan oleh perintah ini.');

        return self::SUCCESS;
    }

    private function writeResultSheet(): string
    {
        $directory = storage_path('app/testing');
        File::ensureDirectoryExists($directory);
        $path = $directory.DIRECTORY_SEPARATOR.'rbac-results-'.now()->format('Ymd-His').'.csv';
        $stream = fopen($path, 'wb');
        if ($stream === false) {
            throw new RuntimeException('Lembar hasil RBAC tidak dapat dibuat.');
        }

        $rows = AuthorizationScenarioCatalog::resolvedRows();
        fwrite($stream, "\xEF\xBB\xBF");
        if ($rows !== []) {
            fputcsv($stream, array_keys($rows[0]), ',', '"', '');
            foreach ($rows as $row) {
                fputcsv($stream, array_values($row), ',', '"', '');
            }
        }
        fclose($stream);

        return $path;
    }

    private function assertValidDataset(?string $csvPath = null): void
    {
        $errors = app(AuthorizationScenarioVerifier::class)->verify($csvPath);
        if ($errors !== []) {
            throw new RuntimeException("Dataset penelitian RBAC tidak konsisten:\n- ".implode("\n- ", $errors));
        }
    }
}
