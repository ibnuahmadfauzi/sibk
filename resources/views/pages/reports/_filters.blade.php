<div class="sibk-panel mb-4 sibk-filter-panel no-print">
    <div class="sibk-panel__body p-4">
        <form
            class="row g-3 align-items-end"
            action="{{ route('reports.index') }}"
            method="GET"
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

            <div class="col-12 col-md-6 col-xl-2">
                <label
                    class="form-label"
                    for="per_page"
                >
                    Data per halaman
                </label>
                <select
                    class="form-select"
                    id="per_page"
                    name="per_page"
                >
                    @foreach([10, 25, 50, 100] as $size)
                        <option
                            value="{{ $size }}"
                            @selected((int) $report['filters']['per_page'] === $size)
                        >
                            {{ $size }} data
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-xl-auto ms-xl-auto d-flex gap-2">
                <a
                    class="btn btn-outline-secondary"
                    href="{{ route('reports.index') }}"
                >
                    Reset
                </a>
                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    Terapkan
                </button>
            </div>
        </form>
    </div>
</div>
