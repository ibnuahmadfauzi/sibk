# Fondasi Konfigurasi Koneksi Dapodik & e-Tatib Implementation Plan

> **Status: DISETUJUI UNTUK IMPLEMENTASI BERTAHAP PADA 8 SEPTEMBER 2026.** Persetujuan mencakup fondasi Fase A pada branch `integrasi-api-plan`; adapter production nyata tetap di luar scope sampai kontrak provider diterima dan lolos admission gate. Pada 9 September 2026 pengguna menetapkan bahwa baseline v1.1 cukup dalam Markdown dan halaman pengaturan koneksi diimplementasikan langsung tanpa artefak Penpot baru.
>
> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **Execution gate:** Selesaikan dan verifikasi satu task sebelum berpindah ke task berikutnya. Driver production tetap `unavailable` pada seluruh task plan ini.

**Goal:** Menyediakan konfigurasi URL dan credential Dapodik/e-Tatib melalui UI Admin IT dengan alur Simpan → Uji → Aktifkan, tanpa menebak kontrak API dan tanpa membahayakan data lama.

**Architecture:** Frontend hanya mengelola konfigurasi dan memicu tindakan. Backend menyimpan credential terenkripsi, mengendalikan state konfigurasi, dan kelak menjalankan adapter provider yang memetakan payload resmi ke `DapodikSnapshot` atau `EtatibSnapshot`. Fase ini mempertahankan driver production sebagai `unavailable`; adapter HTTP nyata dibuat melalui plan terpisah setelah kontrak provider tersedia.

**Tech Stack:** PHP 8.3, Laravel 13.23, Eloquent, Laravel Crypt/encrypted cast, Laravel HTTP Client untuk adapter fase berikutnya, Blade, Bootstrap 5.3, SCSS existing design system, PHPUnit 12.5.

**Spec:** PRD/SRS Aplikasi BK v1.1 yang dibuat pada Task 1 dari baseline v1.0.

## Global Constraints

- Pertahankan PRD/SRS v1.0 sebagai arsip; jangan mengubah atau menghapusnya.
- Semua PHP baru memakai `declare(strict_types=1);`.
- Hanya Admin IT aktif melalui Gate `manageDataMaster` yang boleh mengakses UI dan aksi konfigurasi integrasi pada Task 1–Task 8. Task 0 tetap mengikuti capability domain kasus/penugasan/murid yang sudah ditetapkan AUTH-01–AUTH-07.
- Frontend tidak pernah menghubungi Dapodik/e-Tatib secara langsung.
- Credential tidak boleh muncul pada HTML, JSON, session old input, audit, log, exception, atau response eksternal.
- Jangan menyimpan raw payload API pada tabel murid/e-Tatib.
- Jangan membuat `HttpDapodikConnector` atau `HttpEtatibConnector` berdasarkan field tebakan.
- `is_full_snapshot` kelak wajib berasal dari kontrak eksplisit dan tidak boleh memiliki default `true`.
- Credential pada sistem sumber wajib least-privilege/read-only; larangan method tulis pada kode SIBK saja tidak dianggap cukup.
- `driver_id`, `adapter_version`, dan `contract_version` adalah identitas berbeda dan tidak boleh saling menggantikan.
- Probe sukses wajib membuktikan autentikasi, kompatibilitas kontrak, serta identitas sekolah/sumber yang diharapkan; respons HTTP 2xx saja tidak cukup.
- Endpoint outbound wajib fail-closed terhadap redirect, user-info, DNS rebinding, alamat metadata/link-local, origin drift, proxy tak tepercaya, dan TLS invalid. Akses jaringan privat hanya boleh melalui origin deployment yang ditulis eksplisit.
- Sebelum adapter production dibuat, kontrak wajib menetapkan ukuran maksimum respons/page, pagination, timeout, retry/backoff, rate limit, concurrency, dan kebutuhan queue.
- Halaman pengaturan koneksi dibuat langsung dengan komponen, token, dan pola layout yang sudah ada; hierarki informasi mengutamakan keamanan, kepraktisan, dan kenyamanan Admin IT tanpa membuat desain Penpot baru.
- Gunakan istilah `murid` pada UI.

