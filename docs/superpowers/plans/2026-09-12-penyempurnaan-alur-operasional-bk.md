# Rencana Implementasi Penyempurnaan Alur Operasional BK

**Tanggal rencana:** 12 September 2026  
**Branch integrasi:** `cobasidebar`  
**Branch production:** `main` — tidak disentuh selama pengembangan  
**Kapasitas tim:** 3 pengembang, masing-masing bekerja pada laptop dan branch terpisah
**Status:** Gate Pembuka Bersama dan addendum selesai; tiga branch fitur belum dibuat
**Integrator final:** Pengembang 1/pemilik Jalur A

## 1. Tujuan

Menyempurnakan alur tahun ajaran, pelayanan Guru BK, dan pemantauan Waka Kesiswaan tanpa memperlebar aplikasi dengan workflow yang tidak diperlukan.

Hasil akhirnya harus memenuhi aturan berikut:

1. Semua murid aktif tersedia sebagai data master dan memiliki histori penempatan per tahun ajaran.
2. Pergantian kelas tidak dilakukan otomatis. Sistem memakai impor daftar resmi atau daftar persiapan sekolah.
3. Murid yang belum muncul pada tahun target hanya diberi penanda turunan **Perlu Konfirmasi**. Sistem tidak menebak status naik, tinggal kelas, lulus, pindah, atau keluar.
4. Tahun ajaran hanya dapat diaktifkan Koordinator BK pada atau setelah tanggal mulai.
5. Pelayanan mendesak tetap dapat dicatat memakai identitas sementara NISN–nama ketika daftar tahun baru belum lengkap.
6. Hanya satu Guru BK menjadi penanggung jawab aktif suatu kasus. Guru lain tidak dapat mengubah kasus hanya karena mempunyai akses tambahan.
7. Kasus dan konsultasi memakai lima status yang konsisten.
8. Catatan berstatus selesai atau dibatalkan dikunci dari perubahan biasa. Koreksi tetap melalui alur koreksi terverifikasi.
9. Kasus berjalan tidak berpindah otomatis ketika tahun ajaran atau kelas berubah.
10. Waka Kesiswaan mempunyai portal pemantauan khusus dan dapat membaca ringkasan aman seluruh penanganan kasus sekolah.
11. Koordinasi dengan Waka tetap berlangsung di luar aplikasi. Aplikasi hanya menyimpan ringkasan hasil koordinasi.
12. Kode kasus tetap dibuat dan disimpan sebagai identitas internal sistem, tetapi tidak ditampilkan pada UI, laporan, CSV, dashboard, notifikasi, pencarian pengguna, atau ringkasan audit yang terlihat pengguna.

## 2. Keputusan Domain yang Dibekukan

### 2.1 Kode kasus

`cases.registration_number` dan primary key tetap dipakai backend untuk:

- identitas dan integritas data;
- unique constraint;
- relasi teknis;
- audit internal;
- pemeriksaan dan dukungan teknis.

Kode tersebut tidak dihapus dari database. Larangan hanya berlaku pada lapisan presentasi dan keluaran pengguna.

Pengguna mengenali kasus melalui:

- nama murid;
- kelas pada waktu pelayanan;
- bidang layanan;
- tanggal;
- status.

### 2.2 Status pelayanan

Kasus dan konsultasi memakai kontrak kode dan label yang sama:

| Kode internal | Label pengguna | Terminal |
|---|---|---:|
| `baru` | Baru dicatat | Tidak |
| `sedang_diproses` | Sedang diproses | Tidak |
| `membutuhkan_tindak_lanjut` | Membutuhkan tindak lanjut | Tidak |
| `selesai` | Selesai | Ya |
| `dibatalkan` | Dibatalkan | Ya |

Pemetaan data lama:

| Data lama | Data baru |
|---|---|
| Kasus `dalam_penanganan` | `sedang_diproses` |
| Konsultasi `dijadwalkan` | `baru` |
| Konsultasi `menunggu_konfirmasi` | `sedang_diproses` |
| Konsultasi `terlaksana` | `selesai` |
| `dibatalkan` | `dibatalkan` |

Status tidak berubah otomatis hanya karena waktu berlalu. Tanggal sesi dan tanggal tindak lanjut tetap menjadi informasi terpisah.

### 2.3 Batas akses Waka

Waka dapat melihat proyeksi aman seluruh kasus sekolah. Detail kasus hanya dapat dibuka jika kasus memang dikoordinasikan kepada Waka.

Proyeksi aman hanya memuat:

