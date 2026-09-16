# Penyederhanaan Laporan Guru BK dan Koordinator BK

**Tanggal:** 14 September 2026
**Status:** Disetujui untuk penyusunan implementation plan
**Target:** Halaman Laporan Guru BK dan Koordinator BK Ruang BK
**Referensi perilaku:** PRD/SRS Aplikasi BK v1.1 (`REP-01` sampai `REP-04`)
**Referensi visual:** Implementasi Portal Waka, Penpot `22 - UI High-Fidelity Final`, dan `22.5 - Style Guide`

## 1. Tujuan

Mengganti katalog tujuh kartu laporan Guru BK dan Koordinator BK dengan satu
halaman laporan bertab yang lebih ringkas. Setiap tab menampilkan satu tabel
rekap dengan satu baris per murid, sehingga pengguna tidak perlu memilih
beberapa laporan terpisah untuk menjawab pertanyaan yang berdekatan.

Penyederhanaan ini tidak mengubah:

- hak akses Guru BK dan Koordinator BK;
- sumber data dan aturan periode historis;
- perlindungan informasi sensitif;
- kemampuan cetak dan ekspor CSV;
- kontrak laporan lama yang masih diperlukan untuk kompatibilitas.

## 2. Keputusan Produk yang Disetujui

Halaman `/reports` mempunyai tiga tab:

1. `Pelanggaran & Poin`;
2. `Layanan BK`;
3. `Prestasi`.

Ketiga tab memakai pola yang sama:

- satu baris mewakili satu murid;
- pencarian berdasarkan nama murid;
- filter kelas;
- konteks tahun ajaran dan periode;
- pagination;
- cetak tabel aktif;
- ekspor CSV tabel aktif;
- representasi kartu pada layar kecil.

Implementasi memakai teknologi existing: Eloquent, Form Request, Blade,
Bootstrap, SCSS, JavaScript ringan existing, dan Laravel Pagination. Perubahan
ini tidak menambahkan Spatie Query Builder, Yajra DataTables, Livewire Tables,
Livewire PowerGrid, Filament Tables, jQuery, atau framework tabel lain.

Guru BK hanya melihat murid dan data dalam scope profesionalnya. Koordinator BK
melihat rekap gabungan sesuai kewenangannya dan memperoleh filter Guru BK hanya
pada tab yang memang mempunyai relasi penanggung jawab layanan.

## 3. Arsitektur Informasi

```text
Laporan
|
+-- Pelanggaran & Poin
|   +-- Rekap satu baris per murid
|
+-- Layanan BK
|   +-- Rekap satu baris per murid
|
+-- Prestasi
    +-- Rekap satu baris per murid
```

URL canonical:

```text
GET /reports?tab=pelanggaran
GET /reports?tab=layanan
GET /reports?tab=prestasi
GET /reports/export?tab={tab}&format=csv
```

Tab default adalah `layanan`. Tab aktif dan seluruh filter tervalidasi disimpan
pada URL agar dapat di-bookmark, dibagikan kepada pengguna yang mempunyai
kewenangan sama, serta bekerja benar dengan tombol Back/Forward browser.

## 4. Filter Bersama

Filter utama pada seluruh tab:

| Parameter | Perilaku |
|---|---|
| `q` | Mencari nama murid dalam scope actor; tidak menambah pencarian NISN. |
| `academic_year_id` | Default tahun ajaran aktif; hanya menerima tahun yang tersedia. |
| `date_start` | Default `starts_on` tahun ajaran terpilih; bila tahun ajaran belum tersedia, gunakan awal tahun kalender seperti perilaku laporan existing. |
| `date_end` | Default `ends_on` tahun ajaran terpilih; bila tahun ajaran belum tersedia, gunakan akhir tahun kalender seperti perilaku laporan existing. |
| `classroom_id` | Hanya kelas pada tahun ajaran terpilih dan dalam scope actor. |
| `page` | Nomor halaman minimum 1. |

Filter tambahan tab `Layanan BK` untuk Koordinator BK:

