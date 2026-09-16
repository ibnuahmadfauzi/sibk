<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class OperationalSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_schema_keeps_required_sources_of_truth(): void
    {
        foreach ([
            'students',
            'student_departures',
            'cases',
            'consultations',
            'sessions',
            'cache',
            'cache_locks',
            'audit_logs',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Tabel wajib {$table} hilang.");
        }

        $this->assertTrue(Schema::hasColumns('student_departures', [
            'student_id',
            'departure_type',
            'status',
            'effective_date',
        ]));

        $studentForeignKey = collect(Schema::getForeignKeys('student_departures'))
            ->firstWhere('columns', ['student_id']);
        $this->assertNotNull($studentForeignKey);
        $this->assertSame('students', $studentForeignKey['foreign_table']);

        $studentIndex = collect(Schema::getIndexes('student_departures'))
            ->firstWhere('columns', ['student_id']);
        $this->assertNotNull($studentIndex);
        $this->assertTrue($studentIndex['unique']);
    }

    public function test_schema_does_not_duplicate_departure_or_recap_state(): void
    {
        foreach (['dashboard_recaps', 'report_recaps', 'student_exit_statuses'] as $table) {
            $this->assertFalse(Schema::hasTable($table), "Tabel duplikat {$table} tidak boleh ada.");
        }

        foreach (['departure_status', 'departure_date', 'is_departed'] as $column) {
            $this->assertFalse(
                Schema::hasColumn('students', $column),
                "Kolom status keluar duplikat students.{$column} tidak boleh ada.",
            );
        }
    }
}
