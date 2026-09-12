<!--
Canonical Markdown baseline for version 1.1.
Derived from SRS_Aplikasi_BK_v1.0 with approved integration decisions through 9 September 2026.
The version 1.0 Markdown and DOCX artifacts remain immutable archives.
-->

**SPESIFIKASI KEBUTUHAN PERANGKAT LUNAK (SRS)**

**Aplikasi BK**

Baseline spesifikasi MVP layanan Bimbingan dan Konseling

**Versi:** 1.1

**Status:** Baseline final untuk pengembangan MVP

**Tanggal:** 23 Agustus 2026

**Amandemen disetujui:** 9 September 2026

**Konteks:** Acuan produk: PRD Aplikasi BK v1.1

# Tujuan dan ruang lingkup

Dokumen ini menetapkan fungsi, batas akses, data, integrasi, keamanan, dan kriteria penerimaan Aplikasi BK. Spesifikasi mencakup MVP untuk Guru BK, Koordinator BK, Waka Kesiswaan, dan Admin IT. Wali kelas dan murid berada pada tahap P1.

Kebutuhan P0 wajib tersedia pada MVP. P0 bertahap tetap termasuk MVP, tetapi dikerjakan setelah fungsi inti stabil. Kata ‘harus’ menyatakan perilaku yang wajib dipenuhi.

# Batas sistem

| **Komponen**   | **Peran**                     | **Batas**                                                                                                                  |
|----------------|-------------------------------|----------------------------------------------------------------------------------------------------------------------------|
| Dapodik        | Sumber data master            | Identitas murid, kelas, keanggotaan kelas, dan tahun ajaran melalui mekanisme resmi.                                       |
| e-Tatib        | Sumber pelanggaran dan poin   | Dibaca melalui API; Aplikasi BK tidak membuat atau mengubah transaksi resmi.                                               |
| Aplikasi BK    | Ruang kerja layanan BK        | Kasus, penanganan, konsultasi minimum, tindak lanjut, koordinasi Waka, penyelesaian, prestasi minimum, laporan, dan audit. |
| Data sementara | Fallback sebelum sinkronisasi | NISN dan nama hanya saat kasus/layanan muncul; bukan master alternatif.                                                    |
| Konfigurasi integrasi | Fondasi koneksi teknis | Admin IT menyimpan endpoint, identitas sumber, credential, timeout, status verifikasi, dan versi secara aman; driver production belum tersedia sebelum admission kontrak. |
| Pengguna P1    | Wali kelas dan murid          | Struktur peran dapat disiapkan, tetapi antarmuka dan alurnya tidak dibangun pada P0.                                       |

# Hak akses dan tata kelola

| **Objek/tindakan**  | **Guru BK**                                           | **Koordinator BK**                                    | **Waka Kesiswaan**                                  | **Admin IT**                   |
|---------------------|-------------------------------------------------------|-------------------------------------------------------|-----------------------------------------------------|--------------------------------|
| Daftar/profil murid | Scope aktif dan kasus khusus; termasuk histori murid. | Sesuai scope Guru BK/penugasan dan fungsi koordinasi. | Daftar aman murid dengan kasus; detail bila terkait kasus terkoordinasi. | Master untuk tugas teknis.     |
| Kasus BK            | Buat, baca, ubah sebagai penanggung jawab aktif.      | Alihkan penanggung jawab; baca bila berwenang.        | Ringkasan aman seluruh kasus; detail hanya-baca untuk kasus terkoordinasi. | Tidak otomatis.                |
| Konsultasi sensitif | Baca bila scope murid aktif atau kasus khusus.        | Tidak otomatis di luar scope Guru BK.                 | Tidak otomatis; isi lengkap dikecualikan.           | Tidak.                         |
| Penugasan           | Lihat penugasannya.                                   | Buat/ubah berdasarkan keputusan resmi.                | Lihat ringkasan tata kelola.                        | Dukungan teknis.               |
| Koreksi operasional | Ajukan.                                               | Verifikasi.                                           | Ringkasan bila relevan.                             | Tidak memverifikasi isi.       |
| Koreksi master      | Koordinasi di luar aplikasi.                          | Koordinasi di luar aplikasi.                          | Tidak.                                              | Proses melalui sumber resmi.   |
| Laporan/cetak       | Sesuai scope sendiri.                                 | Gabungan seluruh Guru BK aktif.                       | Ringkasan penanganan seluruh kasus dari field aman. | Tidak otomatis.                |
| Akun/infrastruktur  | Lihat akun sendiri.                                   | Pantau operasional.                                   | Tidak mengelola.                                    | Kelola akun dan infrastruktur. |
| Konfigurasi koneksi | Tidak.                                                | Tidak.                                                | Tidak.                                              | Kelola melalui PG-501 tanpa membuka isi layanan BK. |

- Koordinator BK menjadi penanggung jawab operasional Aplikasi BK.

- Koordinator yang merangkap Guru BK tetap memperoleh akses sensitif hanya melalui scope Guru BK atau penugasan kasus.

- Waka memperoleh ringkasan aman seluruh kasus sekolah. Detail kasus hanya tersedia setelah koordinasi terhadap kasus tersebut tercatat; seluruh akses tetap hanya-baca.

- Guru BK yang memperoleh scope aktif atas murid dapat membaca histori layanan dan konsultasi sebelumnya sampai murid lulus, tetapi tidak mengubah catatan lama.

- Rolling atau perubahan pembagian dicatat Koordinator berdasarkan keputusan resmi; sistem tidak melakukan perubahan otomatis.

- Admin IT aktif mengelola koneksi Dapodik/e-Tatib melalui PG-501. Credential tidak pernah ditampilkan kembali dan setiap aksi konfigurasi memerlukan verifikasi kata sandi saat ini.

# Kebutuhan fungsional

## Autentikasi, otorisasi, dan tata kelola

