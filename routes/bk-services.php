<?php

declare(strict_types=1);

use App\Http\Controllers\CaseController;
use App\Http\Controllers\CaseFollowUpController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pengembangan pelayanan BK
|--------------------------------------------------------------------------
|
| Route baru untuk lifecycle kasus dan konsultasi dimiliki Jalur B.
| Route existing tetap berada di web.php sampai refactor terpisah diperlukan.
|
*/

Route::get('/cases/{case}/edit', [CaseController::class, 'edit'])->name('cases.edit');
Route::patch('/cases/{case}', [CaseController::class, 'update'])->name('cases.update');
Route::patch('/cases/{case}/complete', [CaseController::class, 'complete'])->name('cases.complete');
Route::patch('/cases/{case}/follow-up', [CaseController::class, 'updateFollowUp'])->name('cases.follow-up.update');
Route::get('/cases/{case}/follow-ups', [CaseFollowUpController::class, 'index'])->name('cases.follow-ups.index');
Route::post('/cases/{case}/follow-ups', [CaseFollowUpController::class, 'store'])->name('cases.follow-ups.store');
