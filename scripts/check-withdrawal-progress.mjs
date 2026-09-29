import assert from 'node:assert/strict';

import {
    applyStudentSelection,
    buildStudentOption,
    filterStudents,
    fillDetailModal,
    initWithdrawalProgress,
    readDetailTrigger,
    renderLookupResults,
    renderPopoverHistory,
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

const trigger = {
    dataset: {
        studentName: '<img src=x>',
        studentNisn: '0012345678',
        classroom: 'X RPL 1',
        recordedOn: '26 September 2026',
        recordedDay: 'Sabtu',
        teacherName: 'Guru BK',
        progressLabel: 'Berkas pengunduran masih progres',
        reason: 'Permintaan dari keluarga.',
        note: 'Pertemuan dengan keluarga.',
        followUps: JSON.stringify([{ followUpDateFormatted: '26 Sep 2026', progressLabel: 'Masih progres', notes: '<b>aman</b>' }]),
    },
};
const modalFields = {};
for (const key of ['studentName', 'studentNisn', 'classroom', 'recordedOn', 'teacherName', 'progressLabel', 'reason', 'note']) {
    modalFields[`[data-detail="${key}"]`] = { textContent: '' };
}
modalFields['[data-detail-history]'] = fakeElement('ol');
const modal = { querySelector: (selector) => modalFields[selector] ?? null };
const detail = readDetailTrigger(trigger);
fillDetailModal(modal, detail, { createElement: fakeElement });
assert.equal(modalFields['[data-detail="studentName"]'].textContent, '<img src=x>');
assert.equal(modalFields['[data-detail="recordedOn"]'].textContent, '26 September 2026 (Sabtu)');
assert.equal(modalFields['[data-detail-history]'].children[0].children[1].children[1].textContent, '<b>aman</b>');

const popover = fakeElement('div');
renderPopoverHistory(popover, [
    { follow_up_date_formatted: '27 Sep 2026', progress_label: 'Masuk TU', notes: 'Diterima.' },
    { follow_up_date_formatted: '26 Sep 2026', progress_label: 'Masih progres', notes: null },
], fakeElement);
assert.equal(popover.children.length, 2);
assert.match(popover.children[0].className, /latest/);
assert.equal(popover.children[0].children[1].children[1].textContent, 'Masuk TU');

const listeners = new Map();
const root = {
    querySelector() { return null; },
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

console.log('Withdrawal progress checks passed.');
