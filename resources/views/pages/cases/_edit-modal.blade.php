@php($isEtatib = $case->source?->code === 'e_tatib')
<div class="modal-header border-0 pb-2">
    <h2 class="modal-title fs-5 fw-bold" id="case-modal-title">Edit Kasus BK</h2>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body pt-2 sibk-case-detail sibk-case-edit">
    <form action="{{ route('cases.update', $case) }}" method="POST">
        @csrf
        @method('PATCH')
        <input type="hidden" name="expected_updated_at" value="{{ old('expected_updated_at', $case->updated_at->toJSON()) }}">

        @if($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="row g-3">
            <div class="col-12">
                <section class="sibk-panel" aria-labelledby="case-edit-student-title">
                    <div class="sibk-case-detail__heading justify-content-between flex-wrap">
                        <div class="d-flex align-items-center gap-3">
                            <span class="sibk-case-detail__icon text-primary bg-primary-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path stroke-linecap="round" d="M4 21v-2a8 8 0 0116 0v2"/></svg></span>
                            <h3 class="fs-6 fw-bold mb-0" id="case-edit-student-title">Data Murid</h3>
                        </div>
                        <div class="small"><span class="text-muted">Sumber:</span> <strong>{{ $case->source?->label ?? '—' }}</strong></div>
                    </div>
                    <div class="row g-3 p-3 pt-2">
                        @foreach(['nisn' => ['NISN', $case->identityNisn() ?: '—'], 'name' => ['Nama Murid', $case->identityName()], 'classroom' => ['Rombel', $case->classroom?->name ?? '—']] as $key => [$label, $value])
                            <div class="col-12 col-sm-4">
                                <label class="form-label" for="case-edit-{{ $key }}">{{ $label }}</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control pe-5" id="case-edit-{{ $key }}" value="{{ $value }}" disabled>
                                    <svg class="sibk-case-edit__lock text-secondary" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 018 0v4"/></svg>
                                </div>
                            </div>
                        @endforeach
                        @if($isEtatib)
                            <p class="col-12 small text-muted mb-0">Data murid diisi otomatis dari e-Tatib dan tidak dapat diubah.</p>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-sm-6">
                <section class="sibk-panel h-100" aria-labelledby="case-edit-field-title">
                    <div class="sibk-case-detail__heading">
                        <span class="sibk-case-detail__icon text-primary bg-primary-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linejoin="round" d="M12 3l9 5-9 5-9-5zM3 12l9 5 9-5M3 16l9 5 9-5"/></svg></span>
                        <h3 class="fs-6 fw-bold mb-0" id="case-edit-field-title">Jenis Masalah</h3>
                    </div>
                    <div class="p-3 pt-2">
                        <select class="form-select @error('service_field_id') is-invalid @enderror" name="service_field_id" aria-labelledby="case-edit-field-title" required>
                            @foreach($serviceFields as $serviceField)
                                <option value="{{ $serviceField->id }}" @selected((string) old('service_field_id', $case->service_field_id) === (string) $serviceField->id)>{{ $serviceField->label }}</option>
                            @endforeach
                        </select>
                        @error('service_field_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </section>
            </div>
            <div class="col-12 col-sm-6">
                <section class="sibk-panel h-100" aria-labelledby="case-edit-date-title">
                    <div class="sibk-case-detail__heading">
                        <span class="sibk-case-detail__icon text-success bg-success-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M7 3v4m10-4v4M3 11h18"/></svg></span>
                        <h3 class="fs-6 fw-bold mb-0" id="case-edit-date-title">Tanggal Layanan</h3>
                    </div>
                    <div class="p-3 pt-2">
                        <input type="date" class="form-control @error('service_date') is-invalid @enderror" name="service_date" value="{{ old('service_date', $case->service_date->toDateString()) }}" max="{{ today()->toDateString() }}" aria-labelledby="case-edit-date-title" required>
                        @error('service_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </section>
            </div>
            <div class="col-12">
                <section class="sibk-panel" aria-labelledby="case-edit-notes-title">
                    <div class="sibk-case-detail__heading">
                        <span class="sibk-case-detail__icon text-primary bg-primary-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linejoin="round" d="M6 3h8l4 4v14H6zM14 3v5h4"/><path stroke-linecap="round" d="M9 12h6m-6 4h6"/></svg></span>
                        <h3 class="fs-6 fw-bold mb-0" id="case-edit-notes-title">Catatan Permasalahan</h3>
                    </div>
                    <div class="p-3 pt-2">
                        @foreach(['initial_info' => ['Latar Belakang', true], 'initial_action' => ['Penanganan', true], 'resolution_summary' => ['Ringkasan', false]] as $field => [$label, $required])
                            <div @class(['mb-3' => !$loop->last])>
                                <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                                <textarea class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" rows="3" maxlength="10000" @required($required)>{{ old($field, $case->{$field}) }}</textarea>
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
            @if(request()->boolean('modal'))
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            @else
                <a href="{{ route('cases.show', $case) }}" class="btn btn-outline-secondary">Batal</a>
            @endif
            <button type="submit" name="action" value="save" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
