# Revisi SIBK 3.2 untuk Layanan Guru BK Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menerapkan alur kasus dan konsultasi Revisi SIBK 3.2, termasuk akses baca penuh Waka yang diaudit, autosave lokal, laporan yang konsisten, dan pembersihan skema lama secara forward-only.

**Architecture:** Pertahankan pola Laravel existing: Form Request sebagai batas input, policy dan query scope sebagai batas akses, service sebagai pemilik transaksi/audit, serta Blade/Bootstrap/JavaScript ringan untuk UI. Implementasi dibagi menjadi migration aditif, perpindahan seluruh consumer, lalu migration destruktif setelah scan dependency bersih; tidak ada tabel versi, backend draft, atau dependency baru.

**Tech Stack:** PHP 8.3+, Laravel 13, Eloquent, PHPUnit 12, Blade, Bootstrap 5.3, JavaScript native, Vite, SQLite untuk test.

**Spec:** `docs/superpowers/specs/2026-09-17-revisi-sibk-3-2-guru-bk-design.md`

## Global Constraints

- Gunakan branch fitur dari `cobasidebar`; migration selalu forward-only dan database shared/production tidak boleh di-reset.
- Pertahankan thin controller, Form Request, service/action, policy server-side, scope data, soft delete, dan transaksi existing.
- Waka hanya boleh membaca seluruh kasus/konsultasi melalui proyeksi allowlist. Setiap pembukaan detail oleh Waka diaudit; narasi tidak masuk CSV/ekspor massal.
- Nilai terbaru berada pada row utama. `audit_logs` hanya menerima perubahan field yang benar-benar berbeda saat Simpan, Simpan dan Selesaikan, atau perubahan inline berhasil; autosave tidak diaudit.
- Autosave hanya memakai `localStorage`, TTL 24 jam, namespace per user/form/record, dan tidak boleh menyimpan password, token, credential, file, CSRF, atau method spoofing.
- Jangan menambah package. Gunakan modal Bootstrap, `window.confirm`, `<input type="date">`, SVG existing, dan JavaScript native.
- Gunakan istilah UI Bahasa Indonesia dan `murid`. Gunakan kelas historis pada tanggal layanan serta `id` sebagai tie-breaker sorting/pagination.
- Satu task menghasilkan commit kecil berbahasa Indonesia. Jangan mulai checkpoint destruktif sebelum focused gate checkpoint consumer lulus.

## Peta Struktur Perubahan

```text
docs/requirements + docs/api-contract.md
  -> kontrak aktif sebelum kode berubah

database/migrations + ReferenceSeeder + ServiceRecordStatus
  -> kolom/reference aditif
  -> perpindahan seluruh consumer
  -> drop tabel/kolom retired

Form Request -> Controller -> CaseService/ConsultationService -> AuditService
  -> validasi, optimistic concurrency, transaksi, diff-only audit

CasePolicy/ConsultationPolicy + accessibleTo scopes
  -> Guru BK/Koordinator sesuai ownership existing
  -> Waka membaca semua layanan melalui projection allowlist

Blade + service-records.js + form-draft.js
  -> modal, inline follow-up, autosave perangkat

Dashboard/report/student profile/dummy seeder
  -> tidak lagi bergantung pada follow-up event, coordination, atau status konsultasi
```

## Kontrak Target Ringkas

```text
cases
  + follow_up_type_id nullable -> references.id
  - waka_summary, final_result, continued_plan

consultations
  + problem, handling, result
  - registration_number, case_id, status_id, topic, referral_source,
    starts_at, ends_at, follow_up_date, general_summary

drop tables
  follow_ups, case_coordinations, consultation_private_notes
```

Endpoint yang dipertahankan atau diganti:

```text
GET    /cases?tab=kasus|konsultasi             cases.index
GET    /cases/{case}                           cases.show
GET    /cases/{case}/edit                      cases.edit
PATCH  /cases/{case}                           cases.update
PATCH  /cases/{case}/follow-up                 cases.follow-up.update
GET    /consultations/{consultation}            consultations.show
GET    /consultations/{consultation}/edit       consultations.edit
PATCH  /consultations/{consultation}            consultations.update
```

`cases.show`, `cases.edit`, `consultations.show`, dan `consultations.edit`
merender partial modal bila query `modal=1`; akses langsung tanpa parameter tetap
merender halaman penuh sebagai fallback. Submit fetch memakai `Accept:
application/json`; kegagalan validasi mengembalikan 422, termasuk konflik
`expected_updated_at`.

---

## Checkpoint A — Kontrak dan Fondasi Aditif

### Task 1: Jadikan revisi 3.2 source of truth aktif

**Files:**
- Modify: `docs/requirements/PRD_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements/SRS_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements-index.md`
- Modify: `docs/api-contract.md`
- Modify: `docs/testing/authorization-matrix.md`
- Modify: `docs/current-work.md`
- Test: `tests/Feature/AuthorizationMatrixTest.php`

**Interfaces:**
- Consumes: keputusan final pada spec Revisi SIBK 3.2.
- Produces: requirement aktif untuk tiga status kasus, satu atribut tindak lanjut, konsultasi mandiri, akses read-only Waka, autosave lokal, optimistic concurrency, dan urutan migration.

- [ ] **Step 1: Tambahkan assertion otorisasi yang gagal**

Tambahkan matrix test bahwa Waka aktif dapat `viewAny` dan `view` semua `BkCase`
serta `Consultation`, tetapi tidak dapat `create`, `update`, `archive`,
`resolve`, atau mengubah tindak lanjut.

Run:

```powershell
php artisan test tests/Feature/AuthorizationMatrixTest.php
```

Expected: FAIL karena policy Waka masih bergantung pada koordinasi dan belum
memberi akses konsultasi.

- [ ] **Step 2: Perbarui dokumen kontrak tanpa menggandakan spec**

Di PRD/SRS, ganti requirement yang bertentangan dan beri ID yang dapat dicari
untuk: lifecycle kasus, konsultasi, Waka read-only, audit minimal, autosave,
sorting allowlist, dan concurrency. Di `docs/api-contract.md`, catat payload:

