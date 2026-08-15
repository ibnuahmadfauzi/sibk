---
document_id: srs
version: 1.0
status: baseline-final
last_updated: 2026-08-15
product_reference: docs/product/prd.md
source: SRS_Aplikasi_BK_v1.0.docx
---

# SRS Aplikasi BK

Baseline spesifikasi MVP layanan Bimbingan dan Konseling.

> Dokumen ini merupakan sumber kanonik kebutuhan fungsional, nonfungsional, akses, integrasi, data, dan kriteria penerimaan.

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
| Pengguna P1    | Wali kelas dan murid          | Struktur peran dapat disiapkan, tetapi antarmuka dan alurnya tidak dibangun pada P0.                                       |

# Hak akses dan tata kelola

| **Objek/tindakan**  | **Guru BK**                                           | **Koordinator BK**                                    | **Waka Kesiswaan**                                  | **Admin IT**                   |
|---------------------|-------------------------------------------------------|-------------------------------------------------------|-----------------------------------------------------|--------------------------------|
| Daftar/profil murid | Scope aktif dan kasus khusus; termasuk histori murid. | Sesuai scope Guru BK/penugasan dan fungsi koordinasi. | Ringkasan; detail bila terkait kasus terkoordinasi. | Master untuk tugas teknis.     |
| Kasus BK            | Buat, baca, ubah dalam kewenangan.                    | Alihkan/beri kewenangan; baca bila berwenang.         | Detail hanya-baca untuk kasus terkoordinasi.        | Tidak otomatis.                |
| Konsultasi sensitif | Baca bila scope murid aktif atau kasus khusus.        | Tidak otomatis di luar scope Guru BK.                 | Tidak otomatis; isi lengkap dikecualikan.           | Tidak.                         |
| Penugasan           | Lihat penugasannya.                                   | Buat/ubah berdasarkan keputusan resmi.                | Lihat ringkasan tata kelola.                        | Dukungan teknis.               |
| Koreksi operasional | Ajukan.                                               | Verifikasi.                                           | Ringkasan bila relevan.                             | Tidak memverifikasi isi.       |
| Koreksi master      | Laporkan.                                             | Laporkan/pantau.                                      | Pantau bila diperlukan.                             | Proses melalui sumber resmi.   |
| Laporan/cetak       | Sesuai scope sendiri.                                 | Gabungan seluruh Guru BK aktif.                       | Agregat dan kasus terkoordinasi yang diizinkan.     | Tidak otomatis.                |
| Akun/infrastruktur  | Lihat akun sendiri.                                   | Pantau operasional.                                   | Tidak mengelola.                                    | Kelola akun dan infrastruktur. |

- Koordinator BK menjadi penanggung jawab operasional Aplikasi BK.

- Koordinator yang merangkap Guru BK tetap memperoleh akses sensitif hanya melalui scope Guru BK atau penugasan kasus.

- Waka memperoleh detail kasus setelah koordinasi terhadap kasus tersebut tercatat; akses tetap hanya-baca.

- Guru BK yang memperoleh scope aktif atas murid dapat membaca histori layanan dan konsultasi sebelumnya sampai murid lulus, tetapi tidak mengubah catatan lama.

- Rolling atau perubahan pembagian dicatat Koordinator berdasarkan keputusan resmi; sistem tidak melakukan perubahan otomatis.

# Kebutuhan fungsional

## Autentikasi, otorisasi, dan tata kelola

