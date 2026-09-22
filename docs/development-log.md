# Riwayat Pengembangan Ruang BK

Status berjalan dan petunjuk serah terima ada di [pekerjaan aktif](current-work.md).
File ini hanya memuat ringkasan pekerjaan selesai, bukan laporan evidence panjang.

## Sedang berjalan

- Revisi laporan catatan layanan sudah diimplementasikan pada branch fitur dan
  berstatus `MENUNGGU VERIFIKASI`.
- Test, formatter, build, dan gate belum dijalankan sesuai instruksi pengguna;
  test lama untuk jalur laporan yang dipensiunkan juga belum diperbarui.
- Revisi SIBK 3.2 selesai sampai Checkpoint 7C dan terintegrasi ke
  `cobasidebar`; belum ada pekerjaan rilis ke `main`.
- Adapter production menunggu kontrak resmi provider dan admission gate.

## Pekerjaan selesai

### 22 September 2026 — Penyempurnaan detail inline laporan

- Setelah perbandingan visual, ikon kaca pembesar dipilih sebagai kontrol detail
  dan ikon informasi dihapus.
- Kontrol detail dan chevron tidak memakai outline; latar lembut muncul saat
  hover/focus.
- Hasil Layanan ditampilkan sebagai panel bertingkat mulai dari kolom Nama &
  Kelas, memakai latar lembut dan garis aksen kiri agar terhubung ke row induk.
- Narasi seed diperpanjang melewati batas 80 karakter agar interaksi dapat
  diamati; sebelumnya seluruh contoh hanya 63–77 karakter. Test dan build tidak
  dijalankan.

### 22 September 2026 — Penyajian tabel laporan

- Daftar menambahkan Hari/Tanggal dan Jenis Masalah; Latar Belakang Masalah
  serta Penanganan diringkas menjadi 80 karakter.
- Kontrol detail membuka teks lengkap dan ikon chevron membuka baris Hasil Layanan
  dengan latar berbeda pada tampilan desktop maupun mobile.
- Klik baris, ikon cetak individual, dan tombol cetak pada modal dihilangkan.
  Cetak/unduh rekap tetap tersedia. Test, formatter, dan build tidak dijalankan.

### 22 September 2026 — Penyederhanaan kontrol filter laporan

- Pilihan jumlah data dipindahkan dari panel filter ke header tabel dan langsung
  diterapkan ketika nilainya berubah.
- Tombol Terapkan dan Reset digabung menjadi satu kontrol yang berubah teks dan
  warna sesuai status filter serta perubahan pilihan yang belum diterapkan.
- Perubahan Tahun Ajaran tidak lagi mengirim form otomatis. Test, formatter, dan
  build tidak dijalankan.

### 22 September 2026 — Data contoh pratinjau laporan

- Seeder manual lokal menyiapkan satu catatan kasus bertindak lanjut Home Visit
  dan dua catatan konsultasi memakai data master contoh existing.
- Seeder aman dijalankan ulang, hanya tersedia pada environment local/testing,
  dan tidak ikut `DatabaseSeeder`.
- Database lokal `sibk_uji` diselaraskan ke migration aktif lalu seeder berhasil
  dijalankan. Test, formatter, dan build tidak dijalankan.

### 22 September 2026 — Implementasi revisi laporan catatan layanan

- Daftar laporan kini memuat satu row per kasus/konsultasi dengan tiga filter,
  pilihan jumlah data, urutan terbaru, modal detail, preview individual, dan
  aksi arsip berbasis policy.
- Template rekap disesuaikan menjadi tabel polos tujuh kolom; hasil penyelesaian
  digabungkan ke Penanganan dan klasifikasi tindak lanjut/status memakai field
  aktif. Jam layanan tidak ditampilkan karena tidak tersedia pada schema.
- Preview rekap mengambil seluruh hasil filter dengan urutan paling awal dan
  menyediakan Excel `.xlsx`, Word `.doc`, serta Cetak/Simpan PDF dari browser.
