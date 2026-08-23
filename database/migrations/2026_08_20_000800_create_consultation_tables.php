<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_number', 30)->nullable()->unique();
            $table->foreignId('student_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('temporary_student_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('case_id')->nullable()->constrained('cases')->restrictOnDelete();
            $table->foreignId('service_field_id')->constrained('references')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('references')->restrictOnDelete();
            $table->string('topic', 250);
            $table->string('referral_source', 150)->nullable();
            $table->date('session_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('general_summary')->nullable();
            $table->foreignId('counselor_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'session_date', 'status_id'], 'consultation_student_date_status_index');
            $table->index(['temporary_student_id', 'session_date'], 'consultation_temporary_date_index');
            $table->index(['counselor_id', 'session_date'], 'consultation_counselor_date_index');
        });

        Schema::create('consultation_private_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consultation_id')->unique()->constrained()->restrictOnDelete();
            $table->text('internal_note')->nullable();
            $table->text('sensitive_content')->nullable();
            $table->text('conclusion')->nullable();
            $table->text('follow_up_plan')->nullable();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_private_notes');
        Schema::dropIfExists('consultations');
    }
};
