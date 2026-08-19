@extends('layouts.app-2')

@section('page-title', 'Selesaikan Kasus - Ruang BK')

@php
    $caseNo = request()->query('case', 'K-014');
    $studentName = request()->query('student', 'Murid A');
@endphp

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('cases.show') }}" class="btn btn-icon btn-light" aria-label="Kembali ke Detail Kasus">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="sibk-page-header__copy m-0">
                    <h1 class="mb-1">Selesaikan Kasus</h1>
                    <p class="mb-0 text-muted">Kasus {{ $caseNo }} • {{ $studentName }}</p>
                </div>
            </div>
        </div>

        <!-- Ringkasan Penanganan Panel -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4 p-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="fs-5 mb-1 text-dark fw-bold">Ringkasan Penanganan</h4>
                        <p class="text-muted small mb-0">Status saat ini: <strong>Dalam Penanganan</strong> • Tindak lanjut terakhir: 15 Agustus 2026</p>
                    </div>
                    <span class="sibk-badge sibk-badge--primary px-3 py-2 fw-bold align-self-start align-self-md-center">Dalam Penanganan</span>
                </div>
            </div>
        </div>

        <!-- Form Penyelesaian Panel -->
        <div class="sibk-panel">
            <div class="sibk-panel__body p-4 p-md-5">
                <form action="{{ route('cases.show') }}" method="GET" class="row g-4">
                    
                    <!-- Baris 1: Tanggal Selesai & Hasil Akhir -->
                    <div class="col-12 col-md-4">
                        <label for="tanggal_selesai" class="form-label sibk-form-label text-dark fw-semibold small">Tanggal Selesai</label>
                        <input type="date" class="form-control sibk-form-control" id="tanggal_selesai" name="tanggal_selesai" value="2026-08-16">
                    </div>

                    <div class="col-12 col-md-8">
                        <label for="hasil_akhir" class="form-label sibk-form-label text-dark fw-semibold small">Hasil Akhir</label>
                        <input type="text" class="form-control sibk-form-control" id="hasil_akhir" name="hasil_akhir" placeholder="Tuliskan hasil akhir penanganan">
                    </div>

                    <!-- Baris 2: Ringkasan Penyelesaian -->
                    <div class="col-12">
                        <label for="ringkasan_penyelesaian" class="form-label sibk-form-label text-dark fw-semibold small">Ringkasan Penyelesaian</label>
                        <textarea class="form-control sibk-form-control" id="ringkasan_penyelesaian" name="ringkasan_penyelesaian" rows="4" placeholder="Ringkasan penutupan kasus"></textarea>
                    </div>

                    <!-- Baris 3: Rencana Lanjutan -->
                    <div class="col-12">
                        <label for="rencana_lanjutan" class="form-label sibk-form-label text-dark fw-semibold small">Rencana Lanjutan</label>
                        <textarea class="form-control sibk-form-control" id="rencana_lanjutan" name="rencana_lanjutan" rows="3" placeholder="Isi bila masih ada pemantauan setelah kasus ditutup"></textarea>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="col-12 mt-4 pt-2">
                        <div class="d-flex justify-content-end align-items-center gap-3">
                            <a href="{{ route('cases.show') }}" class="btn btn-light text-primary fw-bold px-4 py-2" style="border-radius: 14px;">Batal</a>
                            <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2" style="border-radius: 14px; background-color: #2f6fc6; border-color: #2f6fc6;">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                Konfirmasi Selesai
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

    </div>
@endsection
