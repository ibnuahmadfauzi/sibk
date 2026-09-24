# Kelola Akun Ringkas Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) for tracking.

**Goal:** Kelola Akun memakai tabel ringkas dan modal tambah/edit, dengan pemilihan peran sementara seperti Penugasan Kelas dan sandi sementara yang tampil sekali di detail baris setelah Buat/Reset.

**Architecture:** Pertahankan `AccountService`, `TemporaryPasswordService`, Form Request, policy, dan route akun yang ada. Ubah penyajian Blade, gunakan satu modal akun untuk tambah/edit dan satu modal peran untuk mengirim seluruh pilihan peran melalui PATCH existing. Respons HTML Buat/Reset merender daftar akun langsung dengan hasil sandi sementara satu kali dan `Cache-Control: no-store, private`; GET biasa tidak pernah menerima sandi itu.

**Tech Stack:** Laravel/PHP 8.3, Blade, Bootstrap, JavaScript dashboard yang sudah ada, PHPUnit.

**Spec:** `docs/requirements/SRS_Aplikasi_BK_v1.1.md` ACC-01/ACC-02 dan AUTH-03/AUTH-06; `docs/api-contract.md` bagian akun; keputusan pengguna 25 September 2026 dan referensi visual halaman Penugasan Kelas/Laporan.

## Global Constraints

- Hanya Admin IT aktif dapat membuat, mengubah, dan mereset akun; reset akun sendiri tetap ditolak.
- Sandi tetap di-hash, sementara unik berlaku 24 jam, wajib diganti saat login, dan tidak muncul di GET, audit, HTML yang dapat di-cache, atau daftar akun berikutnya.
- Jangan menambah tabel, migration, endpoint baru, dependency, atau komponen UI generik. Pertahankan paginasi 20 akun.
- Status aktif/nonaktif tetap tersedia pada modal edit; jangan menghapus kemampuan ACC-01.
- Ikuti `AGENTS.md`: test terarah oleh agent; full suite/build dijalankan pengguna.

## Review Focus

- Refresh, Back, atau membuka URL daftar langsung setelah Buat/Reset tidak menampilkan ulang sandi sementara.
- Akun yang diubah perannya tidak boleh kehilangan seluruh peran; server memvalidasi minimal satu peran aktif.
- Admin tidak dapat mereset sandi sendiri; pengguna lain tidak mendapat endpoint atau nilai sandi sementara.
- Modal edit harus menampilkan nilai akun yang tepat, dan kesalahan validasi mengembalikan input tanpa mengubah akun lain.
- Daftar 20 akun dengan nama/peran panjang tetap terbaca di viewport sempit dan kontrol keyboard tetap dapat dipakai.

---

### Task 1: Tabel akun dan modal tambah/edit

**Files:**
- Modify: `resources/views/pages/admin/users/index.blade.php`.
- Modify: `resources/scss/app-dashboard.scss` hanya untuk aturan lokal Kelola Akun yang tidak tercakup komponen existing.
- Modify: `resources/js/app-dashboard.js` untuk mengisi modal edit dari tombol baris.
- Test: `tests/Feature/AccountManagementTest.php`.

- [ ] **Step 1:** Tulis test tampilan Admin IT: tombol `Tambah akun`, tabel dengan kolom `Nama`, `Peran`, `Sandi`, `Aksi` tanpa `No`, dan modal tambah/edit. Test pengguna lain tetap ditolak dari halaman.
- [ ] **Step 2:** Ganti form tambah besar dan kartu edit per akun dengan tabel. Tampilkan status Aktif/Nonaktif sebagai badge kecil di bawah nama. Di atas tabel letakkan tombol `Tambah akun`; tombol pensil pada tiap baris membuka modal yang sama berisi nama, email, peran, dan status akun.
- [ ] **Step 3:** Modal tambah mengirim `POST /admin/users`; modal edit mengirim `PATCH /admin/users/{user}` dengan Form Request existing. Gunakan `data-*` dari tombol baris untuk nilai awal, tanpa endpoint detail baru. Saat validasi gagal, buka lagi modal yang relevan dengan `old()` dan pesan kesalahan; jangan hilangkan isian pengguna.
- [ ] **Step 4:** Jalankan test terarah akun, `node --check resources/js/app-dashboard.js`, dan `git diff --check`. Pengguna menjalankan build frontend sesuai `AGENTS.md` sebelum pemeriksaan visual.

