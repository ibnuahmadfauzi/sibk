<?php

declare(strict_types=1);

use App\Http\Controllers\CaseController;
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
Route::patch('/cases/{case}/follow-up', [CaseController::class, 'updateFollowUp'])->name('cases.follow-up.update');
