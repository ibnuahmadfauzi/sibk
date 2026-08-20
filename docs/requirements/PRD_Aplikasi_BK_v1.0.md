<!--
AI-readable mirror of the final DOCX baseline.
Source: PRD_Aplikasi_BK_v1.0.docx
Do not edit this Markdown independently from the final baseline document.
-->

**DOKUMEN KEBUTUHAN PRODUK (PRD)**

**Aplikasi BK**

Baseline kebutuhan produk untuk layanan Bimbingan dan Konseling

**Versi:** 1.0

**Status:** Baseline final untuk pengembangan MVP

**Tanggal:** 15 Agustus 2026

**Konteks:** SMKN 1 Surabaya

# Ringkasan produk

Aplikasi BK menjadi ruang kerja terpusat untuk mencatat layanan BK, memantau kasus dan tindak lanjut, menjaga kesinambungan histori murid, serta menyusun laporan dari data operasional yang sama. MVP berfokus pada Guru BK, Koordinator BK, Waka Kesiswaan, dan Admin IT.

Koordinator BK menjadi penanggung jawab operasional penggunaan aplikasi. Guru BK tetap menjadi pemilik proses layanan dan catatan profesional. Waka Kesiswaan menggunakan akses hanya-baca untuk ringkasan, laporan, dan detail kasus yang memang dikoordinasikan kepadanya. Admin IT mengelola akun, infrastruktur, integrasi, dan data master tanpa memperoleh akses otomatis ke isi layanan BK.

Aplikasi BK tidak menggantikan e-Tatib atau Dapodik. Pelanggaran dan poin resmi tetap dikelola di e-Tatib, sedangkan identitas murid, kelas, dan tahun ajaran tetap mengacu pada Dapodik. Aplikasi BK membaca data tersebut dan mencatat layanan BK yang berkaitan dengannya.

# Masalah yang diselesaikan

Pencatatan BK berjalan menggunakan buku, Excel, Google Spreadsheet, WhatsApp, dan Word. Data yang tersebar memperlambat pencarian riwayat, penyusunan laporan, pemantauan tindak lanjut, serta pengendalian akses terhadap informasi sensitif.

| **Gejala**                  | **Penyebab langsung**                                            | **Kebutuhan produk**                                                             |
|-----------------------------|------------------------------------------------------------------|----------------------------------------------------------------------------------|
| Laporan terlambat           | Data dikumpulkan dari beberapa media.                            | Rekap dibentuk dari satu sumber data operasional.                                |
| Status kasus sulit dipantau | Jadwal dan hasil tindak lanjut tersimpan terpisah.               | Setiap kasus memiliki riwayat kronologis dan jadwal berikutnya.                  |
| Riwayat murid terputus      | Pergantian kelas atau Guru BK tidak selalu membawa catatan lama. | Guru BK yang memperoleh scope murid dapat membaca histori layanan sebelumnya.    |
| Perubahan sulit ditelusuri  | Media kerja tidak menyimpan jejak perubahan seragam.             | Perubahan penting dicatat otomatis.                                              |
| Risiko kebocoran informasi  | Batas akses belum konsisten mengikuti tanggung jawab.            | Akses dibatasi per peran, objek, kelas ampuan, tahun ajaran, dan penugasan.      |
| Data master belum tersedia  | Sinkronisasi Dapodik belum selalu siap saat kasus muncul.        | Pencatatan sementara memakai NISN dan nama, lalu direkonsiliasi tanpa duplikasi. |

# Dasar kebutuhan

