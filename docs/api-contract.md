# Ruang BK — API & Service Contract

Status: **AKTIF — IMPLEMENTASI BACKEND**

Dokumen ini mendefinisikan kontrak endpoint, input, output, otorisasi, dan penanganan data untuk modul-modul P0 Ruang BK.

Sumber perilaku aktif: PRD dan SRS v1.1, termasuk amandemen keterlambatan Dapodik 9 September 2026, alur operasional BK 12 September 2026, Portal Waka berbasis tujuan 13 September 2026, laporan tiga tab 14 September 2026, penyederhanaan operasional 15 September 2026, Revisi SIBK 3.2 tanggal 17 September 2026, serta revisi laporan catatan layanan 21 sampai 23 September 2026.

## Kontrak Bersama Pengembangan Paralel

Kontrak berikut dibekukan sebelum Jalur A, B, dan C mulai bekerja:

- Status kasus aktif memakai `sedang_diproses`, `membutuhkan_tindak_lanjut`, dan `selesai`; konsultasi tidak memakai status.
- `selesai` adalah status terminal. Pemilik yang masih berwenang dapat mengedit setelah konfirmasi; identitas, pemilik, dan status terminal tetap tidak berubah.
- Kode registrasi kasus tetap dibuat dan disimpan backend, tetapi tidak menjadi field response/view pengguna, parameter pencarian pengguna, kolom laporan/CSV, atau ringkasan audit yang terlihat pengguna.
- Data master memuat seluruh murid aktif beserta penempatan tahun ajarannya. Kasus/konsultasi hanya dibuat ketika pelayanan benar-benar terjadi.
- Proyeksi detail Waka memakai field layanan aktual yang diizinkan, tanpa `waka_summary`, catatan internal, atau payload provider mentah.
- Route baru lifecycle pelayanan dimiliki `routes/bk-services.php`; route portal Waka dimiliki `routes/waka.php`. Keduanya dimuat di dalam middleware `auth` dan `account.active`.
- Jalur B memakai nama route `cases.edit` untuk `GET /cases/{case}/edit` dan `cases.update` untuk `PATCH /cases/{case}`.
- Portal Waka memakai `waka.monitoring.students` untuk `GET /waka/students-with-cases` dan halaman bersama `reports.index` untuk `GET /reports`. `GET /waka/reports` serta `GET /waka/handling-reports` hanya menjadi pengalihan kompatibilitas ke `/reports`.
- Filter daftar murid menerima `period` (`YYYY-MM`), `status`, `sort`, `direction`, dan `page`. Daftar laporan Waka memakai `academic_year_id`, `classroom_id`, `service_type`, `per_page`, dan `page` yang sama dengan laporan operasional.
- Waka aktif dapat membaca seluruh kasus dan konsultasi melalui policy/query scope khusus; route portal Waka tidak menyediakan tindakan mutasi.
- Autosave form tambah/edit data bisnis memakai `localStorage` per pengguna/form/record selama maksimal 24 jam, tidak dikirim atau diaudit, dan mengecualikan password, token, credential, file, CSRF, serta method spoofing.

### Otorisasi Produk

- **Model otorisasi:** capability global diperiksa oleh Gate/Policy, sedangkan hak atas data diperiksa oleh query scope, periode efektif, penugasan, kepemilikan, dan policy per objek. Tidak tersedia tabel maupun endpoint permission generik.
- **Pengujian:** [matriks otorisasi umum](testing/authorization-matrix.md) menunjuk feature test reguler untuk empat role, URL langsung, scope data, histori, privasi, dan ekspor.

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
- **Request buat:** `name`, `email`, `roles[]` (slug role), dan `is_active` (opsional); password tidak diterima dari form.
- **Request ubah:** Field yang berubah dari `name`, `email`, `roles[]`, dan `is_active`; password memakai alur reset tersendiri.
- **Reset password:** `POST /admin/users/{user}/reset-password` hanya untuk Admin IT aktif terhadap target lain.
- **Response buat/reset:** menampilkan password sementara unik satu kali dengan `Cache-Control: no-store, private`; JSON tidak memuat hash atau token sesi.
- **Business Logic:** `AccountService` menyimpan akun dan role dalam transaksi; `TemporaryPasswordService` menerbitkan password sementara 24 jam, memutus sesi target, menetapkan `must_change_password`, dan menulis audit tanpa nilai password.
- **Status akun:** Penonaktifan/pemulihan menggunakan `is_active`; tidak tersedia endpoint hapus akun permanen.

