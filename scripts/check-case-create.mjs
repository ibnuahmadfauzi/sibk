import assert from 'node:assert/strict';
import { caseSourceLayout, matchCaseStudents } from '../resources/js/case-create.js';

assert.deepEqual(caseSourceLayout('e_tatib'), { etatib: true, referrer: false, wideBasicFields: false });
assert.deepEqual(caseSourceLayout('rujukan'), { etatib: false, referrer: true, wideBasicFields: false });
assert.deepEqual(caseSourceLayout('murid_datang_sendiri'), { etatib: false, referrer: false, wideBasicFields: true });

const students = [
    { id: 1, nisn: '0105303522', name: 'Safanah Senja Putri Aqsa', classroom_name: '10 PH 3' },
    { id: 2, nisn: '0105303523', name: 'Budi Santoso', classroom_name: '10 PH 2' },
];
assert.deepEqual(matchCaseStudents(students, 'safanah').map((student) => student.id), [1]);
assert.deepEqual(matchCaseStudents(students, '03523').map((student) => student.id), [2]);
assert.deepEqual(matchCaseStudents(students, '  '), []);
assert.deepEqual(matchCaseStudents(students, 'tidak ada'), []);

console.log('Alur sumber dan pencarian murid Catat Permasalahan valid.');
