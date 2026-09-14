# Pekerjaan Aktif Ruang BK

## Status

- Checkpoint selesai: 3 — Sederhanakan pengujian RBAC dan audit plan.
- Checkpoint aktif: 4 — Penyederhanaan laporan, Task 1–6 selesai; Task 7 menunggu UAT manual.
- Branch kerja: `checkpoint-4-laporan`, dibuat dari `cobasidebar` setelah PR #11 terintegrasi.
- Worktree: root repository.
- Commit aplikasi terakhir: `af9e69a` (UI dan ekspor tiga tab).

## Hasil dan gate terakhir

- Cleanup menghapus fixture config tanpa consumer, empat aset tanpa referensi,
  satu test trivial, dan dua file publik kosong/percobaan. `laravel/pao` serta
  fallback `Unavailable*Connector` dipertahankan.
- Gate cleanup lulus: 401 test/3.138 assertion, Pint, checker frontend, build,
  Composer strict, dan diff-check.
- PR #9 Checkpoint 2 terverifikasi `MERGED`; branch sumber remote sudah tidak ada.
- Matriks umum menunjuk test empat role, scope data, histori, privasi, dan ekspor.
- Lima test reguler ditambahkan sebelum delapan file perangkat penelitian dihapus.
- Policy, Gate, service, controller, UI, dependency, dan migration produk tidak diubah.
- Command penelitian tidak terdaftar; tidak ada consumer penelitian pada app/database/tests/routes.
- Audit 15 task selesai; route legacy, migration forward-only, dan gate disposable diperjelas.
- Review independen RBAC serta audit plan selesai tanpa blocker material.
- Gate lulus: 402 test/3.139 assertion, Pint, checker frontend, build,
  Composer strict, dan diff-check. Focused sebelum penghapusan: 61 test/615 assertion.
- Cache PHPUnit dan build Vite membutuhkan eksekusi di luar sandbox karena izin
  tulis worktree; pengulangan lulus.
- Verifikasi memakai PHP 8.4.12; runtime PHP 8.3 belum diuji langsung.
- Halaman laporan Guru BK/Koordinator sekarang memakai tiga tab: Pelanggaran &
  Poin, Layanan BK, dan Prestasi; tujuh endpoint legacy tetap tersedia.
- Gate Checkpoint 4 lulus pada SQLite: 418 test/3.293 assertion, Pint, checker
  frontend, build, Composer strict, cache, dan diff-check.
- Focused rekap juga lulus pada MySQL disposable `sibk_report_gate`: 16 test/148
  assertion; instance dan folder sementara sudah dihapus.
- Scan field sensitif dan dependency tabel terlarang tidak menemukan kecocokan.
- UAT manusia tiga viewport berstatus `PENDING MANUAL`; Task 8 belum dimulai.

## Arsip

- Repository private: `https://github.com/Aflahul/sibk-docs-archive`
- Commit snapshot: `dcdd2e9`; sumber `b4310e1`; sepuluh hash sesuai.
- Clone: `D:\PPG 2026\SEMESTER 2\sibk-docs-archive`
- Undangan `ibnuahmadfauzi` masih menunggu penerimaan.

## Langkah berikutnya

1. Jalankan checklist UAT laporan melalui browser biasa pada tiga viewport dan
   kirim hasil PASS/FAIL beserta identitas tester serta browser/versi.
2. Jika UAT lulus, catat hasil akhir Task 7, perbarui checkbox Task 1–7, lalu
   siapkan PR Checkpoint 4 ke `cobasidebar`.
3. Jangan mulai Task 8 sebelum seluruh UAT Checkpoint 4 PASS.
4. Adapter production tetap menunggu kontrak resmi dan admission gate.

## Acuan

- Plan checkpoint: `docs/superpowers/plans/2026-09-15-checkpoint-3-rbac.md`
- Plan berikutnya: `docs/superpowers/plans/2026-09-14-penyederhanaan-laporan-guru-koordinator.md`
- UAT aktif: `docs/testing/2026-09-14-uat-laporan-guru-koordinator.md`
- Matriks umum: `docs/testing/authorization-matrix.md`
- Spec: `docs/superpowers/specs/2026-09-14-baseline-cobasidebar-arsip-dan-penyederhanaan-design.md`
