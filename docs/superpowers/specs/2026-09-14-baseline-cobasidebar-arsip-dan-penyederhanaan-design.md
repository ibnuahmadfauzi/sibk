# Baseline `cobasidebar`, Arsip Dokumentasi, dan Penyederhanaan Pengembangan

**Status:** Disetujui pengguna pada 14 September 2026.

## 1. Tujuan

Perubahan ini menyiapkan repository Ruang BK agar lebih mudah dipahami dan
dirawat selama pengembangan. Pekerjaan dibagi menjadi enam checkpoint supaya
dapat dilanjutkan dengan aman ketika sesi atau konteks kerja terputus.

Tujuan utama:

- menjadikan `cobasidebar` sebagai baseline pengembangan aktif;
- mempertahankan `main` sebagai branch stabil untuk produksi;
- memindahkan dokumen historis yang masih berguna ke repository arsip;
- menghapus perangkat penelitian RBAC yang tidak lagi dibutuhkan;
- menyamakan kebutuhan minimum PHP menjadi PHP 8.3 atau lebih baru;
- menyederhanakan laporan, alur operasional, dan skema berdasarkan plan aktif;
- memecah service besar setelah perilaku produk stabil;
- menyiapkan baseline yang mudah diteruskan menuju produksi.

## 2. Batas Pekerjaan

Pekerjaan ini tidak:

- mengubah atau menghapus branch `main`;
- melakukan force push atau menulis ulang histori Git;
- membuat adapter production Dapodik atau e-Tatib sebelum kontrak resmi
  provider tersedia;
- menambah framework frontend, tabel, atau query baru;
- menghapus pengujian otorisasi yang dibutuhkan aplikasi;
- menyimpan credential, `.env`, cache, atau hasil build ke Git;
- mengubah perilaku bisnis ketika service besar dipecah.

## 3. Keputusan Utama

### 3.1 Branch pengembangan

`cobasidebar` menjadi baseline pengembangan resmi. Semua panduan yang masih
menyebut `development` diperbarui menjadi `cobasidebar`.

`main` tetap menjadi branch stabil. Merge ke `main` hanya dipertimbangkan
setelah checkpoint yang diperlukan selesai dan seluruh gate lulus.

Akun pengembang saat ini hanya collaborator pada repository `sibk`. Karena itu,
perubahan default branch atau branch protection yang memerlukan hak pemilik
tidak termasuk tindakan otomatis dalam pekerjaan ini.

### 3.2 Versi PHP

Kebutuhan minimum resmi adalah PHP 8.3 atau lebih baru. PHP 8.4 tetap dapat
digunakan, tetapi tidak menjadi kebutuhan minimum selama kode tidak memakai
fitur khusus PHP 8.4.

`composer.json`, `AGENTS.md`, README, plan aktif, dan dokumentasi setup harus
menyampaikan aturan yang sama.

### 3.3 Repository arsip

Dokumen historis disimpan pada private repository:

```text
Aflahul/sibk-docs-archive
```

Repository dibuat sebagai snapshot bersih dengan satu initial commit. Histori
per file tidak disalin. Histori lama tetap tersedia pada repository `sibk`.

Setelah repository berhasil dibuat dan diverifikasi, akun
`ibnuahmadfauzi` diundang sebagai collaborator.

Struktur awal:

```text
sibk-docs-archive/
├── README.md
├── archive-index.md
├── requirements/v1.0/
├── plans/completed/
├── plans/superseded/
├── specs/completed/
└── testing/completed/
```

Setiap dokumen mencatat path asal, kategori, tanggal arsip, dan commit sumber.

### 3.4 Perangkat penelitian RBAC

Pembuatan artikel penelitian telah dibatalkan. Karena itu, perangkat penelitian
RBAC tidak menjadi bagian produk dan tidak dimasukkan ke repository arsip.

Artefak berikut dipensiunkan dari HEAD `cobasidebar`:

- `docs/research/rbac-results-analysis.md`;
- seluruh `docs/testing/bukti-rbac/`;
- panduan manual dan matriks CSV penelitian RBAC;
- `AuthorizationScenarioSeeder`;
- `AuthorizationScenarioCatalog`;
- `AuthorizationScenarioVerifier`;
- command `rbac:scenario-reset`;
- command `rbac:scenario-verify`;
- `AuthorizationResearchScenarioTest`;
- referensi perangkat penelitian dari dokumen aktif.

Policy, Gate, feature test modul, dan test matriks akses umum tetap
dipertahankan. Assertion penting yang belum tersedia pada test reguler harus
dipindahkan sebelum test penelitian dihapus.

## 4. Klasifikasi Dokumentasi

### 4.1 Tetap aktif di `sibk`

- PRD dan SRS v1.1;
- `docs/requirements-index.md`;
- `docs/api-contract.md`;
- ADR yang masih berlaku;
- dokumentasi integrasi dan admission gate provider;
- dua spec penyederhanaan yang masih aktif;
- satu plan penyederhanaan aktif;
- matriks otorisasi umum yang ringkas;
- `docs/current-work.md`;
- development log yang sudah diringkas.

