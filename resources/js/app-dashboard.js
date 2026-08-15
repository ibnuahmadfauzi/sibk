// ===========================================
// SIBK Dashboard JS
// ===========================================

// Bootstrap JS
import * as bootstrap from 'bootstrap';

// Expose ke global
window.bootstrap = bootstrap;

// Sidebar toggle (mobile)
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}

// Expose ke global (dipanggil dari onclick di topbar)
window.toggleSidebar = toggleSidebar;

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function (e) {
    const sidebar = document.getElementById('sidebar');
    const menuBtn = document.getElementById('mobileMenuBtn');

    if (sidebar && sidebar.classList.contains('show')) {
        if (!sidebar.contains(e.target) && (!menuBtn || !menuBtn.contains(e.target))) {
            sidebar.classList.remove('show');
        }
    }
});
