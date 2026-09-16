<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corrections', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_number', 30)->nullable()->unique();
            $table->string('correction_type', 30)->index();
            $table->nullableMorphs('target');
            $table->string('target_label', 200);
            $table->string('field_name', 100);
            $table->string('field_label', 150);
            $table->text('old_value')->nullable();
            $table->text('proposed_value')->nullable();
            $table->text('old_value_label')->nullable();
            $table->text('proposed_value_label')->nullable();
            $table->text('reason');
            $table->foreignId('status_id')->constrained('references')->restrictOnDelete();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('external_sync_run_id')->nullable()->constrained('external_sync_runs')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['correction_type', 'status_id', 'created_at'], 'correction_type_status_date_index');
            $table->index(['requester_id', 'created_at'], 'correction_requester_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corrections');
    }
};
