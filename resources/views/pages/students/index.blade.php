@extends('layouts.app-2')

@section('page-title', 'Data Murid - Ruang BK')

@php
    // Dummy data for static UI
    $students = [
        ['nisn' => '0012345678', 'name' => 'Ahmad Fauzi', 'class' => 'X RPL 1', 'gender' => 'L', 'status' => 'Aktif'],
        ['nisn' => '0012345679', 'name' => 'Budi Santoso', 'class' => 'X RPL 1', 'gender' => 'L', 'status' => 'Aktif'],
        ['nisn' => '0012345680', 'name' => 'Citra Lestari', 'class' => 'X RPL 2', 'gender' => 'P', 'status' => 'Aktif'],
        ['nisn' => '0012345681', 'name' => 'Dewi Anggraini', 'class' => 'XI TKJ 1', 'gender' => 'P', 'status' => 'Aktif'],
        ['nisn' => '0012345682', 'name' => 'Eko Prasetyo', 'class' => 'XI TKJ 2', 'gender' => 'L', 'status' => 'Pindah'],
    ];
@endphp

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="sibk-page-header__copy">
                <h1>Data Murid</h1>
                <p>Cari dan lihat profil serta riwayat layanan BK murid.</p>
            </div>
            <div class="sibk-page-header__actions">
                <!-- Data master biasanya disinkronisasi dari Dapodik, tombol ini mungkin sekadar opsi manual -->
                <a href="#" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    Sinkronisasi Data
                </a>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4">
                <form class="sibk-filter-form row g-3 align-items-end" action="#" method="GET">
                    <div class="col-12 col-md-4">
                        <label for="search" class="form-label sibk-form-label">Cari murid</label>
                        <input type="text" class="form-control sibk-form-control" id="search" placeholder="Nama atau NISN">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="kelas" class="form-label sibk-form-label">Kelas</label>
                        <select class="form-select sibk-form-select" id="kelas">
                            <option selected>Semua kelas</option>
                            <option value="10">Kelas 10</option>
                            <option value="11">Kelas 11</option>
                            <option value="12">Kelas 12</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="status" class="form-label sibk-form-label">Status</label>
                        <select class="form-select sibk-form-select" id="status">
                            <option selected>Semua status</option>
                            <option value="aktif">Aktif</option>
                            <option value="lulus">Lulus</option>
                            <option value="pindah">Pindah</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="button" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
                <div class="sibk-filter-footer mt-4 text-muted small fw-medium">
                    {{ count($students) }} murid ditampilkan
                </div>
            </div>
        </div>

        <!-- Table Panel -->
        <div class="sibk-panel">
            <div class="sibk-panel__header p-4 border-0">
                <h3 class="sibk-panel__title">Daftar Murid</h3>
                <p class="sibk-panel__subtitle">Pilih murid untuk melihat profil detail dan riwayat kasus/bimbingan.</p>
            </div>
            
            <div class="table-responsive">
                <table class="table sibk-table mb-0">
                    <thead>
                        <tr>
                            <th>NISN</th>
                            <th>Nama Murid</th>
                            <th>Kelas</th>
                            <th>L/P</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td class="fw-medium text-secondary">{{ $student['nisn'] }}</td>
                                <td class="fw-bold text-dark">{{ $student['name'] }}</td>
                                <td>{{ $student['class'] }}</td>
                                <td>{{ $student['gender'] }}</td>
                                <td>
                                    @php
                                        $badgeTone = 'primary';
                                        if ($student['status'] === 'Pindah') $badgeTone = 'warning';
                                        elseif ($student['status'] === 'Lulus') $badgeTone = 'success';
                                    @endphp
                                    <span class="badge bg-{{ $badgeTone }} bg-opacity-10 text-{{ $badgeTone }}">{{ $student['status'] }}</span>
                                </td>
                                <td>
                                    <a href="{{ url('students/show') }}" class="fw-bold text-decoration-none">Lihat Profil</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Data murid tidak ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="sibk-panel__footer p-4 border-top border-light">
                <div class="text-muted small fw-medium">
                    Menampilkan 1-{{ count($students) }} dari 1.250 murid (Simulasi)
                </div>
            </div>
        </div>

    </div>
@endsection