- PhpSpreadsheet 5.10 ditambahkan; formula injection dan pembersihan temporary
  file ditangani. PHPWord tidak ditambahkan.
- Partial kop/tanda tangan dipakai ulang oleh preview dan Word; resolver tidak
  memilih akun penandatangan secara diam-diam saat role kosong atau ganda.
- Jalur tiga tab, CSV, dan tujuh service/request legacy dipensiunkan setelah scan
  tidak menemukan consumer runtime.
- Tidak ada test, formatter, build, atau gate yang dijalankan pada checkpoint ini.

### 22 September 2026 — Amandemen kontrak dokumen laporan

- Terminologi tabel diperbaiki menjadi Penanganan; detail kasus memakai Catatan
  Penyelesaian dan konsultasi memakai Hasil.
- Preview rekap ditetapkan A4 landscape dan memakai seluruh hasil filter;
  preview satu catatan memakai A4 portrait. Pagination tetap khusus UI.
- Rekap memakai tanda tangan Koordinator BK dan Waka; dokumen individual memakai
  Guru BK penanggung jawab dan Waka. NIP/Kepala Sekolah tidak diasumsikan atau
  di-hardcode karena belum tersedia pada schema.
- Plan mewajibkan partial kop/tanda tangan dan resolver yang menolak pemilihan
  diam-diam ketika akun penandatangan kosong atau ganda.
- Tidak ada implementasi, dependency, test, formatter, build, atau gate yang
  dijalankan pada amandemen dokumentasi ini.

### 21 September 2026 — Perencanaan revisi laporan catatan layanan

- Kelayakan ditinjau terhadap PRD/SRS aktif, route, request, policy, service,
  modal detail, archive, pagination, serta jalur preview/CSV existing.
- Plan mengusulkan daftar per catatan kasus/konsultasi dengan tiga filter,
  pagination 10/25/50/100, urutan UI terbaru, urutan dokumen paling awal,
  preview web, serta unduhan Excel/Word.
- REP-01–REP-04, PRD, indeks, API contract, dan spec aktif telah diamendemen.
  PhpSpreadsheet tetap menjadi gate; implementasi dan testing belum dilakukan.
- Repository referensi menunjukkan preview rekap dan per-item dipisahkan;
  Excel memakai library XLSX, sedangkan Word memakai HTML `.doc`. Pola alur
  diadaptasi tanpa menyalin React/Tailwind atau aset referensi.
- Aturan markup ditambah agar tag Blade/HTML panjang memakai atribut multi-line
  dan struktur tag mudah diperiksa.
- Wireframe dikunci menjadi satu tombol `Cetak / Unduh Rekap` menuju preview
  terpadu. Modal dan preview satu layanan menambahkan Catatan setelah
  Penyelesaian tanpa mengekspos `internal_note` kasus.

### 20 September 2026 — Checkpoint 7C: cleanup skema dan gate akhir

- Migration forward-only menghapus tiga tabel dan dua belas kolom retired;
  consumer sementara serta fixture test diselaraskan ke skema final.
- SQLite dan MySQL 8.4 disposable lulus fresh, rollback, serta upgrade dengan
  15 kasus dan 15 konsultasi aktif tetap terjaga; database shared/production
  tidak disentuh.
- Focused security/privacy gate lulus 88 test/824 assertion. Full gate lulus
  454 test/3.540 assertion beserta Pint, checker frontend, build, Composer
  strict, dan diff-check.
- Review independen menemukan dua masalah indeks Important; keduanya ditutup
  dengan regression test tanpa temuan Critical atau Minor.
- PR #27 terintegrasi ke `cobasidebar` pada `6e16498`; branch sumber remote,
  branch lokal, dan worktree sudah dibersihkan. `main` tidak disentuh dan
  tidak ada dependency baru.

