# Ruang BK — API & Service Contract

Status: **AKTIF — IMPLEMENTASI BACKEND**

Dokumen ini mendefinisikan kontrak endpoint, input, output, otorisasi, dan penanganan data untuk modul-modul P0 Ruang BK.

Sumber perilaku aktif: PRD dan SRS v1.1, termasuk amandemen keterlambatan Dapodik 9 September 2026 serta amandemen alur operasional BK 12 September 2026.

## Kontrak Bersama Pengembangan Paralel

Kontrak berikut dibekukan sebelum Jalur A, B, dan C mulai bekerja:

- Status kasus dan konsultasi memakai kode `baru`, `sedang_diproses`, `membutuhkan_tindak_lanjut`, `selesai`, dan `dibatalkan` dari `App\Support\ServiceRecordStatus`.
- `selesai` dan `dibatalkan` adalah status terminal. Mutasi biasa ditolak oleh policy dan service; perubahan sesudahnya hanya melalui koreksi operasional terverifikasi.
- Kode registrasi kasus tetap dibuat dan disimpan backend, tetapi tidak menjadi field response/view pengguna, parameter pencarian pengguna, kolom laporan/CSV, teks notifikasi, atau ringkasan audit yang terlihat pengguna.
- Data master memuat seluruh murid aktif beserta penempatan tahun ajarannya. Kasus/konsultasi hanya dibuat ketika pelayanan benar-benar terjadi.
- Route baru lifecycle pelayanan dimiliki `routes/bk-services.php`; route portal Waka dimiliki `routes/waka.php`. Keduanya dimuat di dalam middleware `auth` dan `account.active`.
- Jalur B memakai nama route `cases.edit` untuk `GET /cases/{case}/edit` dan `cases.update` untuk `PATCH /cases/{case}`.
- Jalur C memakai `waka.monitoring.students` untuk `GET /waka/students-with-cases` dan `waka.monitoring.handling` untuk `GET /waka/handling-reports`.
- Filter portal Waka hanya `period` (`YYYY-MM`) dan `status`. Sorting menerima `sort`, `direction`, dan `page`; `sort` hanya boleh `student`, `classroom`, `service_field`, `status`, `counselor`, atau `service_date`, sedangkan `direction` hanya `asc` atau `desc`.
- Detail kasus nonterkoordinasi tetap ditolak. Route portal Waka tidak memperluas scope model umum dan tidak menyediakan tindakan mutasi.

### Perangkat Pengujian RBAC Penelitian

- **Reset command:** `php artisan rbac:scenario-reset` dengan opsi `--force` untuk eksekusi noninteraktif. Reset memvalidasi baseline sebelum menerbitkan CSV.
- **Verify command:** `php artisan rbac:scenario-verify` bersifat read-only dan memeriksa baseline database serta CSV terbaru; opsi `--csv=path` memilih lembar tertentu.
- **Environment:** hanya `local` dan `testing`; pemanggilan pada production gagal tanpa membuat atau menghapus data.
- **Credential:** memakai `SIBK_SEED_ACCOUNT_PASSWORD` yang wajib minimal delapan karakter dan tidak pernah dicetak ke terminal/CSV.
- **Efek:** menghapus lalu membuat ulang hanya aktor dan resource penelitian berpenanda/kepemilikan RBAC dalam satu transaksi.
- **Output:** lembar hasil pada `storage/app/testing/rbac-results-YYYYMMDD-HHMMSS.csv` berisi versi dataset, tanggal baseline, label/prasyarat resource, URL/primary key aktual, serta marker yang wajib tampil atau disembunyikan.
- **Seeder:** `AuthorizationScenarioSeeder` tidak dipanggil oleh `DatabaseSeeder`; production seeding tetap hanya membuat role dan referensi.
- **Model otorisasi:** capability global diperiksa oleh Gate/Policy, sedangkan hak atas data diperiksa oleh query scope, periode efektif, penugasan, kepemilikan, koordinasi, dan policy per objek. Tidak tersedia tabel maupun endpoint permission generik.
- **Kontrak HTTP:** perangkat penelitian tidak menambah endpoint. Semua skenario memakai endpoint aplikasi yang sudah ada agar enforcement server yang sesungguhnya ikut diuji.

---

## 1. Modul Autentikasi & Akun (`AUTH`, `ACC`)

### Login & Sesi
- **Endpoint:** `POST /login`
- **Controller:** `AuthController@login`
- **Request:** `email` (string, required), `password` (string, required), `remember` (boolean, optional).
- **Authorization:** Publik / Pengguna Aktif (`EnsureActiveUser`).
- **Response:** Redirect ke `/dashboard` atau HTTP 200/422 dengan pesan error.
- **Audit:** Mencatat waktu login pengguna dan IP address.

### Logout
- **Endpoint:** `POST /logout`
- **Controller:** `AuthController@destroy`
- **Authorization:** Pengguna aktif dengan sesi sah.
- **Response:** Mengakhiri sesi, meregenerasi token CSRF, lalu redirect ke `/login`.

### Pengelolaan Akun Admin IT
- **Endpoint:** `GET /admin/users`, `POST /admin/users`, `PATCH /admin/users/{user}`
- **Controller:** `Admin\\UserManagementController`
- **Authorization:** `UserPolicy`; hanya role aktif `admin_it`.
- **Negosiasi respons:** request browser biasa menerima halaman Blade dan redirect dengan flash message; request dengan `Accept: application/json` tetap menerima JSON untuk integrasi/test.
- **Request buat:** `name`, `email`, `password`, `password_confirmation`, `roles[]` (slug role), `is_active` (opsional).
- **Request ubah:** Field yang berubah dari `name`, `email`, `password`, `password_confirmation`, `roles[]`, dan `is_active`.
- **Response:** JSON berisi pesan dan data akun tanpa password atau token sesi.
- **Business Logic:** `AccountService` menyimpan perubahan akun dan sinkronisasi multi-role dalam transaksi serta membuat audit otomatis.
- **Status akun:** Penonaktifan/pemulihan menggunakan `is_active`; tidak tersedia endpoint hapus akun permanen.

