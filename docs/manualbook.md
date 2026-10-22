# Buku Panduan Ruang BK

Panduan penggunaan dan pemasangan server sekolah

Edisi 23 Oktober 2026. Acuan aplikasi 741d4ac.

Ruang BK membantu sekolah mencatat layanan bimbingan dan konseling (BK), menindaklanjuti permasalahan murid, dan membaca laporan sesuai kewenangan masing-masing peran.

Buku ini dapat dibaca oleh guru yang ingin memahami alur layanan, termasuk yang belum menggunakan aplikasinya. Untuk menjalankan petunjuk di aplikasi, Anda memerlukan akun dengan peran dan penugasan yang sesuai. Panduan pekerjaan dibagi untuk Admin IT, Koordinator BK, Guru BK, dan Waka Kesiswaan.

Mulailah dari penggunaan umum pada Bab 1, lalu pilih panduan peran Anda pada Bab 2 sampai Bab 5. Bab 6 khusus bagi petugas server untuk pemasangan dan pemulihan. Bab 7 berisi latihan, sedangkan Bab 8 menjelaskan istilah yang digunakan.

Setiap petunjuk fitur memuat gambar, langkah, hasil yang perlu diperiksa, serta ketentuan penting. Panduan pengguna mengikuti menu, tab, tombol, dan tautan yang tersedia sesuai peran akun. Alamat pemeriksaan dan perintah pada Bab 6 khusus untuk petugas server.

Tangkapan layar aplikasi memakai data contoh lokal, bukan identitas dan catatan murid sekolah. Warna, logo, nama, tanggal, pilihan tahun, dan angka pada instalasi sekolah dapat berbeda. Diagram server diberi label CONTOH untuk membedakannya dari bukti pemasangan. Gambar hanya menunjukkan layar yang sedang dibahas; ikuti langkah tertulis saat tampilan berubah setelah penyimpanan.

# 1 Alur aplikasi dan penggunaan dasar

## 1.1 Memilih panduan menurut peran

![Gambar 1. Peta menu aplikasi yang tersedia menurut peran pengguna](manualbook/gambar/sitemap.png)

1. **Admin IT** menggunakan Bab 5 untuk akun, kelas, tahun persiapan, daftar murid, API, sinkronisasi, logo, dan tema. API adalah penghubung untuk mengambil data dari aplikasi lain. Hak mengelola pengaturan tidak memberi Admin IT akses ke isi konseling.
2. **Guru BK** menggunakan Bab 2 untuk murid binaan, pencatatan layanan, tindak lanjut, riwayat, dan rekap.
3. **Koordinator BK** menggunakan Bab 3 untuk pemantauan sekolah, pembagian kelas, dan aktivasi tahun. Hak baca lintas guru tidak memberi hak mengubah catatan guru lain. Untuk mencatat layanan, akun juga harus memiliki peran Guru BK dan kelas binaan aktif.
4. **Waka Kesiswaan** menggunakan Bab 4 untuk ringkasan pemantauan dan pengelolaan prestasi. Peran Waka tidak memberi hak mengunduh rekap konseling atau mengubah layanan Guru BK.

**Batas akun:** kombinasi peran yang diperbolehkan hanya Guru BK bersama Koordinator BK. Admin IT dan Waka memakai akun terpisah. Setiap pemilik akun mempunyai Dashboard dan Akun Saya; menu lain mengikuti peran dan penugasan.

## 1.2 Memahami urutan kerja sekolah

![Gambar 2. Urutan persiapan data dan penugasan sebelum layanan BK digunakan](manualbook/gambar/alur-operasional.png)

1. Petugas memasang aplikasi, basis data, akses aman HTTPS, dan penjadwal tugas, lalu membuat akun Admin IT pertama.
2. Admin IT membuat akun, menata Data Kelas, menyiapkan tahun ajaran, serta meninjau dan mengimpor daftar murid. Impor berarti memasukkan data dari berkas atau sistem sumber ke Ruang BK.
3. Koordinator menugaskan Guru BK pada kelas yang dipakai, memeriksa kesiapan, lalu mengaktifkan tahun ajaran.
4. Admin IT meninjau e-Tatib, yaitu aplikasi sumber catatan pelanggaran tata tertib murid. Admin IT mencocokkan identitas sebelum menyinkronkan data atau mengaktifkan jadwal otomatis.
5. Guru BK mencatat dan menangani layanan; Waka memantau ringkasan dan mengelola prestasi. Guru atau Koordinator membuat rekap sesuai kewenangan.
6. Saat tahun berganti, ulangi persiapan daftar murid dan penugasan pada tahun baru. Riwayat lama tetap mengikuti cakupan akses serta ketentuan retensi (masa penyimpanan data).

## 1.3 Masuk dan penggantian sandi pertama
### Masuk menggunakan akun sekolah

![Gambar 3. Halaman Masuk ke Ruang BK dengan kolom email dan kata sandi](manualbook/gambar/v2-login.png)

**Sebelum mulai:** akun pribadi sudah dibuat dan masih aktif. Gunakan alamat aplikasi sekolah yang diawali HTTPS.

1. Buka halaman masuk pada alamat yang diserahkan sekolah.
2. Isi **Email** dan **Kata sandi** yang diberikan Admin IT.
3. Jika perlu memeriksa ketikan, gunakan ikon mata hanya saat layar Anda tidak terlihat orang lain.
4. Tekan **Masuk**. Akun yang sudah memakai sandi pribadi menuju Dashboard; akun baru atau akun yang sandinya diatur ulang menuju penggantian sandi sementara.
5. Bila gagal, periksa ketikan dan status akun. Setelah beberapa percobaan gagal, tunggu pembatasan percobaan berakhir sebelum mencoba lagi.

**Hasil:** sesi masuk menggunakan peran akun sendiri. Bila sandi sementara sudah kedaluwarsa, Admin IT perlu menerbitkan ulang; tidak ada sandi produksi umum dalam buku ini.
### Mengganti sandi sementara

![Gambar 4. Halaman wajib mengganti sandi sementara sebelum menggunakan aplikasi](manualbook/gambar/v2-ganti-sandi-awal.png)

**Sebelum mulai:** berhasil masuk menggunakan sandi sementara yang belum kedaluwarsa. Masa berlaku bawaan adalah 24 jam.

1. Pada halaman penggantian sandi, isi **Kata sandi saat ini** dengan sandi sementara.
2. Isi sandi baru minimal delapan karakter yang mengandung huruf dan angka, serta berbeda dari sandi saat ini.
3. Ulangi sandi baru pada kolom konfirmasi.
4. Tekan **Simpan**. Jika ditolak, perbaiki kolom yang disebut; setelah berhasil, lanjutkan ke Dashboard.

**Hasil:** sandi sementara diganti sandi pribadi. Jangan memotret, menyalin ke buku umum, atau membagikan sandi kepada pengguna lain.

## 1.4 Menggunakan daftar dan formulir
### Mencari, menyaring, mengurutkan, dan membuka halaman daftar

![Gambar 5. Contoh pencarian dan penyaringan pada daftar layanan BK](manualbook/gambar/v2-filter-daftar.png)

Pola daftar serupa dipakai pada layanan, murid, akun, prestasi, dan laporan. Menyaring atau menggunakan **Filter** berarti membatasi data yang ditampilkan, misalnya hanya murid dari kelas tertentu. Pilihan penyaring mengikuti halaman yang sedang dibuka.

1. Isi pencarian dan pilih penyaring yang tersedia, misalnya kelas, jenis, status, atau tahun ajaran.
2. Tekan **Filter**. Gunakan **Reset** bila tersedia untuk mengembalikan pilihan awal.
3. Klik judul kolom bertanda panah untuk mengurutkan. Kolom tanpa kontrol urut tidak dapat dipakai mengurutkan.
4. Gunakan nomor halaman untuk melihat bagian daftar berikutnya; bila daftar menyediakan ikon kaca pembesar, gunakan untuk membaca rincian yang diizinkan.

**Hasil:** daftar menampilkan data yang sesuai pilihan penyaring dan kewenangan. Jika murid tidak ditemukan, periksa penyaring, tahun ajaran, dan cakupan kelas sebelum menyimpulkan datanya terhapus.
### Memeriksa draf dan hasil simpan formulir

![Gambar 6. Formulir pencatatan dengan isian wajib, tombol Kosongkan, dan tombol Simpan](manualbook/gambar/v2-draft-form.png)

Beberapa formulir menyimpan draf, yaitu isian sementara pada peramban (browser) perangkat. Draf belum menjadi catatan resmi sampai penyimpanan berhasil.

1. Lengkapi kolom bertanda bintang. Untuk murid, pilih saran yang benar agar identitas tidak salah.
2. Tekan **Simpan**.
3. Periksa pesan berhasil dan kemunculan catatan pada daftar.
4. Bila pesan **belum tersimpan** muncul, koreksi kolom yang disebut dan simpan lagi. Jangan menganggap draf sebagai bukti data sudah masuk.
5. Gunakan **Kosongkan** untuk membuang draf yang tidak dipakai. Saat Anda menutup formulir yang isinya berubah, aplikasi dapat meminta konfirmasi.
6. Jika perubahan ditolak karena catatan telah diperbarui di tempat lain, muat ulang dan bandingkan isinya sebelum mengulang.

**Batas:** draf berada pada peramban perangkat; jangan meninggalkan catatan pada komputer bersama. Proses **Keluar** membersihkan draf pengguna pada peramban tersebut.

## 1.5 Akun Saya dan keluar
### Memeriksa akun sendiri

![Gambar 7. Halaman Akun Saya berisi identitas peran status dan tombol sandi](manualbook/gambar/v2-akun-saya.png)

Tampilan akun menyesuaikan pengguna yang sedang masuk. Contoh gambar dapat memakai peran berbeda dari akun Anda.

1. Buka **Akun Saya** melalui menu samping atau menu profil kanan atas.
2. Periksa nama, email, peran, status, login terakhir, dan konteks tahun ajaran yang ditampilkan.
3. Untuk koreksi nama, email, atau peran, hubungi Admin IT sesuai penugasan sekolah.

**Hasil:** identitas akun dapat diperiksa tanpa memakai akun pengguna lain. Pengguna tidak mengubah perannya sendiri melalui halaman ini.
### Mengganti sandi pribadi

![Gambar 8. Formulir Ubah Sandi dari Akun Saya](manualbook/gambar/v2-ubah-sandi.png)

**Sebelum mulai:** masih mengetahui sandi saat ini. Jika lupa, minta Admin IT menerbitkan sandi sementara yang baru.

1. Pada **Akun Saya**, tekan **Ubah Sandi**.
2. Isi sandi saat ini, sandi baru, dan konfirmasinya. Sandi baru mengikuti ketentuan pada bagian 1.3.
3. Tekan **Simpan** dan periksa pesan berhasil. Jika sandi saat ini atau konfirmasi tidak sesuai, perbaiki dahulu.

**Hasil:** sandi baru berlaku dan sesi lain akun diputus. Simpan sandi secara privat; jangan menaruhnya di panduan atau tangkapan layar.
### Keluar dari aplikasi

![Gambar 9. Menu profil dengan aksi Keluar](manualbook/gambar/v2-keluar.png)

Keluar setelah pekerjaan selesai, terutama pada komputer bersama.

1. Selesaikan penyimpanan catatan atau buang draf yang tidak dipakai.
2. Buka menu profil kanan atas atau **Akun Saya**, lalu tekan **Keluar**.
3. Pastikan halaman masuk kembali tampil sebelum meninggalkan perangkat.

**Hasil:** sesi perangkat diakhiri dan draf pengguna pada peramban dibersihkan. Menutup tab saja tidak menggantikan tombol Keluar.

# 2 Panduan Guru BK

Guru BK bekerja dengan murid di kelas binaan aktifnya. Koordinator BK dapat membaca catatan dari berbagai guru, tetapi tetap tidak dapat mengubah catatan milik guru lain. Akun yang memiliki peran Koordinator BK sekaligus Guru BK tetap memerlukan kelas binaan aktif untuk mencatat layanan.

Riwayat layanan dari tahun ajaran lama dan layanan sebelum murid resmi keluar dapat dibaca sesuai hak akses serta masa penyimpanan data. Catatan lama tersebut tidak dapat diubah atau diarsipkan.

Menu kerja Guru BK adalah **Dashboard**, **Layanan BK**, **Data Murid**, dan **Laporan**. Pengelolaan akun pribadi dijelaskan pada Bab 1.

## 2.1 Dashboard dan kelas binaan

### Memeriksa ringkasan dan kelas binaan

![Gambar 10. Dashboard Guru BK dengan pilihan Tahun Ajaran, kartu ringkasan, aktivitas, dan tab Kelas Binaan](manualbook/gambar/v2-guru-dashboard.png)

**Tujuan:** melihat murid binaan dan layanan terbaru. **Sebelum mulai:** akun Guru BK aktif. Kelas binaan hanya terisi setelah ada penugasan.

1. Buka **Dashboard** dan pilih **Tahun Ajaran**.
2. Tekan **Filter** untuk menerapkan pilihan atau **Reset** untuk kembali ke pilihan awal.
3. Baca kartu murid binaan, permasalahan, tindak lanjut, dan e-Tatib. Aktivitas terbaru adalah ringkasan, bukan tombol edit.
4. Pada **Kelas Binaan**, pilih **Kelas 10**, **Kelas 11**, atau **Kelas 12**. Tekan **Lihat Semua** jika masih ada kelas yang belum tampak.
5. Untuk mencatat layanan, gunakan **Catat Permasalahan** atau **Catat Konsultasi**. Formulir yang terbuka sama dengan yang ada di Layanan BK.

**Hasil:** angka dan kelas mengikuti tahun serta cakupan Guru BK. **Jika kosong:** periksa tahun aktif, daftar murid, dan penugasan Koordinator.

## 2.2 Catatan Permasalahan

### Mencari dan membaca permasalahan

![Gambar 11. Tab Catatan Permasalahan dengan Cari Murid, penyaring Tindak Lanjut, riwayat, dan ikon rincian](manualbook/gambar/v2-kasus-daftar.png)

**Tujuan:** menemukan kasus dan membaca penanganannya. **Sebelum mulai:** akun dapat membaca layanan murid terkait.

1. Buka **Layanan BK > Catatan Permasalahan**.
2. Isi **Cari Murid** atau pilih **Tindak Lanjut**, lalu tekan **Filter**. Tekan **Reset** bila tersedia untuk menghapus penyaring.
3. Klik label di kolom **Tindak Lanjut** untuk membaca riwayat.
4. Klik ikon kaca pembesar untuk membaca sumber dan isi catatan di bawah baris.
5. Tekan ikon **Lihat selengkapnya** untuk membaca rincian penuh. Mengklik area baris saja tidak membukanya.

**Hasil:** daftar dan rincian hanya memuat kasus yang boleh dibaca. **Jika tidak ditemukan:** periksa penyaring, tahun atau kelas binaan, dan status catatan.

### Mencatat permasalahan dari murid atau rujukan

![Gambar 12. Jendela Catat Permasalahan untuk sumber selain e-Tatib, identitas murid, tanggal, jenis masalah, dan isi catatan](manualbook/gambar/v2-kasus-buat.png)

**Tujuan:** membuat catatan awal kasus. **Sebelum mulai:** peran Guru BK, tahun ajaran aktif, dan kelas binaan aktif.

