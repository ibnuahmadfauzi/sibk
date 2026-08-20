@extends('layouts.app-2')

@section('page-title', 'Pusat Laporan - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Page Header -->
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Pusat Laporan</h1>
                <p>Pilih jenis laporan yang akan dibuat.</p>
            </div>
        </div>

        <!-- Report Catalog Grid (Matching Penpot PG-301) -->
        <div class="row g-4 sibk-report-grid">
            @foreach($reports as $report)
                <div class="{{ $loop->last && $loop->count % 2 !== 0 ? 'col-12 col-md-6 mx-auto' : 'col-12 col-md-6' }}">
                    <div class="sibk-report-card h-100">
                        <div class="sibk-report-card__body">
                            <div class="sibk-report-card__icon-wrapper" style="background-color: {{ $report['badge_bg'] ?? '#e9f2fb' }}; color: {{ $report['icon_color'] ?? '#2f6fc6' }};">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    {!! $report['icon'] !!}
                                </svg>
                            </div>
                            <div class="sibk-report-card__content">
                                <h3 class="sibk-report-card__title">{{ $report['title'] }}</h3>
                                <p class="sibk-report-card__desc">{{ $report['description'] }}</p>
                            </div>
                        </div>
                        <div class="sibk-report-card__footer">
                            <a href="{{ route('reports.preview', ['type' => $report['id']]) }}" class="sibk-report-card__action" aria-label="Buka {{ $report['title'] }}">
                                <span>Buka</span>
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
