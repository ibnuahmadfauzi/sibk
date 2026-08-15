# Checklist Audit Repository

Audit ini wajib dilakukan oleh agen pertama yang bekerja pada kode atau ketika struktur proyek berubah. Hasil audit digunakan untuk menyesuaikan aturan tanpa menebak stack.

## Git dan scope

```bash
git branch --show-current
git status --short
git log -5 --oneline --decorate
```

- Pastikan pekerjaan berasal dari `development` atau branch tugas yang berbasis `development`.
- Jangan menimpa perubahan lokal milik anggota tim.

## Peta file

```bash
rg --files -g '!vendor/**' -g '!node_modules/**' | sed -n '1,240p'
find . -maxdepth 2 -type d -not -path './.git*' -not -path './vendor*' -not -path './node_modules*' | sort
```

Catat lokasi frontend, backend, test, dokumentasi, aset, konfigurasi, dan entrypoint.

## Stack dan versi

Periksa file yang tersedia, bukan seluruh daftar secara membabi buta:

- `composer.json` dan lockfile PHP;
- `package.json` dan lockfile JavaScript;
- konfigurasi bundler seperti Vite/Webpack;
- file Bootstrap dan entrypoint CSS/Sass;
- konfigurasi framework, runtime, database, test, formatter, dan linter;
- README serta contoh environment tanpa membuka nilai rahasia.

Catat versi Bootstrap yang benar-benar terpasang. Jangan upgrade versi mayor sebagai bagian audit.

## Frontend

- Temukan template engine atau framework frontend.
- Temukan layout utama, navigasi, komponen bersama, stylesheet, JavaScript, dan route tampilan.
- Cari penggunaan Bootstrap, CSS variables, inline style, nilai warna langsung, dan komponen duplikat.
- Cari pola form validation, table, modal, alert, pagination, loading, serta error.
- Petakan halaman yang sudah ada ke ID `PG-*`.

Contoh pencarian:

```bash
rg -n "bootstrap|--bs-|--sibk-|#[0-9a-fA-F]{3,8}|style=" . -g '!vendor/**' -g '!node_modules/**'
rg -n "modal|offcanvas|form-control|table|pagination|alert|badge" . -g '!vendor/**' -g '!node_modules/**'
```

## Backend

- Temukan route, controller/handler, service/use-case, model/entity, policy/middleware, migration, serializer/resource, dan test.
- Petakan autentikasi, peran, scope murid, penugasan kasus, koordinasi Waka, dan audit.
- Cari endpoint atau query yang dapat melewati pembatasan objek.
- Identifikasi kontrak yang sudah digunakan frontend.

## Perintah proyek

- Ambil perintah instalasi, dev, build, test, lint, format, dan migration dari script repository.
- Jalankan perintah read-only atau aman yang diperlukan untuk mengonfirmasi lingkungan.
- Catat perintah yang benar pada README atau dokumen pengembangan bila belum tersedia.

## Hasil audit

Handoff audit harus berisi:

- stack dan versi dari file repository;
- struktur utama;
- perintah proyek;
- pola yang dipertahankan;
- konflik terhadap baseline dokumentasi;
- risiko keamanan atau data;
- rekomendasi perubahan bertahap, bukan rewrite otomatis.
