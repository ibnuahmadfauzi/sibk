---
page_id: PG-107
design_status: blocked-domain-validation
last_updated: 2026-08-16
---

# PG-107 — Koordinasi Waka

## Tujuan dan pengguna
Guru BK berwenang atau Koordinator mencatat koordinasi/persetujuan dan membuka detail kasus hanya-baca bagi Waka tujuan.
## Requirement terkait
AUTH-05; CASE-12; DASH-03; AUD-01.
## Informasi, field, dan aksi wajib
Ringkasan kasus, kebutuhan koordinasi, Waka tujuan aktif, status koordinasi, dan Simpan (IT-087–IT-091). Koordinasi tidak mengubah status kasus.
## State dan variasi peran
Validasi, berhasil/gagal, dan Waka read-only pada kasus target (UX-004, UX-010).
## Batas akses dan data sensitif
Waka hanya detail terkoordinasi; tidak mendapat konsultasi sensitif/catatan internal; pencatatan diaudit (AC-033–AC-034).
## Sumber desain
Low-fi: `PG-107 — Koordinasikan Kasus dengan Waka` (`lowfi-working`). High-fi: `PG-107 — Koordinasikan Kasus dengan Waka — Default` (`not-started`; tanpa UUID/ekspor). Arah visual: UX-018, bukan kontrak visual (design-page-map).
## Keputusan UX
UX-004, UX-009, UX-010, UX-011, UX-017.
## Validasi domain yang masih terbuka
IT-090 menyatakan status koordinasi wajib tetapi nilainya belum divalidasi. Sumber belum memberi ID open-validation; daftarkan ID validasi kanonik sebelum menetapkan opsi/status, tanpa mengarangnya.
## Gerbang berikutnya
Daftarkan dan validasi ID kanonik untuk status koordinasi sebelum low-fi final; jangan menetapkan opsi/status.