```json
{
  "expected_updated_at": "2026-09-17T08:30:00.000000Z",
  "action": "save|complete",
  "initial_info": "Murid mengalami kesulitan beradaptasi di kelas.",
  "initial_action": "Guru BK melakukan asesmen awal.",
  "resolution_summary": "Murid dan Guru BK menyepakati langkah penyelesaian."
}
```

Serta payload inline:

```json
{
  "follow_up_type_id": 123,
  "expected_updated_at": "2026-09-17T08:30:00.000000Z"
}
```

- [ ] **Step 3: Aktifkan plan pada handoff**

Ubah `docs/current-work.md` agar branch `revisi-sibk-3-2`, spec, plan, tiga
checkpoint, dan gate berikutnya menjadi pekerjaan aktif. Jangan mengubah catatan
baseline lama selain status/langkah berikutnya.

- [ ] **Step 4: Periksa konsistensi istilah**

Run:

```powershell
rg -n "Baru dicatat|Membutuhkan tindak lanjut|status konsultasi|koordinasi Waka" docs/requirements docs/api-contract.md docs/testing/authorization-matrix.md
git diff --check
```

Expected: istilah lama hanya muncul pada konteks migrasi/retirement yang
eksplisit; `git diff --check` exit 0.

- [ ] **Step 5: Commit**

```powershell
git add docs tests/Feature/AuthorizationMatrixTest.php
git commit -m "docs: aktifkan kontrak revisi SIBK 3.2"
```

### Task 2: Tambahkan skema baru dan reference aktif tanpa drop

**Files:**
- Create: `database/migrations/2026_09_17_000100_add_revisi_sibk_3_2_foundation.php`
- Modify: `app/Support/ServiceRecordStatus.php`
- Modify: `database/seeders/ReferenceSeeder.php`
- Modify: `tests/Feature/FoundationDataTest.php`
- Modify: `tests/Feature/ServiceRecordStatusMigrationTest.php`
- Modify: `tests/Feature/SharedDevelopmentBaselineTest.php`

**Interfaces:**
- Produces: `cases.follow_up_type_id` nullable FK dan index.
- Produces: `consultations.problem`, `handling`, `result` nullable sementara agar migration aman untuk row dummy existing; Form Request tetap mewajibkan nilai pada create/update.
- Produces: status aktif `sedang_diproses`, `membutuhkan_tindak_lanjut`, `selesai` dan empat reference tindak lanjut yang disetujui.

- [ ] **Step 1: Tulis migration test yang gagal**

Assert kolom baru tersedia, status `baru` tidak aktif, label status tepat, dan
hanya empat `follow_up_type` berikut yang aktif:

```php
[
    'surat_panggilan_orang_tua' => 'Surat Panggilan Orang Tua',
    'surat_pernyataan' => 'Surat Pernyataan',
    'home_visit' => 'Home Visit',
    'pengunduran_diri' => 'Pengunduran Diri',
]
```

Run:

```powershell
php artisan test tests/Feature/FoundationDataTest.php tests/Feature/ServiceRecordStatusMigrationTest.php tests/Feature/SharedDevelopmentBaselineTest.php
```

Expected: FAIL karena kolom/reference target belum ada.

- [ ] **Step 2: Implementasikan status aplikasi**

Ubah `ServiceRecordStatus` sehingga `codes()` hanya mengembalikan tiga status,
`initialCode()` mengembalikan `IN_PROGRESS`, dan labelnya tepat:

```php
private const array LABELS = [
    self::IN_PROGRESS => 'Sedang Proses',
    self::NEEDS_FOLLOW_UP => 'Tindak Lanjut',
    self::COMPLETED => 'Selesai',
];
```

Pertahankan konstanta kode `baru` hanya bila migration historis masih
memerlukannya; konstanta tersebut tidak boleh masuk `codes()` atau input aktif.

- [ ] **Step 3: Implementasikan migration aditif dan seeder idempotent**

Migration harus:

1. menambah kolom baru tanpa menghapus kolom/tabel lama;
2. memetakan kasus berstatus `baru` ke `sedang_diproses` sebelum menonaktifkan reference lama;
3. menonaktifkan seluruh `consultation_status`, `follow_up_status`, dan `coordination_status` untuk input baru;
4. menonaktifkan tipe tindak lanjut lama dan mengaktifkan tepat empat tipe target.

Seeder memakai `updateOrCreate()` dan menonaktifkan row kategori yang tidak ada
di allowlist agar rerun tidak menghidupkan pilihan lama.

- [ ] **Step 4: Jalankan focused gate**

```powershell
php artisan test tests/Feature/FoundationDataTest.php tests/Feature/ServiceRecordStatusMigrationTest.php tests/Feature/SharedDevelopmentBaselineTest.php
php vendor/bin/pint --test app/Support database/migrations database/seeders tests/Feature/FoundationDataTest.php tests/Feature/ServiceRecordStatusMigrationTest.php tests/Feature/SharedDevelopmentBaselineTest.php
git diff --check
```

Expected: seluruh command exit 0; tabel lama masih ada pada checkpoint ini.

- [ ] **Step 5: Commit**

```powershell
git add app/Support database/migrations database/seeders tests/Feature
git commit -m "feat: tambah fondasi data revisi SIBK 3.2"
```

### Task 3: Terapkan kontrak domain kasus baru

**Files:**
- Modify: `app/Models/BkCase.php`
- Modify: `app/Http/Requests/StoreCaseRequest.php`
- Modify: `app/Http/Requests/UpdateCaseRequest.php`
- Create: `app/Http/Requests/UpdateCaseFollowUpRequest.php`
- Modify: `app/Services/AuditService.php`
- Modify: `app/Services/CaseService.php`
- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `app/Policies/CasePolicy.php`
- Modify: `routes/bk-services.php`
- Modify: `tests/Feature/CaseManagementTest.php`

**Interfaces:**
- Consumes: source kasus, identitas manual/e-Tatib lokal, tiga narasi, aksi `save|complete`, dan `expected_updated_at`.
- Produces: kasus baru berstatus Sedang Proses; update diff-only; penyelesaian server-date; satu jenis tindak lanjut terkini.

