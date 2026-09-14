# Penyederhanaan Operasional, Akun, dan Skema Data

**Tanggal:** 14 September 2026
**Status:** Menunggu review pengguna
**Target:** UI operasional, lifecycle kasus dan konsultasi, akun, serta hasil akhir skema database Ruang BK
**Referensi perilaku:** PRD/SRS Aplikasi BK v1.1, terutama `ACC-*`, `AUTH-*`, `MD-*`, `CASE-*`, `CONS-*`, `DASH-*`, `NOT-01`, `AUD-01`, dan `COR-*`

## 1. Tujuan

Menyederhanakan alur kerja Ruang BK dengan menghilangkan fitur yang belum
dibutuhkan, mempertahankan jejak audit internal, serta merapikan hasil akhir
database tanpa mengorbankan histori, privasi, dan otorisasi.

Desain ini juga mencegah spaghetti database dengan memastikan bahwa:

- satu fakta bisnis hanya mempunyai satu sumber data utama;
- tabel dipisahkan berdasarkan tanggung jawab domain yang jelas;
- data turunan dashboard dan laporan dihitung melalui query/service, bukan
  disalin ke tabel rekap baru;
- relasi penting memakai foreign key, unique constraint, dan index yang sesuai;
- controller, service, policy, model, dan view tidak mengambil alih tanggung
  jawab lapisan lain;
- tabel atau kolom hanya ditambahkan bila mempunyai kebutuhan bisnis dan
  lifecycle yang nyata.

## 2. Ringkasan Keputusan

Keputusan produk yang disetujui adalah:

1. Menu dan halaman Koreksi Data, Notifikasi, dan Riwayat Perubahan
   dihilangkan dari seluruh UI.
2. Pembuatan notifikasi backend dihentikan. Audit append-only tetap berjalan,
   tetapi tidak mempunyai halaman pembaca pada MVP.
3. Panel dashboard `Aktivitas Terbaru` diganti dengan informasi operasional
   yang aman dan sesuai role.
4. Aksi `Batalkan Kasus` dihilangkan. Status `dibatalkan` tidak tersedia untuk
   kasus atau konsultasi baru.
5. Tombol `Hapus` berarti arsip menggunakan soft delete, bukan penghapusan
   permanen langsung.
6. Hanya Guru BK pemilik catatan yang masih berwenang yang dapat mengedit atau
   mengarsipkan kasus dan konsultasi.
7. Data berstatus `selesai` tetap dapat diedit setelah konfirmasi tambahan dan
   pengisian alasan. Status terminalnya tidak dapat diubah.
8. Semua Guru BK aktif dapat mencatat murid lulus, pindah, keluar, atau
   mengundurkan diri tanpa membuat data murid atau catatan keluar ganda.
9. Reset akun memakai password sementara unik dan memaksa pengguna mengganti
   password setelah login.
10. Enam tabel yang tidak lagi diperlukan dikeluarkan dari hasil akhir skema,
    lalu satu tabel `student_departures` ditambahkan.

## 3. Batas Scope

Objek operasional yang mengikuti aturan edit terminal dan arsip pada desain ini
adalah:

- kasus pada tabel `cases`;
- konsultasi pada tabel `consultations`.

Tindak lanjut, koordinasi Waka, prestasi, data master, data integrasi, dan audit
tetap mengikuti lifecycle masing-masing. Perluasan aturan arsip atau edit
terminal ke objek tersebut memerlukan keputusan terpisah agar perilakunya tidak
diterapkan secara implisit.

## 4. Penghentian Fitur Koreksi, Notifikasi, dan Riwayat

### 4.1 Koreksi Data

Seluruh route, controller, request, service, policy, model, relasi model, view,
sidebar, quick action, fixture preview, dan test khusus Koreksi Data dihapus.
Tabel `corrections` dikeluarkan melalui migration lanjutan.

Kesalahan data master tidak diajukan melalui aplikasi. Guru BK melaporkannya di
luar aplikasi kepada Admin IT, kemudian perbaikan dilakukan pada sumber resmi
dan masuk kembali melalui proses sinkronisasi atau rekonsiliasi yang sudah ada.

