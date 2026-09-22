# Revisi Laporan Catatan Layanan Guru BK dan Koordinator

**Tanggal awal:** 14 September 2026

**Diamendemen:** 21 dan 22 September 2026

**Status:** Disetujui sebagai source of truth untuk implementation plan

**Target:** Halaman Laporan Guru BK dan Koordinator BK

**Requirement:** `REP-01` sampai `REP-04`

## 1. Tujuan

Halaman `/reports` menampilkan catatan kasus dan konsultasi sebagai satu daftar
operasional. Pengguna dapat memfilter data, membuka detail, mengarsipkan record
yang menjadi kewenangannya, mempratinjau dokumen, mencetak/menyimpan PDF melalui
browser, serta mengunduh Excel atau Word.

Portal Waka, dashboard, profil murid, dan modul prestasi tidak berubah.

## 2. Referensi yang Diadaptasi

Referensi `wiwikismiati47-debug/administrasi-bk-smpn7` memisahkan:

- tabel kerja;
- preview rekap resmi;
- preview dokumen per item;
- aksi cetak/PDF browser;
- ekspor Excel;
- unduhan Word kompatibel berbasis HTML `.doc`.

SIBK mengadaptasi pemisahan alur tersebut, tetapi tetap memakai arsitektur
Laravel, policy server, scope Eloquent, Blade, Bootstrap, dan gaya existing.
Kode React/Tailwind referensi tidak disalin.

## 3. Halaman Utama

Filter yang tersedia hanya:

| Parameter | Nilai |
|---|---|
| Tahun ajaran | Tahun yang tersedia; default tahun aktif |
| Kelas | Semua kelas atau kelas dalam tahun terpilih dan scope actor |
| Jenis layanan | Semua, Catatan Kasus, Catatan Konsultasi |
| Jumlah data | 10, 25, 50, 100; default 10 |

Urutan default adalah tanggal layanan terbaru, lalu tipe dan ID secara stabil.
Perubahan filter atau jumlah data kembali ke halaman pertama.

Kolom desktop:

1. No;
2. Hari/Tanggal;
3. Nama & Kelas;
4. Layanan/Jenis Masalah;
5. Latar Belakang Masalah;
6. Penanganan;
7. Aksi.

Latar Belakang Masalah dan Penanganan dibatasi 80 karakter pada tabel. Ikon
mata membuka atau meringkas nilai lengkap. Dokumen tetap memakai nilai lengkap.

## 4. Mapping Data

| Jenis | Tanggal | Latar Belakang Masalah | Penanganan | Hasil Layanan |
|---|---|---|---|---|
| Kasus | `service_date` | `initial_info` | `initial_action` | `resolution_summary` |
| Konsultasi | `session_date` | `problem` | `handling` | `result` |

Kelas memakai membership yang efektif pada tanggal layanan. Identitas sementara
yang sah tetap dapat muncul sesuai scope existing. `internal_note` kasus tidak
digunakan sebagai Catatan karena merupakan data internal terbatas.

## 5. Interaksi Tabel

- Klik area baris tidak membuka modal.
- Ikon mata membuka atau meringkas Latar Belakang Masalah dan Penanganan.
- Ikon chevron membuka baris Hasil Layanan dengan latar berbeda.
- Ikon Hapus menjalankan archive/soft delete existing setelah konfirmasi.
- Ikon Hapus hanya muncul jika policy objek mengizinkan.
- Tombol ikon mempunyai `aria-label`, `title`, focus state terlihat, dan target
  sentuh minimum 44 × 44 px.
- Layar kecil memakai kartu existing; halaman tidak memaksa overflow horizontal.

## 6. Preview Rekap

Satu tombol `Cetak / Unduh Rekap` membuka `GET /reports/preview`, bukan langsung
mengunduh atau membuka dialog print. Preview memakai lembar A4 landscape putih
di atas latar netral seperti referensi, dengan action bar di luar lembar:

- Kembali ke Laporan;
- Download Excel;
- Download Word;
- Cetak / Simpan PDF.

Lembar memuat kop sementara, judul, konteks filter, seluruh tabel hasil filter,
waktu dibuat, dan blok tanda tangan Koordinator BK serta Waka Kesiswaan. Data
disusun dari tanggal layanan paling awal. Preview tidak menerima parameter
format; `format=xlsx|doc` hanya milik endpoint ekspor.

