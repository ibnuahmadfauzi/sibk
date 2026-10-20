<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function withLegacySqliteDatabase(callable $callback): void
    {
        // MySQL DDL commits the test transaction, so legacy schema probes use an isolated SQLite database.
        $defaultConnection = DB::getDefaultConnection();
        $connections = config('database.connections');

        try {
            config(['database.connections.legacy_probe' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'foreign_key_constraints' => true,
            ]]);
            DB::purge('legacy_probe');
            DB::setDefaultConnection('legacy_probe');
            $this->artisan('migrate:fresh', ['--database' => 'legacy_probe'])->assertExitCode(0);
            $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
            $callback();
        } finally {
            DB::setDefaultConnection($defaultConnection);
            DB::purge('legacy_probe');
            config(['database.connections' => $connections]);
        }
    }
}
