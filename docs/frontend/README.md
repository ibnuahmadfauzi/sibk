# Frontend Aplikasi BK

## Status

Area ini merupakan fokus pengembangan aktif. Tujuan tahap pertama adalah menerjemahkan wireframe menjadi UI yang konsisten, responsif, aksesibel, dan dapat disambungkan ke backend tanpa mengarang kontrak data.

Menerjemahkan wireframe bukan pekerjaan mengganti warna atau menyalin susunan kotaknya secara mentah. Wireframe menjadi kerangka UX content-first untuk informasi, pengelompokan, prioritas, navigasi, dan alur dasar. Agen frontend sebagai ahli UI wajib menjaga maksud UX sambil menyempurnakan komposisi agar menarik, profesional, tidak generik, serta lengkap dengan komponen reusable, interaksi, responsif, aksesibilitas, state sistem, dan variasi peran.

## Peran agen frontend

Pada tugas frontend, agen bertindak sebagai:

- senior frontend engineer untuk struktur komponen, state, performa, integrasi, dan pengujian;
- ahli UI untuk hierarki, layout, tipografi, warna, komponen, serta konsistensi visual;
- ahli UX untuk alur, feedback, pencegahan kesalahan, aksesibilitas, dan perlindungan informasi sensitif.

## Sumber wajib

1. `docs/product/ui-inventory.md`
2. Baris halaman terkait di `docs/product/ui-field-actions.md`
3. Requirement terkait di `docs/product/srs.md`
4. `docs/security/access-matrix.md`
5. `docs/design/README.md`
6. Baris halaman terkait di `docs/design/wireframe-page-map.md`
7. Frame Penpot atau ekspor wireframe halaman terkait
8. `docs/design/ui-quality-bar.md`
9. `docs/ux/decision-log.md`
10. Dokumen frontend lain pada folder ini

## Aturan teknologi

- Gunakan Bootstrap yang sudah terpasang di repository.
- Jangan mengganti versi mayor Bootstrap atau menambahkan framework UI paralel dalam tugas tampilan biasa.
- Gunakan komponen dan utility Bootstrap sebelum menulis CSS khusus.
- CSS khusus hanya untuk kebutuhan identitas SIBK atau perilaku yang tidak disediakan Bootstrap.
- Seluruh warna, radius, shadow, ukuran penting, dan variasi komponen berasal dari token terpusat.
- Jangan memakai inline style dan jangan menyebarkan nilai hex/rgb ke file halaman.
- Ikuti pola templating, bundler, dan struktur aset yang ditemukan dari audit repository.

## Batas tahap frontend

- Boleh membuat adapter, fixture sintetis, dan mock state untuk mengembangkan UI.
- Jangan mengubah skema basis data, aturan otorisasi backend, atau integrasi sumber data tanpa tugas backend terpisah.
- Jangan menampilkan aksi yang tidak tersedia bagi peran, tetapi tetap asumsikan server wajib menolak akses langsung.
- Jangan menetapkan opsi domain yang masih terbuka sebagai konstanta final.

## Hasil minimum per halaman

- implementasi mengikuti ID `PG-*`;
- frame atau ekspor wireframe yang digunakan dapat ditelusuri;
- perbedaan antara wireframe dan UI produksi dijelaskan serta tidak mengubah kebutuhan;
- komponen reusable digunakan bila pola muncul lebih dari sekali;
- state loading, kosong, berhasil, gagal, read-only, dan akses ditolak tersedia sesuai kebutuhan;
- tampilan diperiksa pada mobile, tablet, dan desktop;
- keyboard, fokus, label, pesan error, serta kontras diperiksa;
- perubahan memiliki keterlacakan ke requirement dan keputusan UX;
- dokumentasi serta test diperbarui.
