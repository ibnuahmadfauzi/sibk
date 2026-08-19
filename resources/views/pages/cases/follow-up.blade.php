@extends('layouts.app-2')

@section('page-title', 'Tambah Tindak Lanjut - Ruang BK')

@php
    $isEdit = request()->query('mode') === 'edit';
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
                    <h1 class="mb-1">{{ $isEdit ? 'Edit Tindak Lanjut' : 'Tambah Tindak Lanjut' }}</h1>
                    <p class="mb-0 text-muted">Kasus {{ $caseNo }} • {{ $studentName }}</p>
                </div>
            </div>
        </div>

        <!-- Form Panel -->
        <div class="sibk-panel">
            <div class="sibk-panel__body p-4 p-md-5">
                <form action="{{ route('cases.show') }}" method="GET" class="row g-4">
                    
                    <!-- Baris 1: Jenis Tindak Lanjut & Status Pelaksanaan -->
                    <div class="col-12 col-md-6">
                        <label for="jenis_tindak_lanjut" class="form-label sibk-form-label text-dark fw-semibold small">Jenis Tindak Lanjut</label>
                        <select class="form-select sibk-form-select" id="jenis_tindak_lanjut" name="jenis_tindak_lanjut">
                            <option value="" disabled {{ !$isEdit ? 'selected' : '' }}>Pilih jenis kegiatan</option>
                            <option value="konsultasi" {{ $isEdit ? 'selected' : '' }}>Konsultasi Individual</option>
                            <option value="panggilan_ortu">Panggilan Orang Tua</option>
                            <option value="bimbingan_kelompok">Bimbingan Kelompok</option>
                            <option value="home_visit">Kunjungan Rumah (Home Visit)</option>
                            <option value="koordinasi">Koordinasi Wali Kelas / Guru Mapel</option>
                            <option value="konferensi_kasus">Konferensi Kasus</option>
                            <option value="referral">Alih Tangan Kasus (Referral)</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="status_pelaksanaan" class="form-label sibk-form-label text-dark fw-semibold small">Status Pelaksanaan</label>
                        <select class="form-select sibk-form-select" id="status_pelaksanaan" name="status_pelaksanaan">
                            <option value="" disabled {{ !$isEdit ? 'selected' : '' }}>Pilih status</option>
                            <option value="rencana">Rencana / Terjadwal</option>
                            <option value="terlaksana" {{ $isEdit ? 'selected' : '' }}>Terlaksana</option>
                            <option value="ditunda">Ditunda</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>

                    <!-- Baris 2: Tanggal Rencana & Tanggal Pelaksanaan -->
                    <div class="col-12 col-md-6">
                        <label for="tanggal_rencana" class="form-label sibk-form-label text-dark fw-semibold small">Tanggal Rencana</label>
                        <input type="date" class="form-control sibk-form-control" id="tanggal_rencana" name="tanggal_rencana" value="{{ $isEdit ? '2026-08-15' : '' }}">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="tanggal_pelaksanaan" class="form-label sibk-form-label text-dark fw-semibold small">Tanggal Pelaksanaan</label>
                        <input type="date" class="form-control sibk-form-control" id="tanggal_pelaksanaan" name="tanggal_pelaksanaan" value="{{ $isEdit ? '2026-08-15' : '' }}">
                        <span class="text-muted d-block mt-1" style="font-size: 0.75rem;">Isi setelah kegiatan dilaksanakan</span>
                    </div>

                    <!-- Baris 3: Hasil & Rencana Berikutnya -->
                    <div class="col-12 col-md-6">
                        <label for="hasil" class="form-label sibk-form-label text-dark fw-semibold small">Hasil</label>
                        <textarea class="form-control sibk-form-control" id="hasil" name="hasil" rows="4" placeholder="Tuliskan hasil kegiatan">{{ $isEdit ? 'Telah dilaksanakan sesi konsultasi individual dengan murid mengenai kehadiran dan kesepakatan belajar.' : '' }}</textarea>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="rencana_berikutnya" class="form-label sibk-form-label text-dark fw-semibold small">Rencana Berikutnya</label>
                        <textarea class="form-control sibk-form-control" id="rencana_berikutnya" name="rencana_berikutnya" rows="4" placeholder="Tuliskan rencana berikutnya bila ada">{{ $isEdit ? 'Pemantauan berkala pada pekan depan bersama wali kelas.' : '' }}</textarea>
                    </div>

                    <!-- Baris 4: Pencatat (otomatis) -->
                    <div class="col-12">
                        <label for="pencatat" class="form-label sibk-form-label text-dark fw-semibold small">Pencatat (otomatis)</label>
                        <input type="text" class="form-control sibk-form-control" id="pencatat" value="Pengguna yang sedang masuk" readonly style="background-color: #eef3f8; color: #5f6f86; font-weight: 500;">
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="col-12 mt-4 pt-2">
                        <div class="d-flex justify-content-end align-items-center gap-3">
                            <a href="{{ route('cases.show') }}" class="btn btn-light text-primary fw-bold px-4 py-2" style="border-radius: 14px;">Batal</a>
                            <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow-sm" style="border-radius: 14px; background-color: #2f6fc6; border-color: #2f6fc6;">Simpan Tindak Lanjut</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

    </div>
@endsection