Pengeditan catatan operasional selesai tidak memakai pengajuan koreksi baru.
Alasan perubahan disimpan bersama event audit perubahan sehingga tidak
membutuhkan tabel workflow koreksi tersendiri.

### 4.2 Notifikasi

Seluruh route, controller, service, model, view, badge, sidebar, fixture
preview, dan test notifikasi dihapus. Pemanggilan `NotificationService` pada
service kasus, konsultasi, penugasan, dan koordinasi juga dihapus.

Tabel `user_notifications` dikeluarkan melalui migration lanjutan. Penghapusan
ini tidak menghentikan audit dan tidak mengubah validasi transaksi utama.
Jadwal serta pekerjaan penting tetap terlihat pada dashboard dan halaman
operasional terkait.

### 4.3 Riwayat Perubahan

Route, controller, view, sidebar, fixture preview, dan test halaman Riwayat
Perubahan dihapus. Tabel `audit_logs` tetap dipertahankan sebagai jejak audit
append-only yang hanya digunakan backend dan kebutuhan pemeriksaan resmi.

Tidak ada route umum untuk membaca audit pada MVP. Akses audit pada masa depan
harus dirancang sebagai fitur terpisah dengan policy dan proyeksi data aman.

## 5. Pengganti Panel Dashboard

Panel `Aktivitas Terbaru` tidak lagi membaca `audit_logs`. Panel pengganti
bersifat role-aware:

| Role | Informasi pengganti |
|---|---|
| Guru BK | Cakupan kelas aktif, jumlah murid dalam scope, kasus aktif, dan tindak lanjut terdekat. |
| Koordinator BK | Cakupan penugasan seluruh Guru BK, jumlah kelas aktif, dan kelas yang belum mempunyai pengampu efektif. |
| Admin IT | Kesiapan akun, status data master, konflik sinkronisasi, dan status integrasi tanpa membuka isi layanan BK. |
| Waka Kesiswaan | Ringkasan aman tingkat sekolah dari proyeksi kasus yang diizinkan, tanpa aksi mutasi. |

Data panel dihitung dari tabel domain yang sudah menjadi sumber kebenaran.
Tidak dibuat tabel `dashboard_stats`, cache permanen, atau salinan histori.
Cache aplikasi biasa boleh digunakan bila kelak terbukti perlu, tetapi bukan
bagian dari kontrak data dan tidak boleh menjadi sumber kebenaran.

## 6. Lifecycle Kasus dan Konsultasi

### 6.1 Status

Status aktif kasus dan konsultasi untuk data baru adalah:

- `baru`;
- `sedang_diproses`;
- `membutuhkan_tindak_lanjut`;
- `selesai`.

Kode `dibatalkan` tidak muncul pada pilihan form, filter, aksi, atau seeder
fresh database. Migration lanjutan menangani database pengembangan yang sudah
mempunyai data lama dengan cara:

1. mengarsipkan kasus atau konsultasi lama berstatus `dibatalkan`;
2. menonaktifkan nilai referensi `dibatalkan` bila masih ada;
3. mempertahankan nilai historis tersebut hanya selama masih dirujuk data lama.

Koordinasi Waka dan tindak lanjut tidak otomatis kehilangan status pembatalan
karena keduanya mempunyai lifecycle berbeda dan berada di luar keputusan ini.

### 6.2 Edit Data Belum Selesai

Kasus atau konsultasi yang belum berstatus `selesai` dapat langsung diedit oleh
Guru BK pemilik catatan yang masih berwenang. Server tetap menjalankan policy,
validasi Form Request, dan pengecekan kepemilikan di dalam service.

Definisi pemilik catatan:

- kasus: Guru BK penanggung jawab aktif pada `case_assignments`;
- konsultasi: Guru BK pada `consultations.counselor_id` yang masih mempunyai
  kewenangan profesional atas identitas murid terkait.

Penugasan kelas saja tidak memberikan hak untuk mengubah catatan lama milik
Guru BK lain. Koordinator BK, Waka, Admin IT, dan Guru BK lain tidak memperoleh
hak edit hanya karena dapat membaca ringkasan atau mengatur penugasan.

### 6.3 Edit Data Selesai

Ketika tombol edit untuk data `selesai` dipilih, pengguna melihat pemberitahuan:

