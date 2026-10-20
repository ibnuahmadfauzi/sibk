<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_settings', function (Blueprint $table): void {
            $table->json('automatic_sync_schedule_days')->nullable()->after('automatic_sync_enabled_at');
            $table->json('automatic_sync_schedule_times')->nullable()->after('automatic_sync_schedule_days');
        });
    }

    public function down(): void
    {
        Schema::table('integration_settings', function (Blueprint $table): void {
            $table->dropColumn(['automatic_sync_schedule_days', 'automatic_sync_schedule_times']);
        });
    }
};
