@php
    $connectionPresentation = static fn (string $state): array => match ($state) {
        \App\Integrations\IntegrationSettingState::STATE_ACTIVE => ['Terhubung', 'success', 'API siap dipakai untuk sinkronisasi manual dan otomatis.'],
        \App\Integrations\IntegrationSettingState::STATE_READY => ['Siap', 'info', 'API sudah lolos pemeriksaan dan dapat diaktifkan.'],
        \App\Integrations\IntegrationSettingState::STATE_DRAFT => ['Belum terhubung', 'neutral', 'Alamat API sudah tersimpan tetapi belum lolos pemeriksaan.'],
        \App\Integrations\IntegrationSettingState::STATE_TEST_FAILED => ['Uji gagal', 'danger', 'Periksa pengaturan, lalu uji kembali.'],
        \App\Integrations\IntegrationSettingState::STATE_BLOCKED => ['Belum dapat digunakan', 'danger', 'Koneksi diblokir sampai syarat yang kurang dipenuhi.'],
        default => ['Belum lengkap', 'neutral', 'Lengkapi URL dan identitas sumber.'],
    };
@endphp

<section class="mb-4" aria-labelledby="integration-settings-title">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h2 class="fs-5 fw-bold mb-1" id="integration-settings-title">Pengaturan Koneksi e-Tatib</h2>
            <p class="text-muted small mb-0">Tempel alamat API resmi sekolah, lalu hubungkan.</p>
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
                $saveErrors = $errors->getBag($provider.'_save');
                $connectErrors = $errors->getBag($provider.'_connect');
                if ($connectErrors->any()) {
                    $saveErrors = $connectErrors;
                }
            @endphp

            <div class="col-12">
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
                                <strong>Koneksi belum dibuka pada server.</strong>
                                Admin server perlu mengesahkan origin API sebelum koneksi dapat digunakan.
                            </div>
                        @endif

                        <h4 class="fs-6 fw-bold mb-1">Hubungkan API</h4>
                        <p class="text-muted small" id="{{ $provider }}-configuration-help">
                            Koneksi tidak memerlukan token. SIBK hanya membaca data dan tidak mengubah data e-Tatib.
                        </p>

                        <form action="{{ route('data-master.etatib.connect') }}" method="POST" class="row g-3 mb-4">
                            @csrf
                            @if($saveErrors->any())
                                <div class="col-12">
                                    <div class="alert alert-danger py-2 mb-0" id="{{ $provider }}-save-form-errors" role="alert">
                                        <strong>Periksa pengaturan {{ $state->label }}:</strong>
                                        <ul class="mb-0 ps-3">
                                            @foreach($saveErrors->getMessages() as $key => $messages)
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
                                </div>
                            @endif
                            <div class="col-12">
                                <label class="form-label sibk-form-label" for="{{ $provider }}-base-url">Link API e-Tatib</label>
                                <input
                                    type="url"
                                    class="form-control sibk-form-control {{ $saveErrors->has($provider.'.base_url') ? 'is-invalid' : '' }}"
                                    id="{{ $provider }}-base-url"
                                    name="{{ $provider }}[base_url]"
                                    maxlength="500"
                                    value="{{ old($provider.'.base_url', $state->baseUrl) }}"
                                    aria-describedby="{{ $provider }}-base-url-help{{ $saveErrors->has($provider.'.base_url') ? ' '.$provider.'-base-url-error' : '' }}"
                                    @if($saveErrors->has($provider.'.base_url')) aria-invalid="true" @endif
                                    placeholder="https://etatib.sekolah.sch.id/api/pelanggaran"
                                    required
                                >
                                <div class="form-text" id="{{ $provider }}-base-url-help">Gunakan link HTTPS yang langsung menampilkan data API.</div>
                                @if($saveErrors->has($provider.'.base_url'))
                                    <div class="invalid-feedback" id="{{ $provider }}-base-url-error">{{ $saveErrors->first($provider.'.base_url') }}</div>
                                @endif
                            </div>

                            <div class="col-12">
                                <label class="form-label sibk-form-label" for="{{ $provider }}-source-identifier">Kode sumber</label>
                                <input
                                    type="text"
                                    class="form-control sibk-form-control {{ $saveErrors->has($provider.'.expected_source_identifier') ? 'is-invalid' : '' }}"
                                    id="{{ $provider }}-source-identifier"
                                    name="{{ $provider }}[expected_source_identifier]"
                                    maxlength="100"
                                    value="{{ old($provider.'.expected_source_identifier', $state->expectedSourceIdentifier) }}"
                                    aria-describedby="{{ $provider }}-source-identifier-help{{ $saveErrors->has($provider.'.expected_source_identifier') ? ' '.$provider.'-source-identifier-error' : '' }}"
                                    @if($saveErrors->has($provider.'.expected_source_identifier')) aria-invalid="true" @endif
                                    placeholder="smkn1-surabaya"
                                    required
                                >
                                <div class="form-text" id="{{ $provider }}-source-identifier-help">Salin nilai <code>source_id</code> dari hasil API.</div>
                                @if($saveErrors->has($provider.'.expected_source_identifier'))
                                    <div class="invalid-feedback" id="{{ $provider }}-source-identifier-error">{{ $saveErrors->first($provider.'.expected_source_identifier') }}</div>
                                @endif
                            </div>

                            <input type="hidden" name="{{ $provider }}[timeout_seconds]" value="{{ $state->timeoutSeconds ?: 30 }}">
                            <input type="hidden" name="{{ $provider }}[remove_api_key]" value="0">

                            <div class="col-12">
                                <label class="form-label sibk-form-label" for="{{ $provider }}-save-current-password">Kata sandi akun Anda</label>
                                <input
                                    type="password"
                                    class="form-control sibk-form-control {{ $saveErrors->any() ? 'is-invalid' : '' }}"
                                    id="{{ $provider }}-save-current-password"
                                    name="{{ $provider }}[current_password]"
                                    autocomplete="current-password"
                                    spellcheck="false"
                                    autocapitalize="none"
                                    aria-describedby="{{ $provider }}-save-current-password-help{{ $saveErrors->has($provider.'.current_password') ? ' '.$provider.'-save-current-password-error' : '' }}{{ $saveErrors->any() ? ' '.$provider.'-save-form-errors' : '' }}"
                                    @if($saveErrors->any()) aria-invalid="true" @endif
                                    required
                                >
                                <div class="form-text" id="{{ $provider }}-save-current-password-help">Dipakai untuk mengonfirmasi bahwa perubahan dilakukan oleh Admin IT.</div>
                                @if($saveErrors->has($provider.'.current_password'))
                                    <div class="invalid-feedback" id="{{ $provider }}-save-current-password-error">{{ $saveErrors->first($provider.'.current_password') }}</div>
                                @endif
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    {{ $state->state === \App\Integrations\IntegrationSettingState::STATE_ACTIVE
                                        ? 'Hubungkan Ulang'
                                        : 'Hubungkan API' }}
                                </button>
                            </div>
                        </form>

                        <details
                            class="border-top pt-3"
                            @if(
                                $errors->getBag($provider.'_test')->any()
                                || $errors->getBag($provider.'_activate')->any()
                                || $errors->getBag($provider.'_deactivate')->any()
                            ) open @endif
                        >
                            <summary class="small fw-semibold text-primary">Pengaturan lanjutan</summary>
                            <div class="integration-setting__actions pt-3">
                                <p class="text-muted small">Uji atau putuskan koneksi secara terpisah.</p>

                                @foreach([
                                    'test' => ['Uji Koneksi', $state->canTest, 'data-master.integrations.test'],
                                    'activate' => ['Aktifkan', $state->canActivate, 'data-master.integrations.activate'],
                                    'deactivate' => ['Nonaktifkan', $state->canDeactivate, 'data-master.integrations.deactivate'],
                                ] as $action => [$actionLabel, $actionAllowed, $routeName])
                                    @php
                                        $actionErrors = $errors->getBag($provider.'_'.$action);
                                        $actionPasswordKey = $provider.'.current_password';
                                        $actionFormMessages = collect($actionErrors->getMessages())
                                            ->except([$actionPasswordKey, 'action'])
                                            ->flatten()
                                            ->unique()
                                            ->values();
                                        $actionHasError = $actionErrors->any();
                                    @endphp
                                    <form action="{{ route($routeName, ['provider' => $provider]) }}" method="POST" class="integration-setting__action-row">
                                        @csrf
                                        <div class="flex-grow-1">
                                        @if($actionErrors->has('action'))
                                            <div class="alert alert-danger py-2" id="{{ $provider }}-{{ $action }}-action-error" role="alert">
                                                {{ $actionErrors->first('action') }}
                                            </div>
                                        @endif
                                        @if($actionFormMessages->isNotEmpty())
                                            <div class="alert alert-danger py-2" id="{{ $provider }}-{{ $action }}-form-error" role="alert">
                                                <strong>Tindakan tidak dapat diproses:</strong>
                                                <ul class="mb-0 ps-3">
                                                    @foreach($actionFormMessages as $message)
                                                        <li>{{ $message }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        <label class="form-label small fw-semibold" for="{{ $provider }}-{{ $action }}-current-password">Kata sandi untuk {{ strtolower($actionLabel) }}</label>
                                        <input
                                            type="password"
                                            class="form-control form-control-sm {{ $actionHasError ? 'is-invalid' : '' }}"
                                            id="{{ $provider }}-{{ $action }}-current-password"
                                            name="{{ $provider }}[current_password]"
                                            autocomplete="current-password"
                                            spellcheck="false"
                                            autocapitalize="none"
                                            aria-describedby="{{ $provider }}-{{ $action }}-current-password-help{{ $actionErrors->has($actionPasswordKey) ? ' '.$provider.'-'.$action.'-current-password-error' : '' }}{{ $actionErrors->has('action') ? ' '.$provider.'-'.$action.'-action-error' : '' }}{{ $actionFormMessages->isNotEmpty() ? ' '.$provider.'-'.$action.'-form-error' : '' }}"
                                            @if($actionHasError) aria-invalid="true" @endif
                                            @disabled(! $actionAllowed)
                                            required
                                        >
                                        <div class="form-text" id="{{ $provider }}-{{ $action }}-current-password-help">Masukkan kata sandi akun saat tindakan ini tersedia.</div>
                                        @if($actionErrors->has($actionPasswordKey))
                                            <div class="invalid-feedback" id="{{ $provider }}-{{ $action }}-current-password-error">{{ $actionErrors->first($actionPasswordKey) }}</div>
                                        @endif
                                        </div>
                                        <button type="submit" class="btn {{ $action === 'deactivate' ? 'btn-outline-danger' : 'btn-outline-primary' }}" @disabled(! $actionAllowed)>
                                            {{ $actionLabel }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </details>

                        @if($state->lastProbeSummary)
                            <div class="border-top pt-4 mt-4" data-etatib-probe-preview>
                                <h4 class="fs-6 fw-bold mb-2">Data yang ditemukan</h4>
                                <p class="small mb-2">
                                    {{ number_format((int) ($state->lastProbeSummary['record_count'] ?? 0), 0, ',', '.') }} data,
                                    {{ number_format((int) ($state->lastProbeSummary['conflict_count'] ?? 0), 0, ',', '.') }} perlu diperiksa.
                                    Periode {{ $state->lastProbeSummary['from'] ?? '-' }} sampai {{ $state->lastProbeSummary['until'] ?? '-' }}.
                                </p>
                                @if(! empty($state->lastProbeSummary['samples']))
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>NISN</th><th>Waktu</th><th>Pelanggaran</th><th>Poin</th></tr></thead>
                                            <tbody>
                                                @foreach($state->lastProbeSummary['samples'] as $sample)
                                                    <tr>
                                                        <td>{{ $sample['nisn'] }}</td>
                                                        <td>{{ $sample['occurred_at'] }}</td>
                                                        <td>{{ $sample['violation'] }}</td>
                                                        <td>{{ $sample['points'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endif

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
