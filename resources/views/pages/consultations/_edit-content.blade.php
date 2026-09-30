<div class="modal-header border-0 pb-2">
        <h2 class="modal-title fs-5 fw-bold" id="case-modal-title">Edit Konsultasi</h2>
        @if($modal)
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        @endif
    </div>
    <div class="modal-body pt-2 sibk-case-detail sibk-case-edit" @if($modal) data-consultation-edit-modal @endif>
        <form action="{{ route('consultations.update', $consultation) }}" method="POST"
            data-autosave-form="consultation"
            data-autosave-record="{{ $consultation->id }}"
            data-confirm-submit
            data-confirm-message="Layanan ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?">
            @csrf
            @method('PATCH')
            <input type="hidden" name="expected_updated_at" value="{{ old('expected_updated_at', $consultation->updated_at->toJSON()) }}">

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="row g-3">
                <div class="col-12">
                    <section class="sibk-panel" aria-labelledby="consultation-edit-general-title">
                        <div class="sibk-case-detail__heading">
                            <span class="sibk-case-detail__icon text-primary bg-primary-subtle">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v6m0-10v.01"/></svg>
                            </span>
                            <h3 class="fs-6 fw-bold mb-0" id="consultation-edit-general-title">Informasi Umum</h3>
                        </div>
                        <div class="row g-3 p-3 pt-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="consultation-edit-name">Nama Murid</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control pe-5" id="consultation-edit-name" value="{{ $consultation->identityName() }}" disabled>
                                    <svg class="sibk-case-edit__lock text-secondary" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 018 0v4"/></svg>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="consultation-edit-date">Tanggal Layanan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('session_date') is-invalid @enderror" id="consultation-edit-date" name="session_date" value="{{ old('session_date', $consultation->session_date->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                                @error('session_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="consultation-edit-nisn">NISN</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control pe-5" id="consultation-edit-nisn" value="{{ $consultation->identityNisn() ?: '—' }}" disabled>
                                    <svg class="sibk-case-edit__lock text-secondary" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 018 0v4"/></svg>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="consultation-edit-service-field">Jenis Layanan <span class="text-danger">*</span></label>
                                <select class="form-select @error('service_field_id') is-invalid @enderror" id="consultation-edit-service-field" name="service_field_id" required>
                                    @foreach($serviceFields as $field)
                                        <option value="{{ $field->id }}" @selected((string) old('service_field_id', $consultation->service_field_id) === (string) $field->id)>{{ $field->label }}</option>
                                    @endforeach
                                </select>
                                @error('service_field_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="consultation-edit-classroom">Rombel/Kelas</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control pe-5" id="consultation-edit-classroom" value="{{ $consultation->classroom?->name ?? '—' }}" disabled>
                                    <svg class="sibk-case-edit__lock text-secondary" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 018 0v4"/></svg>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="col-12">
                    <section class="sibk-panel" aria-labelledby="consultation-edit-notes-title">
                        <div class="sibk-case-detail__heading">
                            <span class="sibk-case-detail__icon text-primary bg-primary-subtle">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linejoin="round" d="M6 3h8l4 4v14H6zM14 3v5h4"/><path stroke-linecap="round" d="M9 12h6m-6 4h6"/></svg>
                            </span>
                            <h3 class="fs-6 fw-bold mb-0" id="consultation-edit-notes-title">Catatan Permasalahan</h3>
                        </div>
                        <div class="p-3 pt-2">
                            @foreach([
                                'problem' => ['Latar Belakang', true],
                                'handling' => ['Penanganan', true],
                                'result' => ['Ringkasan', true],
                            ] as $field => [$label, $required])
                                <div @class(['mb-3' => !$loop->last])>
                                    <label class="form-label" for="consultation-edit-{{ $field }}">{{ $label }} <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error($field) is-invalid @enderror" id="consultation-edit-{{ $field }}" name="{{ $field }}" rows="3" maxlength="10000" @required($required)>{{ old($field, $consultation->{$field}) }}</textarea>
                                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-end align-items-center gap-2 mt-3">
                <span class="small text-muted me-auto" data-draft-status aria-live="polite"></span>
                @if($modal)
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                @else
                    <a href="{{ route('consultations.show', $consultation) }}" class="btn btn-outline-secondary">Batal</a>
                @endif
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
