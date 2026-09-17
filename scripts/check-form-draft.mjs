import assert from 'node:assert/strict';

import {
    collectSafeValues,
    draftKey,
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
assert.equal(loadDraft(storage, firstKey, () => now + (24 * 60 * 60 * 1000) + 1), null);

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

console.log('Form draft checks passed.');
