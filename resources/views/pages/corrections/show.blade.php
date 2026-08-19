@extends('layouts.app-2')

@section('page-title', 'Detail & Verifikasi Koreksi - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Page Header -->
        <div class="sibk-page-header mb-4">
            <div class="d-flex align-items-center gap-2 mb-2">
                <a href="{{ route('corrections.index') }}" class="sibk-back-link d-inline-flex align-items-center gap-1 text-decoration-none text-muted small fw-semibold">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Koreksi Data
                </a>
            </div>
            <div class="sibk-page-header__copy">
                <h1>Detail Koreksi</h1>
                <p>Periksa nilai lama, nilai usulan, dan alasan perubahan.</p>
            </div>
        </div>

        <form action="{{ route('corrections.index') }}" method="GET">
            <!-- Target Information Banner -->
            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 border-0 pb-0">
                    <h3 class="sibk-panel__title">Informasi Pengajuan</h3>
                </div>
                <div class="sibk-panel__body p-4 pt-2">
                    <div class="d-flex flex-wrap align-items-center gap-2 text-muted">
                        <span class="fw-bold text-dark fs-6">{{ $correction['object'] }}</span>
                        <span>•</span>
                        <span>Atribut: <strong class="text-dark">{{ $correction['attribute'] }}</strong></span>
                        <span>•</span>
                        <span>Pengaju: <strong class="text-dark">{{ $correction['requester'] }}</strong></span>
                        <span>•</span>
                        <span>{{ $correction['date'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Comparison Card -->
            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 border-0 pb-0">
                    <h3 class="sibk-panel__title mb-1">Perbandingan Nilai</h3>
                    <p class="sibk-panel__subtitle text-muted small">Bandingkan data sebelum menentukan hasil pemeriksaan.</p>
                </div>
                <div class="sibk-panel__body p-4 pt-2">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded border bg-light">
                                <div class="text-muted small mb-1 fw-semibold">Nilai Lama</div>
                                <div class="fw-bold text-dark fs-5">{{ $correction['old_value'] }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded border border-primary bg-primary-subtle">
                                <div class="text-primary small mb-1 fw-bold">Nilai Usulan</div>
                                <div class="fw-bold text-primary fs-5">{{ $correction['new_value'] }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 rounded bg-light-subtle border">
                        <span class="fw-semibold text-dark">Alasan Pengajuan:</span>
                        <p class="text-muted small mb-0 mt-1">{{ $correction['reason'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Verification Decision Form -->
            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 border-0 pb-0">
                    <h3 class="sibk-panel__title">Hasil Pemeriksaan</h3>
                </div>
                <div class="sibk-panel__body p-4 pt-2">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="decision" class="form-label sibk-form-label">Keputusan <span class="text-danger">*</span></label>
                            <select class="form-select sibk-form-select" id="decision" name="decision" required>
                                <option value="">Pilih keputusan</option>
                                <option value="approve" selected>Setujui Koreksi</option>
                                <option value="reject">Tolak Koreksi</option>
                                <option value="revise">Minta Perbaikan</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="review_notes" class="form-label sibk-form-label">Catatan Pemeriksaan</label>
                            <textarea class="form-control sibk-form-control" id="review_notes" name="review_notes" rows="3" placeholder="Tambahkan catatan bila diperlukan"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex justify-content-end gap-3 mt-4">
                <a href="{{ route('corrections.index') }}" class="btn btn-outline-secondary px-4 py-2">
                    Kembali
                </a>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Simpan Keputusan
                </button>
            </div>
        </form>
    </div>
@endsection
