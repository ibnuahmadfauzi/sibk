@extends('layouts.app-2')

@section('page-title', 'Penugasan Kelas - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-401">
        @if(session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif

        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy">
                <h1>Penugasan Kelas</h1>
                <p>Daftar penanggung jawab layanan BK per kelas.</p>
            </div>
            @if($canManage)
            <div class="sibk-page-header__actions">
                <a href="{{ route('assignments.classes.manage') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M2 20c0-3.9 2.7-7 6-7s6 3.1 6 7M14 14c3.6 0 6 2.6 6 6"/>
                    </svg>
                    Atur Penugasan
                </a>
            </div>
            @endif
        </div>

        <!-- Filter Panel -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4">
                <h3 class="sibk-filter-title mb-3 d-flex align-items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    Filter Penugasan
                </h3>
                <form class="sibk-filter-form row g-3 align-items-end" action="{{ route('assignments.classes.index') }}" method="GET">
                    <div class="col-12 col-md-3">
                        <label for="tahun_ajaran" class="form-label sibk-form-label">Tahun Ajaran</label>
                        <select class="form-select sibk-form-select" id="tahun_ajaran" name="academic_year_id">
                            <option value="">Semua tahun ajaran</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" @selected((string) request('academic_year_id') === (string) $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="search_kelas" class="form-label sibk-form-label">Cari Kelas</label>
                        <input type="text" class="form-control sibk-form-control" id="search_kelas" name="search_kelas" value="{{ request('search_kelas') }}" placeholder="Nama kelas">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="status" class="form-label sibk-form-label">Status</label>
                        <select class="form-select sibk-form-select" id="status" name="status">
                            <option value="">Semua status</option>
                            <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                            <option value="nonaktif" @selected(request('status') === 'nonaktif')>Tidak Aktif</option>
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
                            @php($isActive = $assignment->effective_from->lte(now()) && ($assignment->effective_until === null || $assignment->effective_until->gte(now())))
                            <tr>
                                <td class="fw-bold text-dark">{{ $assignment->classroom->name }}</td>
                                <td class="fw-semibold text-primary">{{ $assignment->teacher->name }}</td>
                                <td>{{ $assignment->effective_from->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="text-muted">{{ $assignment->effective_until?->locale('id')->translatedFormat('d M Y') ?? '—' }}</td>
                                <td>
                                    <span class="sibk-badge sibk-badge--{{ $isActive ? 'success' : 'neutral' }}">
                                        {{ $isActive ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
                                </td>
                                <td>
                                    @if($canManage)
                                        <a href="{{ route('assignments.classes.manage', ['classroom_id' => $assignment->classroom_id, 'academic_year_id' => $assignment->academic_year_id]) }}" class="fw-bold text-decoration-none text-primary">
                                            Atur
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
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
                    Menampilkan {{ $assignments->isEmpty() ? 0 : 1 }}–{{ $assignments->count() }} dari {{ $assignments->count() }} penugasan kelas
                </div>
            </div>
        </div>
    </div>
@endsection
