import assert from 'node:assert/strict';
import { handleStudentRowClick, nextSortState, sortItems } from '../resources/js/table-sort.js';

const rows = [
    { name: 'Zaki', count: 12, date: '2026-09-12' },
    { name: 'Adi', count: 3, date: '2026-08-01' },
    { name: 'Budi', count: 20, date: '2026-10-02' },
];
let state = 'none';
state = nextSortState(state);
assert.equal(state, 'ascending');
assert.deepEqual(sortItems(rows, (row) => row.count, 'number', state).map((row) => row.count), [3, 12, 20]);
state = nextSortState(state);
assert.equal(state, 'descending');
assert.deepEqual(sortItems(rows, (row) => row.date, 'date', state).map((row) => row.name), ['Budi', 'Zaki', 'Adi']);
state = nextSortState(state);
assert.equal(state, 'none');
assert.deepEqual(sortItems(rows, (row) => row.name, 'text', state), rows);
assert.deepEqual(sortItems(rows, (row) => row.name, 'text', 'ascending').map((row) => row.name), ['Adi', 'Budi', 'Zaki']);

const row = { querySelector: () => ({ href: '/students/12' }) };
const target = (interactive = false, inRow = true) => ({
    closest(selector) {
        if (selector.startsWith('a,')) return interactive ? {} : null;
        return inRow ? row : null;
    },
});
const visited = [];
const navigate = (url) => visited.push(url);
handleStudentRowClick({ target: target() }, navigate);
handleStudentRowClick({ target: target(true) }, navigate);
handleStudentRowClick({ target: target(false, false) }, navigate);
handleStudentRowClick({ target: target(), defaultPrevented: true }, navigate);
assert.deepEqual(visited, ['/students/12']);

console.log('Tri-state, numeric, date, text, and original order OK.');