> Data ini telah dinyatakan selesai. Apakah Anda setuju melanjutkan pengeditan?

Jika pengguna melanjutkan:

- form menampilkan field `Alasan perubahan` yang wajib diisi;
- alasan berupa teks 10 sampai 500 karakter;
- server memeriksa ulang status dan kepemilikan setelah record dikunci;
- `status_id`, `closed_at`, identitas murid, dan pemilik catatan tidak dapat
  diubah melalui alur ini;
- perubahan field lain mengikuti validasi normal objek;
- audit menyimpan actor, waktu, alasan, serta nilai sebelum dan sesudah;
- status tetap `selesai` setelah perubahan berhasil.

Konfirmasi pada browser bukan pengganti otorisasi. Request langsung tanpa
alasan atau dari pengguna yang tidak berwenang ditolak server.

### 6.4 Arsip melalui Tombol Hapus

Tombol tetap berlabel `Hapus` agar sederhana bagi pengguna, tetapi aksi backend
melakukan soft delete. Setiap penghapusan meminta konfirmasi, baik data masih
aktif maupun sudah selesai.

Perilakunya:

- kasus atau konsultasi diisi `deleted_at` secara atomik;
- relasi histori tidak dihapus permanen;
- record arsip tidak tampil pada daftar, dashboard, laporan, pencarian, atau
  pilihan form operasional biasa;
- audit menyimpan actor, waktu, jenis objek, dan aksi arsip;
- hanya pemilik catatan yang memenuhi aturan bagian 6.2 yang dapat mengarsipkan;
- direct URL dan request hasil manipulasi parameter tetap menghasilkan `403`
  atau `404` sesuai kebijakan anti-kebocoran existing.

Arsip tidak mengubah status menjadi `dibatalkan` dan tidak mengubah tanggal
retensi murid. Restore belum disediakan pada UI MVP.

## 7. Pencatatan Murid Keluar

### 7.1 Model Data

Tambahkan tabel `student_departures` sebagai satu-satunya sumber fakta bahwa
seorang murid telah keluar dari sekolah.

| Kolom | Aturan |
|---|---|
| `id` | Primary key. |
| `student_id` | Foreign key ke `students`, wajib, dan unique. |
| `departure_type` | Allowlist: `lulus`, `pindah`, `mengundurkan_diri`, atau `keluar_lainnya`. |
| `effective_date` | Tanggal keluar resmi dan dasar perhitungan retensi. |
| `source` | `manual_bk` atau `dapodik`. |
| `source_identifier` | Identifier sumber bila tersedia; nullable dan tidak ditampilkan sebagai data pengguna. |
| `recorded_by` | Guru BK pencatat awal; foreign key nullable bila akun kelak dihapus. |
| `confirmed_at` | Waktu konfirmasi dari sumber resmi; nullable. |
| `created_at`, `updated_at` | Timestamp perubahan baris. |

Tidak ditambahkan kolom duplikat seperti `students.departure_type`,
`students.departure_date`, atau `students.is_graduated`. Status aktif murid
untuk query operasional diturunkan melalui relasi yang jelas terhadap
`student_departures` dan membership efektif, bukan dari beberapa flag yang
dapat saling bertentangan.

### 7.2 Alur Pencatatan

- Semua akun Guru BK aktif dapat mencatat murid keluar dari halaman murid
  selama murid tersebut berada dalam scope profesionalnya.
- Transaksi mengunci murid dan memeriksa unique `student_id` sebelum menyimpan.
- Jika catatan sudah ada, sistem menampilkan data existing dan tidak membuat
  baris kedua.
- Selama belum dikonfirmasi sumber resmi, jenis dan tanggal keluar hanya dapat
  diperbaiki oleh Guru BK yang masih mempunyai scope profesional atas murid;
  setiap perubahan diaudit. Setelah dikonfirmasi, data menjadi read-only bagi
  pengguna dan hanya proses rekonsiliasi resmi yang dapat memperbarui barisnya.
- Konfirmasi Dapodik memperbarui baris yang sama, mengubah `source` menjadi
  `dapodik`, mengisi `source_identifier`/`confirmed_at`, dan mencatat nilai
  sebelum-sesudah pada audit.
