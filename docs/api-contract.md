# Ruang BK — API & Service Contract

Status: **AKTIF — IMPLEMENTASI BACKEND**

Dokumen ini mendefinisikan kontrak endpoint, input, output, otorisasi, dan penanganan data untuk modul-modul P0 Ruang BK.

Sumber perilaku aktif: PRD dan SRS v1.1, termasuk amandemen keterlambatan Dapodik 9 September 2026, alur operasional BK 12 September 2026, Portal Waka berbasis tujuan 13 September 2026, laporan tiga tab 14 September 2026, penyederhanaan operasional 15 September 2026, Revisi SIBK 3.2 tanggal 17 September 2026, revisi laporan catatan layanan 21 sampai 23 September 2026, serta penyelarasan domain 24 September 2026.

## Konvensi Kontrak

Business rule, lifecycle, authorization outcome, privacy, dan acceptance criteria mengikuti requirement SRS terkait. Dokumen ini hanya menetapkan interface teknis, mapping controller/service, request/response, dan mekanisme enforcement.

### Otorisasi

Capability global diperiksa melalui Gate/Policy; pembatasan data diterapkan melalui query scope, tahun ajaran aktif, kepemilikan, dan policy objek. Tidak tersedia tabel atau endpoint permission generik.

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
- **Authorization:** `Guru BK` (scope profesional yang diizinkan), `Koordinator BK` (semua kasus), dan Waka aktif (proyeksi hanya-baca seluruh kasus).
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
  - `etatib_record_ids[]` (opsional; wajib bila `case_source_id` menunjuk e-Tatib; setiap record harus berasal dari mirror murid dengan NISN yang sama)
- **Business Logic:** `CaseService::createCase()`
  - Generate nomor registrasi kasus unik (`K-YYYY-XXXX`) untuk kebutuhan internal; nilainya tidak masuk keluaran pengguna.
  - Set status default ke `sedang_diproses` (**Sedang Proses**).
  - Hubungkan ke `temporary_students` jika murid belum tersinkron di Dapodik.
  - Buat tepat satu `CaseAssignment` bertipe `owner` untuk Guru BK pencatat,
    tanpa periode tanggal; relasi ini tidak dapat diganti melalui aplikasi.
  - Catat jejak audit otomatis.

### Ubah dan Arsip Kasus
- **Endpoint:** `GET /cases/{case}/edit`, `PATCH /cases/{case}`.
- **Controller:** `CaseController@edit`, `CaseController@update`.
- **Form Request:** `UpdateCaseRequest`.
- **Field:** `action=save|complete` dan `expected_updated_at`. Untuk `action=save`, field yang dapat diubah adalah `initial_info` (Latar Belakang Masalah) dan `initial_action` (Penanganan). Untuk `action=complete`, `resolution_summary` wajib dan diproses sebagai aksi penyelesaian.
- **Field tetap:** murid/identitas sementara, kode internal, sumber kasus, tautan e-Tatib, pemilik, tindak lanjut, dan status tidak dapat diubah melalui `action=save`.
- **Authorization:** hanya Guru BK pemilik yang masih berwenang. Fungsi Koordinator tidak memberikan hak mengubah catatan profesional atau mengalihkan pemilik kasus.
- **Kasus selesai:** `action=save` tetap dapat dipakai oleh pemilik yang masih berwenang sesuai `CASE-14`; status `selesai`, `closed_at`, identitas, dan pemilik tidak berubah.
- **Optimistic concurrency:** konflik `expected_updated_at` ditolak tanpa menimpa perubahan lain.
- **Arsip:** `DELETE /cases/{case}` hanya untuk owner kasus, memakai soft delete, dan tidak mengubah status bisnis.

### Detail Kasus
- **Endpoint:** `GET /cases/{case}`
- **Controller:** `CaseController@show`
- **Authorization:** `CasePolicy@view`
  - Waka Kesiswaan membaca proyeksi hanya-baca seluruh kasus tanpa catatan internal atau payload e-Tatib mentah.
  - Koordinator melihat ringkasan lintas kasus; catatan internal hanya terlihat jika juga berperan sebagai Guru BK dan merupakan owner aktif kasus tersebut.
  - Admin IT tidak memiliki akses daftar/detail kasus hanya karena role teknis.

