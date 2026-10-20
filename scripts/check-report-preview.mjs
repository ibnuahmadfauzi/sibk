import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { initReportPreview, resetReportPeriodFilters } from '../resources/js/report-preview.js';

const view = await readFile('resources/views/pages/reports/index.blade.php', 'utf8');
assert.match(view, /data-report-filter-errors/);
assert.match(view, /data-report-preview-error-message/);
const previewView = await readFile('resources/views/pages/reports/preview.blade.php', 'utf8');
const filterView = await readFile('resources/views/pages/reports/_filters.blade.php', 'utf8');
assert.equal((filterView.match(/min="\{\{ \$report\['period'\]\['semester_start'\] \}\}" max="\{\{ \$report\['period'\]\['semester_end'\] \}\}"/g) ?? []).length, 2);
for (const markup of [view, previewView]) {
    for (const filter of ['semester', 'date_from', 'date_to']) {
        assert(markup.includes(`'${filter}' => $report['filters']['${filter}']`));
    }
}
assert.match(view, /@disabled\(empty\(\$report\['filters'\]\['semester'\]\)\)/);

const dateFrom = { value: '2026-08-01' };
const dateTo = { value: '2026-11-30' };
const classroom = { value: 'X RPL' };
const form = {
    querySelectorAll: () => [dateFrom, dateTo],
    querySelector: () => classroom,
};
const select = (year) => ({
    closest: () => form,
    matches: () => year,
});
resetReportPeriodFilters(select(false));
assert.equal(dateFrom.value, '');
assert.equal(dateTo.value, '');
assert.equal(classroom.value, 'X RPL');
dateFrom.value = '2026-08-01';
dateTo.value = '2026-11-30';
resetReportPeriodFilters(select(true));
assert.equal(dateFrom.value, '');
assert.equal(dateTo.value, '');
assert.equal(classroom.value, '');

const events = () => ({
    listeners: {},
    addEventListener(name, handler) { this.listeners[name] = handler; },
    fire(name) { this.listeners[name]?.({ type: name }); },
});
const classList = () => ({
    values: new Set(),
    add(name) { this.values.add(name); },
    remove(name) { this.values.delete(name); },
    contains(name) { return this.values.has(name); },
});
const loading = { classList: classList() };
const error = { classList: classList() };
const message = { textContent: 'Pratinjau belum dapat ditampilkan.', innerHTML: 'unchanged' };
const print = { disabled: true, ...events() };
const retry = events();
const frame = {
    ...events(), classList: classList(), src: '', contentDocument: null,
    getAttribute(name) { return name === 'src' ? this.src : null; },
};
const nodes = {
    '[data-report-preview-frame]': frame,
    '[data-report-preview-loading]': loading,
    '[data-report-preview-error]': error,
    '[data-report-preview-error-message]': message,
    '[data-report-preview-print]': print,
    '[data-report-preview-retry]': retry,
};
const modal = {
    ...events(), dataset: { previewUrl: '/reports/preview?embedded=1' },
    querySelector(selector) { return nodes[selector]; },
};
globalThis.document = { querySelector: () => modal };
globalThis.ResizeObserver = class { observe() {} };
let timeout;
globalThis.window = {
    setTimeout(callback) { timeout = callback; return 1; },
    clearTimeout() { timeout = undefined; },
};

initReportPreview({ getOrCreateInstance: () => ({ hide() {} }) });
modal.fire('show.bs.modal');
assert.equal(frame.src, modal.dataset.previewUrl);
frame.contentDocument = {
    querySelector(selector) {
        return selector === '[data-report-filter-errors]'
            ? { textContent: 'Kelas tidak ditemukan untuk tahun ajaran ini.' } : null;
    },
};
frame.fire('load');
assert.equal(message.textContent, 'Kelas tidak ditemukan untuk tahun ajaran ini.');
assert.equal(message.innerHTML, 'unchanged');
assert.equal(print.disabled, true);
assert.equal(frame.classList.contains('d-none'), true);
assert.equal(error.classList.contains('d-none'), false);

retry.fire('click');
assert.equal(message.textContent, 'Pratinjau belum dapat ditampilkan.');
assert.equal(error.classList.contains('d-none'), true);
frame.contentDocument = {
    querySelector(selector) {
        return selector === '.sibk-report-embedded .sibk-document-sheet' ? { offsetWidth: 0 } : null;
    },
    addEventListener() {},
};
frame.fire('load');
assert.equal(print.disabled, false);
assert.equal(frame.classList.contains('d-none'), false);

retry.fire('click');
frame.fire('error');
assert.equal(message.textContent, 'Pratinjau belum dapat ditampilkan.');
assert.equal(print.disabled, true);
assert.equal(frame.classList.contains('d-none'), true);

retry.fire('click');
timeout();
assert.equal(message.textContent, 'Pratinjau belum dapat ditampilkan.');
assert.equal(print.disabled, true);

console.log('Pesan validasi pratinjau laporan, retry, sukses, error, dan timeout valid.');
