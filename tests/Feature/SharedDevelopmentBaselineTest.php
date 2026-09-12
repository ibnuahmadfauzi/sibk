<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SharedDevelopmentBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_addendum_gate_menyediakan_ringkasan_aman_waka(): void
    {
        $this->assertTrue(Schema::hasColumn('cases', 'waka_summary'));

        $column = collect(Schema::getColumns('cases'))->firstWhere('name', 'waka_summary');

        $this->assertNotNull($column);
        $this->assertTrue((bool) $column['nullable']);
    }
}