| **ID**  | **Kebutuhan**                                                                                             | **Pri.** | **Kriteria penerimaan**                                                                                 |
|---------|-----------------------------------------------------------------------------------------------------------|----------|---------------------------------------------------------------------------------------------------------|
| AUTH-01 | Pengguna harus masuk dengan akun aktif sebelum mengakses data BK.                                         | P0       | Data operasional tidak tersedia tanpa sesi sah.                                                         |
| AUTH-02 | Guru BK hanya dapat mengakses murid dalam scope aktif dan kasus khusus yang ditugaskan.                   | P0       | Daftar, pencarian, detail, dashboard, laporan, ekspor, URL, dan API memakai batas yang sama.            |
| AUTH-03 | Server harus memeriksa kewenangan pada setiap objek, bagian data, dan tindakan sensitif.                  | P0       | Permintaan langsung di luar kewenangan ditolak tanpa membocorkan isi objek.                             |
| AUTH-04 | Isi konsultasi sensitif hanya dapat dibaca Guru BK yang memiliki scope murid aktif atau kewenangan kasus. | P0       | Jabatan Koordinator, Waka, atau Admin IT tidak otomatis membuka isi konsultasi.                         |
| AUTH-05 | Waka harus memiliki akses hanya-baca pada ringkasan umum dan detail kasus yang dikoordinasikan kepadanya. | P0       | Server menolak detail kasus yang tidak tercatat dikoordinasikan dan menolak setiap perubahan oleh Waka. |
| AUTH-06 | Hak teknis Admin IT harus dipisahkan dari hak membaca layanan BK.                                         | P0       | Admin IT dapat mengelola akun, integrasi, master, dan rekonsiliasi tanpa membuka isi kasus.             |
| AUTH-07 | Akun dengan fungsi Koordinator sekaligus Guru BK harus menerapkan hak tiap fungsi secara terpisah.        | P0       | Fungsi Koordinator tidak memperluas akses konsultasi di luar scope Guru BK.                             |
| GOV-01  | Sistem harus mendukung Koordinator BK sebagai penanggung jawab operasional.                               | P0       | Menu operasional penugasan, verifikasi, koordinasi, dan rekap tersedia bagi Koordinator.                |

## Akun, data master, identitas sementara, dan penugasan

| **ID** | **Kebutuhan**                                                                                                         | **Pri.** | **Kriteria penerimaan**                                                                        |
|--------|-----------------------------------------------------------------------------------------------------------------------|----------|------------------------------------------------------------------------------------------------|
| ACC-01 | Admin IT harus dapat membuat, mengaktifkan, menonaktifkan, dan memulihkan akun sesuai penugasan resmi.                | P0       | Perubahan akun menyimpan pelaku, waktu, peran, dan status.                                     |
| ACC-02 | Pengelolaan akun tidak boleh memberikan akses isi layanan secara otomatis.                                            | P0       | Peran teknis dan kewenangan objek diperiksa terpisah.                                          |
| MD-01  | Data murid, kelas, keanggotaan kelas, dan tahun ajaran harus mengacu pada Dapodik.                                    | P0       | Aplikasi BK tidak menjadi sumber utama perubahan identitas/kelas.                              |
| MD-02  | Koreksi data master harus diproses melalui Admin IT dan sumber resmi.                                                 | P0       | Hasil sinkronisasi terbaru tercatat.                                                           |
| MD-03  | Jika master belum tersinkron dan kasus/layanan harus dicatat, Guru BK harus dapat memasukkan NISN dan nama sementara. | P0       | Form tidak meminta data master lain dan memberi penanda sementara.                             |
| MD-04  | Identitas sementara harus direkonsiliasi menggunakan NISN tanpa membuat murid ganda.                                  | P0       | Nama resmi mengikuti sumber; kasus tetap terhubung; nilai awal dan hasil rekonsiliasi diaudit. |
| ASN-01 | Koordinator harus dapat menetapkan Guru BK untuk kelas dan tahun ajaran tertentu.                                     | P0       | Penugasan menyimpan periode efektif dan dasar keputusan.                                       |
| ASN-02 | Penugasan baru tidak boleh menimpa riwayat lama.                                                                      | P0       | Riwayat penanggung jawab tetap dapat ditelusuri.                                               |
| ASN-03 | Koordinator harus dapat mengubah penugasan di tengah tahun.                                                           | P0       | Perubahan memiliki tanggal efektif dan audit; kasus aktif tidak berpindah otomatis.            |
| ASN-04 | Koordinator harus dapat memberi kewenangan pada kasus khusus.                                                         | P0       | Akses tambahan terbatas pada kasus target dan menyimpan alasan.                                |
| ASN-05 | Pengalihan kasus aktif harus dilakukan secara eksplisit.                                                              | P0       | Simpan penanggung jawab lama, penerima, alasan, waktu berlaku, dan audit.                      |
| ASN-06 | Rolling atau perubahan pembagian dua tahunan tidak boleh dijalankan otomatis.                                         | P0       | Sistem hanya mencatat keputusan resmi yang dimasukkan Koordinator.                             |
| REF-01 | Nilai referensi layanan harus dapat dikelola tanpa mengubah kode.                                                     | P0       | Bidang layanan, jenis tindak lanjut, dan status disimpan sebagai data referensi.               |

