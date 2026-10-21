export const selectedApiSource = (form) => form.querySelector('[data-saved-api-choice]')?.checked
    ? 'saved' : form.elements.namedItem('api_url').value;

export const syncSavedApiSource = (form) => {
    const saved = form.querySelector('[data-saved-api-choice]')?.checked ?? false;
    const url = form.elements.namedItem('api_url');
    if (saved) url.value = '';
    url.disabled = saved;
    url.required = !saved;
    url.placeholder = saved ? 'API tersimpan (disembunyikan)' : url.dataset.apiPlaceholder;
};

export const clearSavedApiSecrets = (section) => {
    for (const selector of ['[data-saved-api-revealed]', '[data-saved-api-password]', '[data-saved-api-new-url]']) {
        section.querySelector(selector).value = '';
    }
    const error = section.querySelector('[data-saved-api-error]');
    error.textContent = '';
    error.hidden = true;
    section.querySelector('[data-saved-api-reveal-fields]').hidden = true;
    section.querySelector('[data-saved-api-hide]').hidden = true;
};

export const revealSavedApiUrl = async (section, request = fetch, signal) => {
    const password = section.querySelector('[data-saved-api-password]');
    const output = section.querySelector('[data-saved-api-revealed]');
    const error = section.querySelector('[data-saved-api-error]');
    output.value = '';
    error.hidden = true;
    try {
        const response = await request(section.dataset.revealUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: new URLSearchParams({ current_password: password.value }),
            signal,
        });
        const payload = await response.json();
        if (signal?.aborted) return false;
        if (!response.ok) {
            error.textContent = payload.errors?.current_password?.[0] ?? payload.message ?? 'Tautan tidak dapat ditampilkan.';
            error.hidden = false;
            return false;
        }
        output.value = payload.api_url;
        password.value = '';
        section.querySelector('[data-saved-api-reveal-fields]').hidden = false;
        section.querySelector('[data-saved-api-hide]').hidden = false;
        return true;
    } catch {
        if (signal?.aborted) return false;
        error.textContent = 'Tautan tidak dapat ditampilkan. Coba lagi.';
        error.hidden = false;
        return false;
    }
};

export const initSavedApiUrls = (root = document, { Modal } = {}) => {
    root.querySelectorAll('[data-saved-api-source-form]').forEach((form) => {
        const choice = form.querySelector('[data-saved-api-choice]');
        syncSavedApiSource(form);
        choice?.addEventListener('change', () => syncSavedApiSource(form));
        window.addEventListener('pagehide', () => { form.elements.namedItem('api_url').value = ''; });
    });

    root.querySelectorAll('[data-saved-api-url]').forEach((section) => {
        const modalNode = section.querySelector('[data-saved-api-modal]');
        const modal = Modal.getOrCreateInstance(modalNode);
        const form = section.querySelector('[data-saved-api-form]');
        const url = section.querySelector('[data-saved-api-new-url]');
        const sourceForm = section.querySelector('[data-saved-api-source-form]');
        const password = section.querySelector('[data-saved-api-password]');
        const submit = section.querySelector('[data-saved-api-submit]');
        let mode = 'edit';
        let controller;
        const open = (nextMode) => {
            controller?.abort();
            clearSavedApiSecrets(section);
            mode = nextMode;
            const edit = mode === 'edit';
            if (edit && selectedApiSource(sourceForm) !== 'saved') url.value = selectedApiSource(sourceForm);
            section.querySelector('[data-saved-api-edit-fields]').hidden = !edit;
            url.disabled = !edit;
            url.required = edit;
            submit.textContent = edit ? 'Simpan tautan' : 'Tampilkan tautan';
            section.querySelector('[data-saved-api-title]').textContent = edit ? 'Simpan tautan API' : 'Tampilkan tautan API';
            modal.show();
        };
        section.querySelector('[data-saved-api-edit-button]').addEventListener('click', () => open('edit'));
        section.querySelector('[data-saved-api-reveal-button]')?.addEventListener('click', () => open('reveal'));
        section.querySelector('[data-saved-api-hide]').addEventListener('click', () => {
            controller?.abort();
            clearSavedApiSecrets(section);
            modal.hide();
        });
        modalNode.addEventListener('shown.bs.modal', () => (mode === 'edit' && !url.value ? url : password).focus());
        modalNode.addEventListener('hidden.bs.modal', () => {
            controller?.abort();
            clearSavedApiSecrets(section);
        });
        form.addEventListener('submit', async (event) => {
            if (mode === 'edit') return;
            event.preventDefault();
            if (!form.reportValidity()) return;
            controller?.abort();
            controller = new AbortController();
            submit.disabled = true;
            await revealSavedApiUrl(section, fetch, controller.signal);
            submit.disabled = false;
        });
        window.addEventListener('pagehide', () => {
            controller?.abort();
            clearSavedApiSecrets(section);
        });
        if (modalNode.hasAttribute('data-saved-api-open-edit')) open('edit');
    });
};

export const initEtatibSchedule = (root = document) => {
    const form = root.querySelector('[data-etatib-schedule-form]');
    if (!form) return;
    const times = form.querySelector('[data-etatib-schedule-times]');
    const password = form.querySelector('[data-etatib-schedule-password]');
    const addTime = () => {
        if (times.querySelectorAll('[data-etatib-schedule-time-row]').length >= 24) return;
        const row = document.createElement('div');
        row.className = 'd-flex gap-2';
        row.dataset.etatibScheduleTimeRow = '';
        const input = document.createElement('input');
        input.className = 'form-control';
        input.type = 'time';
        input.name = 'schedule_times[]';
        input.required = true;
        input.setAttribute('aria-label', 'Jam pembaruan baru');
        const remove = document.createElement('button');
        remove.className = 'btn btn-outline-secondary';
        remove.type = 'button';
        remove.dataset.etatibScheduleRemove = '';
        remove.setAttribute('aria-label', 'Hapus jam pembaruan baru');
        remove.textContent = 'Hapus';
        row.append(input, remove);
        times.append(row);
        input.focus();
    };
    if (!times.querySelector('[data-etatib-schedule-time-row]')) addTime();
    form.querySelector('[data-etatib-schedule-add]').addEventListener('click', addTime);
    times.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-etatib-schedule-remove]');
        if (!remove || times.querySelectorAll('[data-etatib-schedule-time-row]').length === 1) return;
        remove.closest('[data-etatib-schedule-time-row]').remove();
    });
    form.addEventListener('submit', (event) => {
        if (form.querySelector('[name="schedule_days[]"]:checked')) return;
        event.preventDefault();
        const firstDay = form.querySelector('[name="schedule_days[]"]');
        firstDay.setCustomValidity('Pilih paling sedikit satu hari.');
        firstDay.reportValidity();
    });
    form.querySelectorAll('[name="schedule_days[]"]').forEach((day) => {
        day.addEventListener('change', () => form.querySelector('[name="schedule_days[]"]').setCustomValidity(''));
    });
    window.addEventListener('pagehide', () => { password.value = ''; });
};