---

## Keputusan Arsitektur dan State

### State konfigurasi

| State | Kondisi |
|---|---|
| `unconfigured` | URL, credential, atau expected source identifier belum lengkap |
| `draft` | Konfigurasi lengkap, adapter tersedia, belum diuji |
| `blocked` | Adapter belum tersedia, credential rusak, atau endpoint tidak lagi diizinkan |
| `test_failed` | Uji versi konfigurasi terakhir gagal |
| `ready` | Uji sukses untuk versi konfigurasi dan adapter saat ini |
| `active` | State `ready` telah diaktifkan Admin IT |

Aturan transisi:

- Simpan perubahan material menaikkan `configuration_version`, membatalkan verifikasi, dan menonaktifkan koneksi.
- Simpan tanpa perubahan tidak mengubah version/state.
- Field expected source identifier boleh kosong hanya agar konfigurasi parsial dapat disimpan sebagai `unconfigured`; test dan activation wajib gagal tertutup sampai field lengkap.
- API key kosong berarti mempertahankan; API key berisi berarti mengganti; checkbox hapus berarti menghapus.
- Uji tidak boleh mengimpor, membuat `external_sync_runs`, atau memodifikasi cache master.
- Aktivasi hanya boleh dilakukan setelah uji sukses untuk configuration version, driver ID, adapter version, contract version, dan endpoint-policy digest yang sama.
- Deaktivasi selalu diperbolehkan.
- Perubahan driver, allowlist, credential, atau configuration version membuat koneksi efektif terblokir walaupun flag database masih aktif.

### Interface utama

