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
        Schema::create('case_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('follow_up_type_id')->constrained('references')->restrictOnDelete();
            $table->date('follow_up_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_id', 'follow_up_date']);
            $table->index('follow_up_type_id');
        });

        // Backfill data existing jika ada cases dengan follow_up_type_id terisi
        $casesWithFollowUp = DB::table('cases')
            ->whereNotNull('follow_up_type_id')
            ->select(['id', 'follow_up_type_id', 'service_date', 'created_by', 'created_at', 'updated_at'])
            ->get();

        foreach ($casesWithFollowUp as $case) {
            DB::table('case_follow_ups')->insert([
                'case_id' => $case->id,
                'follow_up_type_id' => $case->follow_up_type_id,
                'follow_up_date' => $case->service_date ?? now()->toDateString(),
                'notes' => null,
                'created_by' => $case->created_by,
                'created_at' => $case->created_at ?? now(),
                'updated_at' => $case->updated_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('case_follow_ups');
    }
};
