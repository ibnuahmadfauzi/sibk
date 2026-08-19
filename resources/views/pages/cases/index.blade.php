@extends('layouts.app-2')

@section('page-title', 'Daftar Kasus - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="sibk-page-header__copy">
                <h1>Daftar Kasus</h1>
                <p>Cari, filter, dan buka kasus layanan BK.</p>
            </div>
            <div class="sibk-page-header__actions">
                <a href="{{ route('cases.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Buat Kasus Baru
                </a>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4">
                <form class="sibk-filter-form row g-3 align-items-end" action="#" method="GET">
                    <div class="col-12 col-md-3">
                        <label for="search" class="form-label sibk-form-label">Cari kasus</label>
                        <input type="text" class="form-control sibk-form-control" id="search" placeholder="Nomor kasus atau nama murid">
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="kelas" class="form-label sibk-form-label">Kelas</label>
                        <select class="form-select sibk-form-select" id="kelas">
                            <option selected>Semua kelas</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="sumber" class="form-label sibk-form-label">Sumber</label>
                        <select class="form-select sibk-form-select" id="sumber">
                            <option selected>Semua sumber</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="status" class="form-label sibk-form-label">Status</label>
                        <select class="form-select sibk-form-select" id="status">
                            <option selected>Semua status</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="periode" class="form-label sibk-form-label">Periode</label>
                        <select class="form-select sibk-form-select" id="periode">
                            <option selected>Semua periode</option>
                        </select>
                    </div>
                </form>
                <div class="sibk-filter-footer mt-4 text-muted small fw-medium">
                    {{ count($cases) }} kasus ditampilkan
                </div>
            </div>
        </div>

        <!-- Table Panel -->
        <div class="sibk-panel">
            <div class="sibk-panel__header p-4 border-0">
                <h3 class="sibk-panel__title">Daftar Kasus</h3>
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
                                    <a href="{{ url('cases/show') }}" class="fw-bold text-decoration-none">Buka</a>
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

    </div>
@endsection
