@extends('layouts.app-2')

@section('page-title', 'Preview ' . $record['service'] . ' - Ruang BK')

@section('body')
<div
    class="sibk-dashboard sibk-report-preview-page sibk-report-preview-page--portrait"
    data-page-id="PG-303"
>
    <div class="sibk-report-preview-actions no-print mb-4">
        <a
            class="btn btn-outline-secondary"
            href="{{ route('reports.index') }}"
        >
            Kembali ke Laporan
        </a>
        <button
            class="btn btn-primary"
            type="button"
            data-print-report
        >
            Cetak / Simpan PDF
        </button>
    </div>

    <article class="sibk-document-sheet sibk-document-sheet--portrait">
        @include('pages.reports.print._letterhead', [
            'title' => $record['service'],
            'subtitle' => $record['date_label'],
        ])

        <dl class="sibk-record-identity">
            <div>
                <dt>Nama</dt>
                <dd>{{ $record['name'] }}</dd>
            </div>
            <div>
                <dt>Kelas</dt>
                <dd>{{ $record['classroom'] }}</dd>
            </div>
            <div>
                <dt>Tanggal</dt>
                <dd>{{ $record['date_label'] }}</dd>
            </div>
            <div>
                <dt>Jenis layanan</dt>
                <dd>{{ $record['service_field'] }}</dd>
            </div>
            <div>
                <dt>Guru BK</dt>
                <dd>{{ $record['counselor'] }}</dd>
            </div>
        </dl>

        <section class="sibk-record-section">
            <h3>Permasalahan</h3>
            <p>{{ $record['problem'] }}</p>
        </section>
        <section class="sibk-record-section">
            <h3>Penanganan</h3>
            <p>{{ $record['handling'] }}</p>
        </section>
        <section class="sibk-record-section">
            <h3>{{ $record['detail_label'] }}</h3>
            <p>{{ $record['detail_note'] }}</p>
        </section>

        @include('pages.reports.print._signature-block', [
            'generatedAt' => now(),
        ])
    </article>
</div>
@endsection
