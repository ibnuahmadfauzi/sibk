import assert from 'node:assert/strict';
import { initAutoFilters } from '../resources/js/auto-filter.js';

const control = (props = {}) => {
    const listeners = {};
    return { dataset: {}, value: '', addEventListener: (name, fn) => { listeners[name] = fn; }, fire: (name) => listeners[name]?.(), ...props };
};
const search = control({ tagName: 'INPUT', type: 'search' });
const year = control({ tagName: 'SELECT', value: '2026', dataset: { filterDefault: '2026' } });
const button = control();
let submissions = 0;
let destination;
const form = control({
    action: '/cases', dataset: { filterResetUrl: '/cases?tab=konsultasi' },
    querySelectorAll: () => [search, year], querySelector: () => button,
    reportValidity: () => true, requestSubmit: () => { submissions++; form.fire('submit'); },
});
initAutoFilters({ querySelectorAll: () => [form] }, (url) => { destination = url; });
assert.equal(button.textContent, 'Filter');
search.value = 'Nadia'; search.fire('input');
search.value = 'Nadia Putri'; search.fire('input');
assert.equal(button.textContent, 'Reset');
await new Promise((resolve) => setTimeout(resolve, 500));
assert.equal(submissions, 1);
year.value = '2025'; year.fire('change');
assert.equal(submissions, 2);
search.fire('input'); button.fire('click');
assert.equal(destination, '/cases?tab=konsultasi');
await new Promise((resolve) => setTimeout(resolve, 500));
assert.equal(submissions, 2, 'Reset cancels pending automatic submission');
search.fire('compositionstart'); search.fire('input');
await new Promise((resolve) => setTimeout(resolve, 500));
assert.equal(submissions, 2);
search.fire('compositionend');
await new Promise((resolve) => setTimeout(resolve, 500));
assert.equal(submissions, 3);
console.log('Automatic filter, debounce, defaults, reset, and composition checks passed.');
