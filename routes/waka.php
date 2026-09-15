<?php

declare(strict_types=1);

use App\Http\Controllers\WakaMonitoringController;
use App\Http\Controllers\WakaStudentDepartureController;
use Illuminate\Support\Facades\Route;

Route::get('/waka/students-with-cases', [WakaMonitoringController::class, 'students'])
    ->name('waka.monitoring.students');

Route::get('/waka/student-departures', WakaStudentDepartureController::class)
    ->name('waka.student-departures.index');

Route::get('/waka/reports', [WakaMonitoringController::class, 'reports'])
    ->name('waka.reports');

Route::get('/waka/handling-reports', [WakaMonitoringController::class, 'legacyHandling'])
    ->name('waka.monitoring.handling');

Route::get('/waka/handling-reports/export', [WakaMonitoringController::class, 'export'])
    ->name('waka.monitoring.export');
