<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_tatib_records', function (Blueprint $table): void {
            $table->index('source_nisn', 'etatib_source_nisn_retention_index');
        });
    }

    public function down(): void
    {
        Schema::table('external_tatib_records', function (Blueprint $table): void {
            $table->dropIndex('etatib_source_nisn_retention_index');
        });
    }
};
