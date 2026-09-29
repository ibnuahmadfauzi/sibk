@php
    $manualStudentMode = ! old('student_id', $preselectedStudentId)
        && (old('temporary_nisn') || old('temporary_name') || old('temporary_classroom_id'));
    $selectedClassroom = $selectedStudent?->classMemberships?->first()?->classroom?->name ?? '';
@endphp

@if($errors->any())
    <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-3" role="alert">
        <div class="d-flex align-items-center gap-2 fw-semibold mb-1">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
            </svg>
            Terdapat kesalahan pengisian data:
        </div>
        <ul class="mb-0 ps-4 small">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('consultations.store') }}" method="POST" id="consultation-create-form" data-autosave-form="consultation" data-autosave-record="new" data-manual-mode="{{ $manualStudentMode ? 'true' : 'false' }}">
    @csrf

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.95rem;">1</div>
                <div>
                    <h2 class="h5 fw-bold text-dark mb-0">Murid dan Layanan</h2>
                    <p class="text-secondary small mb-0 mt-1">Lengkapi data murid dan informasi layanan konsultasi.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-4 position-relative">
                    <label for="student_lookup" class="form-label text-dark fw-semibold small mb-2">NISN / Nama Murid <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input class="form-control py-2 pe-5 border-secondary-subtle rounded-3" id="student_lookup" value="{{ $selectedStudent ? $selectedStudent->nisn.' — '.$selectedStudent->name : '' }}" autocomplete="off" placeholder="Ketik NISN atau nama" aria-controls="student_lookup_results" aria-autocomplete="list" aria-expanded="false" @disabled($manualStudentMode)>
                        <svg class="position-absolute top-50 end-0 translate-middle-y me-3 text-secondary" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 5 5"/></svg>
                    </div>
                    <input type="hidden" id="student_id" name="student_id" value="{{ old('student_id', $preselectedStudentId) }}">
                    <div id="student_lookup_results" class="list-group position-absolute start-0 end-0 mx-2 mt-1 shadow border rounded-3 overflow-auto d-none" style="z-index: 20; max-height: 240px;" role="listbox" aria-label="Hasil pencarian murid"></div>
                </div>
                <div class="col-12 col-md-4">
                    <label for="student_name_display" class="form-label text-dark fw-semibold small mb-2">Nama Murid <span class="text-danger">*</span></label>
                    <input class="form-control py-2 border-secondary-subtle rounded-3 bg-light" id="student_name_display" value="{{ $selectedStudent?->name }}" placeholder="Nama murid akan terisi otomatis" readonly>
                </div>
                <div class="col-12 col-md-4">
                    <label for="student_classroom_display" class="form-label text-dark fw-semibold small mb-2">Rombel <span class="text-danger">*</span></label>
                    <input class="form-control py-2 border-secondary-subtle rounded-3 bg-light" id="student_classroom_display" value="{{ $selectedClassroom }}" placeholder="Rombel akan terisi otomatis" readonly>
                </div>

                <div class="col-12 col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-link btn-sm text-decoration-none p-0" id="student_manual_toggle" aria-controls="student_manual_fields" aria-expanded="{{ $manualStudentMode ? 'true' : 'false' }}">
                        {{ $manualStudentMode ? 'Kembali cari murid lokal' : 'Murid belum ada? Isi manual' }}
                    </button>
                </div>
                <div class="col-12 col-md-4">
                    <label for="session_date" class="form-label text-dark fw-semibold small mb-2">Tanggal Layanan <span class="text-danger">*</span></label>
                    <input type="date" class="form-control py-2 border-secondary-subtle rounded-3" id="session_date" name="session_date" value="{{ old('session_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label for="service_field_id" class="form-label text-dark fw-semibold small mb-2">Jenis Masalah <span class="text-danger">*</span></label>
                    <select class="form-select py-2 border-secondary-subtle rounded-3" id="service_field_id" name="service_field_id" required>
                        <option value="">Pilih jenis masalah</option>
                        @foreach($serviceFields as $field)
                            <option value="{{ $field->id }}" @selected((string) old('service_field_id') === (string) $field->id)>{{ $field->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 {{ $manualStudentMode ? '' : 'd-none' }}" id="student_manual_fields">
                    <div class="row g-3 p-3 bg-light border rounded-3">
                        <div class="col-12 col-md-4">
                            <label for="temporary_nisn" class="form-label text-dark fw-semibold small mb-2">NISN Murid Baru <span class="text-danger">*</span></label>
                            <input class="form-control py-2 border-secondary-subtle rounded-3" id="temporary_nisn" name="temporary_nisn" value="{{ old('temporary_nisn') }}" inputmode="numeric" maxlength="20" placeholder="Masukkan NISN">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="temporary_name" class="form-label text-dark fw-semibold small mb-2">Nama Murid Baru <span class="text-danger">*</span></label>
                            <input class="form-control py-2 border-secondary-subtle rounded-3" id="temporary_name" name="temporary_name" value="{{ old('temporary_name') }}" maxlength="150" placeholder="Masukkan nama murid">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="temporary_classroom_id" class="form-label text-dark fw-semibold small mb-2">Rombel Murid Baru <span class="text-danger">*</span></label>
                            <select class="form-select py-2 border-secondary-subtle rounded-3" id="temporary_classroom_id" name="temporary_classroom_id">
                                <option value="">Pilih rombel yang Anda ampu</option>
                                @foreach($temporaryClassrooms as $classroom)
                                    <option value="{{ $classroom->id }}" @selected((string) old('temporary_classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.95rem;">2</div>
                <div>
                    <h2 class="h5 fw-bold text-dark mb-0">Catatan Layanan</h2>
                    <p class="text-secondary small mb-0 mt-1">Tuliskan informasi yang diperlukan untuk mencatat hasil konsultasi.</p>
                </div>
            </div>

            <div class="d-flex flex-column gap-3">
                <div>
                    <label for="problem" class="form-label text-dark fw-semibold small mb-2">Permasalahan <span class="text-danger">*</span></label>
                    <textarea class="form-control border-secondary-subtle rounded-3 px-3 py-2 small" id="problem" name="problem" rows="2" maxlength="10000" placeholder="Tuliskan permasalahan yang dibahas dalam konsultasi ini." required>{{ old('problem') }}</textarea>
                </div>
                <div>
                    <label for="handling" class="form-label text-dark fw-semibold small mb-2">Penanganan <span class="text-danger">*</span></label>
                    <textarea class="form-control border-secondary-subtle rounded-3 px-3 py-2 small" id="handling" name="handling" rows="2" maxlength="10000" placeholder="Tuliskan tindakan yang dilakukan dalam konsultasi ini." required>{{ old('handling') }}</textarea>
                </div>
                <div>
                    <label for="result" class="form-label text-dark fw-semibold small mb-2">Hasil <span class="text-danger">*</span></label>
                    <textarea class="form-control border-secondary-subtle rounded-3 px-3 py-2 small" id="result" name="result" rows="2" maxlength="10000" placeholder="Tuliskan hasil konsultasi secara singkat." required>{{ old('result') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 pb-4">
        <span class="small text-muted me-auto" data-draft-status aria-live="polite"></span>
        <button type="button" class="btn btn-light border border-secondary-subtle px-4 py-2 rounded-3 text-dark fw-medium" data-clear-draft id="btn-clear-draft">Hapus Draft</button>
        <a href="{{ route('cases.index', ['tab' => 'konsultasi']) }}" class="btn btn-link text-secondary text-decoration-none px-3 py-2 fw-medium">Batal</a>
        <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm" id="btn-save-consultation">Simpan</button>
    </div>
</form>
