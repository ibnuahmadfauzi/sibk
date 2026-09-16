# Pekerjaan Aktif Ruang BK

## Status

- Checkpoint selesai: 1-5.
- PR #15 Checkpoint 5B terintegrasi ke `cobasidebar` pada commit `fd9a20a`.
- Branch sumber remote `checkpoint-5b-operasional` sudah tidak ada.
- Checkpoint 6 dibagi menjadi 6A Dapodik, 6B pengaturan integrasi, dan 6C
  laporan/baseline praproduksi tanpa mengubah scope.
- Checkpoint selesai lokal: 6A - Refactor rekonsiliasi Dapodik.
- Branch: `checkpoint-6a-dapodik`.
- Worktree: `.worktrees/checkpoint-6-refactor`.
- Baseline Checkpoint 6: `fd9a20a` dari `origin/cobasidebar`.

## Hasil dan gate terakhir

- `git fetch --prune origin` mengonfirmasi `origin/cobasidebar` pada `fd9a20a`.
- Commit tersebut mempunyai parent kedua `e3e0cb0`, yaitu HEAD PR #15.
- Ref `refs/pull/15/head` menunjuk `e3e0cb0`; branch sumber remote sudah dihapus.
- Baseline worktree lulus `composer test`: 454 test/3.498 assertion.
- Setup memakai PHP/dependency dari lock file; tidak ada update dependency.
- `DapodikReconciliationService` menjadi facade 48 baris dengan tiga operasi
  publik yang tetap kompatibel: preview, keputusan, dan apply.
- Logika dipisah ke `DapodikPreviewBuilder`, `DapodikMatchResolver`,
  `DapodikApplyValidator`, `DapodikApplyService`, dan
  `DapodikDeactivationPlanner`.
- Focused gate lulus 52 test/353 assertion, Pint, dan diff-check.
- Full gate lulus 454 test/3.498 assertion, Pint, checker frontend, build,
  Composer strict, dan diff-check.
- Tidak ada migration, perubahan route/controller/policy/dependency, adapter
  production, atau perubahan test perilaku.
- Commit refactor: `a07e0e6`.

## Scope Checkpoint 6A

1. Pecah `DapodikReconciliationService` menjadi facade tipis untuk preview,
   pencocokan, validasi apply, penerapan, dan deactivation plan.
2. Jalankan focused gate perilaku, transaksi, concurrency, dan rekonsiliasi
   identitas.
3. Jalankan gate penuh, review diff, dan siapkan PR 6A ke `cobasidebar`.

Checkpoint 6B baru memecah `IntegrationSettingService` setelah PR 6A merged.
Checkpoint 6C baru memecah laporan dan menjalankan baseline praproduksi setelah
PR 6B merged.

## Batas wajib

- Tidak membuat migration atau mengubah skema.
- Tidak mengubah route, otorisasi, output, scope data, atau format CSV.
- Tidak menambah dependency.
- Facade lama tetap tersedia untuk seluruh consumer.
- Driver production Dapodik/e-Tatib tetap `unavailable` sampai kontrak resmi
  lolos admission gate.
- Test mengunci perilaku, bukan struktur internal class.

## Langkah berikutnya

1. Push branch dan buat PR ke `cobasidebar` setelah persetujuan pengguna.
2. Setelah merge, verifikasi status `MERGED` dan branch sumber remote bersih.
3. Mulai Checkpoint 6B dari `cobasidebar` hasil merge.
4. Jangan memulai 6B sebelum PR 6A berstatus `MERGED`.

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