| Parameter | Perilaku |
|---|---|
| `counselor_id` | Membatasi data layanan pada Guru BK aktif yang dipilih. Tidak tampil untuk Guru BK biasa. |

Aturan validasi:

- `tab` hanya menerima `pelanggaran`, `layanan`, atau `prestasi`;
- `q` maksimum 100 karakter dan karakter kontrol ditolak;
- tanggal akhir tidak boleh sebelum tanggal awal;
- kelas harus menjadi bagian dari tahun ajaran terpilih;
- ID hasil forge atau di luar scope ditolak server;
- parameter milik tab lain diabaikan dari query dan tidak diteruskan ke ekspor;
- request invalid kembali ke tab terkait dengan pesan Bahasa Indonesia dan
  tidak mengekspos query, identifier internal, atau data murid.

## 5. Tab Pelanggaran & Poin

### 5.1 Tujuan

Menjawab dalam satu tabel: murid mana yang mempunyai pelanggaran, berada di
kelas mana saat kejadian, berapa jumlah pelanggaran dan total poinnya, serta
kapan pelanggaran terakhir tercatat.

### 5.2 Kontrak baris

Satu baris mewakili satu identitas murid dalam periode terpilih. Pengelompokan
memakai ID internal `students.id`, bukan nama. Record e-Tatib yang belum
tertaut hanya boleh ikut bila query scope existing memang mengizinkannya;
identitas tersebut tidak boleh digabung berdasarkan kemiripan nama.

Kolom desktop:

| Kolom | Aturan |
|---|---|
| Murid | Inisial murid dan NISN tersamarkan mengikuti kebijakan laporan existing. |
| Kelas | Kelas historis yang efektif pada tanggal pelanggaran terakhir dalam periode. |
| Jumlah pelanggaran | Jumlah record e-Tatib aktif dalam filter. |
| Total poin | Penjumlahan poin record dalam filter. |
| Pelanggaran terakhir | Jenis dan tanggal kejadian terbaru; tidak memuat body mentah provider. |

Urutan default adalah pelanggaran terakhir terbaru, lalu ID identitas sebagai
tie-breaker stabil. Tabel tidak menambahkan aksi mutasi atau tautan yang
memperluas akses ke record di luar scope.

## 6. Tab Layanan BK

### 6.1 Tujuan

Menjawab dalam satu tabel: murid mana yang memperoleh layanan, berapa jumlah
kasus, konsultasi, dan tindak lanjutnya, apakah masih memerlukan tindak lanjut,
serta kapan layanan terakhir dilakukan.

### 6.2 Kontrak baris

Satu baris mewakili satu identitas layanan yang mempunyai minimal satu kasus,
konsultasi, atau tindak lanjut dalam periode dan scope terpilih. Identitas yang
sudah terverifikasi memakai `student:{students.id}`. Identitas sementara yang
belum direkonsiliasi memakai `temporary:{temporary_students.id}` dan diberi
penanda `Belum terverifikasi Dapodik`. Nama tidak pernah menjadi grouping key.

Identitas sementara hanya muncul bila actor memang berwenang atas kasus atau
konsultasi terkait. Kelasnya ditampilkan sebagai `Belum tersedia` sampai
rekonsiliasi menghasilkan murid dan membership yang sah.

Kolom desktop:

| Kolom | Aturan |
|---|---|
| Murid | Inisial murid dan NISN tersamarkan mengikuti laporan umum existing. |
| Kelas | Kelas historis pada tanggal aktivitas layanan terakhir. |
| Kasus | Jumlah kasus dengan `service_date` dalam periode. |
| Konsultasi | Jumlah konsultasi dengan `session_date` dalam periode. |
| Tindak lanjut | Jumlah tindak lanjut yang termasuk periode berdasarkan tanggal pelaksanaan, atau tanggal rencana bila belum dilaksanakan. |
| Perlu tindak lanjut | Jumlah tindak lanjut nonterminal yang masih memerlukan penyelesaian. |
| Layanan terakhir | Tanggal aktual terbaru dari kasus, konsultasi, atau tindak lanjut yang telah dilaksanakan; jadwal masa depan tidak dianggap layanan selesai. |

