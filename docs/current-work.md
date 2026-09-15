# Pekerjaan Aktif Ruang BK

## Status

- Checkpoint selesai: 4 — Penyederhanaan laporan, Task 1–7.
- Checkpoint selesai: 5A — Penyederhanaan operasional, Task 8–10.
- Checkpoint aktif: 5B — Task 11–15.
- Task 11–14 selesai; berikutnya Task 15.
- Branch: `checkpoint-5b-operasional`.
- Worktree: `.worktrees/checkpoint-5b-operasional`.
- Baseline Checkpoint 5B: `e4bfe52` dari `cobasidebar` setelah PR #14.

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
- UAT manusia oleh `ui` melalui Chrome pada tiga viewport dinyatakan PASS;
  versi browser tidak dilaporkan dan tidak ada temuan gagal.
- PR #12 terverifikasi `MERGED`; branch `checkpoint-4-laporan` sudah dihapus
  lokal/remote dan `cobasidebar` lokal sama dengan origin.
- Keputusan Checkpoint 5A/5B: angka 30 tabel hanya sasaran, bukan gate. Tabel
  kandidat retired tidak dihapus secara fisik pada kedua checkpoint ini.
- Waka Kesiswaan berwenang membaca daftar/detail operasional proses keluar
  murid, termasuk identitas, kelas, status, tanggal, ringkasan, dan petugas;
  tidak mempunyai aksi mutasi.
- `migrate:fresh --seed` boleh pada database lokal/pengembangan yang sudah
  dipastikan bukan shared/production.
- Task 8 menyelaraskan PRD/SRS, indeks requirement, kontrak API, dan spec;
  focused contract test lulus 1 test/8 assertion.
- Task 9 mengganti pembatalan layanan dengan arsip, mengizinkan owner mengedit
  data selesai dengan alasan yang diaudit, dan menyisakan empat status layanan.
  Focused gate lulus 43 test/333 assertion, Pint, dan diff-check.
- Task 10 menghentikan route/UI/runtime koreksi, notifikasi, dan riwayat audit;
  audit append-only tetap digunakan. Dashboard memakai panel konteks aman per
  role. Focused gate lulus 25 test/270 assertion, checker frontend, Pint, dan
  diff-check; regresi area terkait lulus 78 test/688 assertion.
- Gate penuh Checkpoint 5A lulus: 416 test/3.261 assertion, Pint, checker
  frontend, build, Composer strict, dan diff-check.
- Task 11 menambahkan satu proses keluar per murid, keputusan final Koordinator,
  scope layanan berdasarkan tanggal efektif, serta daftar read-only Waka.
  Focused gate lulus 102 test/735 assertion, Pint, checker frontend, dan diff-check.
- Task 12 menerapkan password sementara satu kali, wajib ganti setelah login,
  reset Admin IT, pemutusan sesi target, dan command pemulihan Admin IT.
  Focused gate lulus 29 test/296 assertion, Pint, checker frontend, audit route,
  scan password, dan diff-check.
- Task 13 membuktikan invariant skema fresh/incremental pada SQLite dan MySQL.
  Enam tabel retired tetap ada; consumer runtime kosong, kecuali konfigurasi
  driver Laravel yang sengaja dipertahankan. Focused gate lulus 12 test/77
  assertion, Pint, dan diff-check.
- Task 14 menyelaraskan hitungan murid dashboard dengan tanggal akhir periode,
  menolak preselection murid di luar scope, dan membuktikan arsip tidak muncul
  pada dashboard, monitoring, laporan, atau daftar aktif. Focused gate lulus
  71 test/565 assertion, Pint, checker frontend, scan query, dan diff-check.

## Arsip

- Repository private: `https://github.com/Aflahul/sibk-docs-archive`
- Commit snapshot: `dcdd2e9`; sumber `b4310e1`; sepuluh hash sesuai.
- Clone: `D:\PPG 2026\SEMESTER 2\sibk-docs-archive`
- Undangan `ibnuahmadfauzi` masih menunggu penerimaan.

## Langkah berikutnya

1. Kerjakan Task 15: verifikasi penuh CLI dan handoff UAT gabungan.
2. Review perubahan Checkpoint 5B lalu integrasikan ke `cobasidebar`.
3. Jangan membuat migration drop tabel kandidat retired pada Checkpoint 5B.
4. Adapter production tetap menunggu kontrak resmi dan admission gate.

## Acuan

- Plan aktif: `docs/superpowers/plans/2026-09-14-penyederhanaan-laporan-guru-koordinator.md`
- UAT aktif: `docs/testing/2026-09-14-uat-laporan-guru-koordinator.md`
- Matriks umum: `docs/testing/authorization-matrix.md`
- Spec aktif: `docs/superpowers/specs/2026-09-14-penyederhanaan-operasional-akun-dan-skema-data-design.md`