### 4.2 Dipindahkan ke repository arsip

- PRD dan SRS v1.0 beserta mirror Markdown;
- plan konfigurasi integrasi yang sudah selesai;
- plan Portal Waka yang sudah selesai;
- plan 12 September yang sudah digantikan;
- spec Portal Waka yang sudah selesai;
- laporan UAT lama yang masih berguna sebagai catatan produk;
- salinan development log sebelum diringkas.

### 4.3 Dihapus dari HEAD tanpa masuk arsip

- screenshot dan workbook penelitian RBAC;
- analisis artikel RBAC;
- matriks dan panduan khusus penelitian;
- hasil sementara, cache, file credential, dan hasil build.

Dokumen sumber baru boleh dihapus setelah salinan arsip berhasil di-push dan
diverifikasi. Semua tautan aktif harus diperbarui sebelum commit penghapusan.

## 5. Enam Checkpoint

### Checkpoint 1 — Fondasi arsip

- Periksa dan simpan perubahan lokal yang sudah ada secara terpisah.
- Inventaris dokumen final.
- Buat private repository `Aflahul/sibk-docs-archive`.
- Buat README, indeks, dan struktur arsip.
- Salin dokumen historis yang disepakati.
- Commit dan push snapshot arsip.
- Verifikasi remote dan undang `ibnuahmadfauzi`.
- Jangan menghapus dokumen dari `sibk` pada checkpoint ini.

### Checkpoint 2 — Bersihkan baseline `cobasidebar`

- Verifikasi repository arsip.
- Hapus dokumen usang yang sudah disalin.
- Hapus perangkat visual penelitian RBAC tanpa mengarsipkannya.
- Ringkas `AGENTS.md` dan development log.
- Hentikan kewajiban membaca plan integrasi yang sudah selesai.
- Ganti petunjuk branch `development` menjadi `cobasidebar`.
- Samakan kebutuhan minimum PHP menjadi PHP 8.3 atau lebih baru.
- Perbarui seluruh tautan dokumentasi.

### Checkpoint 3 — Sederhanakan pengujian RBAC

- Buat matriks otorisasi umum yang ringkas.
- Pastikan test umum mencakup empat role dan batas akses data utama.
- Pindahkan assertion penting dari test penelitian bila diperlukan.
- Hapus command, seeder, katalog, verifier, dan test penelitian.
- Hapus kontrak API yang hanya menjelaskan alat penelitian.
- Audit ulang route legacy dan plan 15 task.
- Revisi plan bila ditemukan kontradiksi atau langkah yang sudah tidak relevan.
- Jalankan regresi penuh.

### Checkpoint 4 — Penyederhanaan laporan

- Jalankan Task 1 sampai Task 7 dari plan aktif yang sudah diaudit.
- Sediakan tiga tab laporan Guru BK dan Koordinator BK.
- Pertahankan kontrak legacy yang memang masih dibutuhkan.
- Gunakan scope dan redaksi identitas yang sama pada tabel dan CSV.
- Jalankan focused test, full test, dan pemeriksaan manual sederhana.

### Checkpoint 5 — Penyederhanaan operasional dan skema

- Jalankan Task 8 sampai Task 15 dari plan aktif yang sudah diaudit.
- Sederhanakan lifecycle layanan dan akun.
- Pensiunkan koreksi, notifikasi, riwayat, dan route terkait sesuai keputusan
  produk final.
- Implementasikan proses keluar murid.
- Capai target akhir 30 tabel termasuk tabel `migrations`.
- Verifikasi migration pada SQLite dan MySQL disposable.

### Checkpoint 6 — Refactor dan baseline praproduksi

- Pecah `DapodikReconciliationService`.
- Pecah `IntegrationSettingService`.
- Bersihkan atau ganti `ReportService` lama.
- Pertahankan facade sementara selama masih ada pemanggil lama.
- Jalankan test perilaku, transaksi, concurrency, privasi, dan regresi.
- Siapkan `cobasidebar` sebagai kandidat merge ke `main`.
- Catat kebutuhan adapter production sebagai pekerjaan terpisah.

Adapter production Dapodik/e-Tatib hanya dibuat setelah kontrak resmi provider
lolos admission gate. Bila kontrak sudah tersedia setelah Checkpoint 6, adapter
memakai spec dan plan tersendiri.

## 6. Pencatatan agar Pekerjaan Mudah Dilanjutkan

File `docs/current-work.md` menjadi petunjuk pendek untuk sesi berikutnya dan
dibatasi sekitar 100 baris. Isinya:

- checkpoint aktif;
- status singkat;
- commit terakhir;
- gate yang sudah lulus;
- pekerjaan berikutnya;
- blocker;
- tautan ke plan aktif.

Setelah checkpoint selesai, ringkasan dipindahkan ke development log.
`current-work.md` lalu hanya memuat checkpoint berikutnya. Agent tidak perlu
membaca seluruh plan dan dokumen historis pada awal setiap sesi.