1. Tekan **Catat Permasalahan** pada tab atau Dashboard.
2. Pilih **Sumber** berupa murid datang sendiri, temuan Guru BK, atau rujukan. Jika rujukan, isi **Sumber Rujukan**.
3. Pada **NISN**, cari nomor induk siswa nasional (NISN) atau nama, lalu pilih saran yang benar agar identitas dan rombongan belajar (rombel) terisi. Bila murid belum masuk data utama, isi NISN dan nama sementara, lalu pilih rombel dari kelas binaan Anda.
4. Periksa **Tanggal Layanan** dan pilih **Jenis Masalah**.
5. Isi **Latar Belakang** dan **Penanganan**. **Ringkasan** dapat diisi saat hasil tersedia.
6. Tekan **Simpan**.

**Hasil:** kasus baru muncul berstatus **Sedang Proses**. **Jika ditolak:** periksa kolom wajib, identitas atau NISN, dan rombel binaan. **Kosongkan** membuang draf setelah konfirmasi bila isian telah berubah.

### Mencatat permasalahan dengan referensi e-Tatib

![Gambar 13. Jendela Catat Permasalahan dengan sumber e-Tatib dan data pelanggaran yang tersedia untuk dipilih](manualbook/gambar/v2-kasus-etatib.png)

**Tujuan:** mencatat layanan berdasarkan data pelanggaran resmi yang sudah tersinkron. **Sebelum mulai:** hak pencatatan Guru BK dan data e-Tatib yang dapat dipilih.

1. Buka **Catat Permasalahan**, lalu pilih **Sumber: e-Tatib**.
2. Cari NISN atau nama, lalu pilih pelanggaran yang tepat.
3. Periksa tanggal, kategori, poin, serta muridnya. Tekan **Ganti** bila salah pilih.
4. Jika rombel e-Tatib tidak cocok dengan penugasan, pilih **Rombel Layanan** yang Anda bina.
5. Pilih **Tanggal Layanan** dan **Jenis Masalah**.
6. Isi **Latar Belakang** serta **Penanganan**, lalu tekan **Simpan**.

**Hasil:** kasus tersimpan dengan sumber e-Tatib; data pelanggaran sumber tetap menjadi rujukan. **Jika data tidak ada:** periksa sinkronisasi bersama Admin IT; jangan membuat ulang pelanggaran sumber berdasarkan ingatan.

### Mengedit permasalahan

![Gambar 14. Formulir Edit Permasalahan dengan identitas dan sumber yang tidak dapat diubah serta isian yang dapat diperbarui](manualbook/gambar/v2-kasus-edit.png)

**Tujuan:** memperbaiki tanggal, jenis, atau isi catatan kasus sendiri. **Sebelum mulai:** Anda pemilik kasus dan tahun catatan masih aktif.

1. Pada tab **Catatan Permasalahan**, cari kasus dan tekan ikon **Edit**. Jika kasus sudah **Selesai**, setujui konfirmasi untuk membuka formulir.
2. Periksa nama, NISN, rombel, serta sumber yang tampil sebagai informasi tetap.
3. Perbarui tanggal layanan, jenis masalah, **Latar Belakang**, **Penanganan**, atau **Ringkasan** yang perlu diperbaiki.
4. Tekan **Simpan**.

**Hasil:** catatan terbaru tersimpan tanpa mengganti murid, sumber, pemilik, atau status. **Jika catatan telah diperbarui pengguna lain:** muat ulang sebelum mengedit lagi; perubahan pengguna lain tidak ditimpa.

### Menambahkan tindak lanjut kasus

![Gambar 15. Jendela Tambah Tindak Lanjut berisi jenis serta tanggal](manualbook/gambar/v2-kasus-tindaklanjut.png)

**Tujuan:** mencatat langkah penanganan lanjutan. **Sebelum mulai:** kasus milik Anda masih aktif dan jenis tindak lanjut belum dipakai pada kasus itu.

1. Pada baris kasus, klik label **Tindak Lanjut** untuk membuka riwayat.
2. Tekan **Tambah Tindak Lanjut**.
3. Pilih **Jenis Tindak Lanjut** yang tersedia dan isi **Tanggal Tindak Lanjut**.
4. Tekan **Simpan**, lalu buka lagi label pada baris untuk memastikan catatan masuk.

**Hasil:** riwayat bertambah; penambahan pertama mengubah status kasus menjadi **Tindak Lanjut**. **Jika jenis tidak tersedia:** jenis itu sudah dipakai atau semua jenis aktif telah habis. Satu jenis tidak boleh dicatat dua kali pada kasus yang sama.

### Menyelesaikan permasalahan

![Gambar 16. Aksi Selesaikan Permasalahan pada kasus aktif dan dialog konfirmasinya](manualbook/gambar/v2-kasus-selesai.png)

**Tujuan:** menetapkan hasil akhir kasus sendiri. **Sebelum mulai:** kasus belum selesai dan **Ringkasan** hasil telah disimpan; tindak lanjut tidak wajib.

1. Bila hasil belum ada, tekan **Edit**, isi **Ringkasan**, dan **Simpan**.
2. Kembali ke baris kasus, tekan ikon **Selesaikan Permasalahan**.
3. Periksa nama murid pada konfirmasi, lalu tekan **Selesaikan**.

**Hasil:** status menjadi **Selesai** dan riwayat tetap dapat dibaca di profil murid. **Jika aksi ditolak:** periksa Ringkasan, kepemilikan, dan apakah versi kasus berubah sejak dibuka.

### Mengarsipkan permasalahan

![Gambar 17. Dialog Hapus Kasus BK pada catatan yang belum selesai](manualbook/gambar/v2-kasus-arsip.png)

**Tujuan:** mengeluarkan catatan yang tidak perlu tampil dari daftar aktif. **Sebelum mulai:** kasus milik Anda, tahun aktif, dan kasus belum selesai.

1. Temukan kasus di **Catatan Permasalahan** dan tekan ikon **Arsipkan**.
2. Periksa murid dan kelas pada dialog.
3. Setujui tindakan dengan tombol **Hapus** pada dialog aplikasi.

**Hasil:** kasus tidak tampil lagi dalam daftar aktif, tetapi tetap tersimpan sebagai arsip dan tidak dihapus permanen. **Jika ikon tidak aktif:** kasus sudah selesai atau Anda tidak mempunyai hak arsip.

## 2.3 Sesi Bimbingan & Konsultasi

### Mencari dan membaca konsultasi

![Gambar 18. Tab Sesi Bimbingan dan Konsultasi dengan pencarian, penyaring Jenis Masalah, dan ikon rincian](manualbook/gambar/v2-konsultasi-daftar.png)

**Tujuan:** membaca catatan konsultasi yang boleh diakses. **Sebelum mulai:** Anda memiliki izin membaca layanan murid tersebut. Koordinator dapat membaca catatan lintas guru.

1. Buka **Layanan BK > Sesi Bimbingan & Konsultasi**.
2. Cari nama murid atau pilih **Jenis Masalah**, lalu tekan **Filter**. Klik judul kolom yang mendukung pengurutan bila perlu.
3. Baca **Ringkasan / Hasil** pada daftar.
4. Klik ikon kaca pembesar untuk membaca latar belakang dan penanganan. Buka rincian penuh bila perlu.

**Hasil:** konsultasi tampil sebagai catatan mandiri tanpa status atau tindak lanjut kasus. **Jika kosong:** periksa pencarian dan cakupan murid.

### Mencatat konsultasi

![Gambar 19. Jendela Catat Konsultasi dengan pencarian NISN dan kolom isi catatan wajib](manualbook/gambar/v2-konsultasi-buat.png)

**Tujuan:** mencatat satu sesi layanan. **Sebelum mulai:** peran Guru BK dan kelas binaan aktif.

1. Tekan **Catat Konsultasi** pada tab atau Dashboard.
2. Pada **NISN**, ketik NISN atau nama dan pilih murid dari saran. Bila belum tersedia, isi NISN dan nama, lalu pilih rombel binaan secara manual.
3. Isi tanggal, **Jenis Masalah**, **Latar Belakang Masalah / Permasalahan**, **Penanganan**, serta **Ringkasan / Hasil**.
4. Tekan **Simpan**.

**Hasil:** satu catatan konsultasi muncul di daftar dan profil murid. **Jika gagal:** semua kolom cerita layanan, termasuk Ringkasan / Hasil, wajib diisi pada versi ini. **Kosongkan** membersihkan isian dan draf pada perangkat.

### Mengedit konsultasi

![Gambar 20. Formulir Edit Konsultasi dengan uraian layanan dan tombol Simpan Perubahan](manualbook/gambar/v2-konsultasi-edit.png)

**Tujuan:** memperbaiki catatan konsultasi sendiri. **Sebelum mulai:** Anda pencatat, masih berwenang atas murid, dan tahun catatan aktif.

1. Pada tab konsultasi, tekan ikon **Edit** pada baris milik Anda.
2. Periksa identitas murid yang tidak dapat diganti. Ubah tanggal, jenis, atau uraian layanan yang diperlukan.
3. Tekan **Simpan Perubahan** dan periksa Ringkasan / Hasil di daftar.

**Hasil:** konsultasi diperbarui. **Jika catatan telah diperbarui pengguna lain:** muat ulang catatan sebelum mencoba lagi; layanan tahun lama dan catatan guru lain tidak dapat diedit.

### Mengarsipkan konsultasi

![Gambar 21. Dialog Hapus Konsultasi dengan identitas target dan konfirmasi](manualbook/gambar/v2-konsultasi-arsip.png)

**Tujuan:** mengarsipkan konsultasi sendiri. **Sebelum mulai:** pencatat masih berwenang dan tahun catatan aktif.

1. Cari konsultasi pada tab **Sesi Bimbingan & Konsultasi**.
2. Tekan ikon **Arsipkan**. Periksa identitas murid pada dialog.
3. Konfirmasi dengan tombol **Hapus** yang ditampilkan dialog.

**Hasil:** konsultasi tidak tampil sebagai catatan aktif, tetapi tetap tersimpan sebagai arsip; tombol ini tidak menghapusnya secara permanen. **Jika ikon tidak ada:** periksa kepemilikan dan tahun ajaran.

## 2.4 Progres penanganan pengunduran diri

### Mencari catatan dan riwayat progres

![Gambar 22. Tab Pengunduran Diri dengan penyaring Progres, tombol riwayat, dan ikon catatan awal](manualbook/gambar/v2-pengunduran-daftar.png)

**Tujuan:** memeriksa perkembangan berkas dan catatan awal. **Sebelum mulai:** akses baca layanan murid; Koordinator membaca lintas guru.

1. Buka **Layanan BK > Pengunduran Diri**.
2. Isi **Cari Murid** atau pilih **Progres**, lalu tekan **Filter**.
3. Klik label progres untuk membaca **Riwayat Progres Penanganan**.
4. Klik ikon kaca pembesar untuk membaca **Catatan** awal di bawah baris.

**Hasil:** progres terbaru dan riwayatnya terlihat. **Jika kosong:** periksa pilihan penyaring. Catatan pengunduran diri berbeda dari keputusan resmi keluar.

### Mencatat penanganan pengunduran diri

![Gambar 23. Jendela Catat Pengunduran Diri dengan pilihan murid, tanggal pencatatan, dan Catatan](manualbook/gambar/v2-pengunduran-buat.png)

**Tujuan:** membuat catatan awal penanganan. **Sebelum mulai:** peran Guru BK, murid dalam penugasan aktif, dan belum mempunyai catatan pengunduran diri.

1. Tekan **Catat Pengunduran Diri** dari tab.
2. Ketik nama atau NISN, lalu pilih murid dari saran.
3. Periksa nama dan rombel yang terisi.
4. Isi **Tanggal pencatatan** dan **Catatan**, kemudian tekan **Simpan**.

**Hasil:** satu catatan awal tersimpan dengan progres **Berkas masih diproses**; status aktif murid tidak berubah. **Jika murid tidak tersedia:** periksa penugasan atau apakah ia sudah memiliki catatan awal.

### Menambahkan progres penanganan

![Gambar 24. Jendela Tambah Progres Penanganan dengan pilihan tahap dan Tanggal](manualbook/gambar/v2-pengunduran-progres.png)

**Tujuan:** merekam perkembangan setelah catatan awal. **Sebelum mulai:** murid masih dalam penugasan Guru BK aktif dan catatan awal tersedia.

1. Klik label progres pada baris untuk membuka riwayat.
2. Tekan **Tambah Progres Penanganan**.
3. Pilih **Berkas masih diproses**, **Berkas masih di BK**, atau **Berkas sudah masuk TU**.
4. Isi **Tanggal** dalam rentang sejak catatan awal hingga hari ini.
5. Tekan **Simpan**, lalu buka riwayat lagi untuk memeriksa catatan baru.

**Hasil:** riwayat bertambah; progres yang tampil mengikuti entri bertanggal terbaru. **Jika menambah tanggal lebih lama:** entri tetap disimpan tetapi progres terkini tidak berubah.

### Mengedit catatan awal pengunduran diri

![Gambar 25. Jendela Edit Pengunduran Diri dengan tanggal pencatatan dan Catatan awal](manualbook/gambar/v2-pengunduran-edit.png)

**Tujuan:** mengoreksi catatan awal, bukan menimpa riwayat progres. **Sebelum mulai:** murid masih dalam penugasan Guru BK aktif.

1. Tekan ikon **Edit** pada tab **Pengunduran Diri**.
2. Periksa nama dan NISN yang tidak dapat diubah. Ubah **Tanggal pencatatan** atau **Catatan** sesuai kebutuhan.
3. Tekan **Simpan Perubahan**.

**Hasil:** catatan awal diperbarui dan riwayat progres tetap terpisah. **Jika ditolak:** periksa tanggal yang tidak boleh melewati hari ini dan kewenangan kelas.

### Menghapus catatan penanganan pengunduran diri

![Gambar 26. Dialog Hapus penanganan pengunduran diri yang meminta konfirmasi target murid](manualbook/gambar/v2-pengunduran-hapus.png)

**Tujuan:** menghapus catatan penanganan yang dibuat keliru. **Sebelum mulai:** murid masih dalam penugasan Guru BK aktif.

1. Cari baris murid pada tab **Pengunduran Diri**.
2. Tekan ikon **Hapus**. Periksa nama murid pada dialog.
3. Konfirmasi **Hapus** hanya setelah memastikan catatan yang dipilih.

**Hasil:** catatan penanganan keluar dari daftar; tindakan ini tidak mengubah keputusan resmi keluar murid. **Jika aksi tidak ada:** periksa hak atas murid.

## 2.5 Data Murid dan prestasi baca

### Mencari murid

![Gambar 27. Daftar Murid dengan pencarian nama atau NISN, pilihan Kelas, dan jumlah riwayat](manualbook/gambar/v2-murid-daftar.png)

**Tujuan:** menemukan profil murid dalam cakupan. **Sebelum mulai:** akun Guru BK atau Koordinator BK aktif.

1. Buka **Data Murid**.
2. Isi **Cari murid** dengan nama atau NISN dan pilih **Kelas**, lalu tekan **Filter**.
3. Baca jumlah permasalahan, poin pelanggaran, konsultasi, dan prestasi.
4. Klik nama murid untuk membuka profilnya.