### Riwayat koordinasi Waka (dipensiunkan Revisi 3.2)

Koordinasi dilakukan di luar aplikasi. Tidak ada endpoint, controller, request,
model, relasi, atau data koordinasi pada kontrak aktif.

### Selesaikan Kasus
- **Endpoint:** `PATCH /cases/{case}` dengan `action=complete`.
- **Controller:** `CaseController@update`.
- **Form Request:** `UpdateCaseRequest` dengan validasi kondisional untuk aksi penyelesaian.
- **Request:** `action=complete`, `resolution_summary` (required, string, max:10000), dan `expected_updated_at`.
- **Business Logic:** `CaseService::complete()` menetapkan `resolution_summary`, status `selesai`, dan `closed_at` dari waktu server secara atomik sesuai `CASE-06` dan `CASE-12`; konflik optimistic concurrency ditolak.

---

## 3. Tindak Lanjut Kasus (`CASE-05, CASE-09, CASE-10, CASE-16`)

### Tambah tindak lanjut
- **Endpoint:** `POST /cases/{case}/follow-ups`.
- **Controller:** `CaseFollowUpController@store`.
- **Form Request:** `StoreCaseFollowUpRequest`.
- **Request:** `follow_up_type_id` (required; reference aktif kategori `follow_up_type`) dan `expected_updated_at`.
- **Authorization:** hanya Guru BK pemilik kasus aktif; kasus `selesai` ditolak.
- **Business Logic:** `CaseFollowUpService::add()` membuat record anak dengan `performed_at` waktu server dan actor pembuat. Kombinasi `case_id + follow_up_type_id` unik; jenis yang sudah dipakai ditolak. Tindak lanjut pertama mengubah status kasus menjadi `membutuhkan_tindak_lanjut`.
- **Response/detail:** kasus mengembalikan daftar tindak lanjut tersortir waktu beserta jenis dan `performed_at`, serta daftar jenis aktif yang masih tersedia.

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
  - `session_date`, `problem`, dan `handling` (required); `result` nullable; setiap narasi maksimal 10.000 karakter
  - `expected_updated_at` (required pada edit)
- **Business Logic:** `ConsultationService` memvalidasi identitas/scope dalam transaksi dan menyimpan record utama. Label UI `problem` = Latar Belakang Masalah, `handling` = Penanganan, dan `result` = Hasil / Ringkasan. `result` boleh ditambahkan kemudian melalui edit. Hanya pencatat asli yang masih berwenang dapat mengubah atau mengarsipkan sesi; edit memakai optimistic concurrency; arsip memakai soft delete.
- **Audit:** simpan resmi mencatat hanya field yang berubah beserta nilai sebelum/sesudah; autosave lokal tidak dicatat.

### Daftar dan profil murid
- **Endpoint:** `GET /students`; `GET /students/{student}`. URL kompatibilitas `GET /students/show?nisn=...` mengalihkan ke profil database setelah policy disetujui.
- **Controller:** `StudentController@index`, `StudentController@show`, `StudentController@legacy`.
- **Authorization:** Guru BK melihat murid dari penugasan kelas atau kasus yang menjadi tanggung jawabnya; Koordinator melihat ringkasan; Waka memakai proyeksi portalnya dan Admin IT diarahkan menggunakan Data Master.
- **Response View:** identitas dan histori kelas, kasus/tindak lanjut, mirror e-Tatib, konsultasi serta prestasi yang diizinkan, dan statistik berbasis scope. Proyeksi Waka tidak memuat payload e-Tatib mentah atau catatan internal.

