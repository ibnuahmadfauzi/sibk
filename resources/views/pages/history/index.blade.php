@extends('layouts.app-2')

@section('page-title', 'Riwayat Perubahan - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Page Header -->
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Riwayat Perubahan</h1>
                <p>Telusuri perubahan penting pada data dan layanan.</p>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4">
                <h3 class="sibk-filter-title mb-3 d-flex align-items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    Filter Riwayat
                </h3>
                <form class="sibk-filter-form row g-3 align-items-end" action="{{ route('history.index') }}" method="GET">
                    <div class="col-12 col-md-4">
                        <label for="search" class="form-label sibk-form-label">Cari</label>
                        <input type="text" class="form-control sibk-form-control" id="search" name="search" placeholder="Objek atau pengguna">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="action_type" class="form-label sibk-form-label">Jenis Perubahan</label>
                        <select class="form-select sibk-form-select" id="action_type" name="action_type">
                            <option selected value="">Semua jenis</option>
                            <option value="Ubah status">Ubah status</option>
                            <option value="Ubah penugasan">Ubah penugasan</option>
                            <option value="Verifikasi">Verifikasi</option>
                            <option value="Buat kasus">Buat kasus</option>
                            <option value="Tindak lanjut">Tindak lanjut</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="periode" class="form-label sibk-form-label">Periode</label>
                        <select class="form-select sibk-form-select" id="periode" name="periode">
                            <option selected value="">Semua periode</option>
                            <option value="today">Hari ini</option>
                            <option value="week">7 hari terakhir</option>
                            <option value="month">30 hari terakhir</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100 sibk-btn-apply d-inline-flex align-items-center justify-content-center gap-1">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                            </svg>
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Panel -->
        <div class="sibk-panel">
            <div class="table-responsive">
                <table class="table sibk-table mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Objek</th>
                            <th>Tindakan</th>
                            <th>Ringkasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historyLogs as $log)
                            <tr>
                                <td class="text-muted fw-medium">{{ $log['time'] }}</td>
                                <td class="fw-bold text-dark">{{ $log['user'] }}</td>
                                <td class="fw-semibold text-primary">{{ $log['object'] }}</td>
                                <td>
                                    <span class="sibk-badge sibk-badge--{{ $log['action_tone'] }}">
                                        {{ $log['action'] }}
                                    </span>
                                </td>
                                <td class="text-dark">{{ $log['summary'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat perubahan yang dicatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="sibk-panel__footer p-4 border-top border-light">
                <div class="text-muted small fw-medium">
                    Menampilkan 1–{{ count($historyLogs) }} dari {{ count($historyLogs) }} catatan riwayat
                </div>
            </div>
        </div>
    </div>
@endsection
