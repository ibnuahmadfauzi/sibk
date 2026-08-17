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

- Frontend dapat dikembangkan.
- Backend belum dikembangkan.
- Integrasi Dapodik dan e-Tatib belum diimplementasikan.
- Endpoint, database, dan mekanisme sinkronisasi belum final.

## Batasan

Jangan:
- menciptakan fitur baru tanpa requirement;
- memunculkan informasi sensitif hanya karena tersedia ruang pada UI;
- membuat desain berbeda per role kecuali requirement memerlukannya;
- menduplikasi komponen;
- mengubah file yang tidak berkaitan dengan task;
- mengubah token visual global untuk menyelesaikan masalah satu halaman;
- menambahkan catatan teknis, nama PG, PRD, SRS, backend, atau API ke UI pengguna;
- membuat endpoint, migration, model, job, webhook, atau integrasi eksternal tanpa task backend eksplisit.

Jika requirement dan desain bertentangan, laporkan konflik sebelum mengubah perilaku produk.


## Context Efficiency

- Jangan membuka PRD/SRS bila task hanya visual, styling, lint, build, atau refactor presentasi yang tidak mengubah behavior.
- Bila requirement diperlukan, cari hanya area/ID yang relevan.
- Jangan membuat salinan ringkasan requirement baru kecuali ada kebutuhan nyata.

## Format Requirement

- Gunakan mirror `.md` untuk pencarian/selective reading oleh agent.
- File `.docx` tetap baseline manusia/final dan disimpan bersama mirror Markdown.
- Jangan mengubah `.md` secara independen bila perubahan belum disahkan pada baseline final.
