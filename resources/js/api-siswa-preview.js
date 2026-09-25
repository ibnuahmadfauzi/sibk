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
            ? 'Data siap diimpor. Periksa daftar murid sebelum melanjutkan.'
            : preview.conflict_count
                ? `${preview.conflict_count} data perlu diperiksa. Impor belum dapat dilanjutkan.`
                : 'Ada tahun pelajaran yang belum siap diimpor.',
    );
    container.append(status);

    const summary = createElement('div', 'd-flex flex-wrap gap-4 mb-3');
    summary.append(
        createElement('span', 'fw-semibold', `${preview.rows} baris`),
        createElement('span', 'fw-semibold', `${preview.students} murid unik`),
        createElement('span', 'fw-semibold', `${preview.academic_years.length} tahun pelajaran`),
        createElement('span', 'fw-semibold', `${preview.conflict_count} konflik`),
    );
    container.append(summary);

    const yearTitle = createElement('h4', 'fs-6 fw-bold mb-2', 'Kesiapan Tahun Pelajaran');
    const yearWrapper = createElement('div', 'table-responsive');
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

    const listTitle = createElement('h4', 'fs-6 fw-bold mt-4 mb-2', 'Daftar Murid dari API');
    const controls = createElement('div', 'd-flex flex-wrap align-items-center gap-2 mb-2');
    const search = createElement('input', 'form-control flex-grow-1');
    search.type = 'search';
    search.placeholder = 'Cari NISN, nama, atau rombel';
    search.setAttribute('aria-label', 'Cari murid dalam pratinjau');
    const conflictOnly = createElement('input', 'form-check-input mt-0');
    conflictOnly.type = 'checkbox';
    conflictOnly.id = 'api-siswa-conflict-only';
    const conflictLabel = createElement('label', 'form-check-label', 'Hanya yang perlu diperiksa');
    conflictLabel.htmlFor = conflictOnly.id;
    controls.append(search, conflictOnly, conflictLabel);

    const listWrapper = createElement('div', 'table-responsive');
    const listTable = createElement('table', 'table table-sm sibk-table mb-0');
    const caption = createElement('caption', 'visually-hidden', 'Daftar seluruh murid dari API Siswa');
    const listHead = createElement('thead');
    const headingRow = createElement('tr');
    ['NISN', 'Nama', 'Tahun Pelajaran', 'Rombel API', 'Rombel SIBK', 'Hasil'].forEach((label) => {
        headingRow.append(createElement('th', '', label));
    });
    listHead.append(headingRow);
    const listBody = createElement('tbody');
    listTable.append(caption, listHead, listBody);
    listWrapper.append(listTable);

    const navigation = createElement('div', 'd-flex flex-wrap align-items-center justify-content-between gap-2 mt-2');
    const count = createElement('span', 'text-muted small');
    const buttons = createElement('div', 'd-flex gap-2');
    const previous = createElement('button', 'btn btn-sm btn-outline-primary', 'Sebelumnya');
    const next = createElement('button', 'btn btn-sm btn-outline-primary', 'Berikutnya');
    previous.type = next.type = 'button';
    buttons.append(previous, next);
    navigation.append(count, buttons);

    let page = 0;
    const pageSize = 50;
    const renderRows = () => {
        const term = search.value.trim().toLocaleLowerCase('id');
        const entries = preview.entries.filter((entry) => {
            if (conflictOnly.checked && !['NISN perlu diperiksa', 'Rombel berbeda'].includes(entry.status)) return false;
            return [entry.nisn, entry.name, entry.academic_year, entry.classroom, entry.current_classroom]
                .some((value) => value?.toLocaleLowerCase('id').includes(term));
        });
        const pages = Math.max(1, Math.ceil(entries.length / pageSize));
        page = Math.min(page, pages - 1);
        listBody.replaceChildren();
        entries.slice(page * pageSize, (page + 1) * pageSize).forEach((entry) => {
            const row = createElement('tr', ['NISN perlu diperiksa', 'Rombel berbeda'].includes(entry.status) ? 'table-warning' : '');
            [entry.nisn, entry.name, entry.academic_year, entry.classroom, entry.current_classroom ?? '—', entry.status]
                .forEach((value) => appendCell(row, value));
            listBody.append(row);
        });
        if (!entries.length) {
            const empty = createElement('tr');
            const cell = createElement('td', 'text-center text-muted py-3', 'Tidak ada murid yang cocok.');
            cell.colSpan = 6;
            empty.append(cell);
            listBody.append(empty);
        }
        count.textContent = `${entries.length} data · halaman ${page + 1} dari ${pages}`;
        previous.disabled = page === 0;
        next.disabled = page >= pages - 1;
    };
    search.addEventListener('input', () => { page = 0; renderRows(); });
    conflictOnly.addEventListener('change', () => { page = 0; renderRows(); });
    previous.addEventListener('click', () => { page--; renderRows(); });
    next.addEventListener('click', () => { page++; renderRows(); });
    renderRows();
    container.append(listTitle, controls, listWrapper, navigation);

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
