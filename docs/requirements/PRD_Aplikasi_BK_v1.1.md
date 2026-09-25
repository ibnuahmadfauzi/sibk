<!--
Canonical Markdown baseline for version 1.1.
Derived from PRD_Aplikasi_BK_v1.0 with approved amendments through 15 September 2026.
The version 1.0 Markdown and DOCX artifacts remain immutable archives.
-->

**DOKUMEN KEBUTUHAN PRODUK (PRD)**

**Aplikasi BK**

Baseline kebutuhan produk untuk layanan Bimbingan dan Konseling

**Versi:** 1.1

**Status:** Baseline final untuk pengembangan MVP

**Tanggal:** 23 Agustus 2026

**Amandemen disetujui:** 9, 12, 13, 14, 15, dan 24 September 2026

**Konteks:** SMKN 1 Surabaya

# Ringkasan produk

Aplikasi BK menjadi ruang kerja terpusat untuk mencatat layanan BK, memantau kasus dan tindak lanjut, menjaga kesinambungan histori murid, serta menyusun laporan dari data operasional yang sama. MVP berfokus pada Guru BK, Koordinator BK, Waka Kesiswaan, dan Admin IT.

Koordinator BK menjadi penanggung jawab operasional penggunaan aplikasi. Guru BK tetap menjadi pemilik proses layanan dan catatan profesional. Waka Kesiswaan memakai proyeksi hanya-baca untuk layanan BK dan proses keluar murid, serta menjadi pengelola data prestasi. Admin IT mengelola akun, infrastruktur, integrasi, dan data master tanpa memperoleh akses otomatis ke isi layanan BK.

Aplikasi BK tidak menggantikan e-Tatib atau Dapodik. Pelanggaran dan poin resmi tetap dikelola di e-Tatib, sedangkan identitas murid, kelas, dan tahun ajaran tetap mengacu pada Dapodik/data resmi sekolah. Ruang BK hanya menarik data sumber tanpa write-back. Data e-Tatib yang diperlukan disimpan sebagai mirror read-only agar Guru BK dapat melihat konteks pelanggaran dan penanda data baru tanpa menjadikan poin sebagai pemicu otomatis kasus.

Admin IT mengambil daftar murid melalui URL API Siswa sekali pakai pada tab Dapodik dan menyinkronkan data pelanggaran melalui tab e-Tatib pada Data Master. Browser hanya mengirim URL dan trigger; backend melakukan pengambilan, validasi, serta penyimpanan. Koneksi Dapodik langsung menunggu adapter dan kontrak provider yang disahkan.

Ketika data tahun ajaran baru belum tersedia di SIBK, Admin IT dapat menyiapkan tahun ajaran tanpa mengisi tanggal periode dan mengimpor daftar minimum seluruh murid aktif dari URL API Siswa sekali pakai atau CSV fallback berdasarkan dasar resmi sekolah. Koordinator BK melengkapi penugasan dan dapat mengaktifkan tahun ajaran setelah roster dan penugasan lengkap; setelah aktif, Guru BK dapat langsung melayani murid dalam scope penugasannya. Murid tahun sebelumnya yang belum memiliki penempatan pada tahun target ditampilkan sebagai **Perlu Konfirmasi** tanpa menebak status akademik dan tanpa menghalangi aktivasi murid lain. Data persiapan sementara selalu diberi penanda sampai diverifikasi dari sumber resmi.

# Masalah yang diselesaikan

Pencatatan BK berjalan menggunakan buku, Excel, Google Spreadsheet, WhatsApp, dan Word. Data yang tersebar memperlambat pencarian riwayat, penyusunan laporan, pemantauan tindak lanjut, serta pengendalian akses terhadap informasi sensitif.

| **Gejala**                  | **Penyebab langsung**                                            | **Kebutuhan produk**                                                             |
|-----------------------------|------------------------------------------------------------------|----------------------------------------------------------------------------------|
| Laporan terlambat           | Data dikumpulkan dari beberapa media.                            | Rekap dibentuk dari satu sumber data operasional.                                |
| Status kasus sulit dipantau | Tindak lanjut tersebar pada pencatatan lama. | Setiap kasus menampilkan seluruh tindak lanjut yang sudah dipilih dan status prosesnya. |
| Riwayat murid terputus      | Pergantian kelas atau Guru BK tidak selalu membawa catatan lama. | Guru BK yang memperoleh scope murid dapat membaca histori layanan sebelumnya.    |
| Perubahan sulit ditelusuri  | Media kerja tidak menyimpan jejak perubahan seragam.             | Perubahan penting dicatat otomatis.                                              |
| Risiko kebocoran informasi  | Batas akses belum konsisten mengikuti tanggung jawab.            | Akses dibatasi per peran, objek, kelas ampuan, tahun ajaran, dan penugasan.      |
| Data master belum tersedia  | Dapodik tahun ajaran baru dapat terlambat 2–3 bulan.             | Admin IT menyiapkan data minimum berdasarkan daftar resmi sekolah; Koordinator mengaktifkan setelah penugasan lengkap; hasil Dapodik diterapkan melalui pratinjau tanpa duplikasi. |

