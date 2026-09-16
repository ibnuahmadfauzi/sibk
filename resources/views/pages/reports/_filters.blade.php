<div class="sibk-panel mb-4 sibk-filter-panel no-print">
    <div class="sibk-panel__body p-4">
        <form class="row g-3 align-items-end" action="{{ route('reports.index') }}" method="GET">
            <input type="hidden" name="tab" value="{{ $report['tab'] }}">

            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label" for="q">Cari nama murid</label>
                <input class="form-control" id="q" name="q" type="search" maxlength="100" autocomplete="off" value="{{ $report['filters']['q'] ?? '' }}" @error('q') aria-invalid="true" aria-describedby="q-error" @enderror>
                @error('q')<div class="invalid-feedback d-block" id="q-error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-sm-6 col-xl-2">
                <label class="form-label" for="academic_year_id">Tahun ajaran</label>
                <select class="form-select" id="academic_year_id" name="academic_year_id" @error('academic_year_id') aria-invalid="true" aria-describedby="academic_year_id-error" @enderror>
                    @foreach($report['filter_options']['academic_years'] as $year)
                        <option value="{{ $year['id'] }}" @selected((int) ($report['filters']['academic_year_id'] ?? 0) === $year['id'])>{{ $year['name'] }}</option>
                    @endforeach
                </select>
                @error('academic_year_id')<div class="invalid-feedback d-block" id="academic_year_id-error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-sm-6 col-xl-2">
                <label class="form-label" for="date_start">Tanggal awal</label>
                <input class="form-control" id="date_start" name="date_start" type="date" value="{{ $report['filters']['date_start'] }}" @error('date_start') aria-invalid="true" aria-describedby="date_start-error" @enderror>
                @error('date_start')<div class="invalid-feedback d-block" id="date_start-error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-sm-6 col-xl-2">
                <label class="form-label" for="date_end">Tanggal akhir</label>
                <input class="form-control" id="date_end" name="date_end" type="date" value="{{ $report['filters']['date_end'] }}" @error('date_end') aria-invalid="true" aria-describedby="date_end-error" @enderror>
                @error('date_end')<div class="invalid-feedback d-block" id="date_end-error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-sm-6 col-xl-2">
                <label class="form-label" for="classroom_id">Kelas</label>
                <select class="form-select" id="classroom_id" name="classroom_id" @error('classroom_id') aria-invalid="true" aria-describedby="classroom_id-error" @enderror>
                    <option value="">Semua kelas</option>
                    @foreach($report['filter_options']['classrooms'] as $classroom)
                        <option value="{{ $classroom['id'] }}" @selected((int) ($report['filters']['classroom_id'] ?? 0) === $classroom['id'])>{{ $classroom['name'] }}</option>
                    @endforeach
                </select>
                @error('classroom_id')<div class="invalid-feedback d-block" id="classroom_id-error">{{ $message }}</div>@enderror
            </div>

            @if($report['tab'] === 'layanan' && $report['filter_options']['counselors'] !== [])
                <div class="col-12 col-sm-6 col-xl-2">
                    <label class="form-label" for="counselor_id">Guru BK</label>
                    <select class="form-select" id="counselor_id" name="counselor_id" @error('counselor_id') aria-invalid="true" aria-describedby="counselor_id-error" @enderror>
                        <option value="">Semua Guru BK</option>
                        @foreach($report['filter_options']['counselors'] as $counselor)
                            <option value="{{ $counselor['id'] }}" @selected((int) ($report['filters']['counselor_id'] ?? 0) === $counselor['id'])>{{ $counselor['name'] }}</option>
                        @endforeach
                    </select>
                    @error('counselor_id')<div class="invalid-feedback d-block" id="counselor_id-error">{{ $message }}</div>@enderror
                </div>
            @endif

            <div class="col-12 col-sm-6 col-xl-auto ms-xl-auto d-flex gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('reports.index', ['tab' => $report['tab']]) }}">Reset</a>
                <button type="submit" class="btn btn-primary">Terapkan</button>
            </div>
        </form>
    </div>
</div>
