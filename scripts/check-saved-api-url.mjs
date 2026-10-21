import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { initApiSiswaPreview } from '../resources/js/api-siswa-preview.js';
import { initEtatibApiPreview } from '../resources/js/etatib-api-preview.js';
import { clearSavedApiSecrets, initEtatibSchedule, initSavedApiUrls, revealSavedApiUrl, selectedApiSource, syncSavedApiSource } from '../resources/js/saved-api-url.js';

const savedView = await readFile('resources/views/pages/data-master/_saved-api-url.blade.php', 'utf8');
const etatibView = await readFile('resources/views/pages/data-master/_etatib-api-import.blade.php', 'utf8');
const savedModalView = await readFile('resources/views/pages/data-master/_saved-api-url-modal.blade.php', 'utf8');
assert.match(savedModalView, /data-saved-api-new-url/);
assert.match(savedModalView, /type="password" name="api_url"/);
assert.equal((savedView.match(/name="api_url"/g) || []).length, 1);
assert.doesNotMatch(savedView, /_saved_api_url|data-one-time-api-url/);
assert.match(etatibView, /route\('data-master.etatib.automatic.schedule'\)/);
assert.match(etatibView, /name="schedule_days\[\]"/);
assert.match(etatibView, /name="schedule_times\[\]"/);

const choice = { checked: true };
const url = { value: 'https://manual.example/api', disabled: false, required: true, dataset: { apiPlaceholder: 'https://api.example/data' } };
const sourceForm = {
    elements: { namedItem: () => url },
    querySelector(selector) {
        return selector === '[data-saved-api-choice]' ? choice : null;
    },
};
syncSavedApiSource(sourceForm);
assert.equal(url.value, '');
assert.equal(url.disabled, true);
assert.equal(url.required, false);
assert.equal(url.placeholder, 'API tersimpan (disembunyikan)');
assert.equal(selectedApiSource(sourceForm), 'saved');
choice.checked = false;
syncSavedApiSource(sourceForm);
assert.equal(url.disabled, false);
assert.equal(url.required, true);
assert.equal(url.placeholder, url.dataset.apiPlaceholder);
url.value = 'https://manual.example/api';
assert.equal(selectedApiSource(sourceForm), url.value);

const revealed = { value: '' };
const password = { value: 'rahasia' };
const newUrl = { value: 'https://new.example/api' };
const error = { textContent: '', hidden: true };
const revealFields = { hidden: true };
const hideButton = { hidden: true };
const nodes = {
    '[data-saved-api-revealed]': revealed,
    '[data-saved-api-password]': password,
    '[data-saved-api-new-url]': newUrl,
    '[data-saved-api-error]': error,
    '[data-saved-api-reveal-fields]': revealFields,
    '[data-saved-api-hide]': hideButton,
};
const section = { dataset: { revealUrl: '/api-connections/dapodik/reveal' }, querySelector: (selector) => nodes[selector] };
globalThis.document = { querySelector: () => ({ content: 'csrf' }) };
let requested;
assert.equal(await revealSavedApiUrl(section, async (path, options) => {
    requested = [path, options];
    return { ok: true, json: async () => ({ api_url: 'https://saved.example/private?key=abc' }) };
}), true);
assert.equal(requested[0], section.dataset.revealUrl);
assert.equal(requested[1].method, 'POST');
assert.equal(requested[1].body.get('current_password'), 'rahasia');
assert.equal(revealed.value, 'https://saved.example/private?key=abc');
assert.equal(password.value, '');
assert.equal(revealFields.hidden, false);
clearSavedApiSecrets(section);
assert.equal(revealed.value, '');
assert.equal(newUrl.value, '');
assert.equal(revealFields.hidden, true);
assert.equal(hideButton.hidden, true);

password.value = 'salah';
assert.equal(await revealSavedApiUrl(section, async () => ({
    ok: false, json: async () => ({ errors: { current_password: ['Kata sandi salah.'] } }),
})), false);
assert.equal(revealed.value, '');
assert.equal(error.textContent, 'Kata sandi salah.');
assert.equal(error.hidden, false);
assert.equal(await revealSavedApiUrl(section, async () => { throw new Error('network'); }), false);
assert.equal(error.textContent, 'Tautan tidak dapat ditampilkan. Coba lagi.');
clearSavedApiSecrets(section);
assert.equal(password.value, '');

const handlers = () => ({
    listeners: {},
    addEventListener(name, handler) { this.listeners[name] = handler; },
    fire(name) { return this.listeners[name]?.({ preventDefault() {} }); },
});
const modalNode = { ...handlers(), hasAttribute: () => false };
const saveForm = handlers();
const edit = handlers();
const reveal = handlers();
const submit = { textContent: '' };
const editFields = { hidden: false };
const title = { textContent: '' };
Object.assign(nodes, {
    '[data-saved-api-modal]': modalNode,
    '[data-saved-api-form]': saveForm,
    '[data-saved-api-source-form]': sourceForm,
    '[data-saved-api-submit]': submit,
    '[data-saved-api-edit-button]': edit,
    '[data-saved-api-reveal-button]': reveal,
    '[data-saved-api-edit-fields]': editFields,
    '[data-saved-api-title]': title,
});
Object.assign(choice, handlers());
Object.assign(hideButton, handlers());
const pagehideHandlers = [];
globalThis.window = { addEventListener(name, handler) { if (name === 'pagehide') pagehideHandlers.push(handler); } };
let focused;
newUrl.focus = () => { focused = 'url'; };
password.focus = () => { focused = 'password'; };
initSavedApiUrls({ querySelectorAll: (selector) => selector === '[data-saved-api-source-form]' ? [sourceForm] : [section] }, {
    Modal: { getOrCreateInstance: () => ({ show() {}, hide() {} }) },
});
url.value = 'https://manual.example/new?token=private';
edit.fire('click');
assert.equal(newUrl.value, url.value, 'Simpan menggunakan URL kolom utama tanpa mengetik ulang.');
modalNode.fire('shown.bs.modal');
assert.equal(focused, 'password');
modalNode.fire('hidden.bs.modal');
assert.equal(newUrl.value, '');
choice.checked = true;
choice.fire('change');
assert.equal(url.value, '');
edit.fire('click');
assert.equal(newUrl.value, '', 'Jangan menyalin penanda URL tersimpan ke form simpan.');
modalNode.fire('shown.bs.modal');
assert.equal(focused, 'url');
reveal.fire('click');
assert.equal(newUrl.disabled, true);
assert.equal(newUrl.required, false);
assert.equal(editFields.hidden, true);
choice.checked = false;
choice.fire('change');
url.value = 'https://manual.example/clear-on-leave';
pagehideHandlers.forEach((handler) => handler());
assert.equal(url.value, '');

