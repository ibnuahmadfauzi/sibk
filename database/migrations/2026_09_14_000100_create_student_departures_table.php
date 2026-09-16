<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_departures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('departure_type', 40);
            $table->string('status', 30)->default('dalam_proses')->index();
            $table->date('reported_at');
            $table->string('recommendation_summary', 500)->nullable();
            $table->date('effective_date')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->timestamps();
            $table->index(['status', 'effective_date']);
        });
    }

    public function down(): void
    {
        // Forward-only: proses keluar murid tidak dihapus melalui rollback.
    }
};
