# Portal Waka Berbasis Tujuan Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyediakan portal Waka Kesiswaan yang terpisah dari workspace Guru BK, berisi Dashboard, Murid dengan Kasus, serta satu halaman Laporan berbasis tujuan tanpa membuka data konseling sensitif.

**Architecture:** Portal memakai query/read-model khusus Waka yang tidak menggunakan scope profesional Guru BK. Model kasus hanya dipakai di dalam service; controller dan Blade menerima array proyeksi aman. Dashboard, daftar murid, monitoring penanganan, dan rekap periode berbagi fondasi query aman, sedangkan tab Laporan Akhir hanya menampilkan empty state `Dalam pengembangan` sampai format resmi sekolah tersedia.

**Tech Stack:** PHP 8.3, Laravel 13, Eloquent, Blade, Bootstrap 5.3, SCSS existing Ruang BK, PHPUnit 12.

**Spec:** `docs/superpowers/specs/2026-09-13-portal-waka-wireframe-design.md`

## Global Constraints

- Gunakan Bahasa Indonesia pada seluruh teks pengguna dan istilah `murid`, bukan `siswa`.
- Pertahankan design system Penpot `22.5 - Style Guide` dan komponen existing Ruang BK.
- Jangan menambah warna, font, radius, shadow, atau komponen visual dasar baru.
- Akun Waka murni tidak melihat menu kerja Guru BK atau Koordinator.
- Portal Waka tidak menggunakan `BkCase::accessibleTo()` atau `Student::accessibleTo()` untuk proyeksi sekolah.
- View tidak menerima model `BkCase`, `Student`, `FollowUp`, atau `CaseAssignment` mentah.
- Field aman hanya nama murid, kelas historis, bidang, status, owner Guru BK, tanggal layanan, `waka_summary`, serta jenis/tanggal tindak lanjut berikutnya.
- NISN, `registration_number`, `initial_info`, `internal_note`, isi konsultasi, dokumen, `final_result`, hasil tindak lanjut mentah, dan `next_plan` dilarang pada HTML, CSV, audit, dan data view.
- Detail kasus hanya memiliki tautan jika actor Waka mempunyai koordinasi tercatat pada kasus tersebut.
- Seluruh halaman Waka hanya-baca; jangan menambahkan form mutasi atau tombol yang berakhir `403`.
- Tab Laporan Akhir hanya menampilkan `Dalam pengembangan`; jangan membuat migration, form, lifecycle penerbitan, cetak, PDF, atau ekspor.
- Koordinator BK aktif boleh membuka tab Laporan Akhir untuk melihat status placeholder yang sama, tetapi tidak memperoleh daftar pemantauan, Rekap Periode Waka, atau ekspor Waka kecuali akunnya juga memiliki role Waka.
- Setiap response sukses Dashboard Waka, Murid dengan Kasus, dan tab Laporan mencatat `waka.monitoring.viewed` dengan mode halaman serta hitungan aman; CSV mencatat `waka.monitoring.exported`.
- Nilai CSV yang diawali `=`, `+`, `-`, `@`, tab, atau carriage return wajib diprefiks apostrof untuk mencegah formula injection.
- Tidak ada adapter production Dapodik/e-Tatib baru.
- Semua PHP baru memakai `declare(strict_types=1);`.
- Implementasi dilakukan dengan TDD dan satu verification gate pada setiap task.

---

## Struktur File yang Dikunci

### Fondasi proyeksi aman

- Create: `app/Services/WakaCaseProjectionQuery.php` - query kasus sekolah dengan select dan eager-load aman.
- Modify: `app/Services/WakaMonitoringService.php` - pagination, transformasi row aman, CSV, dan audit.
- Create: `app/Services/WakaStudentCaseService.php` - agregasi satu row per identitas murid.
- Create: `app/Services/WakaDashboardService.php` - angka utama, perhatian, komposisi status, dan penanganan terbaru.
- Create: `app/Services/WakaPeriodReportService.php` - agregat kasus, e-Tatib, prestasi, dan rekap kelas.

### HTTP dan presentasi

- Modify: `app/Http/Requests/WakaMonitoringRequest.php`.
- Create: `app/Http/Requests/WakaReportRequest.php`.
- Modify: `app/Http/Controllers/WakaMonitoringController.php`.
- Modify: `app/Policies/WakaMonitoringPolicy.php`.
- Modify: `routes/waka.php`.
- Create: `resources/views/pages/waka/students.blade.php`.
- Create: `resources/views/pages/waka/reports.blade.php`.
- Create: `resources/views/pages/waka/_handling-report.blade.php`.
- Create: `resources/views/pages/waka/_period-recap.blade.php`.
- Create: `resources/views/pages/waka/_final-report-development.blade.php`.
- Delete after replacement: `resources/views/pages/waka/monitoring.blade.php`.

### Dashboard dan navigasi

- Modify: `app/Services/DashboardService.php`.
- Modify: `app/Http/Controllers/DashboardController.php`.
- Create: `resources/views/pages/waka/dashboard.blade.php`.
- Modify: `resources/views/pages/dashboard/html.blade.php`.
- Modify: `resources/views/components/sidebar.blade.php`.
- Modify: `resources/views/components/empty-state.blade.php`.
- Modify: `resources/scss/app-dashboard.scss`.

### Requirement dan test

- Modify: `docs/requirements/PRD_Aplikasi_BK_v1.1.md`.
- Modify: `docs/requirements/SRS_Aplikasi_BK_v1.1.md`.
- Modify: `docs/requirements-index.md`.
- Modify: `docs/api-contract.md`.
- Modify: `docs/superpowers/plans/2026-09-12-penyempurnaan-alur-operasional-bk.md`.
- Extend: `tests/Feature/WakaMonitoringTest.php`.
- Create: `tests/Feature/WakaDashboardTest.php`.
- Create: `tests/Feature/WakaReportPageTest.php`.
- Extend: `tests/Feature/DashboardNotificationTest.php`.
- Extend: `tests/Feature/AuthorizationMatrixTest.php`.
- Extend: `tests/Feature/FrontendPreviewTest.php`.
- Extend: `tests/Feature/ReportManagementTest.php`.

---

### Task 1: Selaraskan Requirement dan Kontrak Portal Waka

**Files:**

- Modify: `docs/requirements/PRD_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements/SRS_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements-index.md`
- Modify: `docs/api-contract.md`
- Modify: `docs/superpowers/plans/2026-09-12-penyempurnaan-alur-operasional-bk.md`