---

## 2. Modul Kasus BK (`CASE`, `INT`)

### Daftar dan Form Kasus
- **Endpoint:** `GET /cases`, `GET /cases/create`
- **Controller:** `CaseController@index`
- **Authorization:** `Guru BK` (scope kelas aktif / kasus khusus), `Koordinator BK` (semua kasus), `Waka` (kasus terkoordinasi).
- **Query Params:** `search`, `classroom_id`, `case_source_id`, `status_id`, `month`, `tab`, dan `page`.
- **Response Data:** daftar `BkCase` atau `Consultation` terpagina dan tersaring policy sesuai tab aktif.

### Buat Kasus Baru
- **Endpoint:** `POST /cases`
- **Controller:** `CaseController@store`
- **Form Request:** `StoreCaseRequest`
  - `student_id` (nullable, exists:students,id)
  - `temporary_nisn` (required_without:student_id, string, max:20)
  - `temporary_name` (required_without:student_id, string, max:150)
  - `case_source_id` (required, exists:references,id)
  - `service_field_id` (required, exists:references,id)
  - `service_date` (required, date)
  - `initial_info` (required, string)
  - `initial_action` (required, string)
  - `internal_note` (nullable, string)
  - `etatib_record_ids[]` (opsional; wajib untuk sumber e-Tatib dan harus sesuai NISN)
- **Business Logic:** `CaseService::createCase()`
  - Generate nomor registrasi kasus unik (`K-YYYY-XXXX`) untuk kebutuhan internal; nilainya tidak masuk keluaran pengguna.
  - Set status default ke `baru` (**Baru dicatat**).
  - Hubungkan ke `temporary_students` jika murid belum tersinkron di Dapodik.
  - Buat penugasan pemilik awal untuk Guru BK pencatat.
  - Catat jejak audit otomatis.

### Ubah Kasus Berjalan
- **Endpoint:** `GET /cases/{case}/edit`, `PATCH /cases/{case}`.
- **Controller:** `CaseController@edit`, `CaseController@update`.
- **Form Request:** `UpdateCaseRequest`.
- **Field:** `status_id` nonterminal, `service_date`, `service_field_id`, `referral_source`, `initial_info`, `initial_action`, dan `internal_note`.
- **Field tetap:** murid/identitas sementara, kode internal, sumber kasus, serta tautan e-Tatib tidak dapat diubah melalui form ini.
- **Authorization:** hanya Guru BK pemilik aktif dan hanya ketika status kasus belum terminal. Koordinator mengatur penugasan tetapi tidak mengubah catatan profesional.
- **Koreksi:** kasus `selesai` atau `dibatalkan` menolak endpoint ini dan memakai alur koreksi terverifikasi.

### Detail & Koordinasi Kasus
- **Endpoint:** `GET /cases/{case}`
- **Controller:** `CaseController@show`
- **Authorization:** `CasePolicy@view`
  - Waka Kesiswaan hanya dapat melihat jika terdapat record di `case_coordinations` untuk kasus tersebut (detail read-only tanpa catatan konseling sensitif).
  - Koordinator melihat ringkasan lintas kasus; catatan internal hanya terlihat jika juga memiliki penugasan Guru BK aktif pada kasus.
  - Admin IT tidak memiliki akses daftar/detail kasus hanya karena role teknis.

### Ringkasan Koordinasi Waka
- **Endpoint:** `POST /cases/{case}/coordinations`, `PATCH /cases/{case}/coordinations/{coordination}`.
- **Controller:** `CaseCoordinationController@store`, `CaseCoordinationController@update`.
- **Authorization:** Guru BK pemilik kasus aktif atau Koordinator BK; Waka sepenuhnya read-only.
- **Batas fungsi:** koordinasi dilakukan di luar aplikasi. Aplikasi tidak menyediakan chat, komentar, persetujuan/penolakan digital, atau notifikasi percakapan.
- **Request buat:** `waka_user_id`, `coordinated_at`, dan `coordination_need`. Pihak yang terlibat diturunkan dari pencatat dan Waka tujuan.
- **Request hasil:** `status_id` (`selesai` atau `dibatalkan`) dan `result` yang merangkum hasil serta tindak lanjut yang disepakati.
- **Audit:** pembuatan/perubahan koordinasi dan setiap akses detail oleh Waka dicatat.

### Selesaikan Kasus
- **Endpoint:** `GET /cases/{case}/resolve`, `POST /cases/{case}/resolve`
- **Controller:** `CaseController@resolve`
- **Request:** `closed_at`, `final_result`, `resolution_summary` (wajib), dan `continued_plan` (opsional).
- **Business Logic:** `CaseService::resolve()` mengubah status menjadi `Selesai`, mempertahankan histori, dan mencatat audit.

---

## 3. Modul Tindak Lanjut (`CASE-05, 09, 10`)

### Tambah dan Ubah Tindak Lanjut
- **Endpoint:** `GET /cases/{case}/follow-ups/create`, `POST /cases/{case}/follow-ups`, `GET /cases/{case}/follow-ups/{followUp}/edit`, `PATCH /cases/{case}/follow-ups/{followUp}`.
- **Controller:** `FollowUpController`.
- **Form Request:** `SaveFollowUpRequest`
  - `planned_date` (required, date)
  - `execution_date` (nullable, date)
  - `follow_up_type_id` (required, exists:references,id)
  - `status_id` (required, exists:references,id)
  - `result` (nullable, string)
  - `next_plan` (nullable, string)
