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
        $this->assertUnambiguousState();
        $caseSnapshots = $this->snapshots('cases', 'service_date');
        $consultationSnapshots = $this->snapshots('consultations', 'session_date');

        Schema::table('cases', function (Blueprint $table): void {
            $table->unsignedBigInteger('academic_year_id')->nullable()->index();
            $table->unsignedBigInteger('classroom_id')->nullable()->index();
        });
        Schema::table('consultations', function (Blueprint $table): void {
            $table->unsignedBigInteger('academic_year_id')->nullable()->index();
            $table->unsignedBigInteger('classroom_id')->nullable()->index();
        });
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('cases', function (Blueprint $table): void {
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->restrictOnDelete();
                $table->foreign('classroom_id')->references('id')->on('classrooms')->restrictOnDelete();
            });
            Schema::table('consultations', function (Blueprint $table): void {
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->restrictOnDelete();
                $table->foreign('classroom_id')->references('id')->on('classrooms')->restrictOnDelete();
            });
        }

        foreach ($caseSnapshots as $id => $snapshot) {
            DB::table('cases')->where('id', $id)->update($snapshot);
        }
        foreach ($consultationSnapshots as $id => $snapshot) {
            DB::table('consultations')->where('id', $id)->update($snapshot);
        }

        Schema::table('teacher_assignments', function (Blueprint $table): void {
            $table->dropIndex('teacher_assignment_period_index');
            $table->dropIndex('teacher_assignment_user_period_index');
            $table->dropColumn(['effective_from', 'effective_until', 'decision_number', 'notes', 'deleted_at']);
            $table->unique(['classroom_id', 'academic_year_id'], 'teacher_assignment_class_year_unique');
            $table->index(['user_id', 'academic_year_id'], 'teacher_assignment_user_year_index');
        });
        Schema::table('student_class_memberships', function (Blueprint $table): void {
            $table->dropUnique('student_class_membership_period_unique');
            $table->dropIndex('membership_class_period_student_index');
            $table->dropColumn(['effective_from', 'effective_until']);
            $table->unique(['student_id', 'academic_year_id'], 'student_membership_student_year_unique');
        });
        Schema::table('case_assignments', function (Blueprint $table): void {
            $table->dropIndex('case_assignment_period_index');
            $table->dropIndex('case_assignment_user_period_index');
            $table->dropColumn(['assignment_type', 'effective_from', 'effective_until', 'deleted_at']);
            $table->unique('case_id', 'case_assignment_case_unique');
        });
    }

    /** @return array<int, array{academic_year_id: int, classroom_id: int}> */
    private function snapshots(string $table, string $dateColumn): array
    {
        $snapshots = [];
        foreach (DB::table($table)->whereNotNull('student_id')->get(['id', 'student_id', $dateColumn]) as $record) {
            $matches = DB::table('student_class_memberships')
                ->join('academic_years', 'academic_years.id', '=', 'student_class_memberships.academic_year_id')
                ->where('student_class_memberships.student_id', $record->student_id)
                ->whereDate('student_class_memberships.effective_from', '<=', $record->{$dateColumn})
                ->where(function ($query) use ($record, $dateColumn): void {
                    $query->whereNull('student_class_memberships.effective_until')
                        ->orWhereDate('student_class_memberships.effective_until', '>=', $record->{$dateColumn});
                })
                ->where(function ($query) use ($record, $dateColumn): void {
                    $query->whereNull('academic_years.starts_on')
                        ->orWhereDate('academic_years.starts_on', '<=', $record->{$dateColumn});
                })
                ->where(function ($query) use ($record, $dateColumn): void {
                    $query->whereNull('academic_years.ends_on')
                        ->orWhereDate('academic_years.ends_on', '>=', $record->{$dateColumn});
                })
                ->get(['student_class_memberships.academic_year_id', 'student_class_memberships.classroom_id']);

            if ($matches->count() !== 1) {
                throw new RuntimeException("Snapshot {$table} #{$record->id} tidak dapat ditentukan secara tunggal.");
            }

            $snapshots[$record->id] = [
                'academic_year_id' => $matches[0]->academic_year_id,
                'classroom_id' => $matches[0]->classroom_id,
            ];
        }

        return $snapshots;
    }

    private function assertUnambiguousState(): void
    {
        if (DB::table('teacher_assignments')->whereNotNull('deleted_at')->exists()) {
            throw new RuntimeException('Penugasan terarsip harus ditinjau sebelum konversi state.');
        }
        if (DB::table('case_assignments')->whereNotNull('deleted_at')->exists()
            || DB::table('case_assignments')->where('assignment_type', '<>', 'owner')->exists()) {
            throw new RuntimeException('Owner kasus lama harus ditinjau sebelum konversi state.');
        }
        foreach ([
            ['teacher_assignments', ['classroom_id', 'academic_year_id']],
            ['student_class_memberships', ['student_id', 'academic_year_id']],
            ['case_assignments', ['case_id']],
        ] as [$table, $columns]) {
            if (DB::table($table)->select($columns)->groupBy($columns)->havingRaw('COUNT(*) > 1')->exists()) {
                throw new RuntimeException("Ada state ganda pada {$table}; tentukan state yang benar sebelum migrasi.");
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Migrasi state penugasan tidak dapat dibalik tanpa memulihkan data periode dari cadangan.');
    }
};
