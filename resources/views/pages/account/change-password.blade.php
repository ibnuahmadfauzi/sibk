@extends('layouts.app-2')

@section('page-title', 'Ganti Kata Sandi - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="ACCOUNT-CHANGE-PASSWORD">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Ganti Kata Sandi</h1>
                <p>{{ $required ? 'Akses lain dibatasi sampai kata sandi sementara diperbarui.' : 'Perbarui kata sandi akun Anda.' }}</p>
            </div>
        </div>

        <section class="sibk-panel">
            <div class="sibk-panel__body p-4">
                @include('pages.account._password-form')
            </div>
        </section>
    </div>
@endsection