### 19 September 2026 — Checkpoint 7B: cleanup runtime lama

- Menghapus model, service, controller, request, route, view, relasi, ability,
  dan consumer laporan untuk event tindak lanjut, koordinasi, private note,
  serta resolve terpisah yang sudah retired.
- Klasifikasi tindak lanjut singular dan view detail Waka berbasis allowlist
  dipertahankan karena masih menjadi runtime aktif.
- Full gate lulus 453 test/3.524 assertion beserta Pint, checker frontend,
  build, Composer strict, dan diff-check; tidak ada migration atau perubahan
  dependency.
- PR #26 terintegrasi ke `cobasidebar`; branch sumber remote
  `revisi-sibk-3-2-7b-runtime` dihapus dan `main` tidak disentuh.

### 19 September 2026 — Checkpoint 7A: penyelarasan fitur

- Alur kasus/konsultasi, autosave lokal, akses baca Waka, laporan, dashboard,
  profil murid, consumer turunan, dan dummy seeder diselaraskan ke Revisi 3.2.
- Waka hanya membaca proyeksi allowlist, pembukaan detail diaudit, mutasi tetap
  ditolak, dan narasi layanan tidak masuk ekspor massal.
- Tiga lane Wave 2 direview terpisah lalu diintegrasikan tanpa overlap; lima
  kegagalan full gate legacy diperbaiki sebagai perubahan test-only.
- Final review menemukan 5 Important dan 7 Minor; satu fix wave menutup seluruh
  temuan dan scoped re-review menyatakan tidak ada blocker baru.
- Full gate final lulus 454 test/3.529 assertion, Pint, checker frontend,
  build, Composer strict, dan diff-check. Runtime/schema retired tetap tersedia
  sampai Checkpoint 7B/7C sesuai urutan aman.
- PR #25 terintegrasi ke `cobasidebar`; branch sumber remote
  `revisi-sibk-3-2` sudah dihapus dan `main` tidak disentuh.

### 16 September 2026 - Rilis baseline Ruang BK v1.1

- Histori `main` disambungkan ke `cobasidebar` melalui PR #20 tanpa mengubah
  tree aplikasi; README aktif dari `cobasidebar` dipertahankan.
- Cleanup whitespace lama melalui PR #21 membuat diff-check terhadap `main`
  lulus tanpa perubahan perilaku.
- Kandidat `a440bdf` lulus 454 test/3.498 assertion, Pint, checker frontend,
  build, Composer strict, dan diff-check.
- Review independen tidak menemukan temuan Critical, Important, atau Minor;
  `main` dipastikan menjadi ancestor kandidat sebelum rilis.
- PR #22 terintegrasi ke `main` pada `b59b839`; tree produksi identik dengan
  kandidat `cobasidebar` saat rilis.

### 16 September 2026 — Checkpoint 6C: laporan dan baseline praproduksi

- `ReportService` menjadi facade 58 baris; query pelanggaran, layanan, dan
  prestasi dipisah tanpa mengubah tujuh kontrak legacy atau output.
- Focused gate lulus 41 test/467 assertion; gate sensitif lulus 110 test/1.463
  assertion dan 35 test/418 assertion.
- Gate penuh lulus 454 test/3.498 assertion, Pint, checker frontend, build,
  Composer strict, diff-check, serta cache config/route/view.
- Review independen final tidak menemukan temuan Critical, Important, atau Minor.
- Tidak ada migration, perubahan route/controller/policy/dependency, atau
  adapter production. Runtime verifikasi memakai PHP 8.4.11.
- PR #18 terintegrasi ke `cobasidebar` pada `a1426b2`; branch sumber remote
  sudah dihapus.

### 16 September 2026 — Checkpoint 6B: pengaturan integrasi

- `IntegrationSettingService` menjadi facade kompatibel untuk resolver status,
  penyimpanan, uji koneksi, dan aktivasi; binding provider tidak berubah.
