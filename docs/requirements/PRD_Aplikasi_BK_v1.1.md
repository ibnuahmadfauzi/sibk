<!--
Canonical Markdown baseline for version 1.1.
Derived from PRD_Aplikasi_BK_v1.0 with approved integration decisions through 9 September 2026.
The version 1.0 Markdown and DOCX artifacts remain immutable archives.
-->

**DOKUMEN KEBUTUHAN PRODUK (PRD)**

**Aplikasi BK**

Baseline kebutuhan produk untuk layanan Bimbingan dan Konseling

**Versi:** 1.1

**Status:** Baseline final untuk pengembangan MVP

**Tanggal:** 23 Agustus 2026

**Amandemen disetujui:** 9 September 2026

**Konteks:** SMKN 1 Surabaya

# Ringkasan produk

Aplikasi BK menjadi ruang kerja terpusat untuk mencatat layanan BK, memantau kasus dan tindak lanjut, menjaga kesinambungan histori murid, serta menyusun laporan dari data operasional yang sama. MVP berfokus pada Guru BK, Koordinator BK, Waka Kesiswaan, dan Admin IT.

Koordinator BK menjadi penanggung jawab operasional penggunaan aplikasi. Guru BK tetap menjadi pemilik proses layanan dan catatan profesional. Waka Kesiswaan menggunakan akses hanya-baca untuk ringkasan aman seluruh kasus sekolah, laporan penanganan, dan detail kasus yang memang dikoordinasikan kepadanya. Admin IT mengelola akun, infrastruktur, integrasi, dan data master tanpa memperoleh akses otomatis ke isi layanan BK.

Aplikasi BK tidak menggantikan e-Tatib atau Dapodik. Pelanggaran dan poin resmi tetap dikelola di e-Tatib, sedangkan identitas murid, kelas, dan tahun ajaran tetap mengacu pada Dapodik. Aplikasi BK membaca data tersebut dan mencatat layanan BK yang berkaitan dengannya.

Admin IT menyiapkan URL, identitas sumber yang diharapkan, dan credential Dapodik/e-Tatib melalui PG-501 dengan alur Simpan → Uji → Aktifkan. Browser hanya mengirim konfigurasi dan trigger; backend melakukan komunikasi server-to-server, pemetaan, validasi, dan penyimpanan. Fondasi konfigurasi dapat tersedia sebelum kontrak provider disahkan, tetapi driver production dan sinkronisasi nyata tetap diblokir sampai admission kontrak selesai.

Ketika Dapodik tahun ajaran baru terlambat 2–3 bulan, Admin IT dapat menyiapkan tahun ajaran dan daftar minimum seluruh murid aktif berdasarkan dasar resmi sekolah. Koordinator BK melengkapi penugasan dan dapat mengaktifkan tahun ajaran pada atau setelah tanggal mulai; setelah aktif, Guru BK dapat langsung melayani murid dalam scope penugasannya. Murid tahun sebelumnya yang belum memiliki penempatan pada tahun target ditampilkan sebagai **Perlu Konfirmasi** tanpa menebak status akademik dan tanpa menghalangi aktivasi murid lain. Data persiapan sementara selalu diberi penanda dan tidak dinyatakan sebagai data resmi Dapodik.

# Masalah yang diselesaikan

Pencatatan BK berjalan menggunakan buku, Excel, Google Spreadsheet, WhatsApp, dan Word. Data yang tersebar memperlambat pencarian riwayat, penyusunan laporan, pemantauan tindak lanjut, serta pengendalian akses terhadap informasi sensitif.

