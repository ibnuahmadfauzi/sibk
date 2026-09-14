# Checkpoint 2 — Bersihkan Baseline cobasidebar Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans untuk eksekusi langsung sesuai pilihan pengguna. Kerjakan task berurutan, verifikasi, perbarui handoff, lalu commit sebelum lanjut.

**Goal:** Merapikan dokumentasi aktif dan mengeluarkan dokumen historis serta bukti visual penelitian dari repository aplikasi.

**Architecture:** Perubahan dokumentasi dan penghapusan file yang telah diklasifikasikan; tidak mengubah kode aplikasi, dependency, database, atau hak akses. Folder worktree Checkpoint 1 digunakan kembali sesuai permintaan pengguna, pada branch `checkpoint-2-baseline` dari `cobasidebar` commit `386cd3c`.

**Tech Stack:** PHP 8.3 atau lebih baru, Laravel 13, PHPUnit, Pint, Node.js/Vite, Git/GitHub CLI.

**Spec:** `docs/superpowers/specs/2026-09-14-baseline-cobasidebar-arsip-dan-penyederhanaan-design.md`

## Global Constraints

- Baseline pengembangan `cobasidebar`; `main` tetap stabil untuk produksi.
- Minimum resmi PHP 8.3 atau lebih baru; tidak mengubah versi dependency terkunci.
- Adapter production Dapodik/e-Tatib tetap `unavailable` sampai kontrak resmi lolos admission gate.
- Dokumen historis hanya dihapus setelah remote dan hash arsip terverifikasi.
- Screenshot dan workbook penelitian tidak diarsipkan; pemulihan tersedia dalam histori Git.
- Manual/CSV penelitian dan kode pendukungnya tetap sampai Checkpoint 3 karena masih menjadi input test.
- Tidak ada perubahan fitur, route, migration, atau otorisasi pada checkpoint ini.
- Pesan commit menggunakan Bahasa Indonesia sederhana.
- Tidak mengubah default branch/protection GitHub atau force push.

## Task 1 — Verifikasi sumber dan tujuan arsip

**Files:** plan ini dan `docs/current-work.md`.
**Interfaces:** memakai arsip `Aflahul/sibk-docs-archive` commit `dcdd2e9`; menghasilkan izin aman untuk menghapus salinan sumber pada Task 3.

- [x] Periksa worktree bersih dan branch berasal dari `386cd3c`: `git status --short --branch`, `git log -1 --oneline`.
- [x] Jalankan baseline `composer test`. Expected: 411 test dan 3.148 assertion lulus.
- [x] Verifikasi private/default branch/SHA menggunakan `gh repo view Aflahul/sibk-docs-archive --json isPrivate,defaultBranchRef` dan `gh api repos/Aflahul/sibk-docs-archive/git/ref/heads/main`.
- [x] Periksa undangan menggunakan `gh api repos/Aflahul/sibk-docs-archive/invitations`; catat bila masih menunggu, jangan kirim ulang.
- [x] Bandingkan SHA-256 sepuluh pasangan di tabel Task 3 menggunakan `Get-FileHash`. Pastikan clone bersih dan HEAD `dcdd2e9`; normalisasi line ending Git tidak boleh mengubah isi historis yang tersimpan.
- [x] Catat Task 1 selesai/Task 2 berikutnya pada handoff. Jalankan `git diff --check`, lalu commit `docs: mulai pembersihan baseline tahap kedua`.

## Task 2 — Perbarui panduan aktif

**Files:** `README.md`, `AGENTS.md`, `docs/requirements-index.md`, `docs/development-log.md`, `.agents/rules/{backend,project,task-tracking}.md`, `.agents/workflows/implement-backend.md`, dua spec penyederhanaan dan plan penyederhanaan aktif.
**Interfaces:** mengganti pointer lokal arsip dengan URL repository privat, mengarahkan pengembang baru ke v1.1 dan handoff.

- [x] Ringkas README menjadi setup Laravel/Vite, alur feature branch → PR ke `cobasidebar`, gate dasar, dan tautan dokumentasi/arsip. Minimum PHP 8.3; gunakan `composer install` dan `npm ci`, bukan update dependency. Larang reset database shared.
- [x] Ringkas AGENTS: v1.1 aktif, arsip eksternal, `current-work.md` dibaca dulu; plan integrasi Task 0–12 sudah selesai dan tidak wajib dibaca ulang. Pertahankan aturan visual, server authorization, strict typing, serta admission provider.
- [x] Ganti pointer v1.0 ke v1.1 di backend/project/workflow. Ubah klaim DOCX aktif menjadi Markdown v1.1 aktif dan DOCX v1.0 di arsip.
- [x] Selaraskan task tracking: handoff untuk status berjalan, development log untuk ringkasan selesai; tidak menggandakan progres panjang.
- [x] Ringkas development log menjadi integrasi selesai, Portal Waka selesai, Checkpoint 1 selesai, serta checkpoint aktif; tautkan log lama di arsip.
- [x] Perbarui indeks arsip dan kalimat perlindungan v1.0 pada kedua spec serta plan penyederhanaan: arsip tetap tidak diubah di repository terpisah.
- [x] Periksa tidak ada pointer source-of-truth v1.0 atau petunjuk branch `development` di aturan/README. Periksa `composer.json`/lock menerima PHP 8.3; `composer validate --strict`.
- [x] Catat Task 2 selesai/Task 3 berikutnya; `git diff --check`, lalu commit `docs: rapikan panduan pengembangan cobasidebar`.

## Task 3 — Keluarkan dokumen historis yang terarsip

