@extends('layouts.app-2')

@section('page-title', 'Layanan BK - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy">
                <h1>Layanan BK</h1>
                <p>Cari, filter, dan kelola penanganan kasus serta sesi bimbingan konseling.</p>
            </div>
            <div class="sibk-page-header__actions d-flex flex-wrap gap-2">
                @if(($activeTab ?? 'kasus') === 'konsultasi')
                    <a href="{{ route('consultations.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        Catat Konsultasi
                    </a>
                @else
                    <a href="{{ route('cases.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Buat Kasus Baru
                    </a>
                @endif
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="sibk-panel mb-4 p-2">
            <ul class="nav nav-pills gap-1">
                <li class="nav-item">
                    <a class="nav-link {{ ($activeTab ?? 'kasus') === 'kasus' ? 'active' : '' }}" href="{{ route('cases.index', ['tab' => 'kasus']) }}">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                        Kasus & Penanganan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($activeTab ?? '') === 'konsultasi' ? 'active' : '' }}" href="{{ route('cases.index', ['tab' => 'konsultasi']) }}">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        Sesi Bimbingan & Konsultasi
                    </a>
                </li>
            </ul>
        </div>

        @if(($activeTab ?? 'kasus') === 'kasus')
            <!-- TAB 1: KASUS & PENANGANAN -->
            
            <!-- Filter Panel Kasus -->
            <div class="sibk-panel mb-4">
                <div class="sibk-panel__body p-4">
                    <form class="sibk-filter-form row g-3 align-items-end" action="#" method="GET">
                        <div class="col-12 col-md-3">
                            <label for="search_kasus" class="form-label sibk-form-label">Cari kasus</label>
                            <input type="text" class="form-control sibk-form-control" id="search_kasus" placeholder="Nomor kasus atau nama murid">
                        </div>
                        <div class="col-12 col-md-2">
                            <label for="kelas_kasus" class="form-label sibk-form-label">Kelas</label>
                            <select class="form-select sibk-form-select" id="kelas_kasus">
                                <option selected>Semua kelas</option>
                                <option>X RPL 1</option>
                                <option>X RPL 2</option>
                                <option>XI RPL 1</option>
                                <option>XI RPL 2</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label for="sumber_kasus" class="form-label sibk-form-label">Sumber</label>
                            <select class="form-select sibk-form-select" id="sumber_kasus">
                                <option selected>Semua sumber</option>
                                <option>e-Tatib</option>
                                <option>Rujukan</option>
                                <option>Murid datang sendiri</option>
                                <option>Temuan</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label for="status_kasus" class="form-label sibk-form-label">Status</label>
                            <select class="form-select sibk-form-select" id="status_kasus">
                                <option selected>Semua status</option>
                                <option>Baru</option>
                                <option>Dalam Penanganan</option>
                                <option>Selesai</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="periode_kasus" class="form-label sibk-form-label">Periode</label>
                            <select class="form-select sibk-form-select" id="periode_kasus">
                                <option selected>Semua periode</option>
                                <option>Agustus 2026</option>
                                <option>Juli 2026</option>
                            </select>
                        </div>
                    </form>
                    <div class="sibk-filter-footer mt-4 text-muted small fw-medium">
                        {{ count($cases) }} kasus ditampilkan
                    </div>
                </div>
            </div>

            <!-- Table Panel Kasus -->
            <div class="sibk-panel">
                <div class="sibk-panel__header p-4 border-0">
                    <h3 class="sibk-panel__title">Daftar Kasus Aktif & Penanganan</h3>
                    <p class="sibk-panel__subtitle">Kasus layanan BK yang sesuai dengan filter.</p>
                </div>
                
                <div class="table-responsive">
                    <table class="table sibk-table mb-0">
                        <thead>
                            <tr>
                                <th>No. Kasus</th>
                                <th>Murid</th>
                                <th>Kelas</th>
                                <th>Sumber</th>
                                <th>Bidang</th>
                                <th>Status</th>
                                <th>Tindak lanjut</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cases as $case)
                                <tr>
                                    <td class="fw-bold text-primary">{{ $case['no'] }}</td>
                                    <td class="fw-bold text-dark">{{ $case['student'] }}</td>
                                    <td>{{ $case['class'] }}</td>
                                    <td>{{ $case['source'] }}</td>
                                    <td>{{ $case['category'] }}</td>
                                    <td>
                                        @php
                                            $badgeTone = 'primary';
                                            if ($case['status'] === 'Selesai') $badgeTone = 'success';
                                            elseif ($case['status'] === 'Baru') $badgeTone = 'info';
                                        @endphp
                                        <span class="sibk-badge sibk-badge--{{ $badgeTone }}">{{ $case['status'] }}</span>
                                    </td>
                                    <td>{{ $case['follow_up'] }}</td>
                                    <td>
                                        <a href="{{ route('cases.show') }}" class="fw-bold text-decoration-none">Buka</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada kasus yang dicatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="sibk-panel__footer p-4 border-top border-light">
                    <div class="text-muted small fw-medium">
                        Menampilkan 1–{{ count($cases) }} dari {{ count($cases) }} kasus
                    </div>
                </div>
            </div>

        @else
            <!-- TAB 2: SESI BIMBINGAN & KONSULTASI -->
            
            <!-- Filter Panel Konsultasi -->
            <div class="sibk-panel mb-4">
                <div class="sibk-panel__body p-4">
                    <form class="sibk-filter-form row g-3 align-items-end" action="#" method="GET">
                        <div class="col-12 col-md-3">
                            <label for="search_sesi" class="form-label sibk-form-label">Cari sesi bimbingan</label>
                            <input type="text" class="form-control sibk-form-control" id="search_sesi" placeholder="Nama murid atau topik">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="jenis_layanan" class="form-label sibk-form-label">Jenis Layanan</label>
                            <select class="form-select sibk-form-select" id="jenis_layanan">
                                <option selected>Semua jenis layanan</option>
                                <option>Pribadi</option>
                                <option>Sosial</option>
                                <option>Belajar</option>
                                <option>Karier</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="status_sesi" class="form-label sibk-form-label">Status Sesi</label>
                            <select class="form-select sibk-form-select" id="status_sesi">
                                <option selected>Semua status</option>
                                <option>Terlaksana</option>
                                <option>Dijadwalkan</option>
                                <option>Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="periode_sesi" class="form-label sibk-form-label">Periode</label>
                            <select class="form-select sibk-form-select" id="periode_sesi">
                                <option selected>Semua periode</option>
                                <option>Agustus 2026</option>
                                <option>Juli 2026</option>
                            </select>
                        </div>
                    </form>
                    <div class="sibk-filter-footer mt-4 text-muted small fw-medium">
                        {{ count($consultations) }} sesi bimbingan ditampilkan
                    </div>
                </div>
            </div>

            <!-- Table Panel Konsultasi -->
            <div class="sibk-panel">
                <div class="sibk-panel__header p-4 border-0">
                    <h3 class="sibk-panel__title">Daftar Sesi Bimbingan & Konsultasi</h3>
                    <p class="sibk-panel__subtitle">Riwayat dan jadwal interaksi konseling yang tercatat.</p>
                </div>
                
                <div class="table-responsive">
                    <table class="table sibk-table mb-0">
                        <thead>
                            <tr>
                                <th>No. Sesi</th>
                                <th>Tanggal</th>
                                <th>Murid & Kelas</th>
                                <th>Jenis Layanan</th>
                                <th>Kasus Terkait</th>
                                <th>Status</th>
                                <th>Ringkasan Umum</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($consultations as $session)
                                <tr>
                                    <td class="fw-bold text-primary">{{ $session['id'] }}</td>
                                    <td class="fw-medium text-dark">{{ $session['date'] }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $session['student'] }}</div>
                                        <div class="small text-muted">{{ $session['class'] }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $session['type'] }}</span>
                                    </td>
                                    <td>
                                        @if(($session['case_ref'] ?? '—') !== '—')
                                            <a href="{{ route('cases.show') }}" class="fw-semibold text-primary text-decoration-none">
                                                {{ $session['case_ref'] }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeTone = 'primary';
                                            if ($session['status'] === 'Terlaksana') $badgeTone = 'success';
                                            elseif ($session['status'] === 'Dijadwalkan') $badgeTone = 'info';
                                            elseif ($session['status'] === 'Dibatalkan') $badgeTone = 'danger';
                                        @endphp
                                        <span class="sibk-badge sibk-badge--{{ $badgeTone }}">{{ $session['status'] }}</span>
                                    </td>
                                    <td class="text-truncate" style="max-width: 260px;" title="{{ $session['summary'] ?? '' }}">
                                        {{ $session['summary'] ?? $session['topic'] }}
                                    </td>
                                    <td>
                                        <a href="{{ route('consultations.show') }}" class="fw-bold text-decoration-none">Buka</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada sesi bimbingan yang dicatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="sibk-panel__footer p-4 border-top border-light">
                    <div class="text-muted small fw-medium">
                        Menampilkan 1–{{ count($consultations) }} dari {{ count($consultations) }} sesi
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
