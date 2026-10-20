<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateWebAppearanceRequest;
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

        return view('pages.admin.web-settings', ['hasCustomLogo' => $appearance->hasCustomLogo()]);
    }

    public function update(UpdateWebAppearanceRequest $request, WebAppearanceService $appearance): RedirectResponse
    {
        $appearance->replaceLogo($request->file('logo'), $request->user());

        return redirect()->route('admin.web-settings.edit')->with('success', 'Logo web berhasil diperbarui.');
    }

    public function logo(Request $request, WebAppearanceService $appearance): BinaryFileResponse
    {
        return response()->file($appearance->logoPath(), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
