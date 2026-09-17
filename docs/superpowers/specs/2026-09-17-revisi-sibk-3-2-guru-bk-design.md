# Revisi SIBK 3.2 untuk Layanan Guru BK

**Tanggal:** 17 September 2026

**Status:** Disetujui dalam brainstorming dan menunggu review dokumen

**Target:** Kasus, konsultasi, akses Waka, dashboard, autosave, dan skema data terkait

**Sumber revisi:** `RevSIBK_3.2.docx`

## 1. Tujuan

Menyesuaikan alur layanan Guru BK dengan Revisi SIBK 3.2 melalui perubahan
terkendali pada modul yang sudah ada. Desain menyederhanakan tindak lanjut,
penyelesaian kasus, dan konsultasi tanpa membangun modul paralel atau menambah
dependency.

Desain ini menggantikan PRD/SRS v1.1 pada bagian yang bertentangan dan pada
keputusan tambahan yang disetujui selama brainstorming. Ketentuan v1.1 yang
tidak disebut tetap berlaku, kecuali privasi konsultasi dan koordinasi Waka
yang diubah secara eksplisit di bawah. PRD, SRS, dan requirements index harus
diperbarui sebelum perubahan aplikasi diimplementasikan.

## 2. Keputusan Utama

1. Tindak lanjut tidak lagi menjadi record berjadwal dengan status dan hasil
   tersendiri. Setiap kasus hanya memiliki satu jenis tindak lanjut terkini.
2. Status kasus hanya Sedang Proses, Tindak Lanjut, dan Selesai. Kasus baru
   langsung berstatus Sedang Proses.
3. Memilih jenis tindak lanjut mengubah status menjadi Tindak Lanjut;
   mengosongkannya mengembalikan status menjadi Sedang Proses.
4. Penyelesaian dilakukan dari modal edit kasus melalui tombol
   **Simpan dan Selesaikan**, tanpa modal penyelesaian terpisah.
5. Konsultasi menjadi record mandiri yang terhubung ke murid, bukan ke kasus,
   dan tidak mempunyai status atau nomor registrasi.
6. Waka dapat membaca detail terstruktur seluruh kasus dan konsultasi, tetapi
   tidak dapat mengubah atau menghapusnya.
7. Koordinasi Waka dilakukan di luar aplikasi. Fitur dan data koordinasi di
   dalam aplikasi dihentikan.
8. Edit memperbarui record utama secara langsung. `updated_at` menunjukkan
   perubahan terakhir dan audit minimal mencatat field yang berubah saat
   penyimpanan resmi.
9. Autosave memakai `localStorage` pada perangkat pengguna, bukan backend atau
   tabel draft.
10. Data existing masih dummy sehingga tidak diperlukan backfill data bisnis
    yang rumit. Semua perubahan skema tetap memakai migration forward-only.

## 3. Batas Scope

Scope mencakup:

- Buat, daftar, detail, edit, penyelesaian, tindak lanjut, dan arsip kasus.
- Catat, daftar, detail, edit, dan arsip konsultasi.
- Proyeksi hanya-baca kasus dan konsultasi untuk Waka.
- Autosave seluruh form tambah/edit data bisnis.
- Tombol Akses Cepat pada dashboard Guru BK.
- Laporan, dashboard, seeder, factory, dan test yang bergantung pada struktur
  kasus, tindak lanjut, konsultasi, atau koordinasi.

Scope tidak mencakup adapter production Dapodik/e-Tatib baru. Pencarian murid
memakai data lokal hasil sinkronisasi/persiapan yang sudah tersedia. e-Tatib
tetap dibaca dari snapshot lokal resmi. Search/filter, login, password,
credential/token, upload file, aksi destruktif, dan perubahan inline bukan
draft autosave.

## 4. Arsitektur

Arsitektur Laravel existing dipertahankan:

- controller tetap tipis;
- Form Request menangani validasi request;
- service menjalankan transaksi dan logika bisnis;
- policy membatasi akses server-side;
- query scopes menjaga scope data;
- Blade, Bootstrap, dan JavaScript ringan menangani UI;
- tidak ada dependency frontend atau backend baru.

Bootstrap yang sudah terpasang dipakai untuk modal. Konfirmasi existing berbasis
`window.confirm` dapat dipakai untuk edit record selesai dan arsip. Satu helper
JavaScript kecil menangani autosave form agar logika tidak digandakan pada
setiap halaman.

## 5. Model Data

