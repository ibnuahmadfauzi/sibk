@extends('layouts.app-2')

@section('page-title', 'Ajukan Koreksi Data - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Page Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('corrections.index') }}" class="btn btn-light d-inline-flex align-items-center justify-content-center p-2 rounded-circle" style="width: 40px; height: 40px;" aria-label="Kembali">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="sibk-page-header__copy m-0">
                    <h1 class="mb-1 fs-3 fw-bold">Ajukan Koreksi Data</h1>
                    <p class="mb-0 text-muted">Usulkan perubahan data operasional kasus atau perbaikan data master secara resmi.</p>
                </div>
            </div>
        </div>

        <form action="{{ route('corrections.index') }}" method="GET">
            <!-- Bagian 1: Objek & Jenis Koreksi -->
            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <div class="d-flex align-items-center justify-content-center rounded-circle text-primary" style="width: 48px; height: 48px; background-color: #e9f2fb;">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m18 5-3-3H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.83A2 2 0 0 0 19.41 6l-1.41-1zM14 2.5V7h4.5M8 13h8M8 17h8M8 9h2"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="fs-5 mb-1 text-dark fw-bold">Target Data yang Ingin Dikoreksi</h4>
                            <p class="text-muted small mb-0">Tentukan jenis data dan identitas objek yang memerlukan penyesuaian.</p>
                        </div>
                    </div>
                    
                    <div class="row g-4 ms-0 ms-md-5 ps-0 ps-md-2">
                        <!-- Jenis Koreksi -->
                        <div class="col-12">
                            <label class="form-label sibk-form-label text-dark fw-bold mb-2">Kategori Koreksi Data</label>
                            <div class="d-flex flex-wrap gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="kategori_koreksi" id="kat-operasional" value="Operasional" {{ ($objectType ?? 'Kasus') === 'Kasus' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="kat-operasional">
                                        <strong>Data Operasional</strong> <span class="text-muted small">(Kasus, Tanggal Layanan, Bidang Layanan, Tindak Lanjut)</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="kategori_koreksi" id="kat-master" value="Master" {{ ($objectType ?? '') === 'Murid' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="kat-master">
                                        <strong>Data Master</strong> <span class="text-muted small">(Nama Murid, NISN, Rombel/Kelas dari Dapodik)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Objek Target -->
                        <div class="col-md-6">
                            <label for="target_object" class="form-label sibk-form-label text-dark fw-semibold">Objek Target</label>
                            <input type="text" class="form-control sibk-form-control bg-light" id="target_object" name="target_object" value="{{ $objectType ?? 'Kasus' }} {{ $objectId ?? 'K-014' }} ({{ $studentName ?? 'Murid A' }})">
                        </div>

                        <!-- Field / Atribut -->
                        <div class="col-md-6">
                            <label for="field_attribute" class="form-label sibk-form-label text-dark fw-semibold">Atribut / Field yang Ingin Dikoreksi</label>
                            <select class="form-select sibk-form-select" id="field_attribute" name="field_attribute">
                                <option value="Bidang Layanan" {{ ($attribute ?? '') === 'Bidang Layanan' ? 'selected' : '' }}>Bidang Layanan</option>
                                <option value="Tanggal Layanan" {{ ($attribute ?? '') === 'Tanggal Layanan' ? 'selected' : '' }}>Tanggal Layanan</option>
                                <option value="Penanganan Awal" {{ ($attribute ?? '') === 'Penanganan Awal' ? 'selected' : '' }}>Penanganan Awal</option>
                                <option value="Nama Murid" {{ ($attribute ?? '') === 'Nama Murid' ? 'selected' : '' }}>Nama Murid (Data Master)</option>
                                <option value="NISN" {{ ($attribute ?? '') === 'NISN' ? 'selected' : '' }}>NISN (Data Master)</option>
                                <option value="Bentuk Tindak Lanjut" {{ ($attribute ?? '') === 'Bentuk Tindak Lanjut' ? 'selected' : '' }}>Bentuk Tindak Lanjut</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Perbandingan Nilai & Alasan -->
            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <div class="d-flex align-items-center justify-content-center rounded-circle text-warning" style="width: 48px; height: 48px; background-color: #fdf3eb;">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="fs-5 mb-1 text-dark fw-bold">Nilai Perubahan & Alasan Pengajuan</h4>
                            <p class="text-muted small mb-0">Cantumkan perbandingan nilai saat ini dengan usulan nilai baru beserta alasan yang valid.</p>
                        </div>
                    </div>
                    
                    <div class="row g-4 ms-0 ms-md-5 ps-0 ps-md-2">
                        <!-- Nilai Lama -->
                        <div class="col-md-6">
                            <label for="old_value" class="form-label sibk-form-label text-dark fw-semibold">Nilai Saat Ini (Lama)</label>
                            <input type="text" class="form-control sibk-form-control bg-light" id="old_value" name="old_value" value="{{ $oldValue ?? 'Pribadi' }}">
                        </div>

                        <!-- Nilai Usulan -->
                        <div class="col-md-6">
                            <label for="new_value" class="form-label sibk-form-label text-dark fw-semibold">Nilai Usulan (Baru)</label>
                            <input type="text" class="form-control sibk-form-control" id="new_value" name="new_value" placeholder="Masukkan nilai baru yang diusulkan" value="Belajar">
                        </div>

                        <!-- Alasan Pengajuan -->
                        <div class="col-12">
                            <label for="reason" class="form-label sibk-form-label text-dark fw-semibold">Alasan Pengajuan Koreksi <span class="text-danger">*</span></label>
                            <textarea class="form-control sibk-form-control" id="reason" name="reason" rows="3" placeholder="Jelaskan dasar pertimbangan atau alasan perlunya perubahan data ini..." required>Penyesuaian berdasarkan hasil pemeriksaan data layanan dan asesmen tindak lanjut bersama murid.</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Catatan Tata Kelola & Akuntabilitas (SRS COR-01 & COR-02) -->
            <div class="alert alert-info d-flex align-items-start gap-3 p-3 mb-4 rounded-3 border-0 bg-opacity-10 bg-primary">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary flex-shrink-0 mt-1">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <div class="small text-dark">
                    <strong>Ketentuan Tata Kelola Koreksi Data:</strong>
                    <ul class="mb-0 ps-3 mt-1 text-muted">
                        <li>Pengajuan <strong>koreksi data operasional</strong> akan diverifikasi oleh Koordinator BK melalui menu <em>Koreksi Data (PG-404 / PG-405)</em>.</li>
                        <li>Pengajuan <strong>koreksi data master</strong> akan ditindaklanjuti oleh Admin IT melalui sumber resmi Dapodik.</li>
                        <li>Seluruh perubahan, alasan, dan identitas pengaju akan dicatat secara otomatis dalam <strong>Riwayat Perubahan (Audit Trail)</strong>.</li>
                    </ul>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="d-flex justify-content-end gap-3 mb-5">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('corrections.index') }}" class="btn btn-outline-secondary px-4 py-2">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2" onclick="alert('Pengajuan koreksi data berhasil dikirim ke antrean verifikasi!')">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    <span>Kirim Pengajuan Koreksi</span>
                </button>
            </div>
        </form>
    </div>
@endsection
