<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Consultation;
use Database\Seeders\DummyCaseAndServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DummyCaseAndServiceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultation_dummy_data_can_be_seeded_fresh_and_rerun_idempotently(): void
    {
        $this->seed(DummyCaseAndServiceSeeder::class);

        $seededIds = Consultation::query()
            ->orderBy('session_date')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertCount(15, $seededIds);
        $this->assertSame(15, Consultation::query()
            ->whereNotNull('problem')
            ->whereNotNull('handling')
            ->whereNotNull('result')
            ->count());

        $this->seed(DummyCaseAndServiceSeeder::class);

        $this->assertSame($seededIds, Consultation::query()
            ->orderBy('session_date')
            ->orderBy('id')
            ->pluck('id')
            ->all());
        $this->assertDatabaseCount('consultations', 15);
    }
}
