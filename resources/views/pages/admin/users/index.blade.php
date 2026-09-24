@extends('layouts.app-2')

@section('page-title', 'Kelola Akun - Ruang BK')

@section('body')
    @php($oldAccountRoles = old('roles', []))
    <div
        class="sibk-dashboard"
        data-page-id="ADMIN-USERS"
        data-account-old-action="{{ old('_account_action', '') }}"
        data-account-old-target="{{ old('_account_target', '') }}"
        data-account-old-roles="{{ implode(',', is_array($oldAccountRoles) ? $oldAccountRoles : []) }}"
    >
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Kelola Akun</h1>
                <p>Buat akun, tetapkan peran, serta atur akses pengguna.</p>
            </div>
            <button
                class="btn btn-primary"
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#accountModal"
                data-account-create
                data-store-url="{{ route('admin.users.store') }}"
            >
                Tambah akun
            </button>
        </div>

        @if(session('success') || $temporaryPasswordResult !== null)
            <div class="sibk-assignment-toast-region" aria-live="polite" aria-atomic="true">
                <div class="toast sibk-assignment-toast" id="accountSuccessToast" role="status">
                    <div class="toast-body d-flex align-items-start gap-2">
                        <span class="sibk-assignment-toast__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="m5 12 4 4L19 6" />
                            </svg>
                        </span>
                        <div class="flex-grow-1">
                            <strong class="d-block">
                                {{ $temporaryPasswordResult !== null ? 'Sandi sementara tersedia' : 'Perubahan berhasil' }}
                            </strong>
                            <span>
                                {{ $temporaryPasswordResult !== null
                                    ? 'Buka detail akun dan salin sandi sekarang.'
                                    : session('success') }}
                            </span>
                        </div>
                        <button
                            class="btn-close"
                            type="button"
                            data-bs-dismiss="toast"
                            aria-label="Tutup pemberitahuan"
                        ></button>
                    </div>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Periksa kembali data akun.</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="sibk-panel" aria-labelledby="account-list-title">
            <div class="sibk-panel__header">
                <div>
                    <h2 class="sibk-panel__title" id="account-list-title">Daftar Akun</h2>
                    <p class="sibk-panel__subtitle">Pilih peran langsung dari tabel atau buka detail akun.</p>
                </div>
                <span class="sibk-badge sibk-badge--primary">{{ $users->total() }} akun</span>
            </div>
            <div class="table-responsive">
                <table class="table sibk-table mb-0 sibk-account-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Peran</th>
                            <th>Sandi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $managedUser)
                            @php
                                $hasTemporaryPassword = $temporaryPasswordResult !== null
                                    && $temporaryPasswordResult->user->is($managedUser);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $managedUser->name }}</strong>
                                    <span class="d-block small text-muted">
                                        {{ $managedUser->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                        @forelse($managedUser->roles as $role)
                                            <button
                                                class="sibk-account-role-chip"
                                                type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#accountRolesModal"
                                                data-account-role-edit
                                                data-account-id="{{ $managedUser->id }}"
                                                data-account-name="{{ $managedUser->name }}"
                                                data-account-url="{{ route('admin.users.update', $managedUser) }}"
                                                data-account-roles="{{ $managedUser->roles->pluck('slug')->implode(',') }}"
                                                aria-label="Ubah peran {{ $managedUser->name }}"
                                            >
                                                {{ $role->name }}
                                            </button>
                                        @empty
                                            <span class="small text-muted">Belum ada peran</span>
                                        @endforelse
                                        <button
                                            class="btn btn-sm p-0 sibk-icon-button sibk-report-control sibk-class-add"
                                            type="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#accountRolesModal"
                                            data-account-role-edit
                                            data-account-id="{{ $managedUser->id }}"
                                            data-account-name="{{ $managedUser->name }}"
                                            data-account-url="{{ route('admin.users.update', $managedUser) }}"
                                            data-account-roles="{{ $managedUser->roles->pluck('slug')->implode(',') }}"
                                            aria-label="Tambah peran untuk {{ $managedUser->name }}"
                                        >
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <path d="M12 5v14M5 12h14" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    @unless(auth()->user()->is($managedUser))
                                        <form
                                            action="{{ route('admin.users.reset-password', $managedUser) }}"
                                            method="POST"
                                        >
                                            @csrf
                                            <button class="btn btn-outline-primary btn-sm" type="submit">
                                                Reset sandi
                                            </button>
                                        </form>
                                    @else
                                        <span class="small text-muted">Akun Anda</span>
                                    @endunless
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <button
                                            class="btn btn-sm p-0 sibk-icon-button sibk-report-control"
                                            type="button"
                                            data-account-detail-toggle
                                            aria-controls="account-detail-{{ $managedUser->id }}"
                                            aria-expanded="{{ $hasTemporaryPassword ? 'true' : 'false' }}"
                                            aria-label="{{ $hasTemporaryPassword ? 'Tutup' : 'Tampilkan' }} detail {{ $managedUser->name }}"
                                            title="{{ $hasTemporaryPassword ? 'Tutup' : 'Tampilkan' }} detail akun"
                                        >
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <circle cx="10.8" cy="10.8" r="6.3" />
                                                <path d="m15.4 15.4 4.3 4.3" />
                                            </svg>
                                        </button>
                                        <button
                                            class="btn btn-sm p-0 sibk-icon-button sibk-report-control"
                                            type="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#accountModal"
                                            data-account-edit
                                            data-account-id="{{ $managedUser->id }}"
                                            data-account-name="{{ $managedUser->name }}"
                                            data-account-email="{{ $managedUser->email }}"
                                            data-account-active="{{ $managedUser->is_active ? '1' : '0' }}"
                                            data-account-url="{{ route('admin.users.update', $managedUser) }}"
                                            data-account-roles="{{ $managedUser->roles->pluck('slug')->implode(',') }}"
                                            aria-label="Edit akun {{ $managedUser->name }}"
                                            title="Edit akun"
                                        >
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <path d="m4 20 4.5-1 10-10a2 2 0 0 0-2.8-2.8l-10 10L4 20Z" />
                                                <path d="m13.8 7.1 3 3" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                class="sibk-report-detail-row {{ $hasTemporaryPassword ? '' : 'd-none' }}"
                                id="account-detail-{{ $managedUser->id }}"
                            >
                                <td colspan="4">
                                    <div class="sibk-report-detail-panel">
                                        <div class="row g-2 small">
                                            <div class="col-12 col-md-6">
                                                <strong>Email</strong>
                                                <span class="d-block">{{ $managedUser->email }}</span>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <strong>Login terakhir</strong>
                                                <span class="d-block">
                                                    {{ $managedUser->last_login_at?->locale('id')->translatedFormat('d M Y H.i') ?? 'Belum tercatat' }}
                                                </span>
                                            </div>
                                            <div class="col-12">
                                                <strong>Sandi</strong>
                                                @if($hasTemporaryPassword)
                                                    <span class="d-block font-monospace user-select-all" aria-label="Sandi sementara">
                                                        {{ $temporaryPasswordResult->plainTextPassword }}
                                                    </span>
                                                    <span class="d-block text-muted">
                                                        Berlaku sampai {{ $temporaryPasswordResult->expiresAt->locale('id')->translatedFormat('d M Y H.i') }}.
                                                        Sampaikan melalui saluran resmi; pengguna wajib menggantinya saat login.
                                                    </span>
                                                @elseif($managedUser->must_change_password)
                                                    <span class="d-block">
                                        @if($managedUser->temporary_password_expires_at?->isPast())
                                            Sandi sementara telah kedaluwarsa.
                                        @else
                                            Sandi sementara aktif sampai
                                            {{ $managedUser->temporary_password_expires_at?->locale('id')->translatedFormat('d M Y H.i') ?? 'waktu tidak tersedia' }}.
                                        @endif
                                                    </span>
                                                @else
                                                    <span class="d-block">Sandi sudah diganti.</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center py-4 text-muted" colspan="4">Belum ada akun.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="p-3">{{ $users->links() }}</div>
            @endif
        </section>

        <div
            class="modal fade"
            id="accountModal"
            tabindex="-1"
            aria-labelledby="accountModalTitle"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="accountModalTitle">Tambah akun</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form id="accountForm" action="{{ route('admin.users.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="_account_action" value="create">
                        <input type="hidden" name="_account_target" value="">
                        <input type="hidden" name="_method" value="PATCH" disabled>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="accountName">Nama</label>
                                <input
                                    class="form-control"
                                    id="accountName"
                                    name="name"
                                    value="{{ old('name', '') }}"
                                    required
                                    maxlength="150"
                                >
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="accountEmail">Email</label>
                                <input
                                    class="form-control"
                                    id="accountEmail"
                                    name="email"
                                    type="email"
                                    value="{{ old('email', '') }}"
                                    required
                                >
                            </div>
                            <div data-account-role-picker>
                                <span class="form-label d-block">Peran</span>
                                <div class="sibk-class-picker-selected mb-3" aria-live="polite">
                                    <span class="small fw-semibold d-block mb-2">Peran dipilih</span>
                                    <div class="d-flex flex-wrap gap-1" data-account-selected></div>
                                    <span class="small text-muted" data-account-placeholder>Belum ada peran dipilih.</span>
                                </div>
                                <div class="sibk-class-picker-list" data-account-options>
                                    @foreach($roles as $role)
                                        <button
                                            class="sibk-class-picker-option"
                                            type="button"
                                            data-account-role-option
                                            data-role-slug="{{ $role->slug }}"
                                            data-role-name="{{ $role->name }}"
                                        >
                                            {{ $role->name }}
                                        </button>
                                    @endforeach
                                </div>
                                <div data-account-inputs></div>
                            </div>
                            <div class="form-check form-switch mt-3">
                                <input type="hidden" name="is_active" value="0">
                                <input
                                    class="form-check-input"
                                    id="accountActive"
                                    name="is_active"
                                    type="checkbox"
                                    role="switch"
                                    value="1"
                                    @checked(old('is_active', '1') == '1')
                                >
                                <label class="form-check-label" for="accountActive">Akun aktif</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-primary" type="submit" data-account-submit disabled>Buat akun</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="accountRolesModal"
            tabindex="-1"
            aria-labelledby="accountRolesTitle"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="accountRolesTitle">Ubah peran</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form id="accountRolesForm" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_account_action" value="roles">
                        <input type="hidden" name="_account_target" value="">
                        <div class="modal-body" data-account-role-picker>
                            <div class="sibk-class-picker-selected mb-3" aria-live="polite">
                                <span class="small fw-semibold d-block mb-2">Peran dipilih</span>
                                <div class="d-flex flex-wrap gap-1" data-account-selected></div>
                                <span class="small text-muted" data-account-placeholder>Belum ada peran dipilih.</span>
                            </div>
                            <span class="form-label d-block">Pilih peran</span>
                            <div class="sibk-class-picker-list" data-account-options>
                                @foreach($roles as $role)
                                    <button
                                        class="sibk-class-picker-option"
                                        type="button"
                                        data-account-role-option
                                        data-role-slug="{{ $role->slug }}"
                                        data-role-name="{{ $role->name }}"
                                    >
                                        {{ $role->name }}
                                    </button>
                                @endforeach
                            </div>
                            <div data-account-inputs></div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-primary" type="submit" data-account-submit disabled>Simpan peran</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