### Pergantian Password Wajib
- **Endpoint:** `GET /account/change-password`, `PATCH /account/change-password`.
- **Authorization:** akun aktif dengan sesi sah; middleware `password.changed` membatasi route operasional sampai password sementara diganti.
- **Request:** `current_password`, `password`, dan `password_confirmation`; password baru minimal delapan karakter serta memuat huruf dan angka.
- **Business Logic:** password baru mengosongkan `must_change_password` dan `temporary_password_expires_at`, mengisi `password_changed_at`, memutus sesi lain, dan diaudit tanpa nilai password. Password sementara kedaluwarsa ditolak saat login.

---

## 2. Modul Kasus BK (`CASE`, `INT`)

### Daftar dan Form Kasus
- **Endpoint:** `GET /cases`, `GET /cases/create`
- **Controller:** `CaseController@index`
- **Authorization:** `Guru BK` (scope kelas aktif/kasus khusus), `Koordinator BK` (semua kasus), dan Waka aktif (proyeksi hanya-baca seluruh kasus).
- **Query Params:** `search` (nama), `status_id`, `sort`, `direction`, `tab`, dan `page`; sort memakai allowlist dan ID sebagai tie-breaker.
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
  - Set status default ke `sedang_diproses` (**Sedang Proses**).
  - Hubungkan ke `temporary_students` jika murid belum tersinkron di Dapodik.
  - Buat penugasan pemilik awal untuk Guru BK pencatat.
  - Catat jejak audit otomatis.

### Ubah dan Arsip Kasus
- **Endpoint:** `GET /cases/{case}/edit`, `PATCH /cases/{case}`.
- **Controller:** `CaseController@edit`, `CaseController@update`.
- **Form Request:** `UpdateCaseRequest`.
- **Field:** `initial_info`, `initial_action`, `resolution_summary`, `expected_updated_at`, dan `action=save|complete`.
- **Field tetap:** murid/identitas sementara, kode internal, sumber kasus, tautan e-Tatib, pemilik, dan status terminal tidak dapat diubah melalui form ini.
- **Authorization:** hanya Guru BK pemilik aktif. Koordinator mengatur penugasan tetapi tidak mengubah catatan profesional.
- **Edit selesai:** memerlukan konfirmasi; setiap narasi maksimal 10.000 karakter. `action=complete` mewajibkan `resolution_summary`, menetapkan `closed_at` dari server, dan audit menyimpan hanya field berubah.
- **Payload update:**

```json
{
  "expected_updated_at": "2026-09-17T08:30:00.000000Z",
  "action": "save|complete",
  "initial_info": "Murid mengalami kesulitan beradaptasi di kelas.",
  "initial_action": "Guru BK melakukan asesmen awal.",
  "resolution_summary": "Murid dan Guru BK menyepakati langkah penyelesaian."
}
```
- **Arsip:** `DELETE /cases/{case}` hanya untuk pemilik aktif, memakai soft delete, dan tidak mengubah status bisnis.

### Detail Kasus
- **Endpoint:** `GET /cases/{case}`
- **Controller:** `CaseController@show`
- **Authorization:** `CasePolicy@view`
  - Waka Kesiswaan membaca proyeksi hanya-baca seluruh kasus tanpa catatan internal atau payload e-Tatib mentah.
  - Koordinator melihat ringkasan lintas kasus; catatan internal hanya terlihat jika juga memiliki penugasan Guru BK aktif pada kasus.
  - Admin IT tidak memiliki akses daftar/detail kasus hanya karena role teknis.

### Riwayat koordinasi Waka (dipensiunkan Revisi 3.2)

Koordinasi dilakukan di luar aplikasi. Tidak ada endpoint, controller, request,
model, relasi, atau data koordinasi pada kontrak aktif.

### Selesaikan Kasus
- **Endpoint:** tidak ada endpoint atau modal penyelesaian terpisah.
- **Controller:** `CaseController@update` dengan `action=complete`.
- **Request:** `resolution_summary` dan `expected_updated_at`; `closed_at` ditetapkan server.
- **Business Logic:** perubahan atomik menetapkan status `selesai`, menyimpan delta audit, dan menolak konflik optimistic concurrency.

---

## 3. Klasifikasi Tindak Lanjut (`CASE-05, 09, 10`)

