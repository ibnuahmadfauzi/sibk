<?php

declare(strict_types=1);

use App\Http\Controllers\WakaMonitoringController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pengembangan portal Waka Kesiswaan
|--------------------------------------------------------------------------
|
| Route baru untuk proyeksi pemantauan aman Waka dimiliki Jalur C.
| File ini dimuat di dalam middleware autentikasi dan akun aktif.
|
*/

// Murid dengan kasus — tampilan daftar ringkasan aman
Route::get('/waka/students-with-cases', [WakaMonitoringController::class, 'index'])
    ->name('waka.monitoring.students');

// Laporan penanganan — sama dengan students-with-cases, alias untuk kontrak API
Route::get('/waka/handling-reports', [WakaMonitoringController::class, 'index'])
    ->name('waka.monitoring.handling');

// Ekspor laporan penanganan sebagai CSV
Route::get('/waka/handling-reports/export', [WakaMonitoringController::class, 'export'])
    ->name('waka.monitoring.export');
