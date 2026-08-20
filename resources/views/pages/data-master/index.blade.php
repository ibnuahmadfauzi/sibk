@extends('layouts.app-2')

@section('page-title', 'Data Master & Sinkronisasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        @if(session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
        @endif
        @error('sync')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror
        @error('etatib_sync')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror

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
                            @php
                                $dapodikStatus = match($lastDapodikRun?->status) {
                                    'succeeded' => 'Sinkron Aktif',
                                    'warning' => 'Perlu Diperiksa',
                                    'failed' => 'Sinkronisasi Gagal',
                                    'running' => 'Sedang Sinkronisasi',
                                    default => 'Belum Dikonfigurasi',
                                };
                            @endphp
                            <span class="sibk-stat-card__value fs-6 text-dark mt-1">{{ $dapodikStatus }}</span>
                            <span class="sibk-stat-meta text-muted small">
                                {{ $lastSuccessfulDapodikRun?->finished_at ? 'Data terakhir: '.$lastSuccessfulDapodikRun->finished_at->locale('id')->translatedFormat('d M Y, H.i') : 'Belum ada data hasil sinkronisasi' }}
                            </span>
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
                            @php
                                $etatibStatus = match($lastEtatibRun?->status) {
                                    'succeeded' => 'Sinkron Aktif',
                                    'warning' => 'Perlu Diperiksa',
                                    'failed' => 'Sinkronisasi Gagal',
                                    'running' => 'Sedang Sinkronisasi',
                                    default => 'Belum Dikonfigurasi',
                                };
                            @endphp
                            <span class="sibk-stat-card__value fs-6 text-dark mt-1">{{ $etatibStatus }}</span>
                            <span class="sibk-stat-meta text-muted small">{{ $lastSuccessfulEtatibRun?->finished_at ? 'Data terakhir: '.$lastSuccessfulEtatibRun->finished_at->locale('id')->translatedFormat('d M Y, H.i') : 'Belum ada data hasil sinkronisasi' }}</span>
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
                            <span class="sibk-stat-card__value" id="val-dapodik">{{ number_format($studentCount, 0, ',', '.') }}</span>
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
                            <span class="sibk-stat-card__value" id="val-kelas">{{ number_format($classroomCount, 0, ',', '.') }}</span>
                            <span class="sibk-stat-meta text-muted small">Rombel aktif{{ $activeYear ? ' TA '.$activeYear->name : '' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Button Row -->
        <div class="d-flex flex-wrap justify-content-end gap-2 mb-4">
            <form action="{{ route('data-master.etatib.sync') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-primary">Sinkronkan e-Tatib</button>
            </form>
            <form action="{{ route('data-master.dapodik.sync') }}" method="POST">
                @csrf
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2" id="btn-sync-all">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="sync-icon-spin">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                </svg>
                Perbarui Data
            </button>
            </form>
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
                            <p class="text-muted small mb-0">{{ $unresolvedIssueCount }} data belum cocok dan perlu ditinjau pada sumber resmi.</p>
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
                        @forelse($syncRuns as $run)
                            @php
                                [$statusLabel, $statusTone] = match($run->status) {
                                    'succeeded' => ['Berhasil', 'success'],
                                    'warning' => ['Perlu diperiksa', 'warning'],
                                    'failed' => ['Gagal', 'danger'],
                                    'running' => ['Berjalan', 'info'],
                                    default => ['Tidak diketahui', 'neutral'],
                                };
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $run->started_at->locale('id')->translatedFormat('d M H:i') }}</td>
                                <td>{{ $run->source === 'dapodik' ? 'Dapodik' : ($run->source === 'etatib' ? 'e-Tatib' : $run->source) }}</td>
                                <td>{{ $run->source === 'dapodik' ? 'Murid dan kelas' : ($run->source === 'etatib' ? 'Pelanggaran murid' : 'Data eksternal') }}</td>
                                <td><span class="sibk-badge sibk-badge--{{ $statusTone }}">{{ $statusLabel }}</span></td>
                                <td class="text-muted">{{ $run->summary }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat sinkronisasi.</td>
                            </tr>
                        @endforelse
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
