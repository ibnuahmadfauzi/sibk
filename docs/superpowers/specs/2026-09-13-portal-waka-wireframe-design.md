# Draft Wireframe Portal Waka Kesiswaan

**Tanggal:** 13 September 2026  
**Status:** Draft Wireframe v0.1  
**Target:** Portal Waka Kesiswaan Ruang BK  
**Referensi perilaku:** PRD/SRS v1.1 dan rencana penyempurnaan alur operasional BK  
**Referensi visual:** Penpot `22 - UI High-Fidelity Final` dan `22.5 - Style Guide`

## 1. Status dan Batas Dokumen

Dokumen ini mengunci struktur informasi awal portal Waka, tetapi belum menjadi
spesifikasi implementasi final.

Bagian `Laporan Akhir` hanya mengunci tempatnya pada navigasi. Halaman tersebut
belum mempunyai susunan informasi final dan sementara menampilkan status
`Dalam pengembangan`. Dokumen ini belum mengunci:

- format resmi laporan akhir;
- susunan final dokumen cetak;
- format ekspor PDF atau dokumen kantor;
- status dan siklus revisi laporan;
- proses penerbitan dan pembatalan penerbitan;
- kebutuhan tanda tangan atau pengesahan sekolah.

Keputusan tersebut harus divalidasi bersama Koordinator BK, Waka Kesiswaan,
dan pihak sekolah sebelum implementasi workflow laporan akhir.

## 2. Tujuan UX

Portal Waka harus membantu pengguna menjawab tiga pertanyaan secara cepat:

1. Apa kondisi layanan BK sekolah saat ini?
2. Murid atau penanganan mana yang membutuhkan perhatian?
3. Bagaimana hasil layanan BK pada periode tertentu?

Portal tidak menjadi salinan workspace Guru BK. Waka memperoleh proyeksi aman
tingkat sekolah, tanpa hak mutasi dan tanpa membuka catatan profesional mentah.

## 3. Prinsip Desain

### 3.1 Gunakan design system existing

Wireframe tidak memperkenalkan palet, font, radius, shadow, atau bahasa visual
baru. Implementasi wajib menggunakan token dan komponen existing, terutama:

- `--sibk-color-page`;
- `--sibk-color-surface` dan `--sibk-color-surface-raised`;
- `--sibk-color-text`, `--sibk-color-text-muted`, dan `--sibk-color-primary`;
- token warna semantic success, warning, danger, dan info;
- `--sibk-radius-*` dan `--sibk-shadow-*`;
- `--sibk-font-family` dan `--sibk-font-heading`;
- `sibk-panel`, `sibk-badge`, `sibk-table`, field, tombol, dan sidebar existing.

Tidak boleh menggunakan raw hex baru pada komponen portal Waka.

### 3.2 Informasi sebelum dekorasi

- Angka ringkasan memakai kartu existing tanpa grafik dekoratif.
- Informasi yang membutuhkan perhatian ditempatkan sebelum tabel lengkap.
- Warna tidak menjadi satu-satunya penanda status; selalu sertakan label teks.
- Tidak ada animasi baru yang tidak menjelaskan perubahan state.
- Tidak ada emoji sebagai ikon; gunakan SVG existing dengan gaya stroke konsisten.

### 3.3 Privasi secara default

Portal Waka boleh memuat:

- nama murid;
- kelas historis pada tanggal layanan;
- bidang layanan;
- status;
- Guru BK penanggung jawab;
- tanggal pelayanan;
- `waka_summary`;
- jenis dan tanggal tindak lanjut berikutnya.

Portal Waka tidak boleh memuat:

- NISN;
- kode kasus;
- informasi awal sensitif;
- percakapan konseling;
- catatan internal atau catatan pribadi konselor;
- dokumen sensitif;
- hasil lengkap dan narasi mentah tindak lanjut.

## 4. Arsitektur Informasi

```text
RUANG BK
|
+-- Dashboard
|
+-- PEMANTAUAN WAKA
|   +-- Murid dengan Kasus
|   +-- Laporan
|
+-- UTILITAS
    +-- Notifikasi
    +-- Akun Saya
```

### 4.1 Model mental halaman

| Halaman | Pertanyaan utama |
|---|---|
| Dashboard | Apa kondisi sekolah sekarang? |
| Murid dengan Kasus | Murid mana yang sedang atau pernah memperoleh penanganan? |
| Laporan | Bagaimana perkembangan dan hasil layanan dalam suatu periode? |

Menu kerja Guru BK, seperti pembuatan kasus, konsultasi, tindak lanjut, dan
pengalihan kasus, tidak ditampilkan kepada akun Waka murni.

## 5. Wireframe Dashboard Waka

### 5.1 Desktop

