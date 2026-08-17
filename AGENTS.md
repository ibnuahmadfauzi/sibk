# SIBK / Ruang BK

Ruang BK adalah aplikasi layanan Bimbingan dan Konseling untuk SMK Negeri 1 Surabaya.

## Source of Truth

Kebutuhan dan perilaku sistem:
- `docs/requirements/PRD_Aplikasi_BK_v1.0.docx` — baseline final manusia.
- `docs/requirements/PRD_Aplikasi_BK_v1.0.md` — mirror AI-readable.
- `docs/requirements/SRS_Aplikasi_BK_v1.0.docx` — baseline final manusia.
- `docs/requirements/SRS_Aplikasi_BK_v1.0.md` — mirror AI-readable.
- `docs/requirements-index.md` — indeks ringan untuk menentukan bagian requirement yang perlu dibuka.

Referensi visual frontend:
- Penpot page `22 — UI High-Fidelity Final`.

Design system frontend:
- Penpot page `22.5 — Style Guide`.

Page `21 — Wireframe Low-Fidelity Final` adalah artefak proses desain dan bukan referensi visual implementasi frontend.

## Status Pengembangan

- Frontend: aktif untuk dikembangkan. Fokus pengembangan saat ini adalah membangun seluruh antarmuka pengguna (UI statis) dengan asumsi "akun normal" (tanpa pembatasan role/hak akses) hingga ada instruksi untuk mengimplementasikan backend/role.
- Backend: belum dikembangkan.
- Integrasi Dapodik/e-Tatib: belum diimplementasikan.
- API contract: belum dikunci.

## Aturan Umum

- Pertahankan arsitektur dan konvensi repository yang sudah ada.
- Jangan menambah fitur yang tidak tercantum pada requirement.
- Jangan mendesain ulang UI yang sudah disetujui.
- Gunakan komponen yang sudah ada sebelum membuat komponen baru.
- Pisahkan UI, akses data, business logic, dan integrasi eksternal.
- Gunakan Bahasa Indonesia untuk teks yang tampil kepada pengguna.
- Gunakan istilah `murid`, bukan `siswa`, kecuali sumber resmi yang dirujuk memang menggunakan istilah lain.
- Jangan hardcode hak akses berdasarkan tampilan desain. Visibilitas menu, data, dan aksi mengikuti authorization/capability aplikasi.
- Jangan membuat endpoint, migration, model database, atau integrasi backend sebelum ada perintah eksplisit untuk memulai backend.

## Context Efficiency

- Jangan membuka PRD/SRS lengkap pada setiap task.
- Jika requirement diperlukan, prioritaskan mirror `.md` dan baca hanya section/ID relevan.
- Gunakan `docs/requirements-index.md` terlebih dahulu.
- Buka PRD/SRS hanya ketika task memerlukan keputusan scope, behavior, akses, field, acceptance criteria, integrasi, privacy, atau aturan bisnis.
- Untuk pekerjaan visual murni, cukup gunakan Penpot Hi-Fi, Style Guide, dan existing code.
- Jangan menyalin isi PRD/SRS ke rules/workflows karena menambah context berulang.
