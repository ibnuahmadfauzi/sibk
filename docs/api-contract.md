# Ruang BK — API & Service Contract

Status: **AKTIF — IMPLEMENTASI BACKEND**

Dokumen ini mendefinisikan kontrak endpoint, input, output, otorisasi, dan penanganan data untuk modul-modul P0 Ruang BK.

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
  - Generate nomor registrasi kasus unik (`K-YYYY-XXXX`).
  - Set status default ke 'Baru'.
  - Hubungkan ke `temporary_students` jika murid belum tersinkron di Dapodik.
  - Buat penugasan pemilik awal untuk Guru BK pencatat.
  - Catat jejak audit otomatis.

### Detail & Koordinasi Kasus
- **Endpoint:** `GET /cases/{case}`
- **Controller:** `CaseController@show`
- **Authorization:** `CasePolicy@view`
  - Waka Kesiswaan hanya dapat melihat jika terdapat record di `case_coordinations` untuk kasus tersebut (detail read-only tanpa catatan konseling sensitif).
  - Koordinator melihat ringkasan lintas kasus; catatan internal hanya terlihat jika juga memiliki penugasan Guru BK aktif pada kasus.
  - Admin IT tidak memiliki akses daftar/detail kasus hanya karena role teknis.

### Koordinasi Waka
- **Endpoint:** `POST /cases/{case}/coordinations`, `PATCH /cases/{case}/coordinations/{coordination}`.
- **Controller:** `CaseCoordinationController@store`, `CaseCoordinationController@update`.
- **Authorization:** Guru BK dengan penugasan kasus aktif atau Koordinator BK; Waka sepenuhnya read-only.
- **Request buat:** `waka_user_id`, `coordination_need`.
- **Request tutup:** `status_id` (`selesai` atau `dibatalkan`) dan `result` opsional.
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
- **Business Logic:** `FollowUpService::record()`/`update()`; tindak lanjut pertama mengubah `Baru` menjadi `Dalam Penanganan`. Status `Terlaksana` mewajibkan tanggal pelaksanaan dan hasil. Tindak lanjut lama hanya dapat diubah pencatat aslinya yang masih berwenang.

---

## 4. Modul Konsultasi dan Profil Murid (`CONS`, `STU`)

### Daftar, detail, dan formulir konsultasi
- **Endpoint:** `GET /consultations` mengalihkan ke `GET /cases?tab=konsultasi`; `GET /consultations/create`; `GET /consultations/{consultation}`; `GET /consultations/{consultation}/edit`.
- **Controller:** `CaseController@index` untuk daftar dan `ConsultationController` untuk formulir/detail.
- **Filter daftar:** `search` (nomor, nama, NISN, atau topik), `classroom_id`, `service_field_id`, `consultation_status_id`, dan `month` (`YYYY-MM`).
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
  - `general_summary` (wajib untuk status `terlaksana`)
  - `internal_note`, `sensitive_content`, `conclusion`, `follow_up_plan` (nullable, disimpan pada tabel privat)
- **Business Logic:** `ConsultationService` membuat nomor `KNS-YYYY-XXXX`, memvalidasi identitas/scope/kasus/jadwal, dan menulis metadata serta catatan privat dalam satu transaksi. Hanya pencatat asli yang masih memiliki kewenangan profesional dapat mengubah sesi; tidak tersedia endpoint hapus.
- **Audit:** hanya metadata umum dan nama field privat yang berubah. Isi catatan privat tidak dicatat pada audit.

### Daftar dan profil murid
- **Endpoint:** `GET /students`; `GET /students/{student}`. URL kompatibilitas `GET /students/show?nisn=...` mengalihkan ke profil database setelah policy disetujui.
- **Controller:** `StudentController@index`, `StudentController@show`, `StudentController@legacy`.
- **Authorization:** Guru BK melihat murid dari penugasan kelas atau kasus aktif; Koordinator melihat ringkasan; Waka hanya murid dengan kasus yang dikoordinasikan; Admin IT diarahkan menggunakan Data Master.
- **Response View:** identitas dan histori kelas, kasus/tindak lanjut, mirror e-Tatib, konsultasi yang diizinkan, statistik berbasis scope, serta empty state Prestasi sampai Sprint 8. Waka tidak menerima tab/data konsultasi dan hanya menerima e-Tatib yang tertaut ke kasus koordinasinya.

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

### Pengalihan / Penugasan Kasus Khusus
- **Endpoint:** `GET /assignments/cases`, `POST /cases/{case}/assign`
- **Controller:** `AssignmentController@assignCase`
- **Authorization:** `Koordinator BK` only.
- **Request:** `assignment_type` (`transfer` atau `additional`), `to_user_id`, `reason`, `effective_date`.
- **Business Logic:** `AssignmentService::assignCase()` menutup histori pemilik lama saat transfer atau memberi kewenangan tambahan tanpa mengganti pemilik. Target wajib Guru BK aktif dan kasus selesai tidak dapat dialihkan.

