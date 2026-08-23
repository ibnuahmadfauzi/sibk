# Fondasi Konfigurasi Koneksi Dapodik & e-Tatib Implementation Plan

> **Status: MENUNGGU REVIEW DAN KONFIRMASI PENGGUNA.** Dokumen ini bukan izin implementasi. Agent/model AI wajib membaca plan ini dan meminta persetujuan eksplisit sebelum menjalankan Task 1 atau mengubah file dalam scope plan.
>
> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyediakan konfigurasi URL dan credential Dapodik/e-Tatib melalui UI Admin IT dengan alur Simpan → Uji → Aktifkan, tanpa menebak kontrak API dan tanpa membahayakan data lama.

**Architecture:** Frontend hanya mengelola konfigurasi dan memicu tindakan. Backend menyimpan credential terenkripsi, mengendalikan state konfigurasi, dan kelak menjalankan adapter provider yang memetakan payload resmi ke `DapodikSnapshot` atau `EtatibSnapshot`. Fase ini mempertahankan driver production sebagai `unavailable`; adapter HTTP nyata dibuat melalui plan terpisah setelah kontrak provider tersedia.

**Tech Stack:** PHP 8.3, Laravel 13.23, Eloquent, Laravel Crypt/encrypted cast, Laravel HTTP Client untuk adapter fase berikutnya, Blade, Bootstrap 5.3, SCSS existing design system, PHPUnit 12.5.

**Spec:** PRD/SRS Aplikasi BK v1.1 yang dibuat pada Task 1 dari baseline v1.0.

## Global Constraints

- Pertahankan PRD/SRS v1.0 sebagai arsip; jangan mengubah atau menghapusnya.
- Semua PHP baru memakai `declare(strict_types=1);`.
- Hanya Admin IT aktif melalui Gate `manageDataMaster`.
- Frontend tidak pernah menghubungi Dapodik/e-Tatib secara langsung.
- Credential tidak boleh muncul pada HTML, JSON, session old input, audit, log, exception, atau response eksternal.
- Jangan menyimpan raw payload API pada tabel murid/e-Tatib.
- Jangan membuat `HttpDapodikConnector` atau `HttpEtatibConnector` berdasarkan field tebakan.
- `is_full_snapshot` kelak wajib berasal dari kontrak eksplisit dan tidak boleh memiliki default `true`.
- Gunakan istilah `murid` pada UI.

---

## Keputusan Arsitektur dan State

### State konfigurasi

| State | Kondisi |
|---|---|
| `unconfigured` | URL atau credential belum lengkap |
| `draft` | Konfigurasi lengkap, adapter tersedia, belum diuji |
| `blocked` | Adapter belum tersedia, credential rusak, atau endpoint tidak lagi diizinkan |
| `test_failed` | Uji versi konfigurasi terakhir gagal |
| `ready` | Uji sukses untuk versi konfigurasi dan adapter saat ini |
| `active` | State `ready` telah diaktifkan Admin IT |

Aturan transisi:

- Simpan perubahan material menaikkan `configuration_version`, membatalkan verifikasi, dan menonaktifkan koneksi.
- Simpan tanpa perubahan tidak mengubah version/state.
- API key kosong berarti mempertahankan; API key berisi berarti mengganti; checkbox hapus berarti menghapus.
- Uji tidak boleh mengimpor, membuat `external_sync_runs`, atau memodifikasi cache master.
- Aktivasi hanya boleh dilakukan setelah uji sukses untuk configuration version dan adapter version yang sama.
- Deaktivasi selalu diperbolehkan.
- Perubahan driver, allowlist, credential, atau configuration version membuat koneksi efektif terblokir walaupun flag database masih aktif.

### Interface utama

```php
interface IntegrationDriver
{
    public function id(): string;

    public function isAvailable(): bool;

    public function probe(
        IntegrationRuntimeConfiguration $configuration,
    ): IntegrationProbeResult;
}

interface DapodikDriver extends IntegrationDriver
{
    public function fetchSnapshot(
        IntegrationRuntimeConfiguration $configuration,
    ): DapodikSnapshot;
}

interface EtatibDriver extends IntegrationDriver
{
    public function fetchSnapshot(
        IntegrationRuntimeConfiguration $configuration,
    ): EtatibSnapshot;
}

interface IntegrationConfigurationProvider
{
    public function active(
        string $provider,
        string $adapterVersion,
    ): IntegrationRuntimeConfiguration;

    public function assertCurrent(
        string $provider,
        int $configurationVersion,
        string $adapterVersion,
    ): void;
}
```