```php
interface IntegrationDriver
{
    public function id(): string;

    public function adapterVersion(): string;

    public function contractVersion(): string;

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
        string $driverId,
        string $adapterVersion,
        string $contractVersion,
    ): IntegrationRuntimeConfiguration;

    public function assertCurrent(
        string $provider,
        int $configurationVersion,
        string $driverId,
        string $adapterVersion,
        string $contractVersion,
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

## Task 0: Tutup Gap Otorisasi dan Rollover yang Sudah Ada

**Files:**

- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `app/Http/Controllers/StudentController.php`
- Modify: `app/Http/Controllers/AssignmentController.php`
- Modify: `app/Http/Requests/StoreCaseRequest.php`
- Modify: `app/Services/CaseService.php`
- Modify: `app/Models/StudentClassMembership.php`
- Modify: `app/Models/TeacherAssignment.php`
- Modify: `resources/views/pages/assignments/classes/index.blade.php`
- Modify: `resources/views/pages/assignments/classes/manage.blade.php`
- Modify: `resources/views/pages/students/show.blade.php`
- Extend: `tests/Feature/CaseManagementTest.php`
- Extend: `tests/Feature/AssignmentManagementTest.php`
- Extend: `tests/Feature/StudentProfileTest.php`

- [x] **Step 1: Tulis failing test scope e-Tatib**

Pastikan setiap actor yang memiliki capability membuat kasus hanya menerima pilihan record e-Tatib milik murid yang lolos scope akses pembuatan kasusnya. Role Koordinator BK tidak boleh memperluas fungsi Guru BK hanya karena role tersebut melekat pada akun yang sama.

- [x] **Step 2: Gunakan query scope akses yang sama untuk murid dan record e-Tatib**

Jangan memuat maksimal 200 record aktif global ke form. Query harus dibatasi di server berdasarkan murid yang dapat diakses actor. Validasi akhir di Service wajib mengulang scope actor dan relasi record-ke-murid agar ID hasil forge/direct request ditolak meskipun lolos validasi `exists` global. Untuk identitas sementara, hanya record aktif yang belum dipetakan ke master (`student_id` null) dan memiliki NISN exact yang boleh dipilih; record yang sudah dipetakan ke murid master tidak boleh diakses melalui jalur identitas sementara.

- [x] **Step 3: Tulis failing test rollover UI**

Uji status penugasan setelah akhir tahun ajaran, kelas aktif pada profil murid, pasangan kelas-tahun pada form, penugasan masa depan yang sudah dijadwalkan, dan forged `etatib_record_ids` milik murid di luar scope.

- [x] **Step 4: Selaraskan presentasi frontend dengan batas periode domain**

Gunakan scope/helper model yang sudah ada agar controller dan Blade tidak menghitung ulang periode aktif secara berbeda. `StudentController` harus mengirim current membership yang sudah di-resolve oleh query domain; Blade hanya mempresentasikannya. Sediakan kontrak helper/query yang eksplisit untuk status aktif, terjadwal, dan berakhir. Pilihan kelas wajib mengikuti tahun ajaran terpilih tanpa menambah JavaScript baru; pasangan invalid tetap ditolak server.

- [x] **Step 5: Jalankan verification**

```bash
php artisan test --filter CaseManagementTest
php artisan test --filter AssignmentManagementTest
php artisan test --filter StudentProfileTest
npm run check:frontend
git diff --check
```

- [x] **Step 6: Commit**

```bash
git add app/Http/Controllers app/Http/Requests/StoreCaseRequest.php app/Services/CaseService.php app/Models/StudentClassMembership.php app/Models/TeacherAssignment.php resources/views/pages tests/Feature
git commit -m "fix: align rollover UI and e-tatib access scope"
```

**Gate selesai 9 September 2026:** commits `ac7d2af`, `062f563`, dan `db94662`; focused tests 31/31, suite penuh 111/111 dengan 834 assertion, frontend checker, Pint, dan diff-check lulus; scoped re-review menyatakan seluruh finding selesai tanpa breakage baru.

---

## Task 1: Baseline PRD/SRS v1.1 Markdown

**Files:**

- Create: `docs/requirements/PRD_Aplikasi_BK_v1.1.md`
- Create: `docs/requirements/SRS_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements-index.md`
- Modify: `docs/api-contract.md`
- Modify: `AGENTS.md`
- Create: `docs/integrations/dapodik-contract-discovery.md`
- Create: `docs/integrations/etatib-contract-discovery.md`

- [x] **Step 1: Salin baseline Markdown v1.0 menjadi v1.1 tanpa mengubah artefak v1.0**

- [x] **Step 2: Tambahkan keputusan produk ke PRD v1.1**

Tambahkan:

- Admin IT dapat mengelola URL dan credential melalui PG-501.
- Frontend hanya menjadi UI/trigger; fetch, mapping, validasi, dan penyimpanan terjadi di backend.
- Koneksi mengikuti Simpan → Uji → Aktifkan.
- Konfigurasi dapat disiapkan sebelum kontrak provider tersedia, tetapi sinkronisasi nyata tetap diblokir.
- Endpoint outbound dibatasi allowlist deployment.
- Raw payload tidak disimpan secara default.
- Credential sumber wajib read-only dan identitas sekolah/sumber harus dipastikan saat probe.
- Tambahkan risiko credential, SSRF/DNS rebinding, salah identitas sekolah, stale verification, malformed snapshot, payload berlebih, dan duplicate sync beserta pengendaliannya.

- [x] **Step 3: Tambahkan requirement berikut ke SRS v1.1**

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

INT-11 — Uji koneksi baru dinyatakan sukses bila autentikasi, versi kontrak,
          schema minimum, dan identitas sumber/sekolah cocok dengan nilai
          yang diharapkan; status HTTP sukses saja tidak mencukupi.

NFR-09 — Credential dienkripsi menggunakan encrypter Laravel dan tidak
          boleh tampil pada response, session, audit, atau log.

NFR-10 — Endpoint outbound mengikuti exact deployment allowlist,
          redirect dimatikan, operasi per provider diserialisasi, dan
          kegagalan mempertahankan data lama.

NFR-11 — Credential provider harus least-privilege/read-only. Endpoint
          policy memvalidasi resolusi alamat untuk setiap koneksi dan
          menolak metadata/link-local, origin drift, proxy tak tepercaya,
          serta TLS invalid secara fail-closed.

NFR-12 — Adapter production menerapkan batas payload/page, pagination,
          timeout, retry/backoff, rate limit, concurrency, dan backpressure
          yang disahkan dalam kontrak provider.
```

