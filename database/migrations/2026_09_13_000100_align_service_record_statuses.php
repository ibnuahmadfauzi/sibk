<?php

declare(strict_types=1);

use App\Support\ServiceRecordStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->alignCategory(
                category: 'case_status',
                recordTable: 'cases',
                legacyMappings: [
                    'dalam_penanganan' => ServiceRecordStatus::IN_PROGRESS,
                ],
            );
            $this->alignCategory(
                category: 'consultation_status',
                recordTable: 'consultations',
                legacyMappings: [
                    'dijadwalkan' => ServiceRecordStatus::NEW,
                    'menunggu_konfirmasi' => ServiceRecordStatus::IN_PROGRESS,
                    'terlaksana' => ServiceRecordStatus::COMPLETED,
                ],
            );
        });
    }

    public function down(): void
    {
        // Status baru dapat memuat data yang tidak bisa dikembalikan ke status lama tanpa kehilangan makna.
    }

    /** @param array<string, string> $legacyMappings */
    private function alignCategory(string $category, string $recordTable, array $legacyMappings): void
    {
        foreach ($legacyMappings as $legacyCode => $targetCode) {
            $legacy = DB::table('references')
                ->where('category', $category)
                ->where('code', $legacyCode)
                ->first();

            if ($legacy === null) {
                continue;
            }

            $target = DB::table('references')
                ->where('category', $category)
                ->where('code', $targetCode)
                ->first();

            if ($target === null) {
                DB::table('references')->where('id', $legacy->id)->update([
                    'code' => $targetCode,
                    ...$this->definition($targetCode),
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table($recordTable)
                ->where('status_id', $legacy->id)
                ->update(['status_id' => $target->id]);
            DB::table('references')->where('id', $legacy->id)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
        }

        foreach (ServiceRecordStatus::codes() as $code) {
            $existing = DB::table('references')
                ->where('category', $category)
                ->where('code', $code)
                ->first();
            $definition = $this->definition($code);

            if ($existing === null) {
                DB::table('references')->insert([
                    'category' => $category,
                    'code' => $code,
                    ...$definition,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('references')->where('id', $existing->id)->update([
                ...$definition,
                'is_active' => true,
                'updated_at' => now(),
            ]);
        }
    }

    /** @return array{label: string, sort_order: int} */
    private function definition(string $code): array
    {
        $position = array_search($code, ServiceRecordStatus::codes(), true);

        return [
            'label' => ServiceRecordStatus::label($code) ?? $code,
            'sort_order' => (($position === false ? 0 : $position) + 1) * 10,
        ];
    }
};