Driver dipilih dari konfigurasi deployment melalui registry berbasis whitelist. Nama class tidak pernah berasal dari database atau input Admin IT.

### Endpoint web

```text
PATCH /data-master/integrations/{provider}
POST  /data-master/integrations/{provider}/test
POST  /data-master/integrations/{provider}/activate
POST  /data-master/integrations/{provider}/deactivate
```

`{provider}` hanya menerima `dapodik|etatib`. Route sinkronisasi lama tetap dipertahankan.

---

## Task 1: Baseline PRD/SRS v1.1 dan Desain PG-501

**Files:**

- Create: `docs/requirements/PRD_Aplikasi_BK_v1.1.md`
- Create: `docs/requirements/PRD_Aplikasi_BK_v1.1.docx`
- Create: `docs/requirements/SRS_Aplikasi_BK_v1.1.md`
- Create: `docs/requirements/SRS_Aplikasi_BK_v1.1.docx`
- Modify: `docs/requirements-index.md`
- Modify: `docs/api-contract.md`
- Modify: `AGENTS.md`

- [ ] **Step 1: Salin baseline v1.0 menjadi v1.1 tanpa mengubah v1.0**

Gunakan skill `documents` untuk menjaga paritas DOCX–Markdown.

- [ ] **Step 2: Tambahkan keputusan produk ke PRD v1.1**

Tambahkan:

- Admin IT dapat mengelola URL dan credential melalui PG-501.
- Frontend hanya menjadi UI/trigger; fetch, mapping, validasi, dan penyimpanan terjadi di backend.
- Koneksi mengikuti Simpan → Uji → Aktifkan.
- Konfigurasi dapat disiapkan sebelum kontrak provider tersedia, tetapi sinkronisasi nyata tetap diblokir.
- Endpoint outbound dibatasi allowlist deployment.
- Raw payload tidak disimpan secara default.
- Tambahkan risiko credential, SSRF, stale verification, malformed snapshot, dan duplicate sync beserta pengendaliannya.

- [ ] **Step 3: Tambahkan requirement berikut ke SRS v1.1**

```text
INT-05 — Admin IT dapat mengelola konfigurasi endpoint dan credential
          Dapodik/e-Tatib tanpa mengubah kode atau file environment.

INT-06 — Browser hanya mengirim konfigurasi dan trigger kepada backend;
          komunikasi dengan sistem eksternal dilakukan server-to-server.

INT-07 — Konfigurasi mengikuti state Simpan, Uji, dan Aktif;
          perubahan material membatalkan verifikasi dan menonaktifkan koneksi.

INT-08 — Adapter provider memetakan kontrak eksternal resmi ke snapshot
          internal; field yang tidak dibutuhkan diabaikan dan raw payload
          tidak disimpan secara default.

INT-09 — Payload yang tidak valid, tidak lengkap, atau tidak terbukti penuh
          harus ditolak sebelum import dan tidak boleh mengubah data lama.

INT-10 — Driver production tidak boleh diaktifkan sebelum autentikasi,
          endpoint, schema, pagination, semantik full/partial, fixture
          sintetis, dan prosedur gangguan provider disahkan.

NFR-09 — Credential dienkripsi menggunakan encrypter Laravel dan tidak
          boleh tampil pada response, session, audit, atau log.

NFR-10 — Endpoint outbound mengikuti exact deployment allowlist,
          redirect dimatikan, operasi per provider diserialisasi, dan
          kegagalan mempertahankan data lama.
```

Perbarui `DEP-01` dan `DEP-02`: fondasi konfigurasi boleh tersedia, tetapi uji/aktivasi production tetap terblokir sampai kontrak resmi disahkan.

- [ ] **Step 4: Tambahkan riwayat versi 1.1 bertanggal 23 Agustus 2026**