| **ID**  | **Kebutuhan**                                                                                             | **Pri.** | **Kriteria penerimaan**                                                                                 |
|---------|-----------------------------------------------------------------------------------------------------------|----------|---------------------------------------------------------------------------------------------------------|
| AUTH-01 | Pengguna harus masuk dengan akun aktif sebelum mengakses data BK.                                         | P0       | Data operasional tidak tersedia tanpa sesi sah.                                                         |
| AUTH-02 | Guru BK hanya dapat mengakses murid dalam scope aktif dan kasus khusus yang ditugaskan.                   | P0       | Daftar, pencarian, detail, dashboard, laporan, ekspor, URL, dan API memakai batas yang sama.            |
| AUTH-03 | Server harus memeriksa kewenangan pada setiap objek, bagian data, dan tindakan sensitif.                  | P0       | Permintaan langsung di luar kewenangan ditolak tanpa membocorkan isi objek.                             |
| AUTH-04 | Isi konsultasi sensitif hanya dapat dibaca Guru BK yang memiliki scope murid aktif atau kewenangan kasus. | P0       | Jabatan Koordinator, Waka, atau Admin IT tidak otomatis membuka isi konsultasi.                         |
| AUTH-05 | Waka harus memiliki akses hanya-baca pada proyeksi aman seluruh kasus sekolah dan detail kasus yang dikoordinasikan kepadanya. | P0 | Proyeksi hanya memuat field yang diizinkan; server menolak detail kasus yang tidak dikoordinasikan dan menolak setiap perubahan oleh Waka. |
| AUTH-06 | Hak teknis Admin IT harus dipisahkan dari hak membaca layanan BK.                                         | P0       | Admin IT dapat mengelola akun, integrasi, master, dan rekonsiliasi tanpa membuka isi kasus.             |
| AUTH-07 | Akun dengan fungsi Koordinator sekaligus Guru BK harus menerapkan hak tiap fungsi secara terpisah.        | P0       | Fungsi Koordinator tidak memperluas akses konsultasi di luar scope Guru BK.                             |
| GOV-01  | Sistem harus mendukung Koordinator BK sebagai penanggung jawab operasional.                               | P0       | Menu operasional penugasan, verifikasi, koordinasi, dan rekap tersedia bagi Koordinator.                |

## Akun, data master, identitas sementara, dan penugasan

| **ID** | **Kebutuhan**                                                                                                         | **Pri.** | **Kriteria penerimaan**                                                                        |
|--------|-----------------------------------------------------------------------------------------------------------------------|----------|------------------------------------------------------------------------------------------------|
| ACC-01 | Admin IT harus dapat membuat, mengaktifkan, menonaktifkan, dan memulihkan akun sesuai penugasan resmi.                | P0       | Perubahan akun menyimpan pelaku, waktu, peran, dan status.                                     |
| ACC-02 | Pengelolaan akun tidak boleh memberikan akses isi layanan secara otomatis.                                            | P0       | Peran teknis dan kewenangan objek diperiksa terpisah.                                          |
| MD-01  | Data murid, kelas, keanggotaan kelas, dan tahun ajaran harus mengacu pada Dapodik.                                    | P0       | Aplikasi BK tidak menjadi sumber utama perubahan identitas/kelas.                              |
| MD-02  | Koreksi data master harus dikoordinasikan kepada Admin IT di luar aplikasi dan diproses melalui sumber resmi.         | P0       | Aplikasi tidak menyediakan pengajuan koreksi master baru; hasil sinkronisasi terbaru tetap tercatat. |
| MD-03  | Jika master belum tersinkron dan kasus/layanan harus dicatat, Guru BK harus dapat memasukkan NISN dan nama sementara. | P0       | Form tidak meminta data master lain dan memberi penanda sementara.                             |
| MD-04  | Identitas sementara harus direkonsiliasi menggunakan NISN tanpa membuat murid ganda.                                  | P0       | Nama resmi mengikuti sumber; kasus tetap terhubung; nilai awal dan hasil rekonsiliasi diaudit. |
| MD-05  | Admin IT harus dapat membuat tahun ajaran sebagai data persiapan sementara berdasarkan kalender pendidikan, SK, atau dasar resmi sekolah ketika Dapodik terlambat. | P0 | Tahun ajaran baru dibuat belum aktif, memiliki dasar persiapan, dan tidak ditampilkan sebagai data resmi Dapodik. |
| MD-06  | Admin IT harus dapat mengimpor daftar minimum CSV UTF-8 dengan header exact `nisn,nama,rombel` ke tahun ajaran yang belum aktif. | P0 | Seluruh berkas divalidasi sebelum diproses; hasil membuat atau memperbarui hanya baris yang disebutkan tanpa menyimpan berkas mentah atau menonaktifkan baris lain. |
| MD-07  | Asal data harus dipisahkan dari status aktivasi operasional dengan kode `school_provisional`, `dapodik`, atau `legacy_unclassified`. | P0 | `is_active` tidak diturunkan dari asal data dan tidak dapat diubah oleh provider atau sinkronisasi. |
| MD-08  | Koordinator BK harus dapat mengaktifkan tahun ajaran pada atau setelah tanggal mulai setelah rombel dan seluruh penugasan Guru BK lengkap. | P0 | Tahun lengkap sebelum tanggal mulai hanya berstatus siap; direct request ditolak. Aktivasi yang sah menutup tahun aktif sebelumnya tanpa menghapus histori atau mengubah asal data. |
| MD-09  | Guru BK harus memperoleh scope murid data persiapan sementara hanya setelah aktivasi operasional dan sesuai penugasan kelasnya. | P0 | Sebelum aktivasi akses ditolak; setelah aktivasi layanan BK dapat digunakan dan seluruh tampilan terkait memberi penanda sementara yang jelas. |
| MD-10  | Tarik data Dapodik harus menghasilkan pratinjau pencocokan dan memerlukan konfirmasi Admin IT sebelum diterapkan. | P0 | Pratinjau menunjukkan data cocok, baru, berubah, dan konflik tanpa mengubah cache operasional; perubahan hanya terjadi pada aksi penerapan. |
| MD-11  | Pencocokan murid otomatis harus memakai NISN exact; nama tidak boleh menjadi kunci identitas. | P0 | Konflik NISN, tahun ajaran, rombel, atau kepemilikan source ID ditahan; pasangan tahun/rombel hanya dicocokkan otomatis bila unik dan keputusan meragukan diperiksa Admin IT. |
| MD-12  | Penerapan Dapodik harus mempertahankan ID internal dan seluruh relasi serta histori BK yang sudah ada. | P0 | Identitas sumber dan field resmi ditempelkan pada baris yang sama, asal menjadi `dapodik`, nilai lama/baru diaudit, dan tidak ada write-back ke Dapodik/e-Tatib. |
| MD-13  | Tahun ajaran baru harus dibentuk dari daftar penempatan resmi tanpa kenaikan kelas atau keputusan akademik otomatis. | P0 | Impor NISN exact membuat histori keanggotaan target; murid tahun sebelumnya tanpa penempatan target muncul sebagai **Perlu Konfirmasi** secara read-only dan tidak menghalangi aktivasi murid lain. |
| MD-14  | Data master harus memuat seluruh murid aktif, bukan hanya murid yang pernah menerima pelayanan BK.                  | P0 | Data minimum memuat NISN, nama, kelas, tahun ajaran, dan periode keanggotaan; catatan pelayanan baru dibuat hanya ketika layanan terjadi. |
| ASN-01 | Koordinator harus dapat menetapkan Guru BK untuk kelas dan tahun ajaran tertentu.                                     | P0       | Penugasan menyimpan periode efektif dan dasar keputusan.                                       |
| ASN-02 | Penugasan baru tidak boleh menimpa riwayat lama.                                                                      | P0       | Riwayat penanggung jawab tetap dapat ditelusuri.                                               |
| ASN-03 | Koordinator harus dapat mengubah penugasan di tengah tahun.                                                           | P0       | Perubahan memiliki tanggal efektif dan audit; kasus aktif tidak berpindah otomatis.            |
| ASN-04 | Koordinator harus dapat menetapkan penanggung jawab pada kasus khusus di luar scope kelas.                            | P0       | Kasus hanya mempunyai satu penanggung jawab aktif; penetapan menyimpan alasan dan histori.     |
| ASN-05 | Pengalihan kasus aktif harus dilakukan secara eksplisit.                                                              | P0       | Simpan penanggung jawab lama, penerima, alasan, waktu berlaku, dan audit.                      |
| ASN-06 | Rolling atau perubahan pembagian dua tahunan tidak boleh dijalankan otomatis.                                         | P0       | Sistem hanya mencatat keputusan resmi yang dimasukkan Koordinator.                             |
| REF-01 | Nilai referensi layanan harus dapat dikelola tanpa mengubah kode.                                                     | P0       | Bidang layanan, jenis tindak lanjut, dan status disimpan sebagai data referensi.               |

