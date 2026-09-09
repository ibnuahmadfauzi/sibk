<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IntegrationActionRequest;
use App\Http\Requests\Admin\UpdateIntegrationSettingRequest;
use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationProbeResult;
use App\Models\User;
use App\Services\IntegrationSettingService;
use Illuminate\Http\RedirectResponse;

class IntegrationSettingController extends Controller
{
    public function update(
        UpdateIntegrationSettingRequest $request,
        IntegrationSettingService $service,
        string $provider,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->setting();
        unset($data['current_password']);

        try {
            $service->save($provider, $data, $actor);
        } catch (IntegrationConfigurationException $exception) {
            return $this->failure($provider, $exception->resultCode());
        }

        return $this->success($provider, 'Konfigurasi koneksi berhasil disimpan.');
    }

    public function test(
        IntegrationActionRequest $request,
        IntegrationSettingService $service,
        string $provider,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        try {
            $state = $service->testConnection($provider, $actor);
        } catch (IntegrationConfigurationException $exception) {
            return $this->failure($provider, $exception->resultCode());
        }

        return $state->lastTestCode === IntegrationProbeResult::CODE_SUCCESS
            ? $this->success($provider, 'Uji koneksi berhasil.')
            : $this->failure($provider, $state->lastTestCode ?? 'configuration_changed');
    }

    public function activate(
        IntegrationActionRequest $request,
        IntegrationSettingService $service,
        string $provider,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        try {
            $service->activate($provider, $actor);
        } catch (IntegrationConfigurationException $exception) {
            return $this->failure($provider, $exception->resultCode());
        }

        return $this->success($provider, 'Koneksi berhasil diaktifkan.');
    }

    public function deactivate(
        IntegrationActionRequest $request,
        IntegrationSettingService $service,
        string $provider,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        try {
            $service->deactivate($provider, $actor);
        } catch (IntegrationConfigurationException $exception) {
            return $this->failure($provider, $exception->resultCode());
        }

        return $this->success($provider, 'Koneksi berhasil dinonaktifkan.');
    }

    private function success(string $provider, string $message): RedirectResponse
    {
        return $this->redirect($provider)->with('success', $message);
    }

    private function failure(string $provider, string $code): RedirectResponse
    {
        return $this->redirect($provider)->withErrors(['integration' => $this->messageFor($code)]);
    }

    private function redirect(string $provider): RedirectResponse
    {
        return redirect()
            ->to(route('data-master.index').'#integration-'.$provider)
            ->header('Cache-Control', 'no-store');
    }

    private function messageFor(string $code): string
    {
        return match ($code) {
            IntegrationProbeResult::CODE_ADAPTER_UNAVAILABLE => 'Adapter integrasi belum tersedia.',
            'incomplete_configuration' => 'Konfigurasi koneksi belum lengkap.',
            'endpoint_not_allowed' => 'URL endpoint tidak diizinkan.',
            'credential_unreadable' => 'Token tersimpan tidak dapat dibaca. Simpan token baru.',
            'busy' => 'Proses koneksi lain sedang berjalan.',
            'timeout' => 'Uji koneksi melewati batas waktu.',
            'authentication_rejected' => 'Autentikasi koneksi ditolak oleh sumber data.',
            'rate_limited' => 'Sumber data membatasi uji koneksi. Coba beberapa saat lagi.',
            'remote_unavailable' => 'Sumber data sedang tidak tersedia.',
            'contract_invalid' => 'Kontrak sumber data belum valid.',
            'source_identity_mismatch' => 'Identitas sumber data tidak sesuai.',
            'response_too_large' => 'Respons sumber data melebihi batas aman.',
            'connection_failed' => 'Uji koneksi gagal.',
            default => 'Konfigurasi koneksi berubah atau tidak valid. Periksa lalu coba kembali.',
        };
    }
}
