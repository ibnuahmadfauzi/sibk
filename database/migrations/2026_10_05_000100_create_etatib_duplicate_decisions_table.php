<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etatib_duplicate_decisions', function (Blueprint $table): void {
            $table->id();
            $table->char('url_hash', 64);
            $table->char('group_key', 64);
            $table->string('source_nisn', 10);
            $table->string('source_name', 200);
            $table->unsignedSmallInteger('copy_count');
            $table->boolean('is_active')->default(true);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at');
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['url_hash', 'group_key'], 'etatib_duplicate_source_group_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etatib_duplicate_decisions');
    }
};
