# Ruang BK — Source rilis klien

Versi dan commit sumber tersedia pada `RELEASE.json`. Repo ini menerima rilis resmi, bukan tempat pengembangan harian. Tidak ada data murid, akun simulasi, kredensial, atau riwayat Git internal. Source ini masih memerlukan instalasi dependensi dan konfigurasi server; bukan paket server siap jalan.

## Instalasi

Siapkan PHP sesuai `composer.json` dan ekstensi yang dibutuhkan lockfile, Composer 2, MySQL, serta Node.js/npm yang kompatibel dengan Vite untuk build aset. Arahkan document root hanya ke `public/`, gunakan HTTPS, dan beri proses PHP izin tulis pada `storage/` serta `bootstrap/cache/`.

Salin `.env.example` ke `.env`, lalu isi domain dan akses database khusus aplikasi. Pertahankan `APP_ENV=production` serta `APP_DEBUG=false`. Jangan commit `.env`.

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
npm ci --ignore-scripts
npm run build
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan view:cache
```

Langkah ini khusus instalasi pertama pada database baru. Seeder hanya mengisi peran dan referensi; tidak membuat akun atau data murid. Jangan gunakan `composer setup`: ikuti langkah eksplisit di atas. Manifest dan lockfile sumber dipertahankan berpasangan; alat pengembangan PHP tidak dipasang dengan `--no-dev`. Perintah pengujian internal dalam manifest tidak digunakan pada repo klien.

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

Jadwalkan `php artisan schedule:run` setiap menit. Konfigurasikan integrasi melalui aplikasi; adapter yang belum disahkan tetap `unavailable`. Uji login, hak akses, tahun ajaran, impor murid, layanan, laporan, dan scheduler sebelum dipakai sekolah.

Sebelum pembaruan, cadangkan database, `.env` termasuk `APP_KEY`, dan unggahan. Uji rilis pada lingkungan validasi dahulu. Pasang dependensi sesuai lockfile, bangun aset, jalankan migrasi dan perbarui cache. Pertahankan `.env`, `APP_KEY`, dan unggahan lama; jangan mengulang `key:generate`. Migrasi tertentu forward-only: jangan memakai `migrate:fresh` atau reset database. Pemulihan memakai source dan cadangan data yang sesuai.

Lisensi dependensi tetap berlaku; pertahankan pemberitahuan lisensi paket Composer/npm yang dipasang. Ketentuan penyerahan aplikasi mengikuti perjanjian dengan pemilik aplikasi.
