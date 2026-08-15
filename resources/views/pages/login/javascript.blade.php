<script>
    $(document).ready(function () {

        // Toggle show/hide password
        $('#togglePassword').on('click', function () {
            const input = $('#password');
            const eyeIcon = $('#eyeIcon');
            const isPassword = input.attr('type') === 'password';

            input.attr('type', isPassword ? 'text' : 'password');

            // Ganti icon
            if (isPassword) {
                eyeIcon.html(`
                    <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-2.5-5-7-5a6.97 6.97 0 0 0-3.357.882M5.5 5.5A7 7 0 0 0 1 8s2.5 5 7 5a6.97 6.97 0 0 0 3.5-.937M5.5 5.5l6 6" stroke="#4A6FA5" stroke-width="1.3" stroke-linecap="round"/>
                    <line x1="2" y1="2" x2="14" y2="14" stroke="#4A6FA5" stroke-width="1.3" stroke-linecap="round"/>
                `);
            } else {
                eyeIcon.html(`
                    <path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z" stroke="#9CA3AF" stroke-width="1.3"/>
                    <circle cx="8" cy="8" r="2" stroke="#9CA3AF" stroke-width="1.3"/>
                `);
            }
        });

        // Handle form submit
        $('#loginForm').on('submit', function (e) {
            e.preventDefault();

            const identifier = $('#identifier').val().trim();
            const password = $('#password').val().trim();
            const alertBox = $('#validationAlert');
            const msgBox = $('#validationMsg');

            // Reset
            alertBox.hide();

            if (!identifier || !password) {
                msgBox.text('Nama pengguna/email dan kata sandi harus diisi.');
                alertBox.slideDown(200);
                return;
            }

            // Simulasi login gagal (ganti dengan AJAX asli)
            Swal.fire({
                title: "Login Gagal",
                text: "Username atau password tidak valid.",
                icon: "error",
                confirmButtonColor: "#4A6FA5",
                confirmButtonText: "Coba Lagi"
            });
        });

        // Sembunyikan alert saat user mulai mengetik
        $('#identifier, #password').on('input', function () {
            $('#validationAlert').slideUp(150);
        });
    });
</script>