- [ ] **Step 5: Perbarui source-of-truth pointer**

Arahkan `AGENTS.md` dan `requirements-index.md` ke v1.1, tetapi tetap dokumentasikan v1.0 sebagai arsip baseline sebelumnya.

- [ ] **Step 6: Perbarui Penpot PG-501**

Pada page `22 — UI High-Fidelity Final`, tambahkan dua panel konfigurasi menggunakan token page `22.5 — Style Guide`. Jangan mengubah page lain atau membuat design token baru.

- [ ] **Step 7: Verifikasi DOCX dan Markdown memiliki heading, requirement ID, dan version history yang sama**

- [ ] **Step 8: Commit**

```bash
git add AGENTS.md docs/requirements-index.md docs/requirements docs/api-contract.md
git commit -m "docs: baseline secure integration settings in requirements v1.1"
```

---

## Task 2: Persistence dan Perlindungan Credential

**Files:**

- Create: `database/migrations/2026_08_23_000100_create_integration_settings_table.php`
- Create: `app/Models/IntegrationSetting.php`
- Create: `app/Integrations/IntegrationRuntimeConfiguration.php`
- Create: `app/Integrations/IntegrationSettingState.php`
- Create: `app/Integrations/IntegrationProbeResult.php`
- Test: `tests/Feature/IntegrationSettingTest.php`

- [ ] **Step 1: Tulis failing tests persistence**

```php
public function test_credentials_are_encrypted_and_hidden(): void;
public function test_default_setting_is_unverified_and_disabled(): void;
public function test_provider_is_unique(): void;
public function test_user_foreign_keys_become_null_when_user_is_deleted(): void;
public function test_corrupt_ciphertext_is_not_treated_as_configured(): void;
```

- [ ] **Step 2: Jalankan test dan pastikan gagal karena tabel/model belum tersedia**

```bash
php artisan test --filter IntegrationSettingTest
```

- [ ] **Step 3: Buat migration**

```php
Schema::create('integration_settings', function (Blueprint $table): void {
    $table->id();
    $table->string('provider', 30)->unique();
    $table->string('base_url', 500)->nullable();
    $table->text('credentials')->nullable();
    $table->unsignedSmallInteger('timeout_seconds')->default(30);

    $table->unsignedInteger('configuration_version')->default(0);
    $table->unsignedInteger('verified_configuration_version')->nullable();
    $table->string('verified_adapter_version', 100)->nullable();

    $table->string('last_test_status', 20)->default('untested');
    $table->string('last_test_code', 50)->nullable();
    $table->timestamp('last_tested_at')->nullable();
    $table->foreignId('last_tested_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->boolean('is_enabled')->default(false);
    $table->foreignId('updated_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->timestamps();
});
```

Jangan memakai database enum/check agar migration konsisten pada SQLite dan MySQL.

- [ ] **Step 4: Implementasikan model**

Gunakan `#[Fillable]`, `#[Hidden(['credentials'])]`, provider constants, dan casts berikut:

```php
protected function casts(): array
{
    return [
        'credentials' => 'encrypted:array',
        'timeout_seconds' => 'integer',
        'configuration_version' => 'integer',
        'verified_configuration_version' => 'integer',
        'last_tested_at' => 'datetime',
        'is_enabled' => 'boolean',
    ];
}
```

Credential internal untuk fase ini:

```php
[
    'type' => 'api_token',
    'token' => 'nilai-rahasia',
]
```

Jangan membuat accessor yang menelan `Throwable`. Decryption failure harus diteruskan ke module konfigurasi agar diblokir secara eksplisit.

- [ ] **Step 5: Implementasikan DTO aman**

`IntegrationSettingState` tidak boleh memiliki plaintext/ciphertext. `IntegrationRuntimeConfiguration` boleh memuat credential tetapi tidak memiliki `toArray()`, `jsonSerialize()`, atau implementasi logging.

- [ ] **Step 6: Pastikan test lulus**

```bash
php artisan test --filter IntegrationSettingTest
```

- [ ] **Step 7: Commit**

