<?php

/*
|--------------------------------------------------------------------------
| Synthetic frontend preview data
|--------------------------------------------------------------------------
| This file supports the wireframe-to-UI stage only. It is not an API or a
| final backend contract and must never contain real student information.
*/

return [
    'dashboard' => [
        'years' => ['2026/2027', '2025/2026'],
        'roles' => [
            'guru' => [
                'label'       => 'Guru BK',
                'user_name'   => 'Pengguna Demo',
                'scope'       => 'Kelas X AKL 1, X AKL 2, dan penugasan khusus',
                'read_only'   => false,
                'description' => 'Ringkasan kasus dan tindak lanjut dalam cakupan Anda.',
                'stats' => [
                    ['label' => 'Murid dalam cakupan',   'value' => '72', 'delta' => '+3 dari bulan lalu',  'delta_tone' => 'up',   'tone' => 'primary',  'kind' => 'students'],
                    ['label' => 'Kasus aktif',            'value' => '7',  'delta' => '-2 dari bulan lalu',  'delta_tone' => 'down', 'tone' => 'warning',  'kind' => 'cases'],
                    ['label' => 'Tindak lanjut terdekat', 'value' => '5',  'delta' => '+1 dari bulan lalu',  'delta_tone' => 'up',   'tone' => 'success',  'kind' => 'schedule'],
                    ['label' => 'Data e-Tatib terkait',   'value' => '24', 'delta' => '+4 dari bulan lalu',  'delta_tone' => 'up',   'tone' => 'info',     'kind' => 'etatib'],
                ],
                'tindak_lanjut' => [
                    ['date' => '11', 'month' => 'Agu', 'year' => '2026', 'code' => 'K-001', 'title' => 'Konsultasi lanjutan', 'student' => 'Murid A (X AKL 1)',        'status' => 'Dalam penanganan', 'status_tone' => 'info'],
                    ['date' => '12', 'month' => 'Agu', 'year' => '2026', 'code' => 'K-002', 'title' => 'Home visit',          'student' => 'Murid B (X AKL 2)',        'status' => 'Menunggu',         'status_tone' => 'warning'],
                    ['date' => '14', 'month' => 'Agu', 'year' => '2026', 'code' => 'K-006', 'title' => 'Verifikasi hasil',   'student' => 'Murid C (X AKL 1)',        'status' => 'Selesai',          'status_tone' => 'success'],
                ],
                'activities' => [
                    ['icon' => 'case-new',    'title' => 'Kasus baru ditambahkan',  'context' => 'K-006 - Verifikasi hasil',   'time' => '10:24', 'tone' => 'primary'],
                    ['icon' => 'followup',   'title' => 'Tindak lanjut diperbarui', 'context' => 'K-005 - Sesi konseling',    'time' => '09:15', 'tone' => 'success'],
                    ['icon' => 'etatib',     'title' => 'Data e-Tatib diperbarui',  'context' => 'Pelanggaran kelas IX',       'time' => '08:47', 'tone' => 'warning'],
                    ['icon' => 'report',     'title' => 'Laporan baru dibuat',       'context' => 'Laporan bulanan Juli 2026',  'time' => '08:20', 'tone' => 'info'],
                ],
                // Data lama dipertahankan untuk referensi state
                'priority_cases' => [
                    ['code' => 'K-026', 'student' => 'Murid A', 'class' => 'X AKL 1',        'status' => 'Belum ditindaklanjuti',   'updated' => 'Diperbarui 2 hari lalu', 'tone' => 'danger'],
                    ['code' => 'K-031', 'student' => 'Murid B', 'class' => 'X AKL 2',        'status' => 'Jadwal perlu ditetapkan', 'updated' => 'Diperbarui kemarin',     'tone' => 'warning'],
                    ['code' => 'K-034', 'student' => 'Murid C', 'class' => 'Penugasan khusus','status' => 'Dalam penanganan',        'updated' => 'Diperbarui hari ini',    'tone' => 'info'],
                ],
                'schedule' => [
                    ['date' => '18', 'month' => 'Agu', 'time' => '09.00', 'title' => 'Tindak lanjut kasus K-026', 'context' => 'Ruang BK · Murid A'],
                    ['date' => '19', 'month' => 'Agu', 'time' => '10.30', 'title' => 'Konsultasi terjadwal',       'context' => 'Ruang Konseling · Murid D'],
                    ['date' => '21', 'month' => 'Agu', 'time' => '13.00', 'title' => 'Koordinasi internal',        'context' => 'Ruang BK · Kasus K-034'],
                ],
                'violations' => [
                    ['label' => 'Kedisiplinan', 'value' => 11, 'percent' => 46],
                    ['label' => 'Kerapian',     'value' => 8,  'percent' => 33],
                    ['label' => 'Kehadiran',    'value' => 5,  'percent' => 21],
                ],
                'classes' => [
                    ['label' => 'X AKL 1',        'value' => 10],
                    ['label' => 'X AKL 2',        'value' => 8],
                    ['label' => 'Penugasan khusus','value' => 6],
                ],
            ],
            'koordinator' => [
                'label'       => 'Koordinator BK',
                'user_name'   => 'Koordinator Demo',
                'scope'       => 'Rekap seluruh Guru BK aktif dan penugasan yang sah',
                'read_only'   => false,
                'description' => 'Ringkasan kasus dan tindak lanjut dalam cakupan Anda.',
                'stats' => [
                    ['label' => 'Murid dalam cakupan',   'value' => '486', 'delta' => '+12 dari bulan lalu', 'delta_tone' => 'up',   'tone' => 'primary', 'kind' => 'students'],
                    ['label' => 'Kasus aktif',            'value' => '34',  'delta' => '+6 dari bulan lalu',  'delta_tone' => 'up',   'tone' => 'warning', 'kind' => 'cases'],
                    ['label' => 'Tindak lanjut terdekat', 'value' => '18',  'delta' => '+3 dari bulan lalu',  'delta_tone' => 'up',   'tone' => 'success', 'kind' => 'schedule'],
                    ['label' => 'Data e-Tatib terkait',   'value' => '91',  'delta' => '-5 dari bulan lalu',  'delta_tone' => 'down', 'tone' => 'info',    'kind' => 'etatib'],
                ],
                'tindak_lanjut' => [
                    ['date' => '11', 'month' => 'Agu', 'year' => '2026', 'code' => 'K-019', 'title' => 'Review pembagian kasus',  'student' => 'Murid E (XI MPLB 1)',  'status' => 'Dalam penanganan', 'status_tone' => 'info'],
                    ['date' => '12', 'month' => 'Agu', 'year' => '2026', 'code' => 'K-022', 'title' => 'Koordinasi lanjutan',     'student' => 'Murid F (XII AKL 2)', 'status' => 'Menunggu',         'status_tone' => 'warning'],
                    ['date' => '14', 'month' => 'Agu', 'year' => '2026', 'code' => 'K-041', 'title' => 'Evaluasi layanan',        'student' => 'Murid G (X PM 1)',    'status' => 'Selesai',          'status_tone' => 'success'],
                ],
                'activities' => [
                    ['icon' => 'case-new',  'title' => 'Penugasan kasus diperbarui',  'context' => 'K-041 - Evaluasi layanan',       'time' => '11:05', 'tone' => 'primary'],
                    ['icon' => 'followup', 'title' => 'Koordinasi baru dicatat',       'context' => 'K-022 - Koordinasi lanjutan',   'time' => '09:20', 'tone' => 'warning'],
                    ['icon' => 'report',   'title' => 'Rekap layanan diperbarui',      'context' => 'Periode Agustus 2026',           'time' => '08:45', 'tone' => 'success'],
                    ['icon' => 'etatib',   'title' => 'Data e-Tatib tersinkron',       'context' => '91 catatan dalam cakupan',       'time' => '07:30', 'tone' => 'info'],
                ],
                'priority_cases' => [
                    ['code' => 'K-019', 'student' => 'Murid E', 'class' => 'XI MPLB 1', 'status' => 'Belum ada penanggung jawab', 'updated' => 'Diperbarui 3 hari lalu', 'tone' => 'danger'],
                    ['code' => 'K-022', 'student' => 'Murid F', 'class' => 'XII AKL 2', 'status' => 'Perlu koordinasi',           'updated' => 'Diperbarui kemarin',     'tone' => 'warning'],
                    ['code' => 'K-041', 'student' => 'Murid G', 'class' => 'X PM 1',    'status' => 'Dalam penanganan',           'updated' => 'Diperbarui hari ini',    'tone' => 'info'],
                ],
                'schedule' => [
                    ['date' => '18', 'month' => 'Agu', 'time' => '08.00', 'title' => 'Review pembagian kasus aktif',  'context' => 'Ruang BK · Tim Guru BK'],
                    ['date' => '20', 'month' => 'Agu', 'time' => '10.00', 'title' => 'Koordinasi kasus K-022',        'context' => 'Ruang Rapat · Guru BK terkait'],
                    ['date' => '22', 'month' => 'Agu', 'time' => '13.30', 'title' => 'Evaluasi layanan mingguan',     'context' => 'Ruang BK · Tim Guru BK'],
                ],
                'violations' => [
                    ['label' => 'Kedisiplinan', 'value' => 40, 'percent' => 44],
                    ['label' => 'Kehadiran',    'value' => 29, 'percent' => 32],
                    ['label' => 'Kerapian',     'value' => 22, 'percent' => 24],
                ],
                'classes' => [
                    ['label' => 'Kelas X',   'value' => 36],
                    ['label' => 'Kelas XI',  'value' => 31],
                    ['label' => 'Kelas XII', 'value' => 24],
                ],
            ],
            'waka' => [
                'label'       => 'Waka Kesiswaan',
                'user_name'   => 'Waka Demo',
                'scope'       => 'Agregat yang diizinkan dan kasus yang dikoordinasikan',
                'read_only'   => true,
                'description' => 'Ringkasan koordinasi kesiswaan tanpa membuka catatan konsultasi atau catatan internal.',
                'stats' => [
                    ['label' => 'Murid pada agregat',    'value' => '486', 'delta' => 'Tanpa rincian sensitif', 'delta_tone' => 'neutral', 'tone' => 'primary', 'kind' => 'students'],
                    ['label' => 'Kasus terkoordinasi',   'value' => '4',   'delta' => 'Seluruhnya hanya-baca',  'delta_tone' => 'neutral', 'tone' => 'warning', 'kind' => 'cases'],
                    ['label' => 'Tindak lanjut terkait', 'value' => '3',   'delta' => 'Dalam 7 hari terakhir',  'delta_tone' => 'neutral', 'tone' => 'success', 'kind' => 'schedule'],
                    ['label' => 'Pelanggaran agregat',   'value' => '91',  'delta' => 'Sinkron 15 Agu 07.30',   'delta_tone' => 'neutral', 'tone' => 'info',    'kind' => 'etatib'],
                ],
                'tindak_lanjut' => [
                    ['date' => '12', 'month' => 'Agu', 'year' => '2026', 'code' => 'K-012', 'title' => 'Koordinasi kesiswaan',  'student' => 'Murid H (XI AKL 1)', 'status' => 'Menunggu',         'status_tone' => 'warning'],
                    ['date' => '20', 'month' => 'Agu', 'year' => '2026', 'code' => 'K-022', 'title' => 'Koordinasi aktif',      'student' => 'Murid F (XII AKL 2)','status' => 'Dalam penanganan', 'status_tone' => 'info'],
                ],
                'activities' => [
                    ['icon' => 'followup', 'title' => 'Koordinasi diperbarui',         'context' => 'K-022 - Koordinasi aktif',     'time' => '09:20', 'tone' => 'warning'],
                    ['icon' => 'report',   'title' => 'Ringkasan agregat tersedia',     'context' => 'Periode Agustus 2026',          'time' => '08:00', 'tone' => 'success'],
                    ['icon' => 'etatib',   'title' => 'Data e-Tatib tersinkron',        'context' => '91 catatan dalam agregat',      'time' => '07:30', 'tone' => 'info'],
                ],
                'priority_cases' => [
                    ['code' => 'K-012', 'student' => 'Murid H', 'class' => 'XI AKL 1',  'status' => 'Menunggu tanggapan', 'updated' => 'Dikoordinasikan 14 Agu', 'tone' => 'warning'],
                    ['code' => 'K-022', 'student' => 'Murid F', 'class' => 'XII AKL 2', 'status' => 'Koordinasi aktif',  'updated' => 'Diperbarui hari ini',    'tone' => 'info'],
                ],
                'schedule' => [
                    ['date' => '20', 'month' => 'Agu', 'time' => '10.00', 'title' => 'Koordinasi kasus K-022',       'context' => 'Ruang Rapat · Koordinator BK'],
                    ['date' => '25', 'month' => 'Agu', 'time' => '09.30', 'title' => 'Tinjauan ringkasan kesiswaan', 'context' => 'Ruang Waka · Koordinator BK'],
                ],
                'violations' => [
                    ['label' => 'Kedisiplinan', 'value' => 40, 'percent' => 44],
                    ['label' => 'Kehadiran',    'value' => 29, 'percent' => 32],
                    ['label' => 'Kerapian',     'value' => 22, 'percent' => 24],
                ],
                'classes' => [
                    ['label' => 'Kelas X',   'value' => 36],
                    ['label' => 'Kelas XI',  'value' => 31],
                    ['label' => 'Kelas XII', 'value' => 24],
                ],
            ],
        ],
    ],
];