- nama murid;
- kelas historis pada tanggal layanan;
- bidang layanan;
- status;
- Guru BK penanggung jawab;
- tanggal pelayanan;
- Ringkasan Penanganan untuk Waka (`waka_summary`);
- jenis dan tanggal tindak lanjut berikutnya.

Proyeksi tidak boleh memuat:

- kode kasus;
- NISN;
- informasi awal yang sensitif;
- isi percakapan konseling;
- catatan internal profesional;
- catatan pribadi konselor;
- dokumen sensitif;
- narasi lain di luar daftar field aman.

Scope umum `BkCase::accessibleTo()`, `Student::accessibleTo()`, dan `Consultation::accessibleTo()` tidak diperluas. Portal Waka memakai query/read-model khusus agar akses ringkasan tidak membuka detail sensitif.

### 2.4 Koreksi data murid

Fitur baru untuk melaporkan kesalahan data master murid tidak dibuat pada tahap ini. Koordinasi dilakukan langsung di luar aplikasi.

Alur koreksi catatan pelayanan BK yang sudah selesai tetap dipertahankan. Ini berbeda dari koreksi data master murid.

### 2.5 Definisi data dan penugasan lengkap

Aktivasi tahun ajaran diblokir jika salah satu kondisi berikut tidak terpenuhi:

1. tahun target belum aktif;
2. tanggal mulai dan selesai tersedia, berurutan, serta tanggal sekarang berada di dalam periode;
3. tersedia minimal satu rombel aktif;
4. setiap rombel aktif mempunyai minimal satu keanggotaan murid aktif;
5. setiap murid hanya mempunyai satu keanggotaan aktif pada tahun target;
6. setiap rombel mempunyai tepat satu Guru BK aktif yang penugasannya mencakup tanggal mulai tahun ajaran.

Kondisi berikut hanya menjadi peringatan dan tidak memblokir aktivasi:

- murid **Perlu Konfirmasi**;
- data masih berasal dari `school_provisional`;
- identitas sementara masih menunggu rekonsiliasi.

`activationReadiness()` dan `activate()` wajib memakai aturan blocking yang sama agar tombol dan direct request tidak berbeda perilaku.

### 2.6 Konflik identitas sementara

1. Pembuatan identitas sementara ditolak jika NISN sudah tersedia pada master; Guru BK memilih murid master.
2. NISN exact tetap menjadi kunci identitas. Perbedaan nama saja tidak membuat murid baru.
3. Tepat satu murid Dapodik terverifikasi dengan NISN sama menjadi target rekonsiliasi; nama resmi dipakai dan nama masukan tetap diaudit.
4. Beberapa identitas sementara dengan NISN sama tetapi nama berbeda sebelum data resmi tersedia, kandidat lokal ganda, atau pertentangan `dapodik_id` dan NISN menghasilkan `ditahan_konflik`.
5. Konflik tidak mengubah relasi secara otomatis dan hanya diselesaikan Admin IT berdasarkan sumber resmi.
6. Rekonsiliasi mengunci identitas sementara, kandidat murid, serta issue sumber dalam satu transaksi untuk mencegah race dengan impor.

### 2.7 Ringkasan Penanganan untuk Waka

Gunakan satu field `cases.waka_summary` agar portal Waka tidak membaca narasi profesional mentah.

Aturannya:

- ditulis Guru BK penanggung jawab aktif;
- opsional selama status `baru`;
- wajib ketika kasus mulai diproses;
- maksimal 500 karakter;
- berisi tindakan operasional dan perkembangan umum secara faktual;
- tidak berisi percakapan konseling, diagnosis, informasi kesehatan/keluarga, atau catatan pribadi;
- tidak memerlukan persetujuan Koordinator;
- diperbarui menjadi hasil umum ketika kasus diselesaikan;
- terkunci pada status terminal dan hanya dapat diperbaiki melalui koreksi terverifikasi.

Jenis dan tanggal tindak lanjut ditampilkan dari data terstruktur. Portal Waka tidak mengambil `initial_info`, `internal_note`, hasil tindak lanjut lengkap, `next_plan`, atau `final_result` mentah.

### 2.8 Audit pembacaan dan ekspor Waka

Gunakan tiga event:

- `waka.monitoring.viewed` untuk response portal yang berhasil;
- `waka.monitoring.exported` setelah dataset ekspor berhasil disiapkan;
- `case.viewed_by_waka` untuk detail kasus terkoordinasi.

