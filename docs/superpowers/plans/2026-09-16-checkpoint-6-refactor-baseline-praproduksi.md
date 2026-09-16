# Checkpoint 6 Refactor dan Baseline Praproduksi Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Memecah tiga service besar menjadi komponen terfokus tanpa mengubah route, otorisasi, transaksi, keluaran, atau kontrak legacy, lalu menyiapkan `cobasidebar` sebagai kandidat merge ke `main`.

**Architecture:** `DapodikReconciliationService`, `IntegrationSettingService`, dan `ReportService` tetap menjadi facade dengan API publik yang sama. Logika dipindahkan menurut tanggung jawab yang sudah ditetapkan spec; controller, request, provider binding, dan consumer lama tidak perlu berubah.

**Tech Stack:** PHP 8.3+, Laravel 13, Eloquent, PHPUnit 12, Blade/Bootstrap/Vite.

**Spec:** `docs/superpowers/specs/2026-09-14-baseline-cobasidebar-arsip-dan-penyederhanaan-design.md` bagian 5 Checkpoint 6, bagian 7, bagian 8, dan bagian 10-11.

## Global Constraints

- Refactor tidak membuat migration, mengubah otorisasi, mengubah format keluaran, atau menambah dependency.
- Facade lama dan seluruh signature publik tetap tersedia selama masih dipakai controller, request, service, dan test.
- Test memeriksa hasil dan perilaku, bukan susunan internal class.
- Transaksi, lock, fencing token, validasi drift, audit, redaksi credential, pagination, dan scope data harus tetap pada batas yang sama.
- Adapter production Dapodik/e-Tatib tetap `unavailable` sampai kontrak resmi lolos admission gate.
- Gunakan Bahasa Indonesia sederhana untuk dokumentasi dan pesan commit.

## Pembagian Checkpoint

- **Checkpoint 6A — Dapodik:** Task 1, focused gate, full gate, review, dan PR
  ke `cobasidebar`.
- **Checkpoint 6B — Pengaturan integrasi:** Task 2 dimulai dari
  `cobasidebar` setelah PR 6A merged, lalu focused gate, full gate, review,
  dan PR tersendiri.
- **Checkpoint 6C — Laporan dan praproduksi:** Task 3-4 dimulai dari
  `cobasidebar` setelah PR 6B merged, lalu gate praproduksi dan PR terakhir.
- Scope dan perilaku Checkpoint 6 tidak berubah; pembagian ini hanya mengecilkan
  ukuran diff, review, dan risiko integrasi setiap PR.

---

### Checkpoint 6A — Task 1: Pecah rekonsiliasi Dapodik dan pertahankan facade

**Files:**
- Create: `app/Services/DapodikPreviewBuilder.php`
- Create: `app/Services/DapodikMatchResolver.php`
- Create: `app/Services/DapodikApplyValidator.php`
- Create: `app/Services/DapodikApplyService.php`
- Create: `app/Services/DapodikDeactivationPlanner.php`
- Modify: `app/Services/DapodikReconciliationService.php`
- Test: `tests/Feature/DapodikSyncTest.php`

**Interfaces:**
- Consumes: `DapodikSnapshot`, `ExternalSyncRun`, `DapodikSyncPreviewItem`, `IntegrationSetting`, `IntegrationOperationContext`, dan `User` yang sudah dipakai facade.
- Produces: facade dengan `createPreview(...)`, `decide(...)`, dan `apply(...)` yang signature serta return type-nya tidak berubah.
- Produces: `DapodikPreviewBuilder::create(...)`, `DapodikMatchResolver::decide(...)`, `DapodikApplyValidator::validate(...)`, `DapodikApplyService::apply(...)`, dan operasi plan/validasi/apply pada `DapodikDeactivationPlanner`.

- [ ] **Step 1: Kunci perilaku awal dengan test karakterisasi existing**

Run:

```powershell
php artisan test tests/Feature/DapodikSyncTest.php
```

