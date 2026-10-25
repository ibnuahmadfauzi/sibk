<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfigureAutomaticEtatibRequest;
use App\Http\Requests\Admin\DisableAutomaticEtatibRequest;
use App\Http\Requests\Admin\UpdateEtatibAutomaticScheduleRequest;
use App\Integrations\IntegrationBusyException;
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
        $url = $request->apiUrl();
        $preview = $request->session()->pull('etatib_api_preview');
        try {
            $run = $service->activate($url, $actor, $preview, $request->identityDecisions(), $request->duplicateDecisions(), $request->boolean('use_saved_api'));
        } catch (IntegrationBusyException) {
            if (is_array($preview)) {
                $request->session()->put('etatib_api_preview', $preview);
            }

            return back()->withErrors(['etatib_automatic' => 'Sinkronisasi e-Tatib sedang berjalan. Coba lagi setelah selesai.'], 'etatib_automatic');
        }

        if ($run->status === ExternalSyncRun::STATUS_FAILED) {
            return back()->withErrors([
                'etatib_automatic' => $run->summary ?? 'Pembaruan otomatis e-Tatib gagal diaktifkan.',
            ], 'etatib_automatic');
        }

        return back()->with(
            $run->status === ExternalSyncRun::STATUS_WARNING ? 'warning' : 'success',
            $run->status === ExternalSyncRun::STATUS_WARNING
                ? 'Data berhasil disinkronkan dan pembaruan otomatis diaktifkan. '.$run->summary
                : 'Data berhasil disinkronkan dan pembaruan otomatis e-Tatib telah aktif.',
        );
    }

    public function synchronize(Request $request, EtatibAutomaticSyncService $service): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('manageDataMaster');
        /** @var User $actor */
        $actor = $request->user();
        try {
            $run = $service->synchronizeNow($actor);
        } catch (IntegrationBusyException) {
            return back()->withErrors(['etatib_automatic' => 'Sinkronisasi e-Tatib sedang berjalan. Coba lagi setelah selesai.'], 'etatib_automatic');
        }

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
        try {
            $service->deactivate($actor);
        } catch (IntegrationBusyException) {
            return back()->withErrors(['etatib_automatic' => 'Sinkronisasi e-Tatib sedang berjalan. Coba lagi setelah selesai.'], 'etatib_automatic');
        }

        return back()->with('success', 'Pembaruan otomatis e-Tatib dinonaktifkan. Link tersimpan tetap tersedia.');
    }

    public function updateSchedule(
        UpdateEtatibAutomaticScheduleRequest $request,
        EtatibAutomaticSyncService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        try {
            $service->updateSchedule($request->days(), $request->times(), $actor);
        } catch (IntegrationBusyException) {
            return redirect()->route('data-master.index', ['tab' => 'etatib'])
                ->withErrors(['etatib_schedule' => 'Sinkronisasi e-Tatib sedang berjalan. Coba lagi setelah selesai.'], 'etatib_schedule')
                ->header('Cache-Control', 'no-store');
        }

        return redirect()->route('data-master.index', ['tab' => 'etatib'])
            ->with('success', 'Jadwal pembaruan otomatis e-Tatib berhasil disimpan.')
            ->header('Cache-Control', 'no-store');
    }
}
