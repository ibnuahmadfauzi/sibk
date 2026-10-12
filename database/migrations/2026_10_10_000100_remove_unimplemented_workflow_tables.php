<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['corrections', 'user_notifications'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Tabel {$table} berisi data; tinjau sebelum migrasi.");
            }
        }

        Schema::dropIfExists('corrections');
        Schema::dropIfExists('user_notifications');
    }

    public function down(): void
    {
        throw new RuntimeException('Migrasi pembersihan tabel workflow lama bersifat forward-only.');
    }
};
