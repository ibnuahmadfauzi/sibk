---
document_id: wireframe-page-map
version: 1.0
status: canonical-name-map
last_updated: 2026-08-15
---

# Pemetaan Halaman ke Wireframe

Peta ini menetapkan nama frame Penpot dan nama file ekspor untuk seluruh 26 antarmuka. Nama frame wajib diawali ID halaman agar kebutuhan, desain, kode, test, dan pull request dapat ditelusuri dengan istilah yang sama.

| ID halaman | Nama frame Penpot wajib | File ekspor | Prioritas | Catatan transformasi utama |
|---|---|---|---|---|
| PG-001 | PG-001 — Login | `PG-001-login.png` | P0 | Form autentikasi, error umum, loading, dan akses keyboard. |
| PG-002 | PG-002 — Dashboard | `PG-002-dashboard.png` | P0 | Variasi kartu, jadwal, aktivitas, dan scope berdasarkan peran. |
| PG-003 | PG-003 — Notifikasi | `PG-003-notifikasi.png` | P0 | State baca/belum baca, objek tujuan, kosong, dan gagal. |
| PG-004 | PG-004 — Akun Saya | `PG-004-akun-saya.png` | P0 | Ringkasan identitas, scope efektif, dan aksi keluar. |
| PG-101 | PG-101 — Daftar Kasus | `PG-101-daftar-kasus.png` | P0 | Filter aktif, tabel/kartu mobile, pagination, dan pembatasan objek. |
| PG-102 | PG-102 — Buat Kasus Baru | `PG-102-buat-kasus-baru.png` | P0 | Form bertahap, identitas sementara, validasi, dan data sensitif. |
| PG-103 | PG-103 — Detail Kasus | `PG-103-detail-kasus.png` | P0 | Ringkasan, timeline, detail sensitif, state read-only Waka, dan audit. |
| PG-104 | PG-104 — Tambah/Edit Tindak Lanjut | `PG-104-tindak-lanjut.png` | P0 | Jenis dan status terpisah, tanggal, hasil, validasi, dan feedback. |
| PG-105 | PG-105 — Catat Konsultasi | `PG-105-catat-konsultasi.png` | P0 | Metadata, ringkasan umum, pemisahan isi sensitif, dan dokumen yang diizinkan. |
| PG-106 | PG-106 — Selesaikan Kasus | `PG-106-selesaikan-kasus.png` | P0 | Konfirmasi kontekstual, hasil akhir, dampak status, dan audit. |
| PG-107 | PG-107 — Koordinasikan Kasus dengan Waka | `PG-107-koordinasi-waka.png` | P0 | Tujuan koordinasi, batas detail, konfirmasi, dan akses Waka. |
| PG-201 | PG-201 — Daftar Murid | `PG-201-daftar-murid.png` | P0 | Pencarian, filter kelas, scope, state kosong, dan pola mobile. |
| PG-202 | PG-202 — Profil Murid | `PG-202-profil-murid.png` | P0 | Histori lintas kelas/guru, tab data, sensitivitas, dan catatan lama read-only. |
| PG-203 | PG-203 — Catat/Edit Prestasi | `PG-203-prestasi.png` | P0 bertahap | Form prestasi setelah keputusan domain dan fungsi inti stabil. |
| PG-301 | PG-301 — Pusat Laporan | `PG-301-pusat-laporan.png` | P0 | Pemilihan laporan, filter, cakupan peran, dan ringkasan hasil. |
| PG-302 | PG-302 — Pratinjau dan Ekspor Laporan | `PG-302-pratinjau-ekspor.png` | P0 | Preview, field terlarang, format cetak, loading, dan kegagalan ekspor. |
| PG-401 | PG-401 — Daftar Penugasan Kelas | `PG-401-daftar-penugasan.png` | P0 | Periode efektif, histori, jumlah guru dinamis, dan filter. |
| PG-402 | PG-402 — Atur Penugasan Kelas | `PG-402-atur-penugasan.png` | P0 | Perubahan resmi, tanggal efektif, alasan, konfirmasi, dan audit. |
| PG-403 | PG-403 — Penugasan/Pengalihan Kasus | `PG-403-pengalihan-kasus.png` | P0 | Pengalihan eksplisit, pihak lama/baru, alasan, waktu, dan dampak akses. |
| PG-404 | PG-404 — Daftar Koreksi Data | `PG-404-daftar-koreksi.png` | P0 | Status, filter, pengaju, objek, dan pembatasan peran. |
| PG-405 | PG-405 — Detail dan Verifikasi Koreksi | `PG-405-verifikasi-koreksi.png` | P0 | Perbandingan nilai, alasan, keputusan, konfirmasi, dan audit. |
| PG-406 | PG-406 — Riwayat Perubahan | `PG-406-riwayat-perubahan.png` | P0 | Timeline audit yang hanya-baca, filter, dan detail perubahan aman. |
| PG-501 | PG-501 — Data Master dan Status Sinkronisasi | `PG-501-status-sinkronisasi.png` | P0 | Sumber, waktu, status, kegagalan, retry resmi, tanpa isi BK. |
| PG-502 | PG-502 — Rekonsiliasi Identitas Sementara | `PG-502-rekonsiliasi-identitas.png` | P0 | Nilai awal/resmi, konflik, keputusan teknis, dan audit. |
| PG-503 | PG-503 — Kelola Akun | `PG-503-kelola-akun.png` | P0 | Status akun, peran resmi, konfirmasi mutasi, dan pemisahan akses data. |
| PG-901 | PG-901 — Akses Ditolak | `PG-901-akses-ditolak.png` | P0 | Respons aman tanpa membocorkan keberadaan objek sensitif. |

## Ketentuan pemetaan

- Jika nama frame yang ada berbeda, cocokkan berdasarkan tujuan dan isi lalu ubah namanya ke nama kanonik di atas.
- Jangan membuat frame baru hanya karena ada perbedaan kosmetik. Gunakan variasi komponen atau state bila masih satu halaman.
- Untuk variasi peran yang mengubah struktur secara material, gunakan suffix setelah nama kanonik, misalnya `PG-103 — Detail Kasus — Waka Read-only`.
- File ekspor menggunakan nama yang ditetapkan pada tabel dan ditempatkan di `docs/design/wireframes/`.
- Catat perubahan struktur pada pull request dan perbarui peta bila muncul halaman baru yang telah disahkan.
