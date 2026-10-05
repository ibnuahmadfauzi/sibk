import assert from 'node:assert/strict';

import { filterConsultationStudents, initConsultationCreateForm } from '../resources/js/consultation-create.js';

const students = [
    { id: 1, nisn: '0012345678', name: 'Ayu Pratiwi', classroom: 'X RPL 1' },
    { id: 2, nisn: '0098765432', name: 'Budi Santoso', classroom: 'X RPL 2' },
];

assert.deepEqual(filterConsultationStudents(students, '001234').map(({ id }) => id), [1]);
assert.deepEqual(filterConsultationStudents(students, 'bUdI').map(({ id }) => id), [2]);
assert.deepEqual(filterConsultationStudents(students, 'RPL'), []);
assert.deepEqual(filterConsultationStudents(students, ''), []);

const element = () => ({
    value: '',
    children: [],
    listeners: {},
    attrs: {},
    classes: new Set(['d-none']),
    classList: {
        add(name) { this.owner.classes.add(name); },
        remove(name) { this.owner.classes.delete(name); },
        contains(name) { return this.owner.classes.has(name); },
    },
    addEventListener(type, handler) { (this.listeners[type] ??= []).push(handler); },
    dispatch(type, event = {}) { (this.listeners[type] ?? []).forEach((handler) => handler({ target: this, ...event })); },
    dispatchEvent(event) { this.dispatch(event.type, event); },
    setAttribute(name, value) { this.attrs[name] = value; },
    replaceChildren(...children) { this.children = children; },
    append(...children) { this.children.push(...children); },
    querySelector(selector) { return selector === 'button' ? this.children.find((child) => child.type === 'button') : null; },
    querySelectorAll(selector) { return selector === 'button' ? this.children.filter((child) => child.type === 'button') : []; },
    contains(target) { return this === target || this.children.includes(target); },
    focus() { globalThis.document.activeElement = this; this.dispatch('focus'); },
});
const make = () => {
    const node = element();
    node.classList.owner = node;
    return node;
};

globalThis.document = { createElement: make, activeElement: null };
globalThis.Event = class Event { constructor(type) { this.type = type; } };
globalThis.Option = class Option {
    constructor(label, value) { this.label = label; this.value = value; this.dataset = {}; }
    remove() { this.owner.options = this.owner.options.filter((option) => option !== this); }
};

const form = make();
const nisn = make();
const name = make();
const classroom = make();
classroom.options = [{ value: '' }, { value: '11' }, { value: '12' }];
classroom.add = (option) => { option.owner = classroom; classroom.options.push(option); };
classroom.querySelectorAll = (selector) => selector === '[data-lookup-classroom]'
    ? classroom.options.filter((option) => option.dataset?.lookupClassroom) : [];
const studentId = make();
const results = make();
const clear = make();
const nodes = {
    '#consultation-create-form': form,
    '#student_nisn': nisn,
    '#student_name': name,
    '#manual_classroom_select': classroom,
    '#hidden_student_id': studentId,
    '#student_lookup_results': results,
    '[data-clear-fields]': clear,
};
const page = make();
page.dataset = { consultationStudents: JSON.stringify(students.map((student, index) => ({ ...student, classroom_id: index === 0 ? 11 : 99 }))) };
page.querySelector = (selector) => nodes[selector];
page.contains = (target) => Object.values(nodes).includes(target);
initConsultationCreateForm(page);

nisn.value = 'ayu';
nisn.dispatch('input');
assert.equal(results.classList.contains('d-none'), false);
assert.equal(results.querySelectorAll('button').length, 1);
results.querySelector('button').dispatch('click');
assert.equal(studentId.value, '1');
assert.equal(nisn.value, '0012345678');
assert.equal(name.value, 'Ayu Pratiwi');
assert.equal(classroom.value, '11');
assert.equal(results.classList.contains('d-none'), true);

name.value = 'Nama manual';
name.dispatch('input');
assert.equal(studentId.value, '');
assert.equal(results.classList.contains('d-none'), true);

nisn.value = 'budi';
nisn.dispatch('input');
const option = results.querySelector('button');
option.focus();
results.dispatch('keydown', { key: 'Escape' });
assert.equal(globalThis.document.activeElement, nisn);
assert.equal(results.classList.contains('d-none'), true);

nisn.focus();
results.querySelector('button').dispatch('click');
assert.equal(studentId.value, '2');
assert.equal(classroom.value, '99');
assert.equal(classroom.options.some((item) => item.value === '99'), true);
name.value = 'Nama manual';
name.dispatch('input');
assert.equal(studentId.value, '');
assert.equal(classroom.value, '');
assert.equal(classroom.options.some((item) => item.value === '99'), false);

console.log('Pencarian murid untuk catatan konsultasi sesuai.');
