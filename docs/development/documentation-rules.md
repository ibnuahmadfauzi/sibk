# Aturan Dokumentasi

## Tujuan

Dokumentasi harus membuat anggota tim atau agen baru memahami tujuan, batas, keputusan, dan cara memeriksa pekerjaan tanpa membaca ulang seluruh percakapan.

## Aturan penulisan

- Gunakan bahasa Indonesia yang langsung dan konsisten.
- Gunakan istilah **murid**, bukan siswa atau peserta didik.
- Gunakan ID yang stabil untuk requirement, halaman, keputusan, dan UX decision.
- Tulis keadaan yang berlaku, bukan percakapan atau proses berpikir.
- Hindari frasa generik seperti “sesuai kebutuhan”, “dan lain-lain”, atau “implementasikan dengan baik” tanpa kriteria.
- Jangan memasukkan opini model, instruksi sementara, atau komentar yang tidak diperlukan pembaca.
- Gunakan tabel hanya untuk data yang memang perlu dibandingkan.
- Gunakan tautan relatif repository.
- Jangan memasukkan data nyata murid, kredensial, atau informasi sensitif.

## Pemilik dokumen

- PRD: keputusan produk dan pemilik proses.
- SRS: analyst/technical lead bersama pemilik kebutuhan.
- Access matrix: pemilik proses dan backend/security reviewer.
- Inventaris UI: analyst, frontend, dan UI/UX.
- UX decision log: UI/UX reviewer.
- Kontrak API: frontend dan backend bersama.
- Workflow serta Definition of Done: tim pengembangan.

## Kapan dokumen diperbarui

- Requirement atau prioritas berubah.
- Hak akses berubah.
- Halaman, field, aksi, status, atau respons sistem berubah.
- UX decision baru dibuat.
- Kontrak frontend-backend berubah.
- Perintah instalasi, build, test, atau struktur repository berubah.
- Dependency penting ditambah, dihapus, atau dinaikkan versi mayor.

## Perubahan keputusan

1. Catat keputusan dan alasan pada decision log yang sesuai.
2. Perbarui PRD/SRS bila perubahan menyentuh kebutuhan.
3. Perbarui inventaris, access matrix, dan dokumen implementasi turunan.
4. Lakukan perubahan kode dan test.
5. Pastikan seluruh perubahan berada pada pull request yang sama atau saling tertaut.

## Status dokumen

- `baseline-final`: sumber yang telah disahkan untuk implementasi saat ini.
- `approved`: keputusan yang telah disetujui dalam area tertentu.
- `working`: dokumen kerja yang masih dapat berubah tanpa mengubah baseline.
- `superseded`: tidak lagi berlaku dan menunjuk penggantinya.

Jangan memakai kata `final` untuk menutup kemungkinan versioning; gunakan versi dan status secara eksplisit.