## Kasus, koordinasi, dan tindak lanjut

| **ID**  | **Kebutuhan**                                                                                                     | **Pri.** | **Kriteria penerimaan**                                                                                                          |
|---------|-------------------------------------------------------------------------------------------------------------------|----------|----------------------------------------------------------------------------------------------------------------------------------|
| CASE-01 | Guru BK harus dapat membuat kasus untuk murid dalam kewenangannya atau identitas sementara yang sah.              | P0       | Server menolak objek di luar kewenangan dan menandai identitas sementara.                                                        |
| CASE-02 | Sumber kasus harus dapat dipilih dari e-Tatib, murid datang sendiri, temuan Guru BK, atau rujukan.                | P0       | Sumber tersimpan dan tampil pada detail.                                                                                         |
| CASE-03 | Kasus harus memuat informasi awal, penanganan awal, riwayat layanan, tindak lanjut, koordinasi, dan penyelesaian. | P0       | Aktivitas dapat dibaca kronologis sesuai kewenangan.                                                                             |
| CASE-04 | Status kasus harus menggunakan Baru dicatat, Sedang diproses, Membutuhkan tindak lanjut, Selesai, atau Dibatalkan. | P0       | Kode status konsisten dengan konsultasi dan perubahan status tercatat pada audit.                                                  |
| CASE-05 | Guru BK harus dapat menjadwalkan dan mencatat hasil tindak lanjut.                                                | P0       | Jadwal berikutnya tampil pada dashboard pengguna berwenang.                                                                      |
| CASE-06 | Guru BK harus dapat menyelesaikan kasus dengan hasil akhir.                                                       | P0       | Kasus selesai tetap tersedia pada histori murid.                                                                                 |
| CASE-07 | Kasus terkait pelanggaran harus memakai data e-Tatib sebagai referensi resmi.                                     | P0       | Tidak ada transaksi atau pengetikan ulang data yang tersedia.                                                                    |
| CASE-08 | Kasus harus dapat memuat bidang layanan BK dari data referensi.                                                   | P0       | Perubahan nilai tidak memerlukan perubahan kode.                                                                                 |
| CASE-09 | Jenis tindak lanjut harus disimpan terpisah dari status pelaksanaannya.                                           | P0       | Keduanya dapat difilter terpisah.                                                                                                |
| CASE-10 | Sistem harus membedakan waktu pencatatan, tanggal layanan, tanggal rencana, dan tanggal pelaksanaan.              | P0       | Setiap tanggal tersimpan terpisah.                                                                                               |
| CASE-11 | Catatan internal harus opsional dan hanya dapat dibaca pengguna berwenang.                                        | P0       | Tidak tampil pada laporan umum atau Waka secara otomatis.                                                                        |
| CASE-12 | Kasus yang dikoordinasikan kepada Waka harus memiliki ringkasan hasil koordinasi.                                | P0       | Koordinasi dilakukan di luar aplikasi; catatan memuat tanggal, pihak, ringkasan hasil, dan tindak lanjut yang disepakati serta membuka detail hanya-baca untuk Waka tujuan. |
| CASE-13 | Hanya Guru BK penanggung jawab aktif yang dapat mengubah kasus yang belum terminal.                               | P0       | Policy dan service menolak Guru lain, termasuk pemegang akses tambahan lama; Koordinator hanya mengatur penugasan.               |
| CASE-14 | Kasus selesai atau dibatalkan harus dikunci dari perubahan biasa.                                                | P0       | Perubahan setelah terminal hanya dapat diterapkan melalui koreksi operasional yang diverifikasi.                                  |
| CASE-15 | Kode kasus harus dipertahankan sebagai identitas internal dan disembunyikan dari keluaran pengguna.              | P0       | Kode tetap unik di database tetapi tidak muncul pada UI, pencarian pengguna, laporan, ekspor, dashboard, notifikasi, atau audit yang ditampilkan. |

## Integrasi, konsultasi, profil, dan prestasi

