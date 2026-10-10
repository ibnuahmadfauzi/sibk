<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['student_class_memberships', 'teacher_assignments', 'cases', 'consultations'];

        foreach ($tables as $table) {
            $invalid = DB::table("{$table} as records")
                ->join('classrooms', 'classrooms.id', '=', 'records.classroom_id')
                ->whereNotNull('records.academic_year_id')
                ->whereColumn('records.academic_year_id', '<>', 'classrooms.academic_year_id')
                ->exists();

            if ($invalid) {
                throw new RuntimeException("Kelas dan tahun ajaran pada {$table} tidak sesuai; periksa data sebelum migrasi.");
            }
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX student_class_memberships_student_id_academic_year_id_index');

            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('Constraint kelas dan tahun ajaran memerlukan MySQL.');
        }

        if (! $this->hasIndex('classrooms', 'classrooms_id_year_unique')) {
            DB::statement('ALTER TABLE `classrooms` ADD UNIQUE INDEX `classrooms_id_year_unique` (`id`, `academic_year_id`)');
        }

        foreach ($tables as $table) {
            $constraint = "{$table}_classroom_year_foreign";
            if (! $this->hasConstraint($table, $constraint)) {
                DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` FOREIGN KEY (`classroom_id`, `academic_year_id`) REFERENCES `classrooms` (`id`, `academic_year_id`) ON DELETE RESTRICT ON UPDATE RESTRICT");
            }
        }

        foreach ([
            ['student_class_memberships', 'student_class_memberships_student_id_academic_year_id_index'],
            ['cases', 'cases_classroom_id_index'],
            ['consultations', 'consultations_classroom_id_index'],
        ] as [$table, $index]) {
            if ($this->hasIndex($table, $index)) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Constraint kelas dan tahun ajaran bersifat forward-only.');
    }

    private function hasIndex(string $table, string $index): bool
    {
        return (int) DB::selectOne(
            'SELECT COUNT(*) AS total FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $index],
        )->total > 0;
    }

    private function hasConstraint(string $table, string $constraint): bool
    {
        return (int) DB::selectOne(
            'SELECT COUNT(*) AS total FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ? AND constraint_type = ?',
            [$table, $constraint, 'FOREIGN KEY'],
        )->total > 0;
    }
};
