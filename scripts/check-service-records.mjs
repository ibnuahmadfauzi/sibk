import assert from 'node:assert/strict';

import {
    handleModalClick,
    handleModalSubmit,
    hasUnsavedModalChanges,
    initServiceRecords,
    renderModalContent,
    toggleServiceNotes,
    updateFollowUp,
} from '../resources/js/service-records.js';

const notesButton = {
    dataset: { serviceNotesUrl: '/consultations/1?inline=1' },
    attributes: { 'aria-controls': 'consultation-notes-1', 'aria-expanded': 'false' },
    getAttribute(name) { return this.attributes[name]; },
    setAttribute(name, value) { this.attributes[name] = value; },
};
const notesContent = { innerHTML: '', textContent: '' };
let notesHidden = true;
const notesRow = {
    classList: { toggle(name, hidden) { notesHidden = hidden; } },
    querySelector() { return notesContent; },
};
const notesRoot = { querySelector() { return notesRow; } };
let notesRequests = 0;
const loadNotes = async () => {
    notesRequests++;
    return { ok: true, text: async () => '<p>Latar belakang dan penanganan</p>' };
};
await toggleServiceNotes(notesButton, notesRoot, loadNotes);
assert.equal(notesHidden, false);
assert.equal(notesButton.attributes['aria-expanded'], 'true');
assert.equal(notesContent.innerHTML, '<p>Latar belakang dan penanganan</p>');
await toggleServiceNotes(notesButton, notesRoot, loadNotes);
assert.equal(notesHidden, true);
await toggleServiceNotes(notesButton, notesRoot, loadNotes);
assert.equal(notesRequests, 1);
delete notesButton.dataset.notesLoaded;
notesButton.attributes['aria-expanded'] = 'false';
await toggleServiceNotes(notesButton, notesRoot, async () => ({ ok: false }));
assert.match(notesContent.textContent, /gagal dimuat/);
assert.equal(notesButton.dataset.notesLoaded, undefined);
await toggleServiceNotes(notesButton, notesRoot, loadNotes);
await toggleServiceNotes(notesButton, notesRoot, loadNotes);
assert.equal(notesRequests, 2);

class FakeNode {
    constructor(parent = null, { modalUrl, interactive = false } = {}) {
        this.parent = parent;
        this.dataset = modalUrl ? { modalUrl } : {};
        this.interactive = interactive;
    }

    closest(selector) {
        for (let node = this; node; node = node.parent) {
            if (selector === '[data-modal-url]' && node.dataset.modalUrl) return node;
            if (selector.includes('button') && node.interactive) return node;
        }
        return null;
    }

    matches(selector) {
        return selector === '[data-modal-url]' && Boolean(this.dataset.modalUrl);
    }
}

const row = new FakeNode(null, { modalUrl: '/cases/1?modal=1' });
const cell = new FakeNode(row);
const actionButton = new FakeNode(row, { interactive: true });
const modalLink = new FakeNode(row, { modalUrl: '/cases/1/edit?modal=1', interactive: true });
const opened = [];
const eventFor = (target) => ({
    target,
    prevented: false,
    preventDefault() { this.prevented = true; },
});

const rowEvent = eventFor(cell);
assert.equal(handleModalClick(rowEvent, (trigger) => opened.push(trigger)), true);
assert.equal(rowEvent.prevented, true);
assert.equal(opened[0], row);

const controlEvent = eventFor(actionButton);
assert.equal(handleModalClick(controlEvent, (trigger) => opened.push(trigger)), false);
assert.equal(controlEvent.prevented, false);
assert.equal(opened.length, 1);

const linkEvent = eventFor(modalLink);
assert.equal(handleModalClick(linkEvent, (trigger) => opened.push(trigger)), true);
assert.equal(linkEvent.prevented, true);
assert.equal(opened[1], modalLink);

const modalContent = { innerHTML: '' };
const modalElement = { querySelector: () => modalContent, querySelectorAll: () => [] };
let initialisedRoot;
renderModalContent(modalElement, '<form data-autosave-form="case"></form>', (rootElement) => {
    initialisedRoot = rootElement;
});
assert.equal(modalContent.innerHTML, '<form data-autosave-form="case"></form>');
assert.equal(initialisedRoot, modalElement);