- Nama atau kemiripan nama tidak pernah menjadi kunci pencocokan; identitas
  tetap memakai ID murid internal yang telah dipetakan melalui NISN exact.
- Waka hanya menerima dampak agregat yang aman. Admin IT tidak memperoleh akses
  isi layanan BK dari fitur ini.

Constraint database menjadi perlindungan terakhir terhadap pencatatan ganda;
validasi UI dan service memberi pesan Bahasa Indonesia yang lebih ramah.

## 8. Retensi dan Penghapusan Permanen

Masa retensi dihitung dari `student_departures.effective_date`, bukan dari:

- tanggal murid pertama kali dimasukkan ke database;
- tanggal kasus atau konsultasi dibuat;
- tanggal record diarsipkan;
- tanggal sinkronisasi terakhir.

Kebutuhan lanjutan adalah menjadikan data layak dipurge ketika tepat mencapai
tiga tahun setelah tanggal keluar resmi. Eksekusi dapat ditahan hanya oleh
legal hold atau kegagalan verifikasi yang tercatat. Mengarsipkan kasus atau
konsultasi tidak memulai ulang masa retensi tersebut.

Otomasi purge belum dibuat pada scope implementasi ini. Sebelum purge
diaktifkan harus ada plan terpisah yang menentukan dependency order, legal
hold, backup, dry-run, jumlah record, dan audit eksekusi. Sampai plan tersebut
disetujui, data hanya diarsipkan dan tidak dihapus permanen.

Arsip tidak membuat database tidak sehat dengan sendirinya. Kesehatan dijaga
dengan query default yang mengecualikan `deleted_at`, index pada pola query
operasional yang benar-benar digunakan, pagination, serta pemeriksaan query
plan. Index `deleted_at` tidak ditambahkan membabi buta; setiap penambahan harus
dibuktikan oleh pola filter dan pengujian SQLite/MySQL.

## 9. Password Sementara dan Pemulihan Admin IT

### 9.1 Reset oleh Admin IT

Admin IT dapat membuat akun atau mereset password akun lain. Sistem:

1. menghasilkan password sementara unik untuk setiap tindakan;
2. menyimpan hanya hash password;
3. mengisi `users.must_change_password = true`;
4. mengisi `users.temporary_password_expires_at` dengan masa berlaku default
   24 jam yang dapat dikonfigurasi;
5. menghapus seluruh sesi database milik akun tersebut;
6. mencatat actor, target, jenis tindakan, dan waktu pada audit tanpa password;
7. menampilkan password sementara satu kali kepada Admin IT melalui response
   yang tidak boleh dicache.

Tidak ada satu password default bersama, master password, tautan reset email,
atau endpoint reset publik tersembunyi.

### 9.2 Login Wajib Ganti Password

Middleware setelah autentikasi membatasi akun dengan
`must_change_password = true` hanya ke halaman Ganti Password dan Logout.
Password baru harus lolos aturan password aplikasi, berbeda dari password
sementara, dan disimpan atomik bersama:

- `must_change_password = false`;
- `temporary_password_expires_at = null`;
- `password_changed_at = now()`;
- penghapusan sesi lain;
- event audit tanpa nilai password.

Password sementara kedaluwarsa ditolak dan harus direset kembali oleh Admin
IT atau dipulihkan melalui prosedur server untuk Admin IT tunggal.

### 9.3 Admin IT Lupa Password

- Jika ada Admin IT lain yang aktif, Admin IT tersebut memakai alur reset biasa.
- Jika hanya ada satu Admin IT, operator server menjalankan perintah interaktif
  `php artisan sibk:reset-admin-password`.
- Perintah memverifikasi email target mempunyai role Admin IT, meminta password
  sementara secara hidden input, memutus sesi lama, menandai wajib ganti
  password, dan menulis audit sistem.
- Password tidak diterima sebagai argument command, tidak dicetak ke log, dan
  tidak disimpan dalam file.

Tabel `password_reset_tokens` tidak diperlukan selama reset mandiri melalui
email tidak disediakan.

## 10. Target Hasil Akhir Skema Database

Audit migration saat ini menghasilkan 35 tabel termasuk `migrations`.

