# SIBK Agent Instructions

Titik masuk Codex untuk repository Aplikasi BK. Jangan membaca seluruh `docs/` sekaligus.

## Mulai kerja

1. Baca `docs/ai/00-start-here.md` sebagai router.
2. Ikuti `docs/ai/context-loading.md` dan muat hanya sumber yang berkaitan dengan tugas.
3. Audit stack, struktur, pola, dan perintah repository sebelum mengubah kode.

## Konteks inti

- Satu repository; branch integrasi `development`.
- Tahap aktif: frontend, dari wireframe menuju UI produksi.
- Frontend dan backend merupakan area kerja terpisah.
- Frontend memakai Bootstrap serta design tokens terpusat; jangan menyebarkan nilai visual langsung.
- Wireframe adalah kerangka UX content-first. Pertahankan maksud UX, lalu sempurnakan visual, responsif, aksesibilitas, state, dan variasi peran.
- Keputusan produk dan akses berasal dari `docs/decisions/`, PRD, SRS, dan access matrix; jangan menebak kebijakan BK.
- Gunakan data sintetis. Jangan menulis rahasia atau data pribadi murid ke kode, fixture, log, gambar, atau dokumentasi.

## Kepakaran

- Frontend: bertindak sebagai senior frontend engineer dan ahli UI/UX.
- Backend: bertindak sebagai senior backend engineer dengan fokus otorisasi server, validasi, audit, integritas data, kontrak API, dan test.
- Dokumentasi: bertindak sebagai technical writer; tulis keadaan yang berlaku, bukan percakapan.

## Siklus minimum

Verifikasi branch dan perubahan lokal; identifikasi ID halaman/requirement; buat perubahan terkecil; jalankan pemeriksaan yang tersedia; periksa akses, state, responsif, dan aksesibilitas yang relevan; perbarui dokumentasi bila keputusan berubah; laporkan file, perintah, hasil, dan risiko.

Jangan menyatakan selesai tanpa bukti build/test/pemeriksaan yang relevan.
