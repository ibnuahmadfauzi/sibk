import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { actionTitle, actionTooltipConfig, detachedActions, nativeActionSelector, removeRedundantTitle, syncNativeActionTitle } from '../resources/js/action-tooltip-config.js';

const action = (attributes = {}, extra = {}) => ({
    attributes,
    tagName: 'BUTTON',
    innerText: '  Simpan  perubahan ',
    cloneNode() { return { textContent: this.innerText, querySelectorAll: () => [] }; },
    matches() { return !!this.disabled; },
    classList: { contains: () => false },
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
assert.equal(actionTitle(button), '', 'tombol berteks tidak membutuhkan tooltip otomatis');
button.innerText = '';
assert.equal(actionTitle(button), 'Tutup rincian murid');

assert.equal(actionTitle(action({ 'data-bs-original-title': 'Judul lama' })), 'Judul lama');
assert.equal(actionTitle(action()), '');
assert.equal(actionTitle(action({ title: 'Simpan perubahan' })), '');
const redundantOriginal = action({ 'data-bs-original-title': 'Simpan perubahan' });
removeRedundantTitle(redundantOriginal);
assert.equal(redundantOriginal.getAttribute('data-bs-original-title'), null, 'Bootstrap tidak boleh memulihkan judul redundan');
assert.equal(actionTitle(action({}, { tagName: 'INPUT', value: 'Cari', labels: [{ textContent: '  Cari murid ' }] })), '');
assert.equal(actionTitle(action({}, { tagName: 'INPUT', value: 'Cari' })), '');
const sorting = action({ 'aria-label': 'Urutkan Nama dari A ke Z' }, { innerText: 'Nama', classList: { contains: () => true } });
assert.equal(actionTitle(sorting), 'Urutkan Nama dari A ke Z');
sorting.attributes['aria-label'] = 'Urutkan Nama dari Z ke A';
assert.equal(actionTitle(sorting), 'Urutkan Nama dari Z ke A');
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
const tab = action({ 'aria-label': 'Kelas 10' }, { innerText: '' });
syncNativeActionTitle(tab, generatedTitles);
assert.equal(tab.getAttribute('title'), 'Kelas 10');
tab.attributes['aria-label'] = 'Kelas 11';
syncNativeActionTitle(tab, generatedTitles);
assert.equal(tab.getAttribute('title'), 'Kelas 11', 'judul bawaan mengikuti perubahan label');
tab.attributes.title = 'Pilih kelas binaan';
syncNativeActionTitle(tab, generatedTitles);
assert.equal(tab.getAttribute('title'), 'Pilih kelas binaan', 'judul buatan penulis tetap dipertahankan');
const textTab = action({ 'aria-label': 'Kelas 10', title: 'Kelas 10' }, { innerText: 'Kelas 10' });
syncNativeActionTitle(textTab, generatedTitles);
assert.equal(textTab.getAttribute('title'), null);
const disabled = action({ 'aria-label': 'Simpan' }, { innerText: 'Simpan', disabled: true });
syncNativeActionTitle(disabled, generatedTitles);
assert.equal(disabled.getAttribute('title'), null);
disabled.attributes['data-action-tooltip'] = 'Pilih murid terlebih dahulu';
syncNativeActionTitle(disabled, generatedTitles);
assert.equal(disabled.getAttribute('title'), 'Pilih murid terlebih dahulu');
disabled.optedOut = true;
syncNativeActionTitle(disabled, generatedTitles);
assert.equal(disabled.getAttribute('title'), null);

const removedButton = { isConnected: false };
const movedButton = { isConnected: true };
const removedContainer = {
    nodeType: 1,
    matches: () => false,
    querySelectorAll: () => [removedButton, movedButton],
};
assert.deepEqual(detachedActions(removedContainer), [removedButton], 'kontrol yang dipindah tetap memiliki tooltip');
assert.deepEqual(detachedActions({ nodeType: 3 }), [], 'teks yang terhapus bukan kontrol');

const handlers = {};
let observer;
let hidden = 0;
let disposed = 0;
class Tooltip {
    static getInstance(element) { return element.instance; }
    constructor(element) { element.instance = this; }
}
const body = {
    nodeType: 1, matches: () => false, querySelectorAll: () => [], closest: () => null,
    addEventListener: (name, listener, capture) => { handlers[name] = { listener, capture }; },
};
const source = readFileSync(new URL('../resources/js/action-tooltips.js', import.meta.url), 'utf8')
    .replace(/^import .*;\r?\n/gm, '')
    .replace('export const initActionTooltips', 'globalThis.initActionTooltips');
const context = {
    Tooltip, actionSelector: config.selector, actionTooltipConfig, detachedActions,
    nativeActionSelector, removeRedundantTitle, syncNativeActionTitle,
    MutationObserver: class { constructor(callback) { observer = callback; } observe() {} },
};
runInNewContext(source, context);
context.initActionTooltips({ body });
const control = action({}, {
    instance: { hide: () => { hidden++; }, dispose: () => { disposed++; } },
    nodeType: 1, isConnected: false, matches: () => true, querySelectorAll: () => [],
});
let stopped = 0;
const escape = { key: 'Escape', stopPropagation: () => { stopped++; }, preventDefault() {} };
handlers.keydown.listener(escape);
assert.equal(stopped, 0, 'Escape tetap tersedia bagi modal tanpa tooltip');
handlers['show.bs.tooltip'].listener({ target: control });
handlers.keydown.listener(escape);
assert.equal(hidden, 1);
assert.equal(stopped, 1);
assert.equal(handlers.keydown.capture, true, 'tooltip ditutup sebelum handler Escape modal');
observer([{ removedNodes: [control], addedNodes: [], target: body }]);
assert.equal(disposed, 1);
handlers.keydown.listener(escape);
assert.equal(stopped, 1, 'tooltip terhapus tidak menahan Escape');
process.stdout.write('Tooltip kontekstual dan Escape: pemeriksaan lulus.\n');