Audit portal memuat actor, timestamp, mode halaman, parameter tervalidasi, halaman/jumlah hasil atau format/jumlah ekspor, IP, dan user agent. Nama/NISN murid, kode kasus, `waka_summary`, serta narasi pelayanan tidak boleh masuk audit. Audit tetap append-only dan disimpan minimum tiga tahun.

### 2.9 Atomicity pengalihan owner

Pengalihan harus berjalan dalam satu transaksi dengan urutan:

1. lock baris kasus;
2. tolak kasus terminal;
3. lock seluruh assignment owner;
4. pastikan tepat satu owner aktif pada tanggal berlaku;
5. tutup owner lama satu hari sebelum tanggal pengalihan;
6. buat owner baru;
7. simpan audit dan notifikasi;
8. commit seluruh perubahan.

Kegagalan pada tahap mana pun me-rollback semuanya sehingga tidak ada dua owner atau masa tanpa owner akibat kegagalan parsial.

### 2.10 Integrator final

Pengembang 1/pemilik Jalur A merangkap sebagai integrator final karena jalurnya mempunyai dependensi lintas modul paling rendah.

Hanya integrator yang:

- menjaga dan menggabungkan PR ke `cobasidebar`;
- melakukan integrasi dengan urutan A, B, lalu C;
- memperbaiki perbedaan kontrak lintas jalur;
- menjalankan suite penuh, migration audit, smoke test, dan UAT akhir;
- mencatat hasil verification gate.

Pengembang Jalur B dan C mengirim PR dan tidak menggabungkannya sendiri ke `cobasidebar`.

## 3. Dampak Implementasi

### 3.1 Database

- Tidak diperlukan tabel baru untuk rollover, daftar **Perlu Konfirmasi**, atau portal Waka.
- Addendum gate menambahkan kolom nullable `cases.waka_summary` sepanjang maksimal 500 karakter sebagai satu-satunya narasi aman untuk proyeksi seluruh kasus Waka.
- Diperlukan satu migration data untuk menyelaraskan referensi status lama ke lima kode yang disepakati.
- Kolom kode kasus, generator, unique constraint, dan data lama tetap dipertahankan.
- Penugasan kasus tambahan yang sudah ada tidak dihapus agar histori tetap utuh.

### 3.2 Aturan bisnis

- Aktivasi tahun ajaran sebelum tanggal mulai harus ditolak di service, bukan hanya menyembunyikan tombol.
- Daftar **Perlu Konfirmasi** dihitung saat dibaca dan tidak menyimpan dugaan status akademik.
- Penanggung jawab aktif adalah assignment bertipe `owner`, bukan seluruh assignment aktif.
- Status `selesai` dan `dibatalkan` menjadi terminal pada policy dan service.
- Perpindahan kasus harus eksplisit melalui pengalihan oleh Koordinator BK.
- Pengalihan owner memakai satu transaksi dan row lock kasus/assignment.
- Rekonsiliasi identitas menahan konflik NISN/identitas sumber dan tidak memakai nama sebagai kunci.

### 3.3 UI

- Koordinator melihat status tahun ajaran: `Belum siap`, `Siap diaktifkan mulai ...`, `Aktif`, atau `Periode selesai`.
- Waka mendapat menu khusus: Ringkasan, Murid dengan Kasus, Laporan Penanganan, dan Laporan Sekolah.
- Filter laporan penanganan Waka hanya periode dan status.
- Kolom lain diurutkan melalui header tabel dengan daftar sorting yang dibatasi server.
- Kode kasus dihilangkan dari seluruh tampilan pengguna, tetapi ID/kode internal tetap bekerja di backend.

### 3.4 Privasi dan keamanan

- Akses Waka menjadi lebih luas pada tingkat ringkasan, sehingga keluaran harus dibangun dari allowlist field aman.
- Jangan mengirim model kasus mentah ke view Waka.
- Endpoint mutasi tetap tidak tersedia untuk Waka.
- Pembacaan portal Waka dan ekspor laporan tetap diaudit.
- Audit akses Waka tidak menyimpan identitas murid atau narasi pelayanan dan tetap mengikuti retensi minimum tiga tahun.

### 3.5 Integrasi eksternal

- Alur impor persiapan, pencocokan NISN exact, dan rekonsiliasi identitas sementara tetap dipakai.
- Tidak ada adapter production Dapodik atau e-Tatib baru dalam pekerjaan ini.
- Driver production tetap `unavailable` sampai kontrak provider tersedia dan lolos admission gate.

## 4. Gate Pembuka Bersama dan Addendum

Sebelum tiga branch fitur mulai mengubah kode, satu commit dasar harus dibuat di `cobasidebar`. Gate ini mencegah ketiga tim membuat asumsi yang berbeda.