## Kasus, koordinasi, dan tindak lanjut

| **ID**  | **Kebutuhan**                                                                                                     | **Pri.** | **Kriteria penerimaan**                                                                                                          |
|---------|-------------------------------------------------------------------------------------------------------------------|----------|----------------------------------------------------------------------------------------------------------------------------------|
| CASE-01 | Guru BK harus dapat membuat kasus untuk murid dalam kewenangannya atau identitas sementara yang sah.              | P0       | Server menolak objek di luar kewenangan dan menandai identitas sementara.                                                        |
| CASE-02 | Sumber kasus harus dapat dipilih dari e-Tatib, murid datang sendiri, temuan Guru BK, atau rujukan.                | P0       | Sumber tersimpan dan tampil pada detail.                                                                                         |
| CASE-03 | Kasus harus memuat informasi awal, penanganan awal, riwayat layanan, tindak lanjut, koordinasi, dan penyelesaian. | P0       | Aktivitas dapat dibaca kronologis sesuai kewenangan.                                                                             |
| CASE-04 | Status kasus harus menggunakan Baru, Dalam Penanganan, dan Selesai.                                               | P0       | Perubahan status tercatat pada audit.                                                                                            |
| CASE-05 | Guru BK harus dapat menjadwalkan dan mencatat hasil tindak lanjut.                                                | P0       | Jadwal berikutnya tampil pada dashboard pengguna berwenang.                                                                      |
| CASE-06 | Guru BK harus dapat menyelesaikan kasus dengan hasil akhir.                                                       | P0       | Kasus selesai tetap tersedia pada histori murid.                                                                                 |
| CASE-07 | Kasus terkait pelanggaran harus memakai data e-Tatib sebagai referensi resmi.                                     | P0       | Tidak ada transaksi atau pengetikan ulang data yang tersedia.                                                                    |
| CASE-08 | Kasus harus dapat memuat bidang layanan BK dari data referensi.                                                   | P0       | Perubahan nilai tidak memerlukan perubahan kode.                                                                                 |
| CASE-09 | Jenis tindak lanjut harus disimpan terpisah dari status pelaksanaannya.                                           | P0       | Keduanya dapat difilter terpisah.                                                                                                |
| CASE-10 | Sistem harus membedakan waktu pencatatan, tanggal layanan, tanggal rencana, dan tanggal pelaksanaan.              | P0       | Setiap tanggal tersimpan terpisah.                                                                                               |
| CASE-11 | Catatan internal harus opsional dan hanya dapat dibaca pengguna berwenang.                                        | P0       | Tidak tampil pada laporan umum atau Waka secara otomatis.                                                                        |
| CASE-12 | Kasus yang dikoordinasikan kepada Waka harus memiliki catatan koordinasi.                                         | P0       | Catatan memuat kasus, kebutuhan koordinasi/persetujuan, pencatat, waktu, dan status; akses Waka aktif hanya pada kasus tersebut. |

## Integrasi, konsultasi, profil, dan prestasi