### 5.1 Kasus

Tabel `cases` tetap menjadi sumber data utama. Perubahan:

- tambahkan `follow_up_type_id` nullable dengan foreign key ke `references`;
- gunakan kategori reference existing `follow_up_type` untuk empat pilihan:
  Surat Panggilan Orang Tua, Surat Pernyataan, Home Visit, dan Pengunduran Diri;
- pertahankan `resolution_summary` dan tampilkan sebagai Catatan Penyelesaian;
- pertahankan `closed_at` sebagai tanggal penyelesaian;
- hapus `final_result` dan `continued_plan`;
- pertahankan soft delete, identitas murid, sumber kasus, bidang layanan,
  tanggal layanan, latar belakang, penanganan, pemilik, dan audit.

Reference status aktif untuk record baru hanya:

- `sedang_diproses` dengan label **Sedang Proses**;
- `membutuhkan_tindak_lanjut` dengan label **Tindak Lanjut**;
- `selesai` dengan label **Selesai**.

Kode internal `membutuhkan_tindak_lanjut` dipertahankan agar perubahan minimum;
label dan perilakunya mengikuti desain baru. Status `baru` dinonaktifkan dan
tidak dapat dipilih atau dibuat lagi.

Tabel `follow_ups` serta model, controller, request, service, route, view,
relasi, dan test khususnya dihapus. Tabel `case_coordinations` beserta seluruh
consumer-nya juga dihapus.

### 5.2 Konsultasi

Tabel `consultations` menyimpan:

- salah satu dari `student_id` atau `temporary_student_id`;
- `service_field_id`;
- `session_date`;
- `problem`;
- `handling`;
- `result`;
- `counselor_id`;
- timestamps dan soft delete.

`problem`, `handling`, dan `result` wajib diisi, masing-masing maksimal 10.000
karakter, karena setiap konsultasi yang disimpan dianggap layanan yang telah
terlaksana.

Kolom `registration_number`, `case_id`, `status_id`, `topic`,
`referral_source`, `starts_at`, `ends_at`, `follow_up_date`, dan
`general_summary` dihapus. Tabel `consultation_private_notes` beserta model dan
relasinya dihapus. Data dummy tidak perlu dimigrasikan ke bentuk baru.

### 5.3 Audit

Tidak ada tabel versi baru. Record utama selalu memuat nilai terbaru. Audit
append-only existing mencatat satu event pada penyimpanan resmi dengan:

- actor dan waktu;
- tipe dan ID record;
- hanya field yang berubah;
- nilai sebelum dan sesudah perubahan.

Autosave draft tidak menghasilkan audit. Audit tidak mempunyai halaman
pembaca baru dalam scope ini.

## 6. Alur Kasus

### 6.1 Buat Kasus

Pengguna memilih Sumber Kasus lebih dahulu.

Untuk sumber e-Tatib, form menampilkan record e-Tatib lokal yang tersedia dan
berwenang. Pengguna dapat memilih satu atau lebih record sesuai relasi existing.
Identitas serta data pelanggaran berasal dari record yang dipilih; NISN dan
nama tidak diketik ulang. Backend wajib menolak sumber e-Tatib tanpa record
resmi tertaut.

Untuk sumber selain e-Tatib, pengguna mengisi NISN dan nama. Backend mencari
NISN pada murid lokal yang tersedia dan berada dalam scope Guru BK. Jika
ditemukan, kasus memakai `student_id` tanpa menimpa nama resmi. Jika belum
ditemukan, backend membuat atau memakai identitas sementara yang sah agar
dapat direkonsiliasi kemudian.

Kasus baru otomatis berstatus Sedang Proses.

### 6.2 Daftar Kasus

Kolom akhir:

- Murid;
- Kelas;
- Tanggal;
- Sumber;
- Bidang;
- Status;
- Tindak Lanjut;
- Aksi.

Search hanya mencocokkan nama murid atau nama identitas sementara. Filter
utama hanya Status. Header kolom yang relevan dapat dipakai untuk sorting
naik/turun melalui allowlist server; minimal Nama, Kelas, Tanggal, Sumber,
Bidang, dan Status.

Dropdown Tindak Lanjut menyimpan perubahan langsung melalui endpoint kecil.
UI menampilkan status penyimpanan dan mengembalikan nilai sebelumnya bila
request gagal. Pilihan non-null mengubah status menjadi Tindak Lanjut.
Pilihan kosong mengubah status menjadi Sedang Proses. Kasus Selesai tidak dapat
diubah melalui dropdown ini.

