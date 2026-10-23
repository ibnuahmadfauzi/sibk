# Ruang BK — Source rilis klien

Versi dan commit sumber tersedia pada `RELEASE.json`. Repo ini menerima rilis resmi, bukan tempat pengembangan harian. Tidak ada data murid, akun simulasi, kredensial, atau riwayat Git internal. Source ini masih memerlukan instalasi dependensi dan konfigurasi server; bukan paket server siap jalan.

## Instalasi

Buku panduan bergambar dalam format Word tersedia pada `docs/manualbook/Buku-Panduan-Ruang-BK.docx`. Petunjuk penggunaan, instalasi, dan pemulihan juga tersedia pada `docs/manualbook.md` dalam paket rilis. Bab 6 memuat petunjuk lengkap bagi petugas server; langkah di bawah menjadi ringkasan pemasangan.

Siapkan PHP 8.4.1 atau lebih baru dalam seri PHP 8 dengan `pdo_mysql` pada PHP CLI dan PHP-FPM, ekstensi cURL dengan libcurl >= 7.49, ekstensi GD untuk logo dan Excel, ekstensi lain yang dibutuhkan lockfile, Composer 2, MySQL, serta Node.js/npm yang kompatibel dengan Vite untuk build aset. Periksa CLI dengan `php --ri pdo_mysql` dan pastikan ekstensi yang sama aktif pada PHP-FPM. Arahkan document root hanya ke `public/`, gunakan HTTPS, dan beri proses PHP izin tulis pada `storage/` serta `bootstrap/cache/`.

Salin `.env.example` ke `.env`, lalu isi domain dan akses database khusus aplikasi. Pertahankan `APP_ENV=production` serta `APP_DEBUG=false`. Jangan commit `.env`.

Untuk instalasi pertama, buat kunci retensi dengan perintah berikut, lalu salin hasilnya ke `ALUMNI_RETENTION_KEY` pada `.env` sebelum membuat cache konfigurasi. Simpan kunci ini bersama backup rahasia aplikasi; gunakan kembali kunci yang sama saat pembaruan atau pemindahan server.

```sh
php -r "echo 'base64:'.base64_encode(random_bytes(32)),PHP_EOL;"
```

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
npm ci --ignore-scripts
npm run build
# Hanya instalasi pertama ketika APP_KEY masih kosong:
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan view:cache
```

Langkah ini khusus instalasi pertama pada database baru. `key:generate` hanya dijalankan ketika `APP_KEY` masih kosong. Seeder hanya mengisi peran dan referensi; tidak membuat akun atau data murid. Jangan gunakan `composer setup`: ikuti langkah eksplisit di atas. Manifest dan lockfile sumber dipertahankan berpasangan; alat pengembangan PHP tidak dipasang dengan `--no-dev`. Perintah pengujian internal dalam manifest tidak digunakan pada repo klien.

## Admin pertama

Pengelola server menjalankan `php artisan tinker`, mengganti nama/email contoh berikut, lalu menjalankan kode ini pada instalasi baru:

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

Sampaikan kata sandi sementara melalui saluran privat. Pengguna menggantinya saat login. Jika kedaluwarsa, jalankan `php artisan sibk:reset-admin-password` melalui console server. Jangan menyimpan output kata sandi pada tiket publik.

## Operasional dan pembaruan

Setelah konfigurasi, kunci, dan migration selesai, pada VPS Linux jalankan perintah berikut sekali sebagai akun aplikasi yang menjalankan PHP, bukan sebagai root:

```sh
php artisan sibk:pasang-scheduler
```

Perintah mendeteksi direktori aplikasi dan executable PHP yang dipakai, lalu memasang pemicu `schedule:run` setiap menit pada crontab akun tersebut. Keluaran dan error ditulis ke `storage/logs/scheduler.log`; pemasangan berhenti jika direktori atau file log yang sudah ada tidak dapat ditulis akun aplikasi. Entri cron lain dipertahankan. Mengulang perintah untuk instalasi dan PHP yang sama tidak menambah entri. Untuk melihat rencana tanpa memasang, gunakan `php artisan sibk:pasang-scheduler --dry-run`.

Server tetap harus menyediakan perintah `crontab`, mengizinkan akun aplikasi menggunakannya, dan menjalankan layanan cron. Perintah ini tidak memasang paket sistem atau mengaktifkan layanan cron, tidak mengubah crontab akun lain, dan tidak menjalankan sinkronisasi atau penghapusan saat pemasangan. Pastikan tidak ada pemicu aplikasi yang sama pada akun lain, timer sistem, atau panel; jangan menyunting cron bersamaan saat pemasangan. Jika ditemukan jadwal manual atau entri terkelola yang berbeda, perintah berhenti agar tidak menggandakan tugas atau menimpa entri lama.

Jika hosting membatasi akses `crontab`, pasang tugas melalui fasilitas penjadwalan panel hosting. PHP untuk tugas ini juga harus versi 8.4.1 atau lebih baru dalam seri PHP 8. Contoh cron manual pada VPS Linux, dengan lokasi aplikasi dan PHP disesuaikan dengan instalasi:

```cron
* * * * * cd /var/www/sibk && /usr/bin/php artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

