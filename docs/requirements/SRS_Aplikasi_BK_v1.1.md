<!--
Canonical Markdown baseline for version 1.1.
Derived from SRS_Aplikasi_BK_v1.0 with approved amendments through 15 September 2026.
The version 1.0 Markdown and DOCX artifacts remain immutable archives.
-->

**SPESIFIKASI KEBUTUHAN PERANGKAT LUNAK (SRS)**

**Aplikasi BK**

Baseline spesifikasi MVP layanan Bimbingan dan Konseling

**Versi:** 1.1

**Status:** Baseline final untuk pengembangan MVP

**Tanggal:** 23 Agustus 2026

**Amandemen disetujui:** 9, 12, 13, 14, 15, dan 24 September 2026

**Konteks:** Acuan produk: PRD Aplikasi BK v1.1

# Tujuan dan ruang lingkup

Dokumen ini menetapkan fungsi, batas akses, data, integrasi, keamanan, dan kriteria penerimaan Aplikasi BK. Spesifikasi mencakup MVP untuk Guru BK, Koordinator BK, Waka Kesiswaan, dan Admin IT. Wali kelas dan murid berada pada tahap P1.

Kebutuhan P0 wajib tersedia pada MVP. P0 bertahap tetap termasuk MVP, tetapi dikerjakan setelah fungsi inti stabil. Kata ‘harus’ menyatakan perilaku yang wajib dipenuhi.

# Batas sistem

| **Komponen**   | **Peran**                     | **Batas**                                                                                                                  |
|----------------|-------------------------------|----------------------------------------------------------------------------------------------------------------------------|
| Dapodik        | Sumber data master            | Identitas murid, kelas, keanggotaan kelas, dan tahun ajaran melalui mekanisme resmi.                                       |
| e-Tatib        | Sumber pelanggaran dan poin   | Dibaca melalui API; Aplikasi BK tidak membuat atau mengubah transaksi resmi.                                               |
| Aplikasi BK    | Ruang kerja layanan BK        | Kasus, konsultasi, beberapa tindak lanjut per kasus, penyelesaian, histori murid, laporan, audit, dan prestasi yang dikelola Waka. |
| Data sementara | Fallback sebelum sinkronisasi | NISN dan nama hanya saat kasus/layanan muncul; bukan master alternatif.                                                    |
| Konfigurasi integrasi | Fondasi koneksi teknis | Admin IT menyimpan endpoint, identitas sumber, credential, timeout, status verifikasi, dan versi secara aman; driver production belum tersedia sebelum admission kontrak. |
| Pengguna P1    | Wali kelas dan murid          | Struktur peran dapat disiapkan, tetapi antarmuka dan alurnya tidak dibangun pada P0.                                       |

# Hak akses dan tata kelola

| **Objek/tindakan**  | **Guru BK**                                           | **Koordinator BK**                                    | **Waka Kesiswaan**                                  | **Admin IT**                   |
|---------------------|-------------------------------------------------------|-------------------------------------------------------|-----------------------------------------------------|--------------------------------|
| Daftar/profil murid | Scope aktif dan kasus yang menjadi tanggung jawabnya; termasuk histori murid. | Sesuai scope Guru BK/penugasan. | Proyeksi portal hanya-baca sesuai allowlist. | Master untuk tugas teknis. |
| Kasus BK            | Buat, baca, ubah sebagai owner kasus.                 | Baca sesuai kewenangan; tidak mengalihkan owner kasus. | Proyeksi detail seluruh kasus/konsultasi hanya-baca. | Tidak otomatis. |
| Konsultasi sensitif | Baca bila murid berada dalam scope profesional yang sah. | Tidak otomatis di luar scope Guru BK.                 | Tidak otomatis; isi lengkap dikecualikan.           | Tidak.                         |
| Penugasan           | Lihat penugasannya.                                   | Buat/ubah berdasarkan keputusan resmi.                | Lihat ringkasan tata kelola.                        | Dukungan teknis.               |
| Edit/arsip layanan  | Hanya sebagai pemilik catatan yang masih berwenang.    | Tidak mengubah catatan profesional milik Guru BK.     | Tidak.                                              | Tidak.                         |
| Proses keluar murid | Catat rencana untuk murid dalam scope.                 | Putuskan batal atau resmi keluar.                     | Baca daftar/detail operasional tanpa mutasi.        | Tidak membaca isi layanan BK.  |
| Kesalahan master    | Koordinasi di luar aplikasi.                           | Koordinasi di luar aplikasi.                          | Tidak.                                              | Proses melalui sumber resmi.   |
| Laporan/cetak       | Sesuai scope sendiri.                                 | Gabungan seluruh Guru BK aktif.                       | Ringkasan penanganan seluruh kasus dari field aman. | Tidak otomatis.                |
| Akun/infrastruktur  | Lihat akun sendiri.                                   | Pantau operasional.                                   | Tidak mengelola.                                    | Kelola akun dan infrastruktur. |
| Konfigurasi koneksi | Tidak.                                                | Tidak.                                                | Tidak.                                              | Gunakan tab Dapodik untuk impor URL API Siswa dan tab e-Tatib untuk sinkronisasi, tanpa membuka isi layanan BK. |
| Prestasi            | Baca murid dalam scope profesional.                    | Membaca prestasi seluruh murid; tidak mengelola prestasi. | Buat, baca, ubah, dan impor Excel.                  | Tidak mengelola isi prestasi. |

- Koordinator BK menjadi penanggung jawab operasional Aplikasi BK.

- Pada Layanan BK, daftar dan detail Permasalahan/Konsultasi Koordinator mengikuti scope Guru BK yang sah, termasuk saat rangkap peran; tanpa role Guru BK tidak tersedia catatan layanan pribadi. Pembatasan berlaku pula pada histori layanan di profil murid dan URL detail langsung. Khusus halaman Laporan beserta pratinjau/cetak/ekspornya, Koordinator membaca seluruh murid tanpa batas penugasan Guru BK. Tab Pengunduran Diri tetap menampilkan seluruh catatan untuk Koordinator.

- Waka aktif memperoleh proyeksi detail seluruh kasus dan konsultasi yang disetujui dalam mode hanya-baca dan diaudit. Hak mutasi Waka hanya berlaku pada modul prestasi sesuai `ACH-*`.

- Guru BK yang memperoleh scope aktif atas murid dapat membaca histori layanan dan konsultasi sebelumnya sampai murid lulus, tetapi tidak mengubah catatan lama.

- Rolling atau perubahan pembagian dicatat Koordinator berdasarkan keputusan resmi; sistem tidak melakukan perubahan otomatis.

- Admin IT aktif mengimpor URL API Siswa melalui tab Dapodik dan mengelola sinkronisasi e-Tatib melalui tab e-Tatib di Data Master. Panel koneksi Dapodik langsung tidak ditampilkan selama adapter belum tersedia; credential tersimpan tidak pernah ditampilkan kembali.

# Kebutuhan fungsional

## Autentikasi, otorisasi, dan tata kelola

| **ID**  | **Kebutuhan**                                                                                             | **Pri.** | **Kriteria penerimaan**                                                                                 |
|---------|-----------------------------------------------------------------------------------------------------------|----------|---------------------------------------------------------------------------------------------------------|
| AUTH-01 | Pengguna harus masuk dengan akun aktif sebelum mengakses data BK.                                         | P0       | Data operasional tidak tersedia tanpa sesi sah.                                                         |
| AUTH-02 | Guru BK hanya dapat mengakses murid dalam scope aktif dan kasus yang menjadi tanggung jawabnya.                   | P0       | Daftar, pencarian, detail, dashboard, laporan, ekspor, URL, dan API memakai batas yang sama.            |
| AUTH-03 | Server harus memeriksa kewenangan pada setiap objek, bagian data, dan tindakan sensitif.                  | P0       | Permintaan langsung di luar kewenangan ditolak tanpa membocorkan isi objek.                             |
| AUTH-04 | Isi konsultasi hanya dapat dibaca Guru BK (termasuk Koordinator yang merangkap Guru BK) dalam scope profesional, Koordinator melalui halaman Laporan, atau Waka aktif melalui proyeksi detail yang disetujui. | P0 | Admin IT tidak memperoleh isi layanan; akses Waka selalu hanya-baca dan diaudit. |
| AUTH-05 | Waka aktif harus dapat `viewAny` dan `view` seluruh kasus serta konsultasi melalui proyeksi allowlist hanya-baca. | P0 | Server menolak `create`, `update`, `archive`, `resolve`, dan perubahan tindak lanjut oleh Waka; pembacaan detail dicatat. |
| AUTH-06 | Hak teknis Admin IT harus dipisahkan dari hak membaca layanan BK.                                         | P0       | Admin IT dapat mengelola akun, integrasi, master, dan rekonsiliasi tanpa membuka isi kasus.             |
| AUTH-07 | Akun dengan fungsi Koordinator sekaligus Guru BK harus menerapkan hak tiap fungsi secara terpisah.        | P0       | Fungsi Koordinator tidak memperluas akses konsultasi di luar scope Guru BK.                             |
| GOV-01  | Sistem harus mendukung Koordinator BK sebagai penanggung jawab operasional.                               | P0       | Menu operasional penugasan dan rekap tersedia bagi Koordinator tanpa membuka hak edit catatan profesional atau pengelolaan prestasi. |

## Akun, data master, identitas sementara, dan penugasan

Peran Admin IT dan Waka Kesiswaan bersifat tunggal. Guru BK dan Koordinator BK dapat berdiri sendiri atau digabung. Admin IT tidak dapat mengubah peran akunnya sendiri; perubahan peran akun lain divalidasi di server pada pembuatan dan pembaruan.

