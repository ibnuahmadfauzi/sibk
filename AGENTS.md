# SIBK Agent Instructions

Titik masuk Codex untuk repository Aplikasi BK. Jangan membaca seluruh `docs/` sekaligus.

## Mulai kerja

1. Baca `docs/ai/00-start-here.md` sebagai router.
2. Ikuti `docs/ai/context-loading.md`: konteks awal memuat paling banyak empat dokumen domain.
3. Audit stack, struktur, pola, branch, dan perubahan lokal sebelum mengubah kode.

## Tahap dan sumber desain

- Tahap aktif: menyelesaikan low-fidelity, lalu high-fidelity, lalu frontend.
- Low-fidelity approved adalah kontrak UX, bukan sumber visual frontend.
- Frontend hanya boleh dimulai ketika halaman berstatus `hifi-approved`; board atau ekspor high-fidelity halaman itu adalah sumber visualnya.
- Agen menjalankan pemeriksaan teknis; kesesuaian visual direview manual oleh tim. Serahkan sebagai `manual-visual-review-pending` bila review belum disetujui.
- Jangan gunakan browser, screenshot, atau visual regression kecuali tugas memintanya secara eksplisit.

## Konteks dan batas kerja

- Satu repository; branch integrasi `development`. Frontend dan backend adalah area terpisah. Frontend memakai Bootstrap dan design tokens terpusat; jangan menyebarkan nilai visual langsung.
- Keputusan produk dan akses berasal dari `docs/decisions/`, PRD, SRS, dan access matrix; jangan menebak kebijakan BK.
- Gunakan data sintetis. Jangan menulis rahasia atau data pribadi murid ke kode, fixture, log, gambar, atau dokumentasi.
- Ketidakjelasan visual, responsif, state, dan interaksi dapat diputuskan sebagai UX lalu dicatat. Domain, status operasional, hak akses, retensi, dan integrasi tidak boleh diasumsikan.

## Siklus minimum

Identifikasi ID halaman/requirement dan tahap; buat perubahan terkecil; periksa akses, state, responsif, dan aksesibilitas yang relevan; jalankan pemeriksaan tersedia; perbarui dokumentasi bila keputusan berubah; laporkan file, perintah, hasil, dan risiko.

Jangan menyatakan selesai tanpa bukti pemeriksaan yang relevan atau menyatakan kesesuaian visual tanpa review manual.