### Pengelolaan prestasi oleh Waka
- **Endpoint:** `GET /achievements`, `GET /achievements/create`, `POST /achievements`, `GET /achievements/{achievement}`, `GET /achievements/{achievement}/edit`, `PATCH /achievements/{achievement}`, dan `POST /achievements/import`.
- **Controller:** `AchievementController`; impor memakai `AchievementImportController@store`.
- **Form Request:** `AchievementIndexRequest`, `StoreAchievementRequest`, `UpdateAchievementRequest`, dan `ImportAchievementRequest`.
- **Input manual:** `student_id`, `type_id`, `level_id`, `activity_name`, `organizer`, `achievement_date`, dan `result`.
- **Import Excel:** `.xlsx`; setiap baris dipetakan ke murid dan field input manual. Seluruh berkas divalidasi sebelum transaksi, perubahan bersifat atomik, dan berkas mentah tidak disimpan setelah proses.
- **Authorization:** Waka Kesiswaan dapat membuat, membaca, mengubah, dan mengimpor prestasi. Guru BK hanya dapat membaca prestasi murid dalam scope profesional melalui daftar/profil yang diizinkan. Koordinator BK dan Admin IT tidak memperoleh hak kelola prestasi dari fungsi mereka.
- **Tidak tersedia:** endpoint verifikasi, status verifikasi, catatan verifikasi, evidence reference, evidence description, atau upload bukti.
- **Lifecycle:** perubahan prestasi tidak membuat kasus, mengubah status kasus, menambah tindak lanjut, atau menghasilkan rekomendasi otomatis.
- **Audit dan retensi:** pencatatan, perubahan, dan impor diaudit; tidak tersedia endpoint hapus permanen atau penghapusan otomatis.
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
- **Daftar:** satu baris per Guru BK aktif, termasuk yang belum mempunyai kelas. Kolom kelas memuat kelas aktif pada tahun terpilih; jumlah murid hanya menghitung membership aktif milik murid aktif pada tahun tersebut. Guru BK hanya menerima barisnya sendiri.
- **Filter daftar:** `academic_year_id`, `search_kelas` (nama kelas ampuan), dan `status` (`all`, `assigned`, atau `unassigned` berdasarkan status guru). Kelas kosong tetap tersedia dalam pilihan tambah bagi Koordinator meskipun daftar difilter.

### Atur Penugasan Kelas
- **Endpoint:** `POST /assignments/classes`
- **Controller:** `AssignmentController@storeClassAssignment`
- **Authorization:** `Koordinator BK` only.
- **Request:** `user_id` dan `classroom_id`; form tambah kelas kosong mengirim `only_if_unassigned=1`. Tahun ajaran berasal dari kelas dan konteks halaman yang diotorisasi, bukan input terpisah.
- **Business Logic:** `AssignmentService::assignClass()` mengunci kelas, tahun, Guru BK, dan state assignment kelas+tahun. Form tambah menolak kelas yang sudah ditugaskan setelah tampilan dimuat; POST existing tanpa precondition tetap dapat mengganti guru. Guru yang sama menghasilkan no-op; Guru berbeda memperbarui state dan menulis audit before/after. Assignment tahun aktif langsung berlaku untuk scope tahun tersebut, sedangkan assignment Persiapan belum memberi scope. Operasi ini tidak mengubah owner kasus.

### Batalkan Penugasan Kelas
- **Endpoint:** `DELETE /assignments/classes/{classroom}`.
- **Controller:** `AssignmentController@destroyClassAssignment`.
- **Authorization:** hanya Koordinator BK aktif; tahun aktif atau Persiapan.
- **Request:** `user_id` pengampu yang terlihat saat konfirmasi. Server menolak bila penugasan kosong, pengampu berubah, atau tahun sudah menjadi arsip.
- **Business Logic:** `AssignmentService::unassignClass()` mengunci kelas, tahun, dan assignment; mencatat audit `class_assignment.deleted` dengan `user_id` sebelum dan `null` sesudah, lalu menghapus state penugasan dalam transaksi. Scope murid tahun aktif langsung berkurang; owner kasus tetap.