| **ID** | **Kebutuhan**                                                                                                         | **Pri.** | **Kriteria penerimaan**                                                                        |
|--------|-----------------------------------------------------------------------------------------------------------------------|----------|------------------------------------------------------------------------------------------------|
| ACC-01 | Admin IT harus dapat membuat, mengaktifkan, menonaktifkan, dan memulihkan akun sesuai penugasan resmi.                | P0       | Pembuatan/reset menghasilkan password sementara unik yang berlaku 24 jam, memutus sesi lama, mewajibkan pergantian setelah login, dan diaudit tanpa menyimpan nilai password. Informasi akun terbuka otomatis setelah reset; sandi sementara dapat disalin dan dihapus dari tampilan saat informasi ditutup atau halaman ditinggalkan, tanpa muncul lagi saat informasi dibuka ulang. |
| ACC-02 | Pengelolaan akun tidak boleh memberikan akses isi layanan secara otomatis.                                            | P0       | Peran teknis dan kewenangan objek diperiksa terpisah.                                          |
| MD-01  | Data murid, kelas, keanggotaan kelas, dan tahun ajaran harus mengacu pada Dapodik.                                    | P0       | Aplikasi BK tidak menjadi sumber utama perubahan identitas/kelas.                              |
| MD-02  | Koreksi data master harus dikoordinasikan kepada Admin IT di luar aplikasi dan diproses melalui sumber resmi.         | P0       | Aplikasi tidak menyediakan pengajuan koreksi master baru; hasil sinkronisasi terbaru tetap tercatat. |
| MD-03  | Jika master belum tersinkron dan kasus/layanan harus dicatat, Guru BK dapat memasukkan NISN dan nama sementara serta memilih rombel yang ditugaskan kepadanya. | P0       | Rombel harus aktif pada tahun ajaran aktif; catatan menyimpan tahun dan rombel saat layanan dibuat. |
| MD-04  | Identitas yang dicatat sebelum impor harus ditautkan otomatis menggunakan NISN persis sama tanpa membuat murid ganda. | P0 | Impor daftar sekolah atau Dapodik menautkan catatan BK ke satu murid yang cocok; perbedaan nama tidak membuat identitas baru; konflik NISN/identitas sumber ditahan untuk Admin IT; nama awal, nama sumber, dan hasil pencocokan diaudit tanpa konfirmasi rutin Guru BK. |
| MD-05  | Admin IT harus dapat membuat tahun ajaran sebagai data persiapan sementara ketika Dapodik terlambat. | P0 | Form hanya meminta nama tahun ajaran tanpa tanggal periode atau nomor SK; tahun baru belum aktif dan tidak ditampilkan sebagai data resmi Dapodik. Rombel aktif dari Data Kelas tersedia otomatis pada tahun baru tanpa memilih satu per satu. |
| MD-06  | Admin IT harus dapat mengimpor daftar minimum dari URL API Siswa publik atau CSV UTF-8 fallback dengan field exact `nisn,nama,rombel,tahun_pelajaran` ke satu atau beberapa tahun ajaran sementara, termasuk yang sudah aktif. | P0 | Setiap tahun pelajaran harus sudah dibuat sebagai data sementara dan belum selesai; URL hanya dipakai sekali dan tidak disimpan; pratinjau khusus Admin IT menampilkan ringkasan seluruh respons dan hanya identitas baris yang konflik, dengan NISN lengkap; untuk NISN ganda pada tahun yang sama dari API, Admin IT memilih tepat satu baris per NISN dan pilihan diperiksa ulang terhadap respons saat impor; konflik lain tetap menahan impor; seluruh respons/berkas divalidasi sebelum diproses; NISN exact mempertahankan identitas dan riwayat; penempatan baru dibuat per tahun tanpa menaikkan kelas otomatis; rombel berbeda pada tahun yang sama menolak seluruh impor; hanya baris yang disebutkan diproses tanpa menyimpan payload mentah atau menonaktifkan baris lain. |
| MD-07  | Asal data harus dipisahkan dari status aktivasi operasional dengan kode `school_provisional`, `dapodik`, atau `legacy_unclassified`. | P0 | `is_active` tidak diturunkan dari asal data dan tidak dapat diubah oleh provider atau sinkronisasi. |
| MD-08  | Koordinator BK harus dapat mengaktifkan tahun ajaran secara manual setelah roster dan penugasan lengkap. | P0 | Target belum aktif; minimal satu murid tersedia pada tahun target meski sebagian rombel kosong; tidak ada penempatan aktif ganda pada tahun target; setiap rombel aktif memiliki tepat satu Guru BK aktif; aktivasi menjadikan target satu-satunya tahun ajaran aktif. **Perlu Konfirmasi**, provenance sumber, dan rekonsiliasi tertunda hanya menjadi peringatan. Koordinator dapat mengembalikan tahun aktif sebelumnya hanya bila pendahulunya pasti, masih siap, dan belum ada aktivitas operasional sejak aktivasi; kedua perubahan status diaudit dalam satu transaksi. |
| MD-09  | Guru BK harus memperoleh scope murid roster yang telah diterapkan hanya setelah aktivasi operasional dan sesuai penugasan kelasnya. | P0 | Sebelum aktivasi akses ditolak; setelah aktivasi layanan BK dapat digunakan tanpa membedakan apakah roster berasal dari Excel resmi sekolah atau API, sementara provenance sumber tetap dipertahankan. Layanan baru menyimpan tahun ajaran aktif; layanan dari tahun yang tidak lagi aktif tetap dapat dibaca sesuai scope, tetapi tidak dapat diubah atau diarsipkan. |
| MD-10  | Impor Excel dan tarik API Dapodik/data siswa harus menghasilkan pratinjau pencocokan dan memerlukan konfirmasi Admin IT sebelum diterapkan. | P0 | Pratinjau menunjukkan data cocok, baru, berubah, dan konflik tanpa mengubah master operasional; perubahan hanya terjadi setelah aksi penerapan dikonfirmasi. |
| MD-11  | Pencocokan murid otomatis harus memakai NISN exact; nama tidak boleh menjadi kunci identitas. | P0 | Konflik NISN, tahun ajaran, rombel, atau kepemilikan source ID ditahan; pasangan tahun/rombel hanya dicocokkan otomatis bila unik dan keputusan meragukan diperiksa Admin IT. |
| MD-12  | Penerapan roster dari Excel atau API harus mempertahankan ID internal murid dan seluruh relasi serta histori BK yang sudah ada. | P0 | Pencocokan memakai NISN exact; provenance sumber dicatat sesuai jalur yang digunakan, nilai lama/baru yang relevan diaudit, dan tidak ada write-back ke Dapodik/e-Tatib. |
| MD-13  | Tahun ajaran baru harus dibentuk dari daftar penempatan resmi tanpa kenaikan kelas atau keputusan akademik otomatis. | P0 | Impor NISN exact membuat state membership pada tahun target; murid tahun sebelumnya tanpa penempatan target muncul sebagai daftar pemeriksaan read-only dan tidak menghalangi aktivasi murid lain. Daftar diperbarui setelah impor penempatan target. |
| MD-14  | Data master harus memuat seluruh murid aktif, bukan hanya murid yang pernah menerima pelayanan BK.                  | P0 | Data minimum memuat NISN, nama, kelas/rombel, dan tahun ajaran; catatan pelayanan baru dibuat hanya ketika layanan terjadi. |
| MD-15  | Guru BK aktif harus dapat mencatat rencana murid lulus, pindah, keluar, atau mengundurkan diri untuk murid dalam scope profesionalnya. | P0 | Pencatatan pertama membuat tepat satu `student_departures` berstatus `dalam_proses` dan tidak menonaktifkan murid. |
| MD-16  | Hanya Koordinator BK yang dapat menetapkan proses keluar menjadi `batal` atau `resmi_keluar`. | P0 | `resmi_keluar` mewajibkan `effective_date`; Waka hanya dapat membaca daftar/detail operasional proses keluar tanpa mutasi. |
| MD-17  | Sinkronisasi Dapodik/e-Tatib tidak boleh membuat atau mengubah proses keluar murid. | P0 | Provider hanya memperbarui field kontraknya; ketiadaan status/tanggal keluar tidak diisi dengan asumsi dan tidak menggagalkan admission fungsi roster. |
| MD-18  | Retensi murid keluar harus dimulai hanya dari `effective_date` proses berstatus `resmi_keluar`. | P0 | `dalam_proses`, `batal`, dan arsip layanan tidak memulai retensi atau menonaktifkan murid. |
| MD-19  | Guru BK mencatat progres penanganan pengunduran diri di Catatan BK sebagai satu catatan per murid. | P0 | Tombol Catat Pengunduran Diri membuka modal. Murid dipilih melalui pencarian nama/NISN; nama dan rombel mengikuti pilihan murid tanpa input ulang. Daftar pada tab Pengunduran Diri memuat Hari/Tanggal, Nama/Kelas, Guru, Progres, dan Aksi. Ikon kaca pembesar membuka baris Catatan selebar tabel, tanpa modal detail. Edit dan hapus tersedia sesuai policy; riwayat serta penambahan progres tetap melalui kolom Progres. Catatan awal memuat pencatat, tanggal, murid, snapshot kelas, dan catatan wajib serta selalu dimulai pada progres `masih progres`. Perkembangan disimpan sebagai histori tindak lanjut append-only berisi progres (`masih progres`, `berkas di BK`, atau `berkas masuk TU`), tanggal antara tanggal catatan awal dan hari berjalan, catatan opsional, dan pencatat; koreksi boleh maju, mundur, atau mengulang tahap, sedangkan progres terkini mengikuti entri bertanggal paling baru. Guru BK hanya membuat catatan dan tindak lanjut murid pada penugasan aktif; Koordinator BK hanya membaca seluruh catatan, sedangkan Waka dan Admin IT tidak mengaksesnya. Setiap tindak lanjut diaudit dan tidak mengubah `student_departures`, status aktif murid, atau keputusan keluar resmi. |
| ASN-01 | Koordinator harus dapat menetapkan Guru BK untuk kelas dan tahun ajaran tertentu. | P0 | Daftar berbasis Guru BK menampilkan kelas dan jumlah murid aktif; satu kelas memiliki paling banyak satu penugasan pada tahun ajaran; penugasan tidak menyimpan periode tanggal atau dasar keputusan. |
| ASN-02 | Perubahan membership murid dan penugasan Guru BK harus berupa perubahan state pada tahun ajaran yang sama. | P0 | Admin IT mengelola daftar rombel aktif di Data Kelas; daftar awal dikelompokkan berdasarkan jurusan dengan kepanjangan jurusan dalam tanda kurung di samping singkatan yang dikenali dan aksi kaca pembesar untuk melihat rombel jurusan tersebut, dan nama yang tidak dapat dikenali jurusannya tetap tersedia dalam kelompok Lainnya. Tabel jurusan dan rombel menampilkan jumlah murid aktif dengan membership aktif pada tahun ajaran aktif; tanpa tahun aktif jumlahnya nol dan konteks tersebut ditampilkan. Rombel baru tersedia pada tahun berjalan dan Persiapan, perubahan nama/status tidak mengubah tahun yang selesai. Koordinator dapat menugaskan kelas kosong dan membatalkan penugasan setelah konfirmasi; nilai sebelum/sesudah dicatat pada audit tanpa histori periode tanggal. |
| ASN-03 | Koordinator harus dapat mengubah penugasan pada tahun aktif atau Persiapan. | P0 | Pembatalan tahun aktif langsung mengurangi scope; penugasan Persiapan belum memberi scope; tahun arsip hanya dibaca; owner kasus tidak berubah. |
| ASN-06 | Rolling atau perubahan pembagian dua tahunan tidak boleh dijalankan otomatis.                                         | P0       | Sistem hanya mencatat keputusan resmi yang dimasukkan Koordinator.                             |
| REF-01 | Nilai referensi layanan harus dapat dikelola tanpa mengubah kode.                                                     | P0       | Bidang layanan, jenis tindak lanjut, dan status disimpan sebagai data referensi.               |

Kasus baru memperoleh tepat satu owner ketika dibuat. Relasi owner tidak
memakai `effective_from`/`effective_until` dan tidak dapat diubah melalui
aplikasi. Pergantian penugasan kelas tidak memindahkan owner kasus. Bantuan Guru
BK lain dilakukan di luar aplikasi dan pencatatan resmi tetap dilakukan owner.
Audit lama tetap dipertahankan.

## Kasus dan tindak lanjut