```text
+---------------------------------------------------------------------+
| Dashboard Waka Kesiswaan                           Tahun 2026/2027   |
| Ringkasan kondisi layanan BK tingkat sekolah                        |
+---------------------------------------------------------------------+

+----------------+ +----------------+ +----------------+ +------------+
| KASUS BERJALAN | | SEDANG DIPROSES| | PERLU TINDAK   | | SELESAI    |
|      24        | |      15        | | LANJUT      9  | | BULAN INI  |
+----------------+ +----------------+ +----------------+ +------------+

+--------------------------------------------+ +----------------------+
| Membutuhkan Perhatian                     | | Komposisi Status     |
|                                            | |                      |
| Murid    Kelas  Guru BK  Tindak lanjut     | | Baru              8 |
| Aisyah   XI RPL Bu Ratna  15 Sep 2026      | | Diproses         15 |
| Bintang  X TKJ  Pak Dimas 16 Sep 2026      | | Perlu lanjut      9 |
|                                            | | Selesai           12 |
| [Lihat semua penanganan]                   | | Dibatalkan         2 |
+--------------------------------------------+ +----------------------+

+---------------------------------------------------------------------+
| Penanganan Terbaru                                                  |
| Murid | Kelas | Bidang | Status | Guru BK | Tanggal | Ringkasan     |
| ...                                                                 |
|                                      [Buka Monitoring Penanganan ->] |
+---------------------------------------------------------------------+
```

### 5.2 Urutan informasi

1. Konteks tahun ajaran aktif.
2. Empat angka kondisi utama.
3. Penanganan yang membutuhkan perhatian.
4. Komposisi lima status layanan.
5. Penanganan terbaru dan tautan menuju laporan lengkap.

### 5.3 Mobile

```text
[Dashboard Waka]
[Tahun ajaran aktif]

[Kasus berjalan] [Sedang diproses]
[Perlu lanjut]   [Selesai bulan ini]

[Membutuhkan Perhatian]
  - kartu penanganan 1
  - kartu penanganan 2
  [Lihat semua]

[Komposisi Status]

[Penanganan Terbaru]
  - maksimal lima kartu
  [Buka Monitoring Penanganan]
```

Pada mobile, informasi prioritas muncul sebelum komposisi statistik. Tidak ada
tabel lebar yang memaksa scroll horizontal.

## 6. Wireframe Murid dengan Kasus

### 6.1 Kontrak daftar

- Satu baris mewakili satu murid, bukan satu kasus.
- Jumlah kasus dihitung pada periode terpilih.
- Jumlah aktif hanya menghitung kasus nonterminal.
- Status terbaru berasal dari penanganan terakhir pada periode.
- Kelas berasal dari kelas historis pada tanggal penanganan terbaru.
- Guru BK berasal dari owner kasus aktif terbaru atau penanganan terakhir.

### 6.2 Desktop

```text
+---------------------------------------------------------------------+
| Murid dengan Kasus                                                  |
| Daftar murid yang memperoleh penanganan pada periode terpilih       |
+---------------------------------------------------------------------+

+---------------------------------------------------------------------+
| Periode [September 2026] Status [Semua status] [Terapkan] [Reset]   |
| 38 murid ditemukan                                                  |
+---------------------------------------------------------------------+

+---------------------------------------------------------------------+
| Murid    | Kelas  | Kasus | Aktif | Status Terbaru | Guru BK       |
+---------------------------------------------------------------------+
| Aisyah   | XI RPL |   2   |   1   | Diproses       | Bu Ratna      |
| Bintang  | X TKJ  |   1   |   1   | Perlu lanjut   | Pak Dimas     |
| Cahyo    | XII MM |   3   |   0   | Selesai        | Bu Maya       |
+---------------------------------------------------------------------+

                    [Sebelumnya] Halaman 1 dari 4 [Berikutnya]
```

Baris tidak dibuat clickable. Aksi `Lihat koordinasi` hanya muncul jika Waka
memang mempunyai koordinasi tercatat pada salah satu kasus target.

### 6.3 Mobile

```text
+-----------------------------+
| Aisyah Rahmawati            |
| XI RPL                      |
|                             |
| 2 kasus - 1 masih aktif     |
| [Sedang diproses]           |
| Guru BK: Bu Ratna           |
|                             |
| [Lihat koordinasi]          |  hanya jika diizinkan
+-----------------------------+
```

Filter ditumpuk vertikal. Tombol dan tautan mempunyai area interaksi minimum
44 x 44 CSS pixel dengan jarak yang cukup untuk menghindari salah tekan.

## 7. Wireframe Halaman Laporan

Halaman laporan tidak memakai katalog kartu. Seluruh laporan berada pada satu
halaman dengan tiga tab berbasis tujuan pengguna.

