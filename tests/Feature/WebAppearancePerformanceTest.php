<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\WebAppearance;
use App\Services\WebAppearanceService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class WebAppearancePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_login_and_settings_read_appearance_once_per_request_and_pick_up_changes(): void
    {
        $this->assertAppearanceQueryCount(route('login'));
        $admin = $this->admin();
        $this->actingAs($admin);
        $this->assertAppearanceQueryCount(route('admin.web-settings.edit'));

        WebAppearance::query()->findOrFail(1)->update(['theme' => 'navy']);
        $this->assertAppearanceQueryCount(route('admin.web-settings.edit'), 'navy');
        WebAppearance::query()->findOrFail(1)->update(['theme' => 'modern']);
        $this->assertAppearanceQueryCount(route('admin.web-settings.edit'), 'modern');
    }

    public function test_theme_save_invalidates_the_current_request_snapshot(): void
    {
        $this->assertSame('default', app(WebAppearanceService::class)->theme());
        app(WebAppearanceService::class)->updateTheme('navy', $this->admin());
        $this->assertSame('navy', app(WebAppearanceService::class)->theme());
    }

    public function test_logo_versions_cache_and_revalidate_without_hiding_replacements(): void
    {
        $this->actingAs($this->admin());
        foreach ([
            ['admin.web-settings.update', 'logoUrl', 'branding.logo'],
            ['admin.web-settings.agency-logo', 'agencyLogoUrl', 'branding.agency-logo'],
        ] as [$uploadRoute, $urlMethod, $imageRoute]) {
            $this->patch(route($uploadRoute), ['logo' => UploadedFile::fake()->image('first.png', 80, 80)])
                ->assertRedirect(route('admin.web-settings.edit'));
            $url = app(WebAppearanceService::class)->{$urlMethod}();
            $first = $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
            $this->assertStringContainsString('max-age=3600', $first->headers->get('Cache-Control'));
            $etag = $first->headers->get('ETag');
            $this->assertNotNull($etag);
            $this->get($url, ['If-None-Match' => $etag])->assertStatus(304);

            $this->patch(route($uploadRoute), ['logo' => UploadedFile::fake()->image('second.png', 90, 90)])
                ->assertRedirect(route('admin.web-settings.edit'));
            $replacementUrl = app(WebAppearanceService::class)->{$urlMethod}();
            $this->assertNotSame($url, $replacementUrl);
            $replacement = $this->get($replacementUrl, ['If-None-Match' => $etag])->assertOk();
            $this->assertNotSame($etag, $replacement->headers->get('ETag'));
            $unversioned = $this->get(route($imageRoute))->assertOk();
            $this->assertStringContainsString('max-age=0', $unversioned->headers->get('Cache-Control'));
        }
    }

    private function assertAppearanceQueryCount(string $url, string $theme = 'default'): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $this->get($url)->assertOk()->assertSee('data-web-theme="'.$theme.'"', false);
            $queries = array_filter(DB::getQueryLog(), static fn (array $query): bool => str_contains($query['query'], 'web_appearances'));
            $this->assertCount(1, $queries, 'Pengaturan tampilan cukup dibaca sekali pada setiap request.');
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'admin_it')->firstOrFail());

        return $user;
    }
}
