@extends('layouts.app-2')

@section('page-title', 'Layanan BK - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        @if(session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif

        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy">
                <h1>Layanan BK</h1>
                <p>Cari, filter, dan kelola penanganan kasus serta sesi bimbingan konseling.</p>
            </div>
            @if(($activeTab ?? 'kasus') === 'konsultasi')
                <a href="{{ route('consultations.create') }}" class="btn btn-primary">Catat Konsultasi</a>
            @elseif($canCreateCase)
                <a href="{{ route('cases.create') }}" class="btn btn-primary">Buat Kasus Baru</a>
            @endif
        </div>

        <div class="sibk-panel mb-4 p-2">
            <ul class="nav nav-pills gap-1">
                <li class="nav-item"><a class="nav-link {{ $activeTab === 'kasus' ? 'active' : '' }}" href="{{ route('cases.index', ['tab' => 'kasus']) }}">Kasus & Penanganan</a></li>
                <li class="nav-item"><a class="nav-link {{ $activeTab === 'konsultasi' ? 'active' : '' }}" href="{{ route('cases.index', ['tab' => 'konsultasi']) }}">Sesi Bimbingan & Konsultasi</a></li>
            </ul>
        </div>

        @if($activeTab === 'kasus')
            <div class="sibk-panel mb-4">
                <div class="sibk-panel__body p-4">
                    <form class="sibk-filter-form row g-3 align-items-end" action="{{ route('cases.index') }}" method="GET">
                        <input type="hidden" name="tab" value="kasus">
                        <div class="col-12 col-md-3">
                            <label for="search_kasus" class="form-label sibk-form-label">Cari kasus</label>
                            <input type="text" class="form-control sibk-form-control" id="search_kasus" name="search" value="{{ request('search') }}" placeholder="Nomor kasus atau nama murid">
                        </div>
                        <div class="col-12 col-md-2">
                            <label for="kelas_kasus" class="form-label sibk-form-label">Kelas</label>
                            <select class="form-select sibk-form-select" id="kelas_kasus" name="classroom_id">
                                <option value="">Semua kelas</option>
                                @foreach($classrooms as $classroom)
                                    <option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label for="sumber_kasus" class="form-label sibk-form-label">Sumber</label>
                            <select class="form-select sibk-form-select" id="sumber_kasus" name="case_source_id">
                                <option value="">Semua sumber</option>
                                @foreach($caseSources as $source)
                                    <option value="{{ $source->id }}" @selected((string) request('case_source_id') === (string) $source->id)>{{ $source->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label for="status_kasus" class="form-label sibk-form-label">Status</label>
                            <select class="form-select sibk-form-select" id="status_kasus" name="status_id">
                                <option value="">Semua status</option>
                                @foreach($caseStatuses as $status)
                                    <option value="{{ $status->id }}" @selected((string) request('status_id') === (string) $status->id)>{{ $status->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label for="periode_kasus" class="form-label sibk-form-label">Periode</label>
                            <input type="month" class="form-control sibk-form-control" id="periode_kasus" name="month" value="{{ request('month') }}">
                        </div>
                        <div class="col-12 col-md-1"><button class="btn btn-outline-primary w-100" type="submit">Filter</button></div>
                    </form>
                    <div class="sibk-filter-footer mt-4 text-muted small fw-medium">{{ $cases->total() }} kasus ditemukan</div>
                </div>
            </div>

            <div class="sibk-panel">
                <div class="sibk-panel__header p-4 border-0">
                    <h3 class="sibk-panel__title">Daftar Kasus Aktif & Penanganan</h3>
                    <p class="sibk-panel__subtitle">Kasus layanan BK sesuai scope dan filter Anda.</p>
                </div>
                <div class="table-responsive">
                    <table class="table sibk-table mb-0">
                        <thead><tr><th>No. Kasus</th><th>Murid</th><th>Kelas</th><th>Sumber</th><th>Bidang</th><th>Status</th><th>Tindak lanjut</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @forelse($cases as $case)
                                @php
                                    $membership = $case->student?->classMemberships->sortByDesc('effective_from')->first();
                                    $nextFollowUp = $case->followUps->first(fn ($followUp) => $followUp->planned_date->gte(today()) && $followUp->status?->code !== 'dibatalkan');
                                    $tone = match($case->status->code) { 'selesai' => 'success', 'baru' => 'info', default => 'primary' };
                                @endphp
                                <tr>
                                    <td class="fw-bold text-primary">{{ $case->registration_number }}</td>
                                    <td class="fw-bold text-dark">{{ $case->identityName() }} @if($case->temporary_student_id)<span class="badge bg-warning-subtle text-warning-emphasis">Sementara</span>@endif</td>
                                    <td>{{ $membership?->classroom?->name ?? '—' }}</td>
                                    <td>{{ $case->source->label }}</td>
                                    <td>{{ $case->serviceField->label }}</td>
                                    <td><span class="sibk-badge sibk-badge--{{ $tone }}">{{ $case->status->label }}</span></td>
                                    <td>{{ $nextFollowUp?->planned_date?->locale('id')->translatedFormat('d M Y') ?? '—' }}</td>
                                    <td><a href="{{ route('cases.show', $case) }}" class="fw-bold text-decoration-none">Buka</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada kasus yang dapat Anda akses.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($cases->hasPages())<div class="sibk-panel__footer p-4">{{ $cases->links() }}</div>@endif
            </div>
        @else
            <div class="sibk-panel">
                <div class="sibk-panel__header p-4 border-0">
                    <h3 class="sibk-panel__title">Daftar Sesi Bimbingan & Konsultasi</h3>
                    <p class="sibk-panel__subtitle">Modul konsultasi tetap menggunakan fixture sampai Sprint 4.</p>
                </div>
                <div class="table-responsive">
                    <table class="table sibk-table mb-0">
                        <thead><tr><th>No. Sesi</th><th>Tanggal</th><th>Murid & Kelas</th><th>Jenis</th><th>Status</th><th>Ringkasan</th></tr></thead>
                        <tbody>
                            @foreach($consultations as $session)
                                <tr><td class="fw-bold text-primary">{{ $session['id'] }}</td><td>{{ $session['date'] }}</td><td>{{ $session['student'] }}<div class="small text-muted">{{ $session['class'] }}</div></td><td>{{ $session['type'] }}</td><td>{{ $session['status'] }}</td><td>{{ $session['summary'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
