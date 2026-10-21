@extends('layouts.app-2')

@section('page-title', 'Manajemen Web - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy m-0">
                <h1>Manajemen Web</h1>
                <p>Atur logo yang tampil di aplikasi, ikon tab, dan kop laporan.</p>
            </div>
        </div>

        @if(session('success'))
            <x-notification-toast>{{ session('success') }}</x-notification-toast>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Logo belum tersimpan.</strong>
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
                                <button type="submit" class="btn btn-primary">Simpan logo</button>
                            </div>
                        </div>
                        <p id="webLogoHelp" class="form-text">PNG atau JPEG, maksimal 2 MB dan 2048 × 2048 piksel. Sebaiknya gunakan gambar persegi dengan latar transparan.</p>
                        @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