| **Gejala**                  | **Penyebab langsung**                                            | **Kebutuhan produk**                                                             |
|-----------------------------|------------------------------------------------------------------|----------------------------------------------------------------------------------|
| Laporan terlambat           | Data dikumpulkan dari beberapa media.                            | Rekap dibentuk dari satu sumber data operasional.                                |
| Status kasus sulit dipantau | Jadwal dan hasil tindak lanjut tersimpan terpisah.               | Setiap kasus memiliki riwayat kronologis dan jadwal berikutnya.                  |
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
| Waka perlu memantau murid yang memiliki kasus dan hasil penanganannya. | Ringkasan aman seluruh kasus tersedia hanya-baca; detail kasus hanya tersedia bila koordinasi tercatat. |
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
| Guru BK        | Mengelola layanan untuk kelas ampuan dan kasus khusus; membaca histori murid dalam scope aktif, termasuk murid dari data persiapan sementara setelah aktivasi operasional. | Tidak mengakses murid di luar tanggung jawabnya dan tidak mengubah catatan profesional lama milik Guru BK sebelumnya.                                           |
| Koordinator BK | Penanggung jawab operasional; mengatur pembagian, kasus khusus, pengalihan, verifikasi operasional, aktivasi tahun ajaran setelah penugasan lengkap, serta rekap gabungan seluruh Guru BK aktif. | Jabatan koordinator tidak otomatis membuka konsultasi sensitif. Jika merangkap Guru BK, akses sensitif tetap mengikuti scope Guru BK.                           |
| Waka Kesiswaan | Memantau ringkasan aman seluruh kasus sekolah, membaca laporan penanganan, dan terlibat dalam koordinasi di luar aplikasi saat diperlukan. | Hanya-baca. Detail kasus hanya untuk kasus yang dikoordinasikan; tidak mengubah catatan profesional dan tidak membaca isi lengkap konsultasi, catatan internal, dokumen sensitif, atau NISN. |
| Admin IT       | Mengelola akun, infrastruktur, konfigurasi koneksi PG-501, persiapan data tahun ajaran berdasarkan dasar resmi sekolah, sinkronisasi, pratinjau pencocokan, rekonsiliasi identitas, dan koreksi master melalui sumber resmi. | Hak teknis tidak otomatis memberi akses ke isi kasus, layanan, atau konsultasi; credential tidak ditampilkan kembali.                                            |
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
| Master dan identitas sementara | Master Dapodik; data persiapan sementara tahun ajaran dengan impor minimum NISN, nama, dan rombel; serta identitas sementara ketika kasus muncul sebelum sinkronisasi. | Inti        |
| Kasus dan tindak lanjut        | Pembuatan kasus, penanganan awal, jadwal, hasil tindak lanjut, koordinasi Waka, dan penyelesaian.      | Inti        |
| Konteks e-Tatib                | Membaca melalui API dan menautkan pelanggaran/poin tanpa pencatatan ulang atau write-back.             | Inti        |
| Konfigurasi integrasi          | PG-501 untuk URL, identitas sumber, credential, status Simpan/Uji/Aktifkan, dan ketersediaan adapter.  | Inti        |
| Konsultasi minimum             | Metadata, jadwal, status, ringkasan umum, serta pemisahan isi sensitif.                                | Inti        |
| Data dan histori murid         | Profil, e-Tatib, kasus, layanan, konsultasi, tindak lanjut, serta histori lintas kelas dan Guru BK.    | Inti        |
| Dashboard dan laporan          | Pemantauan, rekap per scope Guru BK, rekap gabungan Koordinator, dan laporan Waka yang diizinkan.      | Inti        |
| Akses Waka                     | Portal khusus berisi ringkasan aman seluruh kasus dan laporan penanganan; detail hanya-baca pada kasus yang dikoordinasikan. | Inti        |
| Audit dan koreksi              | Riwayat perubahan, koreksi operasional layanan yang selesai, dan rekonsiliasi identitas.               | Inti        |
| Retensi                        | Data tidak dihapus sebelum tersimpan minimum tiga tahun; penghapusan otomatis belum diaktifkan.        | Inti        |
| Prestasi                       | Pencatatan, riwayat, bukti yang diizinkan, verifikasi, dan laporan minimum.                            | P0 bertahap |

Prestasi tetap termasuk P0, tetapi dikerjakan setelah fungsi kasus, tindak lanjut, laporan, dan pengendalian akses inti stabil.

