@php
    $hasSavedApiUrl = $savedApiUrls[$provider] ?? false;
    $connectionErrors = $errors->getBag('api_connections_'.$provider);
@endphp
    <div class="modal fade" tabindex="-1" aria-labelledby="{{ $provider }}_saved_api_title" aria-hidden="true" data-saved-api-modal @if($connectionErrors->any()) data-saved-api-open-edit @endif>
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <form action="{{ route('data-master.api-connections.update', ['provider' => $provider]) }}" method="POST" data-saved-api-form>
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="{{ $provider }}_saved_api_title" data-saved-api-title>Ubah tautan {{ $label }}</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body d-grid gap-3">
                    <div data-saved-api-edit-fields>
                        <label class="form-label" for="{{ $provider }}_new_api_url">Tautan {{ $label }} baru</label>
                        <input class="form-control" id="{{ $provider }}_new_api_url" type="password" name="api_url" maxlength="2048" placeholder="https://..." autocomplete="off" required data-saved-api-new-url
                            @if($connectionErrors->has('api_url')) aria-invalid="true" aria-describedby="{{ $provider }}_api_url_error" @endif>
                        @if($connectionErrors->has('api_url'))<div class="invalid-feedback d-block" id="{{ $provider }}_api_url_error">{{ $connectionErrors->first('api_url') }}</div>@endif
                        @if($provider === 'etatib' && $hasSavedApiUrl)
                            <p class="alert alert-warning small mt-2 mb-0">Gunakan API e-Tatib sekolah yang sama. Tautan baru memerlukan pratinjau dan aktivasi ulang pembaruan otomatis.</p>
                        @endif
                    </div>
                    <div data-saved-api-reveal-fields hidden>
                        <label class="form-label" for="{{ $provider }}_revealed_api_url">Tautan {{ $label }}</label>
                        <input class="form-control" id="{{ $provider }}_revealed_api_url" type="text" readonly data-saved-api-revealed>
                    </div>
                    <div>
                        <label class="form-label" for="{{ $provider }}_api_password">Kata sandi saat ini</label>
                        <input class="form-control" id="{{ $provider }}_api_password" type="password" name="current_password" autocomplete="current-password" required data-saved-api-password
                            @if($connectionErrors->has('current_password')) aria-invalid="true" aria-describedby="{{ $provider }}_api_password_error" @endif>
                        @if($connectionErrors->has('current_password'))<div class="invalid-feedback d-block" id="{{ $provider }}_api_password_error">{{ $connectionErrors->first('current_password') }}</div>@endif
                        <div class="text-danger small mt-1" role="alert" hidden data-saved-api-error></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-outline-secondary" type="button" hidden data-saved-api-hide>Sembunyikan</button>
                    <button class="btn btn-primary" type="submit" data-saved-api-submit>Simpan tautan</button>
                </div>
            </form>
        </div></div>
    </div>