### Ubah klasifikasi terkini
- **Endpoint:** `PATCH /cases/{case}/follow-up`.
- **Controller:** `CaseController@updateFollowUp`.
- **Form Request:** `follow_up_type_id` nullable dari reference aktif kategori `follow_up_type` dan `expected_updated_at`.
- **Payload update:**

```json
{
  "follow_up_type_id": 123,
  "expected_updated_at": "2026-09-17T08:30:00.000000Z"
}
```
- **Business Logic:** satu nilai non-null mengubah status menjadi `membutuhkan_tindak_lanjut`; nilai null mengembalikan `sedang_diproses`; kasus `selesai` ditolak. Tidak ada jadwal, hasil, riwayat event, atau record anak.

---

## 4. Modul Konsultasi dan Profil Murid (`CONS`, `STU`)

### Daftar, detail, dan formulir konsultasi
- **Endpoint:** `GET /consultations` mengalihkan ke `GET /cases?tab=konsultasi`; `GET /consultations/create`; `GET /consultations/{consultation}`; `GET /consultations/{consultation}/edit`.
- **Controller:** `CaseController@index` untuk daftar dan `ConsultationController` untuk formulir/detail.
- **Filter daftar:** `search` (nama), `service_field_id`, `sort`, `direction`, dan `page`; sort hanya `tanggal`, `nama`, `kelas`, atau `jenis_layanan` dengan ID sebagai tie-breaker.
- **Authorization:** Guru BK membaca histori dalam scope profesional, Koordinator membaca sesuai fungsi, Waka aktif membaca proyeksi detail hanya-baca, dan Admin IT ditolak.

### Catat, ubah, dan arsip konsultasi
- **Endpoint:** `POST /consultations`; `PATCH /consultations/{consultation}`; `DELETE /consultations/{consultation}`.
- **Controller:** `ConsultationController@store`, `ConsultationController@update`, `ConsultationController@destroy`.
- **Form Request:** `StoreConsultationRequest`, `UpdateConsultationRequest`.
  - `student_id` atau `temporary_student_id` (tepat satu wajib)
  - `service_field_id` (required, reference category `service_field`)
  - `session_date`, `problem`, `handling`, dan `result` (required; tiga narasi maksimal 10.000 karakter)
  - `expected_updated_at` (required pada edit)
- **Business Logic:** `ConsultationService` memvalidasi identitas/scope dalam transaksi dan menyimpan record utama. Hanya pencatat asli yang masih berwenang dapat mengubah atau mengarsipkan sesi; edit memakai konfirmasi dan optimistic concurrency; arsip memakai soft delete.
- **Audit:** simpan resmi mencatat hanya field yang berubah beserta nilai sebelum/sesudah; autosave lokal tidak dicatat.

### Daftar dan profil murid
- **Endpoint:** `GET /students`; `GET /students/{student}`. URL kompatibilitas `GET /students/show?nisn=...` mengalihkan ke profil database setelah policy disetujui.
- **Controller:** `StudentController@index`, `StudentController@show`, `StudentController@legacy`.
- **Authorization:** Guru BK melihat murid dari penugasan kelas atau kasus aktif; Koordinator melihat ringkasan; Waka memakai proyeksi portalnya dan Admin IT diarahkan menggunakan Data Master.
- **Response View:** identitas dan histori kelas, kasus/tindak lanjut, mirror e-Tatib, konsultasi serta prestasi yang diizinkan, dan statistik berbasis scope. Proyeksi Waka tidak memuat payload e-Tatib mentah atau catatan internal.

### Pencatatan dan verifikasi prestasi
- **Endpoint:** `GET /achievements`, `GET /achievements/create`, `POST /achievements`, `GET /achievements/{achievement}`, `GET /achievements/{achievement}/edit`, `PATCH /achievements/{achievement}`, dan `POST /achievements/{achievement}/verify`.
- **Controller:** `AchievementController`.
- **Form Request:** `AchievementIndexRequest`, `StoreAchievementRequest`, `UpdateAchievementRequest`, dan `VerifyAchievementRequest`.
- **Input pencatatan:** `student_id`, `type_id`, `level_id`, `activity_name`, `organizer`, `achievement_date`, `result`, `evidence_reference`, `evidence_description`, dan `notes`. Bukti berupa tautan atau referensi arsip; unggahan berkas tidak tersedia sampai `DEP-06` disahkan.
- **Filter daftar:** pencarian, murid, kelas historis, jenis, tingkat, status, tanggal awal/akhir, dan halaman.
- **Authorization:** Guru BK mencatat murid dalam scope profesional dan hanya dapat mengubah catatannya selama berstatus `menunggu`. Koordinator melihat seluruh prestasi dan menetapkan `terverifikasi` atau `ditolak`. Waka hanya membaca prestasi terverifikasi milik murid dengan kasus terkoordinasi. Admin IT ditolak.
- **Verifikasi:** `decision` hanya menerima `terverifikasi` atau `ditolak`; `verification_notes` wajib untuk penolakan. Review memakai row lock dan bersifat final.
- **Audit dan retensi:** pencatatan, perubahan, dan review diaudit tanpa menyalin catatan atau referensi bukti. Tidak tersedia endpoint hapus atau penghapusan otomatis.