class FakeForm {
    constructor(dataset) {
        this.dataset = dataset;
        this.action = '/consultations/1';
        this.method = 'PATCH';
        this.elements = [];
    }

    getAttribute(name) {
        return name === 'action' ? '/consultations/1' : null;
    }
}

globalThis.HTMLFormElement = FakeForm;
const dirtyForm = new FakeForm({ confirmUnsaved: '' });
dirtyForm.elements = [{ name: 'initial_info', value: '', type: 'textarea' }];
const dirtyModal = {
    querySelector: () => modalContent,
    querySelectorAll: (selector) => selector === '[data-confirm-unsaved]' ? [dirtyForm] : [],
};
renderModalContent(dirtyModal, '', () => {});
assert.equal(hasUnsavedModalChanges(dirtyForm), false);
dirtyForm.elements[0].value = 'Catatan belum disimpan';
assert.equal(hasUnsavedModalChanges(dirtyForm), true);
dirtyForm.elements[0].value = '';
assert.equal(hasUnsavedModalChanges(dirtyForm), false);
const submitEventFor = (target) => ({
    target,
    prevented: false,
    stopped: false,
    preventDefault() { this.prevented = true; },
    stopPropagation() { this.stopped = true; },
});
const cancelledEvent = submitEventFor(new FakeForm({
    confirmSubmit: '',
    confirmMessage: 'Lanjutkan pengeditan?',
}));
let requestedWhenCancelled = false;
assert.equal(await handleModalSubmit(cancelledEvent, {
    confirm: () => false,
    request: async () => { requestedWhenCancelled = true; },
}), false);
assert.equal(cancelledEvent.prevented, true);
assert.equal(cancelledEvent.stopped, true);
assert.equal(requestedWhenCancelled, false);

const confirmedEvent = submitEventFor(new FakeForm({
    confirmSubmit: '',
    confirmMessage: 'Lanjutkan pengeditan?',
}));
for (const action of ['save', 'complete']) {
    const submitter = { name: 'action', value: action };
    confirmedEvent.submitter = submitter;
    let receivedSubmitter;
    let redirectedTo;
    assert.equal(await handleModalSubmit(confirmedEvent, {
        confirm: () => true,
        request: async () => ({
            ok: true,
            status: 200,
            json: async () => ({ redirect: '/cases?tab=konsultasi' }),
        }),
        csrfToken: 'csrf',
        formData: (form, button) => {
            receivedSubmitter = button;
            return {};
        },
        clearDraft: () => {},
        redirect: (url) => { redirectedTo = url; },
    }), true);
    assert.equal(receivedSubmitter, submitter);
    assert.equal(redirectedTo, '/cases?tab=konsultasi');
}
assert.equal(confirmedEvent.prevented, true);

const shadowedActionEvent = submitEventFor(new FakeForm({}));
shadowedActionEvent.target.action = { toString: () => '[object HTMLButtonElement]' };
let requestedUrl;
assert.equal(await handleModalSubmit(shadowedActionEvent, {
    confirm: () => true,
    csrfToken: 'csrf',
    request: async (url) => {
        requestedUrl = url;
        return { ok: true, status: 200, json: async () => ({ redirect: '/cases/1' }) };
    },
    formData: () => ({}),
    clearDraft: () => {},
    redirect: () => {},
}), true);
assert.equal(requestedUrl, '/consultations/1');

const failedEvent = submitEventFor(new FakeForm({}));
const notices = [];
assert.equal(await handleModalSubmit(failedEvent, {
    confirm: () => true,
    request: async () => ({ ok: false, status: 500 }),
    csrfToken: 'csrf',
    formData: () => ({}),
    notify: (message) => notices.push(message),
}), false);
assert.deepEqual(notices, ['Gagal menyimpan perubahan. Silakan coba lagi.']);