# Dasar kebutuhan

| **Temuan atau keputusan**                                               | **Dampak pada produk**                                                                                  |
|-------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------|
| Pencarian riwayat satu murid memerlukan 11–20 menit.                    | Pencarian profil dan histori menjadi alur utama.                                                        |
| Penyusunan laporan memerlukan 30–60 menit.                              | Laporan inti harus tersedia tanpa menggabungkan file manual.                                            |
| Terdapat tujuh Guru BK pada kondisi operasional saat ini.               | Koordinator dapat merekap seluruh Guru BK aktif; jumlah tidak ditanam tetap dalam kode.                 |
| Kerahasiaan konsultasi merupakan kebutuhan tertinggi.                   | Pemisahan detail kasus, ringkasan umum, catatan internal, dan konsultasi sensitif diterapkan sejak MVP. |
| Waka perlu memantau murid yang memiliki kasus dan hasil penanganannya. | Proyeksi detail kasus dan konsultasi tersedia hanya-baca serta diaudit. |
| Pergantian Guru BK tidak boleh memutus histori layanan murid.           | Hak baca histori mengikuti scope murid aktif sampai murid lulus.                                        |

# Visi dan tujuan

## Visi produk

Menyediakan ruang kerja digital BK yang aman, sederhana, dan terpusat untuk menghubungkan informasi murid, konteks pelanggaran, proses penanganan, tindak lanjut, dan laporan sesuai kewenangan setiap pengguna.

## Tujuan produk

- Memusatkan riwayat kasus, layanan, konsultasi, dan tindak lanjut pada profil murid.

- Menjaga kesinambungan histori ketika kelas, tahun ajaran, atau Guru BK berubah.

- Menghubungkan data e-Tatib tanpa mencatat ulang atau mengubah pelanggaran dan poin resmi.

- Mendukung penanganan kasus dari informasi awal sampai penyelesaian dan pemantauan Waka hanya-baca.

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
| Guru BK        | Mengelola layanan untuk kelas ampuan dan kasus yang menjadi tanggung jawabnya; membaca histori murid dalam scope aktif; serta mencatat rencana proses keluar murid. | Tidak mengakses murid di luar tanggung jawabnya dan tidak mengubah atau mengarsipkan catatan profesional milik Guru BK sebelumnya. |
| Koordinator BK | Penanggung jawab operasional; mengatur pembagian Guru BK, keputusan akhir proses keluar, aktivasi tahun ajaran setelah penugasan lengkap, serta rekap gabungan seluruh Guru BK aktif. | Jabatan koordinator tidak otomatis membuka konsultasi sensitif atau mengubah catatan profesional Guru BK. Jika merangkap Guru BK, akses sensitif tetap mengikuti scope Guru BK. |
| Waka Kesiswaan | Memantau kondisi layanan BK tingkat sekolah, membaca proses keluar murid, serta mengelola data prestasi murid melalui input manual atau impor Excel. | Layanan BK dan proses keluar hanya-baca melalui proyeksi allowlist yang diaudit; hak mutasi hanya berlaku pada prestasi. Tidak mengakses payload provider mentah, dokumen sensitif, kode kasus, atau NISN pada proyeksi layanan BK. |
| Admin IT       | Mengelola akun dan password sementara, infrastruktur, konfigurasi koneksi PG-501, tahun ajaran, roster murid melalui Excel/API Dapodik, sinkronisasi, pratinjau pencocokan, rekonsiliasi identitas, dan kesalahan master melalui sumber resmi. | Hak teknis tidak otomatis memberi akses ke isi kasus, layanan, konsultasi, atau proses keluar; credential dan password tidak ditampilkan kembali. |
| Wali kelas     | Pengguna tahap P1 untuk informasi terbatas pada kelasnya.                                                                                       | Batas informasi ditetapkan sebelum P1 dibangun.                                                                                                                 |
| Murid          | Pengguna tahap P1 untuk informasi miliknya.                                                                                                     | Tidak melihat data murid lain atau informasi yang dibatasi.                                                                                                     |

- Saat baseline disusun terdapat tujuh Guru BK; sistem menghitung pengguna aktif dan tidak menanam jumlah tersebut dalam kode.

- Rolling atau perubahan pembagian dua tahunan hanya dicatat Koordinator BK berdasarkan keputusan resmi dan tidak dijalankan otomatis oleh sistem.

- Kasus baru memperoleh satu owner saat dibuat. Pergantian penugasan kelas tidak memindahkan kasus aktif. Bantuan Guru BK lain dilakukan di luar aplikasi dan pencatatan resmi tetap dilakukan Guru BK pengampu.

