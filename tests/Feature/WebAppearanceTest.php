<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\WebAppearance;
use App\Services\AuditService;
use App\Services\ReportDocumentExporter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Tests\TestCase;

final class WebAppearanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_only_admin_can_manage_logo(): void
    {
        $teacher = $this->user('guru_bk');

        $this->actingAs($teacher)->get(route('admin.web-settings.edit'))->assertForbidden();
        $this->actingAs($teacher)->patch(route('admin.web-settings.update'), [
            'logo' => UploadedFile::fake()->image('logo.png', 100, 100),
        ])->assertForbidden();
        $this->assertNull(WebAppearance::query()->findOrFail(1)->logo_path);
    }

    public function test_valid_logo_updates_all_image_uses_and_embeds_in_report(): void
    {
        $admin = $this->user('admin_it');
        $upload = UploadedFile::fake()->image('logo.png', 100, 100);
        file_put_contents($upload->getRealPath(), '<?php echo "unsafe";', FILE_APPEND);

        $this->actingAs($admin)->patch(route('admin.web-settings.update'), ['logo' => $upload])
            ->assertRedirect(route('admin.web-settings.edit'));

        $path = WebAppearance::query()->findOrFail(1)->logo_path;
        $this->assertNotNull($path);
        $this->assertTrue(Storage::disk('local')->exists($path));
        $this->assertStringNotContainsString('unsafe', Storage::disk('local')->get($path));
        $this->assertSame(IMAGETYPE_PNG, getimagesize(Storage::disk('local')->path($path))[2]);

        $logo = $this->get(route('branding.logo'))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertSame(Storage::disk('local')->path($path), $logo->getFile()->getPathname());
        $this->get(route('admin.web-settings.edit'))
            ->assertOk()->assertSee(route('branding.logo'));
        $this->get(route('dashboard.preview'))
            ->assertOk()->assertSee('rel="icon" type="image/png" href="'.route('branding.logo').'"', false);
        $this->assertStringContainsString(route('branding.logo'), view('pages.reports.print._letterhead', ['title' => 'Laporan'])->render());

        $document = app(ReportDocumentExporter::class)->export('xlsx', [
            'academic_year' => null,
            'period' => ['label' => 'Semester 1'],
            'document_section_title' => 'Laporan',
            'rows' => [],
        ]);
        $spreadsheet = null;
        try {
            $spreadsheet = IOFactory::load($document['path']);
            $drawing = $spreadsheet->getActiveSheet()->getDrawingCollection();
            $this->assertCount(1, $drawing);
            $this->assertSame('A1', $drawing[0]->getCoordinates());
        } finally {
            $spreadsheet?->disconnectWorksheets();
            unlink($document['path']);
        }

        $this->assertDatabaseHas('audit_logs', ['action' => 'web_appearance.logo_updated', 'actor_id' => $admin->id]);
        $this->assertSame(1, AuditLog::query()->where('action', 'web_appearance.logo_updated')->count());
    }

    public function test_rejected_file_does_not_replace_existing_logo(): void
    {
        $admin = $this->user('admin_it');
        $this->actingAs($admin)->patch(route('admin.web-settings.update'), [
            'logo' => UploadedFile::fake()->image('first.png', 80, 80),
        ])->assertRedirect();
        $oldPath = WebAppearance::query()->findOrFail(1)->logo_path;

        $this->patch(route('admin.web-settings.update'), [
            'logo' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
        ])->assertSessionHasErrors('logo');
        $this->assertSame($oldPath, WebAppearance::query()->findOrFail(1)->logo_path);
        $this->assertTrue(Storage::disk('local')->exists($oldPath));
    }

    public function test_wide_and_tall_logos_stay_inside_report_header_box(): void
    {
        $admin = $this->user('admin_it');

        foreach ([[2048, 16], [16, 2048]] as [$width, $height]) {
            $this->actingAs($admin)->patch(route('admin.web-settings.update'), [
                'logo' => UploadedFile::fake()->image('logo.png', $width, $height),
            ])->assertRedirect();

            $document = app(ReportDocumentExporter::class)->export('xlsx', [
                'academic_year' => null,
                'period' => ['label' => 'Semester 1'],
                'document_section_title' => 'Laporan',
                'rows' => [],
            ]);
            $spreadsheet = null;
            try {
                $spreadsheet = IOFactory::load($document['path']);
                $drawing = $spreadsheet->getActiveSheet()->getDrawingCollection()[0];
                $this->assertGreaterThan(0, $drawing->getWidth());
                $this->assertGreaterThan(0, $drawing->getHeight());
                $this->assertLessThanOrEqual(64, $drawing->getWidth());
                $this->assertLessThanOrEqual(48, $drawing->getHeight());
            } finally {
                $spreadsheet?->disconnectWorksheets();
                unlink($document['path']);
            }
        }
    }

    public function test_failed_audit_rolls_back_logo_change_and_keeps_old_file(): void
    {
        $admin = $this->user('admin_it');
        $this->actingAs($admin)->patch(route('admin.web-settings.update'), [
            'logo' => UploadedFile::fake()->image('first.png', 80, 80),
        ])->assertRedirect();
        $oldPath = WebAppearance::query()->findOrFail(1)->logo_path;

        $this->mock(AuditService::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('audit failed'));
        try {
            $this->withoutExceptionHandling()->patch(route('admin.web-settings.update'), [
                'logo' => UploadedFile::fake()->image('second.png', 80, 80),
            ]);
            $this->fail('Expected the audit write to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit failed', $exception->getMessage());
        }

        $this->assertSame($oldPath, WebAppearance::query()->findOrFail(1)->logo_path);
        $this->assertTrue(Storage::disk('local')->exists($oldPath));
        $this->assertCount(1, Storage::disk('local')->files('branding'));
    }

    public function test_replacing_an_old_logo_preserves_the_file_for_exports_already_reading_it(): void
    {
        $this->actingAs($this->user('admin_it'))->patch(route('admin.web-settings.update'), [
            'logo' => UploadedFile::fake()->image('first.png', 80, 80),
        ])->assertRedirect();
        $oldPath = WebAppearance::query()->findOrFail(1)->logo_path;
        touch(Storage::disk('local')->path($oldPath), time() - 172800);

        $this->patch(route('admin.web-settings.update'), [
            'logo' => UploadedFile::fake()->image('second.png', 80, 80),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertNotSame($oldPath, WebAppearance::query()->findOrFail(1)->logo_path);
        $this->assertTrue(Storage::disk('local')->exists($oldPath));
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }
}
