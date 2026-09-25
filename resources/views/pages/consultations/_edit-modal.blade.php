<div @if($modal) data-consultation-edit-modal @endif>
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($isEdit)
        <div class="alert alert-warning">Layanan ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?</div>
    @endif

    <form action="{{ $isEdit ? route('consultations.update', $consultation) : route('consultations.store') }}" method="POST" data-autosave-form="consultation" data-autosave-record="{{ $consultation?->id ?? 'new' }}"
        @if($isEdit) data-confirm-submit data-confirm-message="Layanan ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?" @endif>
        @csrf
        @if($isEdit)
            @method('PATCH')
            <input type="hidden" name="expected_updated_at" value="{{ old('expected_updated_at', $consultation->updated_at?->toJSON()) }}">
        @endif

        <div class="sibk-panel mb-4">
            <div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Murid dan Layanan</h2></div>
            <div class="sibk-panel__body p-4 row g-4">
                @if($isEdit)
                    @php($membership = $consultation->student?->classMemberships->first())
                    <div class="col-12">
                        <label class="form-label">Murid</label>
                        <div class="form-control bg-light">{{ $consultation->identityName() }} &mdash; NISN {{ $consultation->identityNisn() }}{{ $consultation->student?->usesProvisionalData($membership) ? ' — Sementara' : '' }}</div>
                    </div>
                @else
                    @php($selectedStudent = $students->firstWhere('id', old('student_id', $preselectedStudentId)))
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="student_lookup">Cari Murid Lokal</label>
                        <input class="form-control" id="student_lookup" list="student-options" value="{{ $selectedStudent ? $selectedStudent->nisn.' — '.$selectedStudent->name : '' }}" autocomplete="off" placeholder="Ketik NISN atau nama">
                        <input type="hidden" id="student_id" name="student_id" value="{{ old('student_id', $preselectedStudentId) }}">
                        <datalist id="student-options">
                            @foreach($students as $student)
                                <option value="{{ $student->nisn }} — {{ $student->name }}" data-id="{{ $student->id }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="temporary_nisn">NISN Sementara</label>
                        <input class="form-control" id="temporary_nisn" name="temporary_nisn" value="{{ old('temporary_nisn') }}" inputmode="numeric" maxlength="20">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="temporary_name">Nama Sementara</label>
                        <input class="form-control" id="temporary_name" name="temporary_name" value="{{ old('temporary_name') }}" maxlength="150">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="temporary_classroom_id">Rombel Murid Sementara</label>
                        <select class="form-select" id="temporary_classroom_id" name="temporary_classroom_id">
                            <option value="">Pilih rombel yang Anda ampu</option>
                            @foreach($temporaryClassrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected((string) old('temporary_classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-12 col-md-6">
                    <label class="form-label" for="session_date">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="session_date" name="session_date" value="{{ old('session_date', $consultation?->session_date?->toDateString() ?? today()->toDateString()) }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="service_field_id">Jenis Masalah <span class="text-danger">*</span></label>
                    <select class="form-select" id="service_field_id" name="service_field_id" required>
                        <option value="">Pilih jenis masalah</option>
                        @foreach($serviceFields as $field)
                            <option value="{{ $field->id }}" @selected((string) old('service_field_id', $consultation?->service_field_id) === (string) $field->id)>{{ $field->label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="sibk-panel mb-4">
            <div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Catatan Layanan</h2></div>
            <div class="sibk-panel__body p-4 row g-4">
                <div class="col-12">
                    <label class="form-label" for="problem">Permasalahan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="problem" name="problem" rows="4" maxlength="10000" required>{{ old('problem', $consultation?->problem) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="handling">Penanganan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="handling" name="handling" rows="4" maxlength="10000" required>{{ old('handling', $consultation?->handling) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="result">Hasil <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="result" name="result" rows="4" maxlength="10000" required>{{ old('result', $consultation?->result) }}</textarea>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-5">
            <span class="small text-muted me-auto align-self-center" data-draft-status aria-live="polite"></span>
            <button type="button" class="btn btn-light" data-clear-draft>Hapus Draft</button>
            <a href="{{ $isEdit ? route('consultations.show', $consultation) : route('cases.index', ['tab' => 'konsultasi']) }}" class="btn btn-outline-secondary">Batal</a>
            <button class="btn btn-primary" type="submit">Simpan</button>
        </div>
    </form>
</div>
