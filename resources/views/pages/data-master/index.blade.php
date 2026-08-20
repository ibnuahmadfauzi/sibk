@extends('layouts.app-2')

@section('page-title', 'Data Master & Sinkronisasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy m-0">
                <h1 class="mb-1">Data Master dan Sinkronisasi</h1>
                <p class="mb-0">Pantau pembaruan data murid, kelas, dan data e-Tatib.</p>
            </div>
        </div>

        <!-- Sync 4 Cards (Penpot PG-501) -->
        <div class="row g-4 mb-4">
            <!-- Card 1: Dapodik -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="sibk-stat-card border-0 h-100">
                    <div class="sibk-stat-card__inner">
                        <div class="sibk-stat-card__icon-col">
                            <div class="sibk-stat-card__icon sibk-icon-tone--primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="sibk-stat-card__content-col">
                            <span class="sibk-stat-card__label">Dapodik</span>
                            <span class="sibk-stat-card__value fs-6 text-dark mt-1">Sinkron Aktif</span>
                            <span class="sibk-stat-meta text-muted small">Terakhir: 16 Agu 2026, 07.30</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: e-Tatib -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="sibk-stat-card border-0 h-100">
                    <div class="sibk-stat-card__inner">
                        <div class="sibk-stat-card__icon-col">
                            <div class="sibk-stat-card__icon sibk-icon-tone--success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                                    <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="sibk-stat-card__content-col">
                            <span class="sibk-stat-card__label">e-Tatib</span>
                            <span class="sibk-stat-card__value fs-6 text-dark mt-1">Sinkron Aktif</span>
                            <span class="sibk-stat-meta text-muted small">Terakhir: 16 Agu 2026, 07.45</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Data Murid -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="sibk-stat-card border-0 h-100">
                    <div class="sibk-stat-card__inner">
                        <div class="sibk-stat-card__icon-col">
                            <div class="sibk-stat-card__icon sibk-icon-tone--info">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                        </div>
                        <div class="sibk-stat-card__content-col">
                            <span class="sibk-stat-card__label">Data Murid</span>
                            <span class="sibk-stat-card__value" id="val-dapodik">1.248</span>
                            <span class="sibk-stat-meta text-muted small">Total murid terdaftar</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Kelas -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="sibk-stat-card border-0 h-100">
                    <div class="sibk-stat-card__inner">
                        <div class="sibk-stat-card__icon-col">
                            <div class="sibk-stat-card__icon sibk-icon-tone--warning">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                        </div>
                        <div class="sibk-stat-card__content-col">
                            <span class="sibk-stat-card__label">Kelas</span>
                            <span class="sibk-stat-card__value" id="val-kelas">36</span>
                            <span class="sibk-stat-meta text-muted small">Rombel aktif TA 2026/2027</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Button Row -->
        <div class="d-flex justify-content-end mb-4">
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="btn-sync-all">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="sync-icon-spin">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                </svg>
                Perbarui Data
            </button>
        </div>

        <!-- Data yang Perlu Diperiksa Box (Penpot PG-501) -->
        <div class="sibk-panel mb-4 border-0">
            <div class="sibk-panel__body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="text-warning mt-1">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="fs-6 fw-bold text-dark mb-1">Data yang Perlu Diperiksa</h2>
                            <p class="text-muted small mb-0">3 data belum cocok dan perlu ditinjau sebelum pembaruan berikutnya.</p>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('students.index') }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2 px-3 py-2">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path>
                            </svg>
                            Lihat Data
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Log Table Card -->
        <div class="sibk-panel border-0">
            <div class="table-responsive">
                <table class="table sibk-table mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Sumber</th>
                            <th>Data</th>
                            <th>Hasil</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold">16 Agu 07:45</td>
                            <td>e-Tatib</td>
                            <td>Pelanggaran dan poin</td>
                            <td><span class="sibk-badge sibk-badge--success">Berhasil</span></td>
                            <td class="text-muted">Data terbaru tersedia</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">16 Agu 07:30</td>
                            <td>Dapodik</td>
                            <td>Murid dan kelas</td>
                            <td><span class="sibk-badge sibk-badge--success">Berhasil</span></td>
                            <td class="text-muted">Data terbaru tersedia</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">15 Agu 07:45</td>
                            <td>e-Tatib</td>
                            <td>Pelanggaran dan poin</td>
                            <td><span class="sibk-badge sibk-badge--warning">Perlu diperiksa</span></td>
                            <td class="text-muted">3 data belum cocok</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">15 Agu 07:30</td>
                            <td>Dapodik</td>
                            <td>Murid dan kelas</td>
                            <td><span class="sibk-badge sibk-badge--success">Berhasil</span></td>
                            <td class="text-muted">Sinkronisasi selesai</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Panel Footer Action -->
            <div class="p-3 border-top d-flex justify-content-end bg-light rounded-bottom">
                <a href="{{ route('students.index') }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2 px-3 py-2">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path>
                    </svg>
                    Lihat Data
                </a>
            </div>
        </div>
    </div>
@endsection

@section('extra-javascript')
    <script>
        document.getElementById('btn-sync-all').addEventListener('click', function() {
            const btn = this;
            const icon = btn.querySelector('.sync-icon-spin');
            
            // Add spinning animation class
            icon.style.animation = 'spin 1s linear infinite';
            btn.disabled = true;
            btn.innerHTML = `
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1.2s linear infinite">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                </svg>
                Sinkronisasi...
            `;

            // Inject spin styling if not present
            if (!document.getElementById('spin-style')) {
                const style = document.createElement('style');
                style.id = 'spin-style';
                style.innerHTML = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
                document.head.appendChild(style);
            }

            // Simulate sync
            setTimeout(() => {
                btn.disabled = false;
                btn.className = "btn btn-success d-inline-flex align-items-center gap-2";
                btn.innerHTML = `
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Selesai
                `;
                
                // Show floating success toast or notification
                alert('Sinkronisasi data master Dapodik dan e-Tatib berhasil diselesaikan.');
                
                // Reset button after 3 seconds
                setTimeout(() => {
                    btn.className = "btn btn-primary d-inline-flex align-items-center gap-2";
                    btn.innerHTML = `
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                        </svg>
                        Perbarui Data
                    `;
                }, 3000);
            }, 2500);
        });
    </script>
@endsection