Tabel yang dikeluarkan:

1. `corrections`;
2. `user_notifications`;
3. `password_reset_tokens`;
4. `jobs`;
5. `job_batches`;
6. `failed_jobs`.

Tabel yang ditambahkan:

1. `student_departures`.

Target akhir adalah 30 tabel termasuk `migrations`, atau 29 tabel aplikasi dan
runtime:

| Domain | Tabel yang dipertahankan |
|---|---|
| Akun dan runtime | `users`, `sessions`, `roles`, `user_roles`, `cache`, `cache_locks` |
| Master sekolah | `academic_years`, `classrooms`, `students`, `student_class_memberships`, `student_departures` |
| Referensi dan audit | `references`, `audit_logs` |
| Penugasan dan identitas | `teacher_assignments`, `temporary_students`, `identity_reconciliations` |
| Integrasi | `external_sync_runs`, `external_sync_issues`, `external_tatib_records`, `integration_settings`, `dapodik_sync_preview_items` |
| Kasus | `cases`, `case_assignments`, `case_coordinations`, `follow_ups`, `case_etatib_links` |
| Konsultasi | `consultations`, `consultation_private_notes` |
| Prestasi | `achievements` |

Queue memakai `QUEUE_CONNECTION=sync` untuk MVP. Tabel queue hanya boleh
ditambahkan kembali ketika pekerjaan asynchronous benar-benar dibutuhkan,
terutama setelah adapter production lolos contract admission gate.

`sessions` dipertahankan karena diperlukan untuk pemutusan sesi saat reset
password. `cache` dan `cache_locks` dipertahankan untuk runtime Laravel dan
koordinasi lock, bukan sebagai penyimpanan domain.

## 11. Aturan Anti-Spaghetti Database

Implementation plan wajib mempertahankan aturan berikut:

1. **Satu sumber kebenaran.** Fakta keluar murid hanya berada di
   `student_departures`; status layanan hanya berasal dari relasi referensi;
   audit tidak menjadi sumber state operasional.
2. **Tidak ada tabel rekap UI.** Dashboard dan laporan memakai read service dan
   query ter-scope atas tabel domain existing.
3. **Tidak ada EAV baru.** Field domain penting memakai kolom eksplisit. JSON
   hanya dipakai untuk snapshot audit atau payload teknis yang memang tidak
   menjadi field pencarian utama.
4. **Tidak ada service raksasa.** Account recovery, lifecycle layanan,
   pencatatan murid keluar, dashboard, dan audit tetap berada pada service yang
   fokus dan berkomunikasi melalui kontrak yang jelas.
5. **Relasi dijaga database.** Foreign key, unique constraint, transaksi, dan
   row lock melindungi invariant yang tidak cukup dijaga UI.
6. **Tidak ada flag duplikat.** Jangan menyimpan status keluar atau hasil
   agregat yang sama pada beberapa tabel hanya untuk memudahkan tampilan.
7. **Arsip bukan status bisnis.** `deleted_at` menandai visibilitas record,
   sedangkan `status_id` menjelaskan lifecycle layanan.
8. **Audit append-only.** Alasan edit terminal disimpan pada event audit yang
   sama dengan perubahan, bukan pada tabel alasan terpisah.
9. **Query lintas domain terpusat.** Scope akses tetap berada pada model/query
   scope dan policy; controller atau Blade tidak merakit aturan role sendiri.
10. **Index berbasis penggunaan.** Index ditentukan dari filter, join, sorting,
    dan query plan nyata; bukan menambahkan index ke setiap kolom.

## 12. Strategi Migration

File migration lama tidak dihapus atau ditulis ulang. Perubahan menggunakan
migration lanjutan yang idempotent pada urutan Laravel dan menghasilkan skema
akhir yang sama pada database fresh maupun database pengembangan existing.

Urutan konseptual:

1. tambah field lifecycle password pada `users`;
2. buat `student_departures` beserta constraint dan index;
3. arsipkan data kasus/konsultasi legacy yang berstatus `dibatalkan` lalu
   nonaktifkan referensinya;
4. lepaskan foreign key atau relasi yang masih bergantung pada `corrections` dan
   `user_notifications` bila ada;
