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

function contrast(foreground, background) {
    const luminance = (hex) => {
        const color = hex.length === 4 ? `#${[...hex.slice(1)].map((channel) => channel.repeat(2)).join('')}` : hex;
        const channels = color.match(/[\da-f]{2}/gi).map((channel) => {
            const value = parseInt(channel, 16) / 255;
            return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
        });
        return channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722;
    };
    const [light, dark] = [luminance(foreground), luminance(background)].sort((a, b) => b - a);
    return (light + 0.05) / (dark + 0.05);
}

function rule(css, selector) {
    const escaped = selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return [...css.matchAll(new RegExp(`${escaped}\\s*\\{([^}]+)\\}`, 'g'))].at(-1)?.[1] ?? '';
}

function property(declarations, name) {
    return declarations.match(new RegExp(`(?:^|;)\\s*${name}:\\s*([^;!]+)`))?.[1].trim();
}

function overlay(background, rgba) {
    const [red, green, blue, alpha] = rgba.match(/[\d.]+/g).map(Number);
    return `#${background.slice(1).match(/../g).map((channel, index) =>
        Math.round([red, green, blue][index] * alpha + parseInt(channel, 16) * (1 - alpha))
            .toString(16).padStart(2, '0')).join('')}`;
}

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
        '--sibk-color-primary-gradient-start': '#3475cc',
        '--sibk-color-primary-gradient-end': '#2761ae',
        '--sibk-color-primary-pressed-hover': '#25589e',
        '--sibk-color-primary-pressed': '#204b86',
        '--sibk-color-sidebar-gradient-start': '#3f75bd',
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
    assert.ok(contrast('#ffffff', values['--sibk-color-primary-gradient-start']) >= 4.5, `${name}: teks tombol utama harus terbaca pada ujung terang gradasi.`);
    assert.ok(contrast('#ffffff', values['--sibk-color-sidebar-gradient-start']) >= 4.5, `${name}: teks sidebar harus terbaca pada ujung terang gradasi.`);
    if (name === 'app-dashboard') {
        assert.ok(/\$sibk-tone-primary-text:\s*#[\da-f]+;/i.test(source), 'Teks status utama harus memiliki nilai semantik sendiri, terpisah dari warna merek.');
        for (const selector of ['.sibk-nav-link', '.sibk-sidebar__section', '.sibk-sidebar__tagline']) {
            assert.ok(contrast(property(rule(css, selector), 'color'), values['--sibk-color-sidebar-gradient-start']) >= 4.5, `${selector}: teks harus terbaca pada ujung terang sidebar.`);
        }
        const brand = property(rule(css, '.sibk-sidebar__brand'), 'color');
        assert.ok(contrast(brand, values['--sibk-color-sidebar-gradient-start']) >= 4.5, 'Teks logo sidebar harus terbaca.');
        assert.doesNotMatch(css, /\.sibk-sidebar__brand small[^{}]*\{[^}]*opacity:/, 'Teks kecil logo tidak boleh pudar.');
        const hover = rule(css, '.sibk-nav-link:hover:not(.is-planned):not(.is-active)');
        assert.ok(contrast(property(hover, 'color'), overlay(values['--sibk-color-sidebar-gradient-start'], property(hover, 'background'))) >= 4.5, 'Teks menu sidebar harus terbaca saat hover.');
        for (const tone of ['primary', 'warning', 'success', 'info', 'danger']) {
            const base = rule(css, `.sibk-icon-tone--${tone}`);
            const badge = rule(css, `.sibk-icon-tone--${tone}.badge`);
            const background = property(base, 'background');
            const foreground = property(badge || base, 'color');
            assert.ok(contrast(foreground, background) >= 4.5, `Label status ${tone} harus memiliki kontras minimal 4,5:1.`);
        }
        const placeholder = rule(css, '.form-control::placeholder');
        assert.equal(property(placeholder, 'opacity'), '1', 'Placeholder formulir tidak boleh memudarkan warna teks yang sudah memenuhi kontras.');
        assert.ok(contrast(values['--sibk-color-text-muted'], values['--sibk-color-field']) >= 4.5, 'Warna placeholder dan bidang formulir harus memiliki kontras minimal 4,5:1.');
    }
    defaults.push(values);
}

assert.deepEqual(defaults[0], defaults[1], 'Halaman masuk dan dashboard harus memakai sumber tema bawaan yang sama.');
console.log(`Tema bawaan valid: ${references.size} referensi warna/bayangan tersedia dan kedua stylesheet konsisten.`);
