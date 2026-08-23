@extends('layouts.app-2')

@section('page-title', 'Kelola Akun - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="ADMIN-USERS">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Kelola Akun</h1>
                <p>Buat akun, tetapkan satu atau beberapa peran, serta aktifkan atau nonaktifkan akses pengguna.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Periksa kembali data berikut:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="sibk-panel mb-4">
            <div class="sibk-panel__header">
                <div>
                    <h2 class="sibk-panel__title">Tambah Akun</h2>
                    <p class="sibk-panel__subtitle">Gunakan alamat email resmi dan pilih minimal satu peran.</p>
                </div>
            </div>
            <div class="sibk-panel__body p-4">
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="new-name" class="form-label sibk-form-label">Nama <span class="text-danger">*</span></label>
                            <input id="new-name" class="form-control sibk-form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="new-email" class="form-label sibk-form-label">Email <span class="text-danger">*</span></label>
                            <input id="new-email" class="form-control sibk-form-control" type="email" name="email" value="{{ old('email') }}" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="new-password" class="form-label sibk-form-label">Kata Sandi <span class="text-danger">*</span></label>
                            <input id="new-password" class="form-control sibk-form-control" type="password" name="password" minlength="8" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="new-password-confirmation" class="form-label sibk-form-label">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                            <input id="new-password-confirmation" class="form-control sibk-form-control" type="password" name="password_confirmation" minlength="8" required>
                        </div>
                        <div class="col-12">
                            <span class="form-label sibk-form-label d-block">Peran <span class="text-danger">*</span></span>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($roles as $role)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->slug }}" id="new-role-{{ $role->slug }}" @checked(in_array($role->slug, old('roles', []), true))>
                                        <label class="form-check-label" for="new-role-{{ $role->slug }}">{{ $role->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary px-4" type="submit">Buat Akun</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="sibk-panel">
            <div class="sibk-panel__header">
                <div>
                    <h2 class="sibk-panel__title">Daftar Akun</h2>
                    <p class="sibk-panel__subtitle">Perubahan status menggantikan penghapusan akun permanen.</p>
                </div>
                <span class="sibk-badge sibk-badge--primary">{{ $users->total() }} akun</span>
            </div>
            <div class="sibk-panel__body p-4">
                <div class="d-flex flex-column gap-3">
                    @forelse($users as $managedUser)
                        <form action="{{ route('admin.users.update', $managedUser) }}" method="POST" class="border rounded-3 p-3">
                            @csrf
                            @method('PATCH')
                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                                <div>
                                    <strong>{{ $managedUser->name }}</strong>
                                    <div class="small text-muted">Login terakhir: {{ $managedUser->last_login_at?->locale('id')->translatedFormat('d M Y H.i') ?? 'Belum tercatat' }}</div>
                                </div>
                                <span class="sibk-badge {{ $managedUser->is_active ? 'sibk-badge--success' : 'sibk-badge--warning' }}">
                                    {{ $managedUser->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <div class="row g-3">
                                <div class="col-12 col-lg-4">
                                    <label for="name-{{ $managedUser->id }}" class="form-label sibk-form-label">Nama</label>
                                    <input id="name-{{ $managedUser->id }}" class="form-control sibk-form-control" name="name" value="{{ $managedUser->name }}" required>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <label for="email-{{ $managedUser->id }}" class="form-label sibk-form-label">Email</label>
                                    <input id="email-{{ $managedUser->id }}" class="form-control sibk-form-control" type="email" name="email" value="{{ $managedUser->email }}" required>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <label for="password-{{ $managedUser->id }}" class="form-label sibk-form-label">Kata Sandi Baru</label>
                                    <input id="password-{{ $managedUser->id }}" class="form-control sibk-form-control" type="password" name="password" minlength="8" placeholder="Kosongkan jika tidak diubah">
                                    <input type="password" name="password_confirmation" class="form-control sibk-form-control mt-2" minlength="8" aria-label="Konfirmasi kata sandi baru" placeholder="Konfirmasi kata sandi baru">
                                </div>
                                <div class="col-12 col-lg-8">
                                    <span class="form-label sibk-form-label d-block">Peran</span>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach($roles as $role)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->slug }}" id="role-{{ $managedUser->id }}-{{ $role->slug }}" @checked($managedUser->roles->contains('slug', $role->slug))>
                                                <label class="form-check-label" for="role-{{ $managedUser->id }}-{{ $role->slug }}">{{ $role->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-12 col-lg-4 d-flex align-items-end justify-content-lg-end gap-3">
                                    <input type="hidden" name="is_active" value="0">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="active-{{ $managedUser->id }}" @checked($managedUser->is_active)>
                                        <label class="form-check-label" for="active-{{ $managedUser->id }}">Akun aktif</label>
                                    </div>
                                    <button class="btn btn-primary" type="submit">Simpan</button>
                                </div>
                            </div>
                        </form>
                    @empty
                        <div class="text-center text-muted py-4">Belum ada akun.</div>
                    @endforelse
                </div>

                <div class="mt-4">{{ $users->links() }}</div>
            </div>
        </div>
    </div>
@endsection
