<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_tatib_records', function (Blueprint $table): void {
            $table->id();
            $table->string('source_identifier')->unique();
            $table->string('nisn', 20)->index();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->string('violation_type', 200);
            $table->string('category', 100);
            $table->integer('points')->default(0);
            $table->string('source_status', 100)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('source_synced_at')->nullable();
            $table->timestamp('synced_at');
            $table->timestamps();
        });

        Schema::create('cases', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_number', 30)->nullable()->unique();
            $table->foreignId('student_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('temporary_student_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('case_source_id')->constrained('references')->restrictOnDelete();
            $table->foreignId('service_field_id')->constrained('references')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('references')->restrictOnDelete();
            $table->date('service_date');
            $table->string('referrer', 150)->nullable();
            $table->text('initial_info');
            $table->text('initial_action');
            $table->text('internal_note')->nullable();
            $table->text('final_result')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->text('continued_plan')->nullable();
            $table->date('closed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'status_id', 'service_date'], 'case_student_status_date_index');
            $table->index(['temporary_student_id', 'status_id'], 'case_temporary_status_index');
        });

        Schema::create('case_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('assignment_type', 30);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->text('reason');
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['case_id', 'assignment_type', 'effective_from'], 'case_assignment_period_index');
            $table->index(['user_id', 'effective_from', 'effective_until'], 'case_assignment_user_period_index');
        });

        Schema::create('case_coordinations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('waka_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('references')->restrictOnDelete();
            $table->text('coordination_need');
            $table->text('result')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('coordinated_at');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['case_id', 'waka_user_id', 'status_id'], 'case_coordination_access_index');
        });

        Schema::create('follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('follow_up_type_id')->constrained('references')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('references')->restrictOnDelete();
            $table->date('planned_date');
            $table->date('execution_date')->nullable();
            $table->text('result')->nullable();
            $table->text('next_plan')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['case_id', 'planned_date', 'status_id'], 'follow_up_case_schedule_index');
        });

        Schema::create('case_etatib_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('external_tatib_record_id')->constrained()->restrictOnDelete();
            $table->foreignId('linked_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['case_id', 'external_tatib_record_id'], 'case_etatib_link_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_etatib_links');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('case_coordinations');
        Schema::dropIfExists('case_assignments');
        Schema::dropIfExists('cases');
        Schema::dropIfExists('external_tatib_records');
    }
};
