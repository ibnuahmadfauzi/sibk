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
<?php /**PATH D:\cadangan\Tugas Kuliah\PGG\Sesmter 2\PK\Website BK\sibk\resources\views/pages/dashboard/javascript.blade.php ENDPATH**/ ?>