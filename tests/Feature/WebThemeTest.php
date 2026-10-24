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
use Illuminate\Validation\ValidationException;
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
        $this->patch($url, ['theme' => 'navy'])->assertRedirect(route('login'));

        foreach (['guru_bk', 'koordinator_bk', 'waka_kesiswaan'] as $role) {
            $this->actingAs($this->user($role))->patch($url, ['theme' => 'navy'])->assertForbidden();
        }

        $inactive = $this->user('admin_it');
        $inactive->update(['is_active' => false]);
        $this->actingAs($inactive)->patch($url, ['theme' => 'navy'])->assertRedirect(route('login'));
        $this->assertSame('default', WebAppearance::query()->findOrFail(1)->theme);
    }

    public function test_admin_can_choose_and_reset_theme_with_audit(): void
    {
        $admin = $this->user('admin_it');
        $url = route('admin.web-settings.theme');

        foreach (['navy', 'smkn1', 'modern', 'default'] as $theme) {
            $this->actingAs($admin)->patch($url, ['theme' => $theme])
                ->assertRedirect(route('admin.web-settings.edit'));
            $this->assertSame($theme, WebAppearance::query()->findOrFail(1)->theme);
            $this->assertSame($theme, app(WebAppearanceService::class)->theme());
            $response = $this->get(route('admin.web-settings.edit'))->assertOk()
                ->assertSee('data-web-theme="'.$theme.'"', false)
                ->assertSee('Tema warna')
                ->assertSee('Navy')
                ->assertSee('Logo SMKN 1')
                ->assertSee('Modern Logo')
                ->assertSee('Kembalikan ke default');
            $this->assertSame(4, substr_count($response->getContent(), 'type="radio" name="theme"'));
        }

        $this->assertSame(4, AuditLog::query()->where('action', 'web_appearance.theme_updated')->count());
        $latest = AuditLog::query()->where('action', 'web_appearance.theme_updated')->latest('id')->firstOrFail();
        $this->assertSame(['theme' => 'modern'], $latest->before_values);
        $this->assertSame(['theme' => 'default'], $latest->after_values);
        $this->assertSame($admin->id, $latest->actor_id);
    }

    public function test_invalid_theme_is_rejected_and_stored_value_is_safe(): void
    {
        $this->actingAs($this->user('admin_it'));
        $appearance = WebAppearance::query()->findOrFail(1);
        $appearance->update(['logo_path' => 'branding/existing.png']);

        foreach (['<style>*{display:none}</style>', 'custom', 'pine', 'slate', ''] as $theme) {
            $this->patch(route('admin.web-settings.theme'), ['theme' => $theme])
                ->assertSessionHasErrors('theme');
        }
        $this->assertSame('default', $appearance->fresh()->theme);
        $this->assertSame('branding/existing.png', $appearance->fresh()->logo_path);
        $this->assertSame(0, AuditLog::query()->where('action', 'web_appearance.theme_updated')->count());

        foreach (['unknown', 'pine', 'slate'] as $legacyTheme) {
            $appearance->update(['theme' => $legacyTheme]);
            $this->get(route('admin.web-settings.edit'))->assertOk()
                ->assertSee('data-web-theme="default"', false)
                ->assertDontSee('data-web-theme-colors');
            $this->assertSame('default', app(WebAppearanceService::class)->theme());
            $this->assertSame([], app(WebAppearanceService::class)->themeVariables());
        }
    }

    public function test_custom_palette_submissions_are_rejected_and_legacy_colors_remain_untouched(): void
    {
        $this->actingAs($this->user('admin_it'));
        $url = route('admin.web-settings.theme');
        $palette = WebThemePalette::DEFAULT_COLORS;
        $appearance = WebAppearance::query()->findOrFail(1);
        $appearance->update(['custom_palette' => $palette]);
        $this->patch($url, ['theme' => 'custom', 'custom_palette' => $palette])
            ->assertSessionHasErrors('theme');
        $this->patch($url, ['theme' => 'navy', 'custom_palette' => $palette])
            ->assertSessionHasErrors('custom_palette');

        foreach (['navy', 'default'] as $theme) {
            $this->patch($url, ['theme' => $theme])
                ->assertRedirect(route('admin.web-settings.edit'));
            $this->assertSame($palette, $appearance->fresh()->custom_palette);
        }
        $this->assertSame([], app(WebAppearanceService::class)->themeVariables());
        $this->assertSame(2, AuditLog::query()->where('action', 'web_appearance.theme_updated')->count());
    }

    public function test_service_rejects_custom_and_saved_custom_theme_falls_back(): void
    {
        $admin = $this->user('admin_it');
        $service = app(WebAppearanceService::class);
        try {
            $service->updateTheme('custom', $admin);
            $this->fail('Expected theme validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('theme', $exception->errors());
        }

        WebAppearance::query()->findOrFail(1)->update(['theme' => 'custom', 'custom_palette' => WebThemePalette::DEFAULT_COLORS]);
        $this->assertSame('default', $service->theme());
        $this->assertSame([], $service->themeVariables());
        $this->get(route('login'))->assertOk()
            ->assertSee('data-web-theme="default"', false)
            ->assertDontSee('data-web-theme-colors');
        $this->actingAs($admin)->get(route('admin.web-settings.edit'))->assertOk()
            ->assertDontSee('Palet custom')
            ->assertDontSee('data-web-theme-form');
    }

    public function test_login_uses_saved_theme_and_reports_keep_plain_document_layout(): void
    {
        WebAppearance::query()->findOrFail(1)->update(['theme' => 'modern']);
        $this->get(route('login'))->assertOk()->assertSee('data-web-theme="modern"', false);
        $this->actingAs($this->user('guru_bk'))->get(route('access.denied'))
            ->assertOk()->assertSee('data-web-theme="modern"', false);
        $document = view('layouts.report-embedded')->render();
        $this->assertStringNotContainsString('data-web-theme', $document);
    }

    public function test_preset_colors_render_and_default_removes_every_override(): void
    {
        $appearance = WebAppearance::query()->findOrFail(1);
        $appearance->update(['theme' => 'modern']);
        $this->get(route('login'))->assertOk()
            ->assertSee('data-web-theme="modern"', false)
            ->assertSee('--sibk-color-primary: #c2185b;', false)
            ->assertSee('--sibk-color-secondary: #007c91;', false)
            ->assertSee('--sibk-color-sidebar-gradient-start: #2a3e7c;', false)
            ->assertSee('--sibk-color-page: #f8fafc;', false)
            ->assertSee('--sibk-color-surface-raised: #ffffff;', false);
        $appearance->update(['theme' => 'default']);
        $this->get(route('login'))->assertOk()->assertDontSee('data-web-theme-colors');
    }

    public function test_audit_failure_rolls_back_theme(): void
    {
        $this->actingAs($this->user('admin_it'));
        $this->mock(AuditService::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('audit failed'));

        try {
            $this->withoutExceptionHandling()->patch(route('admin.web-settings.theme'), ['theme' => 'navy']);
            $this->fail('Expected the audit write to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit failed', $exception->getMessage());
        }
        $this->assertSame('default', WebAppearance::query()->findOrFail(1)->theme);
    }

    public function test_audit_failure_rolls_back_legacy_custom_theme_switch(): void
    {
        $this->actingAs($this->user('admin_it'));
        $appearance = WebAppearance::query()->findOrFail(1);
        $appearance->update(['theme' => 'custom', 'custom_palette' => WebThemePalette::DEFAULT_COLORS]);
        $this->mock(AuditService::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('audit failed'));

        try {
            $this->withoutExceptionHandling()->patch(route('admin.web-settings.theme'), ['theme' => 'navy']);
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