- Hak membaca histori murid tidak memberi hak mengubah catatan layanan lama.

# Cakupan produk

## P0 – MVP

| **Area**                       | **Cakupan**                                                                                            | **Urutan**  |
|--------------------------------|--------------------------------------------------------------------------------------------------------|-------------|
| Akses dan akun                 | Login, akun aktif, password sementara unik, wajib ganti password, pengelolaan akun oleh Admin IT, dan pembatasan akses per peran serta objek. | Inti        |
| Penugasan                      | Daftar per Guru BK memuat kelas dan jumlah murid aktif. Koordinator menambah kelas kosong atau membatalkan penugasan setelah konfirmasi; perubahan state diaudit. | Inti        |
| Master murid dan roster        | Tahun ajaran tanpa tanggal wajib; roster murid melalui impor Excel atau tarik API Dapodik/data siswa; pratinjau pencocokan NISN; serta identitas sementara ketika layanan harus dicatat sebelum sinkronisasi. | Inti        |
| Proses keluar murid            | Satu proses per murid, pencatatan awal oleh Guru BK, keputusan akhir Koordinator, dan akses baca Waka. | Inti        |
| Kasus dan tindak lanjut        | Pembuatan kasus dengan Latar Belakang Masalah dan Penanganan, nol atau lebih tindak lanjut unik per jenis, serta penyelesaian dengan Hasil / Ringkasan. | Inti        |
| Konteks e-Tatib                | Sinkronisasi mirror read-only, Cek Poin Pelanggaran berdasarkan NISN, penautan pelanggaran, dan penanda data baru tanpa write-back atau pemicu kasus otomatis. | Inti        |
| Integrasi sumber data          | PG-501 memuat impor URL API Siswa/CSV dan sinkronisasi e-Tatib; koneksi Dapodik langsung hanya tersedia setelah adapter dan kontrak disahkan. | Inti        |
| Konsultasi minimum             | Record mandiri terhubung murid berisi tanggal, jenis layanan, Latar Belakang Masalah, dan Penanganan; Hasil / Ringkasan dapat ditambahkan kemudian. | Inti        |
| Data dan histori murid         | Profil, e-Tatib, kasus, layanan, konsultasi, tindak lanjut, serta histori lintas kelas dan Guru BK.    | Inti        |
| Dashboard dan laporan          | Pemantauan, rekap per scope Guru BK, rekap gabungan Koordinator, dan laporan Waka yang diizinkan.      | Inti        |
| Akses Waka                     | Portal khusus berisi Dashboard, Murid dengan Kasus, dan Laporan; seluruh detail kasus dan konsultasi memakai proyeksi hanya-baca yang diaudit. | Inti        |
| Audit                          | Jejak perubahan append-only tanpa halaman pembaca umum; satu simpan resmi mencatat hanya field yang berubah. | Inti        |
| Retensi                        | Data operasional tidak dihapus sebelum tersimpan minimum tiga tahun; draf Persiapan yang belum aktif dapat dibatalkan sesuai pemeriksaan relasi. Penghapusan otomatis belum diaktifkan. | Inti        |
| Prestasi                       | Pengelolaan oleh Waka melalui input manual atau impor Excel; Guru BK membaca sesuai scope sebagai konteks layanan. Tidak memakai bukti atau workflow verifikasi. | P0 bertahap |

Prestasi tetap termasuk P0, tetapi dikerjakan setelah fungsi kasus, tindak lanjut, laporan, dan pengendalian akses inti stabil.

Konfigurasi koneksi langsung dapat disimpan sebelum kontrak provider tersedia, tetapi uji, aktivasi, dan sinkronisasinya tetap gagal tertutup sampai dokumentasi serta adapter resmi lolos admission kontrak. Impor URL API Siswa sekali pakai mengikuti validasi tersendiri.

## P1 – Setelah MVP tervalidasi

- Akun wali kelas dengan informasi terbatas sesuai kewenangan yang disahkan.

- Akun murid untuk melihat data miliknya dan melakukan koreksi data miliknya.

- Alur koreksi oleh murid yang lebih lengkap.

- Notifikasi lanjutan di luar pengingat operasional minimum.

- Pengelolaan dokumen lanjutan setelah format, akses, dan prosedur retensi disahkan.

## Di luar cakupan MVP

- Menggantikan e-Tatib, membangun mesin poin, atau mengubah pelanggaran dan poin resmi.

- Diagnosis, rekomendasi konseling otomatis, SPK, mesin aturan, threshold poin, atau peringatan otomatis yang menentukan bahwa murid harus menjadi kasus BK. Penanda informatif adanya data e-Tatib baru tetap diperbolehkan.

- Portal orang tua, sanggahan lengkap, dan pengajuan perubahan poin.

- Administrasi kehadiran dan otomatisasi pembuatan atau distribusi surat.

- Pemindahan otomatis kasus aktif atau rolling otomatis pembagian Guru BK.

