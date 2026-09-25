# Pekerjaan Aktif Ruang BK

## Status

- Pembaruan otomatis e-Tatib ditambahkan pada 25 September 2026. Admin IT dapat
  menyimpan link API terenkripsi setelah pratinjau, sinkronisasi ulang, dan
  konfirmasi kata sandi akun. Scheduler menjalankan sinkronisasi Senin-Jumat
  pukul 15.00 `Asia/Jakarta`; aksi manual tetap tersedia dan kegagalan otomatis
  ditampilkan di Data Master serta dashboard Admin IT tanpa membuka URL. Link
  dihapus saat fitur dinonaktifkan. Verifikasi terarah lulus 90 test/523
  assertion dengan 8 test dilewati; Pint, checker frontend, build, Blade cache,
  Composer strict, dan `git diff --check` lulus.
- API e-Tatib dikoreksi pada 24 September 2026 mengikuti kontrak nyata berupa
  array JSON langsung tanpa `source_id`, token, pagination, atau delta. Admin IT
  menempel link sekali pakai, membuka pratinjau, lalu menyinkronkan secara
  manual. Link dan payload mentah tidak disimpan serta tidak memerlukan variabel
  `.env`. NISN 1-10 digit dinormalisasi dengan nol di depan dan dicocokkan exact;
  nama berbeda tetap masuk konflik. Admin IT melihat NISN lengkap pada
  pratinjau dan dapat mencocokkan konflik secara manual ke master murid melalui
  halaman khusus tanpa mengubah data sumber atau master murid.
- Pencocokan manual e-Tatib ditambahkan pada 25 September 2026. Keputusan
  disimpan per pasangan NISN canonical dan nama sumber ternormalisasi, berlaku
  untuk seluruh pelanggaran identitas yang sama, dan dipakai kembali saat
  sinkronisasi berikutnya. Mapping dapat diubah atau dibatalkan selama record
  terkait belum ditautkan ke kasus BK; seluruh mutasi memakai operation lock,
  transaksi, otorisasi Admin IT, dan audit append-only.
  Verifikasi terarah lulus 101 test/808 assertion. Suite penuh memiliki 59
  kegagalan existing di test laporan/kasus/Waka yang masih mengacu service,
  route, dan label sebelum penyederhanaan; tidak ada kegagalan pada test e-Tatib,
  Data Master, atau matriks otorisasi terarah.
- Karena sumber tidak menyediakan ID pelanggaran, SIBK membentuk ID stabil dari
  NISN, waktu, pelanggaran, poin, dan pencatat. Baris identik ditolak karena tidak
  dapat dibedakan. Respons dianggap snapshot penuh. Sinkronisasi sekali pakai
  tidak menyimpan link; jadwal otomatis hanya menyimpan link terenkripsi setelah
  persetujuan dan verifikasi ulang Admin IT.

- Import daftar murid lintas tahun pelajaran telah diimplementasikan di working
  tree `cobasidebar` pada 24 September 2026. Admin IT dapat menempel URL API
  Siswa publik sekali pakai atau memakai CSV fallback dengan field
  `nisn,nama,rombel,tahun_pelajaran`; satu request dapat memuat beberapa tahun
  sementara yang sudah dibuat. URL, query token, dan payload mentah tidak
  disimpan. Feature branch belum dibuat karena izin perubahan ref Git tidak
  diberikan.
- Fetch API Siswa memakai GET server-side, timeout 10 detik, batas respons 2 MiB,
  tanpa redirect, serta menolak localhost dan IP private/reserved. Riwayat
  `external_sync_runs` hanya menyimpan status dan jumlah. Halaman Data Master
  menampilkan API Siswa sebagai jalur utama, CSV sebagai fallback, dan tetap
  mempertahankan konfigurasi e-Tatib lengkap. Backend Dapodik lama tidak dicabut.
- API Siswa memakai alur Cek & Pratinjau sebelum import. Modal menampilkan
  jumlah baris, murid unik, kesiapan tahun/rombel, dan lima contoh dengan NISN
  tersamarkan tanpa mutasi atau penyimpanan payload. Kegagalan DNS, koneksi,
  timeout, SSL, redirect, HTTP, dan JSON dibedakan dengan pesan aman.
- Admin IT dapat memeriksa hasil impor melalui daftar master murid read-only di
  `GET /data-master/students`. Daftar hanya memuat identitas, rombel, tahun
  ajaran, dan status sumber; profil serta isi layanan BK tetap ditolak. Aktivasi
  tahun ajaran tetap menjadi kewenangan Koordinator BK setelah penugasan setiap
  rombel lengkap dan tanggal mulai telah tiba. Verifikasi daftar master dan
  matriks otorisasi lulus 11 test/180 assertion; test persiapan tahun ajaran
  lulus 57 test/476 assertion.
- Verifikasi API Siswa: `DelayedDapodikPreparationTest` lulus 57 test/476
  assertion, `IntegrationSettingTest` lulus 40 test/658 assertion, dan dua test
  tampilan Data Master terdampak lulus 2 test/24 assertion. Pint terarah dan
  `git diff --check` lulus.