| **ID**  | **Kebutuhan**                                                                                                                     | **Pri.**    | **Kriteria penerimaan**                                                                         |
|---------|-----------------------------------------------------------------------------------------------------------------------------------|-------------|-------------------------------------------------------------------------------------------------|
| INT-01  | e-Tatib harus diperlakukan sebagai sistem eksternal.                                                                              | P0          | e-Tatib tidak menjadi modul internal atau navigasi utama.                                       |
| INT-02  | Kasus harus dapat ditautkan ke data e-Tatib yang relevan.                                                                         | P0          | Referensi tampil tanpa pencatatan ulang.                                                        |
| INT-03  | Aplikasi BK harus membaca e-Tatib melalui API resmi dengan NISN sebagai pemetaan utama.                                           | P0          | Autentikasi, field, frekuensi, dan prosedur gangguan ditetapkan sebelum integrasi diaktifkan.   |
| INT-04  | MVP tidak boleh mengirim perubahan ke e-Tatib.                                                                                    | P0          | Tidak tersedia fungsi write-back.                                                               |
| CONS-01 | Sistem harus menyimpan metadata konsultasi dan ringkasan umum yang diizinkan.                                                     | P0          | Tanggal, jenis, status, Guru BK, jadwal, ringkasan umum, dan dokumen yang diizinkan tersedia.   |
| CONS-02 | Isi konsultasi sensitif tidak boleh masuk laporan umum atau detail Waka secara otomatis.                                          | P0          | Tampilan dan ekspor mengecualikan isi lengkap konsultasi.                                       |
| STU-01  | Profil murid harus menggabungkan informasi operasional yang berhak diakses pengguna.                                              | P0          | e-Tatib, kasus, layanan, tindak lanjut, konsultasi, dan prestasi tersedia sesuai kewenangan.    |
| STU-02  | Guru BK dengan scope aktif harus dapat membaca histori layanan/konsultasi murid lintas kelas dan pergantian Guru BK sampai lulus. | P0          | Histori lama terbaca tetapi tidak dapat diubah oleh Guru BK penerus.                            |
| ACH-01  | Sistem harus mendukung pencatatan prestasi setelah fungsi inti stabil.                                                            | P0 bertahap | Prestasi terhubung ke profil dan mengikuti akses.                                               |
| ACH-02  | Data prestasi harus memuat informasi minimum yang disahkan sekolah.                                                               | P0 bertahap | Jenis, tingkat, kegiatan, penyelenggara, tanggal, hasil, bukti, dan status verifikasi tersedia. |

## Dashboard, laporan, notifikasi, audit, dan koreksi

