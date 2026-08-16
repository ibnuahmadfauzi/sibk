<div class="sibk-dashboard" data-page-id="PG-002" data-preview-state="{{ $previewState }}">

    <header class="sibk-page-header">
        <div class="sibk-page-header__copy">
            <h1 id="dashboard-title">Dashboard</h1>
            <p>{{ $dashboard['description'] }}</p>
        </div>
        
        @if ($dashboard['read_only'])
            <div class="alert sibk-read-only-notice" role="status">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.6 2.9 8.5 7 10 4.1-1.5 7-5.4 7-10V6l-7-3Z"/><path d="M12 8v4M12 16h.01"/></svg>
                <div><strong>Tampilan koordinasi hanya-baca</strong><p>Anda hanya melihat agregat yang diizinkan dan kasus yang secara eksplisit dikoordinasikan. Isi konsultasi sensitif serta aksi perubahan tidak ditampilkan.</p></div>
            </div>
        @endif
    </header>

    @if ($previewState === 'error')
        <section class="sibk-state-panel" aria-labelledby="dashboard-error-title">
            <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01"/></svg>
            <h2 id="dashboard-error-title">Ringkasan belum dapat dimuat</h2>
            <p>Data tidak berubah. Periksa koneksi lalu coba kembali tanpa memperluas cakupan akses.</p>
            <a class="btn btn-primary" href="{{ route('dashboard.preview', ['role' => $previewRole, 'year' => $activeYear]) }}">Coba lagi</a>
        </section>
    @elseif ($previewState === 'loading')
        <div class="sibk-loading" role="status" aria-live="polite">
            <span class="visually-hidden">Memuat ringkasan dashboard</span>
            <div class="row g-3" aria-hidden="true">
                @for ($i = 0; $i < 4; $i++)
                    <div class="col-12 col-sm-6 col-xl-3"><div class="sibk-skeleton sibk-skeleton--stat"></div></div>
                @endfor
                <div class="col-12 col-xl-7"><div class="sibk-skeleton sibk-skeleton--panel"></div></div>
                <div class="col-12 col-xl-5"><div class="sibk-skeleton sibk-skeleton--panel"></div></div>
            </div>
        </div>
    @else
        <section aria-label="Statistik utama">
            <div class="row g-3 sibk-stat-row">
                @foreach ($dashboard['stats'] as $stat)
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="sibk-stat-card sibk-tone--{{ $stat['tone'] }}">
                            <div class="sibk-stat-card__inner">
                                <div class="sibk-stat-card__icon-col">
                                    <div class="sibk-stat-card__icon" aria-hidden="true">
                                        @switch($stat['kind'])
                                            @case('students')
                                                <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-4 2.7-7 6-7s6 3 6 7"/><circle cx="17" cy="9" r="2"/><path d="M15 15c3.6 0 6 2.2 6 5"/></svg>
                                                @break
                                            @case('etatib')
                                                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                                                @break
                                            @case('cases')
                                                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                                @break
                                            @default
                                                <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        @endswitch
                                    </div>
                                </div>
                                <div class="sibk-stat-card__content-col">
                                    <h2 class="sibk-stat-card__label">{{ $stat['label'] }}</h2>
                                    <strong class="sibk-stat-card__value">{{ $previewState === 'empty' ? '0' : $stat['value'] }}</strong>
                                    
                                    @if ($previewState !== 'empty' && isset($stat['delta']))
                                        <span class="sibk-stat-delta sibk-stat-delta--{{ $stat['delta_tone'] ?? 'neutral' }}">
                                            @if (($stat['delta_tone'] ?? '') === 'up')
                                                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                                            @elseif (($stat['delta_tone'] ?? '') === 'down')
                                                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
                                            @endif
                                            {{ $stat['delta'] }}
                                        </span>
                                    @else
                                        <span class="sibk-stat-meta">{{ $previewState === 'empty' ? 'Belum ada data' : $stat['meta'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="row g-3 mt-1">
            {{-- Panel Kiri: Tindak Lanjut Terdekat --}}
            <div class="col-12 col-xl-7">
                <section class="sibk-panel" aria-labelledby="tindak-lanjut-title">
                    <header class="sibk-panel__header">
                        <div class="sibk-panel__title-group">
                            <svg class="sibk-panel__icon" aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <h2 id="tindak-lanjut-title">Tindak lanjut terdekat</h2>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-primary sibk-panel__action is-planned">
                            Lihat semua <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </header>

                    @if ($previewState === 'empty' || empty($dashboard['tindak_lanjut']))
                        <x-empty-state title="Tidak ada tindak lanjut" description="Tidak ada jadwal tindak lanjut dalam waktu dekat." />
                    @else
                        <div class="sibk-list-group">
                            @foreach ($dashboard['tindak_lanjut'] as $item)
                                <article class="sibk-list-item">
                                    <div class="sibk-list-item__date-box">
                                        <strong>{{ $item['date'] }}</strong>
                                        <span>{{ $item['month'] }}<br>{{ $item['year'] }}</span>
                                    </div>
                                    <div class="sibk-list-item__content">
                                        <strong>{{ $item['code'] }}</strong>
                                        <span>{{ $item['title'] }}</span>
                                        <small>Siswa: {{ $item['student'] }}</small>
                                    </div>
                                    <div class="sibk-list-item__trailing">
                                        <span class="badge sibk-icon-tone--{{ $item['status_tone'] }}">{{ $item['status'] }}</span>
                                        <svg class="sibk-list-item__chevron" aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            {{-- Panel Kanan: Aktivitas Terbaru --}}
            <div class="col-12 col-xl-5">
                <section class="sibk-panel" aria-labelledby="activity-title">
                    <header class="sibk-panel__header">
                        <div class="sibk-panel__title-group">
                            <svg class="sibk-panel__icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            <h2 id="activity-title">Aktivitas terbaru</h2>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-primary sibk-panel__action is-planned">
                            Lihat semua <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </header>
                    
                    @if ($previewState === 'empty' || empty($dashboard['activities']))
                        <x-empty-state title="Belum ada aktivitas" description="Aktivitas sistem yang relevan dengan Anda akan tampil di sini." />
                    @else
                        <div class="sibk-activity-list">
                            @foreach ($dashboard['activities'] as $activity)
                                <article class="sibk-activity-row">
                                    <div class="sibk-activity-row__icon-circle sibk-icon-tone--{{ $activity['tone'] }}" aria-hidden="true">
                                        @if($activity['icon'] === 'case-new')
                                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                        @elseif($activity['icon'] === 'followup')
                                            <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        @elseif($activity['icon'] === 'etatib')
                                            <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                        @else
                                            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                        @endif
                                    </div>
                                    <div class="sibk-activity-row__content">
                                        <strong>{{ $activity['title'] }}</strong>
                                        <span>{{ $activity['context'] }}</span>
                                    </div>
                                    <time class="sibk-activity-row__time">{{ $activity['time'] }}</time>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>

        {{-- Quick Actions & Dekorasi --}}
        <div class="sibk-dashboard-footer">
            <div class="sibk-quick-actions">
                <button class="btn btn-outline-primary is-planned" type="button">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Lihat daftar kasus
                </button>
                <button class="btn btn-primary is-planned" type="button">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Cari profil murid
                </button>
                <button class="btn btn-outline-primary is-planned" type="button">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Buka laporan
                </button>
            </div>
            
            <div class="sibk-dashboard-deco" aria-hidden="true">
                <p>Konseling Hari Ini,<br>Masa Depan yang Lebih Baik</p>
                <svg class="sibk-dashboard-deco__leaf" viewBox="0 0 120 160" fill="none">
                    <path d="M60 155 C60 155 5 120 8 60 C10 20 35 5 50 18 C57 24 60 60 60 155Z" fill="currentColor" opacity="0.18"/>
                    <path d="M60 155 C60 155 115 120 112 60 C110 20 85 5 70 18 C63 24 60 60 60 155Z" fill="currentColor" opacity="0.11"/>
                    <path d="M60 155 C60 155 30 110 35 70 C38 48 52 42 60 52 C68 42 82 48 85 70 C90 110 60 155 60 155Z" fill="currentColor" opacity="0.14"/>
                </svg>
            </div>
        </div>
    @endif
</div>
