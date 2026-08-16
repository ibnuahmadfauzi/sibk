---
page_id: PG-102
design_status: blocked-domain-validation
last_updated: 2026-08-16
---

# PG-102 — Buat Kasus Baru

## Tujuan dan pengguna
Guru BK mencatat kasus dari sumber sah; identitas sementara dipakai kondisional bila master belum tersedia.
## Requirement terkait
CASE-01–CASE-03; CASE-07; CASE-08; CASE-10; MD-03; INT-02.
## Informasi, field, dan aksi wajib
Pilih murid/master atau NISN+nama sementara; sumber kasus dan referensi e-Tatib kondisional; tanggal layanan, bidang layanan, informasi/penanganan awal, catatan internal, dan Simpan (IT-021–IT-029; IT-092–IT-095).
## State dan variasi peran
Mode Master/Sementara, validasi dekat field, gagal/berhasil; penanda wajib rekonsiliasi pada identitas sementara (UX-003–UX-005).
## Batas akses dan data sensitif
Hanya Guru BK dan murid dalam scope; server memeriksa kewenangan. Informasi awal, penanganan, catatan, NISN/nama sementara sangat sensitif (AC-006).
## Sumber desain
Low-fi: `PG-102 — Buat Kasus Baru` (`lowfi-working`). High-fi: `PG-102 — Buat Kasus Baru — Default` (`not-started`; tanpa UUID/ekspor). Arah visual: UX-018, bukan kontrak visual (design-page-map).
## Keputusan UX
UX-003, UX-004, UX-005, UX-011, UX-017.
## Validasi domain yang masih terbuka
VBK-01–VBK-04, VBK-16: label/kardinalitas/kewajiban bidang, arti tanggal layanan, serta detail rujukan mengubah form/validasi/alur.
## Gerbang berikutnya
Validasi terkait sebelum low-fi final; VBK-05 terkait pemisahan catatan juga harus dipatuhi tanpa mengisi asumsi.