5. drop enam tabel yang disetujui;
6. sesuaikan seeder dan konfigurasi queue;
7. verifikasi jumlah serta struktur tabel pada SQLite dan MySQL.

Migration tidak menjalankan reset database, tidak menghapus data lokal di luar
target yang disetujui, dan tidak membaca file API rahasia. Reset/fresh database
lokal tetap memerlukan persetujuan terpisah.

## 13. Dampak Implementasi

### Route dan Middleware

- hapus route koreksi, notifikasi, riwayat, fixture terkait, dan aksi
  `cases.deactivate` lama;
- tambah route arsip kasus dan konsultasi dengan penamaan generik `destroy`;
- tambah route pencatatan status keluar murid;
- tambah route ganti password wajib;
- tambah middleware `must_change_password`.

### Controller dan Form Request

- controller tetap tipis dan hanya mengorkestrasi request, policy, service, dan
  response;
- tambah Form Request khusus edit terminal, arsip, pencatatan murid keluar, dan
  ganti/reset password;
- hapus controller/request fitur yang dihentikan.

### Service dan Model

- hapus `CorrectionService`, `NotificationService`, dan pemanggilannya;
- ubah `CaseService` dan `ConsultationService` agar mendukung edit terminal
  beralasan serta arsip;
- tambah service fokus untuk `StudentDeparture` dan password sementara;
- hapus relasi `corrections`/`notifications` dari model;
- pertahankan `AuditService` sebagai satu-satunya pintu penulisan audit.

### Policy dan Query Scope

- policy menjadi gate utama edit/arsip pada object level;
- service memeriksa kembali invariant kepemilikan setelah row lock;
- query daftar secara default mengecualikan arsip;
- scope dashboard/laporan tidak berubah menjadi pemeriksaan role di Blade.

### View dan Navigasi

- hilangkan seluruh link Koreksi Data, Notifikasi, dan Riwayat Perubahan;
- hilangkan `Ajukan Koreksi` serta `Batalkan Kasus`;
- tambahkan konfirmasi edit selesai, alasan wajib, dan konfirmasi Hapus;
- ganti panel aktivitas dashboard dengan informasi pada bagian 5;
- semua teks pengguna memakai Bahasa Indonesia dan istilah `murid`.

### Test

- hapus atau ganti test kontrak fitur yang dihentikan;
- tambah test policy dan direct URL untuk tiap role;
- tambah test edit belum selesai, edit selesai tanpa alasan, edit selesai dengan
  alasan, status immutable, dan concurrent ownership change;
- tambah test arsip serta pengecualian record dari seluruh query baca;
- tambah test unique departure, race/duplicate submission, dan konfirmasi
  Dapodik pada baris yang sama;
- tambah test password sementara, expiry, wajib ganti, session invalidation,
  dan recovery Admin IT;
- verifikasi migration fresh dan incremental pada SQLite serta MySQL.

### Dokumentasi

PRD/SRS v1.1 perlu diamendemen tanpa mengubah nama versi. Bagian yang perlu
diselaraskan minimum:

- `NOT-01` untuk penghentian notifikasi;
- `COR-01` dan field/entity Koreksi untuk penghentian workflow koreksi;
- `CASE-04`, `CASE-14`, `CONS-03`, dan bagian status untuk penghapusan status
  pembatalan serta edit terminal beralasan;
- `CASE-13` dan aturan konsultasi untuk kepemilikan edit/arsip;
- `DASH-01` sampai `DASH-03` untuk panel pengganti;
- `ACC-01` untuk password sementara dan pemulihan akun;
- `MD-*`, kebutuhan data, dan retensi untuk `student_departures`;
- `AUTH-*`, privasi, audit, traceability matrix, `docs/requirements-index.md`,
  `docs/api-contract.md`, dan `docs/development-log.md`.

Dokumen v1.0 tetap arsip dan tidak diubah.

## 14. Error Handling dan Keamanan

- Semua otorisasi mutasi diterapkan server-side; visibilitas tombol bukan
  kontrol keamanan.
- Conflict dari unique departure diterjemahkan menjadi pesan yang aman dan
  tidak menampilkan SQL.
- Request stale pada edit/arsip gagal tertutup bila owner atau status berubah
  setelah form dibuka.