**Interfaces:**

- Consumes: keputusan wireframe pada spec.
- Produces: kontrak navigasi, route, tab, metric, privasi, dan status Laporan Akhir.

- [ ] **Step 1: Perbarui arsitektur informasi PRD**

Ganti daftar portal Waka lama menjadi:

```text
Dashboard, Murid dengan Kasus, dan Laporan. Halaman Laporan mempunyai tab
Monitoring Penanganan, Rekap Periode, dan Laporan Akhir. Laporan Akhir hanya
menampilkan status Dalam pengembangan sampai format resmi sekolah disepakati.
```

Tegaskan bahwa laporan pelanggaran per murid/per kelas, poin, tindak lanjut, dan
prestasi bukan menu terpisah bagi Waka. Data tersebut hanya boleh menjadi
konteks agregat dalam Rekap Periode.

- [ ] **Step 2: Amendemen DASH-03 dan REP-05**

Gunakan acceptance criteria berikut:

```text
DASH-03: Dashboard Waka menampilkan empat metric kasus, daftar perhatian,
komposisi status, dan penanganan terbaru dari proyeksi aman seluruh sekolah.

REP-05: Portal Waka mempunyai daftar Murid dengan Kasus dan satu halaman
Laporan bertab. Monitoring Penanganan memakai filter bulan/status. Rekap
Periode hanya menampilkan agregat kasus, konteks e-Tatib, prestasi
terverifikasi, dan ringkasan kelas. Laporan Akhir menampilkan status Dalam
pengembangan tanpa tindakan penerbitan atau ekspor.
```

- [ ] **Step 3: Bekukan metric Rekap Periode**

Metric utama:

```text
Murid ditangani
Kasus tercatat
Membutuhkan tindak lanjut
Kasus selesai
```

Konteks kesiswaan:

```text
Pelanggaran tercatat
Murid terkait pelanggaran
Prestasi terverifikasi
```

- [ ] **Step 4: Perbarui kontrak route dan filter**

```text
GET /waka/students-with-cases
GET /waka/reports?tab=penanganan
GET /waka/reports?tab=rekap
GET /waka/reports?tab=laporan-akhir
GET /waka/handling-reports              redirect kompatibilitas
GET /waka/handling-reports/export       CSV monitoring penanganan
```

Filter Monitoring Penanganan dan daftar murid memakai `period`, `status`,
`sort`, `direction`, dan `page`. Daftar murid hanya menerima sort `murid`,
`kelas`, `status`, atau `guru_bk` dengan default `murid`; Monitoring Penanganan
menerima enam sort key dengan default `tanggal`. Filter rekap adalah
`academic_year_id`, `date_start`, dan `date_end`. Rentang tanggal wajib berada
dalam tahun ajaran terpilih.

Enam nilai `sort` publik Monitoring Penanganan dibekukan menjadi `murid`,
`kelas`, `bidang`, `status`, `guru_bk`, atau `tanggal`. Bookmark lama mempertahankan seluruh parameter yang
sudah tervalidasi ketika diarahkan ke `?tab=penanganan`. Audit pembacaan memakai
mode `dashboard`, `students`, `reports.penanganan`, `reports.rekap`, atau
`reports.laporan-akhir`; Koordinator hanya diizinkan pada mode terakhir.

Perbarui `docs/requirements-index.md` agar mencatat amendemen Portal Waka
13 September 2026 serta tetap menunjuk PRD/SRS v1.1 sebagai baseline aktif.

- [ ] **Step 5: Verifikasi konsistensi dokumen**

```powershell
rg -n "Dashboard, Murid dengan Kasus|Monitoring Penanganan|Rekap Periode|Dalam pengembangan|REP-05|DASH-03" docs/requirements docs/requirements-index.md docs/api-contract.md docs/superpowers/plans/2026-09-12-penyempurnaan-alur-operasional-bk.md
git diff --check
```

Expected: istilah baru muncul pada seluruh source of truth dan tidak ada whitespace error.

- [ ] **Step 6: Commit**

```powershell
git add docs/requirements/PRD_Aplikasi_BK_v1.1.md docs/requirements/SRS_Aplikasi_BK_v1.1.md docs/requirements-index.md docs/api-contract.md docs/superpowers/plans/2026-09-12-penyempurnaan-alur-operasional-bk.md
git commit -m "docs: sederhanakan struktur portal Waka"
```

---

### Task 2: Perbaiki Fondasi Proyeksi Aman Kasus Waka

**Files:**

- Create: `app/Services/WakaCaseProjectionQuery.php`
- Modify: `app/Services/WakaMonitoringService.php`
- Modify: `app/Http/Requests/WakaMonitoringRequest.php`
- Modify: `app/Http/Controllers/WakaMonitoringController.php`
- Extend: `tests/Feature/WakaMonitoringTest.php`

**Interfaces:**

- Consumes: `CaseAssignment::teacher()`, `StudentClassMembership::effectiveEnd()`, dan `ServiceRecordStatus`.
- Produces: `WakaCaseProjectionQuery::build(User $waka, array $filters): Builder`, `WakaMonitoringService::paginateSafe(User $waka, array $filters, int $perPage = 20): LengthAwarePaginator`, `WakaMonitoringService::exportCsvRows(User $waka, array $filters): Collection`, dan `WakaMonitoringService::auditViewed(User $actor, string $mode, array $filters, int $resultCount, Request $request): void`.

- [ ] **Step 1: Tulis regression test owner nyata**

```php
public function test_handling_projection_with_real_owner_is_safe(): void
{
    [$waka, $case, $owner] = $this->wakaCaseFixture();

    CaseAssignment::query()->create([
        'case_id' => $case->id,
        'user_id' => $owner->id,
        'assignment_type' => CaseAssignment::TYPE_OWNER,
        'effective_from' => '2026-07-01',
        'reason' => 'Penanggung jawab awal.',
        'assigned_by' => $owner->id,
    ]);

    $paginator = app(WakaMonitoringService::class)->paginateSafe($waka, [
        'period' => '2026-09',
        'sort' => 'tanggal',
        'direction' => 'desc',
        'page' => '1',
    ]);
    $row = $paginator->items()[0];

    $this->assertSame($owner->name, $row['guru_bk']);
    $this->assertArrayNotHasKey('registration_number', $row);
    $this->assertArrayNotHasKey('initial_info', $row);
    $this->assertArrayNotHasKey('internal_note', $row);
    $this->assertStringNotContainsString('SENTINEL-INTERNAL', json_encode($row, JSON_THROW_ON_ERROR));
}
```

