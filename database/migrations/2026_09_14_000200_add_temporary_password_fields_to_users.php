<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('must_change_password')->default(false)->index();
            $table->timestamp('temporary_password_expires_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
        });
    }

    public function down(): void
    {
        // Forward-only: lifecycle password akun tidak dipulihkan melalui rollback.
    }
};
