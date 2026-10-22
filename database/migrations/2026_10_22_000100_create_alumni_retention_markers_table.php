<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_retention_markers', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 20);
            $table->char('digest', 64)->unique();
            $table->char('key_fingerprint', 64);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Penanda retensi tidak boleh dihapus melalui rollback.');
    }
};
