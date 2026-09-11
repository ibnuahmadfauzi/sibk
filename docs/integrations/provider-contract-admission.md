# Admission Kontrak Provider dan Panduan Penerapan Integrasi

Dokumen ini adalah gate wajib sebelum driver production Dapodik atau e-Tatib selain `unavailable` boleh dirancang, diregistrasikan, diuji terhadap jaringan nyata, atau diaktifkan. Fase A sengaja dirilis dengan kedua driver tetap `unavailable`; konfigurasi dapat disimpan, tetapi tidak ada adapter HTTP production dan tidak ada request outbound.

Lembar bukti per provider harus diisi tanpa credential atau raw payload:

- [Discovery kontrak Dapodik](dapodik-contract-discovery.md)
- [Discovery kontrak e-Tatib](etatib-contract-discovery.md)

## 1. Keputusan admission

Admission dinilai per provider dan per versi kontrak. Statusnya hanya `ditolak` atau `disetujui`; jawaban kosong, asumsi, contoh dari provider lain, atau persetujuan sebagian berarti `ditolak`. Perubahan kontrak, endpoint, autentikasi, identitas sumber, atau semantik dataset mengulang admission dan menghasilkan `contract_version` baru.

Driver selain `unavailable` hanya boleh ditambahkan setelah seluruh bukti berikut tersedia dan disahkan:

- [ ] Dokumentasi autentikasi resmi, masa berlaku, penerbitan, rotasi, revokasi, serta contoh request tersanitasi.
- [ ] Base URL/origin exact, endpoint, method, content type, status sukses/error, dan versi kontrak resmi.
- [ ] Fixture sintetis yang mewakili sukses, malformed, partial, full-empty, oversized, identity mismatch, pagination, dan error; fixture tidak boleh berasal dari raw payload production.
- [ ] Daftar exact collection/field beserta JSON path, tipe, nullability, kewajiban, arti, mutability, dan pemetaan ke snapshot internal.
- [ ] Nama field, format, stabilitas, dan nilai identitas sekolah/sumber yang diharapkan untuk SMKN 1 Surabaya.
- [ ] Bukti dari pengelola provider bahwa credential bersifat least-privilege dan read-only, termasuk bukti bahwa endpoint/method tulis ditolak oleh credential tersebut.
- [ ] Model pagination, ukuran page, cursor/page/offset, urutan stabil, last-page marker, total count, serta perilaku bila dataset berubah selama pagination.
- [ ] Marker dan bukti completeness yang eksplisit serta semantik full snapshot, partial/delta, tombstone, record hilang, dan deletion/deactivation.
- [ ] Batas byte per response/page, total byte setelah dekompresi, record per page, total record, dan total page.
- [ ] Rate limit, header kuota, `Retry-After`, timeout koneksi/page/operasi, error yang boleh di-retry, jumlah retry, backoff, dan jitter.
- [ ] Batas concurrency, serialisasi pagination, backpressure, kebutuhan queue/lock renewal, dan bukti worst-case selesai di bawah hard operation deadline serta lease.
- [ ] Prosedur outage, eskalasi, pemulihan, penghentian sinkronisasi, dan jaminan data lama dipertahankan.
- [ ] Perilaku TLS, DNS/IP, Host/SNI, redirect, proxy, jaringan privat, perubahan alamat, dan origin yang disahkan.
- [ ] Mapping resmi menuju `DapodikSnapshot` atau `EtatibSnapshot`, termasuk provenance/revision serta field immutable/mutable.
- [ ] Validator executable dan matriks test yang membuktikan semua kegagalan ditolak sebelum mutasi.
- [ ] Persetujuan tertulis pemilik kontrak/provider dan Admin IT, lengkap dengan versi dokumen serta tanggal.

Artefak admission disimpan di media dokumentasi internal yang aksesnya terbatas. Jangan commit token, kata sandi, private key, cookie, authorization header, raw payload, atau data pribadi production.

## 2. Aturan adapter fase berikutnya

Adapter production dibuat dalam plan terpisah setelah admission disetujui. Implementasinya wajib memenuhi aturan berikut tanpa pengecualian diam-diam.

### Kontrak dan snapshot

