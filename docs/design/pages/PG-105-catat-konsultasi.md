---
page_id: PG-105
design_status: blocked-domain-validation
last_updated: 2026-08-16
---

# PG-105 — Catat Konsultasi

## Tujuan dan pengguna
Guru BK berwenang mencatat metadata konsultasi dan ringkasan umum yang diizinkan.
## Requirement terkait
AUTH-04; CONS-01; CONS-02.
## Informasi, field, dan aksi wajib
Tanggal, jenis layanan, status, Guru BK penanganan, jadwal tindak lanjut, ringkasan umum, dokumen yang diizinkan, Simpan (IT-044–IT-051).
## State dan variasi peran
Muat, validasi, gagal/berhasil; gunakan pengungkapan bertahap untuk data sensitif (UX-003–UX-005).
## Batas akses dan data sensitif
Hanya Guru BK berwenang; tidak menyimpan isi lengkap bebas. Ringkasan/dokumen sangat sensitif dan tidak otomatis terbuka (AC-011–AC-013).
## Sumber desain
Low-fi: `PG-105 — Catat Konsultasi` (`lowfi-working`). High-fi: `PG-105 — Catat Konsultasi — Default` (`not-started`; tanpa UUID/ekspor). Arah visual: UX-018, bukan kontrak visual (design-page-map).
## Keputusan UX
UX-003, UX-004, UX-005, UX-011, UX-017.
## Validasi domain yang masih terbuka
VBK-08 menentukan jenis/status konsultasi; VBK-14 menentukan aturan dokumen. Keduanya mengubah field/alur.
## Gerbang berikutnya
Validasi VBK-08 dan VBK-14 sebelum low-fi final; batasi pada metadata/ringkasan yang sah.
