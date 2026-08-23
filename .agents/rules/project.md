# Ruang BK — Project Rule

## Source of Truth

1. `docs/requirements/SRS_Aplikasi_BK_v1.0.md` untuk perilaku sistem dan aturan fungsional.
2. `docs/requirements/PRD_Aplikasi_BK_v1.0.md` untuk ruang lingkup, prioritas, dan tujuan produk.
3. Gunakan `docs/requirements-index.md` sebelum membuka dokumen penuh.

Visual frontend:
1. Penpot `22 — UI High-Fidelity Final`.
2. Penpot `22.5 — Style Guide`.

Jangan gunakan `21 — Wireframe Low-Fidelity Final` sebagai referensi visual implementasi frontend.

## Status Fase

- Frontend telah selesai diimplementasikan (semua page PG Penpot Hi-Fi tersedia).
- Backend **aktif dikembangkan** (skema database, model, policy, services, form requests, controllers, seeders, audit trail).
- Integrasi Dapodik dan e-Tatib disiapkan melalui struktur model/cache terkelola dan log rekonsiliasi.

## Batasan

Jangan:
- menciptakan fitur baru di luar requirement SRS/PRD;
- membocorkan atau memunculkan informasi sensitif (catatan konseling lengkap) pada laporan umum atau detail Waka;
- mengubah token visual atau merusak tampilan halaman yang sudah disetujui;
- menduplikasi logika bisnis di luar Service/Action layer;
- mengubah file yang tidak berkaitan dengan modul yang sedang dikerjakan;
- menambahkan catatan teknis internal (nama PG, nama file, istilah PRD/SRS) ke teks UI yang tampil ke pengguna.

Jika requirement dan desain bertentangan, laporkan konflik sebelum mengubah perilaku produk.


## Context Efficiency

- Jangan membuka PRD/SRS bila task hanya visual, styling, lint, build, atau refactor presentasi yang tidak mengubah behavior.
- Bila requirement diperlukan, cari hanya area/ID yang relevan.
- Jangan membuat salinan ringkasan requirement baru kecuali ada kebutuhan nyata.

## Format Requirement

- Gunakan mirror `.md` untuk pencarian/selective reading oleh agent.
- File `.docx` tetap baseline manusia/final dan disimpan bersama mirror Markdown.
- Jangan mengubah `.md` secara independen bila perubahan belum disahkan pada baseline final.
