export const resetReportYearFilters = (select) => {
    const form = select.closest('form');
    const classroom = form?.querySelector('[name="classroom_search"]');
    if (classroom) classroom.value = '';
};

export const initReportPreview = (Modal) => {
    const modal = document.querySelector('[data-report-preview-modal]');
    if (!modal) return;

    const frame = modal.querySelector('[data-report-preview-frame]');
    const loading = modal.querySelector('[data-report-preview-loading]');
    const error = modal.querySelector('[data-report-preview-error]');
    const errorMessage = modal.querySelector('[data-report-preview-error-message]');
    const defaultErrorMessage = errorMessage.textContent;
    const printButton = modal.querySelector('[data-report-preview-print]');
    const retryButton = modal.querySelector('[data-report-preview-retry]');
    const download = modal.querySelector('[data-report-preview-download]');
    const fallback = modal.querySelector('[data-report-preview-fallback]');
    const periodForm = modal.querySelector('[data-report-period-form]');
    const periodStatus = modal.querySelector('[data-report-period-status]');
    const field = (name) => periodForm.querySelector(`[name="${name}"]`);
    let ready = false;
    let dirty = false;
    let loadTimeout;

    const disableOutput = () => {
        printButton.disabled = true;
        download.removeAttribute('href');
        download.setAttribute('aria-disabled', 'true');
        download.setAttribute('tabindex', '-1');
        download.classList.add('disabled');
    };

    const periodUrl = (base) => {
        const url = new URL(base);
        for (const name of ['semester', 'date_from', 'date_to']) {
            url.searchParams.set(name, field(name).value);
        }
        return url;
    };

    const fitPaper = () => {
        if (!ready) return;
        const doc = frame.contentDocument;
        const sheet = doc?.querySelector('.sibk-document-sheet');
        if (!sheet || !sheet.offsetWidth) return;
        const style = frame.contentWindow.getComputedStyle(doc.body);
        const availableWidth = doc.documentElement.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight);
        if (availableWidth <= 0) return;
        doc.body.style.setProperty('--report-preview-scale', Math.min(1, availableWidth / sheet.offsetWidth));
    };

    new ResizeObserver(fitPaper).observe(frame);
    modal.addEventListener('shown.bs.modal', fitPaper);

    const clearLoadTimeout = () => {
        window.clearTimeout(loadTimeout);
        loadTimeout = undefined;
    };

    const showLoading = () => {
        ready = false;
        errorMessage.textContent = defaultErrorMessage;
        disableOutput();
        frame.classList.add('d-none');
        error.classList.add('d-none');
        loading.classList.remove('d-none');
    };

    const showError = (message = defaultErrorMessage) => {
        clearLoadTimeout();
        ready = false;
        errorMessage.textContent = message;
        disableOutput();
        frame.classList.add('d-none');
        loading.classList.add('d-none');
        error.classList.remove('d-none');
    };

    const load = () => {
        if (!periodForm) {
            showError(modal.querySelector('[data-report-period-unavailable]')?.textContent || defaultErrorMessage);
            return;
        }
        const from = field('date_from');
        const to = field('date_to');
        to.setCustomValidity(from.value && to.value && from.value > to.value ? 'Tanggal akhir tidak boleh sebelum tanggal awal.' : '');
        if (!periodForm.reportValidity()) return;
        dirty = false;
        periodStatus.textContent = '';
        showLoading();
        frame.src = periodUrl(modal.dataset.previewUrl).href;
        const standalone = new URL(frame.src);
        standalone.searchParams.delete('embedded');
        fallback.href = standalone.href;
        clearLoadTimeout();
        loadTimeout = window.setTimeout(showError, 20000);
    };

    modal.addEventListener('show.bs.modal', load);
    retryButton.addEventListener('click', load);
    periodForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        load();
    });
    const markChanged = () => {
        dirty = true;
        disableOutput();
        field('date_to').setCustomValidity('');
        periodStatus.textContent = 'Periode berubah. Klik Perbarui rekap sebelum mencetak atau mengunduh.';
    };
    periodForm?.addEventListener('input', markChanged);
    periodForm?.querySelector('[name="semester"]').addEventListener('change', () => {
        const semester = field('semester').value === '1' ? 'One' : 'Two';
        const start = periodForm.dataset[`semester${semester}Start`];
        const end = periodForm.dataset[`semester${semester}End`];
        for (const name of ['date_from', 'date_to']) {
            field(name).min = start;
            field(name).max = end;
        }
        field('date_from').value = start;
        field('date_to').value = end;
        markChanged();
    });
    frame.addEventListener('load', () => {
        if (!frame.getAttribute('src')) return;
        clearLoadTimeout();
        try {
            const document = frame.contentDocument;
            if (!document?.querySelector('.sibk-report-embedded .sibk-document-sheet')) {
                showError(document?.querySelector('[data-report-filter-errors]')?.textContent?.trim() || defaultErrorMessage);
                return;
            }
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') Modal.getOrCreateInstance(modal).hide();
            });
            ready = true;
            loading.classList.add('d-none');
            error.classList.add('d-none');
            frame.classList.remove('d-none');
            fitPaper();
            if (!dirty) {
                printButton.disabled = false;
                download.setAttribute('href', periodUrl(download.dataset.exportUrl).href);
                download.setAttribute('aria-disabled', 'false');
                download.removeAttribute('tabindex');
                download.classList.remove('disabled');
            }
        } catch {
            showError();
        }
    });
    frame.addEventListener('error', () => showError());

    printButton.addEventListener('click', async () => {
        if (!ready || dirty) return;
        try {
            const document = frame.contentDocument;
            await document.fonts.ready;
            if (!ready || dirty || frame.contentDocument !== document) return;
            frame.contentWindow.focus();
            frame.contentWindow.print();
        } catch {
            showError();
        }
    });
};
