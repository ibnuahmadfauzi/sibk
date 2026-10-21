@php
    $hasSavedApiUrl = $savedApiUrls[$provider] ?? false;
@endphp
<div>
    <label class="form-label small" for="{{ $inputId }}">Tautan {{ $label }}</label>
    <div class="row g-2 align-items-end">
        <div class="col-12 col-lg">
            <input class="form-control" type="url" id="{{ $inputId }}" name="api_url" maxlength="2048"
                placeholder="{{ $hasSavedApiUrl ? 'API tersimpan (disembunyikan)' : $placeholder }}" data-api-placeholder="{{ $placeholder }}"
                autocomplete="off" spellcheck="false" @if($hasSavedApiUrl) disabled @else required @endif>
        </div>
        <div class="col-12 col-lg-auto d-flex flex-wrap gap-2">
            @if($hasSavedApiUrl)
                <button class="btn btn-outline-secondary" type="button" data-saved-api-reveal-button>Tampilkan</button>
            @endif
            <button class="btn btn-outline-primary" type="button" data-saved-api-edit-button>{{ $hasSavedApiUrl ? 'Ubah' : 'Simpan' }}</button>
            <button class="btn btn-primary" type="submit"
                @if($provider === 'etatib') data-etatib-preview-button @else data-api-siswa-preview-button @disabled(! $importableYearExists) @endif>
                Tinjau Data
            </button>
        </div>
    </div>
    @if($hasSavedApiUrl)
        <label class="form-check-label d-flex align-items-center gap-2 mt-2" for="{{ $provider }}_use_saved">
            <input class="form-check-input m-0" id="{{ $provider }}_use_saved" type="checkbox" name="use_saved_api" value="1" checked data-saved-api-choice>
            Gunakan API tersimpan
        </label>
    @endif
</div>
