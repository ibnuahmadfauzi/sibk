# Fondasi Konfigurasi Koneksi Dapodik & e-Tatib Implementation Plan

> **Status: DISETUJUI UNTUK IMPLEMENTASI BERTAHAP PADA 8 SEPTEMBER 2026.** Persetujuan mencakup fondasi Fase A pada branch `integrasi-api-plan`; adapter production nyata tetap di luar scope sampai kontrak provider diterima dan lolos admission gate. Pada 9 September 2026 pengguna menetapkan bahwa baseline v1.1 cukup dalam Markdown dan halaman pengaturan koneksi diimplementasikan langsung tanpa artefak Penpot baru. Pada tanggal yang sama, pengguna menyetujui perluasan scope untuk menangani keterlambatan data Dapodik tahun ajaran baru selama 2–3 bulan melalui data persiapan sementara yang kemudian dicocokkan dengan Dapodik tanpa menggandakan atau menimpa histori BK.
>
> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **Execution gate:** Selesaikan dan verifikasi satu task sebelum berpindah ke task berikutnya. Driver production tetap `unavailable` pada seluruh task plan ini.

**Goal:** Menyediakan konfigurasi URL dan credential Dapodik/e-Tatib melalui UI Admin IT dengan alur Simpan → Uji → Aktifkan, sekaligus memastikan layanan BK tetap berjalan ketika data Dapodik tahun ajaran baru terlambat, tanpa menebak kontrak API dan tanpa membahayakan data lama.

**Architecture:** Frontend hanya mengelola konfigurasi dan memicu tindakan. Backend menyimpan credential terenkripsi, mengendalikan state konfigurasi, dan kelak menjalankan adapter provider yang memetakan payload resmi ke `DapodikSnapshot` atau `EtatibSnapshot`. Bila Dapodik terlambat, Admin IT menyiapkan tahun ajaran, rombel, dan daftar minimum NISN–nama secara sementara; Koordinator BK mengaktifkan penggunaan operasional setelah penugasan diperiksa. Snapshot Dapodik yang datang kemudian selalu melalui pratinjau pencocokan dan konfirmasi Admin IT. Fase ini mempertahankan driver production sebagai `unavailable`; adapter HTTP nyata dibuat melalui plan terpisah setelah kontrak provider tersedia.

**Tech Stack:** PHP 8.3, Laravel 13.23, Eloquent, Laravel Crypt/encrypted cast, Laravel HTTP Client untuk adapter fase berikutnya, Blade, Bootstrap 5.3, SCSS existing design system, PHPUnit 12.5.

**Spec:** PRD/SRS Aplikasi BK v1.1 yang dibuat pada Task 1 dari baseline v1.0.

## Global Constraints

- Pertahankan PRD/SRS v1.0 sebagai arsip; jangan mengubah atau menghapusnya.
- Semua PHP baru memakai `declare(strict_types=1);`.
- Hanya Admin IT aktif melalui Gate `manageDataMaster` yang boleh mengakses UI dan aksi konfigurasi integrasi, persiapan data sementara, impor daftar, pratinjau Dapodik, dan penerapan hasil pencocokan. Task 0 tetap mengikuti capability domain kasus/penugasan/murid yang sudah ditetapkan AUTH-01–AUTH-07. Aktivasi operasional tahun ajaran tetap menjadi kewenangan Koordinator BK aktif.
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
- Data persiapan sementara tidak boleh ditampilkan sebagai data resmi Dapodik, tidak boleh dikirim kembali ke Dapodik/e-Tatib, dan hanya memuat field minimum NISN, nama, rombel, periode, serta dasar resmi sekolah. Tingkat dan jurusan boleh tetap kosong sampai sumber resmi tersedia.
- Asal data (`school_provisional`, `dapodik`, atau `legacy_unclassified`) dipisahkan dari status penggunaan operasional tahun ajaran. Sinkronisasi Dapodik tidak boleh mengaktifkan atau mengganti tahun ajaran aktif secara otomatis.
- Pencocokan murid hanya otomatis melalui NISN exact. Nama tidak boleh menjadi kunci identitas. Konflik tahun ajaran, rombel, NISN, atau kepemilikan source ID ditahan untuk Admin IT.
- Penerapan hasil Dapodik menautkan source ID dan memperbarui field resmi pada baris yang telah dicocokkan; kasus, konsultasi, prestasi, penugasan, dan histori BK tidak dipindahkan atau dibuat ulang.
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

### Keadaan data ketika Dapodik terlambat

Status asal data dan status pemakaian harus dibaca sebagai dua hal berbeda:

| Keadaan | Arti |
|---|---|
| `school_provisional` | Data minimum disiapkan di Ruang BK berdasarkan daftar resmi sekolah, tetapi belum dicocokkan dengan Dapodik. |
| `dapodik` | Data telah dicocokkan dengan snapshot Dapodik yang sah dan memiliki identitas sumber. |
| `legacy_unclassified` | Data lama tanpa identitas Dapodik yang asalnya belum dapat dibuktikan; tidak boleh otomatis dianggap data persiapan baru. |
| `is_active = false` | Tahun ajaran masih disiapkan atau sudah ditutup; belum menjadi konteks kerja saat ini. |
| `is_active = true` | Tahun ajaran telah diaktifkan Koordinator BK sebagai konteks kerja saat ini. |

