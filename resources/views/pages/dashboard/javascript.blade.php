<script>
    function toggleSidebar() {
        document.getElementById("sidebar").classList.toggle("show");
    }

    // Tutup sidebar ketika menu diklik pada mobile
    document.querySelectorAll(".sidebar-menu a").forEach(function(link) {

        link.addEventListener("click", function() {

            if (window.innerWidth <= 991) {
                document
                    .getElementById("sidebar")
                    .classList.remove("show");
            }

        });

    });
</script>
