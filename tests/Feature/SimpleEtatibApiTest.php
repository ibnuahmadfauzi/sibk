<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExternalSyncRun;
use App\Models\ExternalTatibRecord;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\EtatibSyncService;
use App\Services\SimpleEtatibApiService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SimpleEtatibApiTest extends TestCase
{
    use RefreshDatabase;

    private const string URL = 'https://etatib.example.test/pelanggaran';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->app->instance(
            SimpleEtatibApiService::class,
            new SimpleEtatibApiService(
                app(EtatibSyncService::class),
                static fn (string $host): array => ['8.8.8.8'],
            ),
        );
    }

    public function test_preview_reads_bare_array_shows_full_nisn_and_does_not_mutate_data(): void
    {
        Student::query()->create([
            'nisn' => '0093200788',
            'name' => 'FERRYSCHA PUTRI',
        ]);
        Http::fake([self::URL => Http::response($this->payload())]);

        $response = $this->actingAs($this->admin())->postJson(
            route('data-master.etatib.preview'),
            ['api_url' => self::URL],
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.rows', 2)
            ->assertJsonPath('data.students', 2)
            ->assertJsonPath('data.matched', 1)
            ->assertJsonPath('data.conflicts', 1)
            ->assertJsonPath('data.sample.0.nisn', '0093200788');
        $this->assertDatabaseCount('external_tatib_records', 0);
        $this->assertDatabaseCount('external_sync_runs', 0);
    }

    public function test_sync_normalizes_short_nisn_and_persists_structured_fields(): void
    {
        $student = Student::query()->create([
            'nisn' => '0093200788',
            'name' => 'FERRYSCHA PUTRI',
        ]);
        Http::fake([self::URL => Http::response([$this->payload()[0]])]);

        $this->actingAs($this->admin())
            ->post(route('data-master.etatib.sync'), ['api_url' => self::URL])
            ->assertRedirect()
            ->assertSessionHas('success', 'Sinkronisasi e-Tatib berhasil.');

        $record = ExternalTatibRecord::query()->sole();
        $this->assertStringStartsWith('simple-', $record->source_identifier);
        $this->assertSame('0093200788', $record->nisn);
        $this->assertSame('93200788', $record->source_nisn);
        $this->assertSame($student->id, $record->student_id);
        $this->assertSame('12 PH 2', $record->source_classroom_name);
        $this->assertSame('ANIS', $record->recorded_by_name);
        $this->assertSame(10, $record->points);
        $this->assertSame(20, $record->source_total_points);
        $this->assertDatabaseHas('external_sync_runs', [
            'source' => 'etatib',
            'status' => ExternalSyncRun::STATUS_SUCCEEDED,
            'received_count' => 1,
            'processed_count' => 1,
        ]);
    }

    public function test_masked_nisn_and_identical_rows_are_rejected_before_sync(): void
    {
        $masked = $this->payload()[0];
        $masked['siswa_nisn'] = '95****88';
        Http::fake([self::URL => Http::response([$masked])]);

        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('api_url');

        Http::fake([self::URL => Http::response([$this->payload()[0], $this->payload()[0]])]);
        $this->actingAs($this->admin())
            ->postJson(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('api_url');

        $this->assertDatabaseCount('external_tatib_records', 0);
    }

    public function test_etatib_api_routes_are_limited_to_admin_it(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('slug', 'guru_bk')->firstOrFail());

        $this->post(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertRedirect(route('login'));
        $this->actingAs($teacher)
            ->post(route('data-master.etatib.preview'), ['api_url' => self::URL])
            ->assertForbidden();
        $this->actingAs($teacher)
            ->post(route('data-master.etatib.sync'), ['api_url' => self::URL])
            ->assertForbidden();
    }

    /** @return list<array<string, int|string>> */
    private function payload(): array
    {
        return [
            [
                'siswa_nisn' => '93200788',
                'siswa_nama' => 'FERRYSCHA PUTRI',
                'siswa_kelas' => '12 PH 2',
                'pelanggaran' => 'Datang Terlambat',
                'poin_pelanggaran' => 10,
                'pencatat' => 'ANIS',
                'kategori' => 'ringan',
                'tanggal_pelanggaran' => '20 Jul 2026 10:17:00',
                'total_poin' => '20',
            ],
            [
                'siswa_nisn' => '0081784737',
                'siswa_nama' => 'Ridho Parulian Siagian',
                'siswa_kelas' => '11 TKJ 1',
                'pelanggaran' => 'Atribut seragam tidak lengkap',
                'poin_pelanggaran' => 10,
                'pencatat' => 'Aminatus Syaadah',
                'kategori' => 'ringan',
                'tanggal_pelanggaran' => '29 Jul 2026 10:17:00',
                'total_poin' => '50',
            ],
        ];
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin_it')->firstOrFail());

        return $admin;
    }
}
