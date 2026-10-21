<?php

declare(strict_types=1);

namespace App\Support;

final class WebThemePalette
{
    public const PRESETS = [
        'pine' => ['primary' => '#1f6658', 'page' => '#edf4f0', 'surface' => '#f8fcf9'],
        'slate' => ['primary' => '#465a75', 'page' => '#eff2f6', 'surface' => '#f8fafc'],
    ];

    public const DEFAULT_COLORS = ['primary' => '#2659a0', 'page' => '#f8f0ea', 'surface' => '#fdfbf9'];

    /** @return array<string, string> */
    public static function errors(array $palette): array
    {
        $errors = [];
        foreach (array_keys($palette) as $key) {
            if (! in_array($key, ['primary', 'page', 'surface'], true)) {
                $errors['custom_palette'] = 'Palet hanya boleh berisi warna utama, halaman, dan permukaan.';
            }
        }

        foreach (['primary' => 'utama', 'page' => 'halaman', 'surface' => 'permukaan'] as $key => $label) {
            if (! isset($palette[$key]) || ! is_string($palette[$key]) || preg_match('/\A#[0-9a-fA-F]{6}\z/D', $palette[$key]) !== 1) {
                $errors['custom_palette.'.$key] = 'Warna '.$label.' harus berupa kode #RRGGBB.';
            }
        }
        if ($errors !== []) {
            return $errors;
        }

        foreach (['#ffffff' => 'teks putih', $palette['page'] => 'halaman', $palette['surface'] => 'permukaan'] as $background => $label) {
            if (self::contrast($palette['primary'], $background) < 4.5) {
                $errors['custom_palette.primary'] = 'Warna utama harus memiliki kontras minimal 4,5:1 terhadap '.$label.'.';
            }
        }
        foreach (['page' => 'halaman', 'surface' => 'permukaan'] as $key => $label) {
            foreach (['#0d2447' => 'teks utama', '#5f6f86' => 'teks sekunder'] as $foreground => $text) {
                if (self::contrast($foreground, $palette[$key]) < 4.5) {
                    $errors['custom_palette.'.$key] = 'Warna '.$label.' harus memiliki kontras minimal 4,5:1 terhadap '.$text.'.';
                }
            }
        }

        return $errors;
    }

