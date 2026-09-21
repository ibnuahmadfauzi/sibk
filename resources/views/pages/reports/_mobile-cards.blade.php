<div class="sibk-operational-report-cards p-3">
    @foreach($report['rows'] as $row)
        <article
            class="sibk-panel sibk-operational-report-card p-3"
            data-modal-url="{{ $row['modal_url'] }}"
            tabindex="0"
        >
            <div class="d-flex justify-content-between gap-3 mb-3">
                <div>
                    <h3 class="h6 mb-1">{{ $row['name'] }}</h3>
                    <p class="small text-muted mb-0">
                        {{ $row['classroom'] }} &middot; {{ $row['date_label'] }}
                    </p>
                </div>
                <span class="sibk-badge">{{ $row['service'] }}</span>
            </div>
            <dl class="mb-3">
                <div class="py-2">
                    <dt>Permasalahan</dt>
                    <dd class="mb-0 sibk-report-text-preview">{{ $row['problem'] }}</dd>
                </div>
                <div class="py-2">
                    <dt>Penanganan</dt>
                    <dd class="mb-0 sibk-report-text-preview">{{ $row['handling'] }}</dd>
                </div>
            </dl>
            <div class="d-flex justify-content-end gap-2">
                <a
                    class="btn btn-sm btn-outline-primary"
                    href="{{ $row['preview_url'] }}"
                >
                    Preview cetak
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
