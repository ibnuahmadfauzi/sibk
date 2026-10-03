<div class="modal-header border-0 pb-2">
    <h2 class="modal-title fs-5 fw-bold" id="case-modal-title">Edit Pengunduran Diri</h2>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body pt-2 sibk-case-detail sibk-case-edit">
    <form action="{{ route('withdrawals.update', $withdrawal) }}" method="POST">
        @csrf
        @method('PATCH')

        @if($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="row g-3">
            <div class="col-12">
                <section class="sibk-panel" aria-labelledby="withdrawal-edit-student-title">
                    <div class="sibk-case-detail__heading">
                        <span class="sibk-case-detail__icon text-primary bg-primary-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path stroke-linecap="round" d="M4 21v-2a8 8 0 0116 0v2"/></svg></span>
                        <h3 class="fs-6 fw-bold mb-0" id="withdrawal-edit-student-title">Data Murid</h3>
                    </div>
                    <div class="row g-3 p-3 pt-2">
                        @foreach([
                            'name' => ['Nama Murid', $withdrawal->student?->name ?? '—'],
                            'nisn' => ['NISN', $withdrawal->student?->nisn ?? '—'],
                            'classroom' => ['Rombel/Kelas', $withdrawal->classroom?->name ?? '—'],
                        ] as $key => [$label, $value])
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="withdrawal-modal-{{ $key }}">{{ $label }}</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control pe-5" id="withdrawal-modal-{{ $key }}" value="{{ $value }}" disabled>
                                    <svg class="sibk-case-edit__lock text-secondary" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 018 0v4"/></svg>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="col-12 col-md-5">
                <section class="sibk-panel h-100" aria-labelledby="withdrawal-edit-date-title">
                    <div class="sibk-case-detail__heading">
                        <span class="sibk-case-detail__icon text-success bg-success-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M7 3v4m10-4v4M3 11h18"/></svg></span>
                        <h3 class="fs-6 fw-bold mb-0" id="withdrawal-edit-date-title">Tanggal Pencatatan</h3>
                    </div>
                    <div class="p-3 pt-2">
                        <label class="form-label" for="withdrawal-modal-recorded-on">Tanggal <span class="text-danger">*</span></label>
                        <input class="form-control @error('recorded_on') is-invalid @enderror" id="withdrawal-modal-recorded-on" name="recorded_on" type="date" value="{{ old('recorded_on', $withdrawal->recorded_on->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                        @error('recorded_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </section>
            </div>

            <div class="col-12 col-md-7">
                <section class="sibk-panel h-100" aria-labelledby="withdrawal-edit-note-title">
                    <div class="sibk-case-detail__heading">
                        <span class="sibk-case-detail__icon text-primary bg-primary-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linejoin="round" d="M6 3h8l4 4v14H6zM14 3v5h4"/><path stroke-linecap="round" d="M9 12h6m-6 4h6"/></svg></span>
                        <h3 class="fs-6 fw-bold mb-0" id="withdrawal-edit-note-title">Catatan Pengunduran Diri</h3>
                    </div>
                    <div class="p-3 pt-2">
                        <label class="form-label" for="withdrawal-modal-note">Catatan <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('note') is-invalid @enderror" id="withdrawal-modal-note" name="note" rows="4" maxlength="2000" required>{{ old('note', $withdrawal->note ?? $withdrawal->reason) }}</textarea>
                        @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </section>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
