import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../resources/js/app-dashboard.js', import.meta.url), 'utf8');
const historyCode = source.slice(source.indexOf('const loadSyncHistory ='), source.indexOf("document.addEventListener('submit', (event) => {"));
assert(historyCode.includes('loadSyncHistory'));

const listeners = {};
const panels = new Map();
const buttons = [];
const pending = [];
const classes = (initial = []) => {
    const values = new Set(initial);
    return {
        contains: (name) => values.has(name),
        add: (name) => values.add(name),
        remove: (name) => values.delete(name),
        toggle: (name, force) => {
            if (force ?? !values.has(name)) values.add(name);
            else values.delete(name);
        },
    };
};

const makePair = (id, param) => {
    const panel = {
        id,
        dataset: {},
        classList: classes(['collapse']),
        innerHTML: '',
        querySelector: () => ({ innerHTML: '' }),
    };
    const attributes = { 'aria-controls': id, 'aria-expanded': 'false' };
    const path = { setAttribute: (_, value) => { button.path = value; } };
    const button = {
        dataset: { historyParam: param },
        getAttribute: (name) => attributes[name],
        setAttribute: (name, value) => { attributes[name] = value; },
        querySelector: () => path,
        addEventListener: (_, callback) => { button.click = callback; },
    };
    panels.set(id, panel);
    buttons.push(button);
    return { panel, button };
};

const decisions = makePair('sync-decisions-content', 'history_decisions');
const runs = makePair('sync-runs-content', 'history_runs');
const location = { href: 'https://example.test/data-master?tab=sinkronisasi&decision_page=2', origin: 'https://example.test', pathname: '/data-master' };
const document = {
    getElementById: (id) => panels.get(id),
    querySelectorAll: (selector) => selector === '[data-sync-history-toggle]' ? buttons : [],
    querySelector: (selector) => buttons.find((button) => selector.includes(button.getAttribute('aria-controls'))),
    addEventListener: (event, callback) => { (listeners[event] ??= []).push(callback); },
};
const window = {
    location,
    history: { state: null, replaceState: (_, __, value) => { location.href = String(value); } },
};
const Collapse = {
    getOrCreateInstance: (panel) => ({
        show: () => panel.classList.add('show'),
        hide: () => panel.classList.remove('show'),
    }),
};
const DOMParser = class {
    parseFromString() {
        return { getElementById: (id) => ({
            innerHTML: `<table data-panel="${id}"></table>`,
            querySelector: () => ({}),
        }) };
    }
};
const fetch = (url) => new Promise((resolve, reject) => pending.push({ url: String(url), resolve, reject }));
runInNewContext(historyCode, { document, window, Collapse, DOMParser, fetch, URL });
const settle = async () => { for (let i = 0; i < 5; i += 1) await Promise.resolve(); };

assert.equal(pending.length, 0, 'Riwayat tidak dimuat saat halaman dibuka');
decisions.button.click();
assert.equal(pending.length, 1);
assert.equal(new URL(pending[0].url).searchParams.get('decision_page'), '2');
assert.equal(decisions.button.getAttribute('aria-expanded'), 'true');
decisions.button.click();
assert.equal(decisions.button.getAttribute('aria-expanded'), 'false');
assert.equal(decisions.panel.classList.contains('show'), false);
pending.shift().resolve({ ok: true, text: async () => '<html></html>' });
await settle();
assert.equal(decisions.panel.dataset.loaded, 'true');
assert.equal(decisions.panel.classList.contains('show'), false, 'Jawaban yang terlambat tidak membuka panel');
decisions.button.click();
assert.equal(pending.length, 0, 'Panel yang pernah dimuat memakai cache');
assert.equal(new URL(location.href).searchParams.get('decision_page'), '2');

decisions.panel.classList.add('collapsing');
decisions.button.click();
assert.equal(decisions.button.getAttribute('aria-expanded'), 'true', 'Klik selama animasi diabaikan');
decisions.panel.classList.remove('collapsing');

runs.button.click();
assert.equal(pending.length, 1);
assert.equal(new URL(pending[0].url).searchParams.has('history_decisions'), false, 'Membuka riwayat lain tidak memuat ulang keputusan');
pending.shift().reject(new Error('jaringan'));
await settle();
assert.match(runs.panel.innerHTML, /Coba lagi/);
const retry = { closest: (selector) => selector === '[data-sync-history-retry]' ? retry : (selector === '[data-sync-history-content]' ? runs.panel : null) };
listeners.click[0]({ target: retry });
assert.equal(pending.length, 1, 'Tombol retry memuat ulang');
pending.shift().resolve({ ok: true, text: async () => '<html></html>' });
await settle();
assert.equal(runs.panel.dataset.loaded, 'true');

const link = { href: 'https://example.test/data-master?tab=sinkronisasi&issue_page=3', closest: () => ({}) };
listeners.click.at(-1)({ target: { closest: () => link } });
const nextUrl = new URL(link.href);
assert.equal(nextUrl.searchParams.get('history_decisions'), '1');
assert.equal(nextUrl.searchParams.get('history_runs'), '1');
assert.equal(nextUrl.searchParams.get('issue_page'), '3');
console.log('Perilaku riwayat sinkronisasi sesuai.');
