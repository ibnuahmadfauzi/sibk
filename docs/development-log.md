# Riwayat Pengembangan Ruang BK

Status berjalan dan petunjuk serah terima ada di [pekerjaan aktif](current-work.md).
File ini hanya memuat ringkasan pekerjaan selesai, bukan laporan evidence panjang.

## Sedang berjalan

- Checkpoint 2 — pembersihan baseline dan dokumentasi, pada branch `checkpoint-2-baseline`.
- Adapter production menunggu kontrak resmi provider dan admission gate.

## Pekerjaan selesai

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