**Hasil:** **Profil Murid** terbuka. **Jika tidak ditemukan:** periksa kelas dan tahun aktif atau minta Admin IT memeriksa daftar murid.

### Membaca profil dan riwayat permasalahan

![Gambar 28. Profil Murid dengan identitas, ringkasan, riwayat kelas, dan tab Permasalahan](manualbook/gambar/v2-murid-profil.png)

**Tujuan:** melihat konteks murid sebelum menangani layanan. **Sebelum mulai:** profil murid dapat dibaca menurut cakupan peran.

1. Klik nama murid pada **Data Murid**.
2. Periksa NISN, kelas, tahun ajaran, ringkasan layanan, poin, prestasi, dan riwayat kelas.
3. Buka tab **Permasalahan**; klik kaca pembesar pada baris untuk sumber, latar belakang, dan penanganan yang diizinkan.

**Hasil:** riwayat layanan murid terbaca tanpa mengubah catatan. **Jika catatan lama tidak dapat diedit:** pengelolaan hanya melalui **Layanan BK** sesuai pemilik dan tahun aktif.

### Membaca data e-Tatib murid

![Gambar 29. Tab Data e-Tatib di Profil Murid dengan kategori, poin, kelas, dan status catatan sumber](manualbook/gambar/v2-murid-etatib.png)

**Tujuan:** memeriksa referensi pelanggaran yang sudah disinkron. **Sebelum mulai:** akses profil murid.

1. Buka **Profil Murid > Data e-Tatib**.
2. Baca tanggal, jenis pelanggaran, kategori, poin, riwayat kelas, dan pencatat.
3. Perhatikan label **Dibatalkan sumber** atau **Arsip**; catatan yang dibatalkan sumber tidak menambah poin aktif.

**Hasil:** konteks e-Tatib tersedia sebagai data baca saja. **Jika data belum tersedia:** tanyakan sinkronisasi kepada Admin IT; jangan menyunting data sumber dari profil.

### Membaca riwayat konsultasi murid

![Gambar 30. Tab Konsultasi pada Profil Murid dengan Ringkasan Hasil dan rincian isi catatan](manualbook/gambar/v2-murid-konsultasi.png)

**Tujuan:** melihat layanan konsultasi sebelumnya yang sah. **Sebelum mulai:** akses profesional atas murid atau peran Koordinator BK.

1. Buka **Profil Murid > Konsultasi**.
2. Baca tanggal, jenis, Guru BK, dan **Hasil/Ringkasan**.
3. Klik kaca pembesar untuk latar belakang dan penanganan yang boleh dibaca.

**Hasil:** riwayat konsultasi tampil sesuai kewenangan. **Jika tab tidak muncul:** akun tidak mempunyai hak baca konsultasi pada profil tersebut.

### Membaca prestasi pada profil murid

![Gambar 31. Tab Prestasi pada Profil Murid dengan tanggal, kegiatan, jenis, tingkat, dan hasil](manualbook/gambar/v2-murid-prestasi.png)

**Tujuan:** mempertimbangkan riwayat prestasi saat memberi layanan. **Sebelum mulai:** akses profil murid.

1. Buka **Profil Murid > Prestasi**.
2. Baca kegiatan, penyelenggara, jenis/tingkat, tanggal, dan hasil.
3. Buka kembali tab layanan bila perlu membandingkan riwayat. Prestasi tidak mengubah status kasus secara otomatis.

**Hasil:** prestasi terbaca tanpa hak edit. **Jika kosong:** belum ada prestasi yang tercatat atau dapat dibaca.

### Mencari prestasi dalam daftar

![Gambar 32. Daftar Prestasi dengan pencarian dan penyaring Tingkat Prestasi dalam mode baca](manualbook/gambar/v2-prestasi-baca.png)

**Tujuan:** mencari prestasi beberapa murid. **Sebelum mulai:** Guru BK hanya dapat membaca prestasi murid yang menjadi kewenangannya. Koordinator BK dapat membaca prestasi seluruh murid.

1. Buka alamat **/achievements**; halaman ini tidak berada pada menu samping kedua peran.
2. Gunakan pencarian dan pilihan **Tingkat Prestasi**, lalu tekan **Filter**.
3. Baca kegiatan, jenis/tingkat, penyelenggara, tanggal, dan hasil pada daftar.

**Hasil:** daftar terbatas menurut hak baca. **Jika tombol catat/impor/edit tidak ada:** pengelolaan prestasi milik Waka Kesiswaan.

## 2.6 Laporan dan dokumen rekap

### Menyaring dan membaca laporan layanan

![Gambar 33. Laporan Layanan BK dengan penyaring tahun ajaran, kelas, jenis layanan, dan aksi rincian](manualbook/gambar/v2-laporan-daftar.png)

**Tujuan:** meninjau catatan sebelum membuat dokumen. **Sebelum mulai:** Guru BK atau Koordinator BK aktif; data mengikuti cakupan masing-masing.

1. Buka **Laporan**.
2. Pilih **Tahun ajaran**, cari **Kelas**, lalu pilih **Jenis layanan BK**: permasalahan, konsultasi, atau pengunduran diri.
3. Tekan **Filter**.
4. Buka ikon kaca pembesar untuk membaca uraian layanan yang diizinkan. Pindah halaman bila perlu.
5. Gunakan **Reset** untuk kembali ke pilihan awal.

**Hasil:** daftar dengan nomor halaman tampil sesuai cakupan Guru BK atau gabungan sekolah untuk Koordinator. **Jika penyaring ditolak:** kelas harus cocok dengan tahun dan hak akses. Penyuntingan dan pengarsipan dilakukan di Layanan BK.

### Membuat pratinjau dan mengatur periode

![Gambar 34. Jendela Pratinjau Rekap dengan Atur periode, semester, tanggal awal/akhir, dan tombol Filter](manualbook/gambar/v2-laporan-preview.png)

**Tujuan:** menyiapkan rekap satu semester dengan periode yang dibutuhkan. **Sebelum mulai:** pilihan penyaring daftar sudah tepat dan hasil dokumen berada dalam batas kapasitas.

1. Tekan **Buat rekap** pada **Laporan**. Pratinjau awal memakai hingga 30 hari terakhir yang tersedia dalam satu semester.
2. Buka **Atur periode**, pilih **Semester 1 (Juli-Desember)** atau **Semester 2 (Januari-Juni)**, lalu isi **Dari tanggal** dan **Sampai tanggal**.
3. Tekan **Filter** dalam jendela dan tunggu pratinjau terbaru selesai.
4. Periksa jenis layanan, rentang tanggal, catatan, dan penandatangan sebelum mencetak.

**Hasil:** pratinjau lengkap mengikuti penyaring dan periode. **Jika gagal:** tanggal harus berurutan dalam satu semester. Bila melebihi batas dokumen, persempit periode atau kelas. **Coba lagi** atau **Buka di halaman baru** tersedia jika pemuatan gagal.

### Mengatur periode rekap di ponsel

![Gambar 35. Pratinjau Rekap pada layar ponsel dengan ringkasan tanggal yang dapat dibuka](manualbook/gambar/v2-laporan-mobile.png)

**Tujuan:** mengubah periode tanpa kehilangan konteks daftar pada layar sempit. **Sebelum mulai:** jendela pratinjau terbuka di ponsel.

1. Ketuk ringkasan tanggal pada **Atur periode** untuk membuka isian.
2. Pilih semester dan isi **Dari tanggal** serta **Sampai tanggal** di dalam semester itu.
3. Ketuk **Filter** dan tunggu ringkasan tanggal serta pratinjau diperbarui.

**Hasil:** pengaturan kembali terlipat dan periode terpilih tetap terlihat. **Jika tombol unduh/cetak nonaktif:** tunggu pratinjau valid yang terbaru.

### Mengunduh Excel atau mencetak PDF

![Gambar 36. Pratinjau Rekap setelah berhasil dimuat dengan Unduh Excel dan Cetak atau Simpan PDF aktif](manualbook/gambar/v2-laporan-preview.png)

**Tujuan:** menyimpan dokumen rekap yang sudah diperiksa. **Sebelum mulai:** pratinjau terbaru berhasil dimuat.

1. Periksa kembali periode dan jumlah catatan pada **Pratinjau Rekap**.
2. Tekan **Unduh Excel** untuk berkas tabel, atau **Cetak / Simpan PDF** untuk membuka pilihan pencetak atau PDF pada peramban.
3. Simpan berkas hanya pada lokasi sekolah yang diizinkan.
4. Tekan **Tutup** untuk kembali ke daftar dengan pilihan penyaring dan posisi yang sama.

**Hasil:** dokumen memakai seluruh data yang lolos penyaringan, bukan hanya halaman daftar yang tampak. **Jika tombol nonaktif:** periode sedang diubah, dimuat, atau pratinjau gagal. Berkas Excel tidak memuat blok tanda tangan seperti hasil cetak.

# 3 Panduan Koordinator BK

Koordinator dapat membaca **Dashboard**, **Layanan BK**, **Data Murid**, prestasi, dan **Laporan** lintas Guru BK dengan mengikuti langkah pada Bab 2. Koordinator juga membagi kelas binaan dan mengaktifkan tahun ajaran. Peran Koordinator saja tidak memberi izin mencatat layanan atau mengedit catatan guru lain. Jika Anda juga memiliki peran Guru BK, izin mencatat tetap mengikuti kelas binaan aktif, sedangkan izin mengedit mengikuti pemilik atau pencatat yang berwenang.

## 3.1 Pemantauan sekolah

### Memeriksa Dashboard Koordinator dan tampilan akun rangkap

![Gambar 37. Dashboard Koordinator BK dengan pilihan Tahun Ajaran dan sakelar Tampilan Guru BK pada akun rangkap](manualbook/gambar/v2-koordinator-dashboard.png)

Gunakan halaman ini untuk memantau kesiapan dan layanan sekolah. Jika akun Anda juga memiliki peran Guru BK, Anda dapat melihat ringkasan kelas binaan sendiri melalui sakelar tampilan.

1. Buka **Dashboard**, pilih **Tahun Ajaran**, lalu tekan **Filter**.
2. Baca indikator dan panel kesiapan penugasan pada tampilan Koordinator.
3. Jika ada, nyalakan **Tampilan Guru BK** untuk ringkasan, aktivitas, e-Tatib, dan kelas binaan sendiri; padamkan untuk kembali ke Koordinator.

Tahun yang Anda pilih tetap berlaku saat tampilan berganti; angkanya mengikuti peran yang sedang ditampilkan. Sakelar hanya muncul pada akun yang memiliki kedua peran dan tidak menambah izin pada menu lain.

## 3.2 Pembagian Kelas Binaan

### Memilih tahun dan memeriksa penugasan

![Gambar 38. Pembagian Kelas Binaan dengan pemilih tahun, filter kelas/status guru, dan daftar guru](manualbook/gambar/v2-penugasan-daftar.png)

Pastikan Admin IT telah menyiapkan tahun dan kelas sebelum Anda memeriksa pembagian kelas binaan.

1. Buka **Pembagian Kelas Binaan**.
2. Pada bagian **Tahun Ajaran**, pilih tahun **Aktif** atau **Persiapan**, lalu **Tampilkan**.
3. Isi **Cari kelas** atau pilih **Status guru**: **Semua**, **Ditugaskan**, atau **Belum ditugaskan**. Lalu tekan **Filter**.
4. Periksa nama Guru BK, penanda kelas, jumlah murid, dan status. Setiap kelas yang berjalan hanya dapat ditugaskan kepada satu Guru BK.

Daftar penugasan untuk tahun pilihan akan tampil. Jika tahun belum tersedia, minta Admin IT membuat tahun persiapan dan menyiapkan daftar murid.

### Menambahkan kelas binaan

![Gambar 39. Jendela Tambah kelas untuk Guru BK dengan pencarian kelas yang belum ditugaskan](manualbook/gambar/v2-penugasan-tambah.png)

Pilih tahun terlebih dahulu. Kelas yang akan diberikan harus tersedia dan belum ditugaskan kepada Guru BK lain.

1. Pada baris guru yang tepat, tekan ikon **Tambah kelas**.
2. Cari kelas yang belum ditugaskan, lalu pilih satu atau beberapa kelas di jendela yang terbuka.
3. Periksa **Kelas dipilih** dan tekan **Tambah kelas**.

Penanda kelas akan muncul pada guru tersebut. Jika yang diubah adalah tahun aktif, cakupan muridnya langsung bertambah. Kelas yang tidak tersedia mungkin sudah ditugaskan atau belum masuk daftar kelas tahun itu.

### Menyalin kelas binaan dari tahun sebelumnya

![Gambar 40. Aksi Salin kelas binaan tahun sebelumnya pada baris guru](manualbook/gambar/v2-penugasan-salin.png)

Anda dapat memakai penugasan tahun sebelumnya sebagai awal pembagian kelas tahun persiapan. Guru harus memiliki kelas pada tahun sebelumnya, dan kelas tujuan harus tersedia.

1. Pilih tahun **Persiapan** pada **Pembagian Kelas Binaan**.
2. Cari guru yang tepat dan tekan ikon **Salin kelas binaan tahun sebelumnya** pada barisnya.
3. Periksa penanda kelas hasil penyalinan dan tambahkan kelas yang belum terisi.

Hanya kelas yang memenuhi syarat yang ditambahkan pada tahun pilihan. Jika daftar tidak berubah, tidak ada kelas sebelumnya yang dapat disalin atau kelas tujuan sudah ditugaskan.

### Melepas penugasan kelas

![Gambar 41. Dialog Batalkan penugasan yang menyebut kelas, guru, dan dampak pada cakupan](manualbook/gambar/v2-penugasan-lepas.png)

Gunakan langkah ini untuk mengoreksi kelas yang masih ditugaskan kepada guru tersebut.

1. Klik ikon silang pada penanda kelas yang hendak dilepas.
2. Baca dialog **Batalkan penugasan?** dan cocokkan nama kelas serta guru.
3. Tekan **Ya, batalkan penugasan**, lalu siapkan pengganti bila kelas tetap aktif.

Kelas kembali berstatus belum ditugaskan. Jika ini terjadi pada tahun aktif, izin guru untuk membaca dan mencatat layanan murid di kelas itu langsung berkurang; catatan lama tetap tersimpan. Jika kelas atau guru pada dialog keliru, tekan **Kembali**.

## 3.3 Aktivasi tahun ajaran

### Mengaktifkan tahun ajaran persiapan

![Gambar 42. Panel Tahun Ajaran Persiapan dengan pemeriksaan kesiapan dan tombol Aktifkan Tahun Ajaran](manualbook/gambar/v2-tahun-aktifkan.png)

Aktivasi menjadikan daftar murid dan penugasan baru sebagai tahun yang digunakan untuk layanan. Sebelumnya, Admin IT harus menyiapkan tahun dan minimal satu murid. Tidak boleh ada murid dengan dua penempatan aktif, dan setiap rombel aktif harus memiliki tepat satu Guru BK aktif.

1. Di **Pembagian Kelas Binaan**, pilih tahun **Persiapan** dan tekan **Tampilkan**.
2. Baca masalah dan peringatan kesiapan. Selesaikan masalah penugasan atau daftar murid bersama Admin IT. Peringatan **Perlu Konfirmasi** atau asal data tidak selalu menghalangi aktivasi.
3. Jika tombol tersedia, tekan **Aktifkan Tahun Ajaran**.
4. Periksa label **Aktif**, lalu minta Guru BK meninjau kembali kelas binaannya.