### Aktivasi Operasional Tahun Ajaran
- **Endpoint:** `POST /assignments/academic-years/{academicYear}/activate`.
- **Authorization:** hanya Koordinator BK aktif.
- **Blocking readiness:** target belum aktif; minimal satu rombel memiliki murid; tidak ada penempatan aktif ganda pada tahun target; dan setiap rombel operasional memiliki tepat satu Guru BK aktif.
- **Warning nonblocking:** murid **Perlu Konfirmasi**, `master_source=school_provisional`, dan identitas sementara yang menunggu rekonsiliasi tidak menggagalkan aktivasi.
- **Business Logic:** `AcademicYearPreparationService::activate()` menjadikan target satu-satunya tahun ajaran aktif, mengisi `activated_at` dan `activated_by`, serta menonaktifkan tahun aktif sebelumnya tanpa menghapus histori atau mengubah `master_source`. Tidak ada `starts_on`/`ends_on` dan tidak ada aktivasi otomatis berbasis kalender.
- **Batas provider:** nilai tahun aktif dari Dapodik tidak pernah mengaktifkan atau mengganti tahun ajaran Ruang BK.


---

## 6. Modul Data Master & Rekonsiliasi (`MD`)

### Persiapan Tahun Ajaran dan Roster
- **Endpoint:**
  - `POST /data-master/academic-years` untuk membuat tahun ajaran.
  - `POST /data-master/academic-years/{academicYear}/roster-imports` untuk mengunggah Excel dan membentuk pratinjau tanpa mengubah master.
  - `GET /data-master/academic-years/{academicYear}/roster-imports/{importRun}` untuk melihat hasil pratinjau.
  - `POST /data-master/academic-years/{academicYear}/roster-imports/{importRun}/apply` untuk menerapkan hasil setelah konfirmasi Admin IT.
- **Authorization:** hanya Admin IT aktif melalui capability `manageDataMaster`. Modul ini tidak memberi Admin IT hak aktivasi operasional atau akses isi layanan BK.
- **Request tahun ajaran:** `name` (contoh `2027/2028`). Tahun baru selalu dibuat `is_active=false`; tidak ada input tanggal mulai/selesai.
- **Request roster:** file `.xlsx` dengan data minimum NISN, nama, dan rombel. NISN menjadi kunci exact dan file mentah tidak disimpan.
- **Pratinjau roster:** seluruh baris divalidasi sebelum preview diterima. Hasil diklasifikasikan sebagai cocok, baru, berubah, atau konflik. Konflik menahan penerapan sampai sumber diperbaiki atau keputusan yang sah tersedia.
- **Business Logic:** `AcademicYearPreparationService` membentuk preview tanpa memutasi master. Setelah konfirmasi Admin IT, hasil divalidasi ulang dan diterapkan dalam satu transaksi atomik sambil mempertahankan ID internal serta provenance yang sudah ada.
- **Rollover:** roster tahun target membuat state membership murid+kelas+tahun tanpa periode tanggal dan tidak menaikkan kelas, memindahkan, meluluskan, atau mengeluarkan murid secara otomatis. Murid tahun sebelumnya tanpa keanggotaan target ditampilkan **Perlu Konfirmasi** sampai penempatan resmi tersedia.
- **Status:** data hasil Excel memakai `master_source=school_provisional`; hasil API Dapodik yang sudah diterapkan memakai `dapodik`; keduanya terpisah dari `is_active`.
- **Scope layanan:** Guru BK memperoleh scope dari tahun ajaran aktif dan penugasan. Histori layanan, kelas lama, dan kasus aktif tidak dibuat ulang ketika roster tahun baru diterapkan.

### Konfigurasi Koneksi Dapodik dan e-Tatib
- **Halaman:** `GET /data-master`, area PG-501 khusus Admin IT aktif melalui capability `manageDataMaster`.
- **Endpoint:**
  - `PATCH /data-master/integrations/{provider}` untuk menyimpan konfigurasi.
  - `POST /data-master/integrations/{provider}/test` untuk menguji tanpa mengimpor data.
  - `POST /data-master/integrations/{provider}/activate` untuk mengaktifkan hasil uji yang masih current.
  - `POST /data-master/integrations/{provider}/deactivate` untuk menonaktifkan koneksi.