- Gunakan exact collection dan field resmi. Unknown field boleh diabaikan sesuai kontrak, tetapi missing collection, tipe salah, nullability salah, marker tidak sah, atau revision yang tidak konsisten wajib gagal tertutup.
- Jangan memberi default `true` pada `is_full_snapshot`. Full marker dan provenance completeness harus eksplisit, berasal dari kontrak resmi, dan dapat diverifikasi executable.
- Validasi setiap page, keterurutan/cursor, total, versi kontrak, dan reported source identifier. Jawaban campuran, berubah di tengah pagination, duplikat identitas, atau berasal dari sekolah/sumber lain wajib ditolak seluruhnya.
- Hitung page count, record count, dan processed byte count tanpa menyimpan body. Batas diperiksa sebelum decode bila memungkinkan, selama pagination/dekompresi, dan kembali oleh validator sebelum transaksi.
- Mapping hanya menghasilkan field minimum snapshot internal. Raw body sukses maupun error tidak boleh disimpan, dicatat ke log/audit, dibawa exception, atau ditampilkan pada response.
- Probe baru sukses bila autentikasi, versi kontrak, schema minimum, completeness yang relevan, dan identitas sumber terbukti; status HTTP 2xx saja tidak cukup.
- Dapodik full snapshot tanpa collection tahun ajaran atau murid wajib ditolak. Collection ada tetapi bertipe salah juga wajib ditolak.
- e-Tatib full snapshot kosong hanya boleh diterima bila kontrak resmi memberi marker completeness dan total nol yang dapat diverifikasi. Tanpa keduanya, pertahankan mirror lama dan tandai kegagalan aman.
- Full snapshot hanya menerapkan deletion/deactivation yang secara eksplisit diizinkan kontrak. Partial/delta tidak boleh menonaktifkan data yang tidak ikut dikirim.

### Transport dan jaringan

- Base URL harus cocok dengan exact origin allowlist deployment. User-info, query, fragment, wildcard host, suffix match, port berbeda, redirect, atau origin drift ditolak.
- Validasi seluruh jawaban DNS tepat sebelum koneksi. Tolak metadata, link-local, multicast, unspecified, jawaban campuran, dan jawaban yang berubah; alamat private/loopback hanya boleh digunakan untuk origin exact dengan opt-in provider.
- Setelah validasi DNS, ikat koneksi ke alamat yang tervalidasi sambil mempertahankan Host dan SNI yang benar. Jangan membiarkan HTTP client melakukan resolve ulang ke alamat lain.
- Matikan redirect. Verifikasi TLS dan hostname tidak boleh dinonaktifkan. Proxy environment tidak dipercaya secara implisit; hanya proxy resmi yang dibuktikan kontrak dan deployment yang dapat dipakai.
- Terapkan batas timeout, retry/backoff, rate limit, concurrency, backpressure, response/page bytes, total bytes/records/pages, hard deadline, dan queue sesuai nilai yang disahkan—bukan nilai tebakan.

### Pengujian dan observabilitas aman

- Test adapter wajib memanggil `Http::preventStrayRequests()` dan hanya memakai fake terhadap exact request yang diharapkan. Test tidak boleh dapat mencapai jaringan nyata.
- Uji happy path dan setiap kondisi penolakan pada checklist admission, termasuk pagination berubah, DNS berubah, TLS invalid, proxy tak tepercaya, response terlalu besar, source identity mismatch, full marker hilang, dan batas operasi.
- Gunakan kode hasil aman yang sudah didaftarkan aplikasi. Log dan audit hanya memuat metadata aman seperti provider, version, origin, count, status, dan digest; tidak pernah body atau credential.
- Operasi memakai lock/fencing provider yang sama dan memeriksa kembali configuration version, `driver_id`, `adapter_version`, `contract_version`, endpoint-policy digest, deadline, serta target sebelum setiap mutasi.
- Kegagalan fetch, validasi, audit, atau fencing mempertahankan data lama. Dapodik tetap melalui pratinjau dan konfirmasi Admin IT; tidak ada write-back ke Dapodik maupun e-Tatib.

## 3. Deployment Fase A

### 3.1 Konfigurasi awal