Perbarui `DEP-01` dan `DEP-02`: fondasi konfigurasi boleh tersedia, tetapi uji/aktivasi production tetap terblokir sampai kontrak resmi disahkan.

- [x] **Step 4: Tambahkan riwayat versi 1.1 bertanggal 23 Agustus 2026**

- [x] **Step 4a: Buat lembar discovery kontrak per provider**

Template harus meminta dokumentasi autentikasi, base URL/origin, identitas sekolah, endpoint, method, contoh respons tersanitasi, field/type/nullability, pagination, full/partial semantics, deletion semantics, timezone, rate limit, retry, batas payload, error model, TLS, jaringan, dan bukti credential read-only. Jangan mengisi jawabannya dengan asumsi.

- [x] **Step 5: Perbarui source-of-truth pointer**

Arahkan `AGENTS.md` dan `requirements-index.md` ke v1.1, tetapi tetap dokumentasikan v1.0 sebagai arsip baseline sebelumnya.

- [x] **Step 6: Catat keputusan implementasi UI langsung**

Dokumentasikan bahwa halaman pengaturan koneksi akan dibuat pada Task 6 menggunakan komponen dan style aplikasi yang sudah ada. Tidak ada artefak Penpot baru yang wajib dibuat untuk halaman ini.

- [x] **Step 7: Verifikasi Markdown v1.1 memiliki heading, requirement ID, dan version history yang lengkap**

- [x] **Step 8: Commit**

