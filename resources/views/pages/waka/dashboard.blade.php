<div class="sibk-dashboard" data-page-id="PG-002" data-dashboard-role="waka">
    <header class="sibk-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="sibk-page-header__copy"><h1 id="dashboard-title">Dashboard Waka Kesiswaan</h1></div>
        @if($years->isNotEmpty())
            <form method="GET" action="{{ route('dashboard.preview') }}" class="d-flex align-items-center gap-2" data-auto-filter data-filter-reset-url="{{ route('dashboard.preview') }}">
                <label for="academic_year_id" class="form-label text-nowrap mb-0">Tahun Ajaran</label>
                <select class="form-select" id="academic_year_id" name="academic_year_id" data-filter-field data-filter-default="{{ request()->has('academic_year_id') ? '' : $activeYear?->id }}">
                    @foreach($years as $year)<option value="{{ $year->id }}" @selected($activeYear?->id === $year->id)>{{ $year->name }}</option>@endforeach
                </select>
                <button class="btn btn-outline-primary text-nowrap" type="submit" data-filter-action>Filter</button>
            </form>
        @endif
    </header>

    <section aria-label="Statistik utama">
        <div class="row g-3 sibk-stat-row">
            @foreach($dashboard['metrics'] as $metric)
                <div class="col-6 col-xl-3">
                    <article class="sibk-stat-card sibk-tone--{{ $metric['tone'] }}">
                        <div class="sibk-stat-card__inner">
                            <div class="sibk-stat-card__icon-col">
                                <div class="sibk-stat-card__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                </div>
                            </div>
                            <div class="sibk-stat-card__content-col">
                                <h2 class="sibk-stat-card__label">{{ $metric['label'] }}</h2>
                                <strong class="sibk-stat-card__value">{{ $metric['value'] }}</strong>
                                <span class="sibk-stat-meta">{{ $metric['meta'] }}</span>
                            </div>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </section>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-8">
            <section class="sibk-panel h-100" aria-labelledby="waka-trend-title">
                <header class="sibk-panel__header"><div class="sibk-panel__title-group"><h2 id="waka-trend-title">Grafik Catatan BK</h2></div></header>
                <div class="p-3 p-md-4">
                    <p class="small text-muted">Jumlah murid yang memiliki catatan BK setiap bulan.</p>
                    @php
                        $step = max(1, (int) ceil((collect($dashboard['trend'])->max('count') ?? 0) / 4));
                        $maximum = $step * 4;
                        $currentMonth = now()->format('Y-m');
                    @endphp
                    <div class="sibk-waka-trend">
                        <div class="sibk-waka-trend__scale" aria-hidden="true">
                            @for($tick = 0; $tick <= 4; $tick++)
                                <span style="bottom: {{ $tick * 25 }}%">{{ $tick * $step }}</span>
                            @endfor
                        </div>
                        <div class="sibk-waka-trend__scroll" tabindex="0" role="group" aria-label="Grafik tren murid per bulan" aria-describedby="waka-trend-note">
                            <div class="sibk-waka-trend__months" role="list" aria-label="Jumlah murid per bulan">
                                @foreach($dashboard['trend'] as $month)
                                    @php($isCurrentMonth = $month['month'] === $currentMonth)
                                    <div class="sibk-waka-trend__month {{ $isCurrentMonth ? 'sibk-waka-trend__month--current' : '' }}" role="listitem" aria-label="{{ $month['month'] }}: {{ $month['count'] }} murid{{ $isCurrentMonth ? ', bulan berjalan' : '' }}">
                                        <div class="sibk-waka-trend__plot" style="--bar-height: {{ $month['count'] / $maximum * 100 }}%" aria-hidden="true">
                                            <strong class="sibk-waka-trend__value">{{ $month['count'] }}</strong>
                                            <span class="sibk-waka-trend__bar"></span>
                                        </div>
                                        <span aria-hidden="true" class="sibk-waka-trend__label small">{{ $month['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between gap-2 small text-muted mt-3">
                        <p class="mb-0" id="waka-trend-note">Setiap murid dihitung sekali per bulan.</p>
                        @if(collect($dashboard['trend'])->contains('month', $currentMonth))
                            <span class="sibk-waka-trend__legend">Bulan berjalan</span>
                        @endif
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-4 d-flex flex-column gap-3">
            <section class="sibk-panel" aria-labelledby="waka-grades-title">
                <header class="sibk-panel__header"><div class="sibk-panel__title-group"><h2 id="waka-grades-title">Murid per Tingkat</h2></div></header>
                <div class="px-3 px-md-4 py-2">
                    @foreach($dashboard['grades'] as $grade)
                        <div class="d-flex justify-content-between gap-3 py-2">
                            <span>Kelas {{ ['X' => 10, 'XI' => 11, 'XII' => 12][$grade['label']] }}</span><strong>{{ $grade['count'] }} murid</strong>
                        </div>
                    @endforeach
                </div>
            </section>
            <section class="sibk-panel" aria-labelledby="waka-top-classrooms-title">
                <header class="sibk-panel__header"><div class="sibk-panel__title-group"><h2 id="waka-top-classrooms-title">Catatan Permasalahan Terbanyak</h2></div></header>
                <div class="px-3 px-md-4 py-2">
                        <ul class="list-unstyled mb-0">
                            @forelse($dashboard['top_case_classrooms'] as $classroom)
                                <li class="d-flex align-items-baseline justify-content-between gap-3 py-2">
                                    <span>{{ $classroom['label'] }}</span>
                                    <strong class="text-nowrap">{{ $classroom['count'] }} murid</strong>
                                </li>
                            @empty
                                <li class="small text-muted py-2">Belum ada catatan permasalahan pada periode ini.</li>
                            @endforelse
                        </ul>
                </div>
            </section>
        </div>
    </div>

    <section class="sibk-panel mt-3" aria-labelledby="waka-follow-up-title">
        <header class="sibk-panel__header"><div class="sibk-panel__title-group"><h2 id="waka-follow-up-title">Murid Perlu Tindak Lanjut</h2></div></header>
        @if(empty($dashboard['follow_up_students']))
            <x-empty-state title="Tidak ada murid perlu tindak lanjut" description="Murid dengan status permasalahan Tindak Lanjut pada tahun ajaran terpilih akan tampil di sini." />
        @else
            <div class="table-responsive">
                <table class="table sibk-table align-middle sibk-waka-follow-ups mb-0" data-client-sort-groups>
                    <thead><tr><x-client-sort-header label="Murid/Kelas" /><th scope="col">Jenis Layanan</th><th scope="col">Guru BK</th><th scope="col">Ringkasan</th><th scope="col">Tindak Lanjut</th></tr></thead>
                    @foreach($dashboard['follow_up_students'] as $student)
                        <tbody>
                            @foreach($student['services'] as $service)
                                <tr>
                                    @if($loop->first)
                                        <th scope="rowgroup" rowspan="{{ count($student['services']) }}" class="align-top">
                                            {{ $student['name'] }}<span class="d-block small text-muted fw-normal">{{ $student['classroom'] }}</span>
                                        </th>
                                    @endif
                                    <td>{{ $service['service'] }}</td>
                                    <td>{{ $service['teacher'] }}</td>
                                    <td class="sibk-waka-follow-ups__summary">{{ $service['summary'] ?: 'Belum ada ringkasan.' }}</td>
                                    <td>{{ $service['follow_up'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        @endif
    </section>
</div>
