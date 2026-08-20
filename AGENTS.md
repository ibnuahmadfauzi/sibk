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

- Frontend: selesai diimplementasikan (seluruh paket halaman PG Penpot Hi-Fi telah tersedia).
- Backend: **aktif untuk dikembangkan**. Implementasi mencakup skema database, otorisasi multi-role (Guru BK, Koordinator BK, Waka Kesiswaan, Admin IT), Service/Action layer, audit trail append-only, dan integrasi dengan controller/views.
- Integrasi Dapodik/e-Tatib: disiapkan melalui cache read-only, pemetaan NISN, dan log sinkronisasi terkelola.
- API contract: aktif disusun dan diselaraskan dengan kebutuhan web controller & service layer.

## Aturan Umum

- Pertahankan arsitektur dan konvensi repository yang sudah ada.
- Jangan menambah fitur yang tidak tercantum pada requirement (SRS/PRD).
- Jangan mendesain ulang UI yang sudah disetujui.
- Gunakan komponen yang sudah ada sebelum membuat komponen baru.
- Pisahkan UI, akses data, business logic, dan integrasi eksternal.
- Gunakan Bahasa Indonesia untuk teks yang tampil kepada pengguna.
- Gunakan istilah `murid`, bukan `siswa`, kecuali sumber resmi yang dirujuk memang menggunakan istilah lain.
- Terapkan hak akses sesuai authorization/capability (AUTH-01 s.d. AUTH-07) pada server/policy.
- Ikuti standar PHP 8.3 (`declare(strict_types=1);`), Thin Controller, Form Request, Service Layer, dan Query Scopes.

## Context Efficiency

- Jangan membuka PRD/SRS lengkap pada setiap task.
- Jika requirement diperlukan, prioritaskan mirror `.md` dan baca hanya section/ID relevan.
- Gunakan `docs/requirements-index.md` terlebih dahulu.
- Buka PRD/SRS hanya ketika task memerlukan keputusan scope, behavior, akses, field, acceptance criteria, integrasi, privacy, atau aturan bisnis.
- Untuk pekerjaan visual murni, cukup gunakan Penpot Hi-Fi, Style Guide, dan existing code.
- Jangan menyalin isi PRD/SRS ke rules/workflows karena menambah context berulang.