Aturan alur keterlambatan:

1. Admin IT membuat tahun ajaran sementara berdasarkan kalender pendidikan, SK, atau dasar resmi sekolah. Tahun ajaran baru selalu dibuat belum aktif.
2. Admin IT mengimpor CSV UTF-8 dengan header exact `nisn,nama,rombel`. Berkas maksimum 2 MiB, diproses atomik, tidak disimpan setelah request, dan satu NISN hanya boleh muncul sekali dalam satu file.
3. Murid yang NISN-nya sudah tersedia memakai baris yang sama. Murid yang belum tersedia dibuat pada cache operasional dengan `master_source=school_provisional`, tanpa `dapodik_id`. Nama resmi yang sudah terverifikasi tidak boleh ditimpa oleh CSV.
4. Rombel dan keanggotaan hasil impor juga memakai `master_source=school_provisional`. Impor hanya menambah atau memperbarui baris yang disebutkan dan tidak menonaktifkan baris yang tidak ada di file.
5. Koordinator BK memeriksa rombel dan penugasan Guru BK. Aktivasi hanya boleh dilakukan bila setiap rombel aktif memiliki penugasan yang mencakup tanggal mulai tahun ajaran. Aktivasi menonaktifkan tahun ajaran lama secara operasional tetapi tidak menghapus histori.
6. Guru BK memperoleh scope murid dari keanggotaan dan penugasan yang sama setelah tahun ajaran aktif, terlepas dari asal `school_provisional` atau `dapodik`. Semua tampilan data sementara wajib memiliki penanda yang jelas.
7. Tarik data Dapodik membuat pratinjau terkontrol dan belum mengubah cache operasional. Admin IT melihat data cocok, baru, berubah, dan konflik sebelum menerapkan hasil.
8. NISN exact dapat dicocokkan otomatis. Tahun ajaran dan rombel hanya dicocokkan otomatis bila pasangan identitasnya unik; pilihan yang meragukan wajib diputuskan Admin IT.
9. Penerapan hasil dilakukan dalam satu transaksi. Baris sementara yang cocok memperoleh `dapodik_id`, field resmi, `master_source=dapodik`, waktu konfirmasi sumber, dan audit nilai lama/baru. Relasi serta histori BK tetap memakai ID internal yang sama.
10. Snapshot penuh hanya boleh menonaktifkan baris yang sebelumnya sudah `master_source=dapodik`. Data `school_provisional` atau `legacy_unclassified` yang belum cocok tetap ditahan untuk pemeriksaan dan tidak boleh hilang otomatis.
11. Nilai `is_active` tahun ajaran dari provider tidak pernah mengaktifkan tahun ajaran Ruang BK. Aktivasi operasional hanya dilakukan Koordinator BK.
12. e-Tatib tetap hanya-baca dan menautkan record melalui NISN exact. Keterlambatan verifikasi Dapodik tidak memberi hak menulis ke e-Tatib atau Dapodik.

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

Dokumentasikan bahwa halaman pengaturan koneksi akan dibuat pada Task 9 menggunakan komponen dan style aplikasi yang sudah ada. Tidak ada artefak Penpot baru yang wajib dibuat untuk halaman ini.

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

- [x] **Step 1: Tulis failing tests persistence**

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

- [x] **Step 2: Jalankan test dan pastikan gagal karena tabel/model belum tersedia**

```bash
php artisan test --filter IntegrationSettingTest
```

- [x] **Step 3: Buat migration**

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

- [x] **Step 4: Implementasikan model**

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

- [x] **Step 5: Implementasikan DTO aman**

`IntegrationSettingState` tidak boleh memiliki plaintext/ciphertext. `IntegrationRuntimeConfiguration` boleh memuat credential tetapi tidak memiliki `toArray()`, `jsonSerialize()`, atau implementasi logging.

`IntegrationProbeResult` membawa hanya metadata aman yang dibutuhkan untuk verifikasi: result code, `driver_id`, `adapter_version`, `contract_version`, reported source identifier, serta ringkasan schema/completeness. Ia tidak boleh membawa raw body atau credential.

- [x] **Step 6: Pastikan test lulus**

```bash
php artisan test --filter IntegrationSettingTest
```

- [x] **Step 6a: Verifikasi migration pada SQLite dan MySQL disposable**

Jalankan migration pada database SQLite test serta database MySQL disposable yang tervalidasi bukan shared/production. Bila MySQL disposable belum tersedia, hentikan Task 2 pada verification gate; jangan menunda kompatibilitas migration sampai Task 12 dan jangan memakai `migrate:fresh`, reset, atau rollback pada database shared.

- [x] **Step 7: Commit**

```bash
git add database/migrations app/Models/IntegrationSetting.php app/Integrations tests/Feature/IntegrationSettingTest.php
git commit -m "feat: add encrypted integration setting state"
```