| **ID**  | **Kebutuhan**                                                                                                                     | **Pri.**    | **Kriteria penerimaan**                                                                         |
|---------|-----------------------------------------------------------------------------------------------------------------------------------|-------------|-------------------------------------------------------------------------------------------------|
| INT-01  | e-Tatib harus diperlakukan sebagai sistem eksternal.                                                                              | P0          | e-Tatib tidak menjadi modul internal atau navigasi utama.                                       |
| INT-02  | Kasus harus dapat ditautkan ke data e-Tatib yang relevan.                                                                         | P0          | Referensi tampil tanpa pencatatan ulang.                                                        |
| INT-03  | Aplikasi BK harus membaca e-Tatib melalui API resmi dengan NISN sebagai pemetaan utama.                                           | P0          | Autentikasi, field, frekuensi, dan prosedur gangguan ditetapkan sebelum integrasi diaktifkan.   |
| INT-04  | MVP tidak boleh mengirim perubahan ke e-Tatib.                                                                                    | P0          | Tidak tersedia fungsi write-back.                                                               |
| INT-05  | Admin IT dapat mengelola konfigurasi endpoint dan credential Dapodik/e-Tatib tanpa mengubah kode atau file environment.          | P0          | Admin IT aktif dapat menyimpan base URL, expected source identifier, token, dan timeout melalui PG-501; secret tidak pernah ditampilkan kembali. |
| INT-06  | Browser hanya mengirim konfigurasi dan trigger kepada backend; komunikasi dengan sistem eksternal dilakukan server-to-server.   | P0          | Tidak ada request browser langsung ke provider; fetch, mapping, validasi, dan penyimpanan hanya dilakukan backend. |
| INT-07  | Konfigurasi mengikuti state Simpan, Uji, dan Aktif; perubahan material membatalkan verifikasi dan menonaktifkan koneksi.          | P0          | Perubahan URL, identitas sumber, credential, timeout, driver, contract, adapter, atau policy menaikkan versi yang relevan dan memblokir penggunaan sampai diuji ulang; simpan tanpa perubahan tidak mengubah state. |
| INT-08  | Adapter provider memetakan kontrak eksternal resmi ke snapshot internal; field yang tidak dibutuhkan diabaikan dan raw payload tidak disimpan secara default. | P0 | Snapshot hanya berisi field domain dan evidence aman; `driver_id`, `adapter_version`, serta `contract_version` dicatat terpisah. |
| INT-09  | Payload yang tidak valid, tidak lengkap, atau tidak terbukti penuh harus ditolak sebelum import dan tidak boleh mengubah data lama. | P0 | Validator executable memeriksa schema, tipe, nullability, identitas, evidence completeness, pagination, jumlah record, dan byte sebelum transaksi import. |
| INT-10  | Driver production tidak boleh diaktifkan sebelum autentikasi, endpoint, schema, pagination, semantik full/partial, fixture sintetis, dan prosedur gangguan provider disahkan. | P0 | Registry hanya menyediakan driver `unavailable` sampai admission kontrak juga mengesahkan deletion semantics, timezone, limits, resilience, TLS/proxy/jaringan, dan mapping snapshot. |
| INT-11  | Uji koneksi baru dinyatakan sukses bila autentikasi, versi kontrak, schema minimum, dan identitas sumber/sekolah cocok dengan nilai yang diharapkan; status HTTP sukses saja tidak mencukupi. | P0 | Probe gagal tertutup pada identitas berbeda, schema/contract tidak kompatibel, konfigurasi tidak lengkap, credential tak terbaca, atau endpoint policy berubah. |
| CONS-01 | Sistem harus menyimpan metadata konsultasi dan ringkasan umum yang diizinkan.                                                     | P0          | Tanggal, jenis, status, Guru BK, jadwal, ringkasan umum, dan dokumen yang diizinkan tersedia.   |
| CONS-02 | Isi konsultasi sensitif tidak boleh masuk laporan umum atau detail Waka secara otomatis.                                          | P0          | Tampilan dan ekspor mengecualikan isi lengkap konsultasi.                                       |
| CONS-03 | Konsultasi harus memakai lima status pelayanan yang sama dan hanya dapat diubah pencatatnya selama belum terminal.                | P0          | Status selesai/dibatalkan menolak edit biasa; koreksi terverifikasi tetap tersedia.            |
| STU-01  | Profil murid harus menggabungkan informasi operasional yang berhak diakses pengguna.                                              | P0          | e-Tatib, kasus, layanan, tindak lanjut, konsultasi, dan prestasi tersedia sesuai kewenangan.    |
| STU-02  | Guru BK dengan scope aktif harus dapat membaca histori layanan/konsultasi murid lintas kelas dan pergantian Guru BK sampai lulus. | P0          | Histori lama terbaca tetapi tidak dapat diubah oleh Guru BK penerus.                            |
| ACH-01  | Sistem harus mendukung pencatatan prestasi setelah fungsi inti stabil.                                                            | P0 bertahap | Prestasi terhubung ke profil dan mengikuti akses.                                               |
| ACH-02  | Data prestasi harus memuat informasi minimum yang disahkan sekolah.                                                               | P0 bertahap | Jenis, tingkat, kegiatan, penyelenggara, tanggal, hasil, bukti, dan status verifikasi tersedia. |

## Dashboard, laporan, notifikasi, audit, dan koreksi

