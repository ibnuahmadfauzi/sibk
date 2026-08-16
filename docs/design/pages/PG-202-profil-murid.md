---
page_id: PG-202
design_status: blocked-domain-validation
last_updated: 2026-08-16
---

# PG-202 — Profil Murid

## Tujuan dan pengguna
Menggabungkan identitas, kelas, layanan, e-Tatib, prestasi, dan histori yang diizinkan.
## Requirement terkait
AUTH-02; AUTH-04; STU-01; STU-02; INT-02; ACH-01.
## Informasi, field, dan aksi wajib
Identitas/kelas; tab/timeline kasus-konsultasi-tindak lanjut; e-Tatib; prestasi; aksi laporkan data salah; histori lintas kelas (IT-056–IT-060; IT-105).
## State dan variasi peran
Muat, kosong, gagal, berhasil, read-only, akses ditolak; tab sensitif tidak dimuat tanpa hak (UX-004, UX-011).
## Batas akses dan data sensitif
Hak per bagian/objek. Pemegang scope aktif membaca histori hingga lulus namun tidak mengubah catatan profesional lama (AC-004, AC-035–AC-036).
## Sumber desain
Low-fi: `PG-202 — Profil Murid` (`lowfi-working`). High-fi: `PG-202 — Profil Murid — Default` (`not-started`; tanpa UUID/ekspor). Arah visual: UX-018, bukan kontrak visual (design-page-map).
## Keputusan UX
UX-003, UX-004, UX-010, UX-011, UX-017.
## Validasi domain yang masih terbuka
VBK-13 menentukan verifikator, status, bukti, dan retensi prestasi; ini mengubah state dan akses pada bagian prestasi yang tampil di halaman ini.
## Gerbang berikutnya
Validasi VBK-13 sebelum low-fi final pada bagian prestasi; jangan menetapkan status atau akses verifikasi.