- **Business Logic:** `FollowUpService::record()`/`update()`; tindak lanjut pertama dapat mengubah `baru` menjadi `sedang_diproses` atau `membutuhkan_tindak_lanjut` sesuai kondisi. Status `Terlaksana` mewajibkan tanggal pelaksanaan dan hasil. Tindak lanjut lama hanya dapat diubah pencatat aslinya yang masih berwenang.

---

## 4. Modul Konsultasi dan Profil Murid (`CONS`, `STU`)

### Daftar, detail, dan formulir konsultasi
- **Endpoint:** `GET /consultations` mengalihkan ke `GET /cases?tab=konsultasi`; `GET /consultations/create`; `GET /consultations/{consultation}`; `GET /consultations/{consultation}/edit`.
- **Controller:** `CaseController@index` untuk daftar dan `ConsultationController` untuk formulir/detail.
- **Filter daftar:** `search` (nama, NISN, atau topik), `classroom_id`, `service_field_id`, `consultation_status_id`, dan `month` (`YYYY-MM`).
- **Authorization:** Guru BK membaca histori ketika masih memiliki scope profesional atas murid. Koordinator membaca metadata dan ringkasan umum. Waka dan Admin IT tidak memiliki akses. Relasi `privateNote` hanya dimuat setelah `ConsultationPolicy@viewSensitive` disetujui.

### Catat dan ubah konsultasi
- **Endpoint:** `POST /consultations`; `PATCH /consultations/{consultation}`.
- **Controller:** `ConsultationController@store`, `ConsultationController@update`.
- **Form Request:** `StoreConsultationRequest`, `UpdateConsultationRequest`.
  - `student_id` atau pasangan `temporary_nisn` + `temporary_name` (salah satu wajib)
  - `case_id` (nullable, kasus dengan identitas sama dan penugasan aktif)
  - `service_field_id` (required, reference category `service_field`)
  - `status_id` (required, reference category `consultation_status`)
  - `topic` (required), `referral_source` (nullable)
  - `session_date` (required), `starts_at`, `ends_at`, `follow_up_date` (nullable)
  - `general_summary` (wajib untuk status `selesai`)
  - `internal_note`, `sensitive_content`, `conclusion`, `follow_up_plan` (nullable, disimpan pada tabel privat)
- **Business Logic:** `ConsultationService` membuat nomor internal `KNS-YYYY-XXXX`, memvalidasi identitas/scope/kasus/jadwal, dan menulis metadata serta catatan privat dalam satu transaksi. Hanya pencatat asli yang masih memiliki kewenangan profesional dapat mengubah sesi berstatus nonterminal; `selesai` dan `dibatalkan` menolak edit biasa. Tidak tersedia endpoint hapus.
- **Audit:** hanya metadata umum dan nama field privat yang berubah. Isi catatan privat tidak dicatat pada audit.

### Daftar dan profil murid
- **Endpoint:** `GET /students`; `GET /students/{student}`. URL kompatibilitas `GET /students/show?nisn=...` mengalihkan ke profil database setelah policy disetujui.
- **Controller:** `StudentController@index`, `StudentController@show`, `StudentController@legacy`.
- **Authorization:** Guru BK melihat murid dari penugasan kelas atau kasus aktif; Koordinator melihat ringkasan; Waka hanya murid dengan kasus yang dikoordinasikan; Admin IT diarahkan menggunakan Data Master.
- **Response View:** identitas dan histori kelas, kasus/tindak lanjut, mirror e-Tatib, konsultasi serta prestasi yang diizinkan, dan statistik berbasis scope. Waka tidak menerima tab/data konsultasi; e-Tatib dibatasi pada kasus koordinasinya dan prestasi dibatasi pada data terverifikasi.

### Pencatatan dan verifikasi prestasi
- **Endpoint:** `GET /achievements`, `GET /achievements/create`, `POST /achievements`, `GET /achievements/{achievement}`, `GET /achievements/{achievement}/edit`, `PATCH /achievements/{achievement}`, dan `POST /achievements/{achievement}/verify`.
- **Controller:** `AchievementController`.
- **Form Request:** `AchievementIndexRequest`, `StoreAchievementRequest`, `UpdateAchievementRequest`, dan `VerifyAchievementRequest`.
- **Input pencatatan:** `student_id`, `type_id`, `level_id`, `activity_name`, `organizer`, `achievement_date`, `result`, `evidence_reference`, `evidence_description`, dan `notes`. Bukti berupa tautan atau referensi arsip; unggahan berkas tidak tersedia sampai `DEP-06` disahkan.
- **Filter daftar:** pencarian, murid, kelas historis, jenis, tingkat, status, tanggal awal/akhir, dan halaman.
- **Authorization:** Guru BK mencatat murid dalam scope profesional dan hanya dapat mengubah catatannya selama berstatus `menunggu`. Koordinator melihat seluruh prestasi dan menetapkan `terverifikasi` atau `ditolak`. Waka hanya membaca prestasi terverifikasi milik murid dengan kasus terkoordinasi. Admin IT ditolak.
- **Verifikasi:** `decision` hanya menerima `terverifikasi` atau `ditolak`; `verification_notes` wajib untuk penolakan. Review memakai row lock dan bersifat final.
- **Audit dan retensi:** pencatatan, perubahan, review, dan koreksi diaudit tanpa menyalin catatan atau referensi bukti. Tidak tersedia endpoint hapus atau penghapusan otomatis.