- [ ] **Step 2: Tulis test kelas historis dan sorting allowlist**

Buat dua membership pada periode berbeda. Pastikan kelas yang tampil efektif pada
`service_date`. Tambahkan nilai nama/ringkasan yang diawali `=`, `+`, `-`, `@`,
tab, dan carriage return lalu pastikan seluruh cell CSV diprefiks apostrof. Uji
keenam sort key dan forged identifier:

```php
foreach (WakaMonitoringRequest::HANDLING_SORT_ALLOWLIST as $sort) {
    $rows = app(WakaMonitoringService::class)->paginateSafe($waka, [
        'sort' => $sort,
        'direction' => 'asc',
        'page' => '1',
    ]);

    $this->assertGreaterThanOrEqual(1, $rows->total());
}

$this->expectException(InvalidArgumentException::class);
app(WakaMonitoringService::class)->paginateSafe($waka, [
    'sort' => 'cases.registration_number',
]);
```

Service wajib melempar `InvalidArgumentException` untuk sort di luar allowlist;
Form Request rejection diuji setelah route baru tersedia pada Task 3.

- [ ] **Step 3: Jalankan test untuk memastikan gagal**

```powershell
php artisan test tests/Feature/WakaMonitoringTest.php --filter="real_owner|historical|sorting"
```

Expected: FAIL karena relasi `user` salah, sorting relasi belum executable, dan
service belum mengembalikan paginator array aman.

- [ ] **Step 4: Buat query object aman**

```php
final class WakaCaseProjectionQuery
{
    /**
     * @param array{
     *   period?: ?string,
     *   status?: ?string,
     *   sort?: ?string,
     *   direction?: ?string,
     *   date_start?: ?string,
     *   date_end?: ?string
     * } $filters
     */
    public function build(User $waka, array $filters = []): Builder
    {
        $query = BkCase::query()
            ->select([
                'cases.id',
                'cases.service_date',
                'cases.waka_summary',
                'cases.closed_at',
                'cases.student_id',
                'cases.temporary_student_id',
                'cases.service_field_id',
                'cases.status_id',
            ])
            ->with([
                'student:id,name',
                'temporaryStudent:id,input_name',
                'student.classMemberships' => static fn ($memberships) => $memberships
                    ->with(['classroom:id,name', 'academicYear:id,starts_on,ends_on'])
                    ->orderByDesc('effective_from'),
                'serviceField:id,label',
                'status:id,label,code',
                'assignments' => static fn ($assignments) => $assignments
                    ->where('assignment_type', CaseAssignment::TYPE_OWNER)
                    ->with('teacher:id,name')
                    ->latest('effective_from')
                    ->latest('id'),
                'followUps' => static fn ($followUps) => $followUps
                    ->whereHas('status', static fn ($status) => $status->where('code', '!=', 'dibatalkan'))
                    ->whereDate('planned_date', '>=', today())
                    ->with('type:id,label')
                    ->orderBy('planned_date'),
                'coordinations' => static fn ($coordinations) => $coordinations
                    ->where('waka_user_id', $waka->getKey()),
            ]);

        $query = $this->applyDateFilters($query, $filters)
            ->when($filters['status'] ?? null, fn (Builder $cases, string $status): Builder => $cases
                ->whereHas('status', fn (Builder $reference): Builder => $reference->where('code', $status)));

        return $this->applyAllowedSort($query, $filters);
    }
}
```

Ganti nama constant menjadi `HANDLING_SORT_ALLOWLIST`; tambahkan
`STUDENT_SORT_ALLOWLIST = ['murid', 'kelas', 'status', 'guru_bk']`. Test forged
identifier dijalankan pada kedua halaman dan memastikan sort yang hanya sah
untuk penanganan ditolak pada daftar murid.

`applyDateFilters()` menerjemahkan `period` menjadi awal/akhir bulan atau memakai
`date_start/date_end` internal dari Dashboard. `applyAllowedSort()` memakai
`match` terhadap enam key allowlist: nama memakai `COALESCE` student/temporary,
kelas memakai membership efektif pada `cases.service_date`, bidang dan status
memakai subquery reference, Guru BK memakai owner efektif saat ini dengan
fallback owner terakhir, dan tanggal memakai `cases.service_date`. Jangan
memakai nilai request sebagai identifier SQL. Setiap branch diakhiri tie-breaker
`cases.id` agar pagination stabil pada SQLite dan MySQL.

- [ ] **Step 5: Perbaiki transformasi row**

```php
$membership = $case->student?->classMemberships->first(
    fn (StudentClassMembership $item): bool => $item->effective_from?->lte($case->service_date)
        && ($item->effectiveEnd() === null || $item->effectiveEnd()->gte($case->service_date)),
);
$ownerAssignment = $case->assignments->first(
    fn (CaseAssignment $assignment): bool => $assignment->effective_from?->lte(today())
        && ($assignment->effective_until === null || $assignment->effective_until->gte(today())),
) ?? $case->assignments->first();
$owner = $ownerAssignment?->teacher;

return [
    'nama_murid' => $case->identityName(),
    'kelas' => $membership?->classroom?->name ?? '-',
    'bidang' => $case->serviceField?->label ?? '-',
    'status' => $case->status?->label ?? '-',
    'status_code' => $case->status?->code ?? '',
    'guru_bk' => $owner?->name ?? '-',
    'tanggal' => $case->service_date?->locale('id')->translatedFormat('d M Y') ?? '-',
    'waka_summary' => $case->waka_summary,
    'tindak_lanjut' => $this->nextFollowUp($case),
    'coordination_url' => $case->coordinations->isNotEmpty()
        ? route('cases.show', $case->getKey())
        : null,
];
```

`paginateSafe()` mengganti collection paginator dengan hasil `toSafeRow()`
sebelum dikembalikan. `exportCsvRows()` juga mengembalikan array CSV aman, bukan
model. `toCsvRow()` memanggil helper formula-escape untuk setiap cell string
sebelum stream dibuat. Sesuaikan action `index()` dan `export()` existing agar
langsung memakai dua kontrak aman ini; pertahankan route dan view lama sampai
Task 3. Controller tidak boleh menerima `BkCase` mentah.

