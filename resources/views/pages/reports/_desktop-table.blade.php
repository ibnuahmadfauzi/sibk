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
                <tr
                    class="sibk-report-row"
                    data-modal-url="{{ $row['modal_url'] }}"
                    tabindex="0"
                >
                    <td>{{ $row['number'] }}</td>
                    <td>
                        <strong>{{ $row['name'] }}</strong>
                        <div class="small text-muted">{{ $row['classroom'] }}</div>
                    </td>
                    <td>
                        {{ $row['service'] }}
                        <div class="small text-muted">{{ $row['date_label'] }}</div>
                    </td>
                    <td>
                        <span
                            class="sibk-report-text-preview"
                            title="{{ $row['problem'] }}"
                        >
                            {{ $row['problem'] }}
                        </span>
                    </td>
                    <td>
                        <span
                            class="sibk-report-text-preview"
                            title="{{ $row['handling'] }}"
                        >
                            {{ $row['handling'] }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <a
                                class="btn btn-sm btn-outline-primary sibk-icon-button"
                                href="{{ $row['preview_url'] }}"
                                aria-label="Preview cetak {{ $row['name'] }}"
                                title="Preview cetak"
                            >
                                <svg
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="M6 9V2h12v7" />
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                                    <rect x="6" y="14" width="12" height="8" />
                                </svg>
                            </a>
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
            @endforeach
        </tbody>
    </table>
</div>
