import 'bootstrap/js/dist/dropdown';
import 'bootstrap/js/dist/offcanvas';

document.querySelectorAll('[data-print-report]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});

// ── Magic Sidebar Indicator ──────────────────────────────────────────────────
// Menciptakan indikator yang meluncur mulus di antara item menu aktif.
// Efek "cutout": indikator berwarna halaman sehingga terlihat berlubang.
(function initSidebarIndicator() {
    const nav = document.querySelector('.sibk-sidebar__nav');
    if (!nav) return;

    // Buat elemen indikator
    const indicator = document.createElement('div');
    indicator.className = 'sibk-nav-indicator';
    nav.insertBefore(indicator, nav.firstChild); // taruh sebelum link pertama

    function moveIndicator(target) {
        if (!target) return;

        // Hitung posisi target relatif terhadap nav container
        const navRect = nav.getBoundingClientRect();
        const targetRect = target.getBoundingClientRect();

        const top    = targetRect.top - navRect.top;
        const height = targetRect.height;

        indicator.style.top    = top + 'px';
        indicator.style.height = height + 'px';
        indicator.style.opacity = '1';
    }

    // Posisi awal (tanpa animasi)
    const activeLink = nav.querySelector('.sibk-nav-link.is-active');
    if (activeLink) {
        // Set langsung tanpa transition untuk posisi awal
        indicator.style.transition = 'none';
        moveIndicator(activeLink);
        // Aktifkan transition setelah frame pertama
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                indicator.style.transition = '';
            });
        });
    } else {
        indicator.style.opacity = '0';
    }
})();

