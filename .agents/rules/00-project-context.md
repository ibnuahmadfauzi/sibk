# SIBK — Aturan Always On Antigravity

Aktifkan sebagai **Always On** untuk Gemini, Sonnet, dan Opus. Jangan memakai impor `@`.

- Buka `docs/ai/00-start-here.md`, lalu ikuti `docs/ai/context-loading.md`; konteks awal maksimal empat dokumen domain dan jangan memuat seluruh `docs/`.
- Tahap aktif: selesaikan low-fidelity, lanjutkan high-fidelity, baru frontend. Low-fidelity adalah kontrak UX, bukan sumber visual frontend.
- Frontend hanya dimulai dari halaman `hifi-approved` serta board/ekspor high-fidelity-nya. Pemeriksaan visual adalah review manual; tanpa persetujuan, handoff sebagai `manual-visual-review-pending`.
- Jangan gunakan browser, screenshot, atau visual regression kecuali diminta eksplisit.
- Audit repository sebelum menyimpulkan stack atau pola. Branch integrasi `development`; frontend dan backend terpisah.
- Frontend memakai Bootstrap dan design tokens terpusat. Jangan mengarang kebijakan BK, akses, status, atau integrasi; gunakan data sintetis dan tanpa rahasia/data pribadi.
- Jalankan pemeriksaan relevan dan laporkan hasil serta risiko sebelum menyatakan selesai.
