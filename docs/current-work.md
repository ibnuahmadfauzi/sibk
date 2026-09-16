# Pekerjaan Aktif Ruang BK

## Status

- Checkpoint selesai: 1-5.
- PR #15 Checkpoint 5B terintegrasi ke `cobasidebar` pada commit `fd9a20a`.
- Branch sumber remote `checkpoint-5b-operasional` sudah tidak ada.
- Checkpoint aktif: 6 - Refactor dan baseline praproduksi.
- Branch: `checkpoint-6-refactor`.
- Worktree: `.worktrees/checkpoint-6-refactor`.
- Baseline Checkpoint 6: `fd9a20a` dari `origin/cobasidebar`.

## Verifikasi awal

- `git fetch --prune origin` mengonfirmasi `origin/cobasidebar` pada `fd9a20a`.
- Commit tersebut mempunyai parent kedua `e3e0cb0`, yaitu HEAD PR #15.
- Ref `refs/pull/15/head` menunjuk `e3e0cb0`; branch sumber remote sudah dihapus.
- Baseline worktree lulus `composer test`: 454 test/3.498 assertion.
- Setup memakai PHP/dependency dari lock file; tidak ada update dependency.

## Scope Checkpoint 6

1. Pecah `DapodikReconciliationService` menjadi facade tipis untuk preview,
   pencocokan, validasi apply, penerapan, dan deactivation plan.
2. Pecah `IntegrationSettingService` menjadi facade untuk resolver status,
   penyimpanan, uji koneksi, dan aktivasi.
3. Ganti isi `ReportService` lama dengan facade, tiga query keluarga laporan,
   dan adapter legacy.
4. Jalankan focused gate untuk perilaku, transaksi/concurrency, privasi,
   otorisasi, serta kompatibilitas legacy.
5. Jalankan gate penuh dan cache praproduksi, lalu siapkan PR ke `cobasidebar`.

## Batas wajib

- Tidak membuat migration atau mengubah skema.
- Tidak mengubah route, otorisasi, output, scope data, atau format CSV.
- Tidak menambah dependency.
- Facade lama tetap tersedia untuk seluruh consumer.
- Driver production Dapodik/e-Tatib tetap `unavailable` sampai kontrak resmi
  lolos admission gate.
- Test mengunci perilaku, bukan struktur internal class.

## Langkah berikutnya

1. Jalankan Task 1 plan: pecah rekonsiliasi Dapodik.
2. Verifikasi focused gate dan commit Task 1 sebelum lanjut.
3. Jalankan Task 2 pengaturan integrasi.
4. Jalankan Task 3 laporan legacy.
5. Jalankan Task 4 gate penuh, review, dan handoff penutupan checkpoint.

## Blocker

- Tidak ada blocker implementasi.
- Adapter production tetap ditahan karena kontrak resmi provider belum tersedia.
- Runtime langsung PHP 8.3 belum diverifikasi; baseline saat ini memakai runtime
  lokal yang tersedia.

## Acuan

- Plan aktif: `docs/superpowers/plans/2026-09-16-checkpoint-6-refactor-baseline-praproduksi.md`
- Spec: `docs/superpowers/specs/2026-09-14-baseline-cobasidebar-arsip-dan-penyederhanaan-design.md`
- Matriks otorisasi: `docs/testing/authorization-matrix.md`
- Kontrak provider: `docs/integrations/provider-contract-admission.md`
- Requirement index: `docs/requirements-index.md`
