# SIBK — Sistem Informasi Bimbingan dan Konseling

Repository ini digunakan untuk pengembangan **SIBK (Sistem Informasi Bimbingan dan Konseling)**.

Dokumen ini berisi panduan dasar untuk melakukan setup repository, berpindah ke branch `development`, serta mengirim perubahan kode ke repository.

---

## 📋 Persyaratan

Sebelum mulai melakukan pengembangan, pastikan perangkat sudah memiliki:

- PHP versi **8.3 atau lebih baru**
- Composer
- Git

### Cek versi PHP

```bash
php -v
```

Pastikan versi PHP yang digunakan adalah **8.3 atau lebih baru**.

### Cek Composer

```bash
composer -V
```

Jika kedua perintah tersebut dapat dijalankan tanpa error, berarti environment sudah siap digunakan.

---

# 🚀 Memulai Project

## 1. Clone Repository

Clone repository SIBK menggunakan perintah berikut:

```bash
git clone https://github.com/ibnuahmadfauzi/sibk.git
```

Setelah proses clone selesai, masuk ke folder project:

```bash
cd sibk
```

---

## 2. Melihat Branch Repository

Sebelum mulai bekerja, pastikan informasi branch dari repository sudah diperbarui:

```bash
git fetch -a
```

Kemudian lihat seluruh branch yang tersedia:

```bash
git branch -a
```

Biasanya akan terlihat beberapa branch seperti:

```text
* main
  remotes/origin/main
  remotes/origin/development
```

---

## 3. Pindah ke Branch Development

Seluruh proses pengembangan dilakukan pada branch:

```text
development
```

Pindah ke branch tersebut dengan perintah:

```bash
git checkout development
```

Kemudian pastikan posisi branch saat ini:

```bash
git branch
```

Jika berhasil, akan muncul:

```text
* development
  main
```

Tanda `*` menunjukkan branch yang sedang aktif.

---

# ⚠️ Aturan Penggunaan Branch

## Jangan melakukan pekerjaan langsung di `main`

Branch `main` digunakan sebagai **branch utama dan backup** project.

Untuk pekerjaan sehari-hari, gunakan:

```text
development
```

### Jangan melakukan push ke:

```text
main
```

### Push perubahan hanya ke:

```text
development
```

> **Penting:** Jangan melakukan `git push origin main`.

---

# 💻 Workflow Pengembangan

Setelah selesai mengerjakan fitur atau melakukan perubahan kode, lakukan proses berikut.

## 1. Periksa perubahan

Sebelum melakukan commit, periksa file yang berubah:

```bash
git status
```

---

## 2. Tambahkan perubahan

Tambahkan perubahan ke staging area:

```bash
git add .
```

---

## 3. Commit perubahan

Simpan perubahan dengan commit:

```bash
git commit -m "isi pekerjaan"
```

Contoh:

```bash
git commit -m "menambahkan halaman dashboard"
```

Contoh lainnya:

```bash
git commit -m "memperbaiki tampilan responsive dashboard"
```

Gunakan pesan commit yang **jelas dan menggambarkan pekerjaan yang dilakukan**.

---

## 4. Push ke Branch Development

Setelah commit selesai, kirim perubahan ke repository:

```bash
git push origin development
```

**Pastikan branch yang digunakan adalah `development`.**

---

# 🔄 Workflow Singkat

Setiap kali selesai mengerjakan perubahan, gunakan alur berikut:

```bash
git status

git add .

git commit -m "isi pekerjaan"

git push origin development
```

Contoh:

```bash
git status

git add .

git commit -m "menambahkan halaman dashboard BK"

git push origin development
```

---

# 🔐 Aturan Penting untuk Tim

Agar repository tetap aman dan tidak terjadi konflik, ikuti aturan berikut.

### ✅ Yang diperbolehkan

- Bekerja pada branch `development`
- Melakukan `git pull` sebelum mulai bekerja
- Melakukan commit secara berkala
- Push perubahan ke `development`
- Menggunakan pesan commit yang jelas

### ❌ Yang tidak diperbolehkan

- Jangan coding langsung di `main`
- Jangan melakukan `push` ke `main`
- Jangan menghapus branch `main`
- Jangan melakukan force push tanpa koordinasi dengan tim
- Jangan menggunakan commit message yang tidak jelas seperti `update`, `fix`, atau `coba`

---

# 📌 Ringkasan Perintah Git

| Perintah                      | Fungsi                                   |
| ----------------------------- | ---------------------------------------- |
| `git clone`                   | Mengambil repository ke komputer         |
| `cd sibk`                     | Masuk ke folder project                  |
| `git fetch -a`                | Memperbarui informasi branch dari remote |
| `git branch -a`               | Melihat seluruh branch                   |
| `git checkout development`    | Berpindah ke branch development          |
| `git status`                  | Melihat status perubahan                 |
| `git add .`                   | Menambahkan perubahan ke staging         |
| `git commit -m "..."`         | Menyimpan perubahan dalam commit         |
| `git push origin development` | Mengirim perubahan ke branch development |

---

# 🌿 Struktur Branch

Untuk saat ini, repository menggunakan dua branch utama:

```text
main
│
└── development
```

### `main`

Digunakan sebagai:

- branch utama;
- versi stabil;
- backup project.

### `development`

Digunakan untuk:

- pengembangan fitur;
- perbaikan bug;
- testing;
- integrasi pekerjaan anggota tim.

---

# 🎯 Prinsip Pengembangan

> **Kerjakan di `development`, jangan di `main`.**

Setiap anggota tim bertanggung jawab untuk memastikan perubahan yang dikirim tidak langsung mengganggu branch `main`.

Alur kerja yang digunakan:

```text
Clone Repository
       ↓
Masuk ke Folder Project
       ↓
Checkout development
       ↓
     Coding
       ↓
     Testing
       ↓
   git status
       ↓
    git add .
       ↓
git commit -m "..."
       ↓
git push origin development
```

---

---

# ⚙️ Menjalankan Project Laravel

Setelah repository berhasil di-clone dan sudah berada pada branch `development`, lakukan beberapa langkah berikut sebelum menjalankan aplikasi.

## 1. Install Dependency Laravel

Project Laravel membutuhkan dependency yang dikelola oleh Composer.

Jalankan:

```bash
composer install
```

Perintah ini akan menginstall seluruh dependency yang tercantum di file `composer.json`.

> Gunakan `composer install`, bukan `composer update`, ketika pertama kali menjalankan project hasil clone agar versi dependency mengikuti `composer.lock`.

---

## 2. Membuat File `.env`

File `.env` berisi konfigurasi lokal project, seperti koneksi database dan konfigurasi aplikasi.

Jika file `.env` belum tersedia, salin file `.env.example`:

### Windows

```bash
copy .env.example .env
```

### Linux / macOS

```bash
cp .env.example .env
```

Setelah itu, buka file:

```text
.env
```

dan sesuaikan konfigurasi dengan environment masing-masing.

Contoh konfigurasi database:

```env
APP_NAME="SIBK"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sibk
DB_USERNAME=root
DB_PASSWORD=
```

> **Catatan:** Jangan mengunggah file `.env` ke repository karena file tersebut dapat berisi konfigurasi dan informasi sensitif.

---

## 3. Generate Application Key

Setelah file `.env` tersedia, generate application key dengan:

```bash
php artisan key:generate
```

Jika berhasil, Laravel akan mengisi nilai `APP_KEY` pada file `.env`.

---

## 4. Siapkan Database

Buat database baru sesuai dengan nama yang ditulis pada `.env`.

Contoh:

```env
DB_DATABASE=sibk
```

Maka buat database dengan nama:

```text
sibk
```

Pastikan MySQL atau database server sudah berjalan.

---

## 5. Jalankan Migration

Setelah database siap, jalankan migration:

```bash
php artisan migrate
```

Jika project memiliki seeder dan membutuhkan data awal, jalankan:

```bash
php artisan db:seed
```

Atau jika ingin menjalankan migration sekaligus seeder:

```bash
php artisan migrate --seed
```

> Jalankan `migrate:fresh` hanya jika memang diperlukan karena perintah tersebut akan menghapus seluruh tabel yang sudah ada.

---

## 6. Install Dependency Frontend

Jika project menggunakan Vite atau dependency frontend dari `package.json`, install dependency Node.js dengan:

```bash
npm install
```

Kemudian jalankan development server:

```bash
npm run dev
```

Biarkan terminal ini tetap berjalan selama proses development jika aplikasi membutuhkan Vite.

---

## 7. Buat Storage Link

Jika aplikasi menggunakan penyimpanan file pada Laravel Storage, jalankan:

```bash
php artisan storage:link
```

Perintah ini membuat symbolic link dari:

```text
storage/app/public
```

ke:

```text
public/storage
```

---

## 8. Jalankan Laravel Development Server

Jalankan aplikasi dengan:

```bash
php artisan serve
```

Secara default aplikasi dapat diakses melalui:

```text
http://127.0.0.1:8000
```

Jika menggunakan Vite, biasanya jalankan dua terminal:

### Terminal 1 — Laravel

```bash
php artisan serve
```

### Terminal 2 — Vite

```bash
npm run dev
```

---

# 🚀 Urutan Setup Pertama Kali

Jika project baru saja di-clone, urutan yang direkomendasikan adalah:

```bash
git clone https://github.com/ibnuahmadfauzi/sibk.git

cd sibk

git fetch -a

git checkout development

composer install

copy .env.example .env

php artisan key:generate

php artisan migrate --seed

npm install

php artisan storage:link

php artisan serve
```

Untuk Linux/macOS, gunakan:

```bash
cp .env.example .env
```

sebagai pengganti:

```bash
copy .env.example .env
```

Jika project menggunakan Vite, buka terminal baru dan jalankan:

```bash
npm run dev
```

---

# 🔁 Saat Mulai Bekerja Setiap Hari

Setelah project sudah pernah disiapkan, tidak perlu mengulangi seluruh proses setup.

Sebelum mulai coding, pastikan berada di branch `development` dan ambil perubahan terbaru:

```bash
git checkout development

git pull origin development
```

Setelah selesai mengerjakan perubahan:

```bash
git status

git add .

git commit -m "isi pekerjaan"

git push origin development
```

Contoh:

```bash
git add .

git commit -m "menambahkan halaman dashboard BK"

git push origin development
```

---

# 🧭 Alur Lengkap Project

```text
Clone Repository
       ↓
Masuk ke Folder Project
       ↓
Checkout development
       ↓
composer install
       ↓
Buat dan Edit .env
       ↓
php artisan key:generate
       ↓
Siapkan Database
       ↓
php artisan migrate --seed
       ↓
npm install
       ↓
php artisan storage:link
       ↓
php artisan serve
       ↓
    Coding
       ↓
    Testing
       ↓
   git status
       ↓
    git add .
       ↓
git commit -m "..."
       ↓
git push origin development
```

## 👥 Untuk Anggota Tim Baru

Jika baru pertama kali mendapatkan project, ikuti langkah berikut:

```bash
git clone https://github.com/ibnuahmadfauzi/sibk.git

cd sibk

git fetch -a

git checkout development
```

Setelah itu, Anda sudah berada pada branch yang digunakan untuk pengembangan.

> **Ingat:** semua perubahan hasil pekerjaan dikirim ke:
>
> ```bash
> git push origin development
> ```
>
> **Jangan melakukan `git push origin main`.**