```bash
git add database/migrations app/Models/IntegrationSetting.php app/Integrations tests/Feature/IntegrationSettingTest.php
git commit -m "feat: add encrypted integration setting state"
```

---

## Task 3: Deployment Policy dan Driver Gate

**Files:**

- Modify: `config/sibk.php`
- Modify: `.env.example`
- Create: `app/Integrations/IntegrationEndpointPolicy.php`
- Create: `app/Integrations/IntegrationDriver.php`
- Create: `app/Integrations/IntegrationDriverRegistry.php`
- Create: `app/Integrations/Dapodik/DapodikDriver.php`
- Create: `app/Integrations/Etatib/EtatibDriver.php`
- Create: `app/Integrations/Dapodik/UnavailableDapodikDriver.php`
- Create: `app/Integrations/Etatib/UnavailableEtatibDriver.php`
- Test: `tests/Unit/IntegrationEndpointPolicyTest.php`
- Test: `tests/Unit/IntegrationDriverRegistryTest.php`

- [ ] **Step 1: Tulis failing tests URL policy**

Uji exact origin match dan penolakan user-info, query/fragment, wildcard/suffix host, port berbeda, origin di luar allowlist, driver selain whitelist, dan allowlist kosong. Origin internal HTTP hanya boleh diterima bila seluruh origin tersebut tercantum eksplisit di deployment config.

- [ ] **Step 2: Tambahkan deployment configuration**

```env
SIBK_DAPODIK_DRIVER=unavailable
SIBK_DAPODIK_ALLOWED_ORIGINS=
SIBK_ETATIB_DRIVER=unavailable
SIBK_ETATIB_ALLOWED_ORIGINS=
```

Format allowlist adalah comma-separated exact origins.

- [ ] **Step 3: Implementasikan endpoint policy**

Policy harus memerlukan URL absolut, membandingkan scheme/host/effective port, menolak username/password/query/fragment, menormalisasi URL, dan dipanggil saat save, test, activate, serta setiap penggunaan sync.

- [ ] **Step 4: Implementasikan driver interfaces dan registry**

Registry hanya mengenal nama driver yang ditulis eksplisit di kode. Pada fase ini satu-satunya driver adalah `unavailable`.

Kode hasil aman:

```text
success
adapter_unavailable
incomplete_configuration
endpoint_not_allowed
credential_unreadable
busy
timeout
connection_failed
authentication_rejected
rate_limited
remote_unavailable
contract_invalid
configuration_changed
```

- [ ] **Step 5: Pastikan driver unavailable tidak menghasilkan outbound request**

- [ ] **Step 6: Jalankan test**

```bash
php artisan test tests/Unit/IntegrationEndpointPolicyTest.php
php artisan test tests/Unit/IntegrationDriverRegistryTest.php
```

- [ ] **Step 7: Commit**

```bash
git add config/sibk.php .env.example app/Integrations tests/Unit
git commit -m "feat: gate integration drivers with deployment policy"
```

---

## Task 4: Deep Module Konfigurasi, Audit, dan Concurrency

**Files:**

- Create: `app/Integrations/IntegrationConfigurationProvider.php`
- Create: `app/Integrations/IntegrationOperationLock.php`
- Create: `app/Integrations/IntegrationConfigurationException.php`
- Create: `app/Integrations/IntegrationBusyException.php`
- Create: `app/Services/IntegrationSettingService.php`
- Extend: `tests/Feature/IntegrationSettingTest.php`

**Produces:**

```php
final class IntegrationSettingService implements IntegrationConfigurationProvider
{
    public function allStates(): array;
    public function save(string $provider, array $data, User $actor): IntegrationSettingState;
    public function testConnection(string $provider, User $actor): IntegrationSettingState;
    public function activate(string $provider, User $actor): IntegrationSettingState;
    public function deactivate(string $provider, User $actor): IntegrationSettingState;
    public function active(string $provider, string $adapterVersion): IntegrationRuntimeConfiguration;
    public function assertCurrent(string $provider, int $configurationVersion, string $adapterVersion): void;
}
```

- [ ] **Step 1: Tulis failing tests state machine**

Uji first save, keep/replace/remove credential, invalid replace+remove, perubahan material, no-op save, test sukses/gagal/stale, activation invariant, deactivation, driver/allowlist drift, serta rollback ketika audit gagal.

