# Development Log & Task Tracker

File ini digunakan oleh Agen AI dan Pengembang untuk mencatat riwayat pekerjaan, tugas yang sedang dikerjakan (In Progress), dan tugas yang sudah diselesaikan (Done).

## 📝 Format Pencatatan

Setiap memulai pekerjaan baru, tambahkan entri di bawah bagian **"Current / Upcoming Tasks"** dengan format:
- `[ ] [Tanggal] Nama Task`

Setelah pekerjaan selesai, pindahkan/ubah entri tersebut ke **"Completed Tasks"** dengan format:
- `[x] [Tanggal] Nama Task`
  - *Catatan/Ringkasan hasil eksekusi singkat.*

---

## 🚀 Current / Upcoming Tasks

- `[ ] [2026-08-20] Eksekusi Migrasi Database & Seeder Ruang BK (Roles, References, AcademicYears, Classrooms, Students, TemporaryStudents, Cases, FollowUps, Consultations, Corrections, AuditLogs)`
- `[ ] [2026-08-20] Implementasi Eloquent Models, Relations & Query Scopes`
- `[ ] [2026-08-20] Implementasi Policies & Otorisasi Multi-Role (Guru BK, Koordinator, Waka, Admin IT)`
- `[ ] [2026-08-20] Implementasi Service Layer (CaseService, StudentService, AssignmentService, ConsultationService, CorrectionService, ReportService)`
- `[ ] [2026-08-20] Integrasi Web Controllers & Form Requests dengan Tampilan Blade`

---

## ✅ Completed Tasks

- `[x] [2026-08-20] Sprint 3 Backend — Kasus, e-Tatib, Koordinasi Waka, Penugasan Kasus, dan Tindak Lanjut`
  - Menambahkan skema additive kasus, histori penugasan, koordinasi Waka, tindak lanjut, mirror e-Tatib read-only, serta tautan kasus–e-Tatib dengan soft delete dan audit.
  - Mengimplementasikan `CaseService`, `FollowUpService`, perluasan `AssignmentService`, `EtatibSyncService`, policy per objek, validasi identitas/NISN, transfer dan kewenangan tambahan, transisi status, serta penyelesaian kasus.
  - Menghubungkan PG-101–PG-104, PG-106, PG-403, dan status e-Tatib PG-501 ke controller, Form Request, service, policy, serta database tanpa membuka akses layanan bagi Admin IT.
  - Memverifikasi migrasi additive SQLite dan MySQL tanpa `migrate:fresh`, seeder berulang, 41 test/200 assertion, kompilasi Blade, Pint, build Vite, dan pemeriksaan frontend.

- `[x] [2026-08-20] Sprint 2 Backend — Master Dapodik, Rekonsiliasi Identitas, dan Penugasan Guru BK`
  - Menambahkan skema additive untuk penugasan kelas, identitas sementara, histori rekonsiliasi, log sinkronisasi, konflik sumber, serta metadata sinkronisasi keanggotaan kelas.
  - Mengimplementasikan connector Dapodik terabstraksi, sinkronisasi full/partial snapshot, perlindungan data sah saat konflik NISN, rekonsiliasi otomatis, `AssignmentService`, policy, dan scope murid aktif.
  - Mengganti fixture PG-401/PG-402/PG-501 dengan controller, Form Request, service, data database, status integrasi nyata, dan otorisasi per peran tanpa mengubah desain halaman.
  - Memverifikasi migrasi SQLite dan MySQL tanpa `migrate:fresh`, seeder idempotent, 32 test/140 assertion, kompilasi Blade, Pint, build Vite, serta pemeriksaan frontend.

- `[x] [2026-08-20] Sprint 1 Backend — Fondasi, Akun, Autentikasi, Otorisasi Dasar, dan Audit`
  - Menambahkan migrasi additive untuk status akun, multi-role, cache master Dapodik, histori kelas, referensi dinamis, dan audit append-only.
  - Mengimplementasikan autentikasi sesi, middleware akun aktif, `UserPolicy`, `AccountService`, Form Request, serta endpoint pengelolaan akun khusus Admin IT.
  - Menghubungkan form login dan logout Blade ke backend nyata sambil mempertahankan tampilan yang telah disetujui.
  - Memverifikasi migrasi MySQL, seeder idempotent, 23 test/95 assertion, build Vite, pemeriksaan frontend, serta respons HTTP login/dashboard/health.

