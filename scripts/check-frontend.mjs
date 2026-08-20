import { readFile } from 'node:fs/promises';
import process from 'node:process';

const files = {
    login: 'resources/views/pages/login/html.blade.php',
    dashboard: 'resources/views/pages/dashboard/html.blade.php',
    notifications: 'resources/views/pages/notifications/index.blade.php',
    sidebar: 'resources/views/components/sidebar.blade.php',
    topbar: 'resources/views/components/topbar.blade.php',
    routes: 'routes/web.php',
    package: 'package.json',
};

const contents = Object.fromEntries(
    await Promise.all(Object.entries(files).map(async ([key, file]) => [key, await readFile(file, 'utf8')])),
);

const failures = [];
const assert = (condition, message) => {
    if (!condition) failures.push(message);
};

for (const key of ['login', 'dashboard', 'notifications', 'sidebar', 'topbar']) {
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
assert(contents.dashboard.includes("$dashboard['stats']"), 'Statistik database PG-002 belum diterapkan.');
assert(contents.dashboard.includes("$dashboard['tindak_lanjut']"), 'Jadwal database PG-002 belum diterapkan.');
assert(contents.dashboard.includes("route('dashboard.preview')"), 'Filter tahun ajaran PG-002 belum terhubung.');
assert(contents.notifications.includes('data-page-id="PG-003"'), 'PG-003 belum dapat ditelusuri dari markup.');
assert(contents.notifications.includes("route('notifications.read-all')"), 'Aksi tandai dibaca PG-003 belum terhubung.');
assert(contents.notifications.includes('@csrf'), 'Aksi PG-003 belum memiliki perlindungan CSRF.');
assert(contents.routes.includes('DashboardController::class'), 'Dashboard belum memakai controller database.');
assert(contents.routes.includes('NotificationController::class'), 'Notifikasi belum memakai controller database.');
assert(!contents.package.includes('"jquery"'), 'Dependency jQuery yang tidak terpakai masih ada.');
assert(!contents.package.includes('"sweetalert2"'), 'Dependency SweetAlert2 yang tidak terpakai masih ada.');

if (failures.length > 0) {
    for (const failure of failures) console.error(`- ${failure}`);
    process.exit(1);
}

console.log('Frontend checks passed for PG-001, PG-002, and PG-003.');
