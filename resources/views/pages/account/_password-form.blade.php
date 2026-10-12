@if($errors->any())
    <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ route('account.password.update') }}" class="row g-3">
    @csrf
    @method('PATCH')
    <div class="col-12">
        <label class="form-label" for="current_password">Kata sandi saat ini</label>
        <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
        <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="show-current-password" data-password-visibility="current_password" aria-controls="current_password"><label class="form-check-label small" for="show-current-password">Tampilkan sandi saat ini</label></div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="password">Kata sandi baru</label>
        <input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
        <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="show-new-password" data-password-visibility="password" aria-controls="password"><label class="form-check-label small" for="show-new-password">Tampilkan sandi baru</label></div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="password_confirmation">Konfirmasi kata sandi baru</label>
        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
        <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="show-password-confirmation" data-password-visibility="password_confirmation" aria-controls="password_confirmation"><label class="form-check-label small" for="show-password-confirmation">Tampilkan konfirmasi sandi</label></div>
    </div>
    <div class="col-12 d-flex justify-content-end gap-2">
        @if($modal ?? false)<button class="btn btn-outline-primary" type="button" data-bs-dismiss="modal">Batal</button>@endif
        <button class="btn btn-primary" type="submit">Simpan</button>
    </div>
</form>