| **ID**  | **Kebutuhan**                                                                                                     | **Pri.** | **Kriteria penerimaan**                                                                          |
|---------|-------------------------------------------------------------------------------------------------------------------|----------|--------------------------------------------------------------------------------------------------|
| DASH-01 | Dashboard harus mengikuti peran, scope murid, penugasan kasus, dan koordinasi Waka.                               | P0       | Hitungan dan tautan tidak memuat data di luar kewenangan.                                        |
| DASH-02 | Dashboard Guru BK harus menampilkan ringkasan operasional utama.                                                  | P0       | Jumlah murid, pelanggaran, kasus, jadwal, dan aktivitas relevan tersedia.                        |
| DASH-03 | Dashboard Waka harus menampilkan ringkasan aman seluruh kasus sekolah.                                             | P0       | Ringkasan tidak memuat field sensitif; tautan detail hanya tersedia untuk kasus yang dikoordinasikan dan tidak menyediakan aksi ubah. |
| REP-01  | Laporan harus menyediakan filter sesuai jenis data dan kewenangan.                                                | P0       | Parameter menghasilkan data konsisten.                                                           |
| REP-02  | Laporan harus mencakup pelanggaran, poin, konsultasi, tindak lanjut, prestasi minimum, dan rekap layanan.         | P0       | Rekap terbentuk tanpa penggabungan file manual.                                                  |
| REP-03  | Cetak dan ekspor harus mengikuti batas akses dan kerahasiaan.                                                     | P0       | Hasil tidak memuat data di luar kewenangan atau field terlarang.                                 |
| REP-04  | Koordinator harus dapat membuat rekap/cetak gabungan seluruh Guru BK aktif, sedangkan Guru BK hanya sesuai scope. | P0       | Saat baseline terdapat tujuh Guru BK; jumlah pengguna dihitung dinamis.                          |
| REP-05  | Waka harus memiliki satu halaman laporan penanganan seluruh kasus dengan filter sederhana.                        | P0       | Filter hanya periode dan status; sorting header memakai allowlist server; keluaran mengecualikan NISN, kode kasus, konsultasi sensitif, catatan internal, dan dokumen. |
| NOT-01  | Sistem harus menampilkan pemberitahuan operasional yang terkait pengguna.                                         | P0       | Jadwal, penugasan, koordinasi, koreksi, dan perubahan penting hanya dikirim kepada pihak berhak. |
| AUD-01  | Perubahan penting harus menghasilkan jejak audit otomatis.                                                        | P0       | Audit memuat pelaku, tindakan, objek, waktu, dan ringkasan perubahan.                            |
| COR-01  | Koreksi data operasional harus diverifikasi Koordinator BK.                                                       | P0       | Simpan pengaju, pemeriksa, alasan, nilai lama/usulan, waktu, dan hasil.                          |
| COR-02  | Kesalahan data master harus ditangani Admin IT melalui sumber resmi.                                              | P0       | Aplikasi tidak mengubah Dapodik secara sepihak dan menampilkan hasil sinkronisasi.               |

# Kebutuhan data

## Field minimum

| **Objek**           | **Field minimum**                                                                                                                                          | **Aturan**                                                   |
|---------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------|
| Kasus               | Kode internal, murid/identitas sementara, sumber, tanggal layanan, bidang, informasi awal, penanganan awal, Guru BK, status, waktu dibuat, catatan internal, hasil akhir. | Kode tidak masuk keluaran pengguna; objek harus berada dalam kewenangan; waktu dibuat otomatis. |
| Koordinasi Waka     | Kasus, kebutuhan koordinasi, pencatat, waktu, Waka tujuan, ringkasan hasil, dan tindak lanjut yang disepakati.                                              | Membuka detail hanya-baca untuk kasus target.                |
| Tindak lanjut       | Kasus, tanggal rencana, tanggal pelaksanaan, jenis, status, hasil, rencana berikutnya, pencatat.                                                           | Jenis dan status terpisah.                                   |
| Konsultasi          | Murid, kasus bila relevan, tanggal, jenis, status, Guru BK, jadwal, ringkasan umum, dokumen diizinkan.                                                     | Isi sensitif dipisahkan dan dibatasi.                        |
| Identitas sementara | NISN, nama masukan, pembuat, waktu, kasus/layanan, status rekonsiliasi.                                                                                    | Hanya ketika master belum tersedia; bukan master alternatif. |
| Rekonsiliasi        | NISN sumber, murid master, nama resmi, status, hasil, pemeriksa, waktu, konflik.                                                                           | Tidak membuat murid ganda; nilai lama tetap diaudit.         |
| Referensi e-Tatib   | Identitas sumber, NISN, tanggal/waktu kejadian, jenis, kategori, poin, waktu sinkronisasi.                                                                 | Hanya-baca.                                                  |
| Prestasi            | Murid, jenis, tingkat, kegiatan, penyelenggara, tanggal, hasil, bukti, status verifikasi.                                                                  | P0 bertahap.                                                 |
| Koreksi             | Objek, field, nilai lama, nilai usulan, alasan, pengaju, pemeriksa, status, waktu.                                                                         | Data master diperbaiki pada sumber resmi.                    |
| Akun                | Identitas pengguna, peran, status aktif, waktu perubahan, pengubah.                                                                                        | Hak teknis dan kewenangan objek dipisahkan.                  |
| Konfigurasi integrasi | Provider, base URL, expected source identifier, credential terenkripsi, timeout, state, configuration version, operation fence version, driver ID, adapter version, contract version, endpoint-policy digest, penguji, dan waktu uji. | Secret tidak keluar dari backend; perubahan material membatalkan verifikasi. |
| Evidence snapshot   | Identitas sumber terlapor, marker/provenance kontrak, jumlah page, jumlah record, dan jumlah byte terproses.                                               | Tidak memuat raw payload; divalidasi sebelum import.         |
| Data persiapan sementara | Tahun ajaran, dasar resmi sekolah, NISN, nama, rombel, asal data, status aktif, pembuat, dan waktu persiapan. | Bukan data resmi Dapodik; status asal dan aktivasi operasional disimpan terpisah. |
| Pratinjau pencocokan | Run sumber, hasil cocok/baru/berubah/konflik, kandidat ID internal, keputusan Admin IT, nilai aman lama/baru, dan waktu keputusan. | Tidak mengubah cache operasional sebelum penerapan dikonfirmasi. |

## Entitas konseptual