### Proses Keluar Murid
- **Endpoint Guru BK/Koordinator:** `POST /students/{student}/departure`, `PATCH /students/{student}/departure`, dan `POST /students/{student}/departure/finalize`.
- **Endpoint Waka:** `GET /waka/student-departures` untuk daftar/detail operasional read-only tanpa narasi privat kasus atau konsultasi.
- **Pencatatan:** Guru BK aktif dalam scope membuat tepat satu baris per murid berstatus `dalam_proses`; `reported_at` tidak dapat diubah setelah pencatatan pertama.
- **Keputusan:** hanya Koordinator BK memilih `batal` atau `resmi_keluar`; `effective_date` wajib untuk `resmi_keluar`.
- **Scope layanan:** hanya `resmi_keluar` yang sudah efektif menghentikan layanan baru dan memulai retensi. Data histori tetap tersedia sesuai policy.
- **Integrasi:** Dapodik/e-Tatib tidak dapat membuat, memperbarui, membatalkan, atau meresmikan proses keluar murid.

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
- **Blocking readiness:** target belum aktif; `starts_on`/`ends_on` valid dan tanggal sekarang berada di dalam periode; minimal satu rombel aktif; setiap rombel aktif mempunyai minimal satu keanggotaan murid aktif; setiap murid hanya mempunyai satu keanggotaan aktif pada tahun target; serta setiap rombel mempunyai tepat satu Guru BK aktif yang penugasannya mencakup tanggal mulai.
- **Warning nonblocking:** murid **Perlu Konfirmasi**, `master_source=school_provisional`, dan identitas sementara yang menunggu rekonsiliasi tidak menggagalkan aktivasi.
- **Business Logic:** `AcademicYearPreparationService::activate()` memakai pemeriksaan blocking yang sama dengan `activationReadiness()`. Tahun lengkap sebelum tanggal mulai berstatus `scheduled`; direct request aktivasi ditolak. Aktivasi yang sah menutup tahun ajaran aktif sebelumnya tanpa menghapus histori atau mengubah `master_source`.
- **Batas provider:** nilai tahun aktif dari Dapodik tidak pernah mengaktifkan atau mengganti tahun ajaran Ruang BK.

### Pengalihan / Penugasan Kasus Khusus
- **Endpoint:** `GET /assignments/cases`, `POST /cases/{case}/assign`
- **Controller:** `AssignmentController@assignCase`
- **Authorization:** `Koordinator BK` only.
- **Request:** `assignment_type=transfer`, `to_user_id`, `reason`, `effective_date`.
- **Business Logic:** `AssignmentService::assignCase()` menjalankan pengalihan dalam satu transaksi: lock kasus; tolak status terminal; lock seluruh assignment owner; pastikan satu owner aktif pada tanggal berlaku; tutup owner lama satu hari sebelum tanggal berlaku; buat satu owner baru; lalu tulis audit. Kegagalan tahap mana pun me-rollback seluruh perubahan. Assignment `additional` lama dipertahankan sebagai histori/akses baca tetapi tidak dapat mengubah kasus dan tidak dibuat lagi. Target wajib Guru BK aktif.

---

## 6. Modul Data Master & Rekonsiliasi (`MD`)

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
- **Proses keluar:** sinkronisasi provider dilarang membuat atau mengubah `student_departures`.

### Batas Field Provider Sekolah

- Provider roster menyediakan nama, NISN, rombel, dan `tahun_pelajaran`.
- Provider e-Tatib menyediakan NISN, nama, kelas, pelanggaran, poin, pencatat,
  kategori, `tanggal_pelanggaran`, dan `total_poin`.
