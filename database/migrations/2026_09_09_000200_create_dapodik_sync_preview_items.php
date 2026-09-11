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
            $table->string('snapshot_fingerprint', 64)->nullable();
            $table->json('snapshot_evidence')->nullable();
            $table->json('deactivation_plan')->nullable();
            $table->unsignedInteger('preview_generation')->nullable();
            $table->unsignedInteger('decision_revision')->default(0);
            $table->unsignedInteger('configuration_version')->nullable();
            $table->unsignedBigInteger('preview_fencing_token')->nullable();
            $table->string('driver_id', 100)->nullable();
            $table->string('adapter_version', 100)->nullable();
            $table->string('contract_version', 100)->nullable();
            $table->string('endpoint_policy_digest', 64)->nullable();
            $table->timestamp('preview_expires_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->index(
                ['source', 'preview_generation'],
                'sync_run_source_preview_generation_index',
            );
        });

        Schema::create('dapodik_sync_preview_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_sync_run_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 30);
            $table->string('source_identifier', 100);
            $table->unsignedBigInteger('candidate_id')->nullable();
            $table->string('match_status', 30)->index();
            $table->json('safe_fields');
            $table->string('item_hash', 64);
            $table->string('target_fingerprint', 64)->nullable();
            $table->unsignedInteger('preview_generation');
            $table->unsignedInteger('decision_revision')->default(0);
            $table->string('decision', 30)->nullable();
            $table->unsignedBigInteger('decision_candidate_id')->nullable();
            $table->string('decision_target_fingerprint', 64)->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['external_sync_run_id', 'entity_type', 'source_identifier'],
                'dapodik_preview_run_entity_source_unique',
            );
            $table->index(
                ['external_sync_run_id', 'preview_generation'],
                'dapodik_preview_run_generation_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dapodik_sync_preview_items');

        Schema::table('external_sync_runs', function (Blueprint $table): void {
            $table->dropIndex('sync_run_source_preview_generation_index');
            $table->dropColumn([
                'snapshot_fingerprint',
                'snapshot_evidence',
                'deactivation_plan',
                'preview_generation',
                'decision_revision',
                'configuration_version',
                'preview_fencing_token',
                'driver_id',
                'adapter_version',
                'contract_version',
                'endpoint_policy_digest',
                'preview_expires_at',
                'applied_at',
                'superseded_at',
            ]);
        });
    }
};
