const createElement = (tag, className, text) => {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (text !== undefined) element.textContent = text;

    return element;
};

const appendCell = (row, text, className = '') => {
    row.append(createElement('td', className, text));
};

const renderPreview = (container, preview) => {
    container.replaceChildren();

    const status = createElement(
        'div',
        `alert ${preview.can_import ? 'alert-success' : 'alert-warning'}`,
        preview.can_import
            ? 'Tahun ajaran siap. Konflik rombel diperiksa saat impor.'
            : 'Data terbaca, tetapi ada tahun pelajaran yang belum siap diimpor.',
    );
    container.append(status);

    const summary = createElement('div', 'd-flex flex-wrap gap-4 mb-3');
    summary.append(
        createElement('span', 'fw-semibold', `${preview.rows} baris`),
        createElement('span', 'fw-semibold', `${preview.students} murid unik`),
        createElement('span', 'fw-semibold', `${preview.academic_years.length} tahun pelajaran`),
    );
    container.append(summary);

    const yearTitle = createElement('h4', 'fs-6 fw-bold mb-2', 'Kesiapan Tahun Pelajaran');
    const yearWrapper = createElement('div', 'table-responsive mb-4');
    const yearTable = createElement('table', 'table table-sm sibk-table mb-0');
    const yearHead = createElement('thead');
    const yearHeadRow = createElement('tr');
    ['Tahun Pelajaran', 'Baris', 'Rombel', 'Status'].forEach((label) => {
        yearHeadRow.append(createElement('th', '', label));
    });
    yearHead.append(yearHeadRow);
    const yearBody = createElement('tbody');
    preview.academic_years.forEach((year) => {
        const row = createElement('tr');
        appendCell(row, year.name, 'fw-semibold');
        appendCell(row, String(year.rows));
        appendCell(row, String(year.classrooms));
        const statusCell = createElement('td');
        statusCell.append(createElement(
            'span',
            `sibk-badge sibk-badge--${year.ready ? 'success' : 'warning'}`,
            year.ready ? 'Siap' : 'Belum Siap',
        ));
        statusCell.append(createElement('div', 'text-muted small mt-1', year.status));
        row.append(statusCell);
        yearBody.append(row);
    });
    yearTable.append(yearHead, yearBody);
    yearWrapper.append(yearTable);
    container.append(yearTitle, yearWrapper);

    const sampleTitle = createElement('h4', 'fs-6 fw-bold mb-2', 'Contoh Data yang Terbaca');
    const sampleNote = createElement(
        'p',
        'text-muted small',
        'Maksimal lima baris ditampilkan. NISN disamarkan dan data API tidak disimpan.',
    );
    const sampleWrapper = createElement('div', 'table-responsive');
    const sampleTable = createElement('table', 'table table-sm sibk-table mb-0');
    const sampleHead = createElement('thead');
    const sampleHeadRow = createElement('tr');
    ['NISN', 'Nama', 'Rombel', 'Tahun Pelajaran'].forEach((label) => {
        sampleHeadRow.append(createElement('th', '', label));
    });
    sampleHead.append(sampleHeadRow);
    const sampleBody = createElement('tbody');
    preview.sample.forEach((item) => {
        const row = createElement('tr');
        appendCell(row, item.nisn);
        appendCell(row, item.name, 'fw-semibold');
        appendCell(row, item.classroom);
        appendCell(row, item.academic_year);
        sampleBody.append(row);
    });
    sampleTable.append(sampleHead, sampleBody);
    sampleWrapper.append(sampleTable);
    container.append(sampleTitle, sampleNote, sampleWrapper);
};

const renderFailure = (container, message) => {
    container.replaceChildren(createElement('div', 'alert alert-danger mb-0', message));
};

export const initApiSiswaPreview = async (root = document) => {
    const form = root.querySelector('[data-api-siswa-import-form]');
    const modalElement = root.querySelector('[data-api-siswa-preview-modal]');
    if (!form || !modalElement) return;

    const { default: Modal } = await import('bootstrap/js/dist/modal.js');
    const modal = Modal.getOrCreateInstance(modalElement);
    const body = modalElement.querySelector('[data-api-siswa-preview-body]');
    const confirmButton = modalElement.querySelector('[data-api-siswa-confirm-import]');
    const previewButton = form.querySelector('[data-api-siswa-preview-button]');
    const urlInput = form.elements.namedItem('api_url');
    let requestController;
    let previewedUrl = '';

    form.addEventListener('submit', async (event) => {
        if (form.dataset.importConfirmed === 'true') {
            delete form.dataset.importConfirmed;
            return;
        }

        event.preventDefault();
        if (!form.reportValidity()) return;

        requestController?.abort();
        requestController = new AbortController();
        previewedUrl = '';
        confirmButton.disabled = true;
        previewButton.disabled = true;
        body.replaceChildren(createElement('p', 'text-muted mb-0', 'Menghubungi API Siswa...'));
        modal.show();

        try {
            const response = await fetch(form.dataset.previewUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: new FormData(form),
                signal: requestController.signal,
            });
            const payload = await response.json();
            if (!response.ok) {
                const message = Object.values(payload.errors ?? {}).flat()[0]
                    ?? payload.message
                    ?? 'Pratinjau API Siswa gagal dimuat.';
                renderFailure(body, message);
                return;
            }

            renderPreview(body, payload.data);
            previewedUrl = urlInput.value;
            confirmButton.disabled = !payload.data.can_import;
        } catch (error) {
            if (error.name !== 'AbortError') {
                renderFailure(body, 'Pratinjau tidak dapat dimuat. Periksa koneksi lalu coba lagi.');
            }
        } finally {
            previewButton.disabled = false;
        }
    });

    confirmButton.addEventListener('click', () => {
        if (!previewedUrl || previewedUrl !== urlInput.value) {
            renderFailure(body, 'Link API berubah. Jalankan pratinjau kembali sebelum mengimpor.');
            confirmButton.disabled = true;
            return;
        }

        form.dataset.importConfirmed = 'true';
        confirmButton.disabled = true;
        modal.hide();
        form.requestSubmit();
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        requestController?.abort();
        previewButton.focus();
    });
};