Tahun pilihan menjadi satu-satunya tahun aktif. Layanan baru mengikuti pembagian kelas tahun itu. Jika tombol tidak dapat ditekan, perbaiki masalah kesiapan yang ditampilkan terlebih dahulu.

### Membatalkan aktivasi untuk kembali ke tahun sebelumnya

![Gambar 43. Dialog Batalkan aktivasi dengan tahun yang akan dikembalikan](manualbook/gambar/v2-tahun-batalkan.png)

Pembatalan hanya tersedia jika tahun sebelumnya dapat dipastikan dan siap digunakan kembali, serta belum ada aktivitas operasional sejak aktivasi tahun baru.

1. Buka **Pembagian Kelas Binaan** pada tahun yang baru aktif.
2. Jika tersedia, tekan **Batalkan aktivasi** dan baca tahun tujuan serta akibatnya pada dialog.
3. Konfirmasi pembatalan, lalu periksa kembali tahun bertanda **Aktif** dan cakupan guru.

Tahun sebelumnya kembali aktif. Jika **Batalkan aktivasi** tidak tersedia atau pembatalan ditolak, syarat pemulihan belum terpenuhi. Jangan mengubah tanggal catatan untuk memindahkan layanan.

Tab **Pengunduran Diri** hanya mencatat perkembangan penanganan. Pada versi ini, tombol untuk mencatat atau menyelesaikan proses keluar resmi belum tersedia di profil murid. Jangan mengubah basis data secara manual untuk menyiasatinya. Ketentuan penyimpanan data alumni dijelaskan pada Bab 6.

# 4 Panduan Waka Kesiswaan

Waka Kesiswaan memantau ringkasan layanan BK dan mengelola prestasi melalui **Dashboard**, **Laporan**, **Prestasi**, dan **Akun Saya**. Guru BK tetap bertanggung jawab mencatat dan mengubah layanan konseling. Waka tidak memiliki akses untuk mengekspor layanan konseling secara massal.

## 4.1 Dashboard Waka
### Memantau indikator dan tindak lanjut sekolah

![Gambar 44. Dashboard Waka menampilkan indikator grafik serta daftar Murid Perlu Tindak Lanjut](manualbook/gambar/v2-waka-dashboard.png)

Akun Waka harus aktif. Indikator layanan akan terisi jika sudah ada data pada tahun yang dipilih.

1. Buka **Dashboard Waka Kesiswaan**, pilih **Tahun Ajaran**, lalu terapkan **Filter** bila diminta.
2. Baca jumlah Murid Tercatat, Sedang Ditangani, Perlu Tindak Lanjut, dan Baru Bulan Ini. Setiap indikator menghitung murid yang berbeda, bukan jumlah seluruh sesi.
3. Baca Grafik Catatan BK. Setiap murid dihitung satu kali pada setiap bulan ketika ia mendapat layanan, bukan sebanyak jumlah kejadian. Periksa Murid per Tingkat dan paling banyak tiga kelas pada Catatan Permasalahan Terbanyak.
4. Pada **Murid Perlu Tindak Lanjut**, baca Murid/Kelas, Jenis Layanan, Guru BK, Ringkasan, dan Tindak Lanjut. Satu murid dapat memiliki lebih dari satu layanan yang masih ditangani.

Gunakan ringkasan ini untuk koordinasi sekolah. Jika angkanya berbeda dari daftar e-Tatib, periksa periode, jenis layanan, dan cara menghitung murid terlebih dahulu.

## 4.2 Laporan Layanan BK
### Menyaring dan membaca ringkasan layanan

![Gambar 45. Laporan Waka dengan filter tahun kelas dan jenis layanan dalam mode baca](manualbook/gambar/v2-waka-laporan.png)

Masuk dengan akun Waka untuk membaca laporan. Tombol membuat rekap dan mengedit layanan memang tidak tersedia untuk peran ini.

1. Buka **Laporan**, pilih **Tahun ajaran**, **Kelas**, dan **Jenis layanan BK**.
2. Tekan **Filter**, lalu baca daftar. Gunakan nomor halaman untuk melihat data berikutnya.
3. Untuk permasalahan/konsultasi, baca identitas layanan, Ringkasan / Hasil, Guru BK, dan Keterangan. Untuk pengunduran diri, Keterangan memuat progres berkas terbaru.
4. Koordinasikan kebutuhan lanjutan dengan Guru BK yang ditampilkan melalui prosedur sekolah.

Waka hanya dapat membaca ringkasan yang ditampilkan. Uraian lengkap layanan yang dibatasi tetap tidak dapat dibuka. Waka juga tidak dapat mengubah layanan, melihat pratinjau cetak, atau mengunduh maupun mencetak rekap layanan. Jangan memakai akun Guru BK untuk melewati batas akses ini.

## 4.3 Pencatatan prestasi
### Mencatat prestasi secara manual

![Gambar 46. Formulir Catat Prestasi untuk memilih murid dan mengisi rincian kegiatan](manualbook/gambar/v2-prestasi-buat.png)

Pastikan murid terdaftar resmi dan masih aktif, serta pilihan jenis dan tingkat prestasi tersedia. Tanggal prestasi tidak boleh berada di masa depan.

1. Buka **Prestasi**, lalu tekan **Catat Prestasi**.
2. Cari nama atau NISN dan pilih murid dari saran; periksa identitas serta rombelnya.
3. Isi Kegiatan/Nama Lomba, Jenis, Tingkat, Tanggal, Penyelenggara, dan Hasil/Peringkat.
4. Tekan **Simpan**, lalu periksa kegiatan baru pada daftar. **Batal** menutup formulir; **Kosongkan** menghapus isian yang belum disimpan.

Prestasi akan tampil pada daftar dan riwayat murid sesuai izin baca. Jika murid tidak ditemukan, periksa daftar murid bersama Admin IT; jangan memakai identitas pengganti. Versi ini belum menyediakan unggahan bukti atau tahap verifikasi.

## 4.4 Templat dan impor prestasi
### Mengunduh, mengisi, dan mengimpor templat prestasi

![Gambar 47. Jendela Impor Excel dengan tautan template petunjuk kode dan pilihan berkas](manualbook/gambar/v2-prestasi-template.png)

Pastikan daftar murid aktif serta pilihan jenis dan tingkat prestasi sudah tersedia.

1. Pada **Prestasi**, tekan **Impor Excel**, lalu **Unduh template Excel prestasi**.
2. Jangan ubah judul kolom `nisn`, `jenis`, `tingkat`, `kegiatan`, `penyelenggara`, `tanggal`, dan `hasil`. Simpan NISN sebagai teks agar angka nol di awal tidak hilang.
3. Isi kode jenis dan tingkat dari **Format dan kode isian Excel**; gunakan tanggal `YYYY-MM-DD` atau tanggal Excel yang sah. Jangan memakai tanggal masa depan atau rumus.
4. Simpan sebagai `.xlsx` maksimal 1.000 baris data dan 2 MB. Pilih **Berkas Excel**, lalu **Impor Prestasi**.
5. Jika impor ditolak, baca daftar kesalahan, perbaiki berkas, pilih kembali, lalu ulangi. Aplikasi memeriksa seluruh baris sebelum menyimpan; berkas yang gagal diperiksa tidak tersimpan sebagian.
6. Setelah berhasil, periksa jumlah dan contoh kegiatan pada daftar. Jangan mengunggah ulang berkas yang sama hanya untuk mengecek hasil.

Seluruh data prestasi yang sah tersimpan bersama. Setelah memperbaiki berkas, pilih kembali berkas yang sudah diperbarui. Mengubah berkas di komputer saja belum mengirimkan perbaikannya ke aplikasi.

## 4.5 Mencari, mengoreksi, dan menghapus prestasi
### Mencari dan mengurutkan daftar prestasi

![Gambar 48. Daftar prestasi dengan pencarian Tingkat Prestasi dan aksi per baris](manualbook/gambar/v2-prestasi-daftar.png)

Pencarian dapat memakai murid, NISN, kegiatan, atau penyelenggara.

1. Buka **Prestasi**, isi **Cari** atau pilih **Tingkat Prestasi**.
2. Tekan **Filter** untuk melihat hasil.
3. Klik kolom Murid, Kegiatan, atau Tanggal untuk mengurutkan. Gunakan nomor halaman bila diperlukan.

Daftar menampilkan hasil sesuai pilihan Anda. Jika tidak ada hasil, coba longgarkan pilihan pencarian.
### Mengedit prestasi

![Gambar 49. Formulir Edit Prestasi dengan identitas murid yang tidak dapat diganti](manualbook/gambar/v2-prestasi-edit.png)

Anda harus masuk sebagai Waka dan memilih catatan prestasi yang sudah ada.

1. Tekan ikon **Edit prestasi** pada baris target.
2. Koreksi kegiatan, jenis, tingkat, tanggal, penyelenggara, atau hasil. Identitas murid tidak diganti melalui edit.
3. Tekan **Simpan**, lalu periksa nilai terbaru pada daftar.

Jika perubahan ditolak karena pengguna lain sudah memperbarui data, muat ulang halaman dan bandingkan isinya sebelum mencoba lagi. Guru BK dan Koordinator hanya dapat membaca prestasi sesuai cakupan aksesnya.
### Menghapus catatan prestasi yang keliru

![Gambar 50. Dialog konfirmasi Hapus prestasi dengan nama kegiatan dan target murid](manualbook/gambar/v2-prestasi-hapus.png)

Pastikan dahulu catatan yang akan dihapus dari daftar aktif.

1. Tekan **Hapus prestasi** pada baris yang tepat.
2. Baca kembali kegiatan dan murid pada dialog konfirmasi.
3. Tekan **Ya, hapus** hanya bila target benar; gunakan **Batal** jika ragu.
4. Periksa bahwa catatan tidak lagi tampil pada daftar aktif.

Penghapusan memindahkan catatan ke arsip aplikasi dan tercatat dalam riwayat perubahan. Halaman ini tidak menyediakan tombol untuk memulihkan catatan dari arsip.

# 5 Panduan Admin IT

Admin IT menyiapkan akun, daftar kelas, daftar murid, sumber pembaruan data, dan tampilan web. Menu yang dipakai ialah **Dashboard**, **Manajemen Web**, **Kelola Akun**, **Data Master**, **Data Kelas**, dan **Akun Saya**. Peran ini tidak memberi akses ke isi layanan BK. Koordinator BK bertanggung jawab membagi kelas binaan dan mengaktifkan tahun ajaran.

> Pada awal tahun, rapikan **Data Kelas**, buat tahun persiapan di **Data Master > Dapodik**, lalu impor daftar murid melalui API Siswa atau berkas. Setelah itu, minta Koordinator membagi kelas binaan dan mengaktifkan tahun ajaran.

## 5.1 Dashboard Admin IT

### Membaca kesiapan akun dan integrasi

![Gambar 51. Dashboard Admin IT menampilkan indikator akun, konflik sinkronisasi, dan status integrasi](manualbook/gambar/v2-admin-dashboard.png)

1. Buka **Dashboard**; pilih **Tahun Ajaran** bila perlu melihat periode tertentu.
2. Baca **Akun Aktif**, **Akun Nonaktif**, **Konflik Sinkronisasi**, dan **Integrasi Bermasalah**.
3. Klik jumlah konflik untuk membuka **Data Master > Sinkronisasi**; periksa **Status sinkronisasi terbaru** dan **Status Integrasi** sebelum menindaklanjuti.

Indikator menunjukkan hal yang perlu diperiksa; halaman ini tidak menguji sambungan secara langsung. Jika pembaruan otomatis e-Tatib gagal, periksa tab **e-Tatib** dan riwayatnya sebelum mencoba lagi.

## 5.2 Kelola Akun

### Mencari dan memeriksa akun

![Gambar 52. Daftar Kelola Akun dengan pencarian, filter peran, dan ikon informasi](manualbook/gambar/v2-akun-daftar.png)

1. Buka **Kelola Akun**; isi **Cari nama atau email** atau pilih **Peran**, lalu **Filter**.
2. Urutkan **Nama** atau **Status** bila daftar panjang.
3. Klik ikon **Lihat Informasi** pada akun untuk memeriksa email, login terakhir, dan status sandi.

**Hasil:** data akun yang dituju terlihat tanpa mengubah aksesnya.

### Membuat akun

![Gambar 53. Formulir Tambah Akun untuk nama, email, dan pilihan peran](manualbook/gambar/v2-akun-tambah.png)

1. Klik **Tambah Akun**, isi **Nama** dan **Email**, lalu pilih minimal satu peran.
2. Pilih Admin IT, Waka Kesiswaan, Guru BK, atau Koordinator BK secara terpisah; hanya Guru BK dan Koordinator BK yang boleh digabung.
3. Klik **Buat akun**.

Akun baru muncul di daftar. Jika email telah dipakai atau kombinasi peran ditolak, perbaiki isian sebelum mencoba lagi.

### Memeriksa informasi akun

![Gambar 54. Panel informasi akun dengan email, login terakhir, dan status sandi](manualbook/gambar/v2-akun-informasi.png)

1. Buka informasi akun melalui ikon **Lihat Informasi** pada baris akun.
2. Periksa email, waktu login terakhir, dan status sandi.
3. Tutup panel informasi setelah selesai.

**Hasil:** detail status akun terlihat tanpa mengubah akun. Sandi sementara tidak ditampilkan pada panel ini; gunakan **Reset Sandi** untuk menerbitkan sandi baru.

### Mengedit identitas dan status akun

![Gambar 55. Jendela Edit Akun dengan nama, email, dan status Aktif atau Nonaktif](manualbook/gambar/v2-akun-edit.png)

1. Klik ikon **Edit Akun** pada baris yang tepat.
2. Koreksi nama atau email, atau pilih **Status**: **Aktif** atau **Nonaktif**.
3. Simpan dan periksa status baru pada daftar.

Akun nonaktif tidak dapat masuk. Admin IT tidak dapat menonaktifkan akunnya sendiri.

### Mengubah peran akun

![Gambar 56. Pemilih peran akun dengan tombol Simpan peran](manualbook/gambar/v2-akun-peran.png)

1. Klik penanda peran atau ikon **Tambah Peran** pada akun tujuan.
2. Pilih kombinasi peran yang diizinkan, lalu **Simpan peran**.
3. Periksa penanda peran baru pada daftar.

Izin akun mengikuti peran baru. Admin IT tidak dapat mengganti perannya sendiri. Jika pilihan ditolak, periksa kembali kombinasi perannya.

### Mereset sandi akun lain

![Gambar 57. Konfirmasi Reset Sandi untuk akun tujuan](manualbook/gambar/v2-akun-reset.png)

1. Klik ikon **Reset Sandi** pada akun tujuan dan periksa nama akun di konfirmasi.
2. Klik **Ya, Reset Sandi**.
3. Salin sandi sementara baru dari informasi akun yang terbuka seperti langkah sebelumnya.

Sandi lama tidak berlaku dan sesi lama berakhir. Jika panel sandi tertutup sebelum sandi baru disalin, terbitkan sandi sementara lagi. Untuk mengganti sandi sendiri, gunakan **Akun Saya**.