---

## 5. Modul Pengelolaan Penugasan (`ASN`, `GOV`)

### Daftar dan Form Penugasan Kelas
- **Endpoint:** `GET /assignments/classes`, `GET /assignments/classes/manage`.
- **Controller:** `AssignmentController@index`, `AssignmentController@manage`.
- **Authorization daftar:** Guru BK melihat penugasannya; Koordinator, Waka, dan Admin IT memperoleh ringkasan sesuai fungsi masing-masing.
- **Authorization form:** hanya Koordinator BK.
- **Filter daftar:** `academic_year_id`, `search_kelas`, dan `status` (`aktif` atau `nonaktif`).

### Atur Penugasan Kelas
- **Endpoint:** `POST /assignments/classes`
- **Controller:** `AssignmentController@storeClassAssignment`
- **Authorization:** `Koordinator BK` only.
- **Request:** `user_id`, `classroom_id`, `academic_year_id`, `decision_number`, `effective_date`, `effective_until` (opsional), dan `notes` (opsional).
- **Business Logic:** `AssignmentService::assignClass()` menolak overlap, menutup periode lama ketika terjadi pergantian tengah tahun, serta mencatat histori dan audit tanpa memindahkan kasus aktif.

### Aktivasi Operasional Tahun Ajaran
- **Endpoint:** `POST /assignments/academic-years/{academicYear}/activate`.
- **Authorization:** hanya Koordinator BK aktif.
- **Business Logic:** `AcademicYearPreparationService::activate()` memeriksa tahun, rombel, murid aktif, tepat satu penugasan Guru BK per rombel yang mencakup tanggal mulai, dan memastikan tanggal mulai telah tiba. Tahun lengkap sebelum tanggal mulai berstatus `scheduled`; direct request aktivasi ditolak. Aktivasi yang sah menutup tahun ajaran aktif sebelumnya tanpa menghapus histori atau mengubah `master_source`.
- **Batas provider:** nilai tahun aktif dari Dapodik tidak pernah mengaktifkan atau mengganti tahun ajaran Ruang BK.

### Pengalihan / Penugasan Kasus Khusus
- **Endpoint:** `GET /assignments/cases`, `POST /cases/{case}/assign`
- **Controller:** `AssignmentController@assignCase`
- **Authorization:** `Koordinator BK` only.
- **Request:** `assignment_type=transfer`, `to_user_id`, `reason`, `effective_date`.
- **Business Logic:** `AssignmentService::assignCase()` menutup histori pemilik lama saat transfer dan membuat satu pemilik aktif baru. Assignment `additional` lama dipertahankan sebagai histori/akses baca tetapi tidak dapat mengubah kasus dan tidak dibuat lagi. Target wajib Guru BK aktif dan kasus terminal tidak dapat dialihkan.

---

## 6. Modul Koreksi Data & Rekonsiliasi (`COR`, `MD`)

### Persiapan Tahun Ajaran saat Dapodik Terlambat
- **Endpoint:**
  - `POST /data-master/academic-years` untuk membuat tahun ajaran persiapan.
  - `POST /data-master/academic-years/{academicYear}/roster-imports` untuk mengimpor daftar minimum.
- **Authorization:** hanya Admin IT aktif melalui capability `manageDataMaster`. Modul ini tidak memberi Admin IT hak aktivasi operasional atau akses isi layanan BK.
- **Request tahun ajaran:** nama/periode, tanggal mulai dan selesai, serta dasar resmi sekolah. Tahun baru selalu dibuat dengan `is_active=false`.
- **Request roster:** CSV UTF-8 maksimum 2 MiB dengan header exact `nisn,nama,rombel`, NISN 10 digit unik per berkas, field wajib aman, dan maksimum 5.000 baris. Berkas mentah tidak disimpan.
- **Business Logic:** `AcademicYearPreparationService::prepareAcademicYear()` dan `importRoster()` memvalidasi seluruh input sebelum transaksi. Impor memakai NISN exact, mempertahankan ID serta provenance data yang sudah ada, memproses seluruh hasil secara atomik, dan tidak menonaktifkan baris yang tidak disebutkan.
- **Rollover:** impor membuat keanggotaan baru pada tahun target dan tidak menaikkan kelas, memindahkan, meluluskan, atau mengeluarkan murid secara otomatis. Murid aktif tahun sebelumnya tanpa keanggotaan target ditampilkan oleh query read-only sebagai **Perlu Konfirmasi**; daftar tersebut tidak memblokir aktivasi dan hilang otomatis setelah penempatan target tersedia.
- **Status:** data baru memakai `master_source=school_provisional`; data lama tanpa identitas sumber tetap `legacy_unclassified`; data terverifikasi memakai `dapodik`. Ketiganya terpisah dari `is_active`.
- **Scope layanan:** Guru BK belum memperoleh akses dari penugasan tahun yang belum aktif. Setelah aktivasi Koordinator, scope mengikuti tahun aktif dan penugasan tanpa membedakan `school_provisional` atau `dapodik`; data sementara tetap diberi penanda.

### Konfigurasi Koneksi Dapodik dan e-Tatib
- **Halaman:** `GET /data-master`, area PG-501 khusus Admin IT aktif melalui capability `manageDataMaster`.
- **Endpoint:**
  - `PATCH /data-master/integrations/{provider}` untuk menyimpan konfigurasi.
  - `POST /data-master/integrations/{provider}/test` untuk menguji tanpa mengimpor data.
  - `POST /data-master/integrations/{provider}/activate` untuk mengaktifkan hasil uji yang masih current.
  - `POST /data-master/integrations/{provider}/deactivate` untuk menonaktifkan koneksi.
