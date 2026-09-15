@extends('layouts.app-2')

@section('page-title', 'Ganti Kata Sandi - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="ACCOUNT-CHANGE-PASSWORD">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Ganti Kata Sandi</h1>
                <p>{{ $required ? 'Akses lain dibatasi sampai kata sandi sementara diperbarui.' : 'Perbarui kata sandi akun Anda.' }}</p>
            </div>
        </div>

        <section class="sibk-panel">
            <div class="sibk-panel__body p-4">
                @if($errors->any())
                    <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <form method="POST" action="{{ route('account.password.update') }}" class="row g-3">
                    @csrf
                    @method('PATCH')
                    <div class="col-12">
                        <label class="form-label" for="current_password">Kata sandi saat ini</label>
                        <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Kata sandi baru</label>
                        <input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirmation">Konfirmasi kata sandi baru</label>
                        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Simpan Kata Sandi</button></div>
                </form>
            </div>
        </section>
    </div>
@endsection
