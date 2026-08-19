@extends('layouts.app-2')

@section('page-title', 'Detail Kasus - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="sibk-page-header__copy">
                <h1>Detail Kasus K-014</h1>
                <p>Murid A &bull; X RPL 1</p>
            </div>
            <div class="sibk-page-header__actions d-flex gap-2">
                <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Tambah Tindak Lanjut
                </button>
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Selesaikan Kasus
                </button>
            </div>
        </div>

        <!-- Ringkasan Kasus + Tabs -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4">
                <!-- Ringkasan Kasus -->
                <div class="mb-4">
                    <h5 class="sibk-panel__title mb-3">Ringkasan Kasus</h5>
                    <div class="d-flex flex-wrap align-items-center gap-4">
                        <span class="text-dark"><strong>Sumber:</strong> e-Tatib</span>
                        <span class="text-dark"><strong>Bidang layanan:</strong> Pribadi</span>
                        <span class="sibk-badge sibk-badge--primary">Dalam Penanganan</span>
                        <span class="text-dark"><strong>Tanggal layanan:</strong> 14 Agustus 2026</span>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs border-bottom mb-4" id="caseTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="ringkasan-tab" data-bs-toggle="tab" data-bs-target="#ringkasan" type="button" role="tab">Ringkasan</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-muted" id="penanganan-tab" data-bs-toggle="tab" data-bs-target="#penanganan" type="button" role="tab">Penanganan Awal</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-muted" id="riwayat-tab" data-bs-toggle="tab" data-bs-target="#riwayat" type="button" role="tab">Riwayat dan Tindak Lanjut</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-muted" id="etatib-tab" data-bs-toggle="tab" data-bs-target="#etatib" type="button" role="tab">Data e-Tatib</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-muted" id="perubahan-tab" data-bs-toggle="tab" data-bs-target="#perubahan" type="button" role="tab">Riwayat Perubahan</button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="caseTabsContent">
                    <div class="tab-pane fade show active" id="ringkasan" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="sibk-panel border">
                                    <div class="sibk-panel__body p-4">
                                        <h6 class="fw-bold text-dark mb-2">Informasi Awal</h6>
                                        <p class="text-muted small mb-0">Ringkasan informasi awal kasus ditampilkan di bagian ini.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="sibk-panel border">
                                    <div class="sibk-panel__body p-4">
                                        <h6 class="fw-bold text-dark mb-2">Penanganan Awal</h6>
                                        <p class="text-muted small mb-0">Ringkasan tindakan awal yang telah dilakukan.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tindak Lanjut Terbaru -->
        <div class="sibk-panel">
            <div class="sibk-panel__header p-4 border-0">
                <h3 class="sibk-panel__title">Tindak Lanjut Terbaru</h3>
            </div>
            <div class="table-responsive">
                <table class="table sibk-table mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th>Hasil</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>15 Agu</td>
                            <td>Konsultasi</td>
                            <td><span class="sibk-badge sibk-badge--success">Selesai</span></td>
                            <td>Ringkasan hasil tindak lanjut</td>
                            <td><a href="#" class="fw-bold text-decoration-none">Buka</a></td>
                        </tr>
                        <tr>
                            <td>18 Agu</td>
                            <td>Pertemuan</td>
                            <td><span class="sibk-badge sibk-badge--warning">Terjadwal</span></td>
                            <td>Belum dilaksanakan</td>
                            <td><a href="#" class="fw-bold text-decoration-none">Buka</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection

