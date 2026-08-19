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
                <h3 class="sibk-filter-title mb-3 d-flex align-items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    Filter Laporan
                </h3>
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
        </div>        <!-- Summary KPI Row -->
        <div class="row g-3 mb-4 sibk-report-kpi-row">
            <div class="col-12 col-md-4">
                <div class="sibk-stat-card sibk-tone--primary p-3 bg-white rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="sibk-stat-card__label text-muted small fw-semibold mb-1">{{ $stats['total']['label'] }}</h4>
                            <div class="sibk-stat-card__value fs-2 fw-bold text-dark">{{ $stats['total']['value'] }}</div>
                            <span class="sibk-stat-meta text-muted small">{{ $stats['total']['sub'] }}</span>
                        </div>
                        <div class="sibk-stat-card__icon p-3 rounded-circle" style="background-color: #e9f2fb; color: #2f6fc6;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="sibk-stat-card sibk-tone--warning p-3 bg-white rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="sibk-stat-card__label text-muted small fw-semibold mb-1">{{ $stats['active']['label'] }}</h4>
                            <div class="sibk-stat-card__value fs-2 fw-bold text-dark">{{ $stats['active']['value'] }}</div>
                            <span class="sibk-stat-meta text-muted small">{{ $stats['active']['sub'] }}</span>
                        </div>
                        <div class="sibk-stat-card__icon p-3 rounded-circle" style="background-color: #fbece3; color: #cf6a2d;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><circle cx="12" cy="14" r="3"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="sibk-stat-card sibk-tone--success p-3 bg-white rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="sibk-stat-card__label text-muted small fw-semibold mb-1">{{ $stats['completed']['label'] }}</h4>
                            <div class="sibk-stat-card__value fs-2 fw-bold text-dark">{{ $stats['completed']['value'] }}</div>
                            <span class="sibk-stat-meta text-muted small">{{ $stats['completed']['sub'] }}</span>
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

        <!-- Data Table Panel -->
        <div class="sibk-panel mb-4">
            <div class="table-responsive">
                <table class="table sibk-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Kode</th>
                            <th scope="col">Kelas</th>
                            <th scope="col">Bidang</th>
                            <th scope="col">Status</th>
                            <th scope="col">Tindak Lanjut</th>
                            <th scope="col">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows ?? $tableRows ?? [] as $row)
                            <tr>
                                <td class="fw-semibold text-primary">{{ $row['code'] ?? '-' }}</td>
                                <td class="fw-medium text-dark">{{ $row['class'] ?? '-' }}</td>
                                <td>{{ $row['field'] ?? $row['category'] ?? '-' }}</td>
                                <td>
                                    @php
                                        $status = $row['status'] ?? 'Baru';
                                        $badgeClass = match($status) {
                                            'Dalam Penanganan' => 'sibk-badge--warning',
                                            'Baru' => 'sibk-badge--primary',
                                            'Selesai' => 'sibk-badge--success',
                                            default => 'sibk-badge--neutral'
                                        };
                                    @endphp
                                    <span class="sibk-badge {{ $badgeClass }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $row['follow_up'] ?? '-' }}</td>
                                <td class="text-muted">{{ $row['date'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
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