Gunakan secret manager atau konfigurasi environment deployment; jangan commit `.env`. Nilai driver Fase A wajib tetap:

```env
SIBK_DAPODIK_DRIVER=unavailable
SIBK_DAPODIK_ALLOWED_ORIGINS=https://origin-dapodik-resmi.example:443
SIBK_DAPODIK_ALLOW_PRIVATE_NETWORKS=false
SIBK_ETATIB_DRIVER=unavailable
SIBK_ETATIB_ALLOWED_ORIGINS=https://origin-etatib-resmi.example:443
SIBK_ETATIB_ALLOW_PRIVATE_NETWORKS=false
```

Ganti origin contoh dengan origin resmi termasuk scheme dan effective port. Satu provider dapat memiliki beberapa origin yang dipisahkan koma. Jangan menambahkan path, query, fragment, wildcard, user-info, atau origin cadangan yang belum disahkan. Aktifkan `ALLOW_PRIVATE_NETWORKS=true` hanya bila origin exact itu memang berada di jaringan privat, kebutuhan telah disahkan, dan jalur firewall keluar dibatasi ke tujuan tersebut.

Setelah environment dan dependency production siap, jalankan migration forward-only dan cache konfigurasi:

```bash
php artisan migrate --force
php artisan config:cache
php artisan view:cache
```

Jangan memakai `migrate:fresh`, rollback, reset, atau SQL destruktif pada database shared/production. Simpan konfigurasi setiap provider melalui PG-501 menggunakan akun Admin IT aktif dan step-up kata sandi. Expected source identifier boleh dilengkapi, tetapi state tetap `blocked` ketika driver `unavailable`; konfigurasi parsial tanpa expected source identifier tetap `unconfigured`. Jangan mencoba mengaktifkan koneksi sebelum adapter resmi lolos admission dan release gate terpisah.

### 3.2 Pemeriksaan keamanan deployment

Sebelum membuka akses pengguna, verifikasi:

- kedua driver efektif bernilai `unavailable` setelah `config:cache`;
- origin deployment sama persis dengan dokumen admission dan private-network opt-in tidak lebih luas dari kebutuhan;
- halaman serta aksi konfigurasi hanya dapat diakses Admin IT aktif dan memakai `Cache-Control: no-store`;
- security headers, CSRF, middleware akun aktif, current-password step-up, dan rate limit uji koneksi tetap aktif;
- credential hanya tampil sebagai indikator boolean, ciphertext database bukan plaintext, field secret tidak masuk session/old input, audit, application log, exception, HTML, atau JSON;
- simpan/uji/aktivasi dengan driver `unavailable` menampilkan `Adapter belum tersedia` atau kode aman `adapter_unavailable`, tombol sinkronisasi disabled, dan direct POST gagal aman tanpa mutasi;
- audit append-only mencatat perubahan konfigurasi menggunakan metadata tersanitasi;
- backup, pemulihan, monitoring, dan kontak outage sudah ditetapkan sebelum jadwal operasional.

### 3.3 Rotasi `APP_KEY`

`APP_KEY` mengenkripsi credential provider. Rotasi dilakukan dalam maintenance window oleh personel berwenang:

1. Simpan key aktif dan key baru hanya di secret manager; jangan menyalinnya ke tiket, chat, command history, log, atau repository.
2. Tetapkan key baru sebagai `APP_KEY` dan masukkan key lama ke `APP_PREVIOUS_KEYS` (comma-separated bila lebih dari satu), lalu jalankan `php artisan config:cache`.
3. Verifikasi aplikasi masih dapat membaca indikator credential. Ciphertext yang tidak dapat dibaca harus membuat integrasi `blocked`; jangan menghapus atau menimpa nilainya sebelum investigasi.
4. Melalui PG-501, ganti setiap credential provider agar disimpan ulang dengan key aktif. Lakukan uji/aktivasi hanya bila adapter kelak sudah admitted; pada Fase A state tetap `blocked`.
5. Setelah seluruh ciphertext relevan ditulis ulang, sesi lama ditangani sesuai prosedur, backup yang memerlukan key lama diperhitungkan, dan verifikasi pemulihan lulus, hapus key lama dari `APP_PREVIOUS_KEYS`, cache ulang konfigurasi, lalu verifikasi kembali.

