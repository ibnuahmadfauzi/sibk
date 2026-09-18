import assert from 'node:assert/strict';

import {
    handleModalClick,
    handleModalSubmit,
    initServiceRecords,
    renderModalContent,
    updateFollowUp,
} from '../resources/js/service-records.js';

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
const modalElement = { querySelector: () => modalContent };
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
    }
}

globalThis.HTMLFormElement = FakeForm;
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
let requestedWhenConfirmed = false;
let redirectedTo;
assert.equal(await handleModalSubmit(confirmedEvent, {
    confirm: () => true,
    request: async () => {
        requestedWhenConfirmed = true;
        return { ok: true, status: 200, json: async () => ({ redirect: '/cases?tab=konsultasi' }) };
    },
    csrfToken: 'csrf',
    formData: () => ({}),
    clearDraft: () => {},
    redirect: (url) => { redirectedTo = url; },
}), true);
assert.equal(confirmedEvent.prevented, true);
assert.equal(requestedWhenConfirmed, true);
assert.equal(redirectedTo, '/cases?tab=konsultasi');

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
};
let sent;
await updateFollowUp(select, {
    root,
    csrfToken: 'csrf',
    request: async (url, options) => {
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

console.log('Service record checks passed.');
