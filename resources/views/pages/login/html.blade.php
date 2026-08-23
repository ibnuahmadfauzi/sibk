<main class="sibk-auth" id="main-content" data-page-id="PG-001">

    {{-- Panel kiri: identitas dan ilustrasi --}}
    <section class="sibk-auth-brand" aria-labelledby="auth-brand-title">
        <div class="sibk-auth-brand__inner">

            <a class="sibk-auth-brand__identity" href="{{ route('login') }}"
                aria-label="Aplikasi BK, kembali ke login">
                <x-logo aria-hidden="true" width="36" height="36" />
                <span>Ruang BK</span>
            </a>

            <div class="sibk-auth-brand__copy">
                <h1 id="auth-brand-title">Pencatatan layanan BK yang terstruktur dan sesuai kewenangan.</h1>
                <div class="sibk-auth-brand__accent" aria-hidden="true"></div>
                <p>Kelola layanan bimbingan dan konseling dengan mudah, aman, dan terorganisir dalam satu sistem.</p>
            </div>

            <div class="sibk-auth-illustration" aria-hidden="true">
                <img src="{{ asset('assets/images/illustration-consultation.png') }}" alt=""
                    width="520" height="380" loading="lazy">
            </div>

            <p class="sibk-auth-brand__note">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.6 2.9 8.5 7 10 4.1-1.5 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                Gunakan akun yang telah ditetapkan oleh sekolah.
            </p>

        </div>
    </section>

    {{-- Panel kanan: form login --}}
    <section class="sibk-auth-form-area" aria-labelledby="login-title">
        <div class="sibk-auth-card card border-0">

            {{-- Logo di atas form --}}
            <div class="sibk-auth-card__logo" aria-hidden="true">
                <x-logo width="56" height="56" />
            </div>

            {{-- Ringkasan error validasi --}}
            <div class="alert sibk-form-summary" id="loginSummary" role="alert" tabindex="-1" @if (! $errors->any()) hidden @endif>
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01"/></svg>
                <div>
                    <strong id="loginSummaryTitle">Periksa kembali isian Anda</strong>
                    <p id="loginSummaryMessage">{{ $errors->first() }}</p>
                </div>
            </div>

            {{-- Notifikasi berhasil (state statis) --}}
            <div class="alert sibk-auth-success" id="loginSuccess" role="status" tabindex="-1" hidden>
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                <div>
                    <strong>Identitas Anda diterima.</strong>
                    <p>Sebentar lagi Anda akan diarahkan ke ruang kerja.</p>
                    <a class="btn btn-sm btn-outline-success mt-2"
                        href="{{ route('dashboard.preview', ['role' => 'guru']) }}">Lanjutkan ke Dashboard</a>
                </div>
            </div>

            <header class="sibk-auth-card__header">
                <h2 id="login-title">Masuk ke Ruang BK</h2>
            </header>

            <form id="loginForm" action="{{ route('login.store') }}" method="post" novalidate>
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="identifier">Email</label>
                    <div class="input-group has-validation">
                        <span class="input-group-text" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                        </span>
                        <input class="form-control @error('email') is-invalid @enderror" id="identifier" name="email" type="email" required
                            autocomplete="username" spellcheck="false"
                            value="{{ old('email') }}" placeholder="Masukkan email akun"
                            aria-describedby="identifierError">
                        <div class="invalid-feedback" id="identifierError">{{ $errors->first('email', 'Email wajib diisi.') }}</div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="password">Kata sandi</label>
                    <div class="input-group has-validation">
                        <span class="input-group-text" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        </span>
                        <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required
                            autocomplete="current-password" placeholder="Masukkan kata sandi"
                            aria-describedby="passwordError">
                        <button class="btn sibk-password-toggle" id="togglePassword" type="button"
                            aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg id="passwordVisibilityIcon" aria-hidden="true" viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                        <div class="invalid-feedback" id="passwordError">Kata sandi wajib diisi.</div>
                    </div>
                </div>

                <button class="btn btn-primary sibk-auth-submit w-100" id="loginButton" type="submit">
                    <span class="spinner-border spinner-border-sm" id="loginSpinner" aria-hidden="true" hidden></span>
                    <span id="loginButtonText">Masuk</span>
                </button>
            </form>

            <div class="sibk-auth-help">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01"/></svg>
                <span>Kesulitan mengakses akun? <strong>Hubungi Admin IT sekolah.</strong></span>
            </div>

        </div>
    </section>

</main>
