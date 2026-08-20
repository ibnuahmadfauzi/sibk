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

- `[ ]` (Tidak ada task aktif saat ini)

---

## ✅ Completed Tasks

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
