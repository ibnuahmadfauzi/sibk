# Checkpoint 3 — Pengujian Otorisasi Umum Implementation Plan

> **For agentic workers:** Gunakan superpowers:executing-plans untuk tugas berurutan dan superpowers:requesting-code-review untuk review independen. Checkbox mencatat verifikasi tiap tugas.

**Goal:** Memensiunkan alat penelitian RBAC sambil mempertahankan pengujian empat role, batas data, privasi, dan kontrak legacy yang masih dibutuhkan.

**Architecture:** Test memakai fixture lokal, RoleSeeder/ReferenceSeeder, dan database test disposable. Policy, Gate, scope, controller, dan service produk tetap dipertahankan. Audit plan penyederhanaan mengoreksi petunjuk eksekusi tanpa menjalankan fitur checkpoint berikutnya.

**Tech Stack:** PHP 8.3 atau lebih baru, Laravel, PHPUnit, Blade, Vite existing.

**Spec:** `docs/superpowers/specs/2026-09-14-baseline-cobasidebar-arsip-dan-penyederhanaan-design.md`, bagian 3.4 dan Checkpoint 3; SRS v1.1 AUTH-01–AUTH-07.

## Batas bersama

- Branch `checkpoint-3-rbac`, baseline `cobasidebar`, worktree `.worktrees/checkpoint-1-arsip`.
- Pertahankan perubahan pengguna, `main`, policy/Gate, dan test modul umum.
- Tidak mengubah perilaku bisnis, UI, dependency, database aplikasi, atau adapter production.
- PHP baru memakai `declare(strict_types=1);`; dokumentasi Bahasa Indonesia dan istilah `murid`.
- Migration forward-only; tidak reset database lokal/shared/production.
- Checkpoint 4 mengerjakan Task 1–7; Checkpoint 5 mengerjakan Task 8–15. Audit bukan bukti implementasi.

### Task 1: Audit cakupan dan lengkapi test reguler

**Files:** `tests/Feature/AssignmentManagementTest.php`, `ConsultationManagementTest.php`, `CaseManagementTest.php`, `ReportManagementTest.php`, `AuthorizationMatrixTest.php`; buat `docs/testing/authorization-matrix.md`.

- [x] Verifikasi PR #9 `MERGED`, branch sumber tidak ada di remote, dan tidak ada commit lokal/PR terbuka yang belum digabung.
- [x] Jalankan `composer test` sebelum perubahan; expected baseline 411 test/3.148 assertion.
- [x] Petakan assertion produk dari `AuthorizationResearchScenarioTest` ke test reguler; assertion katalog, reset, CSV penelitian tidak dipindahkan.
- [x] Tambah test direct URL sebelum/saat/setelah periode penugasan, memakai tanggal tetap dan `travelTo()`.
- [x] Tambah test Koordinator+Guru membaca histori privat dalam scope, gagal edit histori milik guru lama, dan menerima redaksi di luar scope.
- [x] Tambah test follow-up yang bukan anak kasus menghasilkan 404; Waka gagal membuat koordinasi dan jumlah record tetap.
- [x] Tambah test rekap layanan HTML/CSV memuat registrasi layanan yang diizinkan dan menolak layanan luar scope serta marker privat.
- [x] Lengkapi matriks route empat role untuk konsultasi, penugasan manage, dan portal Waka. Jalankan focused test kelima file sebelum penghapusan.
- [x] Buat matriks ringkas dengan role, batas data, dan nama test; tanpa dataset, screenshot, atau workbook penelitian.

### Task 2: Pensiunkan perangkat penelitian

**Files:** hapus `app/Console/Commands/ResetAuthorizationScenario.php`, `VerifyAuthorizationScenario.php`, `app/Support/AuthorizationScenarioCatalog.php`, `AuthorizationScenarioVerifier.php`, `database/seeders/AuthorizationScenarioSeeder.php`, `tests/Feature/AuthorizationResearchScenarioTest.php`, `docs/testing/rbac-manual-testing.md`, `rbac-scenario-matrix.csv`; ubah `docs/api-contract.md`.

