<div class="login-wrapper">
    <section class="login-info">
    </section>


    <section class="login-form-wrapper">
        <div class="login-card">
            <h2 class="login-title">
                Masuk ke Aplikasi BK
            </h2>
            <p class="login-description">
                Gunakan akun yang diberikan sekolah.
            </p>

            <!-- Validation -->
            <div class="validation-info">
                <span class="validation-badge">
                    VALIDASI
                </span>
                <span>
                    Identifier belum dikenali: email,
                    NIP, atau username.
                </span>
            </div>

            <!-- Login Form -->
            <form action="#" method="POST">
                <div class="mb-3">
                    <label for="identifier" class="form-label">
                        Email / NIP / Username
                    </label>
                    <input type="text" class="form-control" id="identifier" name="identifier"
                        placeholder="Masukkan identitas akun">
                </div>


                <div class="mb-0">
                    <label for="password" class="form-label">
                        Kata sandi
                    </label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••••">
                </div>


                <a href="#" class="forgot-password">
                    Lupa kata sandi?
                </a>

                <button class="btn-login">
                    Masuk
                </button>
            </form>

            <p class="login-warning">
                Akses tanpa autentikasi tidak diperbolehkan.
            </p>
        </div>
    </section>
</div>