- **Provider:** `{provider}` hanya menerima `dapodik` atau `etatib`; pemilihan driver berasal dari registry/whitelist deployment, bukan class dari database atau input pengguna. Konfigurasi bersifat tingkat sekolah/provider dan tidak dibuat ulang ketika tahun ajaran berganti.
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
- **e-Tatib:** `EtatibSyncService` memperbarui mirror read-only berdasarkan NISN. Sinkronisasi hanya melakukan perubahan yang dapat dibuktikan oleh kontrak provider yang disahkan; penghapusan, deaktivasi, atau reset record tidak boleh diinferensikan sebelum deletion/reset semantics dikunci pada `DEP-01`. Connector production tetap tidak tersedia sampai `DEP-01` disahkan dan tidak ada endpoint write-back.
- **Proses keluar:** sinkronisasi provider dilarang membuat atau mengubah `student_departures`.

### Akses Mirror e-Tatib untuk Guru BK
- **Endpoint baca:** `GET /students/{student}/etatib`.
- **Endpoint tanda sudah dilihat:** `POST /students/{student}/etatib/acknowledge`.
- **Controller:** `StudentEtatibController@show`, `StudentEtatibController@acknowledge`.
- **Authorization:** hanya Guru BK aktif untuk murid dalam scope profesional yang sah. Admin IT mengelola sinkronisasi melalui Data Master; jabatan Koordinator atau Waka tidak otomatis memberi akses ke endpoint mirror ini.
- **Response:** `last_synced_at`, `has_new_data`, `provider_total_points` bila tersedia, serta daftar mirror pelanggaran berisi waktu kejadian, jenis, kategori, poin, pencatat, dan kelas sumber bila tersedia.
- **Business Logic:** data dibaca dari mirror lokal berdasarkan NISN. `provider_total_points` merupakan nilai total yang dilaporkan provider setelah normalisasi tipe dan tidak dihitung atau diakumulasikan sendiri oleh Ruang BK lintas periode. `acknowledge` hanya menyimpan marker dilihat per actor/murid dan tidak mengubah mirror maupun provider. Tidak ada threshold poin, pembuatan kasus, perubahan status, atau write-back otomatis.
- **Sinkronisasi:** sinkronisasi awal terjadi setelah integrasi e-Tatib aktif; sinkronisasi berikutnya dapat dijalankan oleh scheduler backend atau manual Admin IT melalui endpoint sync yang sama. Guru BK tidak memicu fetch provider langsung dari browser.

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
  - Guru BK menerima data dalam cakupan profesional yang diizinkan, termasuk kasus yang menjadi tanggung jawabnya dan kasus dalam cakupan tersebut yang berstatus Tindak Lanjut.
  - Koordinator BK menerima jumlah Guru BK aktif, kelas tanpa penugasan pada tahun terpilih, dan jumlah kasus Tindak Lanjut tanpa catatan internal.
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
- **Arsitektur informasi akun Waka murni:** Dashboard; PEMANTAUAN WAKA berisi Murid dengan Kasus, Proses Keluar Murid, Prestasi, dan Laporan; UTILITAS berisi Akun Saya. Mutasi Waka hanya tersedia pada Prestasi; layanan BK tetap hanya-baca.
- **Murid dengan Kasus:** satu row per identitas internal, dengan nama, kelas historis, jumlah kasus, jumlah aktif, status terbaru, dan Guru BK. Filter `period/status`; sort hanya `murid`, `kelas`, `status`, atau `guru_bk`.
- **Laporan:** memakai filter dan pagination laporan operasional. Proyeksi Blade hanya berisi No, Hari/Tanggal, Nama/Kelas, Jenis Masalah, Hasil / Ringkasan, Guru BK, dan Keterangan; latar belakang, penanganan, hasil terpisah, URL aksi, dan kemampuan arsip tidak dikirim.
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
- **Business Logic:** `OperationalReportRecapService` menyatukan query `BkCase::accessibleTo()` dan `Consultation::accessibleTo()` sebagai satu row per catatan. Guru BK dibatasi scope profesional atau kasus yang menjadi tanggung jawabnya; Koordinator memperoleh gabungan yang diizinkan.
- **Kolom:** No; Hari/Tanggal; Nama & Kelas; Layanan/Jenis Masalah; Hasil / Ringkasan; Aksi. UI Guru BK/Koordinator menampilkan jenis catatan kecil sebagai `Permasalahan|Konsultasi` tanpa kata `Catatan`, lalu label bidang layanan lebih besar dan tebal. Satu ikon kaca pembesar membuka baris detail Latar Belakang Masalah dan Penanganan; Hasil / Ringkasan tidak diulang pada baris detail.
- **Ringkasan:** dihitung dari seluruh query terscope setelah filter tahun ajaran, kelas, dan jenis layanan, sebelum pagination. Total Catatan selalu tampil. Permasalahan atau Konsultasi yang tidak relevan dengan filter jenis layanan disembunyikan. UI memakai kartu angka; preview/PDF dan Excel memakai kalimat naratif yang menjelaskan total serta komposisi hasil filter.
- **Mapping:** kasus memakai `resolution_summary` sebagai Hasil / Ringkasan, `initial_info` sebagai Latar Belakang Masalah, dan `initial_action` sebagai Penanganan. Konsultasi memakai `result` sebagai Hasil / Ringkasan, `problem` sebagai Latar Belakang Masalah, dan `handling` sebagai Penanganan. Nilai hasil null ditampilkan `—`.
- **Urutan:** daftar memakai tanggal layanan `DESC`; preview/ekspor memakai tanggal layanan `ASC`; keduanya memakai tipe dan ID sebagai tie-breaker stabil.
- **Pagination:** `per_page` hanya memengaruhi daftar. Preview dan ekspor selalu mengambil seluruh dataset hasil filter.
- **Kelas:** kasus/konsultasi menyimpan snapshot `academic_year_id` dan `classroom_id` saat dicatat. Laporan membaca snapshot tersebut secara langsung; pergantian membership atau tahun ajaran tidak menulis ulang konteks layanan lama. `classroom_id` wajib berasal dari `academic_year_id` terpilih dan scope actor; pasangan yang tidak cocok ditolak server.
- **Preview:** satu tombol `Cetak / Unduh Rekap` membuka preview A4 portrait. Tabel putih polos memuat No, Hari/Tanggal dari `service_date|session_date`, Nama/Kelas, Jenis Masalah berupa jenis catatan dan label `service_field_id`, Ringkasan dari `resolution_summary|result`, Guru BK dari pemilik kasus atau `counselor_id`, serta Keterangan. Keterangan Permasalahan memuat label `case_source_id` dan label seluruh tindak lanjut yang tercatat; nilai kosong memakai `—`. Keterangan Konsultasi memakai `—`. Action bar menyediakan Kembali, Download Excel, serta Cetak/Simpan PDF. Halaman laporan tidak menautkan preview individual.
- **Penandatangan:** rekap memakai Koordinator BK dan Waka Kesiswaan; kasus memakai Guru BK pemilik kasus dan Waka; konsultasi memakai `counselor_id` dan Waka. Koordinator/Waka hanya dipilih bila tepat satu akun aktif tersedia. Kondisi kosong/ganda menampilkan `Penandatangan belum tersedia`; pengguna login bukan fallback. Kepala Sekolah dan NIP tidak ditampilkan karena belum memiliki sumber data.
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
- Resource kasus memakai scoped route binding; tindak lanjut memakai record anak `case_follow_ups` tanpa route jadwal/hasil tersendiri, dan koordinasi Waka tidak memiliki route domain.
- Disk private tidak dilayani melalui route aplikasi sampai `DEP-06`; tidak ada endpoint upload, unduh, atau hapus dokumen.
- `DatabaseSeeder` membuat akun sintetis hanya pada environment `local` atau `testing`, dengan password eksplisit dari `SIBK_SEED_ACCOUNT_PASSWORD`.
- Data operasional memakai soft delete dan audit tetap append-only. Tidak tersedia job, command, route, atau kebijakan penghapusan otomatis sebelum prosedur retensi disahkan.
- Indeks hardening mendukung scope tahun/kelas, e-Tatib, kasus, konsultasi, tindak lanjut, prestasi, dan log sinkronisasi tanpa mengubah histori domain.
