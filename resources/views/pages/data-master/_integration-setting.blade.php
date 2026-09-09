@php
    $connectionPresentation = static fn (string $state): array => match ($state) {
        \App\Integrations\IntegrationSettingState::STATE_ACTIVE => ['Aktif', 'success', 'Koneksi siap dipakai saat sinkronisasi tersedia.'],
        \App\Integrations\IntegrationSettingState::STATE_READY => ['Siap diaktifkan', 'info', 'Uji koneksi berhasil. Aktifkan bila pengaturan sudah diperiksa.'],
        \App\Integrations\IntegrationSettingState::STATE_DRAFT => ['Belum diuji', 'neutral', 'Pengaturan sudah lengkap dan perlu diuji.'],
        \App\Integrations\IntegrationSettingState::STATE_TEST_FAILED => ['Uji gagal', 'danger', 'Periksa pengaturan, lalu uji kembali.'],
        \App\Integrations\IntegrationSettingState::STATE_BLOCKED => ['Belum dapat digunakan', 'danger', 'Koneksi diblokir sampai syarat yang kurang dipenuhi.'],
        default => ['Belum lengkap', 'neutral', 'Lengkapi URL, identitas sumber, dan token.'],
    };
@endphp

<section class="mb-4" aria-labelledby="integration-settings-title">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h2 class="fs-5 fw-bold mb-1" id="integration-settings-title">Pengaturan Koneksi Sumber Data</h2>
            <p class="text-muted small mb-0">Atur setiap sumber secara terpisah dengan urutan Simpan, Uji, lalu Aktifkan.</p>
        </div>
        <span class="sibk-badge sibk-badge--neutral">Khusus Admin IT</span>
    </div>

    <div class="row g-4">
        @foreach($integrationStates as $provider => $state)
            @php
                [$connectionLabel, $connectionTone, $connectionHelp] = $connectionPresentation($state->state);
                $freshRun = $provider === \App\Models\IntegrationSetting::PROVIDER_DAPODIK
                    ? $lastSuccessfulDapodikRun
                    : $lastSuccessfulEtatibRun;
                $providerErrors = collect($errors->getMessages())
                    ->filter(static fn (array $messages, string $key): bool => str_starts_with((string) $key, $provider.'.'));
            @endphp

            <div class="col-12 col-xl-6">
                <article
                    class="sibk-panel h-100 integration-setting"
                    id="integration-{{ $provider }}"
                    data-integration-panel="{{ $provider }}"
                    aria-labelledby="{{ $provider }}-integration-title"
                >
                    <div class="sibk-panel__header p-4 border-0 pb-0">
                        <div class="w-100">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <h3 class="sibk-panel__title mb-1" id="{{ $provider }}-integration-title">{{ $state->label }}</h3>
                                    <p class="sibk-panel__subtitle text-muted small mb-0">Ringkasan koneksi</p>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted small d-block mb-1">Status koneksi</span>
                                    <span class="sibk-badge sibk-badge--{{ $connectionTone }}">{{ $connectionLabel }}</span>
                                </div>
                            </div>
                            <p class="small mt-2 mb-0">{{ $connectionHelp }}</p>
                        </div>
                    </div>

                    <div class="sibk-panel__body p-4">
                        @if(! $state->adapterAvailable)
                            <div class="alert alert-danger py-2" role="status">
                                <strong>Adapter belum tersedia.</strong>
                                Pengaturan dapat disimpan, tetapi belum dapat diuji, diaktifkan, atau dipakai untuk sinkronisasi.
                            </div>
                        @endif

                        @if($providerErrors->isNotEmpty())
                            <div class="alert alert-danger py-2" id="{{ $provider }}-form-errors" role="alert">
                                <strong>Periksa pengaturan {{ $state->label }}:</strong>
                                <ul class="mb-0 ps-3">
                                    @foreach($providerErrors as $key => $messages)
                                        @foreach($messages as $message)
                                            <li>
                                                {{ match($key) {
                                                    $provider.'.timeout_seconds' => 'Batas waktu harus antara 5 dan 120 detik.',
                                                    $provider.'.current_password' => 'Kata sandi akun belum benar atau belum diisi.',
                                                    $provider.'.api_key' => 'Token baru tidak valid. Jangan isi token baru saat memilih hapus token.',
                                                    $provider.'.expected_source_identifier' => 'Identitas sumber belum valid.',
                                                    default => $message,
                                                } }}
                                            </li>
                                        @endforeach
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <h4 class="fs-6 fw-bold mb-1">Konfigurasi</h4>
                        <p class="text-muted small" id="{{ $provider }}-configuration-help">
                            Token lama tidak pernah ditampilkan. Kosongkan token bila tidak ingin menggantinya.
                        </p>

                        <form action="{{ route('data-master.integrations.update', ['provider' => $provider]) }}" method="POST" class="row g-3 mb-4">
                            @csrf
                            @method('PATCH')
                            <div class="col-12">
                                <label class="form-label sibk-form-label" for="{{ $provider }}-base-url">URL koneksi</label>
                                <input
                                    type="url"
                                    class="form-control sibk-form-control @error($provider.'.base_url') is-invalid @enderror"
                                    id="{{ $provider }}-base-url"
                                    name="{{ $provider }}[base_url]"
                                    maxlength="500"
                                    value="{{ old($provider.'.base_url', $state->baseUrl) }}"
                                    aria-describedby="{{ $provider }}-base-url-help"
                                    placeholder="https://sumber-data.sekolah/api"
                                >
                                <div class="form-text" id="{{ $provider }}-base-url-help">URL harus sesuai daftar alamat yang diizinkan oleh sekolah.</div>
                            </div>

                            <div class="col-12 col-md-8">
                                <label class="form-label sibk-form-label" for="{{ $provider }}-source-identifier">Identitas sumber yang diharapkan</label>
                                <input
                                    type="text"
                                    class="form-control sibk-form-control @error($provider.'.expected_source_identifier') is-invalid @enderror"
                                    id="{{ $provider }}-source-identifier"
                                    name="{{ $provider }}[expected_source_identifier]"
                                    maxlength="100"
                                    value="{{ old($provider.'.expected_source_identifier', $state->expectedSourceIdentifier) }}"
                                    aria-describedby="{{ $provider }}-source-identifier-help"
                                >
                                <div class="form-text" id="{{ $provider }}-source-identifier-help">Nilai ini harus sama dengan identitas sekolah atau sumber yang dilaporkan penyedia data.</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label sibk-form-label" for="{{ $provider }}-timeout">Batas waktu</label>
                                <div class="input-group">
                                    <input
                                        type="number"
                                        class="form-control sibk-form-control @error($provider.'.timeout_seconds') is-invalid @enderror"
                                        id="{{ $provider }}-timeout"
                                        name="{{ $provider }}[timeout_seconds]"
                                        min="5"
                                        max="120"
                                        value="{{ old($provider.'.timeout_seconds', $state->timeoutSeconds) }}"
                                        aria-describedby="{{ $provider }}-timeout-help"
                                        required
                                    >
                                    <span class="input-group-text">detik</span>
                                </div>
                                <div class="form-text" id="{{ $provider }}-timeout-help">5–120 detik.</div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <label class="form-label sibk-form-label mb-0" for="{{ $provider }}-api-key">Token baru</label>
                                    <span class="sibk-badge sibk-badge--{{ $state->hasCredentials ? 'success' : 'neutral' }}">
                                        Token tersimpan: {{ $state->hasCredentials ? 'Ya' : 'Tidak' }}
                                    </span>
                                </div>
                                <input
                                    type="password"
                                    class="form-control sibk-form-control @error($provider.'.api_key') is-invalid @enderror"
                                    id="{{ $provider }}-api-key"
                                    name="{{ $provider }}[api_key]"
                                    maxlength="1000"
                                    autocomplete="new-password"
                                    spellcheck="false"
                                    autocapitalize="none"
                                    aria-describedby="{{ $provider }}-api-key-help"
                                >
                                <div class="form-text" id="{{ $provider }}-api-key-help">Isi hanya saat memasang atau mengganti token.</div>
                            </div>

                            <div class="col-12">
                                <input type="hidden" name="{{ $provider }}[remove_api_key]" value="0">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="{{ $provider }}-remove-api-key"
                                        name="{{ $provider }}[remove_api_key]"
                                        value="1"
                                        aria-describedby="{{ $provider }}-remove-api-key-help"
                                    >
                                    <label class="form-check-label" for="{{ $provider }}-remove-api-key">Hapus token yang tersimpan</label>
                                    <div class="form-text" id="{{ $provider }}-remove-api-key-help">Koneksi menjadi tidak lengkap sampai token baru disimpan.</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label sibk-form-label" for="{{ $provider }}-save-current-password">Kata sandi akun Anda</label>
                                <input
                                    type="password"
                                    class="form-control sibk-form-control @error($provider.'.current_password') is-invalid @enderror"
                                    id="{{ $provider }}-save-current-password"
                                    name="{{ $provider }}[current_password]"
                                    autocomplete="current-password"
                                    spellcheck="false"
                                    autocapitalize="none"
                                    aria-describedby="{{ $provider }}-save-current-password-help"
                                    required
                                >
                                <div class="form-text" id="{{ $provider }}-save-current-password-help">Diperlukan untuk memastikan perubahan dilakukan oleh Anda.</div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                            </div>
                        </form>

                        <div class="integration-setting__actions border-top pt-4">
                            <h4 class="fs-6 fw-bold mb-3">Uji dan aktifkan</h4>

                            @foreach([
                                'test' => ['Uji Koneksi', $state->canTest, 'data-master.integrations.test'],
                                'activate' => ['Aktifkan', $state->canActivate, 'data-master.integrations.activate'],
                                'deactivate' => ['Nonaktifkan', $state->canDeactivate, 'data-master.integrations.deactivate'],
                            ] as $action => [$actionLabel, $actionAllowed, $routeName])
                                <form action="{{ route($routeName, ['provider' => $provider]) }}" method="POST" class="integration-setting__action-row">
                                    @csrf
                                    <div class="flex-grow-1">
                                        <label class="form-label small fw-semibold" for="{{ $provider }}-{{ $action }}-current-password">Kata sandi untuk {{ strtolower($actionLabel) }}</label>
                                        <input
                                            type="password"
                                            class="form-control form-control-sm"
                                            id="{{ $provider }}-{{ $action }}-current-password"
                                            name="{{ $provider }}[current_password]"
                                            autocomplete="current-password"
                                            spellcheck="false"
                                            autocapitalize="none"
                                            aria-describedby="{{ $provider }}-{{ $action }}-current-password-help"
                                            @disabled(! $actionAllowed)
                                            required
                                        >
                                        <div class="form-text" id="{{ $provider }}-{{ $action }}-current-password-help">Masukkan kata sandi akun saat tindakan ini tersedia.</div>
                                    </div>
                                    <button type="submit" class="btn {{ $action === 'deactivate' ? 'btn-outline-danger' : 'btn-outline-primary' }}" @disabled(! $actionAllowed)>
                                        {{ $actionLabel }}
                                    </button>
                                </form>
                            @endforeach
                        </div>

                        <div class="integration-setting__freshness border-top pt-4 mt-4">
                            <h4 class="fs-6 fw-bold mb-1">Keadaan data terakhir</h4>
                            @if($freshRun?->finished_at)
                                <p class="mb-1">Terakhir berhasil diperbarui {{ $freshRun->finished_at->locale('id')->translatedFormat('d M Y, H.i') }}.</p>
                                <p class="text-muted small mb-0">{{ $freshRun->processed_count }} data diproses. Ini adalah waktu pembaruan data, bukan status koneksi.</p>
                            @else
                                <p class="text-muted small mb-0">Belum ada data yang berhasil diperbarui dari {{ $state->label }}. Ini terpisah dari status koneksi di atas.</p>
                            @endif
                        </div>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
</section>
