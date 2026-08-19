@extends('layouts.app-2')

@section('page-title', 'Daftar Murid - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Page Header -->
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Daftar Murid</h1>
                <p>Cari murid dan buka profil layanan.</p>
            </div>
        </div>

        <!-- Filter Panel: Pencarian Murid -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__header p-4 border-0 pb-0">
                <h3 class="sibk-panel__title d-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    Pencarian Murid
                </h3>
            </div>
            <div class="sibk-panel__body p-4 pt-2">
                <form class="row g-3 align-items-end" action="{{ route('students.index') }}" method="GET">
                    <div class="col-12 col-md-6 col-lg-7">
                        <label for="search" class="form-label sibk-form-label">Cari murid</label>
                        <div class="sibk-input-search-wrapper position-relative">
                            <svg class="position-absolute top-50 translate-middle-y ms-3 text-muted" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            <input type="text" name="search" class="form-control sibk-form-control ps-5" id="search" placeholder="Nama atau NISN" value="{{ request()->query('search') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-3 col-lg-3">
                        <label for="kelas" class="form-label sibk-form-label">Kelas</label>
                        <select class="form-select sibk-form-select" id="kelas" name="class">
                            @foreach($classes ?? ['Semua kelas', 'X RPL 1', 'X RPL 2', 'XI RPL 1', 'XI RPL 2', 'XII RPL 1'] as $cls)
                                <option value="{{ $cls }}" {{ request()->query('class') === $cls ? 'selected' : '' }}>{{ $cls }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-lg-2">
                        <button type="submit" class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-2">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Panel: Daftar Murid -->
        <div class="sibk-panel">
            <div class="table-responsive">
                <table class="table sibk-table mb-0">
                    <thead>
                        <tr>
                            <th>NISN</th>
                            <th>Nama Murid</th>
                            <th>Kelas</th>
                            <th>Kasus Aktif</th>
                            <th>Tindak Lanjut</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students ?? [] as $student)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $student['nisn'] ?? '-' }}</td>
                                <td class="fw-medium text-dark">{{ $student['name'] ?? '-' }}</td>
                                <td>{{ $student['class'] ?? '-' }}</td>
                                <td>
                                    @php
                                        $activeCount = (int) ($student['active_cases'] ?? 0);
                                    @endphp
                                    <span class="fw-semibold {{ $activeCount > 0 ? 'text-primary' : 'text-muted' }}">
                                        {{ $activeCount }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $student['follow_up'] ?? 'Belum ada' }}</td>
                                <td>
                                    <a href="{{ route('students.show', ['nisn' => $student['nisn'] ?? '']) }}" class="fw-bold text-decoration-none text-primary">
                                        Buka
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Tidak ada data murid yang sesuai.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="sibk-panel__footer p-4 border-top border-light">
                <div class="text-muted small fw-medium">
                    Menampilkan {{ count($students ?? []) }} murid
                </div>
            </div>
        </div>
    </div>
@endsection
