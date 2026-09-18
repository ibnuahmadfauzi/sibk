<div class="modal-header">
    <h2 class="modal-title fs-5" id="case-modal-title">Ubah Kasus</h2>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body">
    <form action="{{ route('cases.update', $case) }}" method="POST" data-autosave-form="case" data-autosave-record="{{ $case->id }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="expected_updated_at" value="{{ old('expected_updated_at', $case->updated_at->toJSON()) }}">

        @if($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="mb-3">
            <label class="form-label" for="initial_info">Latar Belakang</label>
            <textarea class="form-control @error('initial_info') is-invalid @enderror" id="initial_info" name="initial_info" rows="5" maxlength="10000" required>{{ old('initial_info', $case->initial_info) }}</textarea>
            @error('initial_info')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="initial_action">Penanganan</label>
            <textarea class="form-control @error('initial_action') is-invalid @enderror" id="initial_action" name="initial_action" rows="5" maxlength="10000" required>{{ old('initial_action', $case->initial_action) }}</textarea>
            @error('initial_action')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="resolution_summary">Catatan Penyelesaian</label>
            <textarea class="form-control @error('resolution_summary') is-invalid @enderror" id="resolution_summary" name="resolution_summary" rows="5" maxlength="10000">{{ old('resolution_summary', $case->resolution_summary) }}</textarea>
            @error('resolution_summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <span class="small text-muted me-auto align-self-center" data-draft-status aria-live="polite"></span>
            <button type="button" class="btn btn-light" data-clear-draft>Hapus Draft</button>
            <a href="{{ route('cases.show', $case) }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" name="action" value="save" class="btn btn-outline-primary">Simpan</button>
            <button type="submit" name="action" value="complete" class="btn btn-primary">Simpan dan Selesaikan</button>
        </div>
    </form>
</div>
