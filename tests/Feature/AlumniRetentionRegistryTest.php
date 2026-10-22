<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Services\AlumniRetentionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

final class AlumniRetentionRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_markers_contain_only_keyed_digests_and_block_either_identifier(): void
    {
        config(['alumni_retention.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        $student = Student::query()->create(['nisn' => '0012345678', 'dapodik_id' => 'source-123', 'name' => 'Alumni Uji']);
        $registry = app(AlumniRetentionRegistry::class);

        DB::transaction(function () use ($registry, $student): void {
            $registry->lockImports();
            $registry->record($student);
            $registry->record($student);
        });

        $this->assertDatabaseCount('alumni_retention_markers', 2);
        $rows = DB::table('alumni_retention_markers')->get()->toJson();
        foreach (['0012345678', 'source-123', 'Alumni Uji'] as $identity) {
            $this->assertStringNotContainsString($identity, $rows);
        }
        $this->assertTrue($registry->blocked(' 0012345678 '));
        $this->assertTrue($registry->blocked(null, 'source-123'));
        $this->assertFalse($registry->blocked('0099999999', 'another-source'));
        $this->assertFalse($registry->blocked('source-123', '0012345678'));
    }

    public function test_imports_work_without_a_key_only_until_markers_exist(): void
    {
        config(['alumni_retention.key' => null]);
        $registry = app(AlumniRetentionRegistry::class);
        $this->assertFalse($registry->blocked('0012345678'));
        DB::table('alumni_retention_markers')->insert([
            'kind' => 'nisn', 'digest' => str_repeat('a', 64),
            'key_fingerprint' => str_repeat('b', 64), 'created_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $registry->blocked('0099999999');
    }

    public function test_changing_the_key_fails_closed_even_for_unknown_identifiers(): void
    {
        config(['alumni_retention.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Alumni Uji']);
        app(AlumniRetentionRegistry::class)->record($student);
        config(['alumni_retention.key' => 'base64:'.base64_encode(str_repeat('b', 32))]);

        $this->expectException(RuntimeException::class);
        app(AlumniRetentionRegistry::class)->blocked('0099999999');
    }

    public function test_purge_cannot_create_markers_with_a_missing_or_short_key(): void
    {
        config(['alumni_retention.key' => 'base64:'.base64_encode('short')]);
        $this->expectException(RuntimeException::class);
        app(AlumniRetentionRegistry::class)->assertReady();
    }

    public function test_import_rejection_does_not_echo_the_identity(): void
    {
        config(['alumni_retention.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Alumni Uji']);
        $registry = app(AlumniRetentionRegistry::class);
        $registry->record($student);
        try {
            $registry->assertImportAllowed('0012345678');
            $this->fail('Impor ulang seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertStringNotContainsString('0012345678', $exception->getMessage());
        }
    }
}
