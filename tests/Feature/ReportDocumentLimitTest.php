<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportDocumentLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    }

    public function test_http_documents_enforce_the_limit_and_preserve_filters(): void
    {
        config()->set('sibk.report_max_document_rows', 2);
        [$year, $classroom, , $coordinator] = $this->consultationFixture(3);
        $filters = [
            'academic_year_id' => $year->id,
            'semester' => '1',
            'classroom_search' => $classroom->name,
            'service_type' => 'consultation',
        ];
        $this->actingAs($coordinator);

        $export = $this->get(route('reports.export', [
            ...$filters,
            'format' => 'xlsx',
            'report_max_document_rows' => 999999,
        ]));
        $export->assertRedirect(route('reports.index', [...$filters, 'per_page' => 10]))
            ->assertSessionHasErrors(['classroom_search']);

        $this->withCookie(config('session.cookie'), session()->getId());
        $index = $this->get($export->headers->get('Location'));
        $index->assertOk()
            ->assertSee('data-report-filter-errors', false)
            ->assertSee('Dokumen laporan dibatasi hingga 2 catatan. Persempit periode atau filter kelas lalu coba lagi.');

        $preview = $this->get(route('reports.preview', [...$filters, 'embedded' => 1]));
        $preview->assertRedirect(route('reports.index', [...$filters, 'per_page' => 10]))
            ->assertSessionHasErrors(['classroom_search']);
        $this->get($preview->headers->get('Location'))
            ->assertOk()
            ->assertSee('data-report-filter-errors', false)
            ->assertSee('Dokumen laporan dibatasi hingga 2 catatan. Persempit periode atau filter kelas lalu coba lagi.');

        $this->getJson(route('reports.export', [...$filters, 'format' => 'xlsx']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['classroom_search']);
    }

    public function test_filtered_xlsx_download_contains_the_two_matching_student_rows(): void
    {
        config()->set('sibk.report_max_document_rows', 2);
        [$year, $classroom, $students, $coordinator] = $this->consultationFixture(2);
        $this->consultationFixture(1, 'X RPL 2', $year, $coordinator);

        $response = $this->actingAs($coordinator)->get(route('reports.export', [
            'academic_year_id' => $year->id,
            'semester' => '1',
            'classroom_id' => $classroom->id,
            'service_type' => 'consultation',
            'format' => 'xlsx',
        ]));
        $response->assertOk()->assertDownload();

        $path = $response->getFile()->getPathname();
        $spreadsheet = null;
        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $this->assertStringContainsString('Semester 1', (string) $sheet->getCell('A2')->getValue());
            $this->assertStringContainsString('1 Juli 2026', (string) $sheet->getCell('A2')->getValue());
            $this->assertStringContainsString('31 Desember 2026', (string) $sheet->getCell('A2')->getValue());
            $this->assertSame($students[0]->name, strtok((string) $sheet->getCell('C5')->getValue(), "\n"));
            $this->assertSame($students[1]->name, strtok((string) $sheet->getCell('C6')->getValue(), "\n"));
            $this->assertNull($sheet->getCell('C7')->getValue());
        } finally {
            $spreadsheet?->disconnectWorksheets();
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_json_document_without_an_academic_year_is_rejected(): void
    {
        $coordinator = $this->coordinator();

        $this->actingAs($coordinator)
            ->getJson(route('reports.export', ['service_type' => 'consultation', 'format' => 'xlsx']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['academic_year_id']);
    }

    /** @return array{AcademicYear, Classroom, list<Student>, User} */
    private function consultationFixture(int $count, string $classroomName = 'X RPL 1', ?AcademicYear $year = null, ?User $coordinator = null): array
    {
        $year ??= AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'name' => $classroomName,
        ]);
        $coordinator ??= $this->coordinator();
        $serviceField = ReferenceValue::query()->forCategory('service_field')->firstOrFail();
        $students = [];

        for ($index = 1; $index <= $count; $index++) {
            $studentNumber = Student::query()->count() + 1;
            $student = Student::query()->create([
                'nisn' => sprintf('%010d', $studentNumber),
                'name' => 'Murid Laporan '.$studentNumber,
                'is_active' => true,
            ]);
            StudentClassMembership::query()->create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => $year->id,
                'is_active' => true,
            ]);
            Consultation::query()->create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'classroom_id' => $classroom->id,
                'service_field_id' => $serviceField->id,
                'session_date' => '2026-09-01',
                'problem' => 'Masalah sintetik.',
                'handling' => 'Penanganan sintetik.',
                'result' => 'Hasil sintetik.',
                'counselor_id' => $coordinator->id,
            ]);
            $students[] = $student;
        }

        return [$year, $classroom, $students, $coordinator];
    }

    private function coordinator(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::query()->where('slug', 'koordinator_bk')->firstOrFail());

        return $user;
    }
}
