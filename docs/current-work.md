# Pekerjaan Aktif Ruang BK

## Status

- Checkpoint selesai: 2 — Bersihkan baseline `cobasidebar`
- Checkpoint berikutnya: 3 — Sederhanakan pengujian RBAC dan audit plan
- Branch kerja: `checkpoint-2-baseline` (menunggu integrasi ke `cobasidebar`)
- Worktree: `.worktrees/checkpoint-1-arsip` (folder lama digunakan ulang)
- Commit task terakhir: `51fa0bc`; commit penutup dapat dilihat dengan `git log -1`.

## Hasil dan gate terakhir

- README/AGENTS/aturan menunjuk PRD/SRS v1.1, minimum PHP 8.3, dan baseline `cobasidebar`.
- Sembilan sumber historis dikeluarkan; development log aktif diringkas.
- 41 screenshot, satu workbook, dan analisis artikel dihapus tanpa diarsipkan.
- Histori Git tidak ditulis ulang; artefak lama tetap dapat dipulihkan.
- Manual/CSV dan kode penelitian masih tersedia sebagai input test sampai Checkpoint 3.
- Gate lulus: 411 test/3.148 assertion, Pint, checker frontend, build, Composer strict, diff-check.
- Focused test penelitian lulus 14 test/120 assertion.
- Tautan lokal pada 14 dokumen yang diubah valid; kode/dependency tidak berubah.
- Test berjalan pada PHP 8.4.12; belum membuktikan runtime PHP 8.3 secara langsung.

## Arsip

- Repository private: `https://github.com/Aflahul/sibk-docs-archive`
- Commit snapshot: `dcdd2e9`; sumber `b4310e1`; sepuluh hash sesuai.
- Clone: `D:\PPG 2026\SEMESTER 2\sibk-docs-archive`
- Undangan `ibnuahmadfauzi` masih menunggu penerimaan.

## Langkah berikutnya

1. Selesaikan integrasi branch Checkpoint 2 sebelum pekerjaan berikutnya.
2. Buat plan rinci Checkpoint 3 dari spec baseline yang disetujui.
3. Audit cakupan empat role dan scope data pada test reguler.
4. Pindahkan assertion penting sebelum menghapus command/seeder/katalog/verifier/test penelitian.
5. Audit route legacy dan plan penyederhanaan 15 task; jangan menganggapnya sudah selesai.
6. Adapter production tetap ditahan sampai kontrak resmi lolos admission gate.

## Acuan

- Plan selesai: `docs/superpowers/plans/2026-09-14-checkpoint-2-baseline.md`
- Spec: `docs/superpowers/specs/2026-09-14-baseline-cobasidebar-arsip-dan-penyederhanaan-design.md`