```text
+---------------------------------------------------------------------+
| Laporan Waka Kesiswaan                              Tahun 2026/2027  |
| Pemantauan dan laporan bidang BK tingkat sekolah                    |
+---------------------------------------------------------------------+

[ Monitoring Penanganan ] [ Rekap Periode ] [ Laporan Akhir ]
```

Tab memakai link atau kontrol native yang dapat dibuka melalui URL langsung.
Pergantian tab harus mempertahankan tombol Back, active state, dan fokus yang
jelas. Contoh parameter konseptual: `?tab=penanganan`.

### 7.1 Tab Monitoring Penanganan

```text
Periode [September 2026] Status [Semua status] [Terapkan] [Reset]

42 penanganan                                         [Ekspor CSV]

Murid | Kelas | Bidang | Status | Guru BK | Tanggal
-----------------------------------------------------------------
Aisyah | XI RPL | Belajar | Diproses | Bu Ratna | 12 Sep 2026
  Ringkasan: Pendampingan penyusunan jadwal belajar...
  Tindak lanjut: Pemantauan rutin - 19 Sep 2026
                                      [Buka detail koordinasi]
```

Keputusan UX:

- satu entri mewakili satu kasus;
- ringkasan dan tindak lanjut ditempatkan pada baris kedua;
- sorting hanya tersedia pada Murid, Kelas, Bidang, Status, Guru BK, dan Tanggal;
- header sorting memakai `aria-sort` dan label arah yang dapat dibaca screen reader;
- ekspor mempertahankan filter aktif;
- tidak tersedia checkbox, bulk action, edit, atau perubahan status.

Pada mobile, setiap entri menjadi kartu. `waka_summary` memakai disclosure
native agar teks panjang tidak memenuhi layar. Aksi detail hanya muncul untuk
kasus terkoordinasi.

### 7.2 Tab Rekap Periode

```text
Periode [Semester Ganjil] Tahun [2026/2027] [Terapkan]

[72 murid dilayani] [96 layanan] [18 perlu lanjut] [68 selesai]

+-------------------------------+ +-------------------------------+
| Pelaksanaan Layanan BK        | | Kondisi Penanganan            |
| Pribadi       28              | | Baru                 8        |
| Belajar       24              | | Sedang diproses     15        |
| Sosial        22              | | Perlu tindak lanjut 18        |
| Karier        22              | | Selesai              50       |
|                               | | Dibatalkan            5       |
+-------------------------------+ +-------------------------------+

+---------------------------------------------------------------------+
| Konteks Kesiswaan                                                 |
| Pelanggaran tercatat: 38 | Murid terkait: 24 | Prestasi: 31       |
+---------------------------------------------------------------------+

+---------------------------------------------------------------------+
| Ringkasan menurut kelas                                           |
| Kelas | Murid dilayani | Kasus aktif | Selesai | Perlu lanjut     |
+---------------------------------------------------------------------+
```

Pelanggaran, poin, dan prestasi menjadi konteks agregat. Ketiganya tidak
menjadi katalog laporan terpisah. Detail pelanggaran individual tidak masuk
rekap default.

### 7.3 Tab Laporan Akhir - PLACEHOLDER

Tab dan route Laporan Akhir tetap disediakan agar struktur navigasi tidak perlu
diubah kembali ketika format laporan telah disepakati. Sampai pihak BK dan
sekolah memberikan contoh atau susunan resmi, halaman tidak menampilkan form,
statistik, tindakan penerbitan, cetak, atau ekspor.

```text
+---------------------------------------------------------------------+
| Laporan Akhir                                                       |
| Format dan susunan laporan akhir bidang BK akan ditetapkan kemudian.|
+---------------------------------------------------------------------+

+---------------------------------------------------------------------+
|                         [ikon dokumen]                              |
|                                                                    |
|                     Dalam pengembangan                             |
|                                                                    |
| Susunan laporan akhir sedang disiapkan bersama pihak BK dan sekolah.|
| Halaman ini akan tersedia setelah format laporan disepakati.        |
+---------------------------------------------------------------------+
```

Aturan placeholder:

- gunakan empty state neutral dengan `sibk-panel` existing;
- ikon dokumen memakai SVG existing dan `aria-hidden="true"`;
- tidak menampilkan tombol yang belum berfungsi;
- tidak membuat data contoh seolah-olah laporan sudah tersedia;
- tidak menampilkan estimasi tanggal penyelesaian yang belum disepakati;
- Waka dan Koordinator dapat melihat informasi status yang sama;
- route dapat dibuka langsung dan active tab tetap terlihat.

### 7.4 Informasi yang Dibutuhkan Kemudian

Wireframe isi Laporan Akhir baru dilanjutkan setelah tersedia contoh atau
keputusan sekolah mengenai struktur dokumen, periode, narasi, pengesahan,
format ekspor, dan hak penyusunan.

