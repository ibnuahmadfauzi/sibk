import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { initReportPreview, resetReportYearFilters } from '../resources/js/report-preview.js';

const view = await readFile('resources/views/pages/reports/index.blade.php', 'utf8');
assert.match(view, /data-report-filter-errors/);
assert.match(view, /data-report-preview-error-message/);
const previewView = await readFile('resources/views/pages/reports/preview.blade.php', 'utf8');
const filterView = await readFile('resources/views/pages/reports/_filters.blade.php', 'utf8');
assert.doesNotMatch(filterView, /name="(?:semester|date_from|date_to)"/);
assert.doesNotMatch(view, /data-report-period-prompt/);
assert.match(view, /Buat rekap/);

const classroom = { value: 'X RPL' };
const form = {
    querySelector: () => classroom,
};
const select = {
    closest: () => form,
};
resetReportYearFilters(select);
assert.equal(classroom.value, '');

const events = () => ({
    listeners: {},
    addEventListener(name, handler) { this.listeners[name] = handler; },
    fire(name) { return this.listeners[name]?.({ type: name, preventDefault() {} }); },
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
const download = { classList: classList(), attributes: {}, setAttribute(name, value) { this.attributes[name] = value; }, removeAttribute(name) { delete this.attributes[name]; }, dataset: { exportUrl: 'http://localhost/reports/export?academic_year_id=1&service_type=case&format=xlsx' } };
const fallback = { href: '' };
const status = { textContent: '' };
let summaryFocused = false;
const periodPanel = { open: false, querySelector: () => ({ focus() { summaryFocused = true; } }) };
const periodLabel = { textContent: '' };
const mobile = { matches: false, ...events() };
const periodFields = {
    semester: { value: '1', ...events() },
    date_from: { value: '2026-09-22' },
    date_to: { value: '2026-10-21', setCustomValidity(message) { this.validationMessage = message; } },
};
const periodForm = {
    ...events(), dataset: { semesterOneStart: '2026-07-01', semesterOneEnd: '2026-12-31', semesterTwoStart: '2027-01-01', semesterTwoEnd: '2027-06-30' },
    querySelector(selector) { return periodFields[selector.match(/name="([^"]+)"/)[1]]; },
    reportValidity() { return !periodFields.date_to.validationMessage; },
};
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
    '[data-report-preview-download]': download,
    '[data-report-preview-fallback]': fallback,
    '[data-report-period-form]': periodForm,
    '[data-report-period-status]': status,
    '[data-report-period-panel]': periodPanel,
    '[data-report-period-label]': periodLabel,
};
const modal = {
    ...events(), dataset: { previewUrl: 'http://localhost/reports/preview?academic_year_id=1&service_type=case&embedded=1' },
    querySelector(selector) { return nodes[selector]; },
};
globalThis.document = { querySelector: () => modal };
globalThis.ResizeObserver = class { observe() {} };
let timeout;
globalThis.window = {
    matchMedia() { return mobile; },
    setTimeout(callback) { timeout = callback; return 1; },
    clearTimeout() { timeout = undefined; },
};

initReportPreview({ getOrCreateInstance: () => ({ hide() {} }) });
assert.equal(periodPanel.open, true, 'Filter periode terlihat di desktop.');
mobile.matches = true;
mobile.fire('change');
assert.equal(periodPanel.open, false, 'Filter dilipat di HP agar kertas mendapat ruang.');
modal.fire('show.bs.modal');
assert.equal(new URL(frame.src).searchParams.get('date_from'), '2026-09-22');
assert.equal(new URL(frame.src).searchParams.get('date_to'), '2026-10-21');
assert.equal(download.attributes.href, undefined);
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
assert.equal(periodLabel.textContent, '22/09/2026 – 21/10/2026');
assert.equal(frame.classList.contains('d-none'), false);
assert.equal(new URL(download.attributes.href).searchParams.get('date_from'), '2026-09-22');
periodFields.date_from.value = '2026-10-01';
periodForm.fire('input');
assert.equal(print.disabled, true, 'Jangan cetak dokumen lama setelah periode diubah.');
assert.equal(download.attributes.href, undefined, 'Jangan unduh periode lama saat perubahan belum diterapkan.');
periodPanel.open = true;
periodForm.fire('submit');
assert.equal(periodPanel.open, false, 'Filter di HP dilipat setelah diterapkan.');
assert.equal(summaryFocused, true, 'Fokus kembali ke tombol pembuka periode.');
assert.equal(new URL(frame.src).searchParams.get('date_from'), '2026-10-01');
periodForm.fire('input');
frame.fire('load');
assert.equal(print.disabled, true, 'Load yang selesai tidak boleh mengaktifkan cetak saat ada perubahan baru.');
periodForm.fire('submit');
frame.fire('load');
assert.equal(print.disabled, false);
assert.equal(new URL(download.attributes.href).searchParams.get('date_from'), '2026-10-01');
assert.equal(new URL(fallback.href).searchParams.has('embedded'), false);
let finishFonts;
let printCount = 0;
frame.contentDocument.fonts = { ready: new Promise((resolve) => { finishFonts = resolve; }) };
frame.contentWindow = { focus() {}, print() { printCount++; } };
const pendingPrint = print.fire('click');
periodForm.fire('input');
finishFonts();
await pendingPrint;
assert.equal(printCount, 0, 'Perubahan periode saat menunggu font harus membatalkan cetak.');
periodFields.semester.value = '2';
periodFields.semester.fire('change');
assert.equal(periodFields.date_from.value, '2027-01-01');
assert.equal(periodFields.date_to.value, '2027-06-30');
assert.equal(periodFields.date_to.min, '2027-01-01');
const previous = frame.src;
periodFields.date_from.value = '2027-02-10';
periodFields.date_to.value = '2027-02-01';
periodPanel.open = true;
periodForm.fire('submit');
assert.equal(frame.src, previous, 'Rentang terbalik tidak boleh memuat dokumen.');
assert.equal(periodPanel.open, true, 'Filter tidak boleh terlipat saat tanggal belum valid.');
periodFields.date_from.value = '2027-02-01';
periodForm.fire('input');

retry.fire('click');
frame.fire('error');
assert.equal(periodPanel.open, true, 'Filter kembali terbuka setelah gagal agar periode bisa diperbaiki.');
assert.equal(message.textContent, 'Pratinjau belum dapat ditampilkan.');
assert.equal(print.disabled, true);
assert.equal(frame.classList.contains('d-none'), true);

retry.fire('click');
timeout();
assert.equal(message.textContent, 'Pratinjau belum dapat ditampilkan.');
assert.equal(print.disabled, true);

mobile.matches = false;
mobile.fire('change');
assert.equal(periodPanel.open, true, 'Filter kembali terlihat saat beralih ke desktop.');
console.log('Pesan validasi pratinjau laporan, retry, sukses, error, dan timeout valid.');
