<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->string('dapodik_id')->nullable()->unique();
            $table->string('name', 20)->unique();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('classrooms', function (Blueprint $table): void {
            $table->id();
            $table->string('dapodik_id')->nullable()->unique();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->unsignedTinyInteger('grade_level')->nullable();
            $table->string('major', 100)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['academic_year_id', 'name']);
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('dapodik_id')->nullable()->unique();
            $table->string('nisn', 20)->unique();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('student_class_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->timestamps();
            $table->unique(
                ['student_id', 'classroom_id', 'effective_from'],
                'student_class_membership_period_unique',
            );
            $table->index(['student_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_class_memberships');
        Schema::dropIfExists('students');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('academic_years');
    }
};
