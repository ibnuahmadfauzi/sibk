# Revisi SIBK 3.2 untuk Layanan Guru BK Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

> **Parallel execution override:** User menyetujui eksekusi paralel pada 17
> September 2026. Gunakan `superpowers:dispatching-parallel-agents` dan
> `superpowers:using-git-worktrees`. Batas sequential pada
> `subagent-driven-development` diganti khusus untuk wave yang file ownership-nya
> terpisah di plan ini; review dan integration gate tetap dijalankan berurutan
> oleh koordinator.

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
- Maksimal tiga implementer berjalan bersamaan karena slot keempat dipakai koordinator. Setiap implementer bekerja pada branch dan worktree sendiri.
- Worker tidak boleh mengedit file di luar ownership lane. Perubahan file shared hanya dilakukan koordinator pada integration gate.
- Worker tidak merge, rebase, push, atau membuka PR. Worker mengembalikan commit, daftar file, hasil focused gate, dan concern kepada koordinator.
- Koordinator mengintegrasikan commit lane dengan cherry-pick, menjalankan review/gate gabungan, lalu membuat wave berikutnya dari HEAD integrasi yang sudah hijau.

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

## Alur Eksekusi Paralel

Branch `revisi-sibk-3-2` adalah branch integrasi dan hanya disentuh koordinator.
Implementer selalu mulai dari commit integrasi yang sama pada awal wave.

```text
SEQUENTIAL FOUNDATION
  Task 1 Kontrak -> Task 2 Migration aditif/status
                          |
          +---------------+---------------+
          |               |               |
WAVE 1    | Lane A        | Lane B        | Lane C
          | Task 3-4      | Task 5        | Task 7
          | Kasus         | Konsultasi    | Frontend shared/autosave
          +---------------+---------------+
                          |
                  INTEGRATION GATE 1
                          |
          +---------------+---------------+
          |               |               |
WAVE 2    | Lane D        | Lane E        | Lane F
          | Task 6        | Task 8A       | Task 8B
          | Waka          | Laporan       | Consumer + dummy
          +---------------+---------------+
                          |
                  INTEGRATION GATE 2
                          |
SEQUENTIAL CLEANUP
  Task 8C runtime retired -> Task 9 drop schema -> Task 10 full gate/handoff
```

## Batas Pengiriman per Checkpoint

Checkpoint A/B/C di bagian task tetap menjelaskan fase teknis. Untuk pembagian
kerja, pause, dan PR, pekerjaan dikirim sebagai tiga checkpoint berikut:

| Checkpoint | Isi | Cara kerja | Batas selesai |
|---|---|---|---|
| 7A — Penyelarasan fitur | Task 1–8B sampai Integration Gate 2 | Lane D, E, dan F paralel; review per lane; integrasi oleh koordinator | Full gate hijau dan satu PR `revisi-sibk-3-2` ke `cobasidebar` |
| 7B — Cleanup runtime | Task 8C | Sequential pada branch baru dari `cobasidebar` terbaru | Runtime retired hilang, full gate hijau, dan satu PR terpisah ke `cobasidebar` |
| 7C — Cleanup skema dan final | Task 9–10 | Sequential pada branch baru dari `cobasidebar` terbaru | Migration forward-only, full gate akhir, handoff, dan satu PR terpisah ke `cobasidebar` |

Aturan checkpoint:

- `main` tidak menjadi target ketiga checkpoint. Rilis produksi dilakukan lewat
  persetujuan dan PR rilis terpisah setelah 7C sudah terintegrasi.
- Checkpoint berikutnya baru dibuat dari `cobasidebar` setelah PR checkpoint
  sebelumnya berstatus `MERGED`; branch sumber remote lalu dihapus sesuai
  aturan repository.
- Setiap checkpoint harus dapat dipause dengan branch bersih, bukti gate, dan
  handoff yang cukup untuk sesi berikutnya.
- Untuk hemat penggunaan, worker hanya membaca task/spec yang terkait lane,
  menjalankan focused test di lane, lalu koordinator menjalankan full gate satu
  kali setelah hasil terintegrasi.
- Maksimal tiga implementer paralel dan satu koordinator. Cleanup 7B/7C tidak
  diparalelkan karena menyentuh runtime/skema bersama dan saling bergantung.

### Titik lanjut Checkpoint 7A

Wave 1 dan Integration Gate 1 sudah terintegrasi lokal pada commit `b023959`.
Tiga worktree Wave 2 sudah tersedia dari commit yang sama dan saat pause hanya
berisi perubahan test-first yang belum di-commit:

```text
.worktrees/revisi-sibk-3-2-wave2-waka
.worktrees/revisi-sibk-3-2-wave2-laporan
.worktrees/revisi-sibk-3-2-wave2-consumer
```

Lanjutkan ketiganya, jangan membuat ulang worktree atau mengulang Wave 1.
Commit lane baru boleh di-cherry-pick setelah focused gate dan review lane
bersih. Setelah Gate 2 dan full gate 7A lulus, perbarui `docs/current-work.md`
serta `docs/development-log.md`, buka PR ke `cobasidebar`, verifikasi status
`MERGED`, lalu pause sebelum membuat branch 7B.

