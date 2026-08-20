<?php

declare(strict_types=1);

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\Admin\DataMasterController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\CaseCoordinationController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// Route kompatibilitas pratinjau dipertahankan sementara dan diarahkan ke endpoint database.

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

    Route::redirect('/_preview/dashboard', '/dashboard')->name('fixtures.dashboard');
    Route::redirect('/_preview/notifications', '/notifications')->name('fixtures.notifications');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.preview');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.preview');
    Route::get('/notifications/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

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

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/preview', [ReportController::class, 'preview'])->name('reports.preview');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
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

    Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');
    Route::get('/achievements/create', [AchievementController::class, 'create'])->name('achievements.create');
    Route::post('/achievements', [AchievementController::class, 'store'])->name('achievements.store');
    Route::get('/achievements/{achievement}', [AchievementController::class, 'show'])->name('achievements.show');
    Route::get('/achievements/{achievement}/edit', [AchievementController::class, 'edit'])->name('achievements.edit');
    Route::patch('/achievements/{achievement}', [AchievementController::class, 'update'])->name('achievements.update');
    Route::post('/achievements/{achievement}/verify', [AchievementController::class, 'verify'])->name('achievements.verify');

    Route::get('/data-master', [DataMasterController::class, 'index'])->name('data-master.index');
    Route::post('/data-master/dapodik/sync', [DataMasterController::class, 'synchronize'])->name('data-master.dapodik.sync');
    Route::post('/data-master/etatib/sync', [DataMasterController::class, 'synchronizeEtatib'])->name('data-master.etatib.sync');

    // PG-901: Akses Ditolak
    Route::get('/access-denied', function () {
        return view('pages.system.access-denied');
    })->name('access.denied');

});
