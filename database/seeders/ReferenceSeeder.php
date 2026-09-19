<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ReferenceValue;
use App\Support\ServiceRecordStatus;
use Illuminate\Database\Seeder;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $values = $this->serviceStatusValues();
        $values = [...$values,
            ['category' => 'case_source', 'code' => 'e_tatib', 'label' => 'e-Tatib', 'sort_order' => 10],
            ['category' => 'case_source', 'code' => 'murid_datang_sendiri', 'label' => 'Murid datang sendiri', 'sort_order' => 20],
            ['category' => 'case_source', 'code' => 'temuan_guru_bk', 'label' => 'Temuan Guru BK', 'sort_order' => 30],
            ['category' => 'case_source', 'code' => 'rujukan', 'label' => 'Rujukan', 'sort_order' => 40],
            ['category' => 'service_field', 'code' => 'pribadi', 'label' => 'Pribadi', 'sort_order' => 10],
            ['category' => 'service_field', 'code' => 'belajar', 'label' => 'Belajar', 'sort_order' => 20],
            ['category' => 'service_field', 'code' => 'sosial', 'label' => 'Sosial', 'sort_order' => 30],
            ['category' => 'service_field', 'code' => 'karier', 'label' => 'Karier', 'sort_order' => 40],
            ['category' => 'follow_up_type', 'code' => 'surat_panggilan_orang_tua', 'label' => 'Surat Panggilan Orang Tua', 'sort_order' => 10],
            ['category' => 'follow_up_type', 'code' => 'surat_pernyataan', 'label' => 'Surat Pernyataan', 'sort_order' => 20],
            ['category' => 'follow_up_type', 'code' => 'home_visit', 'label' => 'Home Visit', 'sort_order' => 30],
            ['category' => 'follow_up_type', 'code' => 'pengunduran_diri', 'label' => 'Pengunduran Diri', 'sort_order' => 40],
            ['category' => 'follow_up_status', 'code' => 'terjadwal', 'label' => 'Rencana / Terjadwal', 'sort_order' => 10],
            ['category' => 'follow_up_status', 'code' => 'terlaksana', 'label' => 'Terlaksana', 'sort_order' => 20],
            ['category' => 'follow_up_status', 'code' => 'ditunda', 'label' => 'Ditunda', 'sort_order' => 30],
            ['category' => 'follow_up_status', 'code' => 'dibatalkan', 'label' => 'Dibatalkan', 'sort_order' => 40],
            ['category' => 'coordination_status', 'code' => 'menunggu', 'label' => 'Menunggu', 'sort_order' => 10],
            ['category' => 'coordination_status', 'code' => 'selesai', 'label' => 'Selesai', 'sort_order' => 20],
            ['category' => 'coordination_status', 'code' => 'dibatalkan', 'label' => 'Dibatalkan', 'sort_order' => 30],
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

        ReferenceValue::query()
            ->whereIn('category', ['consultation_status', 'follow_up_status', 'coordination_status'])
            ->update(['is_active' => false]);

        ReferenceValue::query()
            ->forCategory('case_status')
            ->whereNotIn('code', ServiceRecordStatus::codes())
            ->update(['is_active' => false]);

        ReferenceValue::query()
            ->forCategory('follow_up_type')
            ->whereNotIn('code', [
                'surat_panggilan_orang_tua',
                'surat_pernyataan',
                'home_visit',
                'pengunduran_diri',
            ])
            ->update(['is_active' => false]);
    }

    /** @return list<array{category: string, code: string, label: string, sort_order: int}> */
    private function serviceStatusValues(): array
    {
        $values = [];

        foreach (['case_status', 'consultation_status'] as $category) {
            foreach (ServiceRecordStatus::codes() as $index => $code) {
                $values[] = [
                    'category' => $category,
                    'code' => $code,
                    'label' => ServiceRecordStatus::label($code) ?? $code,
                    'sort_order' => ($index + 1) * 10,
                ];
            }
        }

        return $values;
    }
}
