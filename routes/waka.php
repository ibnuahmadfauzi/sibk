<?php

declare(strict_types=1);

use App\Http\Controllers\WakaMonitoringController;
use App\Http\Controllers\WakaStudentDepartureController;
use Illuminate\Support\Facades\Route;

Route::get('/waka/students-with-cases', [WakaMonitoringController::class, 'students'])
    ->name('waka.monitoring.students');

Route::get('/waka/student-departures', WakaStudentDepartureController::class)
    ->name('waka.student-departures.index');

Route::redirect('/waka/reports', '/reports')
    ->middleware('can:viewWakaMonitoring')
    ->name('waka.reports');

Route::redirect('/waka/handling-reports', '/reports')
    ->middleware('can:viewWakaMonitoring')
    ->name('waka.monitoring.handling');
