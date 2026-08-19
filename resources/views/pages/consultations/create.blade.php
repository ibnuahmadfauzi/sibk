@extends('layouts.app-2')

@section('page-title', 'Catat Konsultasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('cases.index') }}" class="btn btn-icon btn-light" aria-label="Kembali">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="sibk-page-header__copy m-0">
                    <h1 class="mb-1">Catat Konsultasi</h1>
                    <p class="mb-0 text-muted">Catat informasi layanan konsultasi.</p>
                </div>
            </div>
        </div>

        <!-- Form Panel PG-105 -->
        <div class="sibk-panel">
            <div class="sibk-panel__body p-4 p-md-5">
                <form action="{{ route('cases.index') }}" method="GET" class="row g-4" enctype="multipart/form-data">
                    
                    <!-- Baris 1: Murid & Kasus Terkait -->
                    <div class="col-12 col-md-6">
                        <label for="murid" class="form-label sibk-form-label text-dark fw-semibold small">Murid</label>
                        <select class="form-select sibk-form-select" id="murid" name="murid">
                            <option value="" disabled {{ !request('student') && !request('nisn') ? 'selected' : '' }}>Cari dan pilih murid</option>
                            <option value="1" {{ request('student') == 'Murid A' || request('nisn') == '0012345678' ? 'selected' : '' }}>Murid A — X RPL 1</option>
                            <option value="2" {{ request('student') == 'Murid B' || request('nisn') == '0012345679' ? 'selected' : '' }}>Murid B — X RPL 2</option>
                            <option value="3" {{ request('student') == 'Murid C' || request('nisn') == '0012345680' ? 'selected' : '' }}>Murid C — XI RPL 1</option>
                            <option value="4" {{ request('student') == 'Murid D' || request('nisn') == '0012345681' ? 'selected' : '' }}>Murid D — XI RPL 2</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="kasus_terkait" class="form-label sibk-form-label text-dark fw-semibold small">Kasus Terkait</label>
                        <select class="form-select sibk-form-select" id="kasus_terkait" name="kasus_terkait">
                            <option value="" disabled {{ !request('case') ? 'selected' : '' }}>Pilih kasus bila terkait</option>
                            <option value="none">Tidak terkait kasus khusus</option>
                            <option value="K-014" {{ request('case') == 'K-014' ? 'selected' : '' }}>K-014 — Murid A (Pribadi)</option>
                            <option value="K-013" {{ request('case') == 'K-013' ? 'selected' : '' }}>K-013 — Murid B (Belajar)</option>
                            <option value="K-011" {{ request('case') == 'K-011' ? 'selected' : '' }}>K-011 — Murid D (Karier)</option>
                        </select>
                    </div>

                    <!-- Baris 2: Tanggal, Jenis Layanan & Status -->
                    <div class="col-12 col-md-4">
                        <label for="tanggal" class="form-label sibk-form-label text-dark fw-semibold small">Tanggal</label>
                        <input type="date" class="form-control sibk-form-control" id="tanggal" name="tanggal" value="2026-08-16">
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="jenis_layanan" class="form-label sibk-form-label text-dark fw-semibold small">Jenis Layanan</label>
                        <select class="form-select sibk-form-select" id="jenis_layanan" name="jenis_layanan">
                            <option value="" disabled selected>Pilih jenis layanan</option>
                            <option value="pribadi" selected>Pribadi</option>
                            <option value="sosial">Sosial</option>
                            <option value="belajar">Belajar</option>
                            <option value="karier">Karier</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="status" class="form-label sibk-form-label text-dark fw-semibold small">Status</label>
                        <select class="form-select sibk-form-select" id="status" name="status">
                            <option value="" disabled selected>Pilih status</option>
                            <option value="terlaksana" selected>Terlaksana</option>
                            <option value="dijadwalkan">Dijadwalkan</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>

                    <!-- Baris 3: Jadwal Tindak Lanjut -->
                    <div class="col-12">
                        <label for="jadwal_tindak_lanjut" class="form-label sibk-form-label text-dark fw-semibold small">Jadwal Tindak Lanjut</label>
                        <input type="date" class="form-control sibk-form-control" id="jadwal_tindak_lanjut" name="jadwal_tindak_lanjut">
                        <span class="text-muted d-block mt-1" style="font-size: 0.75rem;">Pilih tanggal bila ada</span>
                    </div>

                    <!-- Baris 4: Ringkasan Umum -->
                    <div class="col-12">
                        <label for="ringkasan_umum" class="form-label sibk-form-label text-dark fw-semibold small">Ringkasan Umum</label>
                        <textarea class="form-control sibk-form-control" id="ringkasan_umum" name="ringkasan_umum" rows="4" placeholder="Tuliskan ringkasan layanan yang dapat dicatat"></textarea>
                    </div>

                    <!-- Baris 5: Dokumen Pendukung -->
                    <div class="col-12">
                        <label class="form-label sibk-form-label text-dark fw-semibold small mb-1">Dokumen Pendukung</label>
                        <p class="text-muted small mb-3">Tambahkan dokumen bila diperlukan dan diizinkan.</p>
                        <div class="d-flex align-items-center gap-3">
                            <label for="dokumen" class="btn btn-outline-primary fw-bold px-4 py-2" style="border-radius: 14px; cursor: pointer;">
                                <svg class="me-1" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Pilih Dokumen
                            </label>
                            <input type="file" id="dokumen" name="dokumen" class="d-none" onchange="document.getElementById('fileName').textContent = this.files[0]?.name || ''">
                            <span id="fileName" class="text-muted small"></span>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="col-12 mt-4 pt-2">
                        <div class="d-flex justify-content-end align-items-center gap-3">
                            <a href="{{ route('cases.index') }}" class="btn btn-light text-primary fw-bold px-4 py-2" style="border-radius: 14px;">Batal</a>
                            <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow-sm" style="border-radius: 14px; background-color: #2f6fc6; border-color: #2f6fc6;">Simpan Konsultasi</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

    </div>
@endsection
