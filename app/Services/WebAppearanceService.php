<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\WebAppearance;
use App\Support\WebThemePalette;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class WebAppearanceService
{
    public function __construct(private readonly AuditService $audit) {}

    public function logoPath(): string
    {
        return $this->storedLogoPath('logo_path') ?? public_path('assets/images/logo.png');
    }

    public function agencyLogoPath(): ?string
    {
        return $this->storedLogoPath('agency_logo_path');
    }

    private function storedLogoPath(string $column): ?string
    {
        $stored = WebAppearance::query()->whereKey(1)->value($column);

        return is_string($stored) && preg_match('/\Abranding\/[A-Za-z0-9]{40}\.png\z/', $stored) === 1
            && Storage::disk('local')->exists($stored)
                ? Storage::disk('local')->path($stored)
                : null;
    }

    public function hasCustomLogo(): bool
    {
        return $this->logoPath() !== public_path('assets/images/logo.png');
    }

    public function theme(): string
    {
        $stored = WebAppearance::query()->whereKey(1)->value('theme');

        return in_array($stored, WebAppearance::THEMES, true)
            ? $stored : 'default';
    }

    /** @return array<string, string> */
    public function themeVariables(): array
    {
        $theme = $this->theme();
        if ($theme === 'default') {
            return [];
        }

        return WebThemePalette::variables(WebThemePalette::PRESETS[$theme]);
    }

    public function updateTheme(string $theme, User $actor): void
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        if (! in_array($theme, WebAppearance::THEMES, true)) {
            throw ValidationException::withMessages(['theme' => 'Pilih tema yang tersedia.']);
        }
        DB::transaction(function () use ($theme, $actor): void {
            $appearance = WebAppearance::query()->whereKey(1)->lockForUpdate()->firstOrFail();
            $before = ['theme' => $appearance->theme];
            $after = ['theme' => $theme];
            if ($before === $after) {
                return;
            }

            $appearance->theme = $theme;
            $appearance->save();
            $this->audit->record(
                'web_appearance.theme_updated',
                $appearance,
                'Tema web diperbarui.',
                $actor,
                $before,
                $after,
            );
        });
    }

    public function replaceLogo(UploadedFile $upload, User $actor): void
    {
        $this->replaceBrandingLogo($upload, $actor, 'logo_path');
    }

    public function replaceAgencyLogo(UploadedFile $upload, User $actor): void
    {
        $this->replaceBrandingLogo($upload, $actor, 'agency_logo_path');
    }

    private function replaceBrandingLogo(UploadedFile $upload, User $actor, string $column): void
    {
        $bytes = $upload->get();
        $details = @getimagesizefromstring($bytes);
        if ($details === false || ! in_array($details[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            throw ValidationException::withMessages(['logo' => 'Berkas logo tidak dapat dibaca sebagai PNG atau JPEG.']);
        }
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw ValidationException::withMessages(['logo' => 'Berkas logo tidak dapat dibaca sebagai PNG atau JPEG.']);
        }

        $disk = Storage::disk('local');
        $path = 'branding/'.Str::random(40).'.png';
        $disk->makeDirectory('branding');
        imagealphablending($image, false);
        imagesavealpha($image, true);

        try {
            if (! imagepng($image, $disk->path($path))) {
                throw new RuntimeException('Logo tidak dapat disimpan.');
            }
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        } finally {
            imagedestroy($image);
        }

        try {
            DB::transaction(function () use ($path, $actor, $column): void {
                $appearance = WebAppearance::query()->whereKey(1)->lockForUpdate()->firstOrFail();
                $previous = $appearance->{$column};
                $appearance->{$column} = $path;
                $appearance->save();
                $this->audit->record(
                    $column === 'logo_path' ? 'web_appearance.logo_updated' : 'web_appearance.agency_logo_updated',
                    $appearance,
                    $column === 'logo_path' ? 'Logo web diperbarui.' : 'Logo dinas diperbarui.',
                    $actor,
                    [$column => $previous !== null],
                    [$column => true],
                );

            });
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }

        // shortcut: logo lama dipertahankan untuk ekspor berjalan; bersihkan saat pemeliharaan jika unggahan menumpuk.
    }
}
