# Penyederhanaan Penugasan Kelas

**Tanggal:** 23 September 2026  
**Status:** Menunggu review pengguna  
**Target:** Halaman Penugasan Kelas, lifecycle penugasan Guru BK, aktivasi tahun ajaran, dan penghapusan dasar keputusan  
**Referensi perilaku:** PRD/SRS Aplikasi BK v1.1, terutama `AUTH-02`, `ASN-01`–`ASN-06`, `MD-05`–`MD-14`, `NFR-13`, dan `AUD-01`

## 1. Tujuan

Menjadikan `/assignments/classes` sebagai satu-satunya halaman pengelolaan
penugasan Guru BK per kelas. Halaman menampilkan seluruh kelas pada tahun
ajaran konteks, termasuk kelas yang belum mempunyai Guru BK, dan memungkinkan
Koordinator mengatur penugasan melalui satu modal ringkas.

Keberhasilan desain ini ditandai oleh:

- tidak ada lagi form panjang atau halaman pengelolaan kedua;
- tahun ajaran ditentukan sistem dan ditampilkan sebagai informasi;
- kelas tanpa penugasan terlihat dan dapat langsung dilengkapi;
- histori pergantian Guru BK tetap akurat;
- aktivasi tahun ajaran berikutnya tetap dapat dilakukan tanpa menu baru;
- `decision_number` tidak lagi berada pada schema, request, model, atau UI;
- transaksi, locking, audit, serta otorisasi penugasan kelas tetap berlaku.

## 2. Keputusan Utama

1. Halaman `/assignments/classes` berbasis `Classroom`, bukan daftar
   `TeacherAssignment`.
2. Hanya tersedia pencarian kelas dan filter status `Semua`,
   `Sudah Ditugaskan`, atau `Belum Ditugaskan`.
3. Tahun ajaran tidak menjadi filter bebas. Backend menentukan konteks aktif
   atau satu tahun persiapan terdekat yang sah.
4. Penugasan dibuat atau diganti melalui satu modal Bootstrap reusable.
5. Tanggal efektif ditentukan `AssignmentService`; pengguna tidak mengisi
   tanggal mulai atau akhir.
6. Kolom `decision_number` dihapus melalui migration forward-only. Nilai palsu
   seperti `AUTO` atau `-` tidak digunakan.
7. Kesiapan dan aktivasi tahun ajaran tetap berada pada halaman yang sama.
   Tidak dibuat menu, route halaman, service, atau Blade aktivasi baru.
8. `/assignments/classes/manage` dipertahankan sebagai redirect kompatibilitas.
9. Fitur Pengalihan Permasalahan dihapus. Penanganan oleh Guru BK lain dilakukan
   di luar aplikasi dan pencatatan resmi tetap dilakukan Guru BK pengampu.
10. Daftar laporan Guru BK dan Koordinator hanya menampilkan Ringkasan sebagai
    kolom narasi. Satu ikon kaca pembesar membuka rincian narasi layanan.

## 3. Scope

### 3.1 Termasuk

- query daftar kelas, jumlah murid aktif, dan Guru BK pada tanggal konteks;
- ringkasan jumlah kelas yang sudah memiliki Guru BK;
- pencarian kelas dan filter status penugasan;
- modal penugasan serta informasi beban kelas setiap Guru BK;
- aturan pembuatan, no-op, pergantian aktif, dan penggantian terjadwal;
- ringkasan kesiapan dan aksi aktivasi tahun ajaran berikutnya;
- redirect URL `/assignments/classes/manage`;
- migration penghapusan `teacher_assignments.decision_number`;
- penghapusan route, UI, request, controller, service, dan kontrak Pengalihan
  Permasalahan;
- penyederhanaan kolom serta kontrol detail pada daftar laporan Guru BK dan
  Koordinator;
- amendemen PRD, SRS, API contract, dan dokumentasi terkait;
- penyesuaian seeder serta fixture yang masih mengirim `decision_number`.

### 3.2 Tidak termasuk

