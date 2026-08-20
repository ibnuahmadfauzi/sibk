@extends('layouts.app-2')

@section('page-title', 'Pratinjau Laporan - ' . $reportTitle . ' - Ruang BK')

@section('body')
    <div class="sibk-dashboard sibk-report-preview-page">
        <!-- Print-only Official Letterhead (Kop Surat) -->
        <div class="sibk-print-header">
            <p class="sibk-print-header__instansi">Pemerintah Provinsi Jawa Timur • Dinas Pendidikan</p>
            <h1 class="sibk-print-header__sekolah">SMK NEGERI 1 SURABAYA</h1>
            <p class="sibk-print-header__alamat">Jl. SMEA No. 4, Wonokromo, Surabaya, Jawa Timur 60243 • Telp. (031) 8292038</p>
            <h2 class="sibk-print-header__judul">LAPORAN BIMBINGAN DAN KONSELING: {{ strtoupper($reportTitle) }}</h2>
            <p class="sibk-print-header__periode">Periode Cetak: 1–31 Agustus 2026 • Tahun Ajaran 2026/2027</p>
        </div>

        <!-- Page Header with Back Link (Screen view) -->
        <div class="sibk-page-header mb-4 no-print">
            <div class="d-flex align-items-center gap-2 mb-2">
                <a href="{{ route('reports.index') }}" class="sibk-back-link d-inline-flex align-items-center gap-1 text-decoration-none text-muted small fw-semibold">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Pusat Laporan
                </a>
            </div>
            <div class="sibk-page-header__copy">
                <h1>Pratinjau Laporan</h1>
                <p>{{ $reportTitle }} — <span class="text-muted">{{ $reportDesc ?? 'Rekapitulasi resmi data layanan BK' }}</span></p>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="sibk-panel mb-4 sibk-filter-panel no-print">
            <div class="sibk-panel__body p-4">
                <h3 class="sibk-filter-title mb-3 d-flex align-items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    Filter Laporan
                </h3>
                <form class="sibk-filter-form row g-3 align-items-end" action="{{ route('reports.preview') }}" method="GET">
                    <input type="hidden" name="type" value="{{ $type }}">
                    
                    @if(in_array('periode', $filters ?? ['periode']))
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="filter-periode" class="form-label sibk-form-label">Periode</label>
                            <select class="form-select sibk-form-select" id="filter-periode" name="periode">
                                <option selected value="1-31-agu-2026">1–31 Agustus 2026</option>
                                <option value="juli-2026">Juli 2026</option>
                                <option value="semester-1">Semester Ganjil 2026/2027</option>
                                <option value="semester-2">Semester Genap 2025/2026</option>
                            </select>
                        </div>
                    @endif

                    @if(in_array('kelas', $filters ?? []))
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="filter-kelas" class="form-label sibk-form-label">Kelas</label>
                            <select class="form-select sibk-form-select" id="filter-kelas" name="kelas">
                                <option selected value="">Semua kelas</option>
                                <option value="X RPL 1">X RPL 1</option>
                                <option value="X RPL 2">X RPL 2</option>
                                <option value="XI RPL 1">XI RPL 1</option>
                                <option value="XI RPL 2">XI RPL 2</option>
                                <option value="XII RPL 1">XII RPL 1</option>
                            </select>
                        </div>
                    @endif

                    @if(in_array('bidang', $filters ?? []))
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="filter-bidang" class="form-label sibk-form-label">Bidang Layanan</label>
                            <select class="form-select sibk-form-select" id="filter-bidang" name="bidang">
                                <option selected value="">Semua bidang</option>
                                <option value="Pribadi">Pribadi</option>
                                <option value="Sosial">Sosial</option>
                                <option value="Belajar">Belajar</option>
                                <option value="Karier">Karier</option>
                            </select>
                        </div>
                    @endif

                    @if(in_array('kategori_pelanggaran', $filters ?? []))
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="filter-kategori" class="form-label sibk-form-label">Kategori</label>
                            <select class="form-select sibk-form-select" id="filter-kategori" name="kategori">
                                <option selected value="">Semua kategori</option>
                                <option value="Kedisiplinan">Kedisiplinan</option>
                                <option value="Kerapian">Kerapian</option>
                                <option value="Kerajinan">Kerajinan</option>
                            </select>
                        </div>
                    @endif

                    @if(in_array('ambang_poin', $filters ?? []))
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="filter-ambang" class="form-label sibk-form-label">Ambang Poin</label>
                            <select class="form-select sibk-form-select" id="filter-ambang" name="ambang_poin">
                                <option selected value="">Semua akumulasi</option>
                                <option value="10">Poin ≥ 10</option>
                                <option value="25">Poin ≥ 25 (Ambang Binaan)</option>
                                <option value="50">Poin ≥ 50 (Panggilan Orang Tua)</option>
                            </select>
                        </div>
                    @endif

                    @if(in_array('tingkat_prestasi', $filters ?? []))
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="filter-tingkat" class="form-label sibk-form-label">Tingkat</label>
                            <select class="form-select sibk-form-select" id="filter-tingkat" name="tingkat">
                                <option selected value="">Semua tingkat</option>
                                <option value="Kota">Tingkat Kota</option>
                                <option value="Provinsi">Tingkat Provinsi</option>
                                <option value="Nasional">Tingkat Nasional</option>
                            </select>
                        </div>
                    @endif

                    @if(in_array('status_verifikasi', $filters ?? []))
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="filter-status-verifikasi" class="form-label sibk-form-label">Status Verifikasi</label>
                            <select class="form-select sibk-form-select" id="filter-status-verifikasi" name="status_verifikasi">
                                <option selected value="">Semua status</option>
                                <option value="Terverifikasi">Terverifikasi</option>
                                <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
                            </select>
                        </div>
                    @endif

                    @if(in_array('status', $filters ?? []))
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="filter-status" class="form-label sibk-form-label">Status</label>
                            <select class="form-select sibk-form-select" id="filter-status" name="status">
                                <option selected value="">Semua status</option>
                                <option value="Dalam Penanganan">Dalam Penanganan</option>
                                <option value="Baru">Baru</option>
                                <option value="Selesai">Selesai</option>
                                <option value="Dijadwalkan">Dijadwalkan</option>
                                <option value="Terlaksana">Terlaksana</option>
                            </select>
                        </div>
                    @endif

                    <div class="col-12 col-sm-6 col-md-2 ms-auto">
                        <button type="submit" class="btn btn-primary w-100 sibk-btn-apply">
                            Terapkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary KPI Row (Matching Penpot PG-302 3-Card KPI) -->
        <div class="row g-3 mb-4 sibk-report-kpi-row">
            <!-- 1. Total KPI -->
            <div class="col-12 col-md-4">
                <div class="sibk-stat-card sibk-tone--primary p-3 bg-white rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="sibk-stat-card__label text-muted small fw-semibold mb-1">{{ $stats['total']['label'] ?? 'Jumlah Layanan' }}</h4>
                            <div class="sibk-stat-card__value fs-2 fw-bold text-dark">{{ $stats['total']['value'] ?? '0' }}</div>
                            <span class="sibk-stat-meta text-muted small">{{ $stats['total']['sub'] ?? 'Periode aktif' }}</span>
                        </div>
                        <div class="sibk-stat-card__icon p-3 rounded-circle" style="background-color: #e9f2fb; color: #2f6fc6;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Active / In-progress KPI -->
            <div class="col-12 col-md-4">
                <div class="sibk-stat-card sibk-tone--warning p-3 bg-white rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="sibk-stat-card__label text-muted small fw-semibold mb-1">{{ $stats['active']['label'] ?? 'Kasus Aktif' }}</h4>
                            <div class="sibk-stat-card__value fs-2 fw-bold text-dark">{{ $stats['active']['value'] ?? '0' }}</div>
                            <span class="sibk-stat-meta text-muted small">{{ $stats['active']['sub'] ?? 'Perlu pemantauan' }}</span>
                        </div>
                        <div class="sibk-stat-card__icon p-3 rounded-circle" style="background-color: #fbece3; color: #cf6a2d;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><circle cx="12" cy="14" r="3"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Completed / Resolved KPI -->
            <div class="col-12 col-md-4">
                <div class="sibk-stat-card sibk-tone--success p-3 bg-white rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="sibk-stat-card__label text-muted small fw-semibold mb-1">{{ $stats['completed']['label'] ?? 'Selesai' }}</h4>
                            <div class="sibk-stat-card__value fs-2 fw-bold text-dark">{{ $stats['completed']['value'] ?? '0' }}</div>
                            <span class="sibk-stat-meta text-muted small">{{ $stats['completed']['sub'] ?? 'Tuntas' }}</span>
                        </div>
                        <div class="sibk-stat-card__icon p-3 rounded-circle" style="background-color: #e7f4ef; color: #2f8f73;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table Panel (Matching Penpot PG-302 Grid Table) -->
        <div class="sibk-panel mb-4">
            <div class="table-responsive">
                <table class="table sibk-table align-middle mb-0">
                    <thead>
                        <tr>
                            @foreach($columns as $col)
                                <th scope="col">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                @if(isset($row['col1']))
                                    <td class="fw-semibold text-primary">{{ $row['col1'] }}</td>
                                @endif
                                @if(isset($row['col2']))
                                    <td class="fw-medium text-dark">{{ $row['col2'] }}</td>
                                @endif
                                @if(isset($row['col3']))
                                    <td>{{ $row['col3'] }}</td>
                                @endif
                                @if(isset($row['col4']))
                                    <td>{{ $row['col4'] }}</td>
                                @endif
                                @if(isset($row['col5']))
                                    <td>{{ $row['col5'] }}</td>
                                @endif
                                @if(isset($row['category']))
                                    <td>{{ $row['category'] }}</td>
                                @endif
                                @if(isset($row['badge_value']))
                                    <td>
                                        <span class="sibk-badge sibk-badge--{{ $row['status_tone'] ?? 'primary' }}">
                                            {{ $row['badge_value'] }}
                                        </span>
                                    </td>
                                @endif
                                @if(isset($row['status']))
                                    <td>
                                        @php
                                            $status = $row['status'];
                                            $badgeClass = match($status) {
                                                'Dalam Penanganan', 'Perlu Pembinaan Khusus', 'Menunggu Verifikasi', 'Dalam Proses' => 'sibk-badge--warning',
                                                'Baru', 'Dijadwalkan', 'Pemantauan Rutin' => 'sibk-badge--primary',
                                                'Selesai', 'Terlaksana', 'Terverifikasi', 'Normal' => 'sibk-badge--success',
                                                default => 'sibk-badge--info'
                                            };
                                        @endphp
                                        <span class="sibk-badge {{ $badgeClass }}">
                                            {{ $status }}
                                        </span>
                                    </td>
                                @endif
                                @if(isset($row['col6']))
                                    <td class="text-muted">{{ $row['col6'] }}</td>
                                @endif
                                @if(isset($row['col7']))
                                    <td class="text-muted small">{{ $row['col7'] }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) }}" class="text-center py-4 text-muted">
                                    Tidak ada data untuk filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Action Bar (Cetak & Ekspor) -->
        <div class="d-flex justify-content-end gap-3 no-print mb-4">
            <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-4 py-2" onclick="window.print()">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect width="12" height="8" x="6" y="14"/>
                </svg>
                <span>Cetak Laporan</span>
            </button>
            <div class="dropdown">
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    <span>Ekspor Data</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <button class="dropdown-item d-flex align-items-center gap-2 py-2" type="button" onclick="alert('Laporan {{ $reportTitle }} format Excel (.xlsx) berhasil diunduh.')">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#2e8b57" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="17"/><line x1="16" y1="13" x2="8" y2="17"/></svg>
                            <span>Unduh Microsoft Excel (.xlsx)</span>
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item d-flex align-items-center gap-2 py-2" type="button" onclick="alert('Laporan {{ $reportTitle }} format CSV (.csv) berhasil diunduh.')">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#2f6fc6" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            <span>Unduh CSV Data (.csv)</span>
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button class="dropdown-item d-flex align-items-center gap-2 py-2" type="button" onclick="window.print()">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#d16b24" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6"/></svg>
                            <span>Simpan Dokumen PDF (.pdf)</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Print-only Signature Block (Tanda Tangan Pengesahan) -->
        <div class="sibk-print-footer">
            <div class="sibk-print-footer__grid">
                <div class="sibk-print-footer__col">
                    <p class="sibk-print-footer__role">Mengetahui,<br>Koordinator Bimbingan dan Konseling</p>
                    <p class="sibk-print-footer__name">Dra. Hj. Siti Aminah, M.Pd.</p>
                    <p class="sibk-print-footer__nip">NIP. 19720514 199802 2 001</p>
                </div>
                <div class="sibk-print-footer__col">
                    <p class="sibk-print-footer__role">Surabaya, 31 Agustus 2026<br>Guru Bimbingan dan Konseling</p>
                    <p class="sibk-print-footer__name">Ahmad Fauzi, S.Pd., Kons.</p>
                    <p class="sibk-print-footer__nip">NIP. 19850820 201101 1 008</p>
                </div>
            </div>
        </div>
    </div>
@endsection