Expected: PASS. Refactor ini tidak menambah perilaku baru, sehingga test existing menjadi karakterisasi dan tidak ditambah assertion struktur internal.

- [ ] **Step 2: Pindahkan pembuatan pratinjau**

Pindahkan tubuh `createPreview()` beserta normalisasi, pembuatan item, snapshot evidence, dan fingerprint pratinjau ke `DapodikPreviewBuilder::create()`. Inject hanya dependency yang dipakai: `AuditService`, `IntegrationOperationLock`, `DapodikMatchResolver`, dan `DapodikDeactivationPlanner`.

Signature:

```php
public function create(
    DapodikSnapshot $snapshot,
    ExternalSyncRun $run,
    IntegrationSetting $setting,
    IntegrationOperationContext $context,
    ?User $actor = null,
): ExternalSyncRun
```

- [ ] **Step 3: Pindahkan pencocokan dan keputusan**

Pindahkan `decide()`, perhitungan keputusan membership turunan, pencarian kandidat/target, klasifikasi empat jenis item, fingerprint target, canonical JSON, dan konversi tanggal ke `DapodikMatchResolver`. Jangan mengubah query, urutan lock, pesan validasi, atau payload audit.

Signature utama:

```php
/** @param array{decision: string, candidate_id?: int|null, decision_revision: int} $data */
public function decide(
    ExternalSyncRun $run,
    DapodikSyncPreviewItem $item,
    array $data,
    User $actor,
): DapodikSyncPreviewItem
```

- [ ] **Step 4: Pindahkan validasi apply**

Pindahkan pemeriksaan preview aktif, revision, hash/fingerprint, graph induk-anak, ownership natural key, target drift, dan keputusan membership turunan ke `DapodikApplyValidator::validate()`.

```php
/** @param Collection<int, DapodikSyncPreviewItem> $items */
public function validate(
    ExternalSyncRun $run,
    Collection $items,
    int $expectedDecisionRevision,
): void
```

Validator memakai `DapodikMatchResolver` untuk resolusi target/fingerprint dan `DapodikDeactivationPlanner` untuk membandingkan plan, tanpa menulis data.

- [ ] **Step 5: Pindahkan plan dan penerapan deactivation**

Pindahkan `buildDeactivationPlan()`, `validateDeactivationPlan()`, dan `applyDeactivationPlan()` ke `DapodikDeactivationPlanner`. Pertahankan aturan: hanya row `master_source=dapodik` dari full snapshot yang boleh dinonaktifkan dan setiap perubahan tetap diaudit.

```php
/** @param array<string, list<string>> $incomingSourceIds */
public function build(array $incomingSourceIds): array

/** @param Collection<int, DapodikSyncPreviewItem> $items */
public function validate(ExternalSyncRun $run, Collection $items): void

public function apply(ExternalSyncRun $run, User $actor): void
```

- [ ] **Step 6: Pindahkan transaksi apply dan jadikan facade tipis**

Pindahkan transaksi `apply()`, mutasi item, audit target, pencatatan unmatched issue, rekonsiliasi identitas, dan relink e-Tatib ke `DapodikApplyService`. Pertahankan `DB::transaction()` di luar seluruh rangkaian mutasi.

Isi akhir facade:

```php
final class DapodikReconciliationService
{
    public const int PREVIEW_TTL_HOURS = 24;

    public function __construct(
        private readonly DapodikPreviewBuilder $previewBuilder,
        private readonly DapodikMatchResolver $matchResolver,
        private readonly DapodikApplyService $applyService,
    ) {}

    public function createPreview(
        DapodikSnapshot $snapshot,
        ExternalSyncRun $run,
        IntegrationSetting $setting,
        IntegrationOperationContext $context,
        ?User $actor = null,
    ): ExternalSyncRun {
        return $this->previewBuilder->create($snapshot, $run, $setting, $context, $actor);
    }

    /** @param array{decision: string, candidate_id?: int|null, decision_revision: int} $data */
    public function decide(
        ExternalSyncRun $run,
        DapodikSyncPreviewItem $item,
        array $data,
        User $actor,
    ): DapodikSyncPreviewItem {
        return $this->matchResolver->decide($run, $item, $data, $actor);
    }

    public function apply(ExternalSyncRun $run, User $actor, int $expectedDecisionRevision): ExternalSyncRun
    {
        return $this->applyService->apply($run, $actor, $expectedDecisionRevision);
    }
}
```

