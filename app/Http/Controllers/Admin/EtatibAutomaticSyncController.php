<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfigureAutomaticEtatibRequest;
use App\Http\Requests\Admin\DisableAutomaticEtatibRequest;
use App\Models\ExternalSyncRun;
use App\Models\User;
use App\Services\EtatibAutomaticSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class EtatibAutomaticSyncController extends Controller
{
    public function store(
        ConfigureAutomaticEtatibRequest $request,
        EtatibAutomaticSyncService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $run = $service->activate($request->apiUrl(), $actor, $request->session()->pull('etatib_api_preview'));

        if ($run->status === ExternalSyncRun::STATUS_FAILED) {
            return back()->withErrors([
                'etatib_automatic' => $run->summary ?? 'Pembaruan otomatis e-Tatib gagal diaktifkan.',
            ], 'etatib_automatic');
        }

        return back()->with(
            $run->status === ExternalSyncRun::STATUS_WARNING ? 'warning' : 'success',
            $run->status === ExternalSyncRun::STATUS_WARNING
                ? 'Data berhasil disinkronkan dan pembaruan otomatis diaktifkan, tetapi ada konflik yang perlu diperiksa.'
                : 'Data berhasil disinkronkan dan pembaruan otomatis e-Tatib telah aktif.',
        );
    }

    public function synchronize(Request $request, EtatibAutomaticSyncService $service): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('manageDataMaster');
        /** @var User $actor */
        $actor = $request->user();
        $run = $service->synchronizeNow($actor);

        if ($run->status === ExternalSyncRun::STATUS_FAILED) {
            return back()->withErrors([
                'etatib_automatic' => $run->summary ?? 'Pembaruan e-Tatib gagal.',
            ], 'etatib_automatic');
        }

        return back()->with(
            $run->status === ExternalSyncRun::STATUS_WARNING ? 'warning' : 'success',
            $run->summary,
        );
    }

    public function destroy(
        DisableAutomaticEtatibRequest $request,
        EtatibAutomaticSyncService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $service->deactivate($actor);

        return back()->with('success', 'Pembaruan otomatis e-Tatib dinonaktifkan dan link tersimpan dihapus.');
    }
}
