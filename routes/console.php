<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sibk:sync-etatib')
    ->weekdays()
    ->at('15:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(30)
    ->onOneServer();
