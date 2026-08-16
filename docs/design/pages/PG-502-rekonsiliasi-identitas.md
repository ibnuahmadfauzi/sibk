---
page_id: PG-502
design_status: blocked-domain-validation
last_updated: 2026-08-16
---

# PG-502 — Rekonsiliasi Identitas

## Tujuan dan pengguna
Admin IT mencocokkan NISN/nama sementara dengan master resmi tanpa membuat murid ganda.
## Requirement terkait
AUTH-06; MD-03; MD-04; AUD-01.
## Informasi, field, dan aksi wajib
Daftar NISN/nama masukan/kasus-layanan/pembuat/waktu; kandidat master; status rekonsiliasi; tautkan master atau laporkan konflik/tidak ditemukan (IT-096–IT-100).
## State dan variasi peran
Muat, kosong, gagal, cocok, konflik, tidak ditemukan; konfirmasi sebelum tautkan (IT-098–IT-099; UX-004, UX-009).
## Batas akses dan data sensitif
Hanya Admin IT; detail tidak membuka isi layanan BK; NISN/nama dan kandidat sangat sensitif. Nama resmi mengikuti sumber dan nilai awal teraudit (AC-040–AC-041).
## Sumber desain
Low-fi: `PG-502 — Rekonsiliasi Identitas Sementara` (`lowfi-working`). High-fi: `PG-502 — Rekonsiliasi Identitas Sementara — Default` (`not-started`; tanpa UUID/ekspor). Arah visual: UX-018, bukan kontrak visual (design-page-map).
## Keputusan UX
UX-004, UX-006, UX-009, UX-011, UX-017.
## Validasi domain yang masih terbuka
VTI-01/VTI-02 menentukan field/pemetaan/sinkronisasi sumber; VTI-03 menentukan pelaporan konflik/koreksi master. Ketiganya berdampak pada kandidat dan alur rekonsiliasi.
## Gerbang berikutnya
Validasi VTI-01/VTI-02/VTI-03 sebelum low-fi final; jangan membuat master baru di Aplikasi BK.