Isi commit dasar:

1. Amendemen bagian terkait pada PRD/SRS v1.1 tanpa mengganti nama versi file.
2. Catat keputusan ringkas pada `CONTEXT.md` dan requirement index bila diperlukan.
3. Tambahkan kontrak status kecil `app/Support/ServiceRecordStatus.php` berisi lima kode, label, dan status terminal.
4. Sediakan dua route file kosong/terpisah untuk perubahan baru:
   - `routes/bk-services.php` milik Jalur B;
   - `routes/waka.php` milik Jalur C.
5. `routes/web.php` hanya diubah sekali pada gate ini untuk memuat kedua route file tersebut.
6. Catat nama route, field aman Waka, parameter filter/sort, dan aturan kode kasus dalam kontrak API.

Commit yang disarankan:

```text
docs: tetapkan aturan operasional BK terbaru
```

Setelah commit ini tersedia, ketiga pengembang membuat branch dari SHA yang sama. Tidak ada jalur yang perlu menunggu jalur lain selesai.

Addendum gate diselesaikan sebelum pembuatan branch untuk membekukan:

1. Pengembang 1 sebagai integrator final;
2. kondisi blocking dan warning kesiapan tahun ajaran;
3. prosedur konflik rekonsiliasi identitas sementara;
4. satu field `cases.waka_summary` sebagai narasi aman lintas Jalur B dan C;
5. event serta metadata audit pembacaan/ekspor Waka;
6. urutan transaksi atomik pengalihan owner.

Migration kolom `waka_summary` dimiliki baseline addendum dan tidak boleh diedit oleh Jalur B maupun C. Kedua jalur hanya memakai kontraknya.

## 5. Pembagian Kerja Paralel

### Jalur A — Pengembang 1: Tahun ajaran dan data rollover

**Branch:** `fitur/tahun-ajaran-baru`

Tujuan:

- mencegah aktivasi terlalu awal;
- menampilkan kesiapan aktivasi secara jelas;
- menampilkan murid yang perlu dikonfirmasi tanpa membuat workflow akademik baru;
- memperkuat rekonsiliasi identitas sementara dan konflik NISN.

File baru eksklusif:

- `app/Services/AcademicYearRolloverQuery.php`
- `app/Services/AcademicYearRolloverSummary.php`
- `resources/views/pages/data-master/_academic-year-rollover-exceptions.blade.php`
- `tests/Feature/AcademicYearRolloverTest.php`
- `tests/Feature/TemporaryIdentityConflictTest.php`

File existing eksklusif:

- `app/Services/AcademicYearPreparationService.php`
- `app/Services/StudentIdentityService.php`
- `app/Http/Controllers/Admin/DataMasterController.php`
- `resources/views/pages/data-master/_academic-year-preparation.blade.php`
- `resources/views/pages/assignments/classes/manage.blade.php`
- `tests/Feature/DelayedDapodikPreparationTest.php`

Urutan kerja:

1. Tulis test query rollover read-only.
2. Buat query service dan DTO ringkas.
3. Tambahkan enam pemeriksaan blocking dan tiga warning nonblocking pada `activationReadiness()`.
4. Gunakan pemeriksaan blocking yang sama pada `activate()`.
5. Tampilkan state aktivasi dan tombol hanya ketika state `ready`.
6. Tampilkan daftar **Perlu Konfirmasi**.
7. Kunci temporary identity, kandidat murid, dan issue sumber saat rekonsiliasi.
8. Tahan temporary identity bernama berbeda pada NISN yang sama sebelum data resmi tersedia serta pertentangan NISN/`dapodik_id`.
9. Jalankan focused test dan regression jalur.

Larangan jalur:

- tidak membuat migration;
- tidak mengubah route;
- tidak mengubah model murid atau keanggotaan;
- tidak menentukan naik, tinggal kelas, lulus, pindah, atau keluar;
- tidak menyentuh dashboard/laporan Waka atau status pelayanan.

Commit yang disarankan:

```text
feat: tampilkan kesiapan tahun ajaran baru
fix: batasi aktivasi sesuai tanggal mulai
feat: tampilkan murid yang perlu dikonfirmasi
```

### Jalur B — Pengembang 2: Pelayanan Guru BK dan Koordinator

**Branch:** `fitur/pelayanan-bk`

Tujuan:

- menyelaraskan status kasus dan konsultasi;
- memastikan hanya pemilik aktif yang dapat mengubah kasus;
- mengunci catatan terminal;
- menyediakan edit kasus yang sederhana;
- menyederhanakan pencatatan koordinasi;
- menghilangkan kode kasus dari UI operasional;
- mengelola `waka_summary` tanpa membuka narasi sensitif.

