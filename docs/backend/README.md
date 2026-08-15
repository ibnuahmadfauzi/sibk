# Backend Aplikasi BK

## Status

Backend berada dalam repository yang sama, tetapi bukan fokus tahap frontend saat ini. Dokumen ini menjaga batas agar pekerjaan UI tidak mengubah perilaku server secara tidak sengaja dan menjadi titik awal ketika fase backend dimulai.

## Peran agen backend

Pada tugas backend, agen bertindak sebagai senior backend engineer dengan fokus:

- otorisasi server per peran, scope, objek, bagian data, dan tindakan;
- validasi input dan aturan transisi;
- integritas transaksi dan pencegahan duplikasi;
- audit trail yang tidak dapat diubah pengguna biasa;
- kontrak API yang stabil dan terdokumentasi;
- pemisahan isi sensitif dari ringkasan umum;
- integrasi baca-saja e-Tatib dan master Dapodik;
- pengujian unit, integrasi, otorisasi, serta skenario kegagalan.

## Batas utama

- Jangan menganggap menu tersembunyi sebagai otorisasi.
- Jangan menghardcode tujuh Guru BK, daftar domain yang belum sah, atau aturan yang belum divalidasi.
- Jangan memberi Admin IT akses isi BK melalui fungsi teknis.
- Jangan memberi Waka endpoint mutasi atau detail kasus yang tidak dikoordinasikan.
- Jangan memberi Koordinator akses konsultasi sensitif di luar scope Guru BK.
- Jangan melakukan write-back ke e-Tatib pada MVP.
- Jangan menjadikan data sementara sebagai master alternatif Dapodik.
- Jangan membuat auto-delete sebelum kebijakan retensi, purge, dan recovery disahkan.

## Ketika fase backend dimulai

1. Audit framework, versi runtime, arsitektur, database, migration, policy/middleware, queue, test, dan integrasi yang sudah ada.
2. Cocokkan route, model, service, policy, dan test terhadap ID requirement SRS.
3. Susun kontrak frontend-backend per alur sebelum mengubah respons yang sudah digunakan.
4. Prioritaskan autentikasi, otorisasi, scope, kasus, tindak lanjut, audit, lalu laporan.
5. Uji skenario positif dan negatif untuk setiap peran.

## Handoff dengan frontend

- Frontend menyatakan data, state, dan aksi yang diperlukan.
- Backend menyatakan kontrak request, response, error, pagination, dan izin.
- Kontrak tidak boleh memuat field sensitif yang tidak diperlukan layar.
- Perubahan kontrak harus diperbarui bersama test dan dokumentasi pada pull request yang sama.
