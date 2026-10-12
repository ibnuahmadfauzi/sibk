<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('academic_years')->where('is_active', true)->count() > 1) {
            throw new RuntimeException('Ada lebih dari satu tahun ajaran aktif. Periksa dan tentukan satu tahun aktif sebelum migrasi.');
        }

        match (DB::getDriverName()) {
            'sqlite' => DB::statement('CREATE UNIQUE INDEX academic_years_one_active ON academic_years (is_active) WHERE is_active = 1'),
            'pgsql' => DB::statement('CREATE UNIQUE INDEX academic_years_one_active ON academic_years (is_active) WHERE is_active = true'),
            'mysql', 'mariadb' => $this->addMysqlIndex(),
            default => throw new RuntimeException('Database ini belum mendukung batas satu tahun ajaran aktif.'),
        };
    }

    public function down(): void
    {
        DB::statement('DROP INDEX academic_years_one_active'.(in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) ? ' ON academic_years' : ''));
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE academic_years DROP COLUMN active_unique_key');
        }
    }

    private function addMysqlIndex(): void
    {
        DB::statement('ALTER TABLE academic_years ADD COLUMN active_unique_key TINYINT GENERATED ALWAYS AS (CASE WHEN is_active = 1 THEN 1 ELSE NULL END) STORED');
        DB::statement('CREATE UNIQUE INDEX academic_years_one_active ON academic_years (active_unique_key)');
    }
};
