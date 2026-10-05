import assert from 'node:assert/strict';
import { renderEtatibPreview, updateEtatibReadiness, writeEtatibDecisions } from '../resources/js/etatib-api-preview.js';

class Node {
    constructor(tag) {
        this.tag = tag;
        this.dataset = {};
        this.children = [];
        this.checked = false;
    }
    append(...children) {
        children.forEach((child) => { child.parent = this; });
        this.children.push(...children);
    }
    remove() { this.parent.children = this.parent.children.filter((child) => child !== this); }
    replaceChildren(...children) { this.children = children; }
    querySelectorAll(selector) {
        const key = selector.split(']')[0].slice(6).replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
        return this.children.flatMap((child) => [
            ...(Object.hasOwn(child.dataset, key) && (!selector.endsWith(':checked') || child.checked) ? [child] : []),
            ...child.querySelectorAll(selector),
        ]);
    }
    querySelector(selector) { return this.querySelectorAll(selector)[0]; }
}

globalThis.document = {
    createElement: (tag) => new Node(tag),
    querySelector: () => ({ content: 'test-csrf' }),
};
const body = new Node('div');
const confirm = {};
const automatic = {};
const group = {
    key: 'a'.repeat(64), rows: [849, 850], count: 2,
    nisn: '0012345678', name: '<img src=x onerror=alert(1)>', classroom: 'X RPL', approved: false,
};
const preview = {
    received: 856, rows: 855, missing: 0, undated: 0,
    conflicts: 0, missing_students: 0, identity_conflicts: [], duplicate_groups: [group],
};
const render = (data) => renderEtatibPreview(body, data, '/data-master', '/duplicates/__DECISION__');
const refresh = (missing = 0) => updateEtatibReadiness(body, missing, confirm, automatic);

render(preview);
refresh();
assert.equal(confirm.disabled, true);
assert.equal(automatic.disabled, true);
assert.equal(body.querySelector('[data-etatib-identities]').hidden, true);
const choice = body.querySelector('[data-duplicate-choice]');
assert.equal(choice.checked, false);
assert.equal(choice.value, group.key);
choice.checked = true;
refresh();
assert.equal(confirm.disabled, false);
assert.equal(automatic.disabled, false);
assert.equal(body.querySelector('[data-etatib-identities]').hidden, false);
refresh(1);
assert.equal(confirm.disabled, true);
assert.equal(automatic.disabled, true);
choice.checked = false;
refresh();
assert.equal(confirm.disabled, true);

render({ ...preview, duplicate_groups: [group, { ...group, key: 'b'.repeat(64), rows: [851, 852] }] });
const choices = body.querySelectorAll('[data-duplicate-choice]');
choices[0].checked = true;
refresh();
assert.equal(confirm.disabled, true);
choices[1].checked = true;
refresh();
assert.equal(confirm.disabled, false);
const form = new Node('form');
writeEtatibDecisions(form, body);
assert.deepEqual(form.children.map((input) => [input.name, input.value]), [
    ['duplicate_decisions[]', group.key], ['duplicate_decisions[]', 'b'.repeat(64)],
]);
choices[1].checked = false;
writeEtatibDecisions(form, body);
assert.equal(form.children.length, 1);
assert.equal(form.children[0].value, group.key);

render({ ...preview, duplicate_groups: [{ ...group, approved: true, decision_id: 7 }] });
refresh();
assert.equal(confirm.disabled, false);
assert.equal(body.querySelectorAll('[data-duplicate-choice]').length, 0);
const descendants = (node) => node.children.flatMap((child) => [child, ...descendants(child)]);
const nodes = descendants(body);
assert(nodes.some((node) => node.tag === 'strong' && node.textContent === group.name));
assert(!nodes.some((node) => node.tag === 'img'));
const revoke = nodes.find((node) => node.tag === 'form');
assert.equal(revoke.action, '/duplicates/7');
assert.equal(revoke.method, 'POST');
assert(revoke.children.some((node) => node.name === '_token' && node.value === 'test-csrf'));
assert(descendants(revoke).some((node) => node.type === 'checkbox' && node.required && !node.checked));

render({ ...preview, duplicate_groups: [] });
refresh();
assert.equal(confirm.disabled, false);
assert.equal(body.querySelectorAll('[data-duplicate-choice]').length, 0);
console.log('Pratinjau e-Tatib: keputusan, pembatalan, dan kesiapan sinkronisasi lulus.');
