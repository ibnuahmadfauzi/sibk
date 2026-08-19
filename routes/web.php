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

// Placeholder — PG-103 akan diimplementasikan pada fase berikutnya
Route::get('/cases/show', function () {
    return redirect()->route('cases.index');
})->name('cases.show');

Route::get('/students', function () {
    return view('pages.students.index');
})->name('students.index');

Route::get('/students/show', function () {
    return view('pages.students.show');
})->name('students.show');

Route::get('/consultations', function () {
    return view('pages.consultations.index');
})->name('consultations.index');

Route::get('/consultations/create', function () {
    return view('pages.consultations.create');
})->name('consultations.create');

Route::get('/consultations/show', function () {
    return view('pages.consultations.show');
})->name('consultations.show');

Route::get('/reports', function () {
    $reports = [
        [
            'id' => 'pelanggaran-murid',
            'title' => 'Pelanggaran per Murid',
            'description' => 'Riwayat pelanggaran per murid',
            'tone' => 'warning',
            'badge_bg' => '#fbece3',
            'icon_color' => '#a84a13',
            'icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>',
        ],
        [
            'id' => 'pelanggaran-kelas',
            'title' => 'Pelanggaran per Kelas',
            'description' => 'Ringkasan pelanggaran per kelas',
            'tone' => 'purple',
            'badge_bg' => '#eeeafd',
            'icon_color' => '#6657c8',
            'icon' => '<rect width="18" height="14" x="3" y="5" rx="2"/><path d="M7 15h10M7 11h10M7 7h10"/>',
        ],
        [
            'id' => 'poin-pelanggaran',
            'title' => 'Poin Pelanggaran',
            'description' => 'Rekap poin dalam periode',
            'tone' => 'primary',
            'badge_bg' => '#e9f2fb',
            'icon_color' => '#2f6fc6',
            'icon' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M9 7h6M9 11h6"/>',
        ],
        [
            'id' => 'konsultasi',
            'title' => 'Konsultasi',
            'description' => 'Rekap layanan konsultasi',
            'tone' => 'success',
            'badge_bg' => '#e7f4ef',
            'icon_color' => '#1f6f59',
            'icon' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        ],
        [
            'id' => 'status-tindak-lanjut',
            'title' => 'Status Tindak Lanjut',
            'description' => 'Pemantauan status tindak lanjut',
            'tone' => 'warning',
            'badge_bg' => '#fbece3',
            'icon_color' => '#a84a13',
            'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        ],
        [
            'id' => 'rekap-layanan-bk',
            'title' => 'Rekap Layanan BK',
            'description' => 'Ringkasan seluruh layanan BK',
            'tone' => 'primary',
            'badge_bg' => '#e9f2fb',
            'icon_color' => '#2f6fc6',
            'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
        ],
        [
            'id' => 'prestasi',
            'title' => 'Prestasi',
            'description' => 'Rekap prestasi murid',
            'tone' => 'purple',
            'badge_bg' => '#eeeafd',
            'icon_color' => '#6657c8',
            'icon' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        ],
    ];

    return view('pages.reports.index', compact('reports'));
})->name('reports.index');

Route::get('/reports/preview', function () {
    $reportTypes = [
        'pelanggaran-murid' => 'Pelanggaran per Murid',
        'pelanggaran-kelas' => 'Pelanggaran per Kelas',
        'poin-pelanggaran' => 'Poin Pelanggaran',
        'konsultasi' => 'Konsultasi',
        'status-tindak-lanjut' => 'Status Tindak Lanjut',
        'rekap-layanan-bk' => 'Rekap Layanan BK',
        'prestasi' => 'Prestasi Murid',
    ];

    $type = request()->query('type', 'rekap-layanan-bk');
    $reportTitle = $reportTypes[$type] ?? 'Rekap Layanan BK';

    $stats = [
        'total' => [
            'label' => 'Jumlah Layanan',
            'value' => '42',
            'sub' => 'Periode aktif',
        ],
        'active' => [
            'label' => 'Kasus Aktif',
            'value' => '12',
            'sub' => 'Perlu pemantauan',
        ],
        'completed' => [
            'label' => 'Selesai',
            'value' => '30',
            'sub' => 'Tuntas',
        ],
    ];

    $rows = [
        [
            'code' => 'L-021',
            'class' => 'X RPL 1',
            'category' => 'Pribadi',
            'status' => 'Dalam Penanganan',
            'status_tone' => 'primary',
            'follow_up' => '18 Agu 2026',
            'date' => '14 Agu',
        ],
        [
            'code' => 'L-020',
            'class' => 'X RPL 2',
            'category' => 'Belajar',
            'status' => 'Baru',
            'status_tone' => 'info',
            'follow_up' => 'Belum dijadwalkan',
            'date' => '14 Agu',
        ],
        [
            'code' => 'L-019',
            'class' => 'XI RPL 1',
            'category' => 'Sosial',
            'status' => 'Selesai',
            'status_tone' => 'success',
            'follow_up' => 'Selesai',
            'date' => '13 Agu',
        ],
        [
            'code' => 'L-018',
            'class' => 'X RPL 1',
            'category' => 'Karier',
            'status' => 'Dalam Penanganan',
            'status_tone' => 'primary',
            'follow_up' => '19 Agu 2026',
            'date' => '12 Agu',
        ],
        [
            'code' => 'L-017',
            'class' => 'XI RPL 2',
            'category' => 'Pribadi',
            'status' => 'Selesai',
            'status_tone' => 'success',
            'follow_up' => 'Selesai',
            'date' => '11 Agu',
        ],
        [
            'code' => 'L-016',
            'class' => 'XII RPL 1',
            'category' => 'Belajar',
            'status' => 'Selesai',
            'status_tone' => 'success',
            'follow_up' => 'Selesai',
            'date' => '10 Agu',
        ],
    ];

    return view('pages.reports.preview', compact('type', 'reportTitle', 'stats', 'rows'));
})->name('reports.preview');

// ==========================================================================
// Paket 400: Pengelolaan
// ==========================================================================

// PG-401: Daftar Penugasan Kelas
Route::get('/assignments/classes', function () {
    $assignments = [
        [
            'class' => 'X RPL 1',
            'counselor' => 'Guru BK A',
            'start_date' => '15 Jul 2026',
            'end_date' => '—',
            'status' => 'Aktif',
            'status_tone' => 'success',
        ],
        [
            'class' => 'X RPL 2',
            'counselor' => 'Guru BK B',
            'start_date' => '15 Jul 2026',
            'end_date' => '—',
            'status' => 'Aktif',
            'status_tone' => 'success',
        ],
        [
            'class' => 'XI RPL 1',
            'counselor' => 'Guru BK C',
            'start_date' => '15 Jul 2026',
            'end_date' => '—',
            'status' => 'Aktif',
            'status_tone' => 'success',
        ],
        [
            'class' => 'XI RPL 2',
            'counselor' => 'Guru BK D',
            'start_date' => '15 Jul 2026',
            'end_date' => '—',
            'status' => 'Aktif',
            'status_tone' => 'success',
        ],
        [
            'class' => 'XII RPL 1',
            'counselor' => 'Guru BK E',
            'start_date' => '15 Jul 2026',
            'end_date' => '—',
            'status' => 'Aktif',
            'status_tone' => 'success',
        ],
        [
            'class' => 'XII RPL 2',
            'counselor' => 'Guru BK F',
            'start_date' => '15 Jul 2026',
            'end_date' => '—',
            'status' => 'Aktif',
            'status_tone' => 'success',
        ],
    ];

    return view('pages.assignments.classes.index', compact('assignments'));
})->name('assignments.classes.index');

// PG-402: Atur Penugasan Kelas
Route::get('/assignments/classes/manage', function () {
    $classes = ['X RPL 1', 'X RPL 2', 'XI RPL 1', 'XI RPL 2', 'XII RPL 1', 'XII RPL 2'];
    $counselors = ['Guru BK A', 'Guru BK B', 'Guru BK C', 'Guru BK D', 'Guru BK E', 'Guru BK F'];
    $currentAssignment = [
        'class' => 'X RPL 1',
        'counselor' => 'Guru BK A',
        'start_date' => '15 Juli 2026',
        'status' => 'Aktif',
    ];

    return view('pages.assignments.classes.manage', compact('classes', 'counselors', 'currentAssignment'));
})->name('assignments.classes.manage');

// PG-403: Penugasan atau Pengalihan Kasus
Route::get('/assignments/cases', function () {
    $cases = [
        ['no' => 'K-014', 'student' => 'Murid A', 'class' => 'X RPL 1', 'status' => 'Dalam Penanganan'],
        ['no' => 'K-013', 'student' => 'Murid B', 'class' => 'X RPL 2', 'status' => 'Baru'],
        ['no' => 'K-011', 'student' => 'Murid D', 'class' => 'X RPL 1', 'status' => 'Dalam Penanganan'],
    ];
    $counselors = ['Guru BK A', 'Guru BK B', 'Guru BK C', 'Guru BK D'];
    $selectedCase = $cases[0];

    return view('pages.assignments.cases.index', compact('cases', 'counselors', 'selectedCase'));
})->name('assignments.cases.index');

// PG-404: Daftar Koreksi Data
Route::get('/corrections', function () {
    $corrections = [
        [
            'id' => 1,
            'object' => 'Kasus K-014',
            'attribute' => 'Bidang Layanan',
            'requester' => 'Pengguna A',
            'type' => 'Operasional',
            'status' => 'Menunggu',
            'status_tone' => 'warning',
            'date' => '16 Agu',
            'action' => 'Periksa',
        ],
        [
            'id' => 2,
            'object' => 'Murid M-003',
            'attribute' => 'Nama',
            'requester' => 'Pengguna B',
            'type' => 'Master',
            'status' => 'Diproses',
            'status_tone' => 'info',
            'date' => '15 Agu',
            'action' => 'Buka',
        ],
        [
            'id' => 3,
            'object' => 'Kasus K-009',
            'attribute' => 'Tanggal Layanan',
            'requester' => 'Pengguna C',
            'type' => 'Operasional',
            'status' => 'Selesai',
            'status_tone' => 'success',
            'date' => '14 Agu',
            'action' => 'Buka',
        ],
    ];

    return view('pages.corrections.index', compact('corrections'));
})->name('corrections.index');

// PG-405: Detail dan Verifikasi Koreksi
Route::get('/corrections/show', function () {
    $correction = [
        'object' => 'Kasus K-014',
        'attribute' => 'Bidang Layanan',
        'requester' => 'Pengguna A',
        'date' => '16 Agustus 2026',
        'old_value' => 'Pribadi',
        'new_value' => 'Belajar',
        'reason' => 'Penyesuaian berdasarkan hasil pemeriksaan data layanan.',
        'status' => 'Menunggu',
    ];

    return view('pages.corrections.show', compact('correction'));
})->name('corrections.show');

// PG-406: Riwayat Perubahan
Route::get('/history', function () {
    $historyLogs = [
        [
            'time' => '16 Agu 10:20',
            'user' => 'Pengguna A',
            'object' => 'Kasus K-014',
            'action' => 'Ubah status',
            'action_tone' => 'primary',
            'summary' => 'Baru menjadi Dalam Penanganan',
        ],
        [
            'time' => '16 Agu 09:05',
            'user' => 'Pengguna B',
            'object' => 'Penugasan X RPL 1',
            'action' => 'Ubah penugasan',
            'action_tone' => 'info',
            'summary' => 'Tanggal berlaku diperbarui',
        ],
        [
            'time' => '15 Agu 14:30',
            'user' => 'Pengguna C',
            'object' => 'Koreksi K-009',
            'action' => 'Verifikasi',
            'action_tone' => 'success',
            'summary' => 'Pengajuan koreksi disetujui',
        ],
        [
            'time' => '14 Agu 11:10',
            'user' => 'Pengguna A',
            'object' => 'Kasus K-008',
            'action' => 'Buat kasus',
            'action_tone' => 'warning',
            'summary' => 'Kasus baru dicatat',
        ],
        [
            'time' => '13 Agu 16:45',
            'user' => 'Pengguna B',
            'object' => 'Kasus K-007',
            'action' => 'Tindak lanjut',
            'action_tone' => 'primary',
            'summary' => 'Hasil koordinasi dicatat',
        ],
    ];

    return view('pages.history.index', compact('historyLogs'));
})->name('history.index');


