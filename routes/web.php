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
    $previewRole = 'guru'; // Fokus pada akun normal (tanpa pembatasan role)
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

Route::get('/notifications', function () {
    $roles = config('sibk-preview.dashboard.roles');
    $previewRole = 'guru'; // Fokus pada akun normal (tanpa pembatasan role)
    
    if (! array_key_exists($previewRole, $roles)) {
        $previewRole = 'guru';
    }
    
    $notifications = [
        [
            'id' => 1,
            'title' => 'Jadwal tindak lanjut Murid A hari ini.',
            'time' => 'Hari ini, 09.10',
            'description' => 'Tindak lanjut · Murid A',
            'is_read' => false,
            'icon' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
            'tone' => 'primary',
        ],
        [
            'id' => 2,
            'title' => 'Kasus K-014 diperbarui.',
            'time' => 'Kemarin, 14.20',
            'description' => 'Perubahan terbaru pada penanganan kasus.',
            'is_read' => false,
            'icon' => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
            'tone' => 'success',
        ],
        [
            'id' => 3,
            'title' => 'Koreksi data yang diajukan telah diperiksa.',
            'time' => 'Kemarin, 10.05',
            'description' => 'Hasil pemeriksaan koreksi data sudah tersedia.',
            'is_read' => true,
            'icon' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
            'tone' => 'warning',
        ],
        [
            'id' => 4,
            'title' => 'Penugasan kasus K-009 diperbarui.',
            'time' => '15 Agu, 13.40',
            'description' => 'Perubahan penanggung jawab kasus telah dicatat.',
            'is_read' => true,
            'icon' => '<path d="M16 3h5v5"/><path d="M8 3H3v5"/><path d="M12 22v-8.3a4 4 0 0 0-1.172-2.828l-5.656-5.656A4 4 0 0 1 4 2.343V2"/><path d="M20 2v.343a4 4 0 0 1-1.172 2.828l-5.656 5.656A4 4 0 0 0 12 13.7V22"/>',
            'tone' => 'info',
        ],
    ];

    return view('pages.notifications.index', [
        'dashboard' => $roles[$previewRole],
        'previewRole' => $previewRole,
        'notifications' => $notifications,
    ]);
})->name('notifications.preview');

Route::get('/account', function () {
    // Default fallback 'guru' agar UI bisa dirender
    $previewRole = request()->query('role', 'guru');
    
    // Data dummy akun
    $account = [
        'name' => 'Pengguna Ruang BK',
        'email' => 'pengguna@sekolah.sch.id',
        'username' => 'pengguna.bk',
        'academic_year' => '2026/2027',
    ];

    $dashboard = [
        'label' => 'Akun Saya',
        'description' => 'Informasi akun yang sedang digunakan.'
    ];

    return view('pages.account.index', compact('previewRole', 'dashboard', 'account'));
})->name('account.preview');

Route::get('/cases', function () {
    // Data dummy untuk tabel PG-101
    $cases = [
        [
            'no' => 'K-014',
            'student' => 'Murid A',
            'class' => 'X RPL 1',
            'source' => 'e-Tatib',
            'category' => 'Pribadi',
            'status' => 'Dalam Penanganan',
            'follow_up' => '18 Agu 2026'
        ],
        [
            'no' => 'K-013',
            'student' => 'Murid B',
            'class' => 'X RPL 2',
            'source' => 'Rujukan',
            'category' => 'Belajar',
            'status' => 'Baru',
            'follow_up' => '20 Agu 2026'
        ],
        [
            'no' => 'K-012',
            'student' => 'Murid C',
            'class' => 'XI RPL 1',
            'source' => 'Murid datang sendiri',
            'category' => 'Sosial',
            'status' => 'Selesai',
            'follow_up' => '—'
        ],
        [
            'no' => 'K-011',
            'student' => 'Murid D',
            'class' => 'X RPL 1',
            'source' => 'Temuan',
            'category' => 'Karier',
            'status' => 'Dalam Penanganan',
            'follow_up' => '22 Agu 2026'
        ],
    ];

    $dashboard = [
        'label' => 'Daftar Kasus',
        'description' => 'Cari, filter, dan buka kasus layanan BK.'
    ];

    return view('pages.cases.index', compact('dashboard', 'cases'));
})->name('cases.index');
