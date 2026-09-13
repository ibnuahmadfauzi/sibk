@extends('layouts.app-2')

@section('page-title', 'Pemantauan Kasus — Ruang BK')

@section('body')
<div class="sibk-dashboard" data-page-id="PG-C01">
    <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div class="sibk-page-header__copy">
            <h1>Pemantauan Kasus</h1>
            <p>Proyeksi aman seluruh kasus penanganan BK. Tampilan hanya-baca — tidak memuat informasi konseling sensitif.</p>
        </div>
        @can('exportWakaMonitoring')
        <a href="{{ route('waka.monitoring.export', array_filter($params)) }}"
           class="btn btn-outline-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 class="me-1" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Ekspor CSV
        </a>
        @endcan
    </div>

    {{-- Filter --}}
    <div class="sibk-panel mb-4">
        <div class="sibk-panel__body p-4">
            <form class="sibk-filter-form row g-3 align-items-end" action="{{ route('waka.monitoring.handling') }}" method="GET" id="waka_filter_form">
                <div class="col-12 col-md-3">
                    <label class="form-label" for="waka_period">Periode</label>
                    <input type="month" class="form-control" id="waka_period" name="period"
                           value="{{ $params['period'] ?? '' }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="waka_status">Status</label>
                    <select class="form-select" id="waka_status" name="status">
                        <option value="">Semua status</option>
                        @foreach($statuses as $code => $label)
                            <option value="{{ $code }}" @selected(($params['status'] ?? '') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="sort" value="{{ $params['sort'] }}">
                <input type="hidden" name="direction" value="{{ $params['direction'] }}">
                <div class="col-12 col-md-2">
                    <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
                </div>
                @if(($params['period'] ?? '') || ($params['status'] ?? ''))
                <div class="col-12 col-md-2">
                    <a href="{{ route('waka.monitoring.handling') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="table-responsive">
        <table class="table sibk-table mb-0 align-middle">
            <thead>
                <tr>
                    <th>
                        @php $isSortedMurid = ($params['sort'] === 'murid'); @endphp
                        <a class="text-decoration-none text-reset d-inline-flex align-items-center gap-1"
                           href="{{ route('waka.monitoring.handling', array_merge($params, ['sort' => 'murid', 'direction' => ($isSortedMurid && $params['direction'] === 'asc') ? 'desc' : 'asc', 'page' => 1])) }}">
                            Murid
                            @if($isSortedMurid)
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                    @if($params['direction'] === 'asc')<polyline points="18 15 12 9 6 15"/>
                                    @else<polyline points="6 9 12 15 18 9"/>@endif
                                </svg>
                            @endif
                        </a>
                    </th>
                    <th>
                        @php $isSortedKelas = ($params['sort'] === 'kelas'); @endphp
                        <a class="text-decoration-none text-reset d-inline-flex align-items-center gap-1"
                           href="{{ route('waka.monitoring.handling', array_merge($params, ['sort' => 'kelas', 'direction' => ($isSortedKelas && $params['direction'] === 'asc') ? 'desc' : 'asc', 'page' => 1])) }}">
                            Kelas
                            @if($isSortedKelas)
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                    @if($params['direction'] === 'asc')<polyline points="18 15 12 9 6 15"/>
                                    @else<polyline points="6 9 12 15 18 9"/>@endif
                                </svg>
                            @endif
                        </a>
                    </th>
                    <th>
                        @php $isSortedBidang = ($params['sort'] === 'bidang'); @endphp
                        <a class="text-decoration-none text-reset d-inline-flex align-items-center gap-1"
                           href="{{ route('waka.monitoring.handling', array_merge($params, ['sort' => 'bidang', 'direction' => ($isSortedBidang && $params['direction'] === 'asc') ? 'desc' : 'asc', 'page' => 1])) }}">
                            Bidang Layanan
                            @if($isSortedBidang)
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                    @if($params['direction'] === 'asc')<polyline points="18 15 12 9 6 15"/>
                                    @else<polyline points="6 9 12 15 18 9"/>@endif
                                </svg>
                            @endif
                        </a>
                    </th>
                    <th>
                        @php $isSortedStatus = ($params['sort'] === 'status'); @endphp
                        <a class="text-decoration-none text-reset d-inline-flex align-items-center gap-1"
                           href="{{ route('waka.monitoring.handling', array_merge($params, ['sort' => 'status', 'direction' => ($isSortedStatus && $params['direction'] === 'asc') ? 'desc' : 'asc', 'page' => 1])) }}">
                            Status
                            @if($isSortedStatus)
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                    @if($params['direction'] === 'asc')<polyline points="18 15 12 9 6 15"/>
                                    @else<polyline points="6 9 12 15 18 9"/>@endif
                                </svg>
                            @endif
                        </a>
                    </th>
                    <th>
                        @php $isSortedGuru = ($params['sort'] === 'guru_bk'); @endphp
                        <a class="text-decoration-none text-reset d-inline-flex align-items-center gap-1"
                           href="{{ route('waka.monitoring.handling', array_merge($params, ['sort' => 'guru_bk', 'direction' => ($isSortedGuru && $params['direction'] === 'asc') ? 'desc' : 'asc', 'page' => 1])) }}">
                            Guru BK
                            @if($isSortedGuru)
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                    @if($params['direction'] === 'asc')<polyline points="18 15 12 9 6 15"/>
                                    @else<polyline points="6 9 12 15 18 9"/>@endif
                                </svg>
                            @endif
                        </a>
                    </th>
                    <th>
                        @php $isSortedTanggal = ($params['sort'] === 'tanggal'); @endphp
                        <a class="text-decoration-none text-reset d-inline-flex align-items-center gap-1"
                           href="{{ route('waka.monitoring.handling', array_merge($params, ['sort' => 'tanggal', 'direction' => ($isSortedTanggal && $params['direction'] === 'asc') ? 'desc' : 'asc', 'page' => 1])) }}">
                            Tanggal
                            @if($isSortedTanggal)
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                    @if($params['direction'] === 'asc')<polyline points="18 15 12 9 6 15"/>
                                    @else<polyline points="6 9 12 15 18 9"/>@endif
                                </svg>
                            @endif
                        </a>
                    </th>
                    <th>Ringkasan Penanganan</th>
                    <th>Tindak Lanjut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td class="fw-semibold">{{ $row['nama_murid'] }}</td>
                    <td>{{ $row['kelas'] }}</td>
                    <td>{{ $row['bidang'] }}</td>
                    <td>
                        @php
                            $badgeTone = match($row['status_code']) {
                                'selesai'    => 'success',
                                'dibatalkan' => 'danger',
                                'sedang_diproses', 'membutuhkan_tindak_lanjut' => 'warning',
                                default      => 'primary',
                            };
                        @endphp
                        <span class="sibk-badge sibk-badge--{{ $badgeTone }}">{{ $row['status'] }}</span>
                    </td>
                    <td>{{ $row['guru_bk'] }}</td>
                    <td>{{ $row['tanggal'] }}</td>
                    <td class="text-muted small" style="max-width: 260px;">
                        {{ $row['waka_summary'] ?? '—' }}
                    </td>
                    <td class="small">
                        @if($row['tindak_lanjut'])
                            <span class="fw-semibold">{{ $row['tindak_lanjut']['jenis'] }}</span>
                            <div class="text-muted">{{ $row['tindak_lanjut']['tanggal'] }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Tidak ada data kasus yang sesuai filter.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($paginator->hasPages())
    <div class="mt-3">{{ $paginator->links() }}</div>
    @endif
</div>
@endsection
