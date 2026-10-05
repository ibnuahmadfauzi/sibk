export const initReportPreview = (Modal) => {
    const modal = document.querySelector('[data-report-preview-modal]');
    if (!modal) return;

    const frame = modal.querySelector('[data-report-preview-frame]');
    const loading = modal.querySelector('[data-report-preview-loading]');
    const error = modal.querySelector('[data-report-preview-error]');
    const printButton = modal.querySelector('[data-report-preview-print]');
    const retryButton = modal.querySelector('[data-report-preview-retry]');
    let ready = false;
    let loadTimeout;

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
        printButton.disabled = true;
        frame.classList.add('d-none');
        error.classList.add('d-none');
        loading.classList.remove('d-none');
    };

    const showError = () => {
        clearLoadTimeout();
        ready = false;
        printButton.disabled = true;
        frame.classList.add('d-none');
        loading.classList.add('d-none');
        error.classList.remove('d-none');
    };

    const load = () => {
        showLoading();
        frame.src = modal.dataset.previewUrl;
        clearLoadTimeout();
        loadTimeout = window.setTimeout(showError, 20000);
    };

    modal.addEventListener('show.bs.modal', load);
    retryButton.addEventListener('click', load);
    frame.addEventListener('load', () => {
        if (!frame.getAttribute('src')) return;
        clearLoadTimeout();
        try {
            const document = frame.contentDocument;
            if (!document?.querySelector('.sibk-report-embedded .sibk-document-sheet')) {
                showError();
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
            printButton.disabled = false;
        } catch {
            showError();
        }
    });
    frame.addEventListener('error', showError);

    printButton.addEventListener('click', async () => {
        if (!ready) return;
        try {
            await frame.contentDocument.fonts.ready;
            frame.contentWindow.focus();
            frame.contentWindow.print();
        } catch {
            showError();
        }
    });
};