**Gate selesai 9 September 2026:** commits `3ef085a` dan `b0a1e85`; focused tests 11/11 dengan 43 assertion, migration SQLite serta migration additive/schema MySQL pada database uji `sibk_uji`, Pint, dan diff-check lulus. Re-review memastikan credential tidak bocor melalui debug/export/JSON/log context dan native serialization ditolak.

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

- [x] **Step 1: Tulis failing tests URL policy**

Uji exact origin match dan penolakan user-info, query/fragment, wildcard/suffix host, port berbeda, origin di luar allowlist, driver selain whitelist, allowlist kosong, metadata/link-local/multicast/unspecified, private/loopback tanpa opt-in, jawaban DNS campuran, jawaban berubah, serta canonical endpoint-policy digest. Origin internal HTTP hanya boleh diterima bila seluruh origin tersebut tercantum eksplisit di deployment config dan private-network flag aktif.

- [x] **Step 2: Tambahkan deployment configuration**

```env
SIBK_DAPODIK_DRIVER=unavailable
SIBK_DAPODIK_ALLOWED_ORIGINS=
SIBK_DAPODIK_ALLOW_PRIVATE_NETWORKS=false
SIBK_ETATIB_DRIVER=unavailable
SIBK_ETATIB_ALLOWED_ORIGINS=
SIBK_ETATIB_ALLOW_PRIVATE_NETWORKS=false
```

Format allowlist adalah comma-separated exact origins.

- [x] **Step 3: Implementasikan endpoint policy**

Policy harus memerlukan URL absolut, membandingkan scheme/host/effective port, menolak username/password/query/fragment, menormalisasi URL, dan dipanggil saat save, test, activate, serta setiap penggunaan sync. Pisahkan validasi origin dari validasi target hasil resolusi DNS agar keduanya dapat diuji. Semua alamat hasil resolusi harus diperiksa tepat sebelum koneksi; metadata/link-local/multicast/unspecified selalu ditolak, sedangkan loopback/private hanya dapat dipakai bila origin tercantum exact dan flag private-network provider aktif. Driver production fase berikutnya wajib mengikat koneksi ke alamat yang telah divalidasi sambil mempertahankan Host/SNI, serta menolak jawaban DNS campuran atau berubah; resolve-then-re-resolve oleh HTTP client tidak diperbolehkan. Redirect tetap dimatikan, TLS verification tidak boleh dinonaktifkan, dan proxy environment tidak boleh dipercaya secara implisit.

Policy menyediakan digest SHA-256 atas versi implementasi policy dan konfigurasi deployment provider yang sudah dikanonisasi (allowed origins serta private-network flag). Test harus membuktikan urutan allowlist yang ekuivalen menghasilkan digest sama, sedangkan perubahan efektif menghasilkan digest berbeda.

- [x] **Step 4: Implementasikan driver interfaces dan registry**

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

- [x] **Step 5: Pastikan driver unavailable tidak menghasilkan outbound request**

- [x] **Step 6: Jalankan test**

```bash
php artisan test tests/Unit/IntegrationEndpointPolicyTest.php
php artisan test tests/Unit/IntegrationDriverRegistryTest.php
```

- [x] **Step 7: Commit**

```bash
git add config/sibk.php .env.example app/Integrations tests/Unit
git commit -m "feat: gate integration drivers with deployment policy"
```

**Gate selesai 9 September 2026:** commits `07f2fb8`, `bdd7ab0`, dan `dbc751f`; endpoint policy 84/84 test (92 assertion), registry/probe 10/10 (22), regresi penyimpanan 11/11 (43), Pint, dan diff-check lulus. Review memastikan alamat non-global/special-use, literal/format numerik tersamar, hasil DNS campuran/berubah, serta kode hasil di luar daftar aman ditolak; driver `unavailable` tidak membuat koneksi keluar.

---

## Task 4: Baseline Keterlambatan Dapodik

**Files:**

- Create: `CONTEXT.md`
- Create: `docs/adr/0001-separate-data-verification-from-operational-activation.md`
- Modify: `docs/requirements/PRD_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements/SRS_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements-index.md`
- Modify: `docs/api-contract.md`
- Modify: `AGENTS.md`

- [x] **Step 1: Tetapkan istilah yang tidak tumpang tindih**

Catat istilah `data persiapan sementara`, `terverifikasi Dapodik`, `aktivasi operasional`, dan `pratinjau pencocokan`. Hindari menyebut data persiapan sebagai data resmi atau master alternatif.

- [x] **Step 2: Tambahkan keputusan produk ke PRD v1.1**

Dokumentasikan masalah keterlambatan 2–3 bulan, pembagian tanggung jawab Admin IT/Koordinator/Guru BK, penanda data sementara, impor daftar minimum, penggunaan langsung untuk layanan BK, pratinjau sebelum penerapan Dapodik, dan jaminan histori BK tidak ditimpa. Tegaskan tidak ada write-back ke Dapodik/e-Tatib.

- [x] **Step 3: Tambahkan requirement MD-05 sampai MD-12 dan NFR-13 ke SRS v1.1**

