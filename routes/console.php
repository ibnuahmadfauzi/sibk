<?php

declare(strict_types=1);

use App\Services\EtatibAutomaticSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sibk:sync-etatib')
    ->everyMinute()
    ->timezone('Asia/Jakarta')
    ->when(fn (): bool => app(EtatibAutomaticSyncService::class)->isDueAt(now()))
    ->withoutOverlapping(30)
    ->onOneServer();

Schedule::command('sibk:hapus-alumni --execute')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(180)
    ->onOneServer();