## 7. Pemecahan Service

### 7.1 Rekonsiliasi Dapodik

`DapodikReconciliationService` menjadi facade tipis untuk komponen berikut:

- `DapodikPreviewBuilder` membuat dan mengelompokkan pratinjau;
- `DapodikMatchResolver` mencocokkan tahun ajaran, rombel, dan murid;
- `DapodikApplyValidator` memastikan hasil masih sah;
- `DapodikApplyService` menerapkan perubahan dalam satu transaksi;
- `DapodikDeactivationPlanner` menentukan data resmi yang boleh dinonaktifkan.

### 7.2 Pengaturan integrasi

`IntegrationSettingService` menjadi facade untuk:

- `IntegrationStateResolver` menghitung status konfigurasi;
- `IntegrationSettingUpdater` menyimpan URL dan credential;
- `IntegrationConnectionTester` menjalankan uji koneksi;
- `IntegrationActivationService` mengaktifkan atau menonaktifkan konfigurasi.

### 7.3 Laporan

Laporan dipisahkan menjadi:

- `ViolationReportQuery`;
- `CounselingReportQuery`;
- `AchievementReportQuery`;
- `LegacyReportAdapter` selama kontrak lama masih dipakai.

Refactor tidak membuat migration, mengubah otorisasi, mengubah format keluaran,
atau menambah dependency. Test memeriksa hasil dan perilaku, bukan susunan
internal class.

## 8. Pengujian Umum

Gate dasar setiap checkpoint:

```text
php artisan test
php vendor/bin/pint --test
npm run check:frontend
npm run build
composer validate
git diff --check
```

Tambahan hanya ketika relevan:

- perubahan database diuji pada SQLite dan satu MySQL disposable;
- perubahan akses diuji untuk empat role dan scope objek terkait;
- perubahan integrasi diuji agar credential tidak bocor dan kegagalan aman;
- perubahan UI diperiksa singkat pada desktop dan ponsel;
- refactor dibandingkan dengan perilaku sebelum perubahan.

Pemeriksaan manual cukup memastikan login, menu sesuai role, halaman utama,
penolakan akses, penyimpanan form utama, tampilan ponsel, dan logout. Screenshot
per skenario, workbook, dataset penelitian, serta laporan evidence panjang tidak
diwajibkan.

Checkpoint tidak selesai bila gate wajib gagal. Penyebab dicatat singkat di
`docs/current-work.md`, diperbaiki pada checkpoint yang sama, lalu gate diulang.

## 9. Bahasa Commit dan Dokumentasi

Gunakan Bahasa Indonesia yang mudah dipahami developer pemula. Prefix seperti
`docs`, `test`, `feat`, `fix`, dan `refactor` boleh dipakai, tetapi isi pesan
harus menjelaskan perubahan dengan kata sederhana.

Contoh:

```text
docs: buat tempat arsip untuk dokumen lama
docs: jadikan cobasidebar sebagai branch pengembangan
test: sederhanakan pengujian hak akses
feat: sederhanakan halaman laporan guru bk
refactor: pecah layanan integrasi agar lebih mudah dirawat
```

Satu commit hanya memuat satu perubahan yang mudah dijelaskan. Pemindahan
dokumen tidak dicampur dengan perubahan fitur. Commit akhir setiap checkpoint
hanya dibuat setelah gate wajib lulus.

## 10. Penanganan Kegagalan

- Jika repository arsip gagal dibuat atau diverifikasi, dokumen sumber tidak
  dihapus.
- Jika penghapusan perangkat penelitian merusak test umum, assertion penting
  dipindahkan sebelum penghapusan dilanjutkan.
- Jika migration gagal pada salah satu database uji, checkpoint tidak lanjut.
- Jika refactor mengubah keluaran, facade lama dipertahankan dan perubahan
  diperbaiki pada checkpoint yang sama.
- Tidak ada push ke `main` selama enam checkpoint.
- Tidak ada tindakan destruktif terhadap branch, histori Git, atau database
  shared.

## 11. Kriteria Selesai

- `cobasidebar` terdokumentasi sebagai baseline pengembangan resmi.
- Seluruh dokumentasi aktif menyebut PHP 8.3 atau lebih baru.
- Repository private `Aflahul/sibk-docs-archive` tersedia dan dapat diakses
  collaborator yang disepakati.
- Repository aktif tidak lagi memuat perangkat penelitian RBAC.
- Test otorisasi umum tetap melindungi empat role dan scope data utama.
- Agent dapat melanjutkan pekerjaan hanya dengan membaca `docs/current-work.md`,
  AGENTS, dan bagian plan yang relevan.
- Plan penyederhanaan selesai melalui dua checkpoint terpisah.
- Service besar sudah dibagi tanpa mengubah perilaku.
- Seluruh gate otomatis lulus pada akhir setiap checkpoint.
- Adapter production tetap ditahan sampai kontrak provider tersedia.