File baru eksklusif:

- migration penyelarasan data referensi status;
- `app/Http/Requests/UpdateCaseRequest.php`
- test baru untuk aturan lifecycle bila pemisahan dari test existing diperlukan.

File existing eksklusif:

- `database/seeders/ReferenceSeeder.php`
- `database/seeders/AuthorizationScenarioSeeder.php`
- `app/Models/BkCase.php`
- `app/Models/CaseAssignment.php`
- `app/Models/Consultation.php`
- `app/Policies/CasePolicy.php`
- `app/Policies/ConsultationPolicy.php`
- `app/Services/CaseService.php`
- `app/Services/ConsultationService.php`
- `app/Services/FollowUpService.php`
- `app/Services/AssignmentService.php`
- `app/Services/CorrectionService.php`
- `app/Http/Requests/AssignCaseRequest.php`
- `app/Http/Requests/StoreConsultationRequest.php`
- `app/Http/Requests/UpdateConsultationRequest.php`
- `app/Http/Controllers/CaseController.php`
- `app/Http/Controllers/ConsultationController.php`
- `app/Http/Controllers/AssignmentController.php`
- `app/Http/Controllers/CorrectionController.php`
- `resources/views/pages/cases/*`
- `resources/views/pages/consultations/*`
- `resources/views/pages/assignments/cases/index.blade.php`
- `routes/bk-services.php`
- `tests/Feature/CaseManagementTest.php`
- `tests/Feature/ConsultationManagementTest.php`

Urutan kerja:

1. Buat migration pemetaan status lama dan perbarui seeder.
2. Gunakan kontrak `ServiceRecordStatus` pada policy dan service.
3. Tambahkan `hasActiveOwnerFor()` dan `activeOwnerAssignment()`.
4. Ubah seluruh mutasi kasus agar memeriksa pemilik aktif di policy dan service.
5. Hentikan pembuatan assignment `additional` baru tanpa menghapus histori lama.
6. Terapkan urutan transaksi atomik pengalihan owner dan rollback pada kegagalan parsial.
7. Tambahkan form edit kasus untuk data inti dan `waka_summary` tanpa mengubah identitas murid atau sumber integrasi.
8. Wajibkan `waka_summary` maksimal 500 karakter ketika status meninggalkan `baru`, lalu kunci bersama status terminal.
9. Kunci kasus/konsultasi selesai atau dibatalkan.
10. Pastikan alur koreksi terverifikasi tetap dapat bekerja.
11. Sederhanakan pencatatan hasil koordinasi tanpa chat/persetujuan digital.
12. Hilangkan kode kasus dari halaman, pencarian, notifikasi, label koreksi, dan ringkasan audit yang tampil.
13. Jalankan focused test dan regression jalur.

Larangan jalur:

- tidak mengubah `ReportService`, `DashboardService`, sidebar, atau halaman Waka;
- tidak mengubah `routes/web.php`;
- tidak menghapus data assignment tambahan lama;
- tidak membuat fitur laporan koreksi data master murid.

Commit yang disarankan:

```text
feat: samakan status pelayanan BK
fix: batasi perubahan kasus pada penanggung jawab
fix: kunci catatan pelayanan yang sudah selesai
feat: tambah edit kasus yang masih berjalan
refactor: sembunyikan kode kasus dari tampilan kerja
```

### Jalur C — Pengembang 3: Portal Waka dan laporan

**Branch:** `fitur/monitoring-waka`

Tujuan:

- menyediakan pemantauan seluruh kasus dalam bentuk ringkasan aman;
- mempertahankan larangan membuka detail kasus yang tidak dikoordinasikan;
- menyederhanakan filter dan sorting laporan;
- menghilangkan kode kasus dari dashboard, laporan, dan ekspor;
- mengaudit pembacaan dan ekspor tanpa menyalin data sensitif.

File baru eksklusif:

- `app/Http/Controllers/WakaMonitoringController.php`
- `app/Http/Requests/WakaMonitoringRequest.php`
- `app/Policies/WakaMonitoringPolicy.php`
- `app/Services/WakaMonitoringService.php`
- `resources/views/pages/waka/monitoring.blade.php`
- `tests/Feature/WakaMonitoringTest.php`

File existing eksklusif:

- `app/Services/DashboardService.php`
- `app/Services/ReportService.php`
- `resources/views/components/sidebar.blade.php`
- halaman dashboard dan laporan yang berkaitan;
- `routes/waka.php`
- `tests/Feature/DashboardNotificationTest.php`
- `tests/Feature/ReportManagementTest.php`
- `tests/Feature/AuthorizationMatrixTest.php`
- `tests/Feature/FrontendPreviewTest.php`

Urutan kerja:

1. Tulis privacy/authorization tests dengan sentinel data sensitif.
2. Buat `WakaMonitoringRequest` dengan allowlist periode, status, sort, dan direction.
3. Buat policy khusus Waka.
4. Buat query service yang hanya menghasilkan array/DTO field aman dan membaca `waka_summary` sebagai satu-satunya narasi.
5. Buat satu halaman tabel yang dapat dipakai untuk Murid dengan Kasus dan Laporan Penanganan.
6. Tambahkan menu Waka tanpa membuka menu kerja Guru BK.
7. Selaraskan dashboard Waka menjadi ringkasan seluruh kasus.
8. Hapus kode kasus dari preview laporan, CSV, dashboard, dan keluaran lain milik jalur ini.
9. Catat `waka.monitoring.viewed` dan `waka.monitoring.exported` dengan parameter tervalidasi serta tanpa identitas/narasi murid.
10. Pastikan detail kasus nonterkoordinasi tetap `403` dan tidak mempunyai tautan detail.
11. Jalankan focused test dan regression jalur.

Filter Waka:

- periode dalam bentuk bulan `YYYY-MM`;
- status.

Sorting header yang diizinkan:

- murid;
- kelas;
- bidang layanan;
- status;
- Guru BK;
- tanggal.

Nilai request tidak boleh dipakai langsung sebagai nama kolom SQL. Semua pilihan sort dipetakan melalui allowlist server.

Larangan jalur:

- tidak memperluas scope akses model umum;
- tidak mengubah service mutasi kasus/konsultasi;
- tidak menambah migration;
- tidak membuat chat, komentar, atau tombol setuju/tolak untuk Waka;
- tidak mengirim model kasus mentah ke view.

Commit yang disarankan:

```text
feat: tambah halaman pemantauan Waka Kesiswaan
feat: sederhanakan laporan penanganan untuk Waka
fix: lindungi informasi konseling pada akses Waka
refactor: sembunyikan kode kasus dari laporan
```

## 6. Matriks Kepemilikan Agar Tidak Bentrok

| Area/file bersama | Pemilik tunggal |
|---|---|
| PRD, SRS, `CONTEXT.md`, requirement index, kontrak API | Integrator pada gate pembuka/akhir |
| `routes/web.php` | Integrator pada gate pembuka |
| `routes/bk-services.php` | Jalur B |
| `routes/waka.php` | Jalur C |
| `ServiceRecordStatus.php` | Integrator pada gate pembuka; setelah itu dibekukan |
| Migration `cases.waka_summary` | Integrator pada addendum gate; setelah itu dibekukan |
| Migration dan reference seeder status | Jalur B |
| Model/policy/service operasional kasus dan konsultasi | Jalur B |
| Tahun ajaran, rollover, dan rekonsiliasi identitas sementara | Jalur A |
| Dashboard, laporan, sidebar, portal Waka | Jalur C |
| Style/CSS bersama | Tidak diubah; gunakan komponen existing |

Aturan kerja:

1. Setiap file hanya mempunyai satu pemilik selama fase paralel.
2. Perubahan di luar daftar kepemilikan harus dikirim sebagai catatan kepada pemilik, bukan diedit langsung.
3. Jangan melakukan merge silang antarbranch fitur selama pekerjaan berlangsung.
4. Setiap branch hanya melakukan rebase satu kali terhadap `cobasidebar` sebelum PR dinyatakan siap.
5. Test baru dibuat dalam file jalurnya sendiri jika file test existing dimiliki jalur lain.
6. Pengembang 1 adalah integrator final dan menangani perubahan kecil lintas jalur setelah ketiga PR tersedia.
7. Pengembang Jalur B dan C tidak menggabungkan PR sendiri ke `cobasidebar`.

## 7. Urutan Waktu Paralel

| Waktu | Jalur A | Jalur B | Jalur C |
|---|---|---|---|
| Hari 0, maksimal 2 jam | Membantu verifikasi aturan rollover | Membantu verifikasi kontrak status | Membantu verifikasi field aman Waka |
| Hari 1 | Query rollover, readiness, dan konflik identitas | Migration status, owner, terminal lock | Privacy test dan safe read-model |
| Hari 2 | UI kesiapan dan pengecualian | Edit kasus, ringkasan Waka, koordinasi, penyembunyian kode | Portal, audit akses, dashboard, laporan, penyembunyian kode |
| Hari 3 pagi | Focused regression | Focused regression | Focused regression |
| Hari 3 siang | Siap digabung | Siap digabung | Siap digabung |
| Hari 4 | Integrasi, suite penuh, smoke test, UAT per role | Integrasi | Integrasi |

