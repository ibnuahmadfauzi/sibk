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
            <div class="col-12 col-md-6 col-xl">
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

            <div class="col-12 col-md-6 col-xl">
                <label
                    class="form-label"
                    for="classroom_search"
                >
                    Kelas
                </label>
                <input class="form-control" type="search" id="classroom_search"
                    name="classroom_search" value="{{ $report['filters']['classroom_search'] }}"
                    placeholder="Cari kelas" aria-label="Cari kelas" maxlength="100" data-filter-field>
                @error('classroom_search')
                    <div
                        class="invalid-feedback d-block"
                        id="classroom_search-error"
                    >
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="col-12 col-md-6 col-xl">
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
