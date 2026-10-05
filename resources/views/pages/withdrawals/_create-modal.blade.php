<div class="modal-header border-0 pb-2">
    <h2 class="modal-title fs-5 fw-bold" id="case-modal-title">Catat Pengunduran Diri</h2>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body pt-2" data-withdrawal-create-page data-withdrawal-students="{{ $withdrawalLookup->toJson() }}">
    @if($withdrawalStudents->isEmpty())
        <p class="text-muted mb-0">Semua murid dalam penugasan aktif Anda sudah memiliki catatan pengunduran diri.</p>
    @else
        <form method="POST" action="{{ route('withdrawals.store') }}" data-withdrawal-create-form>
            @csrf
            <input type="hidden" name="student_id" id="withdrawal-student-id" value="{{ old('student_id') }}">
            <div class="row g-3">
                <div class="col-12 col-md-7 position-relative">
                    <label class="form-label" for="withdrawal-student-lookup">Cari nama atau NISN <span class="text-danger">*</span></label>
                    <input class="form-control" id="withdrawal-student-lookup" type="search" autocomplete="off" placeholder="Ketik nama atau NISN murid" aria-controls="withdrawal-student-options" aria-autocomplete="list" aria-expanded="false" required>
                    <div class="list-group position-absolute start-0 end-0 mx-2 mt-1 shadow-sm d-none withdrawal-student-options" id="withdrawal-student-options" role="listbox"></div>
                    <div class="form-text">Pilih murid dari daftar hasil pencarian.</div>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="withdrawal-recorded-on">Tanggal pencatatan <span class="text-danger">*</span></label>
                    <input class="form-control" id="withdrawal-recorded-on" name="recorded_on" type="date" value="{{ old('recorded_on', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                </div>
                <div class="col-12 d-none" data-withdrawal-selected-student aria-live="polite">
                    <div class="rounded-3 border bg-primary-subtle px-3 py-2 small"><strong data-withdrawal-selected-name></strong><span class="text-secondary ms-2" data-withdrawal-selected-classroom></span></div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="withdrawal-note">Catatan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="withdrawal-note" name="note" rows="4" maxlength="2000" required>{{ old('note') }}</textarea>
                    <div class="form-text">Catatan ini mencatat penanganan awal; status murid belum berubah.</div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" data-withdrawal-submit>Simpan</button>
            </div>
        </form>
    @endif
</div>