Konfigurasi koneksi pada PG-501 dapat disimpan sebelum kontrak provider tersedia. Uji, aktivasi, dan sinkronisasi production tetap gagal tertutup sampai dokumentasi serta adapter resmi lolos admission kontrak.

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
| Data persiapan sementara | Admin IT menyiapkan tahun ajaran, rombel, dan daftar minimum `nisn,nama,rombel` dari dasar resmi sekolah ketika Dapodik terlambat. Data diberi penanda, bukan data resmi Dapodik atau master alternatif. |
| Aktivasi operasional | Koordinator BK mengaktifkan tahun ajaran hanya setelah data dan penugasan lengkap serta tanggal mulai telah tiba. Sebelum tanggal mulai, tahun lengkap berstatus siap tetapi belum dapat diaktifkan. Guru BK memperoleh scope murid berdasarkan tahun ajaran aktif dan penugasan, terlepas dari asal data. Provider tidak dapat mengubah status aktif. |
| Pergantian tahun ajaran | Sistem tidak menaikkan kelas atau menetapkan status akademik otomatis. Impor daftar target membuat histori penempatan baru berdasarkan NISN exact; murid lama tanpa penempatan target hanya ditandai **Perlu Konfirmasi** secara read-only dan tidak memblokir aktivasi keseluruhan. |
| Pratinjau pencocokan | Tarik Dapodik hanya menyiapkan hasil cocok, baru, berubah, dan konflik. Cache operasional berubah setelah konfirmasi Admin IT; pencocokan otomatis murid hanya melalui NISN exact. |
| Alur koneksi        | Admin IT mengelola konfigurasi melalui PG-501 dengan urutan Simpan, Uji, lalu Aktifkan; perubahan material membatalkan verifikasi dan menonaktifkan koneksi. |
| Pemrosesan integrasi | Browser hanya mengirim konfigurasi dan trigger; fetch, mapping, validasi snapshot, dan penyimpanan dilakukan backend secara server-to-server. |
| Credential sumber  | Credential provider wajib least-privilege/read-only, disimpan terenkripsi, tidak ditampilkan kembali, dan tidak masuk response, session, audit, atau log. |
| Identitas sumber   | Probe dan setiap sinkronisasi harus membuktikan identitas sekolah/sumber yang dilaporkan cocok dengan nilai yang diharapkan.                   |
| Endpoint outbound  | Origin harus cocok exact dengan allowlist deployment. Metadata/link-local/multicast/unspecified selalu ditolak; private/loopback hanya diizinkan bila origin exact terdaftar dan flag opt-in private-network provider aktif. Redirect, proxy tak tepercaya, TLS invalid, DNS rebinding, dan origin drift ditolak secara fail-closed; koneksi diikat ke alamat tervalidasi dengan Host/SNI tetap benar. |
| Kontrak adapter    | `driver_id`, `adapter_version`, dan `contract_version` disimpan terpisah. Digest kebijakan endpoint mencakup versi policy, exact origins yang dikanonisasi, dan flag private-network provider; driver production tetap tidak tersedia sampai kontrak resmi disahkan. |
| Snapshot internal  | Adapter hanya memetakan field resmi yang diperlukan ke snapshot internal. Raw payload tidak disimpan secara default, dan evidence serta validator executable harus membuktikan identitas, schema, pagination, ukuran, dan full/partial semantics sebelum import. |
| Konsistensi operasi | Operasi per provider diserialisasi, memakai fencing token dan hard deadline di bawah masa lease, serta mempertahankan data lama saat gagal atau hasil menjadi stale. |
| Identitas sementara | Hanya dibuat saat ada kasus/layanan sebelum sinkronisasi, memakai NISN dan nama, lalu dicocokkan tanpa duplikasi.                               |
| Kesinambungan data  | Penerapan Dapodik menempelkan identitas sumber pada ID internal yang sama; kasus, konsultasi, prestasi, penugasan, dan histori BK tidak dibuat ulang atau ditimpa. |
| Arah integrasi      | Ruang BK hanya membaca Dapodik dan e-Tatib; data persiapan, layanan BK, serta hasil koreksi tidak ditulis kembali ke provider.                  |
| Nama resmi          | Setelah rekonsiliasi, tampilan menggunakan nama resmi dari Dapodik/e-Tatib; nilai masukan awal tetap tersimpan pada audit.                      |
| Kewenangan          | Daftar, pencarian, detail, dashboard, laporan, ekspor, URL, dan API menerapkan batas yang sama.                                                 |
| Histori murid       | Guru BK dengan scope aktif dapat membaca histori layanan/konsultasi murid lintas kelas dan pergantian guru, tetapi tidak mengubah catatan lama. |
| Pelayanan BK        | Kasus dan konsultasi memakai status **Baru dicatat**, **Sedang diproses**, **Membutuhkan tindak lanjut**, **Selesai**, atau **Dibatalkan**. Catatan selesai/dibatalkan terkunci dan hanya dapat diperbaiki melalui koreksi terverifikasi. |
| Kode kasus          | Kode kasus tetap dibuat untuk kebutuhan internal, relasi, audit teknis, dan integritas data, tetapi tidak ditampilkan pada UI, pencarian pengguna, laporan, ekspor, dashboard, notifikasi, atau audit yang terlihat pengguna. |
| Koordinasi Waka     | Waka membaca proyeksi aman seluruh kasus; detail hanya tersedia setelah koordinasi dicatat. Koordinasi dilakukan di luar aplikasi dan Guru BK/Koordinator hanya mencatat tanggal, pihak, ringkasan hasil, serta tindak lanjut yang disepakati. |
| Penugasan           | Setiap kasus memiliki satu penanggung jawab aktif. Perubahan menyimpan tanggal efektif dan dasar keputusan; kasus aktif tidak berpindah otomatis akibat pergantian kelas atau tahun ajaran. |
| Koreksi             | Koordinator memverifikasi koreksi operasional catatan pelayanan. Koreksi data master dikoordinasikan dengan Admin IT di luar aplikasi dan diproses pada sumber resmi; fitur laporan koreksi master baru tidak dibuat pada MVP. |
| Audit               | Perubahan status, hasil akhir, penugasan, koordinasi, kewenangan, akun, koreksi, dan rekonsiliasi dicatat otomatis.                             |
| Retensi             | Kasus, layanan, konsultasi, prestasi, dan audit disimpan minimum tiga tahun; prosedur penghapusan tetap memerlukan kebijakan operasional.       |

