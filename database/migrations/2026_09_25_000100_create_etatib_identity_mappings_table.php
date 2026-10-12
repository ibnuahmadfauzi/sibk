<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etatib_identity_mappings', function (Blueprint $table): void {
            $table->id();
            $table->string('source_nisn', 10);
            $table->string('source_name', 200);
            $table->char('source_name_hash', 64);
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('mapped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('mapped_at');
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_nisn', 'source_name_hash'],
                'etatib_identity_source_unique',
            );
            $table->index(
                ['source_nisn', 'source_name_hash', 'is_active'],
                'etatib_identity_active_lookup',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etatib_identity_mappings');
    }
};