| **ID**  | **Kebutuhan**                                                                                                     | **Pri.** | **Kriteria penerimaan**                                                                                                          |
|---------|-------------------------------------------------------------------------------------------------------------------|----------|----------------------------------------------------------------------------------------------------------------------------------|
| CASE-01 | Guru BK harus dapat membuat kasus untuk murid dalam kewenangannya atau identitas sementara yang sah. | P0 | Semua pemicu Catat Permasalahan, termasuk Dashboard, membuka modal lebar yang sama dengan scroll internal dan footer senada body yang tetap terlihat berisi Kosongkan, Batal, Simpan. Kosongkan mereset isian tanpa menutup; penutupan/pengosongan mengonfirmasi perubahan belum disimpan melalui modal konfirmasi aplikasi. Informasi Dasar menggabungkan sumber, field sumber dinamis, tanggal layanan, dan jenis masalah. Section berikutnya hanya Data e-Tatib atau Data Murid sesuai sumber, lalu Catatan Permasalahan; section tidak diberi nomor. Latar Belakang dan Penanganan sejajar, Ringkasan selebar form di bawahnya. Untuk sumber selain e-Tatib, hanya field NISN menawarkan pencarian berdasarkan NISN/nama dalam scope Guru BK dan mengisi NISN, nama, serta rombel saat dipilih; field Nama Murid tidak memicu pencarian. Bila tidak ditemukan, identitas tetap dapat diisi manual. Server menolak objek di luar kewenangan dan menandai identitas sementara; setelah berhasil, pengguna diarahkan ke daftar Catat Permasalahan pada Layanan BK. |
| CASE-02 | Sumber kasus harus dapat dipilih dari e-Tatib, murid datang sendiri, temuan Guru BK, atau rujukan.                | P0       | Sumber tersimpan dan tampil pada detail.                                                                                         |
| CASE-03 | Pencatatan awal kasus harus memuat **Latar Belakang Masalah** (`initial_info`) dan **Penanganan** (`initial_action`) beserta field khusus layanan. | P0 | `resolution_summary` belum wajib saat pembuatan; tidak ada narasi awal ketiga yang wajib diisi. |
| CASE-04 | Status kasus harus menggunakan Sedang Proses, Tindak Lanjut, atau Selesai. | P0 | Kasus baru selalu Sedang Proses; `baru` dinonaktifkan dan konsultasi tidak memakai status. Pada tab Catatan Permasalahan, status tetap dipakai untuk kategori progres dan aturan aksi tetapi tidak ditampilkan sebagai kolom atau filter; filter memakai jenis tindak lanjut terkini. Kolom Hasil ditampilkan setelah Tindak Lanjut sebelum Aksi. Guru BK/Koordinator yang berwenang dapat membuka baris informasi Sumber, Latar Belakang Masalah, dan Penanganan melalui ikon kaca pembesar; aksi modal detail tetap tersedia dan proyeksi Waka tidak diperluas. |
| CASE-05 | Guru BK pemilik harus dapat menambahkan nol atau lebih tindak lanjut pada kasus aktif. | P0 | Setiap jenis tindak lanjut hanya boleh dipakai sekali per kasus; tindak lanjut pertama mengubah status menjadi Tindak Lanjut; kasus boleh diselesaikan tanpa tindak lanjut. |
| CASE-06 | Guru BK harus dapat menyelesaikan kasus dengan **Hasil / Ringkasan**. | P0 | Penyelesaian mewajibkan `resolution_summary`, menetapkan status Selesai dan `closed_at` dari server; kasus selesai tetap tersedia pada histori murid. |
| CASE-07 | Kasus terkait pelanggaran harus memakai data e-Tatib sebagai referensi resmi. | P0 | Tidak ada transaksi atau pengetikan ulang data yang tersedia. Daftar hasil dan ringkasan pelanggaran memakai latar biru lembut, sedangkan judul section tetap pada latar modal. Pelanggaran terbaru tampil sebelum pencarian; NISN/nama dicari otomatis saat diketik. Setelah dipilih, hasil dan pagination disembunyikan; nama menjadi baris utama identitas, dengan NISN dan rombel di bawahnya serta aksi Ganti di kanan. Summary pelanggaran, tanggal, poin, kategori, pencatat, serta Total Poin Pelanggaran tetap terlihat tanpa section Data Murid terpisah. Jika rombel identitas sementara belum cocok, pilihan Rombel Layanan dibatasi penugasan Guru BK. |
| CASE-08 | Kasus harus dapat memuat bidang layanan BK dari data referensi.                                                   | P0       | Perubahan nilai tidak memerlukan perubahan kode.                                                                                 |
| CASE-09 | Tindak lanjut harus disimpan sebagai record anak kasus. | P0 | Record minimal memuat kasus, `follow_up_type_id`, `performed_at`, dan pembuat; kombinasi kasus+jenis unik; tindak lanjut tidak mempunyai status atau hasil sendiri. |
| CASE-10 | Sistem harus membedakan waktu pencatatan, tanggal layanan, waktu tindak lanjut, dan waktu penyelesaian. | P0 | `performed_at` tindak lanjut ditetapkan server saat tindak lanjut ditambahkan; `closed_at` ditetapkan server saat kasus diselesaikan. |
| CASE-11 | Form aktif kasus tidak boleh meminta narasi layanan selain **Latar Belakang Masalah**, **Penanganan**, dan **Hasil / Ringkasan** pada tahap penyelesaian. | P0 | Field legacy di luar kontrak aktif tidak ditampilkan sebagai input layanan baru dan tidak masuk laporan. |
| CASE-12 | Penyelesaian kasus harus menjadi aksi tersendiri dari edit narasi kasus. | P0 | Tombol Selesaikan menampilkan popup konfirmasi aplikasi; **Hasil / Ringkasan** harus sudah disimpan melalui edit. Konfirmasi menetapkan status Selesai dan `closed_at` secara atomik tanpa mengubah narasi; konflik `updated_at` ditolak. Tombol Hapus memakai popup konfirmasi aplikasi sebelum mengarsipkan kasus. |
| CASE-13 | Hanya Guru BK owner kasus yang dapat mengubah atau mengarsipkan kasus. | P0 | Policy dan service menolak Guru lain, termasuk pemegang akses tambahan lama; fungsi Koordinator tidak memberikan hak mengubah, mengarsipkan, atau mengalihkan owner kasus. |
| CASE-14 | Kasus selesai hanya dapat diedit pemilik setelah konfirmasi. | P0 | Tidak ada alasan perubahan tambahan; identitas, pemilik, dan status terminal tetap; perubahan diaudit dan arsip memakai soft delete. |
| CASE-15 | Kode kasus harus dipertahankan sebagai identitas internal dan disembunyikan dari keluaran pengguna.              | P0       | Kode tetap unik di database tetapi tidak muncul pada UI, pencarian pengguna, laporan, ekspor, dashboard, atau audit yang ditampilkan. |
| CASE-16 | Pilihan tambah tindak lanjut harus berasal dari reference aktif kategori `follow_up_type` yang belum dipakai pada kasus. | P0 | Jenis yang sudah dipilih tidak ditawarkan lagi; aksi tambah tidak ditampilkan ketika seluruh jenis aktif sudah digunakan; penambahan memakai lock dan audit. |
| CASE-17 | Edit kasus resmi dapat mengubah `service_field_id`, `service_date`, `initial_info`, `initial_action`, dan `resolution_summary`; sumber serta identitas murid tetap. Penyelesaian dan tindak lanjut memakai aksi terpisah. | P0 | Modal Edit Permasalahan memakai satu surface tanpa card bertingkat. Nama, NISN, rombel, dan sumber ditampilkan sebagai informasi read-only, bukan disabled input; jenis masalah dan tanggal sejajar. Latar Belakang dan Penanganan sejajar dengan Ringkasan selebar form di bawahnya; footer tetap terlihat berisi Batal dan Simpan. Konflik `updated_at` ditolak tanpa menimpa perubahan lain; tanggal layanan tidak boleh setelah murid resmi keluar. |
| CASE-18 | Daftar kasus hanya mengizinkan sorting nama, kelas, tanggal, sumber, bidang, atau status dengan arah `asc`/`desc`. | P0 | Nilai di luar allowlist ditolak/diabaikan; ID menjadi tie-breaker stabil. |
| CASE-19 | Detail kasus pada daftar Layanan BK harus dibuka hanya melalui tombol aksi Detail. | P0 | Klik area baris tidak membuka modal; tombol ikon informasi memiliki nama aksesibel dan petunjuk `Lihat selengkapnya`; modal menampilkan Data Murid, Informasi Layanan, Catatan Permasalahan, serta Riwayat Tindak Lanjut yang tersedia. |

## Pola tabel operasional

| **ID** | **Kebutuhan** | **Pri.** | **Kriteria penerimaan** |
|--------|---------------|----------|--------------------------|
| TBL-01 | Header kolom yang berguna dibandingkan menyediakan satu sorting aktif dengan siklus default, naik, turun, lalu default. | P0 | Seluruh area header dapat diklik dan memiliki ikon kecil serta `aria-sort` yang sesuai. Default memulihkan urutan awal halaman. Nama/kelas/jurusan diurut alfabetis, jumlah numerik, dan tanggal kronologis. Pada tabel berhalaman, sorting diterapkan ke seluruh hasil filter sebelum pagination. Kolom aksi, narasi panjang, dan nilai tanpa manfaat pembandingan tidak diberi sorting. |

## Integrasi, konsultasi, profil, dan prestasi

