# Aturan Operasi Agen

Dokumen ini dibaca untuk tugas lintas area, keputusan ambigu, atau review yang membutuhkan aturan kerja lengkap. Tugas satu halaman mengikuti router dan anggaran konteks terlebih dahulu.

## Hierarki sumber

1. Instruksi eksplisit tugas aktif.
2. `docs/decisions/decision-log.md`.
3. SRS, lalu PRD.
4. `docs/security/access-matrix.md`.
5. Brief dan inventaris UI.
6. Low-fidelity approved untuk kontrak UX, lalu high-fidelity `hifi-approved` untuk kontrak visual frontend.
7. Kode berjalan.

Konflik tidak boleh diselesaikan diam-diam. Catat sumber yang bertentangan dan hentikan bagian yang bergantung pada keputusan tersebut.

## Tahap desain dan visual

Tahap aktif bergerak dari penyelesaian low-fidelity ke high-fidelity lalu frontend. Low-fidelity bukan sumber visual frontend. Implementasi frontend hanya dimulai dari board atau ekspor halaman `hifi-approved`; visual direction approved bukan pengganti status itu. Agen menjalankan pemeriksaan teknis, tetapi kesesuaian visual frontend direview manual oleh tim. Bila belum disetujui, handoff berstatus `manual-visual-review-pending`.

Jangan menggunakan browser, screenshot, atau visual regression otomatis; gunakan hanya bila tugas memintanya secara eksplisit.

## Peran berdasarkan area

- Frontend: senior frontend engineer dan ahli UI/UX; gunakan Bootstrap, tokens, komponen reusable, responsif, aksesibilitas, state, serta pembatasan peran.
- Backend: senior backend engineer; tegakkan otorisasi server, validasi, audit, transaksi, integritas, idempotensi, dan kontrak eksplisit.
- Dokumentasi: technical writer; jaga sumber kanonik, keterlacakan, bahasa faktual, dan tautan relatif.

## UX dan domain

UX boleh menetapkan hierarki visual, komponen, layout responsif, state, feedback, aksesibilitas, dan microcopy yang tidak mengubah kebijakan. Catat keputusan baru di `docs/ux/decision-log.md`.

Agen tidak boleh menetapkan istilah resmi BK, kewajiban/kardinalitas data, status operasional, kewenangan, verifikator, kriteria kasus selesai, retensi, format dokumen, atau mekanisme integrasi. Gunakan `docs/decisions/open-validation.md`.

## Protokol perubahan

Sebelum mengubah: periksa branch/status, audit stack dan pola terkait, tentukan ID halaman/requirement serta tahap, lalu batasi dampak dan muat konteks sesuai anggaran.

Saat mengubah: pertahankan pola sehat, hindari refactor di luar tugas, jangan menggandakan komponen/aturan, jangan menambah dependency tanpa alasan, dan gunakan data sintetis.

Setelah mengubah: jalankan formatter, linter, build, test, serta pemeriksaan teknis yang tersedia. Lakukan review visual manual bila ditugaskan; jangan mengklaim visual selesai tanpa persetujuan tersebut. Perbarui dokumen bila keputusan, kontrak, komponen, atau workflow berubah.

## Handoff

Laporkan tujuan dan ID kebutuhan, tahap serta status desain awal, sumber board/ekspor, file yang berubah, pemeriksaan beserta hasil aktual, status review visual manual, dan risiko atau keputusan terbuka. Jangan mengklaim pemeriksaan yang tidak dijalankan.
