<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\ProvisionalRosterXlsxParser;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class ImportTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-09-27 10:00:00');
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_roster_template_only_downloads_for_admin_and_can_be_uploaded_with_zero_prefixed_nisn(): void
    {
        $this->get(route('data-master.roster-imports.template'))->assertRedirect(route('login'));
        $this->actingAs($this->userWithRole('waka_kesiswaan'))
            ->get(route('data-master.roster-imports.template'))->assertForbidden();

        $response = $this->actingAs($this->userWithRole('admin_it'))
            ->get(route('data-master.roster-imports.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $path = $this->downloadPath($response->baseResponse);
        try {
            $book = IOFactory::load($path);
            $this->assertCount(1, $book->getAllSheets());
            $sheet = $book->getActiveSheet();
            $this->assertSame(['nisn', 'nama', 'rombel', 'tahun_pelajaran'], $sheet->rangeToArray('A1:D1')[0]);
            $this->assertSame(NumberFormat::FORMAT_TEXT, $sheet->getStyle('A2')->getNumberFormat()->getFormatCode());
            $sheet->setCellValueExplicit('A2', '0012345678', DataType::TYPE_STRING);
            $sheet->setCellValue('B2', 'Murid Template');
            $sheet->setCellValue('C2', 'X RPL 1');
            $sheet->setCellValue('D2', '2026/2027');
            (new Xlsx($book))->save($path);
            $book->disconnectWorksheets();

            $rows = app(ProvisionalRosterXlsxParser::class)->parse($this->uploaded($path, 'daftar-murid.xlsx'));
            $this->assertSame([[
                'nisn' => '0012345678',
                'name' => 'Murid Template',
                'classroom' => 'X RPL 1',
                'academic_year_name' => '2026/2027',
            ]], $rows);
        } finally {
            @unlink($path);
        }
    }

    public function test_achievement_template_only_downloads_for_waka_and_filled_file_imports(): void
    {
        $this->actingAs($this->userWithRole('guru_bk'))
            ->get(route('achievements.import.template'))->assertForbidden();
        $this->actingAs($this->userWithRole('admin_it'))
            ->get(route('achievements.import.template'))->assertForbidden();

        $waka = $this->userWithRole('waka_kesiswaan');
        $response = $this->actingAs($waka)
            ->get(route('achievements.import.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $path = $this->downloadPath($response->baseResponse);
        try {
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame(
                ['nisn', 'jenis', 'tingkat', 'kegiatan', 'penyelenggara', 'tanggal', 'hasil'],
                $sheet->rangeToArray('A1:G1')[0],
            );
            $this->assertSame(NumberFormat::FORMAT_TEXT, $sheet->getStyle('A2')->getNumberFormat()->getFormatCode());
            $this->assertSame('"akademik,olahraga,seni_budaya,karya_ilmiah,organisasi,lainnya"', $sheet->getDataValidation('B2')->getFormula1());
            $student = Student::query()->create(['nisn' => '0012345678', 'name' => 'Murid Prestasi', 'is_active' => true]);
            $sheet->fromArray([[
                $student->nisn, 'akademik', 'nasional', 'Lomba Sains', 'Sekolah', '2026-08-10', 'Juara I',
            ]], null, 'A2');
            (new Xlsx($book))->save($path);
            $book->disconnectWorksheets();

            $this->post(route('achievements.import'), ['file' => $this->uploaded($path, 'prestasi.xlsx')])
                ->assertRedirect(route('achievements.index'))
                ->assertSessionHasNoErrors();
            $this->assertSame('Lomba Sains', Achievement::query()->sole()->activity_name);
        } finally {
            @unlink($path);
        }
    }

    private function downloadPath(BinaryFileResponse $response): string
    {
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        return $response->getFile()->getPathname();
    }

    private function uploaded(string $path, string $name): UploadedFile
    {
        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }
}
