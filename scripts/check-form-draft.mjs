import assert from 'node:assert/strict';

import {
    collectSafeValues,
    draftKey,
    initFormDraft,
    loadDraft,
    purgeExpiredDrafts,
    removeDraft,
    removeUserDrafts,
    saveDraft,
} from '../resources/js/form-draft.js';

class FakeStorage {
    #values = new Map();

    get length() { return this.#values.size; }
    key(index) { return [...this.#values.keys()][index] ?? null; }
    getItem(key) { return this.#values.get(key) ?? null; }
    setItem(key, value) { this.#values.set(key, String(value)); }
    removeItem(key) { this.#values.delete(key); }
}

const storage = new FakeStorage();
const now = 1_700_000_000_000;
const clock = () => now;
const firstKey = draftKey('17', 'achievement', 'new');
const otherUserKey = draftKey('18', 'achievement', 'new');
const otherFormKey = draftKey('17', 'assignment', 'new');
const recordKey = draftKey('17', 'achievement', '42');

assert.equal(firstKey, 'sibk:draft:v1:17:achievement:new');
saveDraft(storage, firstKey, { title: 'Prestasi nasional' }, clock);
assert.deepEqual(loadDraft(storage, firstKey, clock), { title: 'Prestasi nasional' });

saveDraft(storage, otherUserKey, { title: 'Milik pengguna lain' }, clock);
saveDraft(storage, otherFormKey, { class_id: '7' }, clock);
saveDraft(storage, recordKey, { title: 'Rekam 42' }, clock);
removeDraft(storage, firstKey);
assert.equal(loadDraft(storage, firstKey, clock), null);
assert.deepEqual(loadDraft(storage, otherUserKey, clock), { title: 'Milik pengguna lain' });
assert.deepEqual(loadDraft(storage, otherFormKey, clock), { class_id: '7' });
assert.deepEqual(loadDraft(storage, recordKey, clock), { title: 'Rekam 42' });

saveDraft(storage, firstKey, { title: 'Kedaluwarsa' }, clock);
assert.equal(loadDraft(storage, firstKey, () => now + (24 * 60 * 60 * 1000)), null);

saveDraft(storage, firstKey, { title: 'Hapus semua milik pengguna' }, clock);
removeUserDrafts(storage, '17');
assert.equal(loadDraft(storage, firstKey, clock), null);
assert.equal(loadDraft(storage, otherFormKey, clock), null);
assert.equal(loadDraft(storage, recordKey, clock), null);
assert.deepEqual(loadDraft(storage, otherUserKey, clock), { title: 'Milik pengguna lain' });

storage.setItem(draftKey('17', 'expired', 'new'), JSON.stringify({ version: 1, savedAt: now - (24 * 60 * 60 * 1000) - 1, values: { title: 'lama' } }));
purgeExpiredDrafts(storage, clock);
assert.equal(storage.getItem(draftKey('17', 'expired', 'new')), null);

assert.deepEqual(collectSafeValues([
    { name: 'title', value: 'Aman', type: 'text' },
    { name: '_token', value: 'csrf', type: 'hidden' },
    { name: '_method', value: 'PATCH', type: 'hidden' },
    { name: 'password', value: 'rahasia', type: 'password' },
    { name: 'api_token', value: 'token', type: 'text' },
    { name: 'credential', value: 'credential', type: 'text' },
    { name: 'secret_note', value: 'secret', type: 'text' },
    { name: 'attachment', value: 'file.csv', type: 'file' },
]), { title: 'Aman' });
assert.deepEqual(collectSafeValues([
    { name: 'etatib_record_ids[]', value: '11', type: 'checkbox', checked: true },
    { name: 'etatib_record_ids[]', value: '22', type: 'checkbox', checked: true },
    { name: 'etatib_record_ids[]', value: '33', type: 'checkbox', checked: false },
]), { 'etatib_record_ids[]': ['11', '22'] });

globalThis.RadioNodeList = class RadioNodeList extends Array {};

class FakeForm {
    constructor() {
        this.dataset = { autosaveForm: 'achievement', autosaveRecord: 'new' };
        this.field = { name: 'title', value: 'Sebelum', type: 'text' };
        this.elements = [this.field];
        this.elements.namedItem = (name) => this.elements.find((field) => field.name === name) ?? null;
        this.listeners = new Map();
    }

    addEventListener(type, listener) {
        const listeners = this.listeners.get(type) ?? [];
        listeners.push(listener);
        this.listeners.set(type, listeners);
    }

    querySelector() { return null; }
    dispatch(type) { return Promise.all((this.listeners.get(type) ?? []).map((listener) => listener({ target: this }))); }
}

const formStorage = new FakeStorage();
const sessionStorage = new FakeStorage();
const form = new FakeForm();
const environment = {
    storage: formStorage,
    sessionStorage,
    userId: '17',
    clock,
    setTimer: () => 1,
    clearTimer: () => {},
};
assert.equal(initFormDraft(form, environment), true);
assert.equal(initFormDraft(form, environment), false);
assert.equal(form.listeners.get('submit').length, 1);
form.field.value = 'Terbaru sebelum debounce';
await form.dispatch('submit');
assert.deepEqual(loadDraft(formStorage, firstKey, clock), { title: 'Terbaru sebelum debounce' });
assert.equal(sessionStorage.getItem('sibk:draft:pending'), firstKey);

class CheckboxForm extends FakeForm {
    constructor(record, savedValues) {
        super();
        this.dataset.autosaveRecord = record;
        this.elements = new RadioNodeList(
            { name: 'etatib_record_ids[]', value: '11', type: 'checkbox', checked: false },
            { name: 'etatib_record_ids[]', value: '22', type: 'checkbox', checked: false },
        );
        this.elements.namedItem = () => this.elements;
        saveDraft(formStorage, draftKey('17', 'achievement', record), savedValues, clock);
    }
}

const arrayForm = new CheckboxForm('42', { 'etatib_record_ids[]': ['11', '22'] });
initFormDraft(arrayForm, environment);
assert.deepEqual([...arrayForm.elements].map((field) => field.checked), [true, true]);

const scalarForm = new CheckboxForm('43', { 'etatib_record_ids[]': '22' });
initFormDraft(scalarForm, environment);
assert.deepEqual([...scalarForm.elements].map((field) => field.checked), [false, true]);

console.log('Form draft checks passed.');
