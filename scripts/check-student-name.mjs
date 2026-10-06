import assert from 'node:assert/strict';
import { displayStudentName } from '../resources/js/student-name.js';

assert.equal(displayStudentName('AATHIRAH ZHINTA CHERA HERWANTO'), 'Aathirah Zhinta Chera Herwanto');
assert.equal(displayStudentName('  NUR-AINI  DWI '), 'Nur-Aini  Dwi');
assert.equal(displayStudentName('ÉLISA D’ANGELO'), 'Élisa D’Angelo');
assert.equal(displayStudentName(null), '');

console.log('Tampilan nama murid sesuai.');