- **Provider:** `{provider}` hanya menerima `dapodik` atau `etatib`; pemilihan driver berasal dari registry/whitelist deployment, bukan class dari database atau input pengguna.
- **Payload namespaced:** `{provider}[base_url]`, `{provider}[expected_source_identifier]`, `{provider}[api_key]`, `{provider}[remove_api_key]`, `{provider}[timeout_seconds]`, dan `{provider}[current_password]`.
- **Step-up:** seluruh aksi konfigurasi memerlukan kata sandi saat ini. API key kosong mempertahankan credential; nilai baru mengganti; checkbox hapus menghapus. Ganti dan hapus sekaligus ditolak.
- **Secret-safe response:** credential dan current password tidak masuk HTML, JSON, session/old input, audit, log, exception, atau response eksternal. Halaman dan response aksi konfigurasi memakai `Cache-Control: no-store`; DTO view hanya membawa boolean `has_credentials`.
- **State:** `unconfigured`, `draft`, `blocked`, `test_failed`, `ready`, dan `active`. Simpan perubahan material menaikkan `configuration_version`, membatalkan verifikasi, serta menonaktifkan koneksi. Simpan tanpa perubahan tidak mengubah version/state.
- **Uji koneksi:** browser hanya mengirim trigger. Backend memanggil probe server-to-server dan tidak membuat `external_sync_runs` atau mengubah cache master. Hasil `success` harus membuktikan autentikasi, contract/schema minimum, serta reported source identifier yang cocok; HTTP 2xx saja tidak cukup.
- **Activation invariant:** configuration version, `driver_id`, `adapter_version`, `contract_version`, dan endpoint-policy digest harus sama dengan hasil uji. Perubahan konfigurasi, driver, adapter, contract, allowlist, atau policy membuat koneksi efektif terblokir sampai diuji ulang.
- **Endpoint policy:** base URL harus cocok exact dengan allowlist origin deployment. Alamat metadata/link-local/multicast/unspecified selalu ditolak; alamat private/loopback hanya boleh digunakan bila origin cocok exact dengan allowlist provider dan flag opt-in private-network provider tersebut aktif. User-info, query/fragment, redirect, origin drift, proxy tak tepercaya, TLS invalid, serta DNS campuran/berubah ditolak fail-closed. Setiap resolusi alamat diperiksa tepat sebelum koneksi dan transport production wajib diikat ke alamat tervalidasi dengan Host/SNI yang benar. Endpoint-policy digest mencakup versi implementasi policy, daftar exact origin yang dikanonisasi, dan nilai flag private-network provider.
- **Concurrency:** save/test/activate/deactivate/sync diserialisasi per provider. Operation context memakai lock, fencing token persisten, row-lock recheck sebelum write, dan hard deadline dengan safety margin di bawah lease; hasil stale tidak boleh diterapkan.
- **Admission gate:** driver production tetap `unavailable` sampai seluruh bukti dan persetujuan pada [Admission Kontrak Provider dan Panduan Penerapan Integrasi](integrations/provider-contract-admission.md) terpenuhi. Bukti wajib mencakup autentikasi, origin/endpoint/method, fixture sintetis, exact field/type/nullability, identitas sumber, credential read-only, pagination/completeness, full/partial/deletion semantics, payload/page limits, rate limit, retry/backoff, timeout, concurrency/backpressure/queue, outage procedure, TLS/proxy/jaringan, mapping snapshot, validator executable, serta persetujuan Admin IT.
- **Rate limit uji:** maksimum lima permintaan per menit untuk kombinasi pengguna dan provider.

### Kontrak Adapter dan Release Integrasi
- **Field dan completeness:** adapter hanya memakai exact field resmi; missing collection, tipe/nullability salah, full marker tidak eksplisit, page/identitas tidak konsisten, atau jawaban berubah di tengah pagination ditolak sebelum mutasi. Dapodik full snapshot wajib memiliki collection tahun ajaran dan collection murid yang bertipe benar serta masing-masing memuat sedikitnya satu record valid; collection yang missing, bertipe salah, atau kosong selalu ditolak sebelum mutasi. e-Tatib full snapshot kosong hanya diterima dengan marker completeness dan total nol terverifikasi dari kontrak resmi.
- **Transport:** redirect dimatikan. DNS divalidasi tepat sebelum koneksi dan transport diikat ke alamat tervalidasi dengan Host/SNI yang benar. Jawaban DNS campuran/berubah, TLS invalid, dan proxy tak tepercaya ditolak fail-closed. Batas response/page serta seluruh operasi berasal dari kontrak yang disahkan.
- **Pengujian adapter:** test wajib memakai `Http::preventStrayRequests()` dan fake exact request agar tidak dapat mencapai jaringan nyata. Raw body sukses/error tidak disimpan atau dicatat; hanya metadata aman yang boleh masuk evidence, audit, log, exception, dan response.
- **Deployment Fase A:** kedua driver tetap `unavailable`, exact origin dan private-network policy diisi melalui environment deployment, lalu konfigurasi di-cache. Konfigurasi provider boleh disimpan, tetapi test/aktivasi/sinkronisasi production tetap gagal aman sampai adapter resmi lolos admission.
- **Rotasi key dan credential:** key lama ditempatkan sementara pada `APP_PREVIOUS_KEYS` saat `APP_KEY` dirotasi, seluruh credential ditulis ulang dengan key aktif sebelum key lama dicabut, dan credential sumber dirotasi/revokasi melalui prosedur least-privilege/read-only pada dokumen admission.
- **Release gate:** suite PHP, Pint, cache konfigurasi/view, pemeriksaan frontend, build, diff-check, pembersihan cache, migration additive SQLite/MySQL disposable, dan manual acceptance wajib lulus. Dilarang memakai `migrate:fresh`, rollback, reset, atau SQL destruktif pada database shared/production.