## 5.3 Data Kelas

### Melihat katalog rombel

![Gambar 58. Data Kelas menampilkan jurusan dan tombol Lihat Rombel](manualbook/gambar/v2-kelas-daftar.png)

1. Buka **Data Kelas** dan pilih jurusan yang akan diperiksa.
2. Klik **Lihat Rombel** untuk melihat nama, status, dan jumlah murid.
3. Cocokkan rombel aktif dengan daftar sekolah sebelum membuat tahun persiapan.

Rombel aktif tersedia untuk tahun berjalan dan tahun persiapan. Jumlah murid yang ditampilkan berasal dari tahun aktif.

### Menambah rombel

![Gambar 59. Formulir tambah rombel dengan isian Nama rombel dan tombol Tambah Rombel](manualbook/gambar/v2-kelas-tambah.png)

1. Pada **Data Kelas**, klik **Tambah Rombel** bila perlu membuka formulir.
2. Isi **Nama rombel** sesuai daftar sekolah.
3. Klik **Tambah Rombel** dan periksa baris baru.

Rombel baru masuk daftar kelas. Jika namanya sudah dipakai, koreksi nama atau aktifkan kembali rombel lama yang sesuai.

### Mengedit atau menonaktifkan rombel

![Gambar 60. Jendela Edit Rombel dengan Nama Rombel dan Status](manualbook/gambar/v2-kelas-edit.png)

1. Klik nama rombel untuk membuka **Edit Rombel**.
2. Koreksi **Nama Rombel** atau pilih **Status** Aktif/Nonaktif.
3. Klik **Simpan** dan periksa hasil pada daftar.

Daftar kelas berubah tanpa mengganti nama pada riwayat tahun sebelumnya. Rombel yang masih memiliki murid atau penugasan tidak dapat dinonaktifkan sebelum penempatannya diselesaikan.

## 5.4 Tahun ajaran persiapan

### Membuat tahun persiapan

![Gambar 61. Tab Dapodik dengan formulir Buat Tahun Ajaran](manualbook/gambar/v2-tahun-persiapan.png)

1. Pastikan rombel aktif sudah benar, lalu buka **Data Master > Dapodik**.
2. Isi nama tahun pada bagian **Buat Tahun Ajaran** sesuai sumber sekolah.
3. Klik **Buat Tahun Ajaran**, lalu siapkan daftar murid melalui API Siswa atau berkas.

Tahun baru berstatus persiapan dan belum digunakan untuk layanan. Rombel aktif tersedia secara otomatis. Nama tahun dalam data dari API atau berkas harus sama persis.

### Membatalkan persiapan yang keliru

![Gambar 62. Konfirmasi pembatalan tahun persiapan dengan tombol Ya, batalkan persiapan](manualbook/gambar/v2-tahun-hapus-persiapan.png)

1. Di daftar **Tahun Ajaran**, pilih aksi pembatalan untuk tahun persiapan yang keliru.
2. Periksa nama tahun dan akibatnya di dialog.
3. Klik **Ya, batalkan persiapan** hanya untuk tahun yang memang akan dibatalkan.

Jika syaratnya terpenuhi, tahun persiapan beserta penempatan murid dan penugasan khusus tahun itu dihapus. Tahun yang pernah aktif atau sudah memiliki layanan maupun data terverifikasi tidak dapat dibatalkan di sini.

## 5.5 Tautan API Siswa dan e-Tatib

Tautan API adalah alamat penghubung yang dipakai aplikasi untuk mengambil data dari sistem sumber sekolah. Anda cukup memasukkan alamat resmi yang diberikan pengelola sistem tersebut; langkah ini tidak memerlukan pemrograman.

### Mengenali kolom tautan API

![Gambar 63. Kolom Tautan API pada tab Data Master dengan pilihan pemakaian tautan tersimpan](manualbook/gambar/v2-api-kolom.png)

1. Buka tab **Dapodik** untuk **Tautan API Siswa** atau tab **e-Tatib** untuk tautan e-Tatib.
2. Pastikan alamat yang akan dipakai adalah HTTPS sumber resmi sekolah.
3. Pilih langkah di bawah sesuai kebutuhan: menyimpan alamat, memakai alamat sementara, mengubahnya, atau menampilkannya.

Kolom ini mengatur alamat yang dipakai aplikasi. Status sumber pada halaman tidak berarti sambungan telah diuji secara langsung.

### Menyimpan tautan resmi

![Gambar 64. Jendela Simpan tautan meminta alamat API dan Kata sandi saat ini](manualbook/gambar/v2-api-simpan.png)

1. Isi **Tautan API Siswa/e-Tatib** pada tab yang tepat, lalu klik **Simpan**.
2. Pada jendela yang terbuka, periksa alamat dan isi **Kata sandi saat ini**.
3. Klik **Simpan tautan**; setelah berhasil, kolom menampilkan penanda tersamar.

Alamat tersimpan dalam bentuk terenkripsi. Jika alamat, sumber, atau kata sandi ditolak, perbaiki isian tanpa menyalin alamat rahasia ke laporan masalah.

### Memakai tautan tersimpan

![Gambar 65. Pilihan Gunakan API tersimpan tercentang dengan tombol Tinjau Data](manualbook/gambar/v2-api-kolom.png)

1. Biarkan **Gunakan API tersimpan** tercentang.
2. Klik **Tinjau Data** pada tab sumber yang tepat.
3. Periksa pratinjau sebelum mengimpor atau menyinkronkan.

**Tinjau Data** memakai alamat tersimpan tanpa menampilkan alamat aslinya. Langkah ini hanya menampilkan pratinjau; data belum diimpor atau disinkronkan. Jika data gagal diambil, Admin IT memeriksa alamat API. Petugas server membantu memeriksa layanan dan jaringan bila diperlukan.

### Mengubah tautan tersimpan

![Gambar 66. Jendela Ubah tautan API dengan alamat baru dan verifikasi sandi](manualbook/gambar/v2-api-ubah.png)

1. Klik **Ubah** di kolom tautan tersimpan.
2. Isi alamat HTTPS baru serta **Kata sandi saat ini**.
3. Simpan dan lakukan **Tinjau Data** lagi sebelum menggunakan sumber baru.

Jika alamat e-Tatib diubah, pembaruan otomatis berhenti sampai pratinjau dan aktivasi ulang berhasil. Meninjau alamat baru saja belum mengaktifkannya kembali.

### Menampilkan lalu menyembunyikan tautan

![Gambar 67. Jendela Tampilkan tautan API dengan alamat contoh dan tombol Sembunyikan](manualbook/gambar/v2-api-tampilkan.png)

1. Klik **Tampilkan** hanya bila perlu memeriksa alamat tersimpan.
2. Masukkan **Kata sandi saat ini** dan baca alamat di tempat privat.
3. Klik **Sembunyikan** atau tutup jendela segera setelah pemeriksaan.

Anda dapat memeriksa alamat tanpa mengubah yang tersimpan. Jangan tampilkan alamat asli dalam gambar atau membagikannya melalui pesan masalah.

### Meninjau alamat sekali pakai

![Gambar 68. Gunakan API tersimpan dilepas dan kolom tautan sementara siap ditinjau](manualbook/gambar/v2-api-sekali-pakai.png)

1. Lepas centang **Gunakan API tersimpan**.
2. Isi tautan HTTPS sementara pada kolom yang sama.
3. Klik **Tinjau Data** dan periksa pratinjau sumber.

Alamat sementara hanya dipakai untuk peninjauan ini. **Tinjau Data** belum menyimpan alamat atau menyinkronkan data, dan alamat tersimpan tetap sama.

## 5.6 Daftar murid dari API Siswa

### Meninjau data API Siswa

![Gambar 69. Pratinjau API Siswa menampilkan jumlah murid, status kecocokan, dan kesiapan tahun](manualbook/gambar/v2-roster-api-preview.png)

1. Buat dahulu tahun yang tercantum di sumber; pada **Data Master > Dapodik** klik **Tinjau Data**.
2. Di **Pratinjau API Siswa**, periksa jumlah baris, murid baru/cocok, nama berbeda, dan kesiapan tiap tahun.
3. Baca konflik tahun, rombel, atau identitas sebelum melanjutkan.

Pratinjau belum mengubah daftar murid. Jika tahun atau rombel tidak cocok, atau data dari sumber gagal diambil, perbaiki sumber lalu tinjau kembali.

### Memilih baris NISN ganda

![Gambar 70. Pratinjau API Siswa dengan kelompok NISN ganda dan pilihan satu baris](manualbook/gambar/v2-roster-api-duplikat.png)

1. Temukan setiap kelompok NISN yang muncul lebih dari sekali dalam tahun sama.
2. Bandingkan nama dan rombel dengan data resmi, lalu pilih **satu** baris yang benar per kelompok.
3. Pastikan tidak ada konflik pemblokir lain sebelum menekan impor.

**Impor Data** tersedia setelah setiap kelompok dipilih dengan benar. Jangan menebak hanya berdasarkan kemiripan nama.

### Mengimpor hasil tinjauan API

![Gambar 71. Hasil impor API Siswa menampilkan jumlah baris dan murid baru atau cocok](manualbook/gambar/v2-roster-api-hasil.png)

1. Dari pratinjau yang telah diperiksa, klik **Impor Data**.
2. Tunggu pemeriksaan ulang sumber dan baca pesan hasil jumlah baris, murid baru/cocok, dan rombel baru.
3. Beritahu Koordinator BK tentang rombel baru agar guru binaan ditugaskan.

Identitas dan riwayat murid yang cocok tidak digandakan. Jika data dari sumber berubah sejak pratinjau, klik **Tinjau Data** lagi sebelum mengimpor.

## 5.7 Daftar murid dari CSV atau Excel

### Mengunduh dan mengisi templat

![Gambar 72. Panel Impor CSV atau Excel jika API belum tersedia dengan tautan Unduh template impor](manualbook/gambar/v2-roster-template.png)

1. Di **Data Master > Dapodik**, buka **Impor CSV atau Excel jika API belum tersedia** dan klik **Unduh template impor**.
2. Isi kolom `nisn`, `nama`, `rombel`, dan `tahun_pelajaran`. Simpan NISN sebagai teks agar angka nol di awal tidak hilang.
3. Gunakan tahun yang telah dibuat dan rombel sekolah yang benar. Untuk CSV gunakan UTF-8 dengan urutan empat kolom sama.

Tahun persiapan harus sudah dibuat. Berkas dibatasi hingga 2 MiB dan 5.000 baris. Berkas Excel harus berformat `.xlsx`, berisi satu lembar, dan tidak menggunakan rumus.

### Meninjau berkas Excel sebelum impor

![Gambar 73. Jendela Pratinjau Excel Daftar Murid dengan ringkasan dan tombol Impor Data](manualbook/gambar/v2-roster-excel-preview.png)

1. Klik **Pilih berkas CSV atau Excel**, pilih berkas, lalu **Tinjau / Impor Berkas**.
2. Untuk Excel, baca **Pratinjau Excel Daftar Murid** dan periksa tahun, rombel, serta konflik.
3. Klik **Impor Data** hanya setelah isinya benar. Untuk CSV, ikuti hasil/validasi setelah pengiriman.

**Jika gagal:** periksa ekstensi, header, ukuran, format NISN, tahun, dan konflik rombel; bila berkas diubah, tinjau lagi.

### Memeriksa hasil impor berkas

![Gambar 74. Pesan hasil impor daftar murid dari berkas dengan jumlah baris dan rombel baru](manualbook/gambar/v2-roster-excel-hasil.png)

1. Baca pesan jumlah baris, murid baru/cocok, dan rombel baru setelah impor.
2. Periksa contoh murid pada **Data Murid** bersama petugas yang berwenang.
3. Serahkan daftar rombel baru kepada Koordinator BK untuk penugasan.

Daftar murid yang sah masuk tanpa mengganti identitas atau riwayat murid yang cocok. Aplikasi tidak menyimpan berkas aslinya.

## 5.8 e-Tatib manual

### Meninjau catatan dan identitas sumber

![Gambar 75. Pratinjau e-Tatib menampilkan jumlah catatan, masalah identitas, dan pilihan kandidat](manualbook/gambar/v2-etatib-preview.png)

1. Pastikan daftar murid tersedia, lalu buka **Data Master > e-Tatib** dan klik **Tinjau Data**.
2. Periksa jumlah catatan, identitas yang belum cocok, catatan lama yang tidak dikirim lagi, dan kejadian tanpa tanggal.
3. Jika identitas berbeda, bandingkan NISN, nama, dan rombel. Pilih murid yang cocok hanya jika Anda yakin. Bila buktinya belum cukup, pilih **Lewati dulu, belum tertaut**.

Pratinjau belum menyinkronkan data. **Pilih berdasarkan NISN/nama** hanya mengusulkan satu murid yang masih perlu Anda periksa.

### Menyetujui kelompok kejadian identik

![Gambar 76. Kelompok duplikat e-Tatib dengan pilihan anggap sebagai satu kejadian](manualbook/gambar/v2-etatib-duplikat.png)

1. Pada pratinjau, periksa tiap kelompok baris identik terhadap sumber.
2. Bila memang satu kejadian, centang **anggap sebagai satu kejadian** pada kelompok itu.
3. Ulangi untuk semua kelompok baru sebelum sinkronisasi.

Sinkronisasi tertahan jika masih ada kelompok baru yang belum diputuskan. Keputusan sebelumnya dapat dilihat pada **Keputusan Duplikasi**.

### Membatalkan keputusan duplikasi lama

![Gambar 77. Panel Keputusan Duplikasi dengan aksi Batalkan Keputusan dan konfirmasi](manualbook/gambar/v2-etatib-keputusan-batal.png)

1. Pada tab **e-Tatib**, buka **Keputusan Duplikasi** dan temukan kelompok yang keliru disetujui.
2. Klik **Batalkan Keputusan** dan periksa kelompok pada dialog konfirmasi.
3. Konfirmasi pembatalan, lalu tinjau ulang sebelum sinkronisasi berikutnya.

Pada sinkronisasi berikutnya, Anda perlu memeriksa kelompok itu lagi. Riwayat keputusan tetap tersimpan.

### Menjalankan sinkronisasi manual

![Gambar 78. Hasil sinkronisasi e-Tatib dan tombol Sinkronkan Sekarang untuk pembaruan manual](manualbook/gambar/v2-etatib-hasil.png)

1. Setelah pratinjau layak, klik **Sinkronkan Data**.
2. Baca pesan berhasil, peringatan, atau gagal setelah kembali ke halaman.
3. Bila otomatis sudah aktif, **Sinkronkan Sekarang** menjalankan pembaruan manual dari tautan tersimpan.

Catatan dari sumber disalin ke aplikasi tanpa mengubah data di e-Tatib. Catatan lama yang tidak lagi dikirim sumber tidak otomatis terhapus. Jika proses gagal, periksa sumber, kesiapan data utama, dan keputusan tentang kejadian identik. Tinjau kembali jika data sumber berubah.

## 5.9 e-Tatib otomatis

### Mengaktifkan pembaruan otomatis

![Gambar 79. Pratinjau e-Tatib dengan tombol Aktifkan Otomatis dan verifikasi sandi](manualbook/gambar/v2-etatib-aktifkan.png)

