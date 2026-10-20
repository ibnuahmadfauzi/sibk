import assert from 'node:assert/strict';
import { readFile, readdir } from 'node:fs/promises';
import { join } from 'node:path';
import * as sass from 'sass';

async function readSources(directory) {
    const entries = await readdir(directory, { withFileTypes: true });
    const sources = await Promise.all(entries.map((entry) => {
        const path = join(directory, entry.name);
        if (entry.isDirectory()) return readSources(path);
        return /\.(scss|js|blade\.php)$/.test(entry.name) ? readFile(path, 'utf8') : '';
    }));
    return sources.join('\n');
}

const source = await readSources('resources');
const references = new Set([...source.matchAll(/var\(\s*(--sibk-(?:color|shadow)-[\w-]+)/g)].map((match) => match[1]));
const defaults = [];

for (const name of ['app-auth', 'app-dashboard']) {
    const { css } = sass.compile(`resources/scss/${name}.scss`, {
        loadPaths: ['node_modules'],
        logger: sass.Logger.silent,
    });
    const root = [...css.matchAll(/:root\s*\{([^}]+)\}/g)].map((match) => match[1]).join('\n');
    assert.ok(root, `${name}: tema bawaan harus tersedia pada :root.`);
    const values = Object.fromEntries([...root.matchAll(/(--sibk-(?:color|shadow)-[\w-]+)\s*:\s*([^;]+);/g)]
        .map((match) => [match[1], match[2].trim()]));

    for (const reference of references) {
        assert.ok(reference in values, `${name}: ${reference} dipakai tetapi tidak tersedia pada tema bawaan.`);
    }
    for (const token of Object.keys(values)) {
        assert.ok(references.has(token), `${name}: ${token} tidak dipakai dan tidak perlu diekspor sebagai pengaturan warna.`);
    }
    for (const [token, original] of Object.entries({
        '--sibk-color-primary': '#2f6fc6',
        '--sibk-color-primary-hover': '#2659a0',
        '--sibk-color-primary-gradient-start': '#357ad6',
        '--sibk-color-primary-gradient-end': '#2761ae',
        '--sibk-color-primary-pressed-hover': '#25589e',
        '--sibk-color-primary-pressed': '#204b86',
        '--sibk-color-sidebar-gradient-start': '#4880c8',
        '--sibk-color-sidebar-gradient-mid': '#2f67aa',
        '--sibk-color-sidebar-gradient-end': '#234f88',
        '--sibk-color-sidebar-card-start': '#3f75bd',
        '--sibk-color-page': '#f8f0ea',
        '--sibk-color-surface-raised': '#fdfbf9',
        '--sibk-color-text': '#0d2447',
    })) {
        assert.equal(values[token]?.toLowerCase(), original, `${name}: identitas warna bawaan ${token} bergeser.`);
    }
    assert.doesNotMatch(values['--sibk-color-info'], /var\(--sibk-color-primary/, 'Warna informasi harus terpisah dari perubahan warna utama.');
    assert.doesNotMatch(values['--sibk-color-warning'], /var\(--sibk-color-accent/, 'Warna peringatan harus terpisah dari perubahan aksen dekoratif.');
    defaults.push(values);
}

assert.deepEqual(defaults[0], defaults[1], 'Halaman masuk dan dashboard harus memakai sumber tema bawaan yang sama.');
console.log(`Tema bawaan valid: ${references.size} referensi warna/bayangan tersedia dan kedua stylesheet konsisten.`);
