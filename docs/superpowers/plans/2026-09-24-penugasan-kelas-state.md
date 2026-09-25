# Rencana implementasi Penugasan Kelas

Acuan: PRD v1.1, SRS v1.1, dan API Contract yang aktif. Tidak ada spec baru.

1. Ubah penyimpanan penugasan menjadi satu state per kelas dan tahun ajaran. Request hanya menerima Guru BK dan kelas; perubahan guru menulis audit sebelum/sesudah, pilihan guru yang sama tidak menulis ulang.
2. Tampilkan daftar berbasis kelas pada satu halaman, termasuk kelas tanpa penugasan; pengelolaan dilakukan dari halaman itu. Pertahankan otorisasi setiap peran menurut API Contract.
3. Selaraskan membership murid dan owner kasus yang dipakai oleh scope akses. Hilangkan ketergantungan pada tanggal periode; penugasan tahun Persiapan belum memberi akses.
4. Simpan tahun ajaran dan kelas saat kasus/konsultasi dibuat, lalu gunakan snapshot itu pada riwayat dan laporan. Perubahan penugasan tidak mengubah owner kasus.
5. Buat migrasi forward-only. Periksa data lama yang ganda/ambigu sebelum konversi agar tidak memilih state atau snapshot secara diam-diam.
6. Sesuaikan seeder dummy dan test yang langsung menyentuh domain ini. Jalankan test dan lint terarah, lalu tinjau diff sebelum commit.

Risiko: data development lama dapat memiliki lebih dari satu state dalam tahun yang sama atau tidak mempunyai kelas saat layanan dibuat. Migrasi menghentikan konversi yang ambigu; data tersebut dibetulkan secara eksplisit pada lingkungan terkait sebelum migrasi diulang. Database bersama tidak direset.