1. Pastikan alamat resmi tersimpan dan penjadwal pada server berjalan.
2. Di **Data Master > e-Tatib**, klik **Tinjau Data** dan selesaikan identitas/duplikasi yang perlu diputus.
3. Klik **Aktifkan Otomatis**, isi **Kata sandi saat ini**, lalu konfirmasi.

Aplikasi menyinkronkan data terlebih dahulu, lalu mengaktifkan pembaruan otomatis jika proses berhasil. Jika aktivasi ditolak, periksa pratinjau dan kata sandi.

### Mengatur hari dan jam pembaruan

![Gambar 80. Jadwal Pembaruan e-Tatib dengan hari, jam WIB, Tambah jam, dan Simpan jadwal](manualbook/gambar/v2-etatib-jadwal.png)

1. Pada **Jadwal Pembaruan e-Tatib**, pilih hari dan jam WIB; bawaan adalah setiap hari pukul 10.00, 13.00, dan 15.00.
2. Gunakan **Tambah jam** atau **Hapus** untuk mengubah jam.
3. Isi kata sandi saat ini dan klik **Simpan jadwal**.

Jadwal baru berlaku pada waktu pembaruan berikutnya. Pilih sedikitnya satu hari dan satu jam, dengan paling banyak 24 waktu berbeda per hari. Menyimpan jadwal tidak mengaktifkan kembali pembaruan yang sedang nonaktif.

### Menonaktifkan pembaruan otomatis

![Gambar 81. Panel Nonaktifkan Pembaruan Otomatis dengan isian sandi dan tombol Nonaktifkan](manualbook/gambar/v2-etatib-nonaktifkan.png)

1. Pada **Nonaktifkan Pembaruan Otomatis**, isi kata sandi saat ini.
2. Klik **Nonaktifkan** dan baca konfirmasi.
3. Setujui untuk menghentikan jadwal otomatis.

Alamat tersimpan tetap dapat dipakai untuk pratinjau atau sinkronisasi manual. Jika jadwal sebelumnya tidak berjalan, periksa riwayat dan penjadwal server.

## 5.10 Konflik identitas dan duplikasi e-Tatib

### Mencari identitas yang belum cocok

![Gambar 82. Konflik e-Tatib tab Belum Cocok dengan identitas, rombel, alasan, dan jumlah pelanggaran](manualbook/gambar/v2-konflik-daftar.png)

1. Klik **Perlu Ditinjau** dari **Data Master** atau buka **Konflik e-Tatib > Belum Cocok**.
2. Cari nama/NISN dan baca **Identitas Sumber**, **Rombel Sumber**, alasan, dan jumlah pelanggaran.
3. Tentukan apakah bukti cukup untuk mencocokkan identitas secara otomatis atau manual.

Identitas yang belum diputuskan tetap belum terhubung dengan murid. Jangan memilih murid hanya berdasarkan nama jika masih ada keraguan.

### Mempratinjau pencocokan otomatis

![Gambar 83. Pratinjau pencocokan otomatis Berdasarkan NISN atau Berdasarkan Nama](manualbook/gambar/v2-konflik-otomatis.png)

1. Klik **Berdasarkan NISN** atau **Berdasarkan Nama**.
2. Periksa pasangan identitas sumber dan data utama yang ditampilkan. Jika hasilnya lebih dari satu atau tidak ada, tangani secara manual.
3. Centang konfirmasi dan klik **Terapkan Pencocokan** hanya untuk pasangan tunggal yang benar.

Pencocokan tersimpan di aplikasi dan tercatat dalam riwayat. Identitas pada sistem sumber tidak berubah.

### Mencocokkan satu identitas secara manual

![Gambar 84. Formulir Cocokkan Identitas e-Tatib dengan pencarian murid pada data utama dan konfirmasi](manualbook/gambar/v2-konflik-manual.png)

1. Pada baris konflik, klik ikon **Cocokkan**.
2. Cari murid pada data utama, lalu bandingkan NISN, nama, dan rombel.
3. Pilih kandidat yang benar, centang konfirmasi, lalu simpan.

Pencocokan di aplikasi dapat menghubungkan NISN dari sumber yang berbeda tanpa mengubah identitas murid pada data utama. Jika bukti belum cukup, biarkan identitas berstatus belum cocok.

### Mengubah target pencocokan manual

![Gambar 85. Tab Pencocokan Manual dengan aksi Ubah Pencocokan](manualbook/gambar/v2-konflik-ubah.png)

1. Buka tab **Pencocokan Manual** dan temukan relasi yang salah.
2. Klik **Ubah Pencocokan**, bandingkan murid pengganti dengan data sumber, lalu simpan pilihan yang benar.
3. Periksa target baru pada daftar.

Perubahan dapat ditolak jika catatan sudah terkait dengan kasus BK. Ikuti prosedur koreksi data resmi sekolah.

### Membatalkan pencocokan manual

![Gambar 86. Baris pencocokan manual dengan aksi Batalkan Pencocokan](manualbook/gambar/v2-konflik-batalkan.png)

1. Pada tab **Pencocokan Manual**, pilih relasi yang tidak lagi sah.
2. Klik **Batalkan Pencocokan** dan periksa pasangan pada konfirmasi browser.
3. Setujui konfirmasi browser untuk melepas pencocokan.

Setelah pencocokan manual dibatalkan, aturan pencocokan otomatis berlaku kembali. Keputusan sebelumnya tetap tercatat dalam riwayat.

## 5.11 Masalah dan riwayat sinkronisasi

### Meninjau masalah

![Gambar 87. Tab Sinkronisasi dengan daftar Masalah Sinkronisasi dan ikon Tinjau](manualbook/gambar/v2-sinkronisasi-masalah.png)

1. Buka **Data Master > Sinkronisasi**.
2. Pada **Masalah Sinkronisasi**, baca sumber, data, alasan, dan status pemeriksaan.
3. Klik ikon **Tinjau** untuk membuka rincian di bawah baris.

Rincian masalah tampil tanpa mengubah data sumber. Tangani konflik identitas melalui **Cocokkan identitas murid**.

### Menyimpan tindakan untuk beda kelas

![Gambar 88. Detail masalah beda kelas dengan pilihan tindakan dan tombol Simpan tindakan](manualbook/gambar/v2-sinkronisasi-tindakan.png)

1. Bandingkan kelas e-Tatib dengan penempatan sekolah pada tanggal kejadian.
2. Jika bukti jelas, pilih kelas sekolah atau kelas e-Tatib yang benar **untuk kejadian itu**, lalu **Simpan tindakan**.
3. Jika belum jelas, pilih **Belum dapat menentukan; perlu koreksi data**, isi **Catatan singkat**, lalu simpan.

Keputusan ini tidak mengubah kelas murid pada data utama. Jika Anda memilih koreksi pada sumber, masalah tetap terbuka. Jangan menebak kelas jika tidak ada data pembanding.

### Membaca riwayat

![Gambar 89. Riwayat Keputusan Kelas dan Riwayat Sinkronisasi dalam keadaan terbuka](manualbook/gambar/v2-sinkronisasi-riwayat.png)

1. Pada tab **Sinkronisasi**, klik **Tampilkan Riwayat** di **Riwayat Keputusan Kelas** atau **Riwayat Sinkronisasi**.
2. Baca keputusan dan hasil proses; gunakan halaman berikutnya bila perlu.
3. Cocokkan waktu dan sumber dengan masalah yang sedang diperiksa.

Anda dapat menelusuri keputusan sebelumnya tanpa mengubahnya.

## 5.12 Status konektor Dapodik langsung

![Gambar 90. Tab Dapodik pada Data Master menggunakan API Siswa dan impor berkas untuk pekerjaan harian](manualbook/gambar/v2-api-kolom.png)

Sambungan langsung ke Dapodik belum tersedia pada versi ini. Gunakan **API Siswa** atau berkas untuk menyiapkan daftar murid seperti pada bagian 5.6 dan 5.7. Tidak ada tombol untuk mengaktifkan sambungan Dapodik langsung pada halaman ini.

## 5.13 Manajemen Web

### Mengganti logo web/sekolah

![Gambar 91. Pengaturan Logo web dengan unggahan dan pratinjau logo sekolah](manualbook/gambar/v2-web-logo.png)

1. Buka **Manajemen Web**, bagian **Logo web**.
2. Klik **Unggah logo baru**, pilih PNG/JPEG resmi maksimal 2 MB dan 2048 × 2048 piksel.
3. Klik **Simpan** dan periksa pratinjau.

Logo dipakai pada aplikasi, ikon tab, dan sisi sekolah pada kop laporan. Jika berkas ditolak, periksa format, ukuran, resolusi, dan keasliannya.

### Mengganti logo dinas pada kop

![Gambar 92. Pengaturan Logo dinas untuk kop laporan dengan unggahan dan pratinjau](manualbook/gambar/v2-web-dinas.png)

1. Pada halaman yang sama, buka **Logo dinas untuk kop laporan**.
2. Pilih gambar resmi PNG/JPEG dengan batas ukuran dan resolusi yang sama.
3. Klik **Simpan** dan periksa pratinjau.

Jika kedua logo tersedia, logo dinas berada di kiri dan logo sekolah di kanan pada kop laporan.

### Memilih atau memulihkan tema

![Gambar 93. Pilihan Tema warna Bawaan, Hijau Pinus, Biru Sabak, dan Kembalikan ke default](manualbook/gambar/v2-web-tema.png)

1. Di **Tema warna**, pilih **Bawaan**, **Hijau Pinus**, atau **Biru Sabak**.
2. Klik **Simpan tema** dan lihat hasil pada halaman.
3. Untuk kembali ke palet bawaan, klik **Kembalikan ke default**.

Tema berlaku pada aplikasi dan halaman masuk. Anda dapat memilih tiga tema yang tersedia; warna status dan kertas laporan tetap sama.

## 5.14 Akun pribadi dan keluar

Ikuti gambar dan langkah **Akun Saya**, **Ubah Sandi**, dan **Keluar** pada bagian 1.5. Periksa nama, peran, email, status, serta waktu masuk terakhir akun Anda. Ubah sandi melalui **Akun Saya**, lalu tekan **Keluar** setelah selesai. Admin IT tidak dapat mereset sandinya sendiri melalui **Kelola Akun**. Jika sandi sementara kedaluwarsa, minta Admin IT lain yang berwenang menerbitkan sandi baru.

# 6 Instalasi dan operasional server sekolah

Bab ini ditujukan kepada petugas teknis yang memasang dan mengelola server sekolah. Isinya adalah **contoh** pemasangan pada satu VPS Ubuntu/Debian dengan Nginx, PHP-FPM, MySQL, dan satu akun Linux aplikasi. VPS adalah server yang pengaturan sistemnya dikelola petugas. Jika penyedia server menyediakan panel web dan terminal, ikuti alur panel yang disebutkan di bawah.

Petugas menjalankan perintah pada bab ini saat pemasangan. Ganti domain `bk.example.sch.id`, lokasi `/var/www/sibk`, nama akun, dan database sesuai lingkungan sekolah. Gunakan paket rilis resmi dan cocokkan versinya dengan `RELEASE.json`.

![Gambar 94. CONTOH Arsitektur acuan deployment server sekolah](manualbook/gambar/deployment.png)

Semua diagram pada bab ini adalah ilustrasi alur, bukan tangkapan layar hasil pemasangan di server sekolah.

## 6.1 Siapkan server

![Gambar 95. CONTOH Prasyarat VPS sekolah](manualbook/gambar/v2-server-prasyarat.png)

1. Arahkan DNS (pengarah domain) ke VPS dan siapkan sertifikat HTTPS. Sediakan Nginx, MySQL, PHP-FPM dan PHP CLI **8.4.1 atau lebih baru dalam seri PHP 8**, Composer 2, serta layanan `cron` aktif. Web dan CLI harus memakai versi dan ekstensi yang sesuai.
2. Pasang cURL (libcurl minimal 7.49), GD untuk logo dan Excel, serta ekstensi lain yang diminta lockfile. Periksa `php -v`, `php -m`, `composer -V`, lalu `composer check-platform-reqs --no-dev` setelah dependensi terpasang. Node.js/npm yang kompatibel dengan Vite diperlukan di server atau mesin builder untuk aset. Jangan memakai `php artisan serve` atau `npm run dev` sebagai layanan produksi.
3. Buat akun Linux aplikasi, misalnya `sibk`, tanpa hak root. Taruh source rilis di `/var/www/sibk`. Proses PHP dan akun cron perlu membaca source serta menulis ke `storage/` dan `bootstrap/cache/`; `storage/app/private/branding` harus dapat ditulis untuk logo. Atur owner/grup sesuai akun proses PHP, tanpa izin `777`. Berkas `.env` hanya boleh dibaca akun yang memerlukannya.
4. Buat database MySQL khusus SIBK dan akun database khusus dengan hak hanya pada database itu, bukan root atau hak global. Migrasi (perubahan struktur database) memerlukan hak `CREATE`, `ALTER`, `INDEX`, dan `DROP` selain baca/tulis. Sesudahnya, hak akun runtime dapat dipersempit. Siapkan akun backup terpisah. Jangan memakai database pengembangan bersama.

![Gambar 96. CONTOH Alur HTTPS hingga PHP-FPM](manualbook/gambar/v2-server-nginx.png)

Contoh blok Nginx, dengan socket PHP-FPM dan jalur sertifikat disesuaikan server. Terbitkan sertifikat sebelum membuka akses pengguna:

```nginx
server {
    listen 80;
    server_name bk.example.sch.id;
    return 301 https://$host$request_uri;
}
server {
    listen 443 ssl;
    server_name bk.example.sch.id;
    root /var/www/sibk/public;
    index index.php;
    ssl_certificate /etc/letsencrypt/live/bk.example.sch.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/bk.example.sch.id/privkey.pem;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }
    location ~ /\. { deny all; }
}
```

Pada panel hosting, pilih PHP 8.4.1+ dan ekstensi yang sama untuk web/CLI, aktifkan HTTPS, dan atur *document root* (direktori yang dilayani web) tepat ke `public/`. Jalankan langkah terminal berikut melalui terminal panel atau bantuan administrator hosting. Jangan arahkan domain ke root proyek.

Setelah menyimpan konfigurasi Nginx sebagai situs aplikasi, jalankan `sudo nginx -t`. Hanya bila hasilnya sukses, muat ulang dengan `sudo systemctl reload nginx`.

Pada VPS berbasis systemd, `sudo systemctl enable --now cron` mengaktifkan layanan cron setelah boot. Pengelolaan layanan memerlukan petugas dengan akses administratif. Pemasang cron aplikasi tetap dijalankan sebagai akun aplikasi, bukan root.

**Batas unggahan.** Atur PHP-FPM agar `upload_max_filesize` minimal 2M dan `post_max_size` lebih besar, misalnya 8M. Pada Nginx, `client_max_body_size 8m;` di blok server memberi ruang untuk formulir multipart. Batas aplikasi tetap 2 MB untuk unggahan yang bersangkutan. Muat ulang PHP-FPM/Nginx sesudah mengubah konfigurasinya. Sesuaikan nama unit PHP-FPM dengan versi CLI yang dipilih.