| **ID**  | **Kebutuhan**                                                                                                                     | **Pri.**    | **Kriteria penerimaan**                                                                         |
|---------|-----------------------------------------------------------------------------------------------------------------------------------|-------------|-------------------------------------------------------------------------------------------------|
| INT-01  | e-Tatib harus diperlakukan sebagai sistem eksternal.                                                                              | P0          | e-Tatib tidak menjadi modul internal atau navigasi utama.                                       |
| INT-02  | Kasus harus dapat ditautkan ke data e-Tatib yang relevan.                                                                         | P0          | Referensi tampil tanpa pencatatan ulang.                                                        |
| INT-03  | Aplikasi BK harus membaca e-Tatib melalui API resmi dengan NISN sebagai pemetaan utama.                                           | P0          | Pencocokan otomatis mewajibkan NISN canonical dan nama ternormalisasi sama; konflik dapat dipetakan manual oleh Admin IT ke master murid tanpa mengubah sumber, dengan keputusan per pasangan NISN+nama sumber dan audit. |
| INT-04  | MVP tidak boleh mengirim perubahan ke e-Tatib.                                                                                    | P0          | Tidak tersedia fungsi write-back.                                                               |
| INT-05  | Admin IT dapat mengelola konfigurasi endpoint dan credential provider yang memerlukannya tanpa mengubah kode atau file environment. | P0          | Admin IT aktif dapat menyimpan base URL, expected source identifier, timeout, serta token Dapodik melalui PG-501; e-Tatib publik tidak meminta token dan secret tidak pernah ditampilkan kembali. |
| INT-06  | Browser hanya mengirim konfigurasi dan trigger kepada backend; komunikasi dengan sistem eksternal dilakukan server-to-server.   | P0          | Tidak ada request browser langsung ke provider; fetch, mapping, validasi, dan penyimpanan hanya dilakukan backend. |
| INT-07  | Konfigurasi mengikuti state Simpan, Uji, dan Aktif; perubahan material membatalkan verifikasi dan menonaktifkan koneksi.          | P0          | Perubahan URL, identitas sumber, credential, timeout, driver, contract, adapter, atau policy menaikkan versi yang relevan dan memblokir penggunaan sampai diuji ulang; simpan tanpa perubahan tidak mengubah state. |
| INT-08  | Adapter provider memetakan kontrak eksternal resmi ke snapshot internal; field yang tidak dibutuhkan diabaikan dan raw payload tidak disimpan secara default. | P0 | Snapshot hanya berisi field domain dan evidence aman; `driver_id`, `adapter_version`, serta `contract_version` dicatat terpisah. |
| INT-09  | Payload yang tidak valid, tidak lengkap, atau tidak terbukti penuh harus ditolak sebelum import dan tidak boleh mengubah data lama. | P0 | Validator executable memeriksa schema, tipe, nullability, identitas, evidence completeness, pagination, jumlah record, dan byte sebelum transaksi import. |
| INT-10  | Driver production tidak boleh diaktifkan sebelum autentikasi, endpoint, schema, pagination, semantik full/partial, fixture sintetis, dan prosedur gangguan provider disahkan. | P0 | Registry hanya menyediakan driver `unavailable` sampai admission kontrak juga mengesahkan deletion semantics, timezone, limits, resilience, TLS/proxy/jaringan, dan mapping snapshot. |
| INT-11  | Uji koneksi baru dinyatakan sukses bila autentikasi, versi kontrak, schema minimum, dan identitas sumber/sekolah cocok dengan nilai yang diharapkan; status HTTP sukses saja tidak mencukupi. | P0 | Probe gagal tertutup pada identitas berbeda, schema/contract tidak kompatibel, konfigurasi tidak lengkap, credential tak terbaca, atau endpoint policy berubah. |
| INT-12  | Sinkronisasi e-Tatib harus menyimpan field yang diperlukan ke mirror lokal read-only yang dipetakan dengan NISN. | P0 | Raw payload tidak disimpan secara default; Guru BK tidak pernah menulis ke provider; sinkronisasi awal, berkala, atau manual Admin IT memakai kontrak adapter yang sama. Sinkronisasi ulang ID sumber yang sama memperbarui record, sedangkan konflik identitas yang belum selesai tetap satu isu terbuka sampai dicocokkan. |
| INT-13  | Guru BK harus dapat melihat poin dan riwayat pelanggaran e-Tatib untuk murid dalam scope serta mengetahui bila ada data baru yang belum dilihat. | P0 | Penanda **Data e-Tatib Baru** bersifat informatif, dapat ditandai sudah dilihat, tidak menghitung threshold, dan tidak membuat/mengubah kasus secara otomatis. |
| INT-14  | Pratinjau URL e-Tatib harus menahan penerapan data yang berubah atau diduga tidak lengkap. | P0 | Admin IT melihat jumlah data, perbandingan nama/NISN/kelas e-Tatib dengan master, dan kemungkinan murid berdasarkan nama tanpa rincian pelanggaran. Admin IT dapat memilih murid master langsung pada konflik pratinjau; pilihan awal tetap belum tertaut dan sistem tidak memilih hanya berdasarkan kelas. Sinkronisasi sekali pakai/aktivasi otomatis menerapkan pilihan yang sah hanya untuk hasil pratinjau yang sama. Bila dua pembacaan berurutan membuktikan hanya waktu kejadian pada record unik yang berubah dan keduanya mendekati waktu pemanggilan, record disimpan dengan tanggal kosong dan ID lokal stabil; pratinjau serta hasil sinkronisasi memberi peringatan jumlahnya. Sinkronisasi manual dan otomatis boleh menerapkan snapshot lengkap tersebut. Sinkronisasi ulang memperbarui record yang sama, termasuk saat tanggal kejadian kemudian tersedia, tanpa menggandakan pelanggaran. Pelanggaran yang tidak dikirim lagi tetap tersimpan dan tampil dalam riwayat BK lintas semester/tahun; ketidakhadiran record tidak menghambat sinkronisasi manual maupun terjadwal dan bukan pembatalan. Pembatalan eksplisit sumber tetap disimpan dengan penanda pembatalan serta tidak dihitung dalam akumulasi poin BK. Akumulasi dihitung dari riwayat lokal yang tidak dibatalkan, terpisah dari total terakhir sumber yang dapat direset per periode. Arsip nonaktif tanpa pembatalan dari sinkronisasi lama tetap ditampilkan dan dihitung. Dugaan koreksi poin pada identitas kejadian yang sama tanpa ID sumber stabil menahan sinkronisasi agar riwayat tidak dihitung ganda; sistem tidak menggabungkan otomatis hanya berdasarkan kemiripan. Perubahan lain atau benturan fingerprint dengan field sumber berbeda menahan seluruh sinkronisasi. Kelompok dengan seluruh sembilan field sumber identik boleh dianggap satu kejadian hanya setelah konfirmasi eksplisit Admin IT; pratinjau menampilkan nomor baris, jumlah salinan, identitas murid, dan ringkasan kesamaan tanpa rincian pelanggaran. Keputusan berlaku untuk sumber, isi kelompok, dan jumlah salinan yang sama; seluruh keputusan diperiksa terhadap snapshot terbaru dan disimpan bersama mapping, mirror, serta audit secara atomik. Tidak ada persetujuan otomatis untuk kelompok baru atau berubah. Sinkronisasi ulang memakai keputusan aktif tanpa menggandakan record atau menghitung ulang total poin sumber. Admin IT dapat meninjau dan membatalkan keputusan melalui pratinjau atau daftar keputusan aktif, termasuk kelompok yang tidak lagi dikirim sumber; pembatalan diaudit dan menahan duplikasi pada sinkronisasi berikutnya sampai disetujui kembali, tanpa menghapus atau memecah riwayat lama. |
| INT-15 | Admin IT aktif dapat memeriksa masalah sinkronisasi dan memilih tindak lanjut lokal. | P0 | Ikon Periksa membuka rincian tepat di bawah baris, menampilkan identitas sumber dan master yang tersedia beserta konteks tahun/kelas; pembanding yang tidak tersedia diberi keterangan. Untuk perbedaan kelas e-Tatib, Admin memilih kelas sekolah tertentu pada tanggal kejadian atau kelas e-Tatib hanya untuk kejadian itu; pilihan, kelas dan tahun terpilih, petugas, waktu, serta audit disimpan dan masalah selesai. Pilihan tidak mengubah penempatan kelas murid atau data sumber. Sinkronisasi berikutnya mempertahankan keputusan selama data pembanding sama dan membuka masalah baru bila berubah. Untuk masalah lain, Admin mencatat kebutuhan koreksi sumber dengan catatan; masalah tetap terbuka. Pencocokan identitas mengikuti INT-03. Isu yang sudah selesai menolak tindakan baru. Tab Data Master mengikuti pola tab halaman Layanan. Tabel masalah sinkronisasi menampilkan maksimal 10 data per halaman. Panel Riwayat Keputusan Kelas dan Riwayat Sinkronisasi beserta informasinya tertutup secara bawaan; data baru dimuat ketika panel dibuka, masing-masing maksimal 10 data per halaman. Aksi outline Perlu Ditinjau dengan counter berada di kanan tab Data Master pada satu baris flex responsif, terpisah secara visual dari tab, menuju halaman konflik identitas e-Tatib. Halaman konflik memakai tab yang sama dan ikon kembali sebelum judul. Heading Keputusan Duplikasi memuat badge jumlah aktif dan helper Keputusan aktif dapat dibatalkan tanpa menghapus riwayat; heading Masalah Sinkronisasi memuat badge jumlah belum selesai dan helper Data berikut perlu ditinjau. Heading section lebih ringan daripada judul halaman dan tidak memakai card tambahan. Kedua riwayat memakai collapse dengan ikon chevron bawah/atas dan tooltip Tampilkan Riwayat/Tutup Riwayat. Aksi tabel memakai ikon tanpa teks dengan tooltip dan label aksesibel: kaca pembesar untuk Tinjau, link untuk Cocokkan, pensil untuk Ubah Pencocokan, dan undo/unlink untuk pembatalan relasi atau keputusan, bukan ikon hapus. Halaman konflik mempertahankan tab Belum Cocok dan Pencocokan Manual tanpa heading berulang, menyediakan pencarian nama/NISN sumber atau murid master, memakai label Rombel Sumber, dan membuka pencocokan melalui modal satu surface. Keputusan Duplikasi menampilkan kolom Murid, Keputusan, Disetujui, dan Aksi. Pagination berbahasa Indonesia; sorting mengikuti TBL-01 tanpa menambah tinggi baris atau mengubah design system. |
| CONS-01 | Konsultasi adalah record mandiri yang terhubung ke murid atau identitas sementara. | P0 | Saat dibuat, field wajib adalah tanggal, jenis layanan, **Latar Belakang Masalah** (`problem`), **Penanganan** (`handling`), dan Guru BK; `result` (**Hasil / Ringkasan**) boleh kosong dan ditambahkan kemudian; setiap narasi maksimal 10.000 karakter. |
| CONS-02 | Daftar laporan Waka hanya memuat field layanan yang disetujui dan tidak menyediakan ekspor. | P0 | Policy dan proyeksi server membatasi keluaran ke Hasil / Ringkasan, Guru BK, Keterangan, serta identitas layanan yang disetujui; pembacaan Waka diaudit. |
| CONS-03 | Konsultasi tidak mempunyai nomor registrasi, kasus, status, atau catatan privat terpisah. | P0 | Hanya pencatat yang masih berwenang dapat mengubah atau mengarsipkan; edit memakai konfirmasi dan arsip memakai soft delete. |
| CONS-04 | Konsultasi mandiri menyimpan tepat satu murid/identitas sementara, `service_field_id`, `session_date`, `problem`, `handling`, `result` nullable, dan `counselor_id`. | P0 | `result` dapat diisi atau diperbarui melalui edit; konsultasi tidak terhubung ke kasus, tidak memiliki status/nomor registrasi, dan edit memakai `expected_updated_at`. |
| CONS-05 | Daftar konsultasi hanya mencari nama serta mengizinkan filter jenis layanan dan sorting tanggal, nama, kelas, atau jenis layanan. | P0 | Arah sorting dibatasi `asc`/`desc`; ID menjadi tie-breaker stabil. |
| CONS-06 | Guru BK membuka Catat Konsultasi melalui modal dari Dashboard dan Layanan BK. | P0 | Kolom NISN mencari NISN atau nama melalui dropdown; pilihan murid mengisi nama dan rombel otomatis. NISN dan nama dapat diisi manual, dengan rombel dipilih dari penugasan aktif. Latar Belakang Masalah dan Penanganan berdampingan pada layar lebar, Hasil / Ringkasan di bawahnya. Tombol Kosongkan menghapus isian dan draft lokal. Daftar Guru BK/Koordinator menampilkan Hasil / Ringkasan; kaca pembesar membuka teks latar belakang dan penanganan tepat di bawah baris konsultasi, dan klik ulang menutupnya. |
| STU-01  | Profil murid harus menggabungkan informasi operasional yang berhak diakses pengguna.                                              | P0          | e-Tatib, kasus, layanan, tindak lanjut, konsultasi, dan prestasi tersedia sesuai kewenangan. Tabel Permasalahan dan Konsultasi menampilkan hari/tanggal, jenis masalah, kelas saat catatan dibuat, Guru BK, hasil/ringkasan, dan aksi kaca pembesar untuk membuka rincian dalam baris, tanpa aksi modal detail. Tindak lanjut terakhir ditampilkan di bawah hasil permasalahan jika tersedia; kolom pencatat tidak ditampilkan pada tab Prestasi. Batas proyeksi Waka dan narasi konsultasi tetap berlaku.    |
| STU-02  | Guru BK dengan scope aktif harus dapat membaca histori layanan/konsultasi murid lintas kelas dan pergantian Guru BK sampai lulus. | P0          | Histori lama terbaca tetapi tidak dapat diubah oleh Guru BK penerus.                            |
| STU-03  | Profil murid harus mempertahankan histori penempatan kelas per tahun ajaran. | P0 | Kelas/tahun pada layanan dan mirror e-Tatib lama tidak ditimpa oleh kelas aktif saat ini; murid tetap satu identitas berdasarkan NISN. |
| ACH-01  | Waka Kesiswaan harus dapat mengelola data prestasi murid setelah fungsi inti stabil. | P0 bertahap | Waka dapat membuat, membaca, mengubah, dan menghapus prestasi; prestasi terhubung ke profil murid. Hapus memerlukan konfirmasi, menolak versi data yang sudah berubah, menggunakan soft delete, dan diaudit tanpa menghapus permanen histori. |
| ACH-02  | Waka Kesiswaan harus dapat mencatat prestasi secara manual atau melalui impor Excel. | P0 bertahap | Impor divalidasi penuh sebelum transaksi, diproses atomik, dan berkas mentah tidak disimpan. Tombol Impor Excel membuka modal berisi ketentuan, pilihan berkas, dan aksi impor; kegagalan validasi membuka kembali modal beserta pesan kesalahan. Daftar prestasi memakai pencarian dan filter tingkat prestasi aktif, bukan kelas; filter tetap mempertahankan scope baca pengguna. |
| ACH-03  | Prestasi harus menyimpan informasi minimum murid, jenis, tingkat, kegiatan, penyelenggara, tanggal, dan hasil. | P0 bertahap | Tambah dan edit memakai modal tanpa panel bertingkat. Pencarian murid berdasarkan ketikan memberi saran NISN, nama, dan kelas aktif saat ini; murid wajib dipilih dari saran. Jenis dan tingkat memakai dropdown. Setelah simpan kembali ke daftar; kegagalan validasi mempertahankan isian modal. Tidak ada halaman/modal detail; aksi daftar hanya edit dan hapus sesuai kewenangan. Tidak ada field bukti/lampiran, status verifikasi, atau catatan verifikasi pada kontrak aktif. |
| ACH-04  | Guru BK membaca prestasi murid dalam scope profesional; Koordinator BK membaca prestasi seluruh murid, termasuk saat rangkap Guru BK. | P0 bertahap | Guru BK dan Koordinator tidak dapat membuat, mengubah, menghapus, mengimpor, atau memverifikasi prestasi tanpa role Waka. Akses baca Koordinator mencakup daftar, profil murid, jumlah prestasi, dan pilihan seluruh kelas. Hak membuat catatan layanan tetap mengikuti kewenangan profesional Guru BK. |
| ACH-05  | Prestasi tidak boleh mengubah lifecycle layanan BK secara otomatis. | P0 bertahap | Menambah/mengubah prestasi tidak membuat kasus, tindak lanjut, status, atau rekomendasi otomatis. |

