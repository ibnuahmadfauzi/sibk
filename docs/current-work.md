# Pekerjaan Aktif Ruang BK

## Status

- Checkpoint selesai: 4 — Penyederhanaan laporan, Task 1–7.
- Checkpoint berikutnya: 5A — Penyederhanaan operasional, Task 8–10.
- Checkpoint 5B memuat Task 11–15 dan baru dimulai setelah Checkpoint 5A
  terintegrasi ke `cobasidebar`.
- Branch perencanaan aktif: `docs/checkpoint-5-split`.
- Worktree: `.worktrees/checkpoint-5-split`.
- Baseline branch: `4f6a919` dari `origin/cobasidebar`.

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

## Arsip

- Repository private: `https://github.com/Aflahul/sibk-docs-archive`
- Commit snapshot: `dcdd2e9`; sumber `b4310e1`; sepuluh hash sesuai.
- Clone: `D:\PPG 2026\SEMESTER 2\sibk-docs-archive`
- Undangan `ibnuahmadfauzi` masih menunggu penerimaan.

## Langkah berikutnya

1. Setelah perubahan perencanaan ini terintegrasi, buat branch Checkpoint 5A
   dari `cobasidebar`, lalu mulai
   Task 8: selaraskan requirement, kontrak API, dan batas skema memakai keputusan
   terbaru di plan/spec aktif.
2. Selesaikan dan integrasikan Task 8–10 sebelum membuat branch Checkpoint 5B.
3. Jangan membuat migration drop tabel kandidat retired pada Checkpoint 5A/5B.
4. Task 11 wajib menyediakan daftar/detail proses keluar read-only untuk Waka.
5. Adapter production tetap menunggu kontrak resmi dan admission gate.

## Acuan

- Plan aktif: `docs/superpowers/plans/2026-09-14-penyederhanaan-laporan-guru-koordinator.md`
- UAT aktif: `docs/testing/2026-09-14-uat-laporan-guru-koordinator.md`
- Matriks umum: `docs/testing/authorization-matrix.md`
- Spec aktif: `docs/superpowers/specs/2026-09-14-penyederhanaan-operasional-akun-dan-skema-data-design.md`