- [ ] **Step 7: Jalankan focused gate**

Run:

```powershell
php artisan test tests/Feature/DapodikSyncTest.php tests/Feature/TemporaryIdentityConflictTest.php
php vendor/bin/pint --test app/Services tests/Feature/DapodikSyncTest.php tests/Feature/TemporaryIdentityConflictTest.php
git diff --check
```

Expected: seluruh command exit 0 dan hasil Dapodik tetap identik.

- [ ] **Step 8: Commit**

```powershell
git add app/Services/DapodikReconciliationService.php app/Services/DapodikPreviewBuilder.php app/Services/DapodikMatchResolver.php app/Services/DapodikApplyValidator.php app/Services/DapodikApplyService.php app/Services/DapodikDeactivationPlanner.php
git commit -m "refactor: pecah layanan rekonsiliasi Dapodik"
```

- [ ] **Step 9: Jalankan full gate Checkpoint 6A**

```powershell
composer test
php vendor/bin/pint --test
npm run check:frontend
npm run build
composer validate --strict
git diff --check
```

Expected: seluruh command exit 0.

- [ ] **Step 10: Catat handoff dan tutup 6A**

Perbarui `docs/current-work.md` dan `docs/development-log.md` dengan hasil focused
gate, full gate, commit terakhir, serta langkah PR ke `cobasidebar`.

```powershell
git add docs/current-work.md docs/development-log.md
git commit -m "docs: tutup checkpoint 6A Dapodik"
```

---

### Checkpoint 6B — Task 2: Pecah pengaturan integrasi dan pertahankan provider binding

**Files:**
- Create: `app/Services/IntegrationStateResolver.php`
- Create: `app/Services/IntegrationSettingUpdater.php`
- Create: `app/Services/IntegrationConnectionTester.php`
- Create: `app/Services/IntegrationActivationService.php`
- Modify: `app/Services/IntegrationSettingService.php`
- Verify unchanged: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/IntegrationSettingTest.php`
- Test: `tests/Feature/DapodikSyncTest.php`
- Test: `tests/Feature/EtatibSyncTest.php`

**Interfaces:**
- Consumes: binding `IntegrationConfigurationProvider::class => IntegrationSettingService::class` yang sudah ada.
- Produces: seluruh method publik facade tetap sama: `allStates()`, `save()`, `testConnection()`, `activate()`, `deactivate()`, `active()`, dan `assertCurrent()`.
- Produces: resolver status/runtime, updater penyimpanan, tester koneksi, dan service aktivasi yang masing-masing memegang satu lifecycle.

- [ ] **Step 1: Kunci perilaku awal**

Run:

```powershell
php artisan test tests/Feature/IntegrationSettingTest.php tests/Feature/DapodikSyncTest.php tests/Feature/EtatibSyncTest.php
```

Expected: PASS, termasuk transaksi, fencing/deadline, redaksi secret, dan driver unavailable.

- [ ] **Step 2: Ekstrak resolver status dan runtime**

Pindahkan `stateForProvider()`, `state()`, `runtimeConfiguration()`, `driver()`, `policy()`, `auditSnapshot()`, `hasReadableCredentials()`, `endpointOrigin()`, `assertProvider()`, dan invariant aktivasi ke `IntegrationStateResolver`. API internal yang dipakai komponen lain:

```php
public function forProvider(string $provider): IntegrationSettingState
public function forSetting(IntegrationSetting $setting): IntegrationSettingState
public function active(string $provider, ?IntegrationOperationContext $context = null): IntegrationRuntimeConfiguration
public function assertCurrent(
    string $provider,
    int $expectedVersion,
    string $driverId,
    string $adapterVersion,
    string $contractVersion,
    ?IntegrationOperationContext $context = null,
): void
public function auditSnapshot(IntegrationSetting $setting, bool $credentialChanged): array
```

- [ ] **Step 3: Ekstrak penyimpanan konfigurasi**

Pindahkan tubuh `save()`, normalisasi input, nullable trim, dan clear verification ke `IntegrationSettingUpdater::save()`. Pertahankan `#[\SensitiveParameter]`, penanganan ciphertext rusak, lock, transaksi, audit, increment `configuration_version`, serta no-op tanpa write.