globalThis.document.createElement = () => ({ className: '', textContent: '' });
let submittedFields;
globalThis.FormData = class {
    constructor(form) {
        submittedFields = {
            use_saved_api: form.choice.checked ? '1' : undefined,
            api_url: form.url.disabled ? undefined : form.url.value,
        };
    }
};
globalThis.fetch = async () => ({ ok: false, json: async () => ({ errors: { api_url: ['Pratinjau ditolak.'] } }) });
const previewForm = () => {
    const form = {
        choice: { checked: true }, url: { value: '', disabled: true },
        dataset: { previewUrl: '/preview' }, handlers: {},
        elements: { namedItem: () => null },
        addEventListener(name, handler) { this.handlers[name] = handler; },
        querySelector: () => ({ disabled: false }),
        querySelectorAll: () => [],
        reportValidity: () => true,
    };
    return form;
};
const previewBody = { replaceChildren() {} };
const previewModal = (nodes) => ({
    handlers: {},
    addEventListener(name, handler) { this.handlers[name] = handler; },
    querySelector(selector) { return nodes[selector]; },
});
const Modal = { getOrCreateInstance: () => ({ show() {} }) };
const siswaForm = previewForm();
const siswaModal = previewModal({
    '[data-api-siswa-preview-body]': previewBody,
    '[data-api-siswa-confirm-import]': { disabled: true, addEventListener() {} },
});
await initApiSiswaPreview({
    querySelector: (selector) => selector === '[data-api-siswa-import-form]' ? siswaForm : siswaModal,
}, { Modal });
await siswaForm.handlers.submit({ preventDefault() {} });
assert.deepEqual(submittedFields, { use_saved_api: '1', api_url: undefined });

const etatibForm = previewForm();
const etatibModal = previewModal({
    '[data-etatib-preview-body]': { ...previewBody, addEventListener() {} },
    '[data-etatib-confirm]': { disabled: true, addEventListener() {} },
    '[data-etatib-confirm-automatic]': { disabled: true, addEventListener() {}, textContent: '' },
    '[data-etatib-automatic-fields]': { hidden: true },
    '[data-etatib-automatic-password]': { disabled: true, required: false, value: '' },
});
await initEtatibApiPreview({
    querySelector: (selector) => selector === '[data-etatib-api-form]' ? etatibForm : etatibModal,
}, { Modal });
await etatibForm.handlers.submit({ preventDefault() {} });
assert.deepEqual(submittedFields, { use_saved_api: '1', api_url: undefined });

const scheduleRows = [];
const makeScheduleNode = () => ({
    dataset: {}, handlers: {}, children: [],
    addEventListener(name, handler) { this.handlers[name] = handler; },
    setAttribute() {}, focus() {},
    append(...children) { this.children.push(...children); },
});
globalThis.document.createElement = makeScheduleNode;
const times = {
    handlers: {},
    querySelectorAll: () => scheduleRows,
    querySelector: () => scheduleRows[0],
    addEventListener(name, handler) { this.handlers[name] = handler; },
    append(row) { scheduleRows.push(row); },
};
const add = { addEventListener(name, handler) { this.handler = handler; } };
const day = {
    checked: false, validity: '',
    setCustomValidity(value) { this.validity = value; },
    reportValidity() { this.reported = true; },
    addEventListener(name, handler) { this.handler = handler; },
};
const schedulePassword = { value: 'rahasia' };
const schedule = {
    handlers: {},
    querySelector(selector) {
        if (selector === '[data-etatib-schedule-times]') return times;
        if (selector === '[data-etatib-schedule-password]') return schedulePassword;
        if (selector === '[data-etatib-schedule-add]') return add;
        return selector.endsWith(':checked') ? (day.checked ? day : null) : day;
    },
    querySelectorAll: () => [day],
    addEventListener(name, handler) { this.handlers[name] = handler; },
};

initEtatibSchedule({ querySelector: () => schedule });
assert.equal(scheduleRows.length, 1);
add.handler();
assert.equal(scheduleRows.length, 2);
let blocked = false;
schedule.handlers.submit({ preventDefault() { blocked = true; } });
assert.equal(blocked, true);
assert.equal(day.reported, true);
day.checked = true;
day.handler();
assert.equal(day.validity, '');
blocked = false;
schedule.handlers.submit({ preventDefault() { blocked = true; } });
assert.equal(blocked, false);
times.handlers.click({ target: { closest: () => ({ closest: () => ({ remove() { scheduleRows.pop(); } }) }) } });
assert.equal(scheduleRows.length, 1);
pagehideHandlers.forEach((handler) => handler());
assert.equal(schedulePassword.value, '');

console.log('URL API tersimpan dan jadwal e-Tatib: sumber, pratinjau, secret, dan waktu lulus.');
