@extends('layouts.app-2')

@section('page-title', 'Pratinjau Laporan - ' . $reportTitle . ' - Ruang BK')

@section('body')
    <div class="sibk-dashboard sibk-report-preview-page">
        <!-- Page Header with Back Link -->
        <div class="sibk-page-header mb-4">
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
                <p>{{ $reportTitle }}</p>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="sibk-panel mb-4 sibk-filter-panel no-print">
            <div class="sibk-panel__body p-4">
                <h3 class="sibk-filter-title mb-3">Filter Laporan</h3>
                <form class="sibk-filter-form row g-3 align-items-end" action="{{ route('reports.preview') }}" method="GET">
                    <input type="hidden" name="type" value="{{ $type }}">
                    
                    <div class="col-12 col-md-3">
                        <label for="periode" class="form-label sibk-form-label">Periode</label>
                        <select class="form-select sibk-form-select" id="periode" name="periode">
                            <option selected value="1-31-agu-2026">1–31 Agustus 2026</option>
                            <option value="bulan-lalu">Juli 2026</option>
                            <option value="semester-1">Semester Ganjil 2026/2027</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="kelas" class="form-label sibk-form-label">Kelas</label>
                        <select class="form-select sibk-form-select" id="kelas" name="kelas">
                            <option selected value="">Semua kelas</option>
                            <option value="X RPL 1">X RPL 1</option>
                            <option value="X RPL 2">X RPL 2</option>
                            <option value="XI RPL 1">XI RPL 1</option>
                            <option value="XI RPL 2">XI RPL 2</option>
                            <option value="XII RPL 1">XII RPL 1</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label for="bidang" class="form-label sibk-form-label">Bidang Layanan</label>
                        <select class="form-select sibk-form-select" id="bidang" name="bidang">
                            <option selected value="">Semua bidang</option>
                            <option value="Pribadi">Pribadi</option>
                            <option value="Sosial">Sosial</option>
                            <option value="Belajar">Belajar</option>
                            <option value="Karier">Karier</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label for="status" class="form-label sibk-form-label">Status</label>
                        <select class="form-select sibk-form-select" id="status" name="status">
                            <option selected value="">Semua status</option>
                            <option value="Dalam Penanganan">Dalam Penanganan</option>
                            <option value="Baru">Baru</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100 sibk-btn-apply">
                            Terapkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary KPI Row -->
        <div class="row g-3 mb-4 sibk-report-kpi-row">
            <div class="col-12 col-md-4">
                <div class="sibk-stat-card sibk-tone--primary">
                    <div class="sibk-stat-card__inner">
                        <div class="sibk-stat-card__content-col">
                            <h4 class="sibk-stat-card__label">{{ $stats['total']['label'] }}</h4>
                            <div class="sibk-stat-card__value">{{ $stats['total']['value'] }}</div>
                            <span class="sibk-stat-meta">{{ $stats['total']['sub'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="sibk-stat-card sibk-tone--warning">
                    <div class="sibk-stat-card__inner">
                        <div class="sibk-stat-card__content-col">
                            <h4 class="sibk-stat-card__label">{{ $stats['active']['label'] }}</h4>
                            <div class="sibk-stat-card__value">{{ $stats['active']['value'] }}</div>
                            <span class="sibk-stat-meta">{{ $stats['active']['sub'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="sibk-stat-card sibk-tone--success">
                    <div class="sibk-stat-card__inner">
                        <div class="sibk-stat-card__content-col">
                            <h4 class="sibk-stat-card__label">{{ $stats['completed']['label'] }}</h4>
                            <div class="sibk-stat-card__value">{{ $stats['completed']['value'] }}</div>
                            <span class="sibk-stat-meta">{{ $stats['completed']['sub'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table Panel -->
        <div class="sibk-panel mb-4">
            <div class="table-responsive">
                <table class="table sibk-table mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Kelas</th>
                            <th>Bidang</th>
                            <th>Status</th>
                            <th>Tindak Lanjut</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="fw-bold text-primary">{{ $row['code'] }}</td>
                                <td class="fw-semibold text-dark">{{ $row['class'] }}</td>
                                <td>{{ $row['category'] }}</td>
                                <td>
                                    <span class="sibk-badge sibk-badge--{{ $row['status_tone'] }}">
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                                <td>{{ $row['follow_up'] }}</td>
                                <td class="text-muted">{{ $row['date'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Tidak ada data untuk filter yang dipilih.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="sibk-panel__footer p-4 border-top border-light d-flex justify-content-between align-items-center">
                <div class="text-muted small fw-medium">
                    Menampilkan 1–{{ count($rows) }} dari 42 data laporan
                </div>
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
                <span>Cetak</span>
            </button>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2" onclick="alert('Laporan berhasil diekspor!')">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <span>Ekspor</span>
            </button>
        </div>
    </div>
@endsection
