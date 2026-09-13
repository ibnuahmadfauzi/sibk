<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ReferenceValue;
use App\Support\ServiceRecordStatus;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ServiceRecordStatusMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_seeder_publishes_the_shared_status_contract(): void
    {
        $this->seed(ReferenceSeeder::class);

        foreach (['case_status', 'consultation_status'] as $category) {
            $statuses = ReferenceValue::query()
                ->forCategory($category)
                ->active()
                ->orderBy('sort_order')
                ->get(['code', 'label']);

            $this->assertSame(ServiceRecordStatus::codes(), $statuses->pluck('code')->all());
            $this->assertSame(
                array_values(ServiceRecordStatus::labels()),
                $statuses->pluck('label')->all(),
            );
        }
    }

    public function test_migration_maps_legacy_statuses_without_losing_service_records(): void
    {
        $connection = 'service_status_migration_probe';
        $originalConnection = DB::getDefaultConnection();
        $this->configureSqliteMigrationProbe($connection);

        try {
            DB::setDefaultConnection($connection);
            $this->createLegacyServiceSchema();
            $this->insertLegacyServiceRecords();

            $migration = require database_path(
                'migrations/2026_09_13_000100_align_service_record_statuses.php',
            );
            $migration->up();

            $this->assertSame(4, DB::table('cases')->count());
            $this->assertSame(4, DB::table('consultations')->count());
            $this->assertSame(
                ServiceRecordStatus::codes(),
                DB::table('references')
                    ->where('category', 'case_status')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->pluck('code')
                    ->all(),
            );
            $this->assertSame(
                ServiceRecordStatus::codes(),
                DB::table('references')
                    ->where('category', 'consultation_status')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->pluck('code')
                    ->all(),
            );
            $this->assertSame(
                [
                    ServiceRecordStatus::NEW,
                    ServiceRecordStatus::IN_PROGRESS,
                    ServiceRecordStatus::COMPLETED,
                    ServiceRecordStatus::CANCELLED,
                ],
                $this->recordStatusCodes('cases'),
            );
            $this->assertSame(
                [
                    ServiceRecordStatus::NEW,
                    ServiceRecordStatus::IN_PROGRESS,
                    ServiceRecordStatus::COMPLETED,
                    ServiceRecordStatus::CANCELLED,
                ],
                $this->recordStatusCodes('consultations'),
            );
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge($connection);
        }
    }

    private function configureSqliteMigrationProbe(string $connection): void
    {
        config()->set('database.connections.'.$connection, [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge($connection);
    }

    private function createLegacyServiceSchema(): void
    {
        Schema::create('references', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 80);
            $table->string('code', 80);
            $table->string('label', 150);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['category', 'code']);
        });
        Schema::create('cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('status_id')->constrained('references')->restrictOnDelete();
        });
        Schema::create('consultations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('status_id')->constrained('references')->restrictOnDelete();
        });
    }

    private function insertLegacyServiceRecords(): void
    {
        $now = now();
        DB::table('references')->insert([
            ['id' => 1, 'category' => 'case_status', 'code' => 'baru', 'label' => 'Baru', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'category' => 'case_status', 'code' => 'dalam_penanganan', 'label' => 'Dalam Penanganan', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'category' => 'case_status', 'code' => 'selesai', 'label' => 'Selesai', 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'category' => 'case_status', 'code' => 'dibatalkan', 'label' => 'Dibatalkan', 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'category' => 'consultation_status', 'code' => 'dijadwalkan', 'label' => 'Dijadwalkan', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'category' => 'consultation_status', 'code' => 'menunggu_konfirmasi', 'label' => 'Menunggu Konfirmasi', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'category' => 'consultation_status', 'code' => 'terlaksana', 'label' => 'Terlaksana', 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'category' => 'consultation_status', 'code' => 'dibatalkan', 'label' => 'Dibatalkan', 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('cases')->insert([
            ['id' => 101, 'status_id' => 1],
            ['id' => 102, 'status_id' => 2],
            ['id' => 103, 'status_id' => 3],
            ['id' => 104, 'status_id' => 4],
        ]);
        DB::table('consultations')->insert([
            ['id' => 201, 'status_id' => 5],
            ['id' => 202, 'status_id' => 6],
            ['id' => 203, 'status_id' => 7],
            ['id' => 204, 'status_id' => 8],
        ]);
    }

    /** @return list<string> */
    private function recordStatusCodes(string $table): array
    {
        return DB::table($table)
            ->join('references', 'references.id', '=', $table.'.status_id')
            ->orderBy($table.'.id')
            ->pluck('references.code')
            ->all();
    }
}