Filter Guru BK pada akun Koordinator membatasi seluruh hitungan pada layanan
yang menjadi kewenangan Guru BK terpilih. Hitungan lintas jenis data tidak boleh
menduplikasi satu record atau memindahkan kepemilikan kasus.
Untuk kasus dan tindak lanjut, kepemilikan ditentukan dari penanggung jawab yang
efektif pada tanggal aktivitas; konsultasi memakai `consultations.counselor_id`.
Filter tidak memakai pengguna yang pertama membuat atau terakhir mencatat record.

## 7. Tab Prestasi

### 7.1 Tujuan

Menjawab dalam satu tabel: murid mana yang mempunyai prestasi, berapa total
prestasinya, berapa yang sudah terverifikasi, tingkat tertinggi yang telah
terverifikasi, dan prestasi terbaru dalam periode.

### 7.2 Kontrak baris

Satu baris mewakili satu `Student` yang mempunyai minimal satu prestasi dalam
periode dan scope terpilih.

Kolom desktop:

| Kolom | Aturan |
|---|---|
| Murid | Inisial murid dan NISN tersamarkan. |
| Kelas | Kelas historis pada tanggal prestasi terbaru. |
| Jumlah prestasi | Jumlah seluruh prestasi yang boleh dilihat actor dalam periode. |
| Terverifikasi | Jumlah prestasi berstatus terverifikasi. |
| Tingkat tertinggi | Label tingkat tertinggi dari prestasi terverifikasi berdasarkan urutan referensi resmi, bukan perbandingan teks. |
| Prestasi terbaru | Nama kegiatan dan tanggal prestasi terbaru tanpa bukti atau catatan privat. |

Jika belum ada prestasi terverifikasi, tingkat tertinggi ditampilkan sebagai
`Belum ada`. Bukti, path file, catatan pemeriksaan, dan catatan prestasi tidak
masuk tabel atau CSV.

## 8. Presentasi Desktop dan Mobile

### 8.1 Desktop

```text
+---------------------------------------------------------------------+
| Laporan                                                             |
| Rekap operasional sesuai kewenangan Anda                            |
+---------------------------------------------------------------------+

[ Pelanggaran & Poin ] [ Layanan BK ] [ Prestasi ]

+---------------------------------------------------------------------+
| Cari murid [____________] Kelas [Semua] Tahun [2026/2027]          |
| Periode [01/07/2026] - [30/06/2027] [Terapkan] [Reset]             |
+---------------------------------------------------------------------+

24 murid ditemukan                         [Cetak] [Unduh CSV]

+---------------------------------------------------------------------+
| Murid | Kelas | ...kolom rekap sesuai tab...                       |
+---------------------------------------------------------------------+
| ...                                                                 |
+---------------------------------------------------------------------+
```

Tab menggunakan link native, `aria-current="page"`, dan focus ring existing.
Tab tidak bergantung pada JavaScript untuk navigasi atau pemuatan data.

### 8.2 Mobile

Pada lebar kecil:

- tab boleh memakai overflow horizontal terkontrol dan tab aktif selalu tampak;
- filter disusun vertikal;
- tombol utama memiliki target minimum 44 x 44 CSS pixel;
- setiap row tabel mempunyai representasi kartu;
- label kolom tetap ditampilkan pada kartu;
- tidak ada horizontal scroll pada halaman utama;
- urutan informasi kartu mengikuti urutan kolom desktop.

## 9. Komponen dan Batas Arsitektur

Implementasi menggunakan service read-only khusus untuk rekap tiga tab. Service
tersebut mengembalikan array aman dan paginator, bukan model Eloquent mentah ke
Blade. Controller tetap tipis: validasi request, pemeriksaan policy, pemanggilan
service, dan pemilihan view/response.

