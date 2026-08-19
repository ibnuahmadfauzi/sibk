# Task Tracking & Development Log

Rule Description: Aturan wajib untuk agen dalam melakukan pelacakan tugas (task tracking) setiap kali akan mengubah kode atau melakukan implementasi.

## Instruksi Pencatatan Tugas

Setiap kali pengguna memberikan instruksi untuk mengerjakan task baru (seperti membuat halaman, memperbaiki bug, refactor, audit, dll), Agen **WAJIB** mengikuti prosedur berikut:

1. **Catat Sebelum Bekerja:** 
   - Sebelum memulai modifikasi kode atau riset mendalam, buka file `docs/development-log.md`.
   - Tambahkan entri baru di bawah bagian "Current / Upcoming Tasks" dengan format `[ ] [Tanggal] Nama Task` atau ubah statusnya menjadi `[IN PROGRESS]`.

2. **Perbarui Setelah Selesai:** 
   - Setelah task selesai dikerjakan, diuji, dan diverifikasi berjalan baik, buka kembali file `docs/development-log.md`.
   - Pindahkan/ubah status task tersebut ke bagian "Completed Tasks" dengan format `[x] [Tanggal] Nama Task`.
   - Tambahkan bullet point berupa ringkasan singkat hasil eksekusinya di bawah nama task tersebut.

## Lokasi Pencatatan
- **HANYA** gunakan file `docs/development-log.md` untuk mencatat riwayat (log) progres pengembangan yang terus bertambah.
- Jangan mencatat log tugas di dalam file `.agents/` atau file konfigurasi AI lainnya agar *rules* tetap bersih dan efisien untuk dibaca oleh agen.