- Penghapusan otomatis sebelum prosedur retensi dan pemulihan disahkan.

# Aturan produk utama

| **Area**            | **Aturan**                                                                                                                                      |
|---------------------|-------------------------------------------------------------------------------------------------------------------------------------------------|
| e-Tatib             | Sumber resmi pelanggaran dan poin. Aplikasi BK membaca melalui API dan tidak melakukan write-back pada MVP.                                     |
| Dapodik             | Sumber resmi identitas murid, kelas, keanggotaan kelas, dan tahun ajaran.                                                                       |
| Data persiapan sementara | Admin IT menyiapkan tahun ajaran, rombel, dan daftar minimum `nisn,nama,rombel,tahun_pelajaran` dari URL API Siswa sekali pakai atau CSV fallback. Satu impor dapat memuat beberapa tahun sementara yang sudah dibuat. Data diberi penanda sampai diverifikasi dari sumber resmi. |
| Pembatalan persiapan | Admin IT dapat membatalkan tahun Persiapan yang belum pernah aktif beserta roster dan penugasannya, selama belum ada layanan atau data terverifikasi. Data murid yang dipakai tahun lain tetap disimpan. |
| Aktivasi operasional | Koordinator BK mengaktifkan tahun ajaran setelah data dan penugasan lengkap. Guru BK memperoleh scope murid berdasarkan tahun ajaran aktif dan penugasan, terlepas dari asal data. Provider tidak dapat mengubah status aktif. |
| Pergantian tahun ajaran | Sistem tidak menaikkan kelas atau menetapkan status akademik otomatis. Impor daftar target membuat histori penempatan baru berdasarkan NISN exact; murid lama tanpa penempatan target hanya ditandai **Perlu Konfirmasi** secara read-only dan tidak memblokir aktivasi keseluruhan. |
| Pratinjau pencocokan | Tarik Dapodik hanya menyiapkan hasil cocok, baru, berubah, dan konflik. Cache operasional berubah setelah konfirmasi Admin IT; pencocokan otomatis murid hanya melalui NISN exact. |
| Konflik identitas sementara | NISN exact tetap menjadi kunci. Nama berbeda tidak membuat murid baru; konflik kandidat NISN/identitas sumber ditahan untuk Admin IT dan tidak mengubah relasi sampai data resmi memastikan target. |
| Alur koneksi        | Tab Dapodik menerima URL API Siswa sekali pakai atau CSV; tab e-Tatib menangani pratinjau dan sinkronisasi. Panel koneksi Dapodik langsung baru ditampilkan setelah adapter tersedia. |
| Pemrosesan integrasi | Browser hanya mengirim konfigurasi dan trigger; fetch, mapping, validasi snapshot, dan penyimpanan dilakukan backend secara server-to-server. |
| Credential sumber  | API e-Tatib publik tidak memakai token. Admin IT memakai URL sekali pakai yang tidak disimpan; koneksi hanya membaca data dan tidak melakukan write-back. |
| Identitas sumber   | Probe dan setiap sinkronisasi harus membuktikan identitas sekolah/sumber yang dilaporkan cocok dengan nilai yang diharapkan.                   |
| Endpoint outbound  | Origin harus cocok exact dengan allowlist deployment. Metadata/link-local/multicast/unspecified selalu ditolak; private/loopback hanya diizinkan bila origin exact terdaftar dan flag opt-in private-network provider aktif. Redirect, proxy tak tepercaya, TLS invalid, DNS rebinding, dan origin drift ditolak secara fail-closed; koneksi diikat ke alamat tervalidasi dengan Host/SNI tetap benar. |
| Kontrak adapter    | `driver_id`, `adapter_version`, dan `contract_version` disimpan terpisah. Digest kebijakan endpoint mencakup versi policy, exact origins yang dikanonisasi, dan flag private-network provider; driver production tetap tidak tersedia sampai kontrak resmi disahkan. |
| Snapshot internal  | Adapter hanya memetakan field resmi yang diperlukan ke snapshot internal. Raw payload tidak disimpan secara default, dan evidence serta validator executable harus membuktikan identitas, schema, pagination, ukuran, dan full/partial semantics sebelum import. |
| Konsistensi operasi | Operasi per provider diserialisasi, memakai fencing token dan hard deadline di bawah masa lease, serta mempertahankan data lama saat gagal atau hasil menjadi stale. |
| Identitas sementara | Hanya dibuat saat ada kasus/layanan sebelum sinkronisasi, memakai NISN dan nama, lalu dicocokkan tanpa duplikasi.                               |
| Kesinambungan data  | Penerapan Dapodik menempelkan identitas sumber pada ID internal yang sama; kasus, konsultasi, prestasi, penugasan, dan histori BK tidak dibuat ulang atau ditimpa. |
| Arah integrasi      | Ruang BK hanya membaca Dapodik dan e-Tatib; data persiapan, layanan BK, serta hasil koreksi tidak ditulis kembali ke provider.                  |
| Nama resmi          | Pencocokan otomatis e-Tatib mewajibkan NISN canonical dan nama ternormalisasi sama. Perbedaan menahan penautan dan tidak menimpa nama master; Admin IT dapat memilih relasi lokal secara manual untuk pasangan NISN+nama sumber dengan audit. |
| Kewenangan          | Daftar, pencarian, detail, dashboard, laporan, ekspor, URL, dan API menerapkan batas yang sama.                                                 |
| Histori murid       | Guru BK dengan scope aktif dapat membaca histori layanan/konsultasi murid lintas kelas dan pergantian guru, tetapi tidak mengubah catatan lama. |
| Pelayanan BK        | Pencatatan awal kasus dan konsultasi memakai **Latar Belakang Masalah** serta **Penanganan**. Kasus baru berstatus **Sedang Proses**; penambahan minimal satu tindak lanjut mengubahnya menjadi **Tindak Lanjut**; penyelesaian mewajibkan **Hasil / Ringkasan** dan mengubah status menjadi **Selesai**. Kasus dapat selesai tanpa tindak lanjut; konsultasi tidak memiliki status dan Hasil / Ringkasan dapat diisi kemudian. |
| Kode kasus          | Kode kasus tetap dibuat untuk kebutuhan internal, relasi, audit teknis, dan integritas data, tetapi tidak ditampilkan pada UI, pencarian pengguna, laporan, ekspor, dashboard, atau audit yang terlihat pengguna. |
| Waka                | Waka aktif membaca proyeksi detail kasus dan konsultasi yang disetujui, tanpa aksi buat, ubah, selesai, tindak lanjut, atau arsip. Koordinasi berlangsung di luar aplikasi dan tidak dicatat sebagai fitur/data layanan. |
| Prestasi            | Waka Kesiswaan menjadi pemilik pengelolaan prestasi melalui input manual atau impor Excel. Guru BK hanya membaca prestasi murid dalam scope sebagai konteks; prestasi tidak memiliki bukti/verifikasi dan tidak otomatis memengaruhi lifecycle layanan BK. |
| Autosave dan konflik | Form tambah/edit data bisnis menyimpan draft lokal per pengguna/form/record maksimal 24 jam tanpa mengirim atau mengaudit draft. Simpan resmi memakai `updated_at` dan menolak konflik agar perubahan tidak saling menimpa. |
| Daftar layanan      | Daftar kasus dan konsultasi hanya menerima search/filter/sorting yang berada pada allowlist server dengan arah `asc`/`desc` dan tie-breaker ID. |
| Penugasan           | Membership murid dan penugasan Guru BK berbasis tahun ajaran tanpa periode tanggal. Perubahan state mencatat nilai sebelum/sesudah pada audit. Kasus baru memperoleh satu owner tetap saat dibuat; aplikasi tidak menyediakan pengalihan owner dan pergantian Guru BK kelas tidak memindahkan owner kasus. |
| Histori konteks     | Record layanan yang relevan menyimpan snapshot tahun ajaran dan kelas saat dicatat agar histori tidak berubah ketika membership atau penugasan berubah. |
| Edit dan arsip      | Tombol Hapus mengarsipkan kasus/konsultasi dengan soft delete. Kesalahan data master dikoordinasikan di luar aplikasi, diperbaiki pada sumber resmi, lalu masuk melalui sinkronisasi atau rekonsiliasi. |
| Proses keluar murid | Pencatatan awal selalu `dalam_proses`; hanya Koordinator menetapkan `batal` atau `resmi_keluar`. Hanya `resmi_keluar` dengan tanggal efektif yang menghentikan layanan baru dan memulai retensi. Provider tidak menentukan status ini. |
| Audit               | Setiap simpan resmi mencatat actor, waktu, tipe/ID record, serta nilai sebelum/sesudah hanya untuk field yang berubah. Pembacaan detail Waka dicatat; draft lokal tidak membuat audit. |
| Retensi             | Kasus, layanan, konsultasi, prestasi, dan audit disimpan minimum tiga tahun; prosedur penghapusan tetap memerlukan kebijakan operasional.       |