- `[x] [2026-08-20] Perencanaan Arsitektur & Persiapan Aturan Backend Ruang BK (SIBK)`
  - Memperbarui `AGENTS.md`, `.agents/rules/backend.md`, `.agents/rules/frontend.md`, dan `.agents/rules/project.md` untuk transisi resmi ke fase backend.
  - Membuat alur kerja baru `.agents/workflows/implement-backend.md`.
  - Memutakhirkan `docs/api-contract.md` dengan kontrak endpoint, service layer, dan otorisasi.
  - Menyusun rancangan arsitektur final mencakup multi-role (AUTH-07), identitas sementara mandiri (MD-03, MD-04), FK data referensi (REF-01), audit log append-only (AUD-01), dan soft deletes retensi 3 tahun (NFR-08).

- `[x] [2026-08-20] Audit dan Penyempurnaan Paket 400 (Pengelolaan) & Paket 500 (Data Master)`
  - Memperbarui `PG-402 (Atur Penugasan Kelas)` dengan menambahkan field *Dasar Keputusan / Nomor SK* dan box ketentuan tata kelola *ASN-02, ASN-03, ASN-06*.
  - Menghubungkan `PG-103 (Detail Kasus)` dengan `PG-403 (Penugasan atau Pengalihan Kasus)` melalui tombol "Alihkan Kasus" dengan parameter kasus dinamis.
  - Menyesuaikan 4 kartu ringkasan sinkronisasi pada `PG-501 (Data Master dan Status Sinkronisasi)` sesuai Penpot (*Dapodik, e-Tatib, Data Murid 1.248, Kelas 36*).
- `[x] [2026-08-20] Implementasi Halaman Dedikasi Form Pengajuan Koreksi Data (corrections/create)`
  - Membuat halaman formulir penuh `/corrections/create` untuk pengajuan koreksi data operasional maupun data master tanpa modal pop-up.
  - Menambahkan tombol aksi "Ajukan Koreksi" pada header halaman Detail Kasus (`/cases/show`) dan Profil Murid (`/students/show`) dengan auto-fill parameter kontekstual.
  - Menambahkan tombol "+ Ajukan Koreksi" pada header halaman Daftar Koreksi Data (`/corrections`).
- `[x] [2026-08-20] Audit dan Penyempurnaan Modul Laporan (PG-301 & PG-302)`
  - Melakukan audit komprehensif kepatuhan terhadap PRD & SRS v1.0 (REP-01 s.d. REP-04, CONS-02, CASE-11, NFR-02, NFR-04) dan desain Penpot Hi-Fi via MCP.
  - Menambahkan SCSS styling untuk `.sibk-report-card`, `.sibk-report-grid`, dan `@media print` lengkap dengan Kop Surat resmi SMKN 1 Surabaya dan blok tanda tangan pengesahan.
  - Mengimplementasikan konfigurasi dinamis untuk seluruh 7 tipe laporan P0 (Pelanggaran per Murid, Pelanggaran per Kelas, Poin Pelanggaran, Konsultasi, Status Tindak Lanjut, Rekap Layanan BK, Prestasi).
  - Menyempurnakan dropdown ekspor data (Excel, CSV, PDF) dan aksesibilitas link.
- `[x] [2026-08-19] Audit dan Penyempurnaan Modul Data Murid (PG-201, PG-202, PG-203)`
  - Memverifikasi implementasi 5 tab dinamis pada Profil Murid (Ringkasan, Kasus, e-Tatib, Konsultasi, Prestasi).
  - Memperbaiki tautan fungsionalitas antar halaman.
- `[x] [2026-08-19] Setup Development Log & Task Tracker`
  - Menambahkan aturan baru pada `AGENTS.md` mengenai kewajiban pelacakan tugas agen.
  - Membuat file `docs/development-log.md` sebagai *source of truth* untuk progres pekerjaan.
