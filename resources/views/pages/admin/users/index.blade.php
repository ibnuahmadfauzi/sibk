@extends('layouts.app-2')

@section('page-title', 'Kelola Akun - Ruang BK')

@section('body')
    @php
        $oldAccountRoles = old('roles', []);
    @endphp
    <div
        class="sibk-dashboard"
        data-page-id="ADMIN-USERS"
        data-current-user-id="{{ auth()->id() }}"
        data-account-old-action="{{ old('_account_action', '') }}"
        data-account-old-target="{{ old('_account_target', '') }}"
        data-account-old-roles="{{ implode(',', is_array($oldAccountRoles) ? $oldAccountRoles : []) }}"
    >
        <div class="sibk-page-header mb-3">
            <div class="sibk-page-header__copy mb-2">
                <h1>Kelola Akun</h1>
                <p>Buat akun, tetapkan peran, dan kelola akses pengguna.</p>
            </div>
            <button
                class="btn btn-primary"
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#accountModal"
                data-account-create
                data-store-url="{{ route('admin.users.store') }}"
            >
                Tambah Akun
            </button>
        </div>

        @if(session('success') || $temporaryPasswordResult !== null)
            <x-notification-toast :title="$temporaryPasswordResult !== null ? 'Sandi sementara tersedia' : 'Perubahan berhasil'">
                {{ $temporaryPasswordResult !== null ? 'Salin sandi sementara pada informasi akun sekarang.' : session('success') }}
            </x-notification-toast>
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

        <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4">
            <form class="row g-3 align-items-end" action="{{ route('admin.users.index') }}" method="GET" data-auto-filter data-filter-reset-url="{{ route('admin.users.index') }}">
                <div class="col-12 col-md">
                    <label class="visually-hidden" for="accountSearch">Cari nama atau email</label>
                    <input class="form-control" id="accountSearch" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari nama atau email..." maxlength="150" data-filter-field>
                </div>
                <div class="col-12 col-md-4">
                    <label class="visually-hidden" for="accountRoleFilter">Peran</label>
                    <select class="form-select" id="accountRoleFilter" name="role" data-filter-field>
                        <option value="">Semua Peran</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->slug }}" @selected($filters['role'] === $role->slug)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto"><button class="btn btn-outline-primary" type="submit" data-filter-action>Filter</button></div>
            </form>
        </div></div>
        <section class="sibk-panel" aria-label="Akun pengguna">
            <div class="table-responsive">
                <table class="table sibk-table mb-0 sibk-account-management-table">
                    <thead>
                        <tr>
                            <x-sort-header name="nama" label="Nama" />
                            <th>Peran</th>
                            <x-sort-header name="status" label="Status" />
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
                                    @if(auth()->user()->is($managedUser))
                                        <div><span class="sibk-badge sibk-badge--primary mt-1">Akun Anda</span></div>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                        @forelse($managedUser->roles as $role)
                                            @if(auth()->user()->is($managedUser))
                                                <span class="sibk-account-role-chip">{{ $role->name }}</span>
                                            @else
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
                                            @endif
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
                                            aria-label="Tambah Peran" title="Tambah Peran"
                                            @disabled(auth()->user()->is($managedUser)
                                                || $managedUser->hasAnyRole(['admin_it', 'waka_kesiswaan']))
                                        >
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <path d="M12 5v14M5 12h14" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <span class="sibk-badge sibk-badge--{{ $managedUser->is_active ? 'success' : 'danger' }}">{{ $managedUser->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <button
                                            class="btn btn-sm p-0 sibk-icon-button sibk-report-control"
                                            type="button"
                                            data-account-detail-toggle
                                            aria-controls="account-detail-{{ $managedUser->id }}"
                                            aria-expanded="{{ $hasTemporaryPassword ? 'true' : 'false' }}"
                                            aria-label="{{ $hasTemporaryPassword ? 'Tutup Informasi' : 'Lihat Informasi' }}"
                                            title="{{ $hasTemporaryPassword ? 'Tutup Informasi' : 'Lihat Informasi' }}"
                                        >
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <circle cx="10.8" cy="10.8" r="6.3" />
                                                <path d="m15.4 15.4 4.3 4.3" />
                                            </svg>
                                        </button>
                                        <form action="{{ route('admin.users.reset-password', $managedUser) }}" method="POST"
                                            data-app-confirm-submit
                                            data-confirm-title="Reset Sandi?"
                                            data-confirm-message="Reset sandi akun"
                                            data-confirm-subject="{{ $managedUser->name }}"
                                            data-confirm-suffix="? Sandi sebelumnya tidak dapat digunakan lagi."
                                            data-confirm-action="Ya, Reset Sandi"
                                            data-confirm-tone="warning">
                                            @csrf
                                            <button class="btn btn-sm p-0 sibk-icon-button sibk-report-control" type="submit" title="Reset Sandi" aria-label="Reset Sandi" @disabled(auth()->user()->is($managedUser))>
                                                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="8" cy="8" r="5"/><path d="m12 12 9 9m-3-3 3-3m-6 0 3-3"/></svg>
                                            </button>
                                        </form>
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
                                            aria-label="Edit Akun"
                                            title="Edit Akun"
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
                                        <div class="row g-3 small">
                                            <div class="col-12 col-md-6 col-lg-4">
                                                <strong>Email</strong>
                                                <a class="d-block text-break" href="mailto:{{ $managedUser->email }}">{{ $managedUser->email }}</a>
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-4">
                                                <strong>Login terakhir</strong>
                                                <span class="d-block">{{ $managedUser->last_login_at ? $managedUser->last_login_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H.i').' WIB' : '' }}</span>
                                                @unless($managedUser->last_login_at)<span>Belum tercatat</span>@endunless
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-4">
                                                <strong>Status sandi</strong>
                                                <span class="d-block">{{ $managedUser->must_change_password ? ($managedUser->temporary_password_expires_at?->isPast() ? 'Sandi sementara kedaluwarsa' : 'Masih menggunakan sandi sementara') : 'Sudah diubah pengguna' }}</span>
                                            </div>
                                        </div>
                                        @if($hasTemporaryPassword)
                                            <div class="mt-3" data-account-secret>
                                                <strong class="d-block small mb-2">Sandi sementara</strong>
                                                <div class="d-flex align-items-center gap-2">
                                                    <code class="user-select-all" data-account-secret-value>{{ $temporaryPasswordResult->plainTextPassword }}</code>
                                                    <button class="btn btn-outline-primary btn-sm" type="button" data-account-copy>Salin</button>
                                                </div>
                                                <p class="small text-muted mt-2 mb-0">Salin sekarang. Sandi hanya ditampilkan sampai informasi ditutup atau halaman ditinggalkan.</p>
                                                <span class="small" role="status" data-account-copy-status></span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center py-4 text-muted" colspan="4">{{ $filters['q'] !== '' || $filters['role'] !== '' ? 'Tidak ada akun yang sesuai filter.' : 'Belum ada akun.' }}</td>
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
                        <h2 class="modal-title fs-5" id="accountModalTitle">Tambah Akun</h2>
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
                            <div class="mb-3 d-none" data-account-status-field>
                                <label class="form-label" for="accountStatus">Status</label>
                                <select class="form-select" id="accountStatus" name="is_active" disabled>
                                    <option value="1" @selected(old('is_active', '1') == '1')>Aktif</option>
                                    <option value="0" @selected(old('is_active', '1') == '0')>Nonaktif</option>
                                </select>
                            </div>
                            <div data-account-role-picker>
                                <span class="form-label d-block">Peran</span>
                                <p class="small text-muted d-none" data-account-role-locked>
                                    Peran akun Anda tidak dapat diubah.
                                </p>
                                <p class="small text-danger d-none" data-account-role-invalid>
                                    Kombinasi peran saat ini tidak sesuai. Lepas peran yang tidak diperlukan.
                                </p>
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
                            <p class="small text-danger d-none" data-account-role-invalid>
                                Kombinasi peran saat ini tidak sesuai. Lepas peran yang tidak diperlukan.
                            </p>
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
