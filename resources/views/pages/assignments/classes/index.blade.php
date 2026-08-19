@extends('layouts.app-2')

@section('page-title', 'Penugasan Kelas - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy">
                <h1>Penugasan Kelas</h1>
                <p>Daftar penanggung jawab layanan BK per kelas.</p>
            </div>
            <div class="sibk-page-header__actions">
                <a href="{{ route('assignments.classes.manage') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M2 20c0-3.9 2.7-7 6-7s6 3.1 6 7M14 14c3.6 0 6 2.6 6 6"/>
                    </svg>
                    Atur Penugasan
                </a>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4">
                <h3 class="sibk-filter-title mb-3">Filter Penugasan</h3>
                <form class="sibk-filter-form row g-3 align-items-end" action="{{ route('assignments.classes.index') }}" method="GET">
                    <div class="col-12 col-md-3">
                        <label for="tahun_ajaran" class="form-label sibk-form-label">Tahun Ajaran</label>
                        <select class="form-select sibk-form-select" id="tahun_ajaran" name="tahun_ajaran">
                            <option selected value="2026/2027">2026/2027</option>
                            <option value="2025/2026">2025/2026</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="search_kelas" class="form-label sibk-form-label">Cari Kelas</label>
                        <input type="text" class="form-control sibk-form-control" id="search_kelas" name="search_kelas" placeholder="Nama kelas">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="status" class="form-label sibk-form-label">Status</label>
                        <select class="form-select sibk-form-select" id="status" name="status">
                            <option selected value="">Semua status</option>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100 sibk-btn-apply">
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
                            <th>Kelas</th>
                            <th>Penanggung Jawab</th>
                            <th>Mulai Berlaku</th>
                            <th>Akhir Berlaku</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                            <tr>
                                <td class="fw-bold text-dark">{{ $assignment['class'] }}</td>
                                <td class="fw-semibold text-primary">{{ $assignment['counselor'] }}</td>
                                <td>{{ $assignment['start_date'] }}</td>
                                <td class="text-muted">{{ $assignment['end_date'] }}</td>
                                <td>
                                    <span class="sibk-badge sibk-badge--{{ $assignment['status_tone'] }}">
                                        {{ $assignment['status'] }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('assignments.classes.manage', ['class' => $assignment['class']]) }}" class="fw-bold text-decoration-none text-primary">
                                        Atur
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada penugasan kelas yang dicatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="sibk-panel__footer p-4 border-top border-light">
                <div class="text-muted small fw-medium">
                    Menampilkan 1–{{ count($assignments) }} dari {{ count($assignments) }} penugasan kelas
                </div>
            </div>
        </div>
    </div>
@endsection
