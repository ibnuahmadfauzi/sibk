# Workflow Wireframe ke UI

## Sasaran

Mengubah setiap wireframe menjadi UI produksi yang dapat ditelusuri ke kebutuhan tanpa memperluas kebijakan produk atau backend.

## Tahap 0 — Audit repository

- Jalankan `docs/development/repository-audit-checklist.md`.
- Temukan versi Bootstrap, bundler, template engine, entrypoint CSS/JS, komponen bersama, routing frontend, dan perintah test/build.
- Pertahankan pola yang masih sehat. Catat konflik terhadap baseline dokumentasi.

**Gerbang:** stack dan lokasi implementasi diketahui dari file repository, bukan asumsi.

## Tahap 1 — Pilih unit antarmuka

- Ambil satu ID `PG-*` dari `docs/product/ui-inventory.md`.
- Baca seluruh item terkait pada `docs/product/ui-field-actions.md`.
- Baca requirement SRS, access matrix, wireframe, dan keputusan UX terkait.
- Identifikasi peran serta variasi read-only.

**Gerbang:** tujuan, data, tindakan, sensitivitas, dan kriteria penerimaan halaman dapat dijelaskan.

## Tahap 2 — Bedah wireframe

- Petakan area layout, hierarki informasi, navigasi, aksi utama, aksi sekunder, dan konten pendukung.
- Tandai bagian berulang yang harus menjadi komponen.
- Tandai ketidakjelasan sebagai UX atau domain.
- Putuskan ketidakjelasan UX berdasarkan pedoman lalu catat pada UX decision log.
- Jangan memutuskan ketidakjelasan domain.

**Gerbang:** wireframe memiliki peta komponen dan daftar state.

## Tahap 3 — Pemetaan Bootstrap

- Cocokkan grid, navigation, card, table, form, modal, offcanvas, alert, badge, pagination, dan utility yang tersedia.
- Gunakan komponen Bootstrap sebelum CSS khusus.
- Tentukan token yang digunakan; jangan menulis nilai visual pada halaman.
- Tentukan perilaku mobile, tablet, dan desktop.

**Gerbang:** tidak ada framework UI paralel atau style sekali pakai tanpa alasan.

## Tahap 4 — Kontrak UI

Untuk setiap komponen, tetapkan:

- input/props/data yang dibutuhkan;
- event atau aksi yang dipancarkan;
- visibility berdasarkan peran;
- state loading, kosong, gagal, berhasil, disabled, dan read-only;
- pesan validasi serta aksesibilitas;
- data yang tidak boleh dimuat atau ditampilkan.

Gunakan fixture sintetis. Jangan membuat kontrak backend final dari tebakan.

**Gerbang:** UI dapat dikembangkan dan diuji tanpa menyamarkan asumsi domain.

## Tahap 5 — Implementasi

- Mulai dari app shell dan komponen bersama.
- Implementasikan satu halaman atau alur kecil pada satu waktu.
- Hindari refactor di luar kebutuhan halaman.
- Gunakan token dan komponen bersama.
- Pertahankan ID halaman/requirement pada nama test, deskripsi PR, atau dokumentasi yang relevan.

## Tahap 6 — Verifikasi

- Bandingkan dengan wireframe dan requirement.
- Periksa seluruh state dan variasi peran.
- Periksa keyboard, fokus, label, error, kontras, zoom, dan ukuran layar.
- Jalankan formatter, linter, test, dan build repository.
- Gunakan `docs/frontend/qa-checklist.md`.

## Tahap 7 — Handoff

- Catat komponen baru atau berubah.
- Catat UX decision baru.
- Jelaskan mock/fixture dan kontrak backend yang masih diperlukan.
- Sertakan hasil pemeriksaan aktual.
- Ajukan pull request menuju `development` sesuai workflow tim.
