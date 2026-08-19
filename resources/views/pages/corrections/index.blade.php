@extends('layouts.app-2')

@section('page-title', 'Koreksi Data - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Page Header -->
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Koreksi Data</h1>
                <p>Daftar pengajuan koreksi data operasional dan master.</p>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4">
                <h3 class="sibk-filter-title mb-3 d-flex align-items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    Filter Pengajuan
                </h3>
                <form class="sibk-filter-form row g-3 align-items-end" action="{{ route('corrections.index') }}" method="GET">
                    <div class="col-12 col-md-4">
                        <label for="search" class="form-label sibk-form-label">Cari</label>
                        <input type="text" class="form-control sibk-form-control" id="search" name="search" placeholder="Objek, atribut, atau pengaju">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="data_type" class="form-label sibk-form-label">Jenis Data</label>
                        <select class="form-select sibk-form-select" id="data_type" name="data_type">
                            <option selected value="">Semua jenis</option>
                            <option value="Operasional">Operasional</option>
                            <option value="Master">Master</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="status" class="form-label sibk-form-label">Status</label>
                        <select class="form-select sibk-form-select" id="status" name="status">
                            <option selected value="">Semua status</option>
                            <option value="Menunggu">Menunggu</option>
                            <option value="Diproses">Diproses</option>
                            <option value="Selesai">Selesai</option>
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
                            <th>Objek</th>
                            <th>Atribut</th>
                            <th>Pengaju</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($corrections as $item)
                            <tr>
                                <td class="fw-bold text-dark">{{ $item['object'] }}</td>
                                <td>{{ $item['attribute'] }}</td>
                                <td>{{ $item['requester'] }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $item['type'] }}</span>
                                </td>
                                <td>
                                    <span class="sibk-badge sibk-badge--{{ $item['status_tone'] }}">
                                        {{ $item['status'] }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $item['date'] }}</td>
                                <td>
                                    <a href="{{ route('corrections.show', ['id' => $item['id']]) }}" class="fw-bold text-decoration-none text-primary">
                                        {{ $item['action'] }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada pengajuan koreksi data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="sibk-panel__footer p-4 border-top border-light">
                <div class="text-muted small fw-medium">
                    Menampilkan 1–{{ count($corrections) }} dari {{ count($corrections) }} pengajuan
                </div>
            </div>
        </div>
    </div>
@endsection