- [x] Setelah focused test lulus, hapus delapan file tersebut dari HEAD tanpa arsip; histori Git tetap tersedia.
- [x] Hapus bagian kontrak alat penelitian; pertahankan kontrak otorisasi produk dan tautkan matriks umum.
- [x] Jalankan `rg -n 'AuthorizationScenario|AuthorizationResearch|rbac:scenario|rbac-manual-testing|rbac-scenario-matrix' app database tests routes docs README.md`; expected tidak ada consumer aktif, hanya catatan pensiun pada spec/plan historis.
- [x] Periksa `php artisan list --raw` agar command penelitian tidak terdaftar; test terkait lulus melalui regresi penuh setelah penghapusan.

### Task 3: Audit route legacy dan plan 15 task

**Files:** `docs/superpowers/plans/2026-09-14-penyederhanaan-laporan-guru-koordinator.md`.

- [x] Bandingkan 15 task dengan dua spec yang ditunjuk plan, source route/controller, dan test existing.
- [x] Catat keputusan tiap keluarga legacy: laporan `type`, profil NISN, bookmark Waka, dan `_preview` termasuk tujuan fitur retired.
- [x] Revisi kontradiksi migration forward-only, petunjuk test/command yang tidak lengkap, dan dependency antartask yang ditemukan.
- [x] Catat status faktual; seluruh Task 1–15 tetap belum selesai sampai implementasi dan gate masing-masing terpenuhi.
- [x] Jalankan `git diff --check` dan periksa tautan lokal dokumen yang diubah.

### Task 4: Regresi, review, dan handoff

**Files:** `docs/current-work.md`, `docs/development-log.md`, plan checkpoint ini.

- [x] Jalankan `composer test`, `php vendor/bin/pint --test`, `npm.cmd run check:frontend`, `npm.cmd run build`, `composer validate --strict`, dan `git diff --check`; semuanya exit 0.
- [x] Review independen terhadap spec, cakupan assertion, penghapusan consumer, audit plan, dan diff keseluruhan; tidak ada blocker material. Handoff diperbaiki agar menunjuk commit `9119b01`.
- [ ] Perbarui checkpoint selesai dan langkah Checkpoint 4 pada handoff; catat jumlah test/assertion dan batas runtime PHP yang benar-benar diuji.
- [ ] Commit dalam Bahasa Indonesia, push branch fitur, dan buat PR ke `cobasidebar`. Merge hanya bila sudah diotorisasi; setelah merge verifikasi `MERGED` sebelum menghapus branch sumber remote.

## Keputusan eksekusi

- Test baru menguatkan perilaku yang sudah ada; bukan fitur baru. Expected value berupa fixture literal, tanpa katalog penelitian.
- Gate baseline sandbox sempat gagal menulis cache PHPUnit/CSV penelitian. Ulangi dengan izin eksekusi yang sesuai sebelum menilai regresi kode.
- Persetujuan melanjutkan checkpoint 3 mencakup penghapusan perangkat penelitian yang sudah disebut spec; tidak memerlukan persetujuan ulang.
- Task 1–2 selesai pada commit `9119b01`; focused 61 test/615 assertion dan regresi 402 test/3.139 assertion lulus.
- Review independen RBAC tidak menemukan blocker; assertion produk penting tetap ada pada test reguler.
- Audit plan mencatat seluruh 15 task belum selesai, memperjelas route legacy dan gate CLI, serta menghapus instruksi rollback yang bertentangan dengan aturan forward-only.
- Verifikasi akhir setelah commit RBAC: 402 test/3.139 assertion lulus pada PHP 8.4.12. Tautan Markdown lokal pada enam dokumen valid; runtime PHP 8.3 belum diuji langsung.
- Probe koneksi SQLite disposable pada petunjuk Task 13 lulus di PowerShell 5.1 tanpa menjalankan migration.
- Review akhir audit plan dan handoff tidak menemukan blocker material; seluruh Task 1–15 tetap belum ditandai selesai.
