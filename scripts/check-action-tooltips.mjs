import assert from 'node:assert/strict';
import { actionTitle, actionTooltipConfig, detachedActions, nativeActionSelector, syncNativeActionTitle } from '../resources/js/action-tooltip-config.js';

const action = (attributes = {}, extra = {}) => ({
    attributes,
    tagName: 'BUTTON',
    innerText: '  Simpan  perubahan ',
    getAttribute(name) { return this.attributes[name] ?? null; },
    setAttribute(name, value) { this.attributes[name] = value; },
    removeAttribute(name) { delete this.attributes[name]; },
    closest() { return this.optedOut ? this : null; },
    ...extra,
});

const button = action({
    'data-action-tooltip': '  Aksi khusus  ',
    'data-bs-title': 'Judul Bootstrap',
    title: 'Judul biasa',
    'aria-label': 'Nama aksesibel',
});
assert.equal(actionTitle(button), 'Aksi khusus');
delete button.attributes['data-action-tooltip'];
assert.equal(actionTitle(button), 'Judul Bootstrap');
delete button.attributes['data-bs-title'];
assert.equal(actionTitle(button), 'Judul biasa');

button.attributes.title = '  Tutup  detail ';
assert.equal(actionTitle(button), 'Tutup detail', 'judul yang berubah harus langsung dipakai');
delete button.attributes.title;
button.attributes['aria-label'] = 'Tutup rincian murid';
assert.equal(actionTitle(button), 'Tutup rincian murid', 'label yang berubah harus mengalahkan judul lama');

assert.equal(actionTitle(action({ 'data-bs-original-title': 'Judul lama' })), 'Judul lama');
assert.equal(actionTitle(action()), 'Simpan perubahan');
assert.equal(actionTitle(action({}, { tagName: 'INPUT', value: 'Cari', labels: [{ textContent: '  Cari murid ' }] })), 'Cari murid');
assert.equal(actionTitle(action({}, { tagName: 'INPUT', value: 'Cari' })), 'Cari');
assert.equal(actionTitle(action({ title: 'Rahasia' }, { optedOut: true })), '');

const container = {};
const config = actionTooltipConfig(container);
assert.equal(config.html, false, 'teks dari data tidak boleh diproses sebagai HTML');
assert.equal(config.trigger, 'hover focus');
assert.equal(config.container, container);
assert.match(config.selector, /\[role="switch"\]/);
assert.match(config.selector, /:not\(:disabled/);
assert.match(config.selector, /\[data-bs-toggle="dropdown"\]/);
assert.match(nativeActionSelector, /\[data-bs-toggle="tab"\]/);
assert.match(nativeActionSelector, /button:disabled/);
assert.equal(config.title, actionTitle);

const generatedTitles = new WeakMap();
const tab = action({ 'aria-label': 'Kelas 10' });
syncNativeActionTitle(tab, generatedTitles);
assert.equal(tab.getAttribute('title'), 'Kelas 10');
tab.attributes['aria-label'] = 'Kelas 11';
syncNativeActionTitle(tab, generatedTitles);
assert.equal(tab.getAttribute('title'), 'Kelas 11', 'judul bawaan mengikuti perubahan label');
tab.attributes.title = 'Pilih kelas binaan';
syncNativeActionTitle(tab, generatedTitles);
assert.equal(tab.getAttribute('title'), 'Pilih kelas binaan', 'judul buatan penulis tetap dipertahankan');

const removedButton = { isConnected: false };
const movedButton = { isConnected: true };
const removedContainer = {
    nodeType: 1,
    matches: () => false,
    querySelectorAll: () => [removedButton, movedButton],
};
assert.deepEqual(detachedActions(removedContainer), [removedButton], 'kontrol yang dipindah tetap memiliki tooltip');
assert.deepEqual(detachedActions({ nodeType: 3 }), [], 'teks yang terhapus bukan kontrol');

process.stdout.write('Tooltip aksi: pemeriksaan lulus.\n');
