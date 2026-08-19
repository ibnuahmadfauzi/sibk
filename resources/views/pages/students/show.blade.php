@extends('layouts.app-2')

@section('page-title', 'Profil Murid - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Page Header -->
        <div class="sibk-page-header mb-4">
            <div class="d-flex align-items-center gap-2 mb-2">
                <a href="{{ route('students.index') }}" class="sibk-back-link d-inline-flex align-items-center gap-1 text-decoration-none text-muted small fw-semibold">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Daftar Murid
                </a>
            </div>
            <div class="sibk-page-header__copy">
                <h1>Profil Murid</h1>
                <p>Riwayat layanan dan informasi terkait murid.</p>
            </div>
        </div>

        <!-- Student Identity Panel -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__header p-4 border-0 pb-0">
                <h3 class="sibk-panel__title d-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Identitas Murid
                </h3>
            </div>
            <div class="sibk-panel__body p-4 pt-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-primary flex-shrink-0" style="width: 52px; height: 52px; background-color: #e5effb; font-size: 1.15rem;">
                        {{ $student['initials'] ?? 'MA' }}
                    </div>
                    <div>
                        <h2 class="fs-4 fw-bold text-dark mb-1">{{ $student['name'] ?? 'Murid A' }}</h2>
                        <div class="text-muted small">
                            NISN {{ $student['nisn'] ?? '0012345678' }} • {{ $student['class'] ?? 'X RPL 1' }} • Tahun Ajaran {{ $student['academic_year'] ?? '2026/2027' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="sibk-panel mb-4 p-2">
            <ul class="nav nav-pills gap-1">
                <li class="nav-item">
                    <a class="nav-link {{ ($activeTab ?? 'ringkasan') === 'ringkasan' ? 'active' : '' }}" href="{{ route('students.show', ['tab' => 'ringkasan', 'nisn' => $student['nisn'] ?? '']) }}">
                        Ringkasan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($activeTab ?? '') === 'kasus' ? 'active' : '' }}" href="{{ route('students.show', ['tab' => 'kasus', 'nisn' => $student['nisn'] ?? '']) }}">
                        Kasus dan Layanan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($activeTab ?? '') === 'etatib' ? 'active' : '' }}" href="{{ route('students.show', ['tab' => 'etatib', 'nisn' => $student['nisn'] ?? '']) }}">
                        Data e-Tatib
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($activeTab ?? '') === 'konsultasi' ? 'active' : '' }}" href="{{ route('students.show', ['tab' => 'konsultasi', 'nisn' => $student['nisn'] ?? '']) }}">
                        Konsultasi dan Tindak Lanjut
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($activeTab ?? '') === 'prestasi' ? 'active' : '' }}" href="{{ route('students.show', ['tab' => 'prestasi', 'nisn' => $student['nisn'] ?? '']) }}">
                        Prestasi
                    </a>
                </li>
            </ul>
        </div>

        <!-- KPI Summary Cards (4 Columns) -->
        <div class="row g-3 mb-4">
            <!-- 1. Kasus Aktif -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sibk-stat-card p-3 rounded-3 border bg-white h-100 d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: {{ $stats['active_cases']['badge_bg'] ?? '#e9f2fb' }}; color: {{ $stats['active_cases']['icon_color'] ?? '#2f6fc6' }};">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">{{ $stats['active_cases']['label'] ?? 'Kasus Aktif' }}</div>
                        <div class="fs-4 fw-bold text-dark">{{ $stats['active_cases']['value'] ?? '1' }}</div>
                        <div class="text-muted small" style="font-size: 0.75rem;">{{ $stats['active_cases']['sub'] ?? 'Dalam penanganan' }}</div>
                    </div>
                </div>
            </div>

            <!-- 2. Poin e-Tatib -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sibk-stat-card p-3 rounded-3 border bg-white h-100 d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: {{ $stats['points']['badge_bg'] ?? '#fdf3e7' }}; color: {{ $stats['points']['icon_color'] ?? '#d97706' }};">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">{{ $stats['points']['label'] ?? 'Poin e-Tatib' }}</div>
                        <div class="fs-4 fw-bold text-dark">{{ $stats['points']['value'] ?? '15' }}</div>
                        <div class="text-muted small" style="font-size: 0.75rem;">{{ $stats['points']['sub'] ?? 'Data terkait' }}</div>
                    </div>
                </div>
            </div>

            <!-- 3. Tindak Lanjut -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sibk-stat-card p-3 rounded-3 border bg-white h-100 d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: {{ $stats['follow_ups']['badge_bg'] ?? '#e3f2fd' }}; color: {{ $stats['follow_ups']['icon_color'] ?? '#0284c7' }};">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">{{ $stats['follow_ups']['label'] ?? 'Tindak Lanjut' }}</div>
                        <div class="fs-4 fw-bold text-dark">{{ $stats['follow_ups']['value'] ?? '2' }}</div>
                        <div class="text-muted small" style="font-size: 0.75rem;">{{ $stats['follow_ups']['sub'] ?? 'Terjadwal' }}</div>
                    </div>
                </div>
            </div>

            <!-- 4. Prestasi -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sibk-stat-card p-3 rounded-3 border bg-white h-100 d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: {{ $stats['achievements']['badge_bg'] ?? '#eeeafd' }}; color: {{ $stats['achievements']['icon_color'] ?? '#6657c8' }};">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 4h8v4.5a4 4 0 0 1-8 0V4z"/>
                            <path d="M8 5.5H5a1.5 1.5 0 0 0 1.5 2.5H8M16 5.5h3a1.5 1.5 0 0 1-1.5 2.5H16M12 12.5v4.5M8.5 19.5h7"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">{{ $stats['achievements']['label'] ?? 'Prestasi' }}</div>
                        <div class="fs-4 fw-bold text-dark">{{ $stats['achievements']['value'] ?? '3' }}</div>
                        <div class="text-muted small" style="font-size: 0.75rem;">{{ $stats['achievements']['sub'] ?? 'Riwayat tercatat' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Layout: Ringkasan Operasional & Aktivitas Terbaru -->
        <div class="row g-4">
            <!-- Left Column: Ringkasan Operasional -->
            <div class="col-12 col-lg-7">
                <div class="sibk-panel h-100">
                    <div class="sibk-panel__header p-4 border-0 pb-0">
                        <h3 class="sibk-panel__title d-flex align-items-center gap-2">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                            Ringkasan Operasional
                        </h3>
                        <p class="sibk-panel__subtitle text-muted small">Informasi yang paling relevan dari layanan murid.</p>
                    </div>
                    <div class="sibk-panel__body p-4 pt-2">
                        <div class="d-flex flex-column gap-3">
                            <div class="p-3 rounded-3 border bg-light">
                                <div class="text-muted small fw-semibold mb-1">Kasus terakhir</div>
                                <div class="fs-6 fw-bold text-dark">{{ $operationalSummary['latest_case'] ?? 'K-014 • Dalam Penanganan' }}</div>
                            </div>
                            <div class="p-3 rounded-3 border bg-light">
                                <div class="text-muted small fw-semibold mb-1">Tindak lanjut berikutnya</div>
                                <div class="fs-6 fw-bold text-dark">{{ $operationalSummary['next_follow_up'] ?? '18 Agustus 2026' }}</div>
                            </div>
                            <div class="p-3 rounded-3 border bg-light">
                                <div class="text-muted small fw-semibold mb-1">Bidang layanan</div>
                                <div class="fs-6 fw-bold text-dark">{{ $operationalSummary['service_field'] ?? 'Pribadi' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Aktivitas Terbaru -->
            <div class="col-12 col-lg-5">
                <div class="sibk-panel h-100">
                    <div class="sibk-panel__header p-4 border-0 pb-0">
                        <h3 class="sibk-panel__title d-flex align-items-center gap-2">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 14 10"/>
                            </svg>
                            Aktivitas Terbaru
                        </h3>
                        <p class="sibk-panel__subtitle text-muted small">Urutan perubahan yang dapat dibaca pengguna.</p>
                    </div>
                    <div class="sibk-panel__body p-4 pt-2">
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                            @forelse($recentActivities ?? [] as $activity)
                                <li class="d-flex align-items-start gap-2 p-2 rounded hover-bg-light">
                                    <span class="text-primary mt-1">•</span>
                                    <div>
                                        <span class="fw-bold text-dark">{{ $activity['date'] ?? '-' }}</span>
                                        <span class="text-muted"> • {{ $activity['activity'] ?? '-' }}</span>
                                    </div>
                                </li>
                            @empty
                                <li class="text-muted small py-3 text-center">Belum ada aktivitas tercatat.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