| **Entitas**               | **Fungsi**                                                                   |
|---------------------------|------------------------------------------------------------------------------|
| Pengguna dan Peran        | Identitas akun, fungsi pengguna, dan status aktif.                           |
| Tahun Ajaran              | Konteks kelas, penugasan, histori, dan laporan.                              |
| Master Dapodik            | Salinan terkontrol data murid, kelas, keanggotaan, dan tahun ajaran.         |
| Identitas Murid Sementara | NISN dan nama untuk kasus sebelum master tersedia.                           |
| Rekonsiliasi Identitas    | Pencocokan NISN sementara ke master tanpa duplikasi.                         |
| Penugasan Guru BK         | Hubungan Guru BK, kelas, tahun ajaran, periode, dan dasar keputusan.         |
| Murid                     | Referensi profil, kasus, layanan, konsultasi, dan prestasi.                  |
| Data e-Tatib              | Referensi eksternal pelanggaran dan poin resmi.                              |
| Kasus                     | Wadah penanganan dari informasi awal sampai penyelesaian.                    |
| Koordinasi Kasus          | Ringkasan hasil koordinasi di luar aplikasi yang memberi Waka akses detail hanya-baca. |
| Penugasan Kasus           | Satu pemilik aktif, pengalihan eksplisit, dan riwayat penanggung jawab.      |
| Tindak Lanjut             | Jadwal, kegiatan, status, hasil, dan rencana berikutnya.                     |
| Konsultasi                | Metadata, ringkasan umum, isi sensitif, dan batas akses.                     |
| Prestasi                  | Riwayat prestasi, bukti yang diizinkan, dan verifikasi.                      |
| Koreksi Data              | Pengajuan, pemeriksaan, hasil, dan hubungan dengan sumber resmi.             |
| Jejak Audit               | Catatan perubahan penting yang dibuat sistem.                                |
| Data Referensi            | Bidang layanan, jenis tindak lanjut, dan status operasional.                 |
| Konfigurasi Integrasi     | State dan versi koneksi Dapodik/e-Tatib beserta credential terenkripsi.      |
| Evidence Snapshot         | Bukti aman identitas, kontrak, pagination, jumlah record, dan ukuran hasil.  |
| Data Persiapan Sementara  | Data minimum berdasarkan daftar resmi sekolah untuk menjaga layanan selama Dapodik terlambat.  |
| Pratinjau Pencocokan      | Hasil pemeriksaan Dapodik sebelum perubahan diterapkan pada cache operasional. |

## Status dan perubahan

- Status kasus dan konsultasi terdiri atas `baru` (**Baru dicatat**), `sedang_diproses` (**Sedang diproses**), `membutuhkan_tindak_lanjut` (**Membutuhkan tindak lanjut**), `selesai` (**Selesai**), dan `dibatalkan` (**Dibatalkan**).

- `selesai` dan `dibatalkan` adalah status terminal. Catatan terminal hanya dapat diperbaiki melalui koreksi operasional yang diverifikasi.

- Jadwal tindak lanjut disimpan pada riwayat kegiatan dan bukan status kasus.

- Penugasan kelas dan kasus menyimpan periode berlaku; riwayat lama tidak ditimpa.

- Koordinasi Waka memiliki status tersendiri dan tidak mengubah status kasus.

- Identitas sementara memiliki status rekonsiliasi dan tidak menjadi master murid baru.

- Status asal data memakai `school_provisional`, `dapodik`, atau `legacy_unclassified` dan tidak menentukan status aktif tahun ajaran.

- Aktivasi operasional tahun ajaran hanya diputuskan Koordinator BK setelah data dan penugasan lengkap serta tanggal mulai telah tiba. Provider dan proses sinkronisasi tidak dapat mengubah `is_active`.

- Murid aktif pada tahun sebelumnya yang belum mempunyai keanggotaan pada tahun target ditampilkan sebagai **Perlu Konfirmasi**. Penanda dihitung saat dibaca, tidak menentukan status akademik, dan tidak memblokir aktivasi keseluruhan.

- Data persiapan sementara yang cocok menjadi terverifikasi Dapodik dengan menempelkan identitas sumber pada ID internal yang sama; relasi dan histori BK tidak dibuat ulang.

- Nilai status kasus dan konsultasi memakai kontrak lima kode yang sama. Status tindak lanjut, koreksi, dan verifikasi prestasi tetap sebagai data referensi terpisah.

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

- Kode kasus pada UI, pencarian pengguna, laporan, ekspor, dashboard, notifikasi, atau audit yang ditampilkan kepada pengguna.

Portal Waka memakai proyeksi aman seluruh kasus yang hanya memuat nama murid, kelas historis, bidang layanan, status, Guru BK penanggung jawab, tanggal pelayanan, ringkasan tindakan yang disahkan, tindak lanjut berikutnya, dan hasil akhir. Detail kasus yang dikoordinasikan kepada Waka bukan laporan umum; aksesnya tetap hanya-baca, dibatasi pada kasus target, dicatat pada audit, dan tidak membuka isi lengkap konsultasi sensitif atau dokumen asli.

## Aturan keamanan

| **Prinsip**                    | **Penerapan minimum**                                                                         |
|--------------------------------|-----------------------------------------------------------------------------------------------|
| Hak minimum                    | Pengguna hanya memperoleh hak yang diperlukan untuk tugasnya.                                 |
| Otorisasi per objek dan bagian | Server memeriksa murid, kasus, konsultasi, dokumen, koordinasi, dan aksi yang diminta.        |
| Batas konsisten                | Pencarian, daftar, detail, dashboard, laporan, ekspor, URL, dan API memakai aturan yang sama. |
| Pemisahan data sensitif        | Ringkasan, detail kasus, catatan internal, dan konsultasi sensitif dipisahkan.                |
| Pemisahan tugas                | Hak teknis, hak koordinasi, dan scope Guru BK tidak saling memperluas otomatis.               |
| Audit otomatis                 | Perubahan penting dibuat sistem dan tidak dapat diubah pengguna biasa.                        |
| Secret-safe workflow           | Credential dienkripsi, tidak masuk HTML/JSON/session/audit/log/exception, tidak memakai old input, dan hanya dapat diganti atau dihapus secara eksplisit setelah step-up Admin IT. |
| Endpoint fail-closed           | Origin cocok exact dengan allowlist deployment; metadata/link-local/multicast/unspecified selalu ditolak; private/loopback hanya diizinkan bila origin exact terdaftar dan flag opt-in private-network provider aktif; redirect, user-info, query/fragment, origin drift, DNS campuran/berubah, proxy tak tepercaya, dan TLS invalid ditolak. |
| Koneksi terikat                | Setiap resolusi alamat divalidasi tepat sebelum koneksi dan transport diikat ke alamat tersebut dengan Host/SNI tetap sesuai origin. |
| Import atomik                  | Snapshot dan evidence divalidasi sebelum transaksi; kegagalan atau operasi stale mempertahankan data lama. |

# Kebutuhan nonfungsional