## Dashboard, laporan, dan audit

| **ID**  | **Kebutuhan**                                                                                                     | **Pri.** | **Kriteria penerimaan**                                                                          |
|---------|-------------------------------------------------------------------------------------------------------------------|----------|--------------------------------------------------------------------------------------------------|
| DASH-01 | Dashboard harus mengikuti peran, scope profesional, dan kepemilikan kasus yang sah. | P0 | Hitungan dan tautan tidak memuat data di luar kewenangan. |
| DASH-02 | Dashboard harus menampilkan konteks operasional sesuai fungsi akun tanpa membaca daftar audit.                    | P0       | Guru BK melihat cakupan layanan, maksimal lima aktivitas terbaru berupa informasi tanpa tautan detail (jenis layanan/permasalahan, nama murid dan kelas, serta tindak lanjut jika tersedia), dan Kelas Binaan berdasarkan penugasan pada tahun ajaran terpilih. Kelas Binaan dikelompokkan dalam tab manual Kelas 10/11/12 dengan default Kelas 10; setiap tingkat menampilkan jumlah kelas dan murid serta enam kelas awal dengan aksi Lihat Semua untuk sisanya, tanpa reload atau autoplay. Koordinator melihat kesiapan penugasan; Admin IT melihat empat indikator Akun Aktif, Akun Nonaktif, Konflik Sinkronisasi, dan Integrasi Bermasalah berdasarkan data aktual tanpa isi layanan BK; konflik dan masalah integrasi memakai warna perhatian. Filter Tahun Ajaran tetap tersedia tanpa kartu jumlah tahun aktif. Aksi cepat hanya Tambah Akun yang membuka form tambah pada Kelola Akun; impor tetap melalui Data Master. Status Sinkronisasi Terbaru memakai nama sumber dan status Bahasa Indonesia, mengutamakan jumlah diproses dan konflik tanpa identifier internal atau ringkasan teknis mentah. Panel Status Integrasi menampilkan kondisi Dapodik, e-Tatib, dan API Siswa beserta keterangan singkat; status mengikuti konfigurasi yang diperlukan oleh alur sumber tersebut dan hasil sinkronisasi terakhir, bukan pemeriksaan koneksi langsung. Impor satu kali menampilkan keterangan hasil impor terakhir; sumber tanpa hasil tidak dinyatakan normal. Kartu indikator tidak memiliki link kecil tambahan. Jumlah konflik Admin IT membuka tab Sinkronisasi yang menampilkan seluruh isu belum selesai beserta sumber, alasan, dan tindak lanjut; konflik identitas e-Tatib ditautkan ke pencocokan yang sudah ada. |
| DASH-03 | Dashboard Waka harus menampilkan kondisi layanan BK sekolah dengan perspektif murid unik. | P0 | Header hanya judul dan pilihan tahun ajaran; tanpa pemberitahuan hanya-lihat atau tombol detail/lihat semua. Murid Tercatat menghitung identitas unik gabungan permasalahan dan konsultasi pada periode tanggal tahun ajaran terpilih; identitas sementara yang sudah direkonsiliasi memakai identitas murid resmi. Sedang Ditangani menghitung murid dengan setidaknya satu permasalahan belum selesai dalam periode; Perlu Tindak Lanjut dan tabel utamanya hanya murid dengan status permasalahan masih Tindak Lanjut. Baru Bulan Ini menghitung murid yang tanggal layanan pertamanya sepanjang riwayat permasalahan/konsultasi yang boleh dibaca jatuh pada bulan berjalan dan periode terpilih. Tren bulanan menghitung murid unik yang memiliki layanan pada tiap bulan, tidak kumulatif dan tidak terbatas murid baru; bulan kosong bernilai nol. Sebaran hanya menampilkan X/XI/XII menggunakan kelas catatan terakhir dalam periode untuk setiap murid. Tingkat yang tidak tersedia tidak ditampilkan pada sebaran, tetapi murid tetap dihitung pada metrik dan tren. Di bawah sebaran ditampilkan maksimal tiga kelas dengan jumlah murid unik yang memiliki catatan permasalahan terbanyak pada periode terpilih; konsultasi tidak dihitung. Kelas mengikuti kelas pada catatan, setiap identitas dihitung sekali per kelas, dan identitas sementara yang direkonsiliasi memakai identitas resmi. Urutan jumlah menurun, lalu nama kelas menaik bila sama; kelas tanpa catatan tidak ditampilkan. Analitik tampil sekitar dua pertiga tren dan sepertiga sebaran. Tabel dikelompokkan per murid dengan kolom Murid/Kelas, Jenis Layanan, Guru BK, Ringkasan dari hasil/ringkasan existing, dan Tindak Lanjut; semua permasalahan Tindak Lanjut murid tersebut tetap terlihat tanpa narasi sensitif atau aksi ubah. |
| DASH-04 | Form tambah/edit data bisnis menggunakan autosave lokal yang aman. | P0 | Draft per pengguna/form/record ber-TTL 24 jam, tidak dikirim/audit, dan mengecualikan password, token, credential, file, CSRF, serta method spoofing. |
| DASH-05 | Dashboard/profil Guru BK harus dapat menandai murid dalam scope yang memiliki data e-Tatib baru sejak terakhir dilihat. | P0 | Penanda hanya membuka konteks poin/pelanggaran dan tidak menyatakan murid wajib menjadi kasus BK. |
| REP-01  | Laporan Guru BK/Koordinator harus menyediakan filter tahun ajaran, kelas, dan jenis layanan `Semua`, `Catatan Kasus`, atau `Catatan Konsultasi`. | P0 | Kelas hanya berasal dari tahun ajaran terpilih dan scope actor; pasangan tahun/kelas yang tidak cocok ditolak server. |
| REP-02  | Laporan Guru BK/Koordinator harus menampilkan satu baris per catatan kasus/konsultasi dengan No, Hari/Tanggal, Nama & Kelas, Layanan/Jenis Masalah, Hasil / Ringkasan, serta Aksi. | P0 | Tabel tampil tanpa panel atau ringkasan di luarnya. Satu ikon kaca pembesar membuka Latar Belakang Masalah dan Penanganan dalam baris detail yang membentang dari kolom No hingga Aksi; Hasil / Ringkasan tidak diulang. Khusus filter pengunduran diri, kolomnya No, Hari/Tanggal, Nama/Kelas, Guru, Keterangan, serta Aksi. Keterangan memuat progres dari tindak lanjut terbaru berdasarkan tanggal lalu ID, atau progres awal jika belum ada tindak lanjut. Kaca pembesar pengunduran diri hanya membuka Catatan dari catatan awal; catatan kosong ditampilkan sebagai `Belum ada catatan.`. Nilai lain yang belum tersedia ditampilkan `—`. Laporan hanya menampilkan aksi lihat detail; pengelolaan dan arsip catatan dilakukan di menu Layanan BK. Klik baris tidak membuka modal dan cetak per catatan tidak ditampilkan. |
| REP-03  | Halaman utama harus memakai pagination biasa di bawah tabel dan urutan tanggal terbaru; preview rekap, cetak/PDF browser, dan Excel memakai seluruh dataset hasil filter dengan urutan tanggal paling awal. | P0 | Default UI 10 data tanpa pemilih jumlah data; preview/PDF dan Excel menyajikan ringkasan hasil filter sebagai kalimat lengkap. Dokumen putih polos memuat No, Hari/Tanggal, Nama/Kelas, Jenis Masalah, Hasil / Ringkasan dari `resolution_summary|result`, Guru BK, dan Keterangan. Nilai hasil yang belum tersedia memakai `—`; Keterangan Permasalahan memuat Sumber dan tindak lanjut yang tercatat, sedangkan Keterangan Konsultasi memakai `—`. |
| REP-04  | Laporan harus menyediakan tombol Cetak/Unduh Rekap menuju preview A4 portrait serta preview satu kasus dan konsultasi dalam A4 portrait. | P0 | Tombol rekap membuka modal selebar kertas dengan kertas A4 di tengah yang skalanya mengecil proporsional mengikuti lebar layar dan area dokumen bergulir. Bilah judul, Unduh Excel, Cetak/Simpan PDF, dan Tutup tetap terlihat. Menutup modal mempertahankan filter dan posisi daftar; cetak tetap A4 dan hanya memuat dokumen. Rekap menyediakan Excel serta Cetak/Simpan PDF; preview memakai blok tanda tangan yang disetujui, sedangkan Excel tidak. |
| REP-05  | Waka memakai halaman daftar laporan yang sama secara visual dengan Koordinator dalam mode hanya-baca. | P0 | Filter layanan tetap tersedia; ringkasan Catatan Layanan dan pemilih jumlah data tidak ditampilkan, dengan pagination biasa di bawah tabel. Kolom kasus/konsultasi dibatasi ke No, Hari/Tanggal, Nama/Kelas, Jenis Masalah, Hasil / Ringkasan, Guru BK, dan Keterangan. Khusus pengunduran diri, Waka dapat membaca daftar lintas kelas sesuai filter tahun ajaran/kelas dengan kolom No, Hari/Tanggal, Nama/Kelas, Guru, dan Keterangan. Keterangan memakai progres berkas dari tindak lanjut terbaru berdasarkan tanggal lalu ID, atau progres awal bila belum ada tindak lanjut. Preview, cetak, unduhan, data narasi mentah, dan aksi layanan tidak tersedia bagi Waka. |
| AUD-01  | Simpan resmi serta pembacaan portal Waka harus menghasilkan jejak audit otomatis. | P0 | Audit append-only menyimpan actor, waktu, tipe/ID record, dan hanya nilai sebelum/sesudah field yang berubah; draft lokal tidak diaudit dan audit Waka tidak memuat nama/NISN/kode kasus/narasi. |

MVP tidak menyediakan workflow Koreksi Data, pusat Notifikasi, atau halaman
Riwayat Perubahan. Jadwal dan pekerjaan penting tetap tersedia pada dashboard
serta halaman operasional terkait; kesalahan data master diproses pada sumber
resmi dan masuk kembali melalui sinkronisasi atau rekonsiliasi.

Mapping daftar memakai `resolution_summary` untuk Hasil / Ringkasan Permasalahan dan `result` untuk Hasil / Ringkasan Konsultasi; nilai null ditampilkan `—`. Baris detail memakai `initial_info`/`problem` sebagai Latar Belakang Masalah serta `initial_action`/`handling` sebagai Penanganan. Kolom Guru BK memakai pemilik kasus atau `counselor_id`. Keterangan Permasalahan memakai label `case_source_id` serta label tindak lanjut yang tercatat; Keterangan Konsultasi memakai `—`. Implementasi memakai stack existing dan Laravel Pagination tanpa dependency tabel baru.

Rekap ditandatangani Koordinator BK dan Waka Kesiswaan. Dokumen kasus ditandatangani pemilik kasus dan Waka; konsultasi memakai `counselor_id` dan Waka. Pengguna login tidak menjadi fallback. Koordinator/Waka hanya dipilih bila tepat satu akun aktif tersedia; kondisi kosong/ganda ditampilkan sebagai penandatangan belum tersedia. Kepala Sekolah dan NIP tidak ditampilkan karena belum mempunyai sumber data pada schema saat ini. Blok tanda tangan hanya muncul di akhir preview cetak, tidak pada Excel, serta tidak boleh terpotong page break.