| **Temuan atau keputusan**                                               | **Dampak pada produk**                                                                                  |
|-------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------|
| Pencarian riwayat satu murid memerlukan 11–20 menit.                    | Pencarian profil dan histori menjadi alur utama.                                                        |
| Penyusunan laporan memerlukan 30–60 menit.                              | Laporan inti harus tersedia tanpa menggabungkan file manual.                                            |
| Terdapat tujuh Guru BK pada kondisi operasional saat ini.               | Koordinator dapat merekap seluruh Guru BK aktif; jumlah tidak ditanam tetap dalam kode.                 |
| Kerahasiaan konsultasi merupakan kebutuhan tertinggi.                   | Pemisahan detail kasus, ringkasan umum, catatan internal, dan konsultasi sensitif diterapkan sejak MVP. |
| Waka memerlukan detail kasus tertentu untuk persetujuan dan koordinasi. | Detail hanya tersedia pada kasus yang tercatat dikoordinasikan dan tetap hanya-baca.                    |
| Pergantian Guru BK tidak boleh memutus histori layanan murid.           | Hak baca histori mengikuti scope murid aktif sampai murid lulus.                                        |

# Visi dan tujuan

## Visi produk

Menyediakan ruang kerja digital BK yang aman, sederhana, dan terpusat untuk menghubungkan informasi murid, konteks pelanggaran, proses penanganan, tindak lanjut, koordinasi, dan laporan sesuai kewenangan setiap pengguna.

## Tujuan produk

- Memusatkan riwayat kasus, layanan, konsultasi, dan tindak lanjut pada profil murid.

- Menjaga kesinambungan histori ketika kelas, tahun ajaran, atau Guru BK berubah.

- Menghubungkan data e-Tatib tanpa mencatat ulang atau mengubah pelanggaran dan poin resmi.

- Mendukung penanganan kasus dari informasi awal sampai penyelesaian dan koordinasi dengan Waka.

- Membentuk dashboard dan laporan dari data operasional yang sama.

- Menjaga kerahasiaan melalui pembatasan akses berbasis tanggung jawab dan objek.

- Mendukung penggunaan melalui telepon genggam dan komputer.

## Indikator keberhasilan

| **Indikator**           | **Kondisi awal** | **Target uji awal**                                           |
|-------------------------|------------------|---------------------------------------------------------------|
| Menemukan riwayat murid | 11–20 menit      | Paling lama 2 menit pada uji kegunaan.                        |
| Membuat laporan inti    | 30–60 menit      | Paling lama 5 menit tanpa penggabungan file manual.           |
| Pembatasan akses        | Belum konsisten  | Seluruh percobaan akses di luar kewenangan ditolak.           |
| Riwayat perubahan       | Sulit diketahui  | Perubahan penting memiliki jejak audit.                       |
| Rekonsiliasi identitas  | Belum tersedia   | NISN sementara ditautkan ke master tanpa membuat murid ganda. |

Target waktu merupakan target uji awal, bukan janji layanan. Nilainya dapat ditetapkan kembali setelah uji kegunaan dengan Guru BK.

# Pengguna dan tata kelola

| **Peran**      | **Tanggung jawab**                                                                                                                              | **Batas akses**                                                                                                                                                 |
|----------------|-------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Guru BK        | Mengelola layanan untuk kelas ampuan dan kasus khusus; membaca histori murid dalam scope aktif.                                                 | Tidak mengakses murid di luar tanggung jawabnya dan tidak mengubah catatan profesional lama milik Guru BK sebelumnya.                                           |
| Koordinator BK | Penanggung jawab operasional; mengatur pembagian, kasus khusus, pengalihan, verifikasi operasional, serta rekap gabungan seluruh Guru BK aktif. | Jabatan koordinator tidak otomatis membuka konsultasi sensitif. Jika merangkap Guru BK, akses sensitif tetap mengikuti scope Guru BK.                           |
| Waka Kesiswaan | Memberi persetujuan atau berkoordinasi pada kasus tertentu serta membaca laporan yang disahkan.                                                 | Hanya-baca. Detail kasus hanya untuk kasus yang dikoordinasikan; tidak mengubah catatan profesional dan tidak otomatis membaca isi lengkap konsultasi sensitif. |
| Admin IT       | Mengelola akun, infrastruktur, integrasi, sinkronisasi, rekonsiliasi identitas, dan koreksi master melalui sumber resmi.                        | Hak teknis tidak otomatis memberi akses ke isi kasus, layanan, atau konsultasi.                                                                                 |
| Wali kelas     | Pengguna tahap P1 untuk informasi terbatas pada kelasnya.                                                                                       | Batas informasi ditetapkan sebelum P1 dibangun.                                                                                                                 |
| Murid          | Pengguna tahap P1 untuk informasi miliknya.                                                                                                     | Tidak melihat data murid lain atau informasi yang dibatasi.                                                                                                     |

