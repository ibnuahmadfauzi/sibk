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
