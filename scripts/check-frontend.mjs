import { readFile } from 'node:fs/promises';
import process from 'node:process';

const files = {
    login: 'resources/views/pages/login/html.blade.php',
    dashboard: 'resources/views/pages/dashboard/html.blade.php',
    sidebar: 'resources/views/components/sidebar.blade.php',
    topbar: 'resources/views/components/topbar.blade.php',
    routes: 'routes/web.php',
    fixtures: 'config/sibk-preview.php',
    package: 'package.json',
};

const contents = Object.fromEntries(
    await Promise.all(Object.entries(files).map(async ([key, file]) => [key, await readFile(file, 'utf8')])),
);

const failures = [];
const assert = (condition, message) => {
    if (!condition) failures.push(message);
};

for (const key of ['login', 'dashboard', 'sidebar', 'topbar']) {
    assert(!/\sstyle\s*=/.test(contents[key]), `${files[key]} masih memakai inline style.`);
    assert(!/\son\w+\s*=/.test(contents[key]), `${files[key]} masih memakai inline event handler.`);
    assert(!/#[0-9a-f]{3,8}\b/i.test(contents[key]), `${files[key]} masih memuat nilai warna langsung.`);
}

assert(contents.login.includes('data-page-id="PG-001"'), 'PG-001 belum dapat ditelusuri dari markup.');
assert(contents.login.includes('name="email"'), 'Field email PG-001 belum mengikuti kontrak autentikasi.');
assert(contents.login.includes('autocomplete="username"'), 'PG-001 belum menetapkan autocomplete username.');
assert(contents.login.includes('autocomplete="current-password"'), 'PG-001 belum menetapkan autocomplete kata sandi.');
assert(contents.login.includes('aria-describedby="identifierError"'), 'Error email PG-001 belum terhubung.');
assert(contents.login.includes('aria-describedby="passwordError"'), 'Error kata sandi PG-001 belum terhubung.');
assert(contents.login.includes("route('login.store')"), 'Form PG-001 belum terhubung ke endpoint login.');
assert(contents.login.includes('@csrf'), 'Form PG-001 belum memiliki perlindungan CSRF.');
assert(contents.login.includes('id="loginSpinner"'), 'State loading PG-001 belum tersedia.');

assert(contents.dashboard.includes('data-page-id="PG-002"'), 'PG-002 belum dapat ditelusuri dari markup.');
assert(contents.dashboard.includes("$dashboard['read_only']"), 'Mode hanya-baca PG-002 belum diterapkan.');
assert(contents.dashboard.includes('@unless ($dashboard[\'read_only\'])'), 'Aksi terlarang Waka PG-002 belum disembunyikan.');
assert(contents.dashboard.includes("$previewState === 'loading'"), 'State loading PG-002 belum tersedia.');
assert(contents.dashboard.includes("$previewState === 'empty'"), 'State kosong PG-002 belum tersedia.');
assert(contents.dashboard.includes("$previewState === 'error'"), 'State gagal PG-002 belum tersedia.');

for (const role of ['guru', 'koordinator', 'waka']) {
    assert(contents.fixtures.includes(`'${role}' => [`), `Fixture peran ${role} belum tersedia.`);
}

assert(contents.fixtures.includes('Kasus terkoordinasi'), 'Fixture Waka PG-002 belum tersedia.');
assert(contents.routes.includes("hasRole('guru_bk')"), 'Dashboard belum menentukan scope Guru BK dari akun.');
assert(contents.routes.includes("hasRole('koordinator_bk')"), 'Dashboard belum menentukan scope Koordinator dari akun.');
assert(contents.routes.includes("hasRole('waka_kesiswaan')"), 'Dashboard belum menentukan scope Waka dari akun.');
assert(contents.routes.includes("['default', 'loading', 'empty', 'error']"), 'Allowlist state dashboard belum tersedia.');
assert(!contents.package.includes('"jquery"'), 'Dependency jQuery yang tidak terpakai masih ada.');
assert(!contents.package.includes('"sweetalert2"'), 'Dependency SweetAlert2 yang tidak terpakai masih ada.');

if (failures.length > 0) {
    for (const failure of failures) console.error(`- ${failure}`);
    process.exit(1);
}

console.log('Frontend checks passed for PG-001 and PG-002.');