- Kedua provider tidak menyediakan status atau tanggal keluar murid.
- API sekolah tidak menentukan status keluar murid. Ketiadaan field keluar tidak
  menggagalkan admission untuk fungsi roster, tetapi dicatat sebagai batas
  kontrak dan tidak boleh diisi dengan asumsi.

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
- **Aturan pencatatan:** bila NISN sudah ada pada master, identitas sementara ditolak dan Guru BK harus memilih murid master. Hanya NISN dan nama masukan yang disimpan.
- **Aturan nama:** perbedaan nama saja tidak membuat identitas baru. Setelah tersedia tepat satu murid Dapodik terverifikasi dengan NISN sama, identitas sementara ditautkan ke murid tersebut, nama resmi dipakai, dan nama masukan tetap berada pada histori/audit.
- **Konflik:** beberapa identitas sementara dengan NISN sama tetapi nama berbeda sebelum data resmi tersedia, lebih dari satu kandidat lokal, atau pertentangan `dapodik_id` dan NISN menghasilkan `ditahan_konflik`. Tidak ada relasi yang diubah otomatis; Admin IT menyelesaikan berdasarkan sumber resmi.
- **Atomicity:** rekonsiliasi mengunci identitas sementara, kandidat murid, dan issue sumber yang relevan dalam satu transaksi agar impor dan rekonsiliasi bersamaan tidak menghasilkan tautan ganda.

### Penghentian Koreksi Data dan Riwayat Perubahan

- Tidak tersedia route, controller, service, atau halaman Koreksi Data dan
  Riwayat Perubahan pada MVP.
- Kesalahan data master diperbaiki pada sumber resmi lalu masuk melalui
  sinkronisasi atau rekonsiliasi.
- `audit_logs` tetap append-only untuk backend dan pemeriksaan resmi, tetapi
  tidak mempunyai endpoint pembaca umum.

---

## 7. Dashboard (`DASH`)

### Dashboard per kewenangan
- **Endpoint:** `GET /dashboard`.
- **Controller:** `DashboardController@index`.
- **Query Params:** `academic_year_id` (opsional; harus merujuk tahun ajaran yang tersedia).
- **Business Logic:** `DashboardService::forUser()` membentuk query terpisah untuk setiap fungsi akun.
  - Guru BK menerima cakupan kelas, kasus khusus aktif, dan kasus berstatus Tindak Lanjut.
  - Koordinator BK menerima jumlah Guru BK aktif, kelas tanpa penugasan efektif, dan jumlah kasus Tindak Lanjut tanpa catatan internal.
  - Waka Kesiswaan menerima agregat aman seluruh kasus sekolah dan tautan detail hanya-baca untuk kasus serta konsultasi sesuai proyeksi allowlist.
  - Admin IT hanya menerima kesiapan akun, tahun ajaran, sinkronisasi, konflik sumber, dan status provider tanpa identitas atau isi layanan BK.
- **Multi-role:** fungsi Koordinator diprioritaskan sebagai rekap tata kelola; role teknis tidak membuka isi layanan sensitif.
- **Payload:** `context_panel` berisi `title` dan daftar item `label`, `value`, serta `meta`; dashboard tidak membaca daftar `audit_logs`.

Tidak tersedia pusat Notifikasi pada MVP. Jadwal dan pekerjaan penting tetap
terlihat pada dashboard serta halaman operasional terkait.

---

## 8. Modul Laporan (`REP`)

