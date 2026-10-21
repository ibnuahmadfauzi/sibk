@extends('layouts.app-2')

@section('page-title', 'Manajemen Web - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy m-0">
                <h1>Manajemen Web</h1>
                <p>Atur logo aplikasi, kop laporan, dan tema warna web.</p>
            </div>
        </div>

        @if(session('success'))
            <x-notification-toast>{{ session('success') }}</x-notification-toast>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Pengaturan belum tersimpan.</strong>
                <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="sibk-panel" aria-labelledby="web-logo-heading">
            <div class="sibk-panel__body p-4">
                <h2 id="web-logo-heading" class="fs-5">Logo web</h2>
                <form action="{{ route('admin.web-settings.update') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-center mt-1">
                    @csrf
                    @method('PATCH')
                    <div class="col-12 col-lg-auto">
                        <img src="{{ route('branding.logo') }}" alt="Logo saat ini" width="96" height="96" class="d-block object-fit-contain">
                    </div>
                    <div class="col-12 col-lg">
                        <label for="webLogo" class="form-label">Unggah logo baru</label>
                        <div class="row g-3 align-items-center">
                            <div class="col-12 col-lg">
                                <input id="webLogo" name="logo" type="file" class="form-control @error('logo') is-invalid @enderror" accept="image/png,image/jpeg" required aria-describedby="webLogoHelp">
                            </div>
                            <div class="col-12 col-lg-auto">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </div>
                        <p id="webLogoHelp" class="form-text">PNG atau JPEG, maksimal 2 MB dan 2048 × 2048 piksel. Sebaiknya gunakan gambar persegi dengan latar transparan.</p>
                        @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </form>
                <div class="border-top mt-4 pt-4">
                    <h3 class="fs-6" id="agency-logo-heading">Logo dinas untuk kop laporan</h3>
                    <form action="{{ route('admin.web-settings.agency-logo') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-center mt-1" aria-labelledby="agency-logo-heading">
                        @csrf
                        @method('PATCH')
                        <div class="col-12 col-lg-auto">
                            @if($hasAgencyLogo)
                                <img src="{{ route('branding.agency-logo') }}" alt="Logo dinas saat ini" width="96" height="96" class="d-block object-fit-contain">
                            @else
                                <span class="small text-muted">Belum ada logo dinas</span>
                            @endif
                        </div>
                        <div class="col-12 col-lg">
                            <label for="agencyLogo" class="form-label">Unggah logo Dinas Pendidikan Jawa Timur</label>
                            <div class="row g-3 align-items-center">
                                <div class="col-12 col-lg">
                                    <input id="agencyLogo" name="logo" type="file" class="form-control" accept="image/png,image/jpeg" required aria-describedby="agencyLogoHelp">
                                </div>
                                <div class="col-12 col-lg-auto">
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </div>
                            </div>
                            <p id="agencyLogoHelp" class="form-text">PNG atau JPEG, maksimal 2 MB dan 2048 &times; 2048 piksel. Hanya digunakan pada kop laporan.</p>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="sibk-panel mt-4" aria-labelledby="web-theme-heading">
            <div class="sibk-panel__body p-4">
                <h2 id="web-theme-heading" class="fs-5">Tema warna</h2>
                <form action="{{ route('admin.web-settings.theme') }}" method="POST" data-web-theme-form>
                    @csrf
                    @method('PATCH')
                    <fieldset>
                        <legend class="fs-6">Pilih palet warna web</legend>
                        <div class="row g-3">
                            @foreach(['default' => 'Bawaan', 'pine' => 'Hijau Pinus', 'slate' => 'Biru Sabak', 'custom' => 'Custom'] as $key => $label)
                                <div class="col-12 col-md-6 col-xl-3">
                                    <label class="d-flex align-items-center gap-2 border rounded p-3 w-100" for="web-theme-{{ $key }}">
                                        <input class="form-check-input m-0" type="radio" name="theme" value="{{ $key }}" id="web-theme-{{ $key }}" @checked(old('theme', $selectedTheme) === $key) required>
                                        @if($key !== 'custom')
                                            <span class="d-flex gap-1" aria-hidden="true">
                                                @if($key === 'default')
                                                    @foreach(['primary', 'page', 'surface'] as $part)
                                                        <span class="sibk-theme-swatch sibk-theme-swatch--default-{{ $part }}"></span>
                                                    @endforeach
                                                @else
                                                    @foreach(\App\Support\WebThemePalette::PRESETS[$key] as $color)
                                                        <span class="sibk-theme-swatch" style="background-color: {{ $color }}"></span>
                                                    @endforeach
                                                @endif
                                            </span>
                                        @endif
                                        <span>{{ $label }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                    <fieldset class="mt-4" data-custom-palette-fields @disabled(old('theme', $selectedTheme) !== 'custom') @if(old('theme', $selectedTheme) !== 'custom') hidden @endif>
                        <legend class="fs-6">Palet custom</legend>
                        <p class="small text-muted" id="custom-palette-help">Pilih warna utama yang gelap serta latar dan kartu yang terang. Input, border, fokus, dan bayangan mengikuti otomatis.</p>
                        <div class="row g-3">
                            @foreach(['primary' => 'Warna utama', 'page' => 'Latar halaman', 'surface' => 'Kartu dan panel'] as $key => $label)
                                @php($value = old('custom_palette.'.$key, $customPalette[$key]))
                                <div class="col-12 col-md-4" data-theme-color-field>
                                    <label class="form-label" for="palette-{{ $key }}">{{ $label }}</label>
                                    <div class="d-flex gap-2">
                                        <input class="form-control form-control-color" type="color" value="{{ is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/D', $value) ? $value : $customPalette[$key] }}" aria-label="Pilih {{ strtolower($label) }}" data-theme-color-picker>
                                        <input class="form-control" type="text" id="palette-{{ $key }}" name="custom_palette[{{ $key }}]" value="{{ is_string($value) ? $value : '' }}" pattern="#[0-9a-fA-F]{6}" maxlength="7" required spellcheck="false" autocomplete="off" data-theme-color-hex
                                            aria-describedby="custom-palette-help @error('custom_palette.'.$key) palette-{{ $key }}-error @enderror" @error('custom_palette.'.$key) aria-invalid="true" @enderror>
                                    </div>
                                    @error('custom_palette.'.$key)<p class="text-danger small mt-2 mb-0" id="palette-{{ $key }}-error">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                    <p class="small text-muted mt-3">Berlaku untuk seluruh pengguna dan halaman masuk. Warna status dan kertas laporan tetap.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">Simpan tema</button>
                        <button type="submit" class="btn btn-outline-primary" name="theme" value="default" formnovalidate>Kembalikan ke default</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