```bash
git add AGENTS.md docs/requirements-index.md docs/requirements docs/integrations docs/api-contract.md
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
public function test_driver_adapter_and_contract_versions_are_stored_separately(): void;
public function test_verified_endpoint_policy_digest_is_persisted_separately(): void;
public function test_operation_fence_version_defaults_to_zero(): void;
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
    $table->string('expected_source_identifier', 100)->nullable();
    $table->text('credentials')->nullable();
    $table->unsignedSmallInteger('timeout_seconds')->default(30);

    $table->unsignedInteger('configuration_version')->default(0);
    $table->unsignedBigInteger('operation_fence_version')->default(0);
    $table->unsignedInteger('verified_configuration_version')->nullable();
    $table->string('verified_driver_id', 100)->nullable();
    $table->string('verified_adapter_version', 100)->nullable();
    $table->string('verified_contract_version', 100)->nullable();
    $table->string('verified_endpoint_policy_digest', 64)->nullable();

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
        'operation_fence_version' => 'integer',
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

`IntegrationProbeResult` membawa hanya metadata aman yang dibutuhkan untuk verifikasi: result code, `driver_id`, `adapter_version`, `contract_version`, reported source identifier, serta ringkasan schema/completeness. Ia tidak boleh membawa raw body atau credential.

- [ ] **Step 6: Pastikan test lulus**

```bash
php artisan test --filter IntegrationSettingTest
```

- [ ] **Step 6a: Verifikasi migration pada SQLite dan MySQL disposable**

Jalankan migration pada database SQLite test serta database MySQL disposable yang tervalidasi bukan shared/production. Bila MySQL disposable belum tersedia, hentikan Task 2 pada verification gate; jangan menunda kompatibilitas migration sampai Task 8 dan jangan memakai `migrate:fresh`, reset, atau rollback pada database shared.

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

Uji exact origin match dan penolakan user-info, query/fragment, wildcard/suffix host, port berbeda, origin di luar allowlist, driver selain whitelist, allowlist kosong, metadata/link-local/multicast/unspecified, private/loopback tanpa opt-in, jawaban DNS campuran, jawaban berubah, serta canonical endpoint-policy digest. Origin internal HTTP hanya boleh diterima bila seluruh origin tersebut tercantum eksplisit di deployment config dan private-network flag aktif.

- [ ] **Step 2: Tambahkan deployment configuration**

```env
SIBK_DAPODIK_DRIVER=unavailable
SIBK_DAPODIK_ALLOWED_ORIGINS=
SIBK_DAPODIK_ALLOW_PRIVATE_NETWORKS=false
SIBK_ETATIB_DRIVER=unavailable
SIBK_ETATIB_ALLOWED_ORIGINS=
SIBK_ETATIB_ALLOW_PRIVATE_NETWORKS=false
```

Format allowlist adalah comma-separated exact origins.

- [ ] **Step 3: Implementasikan endpoint policy**

Policy harus memerlukan URL absolut, membandingkan scheme/host/effective port, menolak username/password/query/fragment, menormalisasi URL, dan dipanggil saat save, test, activate, serta setiap penggunaan sync. Pisahkan validasi origin dari validasi target hasil resolusi DNS agar keduanya dapat diuji. Semua alamat hasil resolusi harus diperiksa tepat sebelum koneksi; metadata/link-local/multicast/unspecified selalu ditolak, sedangkan loopback/private hanya dapat dipakai bila origin tercantum exact dan flag private-network provider aktif. Driver production fase berikutnya wajib mengikat koneksi ke alamat yang telah divalidasi sambil mempertahankan Host/SNI, serta menolak jawaban DNS campuran atau berubah; resolve-then-re-resolve oleh HTTP client tidak diperbolehkan. Redirect tetap dimatikan, TLS verification tidak boleh dinonaktifkan, dan proxy environment tidak boleh dipercaya secara implisit.

Policy menyediakan digest SHA-256 atas versi implementasi policy dan konfigurasi deployment provider yang sudah dikanonisasi (allowed origins serta private-network flag). Test harus membuktikan urutan allowlist yang ekuivalen menghasilkan digest sama, sedangkan perubahan efektif menghasilkan digest berbeda.

- [ ] **Step 4: Implementasikan driver interfaces dan registry**

Registry hanya mengenal nama driver yang ditulis eksplisit di kode. Pada fase ini satu-satunya driver adalah `unavailable`. Registry dan hasil probe membawa `driver_id`, `adapter_version`, dan `contract_version` secara terpisah.

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
source_identity_mismatch
response_too_large
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
- Create: `app/Integrations/IntegrationOperationContext.php`
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
    public function active(string $provider, string $driverId, string $adapterVersion, string $contractVersion): IntegrationRuntimeConfiguration;
    public function assertCurrent(string $provider, int $configurationVersion, string $driverId, string $adapterVersion, string $contractVersion): void;
}
```

- [ ] **Step 1: Tulis failing tests state machine**

Uji first save, keep/replace/remove credential, invalid replace+remove, perubahan material, no-op save, expected source identifier kosong tetap `unconfigured` dan menolak test/activation, test sukses/gagal/stale, activation invariant, deactivation, driver/adapter/contract/allowlist drift, source identity mismatch, stale fencing token setelah lease berpindah, hard deadline, serta rollback ketika audit gagal.

- [ ] **Step 2: Implementasikan per-provider operation lock**

Gunakan cache lock per provider secara non-blocking sebagai exclusion gate. Setelah lock didapat, alokasikan token dengan mengunci row, menaikkan `operation_fence_version`, dan commit dalam transaksi singkat sebelum pekerjaan jaringan yang panjang dimulai; bawa token pada operation context. Save, test, activate, deactivate, dan sync memakai lock/context yang sama. Sebelum menerapkan hasil probe atau memulai transaksi import, kunci row dan pastikan fencing token masih current; proses lama wajib berhenti tanpa write bila lease telah berpindah. Terapkan invariant `hard operation deadline + safety margin <= lease TTL`. Adapter yang worst-case pagination/retry/import-nya tidak muat dalam deadline tidak boleh di-admit tanpa queue/lock renewal design terpisah.

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