| **ID**  | **Kebutuhan**                                                                                                     | **Pri.** | **Kriteria penerimaan**                                                                          |
|---------|-------------------------------------------------------------------------------------------------------------------|----------|--------------------------------------------------------------------------------------------------|
| DASH-01 | Dashboard harus mengikuti peran, scope murid, penugasan kasus, dan koordinasi Waka.                               | P0       | Hitungan dan tautan tidak memuat data di luar kewenangan.                                        |
| DASH-02 | Dashboard Guru BK harus menampilkan ringkasan operasional utama.                                                  | P0       | Jumlah murid, pelanggaran, kasus, jadwal, dan aktivitas relevan tersedia.                        |
| DASH-03 | Dashboard Waka harus menampilkan kasus yang dikoordinasikan kepadanya.                                            | P0       | Tautan hanya membuka detail kasus yang sah dan tidak menyediakan aksi ubah.                      |
| REP-01  | Laporan harus menyediakan filter sesuai jenis data dan kewenangan.                                                | P0       | Parameter menghasilkan data konsisten.                                                           |
| REP-02  | Laporan harus mencakup pelanggaran, poin, konsultasi, tindak lanjut, prestasi minimum, dan rekap layanan.         | P0       | Rekap terbentuk tanpa penggabungan file manual.                                                  |
| REP-03  | Cetak dan ekspor harus mengikuti batas akses dan kerahasiaan.                                                     | P0       | Hasil tidak memuat data di luar kewenangan atau field terlarang.                                 |
| REP-04  | Koordinator harus dapat membuat rekap/cetak gabungan seluruh Guru BK aktif, sedangkan Guru BK hanya sesuai scope. | P0       | Saat baseline terdapat tujuh Guru BK; jumlah pengguna dihitung dinamis.                          |
| NOT-01  | Sistem harus menampilkan pemberitahuan operasional yang terkait pengguna.                                         | P0       | Jadwal, penugasan, koordinasi, koreksi, dan perubahan penting hanya dikirim kepada pihak berhak. |
| AUD-01  | Perubahan penting harus menghasilkan jejak audit otomatis.                                                        | P0       | Audit memuat pelaku, tindakan, objek, waktu, dan ringkasan perubahan.                            |
| COR-01  | Koreksi data operasional harus diverifikasi Koordinator BK.                                                       | P0       | Simpan pengaju, pemeriksa, alasan, nilai lama/usulan, waktu, dan hasil.                          |
| COR-02  | Kesalahan data master harus ditangani Admin IT melalui sumber resmi.                                              | P0       | Aplikasi tidak mengubah Dapodik secara sepihak dan menampilkan hasil sinkronisasi.               |

# Kebutuhan data

## Field minimum

| **Objek**           | **Field minimum**                                                                                                                                          | **Aturan**                                                   |
|---------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------|
| Kasus               | Murid/identitas sementara, sumber, tanggal layanan, bidang, informasi awal, penanganan awal, Guru BK, status, waktu dibuat, catatan internal, hasil akhir. | Objek harus berada dalam kewenangan; waktu dibuat otomatis.  |
| Koordinasi Waka     | Kasus, kebutuhan koordinasi/persetujuan, pencatat, waktu, status, Waka tujuan.                                                                             | Membuka detail hanya-baca untuk kasus target.                |
| Tindak lanjut       | Kasus, tanggal rencana, tanggal pelaksanaan, jenis, status, hasil, rencana berikutnya, pencatat.                                                           | Jenis dan status terpisah.                                   |
| Konsultasi          | Murid, kasus bila relevan, tanggal, jenis, status, Guru BK, jadwal, ringkasan umum, dokumen diizinkan.                                                     | Isi sensitif dipisahkan dan dibatasi.                        |
| Identitas sementara | NISN, nama masukan, pembuat, waktu, kasus/layanan, status rekonsiliasi.                                                                                    | Hanya ketika master belum tersedia; bukan master alternatif. |
| Rekonsiliasi        | NISN sumber, murid master, nama resmi, status, hasil, pemeriksa, waktu, konflik.                                                                           | Tidak membuat murid ganda; nilai lama tetap diaudit.         |
| Referensi e-Tatib   | Identitas sumber, NISN, tanggal/waktu kejadian, jenis, kategori, poin, waktu sinkronisasi.                                                                 | Hanya-baca.                                                  |
| Prestasi            | Murid, jenis, tingkat, kegiatan, penyelenggara, tanggal, hasil, bukti, status verifikasi.                                                                  | P0 bertahap.                                                 |
| Koreksi             | Objek, field, nilai lama, nilai usulan, alasan, pengaju, pemeriksa, status, waktu.                                                                         | Data master diperbaiki pada sumber resmi.                    |
| Akun                | Identitas pengguna, peran, status aktif, waktu perubahan, pengubah.                                                                                        | Hak teknis dan kewenangan objek dipisahkan.                  |

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
| Koordinasi Kasus          | Pencatatan koordinasi/persetujuan yang memberi Waka akses detail hanya-baca. |
| Penugasan Kasus           | Pemilik kasus, kewenangan tambahan, pengalihan, dan riwayat.                 |
| Tindak Lanjut             | Jadwal, kegiatan, status, hasil, dan rencana berikutnya.                     |
| Konsultasi                | Metadata, ringkasan umum, isi sensitif, dan batas akses.                     |
| Prestasi                  | Riwayat prestasi, bukti yang diizinkan, dan verifikasi.                      |
| Koreksi Data              | Pengajuan, pemeriksaan, hasil, dan hubungan dengan sumber resmi.             |
| Jejak Audit               | Catatan perubahan penting yang dibuat sistem.                                |
| Data Referensi            | Bidang layanan, jenis tindak lanjut, dan status operasional.                 |

