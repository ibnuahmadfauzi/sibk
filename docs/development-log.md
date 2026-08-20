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

- `[x] [2026-08-20] Sprint 9 Backend — Hardening, Keamanan, Performa, dan Integrasi Akhir`
  - Menghapus seluruh fixture produksi dan route closure, mempertahankan `_preview/*` sebagai redirect kompatibilitas GET/HEAD, menghubungkan halaman akun serta pencarian global ke data nyata, dan memastikan route/config/view dapat di-cache.
  - Menerapkan navigasi berbasis policy/Gate, matriks `AUTH-01`–`AUTH-07`, scoped binding anti-IDOR, perlindungan route web/CSRF, akun nonaktif, retensi minimum tiga tahun, audit append-only, dan pemisahan penuh Admin IT dari layanan BK.
  - Membatasi akun sintetis ke local/testing dengan password environment eksplisit, menonaktifkan serving storage privat sampai `DEP-06`, serta mempertahankan connector eksternal dan ekspor lanjutan sesuai dependency yang belum disahkan.
  - Menambahkan indeks hardening additive, filter kelas historis di database, pagination/KPI query-backed untuk laporan detail, ekspor CSV bertahap, dan pengujian regresi N+1 dengan pertumbuhan maksimal dua query.
  - Memperluas checker ke seluruh halaman PG dan komponen navigasi, menghapus Blade fixture yang tidak digunakan, serta memverifikasi 87 test/643 assertion, migrasi MySQL tanpa `migrate:fresh`, seeder dua kali, Pint, cache Laravel, kompilasi Blade, build Vite, frontend checker, dan `git diff --check`.

- `[x] [2026-08-20] Sprint 8 Backend — Prestasi P0 Bertahap`
  - Menambahkan skema additive Prestasi, tiga kategori referensi idempotent, bukti berbasis metadata, soft delete tanpa endpoint hapus, typed casts, relasi, scope, dan audit yang tidak menyimpan isi bukti/catatan.
  - Mengimplementasikan `AchievementService`, Form Request, `AchievementPolicy`, transaksi, row locking, antrean verifikasi Koordinator, status final, batas akses Guru BK/Waka/Admin IT, serta koreksi prestasi terverifikasi melalui service terkait.
  - Mengganti fixture PG-203 serta empty state PG-202 dengan pencatatan, detail, edit, verifikasi, histori profil, statistik, filter, kelas historis, preselection murid, dan pesan Bahasa Indonesia tanpa mengubah navigasi utama.
  - Mengaktifkan laporan Prestasi PG-301/PG-302 dan CSV dengan filter jenis/tingkat/status, scope per role, inisial dan NISN tersamarkan, serta pengecualian bukti dan catatan dari keluaran umum.
  - Memverifikasi migrasi additive SQLite dan MySQL tanpa `migrate:fresh`, seeder dua kali, 73 test/502 assertion, kompilasi Blade, Pint, build Vite, pemeriksaan frontend, serta `git diff --check`.

- `[x] [2026-08-20] Sprint 7 Backend — Laporan Terscope, Pratinjau, Cetak, dan Ekspor CSV`
  - Mengimplementasikan `ReportService`, `ReportPolicy`, `ReportRequest`, dan `ReportController` untuk tujuh tipe laporan dengan filter tahun ajaran/periode, kelas historis, murid, kategori, bidang, status, Guru BK, dan ambang poin.
  - Menerapkan scope Guru BK, rekap gabungan Koordinator, batas koordinasi Waka, penolakan Admin IT, masa penugasan kasus, serta evaluasi multi-role tanpa membuka data di luar kewenangan.
  - Mengganti fixture PG-301/PG-302 dengan katalog, KPI, pagination, filter, cetak, identitas pencetak, dan empty state Prestasi dari database tanpa nama/NIP/tanggal sintetis.
  - Menambahkan redaksi inisial dan NISN, pengecualian seluruh narasi privat, serta ekspor CSV streaming dengan UTF-8 BOM, formula-injection protection, dan dataset yang sama dengan pratinjau. XLSX/PDF tetap ditunda sampai `DEP-07`.
  - Memverifikasi 67 test/447 assertion, kompilasi Blade, Pint, build Vite, pemeriksaan frontend PG-301/PG-302, serta `git diff --check`; Sprint ini tidak memerlukan migrasi atau dependensi baru.

