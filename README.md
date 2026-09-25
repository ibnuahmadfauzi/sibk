# Ruang BK / SIBK

Aplikasi layanan Bimbingan dan Konseling untuk SMK Negeri 1 Surabaya.

## Branch dan status

- `cobasidebar`: baseline pengembangan aktif.
- `main`: versi stabil untuk produksi; tidak dipakai untuk pekerjaan harian.
- Kerjakan perubahan di feature branch, lalu buat Pull Request ke `cobasidebar`.
- Jangan force push, menghapus `main`, atau reset database bersama/produksi.

Backend dan frontend tersedia. Penyederhanaan fitur masih berlangsung.
Pekerjaan aktif ditentukan dari instruksi/issue, branch/worktree, dan plan/spec
yang relevan. Fondasi integrasi Dapodik dan e-Tatib sudah selesai, tetapi adapter
production belum tersedia.

## Persyaratan

- PHP 8.3 atau lebih baru, Composer, dan Git.
- Node.js/npm sesuai persyaratan Vite dalam `package-lock.json`.
- MySQL atau SQLite lokal yang dikonfigurasi pada `.env`.

Periksa versi: `php -v`, `composer -V`, `node -v`, dan `npm -v`.
Gunakan versi dependency yang terkunci; jangan menjalankan update tanpa kebutuhan.

## Setup pertama

```bash
git clone https://github.com/ibnuahmadfauzi/sibk.git
cd sibk
git switch cobasidebar
composer install
```

Salin `.env.example` hanya bila `.env` belum ada:

- PowerShell: `Copy-Item -LiteralPath .env.example -Destination .env`
- Linux/macOS: `cp .env.example .env`

Sesuaikan koneksi database lokal pada `.env`. Untuk MySQL, buat database baru
khusus pengembangan. Jangan memakai database produksi untuk mencoba migration
atau seeder. File `.env` berisi rahasia dan tidak boleh masuk Git.

```bash
php artisan key:generate
php artisan migrate
npm ci
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`. Untuk mengubah frontend, jalankan `npm run dev`
di terminal kedua. Di Windows, gunakan `npm.cmd` bila PowerShell memblokir
`npm.ps1`.

Seeder uji API hanya untuk local/testing dan memerlukan
`SIBK_SEED_ACCOUNT_PASSWORD` pada `.env`. `php artisan db:seed` membuat
1 Admin IT (`admin.api@ruangbk.test`), 6 Guru BK, 1 Koordinator BK, dan
1 Waka Kesiswaan. Murid, rombel, tahun ajaran, dan layanan tidak diisi;
Admin IT membuat tahun ajaran dan memasukkan tautan API Siswa di Data Master.
Jalankan pada database lokal yang telah diperiksa. Jangan menjalankan
`migrate:fresh` atau reset pada database bersama.

## Alur kerja tim

Sebelum mulai, baca [AGENTS](AGENTS.md), periksa branch dan status repository,
serta pertahankan perubahan yang sudah ada. Jika pekerjaan memiliki plan/spec
aktif, baca hanya bagian yang relevan dengan task yang sedang dikerjakan.

Contoh untuk checkout biasa yang bersih:

```bash
git switch cobasidebar
git pull --ff-only origin cobasidebar
git switch -c perbaikan-nama-pekerjaan
```

Setelah mengerjakan perubahan:

```bash
git status
git diff --check
git add path/file-yang-diubah
git commit -m "docs: jelaskan perubahan dengan kalimat sederhana"
git push -u origin perbaikan-nama-pekerjaan
```

Buat PR dengan target `cobasidebar`. Gunakan pesan commit Bahasa Indonesia yang
mudah dipahami. Jangan langsung push ke `main`; kesiapan produksi ditinjau terpisah.

Setelah PR berstatus `MERGED`, hapus branch checkpoint/fitur dari GitHub bila
tidak ada commit baru atau PR terbuka yang belum digabung. Jangan hapus
`cobasidebar`, `main`, atau branch yang belum di-merge. Branch lokal dan worktree
yang masih dipakai tidak harus dihapus.

Pemilik/admin repository dapat mengaktifkan **Settings → General → Pull Requests
→ Automatically delete head branches** untuk merge berikutnya. Jika belum aktif,
pengembang menghapus branch lewat tombol **Delete branch** pada PR yang sudah
di-merge, setelah pemeriksaan di atas. Pengaturan ini membutuhkan izin admin.

## Pemeriksaan umum

```bash
composer test
php vendor/bin/pint --test
npm run check:frontend
npm run build
composer validate --strict
git diff --check
```

Pemeriksaan manual cukup login, menu sesuai role, halaman utama, penolakan akses,
form utama, tampilan ponsel, dan logout. Screenshot per skenario maupun workbook
penelitian tidak diwajibkan. Test hak akses server tetap harus dipertahankan.

## Integrasi Dapodik dan e-Tatib

Admin IT dapat menyiapkan konfigurasi melalui Simpan → Uji → Aktifkan. Credential
terenkripsi, audit tersensor, serta fallback data persiapan sementara tersedia.
Driver kedua provider tetap `unavailable`: tidak ada komunikasi production.

Jangan membuat atau mengaktifkan adapter sebelum kontrak resmi lolos
[admission gate](docs/integrations/provider-contract-admission.md).
Lembar kontrak: [Dapodik](docs/integrations/dapodik-contract-discovery.md) dan
[e-Tatib](docs/integrations/etatib-contract-discovery.md).

Deployment: pertahankan driver `unavailable`, isi exact-origin allowlist yang
disahkan, jalankan migration forward-only, lalu cache config/view. Periksa redaksi
credential, audit, `no-store`, dan sinkronisasi yang gagal aman. Rotasi key dan
credential dijelaskan dalam admission gate; jangan mengganti key tanpa prosedur.

## Dokumentasi

- [PRD aktif](docs/requirements/PRD_Aplikasi_BK_v1.1.md)
- [SRS aktif](docs/requirements/SRS_Aplikasi_BK_v1.1.md)
- [Kontrak endpoint dan service](docs/api-contract.md)
- [Admission integrasi provider](docs/integrations/provider-contract-admission.md)
- [Arsip historis privat](https://github.com/Aflahul/sibk-docs-archive)

Plan/spec pekerjaan berada di `docs/superpowers/` dan dibaca hanya bila relevan.
Histori implementasi tersedia melalui Git.
