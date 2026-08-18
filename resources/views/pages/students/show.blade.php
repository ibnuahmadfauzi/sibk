@extends('layouts.app-2')

@section('page-title', 'Profil Murid - Ruang BK')

@php
    $student = [
        'nisn' => '0012345678',
        'nis' => '2021001',
        'name' => 'Ahmad Fauzi',
        'class' => 'X RPL 1',
        'gender' => 'Laki-laki',
        'religion' => 'Islam',
        'phone' => '081234567890',
        'address' => 'Jl. Merdeka No. 123, Surabaya',
        'parent_name' => 'Budi Sudarsono',
        'parent_phone' => '081298765432',
        'status' => 'Aktif'
    ];
@endphp

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ url('students') }}" class="btn btn-icon btn-light" aria-label="Kembali">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="sibk-page-header__copy m-0">
                    <h1 class="mb-1">Profil Murid</h1>
                    <p class="mb-0">Detail informasi dan riwayat layanan BK.</p>
                </div>
            </div>
            <div class="sibk-page-header__actions">
                <a href="#" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                    Edit Profil Manual
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Profile Card -->
            <div class="col-12 col-lg-4">
                <div class="sibk-panel h-100">
                    <div class="sibk-panel__body p-4">
                        <div class="text-center mb-4">
                            <div class="sibk-avatar-placeholder mx-auto mb-3" style="width: 100px; height: 100px; border-radius: 50%; background-color: #e9ecef; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; color: #6c757d;">
                                {{ substr($student['name'], 0, 1) }}
                            </div>
                            <h3 class="fw-bold mb-1">{{ $student['name'] }}</h3>
                            <span class="badge bg-primary bg-opacity-10 text-primary">{{ $student['class'] }}</span>
                        </div>

                        <hr class="text-light">

                        <div class="mb-3">
                            <label class="text-muted small fw-medium d-block mb-1">NISN / NIS</label>
                            <div class="fw-bold text-dark">{{ $student['nisn'] }} / {{ $student['nis'] }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-medium d-block mb-1">Jenis Kelamin</label>
                            <div class="fw-medium text-dark">{{ $student['gender'] }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-medium d-block mb-1">No. HP / WhatsApp</label>
                            <div class="fw-medium text-dark">{{ $student['phone'] }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-medium d-block mb-1">Alamat</label>
                            <div class="fw-medium text-dark">{{ $student['address'] }}</div>
                        </div>
                        <div class="mb-0">
                            <label class="text-muted small fw-medium d-block mb-1">Orang Tua / Wali</label>
                            <div class="fw-medium text-dark">{{ $student['parent_name'] }} ({{ $student['parent_phone'] }})</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: History Tabs -->
            <div class="col-12 col-lg-8">
                <div class="sibk-panel">
                    <div class="sibk-panel__header p-0 border-bottom">
                        <ul class="nav nav-tabs sibk-nav-tabs px-4 border-0" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="konsultasi-tab" data-bs-toggle="tab" data-bs-target="#konsultasi-pane" type="button" role="tab" aria-controls="konsultasi-pane" aria-selected="true">Riwayat Konsultasi</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="kasus-tab" data-bs-toggle="tab" data-bs-target="#kasus-pane" type="button" role="tab" aria-controls="kasus-pane" aria-selected="false">Riwayat Kasus</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="prestasi-tab" data-bs-toggle="tab" data-bs-target="#prestasi-pane" type="button" role="tab" aria-controls="prestasi-pane" aria-selected="false">Prestasi</button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="sibk-panel__body p-4 tab-content" id="profileTabsContent">
                        
                        <!-- Tab Konsultasi -->
                        <div class="tab-pane fade show active" id="konsultasi-pane" role="tabpanel" aria-labelledby="konsultasi-tab" tabindex="0">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="m-0 fs-5">Sesi Bimbingan & Konsultasi</h4>
                                <a href="{{ url('consultations/create') }}" class="btn btn-sm btn-primary">Tambah Sesi</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table sibk-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Topik</th>
                                            <th>Jenis</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>10 Agu 2026</td>
                                            <td class="fw-medium text-dark">Konsultasi Pemilihan Jurusan Kuliah</td>
                                            <td>Bimbingan Karir</td>
                                            <td><span class="badge bg-success bg-opacity-10 text-success">Selesai</span></td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-center py-3 text-muted">Tidak ada riwayat lain.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Kasus -->
                        <div class="tab-pane fade" id="kasus-pane" role="tabpanel" aria-labelledby="kasus-tab" tabindex="0">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="m-0 fs-5">Riwayat Pelanggaran & Kasus</h4>
                                <a href="{{ url('cases/create') }}" class="btn btn-sm btn-primary">Catat Kasus</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table sibk-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Kasus / Pelanggaran</th>
                                            <th>Tindak Lanjut</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">Murid ini tidak memiliki riwayat kasus.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Prestasi -->
                        <div class="tab-pane fade" id="prestasi-pane" role="tabpanel" aria-labelledby="prestasi-tab" tabindex="0">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="m-0 fs-5">Catatan Prestasi</h4>
                                <a href="{{ url('achievements/create') }}" class="btn btn-sm btn-primary">Tambah Prestasi</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table sibk-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Nama Prestasi</th>
                                            <th>Tingkat</th>
                                            <th>Bukti</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>15 Jul 2026</td>
                                            <td class="fw-medium text-dark">Juara 1 Lomba Web Design Provinsi</td>
                                            <td>Provinsi</td>
                                            <td><a href="#" class="text-decoration-none">Lihat</a></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