Ubah `auditViewed()` agar menerima `mode` allowlist dan menyimpan jumlah row
halaman. `auditExported()` tetap menyimpan jumlah seluruh row ekspor. Keduanya
hanya menerima filter yang telah dinormalisasi untuk mode terkait.

- [ ] **Step 6: Jalankan focused verification**

```powershell
php artisan test tests/Feature/WakaMonitoringTest.php
php vendor/bin/pint --test app/Services/WakaCaseProjectionQuery.php app/Services/WakaMonitoringService.php app/Http/Requests/WakaMonitoringRequest.php app/Http/Controllers/WakaMonitoringController.php tests/Feature/WakaMonitoringTest.php
```

- [ ] **Step 7: Commit**

```powershell
git add app/Services/WakaCaseProjectionQuery.php app/Services/WakaMonitoringService.php app/Http/Requests/WakaMonitoringRequest.php app/Http/Controllers/WakaMonitoringController.php tests/Feature/WakaMonitoringTest.php
git commit -m "fix: bangun proyeksi aman kasus Waka"
```

---

### Task 3: Pisahkan Murid dengan Kasus dan Monitoring Penanganan

**Files:**

- Create: `app/Services/WakaStudentCaseService.php`
- Modify: `app/Http/Requests/WakaMonitoringRequest.php`
- Create: `app/Http/Requests/WakaReportRequest.php`
- Modify: `app/Http/Controllers/WakaMonitoringController.php`
- Modify: `app/Policies/WakaMonitoringPolicy.php`
- Modify: `routes/waka.php`
- Create: `resources/views/pages/waka/students.blade.php`
- Create: `resources/views/pages/waka/reports.blade.php`
- Create: `resources/views/pages/waka/_handling-report.blade.php`
- Delete: `resources/views/pages/waka/monitoring.blade.php`
- Extend: `tests/Feature/WakaMonitoringTest.php`

**Interfaces:**

- Consumes: `WakaCaseProjectionQuery::build()`, `WakaMonitoringService::paginateSafe()`, dan `WakaMonitoringService::exportCsvRows()`.
- Produces: route `waka.monitoring.students`, `waka.reports`, `waka.monitoring.handling`, dan `waka.monitoring.export`.

- [ ] **Step 1: Tulis test dua halaman yang berbeda**

```php
public function test_students_page_groups_multiple_cases_into_one_student_row(): void
{
    [$waka, $student] = $this->wakaStudentWithCases(2);

    $this->actingAs($waka)
        ->get(route('waka.monitoring.students'))
        ->assertOk()
        ->assertSee($student->name)
        ->assertSee('2 kasus')
        ->assertSee('1 masih aktif');
}

public function test_reports_page_uses_goal_based_tabs(): void
{
    $waka = $this->createUserWithRole('waka_kesiswaan');

    $this->actingAs($waka)
        ->get(route('waka.reports', ['tab' => 'penanganan']))
        ->assertOk()
        ->assertSeeInOrder(['Monitoring Penanganan', 'Rekap Periode', 'Laporan Akhir'])
        ->assertDontSee('Pelanggaran per Murid')
        ->assertDontSee('Poin Pelanggaran');
}
```

- [ ] **Step 2: Tulis test akses dan bookmark lama**

Uji guest, akun nonaktif, Guru BK, dan Admin IT ditolak. Koordinator ditolak
dari daftar murid, penanganan, rekap, dan ekspor, tetapi boleh membuka tab
`laporan-akhir`. Bookmark lama diarahkan ke tab penanganan sambil mempertahankan
filter tervalidasi:

```php
$this->actingAs($waka)
    ->get(route('waka.monitoring.handling', [
        'period' => '2026-09',
        'status' => ServiceRecordStatus::IN_PROGRESS,
        'sort' => 'murid',
        'direction' => 'asc',
        'page' => 2,
    ]))
    ->assertRedirect(route('waka.reports', [
        'tab' => 'penanganan',
        'period' => '2026-09',
        'status' => ServiceRecordStatus::IN_PROGRESS,
        'sort' => 'murid',
        'direction' => 'asc',
        'page' => 2,
    ]));

$this->actingAs($waka)
    ->get(route('waka.reports', [
        'tab' => 'penanganan',
        'sort' => 'cases.registration_number',
    ]))
    ->assertSessionHasErrors('sort');

$this->actingAs($waka)
    ->get(route('waka.monitoring.students', ['sort' => 'bidang']))
    ->assertSessionHasErrors('sort');
```

- [ ] **Step 3: Jalankan test untuk memastikan gagal**

```powershell
php artisan test tests/Feature/WakaMonitoringTest.php --filter="groups_multiple|goal_based|access|bookmark|forged"
```

- [ ] **Step 4: Implementasikan WakaReportRequest**

```php
final class WakaReportRequest extends FormRequest
{
    public const array TABS = ['penanganan', 'rekap', 'laporan-akhir'];

    public function rules(): array
    {
        return [
            'tab' => ['nullable', Rule::in(self::TABS)],
            'period' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'status' => ['nullable', Rule::in(ServiceRecordStatus::codes())],
            'sort' => ['nullable', Rule::in(WakaMonitoringRequest::HANDLING_SORT_ALLOWLIST)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function tab(): string
    {
        return $this->string('tab', 'penanganan')->toString();
    }

    /** @return array<string, string|null> */
    public function monitoringParams(): array;

    /** @return array{academic_year_id: int, date_start: string, date_end: string} */
    public function recapParams(): array;
}
```

`authorize()` memanggil `WakaMonitoringPolicy::viewReportTab($user, $this->tab())`.
Policy mengizinkan Waka aktif untuk seluruh tab dan Koordinator aktif hanya
untuk `laporan-akhir`. `prepareForValidation()` mengisi tahun ajaran aktif/terbaru
dan kedua batas tanggal hanya ketika tab `rekap` aktif. Tambahkan `after()`
validation untuk memastikan tahun ajaran tersedia dan rentang tanggal rekap
berada di dalam tahun ajaran terpilih. `monitoringParams()` hanya mengembalikan
`period/status/sort/direction/page`; `recapParams()` hanya mengembalikan tiga
filter rekap. Parameter dari tab lain tidak boleh memengaruhi query atau audit.

- [ ] **Step 5: Implementasikan agregasi satu row per murid**

Kelompokkan berdasarkan identitas internal, bukan nama:

```php
$key = $case->student_id !== null
    ? 'student:'.$case->student_id
    : 'temporary:'.$case->temporary_student_id;
```

Return row:

```php
[
    'nama_murid' => string,
    'kelas' => string,
    'jumlah_kasus' => int,
    'jumlah_aktif' => int,
    'status_terbaru' => string,
    'status_code' => string,
    'guru_bk' => string,
    'coordination_url' => ?string,
]
```

Filter `period` membatasi seluruh hitungan. Jika `status` aktif, hanya kasus
dengan status tersebut yang ikut dihitung dan menentukan apakah murid muncul.
Lakukan grouping sebelum slicing halaman, urutkan nama secara case-insensitive
dengan key identitas sebagai tie-breaker, lalu buat `LengthAwarePaginator` 20
row per halaman dan pertahankan query string. Model kasus tetap berada di dalam
service dan tidak diteruskan ke controller.

Pada `WakaMonitoringRequest::authorize()`, gunakan
`exportMonitoring()` ketika route adalah `waka.monitoring.export`; route halaman
memakai `viewMonitoring()`.

- [ ] **Step 6: Pisahkan controller action**

```php
public function students(WakaMonitoringRequest $request): View;
public function reports(WakaReportRequest $request): View;
public function legacyHandling(WakaMonitoringRequest $request): RedirectResponse;
public function export(WakaMonitoringRequest $request): StreamedResponse;
```

`students()` dan `reports()` menerima paginator/array aman dari service, tidak
melakukan mapping model di controller. `reports()` hanya menjalankan query
penanganan ketika tab `penanganan` aktif; tab lain tidak mengeksekusi query
detail. Setiap response sukses memanggil `auditViewed()` dengan mode halaman
dan jumlah row pada halaman, bukan total lintas halaman. `legacyHandling()`
hanya meneruskan parameter hasil `validated()` yang relevan.

- [ ] **Step 7: Tambahkan route**

```php
Route::get('/waka/students-with-cases', [WakaMonitoringController::class, 'students'])
    ->name('waka.monitoring.students');
Route::get('/waka/reports', [WakaMonitoringController::class, 'reports'])
    ->name('waka.reports');
Route::get('/waka/handling-reports', [WakaMonitoringController::class, 'legacyHandling'])
    ->name('waka.monitoring.handling');
Route::get('/waka/handling-reports/export', [WakaMonitoringController::class, 'export'])
    ->name('waka.monitoring.export');
```

- [ ] **Step 8: Buat view desktop dan mobile**

`students.blade.php` memakai tabel desktop dan kartu mobile. `reports.blade.php`
menjadi container tiga link deep-link dengan `aria-current="page"`; jangan
memakai `role="tab"` tanpa implementasi keyboard tablist. `_handling-report.blade.php`
memakai satu row utama dan satu row ringkasan/tindak lanjut. Pada mobile,
ringkasan panjang memakai `<details><summary>` native. Jangan gunakan inline
raw hex atau `onclick`.

Setiap dataset kosong memakai `x-empty-state` dengan penjelasan periode/filter
dan link `Reset filter` bila filter aktif. Validation error memakai komponen
ringkasan error existing dengan fokus yang dapat dicapai keyboard; stack trace
dan detail query tidak dirender. Karena halaman menggunakan navigasi GET
server-rendered tanpa fetch asinkron, tidak dibuat skeleton palsu atau state
loading JavaScript; header dan ruang panel tetap stabil selama navigasi browser.
Tampilkan notice hanya-baca tanpa membuat seluruh konten tampak disabled.

- [ ] **Step 9: Verifikasi**

```powershell
php artisan test tests/Feature/WakaMonitoringTest.php
npm run check:frontend
git diff --check
```

- [ ] **Step 10: Commit**

```powershell
git add app/Http/Controllers/WakaMonitoringController.php app/Http/Requests/WakaMonitoringRequest.php app/Http/Requests/WakaReportRequest.php app/Services/WakaStudentCaseService.php routes/waka.php resources/views/pages/waka tests/Feature/WakaMonitoringTest.php
git commit -m "feat: pisahkan daftar murid dan laporan Waka"
```

---

### Task 4: Bangun Dashboard Khusus Waka

**Files:**

- Create: `app/Services/WakaDashboardService.php`
- Modify: `app/Services/DashboardService.php`
- Modify: `app/Http/Controllers/DashboardController.php`
- Create: `resources/views/pages/waka/dashboard.blade.php`
- Modify: `resources/views/pages/dashboard/html.blade.php`
- Create: `tests/Feature/WakaDashboardTest.php`
- Extend: `tests/Feature/DashboardNotificationTest.php`
- Extend: `tests/Feature/FrontendPreviewTest.php`

**Interfaces:**

- Consumes: `WakaCaseProjectionQuery::build()`, `WakaMonitoringService::auditViewed()`, dan `ServiceRecordStatus`.
- Produces: `WakaDashboardService::build(User $waka, ?AcademicYear $year): array`.

- [ ] **Step 1: Tulis test metric dan urutan informasi**

```php
public function test_waka_dashboard_shows_school_metrics_without_case_codes(): void
{
    [$waka, $case] = $this->dashboardFixture();

    $this->actingAs($waka)->get(route('dashboard.preview'))
        ->assertOk()
        ->assertSeeInOrder([
            'Kasus berjalan',
            'Sedang diproses',
            'Membutuhkan tindak lanjut',
            'Selesai bulan ini',
            'Membutuhkan Perhatian',
            'Komposisi Status',
            'Penanganan Terbaru',
        ])
        ->assertDontSee($case->registration_number)
        ->assertDontSee('SENTINEL-INTERNAL');
}
```

- [ ] **Step 2: Tulis test tautan detail bersyarat**

Buat satu kasus terkoordinasi dan satu nonterkoordinasi. Hanya kasus
terkoordinasi yang memiliki `Buka detail koordinasi`.

- [ ] **Step 3: Jalankan test untuk memastikan gagal**

```powershell
php artisan test tests/Feature/WakaDashboardTest.php
```

Expected: FAIL karena dashboard existing memakai kode kasus dan wrapper link.

- [ ] **Step 4: Implementasikan WakaDashboardService**