- [ ] **Step 1: Ganti test perilaku lama dengan test target yang gagal**

Cover minimum: e-Tatib wajib memiliki record lokal tertaut; input manual exact
match NISN memakai `student_id` dan tidak menimpa nama resmi; no-match memakai
identitas sementara; status awal Sedang Proses; penyelesaian mewajibkan
`resolution_summary`; kasus Selesai tetap dapat diedit; stale
`expected_updated_at` ditolak; audit update hanya memuat field berubah.

Tambahkan test endpoint inline untuk set, replace, clear, kasus selesai, role
terlarang, dan reference tidak aktif.

Run:

```powershell
php artisan test tests/Feature/CaseManagementTest.php
```

Expected: FAIL pada kontrak status, payload, dan endpoint baru.

- [ ] **Step 2: Tambahkan audit diff-only pada service existing**

Tambahkan satu method ke `AuditService`, bukan service baru:

```php
public function recordChanges(
    string $action,
    Model $auditable,
    string $summary,
    User $actor,
    array $before,
    array $after,
): ?AuditLog
```

Method mengambil union key, membuang pasangan yang identik secara strict,
memanggil `record()` hanya bila ada perubahan, dan menyimpan subset before/after
yang sama. Gunakan untuk update kasus, penyelesaian, dan inline tindak lanjut.

- [ ] **Step 3: Sederhanakan model/request kasus**

`BkCase` menambah `followUpType(): BelongsTo` dan fillable
`follow_up_type_id`. `UpdateCaseRequest` hanya menerima:

```php
[
    'initial_info' => ['required', 'string', 'max:10000'],
    'initial_action' => ['required', 'string', 'max:10000'],
    'resolution_summary' => [Rule::requiredIf($this->input('action') === 'complete'), 'nullable', 'string', 'max:10000'],
    'action' => ['required', Rule::in(['save', 'complete'])],
    'expected_updated_at' => ['required', 'date'],
]
```

`UpdateCaseFollowUpRequest` menerima reference aktif kategori
`follow_up_type` atau null dan timestamp expected. Policy memakai `update()`;
kasus terminal ditolak oleh service.

- [ ] **Step 4: Ubah `CaseService` sebagai satu-satunya pemilik transisi**

Pertahankan transaksi dan `lockForUpdate()`. Setelah lock, bandingkan timestamp
aktual dengan `expected_updated_at`; gunakan `ValidationException` pada key
tersebut bila stale.

Signature target:

```php
public function update(BkCase $case, array $data, User $actor): BkCase
public function updateFollowUp(BkCase $case, ?int $typeId, string $expectedUpdatedAt, User $actor): BkCase
```

Aturan `updateFollowUp()`:

```php
$statusCode = $typeId === null
    ? ServiceRecordStatus::IN_PROGRESS
    : ServiceRecordStatus::NEEDS_FOLLOW_UP;
```

`action=complete` menetapkan status Selesai dan `closed_at=now()` dari server.
`action=save` tidak mengubah status/`closed_at`, termasuk saat record sudah
Selesai. Hapus kebutuhan `change_reason` dan `waka_summary`.

- [ ] **Step 5: Hubungkan controller dan route tipis**

Tambahkan:

```php
Route::patch('/cases/{case}/follow-up', [CaseController::class, 'updateFollowUp'])
    ->name('cases.follow-up.update');
```

`CaseController::update()` bertipe `RedirectResponse|JsonResponse`: redirect
untuk submit halaman biasa dan JSON untuk request modal. Method
`updateFollowUp()` selalu `JsonResponse`. Laravel mengembalikan error validasi
JSON 422 secara otomatis ketika header `Accept: application/json` dikirim.

Respons JSON sukses inline:

```json
{
  "message": "Tindak lanjut berhasil diperbarui.",
  "data": {
    "status_code": "membutuhkan_tindak_lanjut",
    "status_label": "Tindak Lanjut",
    "follow_up_type_id": 123,
    "follow_up_type_label": "Home Visit",
    "updated_at": "2026-09-17T08:31:00.000000Z"
  }
}
```

- [ ] **Step 6: Jalankan focused gate**

```powershell
php artisan test tests/Feature/CaseManagementTest.php tests/Feature/AuthorizationMatrixTest.php
php vendor/bin/pint --test app/Models/BkCase.php app/Http/Requests app/Services/AuditService.php app/Services/CaseService.php app/Http/Controllers/CaseController.php app/Policies/CasePolicy.php routes/bk-services.php tests/Feature/CaseManagementTest.php
git diff --check
```

Expected: seluruh test target kasus dan matriks role PASS.

- [ ] **Step 7: Commit**

```powershell
git add app routes tests/Feature/CaseManagementTest.php tests/Feature/AuthorizationMatrixTest.php
git commit -m "feat: sederhanakan lifecycle kasus BK"
```

---

## Checkpoint B — Peralihan Consumer dan UI

### Task 4: Bangun daftar, sorting, modal, dan inline tindak lanjut kasus

**Files:**
- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `resources/views/pages/cases/index.blade.php`
- Modify: `resources/views/pages/cases/create.blade.php`
- Modify: `resources/views/pages/cases/show.blade.php`
- Modify: `resources/views/pages/cases/edit.blade.php`
- Create: `resources/views/pages/cases/_detail-modal.blade.php`
- Create: `resources/views/pages/cases/_edit-modal.blade.php`
- Create: `resources/js/service-records.js`
- Modify: `resources/js/app-dashboard.js`
- Modify: `resources/scss/app-dashboard.scss`
- Modify: `scripts/check-frontend.mjs`
- Modify: `tests/Feature/CaseManagementTest.php`

**Interfaces:**
- Produces: tabel final delapan kolom, search nama, filter status, sorting allowlist, modal shared, dan dropdown inline dengan rollback.
- Consumes: endpoint kasus Task 3 dan modal Bootstrap existing.

- [ ] **Step 1: Tambahkan test daftar/UI yang gagal**