Jika key diduga bocor, rotasi juga credential di sistem sumber karena key yang bocor dapat membuka ciphertext dari backup/database yang terekspos.

### 3.4 Rotasi atau revokasi credential provider

1. Terbitkan credential pengganti dengan scope least-privilege/read-only dan buktikan endpoint tulis ditolak.
2. Simpan credential baru melalui PG-501; jangan memasukkannya ke dokumentasi, fixture, shell argument, atau log.
3. Setelah adapter admitted, uji identitas sumber dan contract version, aktifkan konfigurasi current, lalu verifikasi satu operasi aman. Pada Fase A langkah uji/aktivasi berhenti dengan `Adapter belum tersedia`.
4. Cabut credential lama di provider dan periksa log audit metadata untuk memastikan versi konfigurasi yang benar.
5. Jika ada dugaan kompromi, nonaktifkan koneksi, cabut credential di provider segera, investigasi akses, dan pertahankan cache lama sampai credential pengganti lolos verifikasi.

## 4. Panduan operasional ketika Dapodik terlambat

Alur ini dapat dipakai selama 2–3 bulan keterlambatan tanpa menunggu adapter production:

1. **Admin IT menyiapkan tahun ajaran.** Buka Data Master, buat tahun ajaran dari kalender pendidikan, SK, atau dasar resmi sekolah. Tahun baru selalu belum aktif dan ditandai `Sementara`.
2. **Admin IT mengimpor daftar.** Gunakan CSV UTF-8 maksimum 2 MiB dengan header exact `nisn,nama,rombel`, NISN 10 digit, dan paling banyak 5.000 baris. Periksa ringkasan; berkas mentah tidak disimpan dan baris yang tidak tercantum tidak dinonaktifkan otomatis.
3. **Koordinator BK melengkapi penugasan.** Buka Pengelolaan Penugasan, periksa daftar kekurangan, lalu pastikan setiap rombel memiliki tepat satu Guru BK yang mencakup tanggal mulai tahun ajaran.
4. **Koordinator BK mengaktifkan operasional.** Aktivasi baru berhasil setelah periode, rombel, murid, dan penugasan lengkap. Aktivasi menutup konteks operasional lama tanpa menghapus histori dan tidak mengubah asal data.
5. **Guru BK memakai data sesuai scope.** Murid sementara hanya terlihat oleh Guru BK yang mendapat kelas pada tahun aktif. Daftar, profil, dan form tetap menampilkan penanda `Sementara`.

Ketika adapter Dapodik resmi kelak tersedia dan lolos admission:

1. Admin IT menyimpan konfigurasi, menjalankan Uji Koneksi, dan mengaktifkannya hanya setelah identitas sekolah serta kontrak cocok.
2. Tarik Dapodik membentuk pratinjau; langkah ini belum mengubah cache operasional.
3. Periksa item cocok, baru, berubah, perlu pemetaan, konflik, dan rencana penonaktifan. Pencocokan murid otomatis hanya berdasarkan NISN exact; jangan memakai nama sebagai identitas.
4. Selesaikan pemetaan yang diizinkan dan tahan konflik untuk investigasi. Terapkan hanya pratinjau current yang lengkap.
5. Apply mempertahankan ID internal, penugasan, kasus, konsultasi, tindak lanjut, prestasi, dan histori BK. Nilai aktif tahun ajaran tetap keputusan Koordinator BK, bukan provider.

## 5. Release gate

Setiap release fondasi/adapter menjalankan perintah berikut dalam urutan ini dan mencatat hasilnya tanpa secret:

```bash
php artisan test
php vendor/bin/pint --test
php artisan config:cache
php artisan view:cache
npm run check:frontend
npm run build
git diff --check
php artisan config:clear
php artisan view:clear
```

Selain itu, jalankan `php artisan migrate --force` secara additive pada SQLite test dan database MySQL disposable yang nama serta kepemilikannya telah diverifikasi. Jangan gunakan shared/production database untuk gate. Release ditolak bila satu perintah gagal, driver production berubah dari `unavailable` pada Fase A, bukti admission belum lengkap, pemeriksaan secret gagal, atau manual acceptance belum memiliki bukti.
