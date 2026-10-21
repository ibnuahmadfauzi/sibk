import assert from 'node:assert/strict';
import { initWebTheme } from '../resources/js/web-theme.js';

const events = () => ({
    handlers: {},
    addEventListener(name, handler) { this.handlers[name] = handler; },
    fire(name) { this.handlers[name]?.(); },
});
const custom = { hidden: true, disabled: true };
const choices = ['default', 'pine', 'slate', 'custom'].map((value) => ({ value, ...events() }));
let selected = choices[0];
const picker = { value: '#2f6fc6', ...events() };
const hex = { value: '#2f6fc6', ...events() };
const field = { querySelector: (selector) => selector === '[data-theme-color-picker]' ? picker : hex };
const form = {
    querySelector: (selector) => selector === '[data-custom-palette-fields]' ? custom : selected,
    querySelectorAll: (selector) => selector === '[name="theme"]' ? choices : [field],
};
initWebTheme({ querySelector: () => form });
assert.equal(custom.hidden, true);
assert.equal(custom.disabled, true, 'Palet custom tidak boleh menghalangi simpan palet bawaan.');
selected = choices[3];
selected.fire('change');
assert.equal(custom.hidden, false);
assert.equal(custom.disabled, false);
picker.value = '#1f6658';
picker.fire('input');
assert.equal(hex.value, '#1f6658', 'Color picker dan HEX memakai satu nilai.');
hex.value = '#465A75';
hex.fire('input');
assert.equal(picker.value, '#465A75');
hex.value = '#12';
hex.fire('input');
assert.equal(picker.value, '#465A75', 'HEX belum lengkap tidak boleh merusak color picker.');
selected = choices[1];
selected.fire('change');
assert.equal(custom.disabled, true);
assert.equal(custom.hidden, true);
assert.equal(hex.value, '#12', 'Beralih pilihan tidak boleh diam-diam menghapus edit pengguna.');
initWebTheme({ querySelector: () => null });
console.log('Pilihan palet, bidang custom, dan sinkronisasi warna lulus.');