```php
/** @param array<string, mixed> $data */
public function save(
    string $provider,
    #[\SensitiveParameter] array $data,
    User $actor,
): IntegrationSettingState
```

- [ ] **Step 4: Ekstrak uji koneksi**

Pindahkan tubuh `testConnection()`, validasi probe code, dan pemeriksaan konfigurasi hasil probe ke `IntegrationConnectionTester::test()`. Network fetch tetap di luar transaksi; penyelesaian hasil tetap masuk lock/transaksi dan gagal aman bila config/fence/deadline berubah.

```php
public function test(string $provider, User $actor): IntegrationSettingState
```

- [ ] **Step 5: Ekstrak aktivasi dan nonaktifkan**

Pindahkan `activate()` dan `deactivate()` ke `IntegrationActivationService`. Pertahankan bahwa deactivate tetap dapat commit ketika driver registry tidak dapat di-resolve, tetapi audit failure tetap rollback.

```php
public function activate(string $provider, User $actor): IntegrationSettingState
public function deactivate(string $provider, User $actor): IntegrationSettingState
```

- [ ] **Step 6: Jadikan service lama facade**

```php
final class IntegrationSettingService implements IntegrationConfigurationProvider
{
    public function __construct(
        private readonly IntegrationStateResolver $states,
        private readonly IntegrationSettingUpdater $updater,
        private readonly IntegrationConnectionTester $tester,
        private readonly IntegrationActivationService $activation,
    ) {}

    // Tujuh method publik lama hanya melakukan delegasi dengan signature identik.
}
```

Jangan mengubah binding di `AppServiceProvider`; consumer interface harus tetap memperoleh facade.

- [ ] **Step 7: Jalankan focused gate**

```powershell
php artisan test tests/Feature/IntegrationSettingTest.php tests/Feature/DapodikSyncTest.php tests/Feature/EtatibSyncTest.php
php vendor/bin/pint --test app/Services tests/Feature/IntegrationSettingTest.php tests/Feature/DapodikSyncTest.php tests/Feature/EtatibSyncTest.php
git diff --check
```

Expected: seluruh command exit 0, tidak ada credential pada output test atau diff.

- [ ] **Step 8: Commit**

```powershell
git add app/Services/IntegrationSettingService.php app/Services/IntegrationStateResolver.php app/Services/IntegrationSettingUpdater.php app/Services/IntegrationConnectionTester.php app/Services/IntegrationActivationService.php
git commit -m "refactor: pecah layanan pengaturan integrasi"
```

- [ ] **Step 9: Jalankan full gate Checkpoint 6B**

```powershell
composer test
php vendor/bin/pint --test
npm run check:frontend
npm run build
composer validate --strict
git diff --check
```

Expected: seluruh command exit 0.

- [ ] **Step 10: Catat handoff dan tutup 6B**

Perbarui `docs/current-work.md` dan `docs/development-log.md` dengan hasil focused
gate, full gate, commit terakhir, serta langkah PR ke `cobasidebar`.

```powershell
git add docs/current-work.md docs/development-log.md
git commit -m "docs: tutup checkpoint 6B pengaturan integrasi"
```

---

### Checkpoint 6C — Task 3: Ganti ReportService lama dengan facade dan query per keluarga