---

## 6. Modul Koreksi Data & Rekonsiliasi (`COR`, `MD`)

### Status dan Sinkronisasi Master Eksternal
- **Endpoint:** `GET /data-master`, `POST /data-master/dapodik/sync`, `POST /data-master/etatib/sync`.
- **Controller:** `Admin\DataMasterController@index`, `Admin\DataMasterController@synchronize`.
- **Authorization:** hanya Admin IT; akses ini tidak membuka data layanan BK.
- **Business Logic:** `DapodikSyncService` membaca payload ter-normalisasi melalui `DapodikConnector`, melakukan upsert cache master, menyimpan log sinkronisasi/konflik, lalu menjalankan rekonsiliasi NISN.
- **Mode connector:** production memakai connector tidak tersedia sampai mekanisme resmi `DEP-02` dikonfigurasi; fake connector digunakan pada test.
- **Snapshot:** data yang tidak hadir hanya dinonaktifkan pada snapshot penuh. Payload parsial tidak menonaktifkan data lama.
- **Konflik:** data bermasalah ditahan di `external_sync_issues` dan tidak menimpa data master yang sah.
- **e-Tatib:** `EtatibSyncService` menyimpan mirror read-only berdasarkan NISN. Snapshot penuh dapat menonaktifkan record lama; payload parsial hanya upsert. Connector production tetap tidak tersedia sampai `DEP-01` disahkan dan tidak ada endpoint write-back.

### Identitas Murid Sementara
- **Endpoint:** tidak memiliki endpoint mandiri; dibuat sebagai bagian dari `POST /cases` bila murid belum tersedia pada cache Dapodik.
- **Service:** `StudentIdentityService::createTemporary()` dan `StudentIdentityService::reconcilePending()`.
- **Aturan:** hanya NISN dan nama masukan yang disimpan; kecocokan memakai NISN, nama resmi berasal dari Dapodik, dan nilai awal dipertahankan dalam histori/audit.

### Ajukan Koreksi
- **Endpoint:** `GET /corrections`, `GET /corrections/create`, `POST /corrections`, dan `GET /corrections/{correction}`.
- **Controller:** `CorrectionController@index/create/store/show`.
- **Form Request:** `StoreCorrectionRequest`.
  - `target_type` (`case`, `follow_up`, `consultation`, atau `student`)
  - `target_id` (required, integer)
  - `field_name` (required, atribut yang diizinkan per jenis objek)
  - `proposed_value` (present, nullable hanya untuk atribut yang memang opsional)
  - `reason` (required, string)
- **Business Logic:** `CorrectionService::submit()` membaca nilai lama langsung dari objek terotorisasi, menormalisasi tanggal/jam/referensi, menolak nilai usulan yang sama, memberi nomor `KR-YYYY-XXXX`, dan mencatat audit. Klien tidak dapat menentukan nilai lama atau jenis koreksi secara sepihak.
- **Scope daftar/detail:** Guru BK melihat pengajuannya; Koordinator melihat seluruh pengajuan untuk tata kelola; Waka hanya pengajuan yang terkait kasus koordinasinya; Admin IT hanya koreksi master. Multi-role menggabungkan fungsi tanpa membuka objek layanan di luar fungsi yang sah.

### Verifikasi Koreksi Operasional
- **Endpoint:** `POST /corrections/{correction}/verify`.
- **Controller:** `CorrectionController@verify`
- **Form Request:** `VerifyCorrectionRequest`.
- **Authorization:** `Koordinator BK` only.
- **Request:** `decision` (`approved`, `rejected`, `revision_requested`) dan `review_notes` (wajib untuk penolakan/permintaan perbaikan).
- **Business Logic:** approval memakai row lock, memeriksa bahwa nilai objek belum berubah sejak pengajuan, lalu menerapkan perubahan melalui `CaseService`, `FollowUpService`, atau `ConsultationService`. Tidak tersedia mutasi field generik. Penolakan dan permintaan perbaikan tidak mengubah objek target.

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
  - Waka Kesiswaan hanya menerima kasus yang dikoordinasikan kepadanya dalam mode hanya-baca.
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

### Pusat & Ekspor Laporan
- **Endpoint:** `GET /reports/export`
- **Controller:** `ReportController@export`
- **Query Params:** `report_type`, `academic_year_id`, `classroom_id`, `date_start`, `date_end`, `format` (pdf, excel, csv).
- **Authorization:** `ReportService` otomatis memfilter data sesuai scope peran pengguna dan menyembunyikan catatan konseling sensitif.
