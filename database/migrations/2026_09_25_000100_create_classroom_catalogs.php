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
        Schema::create('classroom_catalogs', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('classrooms', function (Blueprint $table): void {
            $table->foreignId('classroom_catalog_id')->nullable()->constrained()->nullOnDelete();
        });

        $sourceYearId = DB::table('academic_years')->where('is_active', true)->orderByDesc('id')->value('id')
            ?? DB::table('academic_years')->orderByDesc('id')->value('id');
        if ($sourceYearId === null) {
            return;
        }

        foreach (DB::table('classrooms')->where('academic_year_id', $sourceYearId)->orderBy('id')->get(['name', 'is_active']) as $classroom) {
            DB::table('classroom_catalogs')->insertOrIgnore([
                'name' => $classroom->name,
                'is_active' => $classroom->is_active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        foreach (DB::table('classroom_catalogs')->get(['id', 'name']) as $catalog) {
            DB::table('classrooms')->where('name', $catalog->name)->update(['classroom_catalog_id' => $catalog->id]);
        }
    }

    public function down(): void
    {
        Schema::table('classrooms', fn (Blueprint $table) => $table->dropConstrainedForeignId('classroom_catalog_id'));
        Schema::dropIfExists('classroom_catalogs');
    }
};
