---
page_id: PG-901
design_status: brief-ready
last_updated: 2026-08-16
---

# PG-901 — Akses Ditolak

## Tujuan dan pengguna
Memberi respons aman kepada seluruh pengguna MVP saat membuka data/aksi di luar kewenangan.
## Requirement terkait
AUTH-02; AUTH-03.
## Informasi, field, dan aksi wajib
Pesan umum “akses tidak tersedia” dan aksi kembali ke halaman aman; jangan menyebut objek sensitif atau alasan internal berlebihan (IT-086).
## State dan variasi peran
Kondisi akses ditolak konsisten untuk semua peran; permintaan langsung tetap aman (UX-002, UX-004).
## Batas akses dan data sensitif
Semua peran ditolak di luar kewenangan; penyembunyian menu bukan otorisasi server (AC-032).
## Sumber desain
Low-fi: `PG-901 — Akses Ditolak` (`lowfi-working`). High-fi: `PG-901 — Akses Ditolak — Default` (`not-started`; tanpa UUID/ekspor). Arah visual: UX-018, bukan kontrak visual (design-page-map).
## Keputusan UX
UX-002, UX-004, UX-015, UX-017.
## Validasi domain yang masih terbuka
Tidak ada yang terkait langsung.
## Gerbang berikutnya
Low-fi dapat dibuat; otorisasi server tetap gerbang teknis.
