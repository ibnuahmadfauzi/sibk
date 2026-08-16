# Indeks Desain SIBK

Folder ini merutekan pekerjaan dari kontrak produk dan UX ke high-fidelity, lalu frontend. Low-fi `lowfi-approved` adalah kontrak UX; hanya board atau ekspor high-fi `hifi-approved` yang menjadi sumber visual frontend. Tiga PNG arah visual tidak menggantikan status tersebut.

## Urutan pipeline dan sumber

1. **Pipeline desain:** [design-delivery-pipeline.md](design-delivery-pipeline.md) menetapkan gerbang, peran, serta urutan low-fi → high-fi → frontend.
2. **Brief halaman:** [pages/README.md](pages/README.md) dan satu brief `PG-*` memuat konteks terkecil untuk satu halaman.
3. **Low-fidelity:** [wireframe-source.md](wireframe-source.md), [wireframe-page-map.md](wireframe-page-map.md), dan [wireframes/README.md](wireframes/README.md) hanya untuk pekerjaan UX/low-fi atau audit maksud UX.
4. **High-fidelity:** [high-fidelity-source.md](high-fidelity-source.md) dan [design-page-map.md](design-page-map.md) memilih board, status, serta ekspor visual yang akan dipakai.
5. **Bahasa dan kualitas visual:** [visual-language.md](visual-language.md) serta [ui-quality-bar.md](ui-quality-bar.md) menetapkan arah, koreksi, dan quality bar tanpa menetapkan nilai token dari screenshot.
6. **Token dan workflow frontend:** [`docs/frontend/bootstrap-and-design-tokens.md`](../frontend/bootstrap-and-design-tokens.md) dan [`docs/frontend/hifi-to-frontend-workflow.md`](../frontend/hifi-to-frontend-workflow.md) menerapkan UI high-fi approved ke kode.

## Aturan rute singkat

- Untuk UX, mulai dari satu brief dan low-fi terkait; jangan menetapkan gaya visual final dari wireframe.
- Untuk UI high-fi, gunakan brief, low-fi approved, peta desain, bahasa visual, dan foundations/tokens Penpot yang disetujui.
- Untuk frontend, verifikasi `hifi-approved`, lalu muat satu brief serta board atau ekspor high-fi terkait. Low-fi hanya dibuka bila perlu mengaudit maksud UX; PNG arah visual tidak menjadi sumber visual implementasi.
- Jangan menebak nilai warna, font, spacing, radius, atau shadow dari screenshot, dan jangan menambah keputusan domain, hak akses, atau status produk untuk melengkapi desain.
