# Konteks Istilah Ruang BK

Gunakan dokumen ini sebagai glosarium singkat. Perilaku sistem tetap mengacu pada PRD dan SRS v1.1.

- **Data persiapan sementara:** data minimum tahun ajaran baru yang disiapkan dari dasar resmi sekolah ketika Dapodik belum tersedia. Data ini belum resmi dari Dapodik dan bukan master alternatif.
- **Terverifikasi Dapodik:** keadaan data setelah identitasnya berhasil dicocokkan dengan sumber Dapodik dan hasilnya dikonfirmasi.
- **Aktivasi operasional:** keputusan Koordinator BK untuk menjadikan satu tahun ajaran sebagai konteks kerja setelah data dan penugasan lengkap serta tanggal mulai telah tiba. Keputusan ini terpisah dari status verifikasi sumber.
- **Pratinjau pencocokan:** pemeriksaan hasil pencocokan Dapodik sebelum perubahan diterapkan pada data operasional.
- **Perlu Konfirmasi:** hasil perbandingan read-only untuk murid aktif tahun sebelumnya yang belum mempunyai penempatan pada tahun target. Penanda ini bukan keputusan naik kelas, tinggal kelas, lulus, pindah, atau keluar dan tidak memblokir aktivasi keseluruhan.
- **Status pelayanan:** lima status bersama untuk kasus dan konsultasi, yaitu `baru`, `sedang_diproses`, `membutuhkan_tindak_lanjut`, `selesai`, dan `dibatalkan`. Dua status terakhir bersifat terminal.
- **Kode kasus internal:** identitas teknis yang tetap disimpan untuk integritas data dan audit internal, tetapi tidak ditampilkan pada keluaran pengguna.
- **Proyeksi aman Waka:** ringkasan seluruh kasus yang hanya memuat field penanganan yang disahkan. Proyeksi ini tidak membuka NISN, kode kasus, isi konsultasi, catatan internal, atau dokumen sensitif; detail kasus tetap memerlukan koordinasi tercatat.

Asal data memakai kode `school_provisional`, `dapodik`, atau `legacy_unclassified`. Asal data tidak menentukan nilai `is_active` tahun ajaran.
