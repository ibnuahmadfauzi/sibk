<script>
    $(document).ready(function() {
        // Toggle Password Visibility
        $('#togglePassword').on('click', function() {
            const passwordInput = $('#password');
            const icon = $('#togglePasswordIcon');
            
            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                icon.removeClass('bi-eye').addClass('bi-eye-slash');
            } else {
                passwordInput.attr('type', 'password');
                icon.removeClass('bi-eye-slash').addClass('bi-eye');
            }
        });

        // IT Admin Help Action
        $('#btnHelpAdmin').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Bantuan Akses Akun',
                html: '<div style="font-size: 14px; text-align: left; line-height: 1.6; color: #475569;">' +
                      '<p>Jika Anda lupa kata sandi atau akun Anda belum terdaftar, silakan hubungi:</p>' +
                      '<strong>• Unit IT / Administrator Sekolah</strong><br>' +
                      '<span>• Ruang IT Lt. 2 / Email: <code>admin-it@sekolah.sch.id</code></span><br><br>' +
                      '<span style="font-size: 12px; color: #94a3b8;">Pastikan membawa kartu identitas/NIP/NISN saat meminta reset akun.</span>' +
                      '</div>',
                icon: 'info',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#4A72B2'
            });
        });

        // Form Submit Handler (Preview validation)
        $('#loginForm').on('submit', function(e) {
            const identifier = $('#identifier').val().trim();
            const password = $('#password').val();

            if (!identifier || !password) {
                e.preventDefault();
                Swal.fire({
                    title: 'Form Belum Lengkap',
                    text: 'Silakan isi nama pengguna/email dan kata sandi Anda.',
                    icon: 'warning',
                    confirmButtonColor: '#4A72B2'
                });
                return;
            }
        });
    });
</script>
<?php /**PATH D:\Data PPG\Tugas PPG\PK\sibk\resources\views/pages/login/javascript.blade.php ENDPATH**/ ?>