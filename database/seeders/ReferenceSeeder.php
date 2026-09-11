<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ReferenceValue;
use Illuminate\Database\Seeder;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $values = [
            ['category' => 'case_status', 'code' => 'baru', 'label' => 'Baru', 'sort_order' => 10],
            ['category' => 'case_status', 'code' => 'dalam_penanganan', 'label' => 'Dalam Penanganan', 'sort_order' => 20],
            ['category' => 'case_status', 'code' => 'selesai', 'label' => 'Selesai', 'sort_order' => 30],
            ['category' => 'case_status', 'code' => 'dibatalkan', 'label' => 'Dibatalkan', 'sort_order' => 40],
            ['category' => 'case_source', 'code' => 'e_tatib', 'label' => 'e-Tatib', 'sort_order' => 10],
            ['category' => 'case_source', 'code' => 'murid_datang_sendiri', 'label' => 'Murid datang sendiri', 'sort_order' => 20],
            ['category' => 'case_source', 'code' => 'temuan_guru_bk', 'label' => 'Temuan Guru BK', 'sort_order' => 30],
            ['category' => 'case_source', 'code' => 'rujukan', 'label' => 'Rujukan', 'sort_order' => 40],
            ['category' => 'service_field', 'code' => 'pribadi', 'label' => 'Pribadi', 'sort_order' => 10],
            ['category' => 'service_field', 'code' => 'belajar', 'label' => 'Belajar', 'sort_order' => 20],
            ['category' => 'service_field', 'code' => 'sosial', 'label' => 'Sosial', 'sort_order' => 30],
            ['category' => 'service_field', 'code' => 'karier', 'label' => 'Karier', 'sort_order' => 40],
            ['category' => 'follow_up_type', 'code' => 'konsultasi_individual', 'label' => 'Konsultasi Individual', 'sort_order' => 10],
            ['category' => 'follow_up_type', 'code' => 'panggilan_orang_tua', 'label' => 'Panggilan Orang Tua', 'sort_order' => 20],
            ['category' => 'follow_up_type', 'code' => 'bimbingan_kelompok', 'label' => 'Bimbingan Kelompok', 'sort_order' => 30],
            ['category' => 'follow_up_type', 'code' => 'kunjungan_rumah', 'label' => 'Kunjungan Rumah (Home Visit)', 'sort_order' => 40],
            ['category' => 'follow_up_type', 'code' => 'koordinasi_guru', 'label' => 'Koordinasi Wali Kelas / Guru Mapel', 'sort_order' => 50],
            ['category' => 'follow_up_type', 'code' => 'konferensi_kasus', 'label' => 'Konferensi Kasus', 'sort_order' => 60],
            ['category' => 'follow_up_type', 'code' => 'alih_tangan_kasus', 'label' => 'Alih Tangan Kasus (Referral)', 'sort_order' => 70],
            ['category' => 'follow_up_status', 'code' => 'terjadwal', 'label' => 'Rencana / Terjadwal', 'sort_order' => 10],
            ['category' => 'follow_up_status', 'code' => 'terlaksana', 'label' => 'Terlaksana', 'sort_order' => 20],
            ['category' => 'follow_up_status', 'code' => 'ditunda', 'label' => 'Ditunda', 'sort_order' => 30],
            ['category' => 'follow_up_status', 'code' => 'dibatalkan', 'label' => 'Dibatalkan', 'sort_order' => 40],
            ['category' => 'coordination_status', 'code' => 'menunggu', 'label' => 'Menunggu', 'sort_order' => 10],
            ['category' => 'coordination_status', 'code' => 'selesai', 'label' => 'Selesai', 'sort_order' => 20],
            ['category' => 'coordination_status', 'code' => 'dibatalkan', 'label' => 'Dibatalkan', 'sort_order' => 30],
            ['category' => 'consultation_status', 'code' => 'dijadwalkan', 'label' => 'Dijadwalkan', 'sort_order' => 10],
            ['category' => 'consultation_status', 'code' => 'menunggu_konfirmasi', 'label' => 'Menunggu Konfirmasi', 'sort_order' => 20],
            ['category' => 'consultation_status', 'code' => 'terlaksana', 'label' => 'Terlaksana', 'sort_order' => 30],
            ['category' => 'consultation_status', 'code' => 'dibatalkan', 'label' => 'Dibatalkan', 'sort_order' => 40],
            ['category' => 'correction_status', 'code' => 'menunggu', 'label' => 'Menunggu', 'sort_order' => 10],
            ['category' => 'correction_status', 'code' => 'diproses', 'label' => 'Diproses', 'sort_order' => 20],
            ['category' => 'correction_status', 'code' => 'disetujui', 'label' => 'Disetujui', 'sort_order' => 30],
            ['category' => 'correction_status', 'code' => 'ditolak', 'label' => 'Ditolak', 'sort_order' => 40],
            ['category' => 'correction_status', 'code' => 'perlu_perbaikan', 'label' => 'Perlu Perbaikan', 'sort_order' => 50],
            ['category' => 'correction_status', 'code' => 'selesai', 'label' => 'Selesai', 'sort_order' => 60],
            ['category' => 'reconciliation_status', 'code' => 'menunggu_rekonsiliasi', 'label' => 'Menunggu Rekonsiliasi', 'sort_order' => 10],
            ['category' => 'reconciliation_status', 'code' => 'terekonsiliasi', 'label' => 'Terekonsiliasi', 'sort_order' => 20],
            ['category' => 'reconciliation_status', 'code' => 'ditahan_konflik', 'label' => 'Ditahan karena Konflik', 'sort_order' => 30],
            ['category' => 'achievement_type', 'code' => 'akademik', 'label' => 'Akademik', 'sort_order' => 10],
            ['category' => 'achievement_type', 'code' => 'olahraga', 'label' => 'Olahraga', 'sort_order' => 20],
            ['category' => 'achievement_type', 'code' => 'seni_budaya', 'label' => 'Seni & Budaya', 'sort_order' => 30],
            ['category' => 'achievement_type', 'code' => 'karya_ilmiah', 'label' => 'Karya Ilmiah', 'sort_order' => 40],
            ['category' => 'achievement_type', 'code' => 'organisasi', 'label' => 'Organisasi', 'sort_order' => 50],
            ['category' => 'achievement_type', 'code' => 'lainnya', 'label' => 'Lainnya', 'sort_order' => 60],
            ['category' => 'achievement_level', 'code' => 'sekolah', 'label' => 'Sekolah', 'sort_order' => 10],
            ['category' => 'achievement_level', 'code' => 'kota_kabupaten', 'label' => 'Kota / Kabupaten', 'sort_order' => 20],
            ['category' => 'achievement_level', 'code' => 'provinsi', 'label' => 'Provinsi', 'sort_order' => 30],
            ['category' => 'achievement_level', 'code' => 'nasional', 'label' => 'Nasional', 'sort_order' => 40],
            ['category' => 'achievement_level', 'code' => 'internasional', 'label' => 'Internasional', 'sort_order' => 50],
            ['category' => 'achievement_verification_status', 'code' => 'menunggu', 'label' => 'Menunggu Verifikasi', 'sort_order' => 10],
            ['category' => 'achievement_verification_status', 'code' => 'terverifikasi', 'label' => 'Terverifikasi', 'sort_order' => 20],
            ['category' => 'achievement_verification_status', 'code' => 'ditolak', 'label' => 'Ditolak', 'sort_order' => 30],
        ];

        foreach ($values as $value) {
            ReferenceValue::query()->updateOrCreate(
                ['category' => $value['category'], 'code' => $value['code']],
                [...$value, 'is_active' => true],
            );
        }
    }
}