### Status dan Sinkronisasi Master Eksternal
- **Endpoint:** `GET /data-master`, `POST /data-master/dapodik/sync`, `POST /data-master/etatib/sync`.
- **Controller:** `Admin\DataMasterController@index`, `Admin\DataMasterController@synchronize`.
- **Authorization:** hanya Admin IT; akses ini tidak membuka data layanan BK.
- **Business Logic Dapodik:** `DapodikSyncService` kelak mengambil dan memvalidasi snapshot melalui connector terjaga, lalu meminta `DapodikReconciliationService` membentuk pratinjau pencocokan. Endpoint sync tidak melakukan upsert cache operasional.
- **Business Logic e-Tatib:** `EtatibSyncService` memvalidasi snapshot dan memperbarui mirror read-only sesuai kontrak yang sudah disahkan.
- **Mode connector:** production memakai connector tidak tersedia sampai mekanisme resmi `DEP-02` dikonfigurasi; fake connector digunakan pada test.
- **Snapshot:** payload parsial tidak menonaktifkan data lama. Snapshot penuh Dapodik hanya dapat menonaktifkan baris yang sebelumnya sudah terverifikasi Dapodik; data persiapan sementara dan data lama yang belum terklasifikasi tetap ditahan untuk pemeriksaan.
- **Guard konfigurasi:** sinkronisasi production hanya berjalan dengan setting `active` yang masih current terhadap configuration version, driver ID, adapter version, contract version, endpoint-policy digest, dan fencing token. Driver `unavailable` atau konfigurasi stale gagal aman tanpa mengubah data lama.
- **Evidence dan validator:** driver memetakan field resmi yang diperlukan ke snapshot internal tanpa menyimpan raw payload. Evidence membawa reported source identifier, contract marker/provenance, page count, record count, dan processed byte count. Validator executable menolak schema/type/nullability salah, completeness tidak terbukti, source identity mismatch, identity collision, atau limit terlampaui sebelum transaksi import.
- **Batas adapter:** response/page bytes, total bytes/records/pages, pagination, timeout, retry/backoff, rate limit, concurrency, backpressure, hard deadline, dan kebutuhan queue harus berasal dari kontrak yang sudah disahkan.
- **Konflik:** data bermasalah ditahan di `external_sync_issues` dan tidak menimpa data master yang sah.
- **e-Tatib:** `EtatibSyncService` menyimpan mirror read-only berdasarkan NISN. Snapshot penuh dapat menonaktifkan record lama; payload parsial hanya upsert. Connector production tetap tidak tersedia sampai `DEP-01` disahkan dan tidak ada endpoint write-back.

### Pratinjau dan Penerapan Dapodik
- **Endpoint:**
  - `GET /data-master/dapodik/previews/{syncRun}` untuk melihat hasil pencocokan.
  - `PATCH /data-master/dapodik/previews/{syncRun}/items/{item}` untuk menetapkan keputusan pemetaan yang diizinkan.
  - `POST /data-master/dapodik/previews/{syncRun}/apply` untuk menerapkan hasil setelah konfirmasi Admin IT.
- **Authorization:** hanya Admin IT aktif melalui capability `manageDataMaster`; setiap item wajib milik `syncRun` Dapodik yang sama.
- **Pratinjau:** menampilkan hasil `exact_match`, `new_record`, `changed`, `needs_mapping`, atau `conflict`. Pencocokan murid otomatis hanya memakai satu NISN exact; nama tidak menjadi kunci. Tahun ajaran dan rombel hanya dipetakan otomatis jika pasangan identitasnya unik. Konflik ditahan dan tidak dapat dipaksa melalui UI.
- **Pemetaan manual:** hanya kandidat tahun/rombel `school_provisional` pada konteks yang benar atau pembuatan baris resmi baru yang dapat dipilih. Keputusan meragukan dicatat untuk Admin IT.
- **Penerapan:** `DapodikReconciliationService::apply()` memvalidasi ulang run, keputusan, konfigurasi, fencing, fingerprint, hash item, dan target, lalu menerapkan seluruh hasil dalam satu transaksi. Cache operasional baru berubah pada endpoint `apply` setelah konfirmasi Admin IT.
- **Kesinambungan:** baris cocok memperoleh identitas sumber, field resmi, `master_source=dapodik`, waktu konfirmasi, dan audit lama/baru pada ID internal yang sama. Kasus, konsultasi, tindak lanjut, prestasi, penugasan, serta histori BK tidak dipindahkan atau dibuat ulang; `is_active` tidak berubah.
- **Data yang tidak cocok:** snapshot penuh hanya dapat menonaktifkan baris yang sudah `master_source=dapodik`. Data `school_provisional` dan `legacy_unclassified` yang belum cocok tetap ditahan untuk pemeriksaan.
- **Arah data:** tidak ada write-back ke Dapodik atau e-Tatib.

### Identitas Murid Sementara
- **Endpoint:** tidak memiliki endpoint mandiri; dibuat sebagai bagian dari `POST /cases` bila murid belum tersedia pada cache Dapodik.
- **Service:** `StudentIdentityService::createTemporary()` dan `StudentIdentityService::reconcilePending()`.
- **Aturan:** hanya NISN dan nama masukan yang disimpan; kecocokan memakai NISN, nama resmi berasal dari Dapodik, dan nilai awal dipertahankan dalam histori/audit.