- [ ] **Step 2: Implementasikan per-provider operation lock**

Gunakan `Cache::lock('sibk:integration-operation:'.$provider, 600)` secara non-blocking. Save, test, activate, deactivate, dan sync memakai lock yang sama.

- [ ] **Step 3: Implementasikan save transaction dan audit tersanitasi**

Audit actions:

```text
dapodik.connection_settings_updated
dapodik.connection_tested
dapodik.connection_enabled
dapodik.connection_disabled
etatib.connection_settings_updated
etatib.connection_tested
etatib.connection_enabled
etatib.connection_disabled
```

Audit hanya menyimpan provider, endpoint origin, timeout, versions, test status, enabled flag, credential presence, dan credential changed.

- [ ] **Step 4: Implementasikan connection test**

Ambil setting/version, validasi, jalankan probe tanpa transaksi, lalu kunci row dan terapkan hasil hanya jika configuration/adapter version belum berubah. Jangan membuat sync run atau memodifikasi cache.

- [ ] **Step 5: Implementasikan activation invariant dan safe state DTO**

Blade hanya menerima provider, label, base URL, timeout, boolean credential, effective state, safe test code/time, versions, adapter availability, dan capability flags.

- [ ] **Step 6: Jalankan tests**

```bash
php artisan test --filter IntegrationSettingTest
```

- [ ] **Step 7: Commit**

```bash
git add app/Integrations app/Services/IntegrationSettingService.php tests/Feature/IntegrationSettingTest.php
git commit -m "feat: add audited integration configuration lifecycle"
```

---

## Task 5: Admin Endpoints dan Secret-Safe Validation

**Files:**

- Create: `app/Http/Requests/Admin/UpdateIntegrationSettingRequest.php`
- Create: `app/Http/Requests/Admin/IntegrationActionRequest.php`
- Create: `app/Http/Controllers/Admin/IntegrationSettingController.php`
- Modify: `app/Http/Controllers/Admin/DataMasterController.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Extend: `tests/Feature/IntegrationSettingTest.php`

- [ ] **Step 1: Tulis failing authorization/validation tests**

Uji guest, Guru BK, Koordinator BK, Waka, Admin IT nonaktif, dan Admin IT aktif untuk seluruh endpoint.

- [ ] **Step 2: Gunakan payload bernamespace**

```text
dapodik[base_url]
dapodik[api_key]
dapodik[remove_api_key]
dapodik[timeout_seconds]
etatib[base_url]
etatib[api_key]
etatib[remove_api_key]
etatib[timeout_seconds]
```

Base URL wajib lolos endpoint policy; API key nullable max 1000; remove boolean; key dilarang jika remove aktif; timeout 5–120.

- [ ] **Step 3: Cegah token masuk session**

```php
$exceptions->dontFlash([
    'dapodik.api_key',
    'etatib.api_key',
]);
```

- [ ] **Step 4: Implementasikan thin controller dan provider-scoped redirects**

Controller hanya mengambil actor, memanggil service, dan kembali ke `#integration-{provider}` dengan pesan Bahasa Indonesia berdasarkan safe result code.

- [ ] **Step 5: Tambahkan routes**

Gunakan route PATCH/POST yang ditetapkan di bagian Interface. Terapkan `whereIn('provider', IntegrationSetting::PROVIDERS)`.

- [ ] **Step 6: Daftarkan rate limiter**

Maksimum lima uji per menit untuk kombinasi user ID dan provider.

- [ ] **Step 7: DataMasterController hanya mengirim safe states**

- [ ] **Step 8: Jalankan tests**

```bash
php artisan test --filter IntegrationSettingTest
php artisan test --filter AuthorizationMatrixTest
```

- [ ] **Step 9: Commit**

```bash
git add app/Http app/Providers/AppServiceProvider.php bootstrap/app.php routes/web.php tests/Feature/IntegrationSettingTest.php
git commit -m "feat: expose admin integration configuration workflow"
```

---

## Task 6: PG-501 Pengaturan Koneksi

**Files:**