Ambil setting/version, validasi, jalankan probe tanpa transaksi, lalu kunci row dan terapkan hasil hanya jika configuration version, driver ID, adapter version, contract version, dan endpoint-policy digest belum berubah. Simpan digest yang terverifikasi bersama hasil probe. Probe `success` hanya valid bila schema minimum dan identitas sumber yang dikembalikan cocok dengan konfigurasi. Jangan membuat sync run atau memodifikasi cache.

- [ ] **Step 5: Implementasikan activation invariant dan safe state DTO**

Blade hanya menerima provider, label, base URL, expected source identifier, timeout, boolean credential, effective state, safe test code/time, versions, adapter availability, dan capability flags. `active()` dan `assertCurrent()` menghitung ulang endpoint-policy digest dan gagal tertutup bila berbeda dari nilai yang diverifikasi.

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
dapodik[expected_source_identifier]
dapodik[api_key]
dapodik[remove_api_key]
dapodik[timeout_seconds]
dapodik[current_password]
etatib[base_url]
etatib[expected_source_identifier]
etatib[api_key]
etatib[remove_api_key]
etatib[timeout_seconds]
etatib[current_password]
```

Base URL wajib lolos endpoint policy; expected source identifier nullable max 100; API key nullable max 1000; remove boolean; key dilarang jika remove aktif; timeout 5–120. Seluruh aksi konfigurasi memerlukan step-up menggunakan rule `current_password`; password/token tidak boleh masuk old input atau log.

- [ ] **Step 3: Cegah token masuk session**

```php
$exceptions->dontFlash([
    'dapodik.api_key',
    'dapodik.current_password',
    'etatib.api_key',
    'etatib.current_password',
]);
```

- [ ] **Step 4: Implementasikan thin controller dan provider-scoped redirects**

Controller hanya mengambil actor, memanggil service, dan kembali ke `#integration-{provider}` dengan pesan Bahasa Indonesia berdasarkan safe result code.

- [ ] **Step 5: Tambahkan routes**

Gunakan route PATCH/POST yang ditetapkan di bagian Interface. Terapkan `whereIn('provider', IntegrationSetting::PROVIDERS)`.

- [ ] **Step 6: Daftarkan dan pasang rate limiter**

Maksimum lima uji per menit untuk kombinasi user ID dan provider. Route test koneksi wajib memakai named throttle middleware tersebut; test harus membuktikan request keenam ditolak.

- [ ] **Step 7: DataMasterController hanya mengirim safe states**

Response halaman dan seluruh aksi konfigurasi harus memakai `Cache-Control: no-store`; tambahkan test bahwa secret tidak muncul pada HTML, flash data, validation response, exception, atau log. Pertahankan CSRF dan security headers aplikasi; jangan melonggarkan CSP untuk halaman ini.

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

## Task 6: Halaman Pengaturan Koneksi

**Files:**

- Create: `resources/views/pages/data-master/_integration-setting.blade.php`
- Modify: `resources/views/pages/data-master/index.blade.php`
- Modify: `resources/scss/app-dashboard.scss`
- Extend: `tests/Feature/IntegrationSettingTest.php`
- Extend: `tests/Feature/FrontendPreviewTest.php`

- [ ] **Step 1: Tulis failing view tests**

Uji dua panel Admin IT, redaksi secret, pemisahan error, ID/label/ARIA unik, disabled sync button, fail-safe direct POST selama driver masih `unavailable`, serta pemisahan state koneksi dan data freshness. Guard penuh konfigurasi/version/fencing untuk direct POST diselesaikan dan diuji kembali pada Task 7.

- [ ] **Step 2: Tambahkan section setelah status sinkronisasi dan sebelum tabel log**

Susun dua panel `col-12 col-xl-6` dengan existing `sibk-panel`, form classes, badge, dan Bootstrap utilities. Pertahankan style halaman lain dan susun hierarki informasi berdasarkan urutan kerja Admin IT: ringkasan status → konfigurasi → Simpan/Uji/Aktifkan → freshness/log. Tidak perlu membuat atau merujuk desain Penpot baru.

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

