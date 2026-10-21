<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\WebAppearance;
use App\Services\ReportDocumentExporter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class WebAgencyLogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_agency_logo_is_absent_until_uploaded_by_admin(): void
    {
        $url = route('admin.web-settings.agency-logo');
        $this->get(route('branding.agency-logo'))->assertNotFound();
        $this->patch($url, ['logo' => UploadedFile::fake()->image('logo.png')])->assertRedirect(route('login'));

        $teacher = $this->user('guru_bk');
        $this->actingAs($teacher)->patch($url, ['logo' => UploadedFile::fake()->image('logo.png')])->assertForbidden();
        $inactive = $this->user('admin_it');
        $inactive->update(['is_active' => false]);
        $this->actingAs($inactive)->patch($url, ['logo' => UploadedFile::fake()->image('logo.png')])->assertRedirect(route('login'));
        $this->assertNull(WebAppearance::query()->findOrFail(1)->agency_logo_path);
    }

    public function test_admin_uploads_safe_agency_logo_without_changing_school_logo(): void
    {
        $admin = $this->user('admin_it');
        $this->actingAs($admin)->patch(route('admin.web-settings.update'), [
            'logo' => UploadedFile::fake()->image('school.png', 100, 100),
        ])->assertRedirect();
        $schoolPath = WebAppearance::query()->findOrFail(1)->logo_path;

        $upload = UploadedFile::fake()->image('agency.png', 100, 100);
        file_put_contents($upload->getRealPath(), '<?php echo "unsafe";', FILE_APPEND);
        $this->patch(route('admin.web-settings.agency-logo'), ['logo' => $upload])
            ->assertRedirect(route('admin.web-settings.edit'));

        $appearance = WebAppearance::query()->findOrFail(1);
        $this->assertSame($schoolPath, $appearance->logo_path);
        $this->assertNotNull($appearance->agency_logo_path);
        $this->assertStringNotContainsString('unsafe', Storage::disk('local')->get($appearance->agency_logo_path));
        $this->get(route('branding.agency-logo'))->assertOk()->assertHeader('Content-Type', 'image/png');
        $letterhead = view('pages.reports.print._letterhead', ['title' => 'Laporan'])->render();
        $this->assertStringContainsString('sibk-document-letterhead__identity--two-logos', $letterhead);
        $this->assertLessThan(strpos($letterhead, route('branding.logo')), strpos($letterhead, route('branding.agency-logo')));
        $this->assertDatabaseHas('audit_logs', ['action' => 'web_appearance.agency_logo_updated', 'actor_id' => $admin->id]);

        $document = app(ReportDocumentExporter::class)->export('xlsx', [
            'academic_year' => null,
            'period' => ['label' => 'Semester 1'],
            'document_section_title' => 'Laporan',
            'rows' => [],
        ]);
        $spreadsheet = null;
        try {
            $spreadsheet = IOFactory::load($document['path']);
            $drawings = $spreadsheet->getActiveSheet()->getDrawingCollection();
            $this->assertCount(2, $drawings);
            $this->assertSame('A1', $drawings[0]->getCoordinates());
            $this->assertSame('G1', $drawings[1]->getCoordinates());
        } finally {
            $spreadsheet?->disconnectWorksheets();
            unlink($document['path']);
        }
    }

    public function test_invalid_agency_logo_does_not_replace_existing_file(): void
    {
        $this->actingAs($this->user('admin_it'));
        $this->patch(route('admin.web-settings.agency-logo'), [
            'logo' => UploadedFile::fake()->image('first.png', 80, 80),
        ])->assertRedirect();
        $path = WebAppearance::query()->findOrFail(1)->agency_logo_path;

        $this->patch(route('admin.web-settings.agency-logo'), [
            'logo' => UploadedFile::fake()->createWithContent('logo.svg', '<svg/>'),
        ])->assertSessionHasErrors('logo');
        $this->assertSame($path, WebAppearance::query()->findOrFail(1)->agency_logo_path);
        $this->assertTrue(Storage::disk('local')->exists($path));
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }
}