- Pekerjaan aktif: data contoh lintas tahun pada branch
  `fitur/dummy-lintas-tahun-guru-bk`; checkpoint penyederhanaan berikutnya
  adalah penghapusan Pengalihan Permasalahan.
- Branch fitur berikutnya: belum dibuat.
- Worktree: `.worktrees/penyederhanaan-penugasan-kelas`.
- Base: `cobasidebar`; `main` tidak disentuh.
- Status: **KOREKSI LAPORAN SELESAI DAN PR #32 SUDAH DI-MERGE KE `cobasidebar`**.
- Spec: `docs/superpowers/specs/2026-09-23-penyederhanaan-penugasan-kelas-design.md`.

## Hasil implementasi 22–23 September 2026

- Halaman tiga tab diganti satu daftar catatan kasus dan konsultasi.
- Filter dibatasi ke Tahun Ajaran, Kelas, Jenis Layanan BK, dan jumlah data
  `10/25/50/100`; default daftar adalah 10 data terbaru.
- Jumlah data ditempatkan pada header tabel dan langsung memuat ulang tabel.
  Panel filter memakai satu tombol: `Terapkan` saat belum aktif/ada perubahan,
  lalu `Reset` dengan warna berbeda saat filter aktif tanpa perubahan.
- Teks urutan pada header tabel diganti ringkasan seluruh hasil filter. Total
  Catatan selalu tampil; Permasalahan/Konsultasi yang tidak relevan dengan
  filter disembunyikan. Ringkasan yang sama masuk preview/PDF dan Excel.
- Daftar Guru BK/Koordinator menampilkan Hari/Tanggal, Layanan/Jenis Masalah,
  dan Hasil. Satu ikon kaca pembesar membuka row detail Latar Belakang Masalah
  serta Penanganan mulai dari kolom Hari/Tanggal, dengan latar lembut, garis
  aksen kiri, dan bayangan inset agar terlihat tenggelam.
- Jenis catatan ditampilkan kecil sebagai Permasalahan/Konsultasi tanpa kata
  `Catatan`; bidang layanan tampil lebih besar dan tebal.
- Klik baris tidak membuka modal dan aksi cetak individual tidak ditampilkan;
  aksi arsip hanya tampil bila policy objek mengizinkan.
- Tombol `Cetak / Unduh Rekap` membuka preview seluruh hasil filter dengan urutan
  tanggal paling awal. Preview tidak membuka dialog cetak otomatis.
- Preview rekap memakai A4 portrait dengan Download Excel dan Cetak/Simpan PDF.
- Font tabel preview/PDF diperkecil secara terlokalisasi menjadi 8 pt, padding
  dirapatkan, header diizinkan membungkus, dan proporsi kolom portrait diperbaiki.
  Font tabel Excel memakai 9 pt.
- Tabel dokumen putih polos memakai tujuh kolom: No, Hari/Tanggal, Nama/Kelas,
  Jenis Masalah, Ringkasan, Guru BK, dan Keterangan. Ringkasan mengambil
  `resolution_summary` kasus atau `result` konsultasi.
- Keterangan preview/PDF/Excel untuk Permasalahan memuat Sumber dan Tindak
  Lanjut terbaru; Konsultasi tetap memakai `Selesai`.
- Garis luar tabel dokumen tetap utuh sampai sisi bawah baris terakhir.
- Kolom Guru BK memakai owner kasus terakhir untuk Permasalahan atau pencatat
  konsultasi pada `counselor_id`.
- Excel `.xlsx` memakai `phpoffice/phpspreadsheet:^5.10`; unduhan Word dihapus.
- Kop menempatkan dua placeholder logo dan garis tepat setelah alamat. Subtitle
  dokumen hanya menampilkan tahun ajaran.
- Preview/PDF dan Excel menyajikan ringkasan sebagai kalimat lengkap yang
  menjelaskan total serta komposisi hasil filter, bukan deretan angka singkat.
- Rekap memakai Koordinator BK dan Waka Kesiswaan. Kondisi akun penandatangan kosong/ganda
  menampilkan `Penandatangan belum tersedia`; NIP tidak ditampilkan.
- Waka memakai `/reports` dan Blade yang sama dengan Koordinator tanpa penanda
  hanya-baca. Kolom Waka dibatasi ke struktur tabel dokumen: No, Hari/Tanggal,
  Nama/Kelas, Jenis Masalah, Ringkasan, Guru BK, dan Keterangan.
- Waka tidak menerima latar belakang, penanganan, hasil terpisah, URL aksi, atau
  kemampuan arsip pada payload view. Preview, cetak/PDF, Excel, dan preview per
  catatan ditolak server melalui policy dokumen terpisah.
- Portal laporan Waka tiga tab, request, service rekap, dan empat Blade khusus
  dipensiunkan. URL lama diarahkan ke `/reports` untuk akun Waka dan endpoint
  ekspor CSV monitoring lama dihapus.