**Lokasi yang dipakai contoh.** Domain `bk.example.sch.id` diganti domain sekolah, `/var/www/sibk` adalah direktori aplikasi, akun Linux `sibk` adalah pemilik proses aplikasi/cron, dan MySQL memakai database khusus. Ini parameter pemasangan yang ditetapkan petugas, bukan perubahan fitur aplikasi. Untuk paket PHP, gunakan kanal resmi OS/panel yang menyediakan versi kompatibel dan periksa `composer check-platform-reqs --no-dev`; nama paket tidak selalu sama pada setiap OS.

## 6.2 Instalasi pertama

![Gambar 97. CONTOH Pengisian lingkungan produksi tanpa rahasia asli](manualbook/gambar/v2-server-env.png)

1. Masuk sebagai akun aplikasi ke `/var/www/sibk`. Pada paket rilis klien, salin `.env.example` ke `.env` **hanya bila belum ada**. Jika memakai repo pengembangan penuh, gunakan `scripts/client-release/.env.example` sebagai template produksi. Isi domain dan akses database asli secara privat. Pertahankan `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://bk.example.sch.id`, `DB_CONNECTION=mysql`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`, dan `FILESYSTEM_DISK=local`. Jangan simpan credential di buku, repo, atau tiket.
2. Buat nilai rahasia `ALUMNI_RETENTION_KEY` untuk instalasi baru dengan perintah berikut dan salin hasilnya ke `.env`. Simpan bersama backup rahasia. Kunci 32 byte ini harus tetap sama ketika `APP_KEY` dirotasi atau server dipindah.

   ```sh
   php -r "echo 'base64:'.base64_encode(random_bytes(32)),PHP_EOL;"
   ```

![Gambar 98. CONTOH Urutan instalasi pertama pada database baru](manualbook/gambar/v2-server-instalasi.png)

3. Jalankan di server. Dua perintah npm boleh dijalankan di mesin builder; kirim `public/build` hasil build dari rilis source yang sama ke server. Jangan memakai `composer setup` karena skripnya otomatis membuat kunci dan menjalankan migrasi.

   ```sh
   composer install --no-dev --prefer-dist --optimize-autoloader
   composer check-platform-reqs --no-dev
   npm ci --ignore-scripts
   npm run build
   # Hanya instalasi baru ketika APP_KEY masih kosong:
   php artisan key:generate --force
   php artisan migrate --force
   php artisan db:seed --force
   php artisan storage:link
   php artisan config:cache
   php artisan view:cache
   ```

   `key:generate` hanya untuk pemasangan pertama tanpa `APP_KEY`. `migrate --force` memperbarui skema tanpa mereset data. Dalam produksi, `db:seed --force` hanya mengisi peran dan referensi; akun dan data murid tidak dibuat. Jangan gunakan `migrate:fresh`, reset database, atau seeder akun uji. `storage:link` membuat tautan disk publik; logo unggahan aplikasi justru tersimpan di `storage/app/private/branding` dan dilayani lewat route aplikasi.

![Gambar 99. CONTOH Pembuatan Admin IT awal dan pergantian sandi](manualbook/gambar/v2-server-admin.png)

4. Buat Admin IT pertama: jalankan `php artisan tinker` di terminal server, lalu masukkan kode berikut. Ganti nama dan email contoh dengan petugas sekolah yang sah. Kode menolak pembuatan jika Admin IT sudah ada.

   ```php
   $admin = Illuminate\Support\Facades\DB::transaction(function () {
       if (App\Models\User::whereHas('roles', fn ($q) => $q->where('slug', 'admin_it'))->exists()) {
           throw new RuntimeException('Admin sudah ada; gunakan akun tersebut.');
       }
       $role = App\Models\Role::where('slug', 'admin_it')->firstOrFail();
       $user = App\Models\User::create([
           'name' => 'Administrator Sekolah',
           'email' => 'admin@example.sch.id',
           'password' => Illuminate\Support\Str::random(64),
           'is_active' => true,
       ]);
       $user->roles()->attach($role);
       return $user;
   });
   $result = app(App\Services\TemporaryPasswordService::class)->issue($admin);
   echo $result->plainTextPassword;
   ```

   Sampaikan sandi sementara lewat saluran privat; pengguna wajib menggantinya saat login. Untuk akun Admin IT aktif yang kehilangan akses, gunakan `php artisan sibk:reset-admin-password` secara interaktif pada terminal server. Jangan gunakan akun demo.

**Contoh nilai lingkungan.** Salin template produksi utuh, lalu isi bidang berikut. Nilai dalam huruf besar adalah placeholder; jangan menyalinnya sebagai sandi sungguhan.

```dotenv
APP_NAME="Ruang BK"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bk.example.sch.id
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=NAMA_DATABASE_SEKOLAH
DB_USERNAME=AKUN_DATABASE_APLIKASI
DB_PASSWORD="ISI_RAHASIA_DI_SERVER"
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
```

`APP_KEY` dibuat melalui perintah instalasi pertama di atas dan `ALUMNI_RETENTION_KEY` diisi sekali dengan nilai acak yang dibuat khusus. Pertahankan seluruh opsi lain pada template produksi, termasuk pengamanan integrasi dan batas dokumen. Setelah `.env` diperbarui, jalankan ulang `php artisan config:cache`. Jangan menyalin `.env` lokal, alamat database demo, atau konfigurasi hosting demo ke server sekolah.

## 6.3 Scheduler dan retensi

![Gambar 100. CONTOH Batas retensi alumni](manualbook/gambar/v2-server-retensi.png)

1. Sebelum mengaktifkan cron pada database yang sudah berisi data, jalankan `php artisan sibk:hapus-alumni`. Perintah tanpa `--execute` hanya menampilkan pratinjau jumlah **memenuhi syarat** dan **ditunda**. Jangan menghapus relasi atau penanda secara manual untuk memaksa retensi.

   `--execute` menghapus permanen data BK alumni yang memenuhi syarat dalam transaksi per murid; scheduler menjalankannya setiap hari pukul 02.00 WIB. Hanya alumni dengan keputusan **lulus**, status **resmi keluar**, dan tanggal efektif yang telah mencapai satu tahun kalender yang dapat diproses. Murid aktif, pindah, dalam penanganan pengunduran diri, berstatus proses atau batal, serta alumni yang belum jatuh tempo tidak ikut dihapus. Konflik relasi atau keanggotaan yang masih aktif dapat menunda penghapusan.

   Setelah pemeriksaan keselamatan, proses ini membersihkan data identitas, layanan/arsip/tindak lanjut, prestasi, mirror e-Tatib, dan relasi audit lokal terkait. Sumber Dapodik dan e-Tatib tidak menerima perintah hapus. Penanda HMAC mencegah identitas yang telah dihapus diimpor ulang. Jika `ALUMNI_RETENTION_KEY` hilang atau berubah saat penanda sudah ada, impor dan penghapusan berhenti sampai kunci asli dipulihkan.
![Gambar 101. CONTOH Pemasangan dan pemeriksaan scheduler](manualbook/gambar/v2-server-scheduler.png)

2. Pastikan layanan `cron` aktif. Jalankan sebagai akun aplikasi `sibk`, **bukan root**, dari `/var/www/sibk`:

   ```sh
   php artisan sibk:pasang-scheduler --dry-run
   php artisan sibk:pasang-scheduler
   crontab -l
   php artisan schedule:list
   ```

    Dry run hanya memperlihatkan rencana. Pemasang menambah satu pemicu `schedule:run` setiap menit pada crontab akun itu, memakai path PHP/aplikasi yang terdeteksi, dan mengarahkan output ke `storage/logs/scheduler.log`. Ia mempertahankan entri lain dan berhenti jika ada entri scheduler yang berbeda. Sebelum memasang, periksa cron, timer, dan tugas panel lain agar pemicu tidak ganda.

    `schedule:list` hanya menampilkan definisi jadwal. Untuk membuktikan pemicu berjalan, tunggu satu menit; periksa waktu/log `storage/logs/scheduler.log` dan log layanan cron; lalu periksa hasil tugas yang memang jatuh tempo. Jangan menjalankan penghapusan `--execute` hanya untuk membuktikan cron.
3. Jika panel hosting tidak menyediakan `crontab`, buat **satu** tugas panel setiap menit dengan akun aplikasi dan PHP CLI yang sesuai:

   ```cron
   * * * * * cd /var/www/sibk && /usr/bin/php artisan schedule:run >> storage/logs/scheduler.log 2>&1
   ```

    Sesuaikan path PHP. Hari dan jam sinkronisasi e-Tatib diatur lewat Data Master (WIB) dan memakai pemicu Laravel yang sama; jangan buat cron e-Tatib terpisah.

    Driver konektor provider resmi tetap `unavailable` sampai kontraknya disahkan. API Siswa dan tautan e-Tatib yang dikelola lewat UI mempunyai alur terpisah serta tetap perlu ditinjau dan diaktifkan secara sah. Status driver provider tidak berarti jadwal UI otomatis sudah aktif. Dengan `QUEUE_CONNECTION=sync`, instalasi dasar tidak membutuhkan worker queue terpisah.

![Gambar 102. CONTOH Batas penghapusan alumni dan pengelolaan backup](manualbook/gambar/backup-retensi.png)

## 6.4 Pemeriksaan dan pembaruan

![Gambar 103. CONTOH Pembaruan rilis tanpa mengganti kunci dan data](manualbook/gambar/v2-server-update.png)

1. Buka `https://bk.example.sch.id/up` dan halaman login melalui HTTPS. Pastikan HTTP dialihkan ke HTTPS, aset tampil, dan source tidak dapat dibuka langsung. Login Admin IT sekolah, ganti sandi sementara, buat tahun ajaran, lalu periksa Data Master. Dengan akun sah per peran, periksa menu dan izin Guru BK, Koordinator BK, serta Waka Kesiswaan; akses tanpa izin harus ditolak. Uji impor murid, layanan, laporan, unggah logo, dan logout dengan data uji yang disetujui sekolah.
2. Pantau kapasitas disk, backup, MySQL/PHP-FPM/Nginx, `storage/logs/laravel.log`, `storage/logs/scheduler.log`, dan log cron. Rotasikan `scheduler.log` melalui kebijakan rotasi log server agar log setiap menit tidak memenuhi disk. Pastikan jam server sinkron; tanggal operasional, tampilan waktu, serta jadwal retensi/e-Tatib otomatis memakai WIB (`Asia/Jakarta`), tanpa pengaturan tambahan pengguna. Pertahankan `config/app.php` pada UTC untuk timestamp internal; jangan menggeser data lama atau jam sumber e-Tatib secara manual. Jika pembaruan otomatis e-Tatib sudah diaktifkan, periksa status sinkronisasi terakhir pada UI dan error log.
3. Sebelum pembaruan, uji rilis di validasi dan ambil backup. Jalankan `php artisan down`. Tempatkan source rilis baru tanpa menimpa `.env` atau `storage`, pasang dependensi sesuai lockfile, lalu bangun atau serahkan `public/build`. Jalankan `php artisan migrate --force`, `php artisan config:cache`, `php artisan view:cache`, lalu `php artisan up`. Periksa login, logo, skema, dan cron. Jangan mengulang `key:generate` atau membuat ulang kunci retensi.

   Bila lokasi rilis berubah, hentikan pemicu cron lama dahulu sesuai prosedur petugas. Perbarui cron sehingga hanya satu pemicu mengarah ke rilis aktif. Pemasang sengaja menolak entri yang berbeda agar tidak menimpa tugas lain. Jangan menyunting crontab bersamaan dengan proses pemasangan.

## 6.5 Backup dan pemulihan

![Gambar 104. CONTOH Satu snapshot backup database berkas dan kunci](manualbook/gambar/v2-server-backup.png)

1. Tentukan jadwal dan masa simpan cadangan (backup) sesuai kebijakan sekolah. Agar isi database dan berkas berasal dari titik waktu yang sama, hentikan perubahan aplikasi sementara dengan `php artisan down`. Jeda pemicu cron aplikasi selama backup dan pastikan tugas retensi tidak sedang berjalan.

   Cadangkan database, seluruh `storage/app/private` (termasuk logo), dan `.env` dengan **kedua kunci** dari titik waktu yang sama. Jalankan contoh berikut dari direktori aplikasi, dengan tujuan backup privat di luar `public/`. Akun backup harus mempunyai hak baca dan hak tambahan yang diperlukan bila menyertakan routines/triggers. Sandi MySQL diminta melalui prompt `-p`, bukan lewat argumen atau shell history:

   ```sh
   umask 077
   mysqldump --single-transaction --no-tablespaces \
     --routines --triggers -h 127.0.0.1 \
     -u NAMA_AKUN_BACKUP -p NAMA_DATABASE > sibk-YYYYMMDD.sql
   tar -czf sibk-YYYYMMDD-files.tar.gz .env storage/app
   php artisan up
   ```

   Pastikan setiap perintah berhasil dan berkas tidak kosong sebelum melanjutkan. Contoh ini bukan skrip otomatis yang boleh meneruskan kegagalan. Pindahkan salinan terenkripsi ke tempat backup di luar document root dan server utama, batasi aksesnya, serta uji pemulihannya berkala. Aktifkan kembali pemicu cron setelah backup.

   Lindungi SQL dan arsip itu segera karena berisi data murid dan rahasia. Sertakan berkas lain di `storage/app` bila instalasi memakainya. Backup serta unduhan lama **tidak** dibersihkan oleh `sibk:hapus-alumni`; kelola masa simpannya terpisah.
![Gambar 105. CONTOH Pemulihan terisolasi sebelum aplikasi dibuka](manualbook/gambar/v2-server-restore.png)

2. Saat insiden, hentikan akses aplikasi. Pilih **satu** backup utuh dan siapkan source rilis yang cocok. Pulihkan SQL ke database kosong khusus pemulihan, serta `storage/app/private` dan `.env` dari titik yang sama:

   ```sh
   mysql -h 127.0.0.1 -u NAMA_AKUN_PEMULIHAN -p \
     NAMA_DATABASE_PEMULIHAN < sibk-YYYYMMDD.sql
   ```

   Ubah hanya alamat database/domain dalam `.env` hasil pemulihan. Pertahankan `APP_KEY` untuk data terenkripsi dan `ALUMNI_RETENTION_KEY` untuk penanda HMAC. Jangan memulihkan tabel penanda saja atau mengganti kunci tanpa database pasangannya.

   Backup lama bisa memunculkan lagi alumni yang pernah dihapus. Batasi akses, tinjau retensi dan backup, lalu jalankan pratinjau `php artisan sibk:hapus-alumni` sebelum mengaktifkan cron. Jangan impor backup lama ke produksi yang masih aktif.
3. Pulihkan owner/izin file, `vendor` dan `public/build` dari rilis cocok, lalu jalankan `php artisan storage:link`, `php artisan config:cache`, `php artisan view:cache`, dan `php artisan schedule:list`. Verifikasi `/up`, login, logo, data/hak akses per peran, dan satu eksekusi cron nyata sebelum membuka akses kembali. Bila skema backup lebih tua daripada source, jalankan migrasi forward-only `php artisan migrate --force` hanya setelah kecocokan rilis dan backup cadangan dipastikan. Jangan rollback penanda retensi atau memakai `migrate:fresh`.

## 6.6 Gangguan yang sering muncul

![Gambar 106. CONTOH Alur pemeriksaan gangguan tanpa membocorkan rahasia](manualbook/gambar/v2-server-gangguan.png)

