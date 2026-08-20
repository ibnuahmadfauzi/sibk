@extends('layouts.app-2')

@section('page-title', 'Tambah Akun - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('account.preview') }}" class="btn btn-icon btn-light" aria-label="Kembali">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="sibk-page-header__copy m-0">
                    <h1 class="mb-1">Tambah Akun</h1>
                    <p class="mb-0">Buat akun pengguna baru untuk Ruang BK.</p>
                </div>
            </div>
        </div>

        <form action="#" method="POST">
            <!-- Bagian 1: Identitas Akun -->
            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style="width: 48px; height: 48px;">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="fs-5 mb-1 text-dark fw-bold">Identitas Akun</h4>
                            <p class="text-muted small mb-0">Isi data identitas pengguna yang akan diberikan akun.</p>
                        </div>
                    </div>

                    <div class="row g-4 ms-0 ms-md-5 ps-0 ps-md-2">
                        <div class="col-md-6">
                            <label for="nama_lengkap" class="form-label sibk-form-label text-dark fw-medium">Nama Lengkap</label>
                            <input type="text" class="form-control sibk-form-control bg-light border-0" id="nama_lengkap" placeholder="Masukkan nama lengkap pengguna">
                        </div>
                        <div class="col-md-6">
                            <label for="username" class="form-label sibk-form-label text-dark fw-medium">Nama Pengguna</label>
                            <input type="text" class="form-control sibk-form-control bg-light border-0" id="username" placeholder="Contoh: guru.bk">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label sibk-form-label text-dark fw-medium">Email</label>
                            <input type="email" class="form-control sibk-form-control bg-light border-0" id="email" placeholder="Contoh: pengguna@sekolah.sch.id">
                        </div>
                        <div class="col-md-6">
                            <label for="peran" class="form-label sibk-form-label text-dark fw-medium">Peran</label>
                            <select class="form-select sibk-form-select bg-light border-0" id="peran">
                                <option selected disabled>Pilih peran pengguna</option>
                                <option value="guru_bk">Guru BK</option>
                                <option value="koordinator_bk">Koordinator BK</option>
                                <option value="admin_it">Admin IT</option>
                                <option value="kepala_sekolah">Kepala Sekolah</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Kata Sandi -->
            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning" style="width: 48px; height: 48px;">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="fs-5 mb-1 text-dark fw-bold">Kata Sandi</h4>
                            <p class="text-muted small mb-0">Tetapkan kata sandi awal untuk akun ini. Pengguna dapat mengubahnya setelah masuk.</p>
                        </div>
                    </div>

                    <div class="row g-4 ms-0 ms-md-5 ps-0 ps-md-2">
                        <div class="col-md-6">
                            <label for="password" class="form-label sibk-form-label text-dark fw-medium">Kata Sandi</label>
                            <div class="position-relative">
                                <input type="password" class="form-control sibk-form-control bg-light border-0 pe-5" id="password" placeholder="Minimal 8 karakter">
                                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y pe-3 text-muted p-0 border-0" id="toggle-password" aria-label="Tampilkan kata sandi">
                                    <svg id="icon-eye" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label sibk-form-label text-dark fw-medium">Konfirmasi Kata Sandi</label>
                            <div class="position-relative">
                                <input type="password" class="form-control sibk-form-control bg-light border-0 pe-5" id="password_confirmation" placeholder="Ulangi kata sandi">
                                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y pe-3 text-muted p-0 border-0" id="toggle-password-confirm" aria-label="Tampilkan konfirmasi kata sandi">
                                    <svg id="icon-eye-confirm" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Status Akun -->
            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success" style="width: 48px; height: 48px;">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="fs-5 mb-1 text-dark fw-bold">Status Akun</h4>
                            <p class="text-muted small mb-0">Tentukan status awal akun saat dibuat.</p>
                        </div>
                    </div>

                    <div class="ms-0 ms-md-5 ps-0 ps-md-2">
                        <div class="d-flex flex-column gap-3">
                            <div class="form-check d-flex align-items-center gap-3 p-3 rounded-3 bg-light" style="border: 1.5px solid transparent; cursor: pointer;" id="status-aktif-wrapper">
                                <input class="form-check-input mt-0 flex-shrink-0" type="radio" name="status_akun" id="status_aktif" value="aktif" checked style="width: 20px; height: 20px;">
                                <label class="form-check-label d-flex flex-column gap-0 w-100" for="status_aktif" style="cursor: pointer;">
                                    <span class="fw-semibold text-dark">Aktif</span>
                                    <span class="text-muted small">Akun langsung dapat digunakan setelah dibuat.</span>
                                </label>
                            </div>
                            <div class="form-check d-flex align-items-center gap-3 p-3 rounded-3 bg-light" id="status-nonaktif-wrapper">
                                <input class="form-check-input mt-0 flex-shrink-0" type="radio" name="status_akun" id="status_nonaktif" value="nonaktif" style="width: 20px; height: 20px;">
                                <label class="form-check-label d-flex flex-column gap-0 w-100" for="status_nonaktif" style="cursor: pointer;">
                                    <span class="fw-semibold text-dark">Nonaktif</span>
                                    <span class="text-muted small">Akun dibuat tetapi belum dapat digunakan hingga diaktifkan.</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="d-flex justify-content-end gap-3 mt-4 mb-5">
                <a href="{{ route('account.preview') }}" class="btn fw-bold px-5 py-2 rounded-pill shadow-sm" style="color: #2b5cff; background-color: #ffffff; border: 1px solid #e2e8f0;">Batal</a>
                <button type="submit" class="btn btn-primary fw-bold px-5 py-2 rounded-pill shadow-sm border-0" style="background-color: #2b5cff;">Simpan Akun</button>
            </div>
        </form>
    </div>
@endsection

@section('extra-javascript')
    <script>
        // Toggle show/hide password
        function setupPasswordToggle(toggleId, inputId, iconId) {
            const toggleBtn = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            if (!toggleBtn || !input) return;

            toggleBtn.addEventListener('click', function () {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                const icon = document.getElementById(iconId);
                if (icon) {
                    icon.innerHTML = isPassword
                        ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`
                        : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
                }
            });
        }

        setupPasswordToggle('toggle-password', 'password', 'icon-eye');
        setupPasswordToggle('toggle-password-confirm', 'password_confirmation', 'icon-eye-confirm');
    </script>
@endsection
