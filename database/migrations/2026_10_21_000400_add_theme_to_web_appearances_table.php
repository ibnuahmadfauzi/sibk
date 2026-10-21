<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('web_appearances', function (Blueprint $table): void {
            $table->string('theme')->default('default');
            $table->string('agency_logo_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('web_appearances', function (Blueprint $table): void {
            $table->dropColumn(['theme', 'agency_logo_path']);
        });
    }
};