| Gejala | Periksa dan tindakan |
| --- | --- |
| 403 pada halaman tertentu | Periksa status akun, peran, dan izin; penolakan bisa memang benar. Jika seluruh situs gagal, periksa document root `public/` serta izin baca source. |
| 419 saat mengirim formulir | Periksa HTTPS, `APP_URL`, cookie sesi aman, waktu server, `SESSION_DRIVER=database`, dan tabel sesi. Hapus cookie lama setelah perubahan domain. |
| 500/halaman kosong | Periksa `storage/logs/laravel.log`, log PHP-FPM, izin `storage/` dan `bootstrap/cache/`, koneksi database, ekstensi PHP, dan cache konfigurasi. Jangan aktifkan `APP_DEBUG=true` untuk pengguna produksi. |
| CSS/JavaScript hilang | Pastikan `public/build/manifest.json` dan aset dari rilis yang sama ada; periksa HTTPS serta document root. |
| Logo hilang | Periksa berkas privat `storage/app/private/branding`, izin proses PHP, dan route logo. `storage:link` membuat tautan disk publik, bukan lokasi logo privat. |
| Tautan API dari Data Master ditolak | Periksa URL HTTPS publik, resolusi DNS, koneksi, serta format respons API Siswa/e-Tatib. Tujuan jaringan privat ditolak. Driver dan origin allowlist konektor provider tidak mengatur kolom tautan UI ini. |
| Konektor provider resmi ditolak | Periksa driver provider, origin yang disetujui, kontrak, HTTPS, dan jaringan sesuai konfigurasi konektor. Jangan melonggarkan proteksi tujuan hanya agar uji lolos. |
| Scheduler/e-Tatib berhenti | Periksa layanan cron, akun dan path PHP pada `crontab -l`, izin `storage/logs`, `scheduler.log`, serta hari/jam e-Tatib di UI. `schedule:list` bukan bukti eksekusi. |
| Impor tertahan setelah restore | Cocokkan `ALUMNI_RETENTION_KEY` dengan database penanda dari backup yang sama dan periksa log; pulihkan kunci asli, jangan hapus penanda. |

# 7 Latihan lengkap dan pemeriksaan serah terima

## 7.1 Skenario latihan satu siklus

Gunakan lingkungan uji dan murid sintetis yang disetujui untuk latihan ini. Jangan mengunggah data contoh atau menjalankan latihan hapus pada produksi sekolah. Urutan berikut melibatkan beberapa peran; ikuti bidang dan tombol pada bab terkait.

1. **Admin IT:** buat akun Guru BK, Koordinator BK, dan Waka sesuai kombinasi peran yang sah. Siapkan dua rombel (rombongan belajar) contoh dan satu tahun ajaran persiapan. Unduh templat daftar murid dan penempatan rombel, isi murid uji dengan NISN sebagai teks, lalu tinjau dan impor. Pastikan pesan berhasil dan jumlah sesuai.

   ![Gambar 107. Admin meninjau daftar murid sebelum impor pada latihan](manualbook/gambar/v2-roster-excel-preview.png)
2. **Koordinator BK:** pilih tahun persiapan, tetapkan Guru BK untuk setiap rombel yang dipakai, periksa peringatan kesiapan, lalu aktifkan. Masuk sebagai Guru BK untuk memastikan kelas dan muridnya terbaca.

   ![Gambar 108. Koordinator memeriksa kesiapan dan aktivasi tahun ajaran](manualbook/gambar/v2-tahun-aktifkan.png)
3. **Guru BK:** buat satu permasalahan dari sumber non-e-Tatib untuk murid uji. Periksa munculnya status Sedang Proses, tambahkan satu tindak lanjut, simpan Ringkasan, lalu selesaikan. Buat satu konsultasi dengan hasil yang lengkap dan periksa keduanya pada profil murid.

   ![Gambar 109. Guru menambahkan tindak lanjut kasus latihan](manualbook/gambar/v2-kasus-tindaklanjut.png)
4. **Guru BK:** catat satu progres penanganan pengunduran diri pada murid uji lain dan tambahkan progres berkas. Pastikan ini tidak otomatis mengubah status murid menjadi alumni atau resmi keluar.

   ![Gambar 110. Guru mencatat progres penanganan pengunduran diri](manualbook/gambar/v2-pengunduran-progres.png)
5. **Waka:** periksa Dashboard dan Laporan menggunakan tahun/kelas yang sama. Pastikan ringkasan terbaca dan tombol untuk mengubah atau mengunduh uraian layanan tidak tersedia. Catat prestasi uji secara manual, lalu coba template impor prestasi dengan data uji berbeda.

   ![Gambar 111. Waka mencatat prestasi uji setelah membaca ringkasan](manualbook/gambar/v2-prestasi-buat.png)
6. **Guru BK atau Koordinator:** buka Laporan, pilih jenis layanan dan kelas, tekan Buat rekap, ubah rentang pada satu semester, lalu unduh Excel dan simpan PDF. Cocokkan identitas, jumlah, periode, kop, serta penandatangan. Uji pada ponsel dengan periode dilipat.

   ![Gambar 112. Pratinjau rekap sebelum mengunduh Excel atau mencetak PDF](manualbook/gambar/v2-laporan-preview.png)
7. **Admin IT:** periksa logo dan tema. Jika memakai API uji resmi yang disetujui, tinjau e-Tatib terlebih dahulu, lalu uji sinkronisasi manual; jadwal otomatis baru diaktifkan setelah tujuan dan data benar.

   ![Gambar 113. Admin meninjau e-Tatib sebelum sinkronisasi](manualbook/gambar/v2-etatib-preview.png)
8. **Petugas server:** verifikasi satu pemicu cron, bukti log berjalan, status sinkronisasi pada waktu yang ditetapkan, serta keberhasilan backup dan pemulihan pada database terisolasi. Pratinjau retensi cukup untuk pemeriksaan; jangan menghapus data alumni sekadar untuk menguji cron.

   ![Gambar 114. CONTOH pemeriksaan scheduler server sekolah](manualbook/gambar/v2-server-scheduler.png)

## 7.2 Daftar pemeriksaan sebelum digunakan sekolah

![Gambar 115. Urutan persiapan data dan penugasan yang diperiksa sebelum serah terima](manualbook/gambar/alur-operasional.png)

- **Petugas server:** pastikan domain HTTPS, document root `public/`, PHP web/CLI, database, dan aset produksi berfungsi. Halaman error tidak menampilkan rahasia.
- **Admin IT:** pastikan akun pribadi setiap peran dibuat dan penggantian sandi sementara berhasil. Email penandatangan serta jumlah akun aktif yang memegang peran terkait sudah benar.
- **Admin IT dan Koordinator BK:** periksa tahun ajaran aktif, daftar murid, rombel, dan pembagian kelas.
- **Guru BK dan Admin IT:** periksa bahwa Guru BK dapat mencatat murid sesuai kewenangannya dan peran lain tidak memperoleh hak menulis layanan yang tidak semestinya.
- **Waka:** periksa bahwa ringkasan dapat dibaca dan prestasi dapat dikelola, sementara uraian lengkap dan unduhan layanan yang dibatasi tetap tidak tersedia.
- **Admin IT dan Waka:** periksa bahwa templat daftar murid dan prestasi dapat diunduh dan berkas yang salah menampilkan kesalahan yang jelas tanpa impor sebagian.
- **Guru BK atau Koordinator BK:** periksa bahwa rekap dan dokumen contoh sesuai periode satu semester, termasuk logo, orientasi, dan penandatangan.
- **Petugas server dan Admin IT:** periksa bahwa cron aktif, tidak ganda, dan memiliki bukti eksekusi. Cocokkan hari/jam e-Tatib dengan pengaturan di aplikasi, bukan sekadar pengaturan di server.
- **Petugas server:** pastikan kunci aplikasi dan kunci retensi disimpan aman. Uji pemulihan backup utuh dan tetapkan masa simpan backup/unduhan.

## 7.3 Pertanyaan penggunaan sehari-hari

### Bagaimana jika murid tidak muncul saat dicari?

![Gambar 116. Daftar Murid dengan tahun, kelas, dan pencarian identitas](manualbook/gambar/v2-murid-daftar.png)

1. **Guru BK:** periksa tahun ajaran, kelas binaan, dan status murid. **Admin IT:** periksa keberhasilan impor.
2. **Guru BK:** jika formulir mendukung identitas sementara, isi NISN/nama dan rombel yang sah. Jangan memakai identitas murid lain.

### Mengapa catatan guru lain tidak dapat diedit?

![Gambar 117. Aksi kasus yang mengikuti kepemilikan dan kewenangan Guru BK](manualbook/gambar/v2-kasus-daftar.png)

1. **Guru BK:** periksa siapa pencatat atau pemilik catatan. Hak membaca tidak otomatis memberi hak menulis.
2. **Guru BK:** koordinasikan koreksi dengan pemilik catatan; jangan mengganti peran agar tombol edit muncul.

### Mengapa rekap kosong atau tidak dapat diunduh?

![Gambar 118. Periode dan status pratinjau rekap sebelum ekspor](manualbook/gambar/v2-laporan-preview.png)

1. **Guru BK atau Koordinator BK:** periksa jenis layanan, kelas, tanggal, semester, dan catatan dalam cakupan.
2. **Guru BK atau Koordinator BK:** persempit filter bila lebih dari 2.500 catatan.
3. **Guru BK atau Koordinator BK:** tunggu pratinjau selesai dimuat sebelum memilih Excel atau cetak/PDF.

### Mengapa data e-Tatib belum berubah?

![Gambar 119. Riwayat sinkronisasi untuk memeriksa proses e-Tatib terakhir](manualbook/gambar/v2-sinkronisasi-riwayat.png)

1. **Admin IT:** periksa hasil dan waktu sinkronisasi terakhir serta pencocokan identitas.
2. **Admin IT:** periksa hari/jam otomatis. **Petugas server:** periksa log scheduler. Data yang tidak dikirim lagi oleh sumber tidak otomatis menghapus salinan lama.

### Mengapa data alumni belum terhapus setelah satu tahun?

![Gambar 120. CONTOH syarat dan penundaan retensi alumni](manualbook/gambar/v2-server-retensi.png)

1. **Admin IT bersama petugas yang berwenang atas data murid:** periksa keputusan lulus resmi dan tanggal efektif yang sah.
2. **Petugas server:** periksa scheduler dan alasan penundaan. Nama yang absen dari API atau progres pengunduran diri bukan penetapan alumni.

### Mengapa tema berbeda dari contoh buku?

![Gambar 121. Pilihan Tema warna yang tersedia pada Manajemen Web](manualbook/gambar/v2-web-tema.png)

1. Minta Admin IT memeriksa pilihan tema pada **Manajemen Web**.
2. Gunakan menu dan alur sesuai peran; pilihan warna bebas tidak tersedia pada versi ini.

# 8 Istilah dan rujukan petugas

## 8.1 Istilah yang dipakai

- **Dashboard:** halaman ringkasan yang tampil setelah Anda masuk ke aplikasi. Isinya mengikuti peran akun.
- **Tab:** pilihan bagian di dalam halaman, misalnya tab **Dapodik**, **e-Tatib**, dan **Sinkronisasi** pada **Data Master**.
- **Filter:** pilihan untuk membatasi data yang ditampilkan, misalnya berdasarkan kelas atau tahun ajaran.
- **Templat (template):** berkas contoh dengan susunan kolom yang disediakan aplikasi. Isi berkas tersebut tanpa mengganti judul kolomnya.
- **NISN:** singkatan dari Nomor Induk Siswa Nasional, yaitu nomor identitas murid.
- **API:** penghubung untuk mengambil data dari aplikasi lain. Ruang BK memakai API Siswa dan e-Tatib untuk mengambil data dari sistem sumber.
- **e-Tatib:** aplikasi sumber yang mencatat pelanggaran tata tertib murid. Salinan datanya di Ruang BK menjadi rujukan bagi Guru BK, bukan otomatis menjadi catatan layanan konseling.
- **Sinkronisasi:** proses memperbarui salinan data e-Tatib dari sumbernya. Periksa hasilnya pada riwayat sinkronisasi.
- **Impor:** memasukkan data dari sistem sumber atau berkas ke Ruang BK. Ikuti pemeriksaan yang diminta pada fitur yang digunakan.
- **Ekspor:** mengunduh hasil dari aplikasi, misalnya rekap dalam Excel.
- **Roster:** daftar murid dan penempatan rombel pada tahun ajaran tertentu.
- **Rombel (rombongan belajar):** kelompok murid yang terdaftar dalam satu kelas.
- **Persiapan:** tahun ajaran/data sementara yang disiapkan sebelum operasional, dengan status verifikasi sesuai sumbernya.
- **Kelas binaan:** kelas yang ditugaskan kepada Guru BK dan menentukan kewenangan pencatatan aktif.
- **Draf:** isian sementara yang belum berhasil disimpan sebagai catatan resmi.
- **Mirror e-Tatib:** salinan lokal catatan sumber untuk dibaca; bukan aplikasi e-Tatib sumber dan tidak otomatis menjadi catatan konseling.
- **Pratinjau:** pemeriksaan isi/rencana sebelum diterapkan atau dicetak. Menutupnya bukan bukti bahwa impor sudah tersimpan.
- **Scheduler:** pengatur tugas aplikasi yang memeriksa tugas yang jatuh tempo.
- **Cron:** layanan server yang memicu scheduler aplikasi setiap menit.
- **Retensi alumni:** pembersihan permanen data BK alumni yang memenuhi syarat setelah satu tahun kalender, disertai penanda pencocokan HMAC untuk mencegah impor ulang.
- **HMAC:** penanda pencocokan identitas yang digunakan retensi untuk menolak impor ulang data alumni yang telah dihapus. Kunci penandanya harus tetap sama.
- **Arsip layanan:** pengeluaran catatan dari daftar aktif melalui mekanisme aplikasi; berbeda dari penghapusan permanen retensi dan tidak berarti ada tombol pemulihan di UI.
- **Cadangan (backup):** salinan database, berkas privat, dan kunci dari titik waktu yang sama untuk pemulihan.

## 8.2 Rujukan instalasi

![Gambar 122. CONTOH hubungan cadangan database berkas dan kunci saat serah terima server](manualbook/gambar/v2-server-backup.png)

Ikuti konfigurasi rilis Ruang BK yang diserahkan. Persyaratan PHP mengikuti lockfile dan `composer check-platform-reqs`; gunakan rujukan berikut untuk platform server.

- [Laravel Deployment](https://laravel.com/framework/docs/13.x/deployment) untuk document root, direktori yang dapat ditulis, dan konfigurasi produksi.
- [Laravel Task Scheduling](https://laravel.com/framework/docs/13.x/scheduling) untuk pemicu penjadwalan dan pemeriksaan jadwal.
- [Laravel File Storage](https://laravel.com/framework/docs/13.x/filesystem) untuk perbedaan disk privat/publik dan tautan storage.
- [MySQL mysqldump](https://dev.mysql.com/doc/refman/8.4/en/mysqldump.html) untuk opsi serta hak akun backup.

Catat domain, versi rilis, lokasi aplikasi, penanggung jawab, jadwal sinkronisasi, lokasi backup, dan prosedur pemulihan dalam dokumen serah terima privat. Simpan sandi, APP_KEY, ALUMNI_RETENTION_KEY, dan URL API rahasia terpisah dari buku umum ini.