# Kebutuhan data

## Field minimum

| **Objek**           | **Field minimum**                                                                                                                                          | **Aturan**                                                   |
|---------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------|
| Tahun Ajaran         | Nama/identitas tahun, `is_active`, `activated_at`, dan `activated_by`. | Tidak memiliki input tanggal mulai/selesai; hanya satu tahun ajaran boleh aktif. |
| Keanggotaan Kelas   | Murid, kelas, tahun ajaran, status aktif, provenance, dan waktu sinkronisasi. | Maksimal satu state aktif per murid+tahun; tidak memiliki `effective_from`/`effective_until`; perubahan kelas diaudit. |
| Penugasan Guru BK   | Guru BK, kelas, tahun ajaran, pencatat, dan waktu perubahan. | Maksimal satu state berjalan per kelas+tahun; tidak memiliki `effective_from`/`effective_until` atau `decision_number`; perubahan Guru BK diaudit. |
| Kasus               | Kode internal, murid/identitas sementara, snapshot tahun ajaran/kelas, sumber, tanggal layanan, bidang, `initial_info`, `initial_action`, `resolution_summary` nullable, owner Guru BK, status, waktu dibuat, dan `closed_at`. | Label UI narasi adalah Latar Belakang Masalah, Penanganan, dan Hasil / Ringkasan; owner ditetapkan saat pembuatan dan tidak dapat diubah; kode tidak masuk keluaran pengguna; `resolution_summary` wajib hanya saat penyelesaian. |
| Kepemilikan Kasus   | Kasus, owner Guru BK, pencatat, alasan awal, dan waktu pembuatan. | Tepat satu owner per kasus; tidak memiliki `effective_from`/`effective_until` dan tidak menyediakan pergantian owner. |
| Tindak lanjut       | Kasus, `follow_up_type_id`, `performed_at`, pembuat, dan waktu record. | Record anak; jenis unik per kasus; `performed_at` ditetapkan server saat ditambahkan; tidak memiliki status/hasil sendiri. |
| Konsultasi          | Tepat satu murid/identitas sementara, snapshot tahun ajaran/kelas, tanggal, jenis layanan, `problem`, `handling`, `result` nullable, dan Guru BK. | `problem` dan `handling` wajib; `result` dapat diisi kemudian; setiap narasi maksimal 10.000 karakter; tidak memiliki status/nomor registrasi. |
| Identitas sementara | NISN, nama masukan, pembuat, waktu, kasus/layanan, status rekonsiliasi.                                                                                    | Hanya ketika master belum tersedia; bukan master alternatif. |
| Rekonsiliasi        | NISN sumber, murid master, nama resmi, status, hasil, pemeriksa, waktu, konflik.                                                                           | Tidak membuat murid ganda; nilai lama tetap diaudit.         |
| Mirror e-Tatib      | Identitas sumber, NISN, snapshot tahun ajaran lokal, kelas sumber bila tersedia, tanggal/waktu kejadian, jenis pelanggaran, kategori, poin, pencatat, total poin provider bila tersedia, dan waktu sinkronisasi. | Hanya-baca; tidak menjadi sumber perubahan e-Tatib dan tidak memicu lifecycle kasus otomatis. |
| Prestasi            | Murid, snapshot tahun ajaran/kelas, jenis, tingkat, kegiatan, penyelenggara, tanggal, dan hasil. | Dikelola Waka; tanpa bukti/lampiran dan tanpa status verifikasi. |
| Proses keluar murid | Murid, snapshot tahun ajaran/kelas, jenis keluar, status, tanggal pencatatan, ringkasan rekomendasi, tanggal efektif, pencatat, pemutus, waktu keputusan, dan catatan keputusan. | Satu baris per murid; `effective_date` wajib hanya untuk `resmi_keluar`; narasi maksimal 500 karakter. |
| Akun                | Identitas pengguna, peran, status aktif, `must_change_password`, `temporary_password_expires_at`, `password_changed_at`, waktu perubahan, dan pengubah.              | Password sementara berlaku 24 jam, ditampilkan satu kali, dan nilainya tidak masuk audit/log. |
| Konfigurasi integrasi | Provider, base URL, expected source identifier, credential terenkripsi, timeout, state, configuration version, operation fence version, driver ID, adapter version, contract version, endpoint-policy digest, penguji, dan waktu uji. | Secret tidak keluar dari backend; perubahan material membatalkan verifikasi. |
| Evidence snapshot   | Identitas sumber terlapor, marker/provenance kontrak, jumlah page, jumlah record, dan jumlah byte terproses.                                               | Tidak memuat raw payload; divalidasi sebelum import.         |
| Roster Excel/sekolah | Tahun ajaran target, NISN, nama, rombel, asal data, status aktif, pembuat, dan waktu penerapan. | Berasal dari daftar resmi sekolah; provenance sumber dan aktivasi operasional disimpan terpisah; berkas mentah tidak dipertahankan. |
| Pratinjau pencocokan | Run sumber, hasil cocok/baru/berubah/konflik, kandidat ID internal, keputusan Admin IT, nilai aman lama/baru, dan waktu keputusan. | Tidak mengubah cache operasional sebelum penerapan dikonfirmasi. |

## Entitas konseptual

| **Entitas**               | **Fungsi**                                                                   |
|---------------------------|------------------------------------------------------------------------------|
| Pengguna dan Peran        | Identitas akun, fungsi pengguna, dan status aktif.                           |
| Tahun Ajaran              | Konteks kelas, penugasan, histori, dan laporan.                              |
| Master Dapodik            | Salinan terkontrol data murid, kelas, keanggotaan, dan tahun ajaran.         |
| Identitas Murid Sementara | NISN dan nama untuk kasus sebelum master tersedia.                           |
| Rekonsiliasi Identitas    | Pencocokan NISN sementara ke master tanpa duplikasi.                         |
| Penugasan Guru BK         | State Guru BK berjalan untuk satu kelas dan tahun ajaran.                    |
| Murid                     | Referensi profil, kasus, layanan, konsultasi, dan prestasi.                  |
| Mirror e-Tatib            | Salinan terstruktur read-only untuk konteks pelanggaran/poin dan penanda data baru. |
| Kasus                     | Wadah penanganan dari informasi awal sampai penyelesaian.                    |
| Kepemilikan Kasus         | Satu owner tetap yang ditetapkan ketika kasus dibuat; tidak memiliki periode atau pengalihan. |
| Tindak Lanjut             | Record anak kasus untuk setiap jenis tindak lanjut yang dipilih.              |
| Konsultasi                | Layanan mandiri dengan Latar Belakang Masalah, Penanganan, dan Hasil / Ringkasan opsional. |
| Prestasi                  | Riwayat prestasi yang dikelola Waka dan dibaca Guru BK sesuai scope.          |
| Proses Keluar Murid       | Satu proses per murid dari pencatatan awal sampai keputusan Koordinator.     |
| Jejak Audit               | Catatan perubahan penting yang dibuat sistem.                                |
| Data Referensi            | Bidang layanan, jenis tindak lanjut, dan status operasional.                 |
| Konfigurasi Integrasi     | State dan versi koneksi Dapodik/e-Tatib beserta credential terenkripsi.      |
| Evidence Snapshot         | Bukti aman identitas, kontrak, pagination, jumlah record, dan ukuran hasil.  |
| Data Persiapan Sementara  | Data minimum berdasarkan daftar resmi sekolah untuk menjaga layanan selama Dapodik terlambat.  |
| Pratinjau Pencocokan      | Hasil pemeriksaan Dapodik sebelum perubahan diterapkan pada cache operasional. |

## Status dan perubahan

- Status kasus aktif terdiri atas `sedang_diproses` (**Sedang Proses**), `membutuhkan_tindak_lanjut` (**Tindak Lanjut**), dan `selesai` (**Selesai**); konsultasi tidak memiliki status.

- `selesai` adalah satu-satunya status terminal kasus. Pemilik catatan yang
  masih berwenang dapat mengedit setelah konfirmasi; identitas, pemilik, dan
  status terminal tetap tidak berubah.

- Tombol Hapus mengarsipkan kasus atau konsultasi dengan soft delete dan tidak
  mengubah status bisnis. Nilai legacy `dibatalkan` dinonaktifkan untuk kasus
  dan konsultasi baru.

- Proses keluar murid memakai `dalam_proses`, `batal`, dan `resmi_keluar`.
  Hanya `resmi_keluar` dengan `effective_date` yang menghentikan layanan baru
  dan memulai retensi; sinkronisasi provider tidak dapat mengubah proses ini.

- Tindak lanjut adalah record anak kasus. Satu jenis hanya boleh dipakai sekali per kasus; penambahan pertama mengubah status kasus menjadi **Tindak Lanjut**, tetapi kasus dapat diselesaikan tanpa tindak lanjut.

- Membership dan penugasan kelas disimpan sebagai state per tahun ajaran tanpa
  periode tanggal. Perubahannya diaudit. Owner kasus tetap sejak pembuatan dan
  tidak memiliki periode berlaku.

- Koordinasi dengan Waka dilakukan di luar aplikasi dan tidak memiliki data/status aplikasi.

- Identitas sementara memiliki status rekonsiliasi dan tidak menjadi master murid baru.

- Status asal data memakai `school_provisional`, `dapodik`, atau `legacy_unclassified` dan tidak menentukan status aktif tahun ajaran.

- Aktivasi operasional tahun ajaran hanya diputuskan manual oleh Koordinator BK setelah roster dan penugasan lengkap. Tahun ajaran tidak memiliki input tanggal mulai/selesai dan provider/sinkronisasi tidak dapat mengubah `is_active`.

- Admin IT dapat membatalkan tahun Persiapan yang belum pernah aktif, termasuk rombel, penempatan murid sementara, dan penugasan pada tahun itu. Pembatalan ditolak bila ada layanan atau data terverifikasi pada tahun tersebut. Identitas murid yang juga dipakai tahun lain atau telah mempunyai relasi lain tetap disimpan; hanya identitas sementara hasil impor yang menjadi tanpa relasi dapat dihapus. Aksi diaudit. Pengembalian ke tahun aktif sebelumnya hanya tersedia bagi Koordinator bila pendahulu dapat dipastikan dan belum ada aktivitas operasional setelah aktivasi; jika tidak, koreksi dilakukan di luar aksi otomatis.

- Murid aktif pada tahun sebelumnya yang belum mempunyai keanggotaan pada tahun target ditampilkan sebagai **Perlu Konfirmasi**. Penanda dihitung saat dibaca, tidak menentukan status akademik, dan tidak memblokir aktivasi keseluruhan.

- Kesiapan aktivasi diblokir bila belum ada murid sama sekali pada tahun target, ada penempatan aktif ganda, atau ada rombel aktif tanpa tepat satu Guru BK aktif. Rombel kosong yang sudah ditugaskan tidak menghalangi aktivasi. Aktivasi target menonaktifkan tahun aktif sebelumnya; **Perlu Konfirmasi**, provenance roster Excel/sekolah, dan rekonsiliasi tertunda hanya menjadi peringatan.

- Roster Excel yang kemudian cocok dengan data Dapodik/API direkonsiliasi pada ID internal murid yang sama; identitas sumber diperbarui tanpa membuat ulang relasi atau histori BK.

- Status kasus memakai tiga kode aktif; jenis tindak lanjut tetap data referensi. Prestasi tidak memakai status verifikasi.

- State konfigurasi integrasi terdiri atas `unconfigured`, `draft`, `blocked`, `test_failed`, `ready`, dan `active`; driver yang belum tersedia menghasilkan `blocked`.

