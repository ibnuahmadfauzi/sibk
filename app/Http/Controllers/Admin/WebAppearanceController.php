<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateWebAppearanceRequest;
use App\Http\Requests\Admin\UpdateWebThemeRequest;
use App\Services\WebAppearanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class WebAppearanceController extends Controller
{
    public function edit(WebAppearanceService $appearance): View
    {
        Gate::authorize('manageDataMaster');

        return view('pages.admin.web-settings', [
            'hasCustomLogo' => $appearance->hasCustomLogo(),
            'hasAgencyLogo' => $appearance->agencyLogoPath() !== null,
            'selectedTheme' => $appearance->theme(),
        ]);
    }

    public function update(UpdateWebAppearanceRequest $request, WebAppearanceService $appearance): RedirectResponse
    {
        $appearance->replaceLogo($request->file('logo'), $request->user());

        return redirect()->route('admin.web-settings.edit')->with('success', 'Logo web berhasil diperbarui.');
    }

    public function updateTheme(UpdateWebThemeRequest $request, WebAppearanceService $appearance): RedirectResponse
    {
        $appearance->updateTheme($request->validated('theme'), $request->user());

        return redirect()->route('admin.web-settings.edit')->with('success', 'Tema web berhasil diperbarui.');
    }

    public function updateAgencyLogo(UpdateWebAppearanceRequest $request, WebAppearanceService $appearance): RedirectResponse
    {
        $appearance->replaceAgencyLogo($request->file('logo'), $request->user());

        return redirect()->route('admin.web-settings.edit')->with('success', 'Logo dinas berhasil diperbarui.');
    }

    public function logo(Request $request, WebAppearanceService $appearance): BinaryFileResponse
    {
        return $this->logoResponse($request, $appearance->logoPath());
    }

    public function agencyLogo(Request $request, WebAppearanceService $appearance): BinaryFileResponse
    {
        $path = $appearance->agencyLogoPath();
        abort_if($path === null, 404);

        return $this->logoResponse($request, $path);
    }

    private function logoResponse(Request $request, string $path): BinaryFileResponse
    {
        $version = hash_file('xxh128', $path);
        $response = response()->file($path, [
            'Content-Type' => 'image/png',
            'X-Content-Type-Options' => 'nosniff',
        ])->setPublic()->setMaxAge($request->query('v') === $version ? 3600 : 0)->setEtag($version);
        $response->isNotModified($request);

        return $response;
    }
}
