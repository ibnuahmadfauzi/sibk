<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_progresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->date('recorded_on');
            $table->string('progress', 30)->index();
            $table->text('note')->nullable();
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Forward-only: catatan penanganan tidak dihapus melalui rollback.
    }
};
