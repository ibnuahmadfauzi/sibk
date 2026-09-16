@extends('layouts.app-2')

@section('page-title', 'Kata Sandi Sementara - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="ADMIN-TEMPORARY-PASSWORD">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Kata sandi sementara</h1>
                <p>Salin dan sampaikan melalui saluran resmi. Nilai ini hanya ditampilkan sekali.</p>
            </div>
        </div>

        <section class="sibk-panel">
            <div class="sibk-panel__body p-4">
                <p class="mb-1 text-muted">Akun</p>
                <p class="fw-semibold">{{ $result->user->name }} &middot; {{ $result->user->email }}</p>
                <p class="mb-1 text-muted">Kata sandi sementara</p>
                <p class="fs-4 fw-bold font-monospace" aria-label="Kata sandi sementara">{{ $result->plainTextPassword }}</p>
                <p class="text-muted">Berlaku sampai {{ $result->expiresAt->locale('id')->translatedFormat('d M Y H.i') }} dan wajib diganti setelah login.</p>
                <a class="btn btn-primary" href="{{ route('admin.users.index') }}">Kembali ke Kelola Akun</a>
            </div>
        </section>
    </div>
@endsection