- Credential, transaksi, lock/fencing, deadline, audit, redaksi secret, serta
  fallback driver `unavailable` tetap dilindungi test perilaku.
- Focused gate pascarebase lulus 110 test/1.463 assertion; gate penuh pascarebase
  lulus 454 test/3.498 assertion, Pint, checker frontend, build, Composer strict,
  dan diff-check.
- PR #17 terintegrasi ke `cobasidebar` pada `ce85cb7`; branch sumber remote
  sudah dihapus.

### 16 September 2026 - Checkpoint 6A: refactor Dapodik

- `DapodikReconciliationService` menjadi facade tipis; preview, pencocokan,
  validasi apply, penerapan atomik, dan deactivation plan dipisahkan ke lima
  komponen tanpa mengubah kontrak publik.
- Focused gate lulus 52 test/353 assertion. Full gate lulus 454 test/3.498
  assertion, Pint, checker frontend, build, Composer strict, dan diff-check.
- Tidak ada migration, perubahan route/controller/policy/dependency, perubahan
  keluaran, atau adapter production. Runtime verifikasi memakai PHP 8.4.11.
- PR #16 terintegrasi ke `cobasidebar` pada `81e1587`; branch sumber remote
  sudah dihapus.

### 16 September 2026 — Checkpoint 5B Task 15

- Gate otomatis Checkpoint 5B lulus 454 test/3.498 assertion, Pint, checker
  frontend, build, Composer strict, audit dependency, cache, scan keamanan,
  SQLite, MySQL disposable, dan diff-check.
- QA pengguna melaporkan seluruh 17 skenario UAT awal serta uji ulang terbatas
  skenario 8–10 dan 15 pada tiga viewport berstatus `PASS`.
- Review ulang tidak menemukan blocker kode; Task 15 dan plan gabungan ditutup.

### 16 September 2026 — Checkpoint 5B Task 14

- Hitungan murid dashboard memakai ketersediaan layanan pada akhir periode;
  preselection murid di luar scope pada pembuatan kasus ditolak server-side.
- Test lintas permukaan membuktikan arsip tidak muncul pada daftar, dashboard,
  monitoring, dan laporan; histori serta daftar proses keluar Waka tetap ada.
- Focused gate lulus 71 test/565 assertion, Pint, checker frontend, scan query,
  dan diff-check.

### 16 September 2026 — Checkpoint 5B Task 13

- Invariant sumber kebenaran, unique/foreign key proses keluar, dan larangan
  state rekap/keluar duplikat dikunci oleh test skema.
- Enam tabel retired tidak dihapus; consumer runtime kosong selain konfigurasi
  driver Laravel yang tetap tersedia. Default queue contoh/fallback kini `sync`.
- Gate SQLite dan MySQL fresh/incremental lulus; database disposable dihapus.
  Focused gate lulus 12 test/77 assertion, Pint, dan diff-check.

### 16 September 2026 — Checkpoint 5B Task 12

- Pembuatan/reset akun kini menerbitkan password sementara satu kali tanpa
  menyimpan nilai plaintext pada audit atau model JSON.
- Akun wajib mengganti password sebelum membuka route operasional; reset
  memutus sesi target dan command tersembunyi memulihkan Admin IT tunggal.
- Focused gate lulus 29 test/296 assertion, Pint, checker frontend, audit route,
  scan password, dan diff-check.

### 15 September 2026 — Checkpoint 5B Task 11

- Satu proses keluar disimpan per murid dengan status `dalam_proses`, `batal`,
  atau `resmi_keluar`; pembukaan ulang memakai row dan tanggal awal yang sama.
- Hanya Guru BK terscope yang mencatat/mengubah dan hanya Koordinator yang
  memutuskan; Waka memperoleh daftar operasional read-only yang diaudit.
- `resmi_keluar` yang sudah efektif menghentikan layanan baru tanpa menghapus
  histori. Dapodik dan data persiapan tidak mengubah proses keluar.