Input token dan current password tidak memiliki value/old input. Tampilkan indikator boolean `Token tersimpan` dan checkbox hapus eksplisit. Tampilkan expected source identifier sebagai field non-secret dengan bantuan teks bahwa nilai harus cocok dengan identitas yang dilaporkan provider.

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
- Create: `app/Integrations/Dapodik/DapodikSnapshotValidator.php`
- Create: `app/Integrations/Etatib/EtatibSnapshotValidator.php`
- Create: `app/Integrations/IntegrationSnapshotEvidence.php`
- Modify: `app/Integrations/Dapodik/DapodikSnapshot.php`
- Modify: `app/Integrations/Etatib/EtatibSnapshot.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Services/DapodikSyncService.php`
- Modify: `app/Services/EtatibSyncService.php`
- Extend: `tests/Feature/DapodikSyncTest.php`
- Extend: `tests/Feature/EtatibSyncTest.php`

- [ ] **Step 1: Tulis failing guard tests**

Uji sync tanpa konfigurasi aktif, stale verification, unavailable driver, duplicate sync, config change during operation, invalid/oversized snapshot, Dapodik source-ID collision lintas tahun ajaran, safe failed run/audit, dan data lama tetap aktif.

- [ ] **Step 2: Implementasikan configured connector**

```php
public function fetchSnapshot(): DapodikSnapshot
{
    $driver = $this->drivers->dapodik();
    $configuration = $this->settings->active(
        IntegrationSetting::PROVIDER_DAPODIK,
        $driver->id(),
        $driver->adapterVersion(),
        $driver->contractVersion(),
    );

    $snapshot = $driver->fetchSnapshot($configuration);
    $this->validator->validate($snapshot, $configuration);

    $this->settings->assertCurrent(
        IntegrationSetting::PROVIDER_DAPODIK,
        $configuration->configurationVersion,
        $driver->id(),
        $driver->adapterVersion(),
        $driver->contractVersion(),
    );

    return $snapshot;
}
```

Terapkan pola bertipe sama untuk e-Tatib.

Driver menerapkan batas byte dan pagination sebelum dan selama mapping, lalu mengembalikan `IntegrationSnapshotEvidence` tanpa raw payload: reported source identifier, contract marker/provenance, page count, record count, dan processed byte count. Snapshot tidak boleh memakai boolean completeness tanpa evidence yang diturunkan dari kontrak resmi.

Validator harus executable dan berjalan sebelum transaksi import. Ia memeriksa evidence dan menolak collection/type/nullability yang salah, marker completeness yang tidak terbukti, source identity mismatch, limit record/payload yang dilanggar, dan identity collision. Dapodik `source_id` rombel tidak boleh memindahkan record historis ke tahun ajaran lain. Untuk e-Tatib, kontrak admission wajib menetapkan field immutable/mutable dan strategi revision provenance sebelum adapter production diaktifkan.

- [ ] **Step 3: Bind connector domain ke configured connector**

Driver internal tetap `unavailable`, sehingga tidak ada outbound production pada fase ini.

- [ ] **Step 4: Gunakan operation lock sepanjang sinkronisasi**

Lock/fencing context meliputi fetch, validation, import, reconciliation, status run, dan audit. Service harus memverifikasi fencing token di dalam transaksi sebelum mutasi cache; cache lease yang kedaluwarsa tidak boleh membuat proses lama tetap berhak menulis.

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

Driver selain `unavailable` hanya boleh ditambahkan bila tersedia dokumentasi autentikasi, endpoint, fixture sintetis, field/type/nullability, identitas sekolah/sumber, bukti credential read-only, pagination, completeness, full/partial dan deletion semantics, batas payload/page, rate limit, retry/backoff, timeout, concurrency/backpressure, outage procedure, TLS/proxy/network behavior, mapping snapshot, serta persetujuan Admin IT.