Requirement mencakup pembuatan tahun ajaran sementara oleh Admin IT berdasarkan dasar resmi sekolah, impor minimum, pemisahan status asal dan status aktif, aktivasi oleh Koordinator setelah penugasan lengkap, scope Guru BK pada data sementara, pratinjau/konfirmasi Dapodik, pencocokan NISN exact, penahanan konflik, pemeliharaan ID internal/histori, serta validasi dan pemrosesan impor secara atomik.

- [x] **Step 4: Perbarui kontrak endpoint dan Service Layer**

Kontrak web minimum:

```text
POST /data-master/academic-years
POST /data-master/academic-years/{academicYear}/roster-imports
POST /assignments/academic-years/{academicYear}/activate
POST /data-master/dapodik/sync
GET  /data-master/dapodik/previews/{syncRun}
PATCH /data-master/dapodik/previews/{syncRun}/items/{item}
POST /data-master/dapodik/previews/{syncRun}/apply
```

`POST /data-master/dapodik/sync` kelak hanya mengambil, memvalidasi, dan menyiapkan pratinjau. Cache operasional baru berubah pada endpoint `apply` setelah konfirmasi Admin IT.

- [x] **Step 5: Catat keputusan arsitektur**

Status verifikasi sumber dipisahkan dari `is_active` operasional. Data sementara memakai ID internal yang sama dan diubah menjadi terverifikasi dengan menempelkan identitas sumber setelah pencocokan, sehingga seluruh relasi BK tetap utuh.

- [x] **Step 6: Verifikasi dokumen**

```bash
rg "MD-05|MD-12|NFR-13|data persiapan sementara|pratinjau pencocokan" CONTEXT.md AGENTS.md docs/requirements docs/api-contract.md
git diff --check
```

- [x] **Step 7: Commit**

```bash
git add CONTEXT.md AGENTS.md docs/adr docs/requirements docs/requirements-index.md docs/api-contract.md docs/superpowers/plans/2026-08-23-konfigurasi-koneksi-integrasi.md
git commit -m "docs: define delayed Dapodik fallback workflow"
```

---

## Task 5: Penyimpanan dan Aturan Data Persiapan

**Files:**

- Create: `database/migrations/2026_09_09_000100_add_master_source_to_dapodik_cache.php`
- Modify: `app/Models/AcademicYear.php`
- Modify: `app/Models/Classroom.php`
- Modify: `app/Models/Student.php`
- Modify: `app/Models/StudentClassMembership.php`
- Create: `app/Services/AcademicYearPreparationService.php`
- Create: `app/Services/ProvisionalRosterCsvParser.php`
- Create: `app/Services/ProvisionalRosterImportResult.php`
- Test: `tests/Feature/DelayedDapodikPreparationTest.php`

- [x] **Step 1: Tulis failing tests domain persiapan**

Uji pembuatan tahun ajaran belum aktif, nama/periode unik, dasar resmi wajib, penanda `school_provisional`, pemisahan asal data dari `is_active`, backfill data lama yang aman, exact-NISN terhadap Student `dapodik` dan `legacy_unclassified` tanpa reklasifikasi/putus relasi BK, duplicate local NISN yang gagal tertutup, dan audit tersanitasi. Uji bahwa Admin IT tidak dapat mengaktifkan tahun ajaran melalui module ini.

- [x] **Step 2: Tambahkan status verifikasi secara additive**

Tambahkan `master_source` (`school_provisional|dapodik|legacy_unclassified`) dan `source_confirmed_at` pada `academic_years`, `classrooms`, `students`, dan `student_class_memberships`. Backfill baris lama yang memiliki `dapodik_id` menjadi `dapodik` dan baris tanpa identitas sumber menjadi `legacy_unclassified`; tidak ada baris lama yang otomatis dianggap persiapan baru. Tambahkan `prepared_by`, `preparation_reference`, `activated_by`, serta `activated_at` pada tahun ajaran. Jangan gunakan enum/check database agar SQLite dan MySQL konsisten. Migrasi lama tidak diubah.

- [x] **Step 3: Implementasikan module persiapan tahun ajaran**

Interface publik minimum:

```php
public function prepareAcademicYear(array $data, User $actor): AcademicYear;
public function importRoster(AcademicYear $academicYear, UploadedFile $file, User $actor): ProvisionalRosterImportResult;
public function activate(AcademicYear $academicYear, User $actor): AcademicYear;
```

`prepareAcademicYear` dan `importRoster` hanya menerima Admin IT aktif. `activate` hanya menerima Koordinator BK aktif. Pemeriksaan dilakukan kembali pada Service Layer, bukan hanya controller/policy.

- [x] **Step 4: Implementasikan parser CSV ketat**

Terima hanya CSV UTF-8 maksimum 2 MiB dengan header exact `nisn,nama,rombel`. NISN wajib 10 digit; nama dan rombel wajib. Tolak BOM selain UTF-8, baris ekstra/tidak lengkap, NISN duplikat, formula/control character pada field teks, jumlah baris melebihi 5.000, dan file yang tidak dapat diparse. Jangan menyimpan file mentah.

- [x] **Step 5: Import atomik dan idempotent**