- Focused gate lulus 102 test/735 assertion, Pint, checker frontend, dan diff-check.

### 15 September 2026 — Checkpoint 5A: penyederhanaan operasional

- Kontrak operasional diselaraskan, status layanan dipangkas menjadi empat,
  edit terminal beralasan dan arsip diterapkan, lalu fitur koreksi, notifikasi,
  riwayat, serta aktivitas audit UI dihentikan.
- Self-review tidak menemukan blocker material; regresi placeholder agregat
  status Waka diperbaiki pada sumber query.
- Gate penuh lulus 416 test/3.261 assertion, Pint, checker frontend, build,
  Composer strict, dan diff-check.

### 15 September 2026 — Checkpoint 5A Task 10

- Route, UI, controller, request, policy, model, service, dan test fitur koreksi,
  notifikasi, serta riwayat audit dipensiunkan; tabel lama tetap dipertahankan.
- Audit append-only tetap aktif, tetapi dashboard tidak lagi membaca atau
  menampilkan narasinya; panel kanan kini berisi konteks aman per role.
- Focused gate lulus 25 test/270 assertion, regresi area terkait 78 test/688
  assertion, checker frontend, Pint, dan diff-check.

### 15 September 2026 — Checkpoint 5A Task 9

- Status kasus/konsultasi disederhanakan menjadi empat; data legacy berstatus
  dibatalkan diarsipkan dan reference-nya dinonaktifkan.
- Owner dapat mengedit data selesai dengan alasan 10–500 karakter yang hanya
  disimpan pada audit append-only; owner juga dapat mengarsipkan record.
- Focused gate lulus 43 test/333 assertion, Pint, dan diff-check.

### 15 September 2026 — Pembagian Checkpoint 5

- Checkpoint 5A ditetapkan untuk Task 8–10.
- Checkpoint 5B ditetapkan untuk Task 11–15 dan dimulai setelah Checkpoint 5A
  terintegrasi ke `cobasidebar`.
- Pembagian hanya mengubah batas eksekusi dan review, bukan scope atau perilaku.

### 15 September 2026 — Checkpoint 4: penyederhanaan laporan

- Halaman laporan Guru BK/Koordinator disatukan menjadi tiga tab rekap aman;
  endpoint preview dan ekspor tujuh tipe legacy tetap tersedia.
- Scope, filter, pagination database, identitas tersamarkan, ekspor CSV aman,
  tampilan desktop/mobile, empty state, aksesibilitas, dan cetak tab aktif diterapkan.
- Gate lulus 418 test/3.293 assertion pada SQLite; focused MySQL disposable
  lulus 16 test/148 assertion; Pint, Composer strict, cache, checker frontend,
  build, diff-check, scan privasi, dan scan dependency lulus.
- UAT manual oleh `ui` melalui Chrome pada viewport 1440 × 900, 768 × 1024,
  dan 390 × 844 dinyatakan PASS tanpa temuan gagal; versi browser tidak dilaporkan.

### 15 September 2026 — Cleanup file trivial

- Menghapus fixture config tanpa consumer, empat aset tanpa referensi, test bawaan
  trivial, serta dua file publik kosong/percobaan.
- `laravel/pao` dipertahankan karena aktif merangkum output test; dua
  `Unavailable*Connector` dipertahankan sesuai keputusan.
- Gate lulus 401 test/3.138 assertion, Pint, checker frontend, build,
  Composer strict, dan diff-check.

### 15 September 2026 — Checkpoint 3: pengujian otorisasi umum

- Matriks umum menggantikan manual/CSV penelitian dan menunjuk test empat role,
  scope data, histori, privasi, serta ekspor.
- Lima test reguler ditambahkan sebelum delapan file command/seeder/katalog/
  verifier/test/manual/CSV penelitian dipensiunkan; policy dan Gate produk tetap.