- pemindahan kasus aktif ketika Guru BK kelas berubah;
- penghapusan tabel/model `case_assignments` atau histori pemilik kasus existing;
- penghapusan histori `teacher_assignments`;
- menu aktivasi tahun ajaran baru;
- dropdown kustom, library frontend baru, atau komponen JavaScript baru;
- penayangan Sumber atau riwayat Tindak Lanjut pada daftar laporan;
- pagination tabel kelas;
- perubahan data audit lama;
- perubahan policy penugasan kelas selain mempertahankan kewenangan
  Koordinator BK.

## 4. Otorisasi

Hanya Koordinator BK yang dapat membuka halaman, menyimpan penugasan, dan
mengaktifkan tahun ajaran. Otorisasi tetap diperiksa pada policy, Form Request,
controller aktivasi, dan service; menyembunyikan tombol bukan mekanisme
keamanan.

Guru BK, Waka Kesiswaan, dan Admin IT tidak memperoleh akses halaman atau aksi
penugasan hanya karena struktur UI disederhanakan. Aplikasi tidak menyediakan
pengalihan pemilik kasus atau penugasan kasus khusus.

## 5. Penentuan Konteks Tahun Ajaran

Backend menentukan konteks dengan urutan berikut:

1. tanpa parameter, gunakan tahun ajaran aktif;
2. jika belum ada tahun aktif, gunakan tahun persiapan terdekat yang belum
   berakhir dan mempunyai kelas aktif;
3. `academic_year_id` hanya diterima bila menunjuk tahun aktif atau tahun
   persiapan terdekat tersebut;
4. ID tahun lain, tahun yang sudah berakhir, atau pasangan kelas lintas tahun
   ditolak server.

Tahun persiapan terdekat adalah tahun nonaktif yang belum berakhir, mempunyai
minimal satu kelas aktif, dan memiliki `starts_on` terdekat. Jika tahun aktif
tersedia, kandidat harus dimulai setelah tahun aktif tersebut. Tahun dapat
berasal dari persiapan sementara sekolah atau data resmi Dapodik.

Halaman tahun aktif menampilkan banner kecil bila tahun persiapan tersedia:

> Persiapan Tahun Ajaran 2027/2028 tersedia. **Atur Penugasan**

Tombol banner membuka halaman yang sama dengan konteks tahun persiapan. Pada
konteks tersebut tersedia tautan kembali ke tahun aktif. Ini adalah perpindahan
konteks operasional, bukan filter tahun ajaran umum.

## 6. Daftar Kelas

Header halaman menampilkan:

- judul `Penugasan Kelas`;
- `Tahun Ajaran <nama>` sebagai informasi;
- ringkasan `<x> dari <y> kelas sudah memiliki Guru BK`.

Filter hanya memuat:

- `Cari kelas`;
- `Status`: `Semua`, `Sudah Ditugaskan`, `Belum Ditugaskan`.

Tabel memuat:

| Kolom | Isi |
|---|---|
| Kelas | Nama kelas pada tahun konteks. |
| Murid | Jumlah keanggotaan aktif dengan murid aktif pada tahun konteks. |
| Guru BK | Nama Guru BK yang berlaku pada tanggal konteks atau `—`. |
| Status | `Ditugaskan` atau `Belum Ditugaskan`. |
| Aksi | Ikon tambah pengguna atau edit pengguna. |

Tanggal konteks untuk tahun aktif adalah hari ini. Tanggal konteks untuk tahun
persiapan adalah `starts_on`. Assignment historis yang tidak berlaku pada
tanggal konteks tidak membuat kelas berstatus `Ditugaskan`.

Pencarian dan status diproses server melalui query parameter ber-allowlist.
Urutan kelas selalu berdasarkan nama. Tidak ditambahkan pagination karena
jumlah kelas sekolah terbatas dan kebutuhan tersebut belum ada.

Ikon aksi mempunyai `aria-label` dan teks bantuan yang jelas. Baris tabel tidak
menjadi kontrol klik agar interaksi keyboard dan pembaca layar tetap pasti.

## 7. Modal Penugasan

Satu modal Bootstrap dipakai seluruh baris. Modal menampilkan:

- nama kelas;
- tahun ajaran sebagai teks informasi;
- satu `<select>` Guru BK aktif;
- tombol `Batal` dan `Simpan`.

Tombol baris mengisi modal melalui atribut `data-*` dan handler kecil pada
`app-dashboard.js`. Tidak dibuat satu modal per baris atau modul JavaScript
baru. Jika validasi server gagal, halaman membuka kembali modal kelas terkait
dan mempertahankan pilihan lama.

Label pilihan Guru BK menunjukkan beban pada tahun konteks:

- `Nur Aini — Belum memiliki kelas`; atau
- `Siti Aminah — 5 kelas: X-RPL-1, X-RPL-2, XI-RPL-1 +2 lainnya`.

Daftar nama kelas dibatasi maksimal tiga nama, kemudian memakai `+N lainnya`.
Perhitungan hanya memakai assignment yang berlaku pada tanggal konteks dan
tidak menghitung histori yang sudah berakhir.

Request resmi hanya menerima `classroom_id` dan `user_id`. Tahun ajaran dan
tanggal efektif diturunkan backend dari kelas serta konteks sistem.

## 8. Aturan Lifecycle Penugasan

`AssignmentService::assignClass()` tetap menjadi satu-satunya jalur mutasi dan
tetap berjalan dalam transaksi. Service mengunci Guru BK, kelas, tahun ajaran,
dan seluruh assignment kelas pada tahun tersebut sebelum menentukan aksi.

Aturan penyimpanan:

| Kondisi | Perilaku |
|---|---|
| Tahun belum dimulai dan belum ada assignment | Buat assignment mulai `starts_on`. |
| Tahun aktif dan belum ada assignment | Buat assignment mulai hari penyimpanan. |
| Guru terpilih sama dengan assignment konteks | Kembalikan assignment tanpa write atau audit baru. |
| Assignment belum pernah berlaku | Perbarui `user_id` dan `assigned_by` pada record terjadwal yang sama; catat audit sebelum/sesudah. |
| Assignment sudah berlaku dan Guru berubah | Tutup assignment lama kemarin, lalu buat assignment baru mulai hari ini. |
| Tahun sudah berakhir | Tolak perubahan. |
| Ada assignment masa depan lain yang menimbulkan overlap | Tolak perubahan. |

Assignment baru tidak memerlukan `effective_until`; batas akhir efektif tetap
mengikuti akhir tahun ajaran melalui perilaku model yang sudah ada.

Penugasan pertama yang terlambat pada tahun aktif tidak dimundurkan ke awal
tahun. Aturan ini mencegah pemberian scope akses retrospektif kepada Guru BK.
Pergantian Guru BK kelas tidak memindahkan kasus aktif. Kasus tetap mempunyai
pemilik yang sudah tercatat. Penanganan bantuan oleh Guru BK lain berlangsung
di luar aplikasi, sedangkan pencatatan resmi pada aplikasi dilakukan oleh Guru
BK pengampu yang berwenang.

## 9. Kesiapan dan Aktivasi Tahun Ajaran

`AcademicYearPreparationService::activationReadiness()` tetap menjadi sumber
kebenaran kesiapan aktivasi. Halaman konteks persiapan hanya menampilkan:

- ringkasan kelas yang sudah mempunyai Guru BK;
- daftar alasan singkat bila belum siap;
- peringatan data sementara atau identitas yang belum direkonsiliasi;
- status bahwa aktivasi menunggu tanggal mulai; atau
- tombol `Aktifkan Tahun Ajaran` ketika service menyatakan `ready`.

Tabel `Kesiapan Aktivasi` kedua dihapus. Tabel kelas utama sudah menyediakan
detail per kelas yang dibutuhkan untuk melengkapi penugasan.

Aktivasi tetap memakai endpoint, controller, policy, transaksi, locking, dan
audit yang ada. Setelah aktivasi berhasil, pengguna kembali ke
`/assignments/classes`; tahun yang baru aktif otomatis menjadi konteks default.

## 10. Penghapusan `decision_number`