### Matriks lane dan ownership

| Wave | Lane/branch | Scope | File shared yang dilarang disentuh |
|---|---|---|---|
| 1 | A — `revisi-sibk-3-2-wave1-kasus` | Domain, endpoint, test, dan Blade kasus | Infrastruktur JS/checker autosave milik Lane C; tab konsultasi shared milik Gate 1 |
| 1 | B — `revisi-sibk-3-2-wave1-konsultasi` | Domain, endpoint, test, dan Blade konsultasi selain daftar gabungan | `CaseController` dan `pages/cases/index.blade.php` milik Gate 1 |
| 1 | C — `revisi-sibk-3-2-wave1-frontend` | `service-records.js`, `form-draft.js`, import global, autosave form non-kasus/konsultasi, ikon dashboard | Model/controller/service/Blade kasus dan konsultasi milik Lane A/B |
| 2 | D — `revisi-sibk-3-2-wave2-waka` | Policy/scope/proyeksi/detail/audit/UI Waka | Query laporan operasional milik Lane E; dashboard umum dan seeder milik Lane F |
| 2 | E — `revisi-sibk-3-2-wave2-laporan` | Query serta UI laporan layanan | Policy/controller Waka milik Lane D; profil/dashboard/seeder milik Lane F |
| 2 | F — `revisi-sibk-3-2-wave2-consumer` | Dashboard umum, profil murid, scope turunan, dummy seeder, fixture integrasi | File Waka Lane D dan query laporan layanan Lane E |

### Protokol worktree dan integrasi

Pada awal setiap wave, koordinator menggunakan
`superpowers:using-git-worktrees`, memastikan directory worktree terpilih sudah
di-ignore, lalu membuat tiga branch dari HEAD integrasi yang sama. Setiap worker
menerima path worktree absolut, task/lane tunggal, spec, global constraints,
dan larangan subagent/merge/push.

Repository ini sudah mempunyai `.worktrees/` yang di-ignore. Setelah Task 2
lulus dan branch integrasi bersih, buat Wave 1:

```powershell
git check-ignore -q .worktrees
git worktree add .worktrees/revisi-sibk-3-2-wave1-kasus -b revisi-sibk-3-2-wave1-kasus HEAD
git worktree add .worktrees/revisi-sibk-3-2-wave1-konsultasi -b revisi-sibk-3-2-wave1-konsultasi HEAD
git worktree add .worktrees/revisi-sibk-3-2-wave1-frontend -b revisi-sibk-3-2-wave1-frontend HEAD
```

Setelah Integration Gate 1 lulus, buat Wave 2 dari HEAD baru:

```powershell
git worktree add .worktrees/revisi-sibk-3-2-wave2-waka -b revisi-sibk-3-2-wave2-waka HEAD
git worktree add .worktrees/revisi-sibk-3-2-wave2-laporan -b revisi-sibk-3-2-wave2-laporan HEAD
git worktree add .worktrees/revisi-sibk-3-2-wave2-consumer -b revisi-sibk-3-2-wave2-consumer HEAD
```

Di setiap worktree, jalankan `composer install --no-interaction` dan `npm ci`
bila dependency lokal belum tersedia, lalu baseline focused test lane sebelum
edit. Jangan menjalankan migration terhadap database shared; test memakai
konfigurasi SQLite testing masing-masing worktree.

Untuk mengintegrasikan satu wave, jalankan dari branch `revisi-sibk-3-2`:

```powershell
$lanes = @(
    'revisi-sibk-3-2-wave1-kasus',
    'revisi-sibk-3-2-wave1-konsultasi',
    'revisi-sibk-3-2-wave1-frontend'
)

foreach ($lane in $lanes) {
    $baseCommit = git merge-base HEAD $lane
    $laneCommits = @(git rev-list --reverse "$baseCommit..$lane")
    if ($laneCommits.Count -gt 0) {
        git cherry-pick $laneCommits
    }
}
```

Untuk Wave 2, ganti array dengan branch `wave2-waka`, `wave2-laporan`, dan
`wave2-consumer` dalam urutan tersebut. Jika cherry-pick konflik, hentikan
integrasi lane itu, selesaikan konflik berdasarkan ownership/spec, jalankan
focused test file terkait, lalu `git cherry-pick --continue`. Jangan meminta
worker lain mengedit worktree integrasi.

Setiap lane selesai hanya jika laporannya memuat:

```text
Status: DONE atau DONE_WITH_CONCERNS
Commits: hash dan subject berurutan
Files: output git diff --name-only terhadap base wave
Tests: command, exit code, jumlah test/assertion bila tersedia
Concerns: none atau daftar risiko konkret
```

Sesudah tiga implementer selesai, dispatch tiga reviewer read-only secara
paralel—satu reviewer untuk satu lane. Reviewer menerima spec, task lane,
commit base/head, diff lane, dan laporan test. Reviewer memeriksa kepatuhan spec
serta kualitas kode, tidak mengedit file. Temuan dikirim kembali ke implementer
lane yang sama; lane baru boleh diintegrasikan setelah re-review bersih atau
koordinator mencatat ruling eksplisit pada ledger. Dengan demikian implementasi
paralel tidak menghapus review per task.

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