- Simpan perubahan material menaikkan `configuration_version`, membatalkan hasil uji, dan menonaktifkan koneksi. Aktivasi hanya sah jika configuration version, driver ID, adapter version, contract version, dan endpoint-policy digest sama dengan hasil verifikasi.

- Setiap operasi per provider memakai lock, fencing token persisten, recheck row-lock sebelum write, serta hard operation deadline dengan safety margin di bawah masa lease. Proses stale tidak boleh menulis.

# Privasi, keamanan, dan audit

## Informasi yang tidak boleh tampil pada laporan umum

- Nama lengkap murid pada laporan untuk pembaca umum.

- Isi lengkap konsultasi, catatan internal profesional, dan dokumen asli.

- Nama pelapor serta informasi pribadi yang tidak diperlukan.

- Kronologi rinci pelanggaran sensitif.

- Informasi kesehatan atau keluarga tanpa dasar kewenangan yang sah.

- Kode kasus pada UI, pencarian pengguna, laporan, ekspor, dashboard, atau audit yang ditampilkan kepada pengguna.

- NISN, kode kasus, catatan internal, payload provider mentah, dokumen sensitif, audit teknis, dan field di luar allowlist pada proyeksi Waka.

Waka memakai proyeksi hanya-baca seluruh kasus dan konsultasi. Daftar laporan hanya memuat identitas layanan yang disetujui, Hasil / Ringkasan dari `resolution_summary|result`, Guru BK, dan Keterangan; latar belakang, penanganan, aksi, preview, cetak, serta unduhan tidak dikirim ke halaman Waka. Setiap pembacaan dicatat dan Waka tidak memperoleh ekspor massal laporan.

## Aturan keamanan

| **Prinsip**                    | **Penerapan minimum**                                                                         |
|--------------------------------|-----------------------------------------------------------------------------------------------|
| Hak minimum                    | Pengguna hanya memperoleh hak yang diperlukan untuk tugasnya.                                 |
| Otorisasi per objek dan bagian | Server memeriksa murid, kasus, konsultasi, dokumen, dan aksi yang diminta.                    |
| Batas konsisten                | Pencarian, daftar, detail, dashboard, laporan, ekspor, URL, dan API memakai aturan yang sama. |
| Pemisahan data sensitif        | Proyeksi Waka, catatan internal, payload provider, dan keluaran massal dibatasi terpisah.     |
| Pemisahan tugas                | Hak teknis, mode hanya-baca Waka pada layanan BK, hak kelola Waka pada prestasi, dan scope Guru BK tidak saling memperluas otomatis. |
| Audit otomatis                 | Perubahan penting dibuat sistem dan tidak dapat diubah pengguna biasa.                        |
| Secret-safe workflow           | Credential dienkripsi, tidak masuk HTML/JSON/session/audit/log/exception, tidak memakai old input, dan hanya dapat diganti atau dihapus secara eksplisit setelah step-up Admin IT. |
| Endpoint fail-closed           | Origin cocok exact dengan allowlist deployment; metadata/link-local/multicast/unspecified selalu ditolak; private/loopback hanya diizinkan bila origin exact terdaftar dan flag opt-in private-network provider aktif; redirect, user-info, query/fragment, origin drift, DNS campuran/berubah, proxy tak tepercaya, dan TLS invalid ditolak. |
| Koneksi terikat                | Setiap resolusi alamat divalidasi tepat sebelum koneksi dan transport diikat ke alamat tersebut dengan Host/SNI tetap sesuai origin. |
| Import atomik                  | Snapshot dan evidence divalidasi sebelum transaksi; kegagalan atau operasi stale mempertahankan data lama. |

# Kebutuhan nonfungsional

| **ID** | **Kebutuhan**                                                                                                      | **Pri.** | **Kriteria penerimaan**                                                                    |
|--------|--------------------------------------------------------------------------------------------------------------------|----------|--------------------------------------------------------------------------------------------|
| NFR-01 | Setiap akses data operasional memerlukan autentikasi dan otorisasi pada server.                                    | P0       | Permintaan tanpa sesi dan di luar kewenangan ditolak.                                      |
| NFR-02 | Informasi sensitif harus dipisahkan dari proyeksi Waka, catatan internal, dan laporan umum. | P0 | Uji tampilan/ekspor tidak memuat field terlarang. |
| NFR-03 | Jejak audit harus memuat pelaku, tindakan, objek, waktu, dan ringkasan perubahan.                                  | P0       | Perubahan penting menghasilkan audit otomatis.                                             |
| NFR-04 | Alur utama harus dapat digunakan pada telepon genggam, tablet, laptop, dan komputer sekolah.                       | P0       | Login, pencarian, kasus, tindak lanjut, dan laporan dapat diselesaikan pada perangkat uji. Filter daftar memakai tombol konsisten Filter; pilihan diterapkan otomatis dan pencarian teks setelah jeda ketik. Saat nilai berbeda dari bawaan, tombol menjadi Reset untuk membatalkan filter, kembali ke halaman pertama, dan mempertahankan tab/konteks daftar. Seluruh tampilan nama murid (halaman, modal, pilihan pencarian, pratinjau, dan laporan) memakai huruf awal tiap kata besar dan sisanya kecil, termasuk nama sumber kapital; format tampilan tidak mengubah data nama sumber atau aturan pencocokan identitas. |
| NFR-05 | Form utama harus menghindari pengisian ulang data e-Tatib dan Dapodik.                                             | P0       | Data sumber ditampilkan sebagai referensi; fallback hanya NISN+nama.                       |
| NFR-06 | Perubahan membership atau penugasan harus mempertahankan histori layanan tanpa memakai periode tanggal.             | P0       | State sebelum/sesudah tercatat pada audit dan record layanan mempertahankan snapshot tahun ajaran/kelas saat dicatat. |
| NFR-07 | Nilai referensi layanan tidak boleh ditanam langsung dalam kode.                                                   | P0       | Nilai dapat diperbarui peran berwenang tanpa perubahan program.                            |
| NFR-08 | Data operasional dan audit harus disimpan minimum tiga tahun dan tidak dihapus otomatis sebelum prosedur disahkan. | P0       | Pembatalan manual hanya berlaku bagi draf Persiapan yang belum pernah aktif dan tidak memiliki layanan/data terverifikasi; audit tetap tersimpan. Tidak ada penghapusan terjadwal sebelum batas minimum dan kebijakan operasional terpenuhi. |
| NFR-09 | Credential dienkripsi menggunakan encrypter Laravel dan tidak boleh tampil pada response, session, audit, atau log. | P0 | HTML/JSON, flash/old input, exception, dan audit hanya membawa status keberadaan/perubahan credential; seluruh aksi PG-501 memakai step-up dan `Cache-Control: no-store`. |
| NFR-10 | Endpoint outbound mengikuti exact deployment allowlist, redirect dimatikan, operasi per provider diserialisasi, dan kegagalan mempertahankan data lama. | P0 | Save, test, activate, dan sync memvalidasi policy; policy digest mencakup versi policy, exact origins yang dikanonisasi, dan flag private-network provider lalu diverifikasi ulang; lock serta fencing mencegah duplicate/stale write. |
| NFR-11 | Credential provider harus least-privilege/read-only. Endpoint policy memvalidasi resolusi alamat untuk setiap koneksi dan menolak metadata/link-local, origin drift, proxy tak tepercaya, serta TLS invalid secara fail-closed. | P0 | Admission menyertakan bukti scope read-only; metadata/link-local/multicast/unspecified selalu ditolak, private/loopback hanya diterima jika origin cocok exact dan flag opt-in private-network provider aktif, seluruh hasil DNS diperiksa, jawaban campuran/berubah ditolak, serta koneksi diikat ke alamat tervalidasi dengan Host/SNI yang benar. |
| NFR-12 | Adapter production menerapkan batas payload/page, pagination, timeout, retry/backoff, rate limit, concurrency, dan backpressure yang disahkan dalam kontrak provider. | P0 | Kontrak menetapkan angka/batas dan kebutuhan queue; worst-case operation harus muat di hard deadline di bawah lease atau adapter tidak di-admit. |
| NFR-13 | Impor data persiapan dan penerapan hasil Dapodik harus divalidasi penuh sebelum mutasi dan diproses secara atomik. | P0 | CSV wajib UTF-8 maksimum 2 MiB, header exact `nisn,nama,rombel,tahun_pelajaran`, NISN 10 digit unik per tahun pelajaran dalam satu berkas, seluruh field wajib, formula/control character ditolak, dan maksimum 5.000 baris; satu kesalahan, konflik, atau kegagalan membatalkan seluruh perubahan. |
| NFR-14 | Hasil aksi di seluruh halaman memakai toast dengan warna yang konsisten. | P1 | Gagal berwarna merah, berhasil dengan peringatan kuning, dan berhasil hijau. Kesalahan isian tetap ditampilkan dekat formulir agar dapat diperbaiki. |

# Integrasi

## e-Tatib

| **Aspek**              | **Ketentuan**                                                                                                |
|------------------------|--------------------------------------------------------------------------------------------------------------|
| Arah data              | e-Tatib ke Aplikasi BK melalui API; tidak ada write-back.                                                     |
| Mirror                 | Backend menyimpan field yang diperlukan sebagai mirror read-only lokal; browser Guru BK tidak memanggil provider langsung. |
| Pemetaan               | NISN menjadi identitas utama untuk mencocokkan murid.                                                        |
| Data minimum           | ID pelanggaran stabil, NISN/nama/kelas sumber, waktu kejadian, jenis, kategori, poin kejadian, total poin resmi, pencatat, waktu revisi, dan tombstone. |
| Perubahan balik        | Tidak tersedia pada MVP.                                                                                     |
| Kegagalan sinkronisasi | Tampilkan status gangguan dan waktu data terakhir; data lama tidak dianggap terbaru.                         |
| Kegagalan pemetaan     | Data gagal tidak menimpa data sah dan dicatat untuk Admin IT.                                                |
| Credential sumber      | Endpoint e-Tatib publik tidak memakai token; identitas sumber, HTTPS, exact origin, dan sifat read-only tetap wajib dibuktikan saat admission. |
| Uji koneksi            | Autentikasi, contract/schema minimum, dan identitas sekolah/sumber harus terbukti; HTTP 2xx saja tidak cukup. |
| Snapshot               | Adapter memetakan field resmi ke snapshot internal beserta evidence; raw payload tidak disimpan secara default. |
| Driver production      | Tetap `unavailable` sampai lembar discovery dan admission kontrak e-Tatib disahkan.                          |
| Mode sinkronisasi      | Sinkronisasi pertama/full reconciliation memakai `full`; berikutnya `delta` berdasarkan watermark, dengan full ulang paling lambat tujuh hari. |
| Jadwal                 | Sinkronisasi e-Tatib dijalankan manual oleh Admin IT melalui URL sekali pakai; jadwal otomatis tidak digunakan karena URL tidak disimpan. |
| Normalisasi NISN       | NISN sumber harus 1–10 digit dan ditambah nol di depan menjadi 10 digit. Pencocokan otomatis mensyaratkan NISN canonical dan nama ternormalisasi sama; masking, nilai terlalu panjang, murid tidak ditemukan, atau nama berbeda menjadi konflik tanpa fallback nama. Admin IT dapat membuat mapping lokal manual per pasangan NISN+nama sumber. |

## Dapodik dan identitas sementara

