<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['cases', 'consultations'] as $table) {
            if (DB::table($table)
                ->whereRaw('(student_id IS NULL) = (temporary_student_id IS NULL)')
                ->exists()) {
                throw new RuntimeException("Identitas ganda atau kosong pada {$table} harus ditinjau sebelum migrasi.");
            }
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('Constraint identitas layanan memerlukan MySQL.');
        }

        foreach (['cases', 'consultations'] as $table) {
            $exists = DB::selectOne(
                'SELECT COUNT(*) AS total FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ?',
                [$table, "{$table}_identity_xor_check"],
            );
            if ((int) $exists->total > 0) {
                continue;
            }
            DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$table}_identity_xor_check` CHECK ((student_id IS NOT NULL AND temporary_student_id IS NULL) OR (student_id IS NULL AND temporary_student_id IS NOT NULL))");
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Migrasi identitas layanan bersifat forward-only.');
    }
};