### Task 1 — Sequential Foundation: Jadikan revisi 3.2 source of truth aktif

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

### Task 2 — Sequential Foundation: Tambahkan skema baru dan reference aktif tanpa drop

**Files:**
- Create: `database/migrations/2026_09_17_000100_add_revisi_sibk_3_2_foundation.php`
- Modify: `app/Services/AuditService.php`
- Modify: `app/Support/ServiceRecordStatus.php`
- Modify: `database/seeders/ReferenceSeeder.php`
- Modify: `tests/Feature/FoundationDataTest.php`
- Modify: `tests/Feature/ServiceRecordStatusMigrationTest.php`
- Modify: `tests/Feature/SharedDevelopmentBaselineTest.php`

**Interfaces:**
- Produces: `cases.follow_up_type_id` nullable FK dan index.
- Produces: `consultations.problem`, `handling`, `result` nullable sementara agar migration aman untuk row dummy existing; Form Request tetap mewajibkan nilai pada create/update.
- Produces: status aktif `sedang_diproses`, `membutuhkan_tindak_lanjut`, `selesai` dan empat reference tindak lanjut yang disetujui.
- Produces: `AuditService::recordChanges(...)` yang dipakai Lane A dan B tanpa saling bergantung.

- [ ] **Step 1: Tulis migration test yang gagal**

Assert kolom baru tersedia, status `baru` tidak aktif, label status tepat,
empat `follow_up_type` berikut yang aktif, serta `recordChanges()` menyimpan
hanya key yang berbeda dan tidak membuat log bila before/after identik:

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

- [ ] **Step 4: Implementasikan audit diff-only shared**

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
yang sama. Lane A/B wajib memakai method ini untuk update resmi.

- [ ] **Step 5: Jalankan focused gate**

```powershell
php artisan test tests/Feature/FoundationDataTest.php tests/Feature/ServiceRecordStatusMigrationTest.php tests/Feature/SharedDevelopmentBaselineTest.php
php vendor/bin/pint --test app/Support app/Services/AuditService.php database/migrations database/seeders tests/Feature/FoundationDataTest.php tests/Feature/ServiceRecordStatusMigrationTest.php tests/Feature/SharedDevelopmentBaselineTest.php
git diff --check
```

Expected: seluruh command exit 0; tabel lama masih ada pada checkpoint ini.

- [ ] **Step 6: Commit**

```powershell
git add app/Support app/Services/AuditService.php database/migrations database/seeders tests/Feature
git commit -m "feat: tambah fondasi data revisi SIBK 3.2"
```

### Task 3 — Wave 1 Lane A: Terapkan kontrak domain kasus baru

**Files:**
- Modify: `app/Models/BkCase.php`
- Modify: `app/Http/Requests/StoreCaseRequest.php`
- Modify: `app/Http/Requests/UpdateCaseRequest.php`
- Create: `app/Http/Requests/UpdateCaseFollowUpRequest.php`
- Modify: `app/Services/CaseService.php`
- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `app/Policies/CasePolicy.php`
- Modify: `routes/bk-services.php`
- Modify: `tests/Feature/CaseManagementTest.php`

**Interfaces:**
- Consumes: source kasus, identitas manual/e-Tatib lokal, tiga narasi, aksi `save|complete`, `expected_updated_at`, dan `AuditService::recordChanges(...)` dari Task 2.
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

- [ ] **Step 2: Sederhanakan model/request kasus**

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

- [ ] **Step 3: Ubah `CaseService` sebagai satu-satunya pemilik transisi**

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

- [ ] **Step 4: Hubungkan controller dan route tipis**

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

- [ ] **Step 5: Jalankan focused gate**

```powershell
php artisan test tests/Feature/CaseManagementTest.php tests/Feature/AuthorizationMatrixTest.php
php vendor/bin/pint --test app/Models/BkCase.php app/Http/Requests app/Services/CaseService.php app/Http/Controllers/CaseController.php app/Policies/CasePolicy.php routes/bk-services.php tests/Feature/CaseManagementTest.php
git diff --check
```

Expected: seluruh test target kasus dan matriks role PASS.

- [ ] **Step 6: Commit**

```powershell
git add app routes tests/Feature/CaseManagementTest.php
git commit -m "feat: sederhanakan lifecycle kasus BK"
```

---

## Checkpoint B — Peralihan Consumer dan UI

### Task 4 — Wave 1 Lane A: Bangun daftar, sorting, modal, dan inline tindak lanjut kasus

**Files:**
- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `resources/views/pages/cases/index.blade.php`
- Modify: `resources/views/pages/cases/create.blade.php`
- Modify: `resources/views/pages/cases/show.blade.php`
- Modify: `resources/views/pages/cases/edit.blade.php`
- Create: `resources/views/pages/cases/_detail-modal.blade.php`
- Create: `resources/views/pages/cases/_edit-modal.blade.php`
- Modify: `tests/Feature/CaseManagementTest.php`

