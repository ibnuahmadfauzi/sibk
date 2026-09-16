<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_class_memberships', function (Blueprint $table): void {
            $table->index(
                ['classroom_id', 'academic_year_id', 'effective_from', 'effective_until', 'student_id'],
                'membership_class_period_student_index',
            );
        });
        Schema::table('external_tatib_records', function (Blueprint $table): void {
            $table->index(['is_active', 'occurred_at', 'student_id', 'category'], 'etatib_active_date_student_category_index');
        });
        Schema::table('cases', function (Blueprint $table): void {
            $table->index(['service_date', 'status_id', 'service_field_id', 'created_by'], 'case_date_status_field_creator_index');
        });
        Schema::table('consultations', function (Blueprint $table): void {
            $table->index(['session_date', 'status_id', 'service_field_id', 'counselor_id'], 'consultation_date_status_field_user_index');
        });
        Schema::table('follow_ups', function (Blueprint $table): void {
            $table->index(['planned_date', 'status_id', 'case_id'], 'follow_up_date_status_case_index');
        });
        Schema::table('achievements', function (Blueprint $table): void {
            $table->index(['achievement_date', 'verification_status_id', 'student_id'], 'achievement_date_status_student_index');
        });
        Schema::table('external_sync_runs', function (Blueprint $table): void {
            $table->index(['source', 'started_at', 'status'], 'sync_run_source_date_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('external_sync_runs', fn (Blueprint $table) => $table->dropIndex('sync_run_source_date_status_index'));
        Schema::table('achievements', fn (Blueprint $table) => $table->dropIndex('achievement_date_status_student_index'));
        Schema::table('follow_ups', fn (Blueprint $table) => $table->dropIndex('follow_up_date_status_case_index'));
        Schema::table('consultations', fn (Blueprint $table) => $table->dropIndex('consultation_date_status_field_user_index'));
        Schema::table('cases', fn (Blueprint $table) => $table->dropIndex('case_date_status_field_creator_index'));
        Schema::table('external_tatib_records', fn (Blueprint $table) => $table->dropIndex('etatib_active_date_student_category_index'));
        Schema::table('student_class_memberships', fn (Blueprint $table) => $table->dropIndex('membership_class_period_student_index'));
    }
};
