# ADR 0001: Pisahkan Verifikasi Data dari Aktivasi Operasional

- **Status:** Diterima
- **Tanggal:** 9 September 2026

## Konteks

Data Dapodik tahun ajaran baru dapat terlambat 2–3 bulan, sedangkan layanan BK dan pembagian Guru BK harus tetap berjalan. Data persiapan sementara tidak boleh dianggap sebagai data resmi Dapodik, dan kedatangan data provider tidak boleh mengambil alih keputusan operasional sekolah.

## Keputusan

Status asal data dipisahkan dari `is_active` tahun ajaran. Admin IT menyiapkan data minimum berdasarkan dasar resmi sekolah; Koordinator BK melakukan aktivasi operasional setelah penugasan lengkap. Sinkronisasi Dapodik hanya menyiapkan pratinjau pencocokan. Setelah konfirmasi Admin IT, data yang cocok memperoleh identitas sumber dan menjadi terverifikasi Dapodik tanpa mengganti ID internal.

Pencocokan murid otomatis hanya memakai NISN exact. Konflik ditahan untuk pemeriksaan. Provider tidak boleh mengubah `is_active`, dan Ruang BK tidak melakukan write-back ke Dapodik atau e-Tatib.

## Konsekuensi

- Guru BK memperoleh scope melalui tahun ajaran aktif dan penugasan, bukan melalui status asal data.
- Kasus, konsultasi, prestasi, penugasan, dan histori BK tetap terhubung pada ID internal yang sama.
- Penerapan hasil pencocokan harus dikonfirmasi dan atomik; kegagalan tidak boleh mengubah data operasional.
