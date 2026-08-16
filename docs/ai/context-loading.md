# Aturan Pemuatan Konteks

Tujuannya menjaga konsistensi tanpa memuat dokumen yang tidak berkaitan.

## Prinsip

- Jangan membaca seluruh folder `docs/` pada awal tugas; mulai dari router, tahap, ID halaman/requirement, dan file kode terdekat.
- Gunakan pencarian untuk mengambil bagian relevan sebelum membuka dokumen besar.
- Satu aturan memiliki satu sumber kanonik; perluas konteks hanya saat ada dependensi, konflik, atau risiko lintas area.
- Browser, screenshot, dan visual regression tidak digunakan otomatis; gunakan hanya atas permintaan eksplisit.

## Anggaran konteks awal

- Always On Antigravity hanya `.agents/rules/00-project-context.md`, tanpa impor `@` dokumen panjang. Codex membaca `AGENTS.md`, lalu router.
- Tugas satu halaman memulai dengan satu brief PG, satu sumber desain sesuai tahap, dan paling banyak dua pedoman tambahan.
- Untuk frontend, sumber desain adalah board atau ekspor halaman `hifi-approved`, bukan low-fidelity. Gambar visual direction tidak dimuat bila board atau ekspor hifi-approved halaman sudah tersedia.
- PRD, SRS, inventaris field, open validation, dan access matrix dibaca per bagian atau ID, bukan seluruhnya.

Anggaran dapat dilampaui untuk arsitektur, keamanan, migrasi data, atau kebijakan lintas modul; alasan perluasan harus jelas dari tugas.

## Paket konteks minimum

### UX dan low-fidelity satu halaman

1. Brief PG halaman.
2. Sumber low-fidelity yang dipetakan.
3. Paling banyak dua pedoman UX, akses, keputusan, atau state yang relevan.

### UI high-fidelity satu halaman

1. Brief PG halaman.
2. Satu sumber desain sesuai tahap: low-fidelity approved atau board high-fidelity yang dipetakan.
3. Paling banyak dua pedoman visual, token, akses, atau keputusan yang relevan. Peta/status dipakai hanya untuk memilih atau mencatat sumber bila brief belum cukup.

### Frontend satu halaman

1. Brief PG halaman.
2. Board atau ekspor `hifi-approved` halaman.
3. Paling banyak dua pedoman, biasanya workflow frontend dan aturan token.

### Backend satu kontrak/fitur

1. Requirement SRS terkait.
2. Baris access matrix terkait.
3. Aturan backend/API bila kontrak berubah.
4. File kode dan test terdekat.

## Kapan membaca dokumen penuh

Baca dokumen penuh hanya untuk arsitektur lintas modul, model akses atau data sensitif, baseline PRD/SRS, audit konsistensi dokumentasi, atau konflik banyak requirement.

## Larangan

- Jangan memakai `@` untuk mengimpor PRD, SRS, inventaris, atau pedoman panjang ke aturan Always On.
- Jangan memuat dokumen hanya karena tertaut jika tugas tidak bergantung padanya.
- Jangan meringkas ulang sumber besar ke banyak file baru.
- Jangan mengorbankan kebutuhan atau keamanan demi menghemat konteks; perluas bacaan bila risiko menuntutnya.
