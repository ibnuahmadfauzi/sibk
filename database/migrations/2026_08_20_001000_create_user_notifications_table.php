<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30)->index();
            $table->string('title', 200);
            $table->text('message');
            $table->nullableMorphs('target');
            $table->string('action_route', 100)->nullable();
            $table->json('action_parameters')->nullable();
            $table->string('deduplication_key', 190)->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['user_id', 'deduplication_key'], 'notification_user_deduplication_unique');
            $table->index(['user_id', 'created_at'], 'notification_user_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
