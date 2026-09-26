const element = (tag, className = '', text = '') => {
    const node = document.createElement(tag);
    node.className = className;
    node.textContent = text;

    return node;
};

const render = (container, preview, dapodikUrl) => {
    container.replaceChildren();
    const tone = preview.missing > 0 || preview.conflicts > 0 ? 'alert-warning' : 'alert-success';
    container.append(element(
        'div',
        `alert ${tone}`,
        preview.missing > 0
            ? `${preview.missing} pelanggaran aktif sebelumnya tidak ada dalam respons API. Sinkronisasi ditahan; periksa sumber data.`
            : preview.conflicts > 0
            ? `${preview.conflicts} pelanggaran memiliki identitas yang perlu diperiksa.`
            : 'Tidak ada indikasi konflik identitas.',
    ));

    if (preview.active_year) {
        container.append(element('p', 'text-muted small mb-3',
            `Tahun ajaran aktif: ${preview.active_year}. Identitas e-Tatib dicocokkan ke seluruh master murid.`));
    }
    if (preview.missing_students > 0) {
        const link = element('a', 'd-inline-block mb-3 fw-semibold', 'Periksa data murid di tab Dapodik');
        link.href = dapodikUrl;
        container.append(link);
    }

    const summary = element('div', 'd-flex flex-wrap gap-4 mb-3');
    summary.append(
        element('span', 'fw-semibold', `${preview.rows} pelanggaran diterima`),
        element('span', 'fw-semibold', `${preview.students} murid`),
        element('span', 'fw-semibold', `${preview.matched} pelanggaran cocok`),
        element('span', 'fw-semibold', `${preview.conflicts} pelanggaran perlu diperiksa`),
    );
    container.append(summary);
    if (preview.conflicts > 0) {
        container.append(element('p', 'mb-3',
            `${preview.missing_students} identitas memiliki NISN yang belum ada di master. ${preview.name_mismatches} identitas memiliki NISN sama tetapi nama berbeda.`));
        [
            ['name_mismatch', preview.name_mismatches, 'Nama berbeda'],
            ['nisn_not_found', preview.missing_students, 'NISN belum ada di master'],
        ].forEach(([kind, count, label]) => {
            if (!count) return;
            const details = element('details', 'mb-3');
            details.append(element('summary', 'fw-semibold', `Lihat ${count} identitas: ${label}`));
            const list = element('ul', 'list-group mt-2');
            preview.identity_conflicts.filter((item) => item.kind === kind).forEach((item) => {
                const row = element('li', 'list-group-item');
                row.append(
                    element('strong', 'd-block', `${item.name} · NISN ${item.nisn}`),
                    element('span', 'd-block', `Kelas e-Tatib: ${item.classroom}`),
                    element('span', 'text-muted small', item.reason),
                );
                list.append(row);
            });
            details.append(list);
            container.append(details);
        });
        container.append(element('p', 'text-muted small mb-0',
            'Setelah sinkronisasi, buka Yang Perlu Ditinjau untuk mencocokkan identitas yang belum sesuai.'));
    }
};

export const initEtatibApiPreview = async (root = document) => {
    const form = root.querySelector('[data-etatib-api-form]');
    const modalNode = root.querySelector('[data-etatib-preview-modal]');
    if (!form || !modalNode) return;

    const { default: Modal } = await import('bootstrap/js/dist/modal.js');
    const modal = Modal.getOrCreateInstance(modalNode);
    const body = modalNode.querySelector('[data-etatib-preview-body]');
    const confirm = modalNode.querySelector('[data-etatib-confirm]');
    const automaticConfirm = modalNode.querySelector('[data-etatib-confirm-automatic]');
    const automaticPassword = modalNode.querySelector('[data-etatib-automatic-password]');
    const previewButton = form.querySelector('[data-etatib-preview-button]');
    const url = form.elements.namedItem('api_url');
    let previewedUrl = '';
    let controller;

    form.addEventListener('submit', async (event) => {
        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }
        event.preventDefault();
        if (!form.reportValidity()) return;

        controller?.abort();
        controller = new AbortController();
        previewedUrl = '';
        confirm.disabled = true;
        automaticConfirm.disabled = true;
        automaticPassword.disabled = true;
        automaticPassword.required = false;
        automaticPassword.value = '';
        previewButton.disabled = true;
        body.replaceChildren(element('p', 'text-muted mb-0', 'Menghubungi API e-Tatib...'));
        modal.show();

        try {
            const response = await fetch(form.dataset.previewUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: new FormData(form),
                signal: controller.signal,
            });
            const payload = await response.json();
            if (!response.ok) {
                const message = Object.values(payload.errors ?? {}).flat()[0]
                    ?? payload.message
                    ?? 'Pratinjau API e-Tatib gagal dimuat.';
                body.replaceChildren(element('div', 'alert alert-danger mb-0', message));
                return;
            }
            render(body, payload.data, form.dataset.dapodikUrl);
            previewedUrl = url.value;
            confirm.disabled = payload.data.missing > 0;
            automaticPassword.disabled = payload.data.missing > 0;
            automaticConfirm.disabled = payload.data.missing > 0;
        } catch (error) {
            if (error.name !== 'AbortError') {
                body.replaceChildren(element('div', 'alert alert-danger mb-0', 'Pratinjau tidak dapat dimuat.'));
            }
        } finally {
            previewButton.disabled = false;
        }
    });

    confirm.addEventListener('click', () => {
        if (!previewedUrl || previewedUrl !== url.value) {
            body.replaceChildren(element('div', 'alert alert-danger mb-0', 'Link berubah. Jalankan pratinjau kembali.'));
            confirm.disabled = true;
            return;
        }
        form.dataset.confirmed = 'true';
        form.action = form.dataset.manualSyncUrl;
        automaticPassword.required = false;
        automaticPassword.disabled = true;
        confirm.disabled = true;
        automaticConfirm.disabled = true;
        modal.hide();
        form.requestSubmit();
    });

    automaticConfirm.addEventListener('click', () => {
        if (!previewedUrl || previewedUrl !== url.value) {
            body.replaceChildren(element('div', 'alert alert-danger mb-0', 'Link berubah. Jalankan pratinjau kembali.'));
            confirm.disabled = true;
            automaticConfirm.disabled = true;
            return;
        }

        automaticPassword.disabled = false;
        automaticPassword.required = true;
        if (!form.reportValidity()) {
            automaticPassword.focus();
            return;
        }

        form.dataset.confirmed = 'true';
        form.action = form.dataset.automaticSyncUrl;
        confirm.disabled = true;
        automaticConfirm.disabled = true;
        modal.hide();
        form.requestSubmit();
    });

    modalNode.addEventListener('hidden.bs.modal', () => {
        controller?.abort();
        if (form.dataset.confirmed !== 'true') {
            automaticPassword.value = '';
            automaticPassword.disabled = true;
            automaticPassword.required = false;
        }
        previewButton.focus();
    });
};
