# Riwayat Pengembangan Ruang BK

Status berjalan dan petunjuk serah terima ada di [pekerjaan aktif](current-work.md).
File ini hanya memuat ringkasan pekerjaan selesai, bukan laporan evidence panjang.

## Sedang berjalan

- Checkpoint 3 selesai pada branch fitur; [PR #10](https://github.com/ibnuahmadfauzi/sibk/pull/10) menunggu integrasi ke `cobasidebar`.
- Berikutnya Checkpoint 4: Task 1–7 plan penyederhanaan laporan yang sudah diaudit.
- Adapter production menunggu kontrak resmi provider dan admission gate.

## Pekerjaan selesai

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
