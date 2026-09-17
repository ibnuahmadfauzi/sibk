# Pekerjaan Aktif Ruang BK

## Status

- Pekerjaan aktif: Revisi SIBK 3.2 untuk Layanan Guru BK.
- Branch aktif: `revisi-sibk-3-2` dari baseline `25a2174`.
- Spec aktif: `docs/superpowers/specs/2026-09-17-revisi-sibk-3-2-guru-bk-design.md`.
- Plan aktif: `docs/superpowers/plans/2026-09-17-revisi-sibk-3-2-guru-bk.md`.
- Checkpoint aktif: A kontrak/fondasi aditif, B peralihan consumer/UI, C pembersihan destruktif dan gate akhir.

- Checkpoint selesai: 1-6.
- PR #15 Checkpoint 5B terintegrasi ke `cobasidebar` pada commit `fd9a20a`.
- Checkpoint 6 dibagi menjadi 6A Dapodik, 6B pengaturan integrasi, dan 6C
  laporan/baseline praproduksi tanpa mengubah scope.
- PR #16 Checkpoint 6A terintegrasi pada commit `81e1587`; branch sumber remote
  `checkpoint-6a-dapodik` sudah dihapus.
- PR #17 Checkpoint 6B terintegrasi pada commit `ce85cb7`; branch sumber remote
  `checkpoint-6b-integration-settings` sudah dihapus.
- PR #18 Checkpoint 6C terintegrasi pada commit `a1426b2`; branch sumber remote
  `checkpoint-6c-report-baseline` sudah dihapus.
- PR #19 memperbarui handoff Checkpoint 6 pada commit `1f5346e`.
- PR #20 menyambungkan histori `main` ke `cobasidebar` pada commit `abff702`.
- PR #21 membersihkan whitespace gate rilis pada commit `a440bdf`.
- PR #22 merilis baseline Ruang BK v1.1 ke `main` pada commit `b59b839`.
- Branch acuan: `cobasidebar`.
- Baseline pengembangan: `a440bdf` dari `origin/cobasidebar`.
- Baseline produksi: `b59b839` dari `origin/main`; tree aplikasi identik dengan
  baseline pengembangan saat rilis.

## Hasil dan gate terakhir

- Baseline bersih lulus 454 test/3.498 assertion setelah `APP_KEY` testing
  disediakan hanya pada environment proses; tidak ada `.env` atau credential
  yang dibuat/disimpan.
- `ReportService` menjadi facade 58 baris. Query pelanggaran, layanan, dan
  prestasi dipisah bersama adapter legacy dan helper bersama minimum.
- Tujuh constant tipe, `types()`, `catalogFor()`, `build()`, dan
  `exportRows()` tetap kompatibel; controller/request tidak berubah.
- Focused gate laporan lulus 41 test/467 assertion, Pint, checker frontend,
  dan diff-check.
- Gate sensitif integrasi lulus 110 test/1.463 assertion; laporan/otorisasi
  lulus 35 test/418 assertion.
- Full gate lulus 454 test/3.498 assertion, Pint, checker frontend, build,
  Composer strict, dan diff-check terhadap `main` pada kandidat rilis `a440bdf`.
- Cache config, route, dan view dapat dibuat serta dibersihkan. Verifikasi
  memakai `CACHE_STORE=array` sementara karena worktree tidak memiliki
  database SQLite lokal.
- Tidak ada migration, perubahan route/controller/policy/dependency, adapter
  production, output, scope data, format CSV, atau perubahan test perilaku.
- Review independen final tidak menemukan temuan Critical, Important, maupun
  Minor; review rilis juga memastikan tidak ada commit `main` yang hilang,
  konflik merge, credential, atau artefak build/cache ter-commit.
- Commit refactor laporan: `3b90ca4`.
- Verifikasi memakai PHP 8.4.11; runtime langsung PHP 8.3 belum diuji.

## Scope Checkpoint 6C

1. Pecah `ReportService` menjadi facade, adapter legacy, helper bersama, dan
   tiga query keluarga laporan.
2. Pertahankan tujuh kontrak legacy, tiga tab laporan, otorisasi, privasi,
   pagination, query count, dan lazy export.
3. Audit larangan scope dan jalankan baseline praproduksi.
4. Integrasikan Checkpoint 6C melalui PR #18 ke `cobasidebar`.

## Batas wajib

- Tidak membuat migration atau mengubah skema.
- Tidak mengubah route, otorisasi, output, scope data, atau format CSV.
- Tidak menambah dependency.
- Facade lama tetap tersedia untuk seluruh consumer.
- Driver production Dapodik/e-Tatib tetap `unavailable` sampai kontrak resmi
  lolos admission gate.
- Test mengunci perilaku, bukan struktur internal class.

## Langkah berikutnya

1. Selesaikan Task 2: migration aditif, reference aktif, audit delta, dan
   verifikasi SQLite tanpa drop skema lama.
2. Lanjutkan Task 3 lalu Wave 1 hanya setelah gate task sebelumnya lulus.
3. Gate akhir Revisi 3.2: `composer test`, `php vendor/bin/pint --test`,
   `npm run check:frontend`, `npm run build`, `composer validate --strict`, dan
   `git diff --check` sebelum PR ke `cobasidebar`.

## Blocker

- Tidak ada blocker implementasi.
- Adapter production tetap ditahan karena kontrak resmi provider belum tersedia.

## Acuan

- Plan aktif: `docs/superpowers/plans/2026-09-17-revisi-sibk-3-2-guru-bk.md`
- Spec aktif: `docs/superpowers/specs/2026-09-17-revisi-sibk-3-2-guru-bk-design.md`

- Plan selesai: `docs/superpowers/plans/2026-09-16-checkpoint-6-refactor-baseline-praproduksi.md`
- Spec: `docs/superpowers/specs/2026-09-14-baseline-cobasidebar-arsip-dan-penyederhanaan-design.md`
- Matriks otorisasi: `docs/testing/authorization-matrix.md`
- Kontrak provider: `docs/integrations/provider-contract-admission.md`
- Requirement index: `docs/requirements-index.md`