| **ID** | **Kebutuhan**                                                                                                      | **Pri.** | **Kriteria penerimaan**                                                                    |
|--------|--------------------------------------------------------------------------------------------------------------------|----------|--------------------------------------------------------------------------------------------|
| NFR-01 | Setiap akses data operasional memerlukan autentikasi dan otorisasi pada server.                                    | P0       | Permintaan tanpa sesi dan di luar kewenangan ditolak.                                      |
| NFR-02 | Informasi sensitif harus dipisahkan dari ringkasan, detail terkoordinasi, dan laporan umum.                        | P0       | Uji tampilan/ekspor tidak memuat field terlarang.                                          |
| NFR-03 | Jejak audit harus memuat pelaku, tindakan, objek, waktu, dan ringkasan perubahan.                                  | P0       | Perubahan penting menghasilkan audit otomatis.                                             |
| NFR-04 | Alur utama harus dapat digunakan pada telepon genggam, tablet, laptop, dan komputer sekolah.                       | P0       | Login, pencarian, kasus, tindak lanjut, dan laporan dapat diselesaikan pada perangkat uji. |
| NFR-05 | Form utama harus menghindari pengisian ulang data e-Tatib dan Dapodik.                                             | P0       | Data sumber ditampilkan sebagai referensi; fallback hanya NISN+nama.                       |
| NFR-06 | Periode berlaku dan histori harus dipertahankan ketika penugasan atau kelas berubah.                               | P0       | Data lama dan histori layanan tetap tersedia.                                              |
| NFR-07 | Nilai referensi layanan tidak boleh ditanam langsung dalam kode.                                                   | P0       | Nilai dapat diperbarui peran berwenang tanpa perubahan program.                            |
| NFR-08 | Data operasional dan audit harus disimpan minimum tiga tahun dan tidak dihapus otomatis sebelum prosedur disahkan. | P0       | Tidak ada penghapusan terjadwal sebelum batas minimum dan kebijakan operasional terpenuhi. |
| NFR-09 | Credential dienkripsi menggunakan encrypter Laravel dan tidak boleh tampil pada response, session, audit, atau log. | P0 | HTML/JSON, flash/old input, exception, dan audit hanya membawa status keberadaan/perubahan credential; seluruh aksi PG-501 memakai step-up dan `Cache-Control: no-store`. |
| NFR-10 | Endpoint outbound mengikuti exact deployment allowlist, redirect dimatikan, operasi per provider diserialisasi, dan kegagalan mempertahankan data lama. | P0 | Save, test, activate, dan sync memvalidasi policy; policy digest mencakup versi policy, exact origins yang dikanonisasi, dan flag private-network provider lalu diverifikasi ulang; lock serta fencing mencegah duplicate/stale write. |
| NFR-11 | Credential provider harus least-privilege/read-only. Endpoint policy memvalidasi resolusi alamat untuk setiap koneksi dan menolak metadata/link-local, origin drift, proxy tak tepercaya, serta TLS invalid secara fail-closed. | P0 | Admission menyertakan bukti scope read-only; metadata/link-local/multicast/unspecified selalu ditolak, private/loopback hanya diterima jika origin cocok exact dan flag opt-in private-network provider aktif, seluruh hasil DNS diperiksa, jawaban campuran/berubah ditolak, serta koneksi diikat ke alamat tervalidasi dengan Host/SNI yang benar. |
| NFR-12 | Adapter production menerapkan batas payload/page, pagination, timeout, retry/backoff, rate limit, concurrency, dan backpressure yang disahkan dalam kontrak provider. | P0 | Kontrak menetapkan angka/batas dan kebutuhan queue; worst-case operation harus muat di hard deadline di bawah lease atau adapter tidak di-admit. |
| NFR-13 | Impor data persiapan dan penerapan hasil Dapodik harus divalidasi penuh sebelum mutasi dan diproses secara atomik. | P0 | CSV wajib UTF-8 maksimum 2 MiB, header exact `nisn,nama,rombel`, NISN 10 digit unik per berkas, nama dan rombel wajib, formula/control character ditolak, dan maksimum 5.000 baris; satu kesalahan, konflik, atau kegagalan membatalkan seluruh perubahan. |

# Integrasi

## e-Tatib

| **Aspek**              | **Ketentuan**                                                                                                |
|------------------------|--------------------------------------------------------------------------------------------------------------|
| Arah data              | e-Tatib ke Aplikasi BK melalui API untuk baca dan penautan.                                                  |
| Pemetaan               | NISN menjadi identitas utama untuk mencocokkan murid.                                                        |
| Data minimum           | Identitas sumber, NISN, waktu kejadian, jenis, kategori, poin, status bila tersedia, dan waktu sinkronisasi. |
| Perubahan balik        | Tidak tersedia pada MVP.                                                                                     |
| Kegagalan sinkronisasi | Tampilkan status gangguan dan waktu data terakhir; data lama tidak dianggap terbaru.                         |
| Kegagalan pemetaan     | Data gagal tidak menimpa data sah dan dicatat untuk Admin IT.                                                |
| Credential sumber      | Akun/token wajib least-privilege dan read-only dengan bukti scope yang disahkan.                             |
| Uji koneksi            | Autentikasi, contract/schema minimum, dan identitas sekolah/sumber harus terbukti; HTTP 2xx saja tidak cukup. |
| Snapshot               | Adapter memetakan field resmi ke snapshot internal beserta evidence; raw payload tidak disimpan secara default. |
| Driver production      | Tetap `unavailable` sampai lembar discovery dan admission kontrak e-Tatib disahkan.                          |

## Dapodik dan identitas sementara