Test exact header, search nama saja, filter status, sort `nama|kelas|tanggal|
sumber|bidang|status`, direction `asc|desc`, fallback sort invalid, tie-breaker
`cases.id`, serta tidak adanya aksi mutasi bagi Waka.

Tambahkan static assertions pada `check-frontend.mjs` untuk satu modal shell,
`data-modal-url`, `data-follow-up-url`, indikator saving, dan tidak adanya link
route follow-up/resolve lama. Assert juga `aria-labelledby`,
`modal-dialog-scrollable`, tombol tutup yang berlabel, dan focus trigger kembali
setelah modal ditutup.

Run:

```powershell
php artisan test tests/Feature/CaseManagementTest.php
npm run check:frontend
```

Expected: FAIL karena markup dan sorting baru belum ada.

- [ ] **Step 2: Implementasikan query daftar dengan allowlist lokal**

Di `CaseController::index()`, gunakan `match` lokal, bukan query-builder baru.
`nama` mengurutkan `COALESCE(students.name, temporary_students.input_name)`
melalui correlated subquery; `kelas` memakai correlated subquery membership yang
efektif pada `cases.service_date`; `tanggal` memakai `cases.service_date`;
`sumber`, `bidang`, dan `status` memakai correlated subquery `references.label`
untuk foreign key masing-masing.

Default `tanggal desc`; selalu tambahkan `cases.id` dengan arah sama. Filter UI
hanya Status, sedangkan query tab tetap mempertahankan `tab` dan pagination.

- [ ] **Step 3: Implementasikan modal detail/edit progressive fallback**

Pertahankan halaman `show`/`edit` sebagai fallback. Bila `modal=1`, render
partial body yang sama ke modal shell. Detail menampilkan hanya field approved;
edit hanya tiga narasi, hidden `expected_updated_at`, serta tombol `Simpan` dan
`Simpan dan Selesaikan`.

Untuk kasus terminal, tombol Edit memakai:

```html
data-confirm-message="Kasus ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?"
```

Gunakan `window.confirm`; tidak ada modal konfirmasi kedua dan tidak ada field
alasan.

- [ ] **Step 4: Implementasikan JavaScript minimal**

`service-records.js` menangani:

1. fetch partial ke satu Bootstrap modal;
2. submit form modal dengan CSRF dan render error 422 dekat field;
3. dropdown tindak lanjut: disable, tampilkan `Menyimpan…`, PATCH satu kali,
   update status/timestamp, lalu enable;
4. bila gagal, kembalikan pilihan/status/timestamp lama dan tampilkan error.

Abort request lama dengan `AbortController` bila pengguna menutup modal. Import
`Modal` dari package Bootstrap existing; jangan menambah library.

- [ ] **Step 5: Jalankan focused gate dan commit**

```powershell
php artisan test tests/Feature/CaseManagementTest.php
npm run check:frontend
npm run build
php vendor/bin/pint --test app/Http/Controllers/CaseController.php tests/Feature/CaseManagementTest.php
git diff --check
git add app resources scripts tests/Feature/CaseManagementTest.php
git commit -m "feat: sesuaikan antarmuka kasus BK"
```

Expected: seluruh command exit 0; build tidak menambah dependency.

### Task 5: Ganti konsultasi menjadi layanan mandiri yang selalu selesai

**Files:**
- Modify: `app/Models/Consultation.php`
- Modify: `app/Http/Requests/StoreConsultationRequest.php`
- Modify: `app/Http/Requests/UpdateConsultationRequest.php`
- Modify: `app/Services/ConsultationService.php`
- Modify: `app/Policies/ConsultationPolicy.php`
- Modify: `app/Http/Controllers/ConsultationController.php`
- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `resources/views/pages/cases/index.blade.php`
- Modify: `resources/views/pages/consultations/create.blade.php`
- Modify: `resources/views/pages/consultations/show.blade.php`
- Create: `resources/views/pages/consultations/_detail-modal.blade.php`
- Create: `resources/views/pages/consultations/_edit-modal.blade.php`
- Modify: `resources/js/service-records.js`
- Modify: `scripts/check-frontend.mjs`
- Modify: `tests/Feature/ConsultationManagementTest.php`

**Interfaces:**
- Consumes create: identitas lokal/sementara, `session_date`, `service_field_id`, `problem`, `handling`, `result`.
- Consumes update: lima field layanan ditambah `expected_updated_at`; identitas tidak diterima saat edit.
- Produces: layanan mandiri tanpa status, nomor registrasi, case link, atau private-note split.

- [ ] **Step 1: Tulis ulang focused test agar gagal pada kontrak target**

Cover tanggal create default hari server namun editable, exact NISN match,
temporary identity, tiga narasi wajib/maksimal 10.000, edit tanggal, identitas
immutable, stale update ditolak, diff-only audit, soft delete, confirm message,
search nama, filter jenis layanan, sorting tanggal/nama/kelas/jenis, dan header
lima kolom.

Run:

```powershell
php artisan test tests/Feature/ConsultationManagementTest.php
```

Expected: FAIL karena request/model/UI masih memakai status, kasus, dan private note.

- [ ] **Step 2: Sederhanakan model, request, dan service**

Fillable target:

```php
#[Fillable([
    'student_id', 'temporary_student_id', 'service_field_id', 'session_date',
    'problem', 'handling', 'result', 'counselor_id',
])]
```

Hapus relasi `case`, `status`, `privateNote` dari consumer baru. Service create
dan update menulis satu row, memakai identity resolver existing, transaksi,
lock, timestamp expected, dan `AuditService::recordChanges()`.

`ConsultationController::update()` bertipe `RedirectResponse|JsonResponse`
dengan kontrak response yang sama seperti update kasus: redirect untuk halaman
fallback, JSON untuk modal, dan error validasi 422 dari Form Request.

- [ ] **Step 3: Implementasikan form/list/modal**

Create memakai tanggal `today()->toDateString()` hanya bila tidak ada old input.
Autocomplete memakai data murid lokal yang sudah di-scope; native `datalist`
dan hidden `student_id` boleh dipakai, tetapi backend tetap exact-match NISN.
Edit tidak merender input identitas.