### Ajukan Koreksi
- **Endpoint:** `GET /corrections`, `GET /corrections/create`, `POST /corrections`, dan `GET /corrections/{correction}`.
- **Controller:** `CorrectionController@index/create/store/show`.
- **Form Request:** `StoreCorrectionRequest`.
  - `target_type` (`case`, `follow_up`, `consultation`, atau `achievement`)
  - `target_id` (required, integer)
  - `field_name` (required, atribut yang diizinkan per jenis objek)
  - `proposed_value` (present, nullable hanya untuk atribut yang memang opsional)
  - `reason` (required, string)
- **Business Logic:** `CorrectionService::submit()` membaca nilai lama langsung dari objek terotorisasi, menormalisasi tanggal/jam/referensi, menolak nilai usulan yang sama, memberi nomor `KR-YYYY-XXXX`, dan mencatat audit. Klien tidak dapat menentukan nilai lama atau jenis koreksi secara sepihak.
- **Scope daftar/detail:** Guru BK melihat pengajuan operasionalnya; Koordinator melihat seluruh pengajuan operasional untuk tata kelola; Waka tidak memperoleh workflow koreksi; Admin IT hanya dapat melihat/memproses data koreksi master historis yang sudah tersimpan. Multi-role menggabungkan fungsi tanpa membuka objek layanan di luar fungsi yang sah.
- **Koreksi master baru:** tidak tersedia pada MVP. Guru BK/Koordinator berkoordinasi dengan Admin IT di luar aplikasi; perubahan dilakukan pada sumber resmi lalu masuk melalui sinkronisasi.

### Verifikasi Koreksi Operasional
- **Endpoint:** `POST /corrections/{correction}/verify`.
- **Controller:** `CorrectionController@verify`
- **Form Request:** `VerifyCorrectionRequest`.
- **Authorization:** `Koordinator BK` only.
- **Request:** `decision` (`approved`, `rejected`, `revision_requested`) dan `review_notes` (wajib untuk penolakan/permintaan perbaikan).
- **Business Logic:** approval memakai row lock, memeriksa bahwa nilai objek belum berubah sejak pengajuan, lalu menerapkan perubahan melalui `CaseService`, `FollowUpService`, `ConsultationService`, atau `AchievementService`. Prestasi hanya dapat diajukan setelah terverifikasi. Tidak tersedia mutasi field generik.

### Pemrosesan Koreksi Master
- **Endpoint:** `POST /corrections/{correction}/process-master`.
- **Controller:** `CorrectionController@processMaster`.
- **Form Request:** `ProcessMasterCorrectionRequest`.
- **Authorization:** hanya Admin IT dan hanya untuk koreksi master berstatus `menunggu` atau `diproses`.
- **Request:** `action` (`processing`, `completed`, `rejected`), `review_notes`, dan `external_sync_run_id` yang wajib untuk `completed`.
- **Business Logic:** aplikasi tidak mengubah cache Dapodik melalui koreksi. Status `selesai` hanya diterima bila log sinkronisasi Dapodik berhasil/peringatan terjadi setelah pengajuan dan nilai master hasil sinkronisasi sama dengan nilai usulan.

### Riwayat Perubahan
- **Endpoint:** `GET /history`.
- **Controller:** `HistoryController@index`.
- **Response:** audit append-only terpagina berisi waktu, pelaku, tipe/ID objek, tindakan, dan ringkasan; nilai before/after tidak ditampilkan pada PG-406.
- **Scope:** Koordinator memperoleh ringkasan tata kelola; Guru BK dan Waka hanya tindakan yang dilakukannya; Admin IT hanya tindakan teknis, sinkronisasi, akun, dan pemrosesan koreksi master.

---

## 7. Dashboard dan Notifikasi (`DASH`, `NOT`)

### Dashboard per kewenangan
- **Endpoint:** `GET /dashboard`.
- **Controller:** `DashboardController@index`.
- **Query Params:** `academic_year_id` (opsional; harus merujuk tahun ajaran yang tersedia).
- **Business Logic:** `DashboardService::forUser()` membentuk query terpisah untuk setiap fungsi akun.
  - Guru BK menerima agregat murid dalam scope profesional, kasus yang dapat diakses, tindak lanjut terdekat, mirror e-Tatib terkait, dan aktivitasnya sendiri.
  - Koordinator BK menerima rekap tata kelola umum tanpa isi catatan internal atau konsultasi privat.
  - Waka Kesiswaan menerima agregat aman seluruh kasus sekolah; tautan detail hanya tersedia untuk kasus yang dikoordinasikan kepadanya.
  - Admin IT hanya menerima status akun, sinkronisasi, konflik sumber, dan koreksi master tanpa identitas atau isi layanan BK.
- **Multi-role:** fungsi Koordinator diprioritaskan sebagai rekap tata kelola; role teknis tidak membuka isi layanan sensitif.

### Daftar dan status baca notifikasi
- **Endpoint:** `GET /notifications`, `GET /notifications/{notification}`, dan `POST /notifications/read-all`.
- **Controller:** `NotificationController@index/open/markAllRead`.
- **Filter:** `filter=unread` dan `category` (`schedule`, `assignment`, `coordination`, `correction`, atau `change`).
- **Authorization:** notifikasi hanya dapat dilihat dan ditandai dibaca oleh akun penerimanya. Akun nonaktif tidak menerima notifikasi baru dan ditolak middleware akun aktif.
- **Business Logic:** `NotificationService` membuat notifikasi persisten dan idempotent untuk jadwal, penugasan, koordinasi, koreksi, serta perubahan kewenangan yang penting. Tautan aksi hanya dibentuk dari allowlist route server; akses objek tujuan tetap diperiksa kembali oleh policy objek.
- **Privasi:** judul dan pesan tidak menyimpan kata sandi, kredensial, catatan internal kasus, atau isi konsultasi privat.

