# Urutan Implementasi Halaman Frontend

Urutan ini mengurangi pekerjaan ulang dan mengikuti dependensi komponen. Satu gelombang harus stabil sebelum gelombang berikutnya dimulai, kecuali tim membagi pekerjaan pada komponen yang tidak saling bergantung.

## Gelombang 0 — Fondasi UI

- Audit repository dan inventaris komponen yang sudah ada.
- App shell, sidebar, offcanvas mobile, top bar, breadcrumb, container, grid, dan token.
- Komponen dasar: button, form feedback, alert, badge status, empty state, loading state, modal konfirmasi, table shell, filter bar, pagination, dan access denied.

**Selesai ketika:** pola dasar dapat digunakan ulang dan lolos pemeriksaan mobile/desktop serta keyboard.

## Gelombang 1 — Akses dan konteks global

- `PG-001` Login
- `PG-002` Dashboard
- `PG-003` Notifikasi
- `PG-004` Akun Saya
- `PG-901` Akses Ditolak

**Alasan:** membentuk app shell, state peran, pola feedback, serta fondasi navigasi.

## Gelombang 2 — Alur inti kasus

- `PG-101` Daftar Kasus
- `PG-102` Buat Kasus Baru
- `PG-103` Detail Kasus
- `PG-104` Tambah/Edit Tindak Lanjut
- `PG-105` Catat Konsultasi
- `PG-106` Selesaikan Kasus
- `PG-107` Koordinasikan Kasus dengan Waka

**Alasan:** ini merupakan nilai utama MVP dan membentuk komponen form, timeline, detail, akses sensitif, serta read-only Waka.

## Gelombang 3 — Data dan histori murid

- `PG-201` Daftar Murid
- `PG-202` Profil Murid

**Alasan:** menggunakan pola daftar/detail yang sudah stabil dan menggabungkan histori lintas layanan.

## Gelombang 4 — Laporan

- `PG-301` Pusat Laporan
- `PG-302` Pratinjau dan Ekspor Laporan

**Alasan:** bergantung pada pola filter, tabel, scope, dan data operasional dari gelombang sebelumnya.

## Gelombang 5 — Tata kelola Koordinator

- `PG-401` Daftar Penugasan Kelas
- `PG-402` Atur Penugasan Kelas
- `PG-403` Penugasan/Pengalihan Kasus
- `PG-404` Daftar Koreksi Data
- `PG-405` Detail dan Verifikasi Koreksi
- `PG-406` Riwayat Perubahan

**Alasan:** membutuhkan komponen audit, konfirmasi, perbandingan nilai, dan pembatasan peran yang sudah matang.

## Gelombang 6 — Administrasi teknis

- `PG-501` Data Master dan Status Sinkronisasi
- `PG-502` Rekonsiliasi Identitas Sementara
- `PG-503` Kelola Akun

**Alasan:** area teknis memerlukan pemisahan tegas dari isi layanan BK dan state gangguan integrasi.

## Gelombang 7 — P0 bertahap

- `PG-203` Catat/Edit Prestasi

**Syarat mulai:** fungsi kasus, tindak lanjut, laporan, akses, dan keputusan domain prestasi telah stabil.
