# Pekerjaan Aktif Ruang BK

## Status

- Checkpoint selesai: 1-5.
- PR #15 Checkpoint 5B terintegrasi ke `cobasidebar` pada commit `fd9a20a`.
- Branch sumber remote `checkpoint-5b-operasional` sudah tidak ada.
- Checkpoint 6 dibagi menjadi 6A Dapodik, 6B pengaturan integrasi, dan 6C
  laporan/baseline praproduksi tanpa mengubah scope.
- PR #16 Checkpoint 6A terintegrasi ke `cobasidebar` pada commit `81e1587`.
- Branch sumber remote `checkpoint-6a-dapodik` sudah tidak ada.
- Checkpoint selesai lokal: 6B - Refactor pengaturan integrasi.
- Branch: `checkpoint-6b-integration-settings`.
- Worktree: `.worktrees/checkpoint-6b-integration-settings`.
- Baseline Checkpoint 6B: `81e1587` dari `origin/cobasidebar` setelah PR #16.

## Hasil dan gate terakhir

- `git fetch --prune origin` mengonfirmasi `origin/cobasidebar` pada `81e1587`.
- PR #16 berstatus `MERGED`; branch sumber remote 6A sudah dihapus.
- Baseline worktree lulus `composer test`: 454 test/3.498 assertion.
- Setup memakai PHP/dependency dari lock file; tidak ada update dependency.
- `IntegrationSettingService` menjadi facade 99 baris dengan constructor dan
  tujuh method publik yang tetap kompatibel.
- Logika dipisah ke `IntegrationStateResolver`, `IntegrationSettingUpdater`,
  `IntegrationConnectionTester`, dan `IntegrationActivationService`.
- Focused gate lulus 110 test/1.463 assertion, Pint, dan diff-check.
- Full gate lulus 454 test/3.498 assertion, Pint, checker frontend, build,
  Composer strict, dan diff-check.
- Binding `IntegrationConfigurationProvider` tidak berubah; tidak ada migration,
  perubahan route/controller/policy/dependency, atau adapter production.
- Commit refactor: `952162e`.

## Scope Checkpoint 6B

1. Pecah `IntegrationSettingService` menjadi facade untuk resolver status,
   penyimpanan, uji koneksi, dan aktivasi.
2. Pertahankan binding `IntegrationConfigurationProvider`, credential terenkripsi,
   transaksi, lock/fencing, deadline, audit, dan redaksi secret.
3. Jalankan focused gate integrasi dan gate penuh.
4. Integrasikan branch 6B setelah sinkronisasi dan verifikasi ulang.

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

1. Jalankan focused gate dan full gate setelah rebase ke hasil PR #16.
2. Review diff, push, dan buat PR 6B ke `cobasidebar`.
3. Setelah merge, verifikasi status `MERGED` dan hapus branch sumber remote.
4. Mulai Checkpoint 6C hanya setelah PR 6B berstatus `MERGED`.

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
