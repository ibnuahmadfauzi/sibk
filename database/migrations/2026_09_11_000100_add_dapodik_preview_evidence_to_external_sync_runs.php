<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_sync_runs', function (Blueprint $table): void {
            $table->json('snapshot_evidence')->nullable();
            $table->json('deactivation_plan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('external_sync_runs', function (Blueprint $table): void {
            $table->dropColumn([
                'snapshot_evidence',
                'deactivation_plan',
            ]);
        });
    }
};
