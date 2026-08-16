<?php

use Illuminate\Support\Facades\Route;


// Route pratinjau frontend dengan fixture sintetis; bukan kontrak backend final.

Route::get('/', function () {
    return redirect()->route('login.preview');
});
Route::get('/login', function () {
    $allowedStates = ['error', 'system-error', 'success'];
    $authPreviewState = request()->query('auth_state', 'error');

    if (! in_array($authPreviewState, $allowedStates, true)) {
        $authPreviewState = 'error';
    }

    return view('pages.login.index', compact('authPreviewState'));
})->name('login.preview');
Route::get('/dashboard', function () {
    $roles = config('sibk-preview.dashboard.roles');
    $years = config('sibk-preview.dashboard.years');
    $previewRole = request()->query('role', 'guru');
    $previewState = request()->query('state', 'default');
    $activeYear = request()->query('year', $years[0]);

    if (! array_key_exists($previewRole, $roles)) {
        $previewRole = 'guru';
    }

    if (! in_array($previewState, ['default', 'loading', 'empty', 'error'], true)) {
        $previewState = 'default';
    }

    if (! in_array($activeYear, $years, true)) {
        $activeYear = $years[0];
    }

    return view('pages.dashboard.index', [
        'dashboard' => $roles[$previewRole],
        'previewRole' => $previewRole,
        'previewState' => $previewState,
        'previewRoles' => $roles,
        'years' => $years,
        'activeYear' => $activeYear,
    ]);
})->name('dashboard.preview');