- Password sementara, password baru, credential API, dan isi sensitif tidak
  masuk audit, log, exception context, flash session, URL, atau source control.
- Response yang menampilkan password sementara memakai `Cache-Control:
  no-store` dan hanya tersedia satu kali.
- Penghapusan sesi dilakukan setelah transaksi reset password berhasil.
- Audit perubahan terminal menyimpan alasan, tetapi UI umum tidak mempunyai
  akses untuk membacanya.

## 15. Acceptance Criteria

Spesifikasi dianggap terpenuhi bila:

- tidak ada menu, route pengguna, halaman, badge, quick action, atau pembuatan
  backend untuk Koreksi Data dan Notifikasi;
- tidak ada halaman Riwayat Perubahan, sedangkan `audit_logs` tetap append-only;
- dashboard tiap role menampilkan informasi pengganti yang sesuai scope dan
  tidak membaca daftar aktivitas audit;
- kasus dan konsultasi baru tidak dapat memakai status `dibatalkan`;
- tidak ada aksi `Batalkan Kasus`;
- tombol Hapus mengarsipkan, selalu meminta konfirmasi, dan hanya berhasil bagi
  Guru BK pemilik catatan yang masih berwenang;
- record arsip tidak muncul pada seluruh daftar, dashboard, laporan, pencarian,
  ekspor, dan pilihan relasi operasional;
- data selesai hanya dapat diedit pemiliknya setelah konfirmasi dan alasan 10
  sampai 500 karakter;
- status, waktu selesai, identitas, dan pemilik data selesai tidak berubah;
- audit edit terminal menyimpan actor, alasan, waktu, serta before/after tanpa
  field rahasia;
- satu murid hanya mempunyai paling banyak satu `student_departures`;
- input Guru BK dan konfirmasi Dapodik memperbarui baris departure yang sama;
- perhitungan retensi memakai tanggal keluar resmi dan otomasi purge belum
  berjalan tanpa plan lanjutan;
- reset akun menghasilkan password sementara unik, memutus sesi lama, dan
  memaksa penggantian password;
- pemulihan Admin IT tunggal hanya dapat dilakukan melalui command server
  interaktif yang aman;
- hasil akhir migration menghasilkan 30 tabel termasuk `migrations`;
- `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `corrections`,
  dan `user_notifications` tidak ada pada hasil akhir;
- `sessions`, `cache`, `cache_locks`, `audit_logs`, dan seluruh tabel domain yang
  tercantum pada bagian 10 tetap tersedia;
- tidak ada tabel rekap dashboard/laporan, flag keluar murid duplikat, atau
  tabel alasan edit tambahan;
- focused tests, full test suite, Pint, cache Laravel, build Vite, privacy scan,
  migration verification, dan `git diff --check` lulus.

## 16. Verification Gates

Implementation plan harus memecah pekerjaan menjadi gate berurutan:

1. requirement dan API contract telah diamendemen serta tidak kontradiktif;
2. migration fresh/incremental menghasilkan target 30 tabel;
3. fitur UI dan backend yang dihentikan tidak lagi mempunyai consumer;
4. lifecycle kasus/konsultasi, policy, arsip, dan audit lulus focused tests;
5. student departure lulus constraint, concurrency, dan reconciliation tests;
6. temporary password dan admin recovery lulus security tests;
7. dashboard role-aware lulus scope/privacy tests;
8. seluruh suite dan pemeriksaan kualitas repository lulus.

Task berikutnya tidak boleh dimulai sebelum gate task sebelumnya selesai.
Adapter production Dapodik/e-Tatib tetap di luar scope sampai kontrak provider
tersedia dan lolos contract admission gate.

## 17. Di Luar Scope

- reset atau penghapusan database lokal;
- penghapusan atau penulisan ulang file migration lama;
- penghapusan permanen otomatis sebelum plan retensi disetujui;
- restore arsip melalui UI;
- halaman pembaca audit;
- reset mandiri melalui email;
- queue asynchronous dan tabel queue;
- tabel rekap/materialized view untuk dashboard atau laporan;
- adapter production Dapodik/e-Tatib;
- perubahan visual Penpot atau redesign UI yang sudah disetujui.