Daftar hanya menampilkan Tanggal, Murid dan Kelas, Permasalahan, Jenis Layanan,
Aksi. Modal detail Waka/Guru memakai partial sama, tetapi tombol edit/arsip hanya
muncul jika policy mengizinkan.

- [ ] **Step 4: Jalankan focused gate dan commit**

```powershell
php artisan test tests/Feature/ConsultationManagementTest.php tests/Feature/AuthorizationMatrixTest.php
npm run check:frontend
npm run build
php vendor/bin/pint --test app/Models/Consultation.php app/Http/Requests/StoreConsultationRequest.php app/Http/Requests/UpdateConsultationRequest.php app/Services/ConsultationService.php app/Policies/ConsultationPolicy.php app/Http/Controllers/ConsultationController.php tests/Feature/ConsultationManagementTest.php
git diff --check
git add app resources scripts tests/Feature/ConsultationManagementTest.php tests/Feature/AuthorizationMatrixTest.php
git commit -m "feat: sederhanakan layanan konsultasi"
```

### Task 6: Buka proyeksi detail read-only untuk Waka dengan audit

**Files:**
- Modify: `app/Models/BkCase.php`
- Modify: `app/Models/Consultation.php`
- Modify: `app/Policies/CasePolicy.php`
- Modify: `app/Policies/ConsultationPolicy.php`
- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `app/Http/Controllers/ConsultationController.php`
- Modify: `app/Services/WakaCaseProjectionQuery.php`
- Modify: `app/Services/WakaMonitoringService.php`
- Modify: `app/Services/WakaDashboardService.php`
- Modify: `resources/views/pages/waka/dashboard.blade.php`
- Modify: `resources/views/pages/waka/students.blade.php`
- Modify: `resources/views/pages/waka/_handling-report.blade.php`
- Modify: `tests/Feature/WakaMonitoringTest.php`
- Modify: `tests/Feature/WakaDashboardTest.php`
- Modify: `tests/Feature/AuthorizationMatrixTest.php`

**Interfaces:**
- Produces: Waka aktif melihat seluruh row kasus/konsultasi pada tab Layanan BK dan membuka detail approved; semua mutasi 403.
- Produces: audit `case.viewed_by_waka` dan `consultation.viewed_by_waka` per pembukaan detail.
- Produces: CSV Waka tanpa `initial_info`, `initial_action`, `resolution_summary`, `problem`, `handling`, atau `result`.

- [ ] **Step 1: Tambahkan test akses/rantai kebocoran yang gagal**

Uji dua kasus dan dua konsultasi tanpa coordination: semua terlihat oleh Waka,
detail memuat field approved, route update/delete/follow-up tetap 403, Admin IT
tetap tidak bisa membaca, audit dibuat satu kali per detail, dan sentinel narasi
tidak muncul pada CSV.

Run:

```powershell
php artisan test tests/Feature/WakaMonitoringTest.php tests/Feature/WakaDashboardTest.php tests/Feature/AuthorizationMatrixTest.php
```

Expected: FAIL karena akses masih coordination-scoped dan konsultasi belum terlihat.

- [ ] **Step 2: Ubah scope/policy dan proyeksi allowlist**

`accessibleTo()` mengembalikan seluruh row within service period untuk
`koordinator_bk` dan `waka_kesiswaan`; policy `viewAny/view` Waka true,
sedangkan policy mutasi tetap hanya Guru BK owner.

`WakaCaseProjectionQuery::build()` untuk daftar/CSV hanya memilih identitas,
tanggal, status, bidang, jenis tindak lanjut, dan owner tanpa narasi. Tambahkan
`detail(User $waka, int $caseId): BkCase` yang melakukan query terpisah dengan
allowlist:

```php
[
    'cases.id', 'cases.student_id', 'cases.temporary_student_id',
    'cases.service_date', 'cases.status_id', 'cases.service_field_id',
    'cases.follow_up_type_id', 'cases.initial_info', 'cases.initial_action',
    'cases.resolution_summary', 'cases.closed_at',
]
```

Load hanya identitas, kelas historis, status, bidang, jenis tindak lanjut, dan
owner. Jangan load audit, e-Tatib payload, internal note, atau relasi retired.

Pada `ConsultationController::show()`, cabang Waka melakukan re-query dari ID
route binding dengan select `id`, identity FK, `service_field_id`,
`session_date`, `problem`, `handling`, `result`, dan `counselor_id`; cabang ini
tidak memakai model route binding yang sudah memuat seluruh kolom.

- [ ] **Step 3: Ganti seluruh tautan koordinasi**

Ubah `coordination_url` menjadi `detail_url` yang selalu tersedia untuk row yang
berwenang. Label UI menjadi `Lihat detail`; hapus teks bahwa data hanya kasus
terkoordinasi. Dashboard/monitoring tetap agregat read-only dan tidak menjadi
jalur edit.

- [ ] **Step 4: Jalankan focused gate dan commit**

```powershell
php artisan test tests/Feature/WakaMonitoringTest.php tests/Feature/WakaDashboardTest.php tests/Feature/AuthorizationMatrixTest.php
php vendor/bin/pint --test app/Models app/Policies app/Http/Controllers/CaseController.php app/Http/Controllers/ConsultationController.php app/Services/WakaCaseProjectionQuery.php app/Services/WakaMonitoringService.php app/Services/WakaDashboardService.php tests/Feature/WakaMonitoringTest.php tests/Feature/WakaDashboardTest.php tests/Feature/AuthorizationMatrixTest.php
git diff --check
git add app resources/views/pages/waka tests/Feature
git commit -m "feat: beri Waka akses layanan hanya baca"
```

### Task 7: Tambahkan autosave perangkat dan ikon Akses Cepat