- Audit 15 task memperjelas route legacy, migration forward-only, gate
  disposable, dan verifikasi CLI; seluruh 15 task masih belum selesai.
- Gate lulus 402 test/3.139 assertion, Pint, checker frontend, build,
  Composer strict, diff-check, dan tautan lokal enam dokumen.
- Review independen RBAC serta audit plan tidak menemukan blocker material.
- Runtime PHP 8.4.12; PHP 8.3 belum diuji langsung. Probe koneksi disposable
  pada petunjuk migration lulus tanpa menjalankan migration.
- Commit RBAC `9119b01`, audit `59606d4`; PR #10 ke `cobasidebar`.

### 14 September 2026 — Checkpoint 2: pembersihan baseline

- README, AGENTS, dan aturan aktif menunjuk `cobasidebar`, PRD/SRS v1.1, serta minimum PHP 8.3.
- Sembilan dokumen historis dikeluarkan setelah sepuluh hash sumber/arsip diverifikasi;
  development log aktif diringkas dan log asli tetap di arsip.
- 41 screenshot, satu workbook, dan analisis artikel RBAC dipensiunkan tanpa arsip;
  dapat dipulihkan lewat histori Git. Manual/CSV/kode penelitian tetap sampai Checkpoint 3.
- Gate lulus 411 test/3.148 assertion, focused 14 test/120 assertion, Pint,
  checker frontend, build, Composer strict, diff-check, dan pemeriksaan tautan lokal.
- Verifikasi berjalan di PHP 8.4.12; runtime PHP 8.3 belum diuji langsung.
- Tidak ada perubahan kode aplikasi, dependency, database, atau hak akses.

### 14 September 2026 — Checkpoint 1: fondasi arsip

- Repository privat `Aflahul/sibk-docs-archive` dibuat dengan snapshot commit `dcdd2e9`.
- Sepuluh dokumen historis disalin; SHA-256 sesuai sumber `b4310e1`.
- Clone terpisah tersedia di `D:\PPG 2026\SEMESTER 2\sibk-docs-archive`.
- Undangan akses tulis `ibnuahmadfauzi` dikirim; penerimaan masih menunggu.
- PR #8 di-merge ke `cobasidebar` pada `386cd3c`; 411 test/3.148 assertion dan checker frontend lulus.

### 14 September 2026 — Portal Waka

- Dashboard, Murid dengan Kasus, Monitoring Penanganan, dan Rekap Periode tersedia;
  Laporan Akhir masih placeholder sesuai keputusan produk.
- Proyeksi dan CSV mempertahankan privasi; Waka tidak memperoleh catatan konseling lengkap.
- Gate terakhir: 411 test/3.148 assertion, Pint, build, checker frontend, dan UAT responsif lulus.

### 11 September 2026 — Fondasi integrasi Fase A

- Task 0–12 selesai: konfigurasi terenkripsi, state machine, validasi/lock,
  persiapan data sementara, aktivasi operasional, pratinjau, dan apply atomik.
- Admission gate, prosedur deployment/rotasi credential, serta migration additive
  SQLite/MySQL disposable telah diverifikasi.
- Driver production Dapodik/e-Tatib tetap `unavailable`; adapter nyata belum dibuat.

### Agustus 2026 — Fondasi aplikasi

- Multi-role, otorisasi server, layanan kasus/konsultasi/prestasi, penugasan,
  laporan terscope, dan audit append-only diimplementasikan.
- Fitur yang dipensiunkan tetap mengikuti plan penyederhanaan aktif; ringkasan ini
  tidak menyatakan plan 15 task sudah selesai.

## Arsip historis

[Log lengkap sampai 14 September 2026](https://github.com/Aflahul/sibk-docs-archive/blob/main/testing/completed/development-log-through-2026-09-14.md)
dan plan selesai tersedia di repository privat arsip. Akses memerlukan izin.
