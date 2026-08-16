# Mulai dari Sini

Dokumen ini adalah router konteks; jangan membaca seluruh dokumentasi untuk setiap tugas.

## Proyek dan tahap aktif

Aplikasi BK melayani kasus, tindak lanjut, histori murid, koordinasi Waka, laporan, dan audit. Dapodik adalah master murid/kelas; e-Tatib sumber baca-saja pelanggaran dan poin.

Tahap aktif: selesaikan low-fidelity, lanjutkan high-fidelity, lalu frontend. Low-fidelity approved adalah kontrak UX; hanya high-fidelity `hifi-approved` menjadi kontrak visual frontend. Review visual frontend dilakukan manual oleh tim.

## Cara memilih konteks

1. Tentukan tahap, area, ID halaman/requirement, dan peran yang terdampak.
2. Pilih rute di bawah, lalu ikuti batas pada `docs/ai/context-loading.md`.
3. Gunakan pencarian ID untuk membaca bagian relevan; perluas hanya bila ada dependensi, konflik, atau risiko.

## Rute berdasarkan tugas

### UX dan low-fidelity satu halaman

Mulai dari brief `docs/design/pages/PG-xxx-*.md` dan satu sumber low-fidelity yang dipilih melalui `docs/design/design-page-map.md`. Tambahkan paling banyak dua pedoman UX, akses, atau keputusan yang relevan. Gunakan peta hanya untuk memilih sumber bila brief belum menunjukkannya. Low-fidelity menetapkan informasi, alur, prioritas, state, dan peran; jangan menetapkan detail visual frontend.

### UI high-fidelity satu halaman

Mulai dari brief `docs/design/pages/PG-xxx-*.md` dan satu sumber desain sesuai tahap: low-fidelity approved untuk kontrak UX atau board high-fidelity saat melanjutkan desain. Gunakan `docs/design/design-page-map.md` dan `docs/design/high-fidelity-source.md` hanya untuk memilih atau mencatat sumber bila brief belum cukup. Tambahkan paling banyak dua pedoman visual, token, akses, atau keputusan. Tandai status desain dan board/ekspor yang dihasilkan; frontend belum boleh dimulai sebelum `hifi-approved`.

### Frontend satu halaman

Verifikasi `hifi-approved`, lalu rujuk `docs/design/pages/PG-xxx-*.md`, `docs/design/design-page-map.md`, `docs/design/high-fidelity-source.md`, `docs/frontend/hifi-to-frontend-workflow.md`, dan aturan token `docs/frontend/bootstrap-and-design-tokens.md`. Konteks awal hanya satu brief PG, satu board/ekspor high-fidelity halaman, serta paling banyak dua pedoman; gunakan peta dan sumber high-fidelity untuk memilih atau memverifikasi board sebelum memuat pedoman. Jangan memakai low-fidelity atau gambar visual direction sebagai sumber visual bila board/ekspor hifi-approved tersedia. Jalankan pemeriksaan teknis; serahkan review visual kepada manusia.

### Backend

Mulai dari `docs/backend/README.md`, requirement SRS dan access matrix terkait; tambahkan `docs/backend/api-contract-rules.md` bila mengubah kontrak API. Backend tidak mengambil sumber visual sebagai spesifikasi implementasi.

## Batas keputusan

UX dapat menetapkan hierarki visual, layout responsif, state, feedback, aksesibilitas, dan microcopy tanpa mengubah kebijakan; catat keputusan baru di `docs/ux/decision-log.md`. Istilah BK, data wajib, status operasional, hak akses, verifikator, retensi, dan integrasi tidak boleh diasumsikan.
