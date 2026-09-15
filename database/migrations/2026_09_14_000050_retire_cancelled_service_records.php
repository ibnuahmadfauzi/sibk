<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $cancelledIds = DB::table('references')
            ->whereIn('category', ['case_status', 'consultation_status'])
            ->where('code', 'dibatalkan')
            ->pluck('id');

        DB::table('cases')->whereIn('status_id', $cancelledIds)->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);
        DB::table('consultations')->whereIn('status_id', $cancelledIds)->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);
        DB::table('references')->whereIn('id', $cancelledIds)
            ->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Forward-only: arsip lama tidak dapat dibedakan dari hasil konversi secara aman.
    }
};
