import { collectSafeValues, draftKey, flushFormDrafts, initFormDrafts, removeDraft } from './form-draft.js';
import { confirmFormAction } from './confirm-form-action.js';

const formSnapshots = new WeakMap();
const savingForms = new WeakSet();
const snapshotForm = (form) => JSON.stringify(collectSafeValues(form.elements));

export const hasUnsavedModalChanges = (form) => Boolean(form && formSnapshots.has(form)
    && formSnapshots.get(form) !== snapshotForm(form));

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

export const handleModalClick = (event, openModal) => {
    const trigger = event.target.closest?.('[data-modal-url]');
    if (!trigger) return false;

    const control = event.target.closest?.('a, button, input, select, textarea, label, [role="button"]');
    if (control && control !== trigger) return false;

    event.preventDefault();
    openModal(trigger);

    return true;
};

export const renderModalContent = (modalElement, html, initialiseDrafts = initFormDrafts) => {
    flushFormDrafts(modalElement);
    modalElement.querySelector('.modal-content').innerHTML = html;
    modalElement.querySelectorAll('[data-confirm-unsaved]').forEach((form) => {
        formSnapshots.set(form, snapshotForm(form));
    });
    initialiseDrafts(modalElement);
};

export const handleModalSubmit = async (event, environment = {}) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return false;

    event.preventDefault();
    if (savingForms.has(form)) return false;
    const confirm = environment.confirm ?? window.confirm;
    if ('confirmSubmit' in form.dataset && form.dataset.confirmMessage && !confirm(form.dataset.confirmMessage)) {
        event.stopImmediatePropagation?.();
        event.stopPropagation?.();

        return false;
    }

    const request = environment.request ?? fetch;
    const formData = environment.formData ?? ((value, submitter) => new FormData(value, submitter));
    const notify = environment.notify ?? (() => {});
    let response;
    let payload;
    const body = formData(form, event.submitter);
    savingForms.add(form);
    const submitter = event.submitter;
    if (submitter) submitter.disabled = true;
    try {
        response = await request(form.getAttribute('action'), {
            method: form.method || 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': environment.csrfToken ?? csrfToken() },
            body,
        });
        if (response.status === 422) {
            const errors = (await response.json()).errors ?? {};
            renderErrors(form, errors);
            notify(Object.values(errors)[0]?.[0] ?? 'Periksa kembali isian yang belum sesuai.');
            return false;
        }
        if (!response.ok) {
            notify('Gagal menyimpan perubahan. Silakan coba lagi.');
            return false;
        }
        payload = await response.json();
    } catch {
        notify('Gagal menyimpan perubahan. Silakan coba lagi.');
        return false;
    } finally {
        savingForms.delete(form);
        if (submitter) submitter.disabled = false;
    }

    formSnapshots.set(form, snapshotForm(form));
    (environment.clearDraft ?? clearModalDraft)(form);
    const redirect = payload?.redirect ?? window.location.href;
    (environment.redirect ?? ((url) => window.location.assign(url)))(redirect);

    return true;
};

const targetElement = (root, selector) => selector ? root.querySelector(selector) : null;

export const updateFollowUp = async (select, environment = {}) => {
    const root = environment.root ?? document;
    const request = environment.request ?? fetch;
    const token = environment.csrfToken ?? csrfToken();
    const previousValue = select.dataset.previousValue ?? select.value;
    const previousExpectedUpdatedAt = select.dataset.expectedUpdatedAt;
    const label = targetElement(root, select.dataset.followUpLabelTarget);
    const status = targetElement(root, select.dataset.followUpStatusTarget);
    const timestamp = targetElement(root, select.dataset.followUpTimestampTarget);
    const saveStatus = select.parentElement?.querySelector('[data-save-status]');
    const previousLabel = label?.textContent;
    const previousStatus = status?.textContent;
    const previousTimestamp = timestamp?.textContent;
    select.disabled = true;
    if (saveStatus) {
        saveStatus.textContent = 'Menyimpan...';
        saveStatus.classList.toggle('text-danger', false);
    }

    try {
        const response = await request(select.dataset.followUpUrl, {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json' },
            body: JSON.stringify({
                follow_up_type_id: select.value,
                expected_updated_at: select.dataset.expectedUpdatedAt,
            }),
        });
        if (!response.ok) throw new Error('Gagal memperbarui tindak lanjut.');

        const payload = (await response.json()).data ?? {};
        select.dataset.previousValue = select.value;
        if (payload.updated_at) select.dataset.expectedUpdatedAt = payload.updated_at;
        if (label) label.textContent = payload.follow_up_type_label ?? label.textContent;
        if (status) status.textContent = payload.status_label ?? status.textContent;
        if (timestamp) timestamp.textContent = payload.updated_at ?? timestamp.textContent;
        if (saveStatus) saveStatus.textContent = 'Tersimpan';
    } catch {
        select.value = previousValue;
        if (previousExpectedUpdatedAt === undefined) delete select.dataset.expectedUpdatedAt;
        else select.dataset.expectedUpdatedAt = previousExpectedUpdatedAt;
        if (label) label.textContent = previousLabel;
        if (status) status.textContent = previousStatus;
        if (timestamp) timestamp.textContent = previousTimestamp;
        if (saveStatus) {
            saveStatus.textContent = 'Gagal menyimpan';
            saveStatus.classList.toggle('text-danger', true);
        }
    } finally {
        select.disabled = false;
    }
};

