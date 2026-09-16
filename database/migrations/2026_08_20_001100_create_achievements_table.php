<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('type_id')->constrained('references')->restrictOnDelete();
            $table->foreignId('level_id')->constrained('references')->restrictOnDelete();
            $table->string('activity_name', 250);
            $table->string('organizer', 200);
            $table->date('achievement_date');
            $table->string('result', 250);
            $table->text('evidence_reference');
            $table->text('evidence_description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('verification_status_id')->constrained('references')->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'achievement_date', 'verification_status_id'], 'achievement_student_date_status_index');
            $table->index(['recorded_by', 'achievement_date'], 'achievement_recorder_date_index');
            $table->index(['type_id', 'level_id', 'achievement_date'], 'achievement_type_level_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
