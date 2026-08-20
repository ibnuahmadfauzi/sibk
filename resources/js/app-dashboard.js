import 'bootstrap/js/dist/offcanvas';

document.querySelectorAll('[data-print-report]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});
