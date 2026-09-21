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
            <p>Daftar catatan kasus dan konsultasi sesuai kewenangan Anda.</p>
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
        <div class="sibk-panel__header p-4 border-bottom">
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

    <div
        class="modal fade"
        id="report-record-modal"
        tabindex="-1"
        aria-labelledby="case-modal-title"
        aria-hidden="true"
        data-service-record-modal
    >
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2
                        class="modal-title fs-5"
                        id="case-modal-title"
                    >
                        Detail Layanan BK
                    </h2>
                    <button
                        class="btn-close"
                        type="button"
                        data-bs-dismiss="modal"
                        aria-label="Tutup"
                    ></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-0">Memuat data&hellip;</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
