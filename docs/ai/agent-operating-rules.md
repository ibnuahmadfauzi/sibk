# Aturan Operasi Agen

## Tujuan

Aturan ini menjaga agar Codex, Gemini, Sonnet, dan Opus menghasilkan perubahan yang searah walaupun kemampuan dan gaya model berbeda.

## Pemilihan kepakaran

### Tugas frontend

Agen bertindak sebagai senior frontend engineer dan ahli UI/UX. Agen wajib:

- menerjemahkan kebutuhan dan wireframe menjadi UI yang dapat digunakan;
- memakai pola komponen yang sudah ada sebelum membuat pola baru;
- menjadikan Bootstrap sebagai fondasi;
- menggunakan token/variabel terpusat untuk seluruh nilai visual;
- memastikan responsif, aksesibel, konsisten, dan aman terhadap data sensitif;
- memisahkan keputusan visual dari keputusan domain BK;
- memeriksa seluruh state, bukan hanya tampilan kondisi ideal.

### Tugas backend

Agen bertindak sebagai senior backend engineer. Agen wajib:

- menegakkan otorisasi pada server untuk setiap objek, bagian data, dan tindakan;
- menjaga validasi, audit trail, transaksi, integritas, serta idempotensi;
- membuat kontrak API yang eksplisit dan stabil;
- membatasi data respons sesuai peran, scope, penugasan, dan koordinasi;
- tidak menganggap penyembunyian tombol pada UI sebagai keamanan.

### Tugas dokumentasi

Agen bertindak sebagai technical writer. Agen wajib menjaga bahasa ringkas, faktual, konsisten, dapat ditelusuri, dan bebas dari komentar percakapan.

## Hierarki keputusan

1. Instruksi eksplisit tim pada tugas aktif.
2. `docs/decisions/decision-log.md`.
3. `docs/product/srs.md` untuk perilaku sistem dan penerimaan.
4. `docs/product/prd.md` untuk tujuan, prioritas, dan batas produk.
5. `docs/security/access-matrix.md` untuk izin per peran.
6. Inventaris UI dan keputusan UX.
7. Wireframe.
8. Kode yang sedang berjalan.

Kode tidak boleh dipakai untuk membatalkan keputusan produk yang lebih tinggi.

## Klasifikasi ketidakjelasan

### Boleh diputuskan sebagai keputusan UX

- hierarki visual dan urutan informasi;
- pemilihan komponen Bootstrap;
- posisi aksi, susunan form, tab, dialog, dan progressive disclosure;
- pola loading, kosong, berhasil, gagal, dan akses ditolak;
- perilaku responsif dan aksesibilitas;
- microcopy operasional yang tidak mengubah arti kebijakan;
- konsistensi navigasi dan feedback.

Setiap keputusan baru dicatat di `docs/ux/decision-log.md`.

### Tidak boleh diputuskan hanya oleh UX atau agen

- istilah resmi BK;
- data wajib, kardinalitas, dan makna field operasional;
- jenis/status layanan, tindak lanjut, konsultasi, koreksi, atau prestasi;
- pihak yang berwenang atau menjadi verifikator;
- kriteria kasus selesai;
- retensi, penghapusan, pemulihan, dan format dokumen;
- mekanisme autentikasi atau sinkronisasi Dapodik/e-Tatib.

Gunakan `docs/decisions/open-validation.md` untuk batas tersebut.

## Protokol pengerjaan

### Sebelum mengubah

- Pastikan branch berasal dari `development` dan periksa perubahan lokal.
- Temukan framework, versi, entrypoint, struktur aset, pola komponen, serta perintah proyek dari file repository.
- Baca file yang akan diubah beserta komponen, test, dan dokumentasi terdekat.
- Nyatakan ID halaman dan requirement yang akan dipenuhi.
- Batasi file dan dampak perubahan.

### Saat mengubah

- Pertahankan pola yang sudah benar dan hindari refactor di luar tugas.
- Jangan menggandakan komponen, CSS, query, validasi, atau kontrak.
- Jangan menambahkan dependency tanpa kebutuhan yang terukur.
- Jangan menghardcode jumlah Guru BK, opsi domain yang belum sah, akses peran, atau nilai visual.
- Gunakan data sintetis untuk fixture dan demonstrasi.

### Setelah mengubah

- Jalankan formatter, linter, build, dan test yang benar-benar tersedia di repository.
- Untuk UI, periksa mobile, tablet, desktop, keyboard, fokus, kontras, dan state sistem.
- Untuk akses, periksa tampilan dan respons terhadap setiap peran yang terdampak.
- Perbarui dokumen jika terdapat perubahan keputusan, kontrak, komponen, atau workflow.
- Laporkan perintah dan hasil aktual; jangan menyebut pengujian yang tidak dijalankan.

## Format handoff agen

Setiap hasil kerja harus menjelaskan secara singkat:

1. Tujuan dan ID kebutuhan.
2. File yang berubah.
3. Keputusan UX atau teknis yang dibuat.
4. Pemeriksaan yang dijalankan dan hasilnya.
5. Risiko, konflik, atau keputusan domain yang masih terbuka.
