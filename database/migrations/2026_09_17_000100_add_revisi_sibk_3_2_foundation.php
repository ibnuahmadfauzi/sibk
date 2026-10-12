<?php

declare(strict_types=1);

use App\Support\ServiceRecordStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement(
                'ALTER TABLE "cases" ADD COLUMN "follow_up_type_id" INTEGER NULL '
                .'REFERENCES "references" ("id") ON DELETE RESTRICT',
            );
            Schema::table('cases', function (Blueprint $table): void {
                $table->index('follow_up_type_id');
            });
        } else {
            Schema::table('cases', function (Blueprint $table): void {
                $table->foreignId('follow_up_type_id')->nullable()->constrained('references')->restrictOnDelete();
                $table->index('follow_up_type_id');
            });
        }
        Schema::table('consultations', function (Blueprint $table): void {
            $table->text('problem')->nullable();
            $table->text('handling')->nullable();
            $table->text('result')->nullable();
        });

        DB::transaction(function (): void {
            $inProgressId = $this->upsertReference(
                'case_status',
                ServiceRecordStatus::IN_PROGRESS,
                'Sedang Proses',
                10,
                true,
            );
            $this->upsertReference(
                'case_status',
                ServiceRecordStatus::NEEDS_FOLLOW_UP,
                'Tindak Lanjut',
                20,
                true,
            );
            $this->upsertReference('case_status', ServiceRecordStatus::COMPLETED, 'Selesai', 30, true);

            $newStatusId = DB::table('references')
                ->where('category', 'case_status')
                ->where('code', ServiceRecordStatus::NEW)
                ->value('id');

            if ($newStatusId !== null) {
                DB::table('cases')->where('status_id', $newStatusId)->update(['status_id' => $inProgressId]);
            }

            DB::table('references')
                ->where('category', 'case_status')
                ->whereNotIn('code', ServiceRecordStatus::codes())
                ->update(['is_active' => false, 'updated_at' => now()]);
            DB::table('references')
                ->whereIn('category', ['consultation_status', 'follow_up_status', 'coordination_status'])
                ->update(['is_active' => false, 'updated_at' => now()]);

            $followUpTypes = [
                'surat_panggilan_orang_tua' => 'Surat Panggilan Orang Tua',
                'surat_pernyataan' => 'Surat Pernyataan',
                'home_visit' => 'Home Visit',
                'pengunduran_diri' => 'Pengunduran Diri',
            ];

            $sortOrder = 10;
            foreach ($followUpTypes as $code => $label) {
                $this->upsertReference('follow_up_type', $code, $label, $sortOrder, true);
                $sortOrder += 10;
            }

            DB::table('references')
                ->where('category', 'follow_up_type')
                ->whereNotIn('code', array_keys($followUpTypes))
                ->update(['is_active' => false, 'updated_at' => now()]);
        });
    }

    public function down(): void
    {
        // Migration fondasi bersifat forward-only untuk menjaga data operasional.
    }

    private function upsertReference(string $category, string $code, string $label, int $sortOrder, bool $isActive): int
    {
        $reference = DB::table('references')->where('category', $category)->where('code', $code)->first();

        if ($reference !== null) {
            DB::table('references')->where('id', $reference->id)->update([
                'label' => $label,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);

            return $reference->id;
        }

        return (int) DB::table('references')->insertGetId([
            'category' => $category,
            'code' => $code,
            'label' => $label,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