Validasi seluruh file sebelum transaksi. Pencocokan murid mensyaratkan satu NISN exact dan tepat satu kandidat lokal; nol kandidat membuat `Student` baru berstatus `school_provisional`, sedangkan lebih dari satu kandidat ditolak sebagai konflik walaupun database normalnya memiliki unique constraint. Jika `Student` sudah ada—baik `dapodik`, `legacy_unclassified`, maupun `school_provisional`—impor tidak mengubah nama, `dapodik_id`, `master_source`, atau field konfirmasi sumbernya; impor hanya menambah keanggotaan persiapan pada ID internal yang sama. Aturan yang sama mempertahankan provenance tahun/rombel yang sudah ada. Buat atau perbarui hanya baris baru yang memang berasal dari daftar persiapan tanpa menonaktifkan data lain. Pasangan tahun–rombel wajib konsisten. Impor persiapan hanya boleh dilakukan ketika tahun belum aktif. Semua hasil dan perubahan dicatat pada audit tanpa isi file mentah.

- [x] **Step 6: Aktivasi operasional yang aman**

Koordinator hanya dapat mengaktifkan tahun ajaran bila tanggal lengkap, terdapat rombel dan murid aktif, serta setiap rombel memiliki tepat satu penugasan Guru BK yang mencakup tanggal mulai tahun ajaran. Aktivasi menutup `is_active` tahun ajaran lain, tidak menghapus histori, dan tidak mengubah `master_source`. Scope Guru BK wajib memeriksa `academic_years.is_active=true`, sehingga penugasan yang disiapkan lebih awal belum membuka akses murid sebelum aktivasi.

- [x] **Step 7: Verifikasi migration dan test**

```bash
php artisan test --filter DelayedDapodikPreparationTest
php artisan test --filter AssignmentManagementTest
php vendor/bin/pint --test
git diff --check
```

Jalankan migration additive pada SQLite test dan MySQL disposable yang telah divalidasi sebagai database uji. Jangan memakai `migrate:fresh`, rollback, atau reset pada database shared.

- [x] **Step 8: Commit**

```bash
git add database/migrations app/Models app/Services tests/Feature/DelayedDapodikPreparationTest.php
git commit -m "feat: prepare provisional academic year data"
```

---

## Task 6: Halaman Persiapan Tahun Ajaran

**Files:**

- Create: `app/Http/Requests/Admin/StoreProvisionalAcademicYearRequest.php`
- Create: `app/Http/Requests/Admin/ImportProvisionalRosterRequest.php`
- Create: `app/Http/Controllers/Admin/AcademicYearPreparationController.php`
- Create: `app/Http/Controllers/AcademicYearActivationController.php`
- Modify: `app/Http/Controllers/Admin/DataMasterController.php`
- Modify: `app/Http/Controllers/AssignmentController.php`
- Modify: `routes/web.php`
- Create: `resources/views/pages/data-master/_academic-year-preparation.blade.php`
- Modify: `resources/views/pages/data-master/index.blade.php`
- Modify: `resources/views/pages/assignments/classes/manage.blade.php`
- Extend: `tests/Feature/DelayedDapodikPreparationTest.php`
- Extend: `tests/Feature/AuthorizationMatrixTest.php`
- Extend: `tests/Feature/FrontendPreviewTest.php`

- [x] **Step 1: Tulis failing authorization dan validation tests**

Uji guest, Guru BK, Koordinator, Waka, Admin IT nonaktif, dan Admin IT aktif pada create/import. Uji aktivasi hanya untuk Koordinator aktif. Direct request dengan role salah, tahun lain, pasangan tahun–rombel palsu, file terlalu besar, atau field invalid harus ditolak server.

- [x] **Step 2: Tambahkan alur Admin IT pada Data Master**

Susun section dengan pola `sibk-panel`: status tahun ajaran → buat tahun sementara → impor daftar → ringkasan isi dan penanda Sementara/Terverifikasi Dapodik. Form tidak memakai JavaScript baru dan tidak menampilkan isi file setelah validation error.

- [x] **Step 3: Tambahkan alur Koordinator pada Penugasan**

Tampilkan penanda status sumber, daftar kesiapan rombel, penugasan yang belum lengkap, serta tombol `Aktifkan Tahun Ajaran` hanya ketika prasyarat domain terpenuhi. Controller tetap tipis dan Service mengulang seluruh pemeriksaan.

- [x] **Step 4: Pastikan Guru BK langsung memperoleh scope**

Sebelum aktivasi, penugasan yang masih disiapkan tidak boleh membuka pencarian, profil, atau aksi layanan murid kepada Guru BK. Setelah Koordinator mengaktifkan dan penugasan efektif, murid hasil impor muncul pada pencarian/form Guru BK yang ditugaskan dan tetap ditolak bagi Guru BK kelas lain. Uji aksi Service/endpoint sebenarnya—bukan hanya tampilan—untuk membuat, membaca, dan memperbarui kasus serta konsultasi, dan membuat prestasi: Guru yang ditugaskan berhasil hanya setelah aktivasi; sebelum aktivasi dan untuk kelas lain harus ditolak. Penanda Sementara tampil pada daftar, profil, dan form tanpa mengubah aturan akses atau memperluas akses Waka/Admin IT ke isi layanan BK.

- [x] **Step 5: Jalankan verification**

