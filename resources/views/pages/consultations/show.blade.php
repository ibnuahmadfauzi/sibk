@extends('layouts.app-2')

@section('page-title', 'Detail Sesi Konsultasi - Ruang BK')

@php
    $session = [
        'id' => 'KNS-001',
        'student' => 'Ahmad Fauzi',
        'nisn' => '0012345678',
        'class' => 'X RPL 1',
        'topic' => 'Pemilihan Ekstrakurikuler',
        'type' => 'Bimbingan Karir',
        'source' => 'Inisiatif Murid',
        'date' => '18 Agu 2026',
        'time' => '10:00 - 11:00',
        'status' => 'Selesai',
        'counselor' => 'Dra. Budi Hartati',
        'notes' => 'Murid kebingungan memilih antara eskul IT Club atau Basket. Telah diberikan gambaran potensi waktu dan beban tugas.',
        'conclusion' => 'Murid memutuskan untuk mencoba IT Club selama 1 semester pertama.',
        'follow_up' => 'Pantau partisipasi eskul di akhir semester.'
    ];
@endphp

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ url('consultations') }}" class="btn btn-icon btn-light" aria-label="Kembali">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="sibk-page-header__copy m-0">
                    <h1 class="mb-1">Detail Sesi Bimbingan</h1>
                    <p class="mb-0">Nomor Sesi: {{ $session['id'] }}</p>
                </div>
            </div>
            <div class="sibk-page-header__actions">
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Cetak Riwayat
                </button>
                <a href="{{ url('consultations/create?mode=edit') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    Edit Data Sesi
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Info Sesi -->
            <div class="col-12 col-lg-4">
                <div class="sibk-panel mb-4">
                    <div class="sibk-panel__header p-4 border-bottom">
                        <h4 class="m-0 fs-5">Informasi Murid & Jadwal</h4>
                    </div>
                    <div class="sibk-panel__body p-4">
                        <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded">
                            <div class="sibk-avatar-placeholder" style="width: 50px; height: 50px; border-radius: 50%; background-color: #fff; border: 1px solid #dee2e6; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: bold; color: #6c757d;">
                                {{ substr($session['student'], 0, 1) }}
                            </div>
                            <div>
                                <div class="fw-bold text-dark">{{ $session['student'] }}</div>
                                <div class="small text-muted">{{ $session['class'] }} • {{ $session['nisn'] }}</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small fw-medium d-block mb-1">Status Sesi</label>
                            <div><span class="badge bg-success bg-opacity-10 text-success">{{ $session['status'] }}</span></div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-medium d-block mb-1">Jadwal Pelaksanaan</label>
                            <div class="fw-medium text-dark">{{ $session['date'] }}</div>
                            <div class="small text-muted">{{ $session['time'] }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-medium d-block mb-1">Guru Konselor</label>
                            <div class="fw-medium text-dark">{{ $session['counselor'] }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-medium d-block mb-1">Jenis Layanan</label>
                            <div class="fw-medium text-dark">{{ $session['type'] }}</div>
                        </div>
                        <div class="mb-0">
                            <label class="text-muted small fw-medium d-block mb-1">Sumber Rujukan</label>
                            <div class="fw-medium text-dark">{{ $session['source'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Catatan & Hasil -->
            <div class="col-12 col-lg-8">
                <div class="sibk-panel mb-4">
                    <div class="sibk-panel__header p-4 border-bottom">
                        <h4 class="m-0 fs-5">Hasil & Catatan Bimbingan</h4>
                    </div>
                    <div class="sibk-panel__body p-4 p-md-5">
                        
                        <div class="mb-4">
                            <label class="form-label sibk-form-label fw-bold">Topik / Permasalahan Awal</label>
                            <div class="p-3 bg-light rounded text-dark">
                                {{ $session['topic'] }}
                            </div>
                        </div>

                        <form action="#" method="POST">
                            <div class="mb-4">
                                <label for="catatan" class="form-label sibk-form-label fw-bold">Uraian / Proses Bimbingan</label>
                                <textarea class="form-control sibk-form-control" id="catatan" rows="5" placeholder="Tuliskan jalannya proses bimbingan di sini...">{{ $session['notes'] }}</textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="kesimpulan" class="form-label sibk-form-label fw-bold">Kesimpulan / Solusi</label>
                                <textarea class="form-control sibk-form-control" id="kesimpulan" rows="3" placeholder="Tuliskan kesimpulan yang dicapai...">{{ $session['conclusion'] }}</textarea>
                            </div>

                            <div class="mb-4">
                                <label for="tindak_lanjut" class="form-label sibk-form-label fw-bold">Rencana Tindak Lanjut</label>
                                <textarea class="form-control sibk-form-control" id="tindak_lanjut" rows="3" placeholder="Tuliskan tindak lanjut (jika ada)...">{{ $session['follow_up'] }}</textarea>
                                <div class="form-text mt-2">Kosongkan jika tidak ada tindak lanjut khusus.</div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-5">
                                <button type="button" class="btn btn-primary">Simpan Catatan Bimbingan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
