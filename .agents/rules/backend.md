# Ruang BK — Backend Guardrail

Backend belum aktif dikembangkan.

## Status

Jangan mengimplementasikan backend kecuali ada instruksi eksplisit untuk memulai fase backend.

Jangan membuat:
- endpoint;
- migration;
- model;
- seeder;
- job/queue;
- webhook;
- koneksi integrasi.

## Source of Truth

1. `docs/requirements/SRS_Aplikasi_BK_v1.0.md` untuk perilaku sistem dan aturan bisnis.
2. `docs/requirements/PRD_Aplikasi_BK_v1.0.md` untuk scope dan prioritas.
3. `docs/requirements-index.md` sebagai indeks ringan sebelum membuka dokumen penuh.

## Arsitektur Masa Depan

Ketika backend resmi dimulai, pisahkan:
- authentication;
- authorization/capabilities;
- application/service logic;
- data access/repository;
- external integration;
- audit logging.

## Integrasi Eksternal

- Dapodik adalah sumber data master murid/kelas yang disepakati.
- e-Tatib adalah sumber data pelanggaran/poin yang dibaca Ruang BK.
- Ruang BK tidak menggantikan fungsi resmi e-Tatib.
- Mekanisme integrasi aktual tidak boleh ditebak sebelum diputuskan.

## Privacy dan Audit

- Gunakan least privilege.
- Catatan konsultasi sensitif tidak boleh diperluas aksesnya tanpa requirement.
- Audit/riwayat perubahan diperlakukan sebagai data sistem.
- Retensi, koreksi, dan histori penugasan mengikuti requirement.

## API Contract

Saat perencanaan backend dimulai:
- mulai dari kebutuhan data dan aksi;
- dokumentasikan input, output, error, authorization, dan source data;
- tandai keputusan belum final sebagai `TBD`;
- jangan mengunci endpoint atau skema database tanpa persetujuan tim.

## Format Requirement

- Gunakan mirror `.md` untuk pencarian/selective reading oleh agent.
- File `.docx` tetap baseline manusia/final dan disimpan bersama mirror Markdown.
- Jangan mengubah `.md` secara independen bila perubahan belum disahkan pada baseline final.
