# Implement Ruang BK Backend Feature

Description: Alur kerja terstandar implementasi modul/fitur backend Ruang BK.

## Input
Nama modul / entitas (misal: Kasus, Penugasan, Murid, Konsultasi, Koreksi, Laporan).

## Steps

1. **Baca Kebutuhan Spesifik:**
   - Periksa Requirement ID terkait di `docs/requirements/SRS_Aplikasi_BK_v1.0.md` dan `docs/requirements/PRD_Aplikasi_BK_v1.0.md`.
   - Periksa kebutuhan audit (AUD-01), otorisasi (AUTH-01 s.d. AUTH-07), dan retensi (NFR-08).

2. **Skema Database & Migrasi:**
   - Buat file migrasi dengan Foreign Key yang tepat, indeks pencarian, dan `$table->softDeletes()`.
   - Gunakan FK ke `references` untuk status/kategori (jangan hardcode enum).
   - Jalankan `php artisan migrate`.

3. **Eloquent Model & Scopes:**
   - Terapkan `declare(strict_types=1);` dan PHP 8.3 type hinting.
   - Definisikan `$fillable`, `$casts`, relasi Eloquent (`belongsTo`, `hasMany`, dll.).
   - Tambahkan Query Scope (misal: `accessibleBy(User $user)`).

4. **Policy & Otorisasi:**
   - Buat/perbarui Policy di `app/Policies/` sesuai matriks hak akses.
   - Pastikan perlindungan multi-role (Koordinator vs Guru BK) dan pemisahan hak teknis Admin IT.

5. **Service / Action Layer:**
   - Tempatkan logika bisnis di `app/Services/`.
   - Gunakan transaksi `DB::transaction` untuk operasi multi-tabel.
   - Catat jejak audit otomatis via Model Observer atau Service.

6. **Form Request & Controller:**
   - Buat Form Request (`app/Http/Requests`) untuk validasi input.
   - Buat Thin Controller (`app/Http/Controllers`) yang memanggil Service dan me-return View / JSON.

7. **Integrasi Blade View:**
   - Hubungkan Controller dengan file view Blade di `resources/views/pages/`.
   - Ganti mock data statis dengan data dinamis dari Controller.
   - Tambahkan token `@csrf`, `old()`, pesan validasi `$errors`, dan flash alert.

8. **Verifikasi & Quality Gate:**
   - Jalankan test: `php artisan test`.
   - Buka route di browser dan verifikasi integrasi end-to-end.
   - Catat log progres di `docs/development-log.md`.