# Arsitektur informasi dan laporan

Navigasi utama Guru BK terdiri atas Dashboard, Layanan BK, Data Murid, dan Laporan. Guru BK, Koordinator BK, dan Waka memakai halaman Laporan yang sama secara visual berupa daftar catatan kasus dan konsultasi sesuai scope. Waka tetap memakai navigasi khusus Dashboard, Murid dengan Kasus, Prestasi, Laporan, dan Akun Saya, tetapi tidak mempunyai portal laporan bertab terpisah. Proses keluar murid tersedia read-only bagi Waka. Penugasan tersedia sesuai peran Koordinator, sedangkan pengelolaan akun, rekonsiliasi identitas, data master, dan sinkronisasi tersedia bagi Admin IT. Dapodik dan e-Tatib tetap menjadi sistem sumber, bukan modul navigasi utama. Koreksi Data, Notifikasi, dan Riwayat Perubahan tidak menjadi menu atau halaman MVP.

| **Area**            | **Fungsi**                                                                                                |
|---------------------|-----------------------------------------------------------------------------------------------------------|
| Dashboard           | Konteks operasional sesuai peran tanpa daftar audit; Dashboard Waka menampilkan empat metric kasus, daftar perhatian, komposisi status, penanganan terbaru, dan akses baca proses keluar dari proyeksi aman seluruh sekolah. |
| Layanan BK          | Daftar kasus, pembuatan kasus/konsultasi dengan dua narasi utama, penambahan beberapa tindak lanjut per kasus, dan penyelesaian dengan Hasil / Ringkasan. |
| Data Murid          | Profil, histori kelas lintas tahun, mirror e-Tatib beserta penanda data baru, kasus, konsultasi, tindak lanjut, dan prestasi yang diizinkan. |
| Laporan             | Guru BK dan Koordinator memakai daftar catatan kasus/konsultasi dan preview rekap sesuai scope. Waka memakai daftar yang sama dalam mode hanya-baca tanpa preview, cetak, unduhan, atau aksi. |
| Prestasi            | Waka mengelola prestasi manual/Excel; Guru BK membaca prestasi sesuai scope dari profil murid. |
| Penugasan           | Pembagian Guru BK per kelas dan tahun ajaran, perubahan state yang diaudit, serta owner kasus tetap sejak pembuatan. |
| Administrasi teknis | Akun, infrastruktur, konfigurasi koneksi PG-501, status sinkronisasi, kesalahan pemetaan, dan rekonsiliasi identitas. |

