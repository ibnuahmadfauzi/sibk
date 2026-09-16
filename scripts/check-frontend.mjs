import { readFile } from 'node:fs/promises';
import process from 'node:process';

const files = {
    login: 'resources/views/pages/login/html.blade.php',
    dashboard: 'resources/views/pages/dashboard/html.blade.php',
    reportIndex: 'resources/views/pages/reports/index.blade.php',
    reportFilters: 'resources/views/pages/reports/_filters.blade.php',
    reportDesktop: 'resources/views/pages/reports/_desktop-table.blade.php',
    reportMobile: 'resources/views/pages/reports/_mobile-cards.blade.php',
    operationalReportService: 'app/Services/OperationalReportRecapService.php',
    reportPreview: 'resources/views/pages/reports/preview.blade.php',
    casesIndex: 'resources/views/pages/cases/index.blade.php',
    casesCreate: 'resources/views/pages/cases/create.blade.php',
    casesShow: 'resources/views/pages/cases/show.blade.php',
    followUp: 'resources/views/pages/cases/follow-up.blade.php',
    consultationCreate: 'resources/views/pages/consultations/create.blade.php',
    consultationShow: 'resources/views/pages/consultations/show.blade.php',
    caseResolve: 'resources/views/pages/cases/resolve.blade.php',
    studentsIndex: 'resources/views/pages/students/index.blade.php',
    studentsShow: 'resources/views/pages/students/show.blade.php',
    achievementCreate: 'resources/views/pages/achievements/create.blade.php',
    achievementIndex: 'resources/views/pages/achievements/index.blade.php',
    achievementShow: 'resources/views/pages/achievements/show.blade.php',
    assignmentIndex: 'resources/views/pages/assignments/classes/index.blade.php',
    assignmentManage: 'resources/views/pages/assignments/classes/manage.blade.php',
    caseAssignment: 'resources/views/pages/assignments/cases/index.blade.php',
    dataMaster: 'resources/views/pages/data-master/index.blade.php',
    account: 'resources/views/pages/account/index.blade.php',
    accessDenied: 'resources/views/pages/system/access-denied.blade.php',
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

const inputAttribute = (inputTag, attribute) => inputTag.match(new RegExp(
    `(?:^|\\s)${attribute}\\s*=\\s*"([^"]*)"`,
    'i',
))?.[1] ?? '';

const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const inputReferencesError = (markup, inputId, errorId) => {
    const inputTag = (markup.match(/<input\b(?:"[^"]*"|'[^']*'|[^'">])*>/gi) ?? [])
        .find((tag) => inputAttribute(tag, 'id') === inputId);

    return new RegExp(`(?:^|\\s)${escapeRegExp(errorId)}(?=\\s|$|\\{)`).test(
        inputAttribute(inputTag ?? '', 'aria-describedby'),
    );
};

assert(!inputReferencesError(
    '<input id="identifier"><input aria-describedby="identifierError">',
    'identifier',
    'identifierError',
), 'Pemeriksa atribut input tidak boleh mencocokkan atribut lintas tag.');

