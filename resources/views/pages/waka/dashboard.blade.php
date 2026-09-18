<div class="sibk-dashboard" data-page-id="PG-002" data-dashboard-role="waka">
    <header class="sibk-page-header d-flex flex-wrap justify-content-between gap-3">
        <div class="sibk-page-header__copy">
            <h1 id="dashboard-title">Dashboard Waka Kesiswaan</h1>
            <p>{{ $dashboard['description'] }}</p>
            <small class="text-muted">Tahun ajaran {{ $dashboard['scope'] }}</small>
        </div>
        @if($years->isNotEmpty())
            <form method="GET" action="{{ route('dashboard.preview') }}" class="d-flex align-items-end gap-2 sibk-header-filter">
                <div class="flex-grow-1">
                    <label for="academic_year_id" class="form-label small">Tahun Ajaran</label>
                    <select class="form-select" id="academic_year_id" name="academic_year_id">
                        @foreach($years as $year)<option value="{{ $year->id }}" @selected($activeYear?->id === $year->id)>{{ $year->name }}</option>@endforeach
                    </select>
                </div>
                <button class="btn btn-outline-primary text-nowrap" type="submit">Terapkan</button>
            </form>
        @endif

        <div class="alert sibk-read-only-notice" role="status">
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.6 2.9 8.5 7 10 4.1-1.5 7-5.4 7-10V6l-7-3Z"/><path d="M12 8v4M12 16h.01"/></svg>
                <div><strong>Tampilan hanya-baca</strong><p>Anda melihat ringkasan layanan BK sekolah dan dapat membuka detail tanpa mengubah data.</p></div>
        </div>
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
            <section class="sibk-panel sibk-panel--inset" aria-labelledby="waka-attention-title">
                <header class="sibk-panel__header">
                    <div class="sibk-panel__title-group"><h2 id="waka-attention-title">Membutuhkan Perhatian</h2></div>
                    <a href="{{ route('waka.reports', ['tab' => 'penanganan', 'status' => 'membutuhkan_tindak_lanjut']) }}" class="btn btn-sm btn-outline-primary">Lihat semua penanganan</a>
                </header>
                @if(empty($dashboard['attention']))
                    <x-empty-state title="Tidak ada penanganan mendesak" description="Belum ada kasus yang membutuhkan tindak lanjut atau perhatian khusus." />
                @else
                    <div class="p-3">
                        @foreach($dashboard['attention'] as $row)
                            <article class="border-bottom border-secondary-subtle py-3 first-pt-0">
                                <div class="d-flex flex-wrap justify-content-between gap-2">
                                    <div><h3 class="h6 mb-1">{{ $row['nama_murid'] }}</h3><p class="small text-muted mb-0">{{ $row['kelas'] }} - Guru BK: {{ $row['guru_bk'] }}</p></div>
                                    <span class="sibk-badge sibk-badge--warning">{{ $row['status'] }}</span>
                                </div>
                                <p class="small mb-0"><strong>Tindak lanjut:</strong> {{ $row['tindak_lanjut'] }}</p>
                                <a class="btn btn-sm btn-outline-primary mt-2" href="{{ $row['detail_url'] }}">Lihat detail</a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="sibk-panel" aria-labelledby="waka-composition-title">
                <header class="sibk-panel__header"><div class="sibk-panel__title-group"><h2 id="waka-composition-title">Komposisi Status</h2></div></header>
                <div class="p-4">
                    @foreach($dashboard['status_composition'] as $status)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary-subtle">
                            <span>{{ $status['label'] }}</span><strong>{{ $status['count'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <section class="sibk-panel mt-3" aria-labelledby="waka-latest-title">
        <header class="sibk-panel__header">
            <div class="sibk-panel__title-group"><h2 id="waka-latest-title">Penanganan Terbaru</h2></div>
            <a href="{{ route('waka.reports', ['tab' => 'penanganan']) }}" class="btn btn-sm btn-outline-primary">Buka Monitoring Penanganan</a>
        </header>
        @if(empty($dashboard['latest']))
            <x-empty-state title="Belum ada penanganan" description="Penanganan terbaru pada tahun ajaran terpilih akan tampil di sini." />
        @else
            <div class="table-responsive d-none d-lg-block">
                <table class="table sibk-table align-middle">
                    <thead><tr><th>Murid</th><th>Kelas</th><th>Bidang</th><th>Status</th><th>Guru BK</th><th>Tanggal</th><th>Akses</th></tr></thead>
                    <tbody>
                        @foreach($dashboard['latest'] as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['nama_murid'] }}</td><td>{{ $row['kelas'] }}</td><td>{{ $row['bidang'] }}</td>
                                <td><span class="sibk-badge sibk-badge--{{ $row['status_code'] === 'selesai' ? 'success' : (in_array($row['status_code'], ['sedang_diproses', 'membutuhkan_tindak_lanjut'], true) ? 'warning' : 'primary') }}">{{ $row['status'] }}</span></td>
                                <td>{{ $row['guru_bk'] }}</td><td>{{ $row['tanggal'] }}</td>
                                <td><a class="btn btn-sm btn-outline-primary" href="{{ $row['detail_url'] }}">Lihat detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-lg-none p-3">
                @foreach($dashboard['latest'] as $row)
                    <article class="sibk-panel sibk-panel--inset p-3 mb-3">
                        <h3 class="h6 mb-1">{{ $row['nama_murid'] }}</h3><p class="small text-muted mb-2">{{ $row['kelas'] }} - {{ $row['tanggal'] }}</p>
                        <p class="small mb-2">{{ $row['bidang'] }} - Guru BK: <strong>{{ $row['guru_bk'] }}</strong></p>
                        <p class="mb-2"><span class="sibk-badge sibk-badge--warning">{{ $row['status'] }}</span></p>
                        <p class="small mb-3"><strong>Tindak lanjut:</strong> {{ $row['tindak_lanjut'] }}</p>
                        <a class="btn btn-outline-primary w-100" href="{{ $row['detail_url'] }}">Lihat detail</a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
