---
page_id: PG-501
design_status: blocked-domain-validation
last_updated: 2026-08-16
---

# PG-501 — Status Sinkronisasi

## Tujuan dan pengguna
Admin IT melihat sumber, waktu sinkronisasi, status, dan kegagalan pemetaan Dapodik/e-Tatib untuk penanganan teknis.
## Requirement terkait
AUTH-06; MD-01; MD-02; INT-03; COR-02.
## Informasi, field, dan aksi wajib
Sistem sumber, status, waktu sinkronisasi terakhir; kegagalan pemetaan; sinkronkan ulang/periksa detail bila mekanisme resmi mendukung (IT-083–IT-085).
## State dan variasi peran
Muat, kosong, gagal, berhasil; waktu data dan gangguan jelas, tanpa menganggap data lama terbaru (UX-004, UX-006).
## Batas akses dan data sensitif
Hanya Admin IT mengelola sinkronisasi; hak teknis tidak membuka isi layanan/konsultasi BK (AC-028–AC-029, AC-041).
## Sumber desain
Low-fi: `PG-501 — Data Master dan Status Sinkronisasi` (`lowfi-working`). High-fi: `PG-501 — Data Master dan Status Sinkronisasi — Default` (`not-started`; tanpa UUID/ekspor). Arah visual: UX-018, bukan kontrak visual (design-page-map).
## Keputusan UX
UX-004, UX-006, UX-011, UX-017.
## Validasi domain yang masih terbuka
VTI-01/VTI-02 menentukan mekanisme, field, frekuensi, gangguan, serta sinkronisasi/pemetaan; mengubah aksi dan informasi.
## Gerbang berikutnya
Validasi VTI-01/VTI-02 sebelum low-fi final untuk aksi teknis.