## Laporan P0

| **Daftar laporan** | **Filter utama** | **Cakupan peran** |
|---|---|---|
| Catatan layanan BK | Tahun ajaran, kelas, dan jenis layanan `Semua`, `Catatan Kasus`, atau `Catatan Konsultasi` | Guru BK: scope profesional dan kasus yang menjadi tanggung jawabnya; Koordinator: gabungan yang diizinkan; Waka: seluruh sekolah dalam proyeksi hanya-baca. |

Setiap baris mewakili satu catatan. Tabel Guru BK dan Koordinator menampilkan No, Hari/Tanggal, Nama & Kelas, Layanan/Jenis Masalah, Hasil / Ringkasan, serta Aksi. Satu ikon kaca pembesar membuka baris detail Latar Belakang Masalah dan Penanganan; Hasil / Ringkasan tidak diulang pada baris detail. Klik baris tidak membuka modal dan cetak per catatan tidak ditampilkan. Hapus tetap berarti archive/soft delete dan hanya tersedia bila policy objek mengizinkan. Kelas yang dapat dipilih wajib berasal dari tahun ajaran terpilih dan scope pengguna.

Header tabel menampilkan ringkasan seluruh hasil filter, bukan hanya halaman aktif. Ringkasan selalu memuat Total Catatan. Permasalahan disembunyikan ketika filter hanya Konsultasi, dan Konsultasi disembunyikan ketika filter hanya Permasalahan. Preview cetak/PDF dan Excel menyajikan angka tersebut sebagai kalimat lengkap yang menjelaskan total serta komposisi hasil filter.

Halaman utama menampilkan 10 catatan terbaru secara default dan menyediakan pilihan 10, 25, 50, atau 100 data per halaman. Pagination hanya berlaku pada UI. Satu tombol **Cetak / Unduh Rekap** membuka preview rekap A4 portrait; dari sana pengguna dapat mengunduh Excel atau mencetak/menyimpan PDF. Preview dan unduhan selalu memakai seluruh dataset terscope dari tanggal layanan paling awal. Tabel dokumen putih polos memuat No, Hari/Tanggal, Nama/Kelas, Jenis Masalah, Hasil / Ringkasan dari `resolution_summary|result`, Guru BK, dan Keterangan. Nilai Hasil / Ringkasan yang belum tersedia ditampilkan `—`. Keterangan Permasalahan memuat Sumber dan tindak lanjut yang tercatat; Keterangan Konsultasi memakai `—`. Guru BK memakai pemilik kasus atau pencatat konsultasi. Jam tidak ditampilkan karena waktu layanan tidak disimpan.

Preview rekap menyediakan tanda tangan Koordinator BK serta Waka Kesiswaan. Dokumen satu kasus memakai Guru BK pemilik kasus dan Waka; konsultasi memakai Guru BK pencatat pada `counselor_id` dan Waka. Nama bersumber dari akun aktif/penugasan, bukan pengguna yang sedang login. Jika sumber Koordinator atau Waka kosong maupun ganda, dokumen menampilkan bahwa penandatangan belum tersedia. NIP dan Kepala Sekolah tidak ditampilkan sampai tersedia sumber data resmi; Excel tidak memuat tanda tangan. Kop dan blok tanda tangan memakai partial reusable agar template resmi dapat disesuaikan tanpa mengubah kontrak data.

