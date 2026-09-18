# Pekerjaan Aktif Ruang BK

## Status

- Pekerjaan aktif: Revisi SIBK 3.2 untuk Layanan Guru BK.
- Branch aktif: `revisi-sibk-3-2`; checkpoint aman terakhir `b023959`.
- Spec aktif: `docs/superpowers/specs/2026-09-17-revisi-sibk-3-2-guru-bk-design.md`.
- Plan aktif: `docs/superpowers/plans/2026-09-17-revisi-sibk-3-2-guru-bk.md`.
- Checkpoint aktif: 7A — Penyelarasan Fitur, sedang pause sebelum implementasi
  produksi Wave 2.
- Urutan delivery: 7A penyelarasan fitur, 7B cleanup runtime, lalu 7C cleanup
  skema dan gate final. Setiap checkpoint memakai PR terpisah ke `cobasidebar`;
  `main` tidak disentuh.

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

- Wave 1 dan Integration Gate 1 Revisi 3.2 sudah terintegrasi lokal pada commit
  `b023959` dan lulus 42 test/428 assertion, Pint, checker frontend, build, serta
  diff-check.
- Worktree Wave 2 untuk Waka, laporan, dan consumer tersedia dari commit yang
  sama. Saat pause masing-masing hanya berisi perubahan test-first yang belum
  di-commit; belum ada file production Wave 2 yang diubah.
- Root branch dan seluruh worktree Wave 2 lulus `git diff --check`. Tidak ada
  proses implementer aktif, push, PR, atau perubahan ke `main`.
- Detail resume dan batas checkpoint 7A/7B/7C tercatat di plan aktif.

### Baseline terakhir sebelum Revisi 3.2

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

- Pertahankan tiga worktree Wave 2 dan perubahan test-first yang sudah ada;
  jangan mengulang Wave 1.
- Lane D/E/F hanya mengubah file sesuai ownership plan. Review dan focused gate
  wajib sebelum cherry-pick ke branch integrasi.
- Jangan memulai cleanup runtime 7B sebelum PR 7A `MERGED`; jangan membuat
  migration drop 7C sebelum PR 7B `MERGED` dan scan dependency bersih.
- Migration selalu forward-only; database shared/production tidak boleh di-reset.
- Tidak menambah dependency.
- Semua PR checkpoint menargetkan `cobasidebar`. `main` hanya untuk PR rilis
  terpisah setelah persetujuan pengguna.
- Driver production Dapodik/e-Tatib tetap `unavailable` sampai kontrak resmi
  lolos admission gate.

## Langkah berikutnya

1. Saat diminta melanjutkan, resume Lane D (Waka), E (laporan), dan F
   (consumer/seeder) secara paralel dari worktree yang sudah ada.
2. Review setiap lane, cherry-pick ke `revisi-sibk-3-2`, lalu jalankan
   Integration Gate 2 dan full gate Checkpoint 7A.
3. Perbarui handoff/log dan buka PR 7A ke `cobasidebar`; pause setelah status
   `MERGED` terverifikasi.
4. Buat 7B dari `cobasidebar` terbaru untuk cleanup runtime. Setelah PR 7B
   `MERGED`, buat 7C untuk cleanup skema dan gate final.

## Blocker

- Tidak ada blocker implementasi.
- Implementasi sengaja dipause atas permintaan pengguna pada checkpoint aman.
- Adapter production tetap ditahan karena kontrak resmi provider belum tersedia.

## Acuan

- Plan aktif: `docs/superpowers/plans/2026-09-17-revisi-sibk-3-2-guru-bk.md`
- Spec aktif: `docs/superpowers/specs/2026-09-17-revisi-sibk-3-2-guru-bk-design.md`

- Plan selesai: `docs/superpowers/plans/2026-09-16-checkpoint-6-refactor-baseline-praproduksi.md`
- Spec: `docs/superpowers/specs/2026-09-14-baseline-cobasidebar-arsip-dan-penyederhanaan-design.md`
- Matriks otorisasi: `docs/testing/authorization-matrix.md`
- Kontrak provider: `docs/integrations/provider-contract-admission.md`
- Requirement index: `docs/requirements-index.md`