Estimasi total:

- waktu kalender: sekitar 4 hari kerja;
- usaha tim: sekitar 7–9 person-days;
- cadangan: 1 hari bila migration data lama atau privacy test menemukan ketidaksesuaian.

## 8. Urutan Penggabungan

Ketiga jalur dikerjakan bersamaan, tetapi digabung secara berurutan untuk menjaga database dan domain stabil:

1. Jalur A — tahun ajaran, rollover, dan rekonsiliasi identitas.
2. Jalur B — status dan aturan pelayanan.
3. Jalur C — portal Waka dan laporan.
4. Pengembang 1 sebagai integrator membuat commit integrasi untuk penyelarasan kontrak, dokumen, dan perbaikan test lintas jalur.

Urutan merge tidak menjadi dependensi waktu pengerjaan. Jalur C menggunakan kontrak status dari gate pembuka dan fixture miliknya sendiri; ia tidak perlu menunggu migration Jalur B selesai.

Setiap PR menargetkan `cobasidebar`, bukan `main`.

## 9. Verification Gate

### Gate Jalur A

- Tahun lengkap sebelum tanggal mulai menampilkan `Siap diaktifkan mulai ...` tanpa tombol aktif.
- Direct POST sebelum tanggal mulai ditolak dan tahun lama tetap aktif.
- Pada tanggal mulai, Koordinator dapat mengaktifkan tahun target.
- Tahun dengan periode selesai tidak dapat diaktifkan.
- Rombel tanpa murid aktif, penempatan aktif ganda, atau rombel tanpa tepat satu Guru BK aktif menghalangi aktivasi.
- Impor parsial menampilkan **Perlu Konfirmasi** tanpa memblokir seluruh aktivasi.
- Menambah penempatan target menghilangkan murid dari daftar.
- Query daftar tidak memutasi data atau audit.
- Nama berbeda pada NISN sementara yang sama ditahan sampai data resmi tersedia.
- Pertentangan NISN dan `dapodik_id` tidak mengubah relasi dan menghasilkan konflik untuk Admin IT.
- Rekonsiliasi bersamaan dengan impor tidak membuat tautan ganda.

### Gate Jalur B

- Lima status tersedia konsisten pada kasus dan konsultasi.
- Migration memetakan seluruh status lama tanpa kehilangan relasi.
- Pembuat kasus menjadi satu pemilik awal.
- Assignment tambahan tidak dapat mengubah kasus.
- Dua owner aktif yang tumpang tindih ditolak.
- Pengalihan menutup owner lama dan membuka owner baru.
- Kegagalan setelah owner lama ditutup me-rollback transaksi dan mempertahankan owner lama.
- Dua permintaan pengalihan bersamaan diserialisasi oleh lock kasus/assignment.
- Kasus/konsultasi selesai atau dibatalkan menolak edit langsung.
- Koreksi terverifikasi tetap dapat diterapkan.
- Kode kasus tetap unik di database tetapi tidak tampil pada keluaran operasional.
- `waka_summary` opsional pada `baru`, wajib setelah mulai diproses, maksimal 500 karakter, dan terkunci bersama kasus terminal.

### Gate Jalur C

- Waka aktif dapat melihat ringkasan seluruh kasus sekolah.
- Peran lain, akun nonaktif, dan guest ditolak.
- Detail nonterkoordinasi tetap `403`.
- Portal tidak menyediakan aksi mutasi.
- Filter hanya periode dan status.
- Sorting hanya menerima field allowlist.
- Pagination stabil dan tidak menimbulkan N+1 query.
- Sentinel NISN, kode kasus, catatan internal, private note, dan isi sensitif tidak muncul.
- Preview dan CSV tidak memuat kode kasus.
- Kode internal di database tetap tidak berubah.
- Portal hanya membaca `waka_summary` sebagai narasi kasus; sentinel pada field naratif lain tidak tampil.
- Pembacaan dan ekspor menghasilkan event audit dengan parameter aman tanpa identitas/narasi murid.

### Gate integrasi akhir