## Batas dan catatan deployment

- Scheduler host production wajib memanggil `php artisan schedule:run` setiap
  menit. Pada Laragon lokal gunakan `php artisan schedule:work` selama aplikasi
  perlu menjalankan jadwal. Tidak ada catch-up ketika host mati pukul 15.00;
  eksekusi berikutnya jatuh pada hari kerja berikutnya.
- Pada 24 September 2026, branch `fitur/dummy-lintas-tahun-guru-bk` menambah data
  contoh lokal: tahun demo sebelumnya, 22 riwayat kelas X→XI dan XI→XII,
  dua Guru BK tambahan, pembagian enam kelas aktif (dua kelas per guru),
  17 kasus (dua dari tahun lalu), dan 15 konsultasi. Seeder
  `DummyCaseAndServiceSeeder` berhasil dijalankan pada database lokal `sibk_uji`.
  Ulangi dengan `php artisan db:seed --class=DummyCaseAndServiceSeeder` bila
  membutuhkan data contoh di database lokal lain. Seeder tetap dibatasi ke
  environment local/testing dan tidak masuk `DatabaseSeeder`.
- Tidak ada migration atau perubahan skema database pada pekerjaan laporan.
- `ReportPreviewSeeder` tersedia untuk data pratinjau lokal: satu kasus dan dua
  konsultasi. Seeder bersifat idempoten, dibatasi ke environment local/testing,
  dan tidak didaftarkan ke `DatabaseSeeder`.
- Database lokal `sibk_uji` sudah menjalankan migration
  `2026_09_17_000200_remove_revisi_sibk_3_2_legacy_schema` sebelum data contoh
  dibuat; database shared/production tidak disentuh.
- Dockerfile sudah memasang ekstensi PHP `zip`. Runtime lain wajib mengaktifkan
  ekstensi yang dipersyaratkan PhpSpreadsheet sebelum ekspor Excel digunakan.
- Template kop dan dokumen bersifat sementara sampai template resmi sekolah
  selesai; partial terpusat memudahkan penggantian tanpa mengubah query/ekspor.
- Temporary file Excel dihapus setelah response; data spreadsheet yang
  berpotensi menjadi formula dinetralkan.
- Adapter production Dapodik/e-Tatib tetap di luar scope dan `unavailable`.

## Verifikasi yang belum dijalankan

Koreksi laporan sudah diimplementasikan dan diperiksa secara statis. Pint,
build frontend, Composer strict, dan `git diff --check` lulus pada 23 September
2026. Sesuai instruksi pengguna, automated test dan checker frontend belum
dijalankan. Checkpoint penghapusan Pengalihan Permasalahan dan penyederhanaan
Penugasan Kelas masih berada pada tahap spec. Verifikasi perilaku kelak mencakup:

1. Akses halaman dan mutasi hanya untuk Koordinator BK.
2. Seluruh kelas konteks, jumlah murid aktif, pencarian, dan filter status.
3. Konteks tahun aktif/persiapan serta penolakan tahun berakhir atau manipulasi ID.
4. Create, no-op, penggantian terjadwal, pergantian aktif, histori, dan overlap.
5. Readiness serta aktivasi tahun ajaran pada halaman yang sama.
6. Migration penghapusan `decision_number` dan pembersihan seluruh consumer.
7. Penghapusan menu, halaman, endpoint, dan mutasi Pengalihan Permasalahan tanpa
   mengganggu owner awal serta scope akses kasus existing.
8. Penyederhanaan tabel laporan Guru BK/Koordinator menjadi Hasil dan satu
   kontrol detail tanpa mengubah tampilan Waka.
9. Mapping Keterangan preview/PDF/Excel menjadi Sumber dan Tindak Lanjut terbaru
   untuk Permasalahan tanpa mengubah proyeksi Waka.

## Langkah berikutnya

1. Review perubahan import daftar murid lintas tahun dan pindahkan ke feature
   branch sebelum commit bila izin Git tersedia.
2. Pengguna mereview hasil koreksi tabel laporan.
3. Setelah disetujui, lanjutkan penghapusan Pengalihan Permasalahan.
4. Sederhanakan Penugasan Kelas setelah pengalihan selesai.

## Acuan

- Spec aktif: `docs/superpowers/specs/2026-09-23-penyederhanaan-penugasan-kelas-design.md`.
- Plan laporan: `docs/superpowers/plans/2026-09-23-koreksi-tabel-laporan.md`.
- Plan pengalihan: `docs/superpowers/plans/2026-09-23-hapus-pengalihan-permasalahan.md`.
- Plan penugasan: `docs/superpowers/plans/2026-09-23-penyederhanaan-penugasan-kelas.md`.
- Requirement index: `docs/requirements-index.md`.
- API contract: `docs/api-contract.md`.
- Matriks otorisasi: `docs/testing/authorization-matrix.md`.