```bash
php artisan test --filter DelayedDapodikPreparationTest
php artisan test --filter AuthorizationMatrixTest
php artisan test --filter CaseManagementTest
php artisan test --filter ConsultationManagementTest
php artisan test --filter AchievementManagementTest
npm run check:frontend
npm run build
git diff --check
```

- [x] **Step 6: Commit**

```bash
git add app/Http routes/web.php resources/views tests/Feature
git commit -m "feat: add provisional academic year workflow"
```

---

## Task 7: Deep Module Konfigurasi, Audit, dan Concurrency

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

- [x] **Step 1: Tulis failing tests state machine**

Uji first save, keep/replace/remove credential, invalid replace+remove, perubahan material, no-op save, expected source identifier kosong tetap `unconfigured` dan menolak test/activation, test sukses/gagal/stale, activation invariant, deactivation, driver/adapter/contract/allowlist drift, source identity mismatch, stale fencing token setelah lease berpindah, hard deadline, serta rollback ketika audit gagal.

- [x] **Step 2: Implementasikan per-provider operation lock**

Gunakan cache lock per provider secara non-blocking sebagai exclusion gate. Setelah lock didapat, alokasikan token dengan mengunci row, menaikkan `operation_fence_version`, dan commit dalam transaksi singkat sebelum pekerjaan jaringan yang panjang dimulai; bawa token pada operation context. Save, test, activate, deactivate, dan sync memakai lock/context yang sama. Sebelum menerapkan hasil probe atau memulai transaksi import, kunci row dan pastikan fencing token masih current; proses lama wajib berhenti tanpa write bila lease telah berpindah. Terapkan invariant `hard operation deadline + safety margin <= lease TTL`. Adapter yang worst-case pagination/retry/import-nya tidak muat dalam deadline tidak boleh di-admit tanpa queue/lock renewal design terpisah.

- [x] **Step 3: Implementasikan save transaction dan audit tersanitasi**

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

- [x] **Step 4: Implementasikan connection test**

Ambil setting/version, validasi, jalankan probe tanpa transaksi, lalu kunci row dan terapkan hasil hanya jika configuration version, driver ID, adapter version, contract version, dan endpoint-policy digest belum berubah. Simpan digest yang terverifikasi bersama hasil probe. Probe `success` hanya valid bila schema minimum dan identitas sumber yang dikembalikan cocok dengan konfigurasi. Jangan membuat sync run atau memodifikasi cache.

- [x] **Step 5: Implementasikan activation invariant dan safe state DTO**

Blade hanya menerima provider, label, base URL, expected source identifier, timeout, boolean credential, effective state, safe test code/time, versions, adapter availability, dan capability flags. `active()` dan `assertCurrent()` menghitung ulang endpoint-policy digest dan gagal tertutup bila berbeda dari nilai yang diverifikasi.

- [x] **Step 6: Jalankan tests**

```bash
php artisan test --filter IntegrationSettingTest
```

- [x] **Step 7: Commit**

```bash
git add app/Integrations app/Services/IntegrationSettingService.php tests/Feature/IntegrationSettingTest.php
git commit -m "feat: add audited integration configuration lifecycle"
```

---

## Task 8: Admin Endpoints dan Secret-Safe Validation

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

## Task 9: Halaman Pengaturan Koneksi

**Files:**

- Create: `resources/views/pages/data-master/_integration-setting.blade.php`
- Modify: `resources/views/pages/data-master/index.blade.php`
- Modify: `resources/scss/app-dashboard.scss`
- Extend: `tests/Feature/IntegrationSettingTest.php`
- Extend: `tests/Feature/FrontendPreviewTest.php`

- [ ] **Step 1: Tulis failing view tests**

Uji dua panel Admin IT, redaksi secret, pemisahan error, ID/label/ARIA unik, disabled sync button, fail-safe direct POST selama driver masih `unavailable`, serta pemisahan state koneksi dan data freshness. Guard penuh konfigurasi/version/fencing untuk direct POST diselesaikan dan diuji kembali pada Task 10.

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

## Task 10: Guarded Connector dan Sinkronisasi Terkunci

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

Uji configured connector tanpa konfigurasi aktif, stale verification, unavailable driver, config change selama fetch, invalid/oversized snapshot, Dapodik source-ID collision lintas tahun ajaran, safe failure/audit, direct POST Dapodik yang masih gagal tertutup, dan data lama tetap aktif. Uji duplicate apply, expiry, serta state pratinjau baru ditambahkan pada Task 11 setelah module tersebut tersedia.

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

Driver internal tetap `unavailable`, sehingga tidak ada outbound production pada fase ini. Hasil Dapodik kelak diteruskan ke module pratinjau pada Task 11, bukan langsung ke transaksi import. Sampai Task 11 selesai, direct POST Dapodik tetap gagal tertutup. e-Tatib tetap memakai alur sinkronisasi tervalidasi karena tidak mengubah status tahun ajaran/rombel.

- [ ] **Step 4: Sediakan operation lock untuk seluruh operasi provider**

