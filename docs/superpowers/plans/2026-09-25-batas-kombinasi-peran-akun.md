# Batas Kombinasi Peran Akun — Plan Implementasi

**Status:** Rencana; belum diimplementasikan.

**Tujuan:** Admin IT dan Waka Kesiswaan hanya memiliki satu peran. Guru BK dan Koordinator BK boleh berdiri sendiri atau digabung. Admin IT tidak dapat mengubah peran akunnya sendiri.

**Acuan:** `ACC-01`, `ACC-02`, `AUTH-03`, `AUTH-06`, dan `AUTH-07` pada `docs/requirements/SRS_Aplikasi_BK_v1.1.md`; halaman Kelola Akun pada commit `eb5cf9b`.

## Kombinasi yang diterima

| Pilihan peran tersimpan | Hasil |
| --- | --- |
| Admin IT | Diterima, tanpa peran lain |
| Waka Kesiswaan | Diterima, tanpa peran lain |
| Guru BK | Diterima |
| Koordinator BK | Diterima |
| Guru BK + Koordinator BK | Diterima |
| Kombinasi lain atau kosong | Ditolak |

Aturan berlaku saat membuat akun dan ketika `roles[]` dikirim pada PATCH, termasuk permintaan JSON atau panggilan service langsung. PATCH tanpa `roles[]` tetap boleh mengubah nama, email, atau status menurut policy yang ada. Akun Admin IT yang sedang masuk tidak boleh mengirim perubahan peran untuk dirinya sendiri; perubahan identitas akunnya tetap tersedia dengan menghilangkan `roles[]` dari form. Akun lain yang sudah memiliki kombinasi lama yang tidak valid tidak diubah otomatis; Admin IT lain dapat membetulkannya lewat modal peran.

## Task 1 — Validasi server dan kontrak

**Files:** `app/Services/AccountService.php`, `tests/Feature/AccountManagementTest.php`, `docs/requirements/SRS_Aplikasi_BK_v1.1.md`, `docs/api-contract.md`.

- [ ] Tambahkan satu pemeriksaan kombinasi peran di `AccountService`, dipanggil sebelum `create()` menyimpan akun dan sebelum `update()` menyinkronkan peran. Bandingkan daftar slug yang sudah diurutkan dengan lima kombinasi di tabel; jumlah role aktif yang ditemukan harus sama dengan jumlah slug agar panggilan service langsung juga menolak slug tidak aktif/tidak dikenal. Form Request tetap memberi pesan awal untuk input yang salah.
- [ ] Pada `update()`, bila target adalah actor dan payload memuat `roles`, tolak dengan pesan validasi `Peran akun sendiri tidak dapat diubah.`. PATCH tanpa field itu tetap diproses. Jangan membuat capability, endpoint, tabel, atau migration baru.
- [ ] Tambahkan test terarah: lima kombinasi diterima; Admin IT/Waka dengan peran tambahan ditolak saat POST dan PATCH tanpa mengubah database atau audit; permintaan langsung untuk mengubah peran sendiri ditolak; Admin IT masih dapat mengubah nama/email sendiri tanpa `roles`; Guru BK + Koordinator BK tetap sah. Periksa penolakan JSON 422 dan pesan Bahasa Indonesia.
- [ ] Catat aturan kombinasi serta pengecualian edit diri sendiri pada SRS dan kontrak API. Tidak ada perubahan role historis otomatis.

## Task 2 — Pemilih peran di Kelola Akun

**Files:** `resources/views/pages/admin/users/index.blade.php`, `resources/js/app-dashboard.js`, `tests/Feature/AccountManagementTest.php`.

- [ ] Tampilkan chip peran Admin IT milik akun sendiri sebagai teks tanpa tombol `+` atau pintasan modal peran. Pada modal edit diri sendiri, tampilkan peran sebagai informasi tetap dan jangan kirim input `roles[]`; nama, email, dan status tetap dapat diedit.
- [ ] Pakai pemilih peran yang sudah ada. Saat Admin IT atau Waka dipilih, opsi lain dinonaktifkan; saat Guru BK atau Koordinator dipilih, keduanya tetap dapat dipilih bersama dan dua peran eksklusif dinonaktifkan. Membatalkan chip pilihan mengaktifkan kembali opsi yang cocok. Jangan menghapus pilihan pengguna secara diam-diam.
- [ ] Bila akun lama sudah memiliki kombinasi tidak valid, tampilkan peringatan singkat di modal, biarkan Admin IT lain melepas chip yang salah, dan aktifkan tombol simpan hanya setelah kombinasi valid. Validasi server tetap menjadi penentu akhir.
- [ ] Verifikasi interaksi keyboard, modal tambah/edit/peran cepat, kegagalan validasi yang mengembalikan pilihan sementara, serta tampilan kecil. Jalankan `node --check resources/js/app-dashboard.js`, test akun terarah, Pint terarah, dan `git diff --check`. Full suite/build mengikuti `AGENTS.md` dan dijalankan pengguna.

## Batas sederhana

Empat role yang ada cukup untuk aturan ini. Tidak perlu matriks izin generik, konfigurasi kombinasi, migrasi, atau perubahan policy akun. Bila role baru kelak disetujui, perlu keputusan kombinasi baru sebelum memasukkannya ke allowlist.