| **Aspek**    | **Ketentuan**                                                                               |
|--------------|---------------------------------------------------------------------------------------------|
| Arah data    | Dapodik atau hasil ekspor resmi ke Aplikasi BK.                                             |
| Data minimum | Identitas murid, kelas, keanggotaan kelas, dan tahun ajaran.                                |
| Fallback     | Bila Dapodik terlambat 2–3 bulan, Admin IT menyiapkan tahun ajaran dan CSV minimum seluruh murid aktif berupa `nisn,nama,rombel` berdasarkan dasar resmi sekolah; Koordinator mengaktifkan setelah data/penugasan lengkap dan tanggal mulai tiba. |
| Status asal  | `school_provisional`, `dapodik`, dan `legacy_unclassified` terpisah dari `is_active`; provider tidak dapat melakukan aktivasi operasional. |
| Scope Guru BK | Data persiapan dapat dipakai untuk layanan hanya setelah tahun ajaran aktif dan sesuai penugasan; seluruh tampilan memberi penanda sementara. |
| Pratinjau    | Tarik Dapodik menghasilkan pratinjau cocok, baru, berubah, dan konflik tanpa mengubah cache; Admin IT memeriksa serta mengonfirmasi sebelum penerapan. |
| Rekonsiliasi | Cocokkan murid hanya berdasarkan NISN exact, tahan konflik, tempelkan identitas sumber pada ID internal yang sama, gunakan field resmi, dan cegah duplikasi. |
| Konflik      | NISN tidak ditemukan atau ganda ditahan untuk pemeriksaan Admin IT; data sah tidak ditimpa. |
| Koreksi      | Perubahan identitas dilakukan pada sumber resmi lalu disinkronkan kembali.                  |
| Riwayat      | Perubahan kelas/tahun ajaran tidak menghapus kasus, layanan, atau konsultasi sebelumnya.    |
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
| Waka memantau seluruh kasus secara aman dan membaca detail terkoordinasi | AUTH-05; CASE-12; DASH-03; REP-05 | P0 |
| Histori mengikuti scope murid            | AUTH-02; AUTH-04; STU-02               | P0          |
| NISN+nama sebelum sinkronisasi           | MD-03; MD-04; CASE-01; NFR-05          | P0          |
| Dapodik terlambat dan data persiapan      | MD-05 s.d. MD-09; NFR-13               | P0          |
| Pratinjau dan penerapan Dapodik           | MD-10 s.d. MD-12; NFR-13               | P0          |
| Rolling tidak otomatis                   | ASN-03; ASN-06                         | P0          |
| Status pelayanan konsisten dan catatan terminal terkunci | CASE-04; CASE-13; CASE-14; CONS-03 | P0 |
| Kode kasus hanya untuk kebutuhan internal | CASE-15; REP-03; AUD-01                | P0          |
| Admin IT mengelola akun/infrastruktur    | ACC-01; ACC-02; AUTH-06                | P0          |
| Konfigurasi koneksi aman melalui PG-501  | INT-05 s.d. INT-07; NFR-09             | P0          |
| Admission dan verifikasi sumber          | INT-08 s.d. INT-11                     | P0          |
| Endpoint, DNS, dan konsistensi operasi    | NFR-10; NFR-11                         | P0          |
| Batas adapter dan backpressure            | NFR-12                                 | P0          |
| Retensi minimum tiga tahun               | NFR-08                                 | P0          |
| Prestasi                                 | ACH-01; ACH-02; REP-02                 | P0 bertahap |
| Wali kelas dan murid                     | Struktur peran P1                      | P1          |

# Ketergantungan yang belum dikunci

| **ID** | **Area**           | **Kondisi**                                                                            | **Dampak**                                        |
|--------|--------------------|----------------------------------------------------------------------------------------|---------------------------------------------------|
| DEP-01 | e-Tatib            | Fondasi konfigurasi boleh tersedia, tetapi autentikasi, origin/endpoint, identitas sumber, schema, pagination, full/partial dan deletion semantics, fixture sintetis, limits, resilience, TLS/proxy/jaringan, serta prosedur gangguan belum disahkan. | Uji, aktivasi, dan sinkronisasi production tetap diblokir; driver `unavailable`. |
| DEP-02 | Dapodik            | Fondasi konfigurasi boleh tersedia, tetapi mekanisme resmi, autentikasi, origin/endpoint, identitas sekolah, schema, pagination, completeness, fixture sintetis, limits, resilience, TLS/proxy/jaringan, dan konflik NISN belum disahkan. | Uji, aktivasi, dan sinkronisasi production tetap diblokir; driver `unavailable`. |
| DEP-03 | Istilah layanan    | Nama, kardinalitas, kewajiban, dan pemilik data referensi selain lima status inti kasus/konsultasi. | Label form/filter lain dapat berubah.             |
| DEP-04 | Status operasional | Status tindak lanjut, koreksi, dan verifikasi prestasi.                                | Pilihan status tersebut tidak boleh ditanam dalam kode. |
| DEP-05 | Prestasi           | Verifikator, bukti, dan status.                                                        | Modul tetap P0 bertahap.                          |
| DEP-06 | Dokumen/retensi    | Format, ukuran, akses, pemulihan, dan prosedur penghapusan setelah minimum tiga tahun. | Unggah dan penghapusan belum dikunci.             |
| DEP-07 | Ekspor             | Format cetak/ekspor dan kebutuhan penandaan/audit khusus.                              | Luaran laporan belum dapat dikunci seluruhnya.    |

# Sumber dan riwayat versi

Acuan: PRD Aplikasi BK v1.1, kuesioner kebutuhan, contoh pencatatan berjalan, diskusi perancangan, inventaris antarmuka, keputusan validasi Koordinator BK/Guru BK serta Waka Kesiswaan sampai 13 Agustus 2026, keputusan arsitektur fondasi konfigurasi integrasi tanggal 23 Agustus 2026, amandemen keterlambatan Dapodik yang disetujui 9 September 2026, serta keputusan alur operasional tahun ajaran, pelayanan BK, dan pemantauan Waka yang disetujui 12 September 2026.

| **Versi** | **Tanggal**     | **Perubahan**                                                                                                                                                                                                                  |
|-----------|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 0.3       | 12 Agustus 2026 | Menyelaraskan kebutuhan fungsional, data, nonfungsional, integrasi, dan ketertelusuran dengan PRD v0.5.                                                                                                                        |
| 1.0       | 15 Agustus 2026 | Menambahkan tata kelola Koordinator, detail kasus terkoordinasi untuk Waka, histori lintas guru, identitas sementara dan rekonsiliasi, akun Admin IT, laporan gabungan, rolling nonotomatis, serta retensi minimum tiga tahun. |
| 1.1       | 23 Agustus 2026; diamandemen 9 dan 12 September 2026 | Menambahkan fondasi konfigurasi/integrasi, fallback data persiapan, aktivasi sesuai tanggal mulai, rollover tanpa keputusan akademik otomatis, lima status pelayanan, satu penanggung jawab kasus, penguncian terminal, kode kasus internal, dan proyeksi aman seluruh kasus untuk Waka. |
