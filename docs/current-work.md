# Pekerjaan Aktif Ruang BK

## Status

- Pekerjaan aktif: penyederhanaan halaman Penugasan Kelas.
- Branch: `fitur/penyederhanaan-penugasan-kelas`.
- Worktree: `.worktrees/penyederhanaan-penugasan-kelas`.
- Base: `cobasidebar`; `main` tidak disentuh.
- Status: **KOREKSI LAPORAN SELESAI; CHECKPOINT BERIKUTNYA BELUM DIMULAI**.
- Spec: `docs/superpowers/specs/2026-09-23-penyederhanaan-penugasan-kelas-design.md`.

## Hasil implementasi 22–23 September 2026

- Halaman tiga tab diganti satu daftar catatan kasus dan konsultasi.
- Filter dibatasi ke Tahun Ajaran, Kelas, Jenis Layanan BK, dan jumlah data
  `10/25/50/100`; default daftar adalah 10 data terbaru.
- Jumlah data ditempatkan pada header tabel dan langsung memuat ulang tabel.
  Panel filter memakai satu tombol: `Terapkan` saat belum aktif/ada perubahan,
  lalu `Reset` dengan warna berbeda saat filter aktif tanpa perubahan.
- Teks urutan pada header tabel diganti ringkasan seluruh hasil filter. Total
  Catatan selalu tampil; Permasalahan/Konsultasi yang tidak relevan dengan
  filter disembunyikan. Ringkasan yang sama masuk preview/PDF dan Excel.
- Daftar Guru BK/Koordinator menampilkan Hari/Tanggal, Layanan/Jenis Masalah,
  dan Hasil. Satu ikon kaca pembesar membuka row detail Latar Belakang Masalah
  serta Penanganan mulai dari kolom Hari/Tanggal, dengan latar lembut, garis
  aksen kiri, dan bayangan inset agar terlihat tenggelam.
- Jenis catatan ditampilkan kecil sebagai Permasalahan/Konsultasi tanpa kata
  `Catatan`; bidang layanan tampil lebih besar dan tebal.
- Klik baris tidak membuka modal dan aksi cetak individual tidak ditampilkan;
  aksi arsip hanya tampil bila policy objek mengizinkan.
- Tombol `Cetak / Unduh Rekap` membuka preview seluruh hasil filter dengan urutan
  tanggal paling awal. Preview tidak membuka dialog cetak otomatis.
- Preview rekap memakai A4 portrait dengan Download Excel dan Cetak/Simpan PDF.
- Font tabel preview/PDF diperkecil secara terlokalisasi menjadi 8 pt, padding
  dirapatkan, header diizinkan membungkus, dan proporsi kolom portrait diperbaiki.
  Font tabel Excel memakai 9 pt.
- Tabel dokumen putih polos memakai tujuh kolom: No, Hari/Tanggal, Nama/Kelas,
  Jenis Masalah, Ringkasan, Guru BK, dan Keterangan. Ringkasan mengambil
  `resolution_summary` kasus atau `result` konsultasi.
- Keterangan preview/PDF/Excel untuk Permasalahan memuat Sumber dan Tindak
  Lanjut terbaru; Konsultasi tetap memakai `Selesai`.
- Garis luar tabel dokumen tetap utuh sampai sisi bawah baris terakhir.
- Kolom Guru BK memakai owner kasus terakhir untuk Permasalahan atau pencatat
  konsultasi pada `counselor_id`.
- Excel `.xlsx` memakai `phpoffice/phpspreadsheet:^5.10`; unduhan Word dihapus.
- Kop menempatkan dua placeholder logo dan garis tepat setelah alamat. Subtitle
  dokumen hanya menampilkan tahun ajaran.
- Preview/PDF dan Excel menyajikan ringkasan sebagai kalimat lengkap yang
  menjelaskan total serta komposisi hasil filter, bukan deretan angka singkat.
- Rekap memakai Koordinator BK dan Waka Kesiswaan. Kondisi akun penandatangan kosong/ganda
  menampilkan `Penandatangan belum tersedia`; NIP tidak ditampilkan.
- Waka memakai `/reports` dan Blade yang sama dengan Koordinator tanpa penanda
  hanya-baca. Kolom Waka dibatasi ke struktur tabel dokumen: No, Hari/Tanggal,
  Nama/Kelas, Jenis Masalah, Ringkasan, Guru BK, dan Keterangan.
- Waka tidak menerima latar belakang, penanganan, hasil terpisah, URL aksi, atau
  kemampuan arsip pada payload view. Preview, cetak/PDF, Excel, dan preview per
  catatan ditolak server melalui policy dokumen terpisah.
- Portal laporan Waka tiga tab, request, service rekap, dan empat Blade khusus
  dipensiunkan. URL lama diarahkan ke `/reports` untuk akun Waka dan endpoint
  ekspor CSV monitoring lama dihapus.

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

Pekerjaan masih berada pada tahap spec. Sesuai instruksi pengguna, test, Pint,
checker frontend, build, Composer strict, dan gate lain belum dijalankan.
Verifikasi implementasi kelak mencakup:

1. Akses halaman dan mutasi hanya untuk Koordinator BK.
2. Seluruh kelas konteks, jumlah murid aktif, pencarian, dan filter status.
3. Konteks tahun aktif/persiapan serta penolakan tahun berakhir atau manipulasi ID.
4. Create, no-op, penggantian terjadwal, pergantian aktif, histori, dan overlap.
5. Readiness serta aktivasi tahun ajaran pada halaman yang sama.
6. Migration penghapusan `decision_number` dan pembersihan seluruh consumer.
7. Penghapusan menu, halaman, endpoint, dan mutasi Pengalihan Permasalahan tanpa
   mengganggu owner awal serta scope akses kasus existing.
8. Penyederhanaan tabel laporan Guru BK/Koordinator menjadi Hasil dan satu
   kontrol detail tanpa mengubah tampilan Waka.
9. Mapping Keterangan preview/PDF/Excel menjadi Sumber dan Tindak Lanjut terbaru
   untuk Permasalahan tanpa mengubah proyeksi Waka.

## Langkah berikutnya

1. Pengguna mereview hasil koreksi tabel laporan.
2. Setelah disetujui, lanjutkan penghapusan Pengalihan Permasalahan.
3. Sederhanakan Penugasan Kelas setelah pengalihan selesai.
4. Jalankan test dan gate hanya bila pengguna memerintahkan.

## Acuan

- Spec aktif: `docs/superpowers/specs/2026-09-23-penyederhanaan-penugasan-kelas-design.md`.
- Plan laporan: `docs/superpowers/plans/2026-09-23-koreksi-tabel-laporan.md`.
- Plan pengalihan: `docs/superpowers/plans/2026-09-23-hapus-pengalihan-permasalahan.md`.
- Plan penugasan: `docs/superpowers/plans/2026-09-23-penyederhanaan-penugasan-kelas.md`.
- Requirement index: `docs/requirements-index.md`.
- API contract: `docs/api-contract.md`.
- Matriks otorisasi: `docs/testing/authorization-matrix.md`.
