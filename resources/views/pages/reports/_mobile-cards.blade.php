<div class="sibk-operational-report-cards">
    @if(! $report['can_view_document'])
        @foreach($report['rows'] as $row)
            <article
                class="sibk-panel sibk-operational-report-card p-3"
            >
                <div
                    class="d-flex justify-content-between gap-3 mb-3"
                >
                    <div>
                        <h3 class="h6 mb-1">{{ $row['name'] }}</h3>
                        <p class="small text-muted mb-0">
                            {{ $row['classroom'] }} &middot;
                            {{ $row['day_label'] }}, {{ $row['date_label'] }}
                        </p>
                    </div>
                    @if($report['filters']['service_type'] !== 'withdrawal')
                    <span
                        class="sibk-badge flex-column align-items-start gap-0"
                    >
                        <span class="small fw-normal">
                            {{ $row['service'] }}
                        </span>
                        <strong>{{ $row['service_field'] }}</strong>
                    </span>
                    @endif
                </div>
                <dl class="mb-0">
                    @if($report['filters']['service_type'] !== 'withdrawal')
                    <div class="py-2">
                        <dt>Ringkasan</dt>
                        <dd class="mb-0">{{ $row['detail_note'] }}</dd>
                    </div>
                    @endif
                    <div class="py-2">
                        <dt>{{ $report['filters']['service_type'] === 'withdrawal' ? 'Guru' : 'Guru BK' }}</dt>
                        <dd class="mb-0">{{ $row['counselor'] }}</dd>
                    </div>
                    <div class="py-2">
                        <dt>Keterangan</dt>
                        <dd class="mb-0">{{ $row['follow_up_label'] }}</dd>
                    </div>
                </dl>
            </article>
        @endforeach
    @endif
    @if($report['can_view_document'])
    @foreach($report['rows'] as $row)
        @php
            $problem = $row['problem'] ?: '—';
            $handling = $row['handling'] ?: '—';
            $detailId = "report-card-detail-{$row['type']}-{$row['record_id']}";
        @endphp
        <article class="sibk-panel sibk-operational-report-card p-3">
            <div class="d-flex justify-content-between gap-3 mb-3">
                <div>
                    <h3 class="h6 mb-1">{{ $row['name'] }}</h3>
                    <p class="small text-muted mb-0">
                        {{ $row['classroom'] }} &middot;
                        {{ $row['day_label'] }}, {{ $row['date_label'] }}
                    </p>
                </div>
                @if($row['type'] !== 'withdrawal')
                <span class="sibk-badge flex-column align-items-start gap-0">
                    <span class="small fw-normal">{{ $row['service'] }}</span>
                    <strong>{{ $row['service_field'] }}</strong>
                </span>
                @endif
            </div>
            <dl class="mb-3">
                @if($row['type'] === 'withdrawal')
                <div class="py-2">
                    <dt>Guru</dt>
                    <dd class="mb-0">{{ $row['counselor'] }}</dd>
                </div>
                <div class="py-2">
                    <dt>Keterangan</dt>
                    <dd class="mb-0">{{ $row['follow_up_label'] }}</dd>
                </div>
                @else
                <div class="py-2">
                    <dt>Hasil</dt>
                    <dd class="mb-0">{{ $row['detail_note'] }}</dd>
                </div>
                @endif
            </dl>
            <div
                class="sibk-report-detail-panel d-none mb-3"
                id="{{ $detailId }}"
            >
                @if($row['type'] === 'withdrawal')
                <div>
                    <strong class="d-block mb-1">Catatan</strong>
                    <p class="mb-0">{{ $row['problem'] ?: 'Belum ada catatan.' }}</p>
                </div>
                @else
                <div class="mb-3">
                    <strong class="d-block mb-1">Latar Belakang Masalah</strong>
                    <p class="mb-0">{{ $problem }}</p>
                </div>
                <div>
                    <strong class="d-block mb-1">Penanganan</strong>
                    <p class="mb-0">{{ $handling }}</p>
                </div>
                @endif
            </div>
            <div class="d-flex justify-content-end gap-2">
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
        </article>
    @endforeach
    @endif
</div>
