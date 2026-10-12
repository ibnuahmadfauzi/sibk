<div class="modal-header border-0 pb-2">
        <div>
            <h2 class="modal-title fs-5 fw-bold" id="case-modal-title">Edit Konsultasi</h2>
            <p class="text-secondary small mb-0">Perbarui informasi layanan dan catatan konsultasi.</p>
        </div>
        @if($modal)
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        @endif
    </div>
    <div class="modal-body pt-2 sibk-case-detail sibk-case-edit" @if($modal) data-consultation-edit-modal @endif>
        <form action="{{ route('consultations.update', $consultation) }}" method="POST"
            data-autosave-form="consultation"
            data-autosave-record="{{ $consultation->id }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="expected_updated_at" value="{{ old('expected_updated_at', $consultation->updated_at->toJSON()) }}">

            <div class="alert alert-info d-flex align-items-start gap-2 d-none" data-draft-restored role="status">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="text-info flex-shrink-0 mt-1" aria-hidden="true"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/></svg>
                <div class="small"><strong>Draft dipulihkan.</strong> Isian form ini berasal dari draft tersimpan di perangkat Anda dan belum tersimpan ke server.</div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="row g-3 mb-4">
                @foreach([
                    'nisn' => ['NISN', $consultation->identityNisn() ?: '?'],
                    'name' => ['Nama Murid', $consultation->identityName()],
                    'classroom' => ['Rombel', $consultation->classroom?->name ?? '?'],
                ] as $identity => [$label, $value])
                    <div class="col-12 col-md-4">
                        <label class="form-label text-dark fw-semibold small mb-2" for="consultation-edit-{{ $identity }}">{{ $label }}</label>
                        <input type="text" class="form-control py-2 border-secondary-subtle rounded-3" id="consultation-edit-{{ $identity }}" value="{{ $value }}" disabled>
                    </div>
                @endforeach
                <div class="col-12 col-md-4">
                    <label class="form-label text-dark fw-semibold small mb-2" for="consultation-edit-date">Tanggal Layanan <span class="text-danger">*</span></label>
                    <input type="date" class="form-control py-2 border-secondary-subtle rounded-3 @error('session_date') is-invalid @enderror" id="consultation-edit-date" name="session_date" value="{{ old('session_date', $consultation->session_date->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                    @error('session_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label text-dark fw-semibold small mb-2" for="consultation-edit-service-field">Jenis Masalah <span class="text-danger">*</span></label>
                    <select class="form-select py-2 border-secondary-subtle rounded-3 @error('service_field_id') is-invalid @enderror" id="consultation-edit-service-field" name="service_field_id" required>
                        @foreach($serviceFields as $field)
                            <option value="{{ $field->id }}" @selected((string) old('service_field_id', $consultation->service_field_id) === (string) $field->id)>{{ $field->label }}</option>
                        @endforeach
                    </select>
                    @error('service_field_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3 mb-4">
                @foreach([
                    'problem' => ['Latar Belakang Masalah / Permasalahan', 'Jelaskan kondisi, kronologi, atau alasan murid mendapatkan layanan BK.'],
                    'handling' => ['Penanganan', 'Tuliskan tindakan yang dilakukan dalam menangani permasalahan ini.'],
                    'result' => ['Ringkasan / Hasil', 'Tuliskan ringkasan permasalahan dan penanganan secara singkat.'],
                ] as $field => [$label, $placeholder])
                    <div class="col-12 {{ $field === 'result' ? '' : 'col-md-6' }}">
                        <label class="form-label text-dark fw-semibold small mb-2" for="consultation-edit-{{ $field }}">{{ $label }} <span class="text-danger">*</span></label>
                        <textarea class="form-control border-secondary-subtle rounded-3 px-3 py-2 small @error($field) is-invalid @enderror" id="consultation-edit-{{ $field }}" name="{{ $field }}" rows="{{ $field === 'result' ? 2 : 4 }}" maxlength="10000" placeholder="{{ $placeholder }}" required>{{ old($field, $consultation->{$field}) }}</textarea>
                        @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endforeach
            </div>

            <div class="d-flex flex-wrap justify-content-end align-items-center gap-2 mt-3">
                <span class="small text-muted me-auto" data-draft-status aria-live="polite"></span>
                @if($modal)
                    <button type="button" class="btn btn-outline-primary px-4 py-2 rounded-3 fw-medium" data-bs-dismiss="modal">Batal</button>
                @else
                    <a href="{{ route('consultations.show', $consultation) }}" class="btn btn-outline-primary px-4 py-2 rounded-3 fw-medium">Batal</a>
                @endif
                <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