- Saat baseline disusun terdapat tujuh Guru BK; sistem menghitung pengguna aktif dan tidak menanam jumlah tersebut dalam kode.

- Rolling atau perubahan pembagian dua tahunan hanya dicatat Koordinator BK berdasarkan keputusan resmi dan tidak dijalankan otomatis oleh sistem.

- Kasus aktif hanya dialihkan secara eksplisit, dengan penanggung jawab lama, penerima, alasan, waktu berlaku, dan audit.

- Hak membaca histori murid tidak memberi hak mengubah catatan layanan lama.

# Cakupan produk

## P0 – MVP

| **Area**                       | **Cakupan**                                                                                            | **Urutan**  |
|--------------------------------|--------------------------------------------------------------------------------------------------------|-------------|
| Akses dan akun                 | Login, akun aktif, pengelolaan akun oleh Admin IT, dan pembatasan akses per peran serta objek.         | Inti        |
| Penugasan                      | Pembagian Guru BK per kelas, periode efektif, perubahan resmi, kasus khusus, dan pengalihan eksplisit. | Inti        |
| Master dan identitas sementara | Master Dapodik serta pencatatan NISN+nama sementara ketika kasus muncul sebelum sinkronisasi.          | Inti        |
| Kasus dan tindak lanjut        | Pembuatan kasus, penanganan awal, jadwal, hasil tindak lanjut, koordinasi Waka, dan penyelesaian.      | Inti        |
| Konteks e-Tatib                | Membaca melalui API dan menautkan pelanggaran/poin tanpa pencatatan ulang atau write-back.             | Inti        |
| Konsultasi minimum             | Metadata, jadwal, status, ringkasan umum, serta pemisahan isi sensitif.                                | Inti        |
| Data dan histori murid         | Profil, e-Tatib, kasus, layanan, konsultasi, tindak lanjut, serta histori lintas kelas dan Guru BK.    | Inti        |
| Dashboard dan laporan          | Pemantauan, rekap per scope Guru BK, rekap gabungan Koordinator, dan laporan Waka yang diizinkan.      | Inti        |
| Akses Waka                     | Ringkasan dan laporan umum serta detail hanya-baca pada kasus yang dikoordinasikan.                    | Inti        |
| Audit dan koreksi              | Riwayat perubahan, koreksi operasional, laporan kesalahan master, dan rekonsiliasi identitas.          | Inti        |
| Retensi                        | Data tidak dihapus sebelum tersimpan minimum tiga tahun; penghapusan otomatis belum diaktifkan.        | Inti        |
| Prestasi                       | Pencatatan, riwayat, bukti yang diizinkan, verifikasi, dan laporan minimum.                            | P0 bertahap |

Prestasi tetap termasuk P0, tetapi dikerjakan setelah fungsi kasus, tindak lanjut, laporan, dan pengendalian akses inti stabil.

## P1 – Setelah MVP tervalidasi

- Akun wali kelas dengan informasi terbatas sesuai kewenangan yang disahkan.

- Akun murid untuk melihat data miliknya dan melakukan koreksi data miliknya.

- Alur koreksi oleh murid yang lebih lengkap.

- Notifikasi lanjutan di luar pengingat operasional minimum.

- Pengelolaan dokumen lanjutan setelah format, akses, dan prosedur retensi disahkan.

## Di luar cakupan MVP

- Menggantikan e-Tatib, membangun mesin poin, atau mengubah pelanggaran dan poin resmi.

