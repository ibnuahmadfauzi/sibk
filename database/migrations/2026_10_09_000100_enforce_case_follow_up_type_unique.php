<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $latestType = '(SELECT f.follow_up_type_id FROM case_follow_ups f '
            .'WHERE f.case_id = cases.id AND f.deleted_at IS NULL '
            .'ORDER BY f.follow_up_date DESC, f.id DESC LIMIT 1)';

        if (DB::table('cases')
            ->whereRaw("COALESCE(cases.follow_up_type_id, 0) <> COALESCE($latestType, 0)")
            ->exists()) {
            throw new RuntimeException('Ringkasan tindak lanjut kasus tidak sesuai dengan riwayat; tinjau data sebelum migrasi.');
        }

        if (DB::table('case_follow_ups')
            ->select(['case_id', 'follow_up_type_id'])
            ->groupBy(['case_id', 'follow_up_type_id'])
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException('Jenis tindak lanjut ganda harus ditinjau sebelum migrasi.');
        }

        Schema::table('case_follow_ups', function (Blueprint $table): void {
            $table->timestamp('performed_at')->nullable();
            $table->unique(['case_id', 'follow_up_type_id'], 'case_follow_up_type_unique');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Migrasi tindak lanjut kasus bersifat forward-only.');
    }
};
