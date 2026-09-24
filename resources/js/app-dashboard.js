import 'bootstrap/js/dist/dropdown';
import 'bootstrap/js/dist/offcanvas';
import 'bootstrap/js/dist/modal';
import { initFormDrafts } from './form-draft';
import { initServiceRecords } from './service-records';

initFormDrafts();
initServiceRecords();

document.querySelectorAll('[data-print-report]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});

document.querySelectorAll('[data-report-year-filter]').forEach((select) => {
    select.addEventListener('change', () => {
        const form = select.closest('form');
        const classroom = form?.querySelector('[name="classroom_id"]');

        if (classroom) classroom.value = '';
    });
});

document.querySelectorAll('[data-report-filter-form]').forEach((form) => {
    const button = form.querySelector('[data-report-filter-action]');
    const filters = [...form.querySelectorAll('[data-report-filter]')];
    const initialValues = filters.map((field) => field.value).join('|');
    const filtersAreActive = form.dataset.filtersActive === 'true';

    const showApply = () => {
        button.dataset.mode = 'apply';
        button.textContent = 'Terapkan';
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-primary');
    };

    const showReset = () => {
        button.dataset.mode = 'reset';
        button.textContent = 'Reset';
        button.classList.remove('btn-primary');
        button.classList.add('btn-outline-primary');
    };

    form.addEventListener('change', () => {
        const currentValues = filters.map((field) => field.value).join('|');

        if (filtersAreActive && currentValues === initialValues) {
            showReset();
            return;
        }

        showApply();
    });

    form.addEventListener('submit', (event) => {
        if (button.dataset.mode !== 'reset') return;

        event.preventDefault();
        window.location.assign(button.dataset.resetUrl);
    });

    document
        .querySelectorAll('[data-report-page-size][form="report-filter-form"]')
        .forEach((select) => {
            select.addEventListener('change', () => {
                showApply();
                form.requestSubmit();
            });
        });
});

document.querySelectorAll('[data-report-detail-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const detail = document.getElementById(button.getAttribute('aria-controls'));
        const expanded = button.getAttribute('aria-expanded') === 'true';

        detail.classList.toggle('d-none', expanded);
        button.setAttribute('aria-expanded', String(!expanded));
        button.title = expanded ? 'Tampilkan detail layanan' : 'Tutup detail layanan';
        button.setAttribute(
            'aria-label',
            `${button.title} ${button.dataset.reportDetailName}`,
        );
    });
});

document.querySelectorAll('form[data-confirm-submit]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.dataset.confirmMessage;

        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });
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
