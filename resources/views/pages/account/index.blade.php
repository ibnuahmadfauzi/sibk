@extends('layouts.app-2')

@section('page-title', 'Akun Saya - Ruang BK')

@section('body')
    <div class="sibk-dashboard sibk-account-page">
        @if(session('success'))
            <x-notification-toast>{{ session('success') }}</x-notification-toast>
        @endif
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="sibk-page-header__copy">
                <h1>Akun Saya</h1>
                <p>Identitas akun yang digunakan untuk masuk ke Ruang BK.</p>
            </div>
            @can('manageDataMaster')
                <a href="{{ route('admin.users.index') }}" class="btn btn-primary">Kelola Akun</a>
            @endcan
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <section class="sibk-account-card" aria-labelledby="account-information-title">
                    <!-- <h2 id="account-information-title" class="sibk-account-card__title">Informasi Akun</h2> -->
                    <div class="sibk-account-profile">
                        <div class="sibk-account-profile__avatar" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div class="sibk-account-profile__info">
                            <h3 class="sibk-account-profile__name">{{ $account['name'] }}</h3>
                        </div>
                    </div>
                    <dl class="sibk-account-details">
                        <div><dt>Peran</dt><dd>{{ implode(', ', $account['roles']) ?: 'Belum memiliki peran' }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $account['email'] }}</dd></div>
                        <div><dt>Status akun</dt><dd><span class="sibk-badge {{ $account['status'] === 'Aktif' ? 'sibk-badge--success' : 'sibk-badge--warning' }}">{{ $account['status'] }}</span></dd></div>
                        <div><dt>Login terakhir</dt><dd>{{ $account['last_login_at'] ? $account['last_login_at']->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H.i').' WIB' : 'Belum tercatat' }}</dd></div>
                        <div><dt>Tahun ajaran aktif</dt><dd>{{ $account['academic_year'] }}</dd></div>
                    </dl>
                </section>
            </div>

            <div class="col-lg-4 d-flex flex-column gap-4">
                <section class="sibk-account-card sibk-account-card--inset" aria-labelledby="account-security-title">
                    <h2 id="account-security-title" class="sibk-account-card__title">Keamanan Akun</h2>
                    <p class="sibk-account-card__description">Gunakan kata sandi unik dan jangan membagikannya.</p>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#account-password-modal">Ubah Sandi</button>
                    <form action="{{ route('logout') }}" method="POST" class="sibk-account-logout" data-clear-drafts-user>
                        @csrf
                        <button type="submit" class="btn btn-outline-danger d-inline-flex align-items-center gap-2">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <path d="m16 17 5-5-5-5M21 12H9"/>
                            </svg>
                            Keluar
                        </button>
                    </form>
                    </div>
                </section>
                <section class="sibk-account-help" aria-labelledby="account-help-title">
                    <h2 id="account-help-title" class="sibk-account-card__title">Perubahan Data Akun</h2>
                    <p class="sibk-account-card__description mb-0">Untuk mengubah nama, email, atau peran, hubungi Admin IT sekolah.</p>
                </section>
            </div>
        </div>
    </div>
    <div class="modal fade" id="account-password-modal" tabindex="-1" aria-labelledby="account-password-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h2 class="modal-title fs-5" id="account-password-title">Ubah Sandi</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">@include('pages.account._password-form', ['modal' => true])</div>
            </div>
        </div>
    </div>
@endsection

@section('extra-javascript')
@if($errors->any() || request()->boolean('password'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.bootstrap.Modal.getOrCreateInstance(document.getElementById('account-password-modal')).show();
    });
</script>
@endif
@endsection
