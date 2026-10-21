<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\WebAppearance;
use App\Services\AuditService;
use App\Services\WebAppearanceService;
use App\Support\WebThemePalette;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class WebThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_only_active_admin_can_change_theme(): void
    {
        $url = route('admin.web-settings.theme');
        $this->patch($url, ['theme' => 'pine'])->assertRedirect(route('login'));

        foreach (['guru_bk', 'koordinator_bk', 'waka_kesiswaan'] as $role) {
            $this->actingAs($this->user($role))->patch($url, ['theme' => 'pine'])->assertForbidden();
        }

        $inactive = $this->user('admin_it');
        $inactive->update(['is_active' => false]);
        $this->actingAs($inactive)->patch($url, ['theme' => 'pine'])->assertRedirect(route('login'));
        $this->assertSame('default', WebAppearance::query()->findOrFail(1)->theme);
    }

    public function test_admin_can_choose_and_reset_theme_with_audit(): void
    {
        $admin = $this->user('admin_it');
        $url = route('admin.web-settings.theme');

        foreach (['pine', 'slate', 'default'] as $theme) {
            $this->actingAs($admin)->patch($url, ['theme' => $theme])
                ->assertRedirect(route('admin.web-settings.edit'));
            $this->assertSame($theme, WebAppearance::query()->findOrFail(1)->theme);
            $this->assertSame($theme, app(WebAppearanceService::class)->theme());
            $this->get(route('admin.web-settings.edit'))->assertOk()
                ->assertSee('data-web-theme="'.$theme.'"', false)
                ->assertSee('Tema warna')
                ->assertSee('Kembalikan ke default');
        }

        $this->assertSame(3, AuditLog::query()->where('action', 'web_appearance.theme_updated')->count());
        $latest = AuditLog::query()->where('action', 'web_appearance.theme_updated')->latest('id')->firstOrFail();
        $this->assertSame(['theme' => 'slate'], $latest->before_values);
        $this->assertSame(['theme' => 'default'], $latest->after_values);
        $this->assertSame($admin->id, $latest->actor_id);
    }

    public function test_invalid_theme_is_rejected_and_stored_value_is_safe(): void
    {
        $this->actingAs($this->user('admin_it'));
        $appearance = WebAppearance::query()->findOrFail(1);
        $appearance->update(['logo_path' => 'branding/existing.png']);

        foreach (['<style>*{display:none}</style>', ''] as $theme) {
            $this->patch(route('admin.web-settings.theme'), ['theme' => $theme])
                ->assertSessionHasErrors('theme');
        }
        $this->assertSame('default', $appearance->fresh()->theme);
        $this->assertSame('branding/existing.png', $appearance->fresh()->logo_path);
        $this->assertSame(0, AuditLog::query()->where('action', 'web_appearance.theme_updated')->count());

        $appearance->update(['theme' => 'unknown']);
        $this->assertSame('default', app(WebAppearanceService::class)->theme());
    }

    public function test_custom_palette_can_be_saved_and_survives_preset_and_reset(): void
    {
        $this->actingAs($this->user('admin_it'));
        $url = route('admin.web-settings.theme');
        $palette = ['primary' => '#2659a0', 'page' => '#f8f0ea', 'surface' => '#fdfbf9'];
        $this->patch($url, ['theme' => 'custom', 'custom_palette' => $palette])
            ->assertRedirect(route('admin.web-settings.edit'));

        $appearance = WebAppearance::query()->findOrFail(1);
        $this->assertSame($palette, $appearance->custom_palette);
        $this->assertSame('custom', app(WebAppearanceService::class)->theme());
        $this->assertSame($palette, app(WebAppearanceService::class)->customPalette());
        $this->assertSame($palette['primary'], app(WebAppearanceService::class)->themeVariables()['--sibk-color-primary']);
        $this->assertSame($palette, AuditLog::query()->where('action', 'web_appearance.theme_updated')->firstOrFail()->after_values['custom_palette']);

        foreach (['pine', 'default'] as $theme) {
            $this->patch($url, ['theme' => $theme, 'custom_palette' => ['primary' => '<script>']])
                ->assertRedirect(route('admin.web-settings.edit'));
            $this->assertSame($palette, $appearance->fresh()->custom_palette);
        }
        $this->assertSame([], app(WebAppearanceService::class)->themeVariables());
    }

    public function test_custom_palette_rejects_malformed_unknown_and_low_contrast_colors(): void
    {
        $this->actingAs($this->user('admin_it'));
        $url = route('admin.web-settings.theme');
        foreach ([
            [['primary' => '#ffffff', 'page' => '#f8f0ea', 'surface' => '#fdfbf9'], 'custom_palette.primary'],
            [['primary' => '#2659a0', 'page' => '#000000', 'surface' => '#fdfbf9'], 'custom_palette.page'],
            [['primary' => '#2659a0', 'page' => '#f8f0ea', 'surface' => 'red'], 'custom_palette.surface'],
            [WebThemePalette::DEFAULT_COLORS + ['extra' => '#ffffff'], 'custom_palette'],
        ] as [$palette, $field]) {
            $this->patch($url, ['theme' => 'custom', 'custom_palette' => $palette])
                ->assertSessionHasErrors($field);
        }
        $this->assertSame('default', WebAppearance::query()->findOrFail(1)->theme);
        $this->assertNull(WebAppearance::query()->findOrFail(1)->custom_palette);
    }

    public function test_service_rejects_invalid_custom_palette_and_stored_invalid_palette_falls_back(): void
    {
        $admin = $this->user('admin_it');
        $service = app(WebAppearanceService::class);
        try {
            $service->updateTheme('custom', $admin, ['primary' => '#ffffff', 'page' => '#f8f0ea', 'surface' => '#fdfbf9']);
            $this->fail('Expected palette validation to fail.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('custom_palette.primary', $exception->errors());
        }

        WebAppearance::query()->findOrFail(1)->update(['theme' => 'custom', 'custom_palette' => ['primary' => 'var(--injected)']]);
        $this->assertSame('default', $service->theme());
        $this->assertSame([], $service->themeVariables());
        $this->assertSame(WebThemePalette::DEFAULT_COLORS, $service->customPalette());
    }

    public function test_login_uses_saved_theme_and_reports_keep_plain_document_layout(): void
    {
        WebAppearance::query()->findOrFail(1)->update(['theme' => 'pine']);
        $this->get(route('login'))->assertOk()->assertSee('data-web-theme="pine"', false);
        $this->actingAs($this->user('guru_bk'))->get(route('access.denied'))
            ->assertOk()->assertSee('data-web-theme="pine"', false);
        $document = view('layouts.report-embedded')->render();
        $this->assertStringNotContainsString('data-web-theme', $document);
    }

    public function test_custom_colors_render_safely_and_default_removes_every_override(): void
    {
        $appearance = WebAppearance::query()->findOrFail(1);
        $palette = ['primary' => '#5b3678', 'page' => '#f5f0fa', 'surface' => '#fdfaff'];
        $appearance->update(['theme' => 'custom', 'custom_palette' => $palette]);
        $this->get(route('login'))->assertOk()
            ->assertSee('data-web-theme="custom"', false)
            ->assertSee('--sibk-color-primary: #5b3678;', false)
            ->assertSee('--sibk-color-page: #f5f0fa;', false)
            ->assertSee('--sibk-color-surface-raised: #fdfaff;', false);
        $appearance->update(['theme' => 'default']);
        $this->get(route('login'))->assertOk()->assertDontSee('data-web-theme-colors');
        $this->assertSame($palette, $appearance->fresh()->custom_palette);
        $appearance->update(['theme' => 'custom', 'custom_palette' => ['primary' => '</style><script>bad()</script>']]);
        $this->get(route('login'))->assertOk()->assertDontSee('data-web-theme-colors')
            ->assertDontSee('bad()', false);
    }

    public function test_audit_failure_rolls_back_theme(): void
    {
        $this->actingAs($this->user('admin_it'));
        $this->mock(AuditService::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('audit failed'));

        try {
            $this->withoutExceptionHandling()->patch(route('admin.web-settings.theme'), ['theme' => 'pine']);
            $this->fail('Expected the audit write to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit failed', $exception->getMessage());
        }
        $this->assertSame('default', WebAppearance::query()->findOrFail(1)->theme);
    }

    public function test_audit_failure_rolls_back_custom_palette_change(): void
    {
        $this->actingAs($this->user('admin_it'));
        $appearance = WebAppearance::query()->findOrFail(1);
        $appearance->update(['theme' => 'custom', 'custom_palette' => WebThemePalette::DEFAULT_COLORS]);
        $this->mock(AuditService::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('audit failed'));

        try {
            $this->withoutExceptionHandling()->patch(route('admin.web-settings.theme'), [
                'theme' => 'custom',
                'custom_palette' => ['primary' => '#1f6658', 'page' => '#edf4f0', 'surface' => '#f8fcf9'],
            ]);
            $this->fail('Expected the audit write to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit failed', $exception->getMessage());
        }
        $this->assertSame('custom', $appearance->fresh()->theme);
        $this->assertSame(WebThemePalette::DEFAULT_COLORS, $appearance->fresh()->custom_palette);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }
}