# Arsitektur informasi dan laporan

Navigasi utama Guru BK terdiri atas Dashboard, Layanan BK, Data Murid, dan Laporan. Waka memakai tampilan khusus yang sederhana: Ringkasan, Murid dengan Kasus, Laporan Penanganan, dan Laporan Sekolah. Notifikasi dan Akun tersedia secara global. Penugasan tersedia sesuai peran Koordinator, sedangkan pengelolaan akun, rekonsiliasi identitas, data master, dan sinkronisasi tersedia bagi Admin IT. Dapodik dan e-Tatib tetap menjadi sistem sumber, bukan modul navigasi utama.

| **Area**            | **Fungsi**                                                                                                |
|---------------------|-----------------------------------------------------------------------------------------------------------|
| Dashboard           | Kondisi kerja, kasus yang perlu ditindaklanjuti, jadwal, aktivitas, dan kasus terkoordinasi sesuai peran. |
| Layanan BK          | Daftar kasus, pembuatan kasus, penanganan, tindak lanjut, konsultasi, koordinasi Waka, dan penyelesaian.  |
| Data Murid          | Profil serta histori pelanggaran, kasus, layanan, konsultasi, tindak lanjut, dan prestasi yang diizinkan. |
| Laporan             | Rekap, filter, pratinjau, cetak, dan ekspor sesuai scope pengguna.                                        |
| Penugasan           | Pembagian kelas, periode efektif, kasus khusus, pengalihan, dan dasar keputusan resmi.                    |
| Administrasi teknis | Akun, infrastruktur, konfigurasi koneksi PG-501, status sinkronisasi, kesalahan pemetaan, dan rekonsiliasi identitas. |

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

Laporan Penanganan Waka memakai satu halaman dengan filter status dan periode. Murid, kelas, bidang layanan, Guru BK, dan tanggal dapat diurutkan melalui header tabel. Waka tidak menerima kode kasus, NISN, isi konsultasi, catatan internal, atau dokumen sensitif.

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
| Istilah layanan | Nama resmi, kardinalitas, kewajiban pengisian, jenis tindak lanjut, dan status operasional. | Guru BK dan Koordinator BK  |
| Prestasi        | Verifikator, status, bukti yang boleh disimpan, dan format laporan.                         | Koordinator BK dan Waka     |
| Dokumen         | Format, ukuran, akses, pemulihan, dan prosedur penghapusan setelah batas minimum.           | Koordinator, Waka, Admin IT |
| Ekspor          | Format cetak/ekspor dan kebutuhan penandaan atau pencatatan khusus.                         | Koordinator BK dan Waka     |

# Sumber dan riwayat versi

Sumber penyusunan: kuesioner kebutuhan Aplikasi BK, contoh pencatatan berjalan, diskusi perancangan, PRD v0.5, SRS v0.3, inventaris antarmuka v0.1, keputusan validasi Koordinator BK/Guru BK dan Waka Kesiswaan sampai 13 Agustus 2026, keputusan arsitektur fondasi konfigurasi integrasi tanggal 23 Agustus 2026, amandemen keterlambatan Dapodik yang disetujui 9 September 2026, serta keputusan alur operasional tahun ajaran, pelayanan BK, dan pemantauan Waka yang disetujui 12 September 2026.

| **Versi** | **Tanggal**     | **Perubahan**                                                                                                                                                                                                                     |
|-----------|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 0.5       | 12 Agustus 2026 | Mengonsolidasikan batas produk, tata kelola akses, prioritas, dan dependensi.                                                                                                                                                     |
| 1.0       | 15 Agustus 2026 | Menetapkan Koordinator sebagai penanggung jawab operasional; akses detail Waka pada kasus terkoordinasi; histori lintas guru; NISN sementara dan rekonsiliasi; laporan gabungan; akun Admin IT; serta retensi minimum tiga tahun. |
| 1.1       | 23 Agustus 2026; diamandemen 9 dan 12 September 2026 | Menetapkan konfigurasi koneksi aman melalui PG-501, fallback data persiapan, aktivasi sesuai tanggal mulai, rollover tanpa keputusan akademik otomatis, lima status pelayanan, satu penanggung jawab kasus, kode kasus internal, serta proyeksi aman seluruh kasus untuk Waka. |
