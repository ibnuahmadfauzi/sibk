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
    <form class="sibk-report-preview-modal__period border-bottom p-3" data-report-period-form
        data-semester-one-start="{{ $bounds[1][0] }}" data-semester-one-end="{{ $bounds[1][1] }}"
        data-semester-two-start="{{ $bounds[2][0] }}" data-semester-two-end="{{ $bounds[2][1] }}">
        <p class="small mb-2">Tahun ajaran {{ $report['academic_year']?->name }}</p>
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-4">
                <label class="form-label small" for="recap-semester">Semester</label>
                <select class="form-select" id="recap-semester" name="semester" required>
                    <option value="1" @selected($recapPeriod['semester'] === 1) @disabled($bounds[1][0] > $bounds[1][1])>Semester 1 (Juli–Desember)</option>
                    <option value="2" @selected($recapPeriod['semester'] === 2) @disabled($bounds[2][0] > $bounds[2][1])>Semester 2 (Januari–Juni)</option>
                </select>
            </div>
            <div class="col-6 col-sm-4">
                <label class="form-label small" for="recap-date-from">Dari tanggal</label>
                <input class="form-control" type="date" id="recap-date-from" name="date_from" value="{{ $recapPeriod['start'] }}"
                    min="{{ $minimum }}" max="{{ $maximum }}" required aria-describedby="recap-period-help">
            </div>
            <div class="col-6 col-sm-4">
                <label class="form-label small" for="recap-date-to">Sampai tanggal</label>
                <input class="form-control" type="date" id="recap-date-to" name="date_to" value="{{ $recapPeriod['end'] }}"
                    min="{{ $minimum }}" max="{{ $maximum }}" required aria-describedby="recap-period-help">
            </div>
            <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                <p class="small text-muted mb-0" id="recap-period-help">Rentang tanggal harus berada dalam satu semester.</p>
                <button class="btn btn-outline-primary btn-sm" type="submit">Perbarui rekap</button>
            </div>
        </div>
        <p class="small mb-0 mt-2" role="status" data-report-period-status></p>
    </form>
@else
    <p class="alert alert-warning m-3" role="alert" data-report-period-unavailable>{{ $recapPeriodError }}</p>
@endif