```php
final class WakaDashboardService
{
    /** @return array<string, mixed> */
    public function build(User $waka, ?AcademicYear $year): array
    {
        [$start, $end] = $this->period($year);

        $cases = $this->projection->build($waka, [
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
        ]);

        return [
            'role_key' => 'waka',
            'read_only' => true,
            'metrics' => $this->metrics($start, $end),
            'attention' => $this->attentionRows($waka, $start, $end, 5),
            'status_composition' => $this->statusComposition($start, $end),
            'latest' => $this->latestRows($waka, $start, $end, 5),
        ];
    }
}
```

Definisi metric:

```text
Kasus berjalan = status nonterminal dalam periode tahun ajaran
Sedang diproses = status sedang_diproses
Membutuhkan tindak lanjut = status membutuhkan_tindak_lanjut
Selesai bulan ini = status selesai dengan closed_at pada bulan berjalan
```

Urutan `Membutuhkan Perhatian`:

1. tindak lanjut terdekat yang belum dibatalkan;
2. status membutuhkan tindak lanjut tanpa jadwal mendatang;
3. tanggal layanan terlama;
4. ID kasus sebagai tie-breaker.

Semua row `attention` dan `latest` dibentuk melalui proyeksi aman yang sama.
Owner memilih assignment yang efektif saat ini dengan fallback owner terakhir;
kelas memilih membership efektif pada tanggal layanan. `coordination_url` hanya
diisi ketika koordinasi kepada actor Waka ditemukan.

- [ ] **Step 5: Delegasikan cabang Waka dari DashboardService**

```php
public function __construct(private readonly WakaDashboardService $wakaDashboard) {}

if ($user->hasRole('waka_kesiswaan')) {
    return $this->wakaDashboard->build($user, $academicYear);
}
```

Pertahankan cabang Koordinator dan Guru BK sebelum cabang Waka agar multi-role
tetap memakai fungsi operasional yang sah.

- [ ] **Step 6: Buat view dashboard Waka**

Pada `dashboard/html.blade.php`:

```blade
@if($dashboard['role_key'] === 'waka')
    @include('pages.waka.dashboard')
@else
    {{-- dashboard operasional existing tetap utuh --}}
@endif
```

View Waka menggunakan `sibk-stat-card`, `sibk-panel`, `sibk-badge`, dan empty
state existing. Jangan menjadikan seluruh row anchor.

Setelah data Dashboard Waka berhasil dibangun, `DashboardController` mencatat
`waka.monitoring.viewed` dengan mode `dashboard`, filter tahun ajaran yang sudah
dinormalisasi, dan hitungan row aman. Cabang dashboard role lain tidak mencatat
event Waka.

- [ ] **Step 7: Verifikasi**

```powershell
php artisan test tests/Feature/WakaDashboardTest.php
php artisan test tests/Feature/DashboardNotificationTest.php
php artisan test tests/Feature/FrontendPreviewTest.php --filter=waka
npm run check:frontend
```

- [ ] **Step 8: Commit**

```powershell
git add app/Services/WakaDashboardService.php app/Services/DashboardService.php app/Http/Controllers/DashboardController.php resources/views/pages/waka/dashboard.blade.php resources/views/pages/dashboard/html.blade.php tests/Feature/WakaDashboardTest.php tests/Feature/DashboardNotificationTest.php tests/Feature/FrontendPreviewTest.php
git commit -m "feat: tampilkan dashboard khusus Waka"
```

---

### Task 5: Tambahkan Rekap Periode dan Empty State Laporan Akhir

**Files:**

- Create: `app/Services/WakaPeriodReportService.php`
- Modify: `app/Http/Controllers/WakaMonitoringController.php`
- Modify: `app/Policies/ReportPolicy.php`
- Modify: `resources/views/pages/waka/reports.blade.php`
- Create: `resources/views/pages/waka/_period-recap.blade.php`
- Create: `resources/views/pages/waka/_final-report-development.blade.php`
- Create: `tests/Feature/WakaReportPageTest.php`
- Extend: `tests/Feature/ReportManagementTest.php`

**Interfaces:**

- Consumes: filter tahun ajaran/tanggal tervalidasi.
- Produces: `WakaPeriodReportService::build(AcademicYear $year, CarbonImmutable $start, CarbonImmutable $end): array`.

- [ ] **Step 1: Tulis test rekap aggregate-only**

```php
public function test_period_recap_contains_aggregates_without_identity_or_narrative(): void
{
    [$waka, $year, $case] = $this->periodFixture();

    $this->actingAs($waka)->get(route('waka.reports', [
        'tab' => 'rekap',
        'academic_year_id' => $year->id,
        'date_start' => '2026-07-01',
        'date_end' => '2026-12-31',
    ]))->assertOk()
        ->assertSee('Murid ditangani')
        ->assertSee('Kasus tercatat')
        ->assertSee('Konteks Kesiswaan')
        ->assertDontSee($case->identityName())
        ->assertDontSee($case->registration_number)
        ->assertDontSee('SENTINEL-NARASI');
}
```

- [ ] **Step 2: Tulis test Laporan Akhir**

```php
$this->actingAs($waka)
    ->get(route('waka.reports', ['tab' => 'laporan-akhir']))
    ->assertOk()
    ->assertSee('Dalam pengembangan')
    ->assertDontSee('Simpan Draf')
    ->assertDontSee('Terbitkan')
    ->assertDontSee('Unduh PDF');
```

Ulangi assertion empty state untuk Koordinator BK aktif. Pastikan Koordinator
tetap menerima `403` pada tab `penanganan`, tab `rekap`, daftar murid, dan
ekspor Waka.

- [ ] **Step 3: Tulis test laporan generik ditutup untuk Waka murni**

```php
$this->actingAs($waka)->get(route('reports.index'))->assertForbidden();
$this->actingAs($waka)->get(route('reports.preview', [
    'type' => ReportService::TYPE_STUDENT_VIOLATIONS,
]))->assertForbidden();
```

Akun multi-role Guru BK/Koordinator tetap memakai laporan generik sesuai fungsi
non-Waka yang sah.

- [ ] **Step 4: Jalankan test untuk memastikan gagal**

```powershell
php artisan test tests/Feature/WakaReportPageTest.php
php artisan test tests/Feature/ReportManagementTest.php --filter=waka
```

- [ ] **Step 5: Implementasikan WakaPeriodReportService**

