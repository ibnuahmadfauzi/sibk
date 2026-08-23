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
            $table->string('dapodik_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('synced_at')->nullable();
            $table->unique('dapodik_id', 'student_memberships_dapodik_id_unique');
        });

        Schema::create('teacher_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('decision_number', 150);
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['classroom_id', 'academic_year_id', 'effective_from'], 'teacher_assignment_period_index');
            $table->index(['user_id', 'academic_year_id', 'effective_from'], 'teacher_assignment_user_period_index');
        });

        Schema::create('external_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 50)->index();
            $table->string('status', 30)->index();
            $table->boolean('is_full_snapshot')->default(false);
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('received_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('conflict_count')->default(0);
            $table->text('summary')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('external_sync_issues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_sync_run_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 50);
            $table->string('source_identifier')->nullable();
            $table->string('nisn', 20)->nullable()->index();
            $table->string('input_name', 150)->nullable();
            $table->string('issue_code', 50)->index();
            $table->text('summary');
            $table->json('details')->nullable();
            $table->foreignId('resolved_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['external_sync_run_id', 'entity_type'], 'sync_issue_run_entity_index');
        });

        Schema::create('temporary_students', function (Blueprint $table): void {
            $table->id();
            $table->string('nisn', 20)->index();
            $table->string('input_name', 150);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reconciliation_status_id')->constrained('references')->restrictOnDelete();
            $table->foreignId('reconciled_student_id')->nullable()->constrained('students')->restrictOnDelete();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['reconciliation_status_id', 'created_at'], 'temporary_student_status_index');
        });

        Schema::create('identity_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('temporary_student_id')->constrained()->restrictOnDelete();
            $table->string('source_nisn', 20)->index();
            $table->foreignId('student_id')->nullable()->constrained('students')->restrictOnDelete();
            $table->string('official_name', 150)->nullable();
            $table->foreignId('status_id')->constrained('references')->restrictOnDelete();
            $table->text('result');
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('conflict_details')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_reconciliations');
        Schema::dropIfExists('temporary_students');
        Schema::dropIfExists('external_sync_issues');
        Schema::dropIfExists('external_sync_runs');
        Schema::dropIfExists('teacher_assignments');

        Schema::table('student_class_memberships', function (Blueprint $table): void {
            $table->dropUnique('student_memberships_dapodik_id_unique');
            $table->dropColumn(['dapodik_id', 'is_active', 'synced_at']);
        });
    }
};
