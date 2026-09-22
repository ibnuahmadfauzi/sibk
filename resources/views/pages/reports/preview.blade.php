@extends('layouts.app-2')

@section('page-title', 'Preview Laporan Layanan BK - Ruang BK')

@section('body')
@php
    $documentFilters = array_filter([
        'academic_year_id' => $report['filters']['academic_year_id'],
        'classroom_id' => $report['filters']['classroom_id'],
        'service_type' => $report['filters']['service_type'],
    ], fn ($value) => $value !== null && $value !== '');
@endphp
<div
    class="sibk-dashboard sibk-report-preview-page sibk-report-preview-page--landscape"
    data-page-id="PG-302"
>
    <div class="sibk-report-preview-actions no-print mb-4">
        <a
            class="btn btn-outline-secondary"
            href="{{ route('reports.index', $documentFilters) }}"
        >
            Kembali ke Laporan
        </a>
        <div class="d-flex flex-wrap gap-2">
            <a
                class="btn btn-outline-success"
                href="{{ route('reports.export', [...$documentFilters, 'format' => 'xlsx']) }}"
            >
                Download Excel
            </a>
            <button
                class="btn btn-primary"
                type="button"
                data-print-report
            >
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <article class="sibk-document-sheet sibk-document-sheet--landscape">
        @include('pages.reports.print._letterhead', [
            'title' => 'Laporan Layanan Bimbingan dan Konseling',
            'subtitle' => 'Tahun Ajaran '.(
                $report['academic_year']?->name ?? 'belum tersedia'
            ),
        ])

        <p class="sibk-document-summary">
            @foreach($report['summary'] as $item)
                {{ $item['value'] }} {{ $item['label'] }}@unless($loop->last) &middot; @endunless
            @endforeach
        </p>

        <div class="table-responsive">
            <table class="table sibk-table sibk-document-table mb-0">
                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Hari / Tanggal</th>
                        <th scope="col">Nama / Kelas</th>
                        <th scope="col">Jenis Masalah</th>
                        <th scope="col">Ringkasan</th>
                        <th scope="col">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                        <tr>
                            <td>{{ $row['number'] }}</td>
                            <td>
                                <strong class="d-block">
                                    {{ $row['date']->locale('id')->translatedFormat('l') }}
                                </strong>
                                <span class="sibk-document-meta">
                                    {{ $row['date']->locale('id')->translatedFormat('d F Y') }}
                                </span>
                            </td>
                            <td>
                                <strong class="d-block text-uppercase">
                                    {{ $row['name'] }}
                                </strong>
                                <span class="sibk-document-meta">{{ $row['classroom'] }}</span>
                            </td>
                            <td>
                                <span class="sibk-document-meta">{{ $row['service'] }}</span>
                                <strong class="sibk-report-service-field d-block">
                                    {{ $row['service_field'] }}
                                </strong>
                            </td>
                            <td>{{ $row['detail_note'] }}</td>
                            <td>{{ $row['follow_up_label'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                class="text-center py-4"
                                colspan="6"
                            >
                                Tidak ada data sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('pages.reports.print._signature-block', [
            'generatedAt' => $report['generated_at'],
        ])
    </article>
</div>
@endsection
