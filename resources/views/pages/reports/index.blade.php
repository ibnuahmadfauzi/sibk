@extends('layouts.app-2')

@section('page-title', 'Laporan Operasional - Ruang BK')

@section('body')
@php
    $commonFilters = array_filter([
        'q' => $report['filters']['q'] ?? null,
        'academic_year_id' => $report['filters']['academic_year_id'] ?? null,
        'date_start' => $report['filters']['date_start'] ?? null,
        'date_end' => $report['filters']['date_end'] ?? null,
        'classroom_id' => $report['filters']['classroom_id'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');
    $exportFilters = array_filter([
        ...$report['filters'],
        'page' => null,
        'format' => 'csv',
    ], fn ($value) => $value !== null && $value !== '');
    $statLabels = match ($report['tab']) {
        'pelanggaran' => ['student_count' => 'Murid', 'violation_count' => 'Pelanggaran', 'total_points' => 'Total poin'],
        'layanan' => ['student_count' => 'Murid', 'service_count' => 'Layanan', 'follow_up_case_count' => 'Kasus Tindak Lanjut'],
        'prestasi' => ['student_count' => 'Murid', 'achievement_count' => 'Prestasi', 'verified_count' => 'Terverifikasi'],
    };
@endphp
<div class="sibk-dashboard" data-page-id="PG-301">
    <header class="sibk-page-header mb-4">
        <div class="sibk-page-header__copy">
            <h1>Laporan Operasional</h1>
            <p>Rekap aman per murid sesuai periode dan kewenangan Anda.</p>
        </div>
    </header>

    @if($errors->any())
        <div class="alert alert-danger" role="alert" tabindex="-1">
            <strong>Periksa kembali filter laporan.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->getMessages() as $field => $messages)
                    <li><a href="#{{ $field }}">{{ $messages[0] }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    <nav class="mb-4" aria-label="Jenis laporan operasional">
        <div class="nav sibk-report-tabs" role="tablist">
            @foreach($report['tabs'] as $tab)
                @php
                    $tabFilters = ['tab' => $tab['id'], ...$commonFilters];
                    if ($tab['id'] === 'layanan' && $report['tab'] === 'layanan' && isset($report['filters']['counselor_id'])) {
                        $tabFilters['counselor_id'] = $report['filters']['counselor_id'];
                    }
                @endphp
                <a class="nav-link @if($report['tab'] === $tab['id']) active @endif"
                    href="{{ route('reports.index', $tabFilters) }}"
                    role="tab"
                    @if($report['tab'] === $tab['id']) aria-current="page" aria-selected="true" @else aria-selected="false" @endif>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    @include('pages.reports._filters')

    <section class="sibk-operational-report" aria-labelledby="operational-report-title" data-print-area>
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
            <div>
                <h2 id="operational-report-title" class="h4 mb-1">{{ $report['title'] }}</h2>
                <p class="text-muted mb-0">
                    {{ $report['period_start']->locale('id')->translatedFormat('d M Y') }}–{{ $report['period_end']->locale('id')->translatedFormat('d M Y') }}
                    · {{ $report['academic_year']['name'] ?? 'Tanpa tahun ajaran' }}
                </p>
                <p class="text-muted small mb-0">Dibuat {{ $report['generated_at']->locale('id')->translatedFormat('d M Y H:i') }} oleh {{ $report['generated_by'] }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 no-print sibk-report-actions">
                <button type="button" class="btn btn-outline-secondary" data-print-report>Cetak</button>
                <a class="btn btn-primary" href="{{ route('reports.export', $exportFilters) }}">Unduh CSV</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            @foreach($report['stats'] as $key => $value)
                <div class="col-12 col-sm-4">
                    <article class="sibk-stat-card p-3 h-100">
                        <h3 class="sibk-stat-card__label text-muted small fw-semibold mb-1">{{ $statLabels[$key] }}</h3>
                        <div class="sibk-stat-card__value fs-2 fw-bold text-dark">{{ $value }}</div>
                    </article>
                </div>
            @endforeach
        </div>

        @include('pages.reports._desktop-table')
        @include('pages.reports._mobile-cards')

        @if($report['rows']->isEmpty())
            <div class="sibk-panel p-4 text-center">
                <x-empty-state title="Tidak ada murid pada periode atau filter terpilih" description="Ubah filter atau pilih periode lain untuk melihat rekap." />
                <a class="btn btn-outline-secondary mt-3" href="{{ route('reports.index', ['tab' => $report['tab']]) }}">Reset filter</a>
            </div>
        @endif
    </section>

    @if($report['rows']->hasPages())
        <div class="mt-4 no-print">{{ $report['rows']->links() }}</div>
    @endif
</div>
@endsection
