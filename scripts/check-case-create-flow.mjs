import assert from 'node:assert/strict';
import { initCaseCreate } from '../resources/js/case-create.js';

class Element {
    constructor() {
        this.dataset = {};
        this.children = [];
        this.handlers = new Map();
        this.classes = new Set();
        this.classList = {
            add: (name) => this.classes.add(name),
            contains: (name) => this.classes.has(name),
            toggle: (name, force) => {
                if (force) this.classes.add(name);
                else this.classes.delete(name);
            },
        };
        this.value = '';
        this.tagName = 'INPUT';
    }
    addEventListener(name, handler) {
        const handlers = this.handlers.get(name) ?? [];
        handlers.push(handler);
        this.handlers.set(name, handlers);
    }
    dispatchEvent(event) {
        Object.defineProperty(event, 'currentTarget', { value: this, configurable: true });
        event.stopImmediatePropagation = () => { event.stopped = true; };
        event.preventDefault = () => { event.prevented = true; };
        for (const handler of this.handlers.get(event.type) ?? []) {
            handler(event);
            if (event.stopped) break;
        }
        return true;
    }
    click() { this.dispatchEvent({ type: 'click', target: this }); }
    append(...children) { this.children.push(...children); }
    replaceChildren(...children) { this.children = children; }
    setAttribute(name, value) { this[name] = value; }
    contains(element) { return this.children.includes(element); }
    querySelector(selector) { return selector === 'button' ? this.children.find((child) => child.tagName === 'BUTTON') : null; }
    querySelectorAll() { return this.fields ?? []; }
    setCustomValidity(value) { this.validationMessage = value; }
    reportValidity() { return !this.validationMessage; }
    focus() {}
}

const nodes = new Map();
const node = (selector) => {
    if (!nodes.has(selector)) nodes.set(selector, new Element());
    return nodes.get(selector);
};
const source = node('#sumber');
source.selectedOptions = [{ dataset: { code: 'e_tatib' } }];
const summary = node('[data-case-etatib-summary]');
summary.fields = ['violation_type', 'points', 'source_total_points'].map((key) => {
    const field = new Element();
    field.dataset.caseField = key;
    return field;
});
const record = { id: 17, student_id: 29, student_name: 'Safanah', nisn: '0105303522', classroom_name: '10 PH 3', classroom_id: 5, violation_type: 'Terlambat', points: 5, source_total_points: 15 };
const unmapped = { ...record, id: 18, student_id: null, classroom_id: null, student_name: 'Murid Baru' };
const form = new Element();
form.dataset = {
    caseStudents: '[]', caseRecords: JSON.stringify([record, unmapped]),
    casePagination: JSON.stringify({ current_page: 1, last_page: 1, total: 2 }),
    caseSearchUrl: '/cases/create?temporary_nisn=0105303522',
};
form.querySelector = node;
form.elements = [...nodes.values()];
form.reset = () => { source.selectedOptions = [{ dataset: { code: 'e_tatib' } }]; };
node('[data-draft-restored]').classList.add('d-none');
const requests = [];
const request = (url, options) => new Promise((resolve) => requests.push({ url, options, resolve }));
globalThis.document = { createElement: (tag) => { const element = new Element(); element.tagName = tag.toUpperCase(); return element; } };
globalThis.window = { location: { origin: 'https://example.test' }, confirm: () => true };
globalThis.CustomEvent = class { constructor(type, options = {}) { this.type = type; this.detail = options.detail; } };

initCaseCreate(form, request);
assert.equal(node('#etatib_results_container').children.length, 2);
node('#etatib_results_container').children[0].click();
assert.equal(node('#hidden_etatib_record_id').value, 17);
assert.equal(node('#hidden_student_id').value, 29);
assert.equal(summary.fields[0].textContent, 'Terlambat');
assert.equal(node('[data-case-etatib-results]').classList.contains('d-none'), true);
node('[data-case-change-etatib]').click();
node('#etatib_results_container').children[1].click();
assert.equal(node('[data-case-etatib-classroom-choice]').classList.contains('d-none'), false);
assert.equal(node('#etatib_classroom_select').required, true);
node('#etatib_classroom_select').value = '5';
node('#etatib_classroom_select').dispatchEvent({ type: 'change' });
assert.equal(node('[data-case-classroom-value]').value, '5');
node('[data-case-change-etatib]').click();
assert.equal(node('#etatib_classroom_select').required, false);
assert.equal(node('#etatib_classroom_select').disabled, true);

source.selectedOptions = [{ dataset: { code: 'rujukan' } }];
source.dispatchEvent({ type: 'change' });
assert.equal(node('#hidden_etatib_record_id').value, '');
assert.equal(node('#hidden_student_id').value, '');
assert.equal(node('[data-case-student-section]').classList.contains('d-none'), false);
assert.equal(node('[data-case-referrer]').classList.contains('d-none'), false);

source.selectedOptions = [{ dataset: { code: 'e_tatib' } }];
source.dispatchEvent({ type: 'change' });
assert.equal(requests.length, 1);
assert.equal(requests[0].url.searchParams.get('temporary_nisn'), '0105303522');
source.selectedOptions = [{ dataset: { code: 'rujukan' } }];
source.dispatchEvent({ type: 'change' });
requests[0].resolve({ ok: true, json: async () => ({ data: [record], current_page: 1, last_page: 1, total: 1 }) });
await new Promise((resolve) => setTimeout(resolve, 0));
assert.equal(node('[data-case-etatib-section]').classList.contains('d-none'), true);
assert.equal(node('#hidden_etatib_record_id').value, '');

let confirmation;
document.dispatchEvent = (event) => { confirmation = event.detail; };
form.dataset.caseDirty = 'true';
node('#student_name').value = 'Isian yang belum disimpan';
node('[data-case-clear]').click();
assert.equal(confirmation.dataset.confirmTone, 'danger');
assert.equal(node('#student_name').value, 'Isian yang belum disimpan');
form.elements = [...nodes.values()];
confirmation.onConfirm();
assert.equal(node('#student_name').value, '');
assert.equal(form.dataset.caseDirty, 'false');

console.log('Pilihan, pergantian sumber, dan respons pencarian lama valid.');