## 8. State Halaman

Setiap halaman dan tab harus mempunyai state berikut:

### 8.1 Loading

- Gunakan placeholder dengan ruang yang stabil agar layout tidak bergeser.
- Hindari spinner penuh halaman jika navigasi dan judul sudah dapat ditampilkan.
- Jangan mengubah tinggi panel secara tiba-tiba saat data selesai dimuat.

### 8.2 Empty

- Jelaskan bahwa tidak ada data pada periode atau filter terpilih.
- Sediakan `Reset filter` jika filter aktif.
- Jangan menampilkan tabel kosong tanpa penjelasan.

### 8.3 Error

- Tampilkan penyebab umum dan satu jalur pemulihan seperti `Coba lagi`.
- Error tidak boleh menampilkan stack trace, query, kode internal, atau data murid.
- Fokus keyboard diarahkan ke ringkasan error setelah request gagal.

### 8.4 Read-only

- Jelaskan dengan teks bahwa halaman hanya-baca.
- Jangan menggunakan tampilan disabled untuk seluruh konten.
- Hilangkan aksi yang tidak tersedia; jangan menampilkan tombol yang selalu 403.

## 9. Aturan Responsive

| Lebar | Perilaku utama |
|---|---|
| 375 px | Sidebar offcanvas, kartu ringkasan 2 kolom, daftar menjadi kartu |
| 768 px | Panel 2 kolom jika ruang cukup, filter dapat berada satu baris |
| 1024 px | Sidebar desktop, layout dashboard 8/4 kolom |
| 1440 px | Lebar konten dibatasi konsisten, teks panjang tidak melebar penuh |

Aturan tambahan:

- tidak ada horizontal scroll untuk layout halaman utama;
- tabel data yang tidak dapat diringkas harus mempunyai representasi kartu mobile;
- tab boleh memakai overflow horizontal terkontrol, bukan swipe-only navigation;
- active tab selalu terlihat dan mempunyai label teks;
- navigasi, filter, dan scroll state dipertahankan ketika pengguna kembali.

## 10. Accessibility

- Heading mengikuti urutan `h1`, `h2`, lalu `h3`.
- Setiap field memiliki label terlihat dan hubungan `for`/`id`.
- Fokus keyboard tetap terlihat menggunakan token focus existing.
- Active navigation dan status tidak ditandai dengan warna saja.
- Semua ikon dekoratif memakai `aria-hidden="true"`.
- Tombol ikon tanpa teks harus mempunyai accessible name.
- Sorting tabel memakai `aria-sort` pada header aktif.
- Disclosure ringkasan dapat dipakai dengan keyboard.
- Perubahan jumlah hasil tidak memindahkan fokus secara otomatis.
- Target interaksi utama minimum 44 x 44 CSS pixel.

## 11. Audit dan Privasi

- Response portal sukses mencatat `waka.monitoring.viewed`.
- Dataset ekspor yang berhasil disiapkan mencatat `waka.monitoring.exported`.
- Detail terkoordinasi mencatat `case.viewed_by_waka`.
- Audit tidak menyimpan nama, NISN, kode kasus, `waka_summary`, atau narasi layanan.
- Parameter audit hanya berasal dari allowlist yang telah dinormalisasi.
- Ekspor tidak boleh memuat field yang tidak terlihat pada proyeksi aman.

## 12. Acceptance Criteria Wireframe

Wireframe dianggap siap menjadi dasar rencana implementasi jika:

- navigasi akun Waka murni hanya menampilkan Dashboard, Murid dengan Kasus,
  Laporan, Notifikasi, dan Akun Saya;
- Dashboard memprioritaskan kondisi dan penanganan yang membutuhkan perhatian;
- daftar Murid dengan Kasus menggunakan satu baris per murid;
- halaman Laporan memakai tiga tab tanpa katalog kartu;
- Monitoring Penanganan menggunakan satu baris per kasus dan field aman;
- Rekap Periode menyatukan layanan, tindak lanjut, kedisiplinan, dan prestasi;
- tab Laporan Akhir tersedia dan menampilkan empty state `Dalam pengembangan`;
- kasus nonterkoordinasi tidak mempunyai tautan detail;
- desktop dan mobile tidak menampilkan kode kasus, NISN, atau narasi sensitif;
- seluruh visual menggunakan token dan komponen Ruang BK yang sudah ada.

## 13. Di Luar Scope Wireframe Ini

- perubahan database atau migration;
- implementasi route, controller, service, atau Blade;
- pembuatan design token baru;
- perubahan Penpot;
- penetapan resmi format Laporan Akhir;
- tanda tangan digital atau approval berlapis;
- chat, komentar, atau komunikasi kasus di dalam aplikasi.