**Interfaces:**
- Produces: tabel final delapan kolom, search nama, filter status, sorting allowlist, modal shared, serta data contract dropdown inline.
- Consumes: endpoint kasus Task 3 dan atribut yang akan dipasang ke handler Bootstrap/rollback milik Lane C.

- [ ] **Step 1: Tambahkan test daftar/UI yang gagal**

Test exact header, search nama saja, filter status, sort `nama|kelas|tanggal|
sumber|bidang|status`, direction `asc|desc`, fallback sort invalid, tie-breaker
`cases.id`, serta tidak adanya aksi mutasi bagi Waka.

Tambahkan assertion response pada `CaseManagementTest` untuk satu modal shell,
`data-modal-url`, `data-follow-up-url`, indikator saving, dan tidak adanya link
route follow-up/resolve lama. Assert juga `aria-labelledby`,
`modal-dialog-scrollable`, serta tombol tutup yang berlabel. Checker frontend
dan perilaku focus/JavaScript menjadi ownership Lane C dan Gate 1.

Run:

```powershell
php artisan test tests/Feature/CaseManagementTest.php
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

- [ ] **Step 4: Jalankan focused gate dan commit**

```powershell
php artisan test tests/Feature/CaseManagementTest.php
php vendor/bin/pint --test app/Http/Controllers/CaseController.php tests/Feature/CaseManagementTest.php
git diff --check
git add app resources/views/pages/cases tests/Feature/CaseManagementTest.php
git commit -m "feat: sesuaikan antarmuka kasus BK"
```

Expected: seluruh command exit 0. Lane A melaporkan dua commit Task 3-4 dan
tidak mengubah file ownership Lane B/C.

### Task 5 — Wave 1 Lane B: Ganti konsultasi menjadi layanan mandiri yang selalu selesai

**Files:**
- Modify: `app/Models/Consultation.php`
- Modify: `app/Http/Requests/StoreConsultationRequest.php`
- Modify: `app/Http/Requests/UpdateConsultationRequest.php`
- Modify: `app/Services/ConsultationService.php`
- Modify: `app/Policies/ConsultationPolicy.php`
- Modify: `app/Http/Controllers/ConsultationController.php`
- Modify: `resources/views/pages/consultations/create.blade.php`
- Modify: `resources/views/pages/consultations/show.blade.php`
- Create: `resources/views/pages/consultations/_detail-modal.blade.php`
- Create: `resources/views/pages/consultations/_edit-modal.blade.php`
- Modify: `tests/Feature/ConsultationManagementTest.php`

**Interfaces:**
- Consumes create: identitas lokal/sementara, `session_date`, `service_field_id`, `problem`, `handling`, `result`.
- Consumes update: lima field layanan ditambah `expected_updated_at`; identitas tidak diterima saat edit.
- Consumes: `AuditService::recordChanges(...)` dari Task 2.
- Produces: layanan mandiri tanpa status, nomor registrasi, case link, atau private-note split.
- Produces: model/query fields yang dipakai koordinator Gate 1 untuk memasang daftar konsultasi ke tab Layanan BK shared.

- [ ] **Step 1: Tulis ulang focused test agar gagal pada kontrak target**

Cover tanggal create default hari server namun editable, exact NISN match,
temporary identity, tiga narasi wajib/maksimal 10.000, edit tanggal, identitas
immutable, stale update ditolak, diff-only audit, soft delete, confirm message,
serta detail/edit modal. Test daftar gabungan, filter, sorting, dan header lima
kolom ditambahkan koordinator pada Gate 1 karena file daftar dimiliki Lane A.

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

- [ ] **Step 3: Implementasikan form dan modal**

Create memakai tanggal `today()->toDateString()` hanya bila tidak ada old input.
Autocomplete memakai data murid lokal yang sudah di-scope; native `datalist`
dan hidden `student_id` boleh dipakai, tetapi backend tetap exact-match NISN.
Edit tidak merender input identitas.

Modal detail Guru memakai partial shared dan tombol edit/arsip hanya muncul jika
policy mengizinkan. Jangan mengedit `CaseController` atau
`pages/cases/index.blade.php`; daftar gabungan dipasang pada Gate 1.

- [ ] **Step 4: Jalankan focused gate dan commit**

```powershell
php artisan test tests/Feature/ConsultationManagementTest.php tests/Feature/AuthorizationMatrixTest.php
php vendor/bin/pint --test app/Models/Consultation.php app/Http/Requests/StoreConsultationRequest.php app/Http/Requests/UpdateConsultationRequest.php app/Services/ConsultationService.php app/Policies/ConsultationPolicy.php app/Http/Controllers/ConsultationController.php tests/Feature/ConsultationManagementTest.php
git diff --check
git add app resources/views/pages/consultations tests/Feature/ConsultationManagementTest.php
git commit -m "feat: sederhanakan layanan konsultasi"
```

### Task 6 — Wave 2 Lane D: Buka proyeksi detail read-only untuk Waka dengan audit

> **Execution gate:** Jangan dispatch Task 6 berdasarkan urutan tampil dokumen.
> Task ini baru boleh dimulai setelah Lane A/B/C diintegrasikan dan Integration
> Gate 1 lulus.

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

### Task 7 — Wave 1 Lane C: Tambahkan frontend shared, autosave perangkat, dan ikon Akses Cepat

**Files:**
- Create: `resources/js/service-records.js`
- Create: `resources/js/form-draft.js`
- Modify: `resources/js/app-dashboard.js`
- Modify: `resources/views/layouts/app-2.blade.php`
- Modify: `resources/views/components/topbar.blade.php`
- Modify: `resources/views/pages/account/index.blade.php`
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
- Consumes: kontrak `data-modal-url`, `data-follow-up-url`, response JSON, dan error 422 yang ditetapkan Task 3-5; Lane C tidak mengubah Blade pemilik kontrak.
- Produces: handler modal/follow-up generik yang aktif setelah commit Lane A/B diintegrasikan.
- Produces: key `sibk:draft:v1:{userId}:{formKey}:{recordId|new}` dan value `{version:1,savedAt,values}`.
- Produces: helper pure yang menerima storage/clock agar dapat diuji dengan Node tanpa dependency.

- [ ] **Step 1: Buat test frontend shared yang gagal**

`scripts/check-form-draft.mjs` memakai `node:assert/strict` dan fake storage untuk
menguji save/restore, isolasi user/form/record, TTL 24 jam, clear satu draft,
clear semua draft user, serta penolakan `_token`, `_method`, password, token,
credential, secret, dan file.

Di `scripts/check-frontend.mjs`, assert `app-dashboard.js` mengimpor
`service-records.js` dan `form-draft.js`, sedangkan `service-records.js`
menggunakan Bootstrap `Modal`, `AbortController`, header
`Accept: application/json`, dan rollback value pada response gagal. Jangan
assert markup Lane A/B sebelum Gate 1.

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

- [ ] **Step 3: Implementasikan modal dan inline update generik**

`service-records.js` menangani fetch partial ke satu Bootstrap modal, submit
form dengan CSRF, render error 422 dekat field, dan restore focus ke trigger.
Dropdown tindak lanjut di-disable selama PATCH; sukses memperbarui label status
dan timestamp, sedangkan gagal mengembalikan value/status/timestamp lama.
Gunakan `AbortController` saat modal ditutup dan jangan menambah dependency.

- [ ] **Step 4: Tandai hanya form bisnis non-kasus/konsultasi yang aman**

Tambahkan data attributes hanya pada tujuh view non-kasus/konsultasi yang
tercantum di bagian Files. Collector tetap mengabaikan input file pada
persiapan tahun ajaran/roster. Form kasus/konsultasi dipasang koordinator pada
Gate 1 setelah Lane A/B masuk. Jangan tandai search/filter, login, password,
akun, pengelolaan user, integration settings, pratinjau rekonsiliasi Dapodik,
archive confirmation, atau dropdown inline.

- [ ] **Step 5: Tambahkan ikon Akses Cepat tanpa library**

`DashboardService::quickActions()` menambah key `icon` dan `tone` dari allowlist
tetap. Blade memilih SVG inline existing melalui `@switch`; SCSS menggunakan
token warna aplikasi. Tidak ada warna inline atau package ikon.

- [ ] **Step 6: Jalankan focused gate dan commit**

```powershell
npm run check:frontend
npm run build
php artisan test tests/Feature/DashboardTest.php
php vendor/bin/pint --test app/Services/DashboardService.php tests/Feature/DashboardTest.php
git diff --check
git add resources scripts package.json app/Services/DashboardService.php tests/Feature/DashboardTest.php
git commit -m "feat: tambah autosave lokal dan ikon dashboard"
```

### Integration Gate 1 — Gabungkan Lane A, B, dan C

**Files:**
- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `resources/views/pages/cases/index.blade.php`
- Modify: `resources/views/pages/cases/create.blade.php`
- Modify: `resources/views/pages/cases/_edit-modal.blade.php`
- Modify: `resources/views/pages/consultations/create.blade.php`
- Modify: `resources/views/pages/consultations/_edit-modal.blade.php`
- Modify: `scripts/check-frontend.mjs`
- Modify: `tests/Feature/ConsultationManagementTest.php`

**Interfaces:**
- Consumes: commit Lane A/B/C dan kontrak model/endpoint/frontend yang mereka hasilkan.
- Produces: satu baseline hijau dengan daftar konsultasi terpasang di tab shared, autosave kasus/konsultasi aktif, serta frontend checker yang menguji markup final.

- [ ] **Step 1: Verifikasi laporan worker dan ownership tidak tumpang tindih**

```powershell
$wave1 = @(
    'revisi-sibk-3-2-wave1-kasus',
    'revisi-sibk-3-2-wave1-konsultasi',
    'revisi-sibk-3-2-wave1-frontend'
)
$owners = @{}
foreach ($lane in $wave1) {
    $baseCommit = git merge-base HEAD $lane
    foreach ($file in @(git diff --name-only "$baseCommit..$lane")) {
        if ($owners.ContainsKey($file)) {
            throw "File ownership bentrok: $file ($($owners[$file]) dan $lane)"
        }
        $owners[$file] = $lane
    }
}
```

Expected: command selesai tanpa exception; semua lane berstatus DONE atau
DONE_WITH_CONCERNS dan focused gate masing-masing exit 0.

- [ ] **Step 2: Cherry-pick ketiga lane sesuai protokol integrasi**

Gunakan loop cherry-pick pada bagian Protokol worktree dan integrasi. Setelah
setiap lane masuk, jalankan `git status --short`; expected tidak ada operasi
cherry-pick tertunda.

- [ ] **Step 3: Tulis failing test untuk daftar konsultasi shared**

Tambahkan test search hanya berdasarkan nama, filter `service_field_id`, sort
allowlist `tanggal|nama|kelas|jenis_layanan`, direction `asc|desc`, tie-breaker
`consultations.id`, header lima kolom, dan larangan aksi mutasi bagi Waka.

Run:

```powershell
php artisan test tests/Feature/ConsultationManagementTest.php
```

Expected: FAIL karena `CaseController::consultationIndex()` dan tab shared masih
memakai registration number, status, serta kolom lama.

- [ ] **Step 4: Integrasikan daftar dan autosave pada file shared**

Ubah `consultationIndex()` ke field target dan sorting allowlist. Ubah tab
konsultasi menjadi Tanggal, Murid dan Kelas, Permasalahan, Jenis Layanan, Aksi.
Tambahkan `data-autosave-form`, user/form/record key, status draft, dan Hapus
Draft pada empat form kasus/konsultasi yang dimiliki Lane A/B. Tambahkan static
assertion final untuk modal, inline follow-up, autosave, `aria-labelledby`,
dialog scrollable, dan tidak adanya route UI lama.

- [ ] **Step 5: Jalankan integration gate**

```powershell
php artisan test tests/Feature/CaseManagementTest.php tests/Feature/ConsultationManagementTest.php tests/Feature/DashboardTest.php tests/Feature/AuthorizationMatrixTest.php
php vendor/bin/pint --test app/Http/Controllers/CaseController.php tests/Feature/ConsultationManagementTest.php
npm run check:frontend
npm run build
git diff --check
```

Expected: seluruh command exit 0. Task 6/8A/8B belum boleh didispatch jika gate
ini gagal.

- [ ] **Step 6: Commit integrasi Wave 1**

```powershell
git add app/Http/Controllers/CaseController.php resources/views/pages/cases resources/views/pages/consultations scripts/check-frontend.mjs tests/Feature/ConsultationManagementTest.php
git commit -m "chore: integrasikan wave pertama revisi SIBK"
```

### Task 8A — Wave 2 Lane E: Selaraskan laporan layanan BK

**Files:**
- Modify: `app/Services/CounselingReportQuery.php`
- Modify: `app/Services/OperationalReportRecapService.php`
- Modify: `app/Services/LegacyReportAdapter.php`
- Modify: `resources/views/pages/reports/_desktop-table.blade.php`
- Modify: `resources/views/pages/reports/_mobile-cards.blade.php`
- Modify: `resources/views/pages/reports/index.blade.php`
- Modify: `tests/Feature/OperationalReportRecapTest.php`
- Modify: `tests/Feature/ReportManagementTest.php`

**Interfaces:**
- Consumes: skema/model kasus dan konsultasi hasil Gate 1.
- Produces: `follow_up_case_count`; report legacy `status-tindak-lanjut` berbasis kasus terkini; rekap tanpa event `follow_ups` atau status konsultasi.

- [ ] **Step 1: Tulis test agregat yang gagal**

Pastikan satu kasus Tindak Lanjut tetap dihitung satu layanan, report tindak
lanjut berisi satu row per kasus, konsultasi selalu dihitung sebagai layanan
selesai, dan narasi kasus/konsultasi tidak masuk export.

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php tests/Feature/ReportManagementTest.php
```

