@extends('layouts.app-2')

@section('page-title', 'Preview Laporan Layanan BK - Ruang BK')

@section('body')
@php
    $documentFilters = array_filter([
        'academic_year_id' => $report['filters']['academic_year_id'],
        'classroom_id' => $report['filters']['classroom_id'],
        'service_type' => $report['filters']['service_type'],
    ], fn ($value) => $value !== null && $value !== '');
    $selectedClassroom = $report['filter_options']['classrooms']
        ->firstWhere('id', $report['filters']['classroom_id']);
    $serviceLabel = match ($report['filters']['service_type']) {
        'case' => 'Catatan Kasus',
        'consultation' => 'Catatan Konsultasi',
        default => 'Semua layanan',
    };
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
            <a
                class="btn btn-outline-primary"
                href="{{ route('reports.export', [...$documentFilters, 'format' => 'doc']) }}"
            >
                Download Word
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
            'subtitle' => implode(' · ', [
                $report['academic_year']?->name ?? 'Tahun ajaran belum tersedia',
                $selectedClassroom?->name ?? 'Semua kelas',
                $serviceLabel,
            ]),
        ])

        <div class="table-responsive">
            <table class="table sibk-table sibk-document-table mb-0">
                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Nama &amp; Kelas</th>
                        <th scope="col">Layanan</th>
                        <th scope="col">Permasalahan</th>
                        <th scope="col">Penanganan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                        <tr>
                            <td>{{ $row['number'] }}</td>
                            <td>{{ $row['date_label'] }}</td>
                            <td>
                                <strong>{{ $row['name'] }}</strong><br>
                                {{ $row['classroom'] }}
                            </td>
                            <td>{{ $row['service'] }}</td>
                            <td>{{ $row['problem'] }}</td>
                            <td>{{ $row['handling'] }}</td>
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
