# Pekerjaan Aktif Ruang BK

## Status

- Pekerjaan aktif: revisi halaman Laporan Guru BK/Koordinator.
- Branch: `fitur/revisi-laporan-catatan-layanan`.
- Worktree: `.worktrees/revisi-laporan-catatan-layanan`.
- Base: `cobasidebar`; `main` tidak disentuh.
- Status implementasi: **MENUNGGU VERIFIKASI**.
- Commit perencanaan: `30be68f docs: perbarui rencana laporan layanan BK`.

## Hasil implementasi 22 September 2026

- Halaman tiga tab diganti satu daftar catatan kasus dan konsultasi.
- Filter dibatasi ke Tahun Ajaran, Kelas, Jenis Layanan BK, dan jumlah data
  `10/25/50/100`; default daftar adalah 10 data terbaru.
- Jumlah data ditempatkan pada header tabel dan langsung memuat ulang tabel.
  Panel filter memakai satu tombol: `Terapkan` saat belum aktif/ada perubahan,
  lalu `Reset` dengan warna berbeda saat filter aktif tanpa perubahan.
- Teks urutan pada header tabel diganti ringkasan seluruh hasil filter. Total
  Catatan selalu tampil; Permasalahan/Konsultasi yang tidak relevan dengan
  filter disembunyikan. Ringkasan yang sama masuk preview/PDF dan Excel.
- Daftar menampilkan Hari/Tanggal, Layanan/Jenis Masalah, serta cuplikan 80
  karakter untuk Latar Belakang Masalah dan Penanganan. Ikon kaca pembesar
  membuka teks lengkap; ikon chevron membuka panel Hasil Layanan bertingkat
  mulai dari kolom Hari/Tanggal, dengan latar lembut, garis aksen kiri, dan
  bayangan inset agar terlihat tenggelam.
- Jenis catatan ditampilkan kecil sebagai Permasalahan/Konsultasi tanpa kata
  `Catatan`; bidang layanan tampil lebih besar dan tebal.
- Klik baris tidak membuka modal dan aksi cetak individual tidak ditampilkan;
  aksi arsip hanya tampil bila policy objek mengizinkan.
- Tombol `Cetak / Unduh Rekap` membuka preview seluruh hasil filter dengan urutan
  tanggal paling awal. Preview tidak membuka dialog cetak otomatis.
- Preview rekap memakai A4 landscape dengan Download Excel dan Cetak/Simpan PDF.
- Tabel dokumen putih polos memakai enam kolom: No, Hari/Tanggal, Nama/Kelas,
  Jenis Masalah, Ringkasan, dan Keterangan. Ringkasan mengambil
  `resolution_summary` kasus atau `result` konsultasi.
- Excel `.xlsx` memakai `phpoffice/phpspreadsheet:^5.10`; unduhan Word dihapus.
- Kop menempatkan dua placeholder logo dan garis tepat setelah alamat. Subtitle
  dokumen hanya menampilkan tahun ajaran.
- Rekap memakai Koordinator BK dan Waka Kesiswaan. Kondisi akun penandatangan kosong/ganda
  menampilkan `Penandatangan belum tersedia`; NIP tidak ditampilkan.
- Jalur laporan legacy tiga tab dan CSV tidak memiliki consumer runtime sehingga
  request/service legacy dipensiunkan. Test lama yang merujuk jalur tersebut
  belum diperbarui karena pekerjaan test belum diizinkan.

## Batas dan catatan deployment

- Tidak ada migration atau perubahan database.
- `ReportPreviewSeeder` tersedia untuk data pratinjau lokal: satu kasus dan dua
  konsultasi. Seeder bersifat idempoten, dibatasi ke environment local/testing,
  dan tidak didaftarkan ke `DatabaseSeeder`.
- Database lokal `sibk_uji` sudah menjalankan migration
  `2026_09_17_000200_remove_revisi_sibk_3_2_legacy_schema` sebelum data contoh
  dibuat; database shared/production tidak disentuh.
- Dockerfile sudah memasang ekstensi PHP `zip`. Runtime lain wajib mengaktifkan
  ekstensi yang dipersyaratkan PhpSpreadsheet sebelum ekspor Excel digunakan.
- Template kop dan dokumen bersifat sementara sampai template resmi sekolah
  selesai; partial terpusat memudahkan penggantian tanpa mengubah query/ekspor.
- Temporary file Excel dihapus setelah response; data spreadsheet yang
  berpotensi menjadi formula dinetralkan.
- Adapter production Dapodik/e-Tatib tetap di luar scope dan `unavailable`.

## Verifikasi yang belum dijalankan

Sesuai instruksi pengguna, test, Pint, checker frontend, build, Composer strict,
dan gate lain belum dijalankan. Skenario berikut menunggu perintah terpisah:

1. Scope Guru BK/Koordinator dan penolakan akses URL langsung.
2. Validasi pasangan Tahun Ajaran dan Kelas.
3. Pagination UI tidak mengurangi isi preview/unduhan.
4. Orientasi cetak, urutan data, tanda tangan, serta keluaran Excel.
5. Pemeriksaan privasi bahwa NISN, kode kasus, dan catatan internal tidak keluar.

## Langkah berikutnya

1. Tinjau tampilan laporan memakai tiga data contoh lokal.
2. Tunggu perintah pengguna untuk menjalankan atau memperbarui test dan gate.
3. Setelah verifikasi disetujui dan lulus, siapkan PR ke `cobasidebar`.

## Acuan

- Plan: `docs/superpowers/plans/2026-09-21-revisi-laporan-catatan-layanan.md`.
- Spec: `docs/superpowers/specs/2026-09-14-penyederhanaan-laporan-guru-koordinator-design.md`.
- Requirement index: `docs/requirements-index.md`.
- API contract: `docs/api-contract.md`.
- Matriks otorisasi: `docs/testing/authorization-matrix.md`.
