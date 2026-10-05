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
            <br>
        </div>
        @if($report['can_view_document'])
            <div class="sibk-page-header__actions no-print">
                <button
                    class="btn btn-primary"
                    type="button"
                    data-bs-toggle="modal"
                    data-bs-target="#report-preview-modal"
                >
                    Cetak / Unduh Rekap
                </button>
            </div>
        @endif
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
        class="sibk-operational-report"
        aria-label="Daftar layanan BK"
    >
        @include('pages.reports._desktop-table')
        @include('pages.reports._mobile-cards')

        @if($report['rows']->isEmpty())
            <div class="p-4 text-center">
                <x-empty-state
                    title="Belum ada catatan layanan"
                    description="Ubah filter untuk melihat catatan permasalahan atau konsultasi lain."
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

    @if($report['can_view_document'])
        <div class="modal fade sibk-report-preview-modal" id="report-preview-modal" tabindex="-1" aria-labelledby="report-preview-title" aria-hidden="true" data-report-preview-modal data-preview-url="{{ route('reports.preview', [...$documentFilters, 'embedded' => 1]) }}">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5 me-auto" id="report-preview-title">Pratinjau Rekap</h2>
                        <a class="btn btn-success text-white" href="{{ route('reports.export', [...$documentFilters, 'format' => 'xlsx']) }}">Unduh Excel</a>
                        <button class="btn btn-primary" type="button" data-report-preview-print disabled>Cetak / Simpan PDF</button>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="m-auto" role="status" data-report-preview-loading>Menyiapkan pratinjau laporan…</p>
                        <div class="m-auto text-center d-none" role="alert" data-report-preview-error>
                            <p>Pratinjau belum dapat ditampilkan.</p>
                            <button class="btn btn-outline-primary" type="button" data-report-preview-retry>Coba lagi</button>
                            <a class="btn btn-link" href="{{ route('reports.preview', $documentFilters) }}" target="_blank" rel="noopener">Buka di halaman baru</a>
                        </div>
                        <iframe class="d-none" title="Dokumen rekap layanan BK" data-report-preview-frame></iframe>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