Lock/fencing context pada task ini meliputi fetch, validation, import e-Tatib, status run, dan audit. Configured Dapodik connector menghasilkan snapshot tervalidasi, tetapi belum memiliki endpoint yang mengimpor atau membuat preview. Interface operation context harus dapat dipakai Task 11 untuk memperluas cakupan ke pembuatan pratinjau, penerapan, rekonsiliasi, dan audit. Service memverifikasi fencing token di dalam transaksi sebelum setiap mutasi; cache lease yang kedaluwarsa tidak boleh membuat proses lama tetap berhak menulis.

- [ ] **Step 5: Perlakukan configuration/busy exception sebagai expected integration failure**

Jangan `report()` expected failure dan jangan tampilkan secret/raw response.

- [ ] **Step 6: Pertahankan seluruh test domain lama**

- [ ] **Step 7: Jalankan tests**

```bash
php artisan test tests/Feature/DapodikSyncTest.php
php artisan test --filter DelayedDapodikPreparationTest
php artisan test tests/Feature/EtatibSyncTest.php
php artisan test --filter IntegrationSettingTest
```

- [ ] **Step 8: Commit**

```bash
git add app/Integrations app/Providers app/Services tests/Feature
git commit -m "feat: enforce verified integration settings during sync"
```

---

## Task 11: Pratinjau dan Penerapan Dapodik

**Files:**

- Create: `database/migrations/2026_09_09_000200_create_dapodik_sync_preview_items.php`
- Create: `app/Models/DapodikSyncPreviewItem.php`
- Create: `app/Services/DapodikReconciliationService.php`
- Create: `app/Http/Requests/Admin/MapDapodikPreviewItemRequest.php`
- Create: `app/Http/Controllers/Admin/DapodikReconciliationController.php`
- Modify: `app/Services/DapodikSyncService.php`
- Modify: `app/Services/StudentIdentityService.php`
- Modify: `app/Services/EtatibSyncService.php`
- Modify: `app/Models/ExternalSyncRun.php`
- Modify: `app/Http/Controllers/Admin/DataMasterController.php`
- Modify: `routes/web.php`
- Create: `resources/views/pages/data-master/dapodik-preview.blade.php`
- Extend: `tests/Feature/DapodikSyncTest.php`
- Extend: `tests/Feature/DelayedDapodikPreparationTest.php`
- Extend: `tests/Feature/EtatibSyncTest.php`

- [ ] **Step 1: Tulis failing tests pratinjau tanpa mutasi**

Uji bahwa snapshot hanya dapat diambil melalui configured connector, validator, dan operation lock Task 10. Hasilnya hanya membuat run serta item pratinjau ter-normalisasi; cache tahun, rombel, murid, keanggotaan, penugasan, dan data BK belum berubah sebelum Admin IT menerapkan hasil. Driver `unavailable`, konfigurasi tidak aktif, atau lock sibuk harus gagal tertutup tanpa membuat pratinjau palsu.

- [ ] **Step 2: Buat pratinjau immutable dan klasifikasi pencocokan**

Setiap item memiliki salah satu hasil aman: `exact_match`, `new_record`, `changed`, `needs_mapping`, atau `conflict`. `exact_match` murid hanya sah bila satu NISN valid muncul tepat sekali pada snapshot dan menghasilkan tepat satu kandidat `Student` lokal. Nol kandidat menjadi `new_record`; lebih dari satu kandidat atau konflik NISN/source ID menjadi `conflict` dan tidak dapat dipaksa melalui UI. Pertahankan unique constraint NISN dan tambahkan pemeriksaan aplikasi untuk data historis yang rusak.

Simpan hanya field snapshot internal minimum yang sudah divalidasi, bukan body mentah. Run menyimpan fingerprint seluruh snapshot, nomor generasi, configuration version, driver ID, adapter version, contract version, endpoint-policy digest, waktu kedaluwarsa, dan waktu apply. Item menyimpan jenis entitas, source ID, kandidat ID internal, status, field aman, hash item ter-normalisasi, generasi preview, decision revision, keputusan Admin IT, dan waktu keputusan.

- [ ] **Step 3: Sediakan pemetaan manual terbatas**

Admin IT dapat memilih kandidat tahun/rombel `school_provisional` yang belum dikonfirmasi atau memilih membuat baris resmi baru. Kandidat wajib berada pada konteks tahun yang benar. Murid tidak dapat dipetakan berdasarkan nama. Konflik NISN/source ID tidak dapat dipaksa melalui UI. Pembuatan ulang preview menaikkan generasi, membatalkan keputusan lama, dan dicatat pada audit.

- [ ] **Step 4: Terapkan hasil secara atomik setelah konfirmasi**

Kunci run dan seluruh baris target. Tolak run yang bukan milik Dapodik, sudah diterapkan, kedaluwarsa, bukan generasi terbaru, belum lengkap, masih memiliki konflik, atau konfigurasi/policy/fencing/fingerprint/hash item/decision revision/row target telah berubah. Tempelkan `dapodik_id`, field resmi, `master_source=dapodik`, `source_confirmed_at`, dan `synced_at` pada ID internal yang sama. Nama sementara yang berubah disimpan pada audit. Jangan mengubah `is_active` tahun ajaran.

- [ ] **Step 5: Lindungi provenance dan data yang belum cocok**

