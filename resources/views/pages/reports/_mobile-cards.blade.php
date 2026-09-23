<div class="sibk-operational-report-cards p-3">
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
                    <span
                        class="sibk-badge flex-column align-items-start gap-0"
                    >
                        <span class="small fw-normal">
                            {{ $row['service'] }}
                        </span>
                        <strong>{{ $row['service_field'] }}</strong>
                    </span>
                </div>
                <dl class="mb-0">
                    <div class="py-2">
                        <dt>Ringkasan</dt>
                        <dd class="mb-0">{{ $row['detail_note'] }}</dd>
                    </div>
                    <div class="py-2">
                        <dt>Guru BK</dt>
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
                <span class="sibk-badge flex-column align-items-start gap-0">
                    <span class="small fw-normal">{{ $row['service'] }}</span>
                    <strong>{{ $row['service_field'] }}</strong>
                </span>
            </div>
            <dl class="mb-3">
                <div class="py-2">
                    <dt>Hasil</dt>
                    <dd class="mb-0">{{ $row['detail_note'] }}</dd>
                </div>
            </dl>
            <div
                class="sibk-report-detail-panel d-none mb-3"
                id="{{ $detailId }}"
            >
                <div class="mb-3">
                    <strong class="d-block mb-1">Latar Belakang Masalah</strong>
                    <p class="mb-0">{{ $problem }}</p>
                </div>
                <div>
                    <strong class="d-block mb-1">Penanganan</strong>
                    <p class="mb-0">{{ $handling }}</p>
                </div>
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
                @if($row['can_archive'])
                    <form
                        action="{{ $row['archive_url'] }}"
                        method="POST"
                        data-confirm-submit
                        data-confirm-message="Arsipkan catatan layanan ini?"
                    >
                        @csrf
                        @method('DELETE')
                        <button
                            class="btn btn-sm btn-outline-danger"
                            type="submit"
                        >
                            Arsipkan
                        </button>
                    </form>
                @endif
            </div>
        </article>
    @endforeach
    @endif
</div>
