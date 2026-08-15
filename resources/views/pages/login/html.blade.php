<div class="sibk-login-wrapper">
    {{-- Panel Kiri: Branding & Ilustrasi --}}
    <section class="sibk-login-info">
        <div class="sibk-login-info-inner">
            {{-- Brand Logo + Name --}}
            <div class="sibk-brand-header">
                <div class="sibk-brand-logo">
                    <svg width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="19" cy="19" r="19" fill="#EEF2FF"/>
                        <path d="M19 9C16.5 9 14.5 11 14.5 13.5C14.5 15 15.2 16.4 16.3 17.3C14.1 18.1 12.5 19.9 12 22H15C15.5 20.5 17.1 19.5 19 19.5C20.9 19.5 22.5 20.5 23 22H26C25.5 19.9 23.9 18.1 21.7 17.3C22.8 16.4 23.5 15 23.5 13.5C23.5 11 21.5 9 19 9Z" fill="#4A6FA5"/>
                        <path d="M19 10.5C18 10.5 17.2 11.1 17 12L19 13L21 12C20.8 11.1 20 10.5 19 10.5Z" fill="#E8734A" opacity="0.9"/>
                        {{-- leaf left --}}
                        <path d="M13 16C11 15 9.5 16.5 10 18.5C10.5 20 12 20.5 13.5 19.5L13 16Z" fill="#4A6FA5" opacity="0.7"/>
                        {{-- leaf right --}}
                        <path d="M25 16C27 15 28.5 16.5 28 18.5C27.5 20 26 20.5 24.5 19.5L25 16Z" fill="#4A6FA5" opacity="0.7"/>
                    </svg>
                </div>
                <span class="sibk-brand-name">APLIKASI BK</span>
            </div>

            {{-- Tagline --}}
            <div class="sibk-login-info-body">
                <h1 class="sibk-info-tagline">
                    Pencatatan layanan BK yang terstruktur dan sesuai kewenangan.
                </h1>
                <div class="sibk-info-divider"></div>
                <p class="sibk-info-desc">
                    Kelola layanan bimbingan dan konseling dengan mudah, aman, dan terorganisir dalam satu sistem.
                </p>

                {{-- Ilustrasi --}}
                <div class="sibk-info-illustration">
                    <img src="/assets/images/counseling-illustration.jpg" alt="Ilustrasi layanan konseling" class="sibk-illustration-img">
                </div>
            </div>

            {{-- Footer Info --}}
            <div class="sibk-info-footer-note">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="8" cy="8" r="7.5" stroke="#4A6FA5" stroke-width="1"/>
                    <path d="M8 5V8.5L10 10" stroke="#4A6FA5" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
                <span>Gunakan akun yang telah ditetapkan oleh sekolah.</span>
            </div>
        </div>
    </section>

    {{-- Panel Kanan: Form Login --}}
    <section class="sibk-login-form-wrapper">
        <div class="sibk-login-card">
            {{-- Logo di card --}}
            <div class="sibk-card-logo-wrap">
                <div class="sibk-card-logo">
                    <svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="22" cy="22" r="22" fill="#EEF2FF"/>
                        <path d="M22 10C19 10 16.5 12.5 16.5 15.5C16.5 17.2 17.3 18.8 18.6 19.8C15.9 20.8 14 23.1 13.5 26H17C17.6 24.2 19.6 23 22 23C24.4 23 26.4 24.2 27 26H30.5C30 23.1 28.1 20.8 25.4 19.8C26.7 18.8 27.5 17.2 27.5 15.5C27.5 12.5 25 10 22 10Z" fill="#4A6FA5"/>
                        <path d="M22 11.5C20.8 11.5 19.8 12.3 19.5 13.4L22 14.6L24.5 13.4C24.2 12.3 23.2 11.5 22 11.5Z" fill="#E8734A"/>
                        <path d="M15 18.5C12.5 17.2 10.5 19 11 21.5C11.5 23.5 13.2 24 15 22.8L15 18.5Z" fill="#4A6FA5" opacity="0.65"/>
                        <path d="M29 18.5C31.5 17.2 33.5 19 33 21.5C32.5 23.5 30.8 24 29 22.8L29 18.5Z" fill="#4A6FA5" opacity="0.65"/>
                    </svg>
                </div>
            </div>

            <h2 class="sibk-login-title">Masuk ke Aplikasi BK</h2>

            {{-- Alert Validasi (hidden by default) --}}
            <div class="sibk-validation-alert" id="validationAlert" style="display:none;">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6.5" stroke="#c0392b" stroke-width="1"/><path d="M7 4v4" stroke="#c0392b" stroke-width="1.5" stroke-linecap="round"/><circle cx="7" cy="10" r="0.7" fill="#c0392b"/></svg>
                <span id="validationMsg">Username, email, atau NIP tidak dikenali.</span>
            </div>

            {{-- Form --}}
            <form action="#" method="POST" id="loginForm">
                @csrf
                <div class="sibk-field-group">
                    <label for="identifier" class="sibk-field-label">Nama pengguna atau email</label>
                    <div class="sibk-field-input-wrap">
                        <span class="sibk-field-icon">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="5.5" r="2.5" stroke="#9CA3AF" stroke-width="1.3"/><path d="M2.5 13c0-3.038 2.462-5.5 5.5-5.5s5.5 2.462 5.5 5.5" stroke="#9CA3AF" stroke-width="1.3" stroke-linecap="round"/></svg>
                        </span>
                        <input type="text" class="sibk-field-input" id="identifier" name="identifier"
                            placeholder="Masukkan identitas akun" autocomplete="username">
                    </div>
                </div>

                <div class="sibk-field-group">
                    <label for="password" class="sibk-field-label">Kata sandi</label>
                    <div class="sibk-field-input-wrap">
                        <span class="sibk-field-icon">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="3" y="7" width="10" height="7" rx="1.5" stroke="#9CA3AF" stroke-width="1.3"/><path d="M5.5 7V5a2.5 2.5 0 015 0v2" stroke="#9CA3AF" stroke-width="1.3" stroke-linecap="round"/></svg>
                        </span>
                        <input type="password" class="sibk-field-input" id="password" name="password"
                            placeholder="Masukkan kata sandi">
                        <button type="button" class="sibk-toggle-password" id="togglePassword" aria-label="Tampilkan kata sandi">
                            <svg class="eye-icon" id="eyeIcon" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z" stroke="#9CA3AF" stroke-width="1.3"/><circle cx="8" cy="8" r="2" stroke="#9CA3AF" stroke-width="1.3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="sibk-btn-login" id="btnLogin">
                    Masuk
                </button>
            </form>

            <div class="sibk-login-divider">
                <span>atau</span>
            </div>

            <a href="mailto:admin@sekolah.sch.id" class="sibk-btn-help">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><circle cx="9" cy="9" r="8" stroke="#4A6FA5" stroke-width="1.3"/><path d="M6.5 7C6.5 5.619 7.619 4.5 9 4.5s2.5 1.119 2.5 2.5c0 1.5-1.5 2-1.5 3.5" stroke="#4A6FA5" stroke-width="1.3" stroke-linecap="round"/><circle cx="9" cy="13" r="0.75" fill="#4A6FA5"/></svg>
                Kesulitan mengakses akun? Hubungi Admin IT sekolah.
            </a>
        </div>
    </section>
</div>