Migration baru menghapus kolom `decision_number` dari `teacher_assignments`.
Migration pembuat tabel lama tidak diubah. Rollback schema dapat menambahkan
kembali kolom nullable, tetapi tidak dapat memulihkan nilai yang sudah dihapus;
operasional production tetap mengikuti migration forward-only dan prosedur
backup yang berlaku.

Seluruh consumer aktif dihapus:

- atribut fillable pada `TeacherAssignment`;
- validasi dan pesan pada `StoreClassAssignmentRequest`;
- kontrak data, create payload, dan snapshot baru pada `AssignmentService`;
- input pada Blade lama;
- seeder dan fixture test;
- dokumentasi request serta requirement dasar keputusan.

Audit lama bersifat append-only dan tidak dimutasi. Jika snapshot audit lama
memuat `decision_number`, nilai historis tersebut tetap berada pada event lama
dan tidak menjadi field aktif aplikasi.

Amendemen dokumen menetapkan bahwa penugasan menyimpan kelas, Guru BK, tahun
ajaran, periode efektif, pembuat perubahan, dan audit. Dasar keputusan/nomor SK
tidak lagi menjadi data wajib maupun opsional dalam Ruang BK.

## 11. Redirect Kompatibilitas

Route bernama `assignments.classes.manage` tetap tersedia sebagai redirect ke
`assignments.classes.index`. Parameter `academic_year_id` hanya diteruskan bila
lolos aturan konteks tahun ajaran. Bookmark lama tetap berfungsi, sedangkan
view `pages.assignments.classes.manage` dan method controller `manage()` dapat
dihapus.

Tidak ada redirect untuk request mutasi lama yang membawa tanggal atau nomor
SK. Endpoint POST hanya memvalidasi serta memakai `classroom_id` dan `user_id`;
field lama tidak dibaca dan tidak memengaruhi mutasi.

## 12. Error, Audit, dan Konsistensi

- Guru BK tidak aktif atau tidak mempunyai role `guru_bk` ditolak.
- Kelas nonaktif, tahun berakhir, dan konteks tahun yang tidak diizinkan ditolak.
- Validasi dan service tidak mempercayai nilai tahun ajaran dari browser.
- Lock pada kelas mencegah dua Koordinator membuat assignment pertama secara
  bersamaan; pemeriksaan overlap tetap dilakukan sebelum commit.
- Create, update assignment terjadwal, penutupan periode, assignment baru, dan
  aktivasi mencatat audit sebelum/sesudah sesuai jenis perubahan.
- No-op Guru BK yang sama tidak membuat audit semu.
- Kegagalan pada salah satu tahap me-rollback seluruh transaksi.

## 13. Kontrak Dokumen yang Diamendemen

PRD, SRS, dan API contract harus berubah bersama implementasi:

- `ASN-01`: penugasan menyimpan kelas, Guru BK, tahun ajaran, dan periode
  efektif; dasar keputusan dihapus;
- `ASN-03`: tanggal efektif tetap ada sebagai fakta sistem dan audit, tetapi
  tidak lagi dipilih manual pada penugasan kelas;
- daftar/form penugasan pada API contract menjadi satu halaman;
- request penugasan hanya memuat `classroom_id` dan `user_id`;
- aktivasi tahun ajaran tetap menjadi kewenangan Koordinator dan memakai
  readiness existing.

## 14. Penghapusan Pengalihan Permasalahan

Fitur Pengalihan Permasalahan dikeluarkan dari scope aplikasi saat ini. Yang
dihapus meliputi menu sidebar, halaman `assignments/cases`, route daftar dan
mutasi, `AssignCaseRequest`, method controller, method service, fixture preview,
serta test khusus pengalihan.

`case_assignments` dan model `CaseAssignment` tetap dipertahankan karena masih
menjadi sumber pemilik awal, otorisasi kasus, dan histori data existing. Tidak
ada migration yang menghapus tabel atau record pemilik lama.

