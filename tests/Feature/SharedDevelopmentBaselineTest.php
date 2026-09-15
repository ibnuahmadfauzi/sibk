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

    public function test_active_requirements_publish_the_simplified_operational_contract(): void
    {
        $srs = file_get_contents(base_path('docs/requirements/SRS_Aplikasi_BK_v1.1.md'));
        $api = file_get_contents(base_path('docs/api-contract.md'));

        $this->assertIsString($srs);
        $this->assertIsString($api);
        $this->assertStringContainsString('dalam_proses', $srs);
        $this->assertStringContainsString('resmi_keluar', $srs);
        $this->assertStringContainsString('must_change_password', $srs);
        $this->assertStringContainsString('API sekolah tidak menentukan status keluar murid', $api);
        $this->assertStringNotContainsString('Koreksi data operasional harus diverifikasi', $srs);
        $this->assertStringNotContainsString('pemberitahuan operasional yang terkait pengguna', $srs);
    }
}