---

## 8. Modul Laporan (`REP`)

### Portal Pemantauan Waka
- **Endpoint:** `GET /waka/students-with-cases`, `GET /waka/handling-reports`.
- **Controller:** `WakaMonitoringController`.
- **Form Request:** `WakaMonitoringRequest`.
- **Authorization:** hanya Waka Kesiswaan aktif melalui policy khusus; seluruh response hanya-baca dan akses dicatat pada audit.
- **Query Params:** `period` (`YYYY-MM`), `status`, `sort`, `direction`, dan `page` sesuai allowlist kontrak bersama.
- **Field aman:** nama murid, kelas historis, bidang layanan, status, Guru BK penanggung jawab, tanggal pelayanan, ringkasan tindakan yang disahkan, tindak lanjut berikutnya, dan hasil akhir.
- **Field terlarang:** NISN, kode kasus, informasi awal sensitif, catatan internal, isi konsultasi, catatan pribadi konselor, dokumen sensitif, dan narasi di luar allowlist.
- **Detail:** baris kasus nonterkoordinasi tidak memiliki tautan detail; direct request ke detail tetap `403`.

### Pusat, Pratinjau, dan Ekspor Laporan
- **Endpoint:** `GET /reports`, `GET /reports/preview`, dan `GET /reports/export`.
- **Controller:** `ReportController@index/preview/export`.
- **Form Request:** `ReportRequest`.
- **Query Params:**
  - `type`: `pelanggaran-murid`, `pelanggaran-kelas`, `poin-pelanggaran`, `konsultasi`, `status-tindak-lanjut`, `rekap-layanan-bk`, atau `prestasi`.
  - `academic_year_id`, `date_start`, `date_end`, `classroom_id`, `student_id`, `category`, `service_field_id`, `status_id`, `counselor_id`, `minimum_points`, `achievement_type_id`, `achievement_level_id`, dan `page` sesuai tipe.
  - `format=csv` wajib pada endpoint ekspor. XLSX dan PDF server belum tersedia sampai `DEP-07` disahkan.
- **Authorization:** `ReportPolicy` mengizinkan Guru BK, Koordinator BK, dan Waka Kesiswaan; Admin IT ditolak. Waka tidak memperoleh laporan konsultasi atau narasi sensitif, tetapi dapat menerima ringkasan penanganan seluruh kasus melalui proyeksi aman.
- **Business Logic:** `ReportService` memakai scope objek yang sama dengan daftar/detail. Guru BK dibatasi scope profesional atau kasus khusus, Koordinator memperoleh rekap gabungan, dan akun multi-role dihitung berdasarkan fungsi yang sah.
- **Periode dan kelas:** periode default mengikuti tahun ajaran aktif. Kelas ditentukan dari histori keanggotaan yang efektif pada tanggal kejadian, sesi, layanan, atau tindak lanjut.
- **Privasi:** laporan umum memakai inisial murid dan NISN tersamarkan. Portal/laporan penanganan khusus Waka boleh memakai nama murid dan kelas untuk kebutuhan pemantauan, tetapi tidak memuat NISN, kode kasus, catatan privat konsultasi, catatan internal kasus, dokumen, atau narasi sensitif.
- **Pratinjau:** KPI dihitung dari seluruh dataset terfilter dan tabel dipaginasi 20 baris.
- **CSV:** memakai dataset tidak terpagina dari pipeline yang sama. Dataset detail dibaca bertahap dalam chunk agar tidak dimuat seluruhnya ke memori, memakai UTF-8 BOM, nama file terkontrol, serta perlindungan formula injection.
- **Prestasi:** memakai data `achievements`, kelas historis pada tanggal prestasi, filter jenis/tingkat/status, inisial dan NISN tersamarkan, serta mengecualikan bukti dan catatan dari pratinjau maupun CSV. Waka hanya menerima prestasi terverifikasi untuk murid terkoordinasi.

---

## 9. Kontrak Hardening MVP

### Akun dan kompatibilitas URL
- **Endpoint akun:** `GET /account` melalui `AccountController@index`; seluruh nilai berasal dari akun sesi dan database. Tidak tersedia perubahan kata sandi mandiri.
- **Route `_preview/*`:** hanya kompatibilitas bookmark `GET|HEAD` menuju endpoint canonical. Route tidak merender fixture, hanya meneruskan query parameter yang diizinkan, dan menolak metode mutasi.
- **Route privat:** seluruh endpoint selain `/`, `/login`, dan health check berada di balik middleware `auth` serta `account.active`.

### Keamanan, retensi, dan konfigurasi
- Nested resource kasus memakai scoped route binding sehingga tindak lanjut atau koordinasi dari kasus lain menghasilkan `404` sebelum controller dijalankan.
- Disk private tidak dilayani melalui route aplikasi sampai `DEP-06`; tidak ada endpoint upload, unduh, atau hapus dokumen.
- `DatabaseSeeder` membuat akun sintetis hanya pada environment `local` atau `testing`, dengan password eksplisit dari `SIBK_SEED_ACCOUNT_PASSWORD`.
- Data operasional memakai soft delete dan audit tetap append-only. Tidak tersedia job, command, route, atau kebijakan penghapusan otomatis sebelum prosedur retensi disahkan.
- Indeks hardening mendukung scope periode/kelas, e-Tatib, kasus, konsultasi, tindak lanjut, prestasi, dan log sinkronisasi tanpa mengubah histori domain.