    /** @return array<string, string> */
    public static function variables(array $palette): array
    {
        $brand = $palette['primary'];
        $page = $palette['page'];
        $surface = $palette['surface'];
        $hover = self::mix('#000000', $brand, 15);
        $shadow = self::mix('#000000', $page, 20);
        $white = 'rgba(255, 255, 255, 0.95)';

        return [
            '--sibk-color-primary' => $brand,
            '--sibk-color-primary-hover' => $hover,
            '--sibk-color-primary-soft' => self::mix('#ffffff', $brand, 92),
            '--sibk-color-primary-gradient-start' => $brand,
            '--sibk-color-primary-gradient-end' => $hover,
            '--sibk-color-primary-pressed-hover' => self::mix('#000000', $brand, 20),
            '--sibk-color-primary-pressed' => self::mix('#000000', $brand, 30),
            '--sibk-color-primary-button-soft' => self::mix('#ffffff', $brand, 95),
            '--sibk-color-primary-button-pressed' => self::mix('#ffffff', $brand, 88),
            '--sibk-color-primary-avatar-soft' => self::mix('#ffffff', $brand, 90),
            '--sibk-color-primary-hero-soft' => self::mix('#ffffff', $brand, 94),
            '--sibk-color-primary-label' => $brand,
            '--sibk-color-primary-hero-strong' => $brand,
            '--sibk-color-primary-hero-muted' => self::mix('#475569', $brand, 70),
            '--sibk-color-primary-hero-contrast' => '#ffffff',
            '--sibk-color-primary-panel-hover' => self::mix('#000000', $brand, 40),
            '--sibk-color-primary-panel-info' => self::mix('#ffffff', $brand, 80),
            '--sibk-color-sidebar-card-start' => $brand,
            '--sibk-color-sidebar-gradient-start' => $brand,
            '--sibk-color-sidebar-gradient-mid' => $hover,
            '--sibk-color-sidebar-gradient-end' => self::mix('#000000', $brand, 35),
            '--sibk-color-primary-shadow-pressed-rgb' => self::rgb(self::mix('#000000', $brand, 65)),
            '--sibk-color-sidebar-gradient-mid-rgb' => self::rgb($hover),
            '--sibk-color-sidebar-shadow-rgb' => self::rgb(self::mix('#000000', $brand, 40)),
            '--sibk-color-sidebar-logo-shadow-rgb' => self::rgb(self::mix('#000000', $brand, 40)),
            '--sibk-color-panel-shadow-rgb' => self::rgb(self::mix('#000000', $brand, 65)),
            '--sibk-color-primary-label-rgb' => self::rgb($brand),
            '--sibk-color-page' => $page,
            '--sibk-color-surface' => $page,
            '--sibk-color-field' => $page,
            '--sibk-color-brand-panel' => $page,
            '--sibk-color-surface-raised' => $surface,
            '--sibk-color-surface-plain' => $surface,
            '--sibk-color-surface-hover' => self::mix('#ffffff', $surface, 50),
            '--sibk-color-logo-surface' => self::mix($page, $surface, 50),
            '--sibk-color-border' => self::mix('#000000', $page, 8),
            '--sibk-color-border-light' => self::mix('#000000', $page, 4),
            '--sibk-color-border-cool' => self::mix('#000000', $page, 8),
            '--sibk-color-shadow-warm-rgb' => self::rgb($shadow),
            '--sibk-focus-ring' => self::rgba($brand, 0.35),
            '--sibk-shadow-button' => '5px 5px 12px '.self::rgba(self::mix('#000000', $brand, 25), 0.42).', -4px -4px 10px '.$white.', 0 3px 6px '.self::rgba($brand, 0.3),
            '--sibk-shadow-raised-sm' => '3px 3px 6px '.self::rgba($shadow, 0.45).', -3px -3px 6px '.$white,
            '--sibk-shadow-raised' => '6px 6px 14px '.self::rgba($shadow, 0.5).', -6px -6px 14px '.$white.', 2px 2px 4px '.self::rgba($shadow, 0.25),
            '--sibk-shadow-raised-hover' => '9px 9px 20px '.self::rgba($shadow, 0.6).', -9px -9px 20px rgba(255, 255, 255, 1), 3px 3px 6px '.self::rgba($shadow, 0.3),
            '--sibk-shadow-inset-sm' => 'inset 2px 2px 5px '.self::rgba($shadow, 0.5).', inset -2px -2px 5px '.$white,
            '--sibk-shadow-inset' => 'inset 3px 3px 8px '.self::rgba($shadow, 0.55).', inset -3px -3px 8px '.$white,
            '--sibk-shadow-inset-lg' => 'inset 6px 6px 14px '.self::rgba($shadow, 0.6).', inset -6px -6px 14px rgba(255, 255, 255, 1)',
            '--sibk-shadow-btn-light' => '3px 3px 7px '.self::rgba($shadow, 0.3).', -3px -3px 7px '.$white,
            '--bs-primary-rgb' => self::rgb($brand),
        ];
    }

    private static function contrast(string $first, string $second): float
    {
        $a = self::luminance($first);
        $b = self::luminance($second);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    private static function luminance(string $color): float
    {
        $channels = self::channels($color);
        $linear = array_map(static function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, $channels);

        return $linear[0] * 0.2126 + $linear[1] * 0.7152 + $linear[2] * 0.0722;
    }

    /** @return array<int, int> */
    private static function channels(string $color): array
    {
        return [hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2))];
    }

    private static function rgb(string $color): string
    {
        return implode(', ', self::channels($color));
    }

    private static function rgba(string $color, float $alpha): string
    {
        return 'rgba('.self::rgb($color).', '.$alpha.')';
    }

    private static function mix(string $first, string $second, int $percent): string
    {
        $a = self::channels($first);
        $b = self::channels($second);

        return sprintf('#%02x%02x%02x', ...array_map(
            static fn (int $left, int $right): int => (int) round(($left * $percent + $right * (100 - $percent)) / 100),
            $a,
            $b,
        ));
    }
}
