<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DataMasterController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\CaseCoordinationController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// Route pratinjau frontend dengan fixture sintetis; bukan kontrak backend final.

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard.preview' : 'login');
});
Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('login.store');

Route::middleware(['auth', 'account.active'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::post('/admin/users', [UserManagementController::class, 'store'])->name('admin.users.store');
    Route::patch('/admin/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');

    Route::get('/dashboard', function () {
        $roles = config('sibk-preview.dashboard.roles');
        $years = config('sibk-preview.dashboard.years');
        $user = auth()->user();
        $previewRole = match (true) {
            $user?->hasRole('guru_bk') => 'guru',
            $user?->hasRole('koordinator_bk') => 'koordinator',
            $user?->hasRole('waka_kesiswaan') => 'waka',
            default => 'guru',
        };
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
            'description' => 'Informasi akun yang sedang digunakan.',
        ];

        return view('pages.account.index', compact('previewRole', 'dashboard', 'account'));
    })->name('account.preview');

    Route::get('/_preview/cases', function () {
        // Data dummy untuk tabel PG-101 (Kasus)
        $cases = [
            [
                'no' => 'K-014',
                'student' => 'Murid A',
                'class' => 'X RPL 1',
                'source' => 'e-Tatib',
                'category' => 'Pribadi',
                'status' => 'Dalam Penanganan',
                'follow_up' => '18 Agu 2026',
            ],
            [
                'no' => 'K-013',
                'student' => 'Murid B',
                'class' => 'X RPL 2',
                'source' => 'Rujukan',
                'category' => 'Belajar',
                'status' => 'Baru',
                'follow_up' => '20 Agu 2026',
            ],
            [
                'no' => 'K-012',
                'student' => 'Murid C',
                'class' => 'XI RPL 1',
                'source' => 'Murid datang sendiri',
                'category' => 'Sosial',
                'status' => 'Selesai',
                'follow_up' => '—',
            ],
            [
                'no' => 'K-011',
                'student' => 'Murid D',
                'class' => 'X RPL 1',
                'source' => 'Temuan',
                'category' => 'Karier',
                'status' => 'Dalam Penanganan',
                'follow_up' => '22 Agu 2026',
            ],
        ];

        // Data dummy untuk sesi Bimbingan & Konsultasi
        $consultations = [
            [
                'id' => 'KNS-001',
                'student' => 'Murid A',
                'class' => 'X RPL 1',
                'topic' => 'Pemilihan Ekstrakurikuler & Penyesuaian Diri',
                'type' => 'Pribadi',
                'case_ref' => 'K-014',
                'date' => '18 Agu 2026',
                'status' => 'Terlaksana',
                'summary' => 'Murid telah memilih minat eskul dan memahami pembagian waktu belajar.',
            ],
            [
                'id' => 'KNS-002',
                'student' => 'Murid B',
                'class' => 'X RPL 2',
                'topic' => 'Minat Bakat & Eksplorasi Karier Industri',
                'type' => 'Karier',
                'case_ref' => '—',
                'date' => '19 Agu 2026',
                'status' => 'Dijadwalkan',
                'summary' => 'Rencana pembahasan hasil asesmen minat bakat.',
            ],
            [
                'id' => 'KNS-003',
                'student' => 'Murid C',
                'class' => 'XI RPL 1',
                'topic' => 'Peningkatan Motivasi Belajar & Manajemen Waktu',
                'type' => 'Belajar',
                'case_ref' => '—',
                'date' => '20 Agu 2026',
                'status' => 'Dijadwalkan',
                'summary' => 'Diskusi jadwal belajar teratur dan kendala belajar mandiri.',
            ],
            [
                'id' => 'KNS-004',
                'student' => 'Murid D',
                'class' => 'XI RPL 2',
                'topic' => 'Penyesuaian Sosial & Kerjasama Kelompok',
                'type' => 'Sosial',
                'case_ref' => 'K-011',
                'date' => '15 Agu 2026',
                'status' => 'Terlaksana',
                'summary' => 'Mediasi dan komunikasi antar anggota kelompok tugas.',
            ],
        ];

        $activeTab = request()->query('tab', 'kasus');

        return redirect()->route('cases.index', ['tab' => $activeTab]);
    })->name('fixtures.cases.index');

    Route::get('/_preview/cases/create', function () {
        return redirect()->route('cases.create');
    })->name('fixtures.cases.create');

    Route::get('/_preview/cases/show', function () {
        return redirect()->route('cases.index');
    })->name('fixtures.cases.show');

    // PG-104: Tambah atau Edit Tindak Lanjut
    Route::get('/_preview/cases/follow-up', function () {
        return redirect()->route('cases.index');
    })->name('fixtures.cases.follow-up');

    // PG-106: Selesaikan Kasus
    Route::get('/_preview/cases/resolve', function () {
        return redirect()->route('cases.index');
    })->name('fixtures.cases.resolve');

    Route::get('/cases', [CaseController::class, 'index'])->name('cases.index');
    Route::get('/cases/create', [CaseController::class, 'create'])->name('cases.create');
    Route::post('/cases', [CaseController::class, 'store'])->name('cases.store');
    Route::get('/cases/{case}/follow-ups/create', [FollowUpController::class, 'create'])->name('cases.follow-ups.create');
    Route::post('/cases/{case}/follow-ups', [FollowUpController::class, 'store'])->name('cases.follow-ups.store');
    Route::get('/cases/{case}/follow-ups/{followUp}/edit', [FollowUpController::class, 'edit'])->name('cases.follow-ups.edit');
    Route::patch('/cases/{case}/follow-ups/{followUp}', [FollowUpController::class, 'update'])->name('cases.follow-ups.update');
    Route::get('/cases/{case}/resolve', [CaseController::class, 'resolveForm'])->name('cases.resolve.form');
    Route::post('/cases/{case}/resolve', [CaseController::class, 'resolve'])->name('cases.resolve');
    Route::post('/cases/{case}/assign', [AssignmentController::class, 'assignCase'])->name('cases.assign');
    Route::post('/cases/{case}/coordinations', [CaseCoordinationController::class, 'store'])->name('cases.coordinations.store');
    Route::patch('/cases/{case}/coordinations/{coordination}', [CaseCoordinationController::class, 'update'])->name('cases.coordinations.update');
    Route::get('/cases/{case}', [CaseController::class, 'show'])->name('cases.show');

    // PG-201: Daftar Murid
    Route::get('/_preview/students', function () {
        return redirect()->route('students.index');

        $students = [
            [
                'nisn' => '0012345678',
                'name' => 'Murid A',
                'class' => 'X RPL 1',
                'active_cases' => 1,
                'follow_up' => '18 Agu 2026',
            ],
            [
                'nisn' => '0012345679',
                'name' => 'Murid B',
                'class' => 'X RPL 2',
                'active_cases' => 1,
                'follow_up' => 'Belum ada',
            ],
            [
                'nisn' => '0012345680',
                'name' => 'Murid C',
                'class' => 'XI RPL 1',
                'active_cases' => 0,
                'follow_up' => 'Selesai',
            ],
            [
                'nisn' => '0012345681',
                'name' => 'Murid D',
                'class' => 'XI RPL 2',
                'active_cases' => 2,
                'follow_up' => '20 Agu 2026',
            ],
        ];

        $classes = ['Semua kelas', 'X RPL 1', 'X RPL 2', 'XI RPL 1', 'XI RPL 2', 'XII RPL 1'];

        return view('pages.students.index', compact('students', 'classes'));
    })->name('fixtures.students.index');

    // PG-202: Profil Murid
    Route::get('/_preview/students/show', function () {
        return redirect()->route('students.legacy', request()->query());

        $student = [
            'nisn' => request()->query('nisn', '0012345678'),
            'name' => 'Murid A',
            'initials' => 'MA',
            'class' => 'X RPL 1',
            'academic_year' => '2026/2027',
        ];

        $stats = [
            'active_cases' => [
                'value' => '1',
                'label' => 'Kasus Aktif',
                'sub' => 'Dalam penanganan',
                'tone' => 'primary',
                'badge_bg' => '#e9f2fb',
                'icon_color' => '#2f6fc6',
            ],
            'points' => [
                'value' => '15',
                'label' => 'Poin e-Tatib',
                'sub' => 'Data terkait',
                'tone' => 'warning',
                'badge_bg' => '#fdf3e7',
                'icon_color' => '#d97706',
            ],
            'follow_ups' => [
                'value' => '2',
                'label' => 'Tindak Lanjut',
                'sub' => 'Terjadwal',
                'tone' => 'info',
                'badge_bg' => '#e3f2fd',
                'icon_color' => '#0284c7',
            ],
            'achievements' => [
                'value' => '3',
                'label' => 'Prestasi',
                'sub' => 'Riwayat tercatat',
                'tone' => 'purple',
                'badge_bg' => '#eeeafd',
                'icon_color' => '#6657c8',
            ],
        ];

        $operationalSummary = [
            'latest_case' => 'K-014 • Dalam Penanganan',
            'next_follow_up' => '18 Agustus 2026',
            'service_field' => 'Pribadi',
        ];

        $recentActivities = [
            ['date' => '15 Agu', 'activity' => 'Konsultasi dicatat'],
            ['date' => '14 Agu', 'activity' => 'Kasus K-014 dibuat'],
            ['date' => '10 Agu', 'activity' => 'Data e-Tatib diperbarui'],
        ];

        $studentCases = [
            [
                'no' => 'K-014',
                'date' => '14 Agu 2026',
                'category' => 'Pribadi',
                'source' => 'e-Tatib',
                'status' => 'Dalam Penanganan',
                'follow_up' => '18 Agu 2026',
                'pic' => 'Guru BK A',
            ],
            [
                'no' => 'K-005',
                'date' => '10 Jan 2026',
                'category' => 'Belajar',
                'source' => 'Rujukan Wali Kelas',
                'status' => 'Selesai',
                'follow_up' => 'Selesai',
                'pic' => 'Guru BK A',
            ],
        ];

        $studentEtatib = [
            'total_points' => 15,
            'violations' => [
                [
                    'date' => '10 Agu 2026',
                    'time' => '07:15',
                    'violation' => 'Terlambat Masuk Sekolah (>15 Menit)',
                    'category' => 'Kedisiplinan',
                    'points' => 5,
                    'reporter' => 'Petugas Tatib',
                ],
                [
                    'date' => '03 Agu 2026',
                    'time' => '10:30',
                    'violation' => 'Seragam Tidak Sesuai Ketentuan',
                    'category' => 'Kerapian',
                    'points' => 10,
                    'reporter' => 'Wali Kelas',
                ],
            ],
        ];

        $studentConsultations = [
            [
                'id' => 'KNS-001',
                'date' => '18 Agu 2026',
                'type' => 'Pribadi',
                'case_ref' => 'K-014',
                'status' => 'Terlaksana',
                'summary' => 'Murid telah memilih minat eskul dan memahami pembagian waktu belajar.',
            ],
            [
                'id' => 'KNS-009',
                'date' => '12 Agu 2026',
                'type' => 'Belajar',
                'case_ref' => '—',
                'status' => 'Terlaksana',
                'summary' => 'Konseling bimbingan cara belajar efektif menjelang ujian tengah semester.',
            ],
        ];

        $studentAchievements = [
            [
                'activity' => 'Lomba Kompetensi Siswa (LKS) Web Technologies',
                'type' => 'Akademik / Vokasi',
                'level' => 'Tingkat Kota',
                'organizer' => 'Dinas Pendidikan Kota Surabaya',
                'date' => '12 Jul 2026',
                'result' => 'Juara 1',
                'status_verifikasi' => 'Terverifikasi',
            ],
            [
                'activity' => 'Olimpiade Sains Informatika Remaja',
                'type' => 'Ilmiah',
                'level' => 'Tingkat Provinsi',
                'organizer' => 'Universitas Negeri Surabaya',
                'date' => '20 Mei 2026',
                'result' => 'Juara 3',
                'status_verifikasi' => 'Terverifikasi',
            ],
            [
                'activity' => 'Turnamen Basket Antar Pelajar Surabaya',
                'type' => 'Olahraga',
                'level' => 'Tingkat Kota',
                'organizer' => 'Perbasi Surabaya',
                'date' => '15 Feb 2026',
                'result' => 'Semifinalis',
                'status_verifikasi' => 'Terverifikasi',
            ],
        ];

        $activeTab = request()->query('tab', 'ringkasan');

        return view('pages.students.show', compact(
            'student',
            'stats',
            'operationalSummary',
            'recentActivities',
            'studentCases',
            'studentEtatib',
            'studentConsultations',
            'studentAchievements',
            'activeTab'
        ));
    })->name('fixtures.students.show');

    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/show', [StudentController::class, 'legacy'])->name('students.legacy');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');

    Route::get('/consultations', function () {
        return redirect()->route('cases.index', ['tab' => 'konsultasi']);
    })->name('consultations.index');

    Route::get('/_preview/consultations/show', function () {
        return redirect()->route('consultations.index');
    })->name('fixtures.consultations.show');

    Route::get('/consultations/create', [ConsultationController::class, 'create'])->name('consultations.create');
    Route::post('/consultations', [ConsultationController::class, 'store'])->name('consultations.store');
    Route::get('/consultations/{consultation}/edit', [ConsultationController::class, 'edit'])->name('consultations.edit');
    Route::patch('/consultations/{consultation}', [ConsultationController::class, 'update'])->name('consultations.update');
    Route::get('/consultations/{consultation}', [ConsultationController::class, 'show'])->name('consultations.show');

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
        $reportConfigs = [
            'rekap-layanan-bk' => [
                'title' => 'Rekap Layanan BK',
                'desc' => 'Ringkasan seluruh layanan bimbingan dan konseling',
                'stats' => [
                    'total' => ['label' => 'Jumlah Layanan', 'value' => '42', 'sub' => 'Periode aktif', 'tone' => 'primary'],
                    'active' => ['label' => 'Kasus Aktif', 'value' => '12', 'sub' => 'Perlu pemantauan', 'tone' => 'warning'],
                    'completed' => ['label' => 'Selesai', 'value' => '30', 'sub' => 'Tuntas', 'tone' => 'success'],
                ],
                'filters' => ['periode', 'kelas', 'bidang', 'status'],
                'columns' => ['Kode', 'Kelas', 'Bidang Layanan', 'Status', 'Tindak Lanjut', 'Tanggal'],
                'rows' => [
                    ['col1' => 'L-021', 'col2' => 'X RPL 1', 'col3' => 'Pribadi', 'status' => 'Dalam Penanganan', 'status_tone' => 'warning', 'col5' => '18 Agu 2026', 'col6' => '14 Agu'],
                    ['col1' => 'L-020', 'col2' => 'X RPL 2', 'col3' => 'Belajar', 'status' => 'Baru', 'status_tone' => 'primary', 'col5' => 'Belum dijadwalkan', 'col6' => '14 Agu'],
                    ['col1' => 'L-019', 'col2' => 'XI RPL 1', 'col3' => 'Sosial', 'status' => 'Selesai', 'status_tone' => 'success', 'col5' => 'Selesai', 'col6' => '13 Agu'],
                    ['col1' => 'L-018', 'col2' => 'X RPL 1', 'col3' => 'Karier', 'status' => 'Dalam Penanganan', 'status_tone' => 'warning', 'col5' => '19 Agu 2026', 'col6' => '12 Agu'],
                    ['col1' => 'L-017', 'col2' => 'XI RPL 2', 'col3' => 'Pribadi', 'status' => 'Selesai', 'status_tone' => 'success', 'col5' => 'Selesai', 'col6' => '11 Agu'],
                    ['col1' => 'L-016', 'col2' => 'XII RPL 1', 'col3' => 'Belajar', 'status' => 'Selesai', 'status_tone' => 'success', 'col5' => 'Selesai', 'col6' => '10 Agu'],
                ],
            ],
            'pelanggaran-murid' => [
                'title' => 'Pelanggaran per Murid',
                'desc' => 'Riwayat pelanggaran tata tertib per murid',
                'stats' => [
                    'total' => ['label' => 'Total Kejadian', 'value' => '18', 'sub' => 'Periode terpilih', 'tone' => 'primary'],
                    'active' => ['label' => 'Murid Terkait', 'value' => '12', 'sub' => 'Dalam binaan', 'tone' => 'warning'],
                    'completed' => ['label' => 'Total Poin', 'value' => '135', 'sub' => 'Akumulasi tercatat', 'tone' => 'success'],
                ],
                'filters' => ['periode', 'kelas', 'kategori_pelanggaran', 'murid'],
                'columns' => ['NISN', 'Nama Murid', 'Kelas', 'Tanggal', 'Jenis Pelanggaran', 'Kategori', 'Poin'],
                'rows' => [
                    ['col1' => '0012345678', 'col2' => 'Murid A', 'col3' => 'X RPL 1', 'col4' => '14 Agu 2026', 'col5' => 'Terlambat Masuk Sekolah (>15 Menit)', 'category' => 'Kedisiplinan', 'badge_value' => '5 Poin', 'status_tone' => 'primary'],
                    ['col1' => '0012345679', 'col2' => 'Murid B', 'col3' => 'X RPL 2', 'col4' => '12 Agu 2026', 'col5' => 'Seragam Tidak Sesuai Ketentuan', 'category' => 'Kerapian', 'badge_value' => '10 Poin', 'status_tone' => 'warning'],
                    ['col1' => '0012345681', 'col2' => 'Murid D', 'col3' => 'XI RPL 2', 'col4' => '10 Agu 2026', 'col5' => 'Meninggalkan Kelas Tanpa Izin', 'category' => 'Kedisiplinan', 'badge_value' => '15 Poin', 'status_tone' => 'warning'],
                    ['col1' => '0012345682', 'col2' => 'Murid E', 'col3' => 'XII RPL 1', 'col4' => '08 Agu 2026', 'col5' => 'Tidak Mengikuti Upacara Bendera', 'category' => 'Kedisiplinan', 'badge_value' => '10 Poin', 'status_tone' => 'primary'],
                ],
            ],
            'pelanggaran-kelas' => [
                'title' => 'Pelanggaran per Kelas',
                'desc' => 'Ringkasan distribusi pelanggaran dan penanganan per kelas',
                'stats' => [
                    'total' => ['label' => 'Total Pelanggaran', 'value' => '24', 'sub' => 'Seluruh kelas binaan', 'tone' => 'primary'],
                    'active' => ['label' => 'Kelas Tertinggi', 'value' => 'X RPL 1', 'sub' => '8 kejadian tercatat', 'tone' => 'warning'],
                    'completed' => ['label' => 'Kasus Tertangani', 'value' => '19', 'sub' => 'Dari 24 kejadian', 'tone' => 'success'],
                ],
                'filters' => ['periode', 'kelas', 'kategori_pelanggaran'],
                'columns' => ['Kelas', 'Wali Kelas', 'Jumlah Kejadian', 'Total Poin', 'Kategori Dominan', 'Status Penanganan'],
                'rows' => [
                    ['col1' => 'X RPL 1', 'col2' => 'Guru A, S.Pd.', 'col3' => '8 Kejadian', 'col4' => '55 Poin', 'col5' => 'Kedisiplinan', 'status' => 'Dalam Penanganan', 'status_tone' => 'warning'],
                    ['col1' => 'X RPL 2', 'col2' => 'Guru B, S.Pd.', 'col3' => '5 Kejadian', 'col4' => '30 Poin', 'col5' => 'Kerapian', 'status' => 'Selesai', 'status_tone' => 'success'],
                    ['col1' => 'XI RPL 1', 'col2' => 'Guru C, S.Kom.', 'col3' => '3 Kejadian', 'col4' => '15 Poin', 'col5' => 'Kedisiplinan', 'status' => 'Selesai', 'status_tone' => 'success'],
                    ['col1' => 'XI RPL 2', 'col2' => 'Guru D, M.Pd.', 'col3' => '6 Kejadian', 'col4' => '40 Poin', 'col5' => 'Kedisiplinan', 'status' => 'Dalam Penanganan', 'status_tone' => 'warning'],
                    ['col1' => 'XII RPL 1', 'col2' => 'Guru E, S.T.', 'col3' => '2 Kejadian', 'col4' => '10 Poin', 'col5' => 'Kerapian', 'status' => 'Selesai', 'status_tone' => 'success'],
                ],
            ],
            'poin-pelanggaran' => [
                'title' => 'Poin Pelanggaran',
                'desc' => 'Rekapitulasi akumulasi poin pelanggaran dari e-Tatib',
                'stats' => [
                    'total' => ['label' => 'Total Akumulasi Poin', 'value' => '150', 'sub' => 'Sinkronisasi e-Tatib', 'tone' => 'primary'],
                    'active' => ['label' => 'Ambang Pembinaan', 'value' => '4 Murid', 'sub' => 'Poin ≥ 25 poin', 'tone' => 'warning'],
                    'completed' => ['label' => 'Waktu Sinkronisasi', 'value' => 'Hari ini', 'sub' => '16 Agu 2026, 06.00', 'tone' => 'success'],
                ],
                'filters' => ['periode', 'kelas', 'ambang_poin'],
                'columns' => ['NISN', 'Nama Murid', 'Kelas', 'Total Poin', 'Kategori Terkait', 'Status Pembinaan', 'Terakhir Sinkron'],
                'rows' => [
                    ['col1' => '0012345681', 'col2' => 'Murid D', 'col3' => 'XI RPL 2', 'col4' => '35 Poin', 'col5' => 'Kedisiplinan & Kerapian', 'status' => 'Perlu Pembinaan Khusus', 'status_tone' => 'warning', 'col7' => 'Hari ini 06.00'],
                    ['col1' => '0012345678', 'col2' => 'Murid A', 'col3' => 'X RPL 1', 'col4' => '15 Poin', 'col5' => 'Kedisiplinan', 'status' => 'Pemantauan Rutin', 'status_tone' => 'primary', 'col7' => 'Hari ini 06.00'],
                    ['col1' => '0012345679', 'col2' => 'Murid B', 'col3' => 'X RPL 2', 'col4' => '10 Poin', 'col5' => 'Kerapian', 'status' => 'Normal', 'status_tone' => 'success', 'col7' => 'Hari ini 06.00'],
                    ['col1' => '0012345682', 'col2' => 'Murid E', 'col3' => 'XII RPL 1', 'col4' => '10 Poin', 'col5' => 'Kedisiplinan', 'status' => 'Normal', 'status_tone' => 'success', 'col7' => 'Hari ini 06.00'],
                ],
            ],
            'konsultasi' => [
                'title' => 'Konsultasi',
                'desc' => 'Rekapitulasi layanan bimbingan & konsultasi (tanpa isi sensitif)',
                'stats' => [
                    'total' => ['label' => 'Total Konsultasi', 'value' => '28', 'sub' => 'Periode terpilih', 'tone' => 'primary'],
                    'active' => ['label' => 'Terlaksana', 'value' => '22', 'sub' => 'Sesi selesai', 'tone' => 'success'],
                    'completed' => ['label' => 'Dijadwalkan', 'value' => '6', 'sub' => 'Menunggu pelaksanaan', 'tone' => 'warning'],
                ],
                'filters' => ['periode', 'kelas', 'bidang', 'status'],
                'columns' => ['No Sesi', 'Inisial Murid', 'Kelas', 'Bidang Layanan', 'Tanggal', 'Guru BK', 'Status'],
                'rows' => [
                    ['col1' => 'KNS-001', 'col2' => 'Murid A', 'col3' => 'X RPL 1', 'col4' => 'Pribadi', 'col5' => '18 Agu 2026', 'col6' => 'Guru BK A', 'status' => 'Terlaksana', 'status_tone' => 'success'],
                    ['col1' => 'KNS-002', 'col2' => 'Murid B', 'col3' => 'X RPL 2', 'col4' => 'Karier', 'col5' => '19 Agu 2026', 'col6' => 'Guru BK B', 'status' => 'Dijadwalkan', 'status_tone' => 'primary'],
                    ['col1' => 'KNS-003', 'col2' => 'Murid C', 'col3' => 'XI RPL 1', 'col4' => 'Belajar', 'col5' => '20 Agu 2026', 'col6' => 'Guru BK A', 'status' => 'Dijadwalkan', 'status_tone' => 'primary'],
                    ['col1' => 'KNS-004', 'col2' => 'Murid D', 'col3' => 'XI RPL 2', 'col4' => 'Sosial', 'col5' => '15 Agu 2026', 'col6' => 'Guru BK C', 'status' => 'Terlaksana', 'status_tone' => 'success'],
                ],
            ],
            'status-tindak-lanjut' => [
                'title' => 'Status Tindak Lanjut',
                'desc' => 'Pemantauan jadwal dan realisasi pelaksanaan tindak lanjut',
                'stats' => [
                    'total' => ['label' => 'Total Tindak Lanjut', 'value' => '16', 'sub' => 'Kasus aktif & selesai', 'tone' => 'primary'],
                    'active' => ['label' => 'Dalam Penanganan', 'value' => '5', 'sub' => 'Perlu pelaksanaan', 'tone' => 'warning'],
                    'completed' => ['label' => 'Selesai', 'value' => '11', 'sub' => 'Telah terealisasi', 'tone' => 'success'],
                ],
                'filters' => ['periode', 'kelas', 'status'],
                'columns' => ['No Kasus', 'Inisial Murid', 'Kelas', 'Bentuk Tindak Lanjut', 'Tgl Rencana', 'Tgl Realisasi', 'Status'],
                'rows' => [
                    ['col1' => 'K-014', 'col2' => 'Murid A', 'col3' => 'X RPL 1', 'col4' => 'Konseling Individu', 'col5' => '18 Agu 2026', 'col6' => '18 Agu 2026', 'status' => 'Selesai', 'status_tone' => 'success'],
                    ['col1' => 'K-013', 'col2' => 'Murid B', 'col3' => 'X RPL 2', 'col4' => 'Panggilan Orang Tua', 'col5' => '20 Agu 2026', 'col6' => '—', 'status' => 'Dijadwalkan', 'status_tone' => 'primary'],
                    ['col1' => 'K-011', 'col2' => 'Murid D', 'col3' => 'XI RPL 2', 'col4' => 'Konferensi Kasus', 'col5' => '22 Agu 2026', 'col6' => '—', 'status' => 'Dalam Proses', 'status_tone' => 'warning'],
                    ['col1' => 'K-009', 'col2' => 'Murid E', 'col3' => 'XII RPL 1', 'col4' => 'Kunjungan Rumah', 'col5' => '12 Agu 2026', 'col6' => '13 Agu 2026', 'status' => 'Selesai', 'status_tone' => 'success'],
                ],
            ],
            'prestasi' => [
                'title' => 'Prestasi Murid',
                'desc' => 'Rekapitulasi pencatatan dan verifikasi prestasi murid',
                'stats' => [
                    'total' => ['label' => 'Total Prestasi', 'value' => '14', 'sub' => 'Tahun Ajaran 2026/2027', 'tone' => 'primary'],
                    'active' => ['label' => 'Terverifikasi', 'value' => '12', 'sub' => 'Bukti tervalidasi', 'tone' => 'success'],
                    'completed' => ['label' => 'Tingkat Kota / Prov', 'value' => '5', 'sub' => 'Kejuaraan resmi', 'tone' => 'warning'],
                ],
                'filters' => ['periode', 'kelas', 'tingkat_prestasi', 'status_verifikasi'],
                'columns' => ['Inisial Murid', 'Kelas', 'Nama Kegiatan / Lomba', 'Jenis & Tingkat', 'Hasil / Juara', 'Tanggal', 'Status Verifikasi'],
                'rows' => [
                    ['col1' => 'Murid A', 'col2' => 'X RPL 1', 'col3' => 'LKS Web Technologies 2026', 'col4' => 'Vokasi • Kota', 'col5' => 'Juara 1', 'col6' => '12 Jul 2026', 'status' => 'Terverifikasi', 'status_tone' => 'success'],
                    ['col1' => 'Murid A', 'col2' => 'X RPL 1', 'col3' => 'Olimpiade Sains Informatika', 'col4' => 'Ilmiah • Provinsi', 'col5' => 'Juara 3', 'col6' => '20 Mei 2026', 'status' => 'Terverifikasi', 'status_tone' => 'success'],
                    ['col1' => 'Murid C', 'col2' => 'XI RPL 1', 'col3' => 'Turnamen Basket Pelajar', 'col4' => 'Olahraga • Kota', 'col5' => 'Semifinalis', 'col6' => '15 Feb 2026', 'status' => 'Terverifikasi', 'status_tone' => 'success'],
                    ['col1' => 'Murid D', 'col2' => 'XI RPL 2', 'col3' => 'Lomba Karya Tulis Ilmiah', 'col4' => 'Akademik • Nasional', 'col5' => 'Finalis', 'col6' => '10 Agu 2026', 'status' => 'Menunggu Verifikasi', 'status_tone' => 'warning'],
                ],
            ],
        ];

        $type = request()->query('type', 'rekap-layanan-bk');
        if (! array_key_exists($type, $reportConfigs)) {
            $type = 'rekap-layanan-bk';
        }

        $config = $reportConfigs[$type];
        $reportTitle = $config['title'];
        $reportDesc = $config['desc'];
        $stats = $config['stats'];
        $filters = $config['filters'];
        $columns = $config['columns'];
        $rows = $config['rows'];

        return view('pages.reports.preview', compact('type', 'reportTitle', 'reportDesc', 'stats', 'filters', 'columns', 'rows'));
    })->name('reports.preview');

    // ==========================================================================
    // Paket 400: Pengelolaan
    // ==========================================================================

    // PG-401/PG-402: Penugasan kelas berbasis histori dan periode efektif.
    Route::get('/assignments/classes', [AssignmentController::class, 'index'])->name('assignments.classes.index');
    Route::get('/assignments/classes/manage', [AssignmentController::class, 'manage'])->name('assignments.classes.manage');
    Route::post('/assignments/classes', [AssignmentController::class, 'storeClassAssignment'])->name('assignments.classes.store');

    // PG-403: Penugasan atau Pengalihan Kasus
    Route::get('/_preview/assignments/cases', function () {
        $cases = [
            ['no' => 'K-014', 'student' => 'Murid A', 'class' => 'X RPL 1', 'status' => 'Dalam Penanganan'],
            ['no' => 'K-013', 'student' => 'Murid B', 'class' => 'X RPL 2', 'status' => 'Baru'],
            ['no' => 'K-011', 'student' => 'Murid D', 'class' => 'X RPL 1', 'status' => 'Dalam Penanganan'],
        ];
        $counselors = ['Guru BK A', 'Guru BK B', 'Guru BK C', 'Guru BK D'];
        $caseNo = request()->query('case_no', 'K-014');
        $selectedCase = collect($cases)->firstWhere('no', $caseNo) ?? $cases[0];

        return redirect()->route('assignments.cases.index', ['case_no' => $caseNo]);
    })->name('fixtures.assignments.cases.index');

    Route::get('/assignments/cases', [AssignmentController::class, 'caseIndex'])->name('assignments.cases.index');

    // PG-404: Daftar Koreksi Data
    Route::get('/_preview/corrections', function () {
        return redirect()->route('corrections.index');

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
    })->name('fixtures.corrections.index');

    // Form Pengajuan Koreksi Data
    Route::get('/_preview/corrections/create', function () {
        return redirect()->route('corrections.create');

        $objectType = request()->query('object_type', 'Kasus');
        $objectId = request()->query('object_id', 'K-014');
        $studentName = request()->query('student', 'Murid A');
        $attribute = request()->query('attribute', 'Bidang Layanan');
        $oldValue = request()->query('old_value', 'Pribadi');

        return view('pages.corrections.create', compact('objectType', 'objectId', 'studentName', 'attribute', 'oldValue'));
    })->name('fixtures.corrections.create');

    // PG-405: Detail dan Verifikasi Koreksi
    Route::get('/_preview/corrections/show', function () {
        return redirect()->route('corrections.index');

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
    })->name('fixtures.corrections.show');

    // PG-406: Riwayat Perubahan
    Route::get('/_preview/history', function () {
        return redirect()->route('history.index');

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
    })->name('fixtures.history.index');

    Route::get('/corrections', [CorrectionController::class, 'index'])->name('corrections.index');
    Route::get('/corrections/create', [CorrectionController::class, 'create'])->name('corrections.create');
    Route::post('/corrections', [CorrectionController::class, 'store'])->name('corrections.store');
    Route::get('/corrections/{correction}', [CorrectionController::class, 'show'])->name('corrections.show');
    Route::post('/corrections/{correction}/verify', [CorrectionController::class, 'verify'])->name('corrections.verify');
    Route::post('/corrections/{correction}/process-master', [CorrectionController::class, 'processMaster'])->name('corrections.process-master');
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

    Route::get('/achievements/create', function () {
        return view('pages.achievements.create');
    })->name('achievements.create');

    Route::get('/data-master', [DataMasterController::class, 'index'])->name('data-master.index');
    Route::post('/data-master/dapodik/sync', [DataMasterController::class, 'synchronize'])->name('data-master.dapodik.sync');
    Route::post('/data-master/etatib/sync', [DataMasterController::class, 'synchronizeEtatib'])->name('data-master.etatib.sync');

    // PG-901: Akses Ditolak
    Route::get('/access-denied', function () {
        return view('pages.system.access-denied');
    })->name('access.denied');

});
