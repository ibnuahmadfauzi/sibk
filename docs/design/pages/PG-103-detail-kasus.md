---
page_id: PG-103
design_status: blocked-domain-validation
last_updated: 2026-08-16
---

# PG-103 — Detail Kasus

## Tujuan dan pengguna
Menampilkan satu kasus, penanganan, timeline, koordinasi Waka, e-Tatib, dan penyelesaian untuk pihak berwenang.
## Requirement terkait
AUTH-03–AUTH-05; CASE-03–CASE-12; INT-02; AUD-01.
## Informasi, field, dan aksi wajib
Ringkasan kasus; informasi/penanganan awal; timeline tindak lanjut/konsultasi; tautan e-Tatib; audit; aksi tambah tindak lanjut, konsultasi, edit, selesaikan (IT-030–IT-035).
## State dan variasi peran
Muat, kosong, gagal, berhasil, read-only, akses ditolak. Waka melihat bagian kerja yang diizinkan tanpa kontrol ubah (UX-004, UX-010).
## Batas akses dan data sensitif
Per objek/bagian; Waka hanya kasus terkoordinasi, tanpa konsultasi sensitif/catatan internal/dokumen asli (AC-007, AC-012–AC-013).
## Sumber desain
Low-fi: `PG-103 — Detail Kasus` (`lowfi-working`). High-fi: `PG-103 — Detail Kasus — Default` (`not-started`; tanpa UUID/ekspor). Arah visual: UX-018, bukan kontrak visual (design-page-map).
## Keputusan UX
UX-003, UX-004, UX-010, UX-011, UX-017.
## Validasi domain yang masih terbuka
VBK-01–VBK-03, VBK-05–VBK-08, dan VBK-15 dapat mengubah label/status/isi bagian kasus, tindak lanjut, konsultasi, dan penyelesaian.
## Gerbang berikutnya
Pakai pemisahan akses yang telah disahkan; low-fi final menunggu validasi field/status terkait.
