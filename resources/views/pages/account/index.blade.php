@extends('layouts.app-2')

@section('page-title', 'Akun Saya - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header">
            <div class="sibk-page-header__copy">
                <h1>Akun Saya</h1>
                <p>Identitas akun yang digunakan untuk masuk ke Ruang BK.</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Kolom Kiri: Profil & Info Personal -->
            <div class="col-lg-7 col-xl-8">
                <div class="sibk-panel h-100">
                    <div class="sibk-panel__header sibk-account-panel__header">
                        <h3 class="sibk-panel__title">Informasi Akun</h3>
                        <p class="sibk-panel__subtitle">Identitas akun yang digunakan untuk masuk ke Ruang BK.</p>
                    </div>
                    
                    <div class="sibk-panel__body p-4">
                        <div class="sibk-account-profile">
                            <div class="sibk-account-profile__avatar">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <div class="sibk-account-profile__info">
                                <h4 class="sibk-account-profile__name">{{ $account['name'] }}</h4>
                                <p class="sibk-account-profile__email">{{ $account['email'] }}</p>
                            </div>
                        </div>

                        <div class="sibk-account-details">
                            <div class="sibk-account-details__item">
                                <span class="sibk-account-details__label">Nama pengguna</span>
                                <span class="sibk-account-details__value">{{ $account['username'] }}</span>
                            </div>
                            <div class="sibk-account-details__item">
                                <span class="sibk-account-details__label">Email</span>
                                <span class="sibk-account-details__value">{{ $account['email'] }}</span>
                            </div>
                            <div class="sibk-account-details__item">
                                <span class="sibk-account-details__label">Tahun ajaran aktif</span>
                                <span class="sibk-account-details__value">{{ $account['academic_year'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Keamanan & Info -->
            <div class="col-lg-5 col-xl-4 d-flex flex-column gap-4">
                <div class="sibk-panel">
                    <div class="sibk-panel__header sibk-account-panel__header">
                        <h3 class="sibk-panel__title">Keamanan Akun</h3>
                        <p class="sibk-panel__subtitle">Kelola keamanan akun yang sedang digunakan.</p>
                    </div>

                    <div class="sibk-panel__body p-4">
                        <div class="sibk-security-info">
                            <div class="sibk-security-info__icon sibk-icon-tone--primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </div>
                            <div class="sibk-security-info__text">
                                <h5 class="sibk-security-info__title">Kata sandi</h5>
                                <p class="sibk-security-info__desc">Perbarui kata sandi jika diperlukan.</p>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100 sibk-btn-ubah-sandi">Ubah Kata Sandi</button>
                        
                        <a href="{{ route('login.preview') }}" class="btn w-100 sibk-btn-logout mt-3">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" x2="9" y1="12" y2="12"/>
                            </svg>
                            Keluar
                        </a>
                    </div>
                </div>

                <div class="sibk-account-info-box">
                    <h4 class="sibk-account-info-box__title">Akun dan akses</h4>
                    <p class="sibk-account-info-box__text">
                        Untuk keamanan akun dan pemutakhiran data yang tidak bisa Anda perbarui dari halaman ini,
                        silakan menghubungi administrator sistem terkait.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
