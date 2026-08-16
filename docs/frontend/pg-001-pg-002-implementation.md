---
document_id: frontend-pg-001-pg-002-implementation
status: working
last_updated: 2026-08-15
---

# Implementasi PG-001 dan PG-002

Dokumen ini mencatat keadaan frontend `PG-001` Login dan `PG-002` Dashboard pada tahap data statis. Implementasi tidak membuat kontrak autentikasi, otorisasi, Dapodik, atau e-Tatib final.

## Keterlacakan

- `PG-001`: `AUTH-01`, `AUTH-03`, `IT-001` sampai `IT-003`, dan `AC-001`.
- `PG-002`: `AUTH-02`, `AUTH-05`, `DASH-01` sampai `DASH-03`, `IT-004` sampai `IT-010`, dan `AC-002`.
- Nama frame kanonik: `PG-001 — Login` dan `PG-002 — Dashboard`.
- Nama ekspor: `PG-001-login.png` dan `PG-002-dashboard.png`.

Ekspor wireframe belum tersedia pada `docs/design/wireframes/`. Sumber Penpot juga belum dapat diverifikasi dari lingkungan pengembangan ini. Karena itu, implementasi memakai inventaris UI, SRS, access matrix, pedoman UX, dan struktur prototipe sebelumnya sebagai baseline. Cakupan informasi wireframe belum boleh dinyatakan terverifikasi sampai frame Penpot atau ekspornya diperiksa.

## Fondasi yang berlaku

- `_token-values.scss` menjadi sumber nilai Sass untuk override Bootstrap.
- `_tokens.scss` menerbitkan nilai yang sama sebagai CSS custom properties.
- App shell memakai offcanvas Bootstrap pada layar sempit dan sidebar tetap pada desktop.
- Logo, empty state, skip link, fokus, dan reduced motion memakai pola bersama.
- Data pratinjau berada pada `config/sibk-preview.php` dan seluruh identitas murid bersifat sintetis.

## PG-001 Login

Halaman menyediakan:

- nama pengguna/email dan kata sandi wajib;
- toggle visibilitas kata sandi dengan nama aksesibel;
- validasi dekat field dan ringkasan error;
- state loading, kredensial gagal umum, gangguan sistem, dan berhasil;
- pesan yang tidak mengungkap apakah akun tertentu tersedia;
- penjelasan bahwa data operasional tidak dimuat sebelum autentikasi;
- layout desktop dua area dan layout mobile satu area.

State dapat diperiksa melalui:

- `/login?auth_state=error`
- `/login?auth_state=system-error`
- `/login?auth_state=success`

Form selalu disimulasikan di browser. Mode berhasil tidak membuat sesi dan tidak boleh dianggap sebagai autentikasi server.

## PG-002 Dashboard

Dashboard menyediakan:

- filter tahun ajaran aktif;
- kartu jumlah murid, pelanggaran, kasus, dan jadwal;
- waktu sinkronisasi e-Tatib;
- kasus belum ditindaklanjuti atau kasus terkoordinasi;
- jadwal tindak lanjut atau koordinasi terdekat;
- pelanggaran menurut kategori dan kelas;
- aktivitas yang dibatasi menurut peran;
- variasi Guru BK, Koordinator BK, dan Waka Kesiswaan;
- penanda hanya-baca serta penghilangan aksi mutasi pada variasi Waka;
- state default, loading, kosong, dan gagal.

Peran dan state dapat diperiksa dengan parameter berikut:

```text
/dashboard?role=guru&state=default
/dashboard?role=koordinator&state=loading
/dashboard?role=waka&state=empty
/dashboard?role=guru&state=error
```

Nilai `role`, `state`, dan `year` dibatasi dengan allowlist. Parameter tidak memperluas kewenangan karena seluruh halaman masih merupakan fixture frontend.

## Perbedaan dari prototipe sebelumnya

- Komposisi login dipertahankan sebagai panel identitas dan form, tetapi hierarki, microcopy, state, serta aksesibilitas diperkuat.
- Dashboard menambahkan konteks tahun ajaran, scope, waktu sinkronisasi, ringkasan kategori/kelas, kasus prioritas, dan variasi peran yang diwajibkan inventaris.
- Sidebar mobile memakai offcanvas Bootstrap, bukan toggle global dan event handler inline.
- Warna langsung pada template dipindahkan ke token; ikon memakai `currentColor`.
- Tautan ke halaman yang belum diimplementasikan ditampilkan sebagai item terencana nonaktif agar tidak mengarang route atau kontrak halaman berikutnya.

## Batas integrasi

- Server tetap harus memverifikasi kredensial dan membuat sesi sah.
- Server tetap harus menerapkan scope dan otorisasi objek pada hitungan serta tautan Dashboard.
- Detail kasus, daftar murid, laporan, notifikasi, akun, dan akses ditolak dikerjakan pada ID halaman masing-masing.
- Fixture ini tidak boleh diubah menjadi data produksi atau dipakai sebagai sumber kebijakan akses.

## Pemeriksaan

Jalankan:

```powershell
npm.cmd run check:frontend
npm.cmd run build
```

Test Laravel memerlukan PHP yang memenuhi dependency Composer. Lingkungan yang hanya memiliki PHP 8.2 tidak dapat menjalankan test Laravel 13 pada repository ini.

Hasil pemeriksaan 15 Agustus 2026:

- `npm.cmd run check:frontend`: lulus;
- `npm.cmd run build`: lulus;
- seluruh test Laravel melalui image PHP 8.5 dari `Dockerfile`: 15 test dan 51 assertion lulus;
- audit browser desktop pada Login dan Dashboard Guru BK: tidak ditemukan overlap atau clipping;
- emulasi viewport 390 piksel pada Login dan Dashboard Waka: `scrollWidth` sama dengan lebar viewport;
- validasi kosong Login memindahkan fokus ke field pertama, menandai kedua field, dan menampilkan ringkasan;
- state loading Login menonaktifkan tombol serta menampilkan spinner; mode berhasil menjaga nilai kata sandi tanpa pemangkasan;
- offcanvas Dashboard dapat dibuka, mengunci scroll, ditutup dengan Escape, dan mengembalikan fokus ke tombol pemicu.