Gunakan `OperationalReportRecapService` untuk query/agregasi tiga tab dan
`OperationalReportRequest` untuk validasi mode tab pada halaman serta ekspor.
`ReportService` dan `ReportRequest` tetap melayani mode legacy.

Kontrak service minimum:

```php
interface OperationalReportRecap
{
    public function build(User $actor, array $filters): array;

    public function exportRows(User $actor, array $filters): iterable;
}
```

Boundary berikut wajib dipertahankan:

- request validation tidak berada di Blade;
- query dan agregasi tidak berada di controller;
- view tidak menerima `Student`, `BkCase`, `Consultation`, `FollowUp`,
  `ExternalTatibRecord`, atau `Achievement` mentah;
- data tabel dan CSV berasal dari pipeline filter dan scope yang sama;
- jumlah hasil dan nilai agregat per baris berasal dari seluruh dataset
  terfilter, bukan hanya row yang kebetulan tampil pada halaman;
- daftar dipaginasi 20 baris dengan urutan stabil;
- ekspor membaca data bertahap agar tidak memuat seluruh dataset ke memori.

Tab menggunakan pola visual Portal Waka, tetapi class atau komponen bersama
harus bernama generik. Bila style `.sibk-waka-tabs` digeneralisasi, selector lama
tetap dipertahankan sampai seluruh pemakai bermigrasi dan test regresi lulus.

## 10. Kompatibilitas Laporan Lama

Endpoint berikut tetap tersedia selama perubahan ini:

```text
GET /reports/preview?type={legacy-type}
GET /reports/export?type={legacy-type}&format=csv
```

Tujuh nilai `type` lama tetap diterima oleh `ReportRequest` dan `ReportService`.
Halaman `/reports` tidak lagi menampilkan tujuh kartu tersebut. Kompatibilitas
dipertahankan untuk bookmark, automated research scenario, dan consumer internal
yang sudah ada.

Request mode tab dan mode legacy tidak boleh tercampur. Bila `tab` dan `type`
dikirim bersamaan, server menolak request dengan pesan validasi yang aman.

Penghapusan kontrak legacy, bila kelak diperlukan, harus memakai keputusan dan
plan terpisah setelah seluruh consumer terinventarisasi.

## 11. Otorisasi dan Privasi

- `ReportPolicy` tetap menjadi gate akses laporan.
- Guru BK hanya melihat scope kelas aktif, kasus khusus, dan periode efektif
  sesuai aturan domain existing.
- Koordinator BK memperoleh rekap gabungan seluruh Guru BK aktif.
- Admin IT dan akun Waka murni tetap ditolak.
- Akun multi-role memakai capability Guru BK/Koordinator yang sah; portal Waka
  tetap melalui `/waka/reports`.
- Nama lengkap tidak masuk tabel/CSV laporan umum; identitas memakai inisial dan
  NISN tersamarkan seperti kontrak existing.
- Kode kasus, informasi awal, catatan internal, isi konsultasi sensitif,
  `final_result`, `next_plan`, bukti prestasi, dan dokumen tidak boleh masuk view,
  CSV, flash, log, atau error.
- Pencarian nama hanya memengaruhi query dan tidak boleh disalin ke audit atau
  exception context baru.

## 12. Empty, Error, dan Pagination State

### Empty

- Jelaskan bahwa tidak ada murid pada periode atau filter terpilih.
- Jika filter aktif, tampilkan tombol `Reset filter`.
- Jangan menampilkan tabel kosong tanpa penjelasan.

### Error

- Ringkasan error ditempatkan sebelum tab atau panel filter.
- Pesan menggunakan Bahasa Indonesia dan mempunyai `role="alert"`.
- Fokus keyboard dapat diarahkan ke ringkasan error tanpa menutup header.
- Error tidak menampilkan SQL, stack trace, identifier internal, atau field
  sensitif.

### Pagination

- Gunakan paginator server-side 20 row per halaman.
- Link pagination mempertahankan tab dan filter aktif.
- Perubahan filter selalu kembali ke halaman pertama.
- Query memakai tie-breaker ID agar urutan stabil pada SQLite dan MySQL.

