<div class="table-responsive sibk-operational-report-table">
    <table class="table sibk-table align-middle mb-0">
        <thead>
            <tr>
                @foreach($report['columns'] as $column)
                    <th scope="col">{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($report['rows'] as $row)
                @php
                    $problem = $row['problem'] ?: '—';
                    $handling = $row['handling'] ?: '—';
                    $problemPreview = \Illuminate\Support\Str::limit($problem, 80, '...');
                    $handlingPreview = \Illuminate\Support\Str::limit($handling, 80, '...');
                    $resultId = "report-result-{$row['type']}-{$row['record_id']}";
                @endphp
                <tr
                    class="sibk-report-row"
                    data-report-record
                >
                    <td>{{ $row['number'] }}</td>
                    <td>
                        <strong>{{ $row['day_label'] }}</strong>
                        <div class="small text-muted">{{ $row['date_label'] }}</div>
                    </td>
                    <td>
                        <strong>{{ $row['name'] }}</strong>
                        <div class="small text-muted">{{ $row['classroom'] }}</div>
                    </td>
                    <td>
                        {{ $row['service'] }}
                        <div class="small text-muted">{{ $row['service_field'] }}</div>
                    </td>
                    <td>
                        <span data-report-text-preview>{{ $problemPreview }}</span>
                        <span
                            class="d-none"
                            data-report-text-full
                        >{{ $problem }}</span>
                    </td>
                    <td>
                        <span data-report-text-preview>{{ $handlingPreview }}</span>
                        <span
                            class="d-none"
                            data-report-text-full
                        >{{ $handling }}</span>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
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
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 11v5" />
                                    <path d="M12 8h.01" />
                                </svg>
                            </button>
                            <button
                                class="btn btn-sm sibk-icon-button sibk-report-control"
                                type="button"
                                data-report-text-toggle
                                aria-expanded="false"
                                aria-label="Tampilkan teks lengkap {{ $row['name'] }} dengan kaca pembesar"
                                title="Tampilkan teks lengkap (kaca pembesar)"
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
                                        class="btn btn-sm btn-outline-danger sibk-icon-button"
                                        type="submit"
                                        aria-label="Arsipkan {{ $row['name'] }}"
                                        title="Arsipkan"
                                    >
                                        <svg
                                            aria-hidden="true"
                                            viewBox="0 0 24 24"
                                        >
                                            <path d="M3 6h18" />
                                            <path d="M8 6V3h8v3" />
                                            <path d="m19 6-1 15H6L5 6" />
                                            <path d="M10 11v5M14 11v5" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                <tr
                    class="sibk-report-result-row d-none"
                    id="{{ $resultId }}"
                >
                    <td colspan="{{ count($report['columns']) }}">
                        <strong class="d-block mb-1">Hasil Layanan</strong>
                        {{ $row['detail_note'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
