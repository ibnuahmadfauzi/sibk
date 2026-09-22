@php
    $hasActiveFilters = request()->query->has('academic_year_id')
        || request()->query->has('classroom_id')
        || request()->query->has('service_type');
@endphp

<div class="sibk-panel mb-4 sibk-filter-panel no-print">
    <div class="sibk-panel__body p-4">
        <form
            id="report-filter-form"
            class="row g-3 align-items-end"
            action="{{ route('reports.index') }}"
            method="GET"
            data-report-filter-form
            data-filters-active="{{ $hasActiveFilters ? 'true' : 'false' }}"
        >
            <div class="col-12 col-md-6 col-xl-3">
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
                    data-report-filter
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

            <div class="col-12 col-md-6 col-xl-3">
                <label
                    class="form-label"
                    for="classroom_id"
                >
                    Kelas
                </label>
                <select
                    class="form-select"
                    id="classroom_id"
                    name="classroom_id"
                    data-report-filter
                    @error('classroom_id')
                        aria-invalid="true"
                        aria-describedby="classroom_id-error"
                    @enderror
                >
                    <option value="">Semua kelas</option>
                    @foreach($report['filter_options']['classrooms'] as $classroom)
                        <option
                            value="{{ $classroom->id }}"
                            @selected((int) ($report['filters']['classroom_id'] ?? 0) === $classroom->id)
                        >
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
                @error('classroom_id')
                    <div
                        class="invalid-feedback d-block"
                        id="classroom_id-error"
                    >
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="col-12 col-md-6 col-xl-3">
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
                    data-report-filter
                    @error('service_type')
                        aria-invalid="true"
                        aria-describedby="service_type-error"
                    @enderror
                >
                    <option
                        value="all"
                        @selected($report['filters']['service_type'] === 'all')
                    >
                        Semua layanan
                    </option>
                    <option
                        value="case"
                        @selected($report['filters']['service_type'] === 'case')
                    >
                        Catatan Kasus
                    </option>
                    <option
                        value="consultation"
                        @selected($report['filters']['service_type'] === 'consultation')
                    >
                        Catatan Konsultasi
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

            <div class="col-12 col-xl-auto ms-xl-auto d-flex gap-2">
                <button
                    class="btn {{ $hasActiveFilters ? 'btn-outline-primary' : 'btn-primary' }}"
                    type="submit"
                    data-report-filter-action
                    data-mode="{{ $hasActiveFilters ? 'reset' : 'apply' }}"
                    data-reset-url="{{ route('reports.index', ['per_page' => $report['filters']['per_page']]) }}"
                >
                    {{ $hasActiveFilters ? 'Reset' : 'Terapkan' }}
                </button>
            </div>
        </form>
    </div>
</div>