const initialisedRoots = new WeakSet();

export const toggleServiceNotes = async (button, root = document, request = fetch) => {
    const row = root.querySelector(`#${button.getAttribute('aria-controls')}`);
    if (!row) return;
    const content = row.querySelector('[data-service-notes-content]');
    const opening = button.getAttribute('aria-expanded') !== 'true';
    button.setAttribute('aria-expanded', String(opening));
    row.classList.toggle('d-none', !opening);
    if (!opening || button.dataset.notesLoading === 'true' || button.dataset.notesLoaded === 'true') return;

    button.dataset.notesLoading = 'true';
    content.textContent = 'Memuat catatan…';
    try {
        const response = await request(button.dataset.serviceNotesUrl, { headers: { Accept: 'text/html' } });
        if (!response.ok || response.redirected) throw new Error('Gagal memuat catatan.');
        content.innerHTML = await response.text();
        button.dataset.notesLoaded = 'true';
    } catch {
        content.textContent = 'Catatan gagal dimuat. Tutup lalu buka kembali untuk mencoba lagi.';
    } finally {
        delete button.dataset.notesLoading;
    }
};

export const initServiceRecords = async (root = document) => {
    if (initialisedRoots.has(root)) return;
    initialisedRoots.add(root);

    root.addEventListener('click', (event) => {
        const button = event.target.closest?.('[data-service-notes-url]');
        if (button) void toggleServiceNotes(button, root);
    });

    const modalElement = root.querySelector('[data-service-record-modal]');
    let requestController;
    let trigger;

    if (modalElement) {
        const { default: Modal } = await import('bootstrap/js/dist/modal.js');
        const modal = Modal.getOrCreateInstance(modalElement);
        modalElement.addEventListener('sibk:form-cleared', (event) => {
            if (event.target.matches('[data-confirm-unsaved]')) {
                formSnapshots.set(event.target, snapshotForm(event.target));
            }
        });
        modalElement.addEventListener('hide.bs.modal', (event) => {
            if (modalElement.dataset.confirmationSuspended === 'true') return;
            const form = modalElement.querySelector('[data-confirm-unsaved]');
            if (savingForms.has(form)) {
                event.preventDefault();
                return;
            }
            if (hasUnsavedModalChanges(form)) {
                event.preventDefault();
                confirmFormAction(form, 'close', () => {
                    flushFormDrafts(modalElement);
                    requestController?.abort();
                    trigger?.focus();
                    trigger = undefined;
                });
            }
        });
        modalElement.addEventListener('hidden.bs.modal', () => {
            if (modalElement.dataset.confirmationSuspended === 'true') return;
            flushFormDrafts(modalElement);
            requestController?.abort();
            trigger?.focus();
            trigger = undefined;
        });
        root.addEventListener('click', (event) => handleModalClick(event, async (button) => {
            trigger = button;
            requestController?.abort();
            requestController = new AbortController();
            const currentRequest = requestController;
            const url = new URL(button.dataset.modalUrl, window.location.origin);
            url.searchParams.set('modal', '1');
            renderModalContent(modalElement, '<div class="modal-header"><h2 class="modal-title fs-5" id="case-modal-title">Layanan BK</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body" role="status">Memuat data...</div>');
            modal.show();
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: currentRequest.signal });
                if (!response.ok || response.redirected) throw new Error('Gagal memuat data.');
                const html = await response.text();
                if (currentRequest.signal.aborted || requestController !== currentRequest) return;
                renderModalContent(modalElement, html);
                const wide = Boolean(modalElement.querySelector('[data-modal-size="xl"]'));
                modalElement.querySelector('.modal-dialog').classList.toggle('modal-xl', wide);
                modalElement.querySelector('.modal-dialog').classList.toggle('modal-lg', !wide);
                modalElement.dispatchEvent(new CustomEvent('sibk:modal-loaded', { bubbles: true }));
                const form = modalElement.querySelector('[data-confirm-unsaved]');
                const restored = form?.querySelector('[data-draft-restored]:not(.d-none)');
                if (form && !restored) formSnapshots.set(form, snapshotForm(form));
            } catch (error) {
                if (error.name !== 'AbortError' && requestController === currentRequest && !currentRequest.signal.aborted) {
                    const body = modalElement.querySelector('.modal-body');
                    body.setAttribute('role', 'alert');
                    body.textContent = 'Form gagal dimuat. Tutup lalu buka kembali untuk mencoba lagi.';
                }
            }
        }));
        root.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && event.target.matches?.('[data-modal-url]')) {
                event.target.click();
            }
        });
        root.querySelector('[data-modal-auto-open]')?.click();
        modalElement.addEventListener('submit', (event) => { void handleModalSubmit(event, {
            notify: async (message) => {
                const toast = modalElement.querySelector('[data-modal-submit-error] [data-notification-toast]');
                if (!toast) return;
                toast.querySelector('[data-modal-submit-error-message]').textContent = message;
                const { default: Toast } = await import('bootstrap/js/dist/toast.js');
                Toast.getOrCreateInstance(toast, { delay: 8000 }).show();
            },
        }); });
    }

    root.addEventListener('change', (event) => {
        if (event.target.matches?.('[data-follow-up-url]')) updateFollowUp(event.target);
    });
};