- Diagnosis, rekomendasi konseling otomatis, SPK, mesin aturan, dan peringatan dini otomatis.

- Portal orang tua, sanggahan lengkap, dan pengajuan perubahan poin.

- Administrasi kehadiran dan otomatisasi pembuatan atau distribusi surat.

- Pemindahan otomatis kasus aktif atau rolling otomatis pembagian Guru BK.

- Penghapusan otomatis sebelum prosedur retensi dan pemulihan disahkan.

# Aturan produk utama

| **Area**            | **Aturan**                                                                                                                                      |
|---------------------|-------------------------------------------------------------------------------------------------------------------------------------------------|
| e-Tatib             | Sumber resmi pelanggaran dan poin. Aplikasi BK membaca melalui API dan tidak melakukan write-back pada MVP.                                     |
| Dapodik             | Sumber resmi identitas murid, kelas, keanggotaan kelas, dan tahun ajaran.                                                                       |
| Identitas sementara | Hanya dibuat saat ada kasus/layanan sebelum sinkronisasi, memakai NISN dan nama, lalu dicocokkan tanpa duplikasi.                               |
| Nama resmi          | Setelah rekonsiliasi, tampilan menggunakan nama resmi dari Dapodik/e-Tatib; nilai masukan awal tetap tersimpan pada audit.                      |
| Kewenangan          | Daftar, pencarian, detail, dashboard, laporan, ekspor, URL, dan API menerapkan batas yang sama.                                                 |
| Histori murid       | Guru BK dengan scope aktif dapat membaca histori layanan/konsultasi murid lintas kelas dan pergantian guru, tetapi tidak mengubah catatan lama. |
| Koordinasi Waka     | Detail kasus hanya tersedia bagi Waka setelah koordinasi dicatat; akses hanya-baca dan tidak mencakup isi konsultasi sensitif secara otomatis.  |
| Penugasan           | Perubahan menyimpan tanggal efektif dan dasar keputusan; kasus aktif tidak berpindah otomatis.                                                  |
| Koreksi             | Koordinator memverifikasi koreksi operasional; Admin IT menangani data master pada sumber resmi.                                                |
| Audit               | Perubahan status, hasil akhir, penugasan, koordinasi, kewenangan, akun, koreksi, dan rekonsiliasi dicatat otomatis.                             |
| Retensi             | Kasus, layanan, konsultasi, prestasi, dan audit disimpan minimum tiga tahun; prosedur penghapusan tetap memerlukan kebijakan operasional.       |

# Arsitektur informasi dan laporan

Navigasi utama Guru BK terdiri atas Dashboard, Layanan BK, Data Murid, dan Laporan. Notifikasi dan Akun tersedia secara global. Penugasan tersedia sesuai peran Koordinator, sedangkan pengelolaan akun, rekonsiliasi identitas, data master, dan sinkronisasi tersedia bagi Admin IT. Dapodik dan e-Tatib tetap menjadi sistem sumber, bukan modul navigasi utama.

| **Area**            | **Fungsi**                                                                                                |
|---------------------|-----------------------------------------------------------------------------------------------------------|
| Dashboard           | Kondisi kerja, kasus yang perlu ditindaklanjuti, jadwal, aktivitas, dan kasus terkoordinasi sesuai peran. |
| Layanan BK          | Daftar kasus, pembuatan kasus, penanganan, tindak lanjut, konsultasi, koordinasi Waka, dan penyelesaian.  |
| Data Murid          | Profil serta histori pelanggaran, kasus, layanan, konsultasi, tindak lanjut, dan prestasi yang diizinkan. |
| Laporan             | Rekap, filter, pratinjau, cetak, dan ekspor sesuai scope pengguna.                                        |
| Penugasan           | Pembagian kelas, periode efektif, kasus khusus, pengalihan, dan dasar keputusan resmi.                    |
| Administrasi teknis | Akun, infrastruktur, status sinkronisasi, kesalahan pemetaan, dan rekonsiliasi identitas.                 |

