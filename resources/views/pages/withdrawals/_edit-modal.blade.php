<div class="modal-header border-0 pb-2">
    <h2 class="modal-title fs-5 fw-bold" id="case-modal-title">Edit Pengunduran Diri</h2>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body pt-2">
    <form action="{{ route('withdrawals.update', $withdrawal) }}" method="POST">
        @csrf
        @method('PATCH')

        @if($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="row g-3">
            <div class="col-12 col-md-7">
                <label class="form-label" for="withdrawal-modal-student">Nama / NISN murid</label>
                <input class="form-control" id="withdrawal-modal-student" value="{{ \App\Support\StudentName::display($withdrawal->student?->name) ?: 'Murid tidak tersedia' }} ({{ $withdrawal->student?->nisn ?? 'NISN belum tercatat' }})" readonly aria-describedby="withdrawal-modal-classroom">
                <div class="form-text" id="withdrawal-modal-classroom">{{ $withdrawal->classroom?->name ?? 'Kelas belum tercatat' }}</div>
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label" for="withdrawal-modal-recorded-on">Tanggal pencatatan <span class="text-danger">*</span></label>
                <input class="form-control @error('recorded_on') is-invalid @enderror" id="withdrawal-modal-recorded-on" name="recorded_on" type="date" value="{{ old('recorded_on', $withdrawal->recorded_on->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                @error('recorded_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="withdrawal-modal-note">Catatan <span class="text-danger">*</span></label>
                <textarea class="form-control @error('note') is-invalid @enderror" id="withdrawal-modal-note" name="note" rows="4" maxlength="2000" required>{{ old('note', $withdrawal->note ?? $withdrawal->reason) }}</textarea>
                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