**Files:** sembilan file sumber pada tabel di bawah. `docs/development-log.md` tetap aktif tetapi telah diringkas.
**Interfaces:** sumber sebelum penghapusan harus identik dengan arsip yang telah di-push; menghasilkan docs aktif tanpa duplikasi histori.

| Sumber di sibk | Tujuan di sibk-docs-archive |
|---|---|
| `docs/requirements/PRD_Aplikasi_BK_v1.0.docx` | `requirements/v1.0/PRD_Aplikasi_BK_v1.0.docx` |
| `docs/requirements/PRD_Aplikasi_BK_v1.0.md` | `requirements/v1.0/PRD_Aplikasi_BK_v1.0.md` |
| `docs/requirements/SRS_Aplikasi_BK_v1.0.docx` | `requirements/v1.0/SRS_Aplikasi_BK_v1.0.docx` |
| `docs/requirements/SRS_Aplikasi_BK_v1.0.md` | `requirements/v1.0/SRS_Aplikasi_BK_v1.0.md` |
| `docs/superpowers/plans/2026-08-23-konfigurasi-koneksi-integrasi.md` | `plans/completed/2026-08-23-konfigurasi-koneksi-integrasi.md` |
| `docs/superpowers/plans/2026-09-12-penyempurnaan-alur-operasional-bk.md` | `plans/superseded/2026-09-12-penyempurnaan-alur-operasional-bk.md` |
| `docs/superpowers/plans/2026-09-13-portal-waka-berbasis-tujuan.md` | `plans/completed/2026-09-13-portal-waka-berbasis-tujuan.md` |
| `docs/superpowers/specs/2026-09-13-portal-waka-wireframe-design.md` | `specs/completed/2026-09-13-portal-waka-wireframe-design.md` |
| `docs/testing/2026-09-13-uat-portal-waka.md` | `testing/completed/2026-09-13-uat-portal-waka.md` |
| `docs/development-log.md` (versi `b4310e1`) | `testing/completed/development-log-through-2026-09-14.md` |

- [x] Periksa file yang dipilih benar-benar tracked: `git ls-files docs/requirements docs/superpowers docs/testing`.
- [x] Hapus hanya sembilan sumber terarsip, bukan log aktif, dengan `git rm --` dan path eksplisit dari tabel. Tidak ada recursive delete.
- [x] Periksa `git diff --name-status` dan referensi lokal file terhapus dengan `rg --hidden -n --glob '*.md'` (abaikan inventaris historis spec/plan checkpoint yang memang mencatat asal arsip).
- [x] Catat Task 3 selesai/Task 4 berikutnya; `git diff --check`, lalu commit `docs: keluarkan dokumen lama yang sudah diarsipkan`.

## Task 4 — Hapus bukti visual penelitian

**Files:** 41 PNG dan satu XLSX tracked dalam `docs/testing/bukti-rbac/`; `docs/research/rbac-results-analysis.md`.
**Interfaces:** screenshot/workbook/analisis artikel bukan input test; manual dan matriks CSV tetap tersedia sampai Checkpoint 3.

- [x] Inventaris dengan `git ls-files docs/testing/bukti-rbac`; expected: 41 PNG dan satu XLSX. Pastikan tidak ada input test yang membaca file tersebut dengan `rg -n 'bukti-rbac|RBAC-Test-Results|rbac-results-analysis' app tests database`.
- [x] Validasi path absolut bukti berada tepat di `docs/testing/bukti-rbac` dalam worktree dan berisi 42 file tracked. Hapus dengan `git rm -r -- docs/testing/bukti-rbac` dan `git rm -- docs/research/rbac-results-analysis.md`. Tidak memindahkan file ke arsip, tidak menulis ulang histori.
- [x] Tambahkan catatan singkat di manual penelitian bahwa bukti visual dipensiunkan dan manual/CSV sementara masih dipakai test sampai Checkpoint 3. Jangan hapus 41 bagian skenario yang diverifikasi test.
- [x] Jalankan `php artisan test --filter AuthorizationResearchScenarioTest`; expected: seluruh 14 test lulus.
- [x] Catat Task 4 selesai/Task 5 berikutnya; `git diff --check`, lalu commit `docs: hapus bukti visual penelitian yang tidak diperlukan`.

## Task 5 — Gate akhir dan handoff Checkpoint 3

**Files:** plan ini, `docs/current-work.md`, `docs/development-log.md`.
**Interfaces:** menghasilkan baseline dokumentasi yang siap ditinjau melalui PR ke `cobasidebar`.

- [ ] Jalankan `composer test`, `php vendor/bin/pint --test`, `npm.cmd run check:frontend`, `npm.cmd run build`, `composer validate --strict`, dan `git diff --check`. Semua wajib exit 0; catat PHP runtime yang dipakai (8.4.12), jangan mengklaim test telah berjalan di PHP 8.3.
- [ ] Periksa diff hanya dokumentasi/aturan dan penghapusan artefak; tidak ada perubahan kode fitur/dependency.
- [ ] Pastikan sumber historis sudah hilang, v1.1/manual/CSV/test auth tetap tersedia. Tidak ada link Markdown lokal yang rusak dalam dokumen aktif yang diubah; inventaris path dalam backtick bukan tautan navigasi.
- [ ] Handoff maksimum sekitar 100 baris: checkpoint selesai, branch/path kerja, arsip/SHA/undangan, gate lulus, Checkpoint 3 berikutnya (audit test reguler sebelum menghapus perangkat penelitian).
- [ ] Catat ringkasan selesai di development log, centang plan, lalu commit `docs: selesaikan pembersihan baseline tahap kedua`.
- [ ] Push `git push -u origin checkpoint-2-baseline`. Gunakan skill finishing-a-development-branch untuk pilihan integrasi; jangan otomatis merge/main.
