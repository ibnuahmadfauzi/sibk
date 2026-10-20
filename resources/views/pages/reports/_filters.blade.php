<div class="sibk-panel mb-4 sibk-filter-panel no-print">
    <div class="sibk-panel__body p-4">
        <form
            id="report-filter-form"
            class="row g-3 align-items-end"
            action="{{ route('reports.index') }}"
            method="GET"
            data-auto-filter
            data-filter-reset-url="{{ route('reports.index') }}"
        >
            <div class="col-12 col-md-6 col-xl-4">
                <label
                    class="form-label"
                    for="academic_year_id"
                >
                    Tahun ajaran
                </label>
                <select
                    class="form-select"
                    id="academic_year_id"
                    name="academic_year_id"
                    data-filter-field
                    data-filter-default="{{ request()->has('academic_year_id') ? '' : ($report['filters']['academic_year_id'] ?? '') }}"
                    data-report-year-filter
                    @error('academic_year_id')
                        aria-invalid="true"
                        aria-describedby="academic_year_id-error"
                    @enderror
                >
                    @foreach($report['filter_options']['academic_years'] as $year)
                        <option
                            value="{{ $year->id }}"
                            @selected((int) ($report['filters']['academic_year_id'] ?? 0) === $year->id)
                        >
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
                @error('academic_year_id')
                    <div
                        class="invalid-feedback d-block"
                        id="academic_year_id-error"
                    >
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label
                    class="form-label"
                    for="classroom_search"
                >
                    Kelas
                </label>
                <input class="form-control" type="search" id="classroom_search"
                    name="classroom_search" value="{{ $report['filters']['classroom_search'] }}"
                    placeholder="Cari kelas" aria-label="Cari kelas" maxlength="100" data-filter-field
                    @error('classroom_search') aria-invalid="true" aria-describedby="classroom_search-error" @enderror>
                @error('classroom_search')
                    <div
                        class="invalid-feedback d-block"
                        id="classroom_search-error"
                    >
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label
                    class="form-label"
                    for="service_type"
                >
                    Jenis layanan BK
                </label>
                <select
                    class="form-select"
                    id="service_type"
                    name="service_type"
                    data-filter-field
                    data-filter-default="case"
                    @error('service_type')
                        aria-invalid="true"
                        aria-describedby="service_type-error"
                    @enderror
                >
                    <option
                        value="case"
                        @selected($report['filters']['service_type'] === 'case')
                    >
                        Catatan Permasalahan
                    </option>
                    <option
                        value="consultation"
                        @selected($report['filters']['service_type'] === 'consultation')
                    >
                        Catatan Konsultasi
                    </option>
                    <option
                        value="withdrawal"
                        @selected($report['filters']['service_type'] === 'withdrawal')
                    >
                        Catatan Pengunduran Diri
                    </option>
                </select>
                @error('service_type')
                    <div
                        class="invalid-feedback d-block"
                        id="service_type-error"
                    >
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label" for="semester">Semester</label>
                <select class="form-select" id="semester" name="semester" data-filter-field data-filter-default="" data-report-semester-filter
                    aria-describedby="report-period-help @error('semester') semester-error @enderror"
                    @error('semester') aria-invalid="true" @enderror>
                    <option value="" @selected((string) old('semester', $report['filters']['semester'] ?? '') === '')>Semua semester (daftar)</option>
                    <option value="1" @selected((string) old('semester', $report['filters']['semester'] ?? '') === '1')>Semester 1 (Juli–Desember)</option>
                    <option value="2" @selected((string) old('semester', $report['filters']['semester'] ?? '') === '2')>Semester 2 (Januari–Juni)</option>
                </select>
                @error('semester')<div class="invalid-feedback d-block" id="semester-error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label" for="date_from">Tanggal mulai</label>
                <input class="form-control" type="date" id="date_from" name="date_from" value="{{ old('date_from', $report['filters']['date_from'] ?? '') }}" data-filter-field data-filter-default=""
                    @if($report['period']) min="{{ $report['period']['semester_start'] }}" max="{{ $report['period']['semester_end'] }}" @endif
                    aria-describedby="report-period-help @error('date_from') date_from-error @enderror"
                    @error('date_from') aria-invalid="true" @enderror>
                @error('date_from')<div class="invalid-feedback d-block" id="date_from-error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label" for="date_to">Tanggal akhir</label>
                <input class="form-control" type="date" id="date_to" name="date_to" value="{{ old('date_to', $report['filters']['date_to'] ?? '') }}" data-filter-field data-filter-default=""
                    @if($report['period']) min="{{ $report['period']['semester_start'] }}" max="{{ $report['period']['semester_end'] }}" @endif
                    aria-describedby="report-period-help @error('date_to') date_to-error @enderror"
                    @error('date_to') aria-invalid="true" @enderror>
                @error('date_to')<div class="invalid-feedback d-block" id="date_to-error">{{ $message }}</div>@enderror
            </div>

            <p class="col-12 small text-muted mb-0" id="report-period-help">Untuk cetak/unduh, pilih satu semester. Tanggal opsional harus berada di semester tersebut.</p>

            <div class="col-12 col-md-6 col-xl-auto">
                <button
                    class="btn btn-outline-primary"
                    type="submit"
                    data-filter-action
                >
                    Filter
                </button>
            </div>
        </form>
    </div>
</div>
