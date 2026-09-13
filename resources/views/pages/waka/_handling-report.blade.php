@php
    $hasFilters = filled($params['period'] ?? null) || filled($params['status'] ?? null);
    $columns = ['murid' => 'Murid', 'kelas' => 'Kelas', 'bidang' => 'Bidang', 'status' => 'Status', 'guru_bk' => 'Guru BK', 'tanggal' => 'Tanggal'];
@endphp
<section class="sibk-panel mb-4" aria-labelledby="handling-filter-title">
    <div class="sibk-panel__body p-4">
        <h2 class="h6 mb-3" id="handling-filter-title">Filter monitoring penanganan</h2>
        <form class="row g-3 align-items-end" action="{{ route('waka.reports') }}" method="GET">
            <input type="hidden" name="tab" value="penanganan">
            <div class="col-12 col-md-4">
                <label class="form-label" for="waka_handling_period">Periode</label>
                <input class="form-control" id="waka_handling_period" name="period" type="month" value="{{ $params['period'] ?? '' }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="waka_handling_status">Status</label>
                <select class="form-select" id="waka_handling_status" name="status">
                    <option value="">Semua status</option>
                    @foreach($statuses as $code => $label)<option value="{{ $code }}" @selected(($params['status'] ?? '') === $code)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <input type="hidden" name="sort" value="{{ $params['sort'] }}">
            <input type="hidden" name="direction" value="{{ $params['direction'] }}">
            <div class="col-12 col-md-auto"><button class="btn btn-primary w-100" type="submit">Terapkan</button></div>
            @if($hasFilters)<div class="col-12 col-md-auto"><a class="btn btn-outline-secondary w-100" href="{{ route('waka.reports', ['tab' => 'penanganan']) }}">Reset filter</a></div>@endif
        </form>
    </div>
</section>

<section class="sibk-panel" aria-labelledby="handling-results-title">
    <div class="sibk-panel__header flex-wrap gap-2">
        <div class="sibk-panel__title-group"><h2 id="handling-results-title">{{ $paginator->total() }} penanganan</h2></div>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('waka.monitoring.export', array_filter([...$params, 'format' => 'csv'], static fn ($value) => $value !== null && $value !== '')) }}">Ekspor CSV</a>
    </div>

    @if($rows->isEmpty())
        <x-empty-state title="Belum ada penanganan" description="Tidak ada kasus yang sesuai dengan periode atau status terpilih." />
        @if($hasFilters)<div class="text-center pb-4"><a href="{{ route('waka.reports', ['tab' => 'penanganan']) }}" class="btn btn-outline-secondary">Reset filter</a></div>@endif
    @else
        <div class="table-responsive sibk-waka-table--desktop">
            <table class="table sibk-table align-middle">
                <thead><tr>
                    @foreach($columns as $key => $label)
                        @php $activeSort = ($params['sort'] ?? 'tanggal') === $key; @endphp
                        <th scope="col" @if($activeSort) aria-sort="{{ ($params['direction'] ?? 'desc') === 'asc' ? 'ascending' : 'descending' }}" @endif>
                            <a class="text-decoration-none text-reset"
                                @if($activeSort) aria-label="{{ $label }}, diurutkan {{ ($params['direction'] ?? 'desc') === 'asc' ? 'naik' : 'turun' }}" @endif
                                href="{{ route('waka.reports', array_merge(['tab' => 'penanganan'], $params, ['sort' => $key, 'direction' => $activeSort && ($params['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}">
                                {{ $label }}
                            </a>
                        </th>
                    @endforeach
                    <th scope="col">Akses</th>
                </tr></thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['nama_murid'] }}</td><td>{{ $row['kelas'] }}</td><td>{{ $row['bidang'] }}</td>
                            <td><span class="sibk-badge sibk-badge--{{ $row['status_code'] === 'selesai' ? 'success' : (in_array($row['status_code'], ['sedang_diproses', 'membutuhkan_tindak_lanjut'], true) ? 'warning' : 'primary') }}">{{ $row['status'] }}</span></td>
                            <td>{{ $row['guru_bk'] }}</td><td>{{ $row['tanggal'] }}</td>
                            <td>@if($row['coordination_url'])<a class="btn btn-sm btn-outline-primary" href="{{ $row['coordination_url'] }}">Buka detail koordinasi</a>@else<span class="text-muted">-</span>@endif</td>
                        </tr>
                        <tr>
                            <td colspan="7" class="small">
                                <strong>Ringkasan:</strong> {{ $row['waka_summary'] ?: '-' }}
                                <span class="mx-2" aria-hidden="true">|</span>
                                <strong>Tindak lanjut:</strong> @if($row['tindak_lanjut']){{ $row['tindak_lanjut']['jenis'] }} - {{ $row['tindak_lanjut']['tanggal'] }}@else-@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="sibk-waka-card-list p-3">
            @foreach($rows as $row)
                <article class="sibk-panel sibk-panel--inset p-3">
                    <h3 class="h6 mb-1">{{ $row['nama_murid'] }}</h3><p class="small text-muted mb-2">{{ $row['kelas'] }} - {{ $row['tanggal'] }}</p>
                    <p class="mb-2"><span class="sibk-badge sibk-badge--warning">{{ $row['status'] }}</span></p>
                    <p class="small mb-2">{{ $row['bidang'] }} - Guru BK: <strong>{{ $row['guru_bk'] }}</strong></p>
                    <details class="small mb-3"><summary class="fw-semibold">Ringkasan penanganan</summary><p class="mt-2 mb-1">{{ $row['waka_summary'] ?: '-' }}</p><p class="mb-0"><strong>Tindak lanjut:</strong> @if($row['tindak_lanjut']){{ $row['tindak_lanjut']['jenis'] }} - {{ $row['tindak_lanjut']['tanggal'] }}@else-@endif</p></details>
                    @if($row['coordination_url'])<a class="btn btn-outline-primary w-100" href="{{ $row['coordination_url'] }}">Buka detail koordinasi</a>@endif
                </article>
            @endforeach
        </div>

        @if($paginator->hasPages())<div class="px-4 pb-4">{{ $paginator->links() }}</div>@endif
    @endif
</section>
