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

Route::get('/cases/create', function () {
    return view('pages.cases.create');
})->name('cases.create');

Route::get('/cases/show', function () {
    return view('pages.cases.show');
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
            'badge' => 'Murid',
            'badge_bg' => '#fbece3',
            'icon_color' => '#a84a13',
            'bg_class' => 'sibk-report-card__icon--orange',
            'icon' => '<ellipse cx="12" cy="7" rx="3" ry="3" stroke="#a84a13" stroke-width="1.6" fill="none"/><path d="M5.5 19c0.7-3.1 2.6-4.5 6.5-4.5s5.8 1.4 6.5 4.5" stroke="#a84a13" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
        ],
        [
            'id' => 'pelanggaran-kelas',
            'title' => 'Pelanggaran per Kelas',
            'description' => 'Ringkasan pelanggaran per kelas',
            'badge' => 'Kelas',
            'badge_bg' => '#eeeafd',
            'icon_color' => '#6657c8',
            'bg_class' => 'sibk-report-card__icon--purple',
            'icon' => '<path d="M4 6h16v12H4V6z" stroke="#6657c8" stroke-width="1.6" fill="none"/><path d="M8 2.5v5M16 2.5v5M4 10h16M8 14h1M13 14h1" stroke="#6657c8" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
        ],
        [
            'id' => 'poin-pelanggaran',
            'title' => 'Poin Pelanggaran',
            'description' => 'Rekap poin dalam periode',
            'badge' => 'Poin',
            'badge_bg' => '#e9f2fb',
            'icon_color' => '#2f6fc6',
            'bg_class' => 'sibk-report-card__icon--blue',
            'icon' => '<path d="M7 4v16M17 4v16M4 9h16M4 15h16" stroke="#2f6fc6" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
        ],
        [
            'id' => 'konsultasi',
            'title' => 'Konsultasi',
            'description' => 'Rekap layanan konsultasi',
            'badge' => 'Layanan',
            'badge_bg' => '#e7f4ef',
            'icon_color' => '#1f6f59',
            'bg_class' => 'sibk-report-card__icon--teal',
            'icon' => '<path d="M4 6.5A1.5 1.5 0 0 1 5.5 5h13A1.5 1.5 0 0 1 20 6.5v8a1.5 1.5 0 0 1-1.5 1.5H10l-5 4v-4H5.5A1.5 1.5 0 0 1 4 14.5v-8z" stroke="#1f6f59" stroke-width="1.6" stroke-linejoin="round" fill="none"/>',
        ],
        [
            'id' => 'status-tindak-lanjut',
            'title' => 'Status Tindak Lanjut',
            'description' => 'Pemantauan status tindak lanjut',
            'badge' => 'Tindak Lanjut',
            'badge_bg' => '#fbece3',
            'icon_color' => '#a84a13',
            'bg_class' => 'sibk-report-card__icon--yellow',
            'icon' => '<rect rx="2" ry="2" x="4" y="5.5" width="16" height="15" stroke="#a84a13" stroke-width="1.6" fill="none"/><path d="M7.5 3v4M16.5 3v4M4 10h16" stroke="#a84a13" stroke-width="1.6" stroke-linecap="round" fill="none"/><path d="M8 15l2.5 2.5 5.5-5" stroke="#a84a13" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
        ],
        [
            'id' => 'rekap-layanan-bk',
            'title' => 'Rekap Layanan BK',
            'description' => 'Ringkasan seluruh layanan BK',
            'badge' => 'Rekap',
            'badge_bg' => '#e9f2fb',
            'icon_color' => '#2f6fc6',
            'bg_class' => 'sibk-report-card__icon--blue',
            'icon' => '<path d="M6 3.5h7.5L18 8v12.5H6V3.5z" stroke="#2f6fc6" stroke-width="1.6" stroke-linejoin="round" fill="none"/><path d="M13.5 3.5V8H18M9 12h5M9 16h5" stroke="#2f6fc6" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
        ],
        [
            'id' => 'prestasi',
            'title' => 'Prestasi',
            'description' => 'Rekap prestasi murid',
            'badge' => 'Prestasi',
            'badge_bg' => '#eeeafd',
            'icon_color' => '#6657c8',
            'bg_class' => 'sibk-report-card__icon--purple',
            'icon' => '<path d="M8 4h8v4.5a4 4 0 0 1-8 0V4z" stroke="#6657c8" stroke-width="1.6" fill="none"/><path d="M8 5.5H5a1.5 1.5 0 0 0 1.5 2.5H8M16 5.5h3a1.5 1.5 0 0 1-1.5 2.5H16M12 12.5v4.5M8.5 19.5h7" stroke="#6657c8" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
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


