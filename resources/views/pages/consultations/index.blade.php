@extends('layouts.app-2')

@section('page-title', 'Bimbingan & Konsultasi - Ruang BK')

@php
    // Dummy static data
    $consultations = [
        ['id' => 'KNS-001', 'student' => 'Ahmad Fauzi', 'class' => 'X RPL 1', 'topic' => 'Pemilihan Ekstrakurikuler', 'type' => 'Bimbingan Pribadi', 'date' => '18 Agu 2026', 'time' => '10:00 - 11:00', 'status' => 'Selesai'],
        ['id' => 'KNS-002', 'student' => 'Budi Santoso', 'class' => 'X RPL 1', 'topic' => 'Minat Bakat Penjurusan', 'type' => 'Bimbingan Karir', 'date' => '19 Agu 2026', 'time' => '13:00 - 14:00', 'status' => 'Terjadwal'],
        ['id' => 'KNS-003', 'student' => 'Citra Lestari', 'class' => 'X RPL 2', 'topic' => 'Motivasi Belajar Menurun', 'type' => 'Bimbingan Belajar', 'date' => '20 Agu 2026', 'time' => '09:00 - 10:00', 'status' => 'Menunggu Konfirmasi'],
        ['id' => 'KNS-004', 'student' => 'Dewi Anggraini', 'class' => 'XI TKJ 1', 'topic' => 'Konflik dengan Teman Kelas', 'type' => 'Bimbingan Sosial', 'date' => '15 Agu 2026', 'time' => '11:00 - 12:00', 'status' => 'Dibatalkan'],
    ];
@endphp

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="sibk-page-header__copy">
                <h1>Bimbingan & Konsultasi</h1>
                <p>Kelola jadwal, riwayat, dan hasil sesi layanan bimbingan.</p>
            </div>
            <div class="sibk-page-header__actions">
                <a href="{{ url('consultations/create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Buat Jadwal Baru
                </a>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4">
                <form class="sibk-filter-form row g-3 align-items-end" action="#" method="GET">
                    <div class="col-12 col-md-3">
                        <label for="search" class="form-label sibk-form-label">Cari sesi</label>
                        <input type="text" class="form-control sibk-form-control" id="search" placeholder="Nama murid atau topik">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="jenis" class="form-label sibk-form-label">Jenis Layanan</label>
                        <select class="form-select sibk-form-select" id="jenis">
                            <option selected>Semua Jenis</option>
                            <option value="pribadi">Pribadi</option>
                            <option value="sosial">Sosial</option>
                            <option value="belajar">Belajar</option>
                            <option value="karir">Karir</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="status" class="form-label sibk-form-label">Status</label>
                        <select class="form-select sibk-form-select" id="status">
                            <option selected>Semua status</option>
                            <option value="selesai">Selesai</option>
                            <option value="terjadwal">Terjadwal</option>
                            <option value="menunggu">Menunggu</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="tanggal" class="form-label sibk-form-label">Bulan</label>
                        <select class="form-select sibk-form-select" id="tanggal">
                            <option selected>Agustus 2026</option>
                            <option value="juli">Juli 2026</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Panel -->
        <div class="sibk-panel">
            <div class="sibk-panel__header p-4 border-0">
                <h3 class="sibk-panel__title">Jadwal & Riwayat</h3>
                <p class="sibk-panel__subtitle">Daftar sesi bimbingan yang telah dicatat.</p>
            </div>
            
            <div class="table-responsive">
                <table class="table sibk-table mb-0">
                    <thead>
                        <tr>
                            <th>Murid & Kelas</th>
                            <th>Topik & Jenis Layanan</th>
                            <th>Jadwal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consultations as $session)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $session['student'] }}</div>
                                    <div class="small text-muted">{{ $session['class'] }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $session['topic'] }}</div>
                                    <div class="small text-muted">{{ $session['type'] }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $session['date'] }}</div>
                                    <div class="small text-muted">{{ $session['time'] }}</div>
                                </td>
                                <td>
                                    @php
                                        $badgeTone = 'primary';
                                        if ($session['status'] === 'Selesai') $badgeTone = 'success';
                                        elseif ($session['status'] === 'Terjadwal') $badgeTone = 'info';
                                        elseif ($session['status'] === 'Dibatalkan') $badgeTone = 'danger';
                                        elseif ($session['status'] === 'Menunggu Konfirmasi') $badgeTone = 'warning';
                                    @endphp
                                    <span class="badge bg-{{ $badgeTone }} bg-opacity-10 text-{{ $badgeTone }}">{{ $session['status'] }}</span>
                                </td>
                                <td>
                                    <a href="{{ url('consultations/show') }}" class="fw-bold text-decoration-none">Buka</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada data sesi bimbingan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="sibk-panel__footer p-4 border-top border-light">
                <div class="text-muted small fw-medium">
                    Menampilkan 1-{{ count($consultations) }} dari {{ count($consultations) }} sesi
                </div>
            </div>
        </div>

    </div>
@endsection