Jalankan tugas sebagai akun aplikasi yang dapat menulis `storage/` dan `bootstrap/cache/`. Pertahankan `CACHE_STORE=database`; migration menyiapkan tabel cache dan penguncian untuk mencegah tugas tumpang tindih. Periksa daftar tugas dengan `php artisan schedule:list` dan hasil eksekusi pada log scheduler serta aplikasi. `schedule:list` hanya menampilkan jadwal, bukan bukti tugas sudah dijalankan otomatis. Tanpa tugas server ini, jadwal otomatis tidak berjalan meskipun aplikasi bisa dibuka.

Atur URL API Siswa/e-Tatib melalui Data Master, lalu pratinjau dan aktifkan pembaruan e-Tatib. Hari dan jam dapat diubah di tab e-Tatib (WIB); pengaturan UI memerlukan scheduler server tersebut agar berjalan. Adapter Dapodik langsung yang belum disahkan tetap `unavailable`. Uji login, hak akses, tahun ajaran, impor murid, layanan, laporan, dan scheduler sebelum dipakai sekolah.

Penghapusan alumni nonaktif secara bawaan (`ALUMNI_RETENTION_ENABLED=false`); pemasangan scheduler tidak mengaktifkannya dan jadwal e-Tatib tetap dapat berjalan. Pratinjau `php artisan sibk:hapus-alumni` tetap tersedia. Menu pencatatan kelulusan resmi belum ditampilkan pada versi ini; pertahankan penghapusan nonaktif sampai alur dan data kelulusan sekolah siap. Setelah memeriksa pratinjau, kebijakan penyimpanan, dan hasil uji pemulihan cadangan, pengelola dapat mengubah konfigurasi menjadi `true` lalu menjalankan `php artisan config:cache`. Tugas terjadwal kemudian memakai `--execute` pukul 02.00 WIB setiap hari untuk alumni yang memenuhi syarat. Untuk menghentikan eksekusi berikutnya, kembalikan nilai ke `false` dan perbarui cache konfigurasi; proses yang sudah berjalan tidak dibatalkan. Pertahankan kunci serta penanda retensi. Masa simpan backup dan unduhan lama diatur secara terpisah.

Pratinjau, cetak, dan Excel memerlukan satu semester: Juli–Desember atau Januari–Juni pada tahun ajaran terpilih. Rentang tanggal opsional harus berada di semester itu. Batas jumlah catatan diatur melalui `SIBK_REPORT_MAX_DOCUMENT_ROWS`; hasil yang melebihi batas meminta pengguna mempersempit periode atau kelas. Sesuaikan nilainya setelah menguji ukuran catatan dan memori PHP, lalu perbarui cache konfigurasi. Pembatasan dokumen tidak menghapus data yang tersimpan.

Untuk pembaruan, ikuti urutan maintenance, jeda cron, backup satu keadaan, pemasangan, pengujian privat, dan pembukaan akses pada [Bab 6 panduan server](docs/manualbook.md#64-pemeriksaan-dan-pembaruan). Uji rilis pada lingkungan validasi dahulu. Pertahankan `.env`, `APP_KEY`, `ALUMNI_RETENTION_KEY`, dan `storage/app`; jangan mengulang `key:generate` atau membuat ulang kunci retensi. Migrasi tertentu forward-only: jangan memakai `migrate:fresh` atau reset database. Pemulihan memakai source dan cadangan data yang sesuai. Logo dikelola Admin IT melalui Manajemen Web; `storage/app/private/branding` harus dapat ditulis proses PHP.

Lisensi dependensi tetap berlaku; pertahankan pemberitahuan lisensi paket Composer/npm yang dipasang. Ketentuan penyerahan aplikasi mengikuti perjanjian dengan pemilik aplikasi.
