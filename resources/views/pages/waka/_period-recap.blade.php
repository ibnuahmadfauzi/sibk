@php
    $metricCards = [
        ['label' => 'Murid ditangani', 'value' => $recap['metrics']['served_students'], 'tone' => 'primary'],
        ['label' => 'Kasus tercatat', 'value' => $recap['metrics']['cases_recorded'], 'tone' => 'info'],
        ['label' => 'Membutuhkan tindak lanjut', 'value' => $recap['metrics']['needs_follow_up'], 'tone' => 'warning'],
        ['label' => 'Kasus selesai', 'value' => $recap['metrics']['completed'], 'tone' => 'success'],
    ];
@endphp
<section class="sibk-panel mb-4" aria-labelledby="recap-filter-title">
    <div class="sibk-panel__body p-4">
        <h2 class="h6 mb-3" id="recap-filter-title">Periode rekap</h2>
        <form class="row g-3 align-items-end" action="{{ route('waka.reports') }}" method="GET">
            <input type="hidden" name="tab" value="rekap">
            <div class="col-12 col-md-4">
                <label class="form-label" for="recap_academic_year">Tahun ajaran</label>
                <select class="form-select" id="recap_academic_year" name="academic_year_id">
                    @foreach($academicYears as $year)
                        <option value="{{ $year->getKey() }}" @selected((int) $params['academic_year_id'] === $year->getKey())>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="recap_date_start">Tanggal awal</label>
                <input class="form-control" id="recap_date_start" name="date_start" type="date" value="{{ $params['date_start'] }}">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="recap_date_end">Tanggal akhir</label>
                <input class="form-control" id="recap_date_end" name="date_end" type="date" value="{{ $params['date_end'] }}">
            </div>
            <div class="col-12 col-md-auto"><button class="btn btn-primary w-100" type="submit">Terapkan</button></div>
        </form>
    </div>
</section>

<section aria-label="Ringkasan periode" class="mb-4">
    <div class="row g-3 sibk-stat-row">
        @foreach($metricCards as $metric)
            <div class="col-6 col-xl-3">
                <article class="sibk-stat-card sibk-tone--{{ $metric['tone'] }}">
                    <div class="sibk-stat-card__inner">
                        <div class="sibk-stat-card__content-col">
                            <h2 class="sibk-stat-card__label">{{ $metric['label'] }}</h2>
                            <strong class="sibk-stat-card__value">{{ $metric['value'] }}</strong>
                        </div>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <section class="sibk-panel" aria-labelledby="recap-fields-title">
            <header class="sibk-panel__header"><div class="sibk-panel__title-group"><h2 id="recap-fields-title">Pelaksanaan Layanan BK</h2></div></header>
            <div class="p-4">
                @forelse($recap['service_fields'] as $field)
                    <div class="d-flex justify-content-between py-2 border-bottom border-secondary-subtle"><span>{{ $field['label'] }}</span><strong>{{ $field['count'] }}</strong></div>
                @empty
                    <p class="text-muted mb-0">Belum ada layanan pada periode ini.</p>
                @endforelse
            </div>
        </section>
    </div>
    <div class="col-12 col-lg-6">
        <section class="sibk-panel" aria-labelledby="recap-status-title">
            <header class="sibk-panel__header"><div class="sibk-panel__title-group"><h2 id="recap-status-title">Kondisi Penanganan</h2></div></header>
            <div class="p-4">
                @foreach($recap['statuses'] as $status)
                    <div class="d-flex justify-content-between py-2 border-bottom border-secondary-subtle"><span>{{ $status['label'] }}</span><strong>{{ $status['count'] }}</strong></div>
                @endforeach
            </div>
        </section>
    </div>
</div>

<section class="sibk-panel mb-4" aria-labelledby="student-affairs-title">
    <header class="sibk-panel__header"><div class="sibk-panel__title-group"><h2 id="student-affairs-title">Konteks Kesiswaan</h2></div></header>
    <div class="p-4 d-flex flex-column flex-md-row gap-4 justify-content-between">
        <div><span class="text-muted d-block small">Pelanggaran tercatat</span><strong class="fs-4">{{ $recap['student_affairs']['violations'] }}</strong></div>
        <div><span class="text-muted d-block small">Murid terkait pelanggaran</span><strong class="fs-4">{{ $recap['student_affairs']['linked_students'] }}</strong></div>
        <div><span class="text-muted d-block small">Prestasi terverifikasi</span><strong class="fs-4">{{ $recap['student_affairs']['verified_achievements'] }}</strong></div>
    </div>
</section>

<section class="sibk-panel" aria-labelledby="class-recap-title">
    <header class="sibk-panel__header"><div class="sibk-panel__title-group"><h2 id="class-recap-title">Ringkasan menurut kelas</h2></div></header>
    @if(empty($recap['classes']))
        <x-empty-state title="Belum ada rekap kelas" description="Kasus pada periode ini belum memiliki kelas historis yang dapat direkap." />
    @else
        <div class="table-responsive">
            <table class="table sibk-table align-middle">
                <thead><tr><th scope="col">Kelas</th><th scope="col">Murid ditangani</th><th scope="col">Kasus aktif</th><th scope="col">Selesai</th><th scope="col">Perlu tindak lanjut</th></tr></thead>
                <tbody>@foreach($recap['classes'] as $class)<tr><td class="fw-semibold">{{ $class['classroom'] }}</td><td>{{ $class['served_students'] }}</td><td>{{ $class['active_cases'] }}</td><td>{{ $class['completed'] }}</td><td>{{ $class['needs_follow_up'] }}</td></tr>@endforeach</tbody>
            </table>
        </div>
    @endif
</section>
