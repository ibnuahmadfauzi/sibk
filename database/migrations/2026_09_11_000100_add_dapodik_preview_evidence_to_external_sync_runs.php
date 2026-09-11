<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('external_sync_runs', 'snapshot_evidence')) {
            Schema::table('external_sync_runs', function (Blueprint $table): void {
                $table->json('snapshot_evidence')->nullable();
            });
        }

        if (! Schema::hasColumn('external_sync_runs', 'deactivation_plan')) {
            Schema::table('external_sync_runs', function (Blueprint $table): void {
                $table->json('deactivation_plan')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally conservative: both columns may have been created by the
        // historically edited fix-base migration, so ownership cannot be proven.
    }
};
