@php
    $studentLookupData = $students->map(function ($student) {
        $classroom = $student->classMemberships->first()?->classroom;

        return [
            'id' => $student->id,
            'nisn' => $student->nisn,
            'name' => \App\Support\StudentName::display($student->name),
            'classroom' => $classroom?->name ?? 'Rombel belum tercatat',
            'classroom_id' => $classroom?->id,
        ];
    })->values();
@endphp
@if($modal)
    <div class="modal-header border-0 pb-2">
        <div>
            <h2 class="modal-title fs-5 fw-bold" id="case-modal-title">Catat Konsultasi</h2>
            <p class="text-secondary small mb-0">Konsultasi dicatat sebagai layanan yang telah selesai.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
    </div>
    <div class="modal-body pt-2">
@endif
<div data-consultation-create data-consultation-students="{{ $studentLookupData->toJson() }}">
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

<form action="{{ route('consultations.store') }}" method="POST" id="consultation-create-form" data-autosave-form="consultation" data-autosave-record="new">
    @csrf
    <input type="hidden" name="student_id" id="hidden_student_id" value="{{ old('student_id', $preselectedStudentId) }}">

    <div class="alert alert-info d-flex align-items-start gap-2 d-none" data-draft-restored role="status">
        <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="text-info flex-shrink-0 mt-1" aria-hidden="true"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/></svg>
        <div class="small"><strong>Draft dipulihkan.</strong> Isian form ini berasal dari draft tersimpan di perangkat Anda dan belum tersimpan ke server. Klik Kosongkan jika isian ini tidak diperlukan.</div>
    </div>

    <div class="mb-4">
        <div>
            <!-- <div class="d-flex align-items-center gap-3 mb-3">
                <div class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.95rem;">1</div>
                <div>
                    <h2 class="h5 fw-bold text-dark mb-0">Murid dan Layanan</h2>
                    <p class="text-secondary small mb-0 mt-1">Lengkapi data murid dan informasi layanan konsultasi.</p>
                </div>
            </div> -->

            <div class="row g-3">
                <div class="col-12 col-md-4 position-relative">
                    <label for="student_nisn" class="form-label text-dark fw-semibold small mb-2">NISN <span class="text-danger">*</span></label>
                    <input type="text" class="form-control py-2 border-secondary-subtle rounded-3" id="student_nisn" name="temporary_nisn" value="{{ old('temporary_nisn') }}" maxlength="150" pattern="[0-9]+" title="Pilih murid dari daftar atau isi NISN berupa angka." autocomplete="off" placeholder="Ketik NISN atau nama murid" aria-controls="student_lookup_results" aria-autocomplete="list" aria-expanded="false" aria-describedby="student_lookup_hint" required>
                    <div id="student_lookup_results" class="list-group position-absolute start-0 end-0 mx-2 mt-1 shadow-sm d-none" role="listbox" aria-label="Hasil pencarian murid" style="z-index: 1060; max-height: 16rem; overflow-y: auto;"></div>
                </div>
                <div class="col-12 col-md-4">
                    <label for="student_name" class="form-label text-dark fw-semibold small mb-2">Nama Murid <span class="text-danger">*</span></label>
                    <input type="text" class="form-control py-2 border-secondary-subtle rounded-3" id="student_name" name="temporary_name" value="{{ old('temporary_name') }}" maxlength="150" placeholder="Masukkan nama murid" required>
                </div>
                <div class="col-12 col-md-4">
                    <label for="manual_classroom_select" class="form-label text-dark fw-semibold small mb-2">Rombel <span class="text-danger">*</span></label>
                    <select class="form-select py-2 border-secondary-subtle rounded-3" id="manual_classroom_select" name="temporary_classroom_id">
                        <option value="">Pilih rombel yang Anda ampu</option>
                        @foreach($temporaryClassrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected((string) old('temporary_classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- <div class="col-12">
                    <p class="small text-secondary mb-0" id="student_lookup_hint">Cari murid melalui kolom NISN dengan NISN atau nama. Jika tidak ditemukan, isi NISN, nama, dan rombel secara manual.</p>
                </div> -->

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
            </div>
        </div>
    </div>

    <div class="mb-4">
        <div>
            <!-- <div class="d-flex align-items-center gap-3 mb-3">
                <div class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.95rem;">2</div>
                <div>
                    <h2 class="h5 fw-bold text-dark mb-0">Catatan Layanan</h2>
                    <p class="text-secondary small mb-0 mt-1">Tuliskan informasi yang diperlukan untuk mencatat hasil konsultasi.</p>
                </div>
            </div> -->

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="problem" class="form-label text-dark fw-semibold small mb-2">Latar Belakang Masalah / Permasalahan <span class="text-danger">*</span></label>
                    <textarea class="form-control border-secondary-subtle rounded-3 px-3 py-2 small" id="problem" name="problem" rows="4" maxlength="10000" placeholder="Jelaskan kondisi, kronologi, atau alasan murid mendapatkan layanan BK." required>{{ old('problem') }}</textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label for="handling" class="form-label text-dark fw-semibold small mb-2">Penanganan <span class="text-danger">*</span></label>
                    <textarea class="form-control border-secondary-subtle rounded-3 px-3 py-2 small" id="handling" name="handling" rows="4" maxlength="10000" placeholder="Tuliskan tindakan yang dilakukan dalam menangani permasalahan ini." required>{{ old('handling') }}</textarea>
                </div>
                <div class="col-12">
                    <label for="result" class="form-label text-dark fw-semibold small mb-2">Ringkasan / Hasil <span class="text-danger">*</span></label>
                    <textarea class="form-control border-secondary-subtle rounded-3 px-3 py-2 small" id="result" name="result" rows="2" maxlength="10000" placeholder="Tuliskan ringkasan permasalahan dan penanganan secara singkat." required>{{ old('result') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 pb-2">
        <span class="small text-muted me-auto" data-draft-status aria-live="polite"></span>
        <button type="button" class="btn btn-light border border-secondary-subtle px-4 py-2 rounded-3 text-dark fw-medium" data-clear-draft data-clear-fields id="btn-clear-draft">Kosongkan</button>
        @if($modal)
            <button type="button" class="btn btn-outline-primary px-4 py-2 rounded-3 fw-medium" data-bs-dismiss="modal">Batal</button>
        @else
            <a href="{{ route('cases.index', ['tab' => 'konsultasi']) }}" class="btn btn-outline-primary px-4 py-2 rounded-3 fw-medium">Batal</a>
        @endif
        <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm" id="btn-save-consultation">Simpan</button>
    </div>
</form>
</div>
@if($modal)
    </div>
@endif