### 6.3 Detail, Edit, dan Penyelesaian

Klik bagian noninteraktif pada baris atau aksi Detail membuka satu modal.
Detail menampilkan:

- Nama;
- Tanggal;
- Kelas;
- Status;
- Guru BK;
- Latar Belakang;
- Penanganan;
- Catatan Penyelesaian.

Mode edit hanya mengubah Latar Belakang, Penanganan, dan Catatan Penyelesaian.
Ketiga field naratif dibatasi maksimal 10.000 karakter.
Tombol **Simpan** memperbarui data tanpa mengubah status. Tombol
**Simpan dan Selesaikan** mewajibkan Catatan Penyelesaian, mengubah status
menjadi Selesai, dan mengisi `closed_at` dengan tanggal server.

Kasus Selesai tetap dapat diedit setelah konfirmasi: “Kasus ini telah selesai.
Apakah Anda ingin melanjutkan pengeditan?” Tidak ada field alasan tambahan;
perubahan tetap masuk audit. Identitas, pemilik, dan status terminal tidak
dapat diubah.

Tombol Hapus tetap berarti arsip melalui soft delete dan memakai konfirmasi.

## 7. Alur Konsultasi

### 7.1 Catat Konsultasi

Form berisi:

- NISN dan Nama;
- Tanggal;
- Jenis Layanan;
- Permasalahan;
- Penanganan;
- Hasil.

NISN dan nama mendukung autocomplete dari murid lokal hasil sinkronisasi atau
persiapan Dapodik. Browser tidak mengakses provider secara langsung. Jika
murid dipilih, backend memvalidasi ulang scope Guru BK. Jika tidak ada
kecocokan, NISN dan nama dapat disimpan melalui identitas sementara.

Tanggal default memakai tanggal hari ini dari server, tetapi pengguna dapat
mengubahnya sebelum menyimpan. Tanggal tetap dapat diedit setelah record dibuat.

### 7.2 Daftar, Detail, dan Edit

Kolom daftar:

- Tanggal;
- Murid dan Kelas;
- Permasalahan;
- Jenis Layanan;
- Aksi.

Search hanya berdasarkan nama. Filter utama hanya Jenis Layanan. Header yang
relevan dapat diurutkan melalui allowlist server, minimal Tanggal, Nama, Kelas,
dan Jenis Layanan.

Detail dan edit memakai modal. Field yang dapat diedit adalah Tanggal, Jenis
Layanan, Permasalahan, Penanganan, dan Hasil. Identitas murid tidak dapat
diubah setelah penyimpanan.

Setiap edit diawali konfirmasi: “Layanan ini telah selesai. Apakah Anda ingin
melanjutkan pengeditan?” Perubahan masuk audit. Hapus tetap memakai soft delete.

## 8. Akses Waka dan Role Lain

Waka dapat melihat seluruh kasus dan konsultasi melalui proyeksi hanya-baca.
Pembatasan berdasarkan record koordinasi dihapus karena koordinasi tidak lagi
dicatat di aplikasi.

Detail kasus Waka memuat field yang sama dengan modal kasus Guru BK, tanpa
catatan internal atau payload mentah e-Tatib. Detail konsultasi Waka memuat
Tanggal, Murid dan Kelas, Jenis Layanan, Permasalahan, Penanganan, dan Hasil.
Data konsultasi tersedia pada tab Layanan BK existing, bukan menu baru.

Waka tidak memperoleh aksi edit, selesai, tindak lanjut, atau hapus. Pembacaan
detail Waka tetap dapat dicatat sebagai event audit existing.

Koordinator mempertahankan kewenangan penugasan dan cakupan existing, tetapi
tidak otomatis memperoleh hak edit catatan profesional. Admin IT tidak
memperoleh akses isi layanan BK.

## 9. Autosave Lokal

Autosave berlaku pada form tambah/edit data bisnis di aplikasi. Search,
filter, login, password, credential/token, file upload, konfirmasi destruktif,
dan perubahan inline seperti dropdown Tindak Lanjut dikecualikan.

Mekanisme:

1. Event input menunggu debounce 2–3 detik setelah perubahan terakhir.
2. Nilai form yang aman disimpan sebagai JSON di `localStorage`.
3. Kunci memuat ID pengguna, route/form key, dan ID record bila ada.
4. Saat form dibuka kembali pada browser yang sama, draft valid dipulihkan.
5. UI menampilkan status Draft tersimpan atau kegagalan penyimpanan lokal.
6. Draft dihapus setelah Simpan berhasil, Hapus Draft, logout, atau berumur
   lebih dari 24 jam.