## Status dan perubahan

- Status kasus terdiri atas Baru, Dalam Penanganan, dan Selesai.

- Jadwal tindak lanjut disimpan pada riwayat kegiatan dan bukan status kasus.

- Penugasan kelas dan kasus menyimpan periode berlaku; riwayat lama tidak ditimpa.

- Koordinasi Waka memiliki status tersendiri dan tidak mengubah status kasus.

- Identitas sementara memiliki status rekonsiliasi dan tidak menjadi master murid baru.

- Nilai status tindak lanjut, konsultasi, koreksi, dan verifikasi prestasi tetap sebagai data referensi setelah disahkan.

# Privasi, keamanan, dan audit

## Informasi yang tidak boleh tampil pada laporan umum

- Nama lengkap murid pada laporan untuk pembaca umum.

- Isi lengkap konsultasi, catatan internal profesional, dan dokumen asli.

- Nama pelapor serta informasi pribadi yang tidak diperlukan.

- Kronologi rinci pelanggaran sensitif.

- Informasi kesehatan atau keluarga tanpa dasar kewenangan yang sah.

Detail kasus yang dikoordinasikan kepada Waka bukan laporan umum. Akses tersebut tetap hanya-baca, dibatasi pada kasus target, dicatat pada audit, dan tidak otomatis membuka isi lengkap konsultasi sensitif atau dokumen asli.

## Aturan keamanan

| **Prinsip**                    | **Penerapan minimum**                                                                         |
|--------------------------------|-----------------------------------------------------------------------------------------------|
| Hak minimum                    | Pengguna hanya memperoleh hak yang diperlukan untuk tugasnya.                                 |
| Otorisasi per objek dan bagian | Server memeriksa murid, kasus, konsultasi, dokumen, koordinasi, dan aksi yang diminta.        |
| Batas konsisten                | Pencarian, daftar, detail, dashboard, laporan, ekspor, URL, dan API memakai aturan yang sama. |
| Pemisahan data sensitif        | Ringkasan, detail kasus, catatan internal, dan konsultasi sensitif dipisahkan.                |
| Pemisahan tugas                | Hak teknis, hak koordinasi, dan scope Guru BK tidak saling memperluas otomatis.               |
| Audit otomatis                 | Perubahan penting dibuat sistem dan tidak dapat diubah pengguna biasa.                        |

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

## Dapodik dan identitas sementara

| **Aspek**    | **Ketentuan**                                                                               |
|--------------|---------------------------------------------------------------------------------------------|
| Arah data    | Dapodik atau hasil ekspor resmi ke Aplikasi BK.                                             |
| Data minimum | Identitas murid, kelas, keanggotaan kelas, dan tahun ajaran.                                |
| Fallback     | Jika belum tersinkron dan kasus muncul, simpan NISN dan nama sementara pada kasus/layanan.  |
| Rekonsiliasi | Cocokkan berdasarkan NISN, tautkan kasus yang ada, gunakan nama resmi, dan cegah duplikasi. |
| Konflik      | NISN tidak ditemukan atau ganda ditahan untuk pemeriksaan Admin IT; data sah tidak ditimpa. |
| Koreksi      | Perubahan identitas dilakukan pada sumber resmi lalu disinkronkan kembali.                  |
| Riwayat      | Perubahan kelas/tahun ajaran tidak menghapus kasus, layanan, atau konsultasi sebelumnya.    |

