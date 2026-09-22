@extends('layouts.app-2')

@section('page-title', 'Laporan Layanan BK - Ruang BK')

@section('body')
@php
    $documentFilters = array_filter([
        'academic_year_id' => $report['filters']['academic_year_id'],
        'classroom_id' => $report['filters']['classroom_id'],
        'service_type' => $report['filters']['service_type'],
    ], fn ($value) => $value !== null && $value !== '');
@endphp
<div
    class="sibk-dashboard"
    data-page-id="PG-301"
>
    <header class="sibk-page-header mb-4">
        <div class="sibk-page-header__copy">
            <h1>Laporan Layanan BK</h1>
            <p>Daftar catatan permasalahan dan konsultasi sesuai kewenangan Anda.</p>
        </div>
        <div class="sibk-page-header__actions no-print">
            <a
                class="btn btn-primary"
                href="{{ route('reports.preview', $documentFilters) }}"
            >
                Cetak / Unduh Rekap
            </a>
        </div>
    </header>

    @if($errors->any())
        <div
            class="alert alert-danger"
            role="alert"
            tabindex="-1"
        >
            <strong>Periksa kembali filter laporan.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->getMessages() as $field => $messages)
                    <li>
                        <a href="#{{ $field }}">{{ $messages[0] }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('pages.reports._filters')

    <section
        class="sibk-panel sibk-operational-report"
        aria-labelledby="operational-report-title"
    >
        <div
            class="sibk-panel__header p-4 border-bottom d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3"
        >
            <div>
                <h2
                    class="h5 mb-1"
                    id="operational-report-title"
                >
                    Catatan Layanan
                </h2>
                <p class="text-muted small mb-0">
                    Urutan terbaru &middot;
                    {{ $report['academic_year']?->name ?? 'Tahun ajaran belum tersedia' }}
                </p>
            </div>

            <div class="d-flex align-items-center gap-2 no-print">
                <label
                    class="text-muted small text-nowrap"
                    for="per_page"
                >
                    Tampilkan
                </label>
                <select
                    class="form-select form-select-sm"
                    id="per_page"
                    name="per_page"
                    form="report-filter-form"
                    data-report-page-size
                    aria-label="Jumlah data per halaman"
                >
                    @foreach([10, 25, 50, 100] as $size)
                        <option
                            value="{{ $size }}"
                            @selected((int) $report['filters']['per_page'] === $size)
                        >
                            {{ $size }} data
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @include('pages.reports._desktop-table')
        @include('pages.reports._mobile-cards')

        @if($report['rows']->isEmpty())
            <div class="p-4 text-center">
                <x-empty-state
                    title="Belum ada catatan layanan"
                    description="Ubah filter untuk melihat catatan kasus atau konsultasi lain."
                />
                <a
                    class="btn btn-outline-secondary mt-3"
                    href="{{ route('reports.index') }}"
                >
                    Reset filter
                </a>
            </div>
        @endif
    </section>

    @if($report['rows']->hasPages())
        <div class="mt-4 no-print">
            {{ $report['rows']->links() }}
        </div>
    @endif

</div>
@endsection