**Files:**
- Create: `resources/js/form-draft.js`
- Modify: `resources/js/app-dashboard.js`
- Modify: `resources/views/layouts/app-2.blade.php`
- Modify: `resources/views/components/topbar.blade.php`
- Modify: `resources/views/pages/account/index.blade.php`
- Modify: `resources/views/pages/cases/create.blade.php`
- Modify: `resources/views/pages/cases/_edit-modal.blade.php`
- Modify: `resources/views/pages/consultations/create.blade.php`
- Modify: `resources/views/pages/consultations/_edit-modal.blade.php`
- Modify: `resources/views/pages/achievements/create.blade.php`
- Modify: `resources/views/pages/achievements/show.blade.php`
- Modify: `resources/views/pages/assignments/cases/index.blade.php`
- Modify: `resources/views/pages/assignments/classes/index.blade.php`
- Modify: `resources/views/pages/assignments/classes/manage.blade.php`
- Modify: `resources/views/pages/data-master/_academic-year-preparation.blade.php`
- Modify: `resources/views/pages/students/_departure-process.blade.php`
- Modify: `app/Services/DashboardService.php`
- Modify: `resources/views/pages/dashboard/html.blade.php`
- Modify: `resources/scss/app-dashboard.scss`
- Create: `scripts/check-form-draft.mjs`
- Modify: `scripts/check-frontend.mjs`
- Modify: `package.json`
- Modify: `tests/Feature/DashboardTest.php`

**Interfaces:**
- Produces: key `sibk:draft:v1:{userId}:{formKey}:{recordId|new}` dan value `{version:1,savedAt,values}`.
- Produces: helper pure yang menerima storage/clock agar dapat diuji dengan Node tanpa dependency.

- [ ] **Step 1: Buat test Node yang gagal**

`scripts/check-form-draft.mjs` memakai `node:assert/strict` dan fake storage untuk
menguji save/restore, isolasi user/form/record, TTL 24 jam, clear satu draft,
clear semua draft user, serta penolakan `_token`, `_method`, password, token,
credential, secret, dan file.

Ubah package script:

```json
"check:frontend": "node scripts/check-frontend.mjs && node scripts/check-form-draft.mjs"
```

Run `npm run check:frontend`.

Expected: FAIL karena modul draft belum ada.

- [ ] **Step 2: Implementasikan helper autosave minimum**

Export fungsi pure `draftKey`, `collectSafeValues`, `saveDraft`, `loadDraft`,
`removeDraft`, `removeUserDrafts`, dan `purgeExpiredDrafts`; DOM initializer
memasang debounce 2500 ms hanya pada form `[data-autosave-form]`.

Status UI harus membedakan `Draft tersimpan di perangkat`, `Draft dipulihkan`,
dan kegagalan quota/storage. Tombol Hapus Draft hanya menghapus key form aktif.
Saat submit, simpan key aktif sementara di `sessionStorage`. Layout
`app-2.blade.php` memberi `data-save-succeeded="true"` pada `<body>` hanya bila
response mempunyai flash `success`; pada response sukses helper menghapus draft
pending, sedangkan response validasi tetap mempertahankan draft. Setelah
memeriksa response, helper selalu menghapus penanda pending dari
`sessionStorage` agar flash lain tidak menghapus draft yang salah. Submit modal
sukses menghapus key langsung sebelum halaman daftar dimuat ulang. Form logout
memakai `data-clear-drafts-user` untuk menghapus seluruh namespace user sebelum
submit.

- [ ] **Step 3: Tandai hanya form bisnis yang aman**

Tambahkan data attributes hanya pada sebelas view form bisnis yang tercantum di
bagian Files. Collector tetap mengabaikan input file pada persiapan tahun
ajaran/roster. Jangan tandai search/filter, login, password, akun, pengelolaan
user, integration settings, pratinjau rekonsiliasi Dapodik, archive
confirmation, atau dropdown inline.

- [ ] **Step 4: Tambahkan ikon Akses Cepat tanpa library**

`DashboardService::quickActions()` menambah key `icon` dan `tone` dari allowlist
tetap. Blade memilih SVG inline existing melalui `@switch`; SCSS menggunakan
token warna aplikasi. Tidak ada warna inline atau package ikon.

- [ ] **Step 5: Jalankan focused gate dan commit**

```powershell
npm run check:frontend
npm run build
php artisan test tests/Feature/DashboardTest.php
php vendor/bin/pint --test app/Services/DashboardService.php tests/Feature/DashboardTest.php
git diff --check
git add resources scripts package.json app/Services/DashboardService.php tests/Feature/DashboardTest.php
git commit -m "feat: tambah autosave lokal dan ikon dashboard"
```

### Task 8: Selaraskan laporan, dashboard, profil murid, dan dummy data

**Files:**
- Modify: `app/Services/DashboardService.php`
- Modify: `app/Services/CounselingReportQuery.php`
- Modify: `app/Services/OperationalReportRecapService.php`
- Modify: `app/Services/LegacyReportAdapter.php`
- Modify: `app/Services/ViolationReportQuery.php`
- Modify: `app/Http/Controllers/StudentController.php`
- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `app/Services/CaseService.php`
- Modify: `app/Models/Student.php`
- Modify: `app/Models/Achievement.php`
- Modify: `resources/views/pages/dashboard/html.blade.php`
- Modify: `resources/views/pages/reports/_desktop-table.blade.php`
- Modify: `resources/views/pages/reports/_mobile-cards.blade.php`
- Modify: `resources/views/pages/reports/index.blade.php`
- Modify: `resources/views/pages/students/show.blade.php`
- Modify: `resources/views/pages/students/index.blade.php`
- Modify: `database/seeders/DummyCaseAndServiceSeeder.php`
- Modify: `routes/web.php`
- Modify: `routes/bk-services.php`
- Modify: `scripts/check-frontend.mjs`
- Delete: `app/Models/FollowUp.php`
- Delete: `app/Models/CaseCoordination.php`
- Delete: `app/Models/ConsultationPrivateNote.php`
- Delete: `app/Services/FollowUpService.php`
- Delete: `app/Http/Controllers/FollowUpController.php`
- Delete: `app/Http/Controllers/CaseCoordinationController.php`
- Delete: `app/Http/Requests/ResolveCaseRequest.php`
- Delete: `app/Http/Requests/SaveFollowUpRequest.php`
- Delete: `app/Http/Requests/StoreCaseCoordinationRequest.php`
- Delete: `app/Http/Requests/UpdateCaseCoordinationRequest.php`
- Delete: `resources/views/pages/cases/follow-up.blade.php`
- Delete: `resources/views/pages/cases/resolve.blade.php`
- Delete: `resources/views/pages/waka/case-detail.blade.php`
- Modify: `tests/Feature/DashboardTest.php`
- Modify: `tests/Feature/OperationalReportRecapTest.php`
- Modify: `tests/Feature/ReportManagementTest.php`
- Modify: `tests/Feature/StudentProfileTest.php`
- Modify: `tests/Feature/DapodikSyncTest.php`
- Modify: `tests/Feature/DelayedDapodikPreparationTest.php`
- Modify: `tests/Feature/StudentDepartureTest.php`
- Modify: `tests/Feature/AchievementManagementTest.php`
- Modify: `tests/Feature/WakaReportPageTest.php`
- Modify: `tests/Feature/WakaStudentDepartureTest.php`

