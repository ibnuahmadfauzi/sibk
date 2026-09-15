# Riwayat Pengembangan Ruang BK

Status berjalan dan petunjuk serah terima ada di [pekerjaan aktif](current-work.md).
File ini hanya memuat ringkasan pekerjaan selesai, bukan laporan evidence panjang.

## Sedang berjalan

- Checkpoint 3 dan [PR #10](https://github.com/ibnuahmadfauzi/sibk/pull/10) sudah terintegrasi ke `cobasidebar`.
- Cleanup file trivial dan PR #11 sudah terintegrasi ke `cobasidebar`.
- Checkpoint 4 sudah terintegrasi ke `cobasidebar`.
- Checkpoint 5A (Task 8–10) selesai; Checkpoint 5B (Task 11–15) menjadi
  pekerjaan berikutnya setelah integrasi ke `cobasidebar`.
- Adapter production menunggu kontrak resmi provider dan admission gate.

## Pekerjaan selesai

### 16 September 2026 — Checkpoint 5B Task 14

- Hitungan murid dashboard memakai ketersediaan layanan pada akhir periode;
  preselection murid di luar scope pada pembuatan kasus ditolak server-side.
- Test lintas permukaan membuktikan arsip tidak muncul pada daftar, dashboard,
  monitoring, dan laporan; histori serta daftar proses keluar Waka tetap ada.
- Focused gate lulus 71 test/565 assertion, Pint, checker frontend, scan query,
  dan diff-check.

### 16 September 2026 — Checkpoint 5B Task 13

- Invariant sumber kebenaran, unique/foreign key proses keluar, dan larangan
  state rekap/keluar duplikat dikunci oleh test skema.
- Enam tabel retired tidak dihapus; consumer runtime kosong selain konfigurasi
  driver Laravel yang tetap tersedia. Default queue contoh/fallback kini `sync`.
- Gate SQLite dan MySQL fresh/incremental lulus; database disposable dihapus.
  Focused gate lulus 12 test/77 assertion, Pint, dan diff-check.

### 16 September 2026 — Checkpoint 5B Task 12

- Pembuatan/reset akun kini menerbitkan password sementara satu kali tanpa
  menyimpan nilai plaintext pada audit atau model JSON.
- Akun wajib mengganti password sebelum membuka route operasional; reset
  memutus sesi target dan command tersembunyi memulihkan Admin IT tunggal.
- Focused gate lulus 29 test/296 assertion, Pint, checker frontend, audit route,
  scan password, dan diff-check.

### 15 September 2026 — Checkpoint 5B Task 11

- Satu proses keluar disimpan per murid dengan status `dalam_proses`, `batal`,
  atau `resmi_keluar`; pembukaan ulang memakai row dan tanggal awal yang sama.
- Hanya Guru BK terscope yang mencatat/mengubah dan hanya Koordinator yang
  memutuskan; Waka memperoleh daftar operasional read-only yang diaudit.
- `resmi_keluar` yang sudah efektif menghentikan layanan baru tanpa menghapus
  histori. Dapodik dan data persiapan tidak mengubah proses keluar.
- Focused gate lulus 102 test/735 assertion, Pint, checker frontend, dan diff-check.

### 15 September 2026 — Checkpoint 5A: penyederhanaan operasional

- Kontrak operasional diselaraskan, status layanan dipangkas menjadi empat,
  edit terminal beralasan dan arsip diterapkan, lalu fitur koreksi, notifikasi,
  riwayat, serta aktivitas audit UI dihentikan.
- Self-review tidak menemukan blocker material; regresi placeholder agregat
  status Waka diperbaiki pada sumber query.
- Gate penuh lulus 416 test/3.261 assertion, Pint, checker frontend, build,
  Composer strict, dan diff-check.

### 15 September 2026 — Checkpoint 5A Task 10

- Route, UI, controller, request, policy, model, service, dan test fitur koreksi,
  notifikasi, serta riwayat audit dipensiunkan; tabel lama tetap dipertahankan.
- Audit append-only tetap aktif, tetapi dashboard tidak lagi membaca atau
  menampilkan narasinya; panel kanan kini berisi konteks aman per role.
- Focused gate lulus 25 test/270 assertion, regresi area terkait 78 test/688
  assertion, checker frontend, Pint, dan diff-check.

### 15 September 2026 — Checkpoint 5A Task 9

- Status kasus/konsultasi disederhanakan menjadi empat; data legacy berstatus
  dibatalkan diarsipkan dan reference-nya dinonaktifkan.
- Owner dapat mengedit data selesai dengan alasan 10–500 karakter yang hanya
  disimpan pada audit append-only; owner juga dapat mengarsipkan record.
- Focused gate lulus 43 test/333 assertion, Pint, dan diff-check.

### 15 September 2026 — Pembagian Checkpoint 5

- Checkpoint 5A ditetapkan untuk Task 8–10.
- Checkpoint 5B ditetapkan untuk Task 11–15 dan dimulai setelah Checkpoint 5A
  terintegrasi ke `cobasidebar`.
- Pembagian hanya mengubah batas eksekusi dan review, bukan scope atau perilaku.

### 15 September 2026 — Checkpoint 4: penyederhanaan laporan

- Halaman laporan Guru BK/Koordinator disatukan menjadi tiga tab rekap aman;
  endpoint preview dan ekspor tujuh tipe legacy tetap tersedia.
- Scope, filter, pagination database, identitas tersamarkan, ekspor CSV aman,
  tampilan desktop/mobile, empty state, aksesibilitas, dan cetak tab aktif diterapkan.
- Gate lulus 418 test/3.293 assertion pada SQLite; focused MySQL disposable
  lulus 16 test/148 assertion; Pint, Composer strict, cache, checker frontend,
  build, diff-check, scan privasi, dan scan dependency lulus.
- UAT manual oleh `ui` melalui Chrome pada viewport 1440 × 900, 768 × 1024,
  dan 390 × 844 dinyatakan PASS tanpa temuan gagal; versi browser tidak dilaporkan.

### 15 September 2026 — Cleanup file trivial

- Menghapus fixture config tanpa consumer, empat aset tanpa referensi, test bawaan
  trivial, serta dua file publik kosong/percobaan.
- `laravel/pao` dipertahankan karena aktif merangkum output test; dua
  `Unavailable*Connector` dipertahankan sesuai keputusan.
- Gate lulus 401 test/3.138 assertion, Pint, checker frontend, build,
  Composer strict, dan diff-check.

### 15 September 2026 — Checkpoint 3: pengujian otorisasi umum

- Matriks umum menggantikan manual/CSV penelitian dan menunjuk test empat role,
  scope data, histori, privasi, serta ekspor.
- Lima test reguler ditambahkan sebelum delapan file command/seeder/katalog/
  verifier/test/manual/CSV penelitian dipensiunkan; policy dan Gate produk tetap.
- Audit 15 task memperjelas route legacy, migration forward-only, gate
  disposable, dan verifikasi CLI; seluruh 15 task masih belum selesai.
- Gate lulus 402 test/3.139 assertion, Pint, checker frontend, build,
  Composer strict, diff-check, dan tautan lokal enam dokumen.
- Review independen RBAC serta audit plan tidak menemukan blocker material.
- Runtime PHP 8.4.12; PHP 8.3 belum diuji langsung. Probe koneksi disposable
  pada petunjuk migration lulus tanpa menjalankan migration.
- Commit RBAC `9119b01`, audit `59606d4`; PR #10 ke `cobasidebar`.

### 14 September 2026 — Checkpoint 2: pembersihan baseline

- README, AGENTS, dan aturan aktif menunjuk `cobasidebar`, PRD/SRS v1.1, serta minimum PHP 8.3.
- Sembilan dokumen historis dikeluarkan setelah sepuluh hash sumber/arsip diverifikasi;
  development log aktif diringkas dan log asli tetap di arsip.
- 41 screenshot, satu workbook, dan analisis artikel RBAC dipensiunkan tanpa arsip;
  dapat dipulihkan lewat histori Git. Manual/CSV/kode penelitian tetap sampai Checkpoint 3.
- Gate lulus 411 test/3.148 assertion, focused 14 test/120 assertion, Pint,
  checker frontend, build, Composer strict, diff-check, dan pemeriksaan tautan lokal.
- Verifikasi berjalan di PHP 8.4.12; runtime PHP 8.3 belum diuji langsung.
- Tidak ada perubahan kode aplikasi, dependency, database, atau hak akses.

### 14 September 2026 — Checkpoint 1: fondasi arsip

- Repository privat `Aflahul/sibk-docs-archive` dibuat dengan snapshot commit `dcdd2e9`.
- Sepuluh dokumen historis disalin; SHA-256 sesuai sumber `b4310e1`.
- Clone terpisah tersedia di `D:\PPG 2026\SEMESTER 2\sibk-docs-archive`.
- Undangan akses tulis `ibnuahmadfauzi` dikirim; penerimaan masih menunggu.
- PR #8 di-merge ke `cobasidebar` pada `386cd3c`; 411 test/3.148 assertion dan checker frontend lulus.

### 14 September 2026 — Portal Waka

- Dashboard, Murid dengan Kasus, Monitoring Penanganan, dan Rekap Periode tersedia;
  Laporan Akhir masih placeholder sesuai keputusan produk.
- Proyeksi dan CSV mempertahankan privasi; Waka tidak memperoleh catatan konseling lengkap.
- Gate terakhir: 411 test/3.148 assertion, Pint, build, checker frontend, dan UAT responsif lulus.

### 11 September 2026 — Fondasi integrasi Fase A

- Task 0–12 selesai: konfigurasi terenkripsi, state machine, validasi/lock,
  persiapan data sementara, aktivasi operasional, pratinjau, dan apply atomik.
- Admission gate, prosedur deployment/rotasi credential, serta migration additive
  SQLite/MySQL disposable telah diverifikasi.
- Driver production Dapodik/e-Tatib tetap `unavailable`; adapter nyata belum dibuat.

### Agustus 2026 — Fondasi aplikasi

- Multi-role, otorisasi server, layanan kasus/konsultasi/prestasi, penugasan,
  laporan terscope, dan audit append-only diimplementasikan.
- Fitur yang dipensiunkan tetap mengikuti plan penyederhanaan aktif; ringkasan ini
  tidak menyatakan plan 15 task sudah selesai.

## Arsip historis

[Log lengkap sampai 14 September 2026](https://github.com/Aflahul/sibk-docs-archive/blob/main/testing/completed/development-log-through-2026-09-14.md)
dan plan selesai tersedia di repository privat arsip. Akses memerlukan izin.