Kasus baru tetap memperoleh satu owner ketika dibuat. Jika Guru BK lain ikut
menangani secara offline, hasilnya diserahkan kepada Guru BK pengampu dan tidak
dicatat sebagai perpindahan owner. Pergantian penugasan kelas tidak memindahkan
kasus aktif dan aplikasi tidak menyediakan mekanisme darurat pengalihan.

PRD, SRS, API contract, frontend map, serta matriks otorisasi diamendemen untuk
menghapus kewajiban `ASN-04`/`ASN-05` dan endpoint pengalihan. Audit lama
`case.transferred` tetap append-only dan tidak ditulis ulang.

## 15. Koreksi Daftar Laporan

Daftar laporan Guru BK dan Koordinator mempertahankan kolom identitas layanan,
tetapi kolom narasi yang terlihat hanya `Ringkasan`. Nilainya memakai
`resolution_summary` untuk Permasalahan dan `result` untuk Konsultasi.

Kontrol detail disederhanakan menjadi satu ikon kaca pembesar pada kolom Aksi.
Ikon ini membuka satu baris rincian di bawah catatan yang memuat Latar Belakang
Masalah, Penanganan, dan Hasil. Ikon kaca pembesar lama per narasi serta ikon
chevron dihapus. Label aksesibel kontrol memakai istilah `detail layanan`.

Sumber dan Tindak Lanjut tidak ditampilkan pada daftar maupun baris rincian
laporan. Riwayat Tindak Lanjut menjadi pekerjaan terpisah berikutnya karena
schema saat ini hanya menyimpan satu `follow_up_type_id` terbaru. Struktur
preview/PDF/Excel dan proyeksi laporan Waka tidak berubah.

## 16. Kriteria Penerimaan

1. Semua kelas aktif pada tahun konteks muncul meskipun belum mempunyai Guru BK.
2. Jumlah murid hanya menghitung membership aktif dan murid aktif pada tahun itu.
3. Filter status menggunakan assignment pada tanggal konteks, bukan keberadaan
   histori apa pun.
4. Koordinator dapat menetapkan Guru BK melalui satu modal pada halaman utama.
5. Guru BK yang sama tidak menghasilkan write atau audit baru.
6. Pergantian Guru BK pada tahun aktif mempertahankan histori tanpa overlap.
7. Penggantian assignment tahun depan memperbarui record terjadwal yang sama.
8. Penugasan pertama yang terlambat tidak memberikan akses retrospektif.
9. Guru BK nonaktif, kelas lintas tahun, tahun berakhir, dan overlap ditolak.
10. Ringkasan kesiapan dan aktivasi tahun depan tetap tersedia tanpa halaman
    atau menu baru.
11. `/assignments/classes/manage` mengarah ke halaman utama.
12. Schema akhir tidak mempunyai kolom `decision_number` dan aplikasi tidak
    mengirim atau membacanya.
13. Audit lama tidak ditulis ulang dan kasus aktif tidak berpindah otomatis.
14. Menu, halaman, route, request, dan mutasi Pengalihan Permasalahan tidak lagi
    tersedia, sementara owner kasus existing tetap dapat dipakai untuk scope.
15. Tabel laporan Guru BK dan Koordinator hanya menampilkan Ringkasan sebagai
    narasi utama; satu ikon kaca pembesar membuka Latar Belakang Masalah,
    Penanganan, dan Hasil tanpa menampilkan Sumber atau Tindak Lanjut.

## 17. Strategi Verifikasi

Verifikasi terarah harus mencakup daftar kelas kosong/terisi, jumlah murid,
filter status, validasi role dan konteks tahun, seluruh cabang lifecycle
penugasan, no-op, concurrency/overlap, redirect lama, readiness, aktivasi, serta
migration pada database disposable SQLite dan MySQL. Verifikasi juga memastikan
endpoint pengalihan tidak tersedia dan pembuatan kasus tetap mempunyai owner.
Verifikasi laporan memastikan satu kontrol detail berfungsi pada catatan kasus
dan konsultasi serta tidak mengubah preview, PDF, Excel, atau proyeksi Waka.

Gate repository dijalankan menjelang integrasi sesuai perintah pengguna.
Penyusunan spec dan plan tidak menjalankan test, formatter, atau build.