Field password, token, credential, file, CSRF, dan method spoofing tidak pernah
disalin ke `localStorage`. Draft tidak dikirim ke server, tidak tersedia lintas
perangkat, dapat hilang bila data browser dibersihkan, dan tidak dianggap data
resmi. Nilai draft tetap divalidasi penuh saat submit.

## 10. Dashboard

Tombol Akses Cepat diberi ikon menggunakan SVG atau fasilitas Bootstrap yang
sudah tersedia. Warna memakai token/style aplikasi existing dan tidak
mengubah struktur dashboard yang telah disetujui. Tidak ada library ikon baru.

## 11. Validasi dan Penanganan Error

- Semua policy dan validasi dijalankan server-side.
- Update resmi memakai transaksi dan lock record seperti service existing.
- Sumber e-Tatib, record tertaut, identitas murid, dan scope Guru BK harus
  konsisten.
- `follow_up_type_id` wajib berasal dari reference aktif kategori yang benar.
- Status hanya dapat berubah melalui aksi yang diizinkan.
- Catatan Penyelesaian wajib ketika menyelesaikan kasus.
- Tanggal konsultasi harus valid dan sebelum tanggal keluar resmi murid bila
  aturan keluar berlaku.
- Parameter sorting dibatasi pada allowlist kolom dan arah.
- Gagal menyimpan dropdown mengembalikan nilai UI sebelumnya.
- Gagal submit modal mempertahankan input dan menampilkan kesalahan dekat field.
- Waka, Koordinator, Guru BK, dan Admin IT tetap diuji terhadap route langsung,
  bukan hanya visibilitas tombol.

## 12. Urutan Migrasi dan Implementasi

Urutan aman:

1. Perbarui PRD, SRS, requirements index, dan kontrak API yang terdampak.
2. Tambahkan kolom baru serta reference/status baru atau perubahan label yang
   diperlukan.
3. Ubah model, request, service, policy, query, controller, laporan, dan UI ke
   kontrak baru.
4. Hapus consumer runtime tindak lanjut lama, koordinasi, status konsultasi,
   private note, serta nomor registrasi konsultasi.
5. Jalankan migration forward-only untuk menghapus kolom dan tabel retired
   setelah tidak ada consumer.
6. Perbarui factory, seeder dummy, fixture preview, dokumentasi, dan test.

Tidak ada reset database shared/production. Implementasi harus mengecek
dependency sebelum drop dan memakai urutan migration yang dapat berjalan pada
SQLite test serta database target aplikasi.

## 13. Verifikasi

Focused test minimum:

- kasus e-Tatib dan kasus input manual;
- pemetaan murid lokal dan identitas sementara;
- default status Sedang Proses;
- simpan/hapus dropdown tindak lanjut dan transisi status;
- larangan mengubah tindak lanjut kasus Selesai;
- detail, edit, selesai, edit terminal, audit, dan soft delete kasus;
- create/edit/detail/arsip konsultasi serta perubahan tanggal;
- pencarian nama, filter utama, dan sorting allowlist;
- akses hanya-baca seluruh kasus dan konsultasi untuk Waka;
- penolakan akses di luar scope dan larangan mutasi oleh Waka/Admin;
- autosave, restore, isolasi user/form, kedaluwarsa 24 jam, pembersihan, serta
  pengecualian field rahasia;
- laporan dan dashboard setelah penghapusan struktur lama.

Gate akhir mengikuti repository:

- `composer test`;
- `php vendor/bin/pint --test`;
- `npm run check:frontend`;
- `npm run build`;
- `composer validate --strict`;
- `git diff --check`.

## 14. Kriteria Selesai

Desain dianggap terimplementasi bila:

- UI kasus dan konsultasi mengikuti Revisi SIBK 3.2 serta keputusan desain ini;
- skema tidak lagi mempunyai consumer aktif untuk tindak lanjut terpisah,
  koordinasi Waka, status/nomor registrasi konsultasi, atau private note;
- Waka dapat membaca detail terstruktur tanpa memperoleh akses mutasi;
- autosave lokal tidak menyimpan credential dan tidak membuat audit;
- audit resmi, soft delete, scope, dan otorisasi server tetap berfungsi;
- seluruh focused test dan gate repository lulus.
