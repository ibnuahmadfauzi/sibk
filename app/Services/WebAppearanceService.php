<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\WebAppearance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class WebAppearanceService
{
    public function __construct(private readonly AuditService $audit) {}

    public function logoPath(): string
    {
        $stored = WebAppearance::query()->whereKey(1)->value('logo_path');

        return is_string($stored) && preg_match('/\Abranding\/[A-Za-z0-9]{40}\.png\z/', $stored) === 1
            && Storage::disk('local')->exists($stored)
                ? Storage::disk('local')->path($stored)
                : public_path('assets/images/logo.png');
    }

    public function hasCustomLogo(): bool
    {
        return $this->logoPath() !== public_path('assets/images/logo.png');
    }

    public function replaceLogo(UploadedFile $upload, User $actor): void
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
            DB::transaction(function () use ($path, $actor): void {
                $appearance = WebAppearance::query()->whereKey(1)->lockForUpdate()->firstOrFail();
                $previous = $appearance->logo_path;
                $appearance->logo_path = $path;
                $appearance->save();
                $this->audit->record(
                    'web_appearance.logo_updated',
                    $appearance,
                    'Logo web diperbarui.',
                    $actor,
                    ['logo_set' => $previous !== null],
                    ['logo_set' => true],
                );

            });
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }

        // shortcut: logo lama dipertahankan untuk ekspor berjalan; bersihkan saat pemeliharaan jika unggahan menumpuk.
    }
}