**Files:**
- Create: `app/Services/LegacyReportQuery.php`
- Create: `app/Services/ViolationReportQuery.php`
- Create: `app/Services/CounselingReportQuery.php`
- Create: `app/Services/AchievementReportQuery.php`
- Create: `app/Services/LegacyReportAdapter.php`
- Modify: `app/Services/ReportService.php`
- Test: `tests/Feature/ReportManagementTest.php`
- Test: `tests/Feature/AchievementManagementTest.php`
- Test: `tests/Feature/OperationalReportRecapTest.php`

**Interfaces:**
- Consumes: tujuh constant tipe, `ReportService::types()`, `catalogFor()`, `build()`, dan `exportRows()` yang dipakai request/controller/test.
- Produces: tiga query keluarga dan satu adapter legacy; facade lama tetap menjadi satu-satunya kontrak consumer.
- Produces: `LegacyReportQuery` sebagai base kecil untuk helper yang benar-benar dipakai minimal dua keluarga, agar masking, histori kelas, row/stats, dan pagination tidak diduplikasi.

- [ ] **Step 1: Kunci tujuh kontrak legacy dan tiga tab baru**

```powershell
php artisan test tests/Feature/ReportManagementTest.php tests/Feature/AchievementManagementTest.php tests/Feature/OperationalReportRecapTest.php
```

Expected: PASS, termasuk scope Guru BK/Koordinator, privasi, pagination, query count, CSV, dan pemisahan mode `type`/`tab`.

- [ ] **Step 2: Ekstrak helper bersama minimum**

Pindahkan hanya helper yang dipakai lebih dari satu keluarga ke `LegacyReportQuery`: filter kelas historis, lookup membership, `row()`, `datedRow()`, `stats()`, `initials()`, `maskNisn()`, `statusTone()`, dan pagination. Jangan memindahkan katalog, authorization, atau dispatch ke base class.

```php
abstract class LegacyReportQuery
{
    public function __construct(protected readonly ReportPolicy $policy) {}

    /** @return array<string, mixed> */
    abstract public function build(string $type, User $user, array $filters, bool $paginate): array;

    /** @return array{id: string, columns: list<string>, rows: iterable<int, array<string, mixed>>} */
    abstract public function export(string $type, User $user, array $filters): array;
}
```

- [ ] **Step 3: Ekstrak query pelanggaran**

Pindahkan tipe `pelanggaran-murid`, `pelanggaran-kelas`, dan `poin-pelanggaran`, termasuk query e-Tatib, scope role, points tone, pagination murid, serta lazy export ke `ViolationReportQuery`.

- [ ] **Step 4: Ekstrak query layanan**

Pindahkan tipe `konsultasi`, `status-tindak-lanjut`, dan `rekap-layanan-bk`, termasuk scope kasus/konsultasi, filter umum layanan, pemilihan murid resmi/sementara, pagination, dan lazy export ke `CounselingReportQuery`.

- [ ] **Step 5: Ekstrak query prestasi**

Pindahkan tipe `prestasi`, filter status/jenis/tingkat, scope pemilik/Koordinator, pagination, masking NISN, dan lazy export ke `AchievementReportQuery`.

- [ ] **Step 6: Buat adapter legacy dan facade tipis**

`LegacyReportAdapter` memegang authorization, katalog tujuh tipe, resolusi periode, normalisasi filter, filter options, dan dispatch keluarga. `ReportService` mempertahankan seluruh constant dan `types()` lalu mendelegasikan tiga method instance.

```php
class ReportService
{
    // Pertahankan tujuh constant dan types() persis seperti saat ini.

    public function __construct(private readonly LegacyReportAdapter $legacy) {}

    public function catalogFor(User $user): array
    {
        return $this->legacy->catalogFor($user);
    }

    public function build(User $user, array $filters, bool $paginate = true): array
    {
        return $this->legacy->build($user, $filters, $paginate);
    }

    public function exportRows(User $user, array $filters): array
    {
        return $this->legacy->exportRows($user, $filters);
    }
}
```

- [ ] **Step 7: Jalankan focused gate**