- Create: `resources/views/pages/data-master/_integration-setting.blade.php`
- Modify: `resources/views/pages/data-master/index.blade.php`
- Modify: `resources/scss/app-dashboard.scss`
- Extend: `tests/Feature/IntegrationSettingTest.php`
- Extend: `tests/Feature/FrontendPreviewTest.php`

- [ ] **Step 1: Tulis failing view tests**

Uji dua panel Admin IT, redaksi secret, pemisahan error, ID/label/ARIA unik, disabled sync button, direct POST enforcement, serta pemisahan state koneksi dan data freshness.

- [ ] **Step 2: Tambahkan section setelah status sinkronisasi dan sebelum tabel log**

Gunakan dua panel `col-12 col-xl-6`, existing `sibk-panel`, form classes, badge, dan Bootstrap utilities.

- [ ] **Step 3: Terapkan perlindungan input credential**

```html
<input
    type="password"
    name="dapodik[api_key]"
    autocomplete="new-password"
    spellcheck="false"
    autocapitalize="none"
>
```

Input tidak memiliki value/old input. Tampilkan indikator boolean `Token tersimpan` dan checkbox hapus eksplisit.

- [ ] **Step 4: Pisahkan form Simpan, Uji, Aktifkan, dan Nonaktifkan**

Tidak ada modal/collapse atau JavaScript baru.

- [ ] **Step 5: Tambahkan status UI dan badge `danger`/`neutral` menggunakan token existing**

- [ ] **Step 6: Jalankan verification**

```bash
php artisan test --filter IntegrationSettingTest
npm run check:frontend
npm run build
```

- [ ] **Step 7: Commit**

```bash
git add resources/views/pages/data-master resources/scss/app-dashboard.scss tests/Feature
git commit -m "feat: add secure integration settings to data master"
```

---

## Task 7: Guarded Connector dan Sinkronisasi Terkunci

**Files:**

- Create: `app/Integrations/Dapodik/ConfiguredDapodikConnector.php`
- Create: `app/Integrations/Etatib/ConfiguredEtatibConnector.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Services/DapodikSyncService.php`
- Modify: `app/Services/EtatibSyncService.php`
- Extend: `tests/Feature/DapodikSyncTest.php`
- Extend: `tests/Feature/EtatibSyncTest.php`

- [ ] **Step 1: Tulis failing guard tests**

Uji sync tanpa konfigurasi aktif, stale verification, unavailable driver, duplicate sync, config change during operation, safe failed run/audit, dan data lama tetap aktif.

- [ ] **Step 2: Implementasikan configured connector**

```php
public function fetchSnapshot(): DapodikSnapshot
{
    $driver = $this->drivers->dapodik();
    $configuration = $this->settings->active(
        IntegrationSetting::PROVIDER_DAPODIK,
        $driver->id(),
    );

    $snapshot = $driver->fetchSnapshot($configuration);

    $this->settings->assertCurrent(
        IntegrationSetting::PROVIDER_DAPODIK,
        $configuration->configurationVersion,
        $driver->id(),
    );

    return $snapshot;
}
```

Terapkan pola bertipe sama untuk e-Tatib.

- [ ] **Step 3: Bind connector domain ke configured connector**

Driver internal tetap `unavailable`, sehingga tidak ada outbound production pada fase ini.

- [ ] **Step 4: Gunakan operation lock sepanjang sinkronisasi**

Lock meliputi fetch, validation, import, reconciliation, status run, dan audit.

- [ ] **Step 5: Perlakukan configuration/busy exception sebagai expected integration failure**

Jangan `report()` expected failure dan jangan tampilkan secret/raw response.

- [ ] **Step 6: Pertahankan seluruh test domain lama**

- [ ] **Step 7: Jalankan tests**

```bash
php artisan test tests/Feature/DapodikSyncTest.php
php artisan test tests/Feature/EtatibSyncTest.php
php artisan test --filter IntegrationSettingTest
```

- [ ] **Step 8: Commit**

```bash
git add app/Integrations app/Providers app/Services tests/Feature
git commit -m "feat: enforce verified integration settings during sync"
```

---

## Task 8: Contract Admission, Dokumentasi Operasional, dan Release Gate

