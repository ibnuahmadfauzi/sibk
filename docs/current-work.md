# Pekerjaan Aktif Ruang BK

## Status

- Pekerjaan aktif: Revisi SIBK 3.2 untuk Layanan Guru BK.
- Branch aktif: `revisi-sibk-3-2`; kandidat kode Checkpoint 7A `829a723`,
  sedangkan handoff berada pada `HEAD`.
- Spec aktif: `docs/superpowers/specs/2026-09-17-revisi-sibk-3-2-guru-bk-design.md`.
- Plan aktif: `docs/superpowers/plans/2026-09-17-revisi-sibk-3-2-guru-bk.md`.
- Checkpoint aktif: 7A — Penyelarasan Fitur, implementasi dan review selesai;
  branch sudah dipush dan menunggu PR ke `cobasidebar`.
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

- Wave 1, tiga lane Wave 2, dan fix Integration Gate 2 sudah terintegrasi pada
  branch `revisi-sibk-3-2` tanpa overlap file.
- Waka memperoleh akses baca seluruh layanan melalui proyeksi allowlist dan
  audit per pembukaan; semua mutasi tetap ditolak dan narasi tidak masuk CSV.
- Laporan, dashboard, profil murid, scope turunan, seeder, serta fixture sudah
  memakai kontrak kasus/konsultasi target tanpa consumer bisnis retired.
- Final review awal menemukan 5 Important dan 7 Minor. Satu fix wave menutup
  seluruh 12 temuan; scoped re-review tidak menemukan blocker baru.
- Full gate final pada `829a723` lulus 454 test/3.529 assertion, Pint, checker
  frontend, build, Composer strict, dan diff-check.
- Branch `revisi-sibk-3-2` sudah dipush. PR belum dapat dibuat karena konektor
  GitHub tidak memiliki izin tulis; tidak ada perubahan ke `main`. Cleanup
  runtime 7B dan cleanup skema 7C belum dimulai.
- Laporan progress tersedia di
  `docs/laporan-progress-revisi-sibk-3-2.md`.
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

1. Buka PR Checkpoint 7A dari `revisi-sibk-3-2` ke `cobasidebar` memakai akun
   yang memiliki izin tulis.
2. Verifikasi status PR `MERGED`, pastikan tidak ada commit/PR tertinggal, lalu
   hapus branch sumber remote.
3. Pause. Checkpoint 7B baru dibuat dari `cobasidebar` terbaru pada sesi lanjut.

## Blocker

- Tidak ada blocker implementasi. Penutupan eksternal tertahan karena konektor
  GitHub mengembalikan `403 Resource not accessible by integration` saat
  membuat PR.
- Risiko residual tetap: Waka membaca narasi terstruktur sesuai keputusan BK,
  dan draft `localStorage` hanya tersedia pada perangkat/browser yang sama.
- Adapter production tetap ditahan karena kontrak resmi provider belum tersedia.

## Acuan

- Plan aktif: `docs/superpowers/plans/2026-09-17-revisi-sibk-3-2-guru-bk.md`
- Spec aktif: `docs/superpowers/specs/2026-09-17-revisi-sibk-3-2-guru-bk-design.md`

- Plan selesai: `docs/superpowers/plans/2026-09-16-checkpoint-6-refactor-baseline-praproduksi.md`
- Spec: `docs/superpowers/specs/2026-09-14-baseline-cobasidebar-arsip-dan-penyederhanaan-design.md`
- Matriks otorisasi: `docs/testing/authorization-matrix.md`
- Kontrak provider: `docs/integrations/provider-contract-admission.md`
- Requirement index: `docs/requirements-index.md`