## 13. Cetak dan Ekspor

- `Cetak` hanya mencetak judul, konteks filter, waktu pembuatan, identitas
  pembuat, ringkasan, dan tabel tab aktif.
- CSV menggunakan kolom yang sama dengan tabel tab aktif dan dataset yang sama
  tanpa pagination.
- CSV tetap menggunakan UTF-8 BOM dan perlindungan formula injection.
- Nama file berasal dari allowlist tab dan timestamp server.
- Filter aktif diteruskan ke URL ekspor.
- XLSX dan PDF server tetap di luar scope sampai `DEP-07` disahkan.

## 14. Perubahan Source of Truth yang Diperlukan

Implementation plan harus memperbarui:

- `docs/requirements/PRD_Aplikasi_BK_v1.1.md` pada Arsitektur Informasi dan
  Laporan P0;
- `docs/requirements/SRS_Aplikasi_BK_v1.1.md` pada `REP-01` sampai `REP-04`;
- `docs/requirements-index.md`;
- `docs/api-contract.md`;
- `docs/development-log.md` setelah verification gate selesai.

PRD/SRS v1.0 tetap tidak diubah di [repository arsip privat](https://github.com/Aflahul/sibk-docs-archive/tree/main/requirements/v1.0), bukan di repository aplikasi aktif.

## 15. Acceptance Criteria

Spesifikasi dianggap terpenuhi bila:

- `/reports` tidak lagi menampilkan tujuh kartu laporan;
- halaman mempunyai tiga tab deep-link: Pelanggaran & Poin, Layanan BK, dan
  Prestasi;
- setiap tab menampilkan tepat satu tabel rekap, satu baris per murid atau
  identitas sementara yang masih sah pada tab Layanan;
- identitas layanan sementara yang masih sah tidak hilang dari tab Layanan dan
  tidak digabung berdasarkan kemiripan nama;
- pencarian nama, filter kelas, tahun ajaran, dan periode bekerja konsisten;
- filter Guru BK hanya tersedia bagi Koordinator pada tab Layanan BK;
- tab Pelanggaran menampilkan jumlah pelanggaran, total poin, dan pelanggaran
  terakhir;
- tab Layanan menampilkan jumlah kasus, konsultasi, tindak lanjut, kebutuhan
  tindak lanjut, dan layanan terakhir;
- tab Prestasi menampilkan jumlah prestasi, jumlah terverifikasi, tingkat
  tertinggi, dan prestasi terbaru;
- Guru BK dan Koordinator menerima dataset sesuai scope masing-masing;
- Admin IT dan Waka murni tetap ditolak;
- view dan CSV tidak memuat field terlarang;
- cetak dan CSV memakai filter serta dataset tab aktif;
- endpoint `type` lama tetap berfungsi tetapi tidak muncul pada katalog UI;
- desktop, tablet, dan ponsel tidak mengalami horizontal overflow halaman;
- navigasi tab, filter, reset, pagination, cetak, dan ekspor dapat digunakan
  dengan keyboard serta memiliki focus state terlihat;
- focused tests, full suite, Pint, cache Laravel, frontend checker, build Vite,
  privacy scan, dan `git diff --check` lulus;
- UAT mencakup Guru BK dan Koordinator pada 1440 x 900, 768 x 1024, dan
  390 x 844.

## 16. Di Luar Scope

- perubahan database atau migration;
- penambahan Spatie Query Builder, Yajra DataTables, Livewire Tables, Livewire
  PowerGrid, Filament Tables, jQuery, atau framework tabel baru;
- perubahan Portal Waka;
- adapter production Dapodik/e-Tatib;
- penghapusan endpoint laporan legacy;
- format XLSX atau PDF server;
- workflow penerbitan atau pengesahan laporan;
- perubahan Penpot atau design token baru;
- grafik, chart, atau dashboard laporan baru;
- aksi mutasi data dari halaman laporan.
