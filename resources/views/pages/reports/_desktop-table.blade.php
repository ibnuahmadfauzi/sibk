<div class="table-responsive sibk-operational-report-table">
    <table class="table sibk-table align-middle mb-0">
        <thead>
            <tr>
                @foreach($report['columns'] as $column)
                    @if($loop->index === 1)<x-sort-header name="tanggal" :label="$column" />@else<th scope="col">{{ $column }}</th>@endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if(! $report['can_view_document'])
                @foreach($report['rows'] as $row)
                    <tr>
                        <td>{{ $row['number'] }}</td>
                        <td>
                            <strong>{{ $row['day_label'] }}</strong>
                            <div class="small text-muted">{{ $row['date_label'] }}</div>
                        </td>
                        <td>
                            <strong>{{ $row['name'] }}</strong>
                            <div class="small text-muted">{{ $row['classroom'] }}</div>
                        </td>
                        @if($report['filters']['service_type'] !== 'withdrawal')
                        <td>
                            <span class="d-block small text-muted">
                                {{ $row['service'] }}
                            </span>
                            <strong class="sibk-report-service-field d-block">
                                {{ $row['service_field'] }}
                            </strong>
                        </td>
                        <td>{{ $row['detail_note'] }}</td>
                        @endif
                        <td>{{ $row['counselor'] }}</td>
                        <td>{{ $row['follow_up_label'] }}</td>
                    </tr>
                @endforeach
            @endif
            @if($report['can_view_document'])
            @foreach($report['rows'] as $row)
                @php
                    $problem = $row['problem'] ?: '—';
                    $handling = $row['handling'] ?: '—';
                    $detailId = "report-detail-{$row['type']}-{$row['record_id']}";
                @endphp
                <tr class="sibk-report-row">
                    <td>{{ $row['number'] }}</td>
                    <td>
                        <strong>{{ $row['day_label'] }}</strong>
                        <div class="small text-muted">{{ $row['date_label'] }}</div>
                    </td>
                    <td>
                        <strong>{{ $row['name'] }}</strong>
                        <div class="small text-muted">{{ $row['classroom'] }}</div>
                    </td>
                    @if($report['filters']['service_type'] === 'withdrawal')
                        <td>{{ $row['counselor'] }}</td>
                        <td>{{ $row['follow_up_label'] }}</td>
                    @else
                        <td>
                            <span class="d-block small text-muted">{{ $row['service'] }}</span>
                            <strong class="sibk-report-service-field d-block">
                                {{ $row['service_field'] }}
                            </strong>
                        </td>
                        <td>{{ $row['detail_note'] }}</td>
                    @endif
                    <td>
                        <div class="d-flex gap-2">
                            <button
                                class="btn btn-sm sibk-icon-button sibk-report-control"
                                type="button"
                                data-report-detail-toggle
                                data-report-detail-name="{{ $row['name'] }}"
                                aria-controls="{{ $detailId }}"
                                aria-expanded="false"
                                aria-label="Tampilkan detail layanan {{ $row['name'] }}"
                                title="Tampilkan detail layanan"
                            >
                                <svg
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                >
                                    <circle cx="11" cy="11" r="7" />
                                    <path d="m16 16 5 5" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr
                    class="sibk-report-detail-row d-none"
                    id="{{ $detailId }}"
                >
                    <td colspan="{{ count($report['columns']) }}">
                        <div class="sibk-report-detail-panel">
                            @if($row['type'] === 'withdrawal')
                                <strong class="d-block mb-1">Catatan</strong>
                                <p class="mb-0">{{ $row['problem'] ?: 'Belum ada catatan.' }}</p>
                            @else
                                <div class="row g-3">
                                    <div class="col-12 col-lg-6">
                                        <strong class="d-block mb-1">
                                            Latar Belakang Masalah
                                        </strong>
                                        <p class="mb-0">{{ $problem }}</p>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <strong class="d-block mb-1">Penanganan</strong>
                                        <p class="mb-0">{{ $handling }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            @endif
        </tbody>
    </table>
</div>
