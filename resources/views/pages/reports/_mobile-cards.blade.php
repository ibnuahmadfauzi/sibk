<div class="sibk-operational-report-cards p-3">
    @foreach($report['rows'] as $row)
        @php
            $problem = $row['problem'] ?: '—';
            $handling = $row['handling'] ?: '—';
            $problemPreview = \Illuminate\Support\Str::limit($problem, 80, '...');
            $handlingPreview = \Illuminate\Support\Str::limit($handling, 80, '...');
            $resultId = "report-card-result-{$row['type']}-{$row['record_id']}";
        @endphp
        <article
            class="sibk-panel sibk-operational-report-card p-3"
            data-report-record
        >
            <div class="d-flex justify-content-between gap-3 mb-3">
                <div>
                    <h3 class="h6 mb-1">{{ $row['name'] }}</h3>
                    <p class="small text-muted mb-0">
                        {{ $row['classroom'] }} &middot;
                        {{ $row['day_label'] }}, {{ $row['date_label'] }}
                    </p>
                </div>
                <span class="sibk-badge">
                    {{ $row['service'] }} / {{ $row['service_field'] }}
                </span>
            </div>
            <dl class="mb-3">
                <div class="py-2">
                    <dt>Latar Belakang Masalah</dt>
                    <dd class="mb-0">
                        <span data-report-text-preview>{{ $problemPreview }}</span>
                        <span
                            class="d-none"
                            data-report-text-full
                        >{{ $problem }}</span>
                    </dd>
                </div>
                <div class="py-2">
                    <dt>Penanganan</dt>
                    <dd class="mb-0">
                        <span data-report-text-preview>{{ $handlingPreview }}</span>
                        <span
                            class="d-none"
                            data-report-text-full
                        >{{ $handling }}</span>
                    </dd>
                </div>
            </dl>
            <div
                class="sibk-report-result-detail d-none mb-3 p-3"
                id="{{ $resultId }}"
            >
                <strong class="d-block mb-1">Hasil Layanan</strong>
                {{ $row['detail_note'] }}
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button
                    class="btn btn-sm sibk-icon-button sibk-report-control"
                    type="button"
                    data-report-text-toggle
                    aria-expanded="false"
                    aria-label="Tampilkan teks lengkap {{ $row['name'] }}"
                    title="Tampilkan teks lengkap"
                >
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 24 24"
                    >
                        <circle cx="11" cy="11" r="7" />
                        <path d="m16 16 5 5" />
                    </svg>
                </button>
                <button
                    class="btn btn-sm sibk-icon-button sibk-report-control"
                    type="button"
                    data-report-result-toggle
                    aria-controls="{{ $resultId }}"
                    aria-expanded="false"
                    aria-label="Tampilkan hasil layanan {{ $row['name'] }}"
                    title="Tampilkan hasil layanan"
                >
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 24 24"
                    >
                        <path d="m6 9 6 6 6-6" />
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
</div>
