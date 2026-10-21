@if($recapPeriod)
    @php
        $startYear = (int) substr($recapPeriod['semester_start'], 0, 4) - ($recapPeriod['semester'] === 2 ? 1 : 0);
        $yearStart = $report['academic_year']->starts_on?->toDateString();
        $yearEnd = $report['academic_year']->ends_on?->toDateString();
        $bounds = [
            1 => [max($startYear.'-07-01', $yearStart ?? $startYear.'-07-01'), min($startYear.'-12-31', $yearEnd ?? $startYear.'-12-31')],
            2 => [max(($startYear + 1).'-01-01', $yearStart ?? ($startYear + 1).'-01-01'), min(($startYear + 1).'-06-30', $yearEnd ?? ($startYear + 1).'-06-30')],
        ];
        [$minimum, $maximum] = $bounds[$recapPeriod['semester']];
    @endphp
    <details class="sibk-report-preview-modal__period border-bottom" data-report-period-panel>
        <summary class="sibk-report-preview-modal__period-summary" data-action-tooltip="off">
            <span>Atur periode</span>
            <span class="small" data-report-period-label>{{ \Carbon\CarbonImmutable::parse($recapPeriod['start'])->format('d/m/Y') }} &ndash; {{ \Carbon\CarbonImmutable::parse($recapPeriod['end'])->format('d/m/Y') }}</span>
        </summary>
        <form class="p-3" data-report-period-form
            data-semester-one-start="{{ $bounds[1][0] }}" data-semester-one-end="{{ $bounds[1][1] }}"
            data-semester-two-start="{{ $bounds[2][0] }}" data-semester-two-end="{{ $bounds[2][1] }}">
            <p class="small mb-2 d-none d-sm-block">Tahun ajaran {{ $report['academic_year']?->name }}</p>
            <div class="sibk-report-preview-modal__period-fields">
                <div class="sibk-report-preview-modal__semester">
                    <div class="d-flex align-items-center gap-1">
                        <label class="form-label small mb-0" for="recap-semester">Semester</label>
                        <button class="sibk-report-preview-modal__period-help" type="button" aria-label="Batas periode rekap" aria-describedby="recap-period-help"
                            data-action-tooltip="Rentang tanggal harus berada dalam satu semester." data-report-period-help>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7v1"/></svg>
                        </button>
                        <span class="visually-hidden" id="recap-period-help">Rentang tanggal harus berada dalam satu semester.</span>
                    </div>
                    <select class="form-select" id="recap-semester" name="semester" required>
                        <option value="1" @selected($recapPeriod['semester'] === 1) @disabled($bounds[1][0] > $bounds[1][1])>Semester 1 (Juli–Desember)</option>
                        <option value="2" @selected($recapPeriod['semester'] === 2) @disabled($bounds[2][0] > $bounds[2][1])>Semester 2 (Januari–Juni)</option>
                    </select>
                </div>
                <div class="sibk-report-preview-modal__date">
                    <label class="form-label small" for="recap-date-from">Dari tanggal</label>
                    <input class="form-control" type="date" id="recap-date-from" name="date_from" value="{{ $recapPeriod['start'] }}"
                        min="{{ $minimum }}" max="{{ $maximum }}" required aria-describedby="recap-period-help">
                </div>
                <div class="sibk-report-preview-modal__date">
                    <label class="form-label small" for="recap-date-to">Sampai tanggal</label>
                    <input class="form-control" type="date" id="recap-date-to" name="date_to" value="{{ $recapPeriod['end'] }}"
                        min="{{ $minimum }}" max="{{ $maximum }}" required aria-describedby="recap-period-help">
                </div>
                <button class="btn btn-outline-primary" type="submit">Filter</button>
            </div>
            <p class="small mb-0 mt-2" role="status" data-report-period-status></p>
        </form>
    </details>
@else
    <p class="alert alert-warning m-3" role="alert" data-report-period-unavailable>{{ $recapPeriodError }}</p>
@endif