const bladeKeys = Object.keys(files).filter((key) => !['routes', 'package', 'operationalReportService'].includes(key));
for (const key of bladeKeys) {
    assert(!/\sstyle\s*=/.test(contents[key]), `${files[key]} masih memakai inline style.`);
    assert(!/\son\w+\s*=/.test(contents[key]), `${files[key]} masih memakai inline event handler.`);
    assert(!/#[0-9a-f]{3,8}\b/i.test(contents[key]), `${files[key]} masih memuat nilai warna langsung.`);
}

assert(contents.login.includes('data-page-id="PG-001"'), 'PG-001 belum dapat ditelusuri dari markup.');
assert(contents.login.includes('name="email"'), 'Field email PG-001 belum mengikuti kontrak autentikasi.');
assert(contents.login.includes('autocomplete="username"'), 'PG-001 belum menetapkan autocomplete username.');
assert(contents.login.includes('autocomplete="current-password"'), 'PG-001 belum menetapkan autocomplete kata sandi.');
assert(inputReferencesError(contents.login, 'identifier', 'identifierError'), 'Error email PG-001 belum terhubung.');
assert(inputReferencesError(contents.login, 'password', 'passwordError'), 'Error kata sandi PG-001 belum terhubung.');
assert(contents.login.includes("route('login.store')"), 'Form PG-001 belum terhubung ke endpoint login.');
assert(contents.login.includes('@csrf'), 'Form PG-001 belum memiliki perlindungan CSRF.');
assert(contents.login.includes('id="loginSpinner"'), 'State loading PG-001 belum tersedia.');

assert(contents.dashboard.includes('data-page-id="PG-002"'), 'PG-002 belum dapat ditelusuri dari markup.');
assert(contents.dashboard.includes("$dashboard['read_only']"), 'Mode hanya-baca PG-002 belum diterapkan.');
assert(contents.dashboard.includes("$dashboard['stats']"), 'Statistik database PG-002 belum diterapkan.');
assert(contents.dashboard.includes("$dashboard['tindak_lanjut']"), 'Jadwal database PG-002 belum diterapkan.');
assert(contents.dashboard.includes("route('dashboard.preview')"), 'Filter tahun ajaran PG-002 belum terhubung.');
assert(contents.routes.includes('DashboardController::class'), 'Dashboard belum memakai controller database.');
assert(contents.reportIndex.includes('data-page-id="PG-301"'), 'PG-301 belum dapat ditelusuri dari markup.');
const operationalReport = contents.reportIndex + contents.reportFilters + contents.reportDesktop + contents.reportMobile;
for (const label of ['Pelanggaran & Poin', 'Layanan BK', 'Prestasi']) {
    assert(contents.operationalReportService.includes(label), `Tab ${label} PG-301 belum tersedia.`);
}
assert(contents.reportIndex.includes('aria-current="page"'), 'Tab aktif PG-301 belum dapat dikenali.');
assert(contents.reportFilters.includes('name="tab"'), 'Filter PG-301 belum mempertahankan tab aktif.');
assert(contents.reportFilters.includes('name="q"'), 'Pencarian PG-301 belum tersedia.');
assert(contents.reportFilters.includes('name="classroom_id"'), 'Filter kelas PG-301 belum tersedia.');
assert(contents.reportFilters.includes("route('reports.index', ['tab' => $report['tab']])"), 'Reset filter PG-301 belum kembali ke tab aktif.');
assert(contents.reportDesktop.includes('sibk-operational-report-table'), 'Tabel desktop PG-301 belum tersedia.');
assert(contents.reportMobile.includes('sibk-operational-report-cards'), 'Kartu mobile PG-301 belum tersedia.');
assert(contents.reportIndex.includes("route('reports.export', $exportFilters)"), 'Ekspor PG-301 belum memakai filter tervalidasi.');
assert(contents.reportIndex.includes('data-print-report') && contents.reportIndex.includes('data-print-area'), 'Trigger atau area cetak PG-301 belum tersedia.');
assert(!operationalReport.includes('$reports as $report'), 'PG-301 masih melakukan loop katalog laporan lama.');
assert(contents.reportPreview.includes('data-page-id="PG-302"'), 'PG-302 belum dapat ditelusuri dari markup.');
assert(contents.reportPreview.includes("route('reports.export'"), 'Ekspor CSV PG-302 belum terhubung.');
assert(contents.reportPreview.includes('data-print-report'), 'Aksi cetak PG-302 belum tersedia.');
assert(!contents.reportPreview.includes("alert('"), 'PG-302 masih memakai simulasi ekspor.');
assert(contents.routes.includes('ReportController::class'), 'Laporan belum memakai controller database.');
const pageIds = {
    casesIndex: 'PG-101', casesCreate: 'PG-102', casesShow: 'PG-103', followUp: 'PG-104',
    consultationCreate: 'PG-105', caseResolve: 'PG-106', studentsIndex: 'PG-201', studentsShow: 'PG-202',
    achievementCreate: 'PG-203', assignmentIndex: 'PG-401', assignmentManage: 'PG-402',
    caseAssignment: 'PG-403',
    dataMaster: 'PG-501', accessDenied: 'PG-901',
};
for (const [key, pageId] of Object.entries(pageIds)) {
    assert(contents[key].includes(`data-page-id="${pageId}"`), `${pageId} belum dapat ditelusuri dari markup.`);
}
for (const key of ['casesCreate', 'followUp', 'consultationCreate', 'caseResolve', 'achievementCreate', 'assignmentManage', 'caseAssignment', 'dataMaster', 'account', 'topbar']) {
    if (contents[key].includes('method="POST"')) assert(contents[key].includes('@csrf'), `${files[key]} memiliki form POST tanpa @csrf.`);
}
assert(contents.routes.includes('AccountController::class'), 'Halaman akun masih berupa fixture route.');
assert(!contents.routes.includes("Route::redirect('/_preview"), 'Route preview masih menerima metode selain GET/HEAD.');
assert(!contents.routes.includes('Data dummy'), 'Route produksi masih memuat data dummy.');
assert(!contents.routes.includes("return view('pages."), 'Route produksi masih merender Blade melalui closure.');
const runtimeSurface = Object.values(contents).join('\n');
for (const retired of ['CorrectionController', 'NotificationController', "route('corrections.", "route('notifications.", "route('history.", 'Ajukan Koreksi', 'Aktivitas terbaru']) {
    assert(!runtimeSurface.includes(retired), `Consumer retired masih ditemukan: ${retired}`);
}
assert(!contents.package.includes('"jquery"'), 'Dependency jQuery yang tidak terpakai masih ada.');
assert(!contents.package.includes('"sweetalert2"'), 'Dependency SweetAlert2 yang tidak terpakai masih ada.');

if (failures.length > 0) {
    for (const failure of failures) console.error(`- ${failure}`);
    process.exit(1);
}

console.log('Frontend checks passed for all implemented PG pages and shared navigation components.');
