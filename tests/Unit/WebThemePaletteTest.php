<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\WebThemePalette;
use PHPUnit\Framework\TestCase;

final class WebThemePaletteTest extends TestCase
{
    public function test_default_custom_palette_and_presets_meet_contrast_requirements(): void
    {
        foreach ([WebThemePalette::DEFAULT_COLORS, ...array_values(WebThemePalette::PRESETS)] as $palette) {
            $this->assertSame([], WebThemePalette::errors($palette));
        }
    }

    public function test_validation_rejects_unsafe_color_pairs_and_non_hex_values(): void
    {
        $palette = WebThemePalette::DEFAULT_COLORS;
        $palette['primary'] = '#2f6fc6';
        $this->assertArrayHasKey('custom_palette.primary', WebThemePalette::errors($palette));
        $palette['primary'] = '#2659a0';
        $this->assertSame([], WebThemePalette::errors($palette));
        $palette['surface'] = '#000000';
        $this->assertArrayHasKey('custom_palette.surface', WebThemePalette::errors($palette));
        $palette['surface'] = 'url(javascript:alert(1))';
        $this->assertArrayHasKey('custom_palette.surface', WebThemePalette::errors($palette));
    }

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

    public function test_hero_metadata_uses_validated_white_text_even_at_contrast_boundary(): void
    {
        $palette = ['primary' => '#767676', 'page' => '#ffffff', 'surface' => '#ffffff'];
        $this->assertSame([], WebThemePalette::errors($palette));
        $this->assertSame('#ffffff', WebThemePalette::variables($palette)['--sibk-color-primary-hero-contrast']);
    }
}
