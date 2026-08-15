# Workflow Pengembangan

## Model repository

- Frontend dan backend berada dalam satu repository.
- `development` menjadi branch integrasi.
- Pekerjaan dibuat pada branch pendek dari `development`, kemudian digabungkan melalui pull request.

## Penamaan branch

- Fitur: `feat/<ringkas-fitur>`
- Perbaikan: `fix/<ringkas-masalah>`
- Dokumentasi: `docs/<ringkas-perubahan>`
- Refactor terarah: `refactor/<ringkas-area>`
- Pengujian: `test/<ringkas-area>`

Satu branch menangani satu tujuan yang dapat direview.

## Alur tugas

1. Sinkronkan `development` dan pastikan working tree dipahami.
2. Buat branch tugas.
3. Isi brief menggunakan `docs/ai/task-brief-template.md`.
4. Kaitkan ID halaman dan requirement.
5. Audit file dan pola yang sudah ada.
6. Buat rencana perubahan kecil.
7. Implementasikan dan uji secara bertahap.
8. Perbarui dokumentasi dan decision log yang terdampak.
9. Jalankan gerbang pada `docs/development/definition-of-done.md`.
10. Buat pull request menuju `development`.

## Commit

Gunakan format singkat dan spesifik:

- `feat(frontend): implementasikan daftar kasus`
- `fix(frontend): perbaiki fokus modal tindak lanjut`
- `feat(backend): batasi detail kasus terkoordinasi`
- `docs: perbarui keputusan UX dashboard`
- `test: tambah skenario akses Waka`

Jangan mencampur formatting massal, refactor tidak terkait, dan perubahan fitur pada commit yang sama.

## Pull request

Deskripsi pull request minimal memuat:

- tujuan dan konteks;
- ID halaman/requirement;
- perubahan utama;
- screenshot atau rekaman untuk UI menggunakan data sintetis;
- ukuran layar dan peran yang diperiksa;
- perintah test/build dan hasilnya;
- keputusan atau dokumentasi yang berubah;
- risiko dan bagian yang belum dikerjakan.

## Review

- Review kebutuhan: sesuai PRD/SRS dan scope.
- Review UX/UI: alur, konsistensi, responsif, aksesibilitas, dan state.
- Review teknis: struktur, reuse, performa, keamanan, serta test.
- Review akses: peran, scope, bagian sensitif, dan respons server.
- Review dokumentasi: decision log dan kontrak tetap sinkron.

## Aturan merge

- Jangan merge jika pemeriksaan wajib gagal.
- Jangan merge perubahan kebutuhan tanpa pembaruan dokumen kanonik.
- Jangan merge UI yang hanya menampilkan happy path.
- Konflik diselesaikan dengan mempertahankan keputusan terbaru, bukan sekadar memilih versi file.