**Interfaces:**
- Produces: satu kasus tetap dihitung satu layanan; `follow_up_type_id` hanya klasifikasi terkini, bukan event.
- Produces: metric `follow_up_case_count` = jumlah kasus berstatus Tindak Lanjut.
- Produces: tipe report legacy `status-tindak-lanjut` tetap tersedia tetapi row-nya berasal dari kasus terkini, bukan tabel `follow_ups`.

- [ ] **Step 1: Ubah test agregat agar gagal pada semantik baru**

Pastikan satu kasus Tindak Lanjut tidak menambah `service_count`, report tindak
lanjut berisi satu row per kasus, konsultasi selalu dihitung sebagai layanan
selesai, dan tidak ada narasi layanan pada export.

Run:

```powershell
php artisan test tests/Feature/DashboardTest.php tests/Feature/OperationalReportRecapTest.php tests/Feature/ReportManagementTest.php tests/Feature/StudentProfileTest.php
```

Expected: FAIL karena query masih union `follow_ups` dan memakai status konsultasi.

- [ ] **Step 2: Ubah query pada akar consumer**

`OperationalReportRecapService` menyatukan hanya cases + consultations.
`follow_up_case_count` dihitung dari cases dengan status code
`membutuhkan_tindak_lanjut`; current type boleh muncul pada report khusus tetapi
tidak menjadi layanan tambahan.

`CounselingReportQuery::followUps()` dan export-nya diganti query `BkCase` dengan
`follow_up_type_id IS NOT NULL`. `LegacyReportAdapter` mengganti judul menjadi
`Kasus Tindak Lanjut` dan menghapus filter `follow_up_status`.

- [ ] **Step 3: Bersihkan consumer non-laporan**

Dashboard Guru BK menampilkan daftar kasus berstatus Tindak Lanjut tanpa tanggal
jadwal/terlaksana. Dashboard Waka memakai kasus terbaru tanpa coordination.
Profil murid menghapus count/jadwal follow-up dan kolom kasus/status konsultasi.
Update test integrasi Dapodik/departure/prestasi agar fixture memakai skema target.

Pada `Student`, `Achievement`, dan `ViolationReportQuery`, hapus predicate
`cases.coordinations`. Scope Waka yang masih diperlukan hanya boleh mengikuti
keberadaan kasus non-deleted yang kini memang dapat dibaca Waka; jangan expose
payload mentah e-Tatib atau narasi melalui report.

- [ ] **Step 4: Ubah dummy seeder menjadi idempotent pada skema target**

Seeder membuat kasus dengan optional `follow_up_type_id`; konsultasi langsung
memiliki `problem`, `handling`, `result`. Hapus pembuatan `FollowUp`,
`CaseCoordination`, dan `ConsultationPrivateNote`. Jangan reset tabel.

- [ ] **Step 5: Hapus runtime lama sebelum schema drop**

Hapus class, request, controller, view, dan route retired yang tercantum pada
Files. Dari `CaseService`/`CaseController`, hapus method resolve terpisah dan
koordinasi; penyelesaian hanya melalui `cases.update`. Hapus juga route preview
resolve dan daftar file retired pada checker frontend. Tabel/kolom lama masih
ada sampai Task 9, tetapi tidak boleh lagi mempunyai consumer runtime.

- [ ] **Step 6: Jalankan focused gate checkpoint consumer**

```powershell
php artisan test tests/Feature/CaseManagementTest.php tests/Feature/ConsultationManagementTest.php tests/Feature/DashboardTest.php tests/Feature/OperationalReportRecapTest.php tests/Feature/ReportManagementTest.php tests/Feature/StudentProfileTest.php tests/Feature/WakaMonitoringTest.php tests/Feature/WakaDashboardTest.php tests/Feature/WakaReportPageTest.php tests/Feature/DapodikSyncTest.php tests/Feature/DelayedDapodikPreparationTest.php tests/Feature/StudentDepartureTest.php tests/Feature/AchievementManagementTest.php
php vendor/bin/pint --test app database/seeders tests/Feature
npm run check:frontend
npm run build
git diff --check
```

Expected: seluruh command exit 0 sebelum tabel/kolom lama di-drop.

- [ ] **Step 7: Commit**

```powershell
git add app resources database/seeders routes scripts tests/Feature
git commit -m "refactor: selaraskan consumer layanan BK"
```

---

## Checkpoint C — Pembersihan Destruktif dan Gate Akhir

### Task 9: Hapus skema retired setelah dependency scan

**Files:**
- Create: `database/migrations/2026_09_17_000200_remove_revisi_sibk_3_2_legacy_schema.php`
- Modify: `tests/Feature/FoundationDataTest.php`
- Modify: `tests/Feature/SharedDevelopmentBaselineTest.php`

**Interfaces:**
- Produces: skema final tanpa tabel/kolom retired; route follow-up form, resolve form, dan coordination tidak lagi terdaftar.
- Preserves: audit lama karena `audit_logs` memakai morph type/id tanpa FK ke tabel domain.

- [ ] **Step 1: Jalankan scan dependency sebelum drop**

