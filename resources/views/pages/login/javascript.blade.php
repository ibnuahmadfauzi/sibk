<script>
    $(document).ready(function() {
        $('.btn-login').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Login Gagal?",
                text: "username atau password tidak valid",
                icon: "error"
            });
        })
    })
</script>