```powershell
php artisan test tests/Feature/ReportManagementTest.php tests/Feature/AchievementManagementTest.php tests/Feature/OperationalReportRecapTest.php tests/Feature/AuthorizationMatrixTest.php
php vendor/bin/pint --test app/Services tests/Feature/ReportManagementTest.php tests/Feature/AchievementManagementTest.php tests/Feature/OperationalReportRecapTest.php
npm run check:frontend
git diff --check
```

Expected: seluruh command exit 0; tujuh URL legacy dan tiga tab menghasilkan data, scope, masking, dan CSV yang sama.

- [ ] **Step 8: Commit**

```powershell
git add app/Services/ReportService.php app/Services/LegacyReportQuery.php app/Services/ViolationReportQuery.php app/Services/CounselingReportQuery.php app/Services/AchievementReportQuery.php app/Services/LegacyReportAdapter.php
git commit -m "refactor: pecah query laporan lama"
```

---

### Checkpoint 6C — Task 4: Verifikasi baseline praproduksi dan tutup checkpoint

**Files:**
- Modify: `docs/current-work.md`
- Modify: `docs/development-log.md`
- Verify: `docs/integrations/provider-contract-admission.md`
- Verify: `app/Integrations/Dapodik/UnavailableDapodikConnector.php`
- Verify: `app/Integrations/Etatib/UnavailableEtatibConnector.php`

**Interfaces:**
- Consumes: hasil Task 1-3 dan gate umum repository.
- Produces: handoff ringkas Checkpoint 6, bukti gate, serta baseline `cobasidebar` yang siap diajukan ke `main` tanpa mengaktifkan adapter production.

- [ ] **Step 1: Audit diff terhadap larangan scope**

```powershell
git diff --name-status cobasidebar...HEAD
git diff -- database routes app/Http app/Policies composer.json composer.lock package.json package-lock.json
rg -n "class Unavailable(Dapodik|Etatib)Connector|provider-contract-admission" app docs
```

Expected: tidak ada migration, route, controller, policy, dependency, atau adapter production baru.

- [ ] **Step 2: Jalankan gate perilaku sensitif**

```powershell
php artisan test tests/Feature/DapodikSyncTest.php tests/Feature/IntegrationSettingTest.php tests/Feature/EtatibSyncTest.php
php artisan test tests/Feature/ReportManagementTest.php tests/Feature/OperationalReportRecapTest.php tests/Feature/AuthorizationMatrixTest.php
```

Expected: transaksi, concurrency/fencing, privacy, authorization, export, dan kontrak legacy PASS.

- [ ] **Step 3: Jalankan gate penuh**

```powershell
composer test
php vendor/bin/pint --test
npm run check:frontend
npm run build
composer validate --strict
git diff --check
```

Expected: seluruh command exit 0.

- [ ] **Step 4: Verifikasi cache produksi**

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear
```

Expected: seluruh cache dapat dibuat dan dibersihkan tanpa error.

- [ ] **Step 5: Perbarui handoff dan development log**

Catat:

```text
- Checkpoint 6 selesai pada branch checkpoint-6-refactor.
- Tiga facade lama tetap kompatibel; controller/request/provider binding tidak berubah.
- Tidak ada migration, perubahan authorization/output, dependency baru, atau adapter production.
- Gate focused dan penuh beserta jumlah test/assertion terakhir.
- Langkah berikutnya: review diff, PR ke cobasidebar, lalu review kandidat merge ke main.
```

- [ ] **Step 6: Commit penutupan checkpoint**

```powershell
git add docs/current-work.md docs/development-log.md
git commit -m "docs: catat baseline praproduksi checkpoint 6"
```

- [ ] **Step 7: Review final sebelum PR**

```powershell
git status --short --branch
git log --oneline cobasidebar..HEAD
git diff --stat cobasidebar...HEAD
git diff --check cobasidebar...HEAD
```

Expected: worktree bersih, commit hanya berisi scope Checkpoint 6, dan branch siap dibuatkan PR ke `cobasidebar`.
