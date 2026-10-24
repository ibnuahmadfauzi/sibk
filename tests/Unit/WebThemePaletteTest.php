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

    public function test_named_presets_keep_brand_roles_independent_and_contrasting(): void
    {
        $this->assertSame(['navy', 'smkn1', 'modern'], array_keys(WebThemePalette::PRESETS));

        foreach (WebThemePalette::PRESETS as $name => $palette) {
            $variables = WebThemePalette::variables($palette);
            $this->assertSame($palette['sidebar'], $variables['--sibk-color-sidebar-gradient-start'], $name);
            $this->assertSame($palette['primary'], $variables['--sibk-color-primary'], $name);
            $this->assertSame($palette['secondary'], $variables['--sibk-color-secondary'], $name);
            $this->assertSame('#ffffff', $variables['--sibk-color-primary-hero-contrast'], $name);

            foreach ([
                '--sibk-color-primary-gradient-start', '--sibk-color-primary-gradient-end',
                '--sibk-color-primary-pressed-hover', '--sibk-color-primary-pressed',
                '--sibk-color-secondary-gradient-start', '--sibk-color-secondary-gradient-end',
                '--sibk-color-secondary-hover',
                '--sibk-color-sidebar-gradient-start', '--sibk-color-sidebar-gradient-mid',
                '--sibk-color-sidebar-gradient-end',
            ] as $token) {
                $this->assertGreaterThanOrEqual(4.5, self::contrast('#ffffff', $variables[$token]), "$name $token");
            }
            foreach (['--sibk-color-page', '--sibk-color-surface-raised'] as $token) {
                $this->assertGreaterThanOrEqual(3, self::contrast($variables['--sibk-focus-ring'], $variables[$token]), "$name $token focus");
            }
        }
    }

    private static function contrast(string $first, string $second): float
    {
        $luminance = static function (string $hex): float {
            $channels = array_map(
                static function (int $value): float {
                    $channel = $value / 255;

                    return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
                },
                array_map('hexdec', str_split(substr($hex, 1), 2)),
            );

            return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        };

        $a = $luminance($first);
        $b = $luminance($second);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }
}