Print stylesheet menyembunyikan action bar, mengulang header tabel pada halaman
berikutnya, mencegah satu baris terpotong, serta menjaga blok tanda tangan tetap
utuh pada akhir dokumen.

## 7. Detail Per Catatan

Halaman laporan tidak membuka modal atau menampilkan aksi cetak per catatan.
Ringkasan lengkap dibuka dengan ikon mata, sedangkan Hasil Layanan dibuka dengan
ikon chevron langsung di dalam tabel atau kartu.

## 8. Penandatangan

- Kop dan blok tanda tangan dibuat sebagai partial Blade reusable.
- Rekap memakai Koordinator BK dan Waka Kesiswaan.
- Konsultasi memakai relasi `counselor`, bukan pengguna login.
- Koordinator dan Waka hanya dipilih bila tepat satu akun aktif dengan role
  terkait tersedia. Kondisi kosong atau ganda ditampilkan sebagai
  `Penandatangan belum tersedia`.
- Kepala Sekolah dan NIP tidak ditampilkan karena belum mempunyai sumber data
  pada schema saat ini; keduanya tidak boleh di-hardcode.
- Tanda tangan hanya muncul pada akhir preview cetak dan Word serta memakai
  `break-inside: avoid` dan `page-break-inside: avoid`.
- Excel tidak memakai blok tanda tangan.

## 9. Excel dan Word

Pilihan awal yang paling sederhana dan tetap jujur terhadap format:

- Excel: `.xlsx` native melalui `phpoffice/phpspreadsheet`.
- Word: HTML Blade ber-MIME `application/msword` dan ekstensi `.doc`, mengikuti
  pola referensi.
- `.docx` native tidak dibuat pada tahap awal. Tambahkan
  `phpoffice/phpword` hanya jika sekolah mensyaratkan `.docx` tanpa mode
  kompatibilitas.
- Sebagian versi Word dapat menampilkan mode kompatibilitas untuk `.doc`.
  Jika peringatan tersebut tidak diterima pada UAT, naikkan format menjadi
  `.docx` native melalui PHPWord.

Preview, Excel, Word, dan cetak memakai builder/filter/scope yang sama. Hanya UI
yang dipaginasi; semua dokumen memakai seluruh hasil filter. Excel wajib
menetralkan formula injection. Nama file berasal dari allowlist dan timestamp
server.

## 10. Otorisasi dan Privasi

- Guru BK hanya menerima scope profesional dan kasus khusus existing.
- Koordinator menerima gabungan yang diizinkan.
- Admin IT dan Waka murni ditolak.
- Daftar, modal, preview, unduhan, URL langsung, dan archive memakai batas yang
  sama.
- Kode kasus, catatan internal, payload provider, credential, dan field di luar
  allowlist tidak masuk keluaran.
- Hapus tidak pernah menjadi hard delete.

## 11. Batas Implementasi

- Tidak ada migration atau tabel baru.
- Tidak ada framework tabel, template builder, atau library print/PDF baru.
- Gunakan print stylesheet browser untuk Cetak/Simpan PDF.
- Gunakan PhpSpreadsheet hanya untuk `.xlsx` setelah dependency disetujui.
- Jangan menyalin React, Tailwind, Supabase, base64 logo, atau fallback URL logo
  dari repository referensi.
- Markup panjang mengikuti aturan multi-line pada `AGENTS.md`.
- Tidak membuat atau menjalankan testing maupun gate sampai pengguna memberi
  perintah terpisah.

## 12. Acceptance Criteria

- `/reports` tidak lagi menampilkan tiga tab lama.
- Filter hanya tahun ajaran, kelas, jenis layanan, dan jumlah data; kelas wajib
  berasal dari tahun ajaran terpilih.
- Default menampilkan 10 data terbaru.
- Preview/unduhan menampilkan seluruh dataset terfilter dari tanggal paling awal.
- Klik baris tidak membuka modal; ikon mata dan chevron membuka detail inline.
- Preview rekap memakai A4 landscape.
- Action bar preview menyediakan Kembali, Excel, Word, dan Cetak/Simpan PDF.
- Rekap memuat penandatangan yang disetujui tanpa NIP;
  kondisi sumber kosong/ganda tidak memilih akun secara diam-diam.
- Archive hanya tersedia sesuai policy dan tetap soft delete.
- Semua keluaran memakai scope dan allowlist yang sama.
- Portal Waka tidak berubah.
