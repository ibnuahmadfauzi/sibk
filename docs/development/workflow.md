# Workflow Pengembangan

## Model repository

- Frontend dan backend berada dalam satu repository.
- `development` menjadi branch integrasi.
- Setiap perubahan dikerjakan pada branch tugas pendek dari `development`, direview, lalu digabungkan ke `development` sesuai kebijakan tim.

## Penamaan branch

- Fitur: `feat/<ringkas-fitur>`
- Perbaikan: `fix/<ringkas-masalah>`
- Dokumentasi: `docs/<ringkas-perubahan>`
- Refactor terarah: `refactor/<ringkas-area>`
- Pengujian: `test/<ringkas-area>`

Satu branch menangani satu tujuan yang dapat direview.

## Area dan sumber kerja

- **UX/low-fidelity:** bekerja dari requirement, brief `PG-*`, dan sumber low-fidelity untuk menetapkan struktur informasi, alur, aksi, state, hierarki, serta batas peran. Hasil yang disetujui berstatus `lowfi-approved`.
- **UI high-fidelity:** bekerja dari brief dan low-fidelity approved untuk menetapkan komposisi visual, foundations, komponen, responsif, serta aset. Hanya board atau ekspor yang telah `hifi-approved` menjadi sumber visual frontend.
- **Frontend:** memulai dari `hifi-approved`, brief, dan sumber high-fidelity sesuai [workflow high-fidelity ke frontend](../frontend/hifi-to-frontend-workflow.md). Gunakan Bootstrap dan design tokens terpusat; low-fidelity hanya untuk klarifikasi maksud UX, bukan sumber visual produksi.
- **Backend:** bekerja dari SRS, access matrix, model data, dan kontrak API terkait. Backend tidak perlu memuat brief/desain halaman kecuali perubahan benar-benar lintas area atau kontrak membutuhkan konteks tersebut.

## Alur tugas umum

1. Sinkronkan `development` dan pastikan working tree dipahami.
2. Buat branch tugas.
3. Tentukan area; muat hanya sumber yang relevan. Isi brief `docs/ai/task-brief-template.md` bila pekerjaan memerlukannya.
4. Kaitkan ID halaman dan requirement untuk pekerjaan UX, UI, atau frontend; backend menggunakan requirement dan kontrak yang terkait.
5. Audit file dan pola yang sudah ada, lalu buat rencana perubahan kecil.
6. Implementasikan dan jalankan pemeriksaan yang relevan serta tersedia.
7. Perbarui dokumentasi dan decision log yang terdampak.
8. Jalankan gerbang pada `docs/development/definition-of-done.md`.
9. Buat pull request menuju `development`.

## Handoff frontend dan review visual

Alur status frontend adalah `hifi-approved` → pemeriksaan teknis agen → `manual-visual-review-pending` → review visual manual → `implemented`.

- Agen menyerahkan hasil setelah pemeriksaan teknis lulus dengan status `manual-visual-review-pending` dan mencatat `PG-*`, board/ekspor sumber, state/peran, penggunaan Bootstrap/tokens, data sintetis, hasil pemeriksaan, serta risiko terbuka.
- Agen tidak boleh menyatakan halaman selesai, cocok, atau disetujui secara visual sebelum persetujuan manusia tercatat.
- Reviewer manusia memakai prosedur [review visual manual](../frontend/manual-visual-review.md), termasuk desktop, tablet, mobile, dan state/peran yang relevan. Bukti review atau screenshot, bila dibuat, memakai data sintetis.
- Browser, screenshot, dan visual regression tidak dijalankan agen secara otomatis; kegiatan tersebut hanya atas permintaan eksplisit.
- Reviewer manusia atau owner yang ditentukan, bukan agen, mencatat persetujuan dan mengubah status menjadi `implemented`.

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
- status awal `hifi-approved`, board/ekspor sumber, dan status handoff untuk UI/frontend;
- data sintetis untuk fixture dan bukti review/screenshot bila bukti tersebut dibuat;
- ukuran layar dan peran yang diperiksa dalam review manual, bila telah tercatat;
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
- Jangan merge frontend sebagai `implemented` sebelum pemeriksaan teknis lulus dan persetujuan visual manual manusia tercatat.
- Konflik diselesaikan dengan mempertahankan keputusan terbaru, bukan sekadar memilih versi file.
