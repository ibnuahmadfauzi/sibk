---
document_id: wireframe-source
version: 1.0
status: active-reference
last_updated: 2026-08-15
---

# Sumber Wireframe Penpot

## Sumber utama

- **Aplikasi desain:** Penpot
- **Status:** sumber kerangka UX low-fidelity yang ditetapkan pemilik proyek
- **Tautan:** [Wireframe Aplikasi BK di Penpot](https://design.penpot.app/#/workspace?team-id=3be9e5e1-190f-8090-8008-70f42050dcf0&file-id=3be9e5e1-190f-8090-8008-6fae2e8e2654&page-id=3be9e5e1-190f-8090-8008-6fae2e8e2655)
- **Team ID:** `3be9e5e1-190f-8090-8008-70f42050dcf0`
- **File ID:** `3be9e5e1-190f-8090-8008-6fae2e8e2654`
- **Page ID yang tercatat:** `3be9e5e1-190f-8090-8008-6fae2e8e2655`

Tautan di atas menunjukkan file wireframe yang pernah ditetapkan pemilik proyek. ID frame individual tidak boleh dibuat berdasarkan perkiraan. Pemetaan lintas dokumen menggunakan ID `PG-*` dan nama frame kanonik pada [`wireframe-page-map.md`](wireframe-page-map.md).

Wireframe baseline bersifat content-first: fokus utamanya memastikan informasi dan fungsi halaman terpetakan. Pengelompokan, prioritas, navigasi, dan alur yang sudah ditunjukkan tetap diperlakukan sebagai keputusan UX awal, sedangkan detail visual serta responsif disempurnakan pada tahap UI.

## Cara agen memperoleh wireframe

Gunakan urutan berikut:

1. Buka frame terkait pada file Penpot melalui nama kanonik `PG-*`.
2. Jika Penpot tidak dapat diakses, gunakan ekspor pada `docs/design/wireframes/`.
3. Cocokkan elemen dengan `docs/product/ui-inventory.md` dan `docs/product/ui-field-actions.md`.
4. Jika frame dan ekspor sama-sama tidak tersedia, gunakan inventaris sebagai dasar minimum tetapi laporkan bahwa cakupan wireframe belum dapat diverifikasi. Jangan mengarang informasi yang diklaim berasal dari wireframe.

## Posisi wireframe dalam sumber kebenaran

Wireframe menunjukkan informasi, pengelompokan, prioritas, layout dasar, navigasi, dan alur UX, tetapi bukan gaya visual final atau kebijakan produk. Bila terdapat perbedaan:

1. keputusan proyek, SRS, PRD, dan access matrix menentukan kebutuhan serta hak akses;
2. inventaris menentukan halaman, field, aksi, dan state minimum;
3. wireframe membantu memastikan informasi, pengelompokan, navigasi, dan alur tidak terlewat;
4. keputusan UX menyelesaikan atau merevisi bagian wireframe yang belum efektif;
5. `docs/design/ui-quality-bar.md`, Bootstrap, dan design tokens menentukan mutu serta implementasi visual.

## Pemeliharaan sumber

- Gunakan awalan ID `PG-*` pada nama frame Penpot.
- Jangan mengganti ID halaman hanya karena nama menu berubah.
- Setelah frame berubah, perbarui tanggal dan catatan perubahan pada peta halaman.
- Ekspor ulang halaman yang berubah dengan nama file yang sama agar tautan dokumentasi tidak rusak.
- Bila file atau page Penpot dipindahkan, perbarui dokumen ini pada perubahan yang sama.
