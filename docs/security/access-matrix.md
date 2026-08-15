---
document_id: access-matrix
version: 1.0
status: baseline-final
last_updated: 2026-08-15
source: Inventaris_Halaman_dan_Kebutuhan_Antarmuka_Aplikasi_BK_v1.0.xlsx
---

# Matriks Hak Akses MVP

Matriks menyatakan kewenangan fungsional dan objek, bukan sekadar visibilitas tombol. Akses sensitif selalu diperiksa pada server; fungsi Koordinator, Guru BK, Waka, dan Admin IT tidak boleh saling memperluas secara otomatis.

**Ringkasan:** Ringkasan · 42 · aturan akses · Prinsip: hak minimum; berbasis scope murid, tahun ajaran, penugasan kasus, dan koordinasi Waka; detail kasus tidak sama dengan akses konsultasi sensitif.

| ID | Objek/Area | Aksi | Guru BK | Koordinator BK | Waka Kesiswaan | Admin IT | Batas/Kondisi | Requirement SRS |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| AC-001 | Akun | Masuk ke aplikasi | Ya | Ya | Ya | Ya | Hanya dengan akun sah. | AUTH-01 |
| AC-002 | Dashboard | Lihat dashboard | Ya—data dalam kewenangan | Ya—data dalam kewenangan koordinasi | Ya—agregat dan kasus terkoordinasi | Tidak otomatis | Tautan Waka hanya membuka kasus yang secara eksplisit dikoordinasikan. | AUTH-02; AUTH-05; DASH-01; DASH-03 |
| AC-003 | Murid | Lihat daftar murid | Ya—kelas ampuan/kasus khusus | Ya—sesuai kelas ampuan/penugasan | Terbatas—ringkasan yang diizinkan | Ya—master untuk tugas teknis | Akses Admin IT tidak membuka isi layanan BK. | AUTH-02; MD-01 |
| AC-004 | Murid | Lihat profil murid | Ya—scope aktif; histori sah | Ya—sesuai scope Guru BK/penugasan | Terbatas—umum atau terkait kasus terkoordinasi | Terbatas—data master | Hak dibatasi per bagian; histori lama hanya-baca bagi Guru BK pemegang scope baru. | AUTH-02; STU-01; STU-02 |
| AC-005 | e-Tatib | Lihat riwayat/poin | Ya—dalam kewenangan | Ya—bila berwenang | Ringkasan yang diizinkan | Ya—untuk tugas integrasi | Semua bersifat baca; tidak ada write-back MVP. | INT-01 s.d. INT-04 |
| AC-006 | Kasus | Buat kasus | Ya—murid dalam kewenangan | Ya hanya bila juga bertindak sebagai Guru BK berwenang | Tidak | Tidak | Server menolak murid di luar cakupan. | CASE-01; AUTH-03 |
| AC-007 | Kasus | Lihat detail kasus | Ya—dalam kewenangan | Ya—bila berwenang atas kasus | Ya—hanya kasus terkoordinasi; hanya-baca | Tidak otomatis | Waka ditolak untuk kasus lain; koordinasi tidak membuka konsultasi sensitif/catatan internal secara otomatis. | AUTH-02; AUTH-05; CASE-12 |
| AC-008 | Kasus | Ubah informasi/penanganan awal | Ya—dalam kewenangan | Ya hanya bila berwenang sebagai penanggung jawab | Tidak | Tidak | Perubahan penting menghasilkan audit. | CASE-03; AUD-01 |
| AC-009 | Kasus | Ubah status/selesaikan | Ya—penanggung jawab kasus | Ya hanya bila berwenang atas kasus | Tidak | Tidak | Hasil akhir wajib dan riwayat tidak dihapus. | CASE-04; CASE-06 |
| AC-010 | Tindak lanjut | Tambah/ubah tindak lanjut | Ya—dalam kewenangan | Ya bila berwenang atas kasus | Tidak | Tidak | Jenis dan status adalah field terpisah. | CASE-05; CASE-09 |
| AC-011 | Konsultasi | Catat metadata konsultasi | Ya—dalam kewenangan | Ya bila berwenang sebagai Guru BK | Tidak | Tidak | Hanya metadata/ringkasan yang diizinkan. | CONS-01 |
| AC-012 | Konsultasi | Baca ringkasan umum | Ya—dalam kewenangan | Tidak otomatis; hanya bila berwenang | Tidak otomatis | Tidak | Detail kasus terkoordinasi tidak sama dengan akses isi lengkap konsultasi sensitif. | AUTH-04; AUTH-05; CONS-01; CONS-02 |
| AC-013 | Konsultasi | Baca isi sensitif | Ya—Guru BK dengan scope aktif/kewenangan kasus | Tidak otomatis; hanya melalui scope Guru BK | Tidak | Tidak | Jabatan Koordinator, Waka, dan hak teknis Admin IT tidak otomatis membuka isi sensitif. | AUTH-04; AUTH-07 |
| AC-014 | Prestasi | Tambah/edit prestasi | Ya—dalam kewenangan | Ya bila berwenang | Tidak | Tidak | P0 bertahap setelah fungsi inti stabil. | ACH-01; ACH-02 |
| AC-015 | Prestasi | Verifikasi prestasi | TBD | TBD | Tidak | Tidak | Verifier dan status belum disahkan. | ACH-02; SRS v1.0 §11 |
| AC-016 | Penugasan kelas | Lihat penugasan | Ya—penugasannya | Ya—seluruh penugasan yang dikelola | Ringkasan tata kelola | Dukungan teknis | Rolling/perubahan hanya dicatat berdasarkan keputusan resmi; tidak otomatis. | ASN-01; ASN-02; ASN-06 |
| AC-017 | Penugasan kelas | Buat/ubah penugasan | Tidak | Ya | Tidak | Tidak | Menyimpan tahun ajaran dan periode efektif. | ASN-01; ASN-03 |
| AC-018 | Penugasan kasus | Beri kewenangan khusus | Tidak | Ya | Tidak | Tidak | Akses tambahan hanya pada kasus target. | ASN-04 |
| AC-019 | Penugasan kasus | Alihkan kasus aktif | Tidak | Ya | Tidak | Tidak | Harus eksplisit; simpan lama, baru, alasan, dan waktu. | ASN-05 |
| AC-020 | Koreksi operasional | Ajukan koreksi | Ya | Ya | Tidak | Tidak | Hanya objek dalam kewenangan. | COR-01 |
| AC-021 | Koreksi operasional | Verifikasi koreksi | Tidak | Ya | Tidak | Tidak | Daftar field yang wajib diverifikasi masih perlu validasi. | COR-01 |
| AC-022 | Koreksi master | Laporkan kesalahan | Ya | Ya | Pantau bila diperlukan | Ya—terima/koordinasikan | Perubahan sebenarnya dilakukan pada sumber resmi. | MD-02; COR-02 |
| AC-023 | Koreksi master | Ubah identitas/kelas | Tidak | Tidak | Tidak | Melalui Dapodik/sumber resmi | Aplikasi BK hanya menerima hasil sinkronisasi. | MD-01; COR-02 |
| AC-024 | Laporan | Lihat laporan | Ya—scope sendiri | Ya—gabungan seluruh Guru BK aktif | Ya—agregat/format yang diizinkan | Tidak otomatis | Isi konsultasi sensitif dan data terlarang dikecualikan. | REP-01; REP-02; REP-04; CONS-02 |
| AC-025 | Laporan | Ekspor/cetak | Ya—scope sendiri | Ya—gabungan seluruh Guru BK aktif | Ya—format yang diizinkan | Tidak otomatis | Batas ekspor sama dengan tampilan; format dan penandaan masih perlu disahkan. | REP-03; REP-04 |
| AC-026 | Audit | Lihat riwayat perubahan | Ya—objek dalam kewenangan | Ya—objek/kewenangan koordinasi | Ringkasan bila diizinkan | Teknis saja bila diperlukan | Audit tidak memperluas hak baca objek. | AUD-01; AUTH-03 |
| AC-027 | Audit | Ubah/hapus catatan audit | Tidak | Tidak | Tidak | Tidak melalui antarmuka umum | Audit dibuat otomatis dan dilindungi. | AUD-01 |
| AC-028 | Integrasi | Lihat status sinkronisasi | Ringkasan waktu data | Ringkasan waktu data | Ringkasan bila relevan | Ya | Pengguna layanan perlu tahu jika data tidak mutakhir. | INT-03; MD-02 |
| AC-029 | Integrasi | Kelola sinkronisasi/pemetaan | Tidak | Tidak | Tidak | Ya—sesuai mekanisme resmi | Hak integrasi tidak membuka isi layanan; mekanisme teknis/autentikasi masih perlu divalidasi. | AUTH-06; INT-03; MD-02 |
| AC-030 | Data referensi | Kelola Bidang Layanan/Jenis Tindak Lanjut | Tidak | TBD—kandidat Koordinator BK | Tidak | Dukungan teknis, bukan pemilik istilah | Pemilik kebijakan dan proses perubahan referensi belum disahkan. | CASE-08; CASE-09; SRS v1.0 §7 |
| AC-031 | Notifikasi | Lihat/tandai notifikasi | Ya—milik sendiri | Ya—milik sendiri | Ya—milik sendiri | Ya—milik sendiri | Tautan objek tetap diperiksa ulang oleh server. | AUTH-03 |
| AC-032 | Keamanan | Akses langsung objek di luar kewenangan | Ditolak | Ditolak | Ditolak | Ditolak untuk isi layanan | Penyembunyian menu bukan pengganti otorisasi server. | AUTH-02; AUTH-03 |
| AC-033 | Koordinasi Waka | Catat koordinasi/persetujuan | Ya—kasus dalam kewenangan | Ya—sesuai kewenangan operasional | Tidak | Tidak | Pencatatan memuat kebutuhan, tujuan, waktu, status, dan audit; tidak mengubah status kasus. | CASE-12; AUD-01 |
| AC-034 | Kasus terkoordinasi | Ubah catatan profesional/status kasus | Ya—bila penanggung jawab | Ya hanya melalui scope Guru BK/kewenangan kasus | Tidak | Tidak | Akses Waka selalu hanya-baca. | AUTH-05; CASE-12 |
| AC-035 | Riwayat murid | Baca histori lintas kelas/Guru BK | Ya—bila memiliki scope aktif atas murid | Ya—bila memiliki scope Guru BK/penugasan | Terbatas sesuai kewenangan | Tidak untuk isi layanan | Hak baca berlangsung sampai murid lulus sesuai scope aktif; tetap per objek dan bagian. | STU-02; AUTH-04 |
| AC-036 | Riwayat murid | Ubah catatan profesional lama | Tidak kecuali catatan milik sendiri yang masih dapat dikoreksi melalui aturan sah | Tidak otomatis | Tidak | Tidak | Pemegang scope baru tidak mengubah catatan profesional guru sebelumnya. | STU-02; AUD-01 |
| AC-037 | Laporan gabungan | Rekap/cetak seluruh Guru BK aktif | Tidak | Ya | Tidak—menggunakan laporan Waka yang diizinkan | Tidak otomatis | Jumlah Guru BK dinamis; kondisi saat validasi adalah tujuh Guru BK. | REP-04 |
| AC-038 | Laporan scope | Rekap/cetak scope sendiri | Ya | Ya bila juga memiliki scope Guru BK | Tidak | Tidak otomatis | Fungsi Koordinator dan Guru BK dievaluasi terpisah. | REP-01; REP-04; AUTH-07 |
| AC-039 | Akun | Buat/aktifkan/nonaktifkan/pulihkan | Tidak | Tidak | Tidak | Ya | Harus berdasarkan penugasan resmi dan menghasilkan audit. | ACC-01; ACC-02; AUD-01 |
| AC-040 | Identitas sementara | Rekonsiliasi ke master berdasarkan NISN | Tidak—hanya membuat data sementara saat perlu | Tidak | Tidak | Ya | Tidak membuat murid ganda; nama resmi berasal dari sistem sumber; nilai awal tetap diaudit. | MD-03; MD-04; AUTH-06 |
| AC-041 | Hak teknis | Baca isi layanan/konsultasi karena hak Admin IT | Tidak berlaku | Tidak berlaku | Tidak berlaku | Tidak | Hak teknis, infrastruktur, akun, dan integrasi tidak memperluas akses isi BK. | AUTH-06; ACC-02 |
| AC-042 | Penugasan | Jalankan rolling/perubahan otomatis | Tidak | Tidak—hanya mencatat keputusan resmi | Tidak | Tidak | Perubahan tidak otomatis dan tidak otomatis memindahkan kasus aktif. | ASN-03; ASN-05; ASN-06 |