Implementasi memakai Eloquent, Form Request, Blade, Bootstrap, SCSS, JavaScript ringan existing, Laravel Pagination, dan PhpSpreadsheet untuk `.xlsx`. Unduhan Word tidak disediakan.

Laporan pelanggaran, poin, tindak lanjut, dan prestasi tidak menjadi menu laporan terpisah bagi Waka.

Waka melihat daftar layanan dengan filter, ringkasan, urutan, dan pagination yang sama seperti Koordinator. Kolomnya dibatasi ke **No**, **Hari/Tanggal**, **Nama/Kelas**, **Jenis Masalah**, **Hasil / Ringkasan**, **Guru BK**, dan **Keterangan**. Hasil / Ringkasan memakai `resolution_summary|result` dan dapat `—` bila belum tersedia; latar belakang masalah, penanganan, hasil terpisah, aksi, preview, cetak/PDF, dan ekspor tidak tersedia. Waka tidak menerima kode kasus, NISN, catatan internal, payload provider mentah, atau dokumen sensitif.

# Risiko dan ketergantungan

## Risiko utama

| **Risiko**                | **Dampak**                             | **Pengendalian**                                                    |
|---------------------------|----------------------------------------|---------------------------------------------------------------------|
| Data tidak diperbarui     | Dashboard dan laporan tidak dipercaya. | Tampilkan waktu sinkronisasi dan status gangguan.                   |
| Akses detail terlalu luas | Kerahasiaan murid terganggu.           | Pemeriksaan server per objek dan per bagian data.                   |
| Rekonsiliasi salah        | Murid ganda atau kasus tertaut keliru. | NISN sebagai pencocokan, konflik ditahan untuk Admin IT, dan audit. |
| Integrasi belum siap      | Data sumber belum tersedia.            | Gunakan pencatatan sementara terbatas dan status data.              |
| Keterlambatan Dapodik     | Tahun ajaran baru belum siap selama 2–3 bulan. | Gunakan data persiapan sementara dari dasar resmi sekolah, aktivasi Koordinator, penanda asal data, dan pratinjau sebelum penerapan Dapodik. |
| Cakupan membesar          | Fungsi inti terlambat diuji.           | Pertahankan batas P0, P0 bertahap, dan P1.                          |
| Credential bocor atau berhak tulis | Rahasia terungkap atau sumber resmi berubah. | Enkripsi Laravel, redaksi menyeluruh, step-up Admin IT, dan bukti credential least-privilege/read-only. |
| SSRF atau DNS rebinding | Credential terkirim ke tujuan yang salah atau jaringan internal disalahgunakan. | Exact origin allowlist; metadata/link-local/multicast/unspecified selalu ditolak; private/loopback memerlukan origin exact dan opt-in provider; setiap resolusi diperiksa, koneksi diikat ke alamat tervalidasi, redirect/proxy tak tepercaya dimatikan, dan TLS wajib valid. |
| Identitas sekolah salah | Data provider lain masuk ke cache sekolah. | Expected source identifier wajib dan diverifikasi pada probe serta setiap sinkronisasi. |
| Verifikasi menjadi stale | Konfigurasi atau policy berubah setelah uji. | Cocokkan configuration version, driver ID, adapter version, contract version, dan endpoint-policy digest sebelum aktivasi serta penggunaan. |
| Snapshot malformed atau tidak terbukti penuh | Data lama dapat dinonaktifkan secara keliru. | Evidence snapshot dan validator executable menolak schema, nullability, identitas, atau completeness yang tidak terbukti sebelum transaksi. |
| Payload atau pagination berlebih | Memori, waktu proses, dan layanan aplikasi terganggu. | Kontrak wajib menetapkan batas response/page, pagination, timeout, retry/backoff, rate limit, concurrency, backpressure, dan kebutuhan queue. |
| Sinkronisasi duplikat atau lease kedaluwarsa | Dua proses menulis hasil bersamaan atau proses lama tetap menulis. | Lock per provider, rate limit, fencing token persisten, recheck row-lock sebelum write, dan hard operation deadline. |

## Ketergantungan yang belum dikunci

