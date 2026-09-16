<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 30)->unique();
            $table->string('base_url', 500)->nullable();
            $table->string('expected_source_identifier', 100)->nullable();
            $table->text('credentials')->nullable();
            $table->unsignedSmallInteger('timeout_seconds')->default(30);

            $table->unsignedInteger('configuration_version')->default(0);
            $table->unsignedBigInteger('operation_fence_version')->default(0);
            $table->unsignedInteger('verified_configuration_version')->nullable();
            $table->string('verified_driver_id', 100)->nullable();
            $table->string('verified_adapter_version', 100)->nullable();
            $table->string('verified_contract_version', 100)->nullable();
            $table->string('verified_endpoint_policy_digest', 64)->nullable();

            $table->string('last_test_status', 20)->default('untested');
            $table->string('last_test_code', 50)->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->foreignId('last_tested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('is_enabled')->default(false);
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
    }
};
