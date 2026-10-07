@php
    $defaultSourceCode = $preselectedStudentId && ! $temporaryNisnFilter ? 'murid_datang_sendiri' : 'e_tatib';
    $selectedSourceId = old('case_source_id', request('case_source_id', $caseSources->firstWhere('code', $defaultSourceCode)?->id ?? $caseSources->first()?->id));
    $selectedSourceCode = $caseSources->firstWhere('id', $selectedSourceId)?->code ?? 'e_tatib';
    $preselectedStudent = $studentLookupData->firstWhere('id', $preselectedStudentId);
@endphp
<div class="modal-header border-0 pb-2">
    <div>
        <h2 class="modal-title fs-5 fw-bold" id="case-modal-title">Catat Permasalahan</h2>
        <p class="text-secondary small mb-0">Catat informasi layanan BK secara terstruktur dan lengkap.</p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<form action="{{ route('cases.store') }}" method="POST" id="case-create-form" data-page-id="PG-102" data-case-create-form data-confirm-unsaved data-modal-size="xl" data-case-search-url="{{ route('cases.create', ['temporary_nisn' => $temporaryNisnFilter]) }}" data-case-students="{{ $studentLookupData->toJson() }}" data-case-records="{{ $formattedEtatibRecords->toJson() }}" data-case-pagination="{{ json_encode($etatibPagination ?? ['current_page' => 1, 'last_page' => 1, 'total' => $formattedEtatibRecords->count()]) }}" data-autosave-form="case" data-autosave-record="new">
    @csrf
    <input type="hidden" name="student_id" id="hidden_student_id" value="{{ old('student_id', $preselectedStudentId) }}">
    <input type="hidden" name="etatib_record_id" id="hidden_etatib_record_id" value="{{ old('etatib_record_id', old('etatib_record_ids.0')) }}">
    <input type="hidden" name="temporary_classroom_id" data-case-classroom-value value="{{ old('temporary_classroom_id', $preselectedStudent['classroom_id'] ?? '') }}">
    <div class="modal-body pt-2">
        @if($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="alert alert-info d-none" data-draft-restored role="status"><strong>Draft dipulihkan.</strong> Isian ini belum tersimpan ke server. Klik Kosongkan jika tidak diperlukan.</div>

        <section class="mb-4" aria-labelledby="case-create-basic-title">
            <h3 class="h6 fw-bold mb-3" id="case-create-basic-title">Informasi Dasar</h3>
            <div class="row g-3 align-items-start">
                <div class="col-12 col-md-2">
                    <label for="sumber" class="form-label small fw-semibold">Sumber</label>
                    <select class="form-select border-secondary-subtle rounded-3" id="sumber" name="case_source_id" required>
                        @foreach($caseSources as $source)
                            <option value="{{ $source->id }}" data-code="{{ $source->code }}" @selected((string) $selectedSourceId === (string) $source->id)>{{ $source->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 {{ $selectedSourceCode !== 'e_tatib' ? 'd-none' : '' }}" data-case-etatib-search>
                    <label for="etatib_search_input" class="form-label small fw-semibold">Cari NISN atau nama</label>
                    <div data-case-etatib-query>
                        <input type="search" class="form-control border-secondary-subtle rounded-3" id="etatib_search_input" value="{{ $search }}" placeholder="Cari NISN atau nama murid" autocomplete="off" aria-controls="etatib_results_container">
                    </div>
                    <div class="d-none d-flex align-items-center gap-2" data-case-etatib-selected>
                        <span class="small fw-semibold text-break" data-case-selected-identity></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" data-case-change-etatib>Ganti</button>
                    </div>
                </div>
                <div class="col-12 col-md-4 {{ $selectedSourceCode !== 'rujukan' ? 'd-none' : '' }}" data-case-referrer>
                    <label for="referrer" class="form-label small fw-semibold">Sumber Rujukan</label>
                    <input class="form-control border-secondary-subtle rounded-3" id="referrer" name="referrer" value="{{ old('referrer') }}" placeholder="Nama atau pihak yang merujuk">
                </div>
                <div class="col-12 {{ in_array($selectedSourceCode, ['e_tatib', 'rujukan'], true) ? 'col-md-3' : 'col-md-5' }}" data-case-date-column>
                    <label for="service_date" class="form-label small fw-semibold">Tanggal Layanan</label>
                    <input type="date" class="form-control border-secondary-subtle rounded-3" id="service_date" name="service_date" value="{{ old('service_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                </div>
                <div class="col-12 {{ in_array($selectedSourceCode, ['e_tatib', 'rujukan'], true) ? 'col-md-3' : 'col-md-5' }}" data-case-field-column>
                    <label for="service_field_id" class="form-label small fw-semibold">Jenis Masalah</label>
                    <select class="form-select border-secondary-subtle rounded-3" id="service_field_id" name="service_field_id" required>
                        <option value="">Pilih jenis masalah</option>
                        @foreach($serviceFields as $field)<option value="{{ $field->id }}" @selected((string) old('service_field_id') === (string) $field->id)>{{ $field->label }}</option>@endforeach
                    </select>
                </div>
            </div>
        </section>

        <section class="sibk-case-etatib mb-4 {{ $selectedSourceCode !== 'e_tatib' ? 'd-none' : '' }}" data-case-etatib-section aria-labelledby="case-create-etatib-title">
            <h3 class="h6 fw-bold mb-3" id="case-create-etatib-title">Data e-Tatib</h3>
            <div data-case-etatib-results>
                <div id="etatib_results_container" class="list-group mb-2" aria-live="polite"></div>
                <p class="small text-secondary d-none" data-case-etatib-empty>Tidak ditemukan data e-Tatib yang sesuai.</p>
                <div class="d-flex align-items-center justify-content-between gap-2 small text-secondary" data-case-etatib-pagination><span data-case-etatib-count></span><div class="d-flex gap-1" data-case-etatib-pages></div></div>
            </div>
            <dl class="row g-2 mb-0 small d-none border-top pt-3" data-case-etatib-summary>
                <div class="col-12 col-md-6"><dt class="text-secondary fw-normal">Pelanggaran</dt><dd class="fw-semibold mb-0" data-case-field="violation_type"></dd></div>
                <div class="col-6 col-md-3"><dt class="text-secondary fw-normal">Tanggal Pelanggaran</dt><dd class="fw-semibold mb-0" data-case-field="occurred_at"></dd></div>
                <div class="col-6 col-md-3"><dt class="text-secondary fw-normal">Poin</dt><dd class="fw-semibold mb-0" data-case-field="points"></dd></div>
                <div class="col-6 col-md-3"><dt class="text-secondary fw-normal">Kategori</dt><dd class="fw-semibold mb-0" data-case-field="category"></dd></div>
                <div class="col-6 col-md-5"><dt class="text-secondary fw-normal">Dicatat oleh</dt><dd class="fw-semibold mb-0" data-case-field="recorded_by_name"></dd></div>
                <div class="col-12 col-md-4"><dt class="text-secondary fw-normal">Total Poin Pelanggaran</dt><dd class="fw-semibold mb-0" data-case-field="source_total_points"></dd></div>
            </dl>
            <div class="mt-3 d-none" data-case-etatib-classroom-choice>
                <label for="etatib_classroom_select" class="form-label small fw-semibold">Rombel Layanan</label>
                <select class="form-select border-secondary-subtle rounded-3" id="etatib_classroom_select"><option value="">Pilih rombel yang Anda ampu</option>@foreach($temporaryClassrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select>
                <p class="small text-secondary mb-0 mt-1">Rombel e-Tatib belum cocok dengan penugasan Anda. Pilih rombel layanan untuk identitas sementara.</p>
            </div>
        </section>

        <section class="mb-4 {{ $selectedSourceCode === 'e_tatib' ? 'd-none' : '' }}" data-case-student-section aria-labelledby="case-create-student-title">
            <h3 class="h6 fw-bold mb-3" id="case-create-student-title">Data Murid</h3>
            <div class="row g-3">
                <div class="col-12 col-md-4 position-relative"><label for="student_nisn" class="form-label small fw-semibold">NISN</label><input type="text" class="form-control border-secondary-subtle rounded-3" id="student_nisn" name="temporary_nisn" value="{{ old('temporary_nisn', $preselectedStudent['nisn'] ?? '') }}" maxlength="150" placeholder="Cari NISN atau nama" autocomplete="off" aria-controls="student_lookup_results" aria-autocomplete="list" aria-expanded="false"><div id="student_lookup_results" class="list-group position-absolute start-0 end-0 mx-2 mt-1 shadow-sm d-none sibk-case-suggestions" role="listbox" aria-label="Hasil pencarian murid"></div></div>
                <div class="col-12 col-md-4"><label for="student_name" class="form-label small fw-semibold">Nama Murid</label><input type="text" class="form-control border-secondary-subtle rounded-3" id="student_name" name="temporary_name" value="{{ old('temporary_name', $preselectedStudent['name'] ?? '') }}" maxlength="150" placeholder="Nama murid"></div>
                <div class="col-12 col-md-4"><label for="manual_classroom_select" class="form-label small fw-semibold">Rombel</label><select class="form-select border-secondary-subtle rounded-3" id="manual_classroom_select"><option value="">Pilih rombel yang Anda ampu</option>@foreach($temporaryClassrooms as $classroom)<option value="{{ $classroom->id }}" @selected((string) old('temporary_classroom_id', $preselectedStudent['classroom_id'] ?? '') === (string) $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></div>
                <p class="col-12 small text-secondary mb-0">Pilih murid dari saran, atau isi manual jika belum tersedia pada data master.</p>
            </div>
        </section>

        <section aria-labelledby="case-create-notes-title">
            <h3 class="h6 fw-bold mb-3" id="case-create-notes-title">Catatan Permasalahan</h3>
            <div class="row g-3">
                <div class="col-12 col-md-6"><label for="initial_info" class="form-label small fw-semibold">Latar Belakang</label><textarea class="form-control border-secondary-subtle rounded-3" id="initial_info" name="initial_info" rows="4" maxlength="10000" required>{{ old('initial_info') }}</textarea></div>
                <div class="col-12 col-md-6"><label for="initial_action" class="form-label small fw-semibold">Penanganan</label><textarea class="form-control border-secondary-subtle rounded-3" id="initial_action" name="initial_action" rows="4" maxlength="10000" required>{{ old('initial_action') }}</textarea></div>
                <div class="col-12"><label for="resolution_summary" class="form-label small fw-semibold">Ringkasan</label><textarea class="form-control border-secondary-subtle rounded-3" id="resolution_summary" name="resolution_summary" rows="2" maxlength="10000">{{ old('resolution_summary') }}</textarea></div>
            </div>
        </section>
    </div>
    <div class="modal-footer border-top d-flex gap-2">
        <span class="small text-secondary me-auto" data-draft-status aria-live="polite"></span>
        <button type="button" class="btn btn-light border border-secondary-subtle" data-case-clear data-clear-draft data-clear-fields>Kosongkan</button>
        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </div>
</form>
