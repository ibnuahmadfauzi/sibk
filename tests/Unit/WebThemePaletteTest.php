<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\WebThemePalette;
use PHPUnit\Framework\TestCase;

final class WebThemePaletteTest extends TestCase
{
    public function test_generated_variables_are_allowlisted_and_never_contain_raw_input_keys(): void
    {
        $variables = WebThemePalette::variables(WebThemePalette::DEFAULT_COLORS);
        $this->assertSame('#2659a0', $variables['--sibk-color-primary']);
        $this->assertSame('#f8f0ea', $variables['--sibk-color-page']);
        $this->assertSame('#fdfbf9', $variables['--sibk-color-surface-raised']);
        $this->assertSame('38, 89, 160', $variables['--bs-primary-rgb']);
        $this->assertArrayNotHasKey('--sibk-color-preview-canvas', $variables);
        $this->assertArrayNotHasKey('--sibk-color-text', $variables);
    }

    public function test_preset_hero_uses_white_text(): void
    {
        $palette = WebThemePalette::PRESETS['pine'];
        $this->assertSame('#ffffff', WebThemePalette::variables($palette)['--sibk-color-primary-hero-contrast']);
    }
}
