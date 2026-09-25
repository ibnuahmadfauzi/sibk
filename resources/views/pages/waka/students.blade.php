@extends('layouts.app-2')

@section('page-title', 'Murid dengan Permasalahan - Ruang BK')

@section('body')
@php
    $hasFilters = filled($params['period'] ?? null) || filled($params['status'] ?? null);
    $columns = [
        'murid' => 'Murid',
        'kelas' => 'Kelas',
        'status' => 'Status Terbaru',
        'guru_bk' => 'Guru BK',
    ];
@endphp
<div class="sibk-dashboard" data-page-id="PG-WAKA-STUDENTS">
    <header class="sibk-page-header mb-4">
        <div class="sibk-page-header__copy">
            <h1>Murid dengan Permasalahan</h1>
            <p>Daftar murid yang memperoleh penanganan BK pada periode terpilih.</p>
        </div>
    </header>

    <div class="alert sibk-read-only-notice d-flex gap-3 align-items-start" role="status">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z"/><path d="M12 16v-4M12 8h.01"/></svg>
        <div><strong>Hanya untuk dilihat</strong><p class="mb-0">Isi konsultasi pribadi tidak ditampilkan.</p></div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" role="alert" tabindex="-1">
            <strong>Filter belum dapat diterapkan.</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="sibk-panel mb-4" aria-labelledby="student-filter-title">
        <div class="sibk-panel__body p-4">
            <h2 class="h6 mb-3" id="student-filter-title">Filter daftar murid</h2>
            <form class="row g-3 align-items-end" action="{{ route('waka.monitoring.students') }}" method="GET">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="waka_student_period">Periode</label>
                    <input class="form-control" id="waka_student_period" name="period" type="month" value="{{ $params['period'] ?? '' }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="waka_student_status">Status</label>
                    <select class="form-select" id="waka_student_status" name="status">
                        <option value="">Semua status</option>
                        @foreach($statuses as $code => $label)
                            <option value="{{ $code }}" @selected(($params['status'] ?? '') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="sort" value="{{ $params['sort'] }}">
                <input type="hidden" name="direction" value="{{ $params['direction'] }}">
                <div class="col-12 col-md-auto"><button class="btn btn-primary w-100" type="submit">Terapkan</button></div>
                @if($hasFilters)
                    <div class="col-12 col-md-auto"><a class="btn btn-outline-secondary w-100" href="{{ route('waka.monitoring.students') }}">Reset filter</a></div>
                @endif
            </form>
        </div>
    </section>

    <section class="sibk-panel" aria-labelledby="student-results-title">
        <div class="sibk-panel__header">
            <div class="sibk-panel__title-group"><h2 id="student-results-title">{{ $paginator->total() }} murid ditemukan</h2></div>
        </div>

        @if($rows->isEmpty())
            <x-empty-state title="Belum ada murid dengan permasalahan" description="Tidak ada data yang sesuai dengan periode atau status terpilih." />
            @if($hasFilters)<div class="text-center pb-4"><a href="{{ route('waka.monitoring.students') }}" class="btn btn-outline-secondary">Reset filter</a></div>@endif
        @else
            <div class="table-responsive sibk-waka-table--desktop">
                <table class="table sibk-table align-middle">
                    <thead><tr>
                        @foreach($columns as $key => $label)
                            @php $activeSort = ($params['sort'] ?? 'murid') === $key; @endphp
                            <th scope="col" @if($activeSort) aria-sort="{{ ($params['direction'] ?? 'asc') === 'asc' ? 'ascending' : 'descending' }}" @endif>
                                <a class="text-decoration-none text-reset"
                                    @if($activeSort) aria-label="{{ $label }}, diurutkan {{ ($params['direction'] ?? 'asc') === 'asc' ? 'naik' : 'turun' }}" @endif
                                    href="{{ route('waka.monitoring.students', array_merge($params, ['sort' => $key, 'direction' => $activeSort && ($params['direction'] ?? 'asc') === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}">
                                    {{ $label }}
                                </a>
                            </th>
                        @endforeach
                        <th scope="col">Permasalahan</th><th scope="col">Aktif</th><th scope="col">Akses</th>
                    </tr></thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['nama_murid'] }}</td>
                                <td>{{ $row['kelas'] }}</td>
                                <td><span class="sibk-badge sibk-badge--{{ in_array($row['status_code'], ['selesai'], true) ? 'success' : (in_array($row['status_code'], ['sedang_diproses', 'membutuhkan_tindak_lanjut'], true) ? 'warning' : 'primary') }}">{{ $row['status_terbaru'] }}</span></td>
                                <td>{{ $row['guru_bk'] }}</td>
                                <td>{{ $row['jumlah_kasus'] }}</td>
                                <td>{{ $row['jumlah_aktif'] }}</td>
                                <td><a href="{{ $row['detail_url'] }}" class="btn btn-sm btn-outline-primary">Lihat detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sibk-waka-card-list p-3">
                @foreach($rows as $row)
                    <article class="sibk-panel sibk-panel--inset p-3">
                        <h3 class="h6 mb-1">{{ $row['nama_murid'] }}</h3>
                        <p class="text-muted small mb-3">{{ $row['kelas'] }}</p>
                        <p class="mb-2"><strong>{{ $row['jumlah_kasus'] }} permasalahan</strong> - {{ $row['jumlah_aktif'] }} masih aktif</p>
                        <p class="mb-2"><span class="sibk-badge sibk-badge--{{ $row['status_code'] === 'selesai' ? 'success' : 'warning' }}">{{ $row['status_terbaru'] }}</span></p>
                        <p class="small mb-3">Guru BK: <strong>{{ $row['guru_bk'] }}</strong></p>
                        <a href="{{ $row['detail_url'] }}" class="btn btn-outline-primary w-100">Lihat detail</a>
                    </article>
                @endforeach
            </div>

            @if($paginator->hasPages())<div class="px-4 pb-4">{{ $paginator->links() }}</div>@endif
        @endif
    </section>
</div>
@endsection
