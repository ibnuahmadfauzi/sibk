---
page_id: PG-101
design_status: blocked-domain-validation
last_updated: 2026-08-16
---

# PG-101 — Daftar Kasus

## Tujuan dan pengguna
Mencari, menyaring, dan membuka kasus dalam kewenangan Guru BK, Koordinator BK, atau Waka pada kasus terkoordinasi.
## Requirement terkait
AUTH-02; AUTH-05; CASE-01; CASE-04; CASE-12.
## Informasi, field, dan aksi wajib
Kata kunci murid/kasus; filter status, bidang layanan, sumber, kelas, periode; tabel ringkas; Buat kasus baru untuk Guru BK berwenang (IT-015–IT-020).
## State dan variasi peran
Muat, kosong, gagal, berhasil, akses ditolak; tabel mobile memprioritaskan kolom tanpa menghilangkan detail (UX-004, UX-007).
## Batas akses dan data sensitif
Hasil dibatasi sebelum tampil; Waka hanya kasus terkoordinasi dan hanya-baca (AC-007; IT-019).
## Sumber desain
Low-fi: `PG-101 — Daftar Kasus` (`lowfi-working`). High-fi: `PG-101 — Daftar Kasus — Default` (`not-started`; tanpa UUID/ekspor). Arah visual: UX-018, bukan kontrak visual (design-page-map).
## Keputusan UX
UX-002, UX-004, UX-007, UX-014, UX-017.
## Validasi domain yang masih terbuka
VBK-01–VBK-03: istilah, kardinalitas, dan kewajiban Bidang Layanan BK mengubah filter dan data daftar.
## Gerbang berikutnya
Validasi VBK-01–VBK-03 sebelum low-fi final; jangan menetapkan label/opsi di luar baseline.