### Portal Pemantauan Waka
- **Endpoint canonical:** `GET /waka/students-with-cases` dan `GET /reports`.
- **Kompatibilitas:** `GET /waka/reports` serta `GET /waka/handling-reports` mengarahkan akun Waka ke `GET /reports`; endpoint ekspor CSV portal lama dipensiunkan.
- **Controller:** `WakaMonitoringController` untuk daftar murid dan `ReportController` untuk laporan bersama.
- **Form Request:** `WakaMonitoringRequest` untuk daftar murid dan `OperationalReportRequest` untuk halaman laporan bersama.
- **Authorization:** Waka Kesiswaan aktif memperoleh daftar laporan hanya-baca. `ReportPolicy::viewDocument` menolak preview, cetak/PDF, ekspor Excel, dan preview per catatan bagi akun Waka murni.
- **Arsitektur informasi akun Waka murni:** Dashboard; PEMANTAUAN WAKA berisi Murid dengan Kasus, Proses Keluar Murid, dan Laporan; UTILITAS berisi Akun Saya.
- **Murid dengan Kasus:** satu row per identitas internal, dengan nama, kelas historis, jumlah kasus, jumlah aktif, status terbaru, dan Guru BK. Filter `period/status`; sort hanya `murid`, `kelas`, `status`, atau `guru_bk`.
- **Laporan:** memakai filter dan pagination laporan operasional. Proyeksi Blade hanya berisi No, Hari/Tanggal, Nama/Kelas, Jenis Masalah, Ringkasan, Guru BK, dan Keterangan; latar belakang, penanganan, hasil terpisah, URL aksi, dan kemampuan arsip tidak dikirim.
- **Field terlarang:** NISN, kode kasus, catatan internal, latar belakang, penanganan, payload provider mentah, dokumen sensitif, audit teknis, dan field di luar allowlist.
- **Audit pembacaan:** setiap response sukses mencatat `waka.monitoring.viewed` dengan actor, waktu, mode `dashboard`, `students`, atau `reports.layanan`, parameter allowlist yang sudah dinormalisasi, jumlah hasil halaman aman, IP, dan user agent.
- **Audit detail:** setiap detail kasus/konsultasi Waka mencatat event pembacaan.
- **Data terlarang pada audit pembacaan:** nama/NISN murid, kode kasus, dan narasi pelayanan tidak boleh masuk summary maupun before/after. Audit bersifat append-only dan disimpan minimum tiga tahun.

### Pusat, Pratinjau, dan Ekspor Laporan
- **Endpoint daftar:** `GET /reports`.
- **Endpoint preview rekap:** `GET /reports/preview?academic_year_id={id}&classroom_id={id?}&service_type=all|case|consultation`.
- **Endpoint preview catatan:** `GET /reports/records/{type}/{id}/preview`, dengan `type=case|consultation`.
- **Endpoint unduhan:** `GET /reports/export?format=xlsx&academic_year_id={id}&classroom_id={id?}&service_type=all|case|consultation`.
- **Controller:** `ReportController@index/preview/recordPreview/export`.
- **Form Request:** `OperationalReportRequest` untuk daftar, preview, dan ekspor; policy objek existing tetap melindungi preview per catatan.
- **Query Params:**
  - `academic_year_id`: tahun ajaran tersedia; default tahun ajaran aktif.
  - `classroom_id`: opsional dan wajib tersedia pada tahun ajaran serta scope actor.
  - `service_type`: `all`, `case`, atau `consultation`; default `all`.
  - `per_page`: `10`, `25`, `50`, atau `100`; default `10` dan hanya berlaku pada daftar.
  - `page`: integer minimum 1 dan hanya berlaku pada daftar.
  - `format`: hanya `xlsx`; hanya berlaku pada endpoint ekspor.