for (const status of [200, 422]) {
    let draftCleared = false;
    let redirected = false;
    const messages = [];
    assert.equal(await handleModalSubmit(submitEventFor(new FakeForm({})), {
        confirm: () => true,
        request: async () => ({ ok: status === 200, status, json: async () => { throw new SyntaxError('Invalid JSON'); } }),
        csrfToken: 'csrf',
        formData: () => ({}),
        clearDraft: () => { draftCleared = true; },
        redirect: () => { redirected = true; },
        notify: (message) => messages.push(message),
    }), false);
    assert.equal(draftCleared, false);
    assert.equal(redirected, false);
    assert.deepEqual(messages, ['Gagal menyimpan perubahan. Silakan coba lagi.']);
}

const rootListeners = new Map();
const rootWithoutModal = {
    querySelector: () => null,
    addEventListener(type, listener) {
        const listeners = rootListeners.get(type) ?? [];
        listeners.push(listener);
        rootListeners.set(type, listeners);
    },
};
await initServiceRecords(rootWithoutModal);
await initServiceRecords(rootWithoutModal);
assert.equal(rootListeners.get('change').length, 1);

const elements = {
    '#follow-up-label': { textContent: 'Tidak ada' },
    '#case-status': { textContent: 'Sedang Proses' },
    '#case-updated': { textContent: '10.00' },
};
const root = { querySelector: (selector) => elements[selector] ?? null };
const select = {
    value: '30',
    disabled: false,
    dataset: {
        followUpUrl: '/cases/1/follow-up',
        expectedUpdatedAt: '2026-09-18T10:00:00.000000Z',
        previousValue: '',
        followUpLabelTarget: '#follow-up-label',
        followUpStatusTarget: '#case-status',
        followUpTimestampTarget: '#case-updated',
    },
    parentElement: {
        querySelector: (selector) => selector === '[data-save-status]' ? elements['#save-status'] : null,
    },
};
elements['#save-status'] = {
    textContent: '',
    classList: { toggle: () => {} },
};
let sent;
await updateFollowUp(select, {
    root,
    csrfToken: 'csrf',
    request: async (url, options) => {
        assert.equal(elements['#save-status'].textContent, 'Menyimpan...');
        sent = { url, options };
        return {
            ok: true,
            json: async () => ({
                message: 'Tersimpan.',
                data: {
                    follow_up_type_id: 30,
                    follow_up_type_label: 'Home Visit',
                    status_label: 'Tindak Lanjut',
                    updated_at: '2026-09-18T10:05:00.000000Z',
                },
            }),
        };
    },
});
assert.equal(sent.url, '/cases/1/follow-up');
assert.deepEqual(JSON.parse(sent.options.body), {
    follow_up_type_id: '30',
    expected_updated_at: '2026-09-18T10:00:00.000000Z',
});
assert.equal(elements['#follow-up-label'].textContent, 'Home Visit');
assert.equal(elements['#case-status'].textContent, 'Tindak Lanjut');
assert.equal(elements['#case-updated'].textContent, '2026-09-18T10:05:00.000000Z');
assert.equal(select.dataset.expectedUpdatedAt, '2026-09-18T10:05:00.000000Z');
assert.equal(select.dataset.previousValue, '30');
assert.equal(elements['#save-status'].textContent, 'Tersimpan');

select.value = '';
elements['#follow-up-label'].textContent = 'Home Visit';
elements['#case-status'].textContent = 'Tindak Lanjut';
elements['#case-updated'].textContent = '2026-09-18T10:05:00.000000Z';
await updateFollowUp(select, {
    root,
    csrfToken: 'csrf',
    request: async () => ({ ok: false }),
});
assert.equal(select.value, '30');
assert.equal(select.dataset.expectedUpdatedAt, '2026-09-18T10:05:00.000000Z');
assert.equal(elements['#follow-up-label'].textContent, 'Home Visit');
assert.equal(elements['#case-status'].textContent, 'Tindak Lanjut');
assert.equal(elements['#case-updated'].textContent, '2026-09-18T10:05:00.000000Z');
assert.equal(select.disabled, false);
assert.equal(elements['#save-status'].textContent, 'Gagal menyimpan');

console.log('Service record checks passed.');