Impor roster maupun apply tidak pernah mengubah `master_source` Student `dapodik` atau `legacy_unclassified` menjadi `school_provisional`. Snapshot penuh hanya menonaktifkan record `master_source=dapodik` yang terbukti hilang. Record `school_provisional` atau `legacy_unclassified` yang belum cocok tetap aktif sesuai keputusan operasional dan muncul sebagai masalah yang perlu diperiksa. Gagal atau rollback mempertahankan cache lama dan histori BK.

- [ ] **Step 6: Pertahankan penautan identitas dan e-Tatib**

Identitas kasus sementara dan record e-Tatib dicocokkan kembali melalui NISN exact setelah penerapan. Pesan UI membedakan `belum terverifikasi Dapodik` dari `NISN tidak ditemukan`; tidak ada write-back ke provider. Test harus membuktikan `students.id` dan seluruh foreign key kasus, konsultasi, tindak lanjut, prestasi, serta penugasan tetap sama sebelum/sesudah apply.

- [ ] **Step 7: Jalankan verification**

```bash
php artisan test tests/Feature/DapodikSyncTest.php
php artisan test --filter DelayedDapodikPreparationTest
php artisan test tests/Feature/EtatibSyncTest.php
php artisan test --filter CaseManagementTest
php artisan test --filter ConsultationManagementTest
php artisan test --filter AssignmentManagementTest
php vendor/bin/pint --test
git diff --check
```

- [ ] **Step 8: Commit**

```bash
git add database/migrations app/Models app/Services app/Http routes/web.php resources/views/pages/data-master tests/Feature
git commit -m "feat: reconcile provisional data with Dapodik preview"
```

---

## Task 12: Contract Admission, Dokumentasi Operasional, dan Release Gate

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

Deploy Fase A dengan driver `unavailable`, isi exact origins dan kebijakan jaringan privat, cache config, simpan konfigurasi, verifikasi audit/redaksi/no-store/security headers, dan jangan aktifkan sebelum adapter resmi. Dokumentasikan `APP_PREVIOUS_KEYS` untuk rotasi key serta prosedur rotasi/revokasi credential sumber. Tambahkan panduan awam untuk menyiapkan tahun ajaran sementara, mengimpor daftar, melengkapi penugasan, mengaktifkan operasional, dan kelak memeriksa pratinjau Dapodik.

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
- Admin IT dapat menyiapkan tahun ajaran dan mengimpor daftar sementara meskipun driver Dapodik belum tersedia.
- Koordinator dapat melihat kekurangan penugasan dan mengaktifkan tahun ajaran setelah seluruh syarat terpenuhi.
- Murid sementara hanya tampil untuk Guru BK yang mendapat kelasnya dan seluruh tampilan memberi penanda `Sementara`.
- Pratinjau Dapodik tidak mengubah data; apply mempertahankan ID internal, penugasan, dan histori BK.
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
| Dapodik tahun baru terlambat 2–3 bulan | Admin IT menyiapkan data minimum berstatus sementara; Koordinator mengaktifkan setelah penugasan lengkap |
| Data sementara dianggap resmi | Badge/status asal data wajib pada data master, penugasan, pencarian, dan profil murid |
| Sinkronisasi menggandakan murid/rombel | Pratinjau dan konfirmasi; NISN exact; pemetaan tahun/rombel terbatas; tempel source ID pada ID internal yang sama |
| Dapodik mengganti tahun aktif tanpa keputusan sekolah | Status verifikasi dipisahkan dari aktivasi operasional; `is_active` tahun ajaran tidak diambil dari snapshot |
| API mengirim banyak field | Ambil field yang dibutuhkan; jangan menyimpan raw payload |
| APP_KEY berubah | `APP_PREVIOUS_KEYS`; unreadable credential memblokir outbound |
| Duplicate-click atau penyalahgunaan test | Lock dan rate limit lima uji/menit/user/provider |
| Request sinkronisasi panjang/berlebih | Contract admission wajib menentukan pagination, payload/page limit, timeout, retry/backoff, rate limit, backpressure, dan kebutuhan queue sebelum adapter production dibuat |

## Assumptions

- Fase ini mencakup Dapodik dan e-Tatib secara simetris.
- API key/token adalah credential UI awal; schema terenkripsi berbentuk array agar autentikasi resmi dapat dikembangkan tanpa mengekspos secret.
- Tidak ada adapter HTTP nyata atau dummy production dalam fase ini.
- Fake connector tetap hanya untuk automated tests.
- Daftar persiapan sementara berasal dari dokumen/daftar resmi internal sekolah dan bukan hasil input bebas Guru BK.
- Impor data sementara bersifat menambah atau memperbarui baris yang disebutkan; baris yang tidak ada pada file tidak dinonaktifkan otomatis.
- Aktivasi operasional tahun ajaran adalah keputusan Koordinator BK dan tetap terpisah dari verifikasi Dapodik.
- PRD/SRS v1.1 menjadi source of truth baru setelah Task 1 selesai; v1.0 tetap dipertahankan sebagai arsip.
- Eksekusi direkomendasikan memakai subagent-driven development dengan review requirement, security, dan test pada setiap task.