```php
final class WakaPeriodReportService
{
    /** @return array<string, mixed> */
    public function build(
        AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $cases = BkCase::query()->whereBetween('service_date', [$start, $end]);

        return [
            'metrics' => [
                'served_students' => $this->distinctIdentityCount(clone $cases),
                'cases_recorded' => (clone $cases)->count(),
                'needs_follow_up' => $this->caseCountByStatus(
                    clone $cases,
                    ServiceRecordStatus::NEEDS_FOLLOW_UP,
                ),
                'completed' => $this->caseCountByStatus(
                    clone $cases,
                    ServiceRecordStatus::COMPLETED,
                ),
            ],
            'service_fields' => $this->serviceFieldCounts(clone $cases),
            'statuses' => $this->statusCounts(clone $cases),
            'student_affairs' => $this->studentAffairsCounts($start, $end),
            'classes' => $this->classRows($year, $start, $end),
        ];
    }
}
```

Semua query hanya mengembalikan hitungan atau label referensi. Jangan select
nama, NISN, source identifier e-Tatib, narasi prestasi, atau kode kasus.

`studentAffairsCounts()` menghitung record e-Tatib aktif pada rentang tanggal,
distinct `student_id` non-null sebagai murid yang sudah terkait, serta prestasi
dengan `verificationStatus.code = terverifikasi`. Record e-Tatib yang belum
terpetakan tetap masuk hitungan pelanggaran tetapi tidak dihitung sebagai murid
terkait. ID internal tidak pernah masuk return array, view, atau audit.
`classRows()` menentukan kelas dari membership yang efektif pada
`cases.service_date`, bukan kelas aktif saat request berlangsung.

- [ ] **Step 6: Tutup laporan generik untuk akun Waka murni**

```php
public function viewAny(User $user): bool
{
    return $user->is_active
        && $user->hasAnyRole(['guru_bk', 'koordinator_bk']);
}
```

- [ ] **Step 7: Render dua partial**

`_period-recap.blade.php` menampilkan metric, distribusi bidang/status, konteks
kesiswaan, dan rekap kelas. `_final-report-development.blade.php` hanya:

```blade
<x-empty-state
    title="Dalam pengembangan"
    description="Susunan laporan akhir sedang disiapkan bersama pihak BK dan sekolah. Halaman ini akan tersedia setelah format laporan disepakati."
/>
```

- [ ] **Step 8: Verifikasi**

```powershell
php artisan test tests/Feature/WakaReportPageTest.php
php artisan test tests/Feature/ReportManagementTest.php
php vendor/bin/pint --test app/Services/WakaPeriodReportService.php app/Policies/ReportPolicy.php tests/Feature/WakaReportPageTest.php
npm run check:frontend
```

- [ ] **Step 9: Commit**

```powershell
git add app/Services/WakaPeriodReportService.php app/Http/Controllers/WakaMonitoringController.php app/Policies/ReportPolicy.php resources/views/pages/waka tests/Feature/WakaReportPageTest.php tests/Feature/ReportManagementTest.php
git commit -m "feat: satukan laporan periodik Waka"
```

---

### Task 6: Terapkan Navigasi Khusus dan Responsive Design Existing

**Files:**

- Modify: `resources/views/components/sidebar.blade.php`
- Modify: `resources/views/components/empty-state.blade.php`
- Modify: `resources/scss/app-dashboard.scss`
- Extend: `tests/Feature/FrontendPreviewTest.php`
- Extend: `tests/Feature/AuthorizationMatrixTest.php`

**Interfaces:**

- Consumes: route `dashboard.preview`, `waka.monitoring.students`, dan `waka.reports`.
- Produces: menu Waka murni dan class responsive portal.

- [ ] **Step 1: Tulis test sidebar Waka murni**

```php
public function test_waka_only_sidebar_contains_monitoring_navigation(): void
{
    $this->authenticateAs('waka_kesiswaan');

    $this->get(route('dashboard.preview'))
        ->assertOk()
        ->assertSeeInOrder([
            'Dashboard',
            'PEMANTAUAN WAKA',
            'Murid dengan Kasus',
            'Laporan',
            'UTILITAS',
        ])
        ->assertDontSee('Layanan BK')
        ->assertDontSee('Data Murid')
        ->assertDontSee('Penugasan Kelas')
        ->assertDontSee('Pengalihan Kasus');
}
```

- [ ] **Step 2: Tulis test multi-role**

Akun Waka + Koordinator tetap memakai menu Koordinator dan memperoleh section
portal Waka. Akun Waka murni hanya memperoleh navigasi khusus.

- [ ] **Step 3: Jalankan test untuk memastikan gagal**

```powershell
php artisan test tests/Feature/FrontendPreviewTest.php --filter=waka_only_sidebar
php artisan test tests/Feature/AuthorizationMatrixTest.php --filter=waka
```

- [ ] **Step 4: Implementasikan cabang sidebar**

```blade
@php
    $isWakaOnly = auth()->user()?->hasRole('waka_kesiswaan')
        && ! auth()->user()?->hasAnyRole(['guru_bk', 'koordinator_bk', 'admin_it']);
@endphp

@if($isWakaOnly)
    {{-- Dashboard --}}
    <p class="sibk-sidebar__section">PEMANTAUAN WAKA</p>
    {{-- waka.monitoring.students: Murid dengan Kasus --}}
    {{-- waka.reports: Laporan --}}
    <p class="sibk-sidebar__section">UTILITAS</p>
    {{-- Notifikasi dan Akun Saya --}}
@else
    {{-- navigasi role existing --}}
    @can('viewWakaMonitoring')
        {{-- section PEMANTAUAN WAKA yang sama untuk akun multi-role Waka --}}
    @endcan
@endif
```

Gunakan ikon SVG existing dan jangan membuat section `UTAMA`. Link Murid aktif
hanya pada `waka.monitoring.students`; link Laporan aktif pada `waka.reports`.
Legacy route tidak menjadi target navigasi.

- [ ] **Step 5: Tambahkan responsive classes**

```scss
.sibk-waka-tabs {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  scrollbar-width: thin;

  .nav-link {
    min-height: 2.75rem;
    white-space: nowrap;
  }
}

@media (max-width: 767.98px) {
  .sibk-waka-table--desktop { display: none; }
  .sibk-waka-card-list { display: grid; gap: 1rem; }
}

@media (min-width: 768px) {
  .sibk-waka-card-list { display: none; }
}
```

Semua warna, radius, shadow, dan focus memakai `var(--sibk-*)`. Target interaksi
minimum 44 CSS pixel. Jangan menambahkan motion library.

- [ ] **Step 6: Tambahkan accessibility assertions**

