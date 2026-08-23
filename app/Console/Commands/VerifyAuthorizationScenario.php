<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\AuthorizationScenarioCatalog;
use App\Support\AuthorizationScenarioVerifier;
use Illuminate\Console\Command;

class VerifyAuthorizationScenario extends Command
{
    protected $signature = 'rbac:scenario-verify {--csv= : Path CSV hasil yang ikut diperiksa; default memakai CSV terbaru}';

    protected $description = 'Memeriksa baseline database dan keselarasan lembar hasil penelitian RBAC tanpa mengubah data.';

    public function handle(AuthorizationScenarioVerifier $verifier): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Perintah ini hanya tersedia pada environment local atau testing.');

            return self::FAILURE;
        }

        $path = $this->csvPath();
        $errors = $verifier->verify($path);
        if ($errors !== []) {
            $this->error('Baseline penelitian RBAC tidak konsisten.');
            foreach ($errors as $error) {
                $this->line('- '.$error);
            }

            return self::FAILURE;
        }

        $this->components->info('Baseline penelitian RBAC konsisten.');
        $this->line('Versi dataset: '.AuthorizationScenarioCatalog::DATASET_VERSION);
        $this->line('Tanggal baseline: '.AuthorizationScenarioCatalog::baselineDate()?->toDateString());
        $this->line('Aktor: '.count(AuthorizationScenarioCatalog::actors()));
        $this->line('Skenario: '.count(AuthorizationScenarioCatalog::scenarios()));
        $this->line('CSV diperiksa: '.($path ?? 'tidak ada'));

        return self::SUCCESS;
    }

    private function csvPath(): ?string
    {
        $requested = $this->option('csv');
        if (is_string($requested) && $requested !== '') {
            return str_starts_with($requested, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $requested) === 1
                ? $requested
                : base_path($requested);
        }

        $files = glob(storage_path('app/testing/rbac-results-*.csv')) ?: [];
        sort($files);

        return $files === [] ? null : end($files);
    }
}