### Task 2: Peran cepat dengan pilihan sementara

**Files:**
- Modify: `resources/views/pages/admin/users/index.blade.php`.
- Modify: `resources/js/app-dashboard.js`.
- Test: `tests/Feature/AccountManagementTest.php`.

- [ ] **Step 1:** Tulis test bahwa PATCH `roles[]` mengganti daftar peran akun, audit tetap dibuat, pilihan kosong dan slug tak aktif ditolak, serta peran akun lain tidak berubah.
- [ ] **Step 2:** Kolom Peran menampilkan chip peran dan tombol `+` seperti Penugasan Kelas. Klik `+` atau chip membuka modal pemilihan peran untuk akun itu. Daftar peran berasal dari `$roles` aktif yang sudah diberikan controller; tombol pilihan dapat ditambah, lalu dibatalkan dengan klik chip terpilih.
- [ ] **Step 3:** Simpan perubahan hanya saat tombol `Simpan peran` ditekan. Modal mengirim semua slug terpilih sebagai `roles[]` ke PATCH existing; tombol nonaktif bila pilihan kosong. Tutup modal tanpa menyimpan membuang pilihan sementara. Di modal tambah/edit, gunakan pola pilihan yang sama dan validasi minimal satu peran.
- [ ] **Step 4:** Verifikasi satu akun dengan beberapa peran, pembatalan pilihan sebelum simpan, keyboard/fokus modal, serta viewport sempit. Jalankan test terarah yang sama.

### Task 3: Detail baris, sandi, dan reset

**Files:**
- Modify: `resources/views/pages/admin/users/index.blade.php`.
- Modify: `app/Http/Controllers/Admin/UserManagementController.php`.
- Modify: `app/Http/Controllers/Admin/UserPasswordResetController.php`.
- Modify: `docs/api-contract.md` bila respons HTML Buat/Reset berubah.
- Test: `tests/Feature/AccountManagementTest.php` dan test reset password existing.

- [ ] **Step 1:** Tulis test bahwa kaca pembesar membuka detail email dan status sandi; Buat/Reset mengembalikan HTML daftar dengan sandi sementara hanya di baris akun terkait, header `Cache-Control: no-store, private`, dan GET biasa tidak mengandung sandi. Test reset akun sendiri ditolak dan audit tidak memuat sandi.
- [ ] **Step 2:** Kolom Aksi memakai ikon kaca pembesar dengan perilaku detail baris seperti Laporan, serta ikon pensil untuk modal edit. Detail biasa menampilkan email, status `Sandi sementara aktif sampai ...` atau `Sandi sudah diganti`, dan waktu login terakhir bila ada. Jangan tampilkan hash ataupun sandi lama.
- [ ] **Step 3:** Kolom Sandi memakai tombol `Reset sandi`, kecuali akun Admin IT yang sedang masuk. `store` dan reset tetap pada controller serta route existing; masing-masing respons HTML merender Blade daftar dengan query akun/peran yang sama, satu `TemporaryPasswordResult`, dan header `no-store, private`. Buka detail baris terkait yang menampilkan sandi sementara, masa berlaku, dan petunjuk salin/sampaikan. Reload atau kunjungan GET berikutnya tidak membawa nilai itu. JSON API tetap memakai kontrak satu kali existing.
- [ ] **Step 4:** Jalankan test akun/reset terarah, syntax/lint file yang berubah, dan `git diff --check`. Tinjau agar tidak ada nilai sandi pada query string, session flash, audit, atau log. Commit terpisah dari implementasi tahun ajaran.

## Batas keputusan

Tidak ada `password default` tetap per akun. Sandi sementara berbeda pada setiap Buat/Reset dan hanya muncul sekali di detail baris respons tindakan itu. Akun yang sandinya sudah diganti hanya menampilkan status, bukan nilainya.
