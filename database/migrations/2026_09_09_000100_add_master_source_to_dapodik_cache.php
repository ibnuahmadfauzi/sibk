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
        Schema::table('academic_years', function (Blueprint $table): void {
            $table->string('master_source', 30)->default('legacy_unclassified')->index();
            $table->timestamp('source_confirmed_at')->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('preparation_reference', 500)->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
        });

        foreach (['classrooms', 'students', 'student_class_memberships'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('master_source', 30)->default('legacy_unclassified')->index();
                $table->timestamp('source_confirmed_at')->nullable();
            });
        }

        foreach (['academic_years', 'classrooms', 'students', 'student_class_memberships'] as $tableName) {
            DB::table($tableName)
                ->whereNotNull('dapodik_id')
                ->update(['master_source' => 'dapodik']);

            DB::table($tableName)
                ->whereNull('dapodik_id')
                ->update(['master_source' => 'legacy_unclassified']);
        }
    }

    public function down(): void
    {
        Schema::table('student_class_memberships', function (Blueprint $table): void {
            $table->dropColumn(['master_source', 'source_confirmed_at']);
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn(['master_source', 'source_confirmed_at']);
        });

        Schema::table('classrooms', function (Blueprint $table): void {
            $table->dropColumn(['master_source', 'source_confirmed_at']);
        });

        Schema::table('academic_years', function (Blueprint $table): void {
            $table->dropForeign(['prepared_by']);
            $table->dropForeign(['activated_by']);
            $table->dropColumn([
                'master_source',
                'source_confirmed_at',
                'prepared_by',
                'preparation_reference',
                'activated_by',
                'activated_at',
            ]);
        });
    }
};
