import assert from 'node:assert/strict';

import {
    applyStudentSelection,
    buildStudentOption,
    filterStudents,
    initWithdrawalProgress,
    renderLookupResults,
    renderPopoverHistory,
    positionPopover,
    validateLookupSelection,
} from '../resources/js/withdrawal-progress.js';

const students = [
    { id: 1, nisn: '0012345678', name: 'Murid Scope', classroom: 'X RPL 1' },
    { id: 2, nisn: '0098765432', name: 'Murid Lain', classroom: 'X RPL 2' },
];

const fakeElement = (tag) => ({
    tag,
    className: '',
    textContent: '',
    children: [],
    listeners: {},
    attributes: {},
    append(...nodes) { this.children.push(...nodes); },
    replaceChildren(...nodes) { this.children = [...nodes]; },
    addEventListener(type, listener) { this.listeners[type] = listener; },
    setAttribute(name, value) { this.attributes[name] = value; },
});

assert.deepEqual(filterStudents(students, 'scope').map(({ id }) => id), [1]);
assert.deepEqual(filterStudents(students, '0012345678').map(({ id }) => id), [1]);
assert.deepEqual(filterStudents(students, 'RPL').map(({ id }) => id), [1, 2]);

let picked;
const option = buildStudentOption(students[0], (student) => { picked = student; }, fakeElement);
assert.equal(option.attributes.role, 'option');
assert.equal(option.children.map(({ textContent }) => textContent).join('|'), '0012345678|Murid Scope|X RPL 1');
option.listeners.click();
assert.equal(picked.id, 1);

const state = {
    lookup: { value: '', setCustomValidity() {}, setAttribute() {} },
    results: { classList: { add() {}, remove() {} } },
    studentId: { value: '' },
    studentName: { value: '' },
    studentClassroom: { value: '' },
};
applyStudentSelection(state, students[0]);
assert.equal(state.studentId.value, '1');
assert.equal(state.lookup.value, '0012345678 — Murid Scope');
assert.equal(state.studentClassroom.value, 'X RPL 1');

let reported = '';
const invalidState = {
    lookup: {
        setCustomValidity(message) { this.message = message; },
        reportValidity() { reported = this.message; },
    },
    studentId: { value: '' },
};
assert.equal(validateLookupSelection(invalidState), false);
assert.equal(reported, 'Pilih murid dari saran yang tersedia.');

const rendered = [];
const resultsState = {
    lookup: { value: 'scope', disabled: false, setAttribute(name, value) { this[name] = value; } },
    results: {
        classList: { add() {}, remove() {} },
        replaceChildren() { rendered.length = 0; },
        append(node) { rendered.push(node); },
    },
};
assert.equal(renderLookupResults(resultsState, students, { createElement: fakeElement }), true);
assert.equal(rendered[0].children[1].textContent, 'Murid Scope');

const popover = fakeElement('div');
renderPopoverHistory(popover, [
    { follow_up_date_formatted: '27 Sep 2026', progress_label: 'Masuk TU', notes: 'Diterima.' },
    { follow_up_date_formatted: '26 Sep 2026', progress_label: 'Masih progres', notes: null },
], fakeElement);
assert.equal(popover.children.length, 2);
assert.match(popover.children[0].className, /latest/);
assert.equal(popover.children[0].children[1].children[1].textContent, 'Masuk TU');

// Popover positioning: normal (below button) vs flipped (above button when near bottom)
const popoverEl = {
    style: {},
    offsetWidth: 290,
    offsetHeight: 220,
};
const mockEnv = { window: { innerWidth: 1024, innerHeight: 768 } };
const topBtn = { getBoundingClientRect: () => ({ left: 100, top: 100, bottom: 130, right: 200 }) };
positionPopover(popoverEl, topBtn, mockEnv);
assert.equal(popoverEl.style.top, '138px'); // 130 + 8
assert.equal(popoverEl.style.left, '100px');

// When button is near bottom of viewport, popover should flip above
const bottomBtn = { getBoundingClientRect: () => ({ left: 100, top: 600, bottom: 630, right: 200 }) };
positionPopover(popoverEl, bottomBtn, mockEnv);
// top (630 + 8 = 638) + ph (220) = 858 > 768 - 8 (760), flips: rect.top (600) - ph (220) - 8 = 372px
assert.equal(popoverEl.style.top, '372px');
assert.equal(popoverEl.style.left, '100px');

const listeners = new Map();
const noteRow = { hidden: true, classList: { toggle(name, force) { assert.equal(name, 'd-none'); noteRow.hidden = force; } } };
const root = {
    querySelector() { return null; },
    getElementById(id) { return id === 'withdrawal-note-1' ? noteRow : null; },
    addEventListener(type, listener) {
        const queue = listeners.get(type) ?? [];
        queue.push(listener);
        listeners.set(type, queue);
    },
};
assert.equal(await initWithdrawalProgress(root, { window: { addEventListener() {} } }), true);
assert.equal(await initWithdrawalProgress(root, { window: { addEventListener() {} } }), false);
assert.equal(listeners.get('click').length, 1);
assert.equal(listeners.get('keydown').length, 1);
const noteButton = {
    title: 'Tampilkan catatan',
    dataset: { studentName: 'Murid Scope' },
    attributes: { 'aria-controls': 'withdrawal-note-1', 'aria-expanded': 'false' },
    getAttribute(name) { return this.attributes[name]; },
    setAttribute(name, value) { this.attributes[name] = value; },
};
const noteClick = { target: { closest(selector) { return selector === '[data-withdrawal-note-toggle]' ? noteButton : null; } } };
listeners.get('click')[0](noteClick);
assert.equal(noteRow.hidden, false);
assert.equal(noteButton.attributes['aria-expanded'], 'true');
assert.equal(noteButton.attributes['aria-label'], 'Tutup catatan pengunduran diri Murid Scope');
listeners.get('click')[0](noteClick);
assert.equal(noteRow.hidden, true);
assert.equal(noteButton.attributes['aria-expanded'], 'false');

console.log('Withdrawal progress checks passed.');
