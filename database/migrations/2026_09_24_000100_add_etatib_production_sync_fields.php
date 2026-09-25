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
            $table->string('sync_watermark', 191)->nullable()->after('is_enabled');
            $table->timestamp('last_full_synced_at')->nullable()->after('sync_watermark');
            $table->timestamp('last_successful_sync_at')->nullable()->after('last_full_synced_at');
            $table->json('last_probe_summary')->nullable()->after('last_successful_sync_at');
        });

        Schema::table('external_tatib_records', function (Blueprint $table): void {
            $table->string('source_nisn', 20)->nullable()->after('nisn');
            $table->string('source_student_name', 200)->nullable()->after('student_id');
            $table->string('source_classroom_name', 100)->nullable()->after('source_student_name');
            $table->string('recorded_by_name', 150)->nullable()->after('category');
            $table->integer('source_total_points')->nullable()->after('points');
            $table->timestamp('source_deleted_at')->nullable()->after('source_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('external_tatib_records', function (Blueprint $table): void {
            $table->dropColumn([
                'source_nisn',
                'source_student_name',
                'source_classroom_name',
                'recorded_by_name',
                'source_total_points',
                'source_deleted_at',
            ]);
        });

        Schema::table('integration_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'sync_watermark',
                'last_full_synced_at',
                'last_successful_sync_at',
                'last_probe_summary',
            ]);
        });
    }
};