# Ketertelusuran

| **Sumber/keputusan**                     | **Kebutuhan terkait**                  | **Pri.**    |
|------------------------------------------|----------------------------------------|-------------|
| Media pencatatan tersebar                | CASE, STU, REP                         | P0          |
| Laporan 30–60 menit                      | REP-01 s.d. REP-04                     | P0          |
| Kerahasiaan konsultasi                   | AUTH-03 s.d. AUTH-07; CONS-01; CONS-02 | P0          |
| Koordinator penanggung jawab operasional | GOV-01; ASN; REP-04                    | P0          |
| Waka membaca detail kasus terkoordinasi  | AUTH-05; CASE-12; DASH-03              | P0          |
| Histori mengikuti scope murid            | AUTH-02; AUTH-04; STU-02               | P0          |
| NISN+nama sebelum sinkronisasi           | MD-03; MD-04; CASE-01; NFR-05          | P0          |
| Rolling tidak otomatis                   | ASN-03; ASN-06                         | P0          |
| Admin IT mengelola akun/infrastruktur    | ACC-01; ACC-02; AUTH-06                | P0          |
| Retensi minimum tiga tahun               | NFR-08                                 | P0          |
| Prestasi                                 | ACH-01; ACH-02; REP-02                 | P0 bertahap |
| Wali kelas dan murid                     | Struktur peran P1                      | P1          |

# Ketergantungan yang belum dikunci

| **ID** | **Area**           | **Kondisi**                                                                            | **Dampak**                                        |
|--------|--------------------|----------------------------------------------------------------------------------------|---------------------------------------------------|
| DEP-01 | e-Tatib            | Autentikasi API, field rinci, frekuensi sinkronisasi, dan prosedur gangguan.           | Implementasi integrasi belum dapat dikunci penuh. |
| DEP-02 | Dapodik            | Mekanisme sinkronisasi teknis dan penanganan konflik NISN.                             | Proses rekonsiliasi teknis perlu dirinci.         |
| DEP-03 | Istilah layanan    | Nama, kardinalitas, kewajiban, dan pemilik data referensi.                             | Label form/filter dapat berubah.                  |
| DEP-04 | Status operasional | Status tindak lanjut, konsultasi, koreksi, dan verifikasi prestasi.                    | Pilihan status tidak boleh ditanam dalam kode.    |
| DEP-05 | Prestasi           | Verifikator, bukti, dan status.                                                        | Modul tetap P0 bertahap.                          |
| DEP-06 | Dokumen/retensi    | Format, ukuran, akses, pemulihan, dan prosedur penghapusan setelah minimum tiga tahun. | Unggah dan penghapusan belum dikunci.             |
| DEP-07 | Ekspor             | Format cetak/ekspor dan kebutuhan penandaan/audit khusus.                              | Luaran laporan belum dapat dikunci seluruhnya.    |

# Sumber dan riwayat versi

Acuan: PRD Aplikasi BK v1.0, kuesioner kebutuhan, contoh pencatatan berjalan, diskusi perancangan, inventaris antarmuka, dan keputusan validasi Koordinator BK/Guru BK serta Waka Kesiswaan sampai 13 Agustus 2026.

| **Versi** | **Tanggal**     | **Perubahan**                                                                                                                                                                                                                  |
|-----------|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 0.3       | 12 Agustus 2026 | Menyelaraskan kebutuhan fungsional, data, nonfungsional, integrasi, dan ketertelusuran dengan PRD v0.5.                                                                                                                        |
| 1.0       | 15 Agustus 2026 | Menambahkan tata kelola Koordinator, detail kasus terkoordinasi untuk Waka, histori lintas guru, identitas sementara dan rekonsiliasi, akun Admin IT, laporan gabungan, rolling nonotomatis, serta retensi minimum tiga tahun. |