Expected: FAIL karena query masih union `follow_ups` dan memakai status konsultasi.

- [ ] **Step 2: Ubah query dan view laporan**

`OperationalReportRecapService` menyatukan hanya cases + consultations dan
menghitung `follow_up_case_count` dari status `membutuhkan_tindak_lanjut`.
`CounselingReportQuery::followUps()` serta export-nya memakai `BkCase` dengan
`follow_up_type_id IS NOT NULL`. `LegacyReportAdapter` mengganti judul menjadi
`Kasus Tindak Lanjut` dan menghapus filter `follow_up_status`. View desktop,
mobile, dan ringkasan memakai key baru tanpa menggandakan service count.

- [ ] **Step 3: Jalankan focused gate dan commit**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php tests/Feature/ReportManagementTest.php
php vendor/bin/pint --test app/Services/CounselingReportQuery.php app/Services/OperationalReportRecapService.php app/Services/LegacyReportAdapter.php tests/Feature/OperationalReportRecapTest.php tests/Feature/ReportManagementTest.php
git diff --check
git add app/Services resources/views/pages/reports tests/Feature/OperationalReportRecapTest.php tests/Feature/ReportManagementTest.php
git commit -m "refactor: sesuaikan laporan layanan BK"
```

### Task 8B — Wave 2 Lane F: Selaraskan consumer dan dummy data

**Files:**
- Modify: `app/Services/DashboardService.php`
- Modify: `app/Services/ViolationReportQuery.php`
- Modify: `app/Http/Controllers/StudentController.php`
- Modify: `app/Models/Student.php`
- Modify: `app/Models/Achievement.php`
- Modify: `resources/views/pages/dashboard/html.blade.php`
- Modify: `resources/views/pages/students/show.blade.php`
- Modify: `resources/views/pages/students/index.blade.php`
- Modify: `database/seeders/DummyCaseAndServiceSeeder.php`
- Modify: `tests/Feature/DashboardTest.php`
- Modify: `tests/Feature/StudentProfileTest.php`
- Modify: `tests/Feature/DapodikSyncTest.php`
- Modify: `tests/Feature/DelayedDapodikPreparationTest.php`
- Modify: `tests/Feature/StudentDepartureTest.php`
- Modify: `tests/Feature/AchievementManagementTest.php`
- Modify: `tests/Feature/WakaReportPageTest.php`
- Modify: `tests/Feature/WakaStudentDepartureTest.php`

**Interfaces:**
- Consumes: model kasus/konsultasi target dan `follow_up_type_id` dari Gate 1.
- Produces: dashboard/profil/scope turunan/seeder tanpa consumer relasi retired; fixture integrasi memakai skema target.

- [ ] **Step 1: Ubah focused test agar gagal pada consumer lama**

Dashboard Guru BK harus menampilkan kasus berstatus Tindak Lanjut tanpa tanggal
jadwal. Profil murid tidak mempunyai count/jadwal follow-up maupun kolom
kasus/status konsultasi. Fixture Dapodik, departure, prestasi, dan Waka tidak
boleh membuat model retired.

```powershell
php artisan test tests/Feature/DashboardTest.php tests/Feature/StudentProfileTest.php tests/Feature/DapodikSyncTest.php tests/Feature/DelayedDapodikPreparationTest.php tests/Feature/StudentDepartureTest.php tests/Feature/AchievementManagementTest.php tests/Feature/WakaReportPageTest.php tests/Feature/WakaStudentDepartureTest.php
```

Expected: FAIL karena consumer/fixture masih mengakses follow-up event,
coordination, private note, atau status konsultasi.

- [ ] **Step 2: Bersihkan dashboard, profil, dan scope turunan**

Dashboard memakai cases berstatus Tindak Lanjut. Profil murid menghapus elemen
jadwal follow-up serta kolom kasus/status konsultasi. Pada `Student`,
`Achievement`, dan `ViolationReportQuery`, ganti predicate
`cases.coordinations` dengan keberadaan kasus non-deleted yang dapat dibaca Waka;
jangan expose payload mentah e-Tatib atau narasi melalui report.

- [ ] **Step 3: Ubah dummy seeder dan fixture test**

Seeder membuat kasus dengan optional `follow_up_type_id`; konsultasi mempunyai
`problem`, `handling`, `result`. Hapus pembuatan `FollowUp`, `CaseCoordination`,
dan `ConsultationPrivateNote`. Jangan reset tabel. Sesuaikan fixture delapan
test file ke field target tanpa memperluas assertion di luar perilaku terkait.

- [ ] **Step 4: Jalankan focused gate dan commit**

```powershell
php artisan test tests/Feature/DashboardTest.php tests/Feature/StudentProfileTest.php tests/Feature/DapodikSyncTest.php tests/Feature/DelayedDapodikPreparationTest.php tests/Feature/StudentDepartureTest.php tests/Feature/AchievementManagementTest.php tests/Feature/WakaReportPageTest.php tests/Feature/WakaStudentDepartureTest.php
php vendor/bin/pint --test app/Services/DashboardService.php app/Services/ViolationReportQuery.php app/Http/Controllers/StudentController.php app/Models/Student.php app/Models/Achievement.php database/seeders/DummyCaseAndServiceSeeder.php tests/Feature
git diff --check
git add app/Services/DashboardService.php app/Services/ViolationReportQuery.php app/Http/Controllers/StudentController.php app/Models/Student.php app/Models/Achievement.php resources/views/pages/dashboard resources/views/pages/students database/seeders/DummyCaseAndServiceSeeder.php tests/Feature
git commit -m "refactor: selaraskan consumer layanan BK"
```

### Integration Gate 2 — Gabungkan Lane D, E, dan F

- [ ] **Step 1: Verifikasi ownership, laporan worker, dan cherry-pick**

Ulangi pemeriksaan collision Gate 1 untuk branch `wave2-waka`, `wave2-laporan`,
dan `wave2-consumer`. Expected tidak ada file overlap. Cherry-pick dalam urutan
D, E, F memakai protokol integrasi.

- [ ] **Step 2: Jalankan gate gabungan Wave 2**

```powershell
php artisan test tests/Feature/WakaMonitoringTest.php tests/Feature/WakaDashboardTest.php tests/Feature/AuthorizationMatrixTest.php tests/Feature/OperationalReportRecapTest.php tests/Feature/ReportManagementTest.php tests/Feature/DashboardTest.php tests/Feature/StudentProfileTest.php tests/Feature/DapodikSyncTest.php tests/Feature/DelayedDapodikPreparationTest.php tests/Feature/StudentDepartureTest.php tests/Feature/AchievementManagementTest.php tests/Feature/WakaReportPageTest.php tests/Feature/WakaStudentDepartureTest.php
php vendor/bin/pint --test app database/seeders tests/Feature
npm run check:frontend
npm run build
git diff --check
```

Expected: seluruh command exit 0 sebelum runtime lama dihapus.

- [ ] **Step 3: Commit resolusi integrasi bila ada**

Jika Gate 2 memerlukan perubahan file shared, commit hanya perubahan tersebut:

```powershell
git add app resources database/seeders tests/Feature
git commit -m "chore: integrasikan wave kedua revisi SIBK"
```

Jika working tree bersih dan tidak ada resolusi, jangan membuat empty commit.

- [ ] **Step 4: Jalankan full gate Checkpoint 7A**

```powershell
composer test
php vendor/bin/pint --test
npm run check:frontend
npm run build
composer validate --strict
git diff --check
```

Expected: seluruh command exit 0. Catat hasil aktual pada handoff; full suite
tidak perlu diulang oleh setiap worker.

- [ ] **Step 5: Tutup Checkpoint 7A melalui PR**

Perbarui `docs/current-work.md` dan tambahkan ringkasan checkpoint ke
`docs/development-log.md`, lalu commit dokumentasinya. Buka satu PR branch
`revisi-sibk-3-2` ke `cobasidebar`. Jangan menargetkan `main`. Setelah PR
berstatus `MERGED`, hapus branch sumber remote dan pause sebelum Checkpoint 7B.

### Checkpoint 7B / Task 8C — Sequential Cleanup: Hapus runtime lama

Buat branch `revisi-sibk-3-2-7b-runtime` dari `cobasidebar` terbaru hanya
setelah Checkpoint 7A berstatus `MERGED`. Checkpoint ini tidak memakai worker
paralel karena route, controller, service, view, dan checker runtime saling
terkait.

**Files:**
- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `app/Services/CaseService.php`
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

**Interfaces:**
- Consumes: baseline hijau Gate 2 yang sudah tidak membaca struktur retired.
- Produces: runtime tanpa class/route/view retired; tabel/kolom masih tersedia sampai Task 9.

- [ ] **Step 1: Hapus controller/service/route/view lama**

Dari `CaseService`/`CaseController`, hapus method resolve terpisah dan koordinasi;
penyelesaian hanya melalui `cases.update`. Hapus route follow-up form, resolve,
coordination, route preview resolve, import class, dan daftar view retired pada
checker frontend. Hapus seluruh file Delete di atas.

- [ ] **Step 2: Scan consumer runtime**

```powershell
rg -n "FollowUp|CaseCoordination|ConsultationPrivateNote|followUps|coordinations|privateNote|cases\.follow-ups|cases\.coordinations|cases\.resolve" app routes resources database/seeders
```

Expected: exit 1 karena tidak ada match. Jika masih ada match, hapus consumer
tersebut sebelum Task 9; jangan drop tabel lebih dahulu.

- [ ] **Step 3: Jalankan focused gate dan commit**

```powershell
php artisan test tests/Feature/CaseManagementTest.php tests/Feature/ConsultationManagementTest.php tests/Feature/DashboardTest.php tests/Feature/OperationalReportRecapTest.php tests/Feature/ReportManagementTest.php tests/Feature/StudentProfileTest.php tests/Feature/WakaMonitoringTest.php tests/Feature/WakaDashboardTest.php
php vendor/bin/pint --test app routes tests/Feature
npm run check:frontend
npm run build
git diff --check
git add -A app routes resources/views/pages/cases resources/views/pages/waka scripts/check-frontend.mjs
git commit -m "refactor: hapus runtime layanan BK lama"
```

- [ ] **Step 4: Jalankan full gate dan tutup Checkpoint 7B**

```powershell
composer test
php vendor/bin/pint --test
npm run check:frontend
npm run build
composer validate --strict
git diff --check
```

Expected: seluruh command exit 0. Perbarui handoff/log, lalu buka satu PR
`revisi-sibk-3-2-7b-runtime` ke `cobasidebar`. Verifikasi `MERGED`, hapus branch
sumber remote, dan pause sebelum membuat branch 7C.

---

## Checkpoint C / Delivery 7C — Pembersihan Destruktif dan Gate Akhir

Buat branch `revisi-sibk-3-2-7c-schema` dari `cobasidebar` terbaru hanya
setelah Checkpoint 7B berstatus `MERGED`. Task 9 dan 10 berjalan sequential;
migration drop tidak boleh dibuat sebelum scan dependency runtime bersih.

### Task 9 — Sequential Cleanup: Hapus skema retired setelah dependency scan

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

### Task 10 — Sequential Final Gate: Verifikasi end-to-end, review kontrak, dan perbarui handoff

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
`superpowers:requesting-code-review`, buka satu PR
`revisi-sibk-3-2-7c-schema` ke `cobasidebar`, lalu verifikasi status `MERGED`
sebelum menghapus branch sumber remote. Jangan mengubah `main`; rilis produksi
memerlukan persetujuan dan PR rilis terpisah.