1. Seluruh test PHP lulus.
2. Test authorization matrix lulus.
3. Test migration dijalankan pada salinan database yang berisi status lama.
4. Pint/lint lulus.
5. Frontend checker dan build lulus.
6. `git diff --check` bersih.
7. Smoke test empat peran:
   - Guru BK;
   - Koordinator BK;
   - Waka Kesiswaan;
   - Admin IT.
8. Pencarian repo memastikan `registration_number` hanya tersisa pada backend/internal test yang diizinkan.
9. UAT responsive dilakukan pada tampilan mobile dan desktop yang sudah menjadi baseline.
10. Audit Waka diverifikasi tidak memuat nama, NISN, kode kasus, `waka_summary`, atau narasi pelayanan.
11. Integrator final mencatat SHA ketiga PR dan hasil penggabungan pada dokumen verifikasi.

## 10. Risiko dan Mitigasi

| Risiko | Mitigasi |
|---|---|
| Tahun lama nonaktif terlalu cepat | Date gate di service, transaksi, dan direct POST test |
| Sistem menebak status akademik | **Perlu Konfirmasi** dihitung read-only, tanpa field keputusan manual |
| Definisi kesiapan berbeda antara UI dan direct request | `activationReadiness()` dan `activate()` memakai enam kondisi blocking yang sama |
| Rekonsiliasi menautkan identitas yang salah | Lock kandidat dan issue, NISN exact sebagai kunci, serta konflik ditahan untuk Admin IT |
| Waka melihat data sensitif | Safe read-model, field allowlist, sentinel privacy test, detail nonterkoordinasi `403` |
| Narasi profesional bocor melalui laporan Waka | Hanya `waka_summary` maksimal 500 karakter yang menjadi narasi proyeksi seluruh kasus |
| Audit akses Waka justru menyalin data sensitif | Audit hanya menyimpan parameter tervalidasi dan hitungan, tanpa identitas/narasi murid |
| Kode kasus masih bocor | Pemeriksaan HTML, CSV, dashboard, notifikasi, audit summary, placeholder, dan repository search |
| Guru tambahan dapat mengubah kasus | Semua mutasi memeriksa assignment `owner` di policy dan service |
| Pengalihan gagal setelah owner lama ditutup | Satu transaksi, row lock kasus/assignment, dan rollback test |
| Catatan terminal masih dapat diedit | Daftar status terminal tunggal dipakai policy dan service |
| Migration mengubah arti status lama | Pemetaan eksplisit dan audit jumlah record sebelum/sesudah |
| Konflik merge | Satu pemilik per file dan route file terpisah |
| Perubahan melebar | Tidak menambah chat, approval Waka, workflow akademik, atau adapter provider |

## 11. Definition of Done

Pekerjaan dinyatakan selesai apabila:

1. Semua verification gate lulus.
2. PR tiga jalur telah digabung ke `cobasidebar`.
3. Requirement v1.1, kontrak API, dan implementasi mempunyai istilah serta aturan yang sama.
4. Tidak ada kode kasus pada keluaran pengguna.
5. Kode kasus tetap tersedia dan valid secara internal.
6. Tidak ada perluasan akses detail sensitif untuk Waka.
7. Tidak ada perubahan pada `main`.
8. Hasil smoke test dan UAT dicatat dalam dokumen verifikasi integrasi.
9. Pengembang 1 sebagai integrator final telah menandatangani seluruh gate integrasi.

## 12. Catatan Pelaksanaan Addendum Gate

Addendum Gate diselesaikan pada 12 September 2026 di branch `cobasidebar` sebelum pembuatan tiga branch fitur.

Hasil yang dibekukan:

1. Pengembang 1/pemilik Jalur A menjadi integrator final.
2. Enam kondisi blocking dan tiga warning nonblocking aktivasi tahun ajaran telah didefinisikan.
3. Konflik identitas sementara ditahan untuk Admin IT dengan NISN exact sebagai kunci dan nama resmi mengikuti sumber terverifikasi.
4. Migration baseline menambahkan `cases.waka_summary` nullable maksimal 500 karakter.
5. Audit Waka memakai event `waka.monitoring.viewed`, `waka.monitoring.exported`, dan `case.viewed_by_waka` tanpa menyimpan identitas/narasi murid.
6. Pengalihan owner harus berjalan atomik dengan row lock dan rollback penuh pada kegagalan.

Hasil verifikasi addendum:

- focused migration contract: 1 test, 3 assertion, lulus;
- seluruh suite PHP: 358 test, 2.764 assertion, lulus;
- Pint: lulus;
- tiga branch fitur: belum dibuat;
- branch `main`: tidak disentuh.
