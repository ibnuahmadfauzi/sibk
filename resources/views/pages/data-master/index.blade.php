@extends('layouts.app-2')

@section('page-title', 'Data Master & Sinkronisasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy m-0">
                <h1 class="mb-1">Data Master dan Sinkronisasi</h1>
                <p class="mb-0">Pantau pembaruan data murid, kelas, dan data e-Tatib.</p>
            </div>
            <div class="sibk-page-header__actions">
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="btn-sync-all">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="sync-icon-spin">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                    </svg>
                    Perbarui Data
                </button>
            </div>
        </div>

        <!-- Sync Cards -->
        <div class="row g-4 mb-4">
            <!-- Card 1: Dapodik -->
            <div class="col-12 col-md-4">
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
                            <span class="sibk-stat-card__value" id="val-dapodik">1.248 murid</span>
                            <span class="sibk-stat-meta">Terakhir diperbarui 16 Agu 2026, 07:30</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Kelas -->
            <div class="col-12 col-md-4">
                <div class="sibk-stat-card border-0 h-100">
                    <div class="sibk-stat-card__inner">
                        <div class="sibk-stat-card__icon-col">
                            <div class="sibk-stat-card__icon sibk-icon-tone--info">
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
                            <span class="sibk-stat-meta">Tahun ajaran aktif</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: e-Tatib -->
            <div class="col-12 col-md-4">
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
                            <span class="sibk-stat-card__value" id="val-etatib">Aktif</span>
                            <span class="sibk-stat-meta">Terakhir diperbarui 16 Agu 2026, 07:45</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Status & Details -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__header flex-column align-items-start gap-2">
                <div class="d-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                    </svg>
                    <h2 class="m-0 fs-5 fw-bold text-dark">Status Sinkronisasi</h2>
                </div>
                <p class="text-muted small m-0">Data sumber tetap menjadi acuan utama dan riwayat pembaruan dapat ditelusuri.</p>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="sibk-badge sibk-badge--success">Dapodik • Berhasil</span>
                    <span class="sibk-badge sibk-badge--success">e-Tatib • Berhasil</span>
                    <span class="sibk-badge sibk-badge--warning">3 data perlu diperiksa</span>
                </div>
            </div>

            <!-- Table -->
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
            <div class="p-3 border-top d-flex justify-content-end">
                <a href="{{ route('students.index') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