**Files:**

- Create: `docs/integrations/provider-contract-admission.md`
- Modify: `README.md`
- Modify: `docs/api-contract.md`
- Modify: `docs/development-log.md`

- [ ] **Step 1: Dokumentasikan admission gate provider**

Driver selain `unavailable` hanya boleh ditambahkan bila tersedia dokumentasi autentikasi, endpoint, fixture sintetis, field/type, pagination, completeness, full/partial semantics, rate limit, retry, outage procedure, TLS behavior, mapping snapshot, dan persetujuan Admin IT.

- [ ] **Step 2: Kunci aturan adapter fase berikutnya**

Adapter wajib memakai exact field resmi, menolak missing collection/type salah, mewajibkan full marker eksplisit, memvalidasi seluruh page, mematikan redirect, memakai `Http::preventStrayRequests()` pada test, serta tidak menyimpan/log body.

Dapodik full snapshot tanpa tahun ajaran atau murid wajib ditolak. e-Tatib full snapshot kosong hanya boleh diterima bila kontrak resmi memberikan completeness/total terverifikasi.

- [ ] **Step 3: Dokumentasikan deployment**

Deploy Fase A dengan driver `unavailable`, isi exact origins, cache config, simpan draft, verifikasi audit/redaksi, dan jangan aktifkan sebelum adapter resmi. Dokumentasikan `APP_PREVIOUS_KEYS` untuk rotasi key.

- [ ] **Step 4: Jalankan final automated verification**

```bash
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

- [ ] **Step 5: Verifikasi migration secara additive**

Jalankan pada SQLite test dan database MySQL disposable. Jangan memakai `migrate:fresh`, rollback, atau reset pada database shared/production.

- [ ] **Step 6: Manual acceptance**

- Admin IT dapat menyimpan draft kedua provider.
- Role lain ditolak.
- Token tidak muncul di HTML, session, audit, log, atau database plaintext.
- Test/aktivasi menampilkan `Adapter belum tersedia`.
- Tombol sync disabled dan direct POST gagal aman.
- Data lama tidak berubah.
- Desktop/tablet/ponsel sesuai PG-501 Penpot terbaru.

- [ ] **Step 7: Commit**

```bash
git add README.md docs
git commit -m "docs: document integration contract and deployment gates"
```

---

## Risiko dan Pengendalian Final

| Risiko | Pengendalian |
|---|---|
| Kontrak API belum diketahui | Driver `unavailable`; adapter nyata berada di plan terpisah |
| Field API berbeda dari database | Mapping hanya di provider driver menuju snapshot internal |
| Credential bocor | Encrypted cast, hidden model field, safe DTO, `dontFlash`, audit tersensor |
| SSRF/pengalihan credential | Exact deployment origin allowlist; tidak ada wildcard; redirect dimatikan |
| Config berubah setelah test | Configuration version dan adapter version |
| Test/sync bersamaan | Per-provider atomic lock dan version recheck |
| Malformed full snapshot menonaktifkan data | Contract validator fail-closed sebelum snapshot/import |
| API mengirim banyak field | Ambil field yang dibutuhkan; jangan menyimpan raw payload |
| APP_KEY berubah | `APP_PREVIOUS_KEYS`; unreadable credential memblokir outbound |
| Duplicate-click atau penyalahgunaan test | Lock dan rate limit lima uji/menit/user/provider |
| Request sinkronisasi panjang | Contract admission wajib menentukan pagination, limit, dan kebutuhan queue sebelum adapter production dibuat |

## Assumptions

- Fase ini mencakup Dapodik dan e-Tatib secara simetris.
- API key/token adalah credential UI awal; schema terenkripsi berbentuk array agar autentikasi resmi dapat dikembangkan tanpa mengekspos secret.
- Tidak ada adapter HTTP nyata atau dummy production dalam fase ini.
- Fake connector tetap hanya untuk automated tests.
- PRD/SRS v1.1 menjadi source of truth baru setelah Task 1 selesai; v1.0 tetap dipertahankan sebagai arsip.
- Eksekusi direkomendasikan memakai subagent-driven development dengan review requirement, security, dan test pada setiap task.
