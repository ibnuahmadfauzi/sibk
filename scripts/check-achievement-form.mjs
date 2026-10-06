import assert from 'node:assert/strict';
import { invalidateStudentSelection, selectAchievementStudent } from '../resources/js/achievement-form.js';

const state = {
    studentId: { value: '' },
    lookup: { value: '', setCustomValidity(value) { this.error = value; }, setAttribute() {} },
    results: { classList: { add() {} } },
    selected: { textContent: '', classList: { add() {}, remove() {} } },
};
selectAchievementStudent(state, { id: 12, name: 'Murid Uji', nisn: '00123', classroom: 'XI RPL 2' });
assert.equal(state.studentId.value, '12');
assert.match(state.selected.textContent, /00123.*XI RPL 2/);
state.lookup.value = 'Murid lain';
invalidateStudentSelection(state);
assert.equal(state.studentId.value, '');
assert.equal(state.selected.textContent, '');
assert.equal(state.lookup.error, '');
console.log('Pencarian prestasi: pilihan identitas dan pembatalan pilihan lama lulus.');