Tambahkan `aria-hidden="true"` pada wrapper/ikon dekoratif komponen
`x-empty-state`. Periksa `aria-current`, `aria-sort`, label field, `aria-hidden`
pada ikon dekoratif, heading empty state, dan ketiadaan `onclick` pada row.

- [ ] **Step 7: Verifikasi frontend**

```powershell
php artisan test tests/Feature/FrontendPreviewTest.php
php artisan test tests/Feature/AuthorizationMatrixTest.php
npm run check:frontend
npm run build
git diff --check
```

- [ ] **Step 8: Commit**

```powershell
git add resources/views/components/sidebar.blade.php resources/views/components/empty-state.blade.php resources/scss/app-dashboard.scss tests/Feature/FrontendPreviewTest.php tests/Feature/AuthorizationMatrixTest.php
git commit -m "feat: sederhanakan navigasi portal Waka"
```

---

### Task 7: Verification Gate dan UAT Portal Waka

**Files:**

- Create: `docs/testing/2026-09-13-uat-portal-waka.md`
- Modify: `docs/development-log.md`

**Interfaces:**

- Consumes: seluruh task sebelumnya.
- Produces: bukti automated verification, UAT desktop/mobile, audit, dan SHA implementasi.

- [ ] **Step 1: Jalankan focused tests**

```powershell
php artisan test tests/Feature/WakaMonitoringTest.php
php artisan test tests/Feature/WakaDashboardTest.php
php artisan test tests/Feature/WakaReportPageTest.php
php artisan test tests/Feature/DashboardNotificationTest.php
php artisan test tests/Feature/ReportManagementTest.php
php artisan test tests/Feature/AuthorizationMatrixTest.php
php artisan test tests/Feature/FrontendPreviewTest.php
```

Expected: 0 failure dan 0 error.

- [ ] **Step 2: Jalankan full automated gate**

```powershell
php artisan test
php vendor/bin/pint --test
php artisan config:cache
php artisan view:cache
npm run check:frontend
npm run build
git diff --check
php artisan config:clear
php artisan view:clear
```

Expected: seluruh command exit code 0.

- [ ] **Step 3: Jalankan privacy repository scan**

```powershell
rg -n "registration_number|initial_info|internal_note|final_result|continued_plan|next_plan" resources/views/pages/waka
rg -n '\$case->(registration_number|initial_info|internal_note|final_result|continued_plan)|\[''(registration_number|initial_info|internal_note|final_result|continued_plan|next_plan)''\]' app/Services/Waka* app/Http/Controllers/WakaMonitoringController.php
```

Expected: field terlarang tidak muncul pada view atau akses property/data output.
Nama field boleh muncul di assertion denylist test dan dokumentasi keamanan.

- [ ] **Step 4: Jalankan UAT desktop dan mobile**

Verifikasi pada 1440 x 900, 1024 x 768, 768 x 1024, dan 390 x 844:

```text
Dashboard menampilkan metric, perhatian, komposisi, dan terbaru.
Murid dengan Kasus memakai satu row/kartu per murid.
Laporan mempunyai tiga tab dan mempertahankan active state melalui URL.
Monitoring Penanganan dapat filter, sort, paginate, dan export CSV.
Rekap Periode hanya menampilkan agregat.
Laporan Akhir hanya menampilkan Dalam pengembangan.
Koordinator dapat membuka placeholder Laporan Akhir tetapi tidak tab Waka lain.
Tidak ada horizontal overflow halaman.
Tidak ada tombol mutasi.
Kasus nonterkoordinasi tidak mempunyai tautan detail.
Empty state menjelaskan filter aktif dan menyediakan Reset filter.
Urutan fokus mengikuti urutan visual dan focus ring tidak tertutup sidebar/header.
```

- [ ] **Step 5: Verifikasi audit**

Setelah membuka Dashboard, daftar murid, ketiga tab laporan, dan export CSV,
periksa `waka.monitoring.viewed` serta `waka.monitoring.exported`. Mode audit
harus sesuai halaman dan payload tidak boleh mengandung nama murid, NISN, kode
kasus, `waka_summary`, atau narasi pelayanan.

- [ ] **Step 6: Catat hasil dan SHA**

Isi `docs/testing/2026-09-13-uat-portal-waka.md` dengan branch/SHA, viewport,
skenario PASS/FAIL, jumlah test/assertion, hasil build/lint, audit privacy, dan
path rekaman Browser Use jika direkam. Perbarui `docs/development-log.md`.

- [ ] **Step 7: Commit evidence**

```powershell
git add docs/testing/2026-09-13-uat-portal-waka.md docs/development-log.md
git commit -m "docs: catat verifikasi portal Waka"
```

---

## Urutan Eksekusi

```text
Task 1 Requirement contract
    -> Task 2 Safe projection foundation
    -> Task 3 Students + handling pages
    -> Task 4 Waka dashboard
    -> Task 5 Period recap + final report empty state
    -> Task 6 Navigation + responsive
    -> Task 7 Full verification + UAT
```

Task 2 menjadi dependency bersama dan wajib selesai sebelum UI lain. Task 3,
Task 4, dan Task 5 menyentuh service/view berbeda setelah fondasi tersedia,
tetapi eksekusi pada satu branch tetap disarankan berurutan agar kontrak
controller tidak berubah bersamaan.

## Definition of Done

- Requirement aktif, API contract, dan implementasi memakai struktur Portal Waka yang sama.
- Akun Waka murni mempunyai Dashboard, Murid dengan Kasus, Laporan, Notifikasi, dan Akun Saya.
- Dashboard dan portal tidak mengikuti scope kelas/assignment Guru BK.
- Portal menampilkan proyeksi aman seluruh kasus sekolah tanpa membuka detail nonterkoordinasi.
- Halaman Murid dengan Kasus berbeda secara informasi dari Monitoring Penanganan.
- Laporan hanya mempunyai tab Monitoring Penanganan, Rekap Periode, dan Laporan Akhir.
- Laporan Akhir hanya menampilkan `Dalam pengembangan`.
- Laporan generik lama tidak tersedia bagi akun Waka murni.
- HTML, CSV, dan audit tidak memuat kode kasus, NISN, atau narasi terlarang.
- Seluruh visual memakai token dan komponen existing Ruang BK.
- Focused tests, full suite, Pint, frontend checker, build, cache checks, dan diff check lulus.
- UAT desktop/mobile dan audit privacy tercatat.
