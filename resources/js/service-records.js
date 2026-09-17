import Modal from 'bootstrap/js/dist/modal';
import { draftKey, removeDraft } from './form-draft';

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content
    ?? document.querySelector('input[name="_token"]')?.value;

const renderErrors = (form, errors) => {
    form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
    Object.entries(errors).forEach(([name, messages]) => {
        const field = form.elements.namedItem(name);
        if (!field || field instanceof RadioNodeList) return;
        field.classList.add('is-invalid');
        let feedback = form.querySelector(`[data-error-for="${name}"]`);
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.dataset.errorFor = name;
            field.insertAdjacentElement('afterend', feedback);
        }
        feedback.textContent = messages[0];
    });
};

const clearModalDraft = (form) => {
    const userId = document.body.dataset.draftUser;
    if (userId && form.dataset.autosaveForm) {
        removeDraft(window.localStorage, draftKey(userId, form.dataset.autosaveForm, form.dataset.autosaveRecord ?? 'new'));
    }
};

export const initServiceRecords = () => {
    const modalElement = document.querySelector('[data-service-record-modal]');
    let requestController;
    let trigger;

    if (modalElement) {
        const modal = Modal.getOrCreateInstance(modalElement);
        modalElement.addEventListener('hidden.bs.modal', () => {
            requestController?.abort();
            trigger?.focus();
            trigger = undefined;
        });
        document.querySelectorAll('[data-modal-url]').forEach((button) => button.addEventListener('click', async () => {
            trigger = button;
            requestController?.abort();
            requestController = new AbortController();
            const url = new URL(button.dataset.modalUrl, window.location.origin);
            url.searchParams.set('modal', '1');
            modal.show();
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: requestController.signal });
                if (!response.ok) throw new Error('Gagal memuat data.');
                modalElement.querySelector('.modal-content').innerHTML = await response.text();
            } catch (error) {
                if (error.name !== 'AbortError') modalElement.querySelector('.modal-content').textContent = error.message;
            }
        }));
        modalElement.addEventListener('submit', async (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            event.preventDefault();
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: new FormData(form),
            });
            if (response.status === 422) {
                renderErrors(form, (await response.json()).errors ?? {});
                return;
            }
            if (!response.ok) return;
            clearModalDraft(form);
            window.location.assign((await response.json()).redirect ?? window.location.href);
        });
    }

    document.querySelectorAll('[data-follow-up-url]').forEach((select) => select.addEventListener('change', async () => {
        const previousValue = select.dataset.previousValue ?? select.value;
        const status = document.querySelector(select.dataset.followUpStatusTarget);
        const timestamp = document.querySelector(select.dataset.followUpTimestampTarget);
        const previousStatus = status?.textContent;
        const previousTimestamp = timestamp?.textContent;
        select.disabled = true;
        try {
            const response = await fetch(select.dataset.followUpUrl, {
                method: 'PATCH',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json' },
                body: JSON.stringify({ follow_up_type_id: select.value }),
            });
            if (!response.ok) throw new Error('Gagal memperbarui tindak lanjut.');
            const payload = await response.json();
            select.dataset.previousValue = select.value;
            if (status) status.textContent = payload.status_label ?? status.textContent;
            if (timestamp) timestamp.textContent = payload.updated_at ?? timestamp.textContent;
        } catch {
            select.value = previousValue;
            if (status) status.textContent = previousStatus;
            if (timestamp) timestamp.textContent = previousTimestamp;
        } finally {
            select.disabled = false;
        }
    }));
};