- `[x] [2026-08-20] Sprint 6 Backend — Dashboard dan Notifikasi`
  - Menambahkan `DashboardService` dengan query terpisah untuk Guru BK, Koordinator BK, Waka Kesiswaan, dan Admin IT; agregat, jadwal, aktivitas, serta tautan aksi mengikuti tahun ajaran dan kewenangan server tanpa memuat catatan privat.
  - Menambahkan notifikasi persisten, policy kepemilikan, kategori jadwal/penugasan/koordinasi/koreksi/perubahan, deduplikasi, status baca, filter, jumlah belum dibaca pada navigasi, dan allowlist tautan tujuan yang tetap diperiksa policy objek.
  - Menghubungkan event penugasan kelas/kasus, koordinasi Waka, tindak lanjut, konsultasi, dan koreksi kepada penerima aktif yang relevan; Admin IT tidak menerima notifikasi layanan hanya karena role teknis.
  - Mengganti fixture produksi PG-002/PG-003 dengan controller, service, pagination, filter, empty state, dan data database; URL pratinjau lama dipertahankan sebagai redirect kompatibilitas.
  - Memverifikasi migrasi additive SQLite dan MySQL tanpa `migrate:fresh`, seeder dua kali, 59 test/361 assertion, kompilasi Blade, Pint, build Vite, pemeriksaan frontend, serta `git diff --check`.

- `[x] [2026-08-20] Sprint 5 Backend — Koreksi dan Riwayat Perubahan`
  - Menambahkan skema additive koreksi dengan nomor `KR-YYYY-XXXX`, target polymorphic, status referensi dinamis, nilai lama/usulan, pengaju, pemeriksa, hasil, tautan sinkronisasi, soft delete, serta relasi eksplisit pada objek domain.
  - Mengimplementasikan `CorrectionService`, Form Request, `CorrectionPolicy`, row locking, pemeriksaan stale value, matriks akses per peran, dan penerapan koreksi operasional hanya melalui `CaseService`, `FollowUpService`, atau `ConsultationService`.
  - Mengimplementasikan koreksi master tanpa mutasi Dapodik sepihak; status selesai hanya dapat disimpan setelah hasil sinkronisasi Dapodik yang lebih baru cocok dengan nilai usulan.
  - Mengganti fixture PG-404/PG-405/PG-406 dengan daftar/detail/form database, verifikasi Koordinator, pemrosesan Admin IT, audit terpagina dan terscope, filter, pagination, serta pesan Bahasa Indonesia.
  - Memverifikasi migrasi additive SQLite dan MySQL tanpa `migrate:fresh`, seeder dua kali, 59 test/336 assertion, kompilasi Blade, Pint, build Vite, pemeriksaan frontend, serta `git diff --check`.

- `[x] [2026-08-20] Sprint 4 Backend — Konsultasi dan Profil Murid`
  - Menambahkan skema additive `consultations` dan `consultation_private_notes`, referensi empat status konsultasi, nomor sesi `KNS-YYYY-XXXX`, identitas resmi/sementara, relasi kasus opsional, soft delete, transaksi, serta audit yang tidak menyimpan isi sensitif.
  - Mengimplementasikan `ConsultationService`, Form Request, `ConsultationPolicy`, `StudentPolicy`, scope profesional lintas penugasan kelas/kasus, histori privat Guru BK penerus yang read-only, serta redaksi ketat untuk Koordinator, Waka, Admin IT, dan akun multi-role.
  - Mengganti fixture PG-101/PG-105/PG-201/PG-202 dengan query database, filter dan pagination, profil murid terscope, histori kasus/tindak lanjut/e-Tatib/konsultasi, preselection murid, dan empty state Prestasi sampai Sprint 8.
  - Memverifikasi migrasi additive SQLite dan MySQL tanpa `migrate:fresh`, seeder dua kali, 52 test/274 assertion, kompilasi Blade, Pint, build Vite, pemeriksaan frontend, serta `git diff --check`.

- `[x] [2026-08-20] Seeder Akun Sintetis untuk Setiap Role P0`
  - Menambahkan empat akun pengembangan idempotent untuk Guru BK, Koordinator BK, Waka Kesiswaan, dan Admin IT dengan tepat satu role per akun.
  - Menambahkan konfigurasi `SIBK_SEED_ACCOUNT_PASSWORD`, integrasi ke `DatabaseSeeder`, dan test idempotensi serta pencocokan role/password.
  - Seeder berhasil dijalankan dua kali pada database lokal; 42 test/213 assertion dan Pint lulus.

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
