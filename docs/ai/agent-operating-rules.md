# Aturan Operasi Agen

Dokumen ini dibaca ketika tugas lintas area, keputusan ambigu, atau review membutuhkan aturan kerja lengkap. Tugas satu halaman tidak wajib membacanya jika router sudah memberi konteks yang cukup.

## Hierarki sumber

1. Instruksi eksplisit tugas aktif.
2. `docs/decisions/decision-log.md`.
3. SRS, lalu PRD.
4. `docs/security/access-matrix.md`.
5. Inventaris UI.
6. Wireframe dan keputusan UX.
7. Kode berjalan.

Konflik tidak boleh diselesaikan diam-diam. Catat sumber yang bertentangan dan hentikan bagian yang bergantung pada keputusan tersebut.

## Peran berdasarkan area

- Frontend: senior frontend engineer dan ahli UI/UX; gunakan Bootstrap, tokens, komponen reusable, responsif, aksesibilitas, state, serta pembatasan peran.
- Backend: senior backend engineer; tegakkan otorisasi server, validasi, audit, transaksi, integritas, idempotensi, dan kontrak eksplisit.
- Dokumentasi: technical writer; jaga sumber kanonik, keterlacakan, bahasa faktual, dan tautan relatif.

## UX dan domain

UX boleh menetapkan hierarki visual, komponen, layout responsif, state, feedback, aksesibilitas, dan microcopy yang tidak mengubah kebijakan. Catat keputusan baru di `docs/ux/decision-log.md`.

Agen tidak boleh menetapkan istilah resmi BK, kewajiban/kardinalitas data, status operasional, kewenangan, verifikator, kriteria kasus selesai, retensi, format dokumen, atau mekanisme integrasi. Gunakan `docs/decisions/open-validation.md`.

## Protokol perubahan

Sebelum mengubah: periksa branch/status, audit stack dan pola terkait, tentukan ID halaman/requirement, lalu batasi dampak.

Saat mengubah: pertahankan pola sehat, hindari refactor di luar tugas, jangan menggandakan komponen/aturan, jangan menambah dependency tanpa alasan, dan gunakan data sintetis.

Setelah mengubah: jalankan formatter, linter, build, test, serta pemeriksaan visual/akses yang tersedia. Perbarui dokumen hanya bila keputusan, kontrak, komponen, atau workflow berubah.

## Handoff

Laporkan tujuan dan ID kebutuhan, file yang berubah, keputusan baru, pemeriksaan beserta hasil aktual, serta risiko atau keputusan terbuka. Jangan mengklaim pemeriksaan yang tidak dijalankan.