```powershell
rg -n "FollowUp|CaseCoordination|ConsultationPrivateNote|followUps|coordinations|privateNote|cases\.follow-ups|cases\.coordinations|cases\.resolve" app routes resources database/seeders
rg -n "waka_summary|final_result|continued_plan|registration_number|case_id|status_id|topic|referral_source|starts_at|ends_at|follow_up_date|general_summary" app/Models/Consultation.php app/Services/ConsultationService.php app/Http/Controllers/ConsultationController.php resources/views/pages/consultations
```

Expected: tidak ada consumer runtime; match tersisa harus dihapus sebelum lanjut.
Jangan membuat migration destruktif jika command pertama masih menemukan match.

- [ ] **Step 2: Tambahkan test skema final yang gagal**

Assert tiga tabel retired tidak ada; cases tidak memiliki `waka_summary`,
`final_result`, `continued_plan`; consultations tidak memiliki sembilan kolom
retired; FK/index kolom baru tetap ada.

Run:

```powershell
php artisan test tests/Feature/FoundationDataTest.php tests/Feature/SharedDevelopmentBaselineTest.php
```

Expected: FAIL karena skema lama masih ada.

- [ ] **Step 3: Implementasikan migration drop dengan urutan FK aman**

Urutan `up()`:

1. drop `consultation_private_notes`;
2. drop `follow_ups`;
3. drop `case_coordinations`;
4. drop FK/index kolom konsultasi retired lalu drop kolomnya;
5. drop kolom kasus retired.

`down()` hanya memulihkan bentuk struktural minimum yang sama dengan migration
awal; jangan mencoba merekonstruksi data yang memang telah dihapus. Migration
harus lolos fresh SQLite test dan database target Laravel.

- [ ] **Step 4: Verifikasi migration fresh dan upgrade**

```powershell
php artisan test tests/Feature/FoundationDataTest.php tests/Feature/SharedDevelopmentBaselineTest.php tests/Feature/CaseManagementTest.php tests/Feature/ConsultationManagementTest.php
$migrationDb = Join-Path (Resolve-Path .) 'storage/framework/revisi-sibk-migration.sqlite'
New-Item -ItemType File -Force -Path $migrationDb | Out-Null
try {
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = $migrationDb
    php artisan migrate:fresh --force
} finally {
    Remove-Item -LiteralPath $migrationDb -Force
}
php vendor/bin/pint --test database/migrations app routes tests/Feature/FoundationDataTest.php tests/Feature/SharedDevelopmentBaselineTest.php
git diff --check
```

Expected: seluruh command exit 0. Target `migrate:fresh` adalah file SQLite
eksplisit di dalam `storage/framework`, lalu file tersebut dihapus; database
shared/production tidak disentuh.

- [ ] **Step 5: Ulangi scan dan commit**

```powershell
rg -n "FollowUp|CaseCoordination|ConsultationPrivateNote|followUps|coordinations|privateNote|cases\.follow-ups|cases\.coordinations|cases\.resolve" app routes resources database/seeders
git add -A
git commit -m "refactor: hapus skema layanan BK lama"
```

Expected: `rg` exit 1 karena tidak ada match; commit hanya berisi cleanup yang
sudah didahului perpindahan consumer.

### Task 10: Verifikasi end-to-end, review kontrak, dan perbarui handoff

**Files:**
- Modify: `docs/current-work.md`
- Modify: `docs/development-log.md`
- Review: seluruh diff terhadap spec dan requirement aktif

**Interfaces:**
- Produces: bukti gate, checkpoint, dan status branch yang dapat dilanjutkan reviewer/PR.

- [ ] **Step 1: Jalankan focused security/privacy gate**

```powershell
php artisan test tests/Feature/AuthorizationMatrixTest.php tests/Feature/CaseManagementTest.php tests/Feature/ConsultationManagementTest.php tests/Feature/WakaMonitoringTest.php tests/Feature/WakaDashboardTest.php tests/Feature/ReportManagementTest.php tests/Feature/OperationalReportRecapTest.php
```

Expected: PASS; Waka baca penuh hanya pada field approved dan semua mutasi tetap ditolak.

- [ ] **Step 2: Jalankan seluruh gate repository**

```powershell
composer test
php vendor/bin/pint --test
npm run check:frontend
npm run build
composer validate --strict
git diff --check
```

Expected: semua command exit 0. Catat jumlah test/assertion aktual, bukan angka
dari baseline lama.

- [ ] **Step 3: Self-review cakupan dan placeholder**

```powershell
rg -n "TODO|FIXME|placeholder|nanti diisi|sementara implementasi" app routes resources database tests docs/requirements docs/api-contract.md
rg -n "follow_ups|case_coordinations|consultation_private_notes" app routes resources database/seeders tests
rg -n "registration_number|case_id|status_id|topic|general_summary|follow_up_date" app/Models/Consultation.php app/Services/ConsultationService.php app/Http/Controllers/ConsultationController.php resources/views/pages/consultations tests/Feature/ConsultationManagementTest.php
```

Expected: tidak ada placeholder baru atau consumer skema retired. Match yang sah
di migration historis tidak masuk scope command.

Checklist review manual:

- seluruh butir spec bagian 5–15 mempunyai test atau gate;
- signature request/service/controller konsisten;
- tidak ada mass assignment field retired;
- semua sort/direction berasal dari allowlist;
- audit Waka dan audit diff-only tidak menyimpan payload provider/credential;
- draft tidak aktif pada form akun/integrasi/login/password/file/destruktif;
- narasi kasus/konsultasi tidak masuk CSV;
- tidak ada dependency baru.

- [ ] **Step 4: Perbarui handoff dan log**

`docs/current-work.md` mencatat commit checkpoint, hasil gate, risiko residual
Waka/localStorage, dan langkah PR ke `cobasidebar`. Tambahkan ringkasan singkat ke
`docs/development-log.md`; jangan salin output test panjang.

- [ ] **Step 5: Commit dokumentasi verifikasi**

```powershell
git add docs/current-work.md docs/development-log.md
git commit -m "docs: catat verifikasi revisi SIBK 3.2"
git status --short
```

Expected: working tree bersih. Setelah itu gunakan
`superpowers:requesting-code-review`; jangan merge atau menghapus branch sebelum
review, PR, dan status `MERGED` terverifikasi.
