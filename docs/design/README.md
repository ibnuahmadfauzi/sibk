# Acuan Desain dan Wireframe

Folder ini menghubungkan kebutuhan produk, wireframe Penpot, dan implementasi frontend. Wireframe menjadi kerangka UX low-fidelity yang memetakan informasi, field, aksi, pengelompokan, prioritas, area layout, navigasi, dan alur dasar. Detail piksel dan gaya visualnya belum final, tetapi maksud UX yang ditunjukkan tidak boleh diabaikan tanpa alasan.

## Sumber wajib

1. [`wireframe-source.md`](wireframe-source.md) untuk lokasi dan status sumber Penpot.
2. [`wireframe-page-map.md`](wireframe-page-map.md) untuk pemetaan 26 ID halaman ke nama frame dan nama ekspor.
3. [`wireframes/README.md`](wireframes/README.md) untuk aturan ekspor yang dapat dibaca agen tanpa akses Penpot.
4. [`ui-quality-bar.md`](ui-quality-bar.md) untuk standar daya tarik dan mutu visual.
5. `docs/product/ui-inventory.md` dan `docs/product/ui-field-actions.md` untuk cakupan halaman, field, aksi, validasi, dan state.
6. `docs/security/access-matrix.md` untuk batas peran dan objek.
7. `docs/frontend/ui-ux-guidelines.md` serta `docs/ux/decision-log.md` untuk penyempurnaan UX.
8. `docs/frontend/bootstrap-and-design-tokens.md` untuk implementasi visual.

## Makna “wireframe ke UI”

Implementasi wajib mentransformasikan kerangka UX low-fidelity menjadi UI produksi yang menarik dan mudah digunakan. Agen bertindak sebagai ahli UI yang memoles serta menyempurnakan komposisi secara profesional. Pekerjaan tidak selesai hanya dengan mengganti warna, menambahkan bayangan, atau menyalin wireframe secara mentah.

Yang dipertahankan dari wireframe:

- tujuan serta cakupan informasi halaman;
- field, data, aksi, dan kontrol yang telah sesuai dengan inventaris;
- pengelompokan, prioritas informasi, navigasi, dan alur utama yang masih sesuai kebutuhan;
- hubungan fungsi dan perpindahan halaman yang telah disahkan;
- pemisahan informasi umum, sensitif, read-only, dan berbasis peran.

Yang tidak mengikat dari wireframe:

- ukuran presisi, alignment mikro, dan jarak antarelemen;
- jumlah kolom serta susunan responsif ketika ukuran layar berubah;
- bentuk akhir section, card, tab, drawer, modal, dan komponen lain;
- warna, tipografi, ikon, radius, shadow, dan spacing;
- detail layout mobile, tablet, dan desktop yang belum dirancang pada wireframe;
- tingkat kepadatan serta pola progressive disclosure.

Yang wajib disempurnakan pada UI produksi:

- grid, alignment, spacing, ukuran komponen, dan hierarki visual;
- komposisi yang menarik, seimbang, tidak generik, dan sesuai karakter layanan BK;
- tipografi, ikon, warna, border, radius, serta bayangan melalui design tokens;
- komponen Bootstrap yang semantik dan dapat digunakan ulang;
- perilaku responsif untuk mobile, tablet, dan desktop;
- state default, loading, kosong, filter tanpa hasil, berhasil, gagal, disabled, read-only, dan akses ditolak;
- validasi, feedback, konfirmasi, pencegahan kesalahan, dan microcopy;
- akses keyboard, fokus, label, kontras, zoom, serta pembaca layar;
- variasi tampilan berdasarkan peran dan sensitivitas data.

## Batas perubahan

- Agen boleh memperbaiki layout, pengelompokan, dan hierarki yang belum efektif selama tidak mengubah kebutuhan, alur bisnis, hak akses, atau makna field.
- Agen tidak wajib melakukan pixel-copy, tetapi harus mempertahankan maksud UX wireframe yang masih benar.
- Perubahan besar terhadap pengelompokan, prioritas, navigasi, atau alur harus memiliki alasan penggunaan dan dicatat pada `docs/ux/decision-log.md`.
- Bila wireframe bertentangan dengan PRD, SRS, inventaris, access matrix, atau keputusan terbaru, sumber yang lebih tinggi harus diikuti dan perbedaannya dicatat.
- Agen tidak boleh menghapus field atau aksi wajib hanya karena tidak terlihat pada wireframe lama.
- Agen tidak boleh menambah aturan domain, status, hak akses, atau integrasi hanya untuk melengkapi desain.
- Agen tidak boleh mengklaim seluruh informasi wireframe telah tercakup bila tidak dapat membuka Penpot maupun ekspor halaman terkait.

## Hasil minimum per halaman

Setiap implementasi `PG-*` harus memiliki:

- rujukan ke frame Penpot atau ekspor yang digunakan;
- daftar perbedaan terencana antara wireframe dan UI produksi;
- penjelasan bila pengelompokan, prioritas, navigasi, atau alur wireframe diubah;
- penilaian terhadap `docs/design/ui-quality-bar.md`;
- komponen serta state yang diimplementasikan;
- hasil pemeriksaan mobile dan desktop;
- hasil pemeriksaan aksesibilitas serta variasi peran;
- tangkapan layar atau bukti visual setelah implementasi bila workflow repository mendukungnya.