| **Area**        | **Kondisi yang belum dikunci**                                                              | **Pemilik keputusan**       |
|-----------------|---------------------------------------------------------------------------------------------|-----------------------------|
| Integrasi       | Autentikasi, origin, identitas sumber, endpoint/method, schema, pagination, full/partial dan deletion semantics, timezone, batas payload/page, rate limit, retry/backoff, timeout, concurrency/backpressure, TLS/proxy/jaringan, fixture sintetis, dan prosedur gangguan belum disahkan. Driver production tetap tidak tersedia sampai admission kontrak selesai. | Admin IT dan pemilik provider |
| Istilah layanan | Daftar nilai referensi/label final yang belum ditetapkan secara operasional di sekolah. | Guru BK dan Koordinator BK  |
| Dokumen         | Format, ukuran, akses, pemulihan, dan prosedur penghapusan setelah batas minimum.           | Koordinator, Waka, Admin IT |
| Ekspor          | Format cetak/ekspor dan kebutuhan penandaan atau pencatatan khusus.                         | Koordinator BK dan Waka     |

# Sumber dan riwayat versi

Sumber penyusunan: kuesioner kebutuhan Aplikasi BK, contoh pencatatan berjalan, diskusi perancangan, PRD v0.5, SRS v0.3, inventaris antarmuka v0.1, keputusan validasi Koordinator BK/Guru BK dan Waka Kesiswaan sampai 13 Agustus 2026, keputusan arsitektur fondasi konfigurasi integrasi tanggal 23 Agustus 2026, amandemen keterlambatan Dapodik yang disetujui 9 September 2026, keputusan alur operasional BK yang disetujui 12 September 2026, penyederhanaan Portal Waka berbasis tujuan 13 September 2026, laporan tiga tab 14 September 2026, penyederhanaan 15 September 2026, Revisi SIBK 3.2 yang disetujui 17 September 2026, serta revisi laporan catatan layanan 21 sampai 23 September 2026.

| **Versi** | **Tanggal**     | **Perubahan**                                                                                                                                                                                                                     |
|-----------|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 0.5       | 12 Agustus 2026 | Mengonsolidasikan batas produk, tata kelola akses, prioritas, dan dependensi.                                                                                                                                                     |
| 1.0       | 15 Agustus 2026 | Menetapkan Koordinator sebagai penanggung jawab operasional; akses detail Waka pada kasus terkoordinasi; histori lintas guru; NISN sementara dan rekonsiliasi; laporan gabungan; akun Admin IT; serta retensi minimum tiga tahun. |
| 1.1       | 23 Agustus 2026; diamandemen 9, 12, 13, 14, dan 15 September 2026 | Menetapkan konfigurasi koneksi aman melalui PG-501, fallback data persiapan, empat status pelayanan, edit terminal beralasan, arsip layanan, proses keluar murid, password sementara, audit tanpa UI pembaca, panel dashboard role-aware, Portal Waka berbasis tujuan, serta tiga tab rekap laporan Guru BK/Koordinator. |
| 1.1       | 17 September 2026 | Revisi SIBK 3.2: tiga status kasus, satu tindak lanjut terkini, konsultasi mandiri, detail Waka hanya-baca, audit perubahan-delta, autosave lokal, sorting allowlist, dan optimistic concurrency. |
| 1.1       | 21 September 2026 | Mengganti tiga tab rekap Guru BK/Koordinator dengan daftar catatan kasus/konsultasi, preview rekap dan per catatan, cetak/PDF browser, serta unduhan Excel dan Word kompatibel. |
| 1.1       | 22 September 2026 | Menetapkan istilah Penanganan, preview rekap landscape dan individual portrait, seluruh dataset dokumen tanpa pagination UI, validasi kelas per tahun ajaran, serta tanda tangan Koordinator/Guru BK dan Waka tanpa NIP. |
| 1.1       | 23 September 2026 | Menyatukan UI laporan Waka dengan halaman laporan operasional dalam mode hanya-baca, membatasi kolom ke struktur dokumen Koordinator, serta menolak preview/cetak/unduhan Waka. |
| 1.1       | 22 September 2026 | Merevisi daftar dengan Hari/Tanggal, Layanan/Jenis Masalah, disclosure narasi dan Hasil Layanan, serta menghapus akses modal/cetak individual dari halaman laporan. |
| 1.1       | 22 September 2026 | Menyederhanakan dokumen rekap menjadi enam kolom putih polos dan menghapus unduhan Word. |
| 1.1       | 23 September 2026 | Mengubah rekap menjadi A4 portrait dan menambahkan kolom Guru BK sebelum Keterangan. |
| 1.1       | 23 September 2026 | Menampilkan Hasil pada daftar Guru BK/Koordinator, menyatukan detail Latar Belakang Masalah dan Penanganan pada satu kontrol, serta menambahkan Sumber dan Tindak Lanjut terbaru ke Keterangan dokumen. |
| 1.1       | 24 September 2026 | Menyelaraskan roster Excel/API Dapodik, aktivasi tahun ajaran tanpa tanggal, membership/penugasan berbasis tahun tanpa periode tanggal, owner kasus tetap, snapshot konteks layanan, penghapusan dasar keputusan penugasan, mirror read-only e-Tatib dan penanda data baru, narasi layanan, multi-tindak-lanjut, penyelesaian Hasil / Ringkasan, serta pengelolaan prestasi oleh Waka. |
