<div class="modal-header border-0 pb-2">
    <div><h2 class="modal-title fs-5 fw-bold" id="case-modal-title">Edit Permasalahan</h2><p class="text-secondary small mb-0">Perbarui informasi layanan dan catatan permasalahan.</p></div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<form action="{{ route('cases.update', $case) }}" method="POST" data-confirm-unsaved data-modal-size="xl" class="sibk-case-modal-form">
    @csrf
    @method('PATCH')
    <input type="hidden" name="expected_updated_at" value="{{ old('expected_updated_at', $case->updated_at->toJSON()) }}">
    <div class="modal-body pt-2">
        @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="mb-4" aria-labelledby="case-edit-student-title">
            <h3 class="h6 fw-bold mb-3" id="case-edit-student-title">Data Murid</h3>
            <dl class="row g-2 mb-0 small sibk-case-readonly">
                <div class="col-12 col-md-4"><dt class="text-secondary fw-normal">Nama Murid</dt><dd class="fw-semibold mb-0 text-break">{{ $case->identityName() }}</dd></div>
                <div class="col-6 col-md-3"><dt class="text-secondary fw-normal">NISN</dt><dd class="fw-semibold mb-0">{{ $case->identityNisn() ?: '—' }}</dd></div>
                <div class="col-6 col-md-3"><dt class="text-secondary fw-normal">Rombel</dt><dd class="fw-semibold mb-0">{{ $case->classroom?->name ?? '—' }}</dd></div>
                <div class="col-12 col-md-2"><dt class="text-secondary fw-normal">Sumber</dt><dd class="fw-semibold mb-0">{{ $case->source?->label ?? '—' }}</dd></div>
            </dl>
        </section>
        <section class="mb-4 border-top pt-3" aria-labelledby="case-edit-basic-title">
            <h3 class="h6 fw-bold mb-3" id="case-edit-basic-title">Informasi Dasar</h3>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="case-edit-field" class="form-label small fw-semibold">Jenis Masalah</label>
                    <select class="form-select border-secondary-subtle rounded-3 @error('service_field_id') is-invalid @enderror" id="case-edit-field" name="service_field_id" required>
                        @foreach($serviceFields as $serviceField)<option value="{{ $serviceField->id }}" @selected((string) old('service_field_id', $case->service_field_id) === (string) $serviceField->id)>{{ $serviceField->label }}</option>@endforeach
                    </select>
                    @error('service_field_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="case-edit-date" class="form-label small fw-semibold">Tanggal Layanan</label>
                    <input type="date" class="form-control border-secondary-subtle rounded-3 @error('service_date') is-invalid @enderror" id="case-edit-date" name="service_date" value="{{ old('service_date', $case->service_date->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                    @error('service_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>
        <section class="border-top pt-3" aria-labelledby="case-edit-notes-title">
            <h3 class="h6 fw-bold mb-3" id="case-edit-notes-title">Catatan Permasalahan</h3>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="case-edit-initial-info" class="form-label small fw-semibold">Latar Belakang</label>
                    <textarea class="form-control border-secondary-subtle rounded-3 @error('initial_info') is-invalid @enderror" id="case-edit-initial-info" name="initial_info" rows="4" maxlength="10000" required>{{ old('initial_info', $case->initial_info) }}</textarea>
                    @error('initial_info')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="case-edit-initial-action" class="form-label small fw-semibold">Penanganan</label>
                    <textarea class="form-control border-secondary-subtle rounded-3 @error('initial_action') is-invalid @enderror" id="case-edit-initial-action" name="initial_action" rows="4" maxlength="10000" required>{{ old('initial_action', $case->initial_action) }}</textarea>
                    @error('initial_action')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="case-edit-summary" class="form-label small fw-semibold">Ringkasan</label>
                    <textarea class="form-control border-secondary-subtle rounded-3 @error('resolution_summary') is-invalid @enderror" id="case-edit-summary" name="resolution_summary" rows="2" maxlength="10000">{{ old('resolution_summary', $case->resolution_summary) }}</textarea>
                    @error('resolution_summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>
    </div>
    <div class="modal-footer border-top d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="action" value="save" class="btn btn-primary">Simpan</button>
    </div>
</form>