## Laporan P0

| **Laporan**           | **Filter utama**                    | **Cakupan peran**                                            |
|-----------------------|-------------------------------------|--------------------------------------------------------------|
| Pelanggaran per murid | Murid, periode, kategori            | Guru BK: scope; Koordinator: gabungan; Waka: yang diizinkan. |
| Pelanggaran per kelas | Kelas, periode, kategori            | Sesuai scope dan koordinasi.                                 |
| Poin pelanggaran      | Murid/kelas, periode                | Baca dari e-Tatib.                                           |
| Konsultasi            | Periode dan status                  | Tanpa isi sensitif pada laporan umum.                        |
| Status tindak lanjut  | Status, periode, kelas              | Sesuai scope dan koordinasi.                                 |
| Rekap layanan BK      | Periode, bidang layanan, Guru BK    | Koordinator dapat merekap seluruh Guru BK aktif.             |
| Prestasi              | Murid/kelas, jenis/tingkat, periode | P0 bertahap.                                                 |

# Risiko dan ketergantungan

## Risiko utama

| **Risiko**                | **Dampak**                             | **Pengendalian**                                                    |
|---------------------------|----------------------------------------|---------------------------------------------------------------------|
| Data tidak diperbarui     | Dashboard dan laporan tidak dipercaya. | Tampilkan waktu sinkronisasi dan status gangguan.                   |
| Akses detail terlalu luas | Kerahasiaan murid terganggu.           | Pemeriksaan server per objek dan per bagian data.                   |
| Rekonsiliasi salah        | Murid ganda atau kasus tertaut keliru. | NISN sebagai pencocokan, konflik ditahan untuk Admin IT, dan audit. |
| Integrasi belum siap      | Data sumber belum tersedia.            | Gunakan pencatatan sementara terbatas dan status data.              |
| Cakupan membesar          | Fungsi inti terlambat diuji.           | Pertahankan batas P0, P0 bertahap, dan P1.                          |

## Ketergantungan yang belum dikunci

| **Area**        | **Kondisi yang belum dikunci**                                                              | **Pemilik keputusan**       |
|-----------------|---------------------------------------------------------------------------------------------|-----------------------------|
| Integrasi       | Autentikasi API, frekuensi sinkronisasi, pemetaan rinci, dan prosedur gangguan.             | Admin IT                    |
| Istilah layanan | Nama resmi, kardinalitas, kewajiban pengisian, jenis tindak lanjut, dan status operasional. | Guru BK dan Koordinator BK  |
| Prestasi        | Verifikator, status, bukti yang boleh disimpan, dan format laporan.                         | Koordinator BK dan Waka     |
| Dokumen         | Format, ukuran, akses, pemulihan, dan prosedur penghapusan setelah batas minimum.           | Koordinator, Waka, Admin IT |
| Ekspor          | Format cetak/ekspor dan kebutuhan penandaan atau pencatatan khusus.                         | Koordinator BK dan Waka     |

# Sumber dan riwayat versi

Sumber penyusunan: kuesioner kebutuhan Aplikasi BK, contoh pencatatan berjalan, diskusi perancangan, PRD v0.5, SRS v0.3, inventaris antarmuka v0.1, serta keputusan validasi Koordinator BK/Guru BK dan Waka Kesiswaan sampai 13 Agustus 2026.

| **Versi** | **Tanggal**     | **Perubahan**                                                                                                                                                                                                                     |
|-----------|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 0.5       | 12 Agustus 2026 | Mengonsolidasikan batas produk, tata kelola akses, prioritas, dan dependensi.                                                                                                                                                     |
| 1.0       | 15 Agustus 2026 | Menetapkan Koordinator sebagai penanggung jawab operasional; akses detail Waka pada kasus terkoordinasi; histori lintas guru; NISN sementara dan rekonsiliasi; laporan gabungan; akun Admin IT; serta retensi minimum tiga tahun. |
