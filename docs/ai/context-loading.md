# Aturan Pemuatan Konteks

Tujuannya adalah menjaga konsistensi tanpa memenuhi context window dengan dokumen yang tidak berkaitan.

## Prinsip

- Jumlah file di repository tidak menjadi masalah; isi file baru memakai konteks ketika dimuat.
- Jangan membaca seluruh folder `docs/` pada awal tugas.
- Mulai dari router, ID halaman/requirement, dan file kode terdekat.
- Gunakan pencarian untuk mengambil bagian yang relevan sebelum membuka dokumen besar.
- Satu aturan harus memiliki satu sumber kanonik. Dokumen lain cukup menautkannya.
- Perluas konteks hanya ketika ditemukan dependensi, konflik, atau risiko lintas area.

## Anggaran konteks awal

- Always On Antigravity: hanya `.agents/rules/00-project-context.md`; tanpa impor `@` dokumen panjang.
- Codex: `AGENTS.md`, lalu router.
- Tugas biasa: mulai dengan paling banyak empat dokumen domain selain entrypoint.
- PRD, SRS, inventaris field, open validation, dan access matrix dibaca per bagian atau ID, bukan seluruhnya.

Anggaran ini dapat dilampaui untuk perubahan arsitektur, keamanan, migrasi data, atau kebijakan lintas modul. Alasan perluasan harus jelas dari tugas.

## Paket konteks minimum

### Frontend satu halaman

1. Baris halaman pada inventaris.
2. Item field/aksi untuk halaman tersebut.
3. Frame/ekspor wireframe terkait.
4. Satu dokumen pedoman sesuai fokus: visual, UX, akses, atau QA.

### Backend satu kontrak/fitur

1. Requirement SRS terkait.
2. Baris access matrix terkait.
3. Aturan backend/API bila kontrak berubah.
4. File kode dan test terdekat.

### Review

1. Brief/PR dan diff.
2. Requirement serta keputusan yang disebutkan.
3. Checklist kualitas yang sesuai area.

## Kapan membaca dokumen penuh

Baca dokumen penuh hanya untuk:

- menyusun atau mengubah arsitektur lintas modul;
- mengubah model akses atau data sensitif;
- merevisi baseline PRD/SRS;
- melakukan audit konsistensi dokumentasi;
- menyelesaikan konflik yang melibatkan banyak requirement.

## Larangan

- Jangan memakai `@` untuk mengimpor PRD, SRS, inventaris, atau pedoman panjang ke aturan Always On.
- Jangan memuat dokumen hanya karena tertaut jika tugas tidak bergantung padanya.
- Jangan meringkas ulang sumber besar ke banyak file baru.
- Jangan mengorbankan kebutuhan atau keamanan demi menghemat konteks; perluas bacaan bila risiko menuntutnya.
