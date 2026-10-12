export const clearAccountSecrets = (root) => {
    root.querySelectorAll('[data-account-secret]').forEach((section) => {
        section.textContent = '';
        section.remove();
    });
};

export const initAccountInformation = (page, lifecycle = window) => {
    page.querySelectorAll('[data-account-detail-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const detail = page.querySelector(`#${button.getAttribute('aria-controls')}`);
            const expanded = button.getAttribute('aria-expanded') === 'true';
            if (expanded) clearAccountSecrets(detail);
            detail.classList.toggle('d-none', expanded);
            button.setAttribute('aria-expanded', String(!expanded));
            button.title = expanded ? 'Lihat Informasi' : 'Tutup Informasi';
            button.setAttribute('aria-label', button.title);
        });
    });
    page.querySelectorAll('[data-account-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const section = button.closest('[data-account-secret]');
            const status = section.querySelector('[data-account-copy-status]');
            try {
                await navigator.clipboard.writeText(section.querySelector('[data-account-secret-value]').textContent.trim());
                status.textContent = 'Sandi disalin.';
            } catch {
                status.textContent = 'Tidak dapat menyalin otomatis. Pilih sandi lalu salin secara manual.';
            }
        });
    });
    lifecycle.addEventListener('pagehide', () => clearAccountSecrets(page));
};
