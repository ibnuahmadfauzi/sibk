---
page_id: PG-301
design_status: blocked-domain-validation
last_updated: 2026-08-16
---

# PG-301 — Pusat Laporan

## Tujuan dan pengguna
Memilih laporan/filter sesuai scope Guru BK, rekap Koordinator, atau batas Waka.
## Requirement terkait
AUTH-02; AUTH-05; REP-01; REP-02; REP-04.
## Informasi, field, dan aksi wajib
Pilih jenis laporan; filter murid/kelas, periode, kategori/bidang, status tindak lanjut; tampilkan laporan. Scope otomatis menurut peran (IT-065–IT-067; IT-106).
## State dan variasi peran
Muat, kosong, gagal, berhasil; filter aktif terlihat dan dapat dihapus (UX-004, UX-014).
## Batas akses dan data sensitif
Guru BK scope sendiri; Koordinator gabungan aktif; Waka agregat/format diizinkan. Laporan mengecualikan isi konsultasi sensitif (AC-024, AC-037–AC-038).
## Sumber desain
Low-fi: `PG-301 — Pusat Laporan` (`lowfi-working`). High-fi: `PG-301 — Pusat Laporan — Default` (`not-started`; tanpa UUID/ekspor). Arah visual: UX-018, bukan kontrak visual (design-page-map).
## Keputusan UX
UX-004, UX-007, UX-014, UX-017.
## Validasi domain yang masih terbuka
VBK-01 mengubah label filter kategori/bidang; VBK-07 mengubah nilai dan arti filter status tindak lanjut; VBK-08 menentukan jenis/status konsultasi untuk laporan konsultasi. Ketiganya mengubah kontrol/filter atau keluaran halaman.
## Gerbang berikutnya
Validasi VBK-01, VBK-07, dan VBK-08 sebelum low-fi final; scope tetap dinamis, bukan jumlah pengguna tetap.
