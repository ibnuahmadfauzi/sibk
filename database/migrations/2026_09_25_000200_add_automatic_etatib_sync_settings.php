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
            $table->text('automatic_sync_url')->nullable()->after('is_enabled');
            $table->boolean('automatic_sync_enabled')->default(false)->index()->after('automatic_sync_url');
            $table->timestamp('automatic_sync_enabled_at')->nullable()->after('automatic_sync_enabled');
            $table->foreignId('automatic_sync_updated_by')
                ->nullable()
                ->after('automatic_sync_enabled_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('integration_settings', function (Blueprint $table): void {
            $table->dropForeign(['automatic_sync_updated_by']);
            $table->dropIndex(['automatic_sync_enabled']);
            $table->dropColumn([
                'automatic_sync_url',
                'automatic_sync_enabled',
                'automatic_sync_enabled_at',
                'automatic_sync_updated_by',
            ]);
        });
    }
};