- [ ] **Step 2: Kunci aturan adapter fase berikutnya**

Adapter wajib memakai exact field resmi, menolak missing collection/type salah, mewajibkan full marker eksplisit, memvalidasi seluruh page dan identitas sumber, mematikan redirect, memvalidasi DNS lalu mengikat koneksi ke alamat tervalidasi dengan Host/SNI yang benar, menolak jawaban campuran/berubah, TLS invalid, dan proxy tak tepercaya, memakai `Http::preventStrayRequests()` pada test, membatasi ukuran respons, serta tidak menyimpan/log body.

Dapodik full snapshot tanpa tahun ajaran atau murid wajib ditolak. e-Tatib full snapshot kosong hanya boleh diterima bila kontrak resmi memberikan completeness/total terverifikasi.

- [ ] **Step 3: Dokumentasikan deployment**

Deploy Fase A dengan driver `unavailable`, isi exact origins dan kebijakan jaringan privat, cache config, simpan konfigurasi, verifikasi audit/redaksi/no-store/security headers, dan jangan aktifkan sebelum adapter resmi. Dokumentasikan `APP_PREVIOUS_KEYS` untuk rotasi key serta prosedur rotasi/revokasi credential sumber.

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

- Admin IT dapat menyimpan konfigurasi kedua provider; state tetap `blocked` ketika driver `unavailable`, atau `unconfigured` bila expected source identifier belum lengkap.
- Role lain ditolak.
- Token tidak muncul di HTML, session, audit, log, atau database plaintext.
- Test/aktivasi menampilkan `Adapter belum tersedia`.
- Tombol sync disabled dan direct POST gagal aman.
- Data lama tidak berubah.
- Desktop/tablet/ponsel konsisten dengan komponen dan style halaman aplikasi yang sudah ada, dengan hierarki informasi yang praktis dan nyaman digunakan.

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
| Credential memiliki hak tulis | Akun/token sumber least-privilege read-only dan bukti scope sebagai admission gate |
| SSRF/pengalihan credential | Exact deployment origin allowlist, pemeriksaan DNS/address class, private-network opt-in, redirect/proxy tak tepercaya dimatikan, TLS wajib valid |
| Sumber milik sekolah lain | Expected source identifier diverifikasi saat probe dan setiap sinkronisasi |
| Config/policy berubah setelah test | Configuration version, driver ID, adapter version, contract version, dan endpoint-policy digest |
| Test/sync bersamaan | Per-provider atomic lock dan version recheck |
| Lease lock kedaluwarsa saat proses lama masih berjalan | Fencing token persisten, row-lock recheck sebelum write, dan hard operation deadline di bawah lease |
| Malformed full snapshot menonaktifkan data | Validator snapshot executable dan fail-closed sebelum transaksi import |
| API mengirim banyak field | Ambil field yang dibutuhkan; jangan menyimpan raw payload |
| APP_KEY berubah | `APP_PREVIOUS_KEYS`; unreadable credential memblokir outbound |
| Duplicate-click atau penyalahgunaan test | Lock dan rate limit lima uji/menit/user/provider |
| Request sinkronisasi panjang/berlebih | Contract admission wajib menentukan pagination, payload/page limit, timeout, retry/backoff, rate limit, backpressure, dan kebutuhan queue sebelum adapter production dibuat |

## Assumptions

- Fase ini mencakup Dapodik dan e-Tatib secara simetris.
- API key/token adalah credential UI awal; schema terenkripsi berbentuk array agar autentikasi resmi dapat dikembangkan tanpa mengekspos secret.
- Tidak ada adapter HTTP nyata atau dummy production dalam fase ini.
- Fake connector tetap hanya untuk automated tests.
- PRD/SRS v1.1 menjadi source of truth baru setelah Task 1 selesai; v1.0 tetap dipertahankan sebagai arsip.
- Eksekusi direkomendasikan memakai subagent-driven development dengan review requirement, security, dan test pada setiap task.