| **Aspek**    | **Ketentuan**                                                                               |
|--------------|---------------------------------------------------------------------------------------------|
| Arah data    | API Dapodik/data siswa atau daftar resmi sekolah melalui Excel ke Aplikasi BK.                                             |
| Data minimum | Identitas murid, kelas, keanggotaan kelas, dan tahun ajaran.                                |
| Fallback     | Bila Dapodik terlambat 2–3 bulan, Admin IT menyiapkan tahun ajaran tanpa input tanggal periode dan mengimpor URL API Siswa sekali pakai atau CSV minimum murid yang tersedia berupa `nisn,nama,rombel,tahun_pelajaran` berdasarkan dasar resmi sekolah; satu berkas dapat memuat beberapa tahun yang sudah dibuat; Koordinator mengaktifkan setelah data dan penugasan yang tersedia lengkap; murid susulan dapat diimpor ke tahun aktif. |
| Status asal  | `school_provisional`, `dapodik`, dan `legacy_unclassified` terpisah dari `is_active`; provider tidak dapat melakukan aktivasi operasional. |
| Scope Guru BK | Roster yang telah diterapkan dapat dipakai untuk layanan setelah tahun ajaran aktif dan sesuai penugasan; provenance sumber tetap tersedia tanpa mengubah hak layanan. |
| Pratinjau    | Impor Excel maupun tarik API menghasilkan pratinjau cocok, baru, berubah, dan konflik tanpa mengubah master; Admin IT memeriksa serta mengonfirmasi sebelum penerapan. |
| Rekonsiliasi | Cocokkan murid hanya berdasarkan NISN exact, tahan konflik, tempelkan identitas sumber pada ID internal yang sama, gunakan field resmi, dan cegah duplikasi. |
| Konflik      | NISN tidak ditemukan atau ganda ditahan untuk pemeriksaan Admin IT; data sah tidak ditimpa. |
| Koreksi      | Perubahan identitas dilakukan pada sumber resmi sekolah/provider lalu diimpor atau disinkronkan kembali.                  |
| Riwayat      | Setiap roster tahun target membuat state membership untuk tahun tersebut; kelas pada layanan/pelanggaran lama berasal dari snapshot konteks dan tidak ditimpa kelas sekarang. |
| Rollover     | Sistem tidak menaikkan kelas atau menentukan lulus/pindah/keluar otomatis; daftar resmi membuat keanggotaan target dan kekurangan tampil sebagai **Perlu Konfirmasi** tanpa memblokir aktivasi keseluruhan. |
| Credential sumber | Akun/token wajib least-privilege dan read-only dengan bukti scope yang disahkan.        |
| Uji koneksi  | Autentikasi, contract/schema minimum, dan identitas sekolah/sumber harus terbukti; HTTP 2xx saja tidak cukup. |
| Snapshot     | Adapter memetakan field resmi ke snapshot internal beserta evidence; snapshot penuh wajib membuktikan tahun ajaran, identitas sumber, pagination, dan completeness. |
| Driver production | Tetap `unavailable` sampai lembar discovery dan admission kontrak Dapodik disahkan.     |
| Perubahan balik | Tidak ada write-back data persiapan, layanan BK, atau koreksi ke Dapodik/e-Tatib.       |

Untuk kedua provider, endpoint outbound harus lolos exact deployment allowlist dan validasi setiap resolusi DNS, lalu koneksi diikat ke alamat tervalidasi dengan Host/SNI yang benar. Metadata/link-local/multicast/unspecified selalu ditolak; private/loopback hanya diterima bila origin exact terdaftar dan flag opt-in private-network provider aktif. Redirect, proxy tak tepercaya, TLS invalid, origin drift, serta jawaban DNS campuran atau berubah ditolak. Contract admission wajib menetapkan batas response/page, pagination, timeout, retry/backoff, rate limit, concurrency, backpressure, hard deadline, dan kebutuhan queue.

# Ketertelusuran

| **Sumber/keputusan**                     | **Kebutuhan terkait**                  | **Pri.**    |
|------------------------------------------|----------------------------------------|-------------|
| Media pencatatan tersebar                | CASE, STU, REP                         | P0          |
| Laporan 30–60 menit                      | REP-01 s.d. REP-04                     | P0          |
| Kerahasiaan konsultasi                   | AUTH-03 s.d. AUTH-07; CONS-01; CONS-02 | P0          |
| Koordinator penanggung jawab operasional | GOV-01; ASN; REP-04                    | P0          |
| Waka memantau seluruh kasus/konsultasi dan membaca proyeksi detail hanya-baca | AUTH-04; AUTH-05; DASH-03; REP-05 | P0 |
| Histori mengikuti scope murid            | AUTH-02; AUTH-04; STU-02; STU-03       | P0          |
| NISN+nama sebelum sinkronisasi           | MD-03; MD-04; CASE-01; NFR-05          | P0          |
| Roster tahun ajaran Excel/API dan aktivasi manual | MD-05 s.d. MD-14; NFR-13 | P0 |
| Pratinjau dan penerapan roster            | MD-10 s.d. MD-12; NFR-13               | P0          |
| Rolling tidak otomatis                   | ASN-03; ASN-06                         | P0          |
| Status pelayanan konsisten dan catatan terminal terkunci | CASE-04; CASE-13; CASE-14; CONS-03 | P0 |
| Proses keluar murid terkendali dan tidak ditentukan provider | MD-15 s.d. MD-18; AUTH-03; AUD-01 | P0 |
| Kode kasus hanya untuk kebutuhan internal | CASE-15; AUD-01                        | P0          |
| Admin IT mengelola akun dan password sementara | ACC-01; ACC-02; AUTH-06           | P0          |
| Dashboard role-aware tanpa pembaca audit | DASH-01 s.d. DASH-03; AUD-01           | P0          |
| Konfigurasi koneksi aman melalui PG-501  | INT-05 s.d. INT-07; NFR-09             | P0          |
| Admission dan verifikasi sumber          | INT-08 s.d. INT-11                     | P0          |
| Mirror dan penanda e-Tatib               | INT-12; INT-13; DASH-05                | P0          |
| Endpoint, DNS, dan konsistensi operasi    | NFR-10; NFR-11                         | P0          |
| Batas adapter dan backpressure            | NFR-12                                 | P0          |
| Retensi minimum tiga tahun               | NFR-08                                 | P0          |
| Prestasi dikelola Waka                    | ACH-01 s.d. ACH-05                     | P0 bertahap |
| Wali kelas dan murid                     | Struktur peran P1                      | P1          |

# Ketergantungan yang belum dikunci

| **ID** | **Area**           | **Kondisi**                                                                            | **Dampak**                                        |
|--------|--------------------|----------------------------------------------------------------------------------------|---------------------------------------------------|
| DEP-01 | e-Tatib            | Driver HTTP dan validator kontrak `etatib-school-v1` telah disiapkan, tetapi exact origin production, identitas sumber, fixture resmi, bukti jaringan, prosedur gangguan, serta persetujuan tertulis belum disahkan. | Driver efektif tetap `unavailable`; flag admission tidak boleh diaktifkan sebelum seluruh gate terpenuhi. |
| DEP-02 | Dapodik            | Fondasi konfigurasi boleh tersedia, tetapi mekanisme resmi, autentikasi, origin/endpoint, identitas sekolah, schema, pagination, completeness, fixture sintetis, limits, resilience, TLS/proxy/jaringan, dan konflik NISN belum disahkan. | Uji, aktivasi, dan sinkronisasi production tetap diblokir; driver `unavailable`. |
| DEP-03 | Istilah layanan    | Daftar label/nilai referensi operasional sekolah yang belum disahkan tetap dapat berubah tanpa mengubah cardinality/lifecycle yang sudah dikunci. | Label referensi dapat berubah. |
| DEP-06 | Dokumen/retensi    | Format, ukuran, akses, pemulihan, dan prosedur penghapusan setelah minimum tiga tahun. | Unggah dan penghapusan belum dikunci.             |
| DEP-07 | Template laporan   | Struktur data, orientasi A4, peran penandatangan, cetak/PDF browser, dan `.xlsx` sudah dikunci; aset logo serta jarak tanda tangan final masih menunggu format resmi sekolah. | Kepala Sekolah/NIP tidak boleh di-hardcode; perubahan visual tidak boleh mengubah filter, scope, urutan, dataset, atau peran penandatangan. |

# Sumber dan riwayat versi

Acuan: PRD Aplikasi BK v1.1, kuesioner kebutuhan, contoh pencatatan berjalan, diskusi perancangan, inventaris antarmuka, keputusan validasi Koordinator BK/Guru BK serta Waka Kesiswaan sampai 13 Agustus 2026, keputusan arsitektur fondasi konfigurasi integrasi tanggal 23 Agustus 2026, amandemen keterlambatan Dapodik 9 September 2026, alur operasional BK 12 September 2026, Portal Waka 13 September 2026, laporan tiga tab 14 September 2026, penyederhanaan 15 September 2026, Revisi SIBK 3.2 yang disetujui 17 September 2026, serta revisi laporan catatan layanan 21 sampai 23 September 2026.

| **Versi** | **Tanggal**     | **Perubahan**                                                                                                                                                                                                                  |
|-----------|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 0.3       | 12 Agustus 2026 | Menyelaraskan kebutuhan fungsional, data, nonfungsional, integrasi, dan ketertelusuran dengan PRD v0.5.                                                                                                                        |
| 1.0       | 15 Agustus 2026 | Menambahkan tata kelola Koordinator, detail kasus terkoordinasi untuk Waka, histori lintas guru, identitas sementara dan rekonsiliasi, akun Admin IT, laporan gabungan, rolling nonotomatis, serta retensi minimum tiga tahun. |
| 1.1       | 23 Agustus 2026; diamandemen 9, 12, 13, 14, dan 15 September 2026 | Menambahkan fondasi konfigurasi/integrasi, fallback data persiapan, empat status pelayanan, edit terminal beralasan, arsip layanan, proses keluar murid, password sementara, audit tanpa UI pembaca, panel dashboard role-aware, Portal Waka berbasis tujuan, serta tiga tab rekap laporan Guru BK/Koordinator. |
| 1.1       | 17 September 2026 | Revisi SIBK 3.2: tiga status kasus, satu tindak lanjut terkini, konsultasi mandiri, Waka hanya-baca, audit perubahan-delta, autosave lokal, sorting allowlist, dan optimistic concurrency. |
| 1.1       | 21 September 2026 | Mengganti tiga tab rekap Guru BK/Koordinator dengan daftar per catatan, preview rekap/per catatan, cetak/PDF browser, dan Excel `.xlsx`. |
| 1.1       | 22 September 2026 | Menambah Hari/Tanggal dan Jenis Masalah pada daftar, disclosure narasi serta Hasil Layanan, dan menghapus akses modal/cetak individual dari halaman laporan. |
| 1.1       | 22 September 2026 | Menetapkan terminologi Penanganan, validasi kelas per tahun ajaran, seluruh dataset dokumen tanpa pagination UI, orientasi landscape/portrait, serta tanda tangan Koordinator/Guru BK dan Waka tanpa NIP. |
| 1.1       | 22 September 2026 | Menyederhanakan dokumen rekap menjadi enam kolom putih polos dan menghapus unduhan Word. |
| 1.1       | 23 September 2026 | Mengubah rekap menjadi A4 portrait dan menambahkan kolom Guru BK sebelum Keterangan. |
| 1.1       | 23 September 2026 | Menyatukan UI laporan Waka dengan halaman laporan operasional dalam mode hanya-baca, membatasi kolom ke struktur dokumen Koordinator, serta menolak preview/cetak/unduhan Waka. |
| 1.1       | 23 September 2026 | Menampilkan Hasil pada daftar Guru BK/Koordinator, menyatukan detail Latar Belakang Masalah dan Penanganan pada satu kontrol, serta menambahkan Sumber dan Tindak Lanjut terbaru ke Keterangan dokumen. |
| 1.1       | 24 September 2026 | Menyelaraskan roster Excel/API Dapodik, aktivasi tahun ajaran tanpa tanggal, membership/penugasan berbasis tahun tanpa periode tanggal, owner kasus tetap tanpa periode, snapshot konteks layanan, penghapusan `decision_number`, mirror/penanda e-Tatib read-only, dua narasi awal layanan, multi-tindak-lanjut, penyelesaian Hasil / Ringkasan, dan prestasi yang dikelola Waka tanpa verifikasi/bukti. |