- **Authorization:** `ReportPolicy::viewAny` mengizinkan Guru BK, Koordinator BK, dan Waka. `ReportPolicy::viewDocument` mengizinkan Koordinator serta Guru BK yang tidak merangkap Waka; Admin IT ditolak. Akun Waka+Guru tetap memakai proyeksi Waka tanpa dokumen agar cakupan seluruh sekolah tidak berpindah ke kemampuan cetak Guru BK; Koordinator tetap memperoleh dokumen sesuai kewenangannya.
- **Business Logic:** `OperationalReportRecapService` menyatukan query `BkCase::accessibleTo()` dan `Consultation::accessibleTo()` sebagai satu row per catatan. Guru BK dibatasi scope profesional atau kasus khusus; Koordinator memperoleh gabungan yang diizinkan.
- **Kolom:** No; Hari/Tanggal; Nama & Kelas; Layanan/Jenis Masalah; Hasil; Aksi. UI Guru BK/Koordinator menampilkan jenis catatan kecil sebagai `Permasalahan|Konsultasi` tanpa kata `Catatan`, lalu label bidang layanan lebih besar dan tebal. Satu ikon kaca pembesar membuka baris detail Latar Belakang Masalah dan Penanganan; Hasil tidak diulang pada baris detail.
- **Ringkasan:** dihitung dari seluruh query terscope setelah filter tahun ajaran, kelas, dan jenis layanan, sebelum pagination. Total Catatan selalu tampil. Permasalahan atau Konsultasi yang tidak relevan dengan filter jenis layanan disembunyikan. UI memakai kartu angka; preview/PDF dan Excel memakai kalimat naratif yang menjelaskan total serta komposisi hasil filter.
- **Mapping:** kasus memakai `resolution_summary` sebagai Hasil, `initial_info` sebagai Latar Belakang Masalah, dan `initial_action` sebagai Penanganan. Konsultasi memakai `result` sebagai Hasil, `problem` sebagai Latar Belakang Masalah, dan `handling` sebagai Penanganan. `internal_note` kasus tidak masuk laporan.
- **Urutan:** daftar memakai tanggal layanan `DESC`; preview/ekspor memakai tanggal layanan `ASC`; keduanya memakai tipe dan ID sebagai tie-breaker stabil.
- **Pagination:** `per_page` hanya memengaruhi daftar. Preview dan ekspor selalu mengambil seluruh dataset hasil filter.
- **Kelas:** kelas ditentukan dari histori keanggotaan yang efektif pada tanggal kasus/konsultasi. `classroom_id` wajib berasal dari `academic_year_id` terpilih dan scope actor; pasangan yang tidak cocok ditolak server.
- **Preview:** satu tombol `Cetak / Unduh Rekap` membuka preview A4 portrait. Tabel putih polos memuat No, Hari/Tanggal dari `service_date|session_date`, Nama/Kelas, Jenis Masalah berupa jenis catatan dan label `service_field_id`, Ringkasan dari `resolution_summary|result`, Guru BK dari owner kasus terakhir atau `counselor_id`, serta Keterangan. Keterangan Permasalahan memuat label `case_source_id` dan label `follow_up_type_id` terbaru; nilai kosong memakai `—`. Keterangan Konsultasi memakai `Selesai`. Action bar menyediakan Kembali, Download Excel, serta Cetak/Simpan PDF. Halaman laporan tidak menautkan preview individual.
- **Penandatangan:** rekap memakai Koordinator BK dan Waka Kesiswaan; kasus memakai assignment owner terakhir dan Waka; konsultasi memakai `counselor_id` dan Waka. Koordinator/Waka hanya dipilih bila tepat satu akun aktif tersedia. Kondisi kosong/ganda menampilkan `Penandatangan belum tersedia`; pengguna login bukan fallback. Kepala Sekolah dan NIP tidak ditampilkan karena belum memiliki sumber data.
- **Layout bersama:** preview memakai partial kop dan tanda tangan. Blok tanda tangan hanya berada di akhir dokumen dan tidak terpotong page break. Excel tidak memuat tanda tangan.
- **Ekspor:** hanya Excel `.xlsx` dengan PhpSpreadsheet dan perlindungan formula injection; unduhan Word tidak tersedia.
- **Arsip:** aksi Hapus meneruskan ke endpoint archive existing dan hanya ditampilkan bila policy objek mengizinkan.
- **Privasi:** kode kasus, catatan internal, payload provider, dan field di luar allowlist tidak masuk daftar atau keluaran.

---

## 9. Kontrak Hardening MVP

### Akun dan kompatibilitas URL
- **Endpoint akun:** `GET /account` melalui `AccountController@index`; seluruh nilai berasal dari akun sesi dan database. Pergantian kata sandi tersedia melalui endpoint akun terautentikasi, bukan reset publik/email.
- **Route `_preview/*`:** hanya kompatibilitas bookmark `GET|HEAD` menuju endpoint canonical. Route tidak merender fixture, hanya meneruskan query parameter yang diizinkan, dan menolak metode mutasi.
- **Route privat:** seluruh endpoint selain `/`, `/login`, dan health check berada di balik middleware `auth` serta `account.active`.

### Keamanan, retensi, dan konfigurasi
- Resource kasus memakai scoped route binding untuk record anak yang masih aktif; route tindak lanjut berjadwal dan koordinasi tidak terdaftar.
- Disk private tidak dilayani melalui route aplikasi sampai `DEP-06`; tidak ada endpoint upload, unduh, atau hapus dokumen.
- `DatabaseSeeder` membuat akun sintetis hanya pada environment `local` atau `testing`, dengan password eksplisit dari `SIBK_SEED_ACCOUNT_PASSWORD`.
- Data operasional memakai soft delete dan audit tetap append-only. Tidak tersedia job, command, route, atau kebijakan penghapusan otomatis sebelum prosedur retensi disahkan.
- Indeks hardening mendukung scope periode/kelas, e-Tatib, kasus, konsultasi, tindak lanjut, prestasi, dan log sinkronisasi tanpa mengubah histori domain.
