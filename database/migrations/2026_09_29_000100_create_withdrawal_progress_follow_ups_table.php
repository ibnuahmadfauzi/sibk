<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_progress_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('withdrawal_progress_id')->constrained('withdrawal_progresses')->cascadeOnDelete();
            $table->string('progress', 30);
            $table->date('follow_up_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['withdrawal_progress_id', 'follow_up_date'], 'withdrawal_follow_ups_parent_date_index');
        });

        DB::table('withdrawal_progresses')
            ->orderBy('id')
            ->each(function (object $withdrawal): void {
                DB::table('withdrawal_progress_follow_ups')->insert([
                    'withdrawal_progress_id' => $withdrawal->id,
                    'progress' => $withdrawal->progress,
                    'follow_up_date' => $withdrawal->recorded_on,
                    'notes' => null,
                    'created_by' => $withdrawal->teacher_id,
                    'created_at' => $withdrawal->created_at,
                    'updated_at' => $withdrawal->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        // Forward-only: riwayat progres pengunduran diri tidak dihapus melalui rollback.
    }
};
